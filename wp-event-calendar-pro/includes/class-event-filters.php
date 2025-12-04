<?php
/**
 * Event Filters System
 * Advanced filtering for events
 *
 * @package WP_Event_Calendar_Pro
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class WECP_Event_Filters {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_shortcode('wecp_event_filters', array($this, 'filters_shortcode'));
        add_action('wp_ajax_wecp_filter_events', array($this, 'ajax_filter_events'));
        add_action('wp_ajax_nopriv_wecp_filter_events', array($this, 'ajax_filter_events'));
        add_action('widgets_init', array($this, 'register_filter_widget'));
    }

    /**
     * Event filters shortcode
     * Usage: [wecp_event_filters]
     */
    public function filters_shortcode($atts) {
        $atts = shortcode_atts(array(
            'show_categories' => 'yes',
            'show_date_range' => 'yes',
            'show_location' => 'yes',
            'show_price' => 'yes',
            'show_search' => 'yes',
        ), $atts);

        ob_start();
        $this->render_filters($atts);
        return ob_get_clean();
    }

    /**
     * Render filter form
     */
    public function render_filters($atts = array()) {
        $defaults = array(
            'show_categories' => 'yes',
            'show_date_range' => 'yes',
            'show_location' => 'yes',
            'show_price' => 'yes',
            'show_search' => 'yes',
        );

        $atts = wp_parse_args($atts, $defaults);

        ?>
        <div class="wecp-event-filters">
            <form class="wecp-filter-form" id="wecp-filter-form" method="GET">

                <?php if ($atts['show_search'] === 'yes'): ?>
                    <div class="wecp-filter-group wecp-filter-search">
                        <label for="wecp-filter-keyword"><?php _e('Search Events', 'wp-event-calendar-pro'); ?></label>
                        <input type="text"
                               id="wecp-filter-keyword"
                               name="keyword"
                               class="wecp-filter-input"
                               placeholder="<?php esc_attr_e('Search by keyword...', 'wp-event-calendar-pro'); ?>">
                    </div>
                <?php endif; ?>

                <?php if ($atts['show_categories'] === 'yes'): ?>
                    <div class="wecp-filter-group wecp-filter-category">
                        <label for="wecp-filter-category"><?php _e('Category', 'wp-event-calendar-pro'); ?></label>
                        <select id="wecp-filter-category" name="category" class="wecp-filter-select">
                            <option value=""><?php _e('All Categories', 'wp-event-calendar-pro'); ?></option>
                            <?php
                            $categories = get_terms(array(
                                'taxonomy' => 'wecp_event_category',
                                'hide_empty' => true,
                            ));

                            if (!empty($categories) && !is_wp_error($categories)) {
                                foreach ($categories as $category) {
                                    printf(
                                        '<option value="%s">%s (%d)</option>',
                                        esc_attr($category->slug),
                                        esc_html($category->name),
                                        $category->count
                                    );
                                }
                            }
                            ?>
                        </select>
                    </div>
                <?php endif; ?>

                <?php if ($atts['show_date_range'] === 'yes'): ?>
                    <div class="wecp-filter-group wecp-filter-date-range">
                        <label><?php _e('Date Range', 'wp-event-calendar-pro'); ?></label>
                        <div class="wecp-date-range-inputs">
                            <input type="date"
                                   id="wecp-filter-date-from"
                                   name="date_from"
                                   class="wecp-filter-input"
                                   placeholder="<?php esc_attr_e('From', 'wp-event-calendar-pro'); ?>">
                            <span class="wecp-date-separator">-</span>
                            <input type="date"
                                   id="wecp-filter-date-to"
                                   name="date_to"
                                   class="wecp-filter-input"
                                   placeholder="<?php esc_attr_e('To', 'wp-event-calendar-pro'); ?>">
                        </div>
                        <div class="wecp-quick-dates">
                            <button type="button" class="wecp-quick-date-btn" data-range="today">
                                <?php _e('Today', 'wp-event-calendar-pro'); ?>
                            </button>
                            <button type="button" class="wecp-quick-date-btn" data-range="tomorrow">
                                <?php _e('Tomorrow', 'wp-event-calendar-pro'); ?>
                            </button>
                            <button type="button" class="wecp-quick-date-btn" data-range="this_week">
                                <?php _e('This Week', 'wp-event-calendar-pro'); ?>
                            </button>
                            <button type="button" class="wecp-quick-date-btn" data-range="this_month">
                                <?php _e('This Month', 'wp-event-calendar-pro'); ?>
                            </button>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($atts['show_location'] === 'yes'): ?>
                    <div class="wecp-filter-group wecp-filter-location">
                        <label for="wecp-filter-city"><?php _e('Location', 'wp-event-calendar-pro'); ?></label>
                        <div class="wecp-location-inputs">
                            <input type="text"
                                   id="wecp-filter-city"
                                   name="city"
                                   class="wecp-filter-input"
                                   placeholder="<?php esc_attr_e('City', 'wp-event-calendar-pro'); ?>">
                            <select id="wecp-filter-state" name="state" class="wecp-filter-select">
                                <option value=""><?php _e('Any State', 'wp-event-calendar-pro'); ?></option>
                                <?php $this->render_state_options(); ?>
                            </select>
                        </div>

                        <!-- Distance filter (optional) -->
                        <div class="wecp-distance-filter" style="display:none;">
                            <label for="wecp-filter-distance"><?php _e('Within:', 'wp-event-calendar-pro'); ?></label>
                            <select id="wecp-filter-distance" name="distance">
                                <option value=""><?php _e('Any Distance', 'wp-event-calendar-pro'); ?></option>
                                <option value="10">10 km</option>
                                <option value="25">25 km</option>
                                <option value="50">50 km</option>
                                <option value="100">100 km</option>
                                <option value="250">250 km</option>
                            </select>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($atts['show_price'] === 'yes' && class_exists('WooCommerce')): ?>
                    <div class="wecp-filter-group wecp-filter-price">
                        <label for="wecp-filter-price-range"><?php _e('Price Range', 'wp-event-calendar-pro'); ?></label>

                        <div class="wecp-price-range-inputs">
                            <input type="number"
                                   id="wecp-filter-price-min"
                                   name="price_min"
                                   class="wecp-filter-input"
                                   placeholder="<?php esc_attr_e('Min', 'wp-event-calendar-pro'); ?>"
                                   min="0"
                                   step="1">
                            <span class="wecp-price-separator">-</span>
                            <input type="number"
                                   id="wecp-filter-price-max"
                                   name="price_max"
                                   class="wecp-filter-input"
                                   placeholder="<?php esc_attr_e('Max', 'wp-event-calendar-pro'); ?>"
                                   min="0"
                                   step="1">
                        </div>

                        <div class="wecp-quick-prices">
                            <button type="button" class="wecp-quick-price-btn" data-price="free">
                                <?php _e('Free', 'wp-event-calendar-pro'); ?>
                            </button>
                            <button type="button" class="wecp-quick-price-btn" data-min="0" data-max="25">
                                <?php _e('Under $25', 'wp-event-calendar-pro'); ?>
                            </button>
                            <button type="button" class="wecp-quick-price-btn" data-min="25" data-max="50">
                                <?php _e('$25 - $50', 'wp-event-calendar-pro'); ?>
                            </button>
                            <button type="button" class="wecp-quick-price-btn" data-min="50" data-max="100">
                                <?php _e('$50 - $100', 'wp-event-calendar-pro'); ?>
                            </button>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="wecp-filter-actions">
                    <button type="submit" class="wecp-filter-submit-btn">
                        <span class="dashicons dashicons-search"></span>
                        <?php _e('Apply Filters', 'wp-event-calendar-pro'); ?>
                    </button>
                    <button type="button" class="wecp-filter-reset-btn">
                        <span class="dashicons dashicons-image-rotate"></span>
                        <?php _e('Reset', 'wp-event-calendar-pro'); ?>
                    </button>
                </div>

                <div class="wecp-active-filters" id="wecp-active-filters"></div>
            </form>

            <!-- Results container -->
            <div class="wecp-filter-results" id="wecp-filter-results">
                <div class="wecp-results-header">
                    <span class="wecp-results-count"></span>
                    <select class="wecp-results-sort" id="wecp-results-sort">
                        <option value="date_asc"><?php _e('Date (Earliest First)', 'wp-event-calendar-pro'); ?></option>
                        <option value="date_desc"><?php _e('Date (Latest First)', 'wp-event-calendar-pro'); ?></option>
                        <option value="title_asc"><?php _e('Title (A-Z)', 'wp-event-calendar-pro'); ?></option>
                        <option value="title_desc"><?php _e('Title (Z-A)', 'wp-event-calendar-pro'); ?></option>
                        <?php if (class_exists('WooCommerce')): ?>
                            <option value="price_asc"><?php _e('Price (Low to High)', 'wp-event-calendar-pro'); ?></option>
                            <option value="price_desc"><?php _e('Price (High to Low)', 'wp-event-calendar-pro'); ?></option>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="wecp-results-list"></div>
                <div class="wecp-results-loading" style="display:none;">
                    <div class="wecp-spinner"></div>
                    <p><?php _e('Loading events...', 'wp-event-calendar-pro'); ?></p>
                </div>
            </div>
        </div>

        <script>
        jQuery(document).ready(function($) {
            // Quick date filters
            $('.wecp-quick-date-btn').on('click', function() {
                const range = $(this).data('range');
                const today = new Date();
                let dateFrom, dateTo;

                switch(range) {
                    case 'today':
                        dateFrom = dateTo = today.toISOString().split('T')[0];
                        break;
                    case 'tomorrow':
                        const tomorrow = new Date(today);
                        tomorrow.setDate(tomorrow.getDate() + 1);
                        dateFrom = dateTo = tomorrow.toISOString().split('T')[0];
                        break;
                    case 'this_week':
                        dateFrom = today.toISOString().split('T')[0];
                        const endWeek = new Date(today);
                        endWeek.setDate(endWeek.getDate() + (7 - endWeek.getDay()));
                        dateTo = endWeek.toISOString().split('T')[0];
                        break;
                    case 'this_month':
                        dateFrom = today.toISOString().split('T')[0];
                        const endMonth = new Date(today.getFullYear(), today.getMonth() + 1, 0);
                        dateTo = endMonth.toISOString().split('T')[0];
                        break;
                }

                $('#wecp-filter-date-from').val(dateFrom);
                $('#wecp-filter-date-to').val(dateTo);
                $('.wecp-quick-date-btn').removeClass('active');
                $(this).addClass('active');
            });

            // Quick price filters
            $('.wecp-quick-price-btn').on('click', function() {
                if ($(this).data('price') === 'free') {
                    $('#wecp-filter-price-min').val(0);
                    $('#wecp-filter-price-max').val(0);
                } else {
                    $('#wecp-filter-price-min').val($(this).data('min'));
                    $('#wecp-filter-price-max').val($(this).data('max'));
                }
                $('.wecp-quick-price-btn').removeClass('active');
                $(this).addClass('active');
            });

            // Filter form submission
            $('#wecp-filter-form').on('submit', function(e) {
                e.preventDefault();
                wecpFilterEvents();
            });

            // Real-time filtering on input change
            $('#wecp-filter-form select, #wecp-filter-form input').on('change', function() {
                wecpFilterEvents();
            });

            // Reset filters
            $('.wecp-filter-reset-btn').on('click', function() {
                $('#wecp-filter-form')[0].reset();
                $('.wecp-quick-date-btn, .wecp-quick-price-btn').removeClass('active');
                $('#wecp-active-filters').empty();
                wecpFilterEvents();
            });

            // Sort change
            $('#wecp-results-sort').on('change', function() {
                wecpFilterEvents();
            });

            // Filter events function
            function wecpFilterEvents() {
                const formData = $('#wecp-filter-form').serialize();
                const sortBy = $('#wecp-results-sort').val();

                $('.wecp-results-loading').show();
                $('.wecp-results-list').hide();

                $.ajax({
                    url: wecpData.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'wecp_filter_events',
                        nonce: wecpData.nonce,
                        filters: formData,
                        sort: sortBy
                    },
                    success: function(response) {
                        if (response.success) {
                            $('.wecp-results-list').html(response.data.html);
                            $('.wecp-results-count').text(response.data.count + ' <?php echo esc_js(__('events found', 'wp-event-calendar-pro')); ?>');
                            updateActiveFilters(response.data.active_filters);
                        } else {
                            $('.wecp-results-list').html('<p><?php echo esc_js(__('No events found matching your criteria.', 'wp-event-calendar-pro')); ?></p>');
                            $('.wecp-results-count').text('0 <?php echo esc_js(__('events found', 'wp-event-calendar-pro')); ?>');
                        }
                        $('.wecp-results-loading').hide();
                        $('.wecp-results-list').fadeIn();
                    },
                    error: function() {
                        $('.wecp-results-loading').hide();
                        $('.wecp-results-list').html('<p><?php echo esc_js(__('Error loading events. Please try again.', 'wp-event-calendar-pro')); ?></p>').show();
                    }
                });
            }

            // Update active filters display
            function updateActiveFilters(filters) {
                const $container = $('#wecp-active-filters');
                $container.empty();

                if (Object.keys(filters).length === 0) {
                    return;
                }

                $container.append('<div class="wecp-active-filters-label"><?php echo esc_js(__('Active Filters:', 'wp-event-calendar-pro')); ?></div>');

                $.each(filters, function(key, value) {
                    const $tag = $('<span class="wecp-filter-tag"></span>')
                        .text(value)
                        .append('<span class="wecp-remove-filter" data-filter="' + key + '">×</span>');
                    $container.append($tag);
                });

                // Remove filter tag click
                $('.wecp-remove-filter').on('click', function() {
                    const filter = $(this).data('filter');
                    $('[name="' + filter + '"]').val('');
                    wecpFilterEvents();
                });
            }

            // Load initial results
            wecpFilterEvents();
        });
        </script>
        <?php
    }

    /**
     * AJAX handler for filtering events
     */
    public function ajax_filter_events() {
        check_ajax_referer('wecp_nonce', 'nonce');

        parse_str($_POST['filters'], $filters);
        $sort = sanitize_text_field($_POST['sort']);

        $events = $this->get_filtered_events($filters, $sort);

        if (!empty($events)) {
            ob_start();
            foreach ($events as $event) {
                $this->render_event_card($event);
            }
            $html = ob_get_clean();

            $active_filters = $this->get_active_filters($filters);

            wp_send_json_success(array(
                'html' => $html,
                'count' => count($events),
                'active_filters' => $active_filters,
            ));
        } else {
            wp_send_json_error();
        }
    }

    /**
     * Get filtered events
     */
    private function get_filtered_events($filters, $sort = 'date_asc') {
        $args = array(
            'post_type' => 'wecp_event',
            'post_status' => 'publish',
            'posts_per_page' => -1,
        );

        $meta_query = array('relation' => 'AND');
        $tax_query = array();

        // Keyword search
        if (!empty($filters['keyword'])) {
            $args['s'] = sanitize_text_field($filters['keyword']);
        }

        // Category filter
        if (!empty($filters['category'])) {
            $tax_query[] = array(
                'taxonomy' => 'wecp_event_category',
                'field' => 'slug',
                'terms' => sanitize_text_field($filters['category']),
            );
        }

        // Date range filter
        if (!empty($filters['date_from'])) {
            $meta_query[] = array(
                'key' => '_wecp_start_date',
                'value' => sanitize_text_field($filters['date_from']),
                'compare' => '>=',
                'type' => 'DATE',
            );
        }

        if (!empty($filters['date_to'])) {
            $meta_query[] = array(
                'key' => '_wecp_start_date',
                'value' => sanitize_text_field($filters['date_to']),
                'compare' => '<=',
                'type' => 'DATE',
            );
        }

        // Location filters
        if (!empty($filters['city'])) {
            $meta_query[] = array(
                'key' => '_wecp_venue_city',
                'value' => sanitize_text_field($filters['city']),
                'compare' => 'LIKE',
            );
        }

        if (!empty($filters['state'])) {
            $meta_query[] = array(
                'key' => '_wecp_venue_state',
                'value' => sanitize_text_field($filters['state']),
                'compare' => '=',
            );
        }

        // Price range filter
        if (isset($filters['price_min']) && $filters['price_min'] !== '') {
            $meta_query[] = array(
                'key' => '_wecp_ticket_price',
                'value' => floatval($filters['price_min']),
                'compare' => '>=',
                'type' => 'NUMERIC',
            );
        }

        if (isset($filters['price_max']) && $filters['price_max'] !== '') {
            $meta_query[] = array(
                'key' => '_wecp_ticket_price',
                'value' => floatval($filters['price_max']),
                'compare' => '<=',
                'type' => 'NUMERIC',
            );
        }

        if (!empty($meta_query)) {
            $args['meta_query'] = $meta_query;
        }

        if (!empty($tax_query)) {
            $args['tax_query'] = $tax_query;
        }

        // Sorting
        switch ($sort) {
            case 'date_desc':
                $args['meta_key'] = '_wecp_start_date';
                $args['orderby'] = 'meta_value';
                $args['order'] = 'DESC';
                break;
            case 'title_asc':
                $args['orderby'] = 'title';
                $args['order'] = 'ASC';
                break;
            case 'title_desc':
                $args['orderby'] = 'title';
                $args['order'] = 'DESC';
                break;
            case 'price_asc':
                $args['meta_key'] = '_wecp_ticket_price';
                $args['orderby'] = 'meta_value_num';
                $args['order'] = 'ASC';
                break;
            case 'price_desc':
                $args['meta_key'] = '_wecp_ticket_price';
                $args['orderby'] = 'meta_value_num';
                $args['order'] = 'DESC';
                break;
            case 'date_asc':
            default:
                $args['meta_key'] = '_wecp_start_date';
                $args['orderby'] = 'meta_value';
                $args['order'] = 'ASC';
                break;
        }

        $query = new WP_Query($args);
        return $query->posts;
    }

    /**
     * Render event card for filter results
     */
    private function render_event_card($event) {
        $start_date = get_post_meta($event->ID, '_wecp_start_date', true);
        $start_time = get_post_meta($event->ID, '_wecp_start_time', true);
        $venue_name = get_post_meta($event->ID, '_wecp_venue_name', true);
        $venue_city = get_post_meta($event->ID, '_wecp_venue_city', true);
        $color = get_post_meta($event->ID, '_wecp_event_color', true) ?: '#3498db';
        $categories = wp_get_post_terms($event->ID, 'wecp_event_category');

        ?>
        <div class="wecp-filter-event-card" style="border-left-color: <?php echo esc_attr($color); ?>;">
            <?php if (has_post_thumbnail($event->ID)): ?>
                <div class="wecp-event-card-image">
                    <?php echo get_the_post_thumbnail($event->ID, 'medium'); ?>
                </div>
            <?php endif; ?>

            <div class="wecp-event-card-content">
                <div class="wecp-event-card-date">
                    <?php echo date_i18n('M j, Y', strtotime($start_date)); ?>
                    <?php if ($start_time): ?>
                        · <?php echo esc_html($start_time); ?>
                    <?php endif; ?>
                </div>

                <h3 class="wecp-event-card-title">
                    <a href="<?php echo get_permalink($event->ID); ?>">
                        <?php echo esc_html($event->post_title); ?>
                    </a>
                </h3>

                <?php if (!empty($categories)): ?>
                    <div class="wecp-event-card-categories">
                        <?php foreach ($categories as $cat): ?>
                            <span class="wecp-category-badge"><?php echo esc_html($cat->name); ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if ($venue_name || $venue_city): ?>
                    <div class="wecp-event-card-location">
                        <span class="dashicons dashicons-location"></span>
                        <?php echo esc_html($venue_name); ?>
                        <?php if ($venue_city): ?>
                            · <?php echo esc_html($venue_city); ?>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <?php if ($event->post_excerpt): ?>
                    <div class="wecp-event-card-excerpt">
                        <?php echo esc_html(wp_trim_words($event->post_excerpt, 20)); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Get active filters for display
     */
    private function get_active_filters($filters) {
        $active = array();

        if (!empty($filters['keyword'])) {
            $active['keyword'] = sprintf(__('Keyword: %s', 'wp-event-calendar-pro'), $filters['keyword']);
        }

        if (!empty($filters['category'])) {
            $term = get_term_by('slug', $filters['category'], 'wecp_event_category');
            if ($term) {
                $active['category'] = sprintf(__('Category: %s', 'wp-event-calendar-pro'), $term->name);
            }
        }

        if (!empty($filters['date_from']) || !empty($filters['date_to'])) {
            $date_str = '';
            if (!empty($filters['date_from'])) {
                $date_str = date_i18n('M j', strtotime($filters['date_from']));
            }
            if (!empty($filters['date_to'])) {
                $date_str .= ' - ' . date_i18n('M j, Y', strtotime($filters['date_to']));
            }
            $active['date_range'] = $date_str;
        }

        if (!empty($filters['city'])) {
            $active['city'] = sprintf(__('City: %s', 'wp-event-calendar-pro'), $filters['city']);
        }

        if (!empty($filters['state'])) {
            $active['state'] = sprintf(__('State: %s', 'wp-event-calendar-pro'), $filters['state']);
        }

        if (isset($filters['price_min']) || isset($filters['price_max'])) {
            $price_str = __('Price: ', 'wp-event-calendar-pro');
            if (isset($filters['price_min'])) {
                $price_str .= '$' . $filters['price_min'];
            }
            if (isset($filters['price_max'])) {
                $price_str .= ' - $' . $filters['price_max'];
            }
            $active['price'] = $price_str;
        }

        return $active;
    }

    /**
     * Render state options
     */
    private function render_state_options() {
        $states = array(
            'AL' => 'Alabama', 'AK' => 'Alaska', 'AZ' => 'Arizona', 'AR' => 'Arkansas',
            'CA' => 'California', 'CO' => 'Colorado', 'CT' => 'Connecticut', 'DE' => 'Delaware',
            'FL' => 'Florida', 'GA' => 'Georgia', 'HI' => 'Hawaii', 'ID' => 'Idaho',
            'IL' => 'Illinois', 'IN' => 'Indiana', 'IA' => 'Iowa', 'KS' => 'Kansas',
            'KY' => 'Kentucky', 'LA' => 'Louisiana', 'ME' => 'Maine', 'MD' => 'Maryland',
            'MA' => 'Massachusetts', 'MI' => 'Michigan', 'MN' => 'Minnesota', 'MS' => 'Mississippi',
            'MO' => 'Missouri', 'MT' => 'Montana', 'NE' => 'Nebraska', 'NV' => 'Nevada',
            'NH' => 'New Hampshire', 'NJ' => 'New Jersey', 'NM' => 'New Mexico', 'NY' => 'New York',
            'NC' => 'North Carolina', 'ND' => 'North Dakota', 'OH' => 'Ohio', 'OK' => 'Oklahoma',
            'OR' => 'Oregon', 'PA' => 'Pennsylvania', 'RI' => 'Rhode Island', 'SC' => 'South Carolina',
            'SD' => 'South Dakota', 'TN' => 'Tennessee', 'TX' => 'Texas', 'UT' => 'Utah',
            'VT' => 'Vermont', 'VA' => 'Virginia', 'WA' => 'Washington', 'WV' => 'West Virginia',
            'WI' => 'Wisconsin', 'WY' => 'Wyoming'
        );

        foreach ($states as $code => $name) {
            printf('<option value="%s">%s</option>', esc_attr($code), esc_html($name));
        }
    }

    /**
     * Register filter widget
     */
    public function register_filter_widget() {
        register_widget('WECP_Event_Filter_Widget');
    }
}

