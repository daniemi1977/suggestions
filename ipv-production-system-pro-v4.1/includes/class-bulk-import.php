<?php
/**
 * IPV Production System Pro - Bulk Import
 *
 * Importazione massiva di video precedenti dal canale YouTube.
 * Funzionalità:
 * - Lista ultimi N video dal canale
 * - Selezione multipla con checkbox
 * - Controllo duplicati
 * - Preview dati prima dell'importazione
 * - Importazione batch in coda
 *
 * @package IPV_Production_System_Pro
 * @version 4.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class IPV_Prod_Bulk_Import {

    /**
     * Numero massimo di video da caricare per pagina
     */
    const VIDEOS_PER_PAGE = 25;

    /**
     * Inizializza la classe
     */
    public static function init() {
        add_action( 'admin_post_ipv_prod_bulk_import', [ __CLASS__, 'handle_bulk_import' ] );
        add_action( 'wp_ajax_ipv_prod_load_channel_videos', [ __CLASS__, 'ajax_load_channel_videos' ] );
        add_action( 'wp_ajax_ipv_prod_check_duplicates', [ __CLASS__, 'ajax_check_duplicates' ] );
    }

    /**
     * Renderizza la pagina Bulk Import
     */
    public static function render_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $channel_id = get_option( 'ipv_youtube_channel_id', '' );
        $youtube_key = get_option( 'ipv_youtube_api_key', '' );

        $notice = '';
        if ( isset( $_GET['ipv_bulk_success'] ) ) {
            $count = intval( $_GET['ipv_bulk_success'] );
            $notice = '<div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i>
                <strong>' . $count . ' video aggiunti alla coda!</strong> Verranno processati automaticamente.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>';
        } elseif ( isset( $_GET['ipv_bulk_error'] ) ) {
            $msg = sanitize_text_field( wp_unslash( $_GET['ipv_bulk_error'] ) );
            $notice = '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <strong>Errore:</strong> ' . esc_html( $msg ) . '
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>';
        }
        ?>
        <div class="wrap ipv-prod-wrap">
            <div class="ipv-prod-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h1 class="mb-1">
                            <i class="bi bi-collection-play text-white me-2"></i>
                            Bulk Import Video
                        </h1>
                        <p class="text-muted mb-0">Importa video precedenti dal canale YouTube</p>
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
                    <a class="nav-link active" href="<?php echo esc_url( admin_url( 'admin.php?page=ipv-production-bulk-import' ) ); ?>">
                        <i class="bi bi-collection-play me-1"></i>Bulk Import
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

            <?php echo $notice; ?>

            <?php if ( empty( $youtube_key ) ) : ?>
                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <strong>YouTube API Key non configurata.</strong>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=ipv-production-settings' ) ); ?>">Vai alle Impostazioni</a> per configurarla.
                </div>
            <?php else : ?>

            <div class="row g-4">
                <!-- Configurazione Canale -->
                <div class="col-lg-4">
                    <div class="card shadow-sm">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">
                                <i class="bi bi-youtube text-danger me-2"></i>
                                Configurazione Canale
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-4">
                                <label class="form-label">
                                    <i class="bi bi-hash me-1"></i>
                                    Channel ID
                                </label>
                                <div class="input-group">
                                    <input type="text"
                                           id="ipv_channel_id"
                                           class="form-control"
                                           value="<?php echo esc_attr( $channel_id ); ?>"
                                           placeholder="UC..." />
                                    <button class="btn btn-outline-secondary" type="button" id="ipv-save-channel">
                                        <i class="bi bi-check"></i>
                                    </button>
                                </div>
                                <div class="form-text">
                                    <i class="bi bi-info-circle me-1"></i>
                                    Trova il Channel ID dal tuo canale YouTube
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label">
                                    <i class="bi bi-link-45deg me-1"></i>
                                    Oppure URL Canale
                                </label>
                                <div class="input-group">
                                    <input type="url"
                                           id="ipv_channel_url"
                                           class="form-control"
                                           placeholder="https://youtube.com/@..." />
                                    <button class="btn btn-outline-primary" type="button" id="ipv-resolve-url">
                                        <i class="bi bi-search"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label">
                                    <i class="bi bi-sliders me-1"></i>
                                    Video da Caricare
                                </label>
                                <select id="ipv_videos_count" class="form-select">
                                    <option value="10">Ultimi 10</option>
                                    <option value="25" selected>Ultimi 25</option>
                                    <option value="50">Ultimi 50</option>
                                    <option value="100">Ultimi 100</option>
                                </select>
                            </div>

                            <div class="d-grid">
                                <button class="btn btn-primary" id="ipv-load-videos">
                                    <i class="bi bi-download me-1"></i>
                                    Carica Video dal Canale
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Info Box -->
                    <div class="card shadow-sm mt-4">
                        <div class="card-body">
                            <h6 class="mb-3">
                                <i class="bi bi-lightbulb-fill text-warning me-2"></i>
                                Come Trovare il Channel ID
                            </h6>
                            <ol class="small mb-0">
                                <li class="mb-2">Vai sul tuo canale YouTube</li>
                                <li class="mb-2">Clicca su "Personalizza canale"</li>
                                <li class="mb-2">Nella URL troverai: <code>/channel/UC...</code></li>
                                <li>Copia la parte che inizia con <strong>UC</strong></li>
                            </ol>
                            <hr>
                            <p class="small text-muted mb-0">
                                <i class="bi bi-info-circle me-1"></i>
                                Oppure usa il campo URL e il sistema risolverà automaticamente il Channel ID.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Lista Video -->
                <div class="col-lg-8">
                    <div class="card shadow-sm">
                        <div class="card-header bg-white">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">
                                    <i class="bi bi-collection text-primary me-2"></i>
                                    Video del Canale
                                </h5>
                                <div id="ipv-video-actions" style="display: none;">
                                    <span class="badge bg-primary me-2" id="ipv-selected-count">0 selezionati</span>
                                    <button class="btn btn-sm btn-outline-secondary me-2" id="ipv-select-all">
                                        Seleziona Tutti
                                    </button>
                                    <button class="btn btn-sm btn-outline-secondary me-2" id="ipv-deselect-all">
                                        Deseleziona
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div id="ipv-videos-container">
                                <div class="ipv-empty-state">
                                    <i class="bi bi-collection-play"></i>
                                    <h3>Nessun Video Caricato</h3>
                                    <p>Configura il Channel ID e clicca "Carica Video dal Canale"</p>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer bg-light" id="ipv-import-footer" style="display: none;">
                            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" id="ipv-bulk-import-form">
                                <?php wp_nonce_field( 'ipv_prod_bulk_import', 'ipv_prod_bulk_import_nonce' ); ?>
                                <input type="hidden" name="action" value="ipv_prod_bulk_import" />
                                <input type="hidden" name="video_ids" id="ipv_selected_video_ids" value="" />

                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="ipv_skip_existing" name="skip_existing" value="1" checked>
                                        <label class="form-check-label" for="ipv_skip_existing">
                                            Salta video già importati
                                        </label>
                                    </div>
                                    <button type="submit" class="btn btn-success btn-lg" id="ipv-start-import">
                                        <i class="bi bi-cloud-download me-1"></i>
                                        Importa Video Selezionati
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Loading More -->
                    <div id="ipv-load-more-container" class="text-center mt-4" style="display: none;">
                        <button class="btn btn-outline-primary" id="ipv-load-more">
                            <i class="bi bi-arrow-down-circle me-1"></i>
                            Carica Altri Video
                        </button>
                    </div>
                </div>
            </div>

            <?php endif; ?>
        </div>

        <style>
            .ipv-video-item {
                display: flex;
                align-items: center;
                padding: 15px;
                border-bottom: 1px solid #e9ecef;
                transition: background-color 0.2s;
            }
            .ipv-video-item:hover {
                background-color: #f8f9fa;
            }
            .ipv-video-item.selected {
                background-color: #e7f3ff;
            }
            .ipv-video-item.already-imported {
                opacity: 0.6;
                background-color: #f0f0f0;
            }
            .ipv-video-thumb {
                width: 120px;
                height: 68px;
                object-fit: cover;
                border-radius: 4px;
                margin-right: 15px;
            }
            .ipv-video-info {
                flex: 1;
                min-width: 0;
            }
            .ipv-video-title {
                font-weight: 600;
                margin-bottom: 4px;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }
            .ipv-video-meta {
                font-size: 12px;
                color: #6c757d;
            }
            .ipv-video-meta span {
                margin-right: 12px;
            }
            .ipv-video-checkbox {
                width: 20px;
                height: 20px;
                margin-right: 15px;
            }
            .ipv-video-status {
                margin-left: 15px;
            }
            #ipv-videos-container {
                max-height: 600px;
                overflow-y: auto;
            }
        </style>

        <script>
        jQuery(document).ready(function($) {
            let selectedVideos = new Set();
            let allVideos = [];
            let nextPageToken = '';

            // Save Channel ID
            $('#ipv-save-channel').on('click', function() {
                const channelId = $('#ipv_channel_id').val().trim();
                if (!channelId) return;

                $.post(ajaxurl, {
                    action: 'ipv_prod_save_channel_id',
                    channel_id: channelId,
                    nonce: ipvProdAjax.nonce
                }, function(response) {
                    if (response.success) {
                        showToast('Channel ID salvato!', 'success');
                    }
                });
            });

            // Resolve Channel URL
            $('#ipv-resolve-url').on('click', function() {
                const url = $('#ipv_channel_url').val().trim();
                if (!url) return;

                $(this).html('<span class="spinner-border spinner-border-sm"></span>');

                $.post(ajaxurl, {
                    action: 'ipv_prod_resolve_channel_url',
                    url: url,
                    nonce: ipvProdAjax.nonce
                }, function(response) {
                    $('#ipv-resolve-url').html('<i class="bi bi-search"></i>');
                    if (response.success) {
                        $('#ipv_channel_id').val(response.data.channel_id);
                        showToast('Channel ID trovato: ' + response.data.channel_id, 'success');
                    } else {
                        showToast(response.data.message, 'danger');
                    }
                });
            });

            // Load Videos
            $('#ipv-load-videos').on('click', function() {
                const channelId = $('#ipv_channel_id').val().trim();
                const count = $('#ipv_videos_count').val();

                if (!channelId) {
                    showToast('Inserisci un Channel ID valido', 'warning');
                    return;
                }

                loadVideos(channelId, count, '');
            });

            function loadVideos(channelId, count, pageToken) {
                $('#ipv-load-videos').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Caricamento...');

                $.post(ajaxurl, {
                    action: 'ipv_prod_load_channel_videos',
                    channel_id: channelId,
                    count: count,
                    page_token: pageToken,
                    nonce: ipvProdAjax.nonce
                }, function(response) {
                    $('#ipv-load-videos').prop('disabled', false).html('<i class="bi bi-download me-1"></i> Carica Video dal Canale');

                    if (response.success) {
                        if (pageToken === '') {
                            allVideos = response.data.videos;
                            renderVideos(response.data.videos, false);
                        } else {
                            allVideos = allVideos.concat(response.data.videos);
                            renderVideos(response.data.videos, true);
                        }

                        nextPageToken = response.data.next_page_token;

                        if (nextPageToken) {
                            $('#ipv-load-more-container').show();
                        } else {
                            $('#ipv-load-more-container').hide();
                        }

                        $('#ipv-video-actions').show();
                        $('#ipv-import-footer').show();

                        checkDuplicates();
                    } else {
                        showToast(response.data.message, 'danger');
                    }
                });
            }

            // Load More
            $('#ipv-load-more').on('click', function() {
                const channelId = $('#ipv_channel_id').val().trim();
                const count = $('#ipv_videos_count').val();
                loadVideos(channelId, count, nextPageToken);
            });

            function renderVideos(videos, append) {
                const container = $('#ipv-videos-container');

                if (!append) {
                    container.empty();
                }

                videos.forEach(function(video) {
                    const duration = video.duration_formatted || '0:00';
                    const views = formatNumber(video.view_count || 0);
                    const date = formatDate(video.published_at);

                    const html = `
                        <div class="ipv-video-item" data-video-id="${video.video_id}">
                            <input type="checkbox" class="ipv-video-checkbox form-check-input" value="${video.video_id}">
                            <img src="${video.thumbnail_url}" alt="" class="ipv-video-thumb">
                            <div class="ipv-video-info">
                                <div class="ipv-video-title">${escapeHtml(video.title)}</div>
                                <div class="ipv-video-meta">
                                    <span><i class="bi bi-clock me-1"></i>${duration}</span>
                                    <span><i class="bi bi-eye me-1"></i>${views} views</span>
                                    <span><i class="bi bi-calendar me-1"></i>${date}</span>
                                </div>
                            </div>
                            <div class="ipv-video-status"></div>
                        </div>
                    `;

                    container.append(html);
                });
            }

            function checkDuplicates() {
                const videoIds = allVideos.map(v => v.video_id);

                $.post(ajaxurl, {
                    action: 'ipv_prod_check_duplicates',
                    video_ids: videoIds,
                    nonce: ipvProdAjax.nonce
                }, function(response) {
                    if (response.success) {
                        response.data.duplicates.forEach(function(videoId) {
                            const item = $(`.ipv-video-item[data-video-id="${videoId}"]`);
                            item.addClass('already-imported');
                            item.find('.ipv-video-status').html('<span class="badge bg-secondary">Già importato</span>');
                            item.find('.ipv-video-checkbox').prop('disabled', true);
                        });
                    }
                });
            }

            // Checkbox handling
            $(document).on('change', '.ipv-video-checkbox', function() {
                const videoId = $(this).val();
                const item = $(this).closest('.ipv-video-item');

                if ($(this).is(':checked')) {
                    selectedVideos.add(videoId);
                    item.addClass('selected');
                } else {
                    selectedVideos.delete(videoId);
                    item.removeClass('selected');
                }

                updateSelectedCount();
            });

            // Select All
            $('#ipv-select-all').on('click', function() {
                $('.ipv-video-checkbox:not(:disabled)').each(function() {
                    $(this).prop('checked', true).trigger('change');
                });
            });

            // Deselect All
            $('#ipv-deselect-all').on('click', function() {
                $('.ipv-video-checkbox').each(function() {
                    $(this).prop('checked', false).trigger('change');
                });
            });

            function updateSelectedCount() {
                $('#ipv-selected-count').text(selectedVideos.size + ' selezionati');
                $('#ipv_selected_video_ids').val(Array.from(selectedVideos).join(','));
            }

            // Form submit validation
            $('#ipv-bulk-import-form').on('submit', function(e) {
                if (selectedVideos.size === 0) {
                    e.preventDefault();
                    showToast('Seleziona almeno un video da importare', 'warning');
                    return false;
                }
            });

            // Helpers
            function formatNumber(num) {
                if (num >= 1000000) return (num / 1000000).toFixed(1) + 'M';
                if (num >= 1000) return (num / 1000).toFixed(1) + 'K';
                return num.toString();
            }

            function formatDate(dateStr) {
                const date = new Date(dateStr);
                return date.toLocaleDateString('it-IT', { day: '2-digit', month: 'short', year: 'numeric' });
            }

            function escapeHtml(text) {
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }

            function showToast(message, type) {
                const toast = $('#ipv-toast');
                toast.find('.toast-body').text(message);
                toast.removeClass('bg-success bg-danger bg-warning').addClass('bg-' + type);
                new bootstrap.Toast(toast[0]).show();
            }
        });
        </script>
        <?php
    }

    /**
     * Gestisce l'importazione bulk
     */
    public static function handle_bulk_import() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Non autorizzato.' );
        }

        if ( ! isset( $_POST['ipv_prod_bulk_import_nonce'] ) ||
             ! wp_verify_nonce( $_POST['ipv_prod_bulk_import_nonce'], 'ipv_prod_bulk_import' ) ) {
            wp_die( 'Nonce non valido.' );
        }

        $video_ids_str = isset( $_POST['video_ids'] ) ? sanitize_text_field( wp_unslash( $_POST['video_ids'] ) ) : '';
        $skip_existing = isset( $_POST['skip_existing'] ) && $_POST['skip_existing'] === '1';

        if ( empty( $video_ids_str ) ) {
            wp_safe_redirect(
                add_query_arg(
                    [
                        'page'           => 'ipv-production-bulk-import',
                        'ipv_bulk_error' => rawurlencode( 'Nessun video selezionato.' ),
                    ],
                    admin_url( 'admin.php' )
                )
            );
            exit;
        }

        $video_ids = array_filter( array_map( 'trim', explode( ',', $video_ids_str ) ) );
        $imported = 0;

        foreach ( $video_ids as $video_id ) {
            // Controllo duplicati
            if ( $skip_existing && self::video_already_imported( $video_id ) ) {
                continue;
            }

            // Aggiungi alla coda
            $video_url = 'https://www.youtube.com/watch?v=' . $video_id;
            IPV_Prod_Queue::enqueue( $video_id, $video_url, 'bulk' );
            $imported++;
        }

        wp_safe_redirect(
            add_query_arg(
                [
                    'page'             => 'ipv-production-bulk-import',
                    'ipv_bulk_success' => $imported,
                ],
                admin_url( 'admin.php' )
            )
        );
        exit;
    }

    /**
     * AJAX: Carica video dal canale
     */
    public static function ajax_load_channel_videos() {
        check_ajax_referer( 'ipv_prod_ajax', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => 'Non autorizzato.' ] );
        }

        $channel_id = isset( $_POST['channel_id'] ) ? sanitize_text_field( wp_unslash( $_POST['channel_id'] ) ) : '';
        $count = isset( $_POST['count'] ) ? intval( $_POST['count'] ) : 25;
        $page_token = isset( $_POST['page_token'] ) ? sanitize_text_field( wp_unslash( $_POST['page_token'] ) ) : '';

        if ( empty( $channel_id ) ) {
            wp_send_json_error( [ 'message' => 'Channel ID mancante.' ] );
        }

        // Salva il channel ID per uso futuro
        update_option( 'ipv_youtube_channel_id', $channel_id );

        $result = IPV_Prod_YouTube_API::get_channel_videos( $channel_id, $count, $page_token );

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( [ 'message' => $result->get_error_message() ] );
        }

        wp_send_json_success( $result );
    }

    /**
     * AJAX: Controlla duplicati
     */
    public static function ajax_check_duplicates() {
        check_ajax_referer( 'ipv_prod_ajax', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => 'Non autorizzato.' ] );
        }

        $video_ids = isset( $_POST['video_ids'] ) ? array_map( 'sanitize_text_field', $_POST['video_ids'] ) : [];

        $duplicates = [];
        foreach ( $video_ids as $video_id ) {
            if ( self::video_already_imported( $video_id ) ) {
                $duplicates[] = $video_id;
            }
        }

        wp_send_json_success( [ 'duplicates' => $duplicates ] );
    }

    /**
     * Verifica se un video è già stato importato
     *
     * @param string $video_id YouTube Video ID
     * @return bool True se già importato
     */
    public static function video_already_imported( $video_id ) {
        global $wpdb;

        // Check 1: Meta del post
        $exists = get_posts( [
            'post_type'   => 'video_ipv',
            'post_status' => 'any',
            'meta_key'    => '_ipv_video_id',
            'meta_value'  => $video_id,
            'fields'      => 'ids',
            'numberposts' => 1,
        ] );

        if ( ! empty( $exists ) ) {
            return true;
        }

        // Check 2: Coda (pending o processing)
        $table = IPV_Prod_Queue::table_name();
        $in_queue = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$table} WHERE video_id = %s AND status IN ('pending', 'processing')",
                $video_id
            )
        );

        return intval( $in_queue ) > 0;
    }
}

