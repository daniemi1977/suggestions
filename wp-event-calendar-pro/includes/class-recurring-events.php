<?php
/**
 * Recurring Events Manager
 * Handles event recurrence patterns and instance generation
 *
 * @package WP_Event_Calendar_Pro
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class WECP_Recurring_Events {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('save_post_wecp_event', array($this, 'save_recurrence_meta'), 10, 2);
        add_action('wecp_generate_recurring_instances', array($this, 'generate_recurring_instances'), 10, 1);
        add_filter('wecp_get_events', array($this, 'add_recurring_instances'), 10, 2);
    }

    /**
     * Add recurrence meta box
     */
    public function add_recurrence_meta_box() {
        add_meta_box(
            'wecp_recurrence',
            __('Recurrence Settings', 'wp-event-calendar-pro'),
            array($this, 'render_recurrence_meta_box'),
            'wecp_event',
            'normal',
            'high'
        );
    }

    /**
     * Render recurrence meta box
     */
    public function render_recurrence_meta_box($post) {
        wp_nonce_field('wecp_recurrence_meta', 'wecp_recurrence_nonce');

        $is_recurring = get_post_meta($post->ID, '_wecp_is_recurring', true);
        $recurrence_type = get_post_meta($post->ID, '_wecp_recurrence_type', true) ?: 'daily';
        $recurrence_interval = get_post_meta($post->ID, '_wecp_recurrence_interval', true) ?: 1;
        $recurrence_end_type = get_post_meta($post->ID, '_wecp_recurrence_end_type', true) ?: 'never';
        $recurrence_end_date = get_post_meta($post->ID, '_wecp_recurrence_end_date', true);
        $recurrence_count = get_post_meta($post->ID, '_wecp_recurrence_count', true) ?: 10;
        $recurrence_days = get_post_meta($post->ID, '_wecp_recurrence_days', true) ?: array();
        $recurrence_monthly_type = get_post_meta($post->ID, '_wecp_recurrence_monthly_type', true) ?: 'date';
        $recurrence_monthly_week = get_post_meta($post->ID, '_wecp_recurrence_monthly_week', true) ?: 'first';
        $recurrence_monthly_day = get_post_meta($post->ID, '_wecp_recurrence_monthly_day', true) ?: 'monday';

        ?>
        <div class="wecp-recurrence-settings">
            <p>
                <label>
                    <input type="checkbox"
                           name="wecp_is_recurring"
                           id="wecp_is_recurring"
                           value="1"
                           <?php checked($is_recurring, '1'); ?>>
                    <?php _e('This is a recurring event', 'wp-event-calendar-pro'); ?>
                </label>
            </p>

            <div id="wecp_recurrence_options" style="<?php echo $is_recurring ? '' : 'display:none;'; ?>">

                <!-- Recurrence Pattern -->
                <p>
                    <label for="wecp_recurrence_type">
                        <strong><?php _e('Repeat Pattern:', 'wp-event-calendar-pro'); ?></strong>
                    </label>
                    <select name="wecp_recurrence_type" id="wecp_recurrence_type" class="widefat">
                        <option value="daily" <?php selected($recurrence_type, 'daily'); ?>>
                            <?php _e('Daily', 'wp-event-calendar-pro'); ?>
                        </option>
                        <option value="weekly" <?php selected($recurrence_type, 'weekly'); ?>>
                            <?php _e('Weekly', 'wp-event-calendar-pro'); ?>
                        </option>
                        <option value="monthly" <?php selected($recurrence_type, 'monthly'); ?>>
                            <?php _e('Monthly', 'wp-event-calendar-pro'); ?>
                        </option>
                        <option value="yearly" <?php selected($recurrence_type, 'yearly'); ?>>
                            <?php _e('Yearly', 'wp-event-calendar-pro'); ?>
                        </option>
                        <option value="custom" <?php selected($recurrence_type, 'custom'); ?>>
                            <?php _e('Custom Days', 'wp-event-calendar-pro'); ?>
                        </option>
                    </select>
                </p>

                <!-- Interval -->
                <p id="wecp_interval_container">
                    <label for="wecp_recurrence_interval">
                        <strong><?php _e('Repeat Every:', 'wp-event-calendar-pro'); ?></strong>
                    </label>
                    <input type="number"
                           name="wecp_recurrence_interval"
                           id="wecp_recurrence_interval"
                           value="<?php echo esc_attr($recurrence_interval); ?>"
                           min="1"
                           max="365"
                           class="small-text">
                    <span id="wecp_interval_label"><?php _e('day(s)', 'wp-event-calendar-pro'); ?></span>
                </p>

                <!-- Weekly Days Selection -->
                <div id="wecp_weekly_days" style="display:none;">
                    <p><strong><?php _e('Repeat On:', 'wp-event-calendar-pro'); ?></strong></p>
                    <?php
                    $days = array(
                        'monday' => __('Monday', 'wp-event-calendar-pro'),
                        'tuesday' => __('Tuesday', 'wp-event-calendar-pro'),
                        'wednesday' => __('Wednesday', 'wp-event-calendar-pro'),
                        'thursday' => __('Thursday', 'wp-event-calendar-pro'),
                        'friday' => __('Friday', 'wp-event-calendar-pro'),
                        'saturday' => __('Saturday', 'wp-event-calendar-pro'),
                        'sunday' => __('Sunday', 'wp-event-calendar-pro'),
                    );
                    foreach ($days as $day_key => $day_label) :
                    ?>
                        <label style="display:inline-block; margin-right:15px;">
                            <input type="checkbox"
                                   name="wecp_recurrence_days[]"
                                   value="<?php echo esc_attr($day_key); ?>"
                                   <?php checked(in_array($day_key, (array)$recurrence_days)); ?>>
                            <?php echo esc_html($day_label); ?>
                        </label>
                    <?php endforeach; ?>
                </div>

                <!-- Monthly Options -->
                <div id="wecp_monthly_options" style="display:none;">
                    <p><strong><?php _e('Monthly Repeat Type:', 'wp-event-calendar-pro'); ?></strong></p>
                    <label style="display:block; margin-bottom:10px;">
                        <input type="radio"
                               name="wecp_recurrence_monthly_type"
                               value="date"
                               <?php checked($recurrence_monthly_type, 'date'); ?>>
                        <?php _e('On the same date each month (e.g., 15th of every month)', 'wp-event-calendar-pro'); ?>
                    </label>
                    <label style="display:block;">
                        <input type="radio"
                               name="wecp_recurrence_monthly_type"
                               value="day"
                               <?php checked($recurrence_monthly_type, 'day'); ?>>
                        <?php _e('On the same weekday (e.g., 2nd Tuesday of every month)', 'wp-event-calendar-pro'); ?>
                    </label>

                    <div id="wecp_monthly_day_options" style="margin-top:10px; <?php echo $recurrence_monthly_type === 'day' ? '' : 'display:none;'; ?>">
                        <select name="wecp_recurrence_monthly_week">
                            <option value="first" <?php selected($recurrence_monthly_week, 'first'); ?>><?php _e('First', 'wp-event-calendar-pro'); ?></option>
                            <option value="second" <?php selected($recurrence_monthly_week, 'second'); ?>><?php _e('Second', 'wp-event-calendar-pro'); ?></option>
                            <option value="third" <?php selected($recurrence_monthly_week, 'third'); ?>><?php _e('Third', 'wp-event-calendar-pro'); ?></option>
                            <option value="fourth" <?php selected($recurrence_monthly_week, 'fourth'); ?>><?php _e('Fourth', 'wp-event-calendar-pro'); ?></option>
                            <option value="last" <?php selected($recurrence_monthly_week, 'last'); ?>><?php _e('Last', 'wp-event-calendar-pro'); ?></option>
                        </select>
                        <select name="wecp_recurrence_monthly_day">
                            <?php foreach ($days as $day_key => $day_label) : ?>
                                <option value="<?php echo esc_attr($day_key); ?>" <?php selected($recurrence_monthly_day, $day_key); ?>>
                                    <?php echo esc_html($day_label); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- End Type -->
                <p style="margin-top:20px;">
                    <strong><?php _e('End Repeat:', 'wp-event-calendar-pro'); ?></strong>
                </p>

                <label style="display:block; margin-bottom:10px;">
                    <input type="radio"
                           name="wecp_recurrence_end_type"
                           value="never"
                           <?php checked($recurrence_end_type, 'never'); ?>>
                    <?php _e('Never', 'wp-event-calendar-pro'); ?>
                </label>

                <label style="display:block; margin-bottom:10px;">
                    <input type="radio"
                           name="wecp_recurrence_end_type"
                           value="date"
                           <?php checked($recurrence_end_type, 'date'); ?>>
                    <?php _e('On Date:', 'wp-event-calendar-pro'); ?>
                    <input type="date"
                           name="wecp_recurrence_end_date"
                           id="wecp_recurrence_end_date"
                           value="<?php echo esc_attr($recurrence_end_date); ?>">
                </label>

                <label style="display:block;">
                    <input type="radio"
                           name="wecp_recurrence_end_type"
                           value="count"
                           <?php checked($recurrence_end_type, 'count'); ?>>
                    <?php _e('After', 'wp-event-calendar-pro'); ?>
                    <input type="number"
                           name="wecp_recurrence_count"
                           id="wecp_recurrence_count"
                           value="<?php echo esc_attr($recurrence_count); ?>"
                           min="1"
                           max="365"
                           class="small-text">
                    <?php _e('occurrences', 'wp-event-calendar-pro'); ?>
                </label>

                <p class="description" style="margin-top:15px;">
                    <em><?php _e('Note: Recurring event instances will be generated automatically. Changes to the main event will update all future instances.', 'wp-event-calendar-pro'); ?></em>
                </p>
            </div>
        </div>

        <script>
        jQuery(document).ready(function($) {
            // Toggle recurrence options
            $('#wecp_is_recurring').on('change', function() {
                $('#wecp_recurrence_options').toggle($(this).is(':checked'));
            });

            // Update interval label based on recurrence type
            function updateIntervalLabel() {
                const type = $('#wecp_recurrence_type').val();
                const labels = {
                    'daily': '<?php echo esc_js(__('day(s)', 'wp-event-calendar-pro')); ?>',
                    'weekly': '<?php echo esc_js(__('week(s)', 'wp-event-calendar-pro')); ?>',
                    'monthly': '<?php echo esc_js(__('month(s)', 'wp-event-calendar-pro')); ?>',
                    'yearly': '<?php echo esc_js(__('year(s)', 'wp-event-calendar-pro')); ?>',
                    'custom': '<?php echo esc_js(__('day(s)', 'wp-event-calendar-pro')); ?>'
                };
                $('#wecp_interval_label').text(labels[type] || labels.daily);

                // Show/hide type-specific options
                $('#wecp_weekly_days').toggle(type === 'weekly');
                $('#wecp_monthly_options').toggle(type === 'monthly');
                $('#wecp_interval_container').toggle(type !== 'custom');
            }

            $('#wecp_recurrence_type').on('change', updateIntervalLabel);
            updateIntervalLabel();

            // Toggle monthly day options
            $('input[name="wecp_recurrence_monthly_type"]').on('change', function() {
                $('#wecp_monthly_day_options').toggle($(this).val() === 'day');
            });
        });
        </script>

        <style>
        .wecp-recurrence-settings label {
            font-weight: normal;
        }
        .wecp-recurrence-settings .small-text {
            width: 60px;
        }
        </style>
        <?php
    }

    /**
     * Save recurrence metadata
     */
    public function save_recurrence_meta($post_id, $post) {
        // Security checks
        if (!isset($_POST['wecp_recurrence_nonce']) || !wp_verify_nonce($_POST['wecp_recurrence_nonce'], 'wecp_recurrence_meta')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Save recurrence settings
        $is_recurring = isset($_POST['wecp_is_recurring']) ? '1' : '0';
        update_post_meta($post_id, '_wecp_is_recurring', $is_recurring);

        if ($is_recurring === '1') {
            update_post_meta($post_id, '_wecp_recurrence_type', sanitize_text_field($_POST['wecp_recurrence_type']));
            update_post_meta($post_id, '_wecp_recurrence_interval', absint($_POST['wecp_recurrence_interval']));
            update_post_meta($post_id, '_wecp_recurrence_end_type', sanitize_text_field($_POST['wecp_recurrence_end_type']));
            update_post_meta($post_id, '_wecp_recurrence_end_date', sanitize_text_field($_POST['wecp_recurrence_end_date']));
            update_post_meta($post_id, '_wecp_recurrence_count', absint($_POST['wecp_recurrence_count']));
            update_post_meta($post_id, '_wecp_recurrence_monthly_type', sanitize_text_field($_POST['wecp_recurrence_monthly_type']));
            update_post_meta($post_id, '_wecp_recurrence_monthly_week', sanitize_text_field($_POST['wecp_recurrence_monthly_week']));
            update_post_meta($post_id, '_wecp_recurrence_monthly_day', sanitize_text_field($_POST['wecp_recurrence_monthly_day']));

            $days = isset($_POST['wecp_recurrence_days']) ? array_map('sanitize_text_field', $_POST['wecp_recurrence_days']) : array();
            update_post_meta($post_id, '_wecp_recurrence_days', $days);

            // Schedule instance generation
            wp_schedule_single_event(time() + 10, 'wecp_generate_recurring_instances', array($post_id));
        } else {
            // Delete recurring instances if event is no longer recurring
            $this->delete_recurring_instances($post_id);
        }
    }

    /**
     * Generate recurring event instances
     */
    public function generate_recurring_instances($event_id) {
        // Delete existing instances first
        $this->delete_recurring_instances($event_id);

        $is_recurring = get_post_meta($event_id, '_wecp_is_recurring', true);
        if ($is_recurring !== '1') {
            return;
        }

        // Get event start date
        $start_date = get_post_meta($event_id, '_wecp_start_date', true);
        $start_time = get_post_meta($event_id, '_wecp_start_time', true);
        $end_time = get_post_meta($event_id, '_wecp_end_time', true);
        $all_day = get_post_meta($event_id, '_wecp_all_day', true);

        if (empty($start_date)) {
            return;
        }

        // Get recurrence settings
        $recurrence_type = get_post_meta($event_id, '_wecp_recurrence_type', true);
        $interval = absint(get_post_meta($event_id, '_wecp_recurrence_interval', true)) ?: 1;
        $end_type = get_post_meta($event_id, '_wecp_recurrence_end_type', true);
        $end_date = get_post_meta($event_id, '_wecp_recurrence_end_date', true);
        $count = absint(get_post_meta($event_id, '_wecp_recurrence_count', true)) ?: 10;

        // Generate instances
        $instances = $this->calculate_instances($start_date, $recurrence_type, $interval, $end_type, $end_date, $count, $event_id);

        // Store instances
        update_post_meta($event_id, '_wecp_recurring_instances', $instances);
    }

    /**
     * Calculate recurring instances
     */
    private function calculate_instances($start_date, $type, $interval, $end_type, $end_date, $count, $event_id) {
        $instances = array();
        $current_date = new DateTime($start_date);
        $max_date = $end_type === 'date' && !empty($end_date) ? new DateTime($end_date) : null;
        $max_count = $end_type === 'count' ? $count : 365; // Safety limit

        $iteration = 0;

        while ($iteration < $max_count) {
            // Check if we've passed the end date
            if ($max_date && $current_date > $max_date) {
                break;
            }

            // Add this instance
            $instances[] = $current_date->format('Y-m-d');

            // Calculate next occurrence
            switch ($type) {
                case 'daily':
                    $current_date->modify("+{$interval} days");
                    break;

                case 'weekly':
                    $days = get_post_meta($event_id, '_wecp_recurrence_days', true);
                    if (!empty($days)) {
                        $current_date = $this->get_next_weekday($current_date, $days, $interval);
                    } else {
                        $current_date->modify("+{$interval} weeks");
                    }
                    break;

                case 'monthly':
                    $monthly_type = get_post_meta($event_id, '_wecp_recurrence_monthly_type', true);
                    if ($monthly_type === 'day') {
                        $week = get_post_meta($event_id, '_wecp_recurrence_monthly_week', true);
                        $day = get_post_meta($event_id, '_wecp_recurrence_monthly_day', true);
                        $current_date = $this->get_next_monthly_weekday($current_date, $week, $day, $interval);
                    } else {
                        $current_date->modify("+{$interval} months");
                    }
                    break;

                case 'yearly':
                    $current_date->modify("+{$interval} years");
                    break;

                default:
                    $current_date->modify("+1 day");
                    break;
            }

            $iteration++;
        }

        return $instances;
    }

    /**
     * Get next occurrence for weekly pattern with specific days
     */
    private function get_next_weekday($current_date, $days, $interval) {
        $current_day = strtolower($current_date->format('l'));
        $day_map = array('monday' => 1, 'tuesday' => 2, 'wednesday' => 3, 'thursday' => 4, 'friday' => 5, 'saturday' => 6, 'sunday' => 7);

        $current_day_num = $day_map[$current_day];
        $target_days = array_map(function($d) use ($day_map) { return $day_map[$d]; }, $days);
        sort($target_days);

        // Find next day
        $next_day = null;
        foreach ($target_days as $target) {
            if ($target > $current_day_num) {
                $next_day = $target;
                break;
            }
        }

        if ($next_day === null) {
            // Wrap to next week
            $next_day = $target_days[0];
            $days_to_add = (7 - $current_day_num) + $next_day + (($interval - 1) * 7);
        } else {
            $days_to_add = $next_day - $current_day_num;
        }

        $new_date = clone $current_date;
        $new_date->modify("+{$days_to_add} days");
        return $new_date;
    }

    /**
     * Get next monthly occurrence for weekday pattern (e.g., "2nd Tuesday")
     */
    private function get_next_monthly_weekday($current_date, $week, $day, $interval) {
        $new_date = clone $current_date;
        $new_date->modify("+{$interval} months");
        $new_date->modify('first day of this month');

        $week_map = array(
            'first' => 'first',
            'second' => 'second',
            'third' => 'third',
            'fourth' => 'fourth',
            'last' => 'last'
        );

        $week_modifier = $week_map[$week];
        $new_date->modify("{$week_modifier} {$day} of this month");

        return $new_date;
    }

    /**
     * Delete recurring instances
     */
    private function delete_recurring_instances($event_id) {
        delete_post_meta($event_id, '_wecp_recurring_instances');
    }

    /**
     * Add recurring instances to event queries
     */
    public function add_recurring_instances($events, $args) {
        $expanded_events = array();

        foreach ($events as $event) {
            // Add original event
            $expanded_events[] = $event;

            // Check if recurring
            $is_recurring = get_post_meta($event->ID, '_wecp_is_recurring', true);
            if ($is_recurring === '1') {
                $instances = get_post_meta($event->ID, '_wecp_recurring_instances', true);

                if (!empty($instances) && is_array($instances)) {
                    $start_date = get_post_meta($event->ID, '_wecp_start_date', true);

                    foreach ($instances as $instance_date) {
                        // Skip the original date
                        if ($instance_date === $start_date) {
                            continue;
                        }

                        // Create virtual event object
                        $virtual_event = clone $event;
                        $virtual_event->is_recurring_instance = true;
                        $virtual_event->instance_date = $instance_date;
                        $virtual_event->parent_event_id = $event->ID;

                        $expanded_events[] = $virtual_event;
                    }
                }
            }
        }

        return $expanded_events;
    }

    /**
     * Get event date (handles recurring instances)
     */
    public static function get_event_date($event) {
        if (isset($event->is_recurring_instance) && $event->is_recurring_instance) {
            return $event->instance_date;
        }

        return get_post_meta($event->ID, '_wecp_start_date', true);
    }

    /**
     * Check if event is recurring
     */
    public static function is_recurring($event_id) {
        return get_post_meta($event_id, '_wecp_is_recurring', true) === '1';
    }
}