/**
 * Event Filter Widget
 */
class WECP_Event_Filter_Widget extends WP_Widget {

    public function __construct() {
        parent::__construct(
            'wecp_event_filter',
            __('Event Filters', 'wp-event-calendar-pro'),
            array('description' => __('Filter events by category, date, location, and price', 'wp-event-calendar-pro'))
        );
    }

    public function widget($args, $instance) {
        echo $args['before_widget'];

        if (!empty($instance['title'])) {
            echo $args['before_title'] . apply_filters('widget_title', $instance['title']) . $args['after_title'];
        }

        $filter_atts = array(
            'show_categories' => !empty($instance['show_categories']) ? 'yes' : 'no',
            'show_date_range' => !empty($instance['show_date_range']) ? 'yes' : 'no',
            'show_location' => !empty($instance['show_location']) ? 'yes' : 'no',
            'show_price' => !empty($instance['show_price']) ? 'yes' : 'no',
            'show_search' => !empty($instance['show_search']) ? 'yes' : 'no',
        );

        WECP_Event_Filters::get_instance()->render_filters($filter_atts);

        echo $args['after_widget'];
    }

    public function form($instance) {
        $title = !empty($instance['title']) ? $instance['title'] : __('Filter Events', 'wp-event-calendar-pro');
        $show_categories = !empty($instance['show_categories']) ? 'checked' : '';
        $show_date_range = !empty($instance['show_date_range']) ? 'checked' : '';
        $show_location = !empty($instance['show_location']) ? 'checked' : '';
        $show_price = !empty($instance['show_price']) ? 'checked' : '';
        $show_search = !empty($instance['show_search']) ? 'checked' : '';

        ?>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('title')); ?>">
                <?php _e('Title:', 'wp-event-calendar-pro'); ?>
            </label>
            <input class="widefat"
                   id="<?php echo esc_attr($this->get_field_id('title')); ?>"
                   name="<?php echo esc_attr($this->get_field_name('title')); ?>"
                   type="text"
                   value="<?php echo esc_attr($title); ?>">
        </p>

