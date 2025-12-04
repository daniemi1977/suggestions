<?php
/**
 * Event Calendar Renderer
 *
 * @package WP_Event_Calendar_Pro
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class WECP_Event_Calendar {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Shortcodes
        add_shortcode('wecp_calendar', array($this, 'calendar_shortcode'));
        add_shortcode('wecp_events_list', array($this, 'events_list_shortcode'));
        add_shortcode('wecp_events_tile', array($this, 'events_tile_shortcode'));

        // AJAX handlers
        add_action('wp_ajax_wecp_get_events', array($this, 'ajax_get_events'));
        add_action('wp_ajax_nopriv_wecp_get_events', array($this, 'ajax_get_events'));
        add_action('wp_ajax_wecp_get_event_details', array($this, 'ajax_get_event_details'));
        add_action('wp_ajax_nopriv_wecp_get_event_details', array($this, 'ajax_get_event_details'));
    }

    /**
     * Calendar shortcode
     * Usage: [wecp_calendar view="month" category="concerts" limit="10"]
     */
    public function calendar_shortcode($atts) {
        $atts = shortcode_atts(array(
            'view' => 'month',
            'category' => '',
            'tag' => '',
            'limit' => 100,
            'show_past' => 'no',
            'event_color' => '',
            'interaction' => 'lightbox', // lightbox, slide, page, none
        ), $atts);

        ob_start();
        $this->render_calendar($atts);
        return ob_get_clean();
    }

    /**
     * Events list shortcode
     */
    public function events_list_shortcode($atts) {
        $atts = shortcode_atts(array(
            'category' => '',
            'tag' => '',
            'limit' => 10,
            'show_past' => 'no',
        ), $atts);

        ob_start();
        $this->render_list_view($atts);
        return ob_get_clean();
    }

    /**
     * Events tile shortcode
     */
    public function events_tile_shortcode($atts) {
        $atts = shortcode_atts(array(
            'category' => '',
            'tag' => '',
            'limit' => 12,
            'columns' => 3,
            'show_past' => 'no',
        ), $atts);

        ob_start();
        $this->render_tile_view($atts);
        return ob_get_clean();
    }

    /**
     * Render calendar view
     */
    private function render_calendar($atts) {
        $view = $atts['view'];
        $current_month = isset($_GET['month']) ? intval($_GET['month']) : date('n');
        $current_year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
        $current_week = isset($_GET['week']) ? intval($_GET['week']) : date('W');
        $current_day = isset($_GET['day']) ? sanitize_text_field($_GET['day']) : date('Y-m-d');

        ?>
        <div class="wecp-calendar-wrapper wecp-view-<?php echo esc_attr($view); ?>" data-view="<?php echo esc_attr($view); ?>" data-interaction="<?php echo esc_attr($atts['interaction']); ?>">
            <!-- View Switcher -->
            <div class="wecp-view-switcher">
                <button class="wecp-view-btn <?php echo $view === 'month' ? 'active' : ''; ?>" data-view="month">
                    <?php _e('Month', 'wp-event-calendar-pro'); ?>
                </button>
                <button class="wecp-view-btn <?php echo $view === 'week' ? 'active' : ''; ?>" data-view="week">
                    <?php _e('Week', 'wp-event-calendar-pro'); ?>
                </button>
                <button class="wecp-view-btn <?php echo $view === 'day' ? 'active' : ''; ?>" data-view="day">
                    <?php _e('Day', 'wp-event-calendar-pro'); ?>
                </button>
                <button class="wecp-view-btn <?php echo $view === 'agenda' ? 'active' : ''; ?>" data-view="agenda">
                    <?php _e('Agenda', 'wp-event-calendar-pro'); ?>
                </button>
            </div>

            <!-- Calendar Header -->
            <div class="wecp-calendar-header">
                <button class="wecp-nav-prev" data-view="<?php echo esc_attr($view); ?>" data-month="<?php echo $current_month; ?>" data-year="<?php echo $current_year; ?>" data-week="<?php echo $current_week; ?>" data-day="<?php echo esc_attr($current_day); ?>">
                    <span class="dashicons dashicons-arrow-left-alt2"></span>
                </button>
                <h2 class="wecp-calendar-title">
                    <?php $this->render_calendar_title($view, $current_month, $current_year, $current_week, $current_day); ?>
                </h2>
                <button class="wecp-nav-next" data-view="<?php echo esc_attr($view); ?>" data-month="<?php echo $current_month; ?>" data-year="<?php echo $current_year; ?>" data-week="<?php echo $current_week; ?>" data-day="<?php echo esc_attr($current_day); ?>">
                    <span class="dashicons dashicons-arrow-right-alt2"></span>
                </button>
                <button class="wecp-today-btn" title="<?php esc_attr_e('Go to Today', 'wp-event-calendar-pro'); ?>">
                    <?php _e('Today', 'wp-event-calendar-pro'); ?>
                </button>
            </div>

            <!-- Calendar Content -->
            <div class="wecp-calendar-content">
                <?php
                switch ($view) {
                    case 'week':
                        $this->render_week_view($current_week, $current_year, $atts);
                        break;
                    case 'day':
                        $this->render_day_view($current_day, $atts);
                        break;
                    case 'agenda':
                        $this->render_agenda_view($current_month, $current_year, $atts);
                        break;
                    case 'month':
                    default:
                        $this->render_calendar_grid($current_month, $current_year, $atts);
                        break;
                }
                ?>
            </div>

            <!-- Event Lightbox -->
            <div id="wecp-lightbox" class="wecp-lightbox" style="display: none;">
                <div class="wecp-lightbox-overlay"></div>
                <div class="wecp-lightbox-content">
                    <button class="wecp-lightbox-close">×</button>
                    <div class="wecp-lightbox-body"></div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render calendar title based on view
     */
    private function render_calendar_title($view, $month, $year, $week, $day) {
        switch ($view) {
            case 'week':
                $week_start = new DateTime();
                $week_start->setISODate($year, $week);
                $week_end = clone $week_start;
                $week_end->modify('+6 days');

                printf(
                    '<span class="week-range">%s - %s</span>',
                    $week_start->format('M j'),
                    $week_end->format('M j, Y')
                );
                break;

            case 'day':
                $day_obj = new DateTime($day);
                printf(
                    '<span class="day-title">%s</span>',
                    $day_obj->format('l, F j, Y')
                );
                break;

            case 'agenda':
                printf(
                    '<span class="agenda-title">%s</span>',
                    date_i18n('F Y', mktime(0, 0, 0, $month, 1, $year))
                );
                break;

            case 'month':
            default:
                printf(
                    '<span class="month-name">%s</span>',
                    date_i18n('F Y', mktime(0, 0, 0, $month, 1, $year))
                );
                break;
        }
    }

    /**
     * Render calendar grid
     */
    private function render_calendar_grid($month, $year, $atts) {
        $first_day = mktime(0, 0, 0, $month, 1, $year);
        $days_in_month = date('t', $first_day);
        $day_of_week = date('w', $first_day);

        // Get events for this month
        $events = $this->get_events_for_month($month, $year, $atts);

        ?>
        <div class="wecp-calendar-head">
            <?php
            $day_names = array(
                __('Sun', 'wp-event-calendar-pro'),
                __('Mon', 'wp-event-calendar-pro'),
                __('Tue', 'wp-event-calendar-pro'),
                __('Wed', 'wp-event-calendar-pro'),
                __('Thu', 'wp-event-calendar-pro'),
                __('Fri', 'wp-event-calendar-pro'),
                __('Sat', 'wp-event-calendar-pro'),
            );

            foreach ($day_names as $day) {
                echo '<div class="wecp-calendar-day-name">' . esc_html($day) . '</div>';
            }
            ?>
        </div>
        <div class="wecp-calendar-body">
            <?php
            // Empty cells before first day
            for ($i = 0; $i < $day_of_week; $i++) {
                echo '<div class="wecp-calendar-cell wecp-empty"></div>';
            }

            // Days of month
            for ($day = 1; $day <= $days_in_month; $day++) {
                $date = sprintf('%04d-%02d-%02d', $year, $month, $day);
                $is_today = ($date === date('Y-m-d'));
                $day_events = isset($events[$date]) ? $events[$date] : array();

                $classes = array('wecp-calendar-cell');
                if ($is_today) {
                    $classes[] = 'wecp-today';
                }
                if (!empty($day_events)) {
                    $classes[] = 'wecp-has-events';
                }

                echo '<div class="' . esc_attr(implode(' ', $classes)) . '" data-date="' . esc_attr($date) . '">';
                echo '<div class="wecp-cell-date">' . $day . '</div>';

                if (!empty($day_events)) {
                    echo '<div class="wecp-cell-events">';
                    foreach ($day_events as $event) {
                        $color = get_post_meta($event->ID, '_wecp_event_color', true) ?: '#3498db';
                        echo '<div class="wecp-event-dot" style="background-color: ' . esc_attr($color) . '" data-event-id="' . esc_attr($event->ID) . '" title="' . esc_attr($event->post_title) . '"></div>';
                    }
                    echo '</div>';
                }

                echo '</div>';
            }

            // Fill remaining cells
            $total_cells = $day_of_week + $days_in_month;
            $remaining = (7 - ($total_cells % 7)) % 7;
            for ($i = 0; $i < $remaining; $i++) {
                echo '<div class="wecp-calendar-cell wecp-empty"></div>';
            }
            ?>
        </div>
        <?php
    }

    /**
     * Render week view
     */
    private function render_week_view($week, $year, $atts) {
        $week_start = new DateTime();
        $week_start->setISODate($year, $week);

        $day_names = array(
            __('Sunday', 'wp-event-calendar-pro'),
            __('Monday', 'wp-event-calendar-pro'),
            __('Tuesday', 'wp-event-calendar-pro'),
            __('Wednesday', 'wp-event-calendar-pro'),
            __('Thursday', 'wp-event-calendar-pro'),
            __('Friday', 'wp-event-calendar-pro'),
            __('Saturday', 'wp-event-calendar-pro'),
        );

        ?>
        <div class="wecp-week-view">
            <!-- Time column labels -->
            <div class="wecp-week-header">
                <div class="wecp-time-col-label"></div>
                <?php
                for ($i = 0; $i < 7; $i++) {
                    $day = clone $week_start;
                    $day->modify("+{$i} days");
                    $is_today = ($day->format('Y-m-d') === date('Y-m-d'));
                    ?>
                    <div class="wecp-week-day-header <?php echo $is_today ? 'wecp-today' : ''; ?>">
                        <div class="wecp-day-name"><?php echo esc_html($day_names[$day->format('w')]); ?></div>
                        <div class="wecp-day-date"><?php echo $day->format('j'); ?></div>
                    </div>
                    <?php
                }
                ?>
            </div>

            <!-- Week grid with time slots -->
            <div class="wecp-week-grid">
                <?php
                // Get events for this week
                $week_events = array();
                for ($i = 0; $i < 7; $i++) {
                    $day = clone $week_start;
                    $day->modify("+{$i} days");
                    $date = $day->format('Y-m-d');
                    $day_events = $this->get_events_for_date($date, $atts);
                    $week_events[$date] = $day_events;
                }

                // Render time slots (24 hours)
                for ($hour = 0; $hour < 24; $hour++) {
                    ?>
                    <div class="wecp-week-row" data-hour="<?php echo $hour; ?>">
                        <div class="wecp-time-label">
                            <?php echo sprintf('%02d:00', $hour); ?>
                        </div>
                        <?php
                        for ($i = 0; $i < 7; $i++) {
                            $day = clone $week_start;
                            $day->modify("+{$i} days");
                            $date = $day->format('Y-m-d');
                            $is_today = ($date === date('Y-m-d'));

                            // Filter events for this hour
                            $hour_events = array();
                            if (!empty($week_events[$date])) {
                                foreach ($week_events[$date] as $event) {
                                    $start_time = get_post_meta($event->ID, '_wecp_start_time', true);
                                    if ($start_time) {
                                        $event_hour = intval(substr($start_time, 0, 2));
                                        if ($event_hour === $hour) {
                                            $hour_events[] = $event;
                                        }
                                    }
                                }
                            }

                            $classes = array('wecp-week-cell');
                            if ($is_today) $classes[] = 'wecp-today';
                            if (!empty($hour_events)) $classes[] = 'wecp-has-events';
                            ?>
                            <div class="<?php echo esc_attr(implode(' ', $classes)); ?>" data-date="<?php echo esc_attr($date); ?>" data-hour="<?php echo $hour; ?>">
                                <?php
                                foreach ($hour_events as $event) {
                                    $color = get_post_meta($event->ID, '_wecp_event_color', true) ?: '#3498db';
                                    $start_time = get_post_meta($event->ID, '_wecp_start_time', true);
                                    ?>
                                    <div class="wecp-week-event" style="border-left-color: <?php echo esc_attr($color); ?>;" data-event-id="<?php echo esc_attr($event->ID); ?>" title="<?php echo esc_attr($event->post_title); ?>">
                                        <div class="wecp-event-time"><?php echo esc_html($start_time); ?></div>
                                        <div class="wecp-event-title"><?php echo esc_html($event->post_title); ?></div>
                                    </div>
                                    <?php
                                }
                                ?>
                            </div>
                            <?php
                        }
                        ?>
                    </div>
                    <?php
                }
                ?>
            </div>
        </div>
        <?php
    }

    /**
     * Render day view
     */
    private function render_day_view($date, $atts) {
        $day = new DateTime($date);
        $events = $this->get_events_for_date($date, $atts);

        ?>
        <div class="wecp-day-view">
            <!-- Time slots for the day -->
            <div class="wecp-day-timeline">
                <?php
                for ($hour = 0; $hour < 24; $hour++) {
                    // Filter events for this hour
                    $hour_events = array();
                    foreach ($events as $event) {
                        $start_time = get_post_meta($event->ID, '_wecp_start_time', true);
                        $all_day = get_post_meta($event->ID, '_wecp_all_day', true);

                        if ($all_day) {
                            // All-day events go in hour 0
                            if ($hour === 0) {
                                $hour_events[] = $event;
                            }
                        } elseif ($start_time) {
                            $event_hour = intval(substr($start_time, 0, 2));
                            if ($event_hour === $hour) {
                                $hour_events[] = $event;
                            }
                        }
                    }

                    $has_events = !empty($hour_events);
                    ?>
                    <div class="wecp-day-hour <?php echo $has_events ? 'wecp-has-events' : ''; ?>" data-hour="<?php echo $hour; ?>">
                        <div class="wecp-hour-label">
                            <?php echo sprintf('%02d:00', $hour); ?>
                        </div>
                        <div class="wecp-hour-events">
                            <?php
                            foreach ($hour_events as $event) {
                                $color = get_post_meta($event->ID, '_wecp_event_color', true) ?: '#3498db';
                                $start_time = get_post_meta($event->ID, '_wecp_start_time', true);
                                $end_time = get_post_meta($event->ID, '_wecp_end_time', true);
                                $venue = get_post_meta($event->ID, '_wecp_venue_name', true);
                                $all_day = get_post_meta($event->ID, '_wecp_all_day', true);
                                ?>
                                <div class="wecp-day-event" style="border-left-color: <?php echo esc_attr($color); ?>;" data-event-id="<?php echo esc_attr($event->ID); ?>">
                                    <div class="wecp-event-time">
                                        <?php
                                        if ($all_day) {
                                            _e('All Day', 'wp-event-calendar-pro');
                                        } else {
                                            echo esc_html($start_time);
                                            if ($end_time) {
                                                echo ' - ' . esc_html($end_time);
                                            }
                                        }
                                        ?>
                                    </div>
                                    <div class="wecp-event-title"><?php echo esc_html($event->post_title); ?></div>
                                    <?php if ($venue): ?>
                                        <div class="wecp-event-venue">
                                            <span class="dashicons dashicons-location"></span>
                                            <?php echo esc_html($venue); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <?php
                            }
                            ?>
                        </div>
                    </div>
                    <?php
                }
                ?>
            </div>

            <?php if (empty($events)): ?>
                <div class="wecp-no-events">
                    <p><?php _e('No events scheduled for this day.', 'wp-event-calendar-pro'); ?></p>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Render agenda view (list of upcoming events)
     */
    private function render_agenda_view($month, $year, $atts) {
        // Get all events for the month, grouped by date
        $events = $this->get_events_for_month($month, $year, $atts);

        if (empty($events)) {
            echo '<div class="wecp-no-events"><p>' . __('No events found for this month.', 'wp-event-calendar-pro') . '</p></div>';
            return;
        }

        // Sort events by date
        ksort($events);

        ?>
        <div class="wecp-agenda-view">
            <?php
            foreach ($events as $date => $day_events) {
                $day = new DateTime($date);
                $is_today = ($date === date('Y-m-d'));
                ?>
                <div class="wecp-agenda-date <?php echo $is_today ? 'wecp-today' : ''; ?>" data-date="<?php echo esc_attr($date); ?>">
                    <div class="wecp-agenda-date-header">
                        <div class="wecp-agenda-day-number"><?php echo $day->format('j'); ?></div>
                        <div class="wecp-agenda-day-info">
                            <div class="wecp-agenda-day-name"><?php echo $day->format('l'); ?></div>
                            <div class="wecp-agenda-month-name"><?php echo $day->format('F Y'); ?></div>
                        </div>
                        <div class="wecp-agenda-event-count">
                            <?php printf(_n('%d event', '%d events', count($day_events), 'wp-event-calendar-pro'), count($day_events)); ?>
                        </div>
                    </div>

                    <div class="wecp-agenda-events">
                        <?php foreach ($day_events as $event):
                            $color = get_post_meta($event->ID, '_wecp_event_color', true) ?: '#3498db';
                            $start_time = get_post_meta($event->ID, '_wecp_start_time', true);
                            $end_time = get_post_meta($event->ID, '_wecp_end_time', true);
                            $all_day = get_post_meta($event->ID, '_wecp_all_day', true);
                            $venue = get_post_meta($event->ID, '_wecp_venue_name', true);
                            $categories = wp_get_post_terms($event->ID, 'wecp_event_category');
                            ?>
                            <div class="wecp-agenda-event" style="border-left-color: <?php echo esc_attr($color); ?>;" data-event-id="<?php echo esc_attr($event->ID); ?>">
                                <div class="wecp-agenda-event-time">
                                    <?php
                                    if ($all_day) {
                                        _e('All Day', 'wp-event-calendar-pro');
                                    } else {
                                        echo esc_html($start_time);
                                        if ($end_time) {
                                            echo ' - ' . esc_html($end_time);
                                        }
                                    }
                                    ?>
                                </div>
                                <div class="wecp-agenda-event-content">
                                    <h3 class="wecp-agenda-event-title"><?php echo esc_html($event->post_title); ?></h3>

                                    <?php if (!empty($categories)): ?>
                                        <div class="wecp-agenda-event-categories">
                                            <?php foreach ($categories as $cat): ?>
                                                <span class="wecp-event-category"><?php echo esc_html($cat->name); ?></span>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>

                                    <?php if ($venue): ?>
                                        <div class="wecp-agenda-event-venue">
                                            <span class="dashicons dashicons-location"></span>
                                            <?php echo esc_html($venue); ?>
                                        </div>
                                    <?php endif; ?>

                                    <?php if ($event->post_excerpt): ?>
                                        <div class="wecp-agenda-event-excerpt">
                                            <?php echo esc_html($event->post_excerpt); ?>
                                        </div>
                                    <?php endif; ?>

                                    <div class="wecp-agenda-event-actions">
                                        <button class="wecp-event-details-btn" data-event-id="<?php echo esc_attr($event->ID); ?>">
                                            <?php _e('View Details', 'wp-event-calendar-pro'); ?>
                                        </button>

                                        <?php
                                        // Check if booking is enabled
                                        $enable_booking = get_post_meta($event->ID, '_wecp_enable_booking', true);
                                        if ($enable_booking && class_exists('WooCommerce')) {
                                            $product_id = get_post_meta($event->ID, '_wecp_product_id', true);
                                            if ($product_id) {
                                                $product = wc_get_product($product_id);
                                                if ($product && $product->is_in_stock()) {
                                                    ?>
                                                    <button class="wecp-book-btn" data-product-id="<?php echo esc_attr($product_id); ?>">
                                                        <?php _e('Book Now', 'wp-event-calendar-pro'); ?>
                                                    </button>
                                                    <?php
                                                } else {
                                                    ?>
                                                    <span class="wecp-sold-out"><?php _e('Sold Out', 'wp-event-calendar-pro'); ?></span>
                                                    <?php
                                                }
                                            }
                                        }
                                        ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php
            }
            ?>
        </div>
        <?php
    }

    /**
     * Get events for a specific date
     */
    private function get_events_for_date($date, $atts) {
        $args = array(
            'post_type' => 'wecp_event',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'meta_query' => array(
                array(
                    'key' => '_wecp_start_date',
                    'value' => $date,
                    'compare' => '=',
                    'type' => 'DATE',
                ),
            ),
        );

        // Add category filter
        if (!empty($atts['category'])) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'wecp_event_category',
                    'field' => 'slug',
                    'terms' => $atts['category'],
                ),
            );
        }

        $query = new WP_Query($args);
        return $query->posts;
    }

    /**
     * Render list view
     */
    private function render_list_view($atts) {
        $events = $this->get_events($atts);

        if (empty($events)) {
            echo '<p>' . __('No events found.', 'wp-event-calendar-pro') . '</p>';
            return;
        }

        ?>
        <div class="wecp-events-list">
            <?php foreach ($events as $event): ?>
                <?php $this->render_event_card($event, 'list'); ?>
            <?php endforeach; ?>
        </div>
        <?php
    }

    /**
     * Render tile view
     */
    private function render_tile_view($atts) {
        $events = $this->get_events($atts);

        if (empty($events)) {
            echo '<p>' . __('No events found.', 'wp-event-calendar-pro') . '</p>';
            return;
        }

        $columns = isset($atts['columns']) ? intval($atts['columns']) : 3;

        ?>
        <div class="wecp-events-tiles" data-columns="<?php echo esc_attr($columns); ?>">
            <?php foreach ($events as $event): ?>
                <?php $this->render_event_card($event, 'tile'); ?>
            <?php endforeach; ?>
        </div>
        <?php
    }

    /**
     * Render event card
     */
    private function render_event_card($event, $layout = 'list') {
        $start_date = get_post_meta($event->ID, '_wecp_start_date', true);
        $start_time = get_post_meta($event->ID, '_wecp_start_time', true);
        $venue_name = get_post_meta($event->ID, '_wecp_venue_name', true);
        $event_color = get_post_meta($event->ID, '_wecp_event_color', true) ?: '#3498db';
        $enable_booking = get_post_meta($event->ID, '_wecp_enable_booking', true);
        $product_id = get_post_meta($event->ID, '_wecp_wc_product_id', true);

        ?>
        <div class="wecp-event-card wecp-layout-<?php echo esc_attr($layout); ?>" data-event-id="<?php echo esc_attr($event->ID); ?>" style="border-left-color: <?php echo esc_attr($event_color); ?>">
            <?php if (has_post_thumbnail($event->ID)): ?>
                <div class="wecp-event-thumbnail">
                    <?php echo get_the_post_thumbnail($event->ID, 'medium'); ?>
                </div>
            <?php endif; ?>

            <div class="wecp-event-content">
                <div class="wecp-event-date">
                    <span class="wecp-date-day"><?php echo date_i18n('d', strtotime($start_date)); ?></span>
                    <span class="wecp-date-month"><?php echo date_i18n('M', strtotime($start_date)); ?></span>
                </div>

                <div class="wecp-event-details">
                    <h3 class="wecp-event-title"><?php echo esc_html($event->post_title); ?></h3>

                    <?php if ($start_time): ?>
                        <div class="wecp-event-time">
                            <span class="dashicons dashicons-clock"></span>
                            <?php echo esc_html($start_time); ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($venue_name): ?>
                        <div class="wecp-event-venue">
                            <span class="dashicons dashicons-location"></span>
                            <?php echo esc_html($venue_name); ?>
                        </div>
                    <?php endif; ?>

                    <div class="wecp-event-excerpt">
                        <?php echo wp_trim_words($event->post_excerpt, 20); ?>
                    </div>

                    <div class="wecp-event-actions">
                        <button class="wecp-btn wecp-btn-details" data-event-id="<?php echo esc_attr($event->ID); ?>">
                            <?php _e('View Details', 'wp-event-calendar-pro'); ?>
                        </button>

                        <?php if ($enable_booking === '1' && $product_id): ?>
                            <button class="wecp-btn wecp-btn-book" data-event-id="<?php echo esc_attr($event->ID); ?>" data-product-id="<?php echo esc_attr($product_id); ?>">
                                <?php _e('Book Now', 'wp-event-calendar-pro'); ?>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Get events for month
     */
    private function get_events_for_month($month, $year, $atts) {
        $start_date = sprintf('%04d-%02d-01', $year, $month);
        $end_date = date('Y-m-t', strtotime($start_date));

        $args = array(
            'post_type' => 'wecp_event',
            'posts_per_page' => -1,
            'meta_query' => array(
                array(
                    'key' => '_wecp_start_date',
                    'value' => array($start_date, $end_date),
                    'compare' => 'BETWEEN',
                    'type' => 'DATE',
                ),
            ),
            'orderby' => 'meta_value',
            'order' => 'ASC',
        );

        // Filter by category
        if (!empty($atts['category'])) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'wecp_event_category',
                    'field' => 'slug',
                    'terms' => $atts['category'],
                ),
            );
        }

        $query = new WP_Query($args);
        $events_by_date = array();

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $event_id = get_the_ID();
                $event_date = get_post_meta($event_id, '_wecp_start_date', true);

                if (!isset($events_by_date[$event_date])) {
                    $events_by_date[$event_date] = array();
                }

                $events_by_date[$event_date][] = get_post($event_id);
            }
            wp_reset_postdata();
        }

        return $events_by_date;
    }

    /**
     * Get events
     */
    private function get_events($atts) {
        $args = array(
            'post_type' => 'wecp_event',
            'posts_per_page' => intval($atts['limit']),
            'meta_key' => '_wecp_start_date',
            'orderby' => 'meta_value',
            'order' => 'ASC',
        );

        // Show past events
        if ($atts['show_past'] === 'no') {
            $args['meta_query'] = array(
                array(
                    'key' => '_wecp_start_date',
                    'value' => date('Y-m-d'),
                    'compare' => '>=',
                    'type' => 'DATE',
                ),
            );
        }

        // Filter by category
        if (!empty($atts['category'])) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'wecp_event_category',
                    'field' => 'slug',
                    'terms' => $atts['category'],
                ),
            );
        }

        $query = new WP_Query($args);
        return $query->posts;
    }

    /**
     * AJAX: Get events for date range
     */
    public function ajax_get_events() {
        check_ajax_referer('wecp_nonce', 'nonce');

        $month = isset($_POST['month']) ? intval($_POST['month']) : date('n');
        $year = isset($_POST['year']) ? intval($_POST['year']) : date('Y');

        $events = $this->get_events_for_month($month, $year, array());

        wp_send_json_success($events);
    }

    /**
     * AJAX: Get event details
     */
    public function ajax_get_event_details() {
        check_ajax_referer('wecp_nonce', 'nonce');

        $event_id = isset($_POST['event_id']) ? intval($_POST['event_id']) : 0;

        if (!$event_id) {
            wp_send_json_error(array('message' => __('Invalid event ID', 'wp-event-calendar-pro')));
        }

        $event = get_post($event_id);

        if (!$event) {
            wp_send_json_error(array('message' => __('Event not found', 'wp-event-calendar-pro')));
        }

        // Get event meta
        $start_date = get_post_meta($event_id, '_wecp_start_date', true);
        $end_date = get_post_meta($event_id, '_wecp_end_date', true);
        $start_time = get_post_meta($event_id, '_wecp_start_time', true);
        $end_time = get_post_meta($event_id, '_wecp_end_time', true);
        $venue_name = get_post_meta($event_id, '_wecp_venue_name', true);
        $venue_address = get_post_meta($event_id, '_wecp_venue_address', true);
        $enable_booking = get_post_meta($event_id, '_wecp_enable_booking', true);
        $product_id = get_post_meta($event_id, '_wecp_wc_product_id', true);

        ob_start();
        ?>
        <div class="wecp-event-details-modal">
            <?php if (has_post_thumbnail($event_id)): ?>
                <div class="wecp-modal-image">
                    <?php echo get_the_post_thumbnail($event_id, 'large'); ?>
                </div>
            <?php endif; ?>

            <h2><?php echo esc_html($event->post_title); ?></h2>

            <div class="wecp-modal-meta">
                <div class="wecp-meta-item">
                    <span class="dashicons dashicons-calendar"></span>
                    <strong><?php _e('Date:', 'wp-event-calendar-pro'); ?></strong>
                    <?php echo date_i18n(get_option('date_format'), strtotime($start_date)); ?>
                    <?php if ($end_date && $end_date !== $start_date): ?>
                        - <?php echo date_i18n(get_option('date_format'), strtotime($end_date)); ?>
                    <?php endif; ?>
                </div>

                <?php if ($start_time): ?>
                <div class="wecp-meta-item">
                    <span class="dashicons dashicons-clock"></span>
                    <strong><?php _e('Time:', 'wp-event-calendar-pro'); ?></strong>
                    <?php echo esc_html($start_time); ?>
                    <?php if ($end_time): ?>
                        - <?php echo esc_html($end_time); ?>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <?php if ($venue_name): ?>
                <div class="wecp-meta-item">
                    <span class="dashicons dashicons-location"></span>
                    <strong><?php _e('Location:', 'wp-event-calendar-pro'); ?></strong>
                    <?php echo esc_html($venue_name); ?>
                    <?php if ($venue_address): ?>
                        <br><?php echo esc_html($venue_address); ?>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>

            <div class="wecp-modal-content">
                <?php echo wpautop($event->post_content); ?>
            </div>

            <?php if ($enable_booking === '1' && $product_id): ?>
            <div class="wecp-modal-booking">
                <button class="wecp-btn wecp-btn-book-large" data-event-id="<?php echo esc_attr($event_id); ?>" data-product-id="<?php echo esc_attr($product_id); ?>">
                    <?php _e('Book This Event', 'wp-event-calendar-pro'); ?>
                </button>
            </div>
            <?php endif; ?>
        </div>
        <?php
        $html = ob_get_clean();

        wp_send_json_success(array('html' => $html));
    }
}
