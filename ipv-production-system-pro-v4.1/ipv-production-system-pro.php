<?php
/**
 * Plugin Name: IPV Production System Pro
 * Plugin URI: https://aiedintorni.it
 * Description: Sistema di produzione avanzato per "Il Punto di Vista": importazione video YouTube, auto-import RSS, trascrizioni SupaData, generazione AI con Golden Prompt. UI moderna con Bootstrap 5 e grafici interattivi.
 * Version: 4.2.0
 * Author: Daniele / IPV
 * Text Domain: ipv-production-system-pro
 * Requires at least: 5.8
 * Requires PHP: 7.4
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'IPV_PROD_VERSION', '4.2.0' );
define( 'IPV_PROD_PLUGIN_FILE', __FILE__ );
define( 'IPV_PROD_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'IPV_PROD_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Includes
require_once IPV_PROD_PLUGIN_DIR . 'includes/class-logger.php';
require_once IPV_PROD_PLUGIN_DIR . 'includes/class-settings.php';
require_once IPV_PROD_PLUGIN_DIR . 'includes/class-cpt.php';
require_once IPV_PROD_PLUGIN_DIR . 'includes/class-supadata.php';
require_once IPV_PROD_PLUGIN_DIR . 'includes/class-ai-generator.php';
require_once IPV_PROD_PLUGIN_DIR . 'includes/class-queue.php';
require_once IPV_PROD_PLUGIN_DIR . 'includes/class-youtube-importer.php';
require_once IPV_PROD_PLUGIN_DIR . 'includes/class-rss-importer.php';
require_once IPV_PROD_PLUGIN_DIR . 'includes/class-youtube-api.php';
require_once IPV_PROD_PLUGIN_DIR . 'includes/class-bulk-import.php';
require_once IPV_PROD_PLUGIN_DIR . 'includes/class-video-list-columns.php';

class IPV_Production_System_Pro {

    private static $instance = null;

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // CPT
        add_action( 'init', [ 'IPV_Prod_CPT', 'register' ] );

        // Settings
        add_action( 'admin_init', [ 'IPV_Prod_Settings', 'register_settings' ] );

        // Menu admin
        add_action( 'admin_menu', [ $this, 'register_menu' ] );

        // Assets
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );

        // Cron schedule personalizzato "minute"
        add_filter( 'cron_schedules', [ $this, 'add_cron_schedules' ] );

        // Hook per processare la coda
        add_action( 'ipv_prod_process_queue', [ 'IPV_Prod_Queue', 'process_queue' ] );

        // AJAX endpoints
        add_action( 'wp_ajax_ipv_prod_get_stats', [ $this, 'ajax_get_stats' ] );
        add_action( 'wp_ajax_ipv_prod_process_queue', [ $this, 'ajax_process_queue' ] );
    }

    public function enqueue_admin_assets( $hook ) {
        // Carica solo nelle nostre pagine
        if ( strpos( $hook, 'ipv-production' ) === false ) {
            return;
        }

        // Bootstrap 5
        wp_enqueue_style(
            'ipv-bootstrap',
            'https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css',
            [],
            '5.3.2'
        );

        // Chart.js per grafici
        wp_enqueue_script(
            'ipv-chartjs',
            'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js',
            [],
            '4.4.0',
            true
        );

        // Bootstrap Icons
        wp_enqueue_style(
            'ipv-bootstrap-icons',
            'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css',
            [],
            '1.11.1'
        );

        // CSS personalizzato
        wp_enqueue_style(
            'ipv-prod-admin',
            IPV_PROD_PLUGIN_URL . 'assets/css/admin.css',
            [ 'ipv-bootstrap' ],
            IPV_PROD_VERSION
        );

        // JavaScript personalizzato
        wp_enqueue_script(
            'ipv-prod-admin',
            IPV_PROD_PLUGIN_URL . 'assets/js/admin.js',
            [ 'jquery', 'ipv-chartjs' ],
            IPV_PROD_VERSION,
            true
        );

        // Localize script per AJAX
        wp_localize_script(
            'ipv-prod-admin',
            'ipvProdAjax',
            [
                'ajaxurl' => admin_url( 'admin-ajax.php' ),
                'nonce'   => wp_create_nonce( 'ipv_prod_ajax' ),
            ]
        );
    }

    public function add_cron_schedules( $schedules ) {
        if ( ! isset( $schedules['minute'] ) ) {
            $schedules['minute'] = [
                'interval' => 60,
                'display'  => __( 'Ogni minuto', 'ipv-production-system-pro' ),
            ];
        }
        return $schedules;
    }

    public function register_menu() {
        add_menu_page(
            'IPV Production',
            'IPV Production',
            'manage_options',
            'ipv-production-dashboard',
            [ $this, 'render_dashboard_page' ],
            'dashicons-video-alt3',
            26
        );

        add_submenu_page(
            'ipv-production-dashboard',
            'Dashboard',
            'Dashboard',
            'manage_options',
            'ipv-production-dashboard',
            [ $this, 'render_dashboard_page' ]
        );

        add_submenu_page(
            'ipv-production-dashboard',
            'Importa Video',
            'Importa Video',
            'manage_options',
            'ipv-production-import',
            [ 'IPV_Prod_YouTube_Importer', 'render_page' ]
        );

        add_submenu_page(
            'ipv-production-dashboard',
            'Bulk Import',
            'Bulk Import',
            'manage_options',
            'ipv-production-bulk-import',
            [ 'IPV_Prod_Bulk_Import', 'render_page' ]
        );

        add_submenu_page(
            'ipv-production-dashboard',
            'Auto-Import RSS',
            'Auto-Import RSS',
            'manage_options',
            'ipv-production-rss',
            [ 'IPV_Prod_RSS_Importer', 'render_settings_page' ]
        );

        add_submenu_page(
            'ipv-production-dashboard',
            'Coda',
            'Coda',
            'manage_options',
            'ipv-production-queue',
            [ 'IPV_Prod_Queue', 'render_admin_page' ]
        );

        add_submenu_page(
            'ipv-production-dashboard',
            'Impostazioni',
            'Impostazioni',
            'manage_options',
            'ipv-production-settings',
            [ 'IPV_Prod_Settings', 'render_page' ]
        );
    }

    public function render_dashboard_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $stats = IPV_Prod_Queue::get_stats();
        $cron_status = $this->check_cron_status();
        $rss_stats = IPV_Prod_RSS_Importer::get_rss_stats();
        $rss_enabled = get_option( 'ipv_rss_enabled', false );
        ?>
        <div class="wrap ipv-prod-wrap">
            <div class="ipv-prod-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h1 class="mb-1">
                            <i class="bi bi-play-circle-fill text-primary me-2"></i>
                            IPV Production System Pro
                        </h1>
                        <p class="text-muted mb-0">
                            <span class="badge bg-success">v<?php echo esc_html( IPV_PROD_VERSION ); ?></span>
                            <span class="ms-2">Golden Prompt Edition</span>
                        </p>
                    </div>
                    <div>
                        <button class="btn btn-primary" id="ipv-manual-process">
                            <i class="bi bi-arrow-clockwise me-1"></i>
                            Processa Coda Ora
                        </button>
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=ipv-production-settings' ) ); ?>" class="btn btn-outline-secondary">
                            <i class="bi bi-gear me-1"></i>
                            Impostazioni
                        </a>
                    </div>
                </div>
            </div>

            <ul class="nav nav-tabs mb-4" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" href="<?php echo esc_url( admin_url( 'admin.php?page=ipv-production-dashboard' ) ); ?>">
                        <i class="bi bi-speedometer2 me-1"></i>Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo esc_url( admin_url( 'admin.php?page=ipv-production-import' ) ); ?>">
                        <i class="bi bi-upload me-1"></i>Importa Video
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo esc_url( admin_url( 'admin.php?page=ipv-production-rss' ) ); ?>">
                        <i class="bi bi-rss me-1"></i>Auto-Import RSS
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo esc_url( admin_url( 'admin.php?page=ipv-production-queue' ) ); ?>">
                        <i class="bi bi-list-task me-1"></i>Coda
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo esc_url( admin_url( 'admin.php?page=ipv-production-settings' ) ); ?>">
                        <i class="bi bi-gear me-1"></i>Impostazioni
                    </a>
                </li>
            </ul>

            <!-- Stats Cards -->
            <div class="row g-3 mb-4" id="ipv-stats-cards">
                <div class="col-md-3">
                    <div class="ipv-stat-card card-pending">
                        <div class="ipv-stat-icon">
                            <i class="bi bi-hourglass-split"></i>
                        </div>
                        <div class="ipv-stat-content">
                            <div class="ipv-stat-label">In Attesa</div>
                            <div class="ipv-stat-value" id="stat-pending"><?php echo intval( $stats['pending'] ); ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="ipv-stat-card card-processing">
                        <div class="ipv-stat-icon">
                            <i class="bi bi-arrow-repeat"></i>
                        </div>
                        <div class="ipv-stat-content">
                            <div class="ipv-stat-label">In Lavorazione</div>
                            <div class="ipv-stat-value" id="stat-processing"><?php echo intval( $stats['processing'] ); ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="ipv-stat-card card-done">
                        <div class="ipv-stat-icon">
                            <i class="bi bi-check-circle-fill"></i>
                        </div>
                        <div class="ipv-stat-content">
                            <div class="ipv-stat-label">Completati</div>
                            <div class="ipv-stat-value" id="stat-done"><?php echo intval( $stats['done'] ); ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="ipv-stat-card card-error">
                        <div class="ipv-stat-icon">
                            <i class="bi bi-exclamation-triangle-fill"></i>
                        </div>
                        <div class="ipv-stat-content">
                            <div class="ipv-stat-label">In Errore</div>
                            <div class="ipv-stat-value" id="stat-error"><?php echo intval( $stats['error'] ); ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- RSS Auto-Import Info -->
            <?php if ( $rss_enabled && ! empty( get_option( 'ipv_rss_feed_url', '' ) ) ) : ?>
            <div class="alert alert-info d-flex align-items-center mb-4">
                <i class="bi bi-rss-fill fs-4 me-3"></i>
                <div class="flex-grow-1">
                    <strong>RSS Auto-Import Attivo</strong>
                    <br>
                    <small>
                        Ultimo controllo: <?php echo $rss_stats['last_check'] === 'Mai' ? 'Mai' : esc_html( mysql2date( 'd/m H:i', $rss_stats['last_check'] ) ); ?> • 
                        Importati: <?php echo intval( $rss_stats['total_imported'] ); ?> video
                    </small>
                </div>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=ipv-production-rss' ) ); ?>" class="btn btn-sm btn-outline-primary">
                    Gestisci RSS
                </a>
            </div>
            <?php elseif ( ! $rss_enabled ) : ?>
            <div class="alert alert-warning d-flex align-items-center mb-4">
                <i class="bi bi-info-circle-fill fs-4 me-3"></i>
                <div class="flex-grow-1">
                    <strong>RSS Auto-Import Disattivo</strong>
                    <br>
                    <small>Importa automaticamente nuovi video dal tuo canale YouTube</small>
                </div>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=ipv-production-rss' ) ); ?>" class="btn btn-sm btn-warning">
                    Configura RSS
                </a>
            </div>
            <?php endif; ?>

            <div class="row g-4">
                <!-- Chart Statistiche -->
                <div class="col-lg-8">
                    <div class="card shadow-sm">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">
                                <i class="bi bi-bar-chart-fill text-primary me-2"></i>
                                Statistiche Coda
                            </h5>
                        </div>
                        <div class="card-body">
                            <canvas id="ipv-stats-chart" height="250"></canvas>
                        </div>
                    </div>
                </div>

                <!-- System Health -->
                <div class="col-lg-4">
                    <div class="card shadow-sm">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">
                                <i class="bi bi-heart-pulse-fill text-danger me-2"></i>
                                System Health
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="ipv-health-check mb-3">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-<?php echo $cron_status ? 'check-circle-fill text-success' : 'x-circle-fill text-danger'; ?> fs-4 me-2"></i>
                                    <div class="flex-grow-1">
                                        <div class="fw-medium">WordPress Cron</div>
                                        <small class="text-muted"><?php echo $cron_status ? 'Attivo' : 'Non attivo'; ?></small>
                                    </div>
                                </div>
                            </div>

                            <?php
                            $apis = [
                                [ 'name' => 'SupaData API', 'key' => get_option( 'ipv_supadata_api_key', '' ) ],
                                [ 'name' => 'OpenAI API', 'key' => get_option( 'ipv_openai_api_key', '' ) ],
                                [ 'name' => 'YouTube API', 'key' => get_option( 'ipv_youtube_api_key', '' ), 'optional' => true ],
                            ];

                            foreach ( $apis as $api ) :
                                $configured = ! empty( $api['key'] );
                                ?>
                                <div class="ipv-health-check mb-3">
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-<?php echo $configured ? 'check-circle-fill text-success' : ( isset( $api['optional'] ) ? 'info-circle-fill text-warning' : 'x-circle-fill text-danger' ); ?> fs-4 me-2"></i>
                                        <div class="flex-grow-1">
                                            <div class="fw-medium"><?php echo esc_html( $api['name'] ); ?></div>
                                            <small class="text-muted">
                                                <?php echo $configured ? 'Configurata' : ( isset( $api['optional'] ) ? 'Opzionale' : 'Non configurata' ); ?>
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="card shadow-sm mt-3">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">
                                <i class="bi bi-lightning-fill text-warning me-2"></i>
                                Quick Actions
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <a href="<?php echo esc_url( admin_url( 'admin.php?page=ipv-production-import' ) ); ?>" class="btn btn-primary">
                                    <i class="bi bi-plus-circle me-1"></i> Importa Video
                                </a>
                                <a href="<?php echo esc_url( admin_url( 'admin.php?page=ipv-production-queue' ) ); ?>" class="btn btn-outline-secondary">
                                    <i class="bi bi-list-ul me-1"></i> Vedi Coda
                                </a>
                                <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=video_ipv' ) ); ?>" class="btn btn-outline-secondary">
                                    <i class="bi bi-collection-play me-1"></i> Gestisci Video
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Toast Container -->
            <div class="toast-container position-fixed bottom-0 end-0 p-3">
                <div id="ipv-toast" class="toast" role="alert">
                    <div class="toast-header">
                        <i class="bi bi-info-circle-fill text-primary me-2"></i>
                        <strong class="me-auto">IPV Production</strong>
                        <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
                    </div>
                    <div class="toast-body">
                        <!-- Dynamic content -->
                    </div>
                </div>
            </div>
        </div>

        <script>
        jQuery(document).ready(function($) {
            // Inizializza Chart.js
            const ctx = document.getElementById('ipv-stats-chart');
            if (ctx) {
                new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: ['In Attesa', 'In Lavorazione', 'Completati', 'In Errore'],
                        datasets: [{
                            data: [
                                <?php echo intval( $stats['pending'] ); ?>,
                                <?php echo intval( $stats['processing'] ); ?>,
                                <?php echo intval( $stats['done'] ); ?>,
                                <?php echo intval( $stats['error'] ); ?>
                            ],
                            backgroundColor: [
                                '#ffc107',
                                '#17a2b8',
                                '#28a745',
                                '#dc3545'
                            ],
                            borderWidth: 0
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    padding: 20,
                                    font: {
                                        size: 13
                                    }
                                }
                            }
                        }
                    }
                });
            }
        });
        </script>
        <?php
    }

    private function check_cron_status() {
        return (bool) wp_next_scheduled( 'ipv_prod_process_queue' );
    }

    public function ajax_get_stats() {
        check_ajax_referer( 'ipv_prod_ajax', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => 'Non autorizzato.' ] );
        }

        $stats = IPV_Prod_Queue::get_stats();
        wp_send_json_success( $stats );
    }

    public function ajax_process_queue() {
        check_ajax_referer( 'ipv_prod_ajax', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => 'Non autorizzato.' ] );
        }

        IPV_Prod_Queue::process_queue();
        $stats = IPV_Prod_Queue::get_stats();

        wp_send_json_success( [
            'message' => 'Coda processata con successo.',
            'stats'   => $stats,
        ] );
    }

    public static function activate() {
        IPV_Prod_Queue::create_table();

        if ( ! wp_next_scheduled( 'ipv_prod_process_queue' ) ) {
            wp_schedule_event( time() + 60, 'minute', 'ipv_prod_process_queue' );
        }

        // Attiva RSS cron se abilitato
        $rss_enabled = get_option( 'ipv_rss_enabled', false );
        if ( $rss_enabled ) {
            IPV_Prod_RSS_Importer::activate_rss_cron();
        }

        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            IPV_Prod_Logger::log( 'Plugin attivato v' . IPV_PROD_VERSION );
        }
    }

    public static function deactivate() {
        $timestamp = wp_next_scheduled( 'ipv_prod_process_queue' );
        if ( $timestamp ) {
            wp_unschedule_event( $timestamp, 'ipv_prod_process_queue' );
        }

        // Disattiva RSS cron
        IPV_Prod_RSS_Importer::deactivate_rss_cron();

        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            IPV_Prod_Logger::log( 'Plugin disattivato.' );
        }
    }
}

register_activation_hook( __FILE__, [ 'IPV_Production_System_Pro', 'activate' ] );
register_deactivation_hook( __FILE__, [ 'IPV_Production_System_Pro', 'deactivate' ] );

IPV_Production_System_Pro::get_instance();
