<?php
/**
 * Admin Settings
 *
 * @package WP_Event_Calendar_Pro
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class WECP_Admin_Settings {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            __('Event Calendar Settings', 'wp-event-calendar-pro'),
            __('Event Calendar', 'wp-event-calendar-pro'),
            'manage_options',
            'wecp-settings',
            array($this, 'render_settings_page'),
            'dashicons-calendar-alt',
            26
        );

        add_submenu_page(
            'wecp-settings',
            __('Settings', 'wp-event-calendar-pro'),
            __('Settings', 'wp-event-calendar-pro'),
            'manage_options',
            'wecp-settings'
        );

        add_submenu_page(
            'wecp-settings',
            __('All Events', 'wp-event-calendar-pro'),
            __('All Events', 'wp-event-calendar-pro'),
            'edit_posts',
            'edit.php?post_type=wecp_event'
        );

        add_submenu_page(
            'wecp-settings',
            __('Add New Event', 'wp-event-calendar-pro'),
            __('Add New', 'wp-event-calendar-pro'),
            'edit_posts',
            'post-new.php?post_type=wecp_event'
        );

        add_submenu_page(
            'wecp-settings',
            __('Categories', 'wp-event-calendar-pro'),
            __('Categories', 'wp-event-calendar-pro'),
            'manage_categories',
            'edit-tags.php?taxonomy=wecp_event_category&post_type=wecp_event'
        );
    }

    /**
     * Register settings
     */
    public function register_settings() {
        register_setting('wecp_settings', 'wecp_calendar_view');
        register_setting('wecp_settings', 'wecp_events_per_page');
        register_setting('wecp_settings', 'wecp_show_past_events');
        register_setting('wecp_settings', 'wecp_default_event_color');
        register_setting('wecp_settings', 'wecp_enable_lightbox');
        register_setting('wecp_settings', 'wecp_enable_booking');
        register_setting('wecp_settings', 'wecp_google_maps_api');
    }

    /**
     * Render settings page
     */
    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        // Save settings
        if (isset($_POST['wecp_save_settings'])) {
            check_admin_referer('wecp_settings_nonce');

            update_option('wecp_calendar_view', sanitize_text_field($_POST['wecp_calendar_view']));
            update_option('wecp_events_per_page', intval($_POST['wecp_events_per_page']));
            update_option('wecp_show_past_events', isset($_POST['wecp_show_past_events']) ? '1' : '0');
            update_option('wecp_default_event_color', sanitize_hex_color($_POST['wecp_default_event_color']));
            update_option('wecp_enable_lightbox', isset($_POST['wecp_enable_lightbox']) ? '1' : '0');
            update_option('wecp_enable_booking', isset($_POST['wecp_enable_booking']) ? '1' : '0');
            update_option('wecp_google_maps_api', sanitize_text_field($_POST['wecp_google_maps_api']));

            echo '<div class="notice notice-success"><p>' . __('Settings saved successfully!', 'wp-event-calendar-pro') . '</p></div>';
        }

        $calendar_view = get_option('wecp_calendar_view', 'month');
        $events_per_page = get_option('wecp_events_per_page', 10);
        $show_past_events = get_option('wecp_show_past_events', false);
        $default_event_color = get_option('wecp_default_event_color', '#3498db');
        $enable_lightbox = get_option('wecp_enable_lightbox', true);
        $enable_booking = get_option('wecp_enable_booking', true);
        $google_maps_api = get_option('wecp_google_maps_api', '');

        ?>
        <div class="wrap">
            <h1><?php _e('Event Calendar Settings', 'wp-event-calendar-pro'); ?></h1>

            <form method="post" action="">
                <?php wp_nonce_field('wecp_settings_nonce'); ?>

                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="wecp_calendar_view"><?php _e('Default Calendar View', 'wp-event-calendar-pro'); ?></label>
                        </th>
                        <td>
                            <select name="wecp_calendar_view" id="wecp_calendar_view">
                                <option value="month" <?php selected($calendar_view, 'month'); ?>><?php _e('Month', 'wp-event-calendar-pro'); ?></option>
                                <option value="week" <?php selected($calendar_view, 'week'); ?>><?php _e('Week', 'wp-event-calendar-pro'); ?></option>
                                <option value="list" <?php selected($calendar_view, 'list'); ?>><?php _e('List', 'wp-event-calendar-pro'); ?></option>
                                <option value="tile" <?php selected($calendar_view, 'tile'); ?>><?php _e('Tile', 'wp-event-calendar-pro'); ?></option>
                            </select>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="wecp_events_per_page"><?php _e('Events Per Page', 'wp-event-calendar-pro'); ?></label>
                        </th>
                        <td>
                            <input type="number" name="wecp_events_per_page" id="wecp_events_per_page" value="<?php echo esc_attr($events_per_page); ?>" min="1" max="100" class="small-text">
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="wecp_show_past_events"><?php _e('Show Past Events', 'wp-event-calendar-pro'); ?></label>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox" name="wecp_show_past_events" id="wecp_show_past_events" value="1" <?php checked($show_past_events, '1'); ?>>
                                <?php _e('Display past events in calendar', 'wp-event-calendar-pro'); ?>
                            </label>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="wecp_default_event_color"><?php _e('Default Event Color', 'wp-event-calendar-pro'); ?></label>
                        </th>
                        <td>
                            <input type="text" name="wecp_default_event_color" id="wecp_default_event_color" value="<?php echo esc_attr($default_event_color); ?>" class="wecp-color-picker">
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="wecp_enable_lightbox"><?php _e('Enable Lightbox', 'wp-event-calendar-pro'); ?></label>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox" name="wecp_enable_lightbox" id="wecp_enable_lightbox" value="1" <?php checked($enable_lightbox, '1'); ?>>
                                <?php _e('Open event details in lightbox popup', 'wp-event-calendar-pro'); ?>
                            </label>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="wecp_enable_booking"><?php _e('Enable Booking', 'wp-event-calendar-pro'); ?></label>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox" name="wecp_enable_booking" id="wecp_enable_booking" value="1" <?php checked($enable_booking, '1'); ?>>
                                <?php _e('Enable WooCommerce booking functionality', 'wp-event-calendar-pro'); ?>
                            </label>
                            <?php if (!class_exists('WooCommerce')): ?>
                                <p class="description" style="color: #d63638;">
                                    <?php _e('WooCommerce is not installed. Please install WooCommerce to enable booking.', 'wp-event-calendar-pro'); ?>
                                </p>
                            <?php endif; ?>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="wecp_google_maps_api"><?php _e('Google Maps API Key', 'wp-event-calendar-pro'); ?></label>
                        </th>
                        <td>
                            <input type="text" name="wecp_google_maps_api" id="wecp_google_maps_api" value="<?php echo esc_attr($google_maps_api); ?>" class="regular-text">
                            <p class="description">
                                <?php _e('Optional: Add Google Maps API key to display venue maps.', 'wp-event-calendar-pro'); ?>
                                <a href="https://developers.google.com/maps/documentation/javascript/get-api-key" target="_blank">
                                    <?php _e('Get API Key', 'wp-event-calendar-pro'); ?>
                                </a>
                            </p>
                        </td>
                    </tr>
                </table>

                <h2><?php _e('Shortcode Usage', 'wp-event-calendar-pro'); ?></h2>
                <div class="wecp-shortcode-help">
                    <p><strong><?php _e('Calendar View:', 'wp-event-calendar-pro'); ?></strong></p>
                    <code>[wecp_calendar view="month" category="concerts" interaction="lightbox"]</code>

                    <p><strong><?php _e('List View:', 'wp-event-calendar-pro'); ?></strong></p>
                    <code>[wecp_events_list category="workshops" limit="10"]</code>

                    <p><strong><?php _e('Tile View:', 'wp-event-calendar-pro'); ?></strong></p>
                    <code>[wecp_events_tile columns="3" limit="12"]</code>

                    <p><strong><?php _e('Ticket Scanner:', 'wp-event-calendar-pro'); ?></strong></p>
                    <code>[wecp_ticket_scanner]</code>
                </div>

                <?php submit_button(__('Save Settings', 'wp-event-calendar-pro'), 'primary', 'wecp_save_settings'); ?>
            </form>
        </div>
        <?php
    }
}
