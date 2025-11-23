<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class IPV_Prod_RSS_Importer {

    /**
     * Inizializza gli hook
     */
    public static function init() {
        // Registra il cron job RSS
        add_action( 'ipv_prod_rss_import', [ __CLASS__, 'process_rss_feed' ] );
        
        // Aggiungi schedule personalizzato se non esiste
        add_filter( 'cron_schedules', [ __CLASS__, 'add_rss_schedules' ] );
    }

    /**
     * Aggiunge schedule personalizzati per il cron RSS
     */
    public static function add_rss_schedules( $schedules ) {
        if ( ! isset( $schedules['every_30_minutes'] ) ) {
            $schedules['every_30_minutes'] = [
                'interval' => 1800,
                'display'  => __( 'Ogni 30 minuti', 'ipv-production-system-pro' ),
            ];
        }
        
        if ( ! isset( $schedules['hourly'] ) ) {
            $schedules['hourly'] = [
                'interval' => 3600,
                'display'  => __( 'Ogni ora', 'ipv-production-system-pro' ),
            ];
        }

        if ( ! isset( $schedules['every_6_hours'] ) ) {
            $schedules['every_6_hours'] = [
                'interval' => 21600,
                'display'  => __( 'Ogni 6 ore', 'ipv-production-system-pro' ),
            ];
        }

        if ( ! isset( $schedules['twicedaily'] ) ) {
            $schedules['twicedaily'] = [
                'interval' => 43200,
                'display'  => __( 'Due volte al giorno', 'ipv-production-system-pro' ),
            ];
        }

        return $schedules;
    }

    /**
     * Processa il feed RSS del canale
     */
    public static function process_rss_feed() {
        $feed_url = get_option( 'ipv_rss_feed_url', '' );
        
        if ( empty( $feed_url ) ) {
            IPV_Prod_Logger::log( 'RSS Import: Feed URL non configurato' );
            return;
        }

        // Fetch del feed
        $response = wp_remote_get( $feed_url, [
            'timeout' => 30,
            'headers' => [
                'User-Agent' => 'IPV Production System Pro/4.1.0',
            ],
        ] );

        if ( is_wp_error( $response ) ) {
            IPV_Prod_Logger::log( 'RSS Import: Errore fetch feed', [
                'error' => $response->get_error_message(),
            ] );
            return;
        }

        $body = wp_remote_retrieve_body( $response );
        if ( empty( $body ) ) {
            IPV_Prod_Logger::log( 'RSS Import: Feed vuoto' );
            return;
        }

        // Parse XML
        libxml_use_internal_errors( true );
        $xml = simplexml_load_string( $body );
        
        if ( false === $xml ) {
            IPV_Prod_Logger::log( 'RSS Import: Errore parsing XML' );
            return;
        }

        // Namespace per Atom feed
        $xml->registerXPathNamespace( 'atom', 'http://www.w3.org/2005/Atom' );
        $xml->registerXPathNamespace( 'yt', 'http://www.youtube.com/xml/schemas/2015' );

        // Estrai entries
        $entries = $xml->xpath( '//atom:entry' );
        
        if ( empty( $entries ) ) {
            IPV_Prod_Logger::log( 'RSS Import: Nessun video trovato nel feed' );
            return;
        }

        $imported_count = 0;
        $skipped_count  = 0;
        $limit          = get_option( 'ipv_rss_import_limit', 10 );

        foreach ( array_slice( $entries, 0, $limit ) as $entry ) {
            $video_id = self::extract_video_id_from_entry( $entry );
            
            if ( empty( $video_id ) ) {
                continue;
            }

            // Verifica se già importato
            if ( self::video_already_imported( $video_id ) ) {
                $skipped_count++;
                continue;
            }

            // Costruisci URL
            $video_url = 'https://www.youtube.com/watch?v=' . $video_id;

            // Aggiungi alla coda
            IPV_Prod_Queue::enqueue( $video_id, $video_url, 'rss' );
            $imported_count++;
        }

        // Salva statistiche
        self::update_rss_stats( $imported_count, $skipped_count );

        IPV_Prod_Logger::log( 'RSS Import: Completato', [
            'imported' => $imported_count,
            'skipped'  => $skipped_count,
        ] );
    }

    /**
     * Estrae il video ID da un entry XML
     */
    protected static function extract_video_id_from_entry( $entry ) {
        // Namespace
        $entry->registerXPathNamespace( 'yt', 'http://www.youtube.com/xml/schemas/2015' );
        
        // Cerca videoId
        $video_id_nodes = $entry->xpath( 'yt:videoId' );
        
        if ( ! empty( $video_id_nodes ) ) {
            return (string) $video_id_nodes[0];
        }

        // Fallback: estrai da link
        $link = (string) $entry->link['href'];
        if ( preg_match( '/watch\?v=([^&]+)/', $link, $matches ) ) {
            return $matches[1];
        }

        return '';
    }

    /**
     * Verifica se un video è già stato importato
     */
    protected static function video_already_imported( $video_id ) {
        global $wpdb;
        
        // Cerca nel CPT
        $post_exists = get_posts( [
            'post_type'   => 'video_ipv',
            'meta_key'    => '_ipv_video_id',
            'meta_value'  => $video_id,
            'fields'      => 'ids',
            'numberposts' => 1,
        ] );

        if ( ! empty( $post_exists ) ) {
            return true;
        }

        // Cerca nella coda
        $table = IPV_Prod_Queue::table_name();
        $queue_exists = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$table} WHERE video_id = %s",
                $video_id
            )
        );

        return $queue_exists > 0;
    }

    /**
     * Aggiorna le statistiche RSS
     */
    protected static function update_rss_stats( $imported, $skipped ) {
        $stats = get_option( 'ipv_rss_stats', [
            'last_check'     => '',
            'total_imported' => 0,
            'total_skipped'  => 0,
            'last_imported'  => 0,
        ] );

        $stats['last_check']     = current_time( 'mysql' );
        $stats['total_imported'] += $imported;
        $stats['total_skipped']  += $skipped;
        $stats['last_imported']  = $imported;

        update_option( 'ipv_rss_stats', $stats );
    }

    /**
     * Ottieni le statistiche RSS
     */
    public static function get_rss_stats() {
        return get_option( 'ipv_rss_stats', [
            'last_check'     => 'Mai',
            'total_imported' => 0,
            'total_skipped'  => 0,
            'last_imported'  => 0,
        ] );
    }

    /**
     * Test manuale del feed RSS
     */
    public static function test_rss_feed( $feed_url ) {
        $response = wp_remote_get( $feed_url, [
            'timeout' => 30,
        ] );

        if ( is_wp_error( $response ) ) {
            return [
                'success' => false,
                'message' => 'Errore connessione: ' . $response->get_error_message(),
            ];
        }

        $code = wp_remote_retrieve_response_code( $response );
        if ( $code !== 200 ) {
            return [
                'success' => false,
                'message' => 'Errore HTTP ' . $code,
            ];
        }

        $body = wp_remote_retrieve_body( $response );
        if ( empty( $body ) ) {
            return [
                'success' => false,
                'message' => 'Feed vuoto',
            ];
        }

        libxml_use_internal_errors( true );
        $xml = simplexml_load_string( $body );
        
        if ( false === $xml ) {
            return [
                'success' => false,
                'message' => 'XML non valido',
            ];
        }

        $xml->registerXPathNamespace( 'atom', 'http://www.w3.org/2005/Atom' );
        $entries = $xml->xpath( '//atom:entry' );

        if ( empty( $entries ) ) {
            return [
                'success' => false,
                'message' => 'Nessun video trovato nel feed',
            ];
        }

        return [
            'success'      => true,
            'message'      => 'Feed valido!',
            'video_count'  => count( $entries ),
            'latest_video' => self::extract_video_id_from_entry( $entries[0] ),
        ];
    }

    /**
     * Attiva il cron RSS
     */
    public static function activate_rss_cron() {
        $schedule = get_option( 'ipv_rss_schedule', 'hourly' );
        
        if ( ! wp_next_scheduled( 'ipv_prod_rss_import' ) ) {
            wp_schedule_event( time() + 300, $schedule, 'ipv_prod_rss_import' );
            IPV_Prod_Logger::log( 'RSS Cron: Attivato con schedule ' . $schedule );
        }
    }

    /**
     * Disattiva il cron RSS
     */
    public static function deactivate_rss_cron() {
        $timestamp = wp_next_scheduled( 'ipv_prod_rss_import' );
        if ( $timestamp ) {
            wp_unschedule_event( $timestamp, 'ipv_prod_rss_import' );
            IPV_Prod_Logger::log( 'RSS Cron: Disattivato' );
        }
    }

    /**
     * Riattiva il cron con nuovo schedule
     */
    public static function reschedule_rss_cron( $new_schedule ) {
        self::deactivate_rss_cron();
        update_option( 'ipv_rss_schedule', $new_schedule );
        self::activate_rss_cron();
    }

    /**
     * Importa manualmente dal feed
     */
    public static function manual_import() {
        self::process_rss_feed();
        
        $stats = self::get_rss_stats();
        
        return [
            'success'  => true,
            'imported' => $stats['last_imported'],
            'message'  => sprintf(
                'Importati %d nuovi video dal feed RSS',
                $stats['last_imported']
            ),
        ];
    }

    /**
     * Renderizza la pagina impostazioni RSS
     */
    public static function render_settings_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        // Salvataggio impostazioni
        if ( isset( $_POST['ipv_save_rss_settings'] ) ) {
            check_admin_referer( 'ipv_rss_settings_save' );
            self::save_rss_settings();
            ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i>
                <strong>Impostazioni RSS salvate!</strong> Il feed verrà controllato automaticamente.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php
        }

        // Test feed
        if ( isset( $_POST['ipv_test_rss_feed'] ) ) {
            check_admin_referer( 'ipv_rss_test' );
            $feed_url = isset( $_POST['ipv_rss_feed_url'] ) ? esc_url_raw( $_POST['ipv_rss_feed_url'] ) : '';
            $test_result = self::test_rss_feed( $feed_url );
        }

        // Importazione manuale
        if ( isset( $_POST['ipv_manual_rss_import'] ) ) {
            check_admin_referer( 'ipv_rss_manual' );
            $import_result = self::manual_import();
        }

        $feed_url      = get_option( 'ipv_rss_feed_url', '' );
        $rss_enabled   = get_option( 'ipv_rss_enabled', false );
        $rss_schedule  = get_option( 'ipv_rss_schedule', 'hourly' );
        $import_limit  = get_option( 'ipv_rss_import_limit', 10 );
        $stats         = self::get_rss_stats();
        $next_run      = wp_next_scheduled( 'ipv_prod_rss_import' );
        
        require IPV_PROD_PLUGIN_DIR . 'includes/views/rss-settings.php';
    }

    /**
     * Salva le impostazioni RSS
     */
    protected static function save_rss_settings() {
        $fields = [
            'ipv_rss_feed_url'    => 'esc_url_raw',
            'ipv_rss_enabled'     => 'rest_sanitize_boolean',
            'ipv_rss_schedule'    => 'sanitize_text_field',
            'ipv_rss_import_limit' => 'absint',
        ];

        foreach ( $fields as $field => $sanitize_func ) {
            if ( isset( $_POST[ $field ] ) ) {
                $value = call_user_func( $sanitize_func, wp_unslash( $_POST[ $field ] ) );
                update_option( $field, $value );
            }
        }

        // Gestisci cron
        $enabled = get_option( 'ipv_rss_enabled', false );
        $schedule = get_option( 'ipv_rss_schedule', 'hourly' );

        if ( $enabled ) {
            self::reschedule_rss_cron( $schedule );
        } else {
            self::deactivate_rss_cron();
        }
    }
}

// Inizializza
IPV_Prod_RSS_Importer::init();