// Inizializza la classe
add_action( 'init', [ 'IPV_Prod_Bulk_Import', 'init' ] );

// AJAX per salvare channel ID
add_action( 'wp_ajax_ipv_prod_save_channel_id', function() {
    check_ajax_referer( 'ipv_prod_ajax', 'nonce' );

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( [ 'message' => 'Non autorizzato.' ] );
    }

    $channel_id = isset( $_POST['channel_id'] ) ? sanitize_text_field( wp_unslash( $_POST['channel_id'] ) ) : '';
    update_option( 'ipv_youtube_channel_id', $channel_id );

    wp_send_json_success();
} );

// AJAX per risolvere URL canale
add_action( 'wp_ajax_ipv_prod_resolve_channel_url', function() {
    check_ajax_referer( 'ipv_prod_ajax', 'nonce' );

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( [ 'message' => 'Non autorizzato.' ] );
    }

    $url = isset( $_POST['url'] ) ? esc_url_raw( wp_unslash( $_POST['url'] ) ) : '';

    if ( empty( $url ) ) {
        wp_send_json_error( [ 'message' => 'URL mancante.' ] );
    }

    $channel_id = IPV_Prod_YouTube_API::extract_channel_id( $url );

    if ( is_wp_error( $channel_id ) ) {
        wp_send_json_error( [ 'message' => $channel_id->get_error_message() ] );
    }

    update_option( 'ipv_youtube_channel_id', $channel_id );

    wp_send_json_success( [ 'channel_id' => $channel_id ] );
} );
