<?php
/**
 * Dashboard
 *
 * Main dashboard with import form and statistics
 */

if (!defined('ABSPATH')) {
    exit;
}

class IPV_Dashboard {

    /**
     * Queue manager instance
     */
    private $queue_manager;

    /**
     * Constructor
     */
    public function __construct() {
        $this->queue_manager = new IPV_Queue_Manager();
    }

    /**
     * Render dashboard page
     */
    public function render_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        $stats = $this->queue_manager->get_stats();
        $recent_items = $this->queue_manager->get_queue_items(null, 10, 0);

        ?>
        <div class="wrap ipv-pro-dashboard">
            <h1>🎬 IPV Production System Pro</h1>
            <p class="ipv-tagline">Sistema completo per importazione automatica video YouTube con trascrizione AI e generazione contenuti</p>

            <!-- Quick Stats -->
            <div class="ipv-dashboard-grid">
                <div class="ipv-card">
                    <div class="ipv-card-icon">📊</div>
                    <div class="ipv-card-content">
                        <h3>Statistiche Coda</h3>
                        <div class="ipv-stats-grid">
                            <div class="ipv-stat-item">
                                <span class="ipv-stat-number"><?php echo esc_html($stats['total']); ?></span>
                                <span class="ipv-stat-label">Totale Video</span>
                            </div>
                            <div class="ipv-stat-item">
                                <span class="ipv-stat-number ipv-text-pending"><?php echo esc_html($stats['pending']); ?></span>
                                <span class="ipv-stat-label">In Coda</span>
                            </div>
                            <div class="ipv-stat-item">
                                <span class="ipv-stat-number ipv-text-processing"><?php echo esc_html($stats['processing']); ?></span>
                                <span class="ipv-stat-label">In Elaborazione</span>
                            </div>
                            <div class="ipv-stat-item">
                                <span class="ipv-stat-number ipv-text-completed"><?php echo esc_html($stats['completed']); ?></span>
                                <span class="ipv-stat-label">Completati</span>
                            </div>
                            <div class="ipv-stat-item">
                                <span class="ipv-stat-number ipv-text-failed"><?php echo esc_html($stats['failed']); ?></span>
                                <span class="ipv-stat-label">Errori</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="ipv-card">
                    <div class="ipv-card-icon">⚙️</div>
                    <div class="ipv-card-content">
                        <h3>Configurazione Sistema</h3>
                        <?php echo $this->render_system_status(); ?>
                    </div>
                </div>
            </div>

            <!-- Import Forms -->
            <div class="ipv-import-section">
                <div class="ipv-card">
                    <h2>📥 Importa Video</h2>

                    <!-- Single Import -->
                    <div class="ipv-import-form">
                        <h3>Importa Singolo Video</h3>
                        <div class="ipv-form-group">
                            <input type="text"
                                   id="ipv-single-url"
                                   class="ipv-input-url"
                                   placeholder="https://www.youtube.com/watch?v=..."
                                   autocomplete="off">
                            <button type="button" id="ipv-btn-import-single" class="button button-primary button-large">
                                ➕ Aggiungi alla Coda
                            </button>
                        </div>
                        <div id="ipv-single-result" class="ipv-result-message"></div>
                    </div>

                    <!-- Bulk Import -->
                    <div class="ipv-import-form ipv-bulk-import">
                        <h3>Importa Multipli Video (Bulk)</h3>
                        <p class="description">Inserisci un URL per riga. Massimo 50 video per volta.</p>
                        <textarea id="ipv-bulk-urls"
                                  class="ipv-textarea-bulk"
                                  rows="8"
                                  placeholder="https://www.youtube.com/watch?v=...&#10;https://www.youtube.com/watch?v=...&#10;https://www.youtube.com/watch?v=..."></textarea>
                        <button type="button" id="ipv-btn-import-bulk" class="button button-primary button-large">
                            📦 Importa Bulk
                        </button>
                        <div id="ipv-bulk-result" class="ipv-result-message"></div>
                        <div id="ipv-bulk-progress" class="ipv-progress-container" style="display:none;">
                            <div class="ipv-progress-bar">
                                <div class="ipv-progress-fill" style="width: 0%;"></div>
                            </div>
                            <div class="ipv-progress-text">Elaborazione: <span class="ipv-progress-current">0</span> / <span class="ipv-progress-total">0</span></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="ipv-recent-activity">
                <div class="ipv-card">
                    <h2>🕐 Attività Recente</h2>

