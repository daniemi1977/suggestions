<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class IPV_Prod_Queue {

    public static function table_name() {
        global $wpdb;
        return $wpdb->prefix . 'ipv_prod_queue';
    }

    public static function create_table() {
        global $wpdb;

        $table   = self::table_name();
        $charset = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            video_id VARCHAR(32) NOT NULL,
            video_url TEXT NOT NULL,
            source VARCHAR(20) NOT NULL DEFAULT 'manual',
            status VARCHAR(20) NOT NULL DEFAULT 'pending',
            attempts SMALLINT UNSIGNED NOT NULL DEFAULT 0,
            last_error TEXT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY  (id),
            KEY status (status),
            KEY video_id (video_id)
        ) {$charset};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
    }

    public static function enqueue( $video_id, $video_url, $source = 'manual' ) {
        global $wpdb;

        $table = self::table_name();
        $now   = current_time( 'mysql' );

        $wpdb->insert(
            $table,
            [
                'video_id'   => $video_id,
                'video_url'  => $video_url,
                'source'     => $source,
                'status'     => 'pending',
                'attempts'   => 0,
                'last_error' => '',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [ '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s' ]
        );

        IPV_Prod_Logger::log( 'Job inserito in coda', [ 'video_id' => $video_id, 'source' => $source ] );
    }

    public static function get_stats() {
        global $wpdb;
        $table = self::table_name();

        $statuses = [ 'pending', 'processing', 'done', 'error' ];
        $out      = [];

        foreach ( $statuses as $status ) {
            $out[ $status ] = (int) $wpdb->get_var(
                $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE status = %s", $status )
            );
        }

        return $out;
    }

    protected static function get_pending_jobs( $limit = 3 ) {
        global $wpdb;
        $table = self::table_name();

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$table} WHERE status = %s ORDER BY created_at ASC LIMIT %d",
                'pending',
                $limit
            )
        );
    }

    public static function process_queue() {
        global $wpdb;

        $jobs = self::get_pending_jobs( 3 );
        if ( empty( $jobs ) ) {
            return;
        }

        foreach ( $jobs as $job ) {
            $table = self::table_name();

            $wpdb->update(
                $table,
                [
                    'status'     => 'processing',
                    'attempts'   => (int) $job->attempts + 1,
                    'updated_at' => current_time( 'mysql' ),
                ],
                [ 'id' => $job->id ],
                [ '%s', '%d', '%s' ],
                [ '%d' ]
            );

            try {
                self::process_single_job( $job );
                $wpdb->update(
                    $table,
                    [
                        'status'     => 'done',
                        'last_error' => '',
                        'updated_at' => current_time( 'mysql' ),
                    ],
                    [ 'id' => $job->id ],
                    [ '%s', '%s', '%s' ],
                    [ '%d' ]
                );
            } catch ( Exception $e ) {
                $wpdb->update(
                    $table,
                    [
                        'status'     => 'error',
                        'last_error' => $e->getMessage(),
                        'updated_at' => current_time( 'mysql' ),
                    ],
                    [ 'id' => $job->id ],
                    [ '%s', '%s', '%s' ],
                    [ '%d' ]
                );
                IPV_Prod_Logger::log( 'Errore processando job', [ 'id' => $job->id, 'error' => $e->getMessage() ] );
            }
        }
    }

    protected static function process_single_job( $job ) {
        $video_id  = $job->video_id;
        $video_url = $job->video_url;

        if ( empty( $video_id ) ) {
            throw new Exception( 'Video ID mancante.' );
        }

        $post_id = self::get_post_id_by_video_id( $video_id );
        if ( ! $post_id ) {
            // Usa la nuova YouTube API per ottenere TUTTI i dati
            $video_data = IPV_Prod_YouTube_API::get_video_data( $video_id );

            if ( is_wp_error( $video_data ) ) {
                // Fallback: crea comunque il post con dati minimi
                $title = 'Video YouTube ' . $video_id;
                $video_data = null;
            } else {
                $title = ! empty( $video_data['title'] ) ? $video_data['title'] : 'Video YouTube ' . $video_id;
            }

            $post_arr = [
                'post_type'   => 'video_ipv',
                'post_title'  => $title,
                'post_status' => 'publish',
            ];

            $post_id = wp_insert_post( $post_arr );
            if ( ! $post_id || is_wp_error( $post_id ) ) {
                throw new Exception( 'Impossibile creare il post video.' );
            }

            // Salva i meta base
            update_post_meta( $post_id, '_ipv_video_id', $video_id );
            update_post_meta( $post_id, '_ipv_youtube_url', $video_url );
            update_post_meta( $post_id, '_ipv_source', $job->source );

            // Se abbiamo i dati YouTube completi, salvali tutti
            if ( $video_data ) {
                IPV_Prod_YouTube_API::save_video_meta( $post_id, $video_data );

                // Scarica e imposta la thumbnail come featured image
                self::set_featured_image_from_youtube( $post_id, $video_data['thumbnail_url'] );

                // Importa i tag YouTube come tag del post
                if ( ! empty( $video_data['tags'] ) ) {
                    wp_set_object_terms( $post_id, $video_data['tags'], 'video_tag', true );
                }
            }
        }

        // Genera trascrizione
        $mode       = get_option( 'ipv_transcript_mode', 'auto' );
        $transcript = IPV_Prod_Supadata::get_transcript( $video_id, $mode );
        if ( is_wp_error( $transcript ) ) {
            throw new Exception( 'Errore SupaData: ' . $transcript->get_error_message() );
        }
        update_post_meta( $post_id, '_ipv_transcript', $transcript );

        // Genera descrizione AI
        $desc = IPV_Prod_AI_Generator::generate_description( get_the_title( $post_id ), $transcript );
        if ( is_wp_error( $desc ) ) {
            throw new Exception( 'Errore OpenAI: ' . $desc->get_error_message() );
        }

        // Salva la descrizione come contenuto del post
        wp_update_post(
            [
                'ID'           => $post_id,
                'post_content' => $desc,
            ]
        );

        update_post_meta( $post_id, '_ipv_ai_description', $desc );

        IPV_Prod_Logger::log( 'Job completato con successo', [
            'post_id'  => $post_id,
            'video_id' => $video_id,
        ] );
    }

    /**
     * Scarica e imposta la thumbnail di YouTube come featured image
     */
    protected static function set_featured_image_from_youtube( $post_id, $thumbnail_url ) {
        if ( empty( $thumbnail_url ) || has_post_thumbnail( $post_id ) ) {
            return false;
        }

        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        $tmp = download_url( $thumbnail_url );
        if ( is_wp_error( $tmp ) ) {
            return false;
        }

        $file_array = [
            'name'     => 'youtube-thumb-' . $post_id . '.jpg',
            'tmp_name' => $tmp,
        ];

        $attach_id = media_handle_sideload( $file_array, $post_id );

        if ( file_exists( $tmp ) ) {
            @unlink( $tmp );
        }

        if ( is_wp_error( $attach_id ) ) {
            return false;
        }

        set_post_thumbnail( $post_id, $attach_id );
        return true;
    }

    protected static function get_post_id_by_video_id( $video_id ) {
        $posts = get_posts(
            [
                'post_type'   => 'video_ipv',
                'meta_key'    => '_ipv_video_id',
                'meta_value'  => $video_id,
                'fields'      => 'ids',
                'numberposts' => 1,
            ]
        );

        if ( ! empty( $posts ) ) {
            return (int) $posts[0];
        }
        return 0;
    }

    protected static function fetch_youtube_data( $video_id ) {
        $api_key = get_option( 'ipv_youtube_api_key', '' );
        if ( empty( $api_key ) ) {
            return [
                'title' => 'Video YouTube ' . $video_id,
            ];
        }

        $url = add_query_arg(
            [
                'part' => 'snippet',
                'id'   => $video_id,
                'key'  => $api_key,
            ],
            'https://www.googleapis.com/youtube/v3/videos'
        );

        $response = wp_remote_get( $url, [ 'timeout' => 20 ] );
        if ( is_wp_error( $response ) ) {
            return [
                'title' => 'Video YouTube ' . $video_id,
            ];
        }

        $code = wp_remote_retrieve_response_code( $response );
        if ( $code < 200 || $code >= 300 ) {
            return [
                'title' => 'Video YouTube ' . $video_id,
            ];
        }

        $data = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( empty( $data['items'][0]['snippet']['title'] ) ) {
            return [
                'title' => 'Video YouTube ' . $video_id,
            ];
        }

        return [
            'title' => $data['items'][0]['snippet']['title'],
        ];
    }

    public static function render_admin_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        global $wpdb;
        $table = self::table_name();

        $jobs = $wpdb->get_results( "SELECT * FROM {$table} ORDER BY created_at DESC LIMIT 100" );
        $stats = self::get_stats();
        ?>
        <div class="wrap ipv-prod-wrap">
            <div class="ipv-prod-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h1 class="mb-1">
                            <i class="bi bi-list-task text-white me-2"></i>
                            Coda di Produzione
                        </h1>
                        <p class="text-muted mb-0">Gestisci e monitora i job in corso</p>
                    </div>
                    <div>
                        <button class="btn btn-light" id="ipv-manual-process">
                            <i class="bi bi-arrow-clockwise me-1"></i>
                            Processa Ora
                        </button>
                    </div>
                </div>
            </div>

            <ul class="nav nav-tabs mb-4" role="tablist">
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo esc_url( admin_url( 'admin.php?page=ipv-production-dashboard' ) ); ?>">
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
                    <a class="nav-link active" href="<?php echo esc_url( admin_url( 'admin.php?page=ipv-production-queue' ) ); ?>">
                        <i class="bi bi-list-task me-1"></i>Coda
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo esc_url( admin_url( 'admin.php?page=ipv-production-settings' ) ); ?>">
                        <i class="bi bi-gear me-1"></i>Impostazioni
                    </a>
                </li>
            </ul>

            <!-- Info Box -->
            <div class="alert alert-info mb-4">
                <div class="d-flex align-items-center">
                    <i class="bi bi-info-circle-fill fs-4 me-3"></i>
                    <div class="flex-grow-1">
                        <p class="mb-0">
                            <strong>Cron automatico:</strong> La coda viene processata automaticamente ogni minuto dal sistema WordPress Cron.
                            Puoi anche processarla manualmente usando il pulsante "Processa Ora".
                        </p>
                    </div>
                </div>
            </div>

            <!-- Stats Cards Mini -->
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="card shadow-sm text-center">
                        <div class="card-body">
                            <i class="bi bi-hourglass-split text-warning fs-2"></i>
                            <h3 class="mt-2 mb-0"><?php echo intval( $stats['pending'] ); ?></h3>
                            <small class="text-muted">In Attesa</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card shadow-sm text-center">
                        <div class="card-body">
                            <i class="bi bi-arrow-repeat text-info fs-2"></i>
                            <h3 class="mt-2 mb-0"><?php echo intval( $stats['processing'] ); ?></h3>
                            <small class="text-muted">In Lavorazione</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card shadow-sm text-center">
                        <div class="card-body">
                            <i class="bi bi-check-circle-fill text-success fs-2"></i>
                            <h3 class="mt-2 mb-0"><?php echo intval( $stats['done'] ); ?></h3>
                            <small class="text-muted">Completati</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card shadow-sm text-center">
                        <div class="card-body">
                            <i class="bi bi-exclamation-triangle-fill text-danger fs-2"></i>
                            <h3 class="mt-2 mb-0"><?php echo intval( $stats['error'] ); ?></h3>
                            <small class="text-muted">In Errore</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Jobs Table -->
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="bi bi-table text-primary me-2"></i>
                            Ultimi 100 Job
                        </h5>
                        <span class="badge bg-secondary"><?php echo count( $jobs ); ?> job</span>
                    </div>
                </div>
                <div class="card-body p-0">
                    <?php if ( empty( $jobs ) ) : ?>
                        <div class="ipv-empty-state">
                            <i class="bi bi-inbox"></i>
                            <h3>Nessun Job in Coda</h3>
                            <p>Inizia importando un video dalla pagina "Importa Video"</p>
                            <a href="<?php echo esc_url( admin_url( 'admin.php?page=ipv-production-import' ) ); ?>" class="btn btn-primary">
                                <i class="bi bi-plus-circle me-1"></i>
                                Importa Primo Video
                            </a>
                        </div>
                    <?php else : ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th style="width: 60px;">ID</th>
                                        <th>Video</th>
                                        <th style="width: 100px;">Fonte</th>
                                        <th style="width: 120px;">Status</th>
                                        <th style="width: 80px; text-align: center;">Tentativi</th>
                                        <th>Ultimo Errore</th>
                                        <th style="width: 150px;">Creato</th>
                                        <th style="width: 150px;">Aggiornato</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ( $jobs as $job ) : ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo intval( $job->id ); ?></strong>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <i class="bi bi-youtube text-danger me-2"></i>
                                                    <div>
                                                        <code class="small"><?php echo esc_html( $job->video_id ); ?></code>
                                                        <br>
                                                        <a href="<?php echo esc_url( $job->video_url ); ?>" 
                                                           target="_blank" 
                                                           class="small text-muted">
                                                            <i class="bi bi-box-arrow-up-right me-1"></i>
                                                            Apri su YouTube
                                                        </a>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge bg-secondary"><?php echo esc_html( $job->source ); ?></span>
                                            </td>
                                            <td>
                                                <?php
                                                $badge_class = 'secondary';
                                                $icon = 'circle';
                                                
                                                switch ( $job->status ) {
                                                    case 'pending':
                                                        $badge_class = 'warning text-dark';
                                                        $icon = 'hourglass-split';
                                                        break;
                                                    case 'processing':
                                                        $badge_class = 'info';
                                                        $icon = 'arrow-repeat';
                                                        break;
                                                    case 'done':
                                                        $badge_class = 'success';
                                                        $icon = 'check-circle-fill';
                                                        break;
                                                    case 'error':
                                                        $badge_class = 'danger';
                                                        $icon = 'exclamation-triangle-fill';
                                                        break;
                                                }
                                                ?>
                                                <span class="badge bg-<?php echo esc_attr( $badge_class ); ?>">
                                                    <i class="bi bi-<?php echo esc_attr( $icon ); ?> me-1"></i>
                                                    <?php echo esc_html( ucfirst( $job->status ) ); ?>
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <?php if ( $job->attempts > 0 ) : ?>
                                                    <span class="badge bg-warning text-dark">
                                                        <?php echo intval( $job->attempts ); ?>x
                                                    </span>
                                                <?php else : ?>
                                                    <span class="text-muted">—</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ( ! empty( $job->last_error ) ) : ?>
                                                    <div class="text-danger small" style="max-width: 300px;">
                                                        <i class="bi bi-exclamation-circle me-1"></i>
                                                        <?php echo esc_html( mb_substr( $job->last_error, 0, 100 ) ); ?>
                                                        <?php if ( strlen( $job->last_error ) > 100 ) : ?>
                                                            <span class="text-muted">...</span>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php else : ?>
                                                    <span class="text-muted">—</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <small class="text-muted">
                                                    <?php echo esc_html( $job->created_at ); ?>
                                                </small>
                                            </td>
                                            <td>
                                                <small class="text-muted">
                                                    <?php echo esc_html( $job->updated_at ); ?>
                                                </small>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
    }
}

add_action( 'admin_post_ipv_prod_manual_process_queue', function () {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( 'Non autorizzato.' );
    }
    if ( ! isset( $_POST['ipv_prod_manual_process_nonce'] ) || ! wp_verify_nonce( $_POST['ipv_prod_manual_process_nonce'], 'ipv_prod_manual_process' ) ) {
        wp_die( 'Nonce non valido.' );
    }

    IPV_Prod_Queue::process_queue();

    wp_safe_redirect( wp_get_referer() ? wp_get_referer() : admin_url( 'admin.php?page=ipv-production-dashboard' ) );
    exit;
} );
