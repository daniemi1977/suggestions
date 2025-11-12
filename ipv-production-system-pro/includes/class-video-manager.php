<?php
/**
 * Video Manager
 *
 * Interface for managing imported videos with detailed status
 */

if (!defined('ABSPATH')) {
    exit;
}

class IPV_Video_Manager {

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
     * Render video manager page
     */
    public function render_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        // Get filter status
        $filter_status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : null;

        // Get queue items
        $items = $this->queue_manager->get_queue_items($filter_status, 50, 0);
        $stats = $this->queue_manager->get_stats();

        ?>
        <div class="wrap ipv-pro-video-manager">
            <h1>📹 Video Manager</h1>

            <!-- Stats Bar -->
            <div class="ipv-stats-bar">
                <div class="ipv-stat">
                    <span class="ipv-stat-label">Totale</span>
                    <span class="ipv-stat-value"><?php echo esc_html($stats['total']); ?></span>
                </div>
                <div class="ipv-stat">
                    <span class="ipv-stat-label">In Coda</span>
                    <span class="ipv-stat-value ipv-stat-pending"><?php echo esc_html($stats['pending']); ?></span>
                </div>
                <div class="ipv-stat">
                    <span class="ipv-stat-label">In Elaborazione</span>
                    <span class="ipv-stat-value ipv-stat-processing"><?php echo esc_html($stats['processing']); ?></span>
                </div>
                <div class="ipv-stat">
                    <span class="ipv-stat-label">Completati</span>
                    <span class="ipv-stat-value ipv-stat-completed"><?php echo esc_html($stats['completed']); ?></span>
                </div>
                <div class="ipv-stat">
                    <span class="ipv-stat-label">Errori</span>
                    <span class="ipv-stat-value ipv-stat-failed"><?php echo esc_html($stats['failed']); ?></span>
                </div>
            </div>

            <!-- Filter Tabs -->
            <div class="ipv-filter-tabs">
                <a href="?page=ipv-video-manager" class="<?php echo is_null($filter_status) ? 'active' : ''; ?>">
                    Tutti
                </a>
                <a href="?page=ipv-video-manager&status=pending" class="<?php echo $filter_status === 'pending' ? 'active' : ''; ?>">
                    In Coda
                </a>
                <a href="?page=ipv-video-manager&status=processing" class="<?php echo $filter_status === 'processing' ? 'active' : ''; ?>">
                    In Elaborazione
                </a>
                <a href="?page=ipv-video-manager&status=completed" class="<?php echo $filter_status === 'completed' ? 'active' : ''; ?>">
                    Completati
                </a>
                <a href="?page=ipv-video-manager&status=failed" class="<?php echo $filter_status === 'failed' ? 'active' : ''; ?>">
                    Errori
                </a>
            </div>

            <!-- Videos Table -->
            <?php if (empty($items)): ?>
                <div class="ipv-empty-state">
                    <p>📭 Nessun video in coda. <a href="?page=ipv-production-pro">Importa il tuo primo video</a>!</p>
                </div>
            <?php else: ?>
                <table class="wp-list-table widefat fixed striped ipv-videos-table">
                    <thead>
                        <tr>
                            <th style="width: 5%;">ID</th>
                            <th style="width: 40%;">Video</th>
                            <th style="width: 20%;">Stato</th>
                            <th style="width: 15%;">Step Corrente</th>
                            <th style="width: 10%;">Tentativi</th>
                            <th style="width: 10%;">Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                            <?php
                            $post = get_post($item->post_id);
                            $video_url = get_post_meta($item->post_id, '_ipv_video_url', true);
                            $error = $item->error_message;
                            ?>
                            <tr>
                                <td><?php echo esc_html($item->id); ?></td>
                                <td>
                                    <strong>
                                        <?php if ($post): ?>
                                            <a href="<?php echo get_edit_post_link($item->post_id); ?>" target="_blank">
                                                <?php echo esc_html($post->post_title); ?>
                                            </a>
                                        <?php else: ?>
                                            Post #<?php echo esc_html($item->post_id); ?>
                                        <?php endif; ?>
                                    </strong>
                                    <br>
                                    <small>
                                        <a href="<?php echo esc_url($video_url); ?>" target="_blank" class="ipv-video-link">
                                            <?php echo esc_html($video_url); ?>
                                        </a>
                                    </small>
                                </td>
                                <td>
                                    <?php echo $this->render_status_badge($item->status, $error); ?>
                                </td>
                                <td>
                                    <span class="ipv-step-badge">
                                        <?php echo $this->get_step_label($item->current_step); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php echo esc_html($item->retry_count); ?> / <?php echo get_option('ipv_pro_max_retry', 3); ?>
                                </td>
                                <td>
                                    <?php echo $this->render_actions($item); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Render status badge with tooltip for errors
     */
    private function render_status_badge($status, $error = null) {
        $badges = [
            'pending' => ['label' => '○ In Coda', 'class' => 'ipv-badge-pending', 'icon' => '○'],
            'processing' => ['label' => '⏳ Elaborando', 'class' => 'ipv-badge-processing', 'icon' => '⏳'],
            'completed' => ['label' => '✓ Completato', 'class' => 'ipv-badge-completed', 'icon' => '✓'],
            'failed' => ['label' => '✗ Errore', 'class' => 'ipv-badge-failed', 'icon' => '✗']
        ];

        $badge = isset($badges[$status]) ? $badges[$status] : ['label' => $status, 'class' => '', 'icon' => ''];

        $output = '<span class="ipv-status-badge ' . esc_attr($badge['class']) . '">';
        $output .= esc_html($badge['label']);
        $output .= '</span>';

        // Add error tooltip if present
        if ($status === 'failed' && !empty($error)) {
            $output .= ' <span class="ipv-error-tooltip" title="' . esc_attr($error) . '">ℹ️</span>';
        }

        return $output;
    }

    /**
     * Get human-readable step label
     */
    private function get_step_label($step) {
        $labels = [
            'queued' => 'In coda',
            'Inizio elaborazione' => 'Inizializzazione',
            'Importazione dati YouTube' => 'Import YouTube',
            'Generazione trascrizione' => 'Trascrizione',
            'Generazione contenuti AI' => 'AI Content',
            'Finalizzazione post' => 'Finalizzazione',
            'Elaborazione completata' => 'Completato',
            'retry_scheduled' => 'Retry programmato',
            'retry_manual' => 'Retry manuale'
        ];

        return isset($labels[$step]) ? $labels[$step] : $step;
    }

    /**
     * Render action buttons
     */
    private function render_actions($item) {
        $actions = '';

        // Retry button for failed items
        if ($item->status === 'failed') {
            $actions .= sprintf(
                '<button class="button button-small ipv-action-retry" data-queue-id="%d" title="Riprova elaborazione">
                    🔄 Riprova
                </button> ',
                $item->id
            );
        }

        // View post button
        if ($item->status === 'completed') {
            $actions .= sprintf(
                '<a href="%s" class="button button-small" target="_blank" title="Vedi post">
                    👁️ Vedi
                </a> ',
                get_edit_post_link($item->post_id)
            );
        }

        // Delete button
        $actions .= sprintf(
            '<button class="button button-small button-link-delete ipv-action-delete" data-queue-id="%d" data-post-id="%d" title="Elimina dalla coda">
                🗑️
            </button>',
            $item->id,
            $item->post_id
        );

        return $actions;
    }
}
