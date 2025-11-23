<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class IPV_Prod_YouTube_Importer {

    public static function render_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $notice = '';
        if ( isset( $_GET['ipv_import_success'] ) && '1' === $_GET['ipv_import_success'] ) {
            $notice = '<div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i>
                <strong>Video aggiunto in coda con successo!</strong> Verrà processato automaticamente dal sistema.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>';
        } elseif ( isset( $_GET['ipv_import_error'] ) ) {
            $msg    = sanitize_text_field( wp_unslash( $_GET['ipv_import_error'] ) );
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
                            <i class="bi bi-upload text-white me-2"></i>
                            Importa Video YouTube
                        </h1>
                        <p class="text-muted mb-0">Aggiungi video alla coda di produzione</p>
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
                    <a class="nav-link active" href="<?php echo esc_url( admin_url( 'admin.php?page=ipv-production-import' ) ); ?>">
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

            <?php echo $notice; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

            <div class="row g-4">
                <!-- Form Importazione -->
                <div class="col-lg-7">
                    <div class="card shadow-sm">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">
                                <i class="bi bi-plus-circle-fill text-primary me-2"></i>
                                Aggiungi Nuovo Video
                            </h5>
                        </div>
                        <div class="card-body">
                            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="ipv-form">
                                <?php wp_nonce_field( 'ipv_prod_import_video', 'ipv_prod_import_video_nonce' ); ?>
                                <input type="hidden" name="action" value="ipv_prod_import_video" />

                                <div class="mb-4">
                                    <label for="ipv_youtube_url" class="form-label">
                                        <i class="bi bi-youtube text-danger me-1"></i>
                                        URL YouTube
                                        <span class="text-danger">*</span>
                                    </label>
                                    <input type="url" 
                                           id="ipv_youtube_url" 
                                           name="ipv_youtube_url" 
                                           class="form-control form-control-lg ipv-validate" 
                                           placeholder="https://www.youtube.com/watch?v=..." 
                                           data-validate-type="required"
                                           required />
                                    <div class="form-text">
                                        <i class="bi bi-info-circle me-1"></i>
                                        Incolla l'URL completo del video YouTube da processare
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <label for="ipv_source" class="form-label">
                                        <i class="bi bi-tag me-1"></i>
                                        Fonte
                                    </label>
                                    <select name="ipv_source" id="ipv_source" class="form-select">
                                        <option value="manual">Manuale</option>
                                        <option value="rss">RSS Feed</option>
                                        <option value="playlist">Playlist</option>
                                    </select>
                                    <div class="form-text">
                                        <i class="bi bi-info-circle me-1"></i>
                                        Indica da dove proviene il video (per statistiche)
                                    </div>
                                </div>

                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        <i class="bi bi-plus-circle me-1"></i>
                                        Aggiungi alla Coda
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Info Box -->
                    <div class="card shadow-sm mt-4">
                        <div class="card-body">
                            <h6 class="mb-3">
                                <i class="bi bi-lightbulb-fill text-warning me-2"></i>
                                Formati URL supportati
                            </h6>
                            <ul class="list-unstyled mb-0">
                                <li class="mb-2">
                                    <i class="bi bi-check-circle-fill text-success me-2"></i>
                                    <code>https://www.youtube.com/watch?v=VIDEO_ID</code>
                                </li>
                                <li class="mb-2">
                                    <i class="bi bi-check-circle-fill text-success me-2"></i>
                                    <code>https://youtu.be/VIDEO_ID</code>
                                </li>
                                <li>
                                    <i class="bi bi-check-circle-fill text-success me-2"></i>
                                    <code>https://www.youtube.com/watch?v=VIDEO_ID&t=123s</code>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Sidebar Info -->
                <div class="col-lg-5">
                    <div class="card shadow-sm">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">
                                <i class="bi bi-info-circle-fill text-info me-2"></i>
                                Come Funziona
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="ipv-process-step mb-4">
                                <div class="d-flex">
                                    <div class="ipv-step-number me-3">
                                        <div class="badge bg-primary rounded-circle p-2" style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                                            1
                                        </div>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1">Importazione Video</h6>
                                        <p class="text-muted mb-0 small">
                                            Il video viene aggiunto alla coda e il CPT viene creato
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="ipv-process-step mb-4">
                                <div class="d-flex">
                                    <div class="ipv-step-number me-3">
                                        <div class="badge bg-info rounded-circle p-2" style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                                            2
                                        </div>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1">Generazione Trascrizione</h6>
                                        <p class="text-muted mb-0 small">
                                            SupaData API estrae la trascrizione del video
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="ipv-process-step mb-4">
                                <div class="d-flex">
                                    <div class="ipv-step-number me-3">
                                        <div class="badge bg-warning rounded-circle p-2" style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                                            3
                                        </div>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1">AI Generation</h6>
                                        <p class="text-muted mb-0 small">
                                            OpenAI genera la descrizione usando il Golden Prompt
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="ipv-process-step">
                                <div class="d-flex">
                                    <div class="ipv-step-number me-3">
                                        <div class="badge bg-success rounded-circle p-2" style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                                            4
                                        </div>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1">Pubblicazione</h6>
                                        <p class="text-muted mb-0 small">
                                            La descrizione viene salvata nel post pronta all'uso
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Statistics -->
                    <div class="card shadow-sm mt-4">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">
                                <i class="bi bi-graph-up text-success me-2"></i>
                                Statistiche Veloci
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php
                            $stats = IPV_Prod_Queue::get_stats();
                            $total = array_sum( $stats );
                            ?>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="text-muted">Video Totali</span>
                                <span class="badge bg-primary fs-6"><?php echo intval( $total ); ?></span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="text-muted">In Coda</span>
                                <span class="badge bg-warning text-dark fs-6"><?php echo intval( $stats['pending'] ); ?></span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-muted">Completati</span>
                                <span class="badge bg-success fs-6"><?php echo intval( $stats['done'] ); ?></span>
                            </div>
                        </div>
                        <div class="card-footer bg-light text-center">
                            <a href="<?php echo esc_url( admin_url( 'admin.php?page=ipv-production-queue' ) ); ?>" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-eye me-1"></i>
                                Vedi Coda Completa
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    public static function handle_form_submit() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Non autorizzato.' );
        }

        if ( ! isset( $_POST['ipv_prod_import_video_nonce'] ) || ! wp_verify_nonce( $_POST['ipv_prod_import_video_nonce'], 'ipv_prod_import_video' ) ) {
            wp_die( 'Nonce non valido.' );
        }

        $url    = isset( $_POST['ipv_youtube_url'] ) ? esc_url_raw( wp_unslash( $_POST['ipv_youtube_url'] ) ) : '';
        $source = isset( $_POST['ipv_source'] ) ? sanitize_text_field( wp_unslash( $_POST['ipv_source'] ) ) : 'manual';

        if ( empty( $url ) ) {
            self::redirect_with_error( 'URL mancante.' );
        }

        $video_id = self::extract_video_id( $url );
        if ( ! $video_id ) {
            self::redirect_with_error( 'Impossibile estrarre il Video ID dall\'URL fornito.' );
        }

        IPV_Prod_Queue::enqueue( $video_id, $url, $source );

        wp_safe_redirect(
            add_query_arg(
                [
                    'page'               => 'ipv-production-import',
                    'ipv_import_success' => '1',
                ],
                admin_url( 'admin.php' )
            )
        );
        exit;
    }

    protected static function redirect_with_error( $msg ) {
        wp_safe_redirect(
            add_query_arg(
                [
                    'page'              => 'ipv-production-import',
                    'ipv_import_error'  => rawurlencode( $msg ),
                ],
                admin_url( 'admin.php' )
            )
        );
        exit;
    }

    protected static function extract_video_id( $url ) {
        // Standard YouTube URL
        if ( preg_match( '/[?&]v=([^&]+)/', $url, $matches ) ) {
            return $matches[1];
        }

        // Short YouTube URL
        if ( preg_match( '/youtu\.be\/([^?&]+)/', $url, $matches ) ) {
            return $matches[1];
        }

        return false;
    }
}

add_action( 'admin_post_ipv_prod_import_video', [ 'IPV_Prod_YouTube_Importer', 'handle_form_submit' ] );
