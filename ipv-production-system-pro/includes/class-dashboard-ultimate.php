<?php
/**
 * Dashboard Ultimate - Professional SaaS-style Dashboard
 *
 * Features:
 * - Real-time stats cards with trends
 * - Interactive Chart.js graphs (last 30 days)
 * - Quick actions with gradient buttons
 * - Recent activity with live updates
 * - System status panel
 * - Top 5 videos with thumbnails
 * - Quick stats breakdown
 * - Storage & performance metrics
 */

if (!defined('ABSPATH')) exit;

class IPV_Dashboard_Ultimate {

    public static function init() {
        add_action('admin_menu', [__CLASS__, 'add_menu'], 5);
        add_action('admin_enqueue_scripts', [__CLASS__, 'enqueue_assets']);
        add_action('wp_ajax_ipv_recent_activity', [__CLASS__, 'ajax_recent_activity']);
    }

    public static function add_menu() {
        add_submenu_page(
            'edit.php?post_type=ipv_video',
            __('Dashboard Ultimate', 'ipv-production-pro'),
            __('📊 Dashboard Ultimate', 'ipv-production-pro'),
            'manage_options',
            'ipv-dashboard-ultimate',
            [__CLASS__, 'render_page']
        );
    }

    public static function enqueue_assets($hook) {
        if (strpos($hook, 'ipv-dashboard-ultimate') === false) return;

        // Chart.js from CDN
        wp_enqueue_script('chartjs', 'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js', [], '4.4.0', true);

        // Inline CSS
        wp_add_inline_style('wp-admin', self::get_inline_css());
    }