        <p>
            <input class="checkbox"
                   type="checkbox"
                   id="<?php echo esc_attr($this->get_field_id('show_search')); ?>"
                   name="<?php echo esc_attr($this->get_field_name('show_search')); ?>"
                   <?php echo $show_search; ?>>
            <label for="<?php echo esc_attr($this->get_field_id('show_search')); ?>">
                <?php _e('Show Search', 'wp-event-calendar-pro'); ?>
            </label>
        </p>

        <p>
            <input class="checkbox"
                   type="checkbox"
                   id="<?php echo esc_attr($this->get_field_id('show_categories')); ?>"
                   name="<?php echo esc_attr($this->get_field_name('show_categories')); ?>"
                   <?php echo $show_categories; ?>>
            <label for="<?php echo esc_attr($this->get_field_id('show_categories')); ?>">
                <?php _e('Show Categories', 'wp-event-calendar-pro'); ?>
            </label>
        </p>

        <p>
            <input class="checkbox"
                   type="checkbox"
                   id="<?php echo esc_attr($this->get_field_id('show_date_range')); ?>"
                   name="<?php echo esc_attr($this->get_field_name('show_date_range')); ?>"
                   <?php echo $show_date_range; ?>>
            <label for="<?php echo esc_attr($this->get_field_id('show_date_range')); ?>">
                <?php _e('Show Date Range', 'wp-event-calendar-pro'); ?>
            </label>
        </p>

