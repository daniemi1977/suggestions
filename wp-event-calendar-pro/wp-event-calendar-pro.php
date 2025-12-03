<?php
/**
 * Plugin Name: WP Event Calendar Pro
 * Plugin URI: https://github.com/daniemi1977/wp-event-calendar-pro
 * Description: Beautiful event calendar with WooCommerce booking integration. Inspired by EventON with modern design.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://github.com/daniemi1977
 * Text Domain: wp-event-calendar-pro
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 * WC tested up to: 8.5
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Plugin constants
define('WECP_VERSION', '1.0.0');
define('WECP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WECP_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WECP_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Main plugin class
 */
class WP_Event_Calendar_Pro {

    private static $instance = null;

    /**
     * Get singleton instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->init_hooks();
        $this->load_dependencies();
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        add_action('plugins_loaded', array($this, 'load_textdomain'));
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));

        // Check for WooCommerce
        add_action('admin_notices', array($this, 'check_woocommerce'));
    }

    /**
     * Load plugin dependencies
     */
    private function load_dependencies() {
        require_once WECP_PLUGIN_DIR . 'includes/class-event-post-type.php';
        require_once WECP_PLUGIN_DIR . 'includes/class-event-calendar.php';
        require_once WECP_PLUGIN_DIR . 'includes/class-woocommerce-integration.php';
        require_once WECP_PLUGIN_DIR . 'includes/class-booking-manager.php';
        require_once WECP_PLUGIN_DIR . 'includes/class-qr-code-generator.php';
        require_once WECP_PLUGIN_DIR . 'includes/class-admin-settings.php';
    }

    /**
     * Load text domain for translations
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'wp-event-calendar-pro',
            false,
            dirname(WECP_PLUGIN_BASENAME) . '/languages'
        );
    }

    /**
     * Initialize plugin
     */
    public function init() {
        // Initialize classes
        WECP_Event_Post_Type::get_instance();
        WECP_Event_Calendar::get_instance();
        WECP_Admin_Settings::get_instance();

        // Initialize WooCommerce integration if WooCommerce is active
        if (class_exists('WooCommerce')) {
            WECP_WooCommerce_Integration::get_instance();
            WECP_Booking_Manager::get_instance();
        }
    }

    /**
     * Enqueue frontend scripts and styles
     */
    public function enqueue_scripts() {
        // CSS
        wp_enqueue_style(
            'wecp-calendar',
            WECP_PLUGIN_URL . 'assets/css/calendar.css',
            array(),
            WECP_VERSION
        );

        // JavaScript
        wp_enqueue_script(
            'wecp-calendar',
            WECP_PLUGIN_URL . 'assets/js/calendar.js',
            array('jquery'),
            WECP_VERSION,
            true
        );

        // Localize script
        wp_localize_script('wecp-calendar', 'wecpData', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wecp_nonce'),
            'monthNames' => $this->get_month_names(),
            'dayNames' => $this->get_day_names(),
            'strings' => array(
                'loading' => __('Loading...', 'wp-event-calendar-pro'),
                'noEvents' => __('No events found', 'wp-event-calendar-pro'),
                'addToCart' => __('Add to Cart', 'wp-event-calendar-pro'),
                'soldOut' => __('Sold Out', 'wp-event-calendar-pro'),
            )
        ));
    }

    /**
     * Enqueue admin scripts and styles
     */
    public function admin_enqueue_scripts($hook) {
        global $post_type;

        if ($post_type === 'wecp_event' || $hook === 'toplevel_page_wecp-settings') {
            wp_enqueue_style(
                'wecp-admin',
                WECP_PLUGIN_URL . 'assets/css/admin.css',
                array(),
                WECP_VERSION
            );

            wp_enqueue_script(
                'wecp-admin',
                WECP_PLUGIN_URL . 'assets/js/admin.js',
                array('jquery', 'jquery-ui-datepicker', 'wp-color-picker'),
                WECP_VERSION,
                true
            );

            wp_enqueue_style('wp-color-picker');
        }
    }

    /**
     * Check if WooCommerce is active
     */
    public function check_woocommerce() {
        if (!class_exists('WooCommerce')) {
            ?>
            <div class="notice notice-warning">
                <p>
                    <strong><?php _e('WP Event Calendar Pro', 'wp-event-calendar-pro'); ?>:</strong>
                    <?php _e('WooCommerce is required for booking functionality. Please install and activate WooCommerce.', 'wp-event-calendar-pro'); ?>
                </p>
            </div>
            <?php
        }
    }

    /**
     * Get localized month names
     */
    private function get_month_names() {
        return array(
            __('January', 'wp-event-calendar-pro'),
            __('February', 'wp-event-calendar-pro'),
            __('March', 'wp-event-calendar-pro'),
            __('April', 'wp-event-calendar-pro'),
            __('May', 'wp-event-calendar-pro'),
            __('June', 'wp-event-calendar-pro'),
            __('July', 'wp-event-calendar-pro'),
            __('August', 'wp-event-calendar-pro'),
            __('September', 'wp-event-calendar-pro'),
            __('October', 'wp-event-calendar-pro'),
            __('November', 'wp-event-calendar-pro'),
            __('December', 'wp-event-calendar-pro'),
        );
    }

    /**
     * Get localized day names
     */
    private function get_day_names() {
        return array(
            __('Sunday', 'wp-event-calendar-pro'),
            __('Monday', 'wp-event-calendar-pro'),
            __('Tuesday', 'wp-event-calendar-pro'),
            __('Wednesday', 'wp-event-calendar-pro'),
            __('Thursday', 'wp-event-calendar-pro'),
            __('Friday', 'wp-event-calendar-pro'),
            __('Saturday', 'wp-event-calendar-pro'),
        );
    }
}

/**
 * Initialize the plugin
 */
function wecp_init() {
    return WP_Event_Calendar_Pro::get_instance();
}

// Start the plugin
wecp_init();

/**
 * Activation hook
 */
register_activation_hook(__FILE__, 'wecp_activate');
function wecp_activate() {
    // Create event post type
    require_once WECP_PLUGIN_DIR . 'includes/class-event-post-type.php';
    WECP_Event_Post_Type::get_instance();

    // Flush rewrite rules
    flush_rewrite_rules();

    // Set default options
    $defaults = array(
        'calendar_view' => 'month',
        'events_per_page' => 10,
        'show_past_events' => false,
        'default_event_color' => '#3498db',
        'enable_lightbox' => true,
        'enable_booking' => true,
    );

    foreach ($defaults as $key => $value) {
        if (get_option('wecp_' . $key) === false) {
            add_option('wecp_' . $key, $value);
        }
    }
}

/**
 * Deactivation hook
 */
register_deactivation_hook(__FILE__, 'wecp_deactivate');
function wecp_deactivate() {
    flush_rewrite_rules();
}