    public static function render_page() {
        if (!current_user_can('manage_options')) wp_die('Non autorizzato');

        $stats = self::get_stats();
        $system = self::get_system_status();

        ?>
        <div class="wrap ipv-ultimate-dashboard">
            <!-- Header -->
            <div class="ipv-dash-header">
                <div class="ipv-dash-header-content">
                    <h1>
                        <span class="dashicons dashicons-video-alt3"></span>
                        IPV Production System Pro - Dashboard Ultimate
                    </h1>
                    <p class="ipv-dash-subtitle">Sistema Editoriale Professionale per Video YouTube</p>
                </div>
                <div class="ipv-dash-header-actions">
                    <button class="button button-primary" onclick="location.href='?page=ipv-video-manager'">
                        <span class="dashicons dashicons-download"></span> Importa Video
                    </button>
                    <button class="button" onclick="location.reload()">
                        <span class="dashicons dashicons-update"></span> Refresh
                    </button>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="ipv-stats-grid">
                <!-- Total Videos -->
                <div class="ipv-stat-card ipv-card-blue">
                    <div class="ipv-stat-icon">
                        <span class="dashicons dashicons-video-alt3"></span>
                    </div>
                    <div class="ipv-stat-content">
                        <h3><?php echo number_format($stats['total_videos']); ?></h3>
                        <p>Video Totali</p>
                        <span class="ipv-stat-change <?php echo $stats['videos_change'] >= 0 ? 'positive' : 'negative'; ?>">
                            <?php echo $stats['videos_change'] >= 0 ? '+' : ''; ?><?php echo $stats['videos_change']; ?> questa settimana
                        </span>
                    </div>
                </div>

                <!-- With Transcripts -->
                <div class="ipv-stat-card ipv-card-green">
                    <div class="ipv-stat-icon">
                        <span class="dashicons dashicons-media-text"></span>
                    </div>
                    <div class="ipv-stat-content">
                        <h3><?php echo number_format($stats['with_transcripts']); ?></h3>
                        <p>Con Trascrizione</p>
                        <span class="ipv-stat-change">
                            <?php echo round(($stats['with_transcripts'] / max($stats['total_videos'], 1)) * 100); ?>% completato
                        </span>
                    </div>
                </div>

                <!-- AI Generated -->
                <div class="ipv-stat-card ipv-card-purple">
                    <div class="ipv-stat-icon">
                        <span class="dashicons dashicons-admin-generic"></span>
                    </div>
                    <div class="ipv-stat-content">
                        <h3><?php echo number_format($stats['ai_generated']); ?></h3>
                        <p>AI Generati</p>
                        <span class="ipv-stat-change">
                            <?php echo round(($stats['ai_generated'] / max($stats['total_videos'], 1)) * 100); ?>% completato
                        </span>
                    </div>
                </div>

                <!-- Total Views -->
                <div class="ipv-stat-card ipv-card-orange">
                    <div class="ipv-stat-icon">
                        <span class="dashicons dashicons-visibility"></span>
                    </div>
                    <div class="ipv-stat-content">
                        <h3><?php echo self::format_number($stats['total_views']); ?></h3>
                        <p>Visualizzazioni Totali</p>
                        <span class="ipv-stat-change positive">
                            Media: <?php echo self::format_number($stats['avg_views']); ?> per video
                        </span>
                    </div>
                </div>
            </div>

            <!-- Main Content Grid -->
            <div class="ipv-content-grid">
                <!-- Left Column -->
                <div class="ipv-content-left">

                    <!-- Chart -->
                    <div class="ipv-card">
                        <div class="ipv-card-header">
                            <h2><span class="dashicons dashicons-chart-line"></span> Statistiche Ultimi 30 Giorni</h2>
                        </div>
                        <div class="ipv-card-body">
                            <canvas id="ipvStatsChart" height="80"></canvas>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="ipv-card">
                        <div class="ipv-card-header">
                            <h2><span class="dashicons dashicons-admin-tools"></span> Azioni Rapide</h2>
                        </div>
                        <div class="ipv-card-body">
                            <div class="ipv-quick-actions">
                                <a href="?page=ipv-video-manager" class="ipv-action-btn ipv-btn-primary">
                                    <span class="dashicons dashicons-download"></span>
                                    <div>
                                        <strong>Import Video</strong>
                                        <small>Importa video da YouTube</small>
                                    </div>
                                </a>
                                <a href="?page=ipv-production-settings" class="ipv-action-btn ipv-btn-secondary">
                                    <span class="dashicons dashicons-admin-settings"></span>
                                    <div>
                                        <strong>Impostazioni</strong>
                                        <small>Configura API keys</small>
                                    </div>
                                </a>
                                <a href="edit.php?post_type=ipv_video" class="ipv-action-btn ipv-btn-tertiary">
                                    <span class="dashicons dashicons-video-alt3"></span>
                                    <div>
                                        <strong>Tutti i Video</strong>
                                        <small>Gestisci contenuti</small>
                                    </div>
                                </a>
                                <a href="?page=ipv-tools-advanced" class="ipv-action-btn ipv-btn-success">
                                    <span class="dashicons dashicons-admin-tools"></span>
                                    <div>
                                        <strong>Tools Avanzati</strong>
                                        <small>Export, diagnostics, cache</small>
                                    </div>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Activity -->
                    <div class="ipv-card">
                        <div class="ipv-card-header">
                            <h2><span class="dashicons dashicons-backup"></span> Attività Recente</h2>
                        </div>
                        <div class="ipv-card-body">
                            <div id="ipv-recent-activity">
                                <p style="text-align:center;padding:20px;color:#999;">
                                    <span class="dashicons dashicons-update" style="animation: rotation 2s infinite linear;"></span>
                                    Caricamento...
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column -->
                <div class="ipv-content-right">

                    <!-- System Status -->
                    <div class="ipv-card">
                        <div class="ipv-card-header">
                            <h2><span class="dashicons dashicons-admin-generic"></span> Stato Sistema</h2>
                        </div>
                        <div class="ipv-card-body">
                            <div class="ipv-system-status">
                                <div class="ipv-status-item">
                                    <div class="ipv-status-label">
                                        <span class="dashicons dashicons-youtube"></span> YouTube API
                                    </div>
                                    <div class="ipv-status-value <?php echo $system['youtube'] ? 'status-ok' : 'status-error'; ?>">
                                        <?php echo $system['youtube'] ? '✓ Configurato' : '✗ Non configurato'; ?>
                                    </div>
                                </div>
                                <div class="ipv-status-item">
                                    <div class="ipv-status-label">
                                        <span class="dashicons dashicons-admin-generic"></span> OpenAI API
                                    </div>
                                    <div class="ipv-status-value <?php echo $system['openai'] ? 'status-ok' : 'status-error'; ?>">
                                        <?php echo $system['openai'] ? '✓ Configurato' : '✗ Non configurato'; ?>
                                    </div>
                                </div>
                                <div class="ipv-status-item">
                                    <div class="ipv-status-label">
                                        <span class="dashicons dashicons-media-text"></span> SupaData API
                                    </div>
                                    <div class="ipv-status-value <?php echo $system['supadata'] ? 'status-ok' : 'status-warning'; ?>">
                                        <?php echo $system['supadata'] ? '✓ Configurato' : '○ Opzionale'; ?>
                                    </div>
                                </div>
                                <div class="ipv-status-item">
                                    <div class="ipv-status-label">
                                        <span class="dashicons dashicons-update"></span> Cron YouTube Updates
                                    </div>
                                    <div class="ipv-status-value status-ok">
                                        ✓ Attivo
                                    </div>
                                </div>
                                <div class="ipv-status-item">
                                    <div class="ipv-status-label">
                                        <span class="dashicons dashicons-wordpress"></span> WordPress
                                    </div>
                                    <div class="ipv-status-value status-ok">
                                        <?php echo get_bloginfo('version'); ?>
                                    </div>
                                </div>
                                <div class="ipv-status-item">
                                    <div class="ipv-status-label">
                                        <span class="dashicons dashicons-editor-code"></span> PHP
                                    </div>
                                    <div class="ipv-status-value <?php echo version_compare(PHP_VERSION, '7.4', '>=') ? 'status-ok' : 'status-warning'; ?>">
                                        <?php echo PHP_VERSION; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Top Videos -->
                    <div class="ipv-card">
                        <div class="ipv-card-header">
                            <h2><span class="dashicons dashicons-star-filled"></span> Top 5 Video</h2>
                        </div>
                        <div class="ipv-card-body">
                            <?php self::render_top_videos(); ?>
                        </div>
                    </div>

                    <!-- Quick Stats -->
                    <div class="ipv-card">
                        <div class="ipv-card-header">
                            <h2><span class="dashicons dashicons-chart-pie"></span> Statistiche Rapide</h2>
                        </div>
                        <div class="ipv-card-body">
                            <div class="ipv-quick-stats">
                                <div class="ipv-quick-stat">
                                    <span class="ipv-stat-number"><?php echo $stats['total_videos']; ?></span>
                                    <span class="ipv-stat-label">Video</span>
                                </div>
                                <div class="ipv-quick-stat">
                                    <span class="ipv-stat-number"><?php echo $stats['with_transcripts']; ?></span>
                                    <span class="ipv-stat-label">Trascrizioni</span>
                                </div>
                                <div class="ipv-quick-stat">
                                    <span class="ipv-stat-number"><?php echo $stats['ai_generated']; ?></span>
                                    <span class="ipv-stat-label">AI</span>
                                </div>
                                <div class="ipv-quick-stat">
                                    <span class="ipv-stat-number"><?php echo round($stats['avg_duration'] / 60); ?>m</span>
                                    <span class="ipv-stat-label">Durata Media</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Storage Info -->
                    <div class="ipv-card">
                        <div class="ipv-card-header">
                            <h2><span class="dashicons dashicons-database-view"></span> Storage & Cache</h2>
                        </div>
                        <div class="ipv-card-body">
                            <div class="ipv-storage-info">
                                <div class="ipv-storage-item">
                                    <strong>Upload Directory:</strong>
                                    <span><?php echo $system['uploads_size']; ?></span>
                                </div>
                                <div class="ipv-storage-item">
                                    <strong>Debug Log:</strong>
                                    <span><?php echo $system['log_size']; ?></span>
                                </div>
                                <div class="ipv-storage-item">
                                    <strong>Database Size:</strong>
                                    <span><?php echo $system['db_size']; ?></span>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <!-- Chart Script -->
            <script>
            jQuery(document).ready(function($) {
                // Chart initialization
                const ctx = document.getElementById('ipvStatsChart');
                if (ctx && typeof Chart !== 'undefined') {
                    new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: <?php echo json_encode($stats['chart_labels']); ?>,
                            datasets: [{
                                label: 'Video Importati',
                                data: <?php echo json_encode($stats['chart_videos']); ?>,
                                borderColor: '#4f46e5',
                                backgroundColor: 'rgba(79, 70, 229, 0.1)',
                                tension: 0.4,
                                fill: true
                            }, {
                                label: 'AI Generati',
                                data: <?php echo json_encode($stats['chart_ai']); ?>,
                                borderColor: '#10b981',
                                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                                tension: 0.4,
                                fill: true
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: true,
                            plugins: {
                                legend: {
                                    position: 'bottom'
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        precision: 0
                                    }
                                }
                            }
                        }
                    });
                }

                // Load recent activity via AJAX
                $.post(ajaxurl, {
                    action: 'ipv_recent_activity'
                }, function(response) {
                    if (response.success) {
                        $('#ipv-recent-activity').html(response.data);
                    } else {
                        $('#ipv-recent-activity').html('<p style="text-align:center;color:#999;padding:20px;">Nessuna attività</p>');
                    }
                });
            });
            </script>
        </div>
        <?php
    }

    /**
     * Get dashboard statistics
     */
    private static function get_stats() {
        global $wpdb;

        // Total videos
        $total = wp_count_posts('ipv_video');
        $total_videos = $total->publish + $total->draft;

        // Videos with transcripts
        $with_transcripts = $wpdb->get_var("
            SELECT COUNT(DISTINCT post_id)
            FROM {$wpdb->postmeta}
            WHERE meta_key = '_ipv_transcript'
            AND meta_value != ''
        ");

        // AI generated (videos with AI content)
        $ai_generated = $wpdb->get_var("
            SELECT COUNT(DISTINCT post_id)
            FROM {$wpdb->postmeta}
            WHERE meta_key = '_ipv_ai_content'
            AND meta_value != ''
        ");

        // Views and duration
        $videos = get_posts(['post_type' => 'ipv_video', 'posts_per_page' => -1, 'fields' => 'ids']);
        $total_views = 0;
        $total_duration = 0;

        foreach ($videos as $vid) {
            $views = get_post_meta($vid, '_ipv_view_count', true);
            $duration = get_post_meta($vid, '_ipv_duration', true);

            $total_views += intval($views);
            $total_duration += self::duration_to_seconds($duration);
        }

        // Videos change (last 7 days)
        $week_ago = date('Y-m-d', strtotime('-7 days'));
        $videos_change = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*)
            FROM {$wpdb->posts}
            WHERE post_type = 'ipv_video'
            AND post_date >= %s
        ", $week_ago));

        // Chart data (last 30 days)
        $chart_labels = [];
        $chart_videos = [];
        $chart_ai = [];

        for ($i = 29; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $chart_labels[] = date('d M', strtotime($date));

            // Videos created on this date
            $count = $wpdb->get_var($wpdb->prepare("
                SELECT COUNT(*)
                FROM {$wpdb->posts}
                WHERE post_type = 'ipv_video'
                AND DATE(post_date) = %s
            ", $date));
            $chart_videos[] = intval($count);

            // AI generated on this date (check for AI meta created on this date)
            $ai_count = $wpdb->get_var($wpdb->prepare("
                SELECT COUNT(DISTINCT pm.post_id)
                FROM {$wpdb->postmeta} pm
                INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
                WHERE pm.meta_key = '_ipv_ai_content'
                AND pm.meta_value != ''
                AND DATE(p.post_modified) = %s
            ", $date));
            $chart_ai[] = intval($ai_count);
        }

        return [
            'total_videos' => intval($total_videos),
            'with_transcripts' => intval($with_transcripts),
            'ai_generated' => intval($ai_generated),
            'total_views' => intval($total_views),
            'avg_views' => $total_videos > 0 ? round($total_views / $total_videos) : 0,
            'avg_duration' => $total_videos > 0 ? round($total_duration / $total_videos) : 0,
            'videos_change' => intval($videos_change),
            'chart_labels' => $chart_labels,
            'chart_videos' => $chart_videos,
            'chart_ai' => $chart_ai
        ];
    }

    /**
     * Get system status
     */
    private static function get_system_status() {
        return [
            'youtube' => !empty(get_option('ipv_pro_youtube_api_key')),
            'openai' => !empty(get_option('ipv_pro_openai_api_key')),
            'supadata' => !empty(get_option('ipv_pro_supadata_api_key')),
            'uploads_size' => self::format_bytes(self::get_uploads_size()),
            'log_size' => self::format_bytes(self::get_log_size()),
            'db_size' => self::format_bytes(self::get_db_size())
        ];
    }

    /**
     * Render top 5 videos by views
     */
    private static function render_top_videos() {
        $videos = get_posts([
            'post_type' => 'ipv_video',
            'posts_per_page' => 5,
            'meta_key' => '_ipv_view_count',
            'orderby' => 'meta_value_num',
            'order' => 'DESC'
        ]);

        if (empty($videos)) {
            echo '<p style="text-align:center;color:#999;padding:20px;">Nessun video ancora</p>';
            return;
        }

        echo '<div class="ipv-top-videos">';
        foreach ($videos as $i => $video) {
            $views = get_post_meta($video->ID, '_ipv_view_count', true);
            $thumb = get_post_meta($video->ID, '_ipv_thumbnail_url', true);

            echo '<div class="ipv-top-video-item">';
            echo '<span class="ipv-rank">#' . ($i + 1) . '</span>';
            if ($thumb) {
                echo '<img src="' . esc_url($thumb) . '" alt="" style="width:60px;height:auto;border-radius:4px;">';
            }
            echo '<div class="ipv-top-video-info">';
            echo '<strong>' . esc_html(get_the_title($video)) . '</strong>';
            echo '<small>' . self::format_number($views) . ' views</small>';
            echo '</div>';
            echo '</div>';
        }
        echo '</div>';
    }

    /**
     * AJAX: Get recent activity
     */
    public static function ajax_recent_activity() {
        $activities = self::get_recent_activity();

        if (empty($activities)) {
            wp_send_json_success('<p style="text-align:center;color:#999;padding:20px;">Nessuna attività recente</p>');
        }

        $html = '<div class="ipv-activity-list">';
        foreach ($activities as $activity) {
            $html .= '<div class="ipv-activity-item">';
            $html .= '<span class="ipv-activity-icon ' . esc_attr($activity['type']) . '">';
            $html .= '<span class="dashicons ' . esc_attr($activity['icon']) . '"></span>';
            $html .= '</span>';
            $html .= '<div class="ipv-activity-content">';
            $html .= '<strong>' . esc_html($activity['title']) . '</strong>';
            $html .= '<small>' . esc_html($activity['time']) . '</small>';
            $html .= '</div>';
            $html .= '</div>';
        }
        $html .= '</div>';

        wp_send_json_success($html);
    }

    /**
     * Get recent activity from posts
     */
    private static function get_recent_activity() {
        $recent_posts = get_posts([
            'post_type' => 'ipv_video',
            'posts_per_page' => 10,
            'orderby' => 'modified',
            'order' => 'DESC'
        ]);

        $activities = [];
        foreach ($recent_posts as $post) {
            $has_ai = get_post_meta($post->ID, '_ipv_ai_content', true);
            $has_transcript = get_post_meta($post->ID, '_ipv_transcript', true);

            if ($has_ai) {
                $activities[] = [
                    'type' => 'success',
                    'icon' => 'dashicons-yes-alt',
                    'title' => 'AI Generato: ' . get_the_title($post),
                    'time' => human_time_diff(strtotime($post->post_modified), current_time('timestamp')) . ' fa'
                ];
            } elseif ($has_transcript) {
                $activities[] = [
                    'type' => 'info',
                    'icon' => 'dashicons-media-text',
                    'title' => 'Trascrizione: ' . get_the_title($post),
                    'time' => human_time_diff(strtotime($post->post_modified), current_time('timestamp')) . ' fa'
                ];
            } else {
                $activities[] = [
                    'type' => 'info',
                    'icon' => 'dashicons-video-alt3',
                    'title' => 'Importato: ' . get_the_title($post),
                    'time' => human_time_diff(strtotime($post->post_date), current_time('timestamp')) . ' fa'
                ];
            }
        }

        return array_slice($activities, 0, 8);
    }

    /**
     * Helper: Get uploads directory size
     */
    private static function get_uploads_size() {
        $upload_dir = wp_upload_dir();
        $dir = $upload_dir['basedir'];

        if (!is_dir($dir)) return 0;

        $size = 0;
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($files as $file) {
            if ($file->isFile()) {
                $size += $file->getSize();
            }
        }

        return $size;
    }

    /**
     * Helper: Get debug log size
     */
    private static function get_log_size() {
        $log_file = WP_CONTENT_DIR . '/debug.log';
        return file_exists($log_file) ? filesize($log_file) : 0;
    }

    /**
     * Helper: Get database size
     */
    private static function get_db_size() {
        global $wpdb;

        $size = $wpdb->get_var("
            SELECT SUM(data_length + index_length)
            FROM information_schema.TABLES
            WHERE table_schema = '{$wpdb->dbname}'
        ");

        return intval($size);
    }

    /**
     * Helper: Convert duration to seconds
     */
    private static function duration_to_seconds($duration) {
        if (empty($duration)) return 0;

        // ISO 8601 format (PT1H2M3S)
        if (preg_match('/PT(?:(\d+)H)?(?:(\d+)M)?(?:(\d+)S)?/', $duration, $matches)) {
            $hours = isset($matches[1]) ? intval($matches[1]) : 0;
            $minutes = isset($matches[2]) ? intval($matches[2]) : 0;
            $seconds = isset($matches[3]) ? intval($matches[3]) : 0;
            return ($hours * 3600) + ($minutes * 60) + $seconds;
        }

        return 0;
    }

    /**
     * Helper: Format bytes
     */
    private static function format_bytes($bytes) {
        if ($bytes < 1024) return $bytes . ' B';
        if ($bytes < 1024 * 1024) return round($bytes / 1024, 1) . ' KB';
        if ($bytes < 1024 * 1024 * 1024) return round($bytes / (1024 * 1024), 1) . ' MB';
        return round($bytes / (1024 * 1024 * 1024), 1) . ' GB';
    }

    /**
     * Helper: Format number (K, M)
     */
    private static function format_number($num) {
        if ($num < 1000) return number_format($num);
        if ($num < 1000000) return number_format($num / 1000, 1) . 'K';
        return number_format($num / 1000000, 1) . 'M';
    }

    /**
     * Inline CSS for dashboard
     */
    private static function get_inline_css() {
        return <<<CSS
/* IPV Ultimate Dashboard Styles */
.ipv-ultimate-dashboard { margin: 20px 20px 20px 0; }

.ipv-dash-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 30px;
    border-radius: 12px;
    margin-bottom: 30px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}

.ipv-dash-header h1 {
    color: white !important;
    font-size: 28px;
    margin: 0 0 8px 0;
    display: flex;
    align-items: center;
    gap: 12px;
}

.ipv-dash-header h1 .dashicons { font-size: 32px; width: 32px; height: 32px; }
.ipv-dash-subtitle { margin: 0; opacity: 0.9; font-size: 14px; }

.ipv-dash-header-actions { display: flex; gap: 10px; }
.ipv-dash-header-actions .button {
    border: 2px solid rgba(255,255,255,0.3);
    background: rgba(255,255,255,0.1);
    color: white !important;
    backdrop-filter: blur(10px);
    transition: all 0.3s;
}

.ipv-dash-header-actions .button:hover {
    background: rgba(255,255,255,0.2);
    border-color: rgba(255,255,255,0.5);
}

.ipv-dash-header-actions .button-primary {
    background: white !important;
    color: #667eea !important;
    border-color: white !important;
}

.ipv-dash-header-actions .button-primary:hover {
    background: #f0f0f0 !important;
}

.ipv-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.ipv-stat-card {
    background: white;
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    display: flex;
    align-items: center;
    gap: 20px;
    transition: transform 0.2s, box-shadow 0.2s;
}

.ipv-stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.12);
}

.ipv-stat-icon {
    width: 60px;
    height: 60px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 28px;
}

.ipv-card-blue .ipv-stat-icon {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.ipv-card-green .ipv-stat-icon {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: white;
}

.ipv-card-purple .ipv-stat-icon {
    background: linear-gradient(135deg, #a855f7 0%, #7e22ce 100%);
    color: white;
}

.ipv-card-orange .ipv-stat-icon {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    color: white;
}

.ipv-stat-content h3 {
    margin: 0 0 4px 0;
    font-size: 32px;
    font-weight: 700;
    color: #1f2937;
}

.ipv-stat-content p {
    margin: 0 0 8px 0;
    color: #6b7280;
    font-size: 14px;
    font-weight: 500;
}

.ipv-stat-change {
    font-size: 12px;
    color: #6b7280;
}

.ipv-stat-change.positive { color: #10b981; }
.ipv-stat-change.negative { color: #ef4444; }

.ipv-content-grid {
    display: grid;
    grid-template-columns: 1fr 400px;
    gap: 20px;
}

@media (max-width: 1400px) {
    .ipv-content-grid {
        grid-template-columns: 1fr;
    }
}

.ipv-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    margin-bottom: 20px;
    overflow: hidden;
}

.ipv-card-header {
    padding: 20px 24px;
    border-bottom: 1px solid #e5e7eb;
    background: #f9fafb;
}

.ipv-card-header h2 {
    margin: 0;
    font-size: 16px;
    font-weight: 600;
    color: #1f2937;
    display: flex;
    align-items: center;
    gap: 8px;
}

.ipv-card-body { padding: 24px; }

.ipv-quick-actions {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 16px;
}

.ipv-action-btn {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 20px;
    border-radius: 8px;
    text-decoration: none;
    transition: all 0.2s;
    border: 2px solid #e5e7eb;
    color: inherit;
}

.ipv-action-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    text-decoration: none;
}

.ipv-btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white !important;
    border-color: transparent;
}

.ipv-btn-secondary {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: white !important;
    border-color: transparent;
}

.ipv-btn-tertiary {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    color: white !important;
    border-color: transparent;
}

.ipv-btn-success {
    background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%);
    color: white !important;
    border-color: transparent;
}

.ipv-action-btn strong {
    display: block;
    font-size: 14px;
    font-weight: 600;
}

.ipv-action-btn small {
    display: block;
    font-size: 12px;
    opacity: 0.8;
}

.ipv-system-status { display: flex; flex-direction: column; gap: 12px; }

.ipv-status-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px;
    background: #f9fafb;
    border-radius: 6px;
}

.ipv-status-label {
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: 500;
    font-size: 13px;
}

.ipv-status-value {
    font-size: 13px;
    font-weight: 600;
    padding: 4px 12px;
    border-radius: 4px;
}

.ipv-status-value.status-ok {
    background: #d1fae5;
    color: #065f46;
}

.ipv-status-value.status-error {
    background: #fee2e2;
    color: #991b1b;
}

.ipv-status-value.status-warning {
    background: #fef3c7;
    color: #92400e;
}

.ipv-top-videos {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.ipv-top-video-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px;
    background: #f9fafb;
    border-radius: 6px;
    transition: background 0.2s;
}

.ipv-top-video-item:hover {
    background: #f3f4f6;
}

.ipv-rank {
    font-weight: 700;
    color: #667eea;
    font-size: 18px;
    min-width: 30px;
}

.ipv-top-video-info {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.ipv-top-video-info strong {
    font-size: 13px;
    color: #1f2937;
    display: block;
}

.ipv-top-video-info small {
    font-size: 12px;
    color: #6b7280;
}

.ipv-quick-stats {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
}

.ipv-quick-stat {
    text-align: center;
    padding: 16px;
    background: #f9fafb;
    border-radius: 8px;
}

.ipv-stat-number {
    display: block;
    font-size: 24px;
    font-weight: 700;
    color: #1f2937;
    margin-bottom: 4px;
}

.ipv-stat-label {
    display: block;
    font-size: 11px;
    color: #6b7280;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.ipv-storage-info {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.ipv-storage-item {
    display: flex;
    justify-content: space-between;
    padding: 10px 12px;
    background: #f9fafb;
    border-radius: 4px;
    font-size: 13px;
}

.ipv-activity-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.ipv-activity-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px;
    background: #f9fafb;
    border-radius: 6px;
}

.ipv-activity-icon {
    width: 40px;
    height: 40px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.ipv-activity-icon.success {
    background: #d1fae5;
    color: #065f46;
}

.ipv-activity-icon.info {
    background: #dbeafe;
    color: #1e40af;
}

.ipv-activity-content {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.ipv-activity-content strong {
    font-size: 13px;
    color: #1f2937;
}

.ipv-activity-content small {
    font-size: 12px;
    color: #6b7280;
}

@keyframes rotation {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}
CSS;
    }
}
