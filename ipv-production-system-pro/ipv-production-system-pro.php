<?php
/**
 * Plugin Name: IPV Production System Pro - ULTIMATE Edition
 * Plugin URI: https://ilpuntodivistachannel.com
 * Description: Sistema editoriale enterprise con Dashboard Ultimate, Tools Avanzati, RSS Auto-Taxonomy (categorie, relatori, tag automatici), controllo media duplicati/orfani, diagnostics, cache management, export/import config, orphan videos detector e molto altro.
 * Version: 2.3.3
 * Author: Daniele
 * Author URI: https://ilpuntodivistachannel.com
 * License: GPL-2.0+
 * Text Domain: ipv-production-pro
 * Requires at least: 5.8
 * Requires PHP: 7.4
 *
 * CHANGELOG v2.3.3 - Auto-Repair Orphan Videos System
 * ✅ Sistema automatico di riparazione video orfani
 * ✅ Opzione nelle impostazioni "Auto-Ripara Video Orfani" (abilitata di default)
 * ✅ Si esegue automaticamente DOPO ogni RSS auto-import
 * ✅ Trova video esistenti nel database ma non nella queue
 * ✅ Li riassocia automaticamente (status: 'auto_repaired')
 * ✅ Logging dettagliato di tutte le operazioni
 * ✅ Report nel messaggio auto-import: "Auto-repair: X video orfani riparati"
 * ✅ Può essere disabilitato se non serve
 * ✅ Limite 100 video per esecuzione (performance)
 * ✅ Skip video senza URL (con logging errore)
 * ✅ RISOLVE PER SEMPRE il problema dei video invisibili!
 *
 * Come funziona:
 * 1. Ogni volta che RSS auto-import viene eseguito
 * 2. Dopo import, cerca video orfani (LEFT JOIN)
 * 3. Per ogni video orfano trovato:
 *    - Recupera video_url da meta (o ricostruisce da video_id)
 *    - Lo riaggiunge alla queue come 'completed'
 *    - Logga l'operazione
 * 4. I video diventano immediatamente visibili
 * 5. Report finale con count video riparati
 *
 * Configurazione: Impostazioni → RSS Auto-Import → "Auto-Ripara Video Orfani"
 *
 * CHANGELOG v2.3.2 - Orphan Videos Detector & Deep Clean Database Tool
 * ✅ Rilevamento automatico video orfani (post senza queue entry)
 * ✅ Lista dettagliata video orfani con ID, titolo, stato, data
 * ✅ Funzione "Ripara" - riassocia video orfani alla queue (li rende visibili)
 * ✅ Funzione "Elimina" - rimozione definitiva video orfani dal database
 * ✅ Statistiche in tempo reale: Video in Queue vs Video Post Totali
 * ✅ Alert visivo quando ci sono video orfani
 * ✅ Tool integrato in Advanced Tools per facile accesso
 * ✅ Conferme di sicurezza per operazioni distruttive
 * ✅ Auto-refresh dopo riparazione/eliminazione
 * ✅ Risolve problema: "video importati precedentemente non visibili dopo aggiornamento plugin"
 * ✅ **DEEP CLEAN DATABASE** - Pulizia profonda di TUTTE le tabelle:
 *    • Elimina tutti i post ipv_video (pubblicati, bozze, trash)
 *    • Elimina tutti i postmeta (_ipv_*)
 *    • Pulisce completamente la coda processing (ipv_processing_queue)
 *    • Rimuove tutte le relazioni con categorie e tassonomie
 *    • Pulisce termini orfani nelle tassonomie custom
 *    • Ottimizza tutte le tabelle dopo la pulizia
 *    • Triple conferma di sicurezza (con digitazione parola "ELIMINA")
 *    • Transaction SQL per rollback in caso di errori
 *    • Report dettagliato con conteggio record eliminati
 *
 * CHANGELOG v2.3.1 - RSS Auto-Taxonomy System
 * ✅ Auto-import categorie canale YouTube → WordPress categories
 * ✅ Auto-estrazione relatori/ospiti dal titolo → ipv_guest taxonomy + categories
 * ✅ Auto-estrazione hashtags dalla description → WordPress tags
 * ✅ Auto-estrazione topics dalla description → ipv_topic taxonomy
 * ✅ Auto-assegnazione channel name → ipv_channel_theme taxonomy
 * ✅ Pattern matching intelligente per speaker names (con/ft./feat./ospite/guest)
 * ✅ Parsing avanzato RSS feed con tutti i metadati disponibili
 * ✅ Taxonomy assignment BEFORE AI processing (early assignment)
 * ✅ Logging dettagliato per debug e monitoring
 *
 * CHANGELOG v2.3.0 - ULTIMATE Edition
 * ✅ Dashboard Ultimate con Chart.js (30-day analytics)
 * ✅ Tools Avanzati: 16 enterprise tools
 * ✅ Media Duplicates/Orphan Checker
 * ✅ Export/Import Config, Cache Management, DB Optimizer
 * ✅ Diagnostics Panel, API Testing, Log Viewer
 * ✅ CSV Report Generator, Bulk Operations
 * ✅ All buttons with working AJAX handlers (no placeholders)
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Plugin constants
define('IPV_PRO_VERSION', '2.3.2');
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
        // Core System
        require_once IPV_PRO_INCLUDES_DIR . 'class-logger.php';
        require_once IPV_PRO_INCLUDES_DIR . 'class-cpt-video.php';
        require_once IPV_PRO_INCLUDES_DIR . 'class-admin-actions.php';
        require_once IPV_PRO_INCLUDES_DIR . 'class-channel-config.php';
        require_once IPV_PRO_INCLUDES_DIR . 'class-prompt-builder.php';

        // API Integration
        require_once IPV_PRO_INCLUDES_DIR . 'class-youtube-api.php';
        require_once IPV_PRO_INCLUDES_DIR . 'class-youtube-data-updater.php';
        require_once IPV_PRO_INCLUDES_DIR . 'class-supadata-api.php';
        require_once IPV_PRO_INCLUDES_DIR . 'class-openai-api.php';

        // Processing & Management
        require_once IPV_PRO_INCLUDES_DIR . 'class-queue-manager.php';
        require_once IPV_PRO_INCLUDES_DIR . 'class-rss-auto-import.php';
        require_once IPV_PRO_INCLUDES_DIR . 'class-video-manager.php';

        // Admin Interface
        require_once IPV_PRO_INCLUDES_DIR . 'class-settings.php';
        require_once IPV_PRO_INCLUDES_DIR . 'class-dashboard.php';
        require_once IPV_PRO_INCLUDES_DIR . 'class-dashboard-ultimate.php';  // NEW: Ultimate Dashboard
        require_once IPV_PRO_INCLUDES_DIR . 'class-tools-advanced.php';      // NEW: Advanced Tools
        require_once IPV_PRO_INCLUDES_DIR . 'class-ajax-handlers.php';

        // Frontend
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

        // Initialize ULTIMATE Edition components
        IPV_Dashboard_Ultimate::init();
        IPV_Tools_Advanced::init();
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
