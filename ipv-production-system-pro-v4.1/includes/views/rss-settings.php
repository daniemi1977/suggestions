<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="wrap ipv-prod-wrap">
    <div class="ipv-prod-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="mb-1">
                    <i class="bi bi-rss-fill text-white me-2"></i>
                    Auto-Import RSS
                </h1>
                <p class="text-muted mb-0">Importa automaticamente nuovi video dal tuo canale YouTube</p>
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
            <a class="nav-link active" href="<?php echo esc_url( admin_url( 'admin.php?page=ipv-production-rss' ) ); ?>">
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

    <?php if ( isset( $test_result ) ) : ?>
        <div class="alert alert-<?php echo $test_result['success'] ? 'success' : 'danger'; ?> alert-dismissible fade show" role="alert">
            <i class="bi bi-<?php echo $test_result['success'] ? 'check-circle-fill' : 'exclamation-triangle-fill'; ?> me-2"></i>
            <strong>Test Feed:</strong> <?php echo esc_html( $test_result['message'] ); ?>
            <?php if ( $test_result['success'] ) : ?>
                <br><small>Trovati <?php echo intval( $test_result['video_count'] ); ?> video nel feed</small>
            <?php endif; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ( isset( $import_result ) ) : ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>
            <strong><?php echo esc_html( $import_result['message'] ); ?></strong>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- Configurazione RSS -->
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">
                        <i class="bi bi-gear-fill text-primary me-2"></i>
                        Configurazione Feed RSS
                    </h5>
                </div>
                <div class="card-body">
                    <form method="post" action="" class="ipv-form">
                        <?php wp_nonce_field( 'ipv_rss_settings_save' ); ?>
                        <input type="hidden" name="ipv_save_rss_settings" value="1" />

                        <!-- Enable RSS -->
                        <div class="mb-4">
                            <div class="form-check form-switch">
                                <input class="form-check-input" 
                                       type="checkbox" 
                                       name="ipv_rss_enabled" 
                                       id="ipv_rss_enabled" 
                                       value="1"
                                       <?php checked( $rss_enabled, true ); ?> />
                                <label class="form-check-label" for="ipv_rss_enabled">
                                    <strong>Abilita Auto-Import RSS</strong>
                                </label>
                            </div>
                            <div class="form-text">
                                <i class="bi bi-info-circle me-1"></i>
                                Quando attivo, il sistema controlla automaticamente il feed e importa nuovi video
                            </div>
                        </div>

                        <!-- Feed URL -->
                        <div class="mb-4">
                            <label class="form-label">
                                <i class="bi bi-rss me-1"></i>
                                URL Feed RSS YouTube
                                <span class="text-danger">*</span>
                            </label>
                            <input type="url" 
                                   class="form-control font-monospace" 
                                   name="ipv_rss_feed_url" 
                                   value="<?php echo esc_attr( $feed_url ); ?>" 
                                   placeholder="https://www.youtube.com/feeds/videos.xml?channel_id=..." 
                                   required />
                            <div class="form-text">
                                <i class="bi bi-lightbulb me-1"></i>
                                <strong>Il tuo feed:</strong> <code>https://www.youtube.com/feeds/videos.xml?channel_id=UCanPklit8pX7GuifWxHUiEw</code>
                            </div>
                        </div>

                        <!-- Schedule -->
                        <div class="mb-4">
                            <label class="form-label">
                                <i class="bi bi-clock me-1"></i>
                                Frequenza Controllo
                            </label>
                            <select name="ipv_rss_schedule" class="form-select">
                                <option value="every_30_minutes" <?php selected( $rss_schedule, 'every_30_minutes' ); ?>>
                                    Ogni 30 minuti (più frequente)
                                </option>
                                <option value="hourly" <?php selected( $rss_schedule, 'hourly' ); ?>>
                                    Ogni ora (consigliato)
                                </option>
                                <option value="every_6_hours" <?php selected( $rss_schedule, 'every_6_hours' ); ?>>
                                    Ogni 6 ore
                                </option>
                                <option value="twicedaily" <?php selected( $rss_schedule, 'twicedaily' ); ?>>
                                    Due volte al giorno
                                </option>
                                <option value="daily" <?php selected( $rss_schedule, 'daily' ); ?>>
                                    Una volta al giorno
                                </option>
                            </select>
                            <div class="form-text">
                                <i class="bi bi-info-circle me-1"></i>
                                Quanto spesso controllare il feed per nuovi video
                            </div>
                        </div>

                        <!-- Import Limit -->
                        <div class="mb-4">
                            <label class="form-label">
                                <i class="bi bi-hash me-1"></i>
                                Limite Video per Controllo
                            </label>
                            <input type="number" 
                                   class="form-control" 
                                   name="ipv_rss_import_limit" 
                                   value="<?php echo intval( $import_limit ); ?>" 
                                   min="1" 
                                   max="50" />
                            <div class="form-text">
                                <i class="bi bi-info-circle me-1"></i>
                                Numero massimo di video da importare ad ogni controllo (consigliato: 10)
                            </div>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle me-1"></i>
                                Salva Configurazione
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Test & Import Manuale -->
            <div class="card shadow-sm mt-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">
                        <i class="bi bi-tools text-warning me-2"></i>
                        Test & Importazione Manuale
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <form method="post" action="">
                                <?php wp_nonce_field( 'ipv_rss_test' ); ?>
                                <input type="hidden" name="ipv_test_rss_feed" value="1" />
                                <input type="hidden" name="ipv_rss_feed_url" value="<?php echo esc_attr( $feed_url ); ?>" />
                                <button type="submit" class="btn btn-outline-primary w-100" <?php disabled( empty( $feed_url ) ); ?>>
                                    <i class="bi bi-shield-check me-1"></i>
                                    Testa Feed
                                </button>
                                <small class="text-muted d-block mt-2">Verifica che il feed sia valido</small>
                            </form>
                        </div>
                        <div class="col-md-6">
                            <form method="post" action="">
                                <?php wp_nonce_field( 'ipv_rss_manual' ); ?>
                                <input type="hidden" name="ipv_manual_rss_import" value="1" />
                                <button type="submit" class="btn btn-outline-success w-100" <?php disabled( empty( $feed_url ) ); ?>>
                                    <i class="bi bi-download me-1"></i>
                                    Importa Ora
                                </button>
                                <small class="text-muted d-block mt-2">Forza l'importazione immediata</small>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar Stats & Info -->
        <div class="col-lg-4">
            <!-- Statistiche RSS -->
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">
                        <i class="bi bi-graph-up text-success me-2"></i>
                        Statistiche RSS
                    </h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted">Stato</span>
                            <span class="badge bg-<?php echo $rss_enabled ? 'success' : 'secondary'; ?>">
                                <?php echo $rss_enabled ? '✓ Attivo' : '○ Disattivo'; ?>
                            </span>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted">Ultimo Controllo</span>
                            <span class="fw-medium">
                                <?php 
                                if ( $stats['last_check'] === 'Mai' ) {
                                    echo 'Mai';
                                } else {
                                    echo esc_html( mysql2date( 'd/m/Y H:i', $stats['last_check'] ) );
                                }
                                ?>
                            </span>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted">Video Importati</span>
                            <span class="badge bg-primary fs-6">
                                <?php echo intval( $stats['total_imported'] ); ?>
                            </span>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted">Video Saltati</span>
                            <span class="badge bg-warning text-dark fs-6">
                                <?php echo intval( $stats['total_skipped'] ); ?>
                            </span>
                        </div>
                    </div>

                    <?php if ( $next_run ) : ?>
                        <div class="mb-0">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-muted">Prossimo Controllo</span>
                                <span class="fw-medium">
                                    <?php echo esc_html( human_time_diff( $next_run ) ); ?>
                                </span>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Come Funziona -->
            <div class="card shadow-sm mt-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">
                        <i class="bi bi-question-circle text-info me-2"></i>
                        Come Funziona
                    </h5>
                </div>
                <div class="card-body">
                    <div class="ipv-process-step mb-3">
                        <div class="d-flex">
                            <div class="me-3">
                                <div class="badge bg-primary rounded-circle" style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;">
                                    1
                                </div>
                            </div>
                            <div>
                                <h6 class="mb-1">Monitoraggio Automatico</h6>
                                <p class="text-muted mb-0 small">
                                    Il sistema controlla il feed RSS alla frequenza impostata
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="ipv-process-step mb-3">
                        <div class="d-flex">
                            <div class="me-3">
                                <div class="badge bg-info rounded-circle" style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;">
                                    2
                                </div>
                            </div>
                            <div>
                                <h6 class="mb-1">Rilevamento Nuovi Video</h6>
                                <p class="text-muted mb-0 small">
                                    Identifica automaticamente i video non ancora importati
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="ipv-process-step mb-0">
                        <div class="d-flex">
                            <div class="me-3">
                                <div class="badge bg-success rounded-circle" style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;">
                                    3
                                </div>
                            </div>
                            <div>
                                <h6 class="mb-1">Importazione Automatica</h6>
                                <p class="text-muted mb-0 small">
                                    Aggiunge i nuovi video alla coda per il processamento
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Info Feed -->
            <div class="card shadow-sm mt-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">
                        <i class="bi bi-info-circle text-primary me-2"></i>
                        Info Feed
                    </h5>
                </div>
                <div class="card-body">
                    <h6>Formato Feed YouTube</h6>
                    <p class="small text-muted mb-3">
                        Il feed deve essere nel formato:
                    </p>
                    <code class="d-block small mb-3" style="word-break: break-all;">
                        https://www.youtube.com/feeds/videos.xml?channel_id=CHANNEL_ID
                    </code>
                    
                    <h6>Il Tuo Channel ID</h6>
                    <code class="d-block small">
                        UCanPklit8pX7GuifWxHUiEw
                    </code>
                </div>
            </div>
        </div>
    </div>
</div>