        <p>
            <input class="checkbox"
                   type="checkbox"
                   id="<?php echo esc_attr($this->get_field_id('show_location')); ?>"
                   name="<?php echo esc_attr($this->get_field_name('show_location')); ?>"
                   <?php echo $show_location; ?>>
            <label for="<?php echo esc_attr($this->get_field_id('show_location')); ?>">
                <?php _e('Show Location', 'wp-event-calendar-pro'); ?>
            </label>
        </p>

        <p>
            <input class="checkbox"
                   type="checkbox"
                   id="<?php echo esc_attr($this->get_field_id('show_price')); ?>"
                   name="<?php echo esc_attr($this->get_field_name('show_price')); ?>"
                   <?php echo $show_price; ?>>
            <label for="<?php echo esc_attr($this->get_field_id('show_price')); ?>">
                <?php _e('Show Price Range', 'wp-event-calendar-pro'); ?>
            </label>
        </p>
        <?php
    }

    public function update($new_instance, $old_instance) {
        $instance = array();
        $instance['title'] = (!empty($new_instance['title'])) ? sanitize_text_field($new_instance['title']) : '';
        $instance['show_categories'] = !empty($new_instance['show_categories']);
        $instance['show_date_range'] = !empty($new_instance['show_date_range']);
        $instance['show_location'] = !empty($new_instance['show_location']);
        $instance['show_price'] = !empty($new_instance['show_price']);
        $instance['show_search'] = !empty($new_instance['show_search']);
        return $instance;
    }
}