                    <?php if (empty($recent_items)): ?>
                        <p class="ipv-empty-message">Nessuna attività recente. Importa il tuo primo video!</p>
                    <?php else: ?>
                        <table class="wp-list-table widefat striped ipv-recent-table">
                            <thead>
                                <tr>
                                    <th style="width: 50%;">Video</th>
                                    <th style="width: 20%;">Stato</th>
                                    <th style="width: 20%;">Step</th>
                                    <th style="width: 10%;">Data</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_items as $item): ?>
                                    <?php
                                    $post = get_post($item->post_id);
                                    $video_url = get_post_meta($item->post_id, '_ipv_video_url', true);
                                    ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo esc_html($post ? $post->post_title : 'Post #' . $item->post_id); ?></strong>
                                            <br>
                                            <small><a href="<?php echo esc_url($video_url); ?>" target="_blank"><?php echo esc_html($video_url); ?></a></small>
                                        </td>
                                        <td><?php echo $this->render_status_badge($item->status); ?></td>
                                        <td><span class="ipv-step-label"><?php echo esc_html($item->current_step); ?></span></td>
                                        <td><?php echo esc_html(human_time_diff(strtotime($item->updated_at), current_time('timestamp'))); ?> fa</td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>

                        <p class="ipv-view-all">
                            <a href="?page=ipv-video-manager" class="button">Vedi Tutti i Video →</a>
                        </p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Quick Links -->
            <div class="ipv-quick-links">
                <h3>🔗 Link Rapidi</h3>
                <div class="ipv-links-grid">
                    <a href="?page=ipv-video-manager" class="ipv-link-card">
                        <span class="ipv-link-icon">📹</span>
                        <span class="ipv-link-text">Video Manager</span>
                    </a>
                    <a href="?page=ipv-production-settings" class="ipv-link-card">
                        <span class="ipv-link-icon">⚙️</span>
                        <span class="ipv-link-text">Impostazioni</span>
                    </a>
                    <a href="edit.php?post_type=post" class="ipv-link-card">
                        <span class="ipv-link-icon">📝</span>
                        <span class="ipv-link-text">Tutti i Post</span>
                    </a>
                    <a href="https://ilpuntodivistachannel.com" target="_blank" class="ipv-link-card">
                        <span class="ipv-link-icon">🌐</span>
                        <span class="ipv-link-text">Il Punto di Vista</span>
                    </a>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render system status checklist
     */
    private function render_system_status() {
        $youtube_key = get_option('ipv_pro_youtube_api_key');
        $supadata_key = get_option('ipv_pro_supadata_api_key');
        $openai_key = get_option('ipv_pro_openai_api_key');

        $checks = [
            ['label' => 'YouTube API', 'status' => !empty($youtube_key)],
            ['label' => 'SupaData API', 'status' => !empty($supadata_key)],
            ['label' => 'OpenAI API', 'status' => !empty($openai_key)],
            ['label' => 'Cron Attivo', 'status' => wp_next_scheduled('ipv_pro_process_queue') !== false]
        ];

        $output = '<ul class="ipv-status-list">';

        foreach ($checks as $check) {
            $icon = $check['status'] ? '✅' : '❌';
            $class = $check['status'] ? 'ipv-status-ok' : 'ipv-status-error';

            $output .= sprintf(
                '<li class="%s"><span class="ipv-status-icon">%s</span> %s</li>',
                esc_attr($class),
                $icon,
                esc_html($check['label'])
            );
        }

        $output .= '</ul>';

        $all_ok = array_reduce($checks, function($carry, $check) {
            return $carry && $check['status'];
        }, true);

        if (!$all_ok) {
            $output .= '<p class="ipv-warning">⚠️ Configura le API nelle <a href="?page=ipv-production-settings">Impostazioni</a></p>';
        } else {
            $output .= '<p class="ipv-success">✅ Sistema pronto per l\'uso!</p>';
        }

        return $output;
    }

    /**
     * Render simple status badge
     */
    private function render_status_badge($status) {
        $badges = [
            'pending' => ['label' => '○ In Coda', 'class' => 'ipv-badge-pending'],
            'processing' => ['label' => '⏳ Elaborando', 'class' => 'ipv-badge-processing'],
            'completed' => ['label' => '✓ Completato', 'class' => 'ipv-badge-completed'],
            'failed' => ['label' => '✗ Errore', 'class' => 'ipv-badge-failed']
        ];

        $badge = isset($badges[$status]) ? $badges[$status] : ['label' => $status, 'class' => ''];

        return sprintf(
            '<span class="ipv-status-badge %s">%s</span>',
            esc_attr($badge['class']),
            esc_html($badge['label'])
        );
    }
}
