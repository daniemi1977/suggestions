<?php
/**
 * Plugin Name: IPV Production System Pro
 * Plugin URI: https://ilpuntodivistachannel.com
 * Description: Sistema completo per importazione automatica video YouTube con trascrizione AI (SupaData) e generazione contenuti (OpenAI) per Il Punto di Vista
 * Version: 2.1.0
 * Author: Daniele
 * Author URI: https://ilpuntodivistachannel.com
 * License: GPL-2.0+
 * Text Domain: ipv-production-pro
 * Requires at least: 5.8
 * Requires PHP: 7.4
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Plugin constants
define('IPV_PRO_VERSION', '2.1.0');
define('IPV_PRO_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('IPV_PRO_PLUGIN_URL', plugin_dir_url(__FILE__));
define('IPV_PRO_INCLUDES_DIR', IPV_PRO_PLUGIN_DIR . 'includes/');

/**
 * Main Plugin Class
 */
class IPV_Production_System_Pro {

    /**
     * Single instance
     */
    private static $instance = null;

    /**
     * Class instances
     */
    public $cpt_video;
    public $admin_actions;
    public $youtube_api;
    public $youtube_data_updater;
    public $supadata_api;
    public $openai_api;
    public $queue_manager;
    public $rss_auto_import;
    public $settings;
    public $video_manager;
    public $dashboard;
    public $ajax_handlers;
    public $theme_integration;

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
        $this->load_dependencies();
        $this->init_hooks();
        $this->init_components();
    }

    /**
     * Load required files
     */
    private function load_dependencies() {
        require_once IPV_PRO_INCLUDES_DIR . 'class-logger.php';
        require_once IPV_PRO_INCLUDES_DIR . 'class-cpt-video.php';
        require_once IPV_PRO_INCLUDES_DIR . 'class-admin-actions.php';
        require_once IPV_PRO_INCLUDES_DIR . 'class-channel-config.php';
        require_once IPV_PRO_INCLUDES_DIR . 'class-prompt-builder.php';
        require_once IPV_PRO_INCLUDES_DIR . 'class-youtube-api.php';
        require_once IPV_PRO_INCLUDES_DIR . 'class-youtube-data-updater.php';
        require_once IPV_PRO_INCLUDES_DIR . 'class-supadata-api.php';
        require_once IPV_PRO_INCLUDES_DIR . 'class-openai-api.php';
        require_once IPV_PRO_INCLUDES_DIR . 'class-queue-manager.php';
        require_once IPV_PRO_INCLUDES_DIR . 'class-rss-auto-import.php';
        require_once IPV_PRO_INCLUDES_DIR . 'class-settings.php';
        require_once IPV_PRO_INCLUDES_DIR . 'class-video-manager.php';
        require_once IPV_PRO_INCLUDES_DIR . 'class-dashboard.php';
        require_once IPV_PRO_INCLUDES_DIR . 'class-ajax-handlers.php';
        require_once IPV_PRO_INCLUDES_DIR . 'class-theme-integration.php';
    }

    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        register_activation_hook(__FILE__, [$this, 'activate']);
        register_deactivation_hook(__FILE__, [$this, 'deactivate']);

        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        add_action('admin_menu', [$this, 'add_admin_menu']);
    }

    /**
     * Initialize plugin components
     */
    private function init_components() {
        $this->cpt_video = new IPV_CPT_Video();
        $this->admin_actions = new IPV_Admin_Actions();
        $this->youtube_api = new IPV_YouTube_API();
        $this->youtube_data_updater = new IPV_YouTube_Data_Updater();
        $this->supadata_api = new IPV_SupaData_API();
        $this->openai_api = new IPV_OpenAI_API();
        $this->queue_manager = new IPV_Queue_Manager();
        $this->rss_auto_import = new IPV_RSS_Auto_Import();
        $this->settings = new IPV_Settings();
        $this->video_manager = new IPV_Video_Manager();
        $this->dashboard = new IPV_Dashboard();
        $this->ajax_handlers = new IPV_Ajax_Handlers();
        $this->theme_integration = new IPV_Theme_Integration();
    }

    /**
     * Plugin activation
     */
    public function activate() {
        // Create queue table
        global $wpdb;
        $table_name = $wpdb->prefix . 'ipv_processing_queue';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            post_id bigint(20) NOT NULL,
            video_url varchar(500) NOT NULL,
            status varchar(50) NOT NULL DEFAULT 'pending',
            current_step varchar(100) DEFAULT NULL,
            error_message text DEFAULT NULL,
            retry_count int(11) DEFAULT 0,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY post_id (post_id),
            KEY status (status)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        // Set default options
        $defaults = [
            'ipv_pro_youtube_api_key' => '',
            'ipv_pro_supadata_api_key' => '',
            'ipv_pro_openai_api_key' => '',
            'ipv_pro_openai_model' => 'gpt-4o-mini',
            'ipv_pro_transcript_mode' => 'auto',
            'ipv_pro_transcript_timeout' => 300,
            'ipv_pro_max_retry' => 3,
            'ipv_pro_category_mapping' => [
                '22' => 0, // People & Blogs
                '10' => 0, // Music
                '24' => 0, // Entertainment
                '25' => 0, // News & Politics
                '28' => 0  // Science & Technology
            ],
            'ipv_pro_default_category' => 1,
            'ipv_pro_auto_publish' => false,
            'ipv_pro_batch_size' => 5,
            'ipv_pro_rss_feed_url' => '',
            'ipv_pro_auto_import_enabled' => false,
            'ipv_pro_auto_import_interval' => 60,
            'ipv_pro_auto_import_max_videos' => 10,
            'ipv_pro_auto_import_email_notifications' => false,
            'ipv_pro_auto_import_notification_email' => get_option('admin_email')
        ];

        foreach ($defaults as $key => $value) {
            if (get_option($key) === false) {
                add_option($key, $value);
            }
        }

        // Schedule cron for queue processing
        if (!wp_next_scheduled('ipv_pro_process_queue')) {
            wp_schedule_event(time(), 'every_minute', 'ipv_pro_process_queue');
        }

        // Schedule cron for RSS auto-import
        if (!wp_next_scheduled('ipv_pro_auto_import_check')) {
            wp_schedule_event(time(), 'hourly', 'ipv_pro_auto_import_check');
        }

        // Schedule cron for YouTube data updates
        IPV_YouTube_Data_Updater::schedule_cron();

        // Create error logs table for Logger
        IPV_Pro_Logger::create_error_logs_table();
    }

    /**
     * Plugin deactivation
     */
    public function deactivate() {
        wp_clear_scheduled_hook('ipv_pro_process_queue');
        wp_clear_scheduled_hook('ipv_pro_auto_import_check');

        // Unschedule YouTube data updates
        IPV_YouTube_Data_Updater::unschedule_cron();
    }

    /**
     * Add admin menu pages
     */
    public function add_admin_menu() {
        // Main menu
        add_menu_page(
            'IPV Production Pro',
            'IPV Production',
            'manage_options',
            'ipv-production-pro',
            [$this->dashboard, 'render_page'],
            'dashicons-video-alt3',
            30
        );

        // Dashboard submenu
        add_submenu_page(
            'ipv-production-pro',
            'Dashboard',
            'Dashboard',
            'manage_options',
            'ipv-production-pro',
            [$this->dashboard, 'render_page']
        );

        // All Videos (CPT) submenu
        add_submenu_page(
            'ipv-production-pro',
            'Tutti i Video',
            'Tutti i Video',
            'manage_options',
            'edit.php?post_type=ipv_video'
        );

        // Add New Video submenu
        add_submenu_page(
            'ipv-production-pro',
            'Aggiungi Video',
            'Aggiungi Video',
            'manage_options',
            'post-new.php?post_type=ipv_video'
        );

        // Topics taxonomy
        add_submenu_page(
            'ipv-production-pro',
            'Argomenti',
            'Argomenti',
            'manage_options',
            'edit-tags.php?taxonomy=ipv_topic&post_type=ipv_video'
        );

        // Guests taxonomy
        add_submenu_page(
            'ipv-production-pro',
            'Ospiti',
            'Ospiti',
            'manage_options',
            'edit-tags.php?taxonomy=ipv_guest&post_type=ipv_video'
        );

        // Channel Themes taxonomy
        add_submenu_page(
            'ipv-production-pro',
            'Temi Canale',
            'Temi Canale',
            'manage_options',
            'edit-tags.php?taxonomy=ipv_channel_theme&post_type=ipv_video'
        );

        // Video Manager submenu
        add_submenu_page(
            'ipv-production-pro',
            'Video Manager',
            'Video Manager',
            'manage_options',
            'ipv-video-manager',
            [$this->video_manager, 'render_page']
        );

        // Settings submenu
        add_submenu_page(
            'ipv-production-pro',
            'Impostazioni',
            'Impostazioni',
            'manage_options',
            'ipv-production-settings',
            [$this->settings, 'render_page']
        );
    }

    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        // Only load on plugin pages
        if (strpos($hook, 'ipv-') === false) {
            return;
        }

        // CSS
        wp_enqueue_style(
            'ipv-pro-admin',
            IPV_PRO_PLUGIN_URL . 'assets/css/admin.css',
            [],
            IPV_PRO_VERSION
        );

        // JavaScript
        wp_enqueue_script(
            'ipv-pro-admin',
            IPV_PRO_PLUGIN_URL . 'assets/js/admin.js',
            ['jquery'],
            IPV_PRO_VERSION,
            true
        );

        // Localize script
        wp_localize_script('ipv-pro-admin', 'ipvPro', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ipv_pro_nonce'),
            'strings' => [
                'confirm_delete' => 'Sei sicuro di voler eliminare questo video dalla coda?',
                'confirm_retry' => 'Vuoi riprovare l\'elaborazione di questo video?',
                'processing' => 'Elaborazione in corso...',
                'success' => 'Operazione completata con successo!',
                'error' => 'Si è verificato un errore.'
            ]
        ]);
    }
}

/**
 * Add custom cron schedule
 */
add_filter('cron_schedules', function($schedules) {
    $schedules['every_minute'] = [
        'interval' => 60,
        'display' => __('Every Minute', 'ipv-production-pro')
    ];
    return $schedules;
});

/**
 * Hook queue processing to cron
 */
add_action('ipv_pro_process_queue', function() {
    $queue_manager = IPV_Production_System_Pro::get_instance()->queue_manager;
    $queue_manager->process_queue();
});

/**
 * Hook RSS auto-import to cron
 */
add_action('ipv_pro_auto_import_check', function() {
    $rss_auto_import = IPV_Production_System_Pro::get_instance()->rss_auto_import;
    if ($rss_auto_import->is_enabled()) {
        $rss_auto_import->check_and_import();
    }
});

/**
 * Initialize plugin
 */
function ipv_production_system_pro_init() {
    return IPV_Production_System_Pro::get_instance();
}

// Start the plugin
ipv_production_system_pro_init();
