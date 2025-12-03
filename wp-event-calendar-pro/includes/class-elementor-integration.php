<?php
/**
 * Elementor Integration
 *
 * @package WP_Event_Calendar_Pro
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class WECP_Elementor_Integration {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Check if Elementor is installed and activated
        add_action('plugins_loaded', array($this, 'init'));
    }

    /**
     * Initialize Elementor integration
     */
    public function init() {
        // Check if Elementor is installed
        if (!did_action('elementor/loaded')) {
            return;
        }

        // Register widgets
        add_action('elementor/widgets/register', array($this, 'register_widgets'));

        // Register widget categories
        add_action('elementor/elements/categories_registered', array($this, 'register_categories'));

        // Enqueue Elementor editor scripts
        add_action('elementor/editor/after_enqueue_scripts', array($this, 'editor_scripts'));

        // Add Elementor support for custom post type
        add_action('init', array($this, 'add_elementor_support'));
    }

    /**
     * Register widget categories
     */
    public function register_categories($elements_manager) {
        $elements_manager->add_category(
            'wecp-events',
            array(
                'title' => __('Event Calendar Pro', 'wp-event-calendar-pro'),
                'icon' => 'fa fa-calendar',
            )
        );
    }

    /**
     * Register widgets
     */
    public function register_widgets($widgets_manager) {
        // Include widget files
        require_once WECP_PLUGIN_DIR . 'includes/elementor/widget-calendar.php';
        require_once WECP_PLUGIN_DIR . 'includes/elementor/widget-events-list.php';
        require_once WECP_PLUGIN_DIR . 'includes/elementor/widget-events-tile.php';
        require_once WECP_PLUGIN_DIR . 'includes/elementor/widget-single-event.php';

        // Register widgets
        $widgets_manager->register(new \WECP_Elementor_Calendar_Widget());
        $widgets_manager->register(new \WECP_Elementor_Events_List_Widget());
        $widgets_manager->register(new \WECP_Elementor_Events_Tile_Widget());
        $widgets_manager->register(new \WECP_Elementor_Single_Event_Widget());
    }

    /**
     * Enqueue editor scripts
     */
    public function editor_scripts() {
        wp_enqueue_style(
            'wecp-elementor-editor',
            WECP_PLUGIN_URL . 'assets/css/elementor-editor.css',
            array(),
            WECP_VERSION
        );
    }

    /**
     * Add Elementor support to custom post type
     */
    public function add_elementor_support() {
        add_post_type_support('wecp_event', 'elementor');
    }

    /**
     * Get available themes/skins
     */
    public static function get_available_skins() {
        return array(
            'default' => __('Default', 'wp-event-calendar-pro'),
            'minimal' => __('Minimal', 'wp-event-calendar-pro'),
            'modern' => __('Modern', 'wp-event-calendar-pro'),
            'bold' => __('Bold', 'wp-event-calendar-pro'),
            'classic' => __('Classic', 'wp-event-calendar-pro'),
            'elegant' => __('Elegant', 'wp-event-calendar-pro'),
        );
    }

    /**
     * Get event categories for Elementor controls
     */
    public static function get_event_categories() {
        $categories = get_terms(array(
            'taxonomy' => 'wecp_event_category',
            'hide_empty' => false,
        ));

        $options = array('' => __('All Categories', 'wp-event-calendar-pro'));

        if (!is_wp_error($categories)) {
            foreach ($categories as $category) {
                $options[$category->slug] = $category->name;
            }
        }

        return $options;
    }

    /**
     * Get event tags for Elementor controls
     */
    public static function get_event_tags() {
        $tags = get_terms(array(
            'taxonomy' => 'wecp_event_tag',
            'hide_empty' => false,
        ));

        $options = array('' => __('All Tags', 'wp-event-calendar-pro'));

        if (!is_wp_error($tags)) {
            foreach ($tags as $tag) {
                $options[$tag->slug] = $tag->name;
            }
        }

        return $options;
    }
}
