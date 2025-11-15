<?php
/**
 * Tools Avanzati - Professional Enterprise-Grade Tools
 *
 * Features:
 * - Export/Import Configuration (JSON backup/restore)
 * - Cache Management (granular control)
 * - Database Optimizer (+ orphan meta cleanup)
 * - Media Duplicate/Orphan Checker
 * - Diagnostics Panel (automatic system checks)
 * - Log Viewer (syntax highlighting)
 * - Report Generator (CSV/Excel)
 * - Bulk Operations (delete drafts, publish all, update dates)
 * - Shortcode Generator (visual)
 * - System Info (detailed)
 */

if (!defined('ABSPATH')) exit;

class IPV_Tools_Advanced {

    public static function init() {
        add_action('admin_menu', [__CLASS__, 'add_menu'], 30);

        // Register all AJAX handlers
        add_action('wp_ajax_ipv_export_config', [__CLASS__, 'ajax_export_config']);
        add_action('wp_ajax_ipv_import_config', [__CLASS__, 'ajax_import_config']);
        add_action('wp_ajax_ipv_clear_cache', [__CLASS__, 'ajax_clear_cache']);
        add_action('wp_ajax_ipv_optimize_db', [__CLASS__, 'ajax_optimize_db']);
        add_action('wp_ajax_ipv_clean_orphans', [__CLASS__, 'ajax_clean_orphans']);
        add_action('wp_ajax_ipv_check_media_duplicates', [__CLASS__, 'ajax_check_media_duplicates']);
        add_action('wp_ajax_ipv_clean_orphan_media', [__CLASS__, 'ajax_clean_orphan_media']);
        add_action('wp_ajax_ipv_run_diagnostic', [__CLASS__, 'ajax_run_diagnostic']);
        add_action('wp_ajax_ipv_test_apis', [__CLASS__, 'ajax_test_apis']);
        add_action('wp_ajax_ipv_refresh_logs', [__CLASS__, 'ajax_refresh_logs']);
        add_action('wp_ajax_ipv_clear_logs', [__CLASS__, 'ajax_clear_logs']);
        add_action('wp_ajax_ipv_download_logs', [__CLASS__, 'ajax_download_logs']);
        add_action('wp_ajax_ipv_generate_csv', [__CLASS__, 'ajax_generate_csv']);
        add_action('wp_ajax_ipv_bulk_delete_drafts', [__CLASS__, 'ajax_bulk_delete_drafts']);
        add_action('wp_ajax_ipv_bulk_publish_all', [__CLASS__, 'ajax_bulk_publish_all']);
        add_action('wp_ajax_ipv_bulk_update_dates', [__CLASS__, 'ajax_bulk_update_dates']);
        add_action('wp_ajax_ipv_check_orphan_videos', [__CLASS__, 'ajax_check_orphan_videos']);
        add_action('wp_ajax_ipv_delete_orphan_videos', [__CLASS__, 'ajax_delete_orphan_videos']);
        add_action('wp_ajax_ipv_repair_orphan_videos', [__CLASS__, 'ajax_repair_orphan_videos']);
        add_action('wp_ajax_ipv_deep_clean_database', [__CLASS__, 'ajax_deep_clean_database']);
    }

    public static function add_menu() {
        add_submenu_page(
            'edit.php?post_type=ipv_video',
            __('Tools Avanzati', 'ipv-production-pro'),
            __('🛠️ Tools Avanzati', 'ipv-production-pro'),
            'manage_options',
            'ipv-tools-advanced',
            [__CLASS__, 'render_page']
        );
    }

    public static function render_page() {
        if (!current_user_can('manage_options')) wp_die('Non autorizzato');

        $cache_size = self::get_cache_size();
        $db_info = self::get_db_info();
        $media_info = self::get_media_info();
        $orphan_videos_info = self::get_orphan_videos_info();

        ?>
        <div class="wrap ipv-tools-advanced">
            <h1><span class="dashicons dashicons-admin-tools"></span> Tools Avanzati - Enterprise Edition</h1>
            <p class="description">Strumenti professionali per gestione, manutenzione e ottimizzazione del sistema.</p>

            <div class="ipv-tools-grid">

                <!-- Export/Import Configuration -->
                <div class="ipv-tool-card">
                    <div class="ipv-tool-header">
                        <span class="dashicons dashicons-database-export"></span>
                        <h2>Export/Import Configurazione</h2>
                    </div>
                    <div class="ipv-tool-body">
                        <p>Esporta o importa tutte le impostazioni del plugin in formato JSON.</p>

                        <div class="ipv-tool-actions">
                            <button class="button button-primary" id="ipv-export-config">
                                <span class="dashicons dashicons-download"></span> Export Config (JSON)
                            </button>
                            <button class="button" onclick="document.getElementById('ipv-import-file').click()">
                                <span class="dashicons dashicons-upload"></span> Import Config
                            </button>
                            <input type="file" id="ipv-import-file" style="display:none" accept=".json">
                        </div>

                        <div id="export-import-status" class="ipv-tool-status"></div>
                    </div>
                </div>

                <!-- Cache Management -->
                <div class="ipv-tool-card">
                    <div class="ipv-tool-header">
                        <span class="dashicons dashicons-database"></span>
                        <h2>Gestione Cache</h2>
                    </div>
                    <div class="ipv-tool-body">
                        <p>Pulisci cache WordPress per liberare spazio e risolvere problemi.</p>

                        <div class="ipv-cache-info">
                            <strong>Transients Totali:</strong> <?php echo $cache_size['count']; ?> (<?php echo self::format_bytes($cache_size['size']); ?>)
                        </div>

                        <div class="ipv-tool-actions">
                            <button class="button button-primary" id="ipv-clear-cache-all">
                                <span class="dashicons dashicons-trash"></span> Pulisci Tutto
                            </button>
                            <button class="button" id="ipv-clear-cache-ipv">
                                <span class="dashicons dashicons-video-alt3"></span> Solo IPV
                            </button>
                        </div>

                        <div id="cache-status" class="ipv-tool-status"></div>
                    </div>
                </div>

                <!-- Database Optimizer -->
                <div class="ipv-tool-card">
                    <div class="ipv-tool-header">
                        <span class="dashicons dashicons-database-view"></span>
                        <h2>Ottimizzazione Database</h2>
                    </div>
                    <div class="ipv-tool-body">
                        <p>Ottimizza tabelle database per migliorare performance.</p>

                        <div class="ipv-db-info">
                            <div><strong>Video Posts:</strong> <?php echo $db_info['posts']; ?></div>
                            <div><strong>Post Meta:</strong> <?php echo $db_info['meta']; ?></div>
                            <div><strong>Orphan Meta:</strong> <?php echo $db_info['orphans']; ?></div>
                            <div><strong>DB Size:</strong> <?php echo $db_info['size']; ?></div>
                        </div>

                        <div class="ipv-tool-actions">
                            <button class="button button-primary" id="ipv-optimize-db">
                                <span class="dashicons dashicons-admin-generic"></span> Ottimizza DB
                            </button>
                            <button class="button button-secondary" id="ipv-clean-orphans">
                                <span class="dashicons dashicons-trash"></span> Pulisci Orphan Meta
                            </button>
                        </div>

                        <div id="db-status" class="ipv-tool-status"></div>
                    </div>
                </div>

                <!-- Orphan Videos Checker (NEW!) -->
                <div class="ipv-tool-card">
                    <div class="ipv-tool-header">
                        <span class="dashicons dashicons-video-alt3"></span>
                        <h2>Video Orfani/Invisibili</h2>
                    </div>
                    <div class="ipv-tool-body">
                        <p>Trova e gestisci video importati precedentemente ma non visibili nella lista Video Manager.</p>

                        <div class="ipv-db-info">
                            <div><strong>Video in Queue:</strong> <?php echo $orphan_videos_info['in_queue']; ?></div>
                            <div><strong>Video Post Totali:</strong> <?php echo $orphan_videos_info['total_posts']; ?></div>
                            <div><strong>Video Orfani:</strong> <span style="color: #d63638; font-weight: bold;"><?php echo $orphan_videos_info['orphans']; ?></span></div>
                        </div>

                        <?php if ($orphan_videos_info['orphans'] > 0): ?>
                            <div class="notice notice-warning inline" style="margin: 10px 0;">
                                <p>⚠️ <strong><?php echo $orphan_videos_info['orphans']; ?> video non visibili</strong> nella lista Video Manager ma presenti nel database!</p>
                            </div>
                        <?php endif; ?>

                        <div class="ipv-tool-actions">
                            <button class="button button-primary" id="ipv-check-orphan-videos">
                                <span class="dashicons dashicons-search"></span> Trova Video Orfani
                            </button>
                            <button class="button button-secondary" id="ipv-repair-orphan-videos" <?php echo $orphan_videos_info['orphans'] == 0 ? 'disabled' : ''; ?>>
                                <span class="dashicons dashicons-admin-tools"></span> Ripara (Riassocia)
                            </button>
                            <button class="button button-link-delete" id="ipv-delete-orphan-videos" <?php echo $orphan_videos_info['orphans'] == 0 ? 'disabled' : ''; ?>>
                                <span class="dashicons dashicons-trash"></span> Elimina Tutti
                            </button>
                        </div>

                        <div id="orphan-videos-status" class="ipv-tool-status"></div>
                        <div id="orphan-videos-results" class="ipv-orphan-videos-results"></div>
                    </div>
                </div>

                <!-- Media Checker -->
                <div class="ipv-tool-card">
                    <div class="ipv-tool-header">
                        <span class="dashicons dashicons-format-image"></span>
                        <h2>Controllo Media Duplicati/Orfani</h2>
                    </div>
                    <div class="ipv-tool-body">
                        <p>Identifica e rimuovi media duplicati e file orfani non collegati a post.</p>

                        <div class="ipv-db-info">
                            <div><strong>Media Totali:</strong> <?php echo $media_info['total']; ?></div>
                            <div><strong>File Orfani:</strong> <?php echo $media_info['orphans']; ?></div>
                            <div><strong>Spazio Occupato:</strong> <?php echo $media_info['size']; ?></div>
                        </div>

                        <div class="ipv-tool-actions">
                            <button class="button button-primary" id="ipv-check-media-duplicates">
                                <span class="dashicons dashicons-search"></span> Trova Duplicati
                            </button>
                            <button class="button button-secondary" id="ipv-clean-orphan-media">
                                <span class="dashicons dashicons-trash"></span> Rimuovi Media Orfani
                            </button>
                        </div>

                        <div id="media-status" class="ipv-tool-status"></div>
                        <div id="media-results" class="ipv-media-results"></div>
                    </div>
                </div>

                <!-- Diagnostics Panel -->
                <div class="ipv-tool-card">
                    <div class="ipv-tool-header">
                        <span class="dashicons dashicons-search"></span>
                        <h2>Diagnostica Sistema</h2>
                    </div>
                    <div class="ipv-tool-body">
                        <p>Esegui controlli completi del sistema per identificare problemi.</p>

                        <div class="ipv-tool-actions">
                            <button class="button button-primary" id="ipv-run-diagnostic">
                                <span class="dashicons dashicons-admin-tools"></span> Esegui Diagnostica
                            </button>
                            <button class="button" id="ipv-test-apis">
                                <span class="dashicons dashicons-admin-plugins"></span> Test API
                            </button>
                        </div>

                        <div id="diagnostic-results" class="ipv-diagnostic-results"></div>
                    </div>
                </div>

                <!-- Log Viewer -->
                <div class="ipv-tool-card ipv-tool-wide">
                    <div class="ipv-tool-header">
                        <span class="dashicons dashicons-media-code"></span>
                        <h2>Log Viewer</h2>
                    </div>
                    <div class="ipv-tool-body">
                        <p>Visualizza ultimi log del sistema con syntax highlighting.</p>

                        <div class="ipv-tool-actions">
                            <button class="button" id="ipv-refresh-logs">
                                <span class="dashicons dashicons-update"></span> Aggiorna
                            </button>
                            <button class="button" id="ipv-clear-logs">
                                <span class="dashicons dashicons-trash"></span> Pulisci Log
                            </button>
                            <button class="button" id="ipv-download-logs">
                                <span class="dashicons dashicons-download"></span> Download
                            </button>
                        </div>

                        <div class="ipv-log-viewer" id="ipv-log-viewer">
                            <?php self::render_logs(); ?>
                        </div>
                    </div>
                </div>

                <!-- Report Generator -->
                <div class="ipv-tool-card ipv-tool-wide">
                    <div class="ipv-tool-header">
                        <span class="dashicons dashicons-chart-bar"></span>
                        <h2>Generatore Report</h2>
                    </div>
                    <div class="ipv-tool-body">
                        <p>Genera report dettagliati su video, performance e attività.</p>

                        <div class="ipv-report-options">
                            <label>
                                <input type="checkbox" name="report_stats" checked> Statistiche Video
                            </label>
                            <label>
                                <input type="checkbox" name="report_performance" checked> Performance
                            </label>
                            <label>
                                <input type="checkbox" name="report_activity" checked> Attività Recente
                            </label>
                            <label>
                                <input type="checkbox" name="report_api" checked> Stato API
                            </label>
                        </div>

                        <div class="ipv-tool-actions">
                            <button class="button button-primary" id="ipv-generate-csv">
                                <span class="dashicons dashicons-media-spreadsheet"></span> Export CSV
                            </button>
                        </div>

                        <div id="report-status" class="ipv-tool-status"></div>
                    </div>
                </div>

                <!-- Bulk Operations -->
                <div class="ipv-tool-card">
                    <div class="ipv-tool-header">
                        <span class="dashicons dashicons-update-alt"></span>
                        <h2>Operazioni Bulk Avanzate</h2>
                    </div>
                    <div class="ipv-tool-body">
                        <p>Operazioni massive sui video. <strong>Attenzione:</strong> queste azioni sono irreversibili!</p>

                        <div class="ipv-tool-actions">
                            <button class="button" id="ipv-bulk-delete-drafts">
                                <span class="dashicons dashicons-trash"></span> Elimina Tutte le Bozze
                            </button>
                            <button class="button" id="ipv-bulk-publish-all">
                                <span class="dashicons dashicons-yes-alt"></span> Pubblica Tutti i Video
                            </button>
                            <button class="button" id="ipv-bulk-update-dates">
                                <span class="dashicons dashicons-calendar-alt"></span> Aggiorna Date Modifica
                            </button>
                        </div>

                        <div id="bulk-status" class="ipv-tool-status"></div>
                    </div>
                </div>

                <!-- Shortcode Generator -->
                <div class="ipv-tool-card">
                    <div class="ipv-tool-header">
                        <span class="dashicons dashicons-shortcode"></span>
                        <h2>Shortcode Generator</h2>
                    </div>
                    <div class="ipv-tool-body">
                        <p>Genera shortcode personalizzati per visualizzare video.</p>

                        <div class="ipv-shortcode-options">
                            <label>
                                Numero video: <input type="number" id="sc-perpage" value="9" min="1" max="50">
                            </label>
                            <label>
                                <input type="checkbox" id="sc-published-only" checked> Solo pubblicati
                            </label>
                        </div>

                        <div class="ipv-generated-shortcode">
                            <code id="generated-shortcode">[ipv_videos count="9" status="publish"]</code>
                        </div>

                        <div class="ipv-tool-actions">
                            <button class="button button-primary" id="ipv-copy-shortcode">
                                <span class="dashicons dashicons-admin-page"></span> Copia Shortcode
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Deep Clean Database (DANGER ZONE!) -->
                <div class="ipv-tool-card ipv-tool-wide" style="border: 3px solid #d63638;">
                    <div class="ipv-tool-header" style="background: #d63638; color: white;">
                        <span class="dashicons dashicons-warning"></span>
                        <h2>⚠️ PULIZIA PROFONDA DATABASE - ZONA PERICOLOSA</h2>
                    </div>
                    <div class="ipv-tool-body">
                        <div class="notice notice-error inline" style="margin: 0 0 15px 0; padding: 15px; border-left-width: 5px;">
                            <p style="font-size: 16px; font-weight: bold; margin: 0 0 10px 0;">
                                ⛔ ATTENZIONE: QUESTA OPERAZIONE ELIMINA DEFINITIVAMENTE TUTTI I DATI DEI VIDEO!
                            </p>
                            <p style="margin: 0;">
                                Verranno eliminate TUTTE le seguenti informazioni:<br>
                                • Tutti i post ipv_video (pubblicati, bozze, trash)<br>
                                • Tutti i metadati video (_ipv_*)<br>
                                • Tutti i record nella coda processing<br>
                                • Tutte le relazioni con categorie e tassonomie<br>
                                • TUTTO sarà IRRECUPERABILE!
                            </p>
                        </div>

                        <p style="font-weight: bold; color: #d63638;">
                            Usa questo tool SOLO se vuoi ricominciare da zero con un database completamente pulito!
                        </p>

                        <?php
                        global $wpdb;
                        $queue_table = $wpdb->prefix . 'ipv_processing_queue';

                        $total_posts = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'ipv_video'");
                        $total_meta = $wpdb->get_var("
                            SELECT COUNT(*) FROM {$wpdb->postmeta} pm
                            INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
                            WHERE p.post_type = 'ipv_video'
                        ");
                        $total_queue = $wpdb->get_var("SELECT COUNT(*) FROM {$queue_table}");
                        $total_relations = $wpdb->get_var("
                            SELECT COUNT(*) FROM {$wpdb->term_relationships} tr
                            INNER JOIN {$wpdb->posts} p ON tr.object_id = p.ID
                            WHERE p.post_type = 'ipv_video'
                        ");
                        ?>

                        <div class="ipv-db-info" style="background: #fff3cd; padding: 15px; border-left: 4px solid #856404; margin: 15px 0;">
                            <h3 style="margin: 0 0 10px 0; color: #856404;">📊 DATI CHE VERRANNO ELIMINATI:</h3>
                            <div><strong>Video Posts (ipv_video):</strong> <?php echo number_format($total_posts); ?></div>
                            <div><strong>Post Meta Records:</strong> <?php echo number_format($total_meta); ?></div>
                            <div><strong>Queue Records:</strong> <?php echo number_format($total_queue); ?></div>
                            <div><strong>Taxonomy Relations:</strong> <?php echo number_format($total_relations); ?></div>
                            <div style="margin-top: 10px; padding-top: 10px; border-top: 1px solid #856404;">
                                <strong>TOTALE RECORD DA ELIMINARE:</strong> <span style="color: #d63638; font-size: 18px; font-weight: bold;">
                                    <?php echo number_format($total_posts + $total_meta + $total_queue + $total_relations); ?>
                                </span>
                            </div>
                        </div>

                        <div style="background: #f0f0f1; padding: 15px; margin: 15px 0; border-radius: 4px;">
                            <h4 style="margin: 0 0 10px 0;">✅ Questa operazione è utile se:</h4>
                            <ul style="margin: 0;">
                                <li>Vuoi ricominciare da zero con nuovi video</li>
                                <li>Hai dati corrotti o inconsistenti nel database</li>
                                <li>Vuoi fare test di import massivi</li>
                                <li>Hai problemi con video duplicati o invisibili che non riesci a risolvere</li>
                            </ul>
                        </div>

                        <div class="ipv-tool-actions" style="margin-top: 20px;">
                            <button class="button button-large button-link-delete" id="ipv-deep-clean-database" style="height: 50px; font-size: 16px; font-weight: bold;">
                                <span class="dashicons dashicons-trash" style="font-size: 20px;"></span>
                                ELIMINA TUTTO IL DATABASE VIDEO
                            </button>
                        </div>

                        <div id="deep-clean-status" class="ipv-tool-status"></div>
                    </div>
                </div>

                <!-- System Info -->
                <div class="ipv-tool-card ipv-tool-wide">
                    <div class="ipv-tool-header">
                        <span class="dashicons dashicons-info"></span>
                        <h2>Informazioni Sistema</h2>
                    </div>
                    <div class="ipv-tool-body">
                        <?php self::render_system_info(); ?>
                    </div>
                </div>

            </div>

            <?php echo self::get_inline_css(); ?>
            <?php echo self::get_inline_js(); ?>
        </div>
        <?php
    }

    /**
     * =====================================================
     * AJAX HANDLERS
     * =====================================================
     */

    public static function ajax_export_config() {
        $config = [
            'ipv_pro_youtube_api_key' => get_option('ipv_pro_youtube_api_key'),
            'ipv_pro_supadata_api_key' => get_option('ipv_pro_supadata_api_key'),
            'ipv_pro_openai_api_key' => get_option('ipv_pro_openai_api_key'),
            'ipv_pro_openai_model' => get_option('ipv_pro_openai_model'),
            'ipv_pro_transcript_mode' => get_option('ipv_pro_transcript_mode'),
            'ipv_pro_transcript_timeout' => get_option('ipv_pro_transcript_timeout'),
            'ipv_pro_auto_publish' => get_option('ipv_pro_auto_publish'),
            'ipv_pro_batch_size' => get_option('ipv_pro_batch_size'),
            'ipv_pro_rss_feed_url' => get_option('ipv_pro_rss_feed_url'),
            'ipv_pro_auto_import_enabled' => get_option('ipv_pro_auto_import_enabled'),
            'ipv_pro_auto_import_interval' => get_option('ipv_pro_auto_import_interval'),
            'ipv_pro_category_mapping' => get_option('ipv_pro_category_mapping'),
            'version' => IPV_PRO_VERSION,
            'exported' => current_time('mysql')
        ];

        wp_send_json_success($config);
    }

    public static function ajax_import_config() {
        $config = json_decode(stripslashes($_POST['config']), true);

        if (!is_array($config)) {
            wp_send_json_error(['message' => 'Configurazione non valida']);
            return;
        }

        $imported = 0;
        foreach ($config as $key => $value) {
            if (strpos($key, 'ipv_pro_') === 0) {
                update_option($key, $value);
                $imported++;
            }
        }

        wp_send_json_success(['message' => "Configurazione importata con successo! ({$imported} impostazioni)"]);
    }

    public static function ajax_clear_cache() {
        global $wpdb;

        $type = $_POST['type'] ?? 'all';
        $deleted = 0;

        if ($type === 'all') {
            $deleted = $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_%'");
        } elseif ($type === 'ipv') {
            $deleted = $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_ipv_%'");
        }

        wp_send_json_success(['message' => "Cache pulita con successo! ({$deleted} transients eliminati)"]);
    }

    public static function ajax_optimize_db() {
        global $wpdb;

        $tables = [$wpdb->posts, $wpdb->postmeta, $wpdb->options];
        $optimized = 0;

        foreach ($tables as $table) {
            $result = $wpdb->query("OPTIMIZE TABLE {$table}");
            if ($result !== false) $optimized++;
        }

        wp_send_json_success(['message' => "Database ottimizzato! ({$optimized} tabelle)"]);
    }

    public static function ajax_clean_orphans() {
        global $wpdb;

        // Delete orphaned post meta
        $deleted = $wpdb->query("
            DELETE pm FROM {$wpdb->postmeta} pm
            LEFT JOIN {$wpdb->posts} p ON p.ID = pm.post_id
            WHERE p.ID IS NULL
        ");

        wp_send_json_success(['message' => "Orphan meta puliti! ({$deleted} record eliminati)"]);
    }

    public static function ajax_check_media_duplicates() {
        global $wpdb;

        // Find duplicate files by hash
        $duplicates = $wpdb->get_results("
            SELECT post_title, COUNT(*) as count
            FROM {$wpdb->posts}
            WHERE post_type = 'attachment'
            GROUP BY post_title
            HAVING count > 1
        ");

        if (empty($duplicates)) {
            wp_send_json_success(['message' => 'Nessun duplicato trovato!', 'html' => '<p style=\"color:#10b981;\">✓ Nessun media duplicato</p>']);
            return;
        }

        $html = '<div class=\"ipv-duplicates-list\">';
        $html .= '<p><strong>Trovati ' . count($duplicates) . ' file duplicati:</strong></p>';
        foreach ($duplicates as $dup) {
            $html .= '<div class=\"ipv-duplicate-item\">';
            $html .= esc_html($dup->post_title) . ' <span class=\"badge\">' . $dup->count . ' copie</span>';
            $html .= '</div>';
        }
        $html .= '</div>';

        wp_send_json_success(['message' => count($duplicates) . ' duplicati trovati', 'html' => $html]);
    }

    public static function ajax_clean_orphan_media() {
        global $wpdb;

        // Find media not attached to any post
        $orphans = $wpdb->get_results("
            SELECT ID
            FROM {$wpdb->posts}
            WHERE post_type = 'attachment'
            AND post_parent = 0
        ");

        $deleted = 0;
        foreach ($orphans as $orphan) {
            if (wp_delete_attachment($orphan->ID, true)) {
                $deleted++;
            }
        }

        wp_send_json_success(['message' => "Media orfani eliminati! ({$deleted} file)"]);
    }

    public static function ajax_run_diagnostic() {
        $checks = [
            ['name' => 'WordPress Version', 'status' => version_compare(get_bloginfo('version'), '5.8', '>='), 'value' => get_bloginfo('version')],
            ['name' => 'PHP Version', 'status' => version_compare(PHP_VERSION, '7.4', '>='), 'value' => PHP_VERSION],
            ['name' => 'MySQL Version', 'status' => version_compare($GLOBALS['wpdb']->db_version(), '5.6', '>='), 'value' => $GLOBALS['wpdb']->db_version()],
            ['name' => 'YouTube API', 'status' => !empty(get_option('ipv_pro_youtube_api_key')), 'value' => !empty(get_option('ipv_pro_youtube_api_key')) ? 'Configurato' : 'Non configurato'],
            ['name' => 'OpenAI API', 'status' => !empty(get_option('ipv_pro_openai_api_key')), 'value' => !empty(get_option('ipv_pro_openai_api_key')) ? 'Configurato' : 'Non configurato'],
            ['name' => 'SupaData API', 'status' => !empty(get_option('ipv_pro_supadata_api_key')), 'value' => !empty(get_option('ipv_pro_supadata_api_key')) ? 'Configurato' : 'Opzionale'],
            ['name' => 'Write Permissions', 'status' => is_writable(WP_CONTENT_DIR), 'value' => is_writable(WP_CONTENT_DIR) ? 'OK' : 'Errore'],
            ['name' => 'Memory Limit', 'status' => intval(ini_get('memory_limit')) >= 256, 'value' => ini_get('memory_limit')],
            ['name' => 'Max Execution Time', 'status' => intval(ini_get('max_execution_time')) >= 60, 'value' => ini_get('max_execution_time') . 's'],
            ['name' => 'Cron System', 'status' => wp_next_scheduled('ipv_pro_process_queue') !== false, 'value' => wp_next_scheduled('ipv_pro_process_queue') ? 'Attivo' : 'Non attivo'],
        ];

        $html = '';
        foreach ($checks as $check) {
            $class = $check['status'] ? 'pass' : 'fail';
            $icon = $check['status'] ? '✓' : '✗';
            $html .= '<div class=\"ipv-diagnostic-item ' . $class . '\">';
            $html .= '<strong>' . esc_html($check['name']) . '</strong>';
            $html .= '<span>' . $icon . ' ' . esc_html($check['value']) . '</span>';
            $html .= '</div>';
        }

        wp_send_json_success(['html' => $html]);
    }

    public static function ajax_test_apis() {
        $results = [];

        // Test YouTube API
        $youtube_key = get_option('ipv_pro_youtube_api_key');
        if (!empty($youtube_key)) {
            $youtube_api = new IPV_YouTube_API();
            $test = $youtube_api->test_connection();
            $results[] = ['name' => 'YouTube API', 'status' => $test['success'], 'message' => $test['message']];
        } else {
            $results[] = ['name' => 'YouTube API', 'status' => false, 'message' => 'API key non configurata'];
        }

        // Test OpenAI API
        $openai_key = get_option('ipv_pro_openai_api_key');
        if (!empty($openai_key)) {
            $openai_api = new IPV_OpenAI_API();
            $test = $openai_api->test_connection();
            $results[] = ['name' => 'OpenAI API', 'status' => $test['success'], 'message' => $test['message']];
        } else {
            $results[] = ['name' => 'OpenAI API', 'status' => false, 'message' => 'API key non configurata'];
        }

        // Test SupaData API
        $supadata_key = get_option('ipv_pro_supadata_api_key');
        if (!empty($supadata_key)) {
            $supadata_api = new IPV_SupaData_API();
            $test = $supadata_api->test_connection();
            $results[] = ['name' => 'SupaData API', 'status' => $test['success'], 'message' => $test['message']];
        } else {
            $results[] = ['name' => 'SupaData API', 'status' => true, 'message' => 'Opzionale - non configurata'];
        }

        $html = '';
        foreach ($results as $result) {
            $class = $result['status'] ? 'pass' : 'fail';
            $icon = $result['status'] ? '✓' : '✗';
            $html .= '<div class=\"ipv-diagnostic-item ' . $class . '\">';
            $html .= '<strong>' . esc_html($result['name']) . '</strong>';
            $html .= '<span>' . $icon . ' ' . esc_html($result['message']) . '</span>';
            $html .= '</div>';
        }

        wp_send_json_success(['html' => $html]);
    }

    public static function ajax_refresh_logs() {
        ob_start();
        self::render_logs();
        $html = ob_get_clean();

        wp_send_json_success(['html' => $html]);
    }

    public static function ajax_clear_logs() {
        $log_file = WP_CONTENT_DIR . '/debug.log';

        if (file_exists($log_file)) {
            file_put_contents($log_file, '');
            wp_send_json_success(['message' => 'Log puliti con successo!']);
        } else {
            wp_send_json_error(['message' => 'File di log non trovato']);
        }
    }

    public static function ajax_download_logs() {
        $log_file = WP_CONTENT_DIR . '/debug.log';

        if (file_exists($log_file)) {
            $content = file_get_contents($log_file);
            wp_send_json_success(['content' => $content, 'filename' => 'ipv-debug-' . date('Y-m-d-His') . '.log']);
        } else {
            wp_send_json_error(['message' => 'File di log non trovato']);
        }
    }

    public static function ajax_generate_csv() {
        global $wpdb;

        $videos = $wpdb->get_results("
            SELECT p.ID, p.post_title, p.post_status, p.post_date, p.post_modified
            FROM {$wpdb->posts} p
            WHERE p.post_type = 'ipv_video'
            ORDER BY p.post_date DESC
        ");

        $csv = "ID,Titolo,Status,Data Creazione,Ultima Modifica,Views,Durata,Trascrizione,AI\n";

        foreach ($videos as $video) {
            $views = get_post_meta($video->ID, '_ipv_view_count', true);
            $duration = get_post_meta($video->ID, '_ipv_duration', true);
            $has_transcript = get_post_meta($video->ID, '_ipv_transcript', true) ? 'Si' : 'No';
            $has_ai = get_post_meta($video->ID, '_ipv_ai_content', true) ? 'Si' : 'No';

            $csv .= sprintf(
                "%d,\"%s\",%s,%s,%s,%d,%s,%s,%s\n",
                $video->ID,
                str_replace('"', '""', $video->post_title),
                $video->post_status,
                $video->post_date,
                $video->post_modified,
                intval($views),
                $duration,
                $has_transcript,
                $has_ai
            );
        }

        wp_send_json_success([
            'content' => $csv,
            'filename' => 'ipv-videos-export-' . date('Y-m-d-His') . '.csv'
        ]);
    }

    public static function ajax_bulk_delete_drafts() {
        global $wpdb;

        $count = $wpdb->query("
            DELETE FROM {$wpdb->posts}
            WHERE post_type = 'ipv_video'
            AND post_status = 'draft'
        ");

        wp_send_json_success(['message' => "Bozze eliminate! ({$count} video)"]);
    }

    public static function ajax_bulk_publish_all() {
        global $wpdb;

        $count = $wpdb->query("
            UPDATE {$wpdb->posts}
            SET post_status = 'publish'
            WHERE post_type = 'ipv_video'
            AND post_status = 'draft'
        ");

        wp_send_json_success(['message' => "Video pubblicati! ({$count} video)"]);
    }

    public static function ajax_bulk_update_dates() {
        global $wpdb;

        $count = $wpdb->query("
            UPDATE {$wpdb->posts}
            SET post_modified = NOW(), post_modified_gmt = UTC_TIMESTAMP()
            WHERE post_type = 'ipv_video'
        ");

        wp_send_json_success(['message' => "Date aggiornate! ({$count} video)"]);
    }

    /**
     * Check for orphan videos (posts not in queue)
     */
    public static function ajax_check_orphan_videos() {
        global $wpdb;

        $queue_table = $wpdb->prefix . 'ipv_processing_queue';

        // Find posts that are NOT in the queue
        $orphan_posts = $wpdb->get_results("
            SELECT p.ID, p.post_title, p.post_status, p.post_date
            FROM {$wpdb->posts} p
            LEFT JOIN {$queue_table} q ON p.ID = q.post_id
            WHERE p.post_type = 'ipv_video'
            AND q.id IS NULL
            ORDER BY p.post_date DESC
            LIMIT 50
        ");

        if (empty($orphan_posts)) {
            wp_send_json_success([
                'message' => '✅ Nessun video orfano trovato! Tutti i video sono nella queue.',
                'html' => ''
            ]);
            return;
        }

        $html = '<div class="ipv-orphan-videos-list">';
        $html .= '<h3>📹 Video Orfani Trovati (' . count($orphan_posts) . ')</h3>';
        $html .= '<table class="wp-list-table widefat fixed striped">';
        $html .= '<thead><tr>';
        $html .= '<th>ID</th>';
        $html .= '<th>Titolo</th>';
        $html .= '<th>Stato</th>';
        $html .= '<th>Data</th>';
        $html .= '<th>Azione</th>';
        $html .= '</tr></thead><tbody>';

        foreach ($orphan_posts as $post) {
            $status_label = $post->post_status === 'publish' ? '✅ Pubblicato' : '📝 Bozza';
            $edit_link = get_edit_post_link($post->ID);

            $html .= '<tr>';
            $html .= '<td>' . $post->ID . '</td>';
            $html .= '<td><strong>' . esc_html($post->post_title) . '</strong></td>';
            $html .= '<td>' . $status_label . '</td>';
            $html .= '<td>' . date('d/m/Y H:i', strtotime($post->post_date)) . '</td>';
            $html .= '<td><a href="' . $edit_link . '" class="button button-small" target="_blank">Modifica</a></td>';
            $html .= '</tr>';
        }

        $html .= '</tbody></table>';
        $html .= '</div>';

        wp_send_json_success([
            'message' => '⚠️ Trovati ' . count($orphan_posts) . ' video orfani! Questi video esistono come post ma non sono nella queue.',
            'html' => $html,
            'count' => count($orphan_posts)
        ]);
    }

    /**
     * Delete orphan videos permanently
     */
    public static function ajax_delete_orphan_videos() {
        global $wpdb;

        $queue_table = $wpdb->prefix . 'ipv_processing_queue';

        // Find orphan posts
        $orphan_ids = $wpdb->get_col("
            SELECT p.ID
            FROM {$wpdb->posts} p
            LEFT JOIN {$queue_table} q ON p.ID = q.post_id
            WHERE p.post_type = 'ipv_video'
            AND q.id IS NULL
        ");

        if (empty($orphan_ids)) {
            wp_send_json_success(['message' => 'Nessun video orfano da eliminare.']);
            return;
        }

        $deleted = 0;
        foreach ($orphan_ids as $post_id) {
            if (wp_delete_post($post_id, true)) { // true = force delete (bypass trash)
                $deleted++;
            }
        }

        wp_send_json_success([
            'message' => "✅ Video orfani eliminati! ({$deleted} video rimossi definitivamente)",
            'deleted' => $deleted
        ]);
    }

    /**
     * Repair orphan videos by re-adding them to queue
     */
    public static function ajax_repair_orphan_videos() {
        global $wpdb;

        $queue_table = $wpdb->prefix . 'ipv_processing_queue';

        // Find orphan posts
        $orphan_posts = $wpdb->get_results("
            SELECT p.ID, p.post_title
            FROM {$wpdb->posts} p
            LEFT JOIN {$queue_table} q ON p.ID = q.post_id
            WHERE p.post_type = 'ipv_video'
            AND q.id IS NULL
        ");

        if (empty($orphan_posts)) {
            wp_send_json_success(['message' => 'Nessun video orfano da riparare.']);
            return;
        }

        $repaired = 0;
        foreach ($orphan_posts as $post) {
            // Get video URL from post meta
            $video_url = get_post_meta($post->ID, '_ipv_video_url', true);

            if (empty($video_url)) {
                // Try to get video_id and reconstruct URL
                $video_id = get_post_meta($post->ID, '_ipv_video_id', true);
                if (!empty($video_id)) {
                    $video_url = "https://www.youtube.com/watch?v={$video_id}";
                }
            }

            if (empty($video_url)) {
                continue; // Skip if no video URL
            }

            // Re-add to queue as completed (to make it visible)
            $result = $wpdb->insert(
                $queue_table,
                [
                    'post_id' => $post->ID,
                    'video_url' => $video_url,
                    'status' => 'completed',
                    'current_step' => 'repair_restored',
                    'retry_count' => 0,
                    'created_at' => current_time('mysql'),
                    'updated_at' => current_time('mysql')
                ],
                ['%d', '%s', '%s', '%s', '%d', '%s', '%s']
            );

            if ($result !== false) {
                $repaired++;
            }
        }

        wp_send_json_success([
            'message' => "✅ Video riparati e riassociati alla queue! ({$repaired} video ora visibili nel Video Manager)",
            'repaired' => $repaired
        ]);
    }

    /**
     * Deep Clean Database - ELIMINATES ALL VIDEO DATA FROM ALL TABLES
     * WARNING: This is IRREVERSIBLE and DESTRUCTIVE!
     */
    public static function ajax_deep_clean_database() {
        global $wpdb;

        // Security check
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Non autorizzato!']);
            return;
        }

        $queue_table = $wpdb->prefix . 'ipv_processing_queue';

        // Count data before deletion (for reporting)
        $count_posts = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'ipv_video'");
        $count_meta = $wpdb->get_var("
            SELECT COUNT(*) FROM {$wpdb->postmeta} pm
            INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
            WHERE p.post_type = 'ipv_video'
        ");
        $count_queue = $wpdb->get_var("SELECT COUNT(*) FROM {$queue_table}");
        $count_relations = $wpdb->get_var("
            SELECT COUNT(*) FROM {$wpdb->term_relationships} tr
            INNER JOIN {$wpdb->posts} p ON tr.object_id = p.ID
            WHERE p.post_type = 'ipv_video'
        ");

        // Start transaction for safety
        $wpdb->query('START TRANSACTION');

        try {
            // STEP 1: Delete all ipv_video posts (this will also trigger wp_delete_post hooks)
            $post_ids = $wpdb->get_col("SELECT ID FROM {$wpdb->posts} WHERE post_type = 'ipv_video'");

            $deleted_posts = 0;
            foreach ($post_ids as $post_id) {
                // wp_delete_post with true = force delete, bypass trash
                if (wp_delete_post($post_id, true)) {
                    $deleted_posts++;
                }
            }

            // STEP 2: Delete any remaining postmeta (cleanup stragglers)
            $wpdb->query("
                DELETE pm FROM {$wpdb->postmeta} pm
                LEFT JOIN {$wpdb->posts} p ON pm.post_id = p.ID
                WHERE p.ID IS NULL
            ");

            // STEP 3: Delete all queue records
            $deleted_queue = $wpdb->query("TRUNCATE TABLE {$queue_table}");

            // STEP 4: Delete orphan term relationships (cleanup any left from posts)
            $wpdb->query("
                DELETE tr FROM {$wpdb->term_relationships} tr
                LEFT JOIN {$wpdb->posts} p ON tr.object_id = p.ID
                WHERE p.ID IS NULL
            ");

            // STEP 5: Clean up orphan terms in custom taxonomies (optional but recommended)
            $taxonomies = ['ipv_topic', 'ipv_guest', 'ipv_channel_theme'];
            foreach ($taxonomies as $taxonomy) {
                // Delete terms that have no posts associated
                $wpdb->query("
                    DELETE t FROM {$wpdb->terms} t
                    INNER JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id
                    LEFT JOIN {$wpdb->term_relationships} tr ON tt.term_taxonomy_id = tr.term_taxonomy_id
                    WHERE tt.taxonomy = '{$taxonomy}'
                    AND tr.object_id IS NULL
                ");
            }

            // STEP 6: Optimize tables after mass deletion
            $wpdb->query("OPTIMIZE TABLE {$wpdb->posts}");
            $wpdb->query("OPTIMIZE TABLE {$wpdb->postmeta}");
            $wpdb->query("OPTIMIZE TABLE {$queue_table}");
            $wpdb->query("OPTIMIZE TABLE {$wpdb->term_relationships}");

            // Commit transaction
            $wpdb->query('COMMIT');

            // Success message with detailed report
            wp_send_json_success([
                'message' => sprintf(
                    "✅ DATABASE COMPLETAMENTE PULITO!\n\n" .
                    "📊 Record eliminati:\n" .
                    "• Video Posts: %d\n" .
                    "• Post Meta: %d\n" .
                    "• Queue Records: %d\n" .
                    "• Taxonomy Relations: %d\n\n" .
                    "TOTALE: %d record eliminati\n\n" .
                    "Il database è ora completamente pulito e pronto per nuovi import!",
                    $deleted_posts,
                    $count_meta,
                    $count_queue,
                    $count_relations,
                    $deleted_posts + $count_meta + $count_queue + $count_relations
                ),
                'deleted' => [
                    'posts' => $deleted_posts,
                    'meta' => $count_meta,
                    'queue' => $count_queue,
                    'relations' => $count_relations,
                    'total' => $deleted_posts + $count_meta + $count_queue + $count_relations
                ]
            ]);

        } catch (Exception $e) {
            // Rollback on error
            $wpdb->query('ROLLBACK');

            wp_send_json_error([
                'message' => '❌ Errore durante la pulizia del database: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * =====================================================
     * HELPER FUNCTIONS
     * =====================================================
     */

    private static function get_cache_size() {
        global $wpdb;

        $transients = $wpdb->get_results("
            SELECT option_name, LENGTH(option_value) as size
            FROM {$wpdb->options}
            WHERE option_name LIKE '_transient_%'
        ");

        $total_size = 0;
        foreach ($transients as $t) {
            $total_size += $t->size;
        }

        return [
            'count' => count($transients),
            'size' => $total_size
        ];
    }

    private static function get_db_info() {
        global $wpdb;

        $posts = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'ipv_video'");

        $meta = $wpdb->get_var("
            SELECT COUNT(*)
            FROM {$wpdb->postmeta} pm
            INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
            WHERE p.post_type = 'ipv_video'
        ");

        $orphans = $wpdb->get_var("
            SELECT COUNT(*)
            FROM {$wpdb->postmeta} pm
            LEFT JOIN {$wpdb->posts} p ON pm.post_id = p.ID
            WHERE p.ID IS NULL
        ");

        $size_query = $wpdb->get_results("SHOW TABLE STATUS LIKE '{$wpdb->posts}'");
        $size = isset($size_query[0]->Data_length) ? $size_query[0]->Data_length : 0;

        return [
            'posts' => number_format($posts),
            'meta' => number_format($meta),
            'orphans' => number_format($orphans),
            'size' => self::format_bytes($size)
        ];
    }

    private static function get_media_info() {
        global $wpdb;

        $total = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'attachment'");
        $orphans = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'attachment' AND post_parent = 0");

        $upload_dir = wp_upload_dir();
        $size = 0;

        if (is_dir($upload_dir['basedir'])) {
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($upload_dir['basedir'], RecursiveDirectoryIterator::SKIP_DOTS)
            );

            foreach ($files as $file) {
                if ($file->isFile()) {
                    $size += $file->getSize();
                }
            }
        }

        return [
            'total' => number_format($total),
            'orphans' => number_format($orphans),
            'size' => self::format_bytes($size)
        ];
    }

    private static function get_orphan_videos_info() {
        global $wpdb;

        // Get total ipv_video posts
        $total_posts = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'ipv_video'");

        // Get posts in queue
        $queue_table = $wpdb->prefix . 'ipv_processing_queue';
        $in_queue = $wpdb->get_var("SELECT COUNT(DISTINCT post_id) FROM {$queue_table}");

        // Orphan videos = posts without queue entry
        $orphans = $total_posts - $in_queue;

        return [
            'total_posts' => (int)$total_posts,
            'in_queue' => (int)$in_queue,
            'orphans' => max(0, (int)$orphans) // Ensure no negative
        ];
    }

    private static function render_logs() {
        $log_file = WP_CONTENT_DIR . '/debug.log';

        if (!file_exists($log_file)) {
            echo '<div class=\"log-line\">Nessun log disponibile. Abilita WP_DEBUG_LOG in wp-config.php</div>';
            return;
        }

        $logs = file($log_file);
        $logs = array_slice($logs, -100); // Last 100 lines

        foreach ($logs as $log) {
            $class = '';
            if (strpos($log, 'ERROR') !== false || strpos($log, '❌') !== false || strpos($log, 'Fatal') !== false) {
                $class = 'log-error';
            } elseif (strpos($log, '✅') !== false || strpos($log, 'SUCCESS') !== false) {
                $class = 'log-success';
            } elseif (strpos($log, '⚠') !== false || strpos($log, 'WARNING') !== false) {
                $class = 'log-warning';
            }

            echo '<div class=\"log-line ' . $class . '\">' . esc_html($log) . '</div>';
        }
    }

    private static function render_system_info() {
        global $wpdb;

        $info = [
            'WordPress' => [
                'Version' => get_bloginfo('version'),
                'Home URL' => get_home_url(),
                'Site URL' => get_site_url(),
                'Admin URL' => admin_url(),
                'Multisite' => is_multisite() ? 'Yes' : 'No',
                'Language' => get_locale(),
            ],
            'Server' => [
                'PHP Version' => PHP_VERSION,
                'MySQL Version' => $wpdb->db_version(),
                'Server Software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
                'Memory Limit' => ini_get('memory_limit'),
                'Max Execution Time' => ini_get('max_execution_time') . 's',
                'Upload Max Size' => ini_get('upload_max_filesize'),
                'Post Max Size' => ini_get('post_max_size'),
                'Max Input Vars' => ini_get('max_input_vars'),
            ],
            'Plugin' => [
                'Version' => IPV_PRO_VERSION,
                'Total Videos' => wp_count_posts('ipv_video')->publish,
                'Install Path' => IPV_PRO_PLUGIN_DIR,
                'Install URL' => IPV_PRO_PLUGIN_URL,
            ],
        ];

        echo '<div class=\"ipv-system-info-grid\">';
        foreach ($info as $section => $items) {
            echo '<div class=\"ipv-system-info-section\">';
            echo '<h3>' . esc_html($section) . '</h3>';
            foreach ($items as $key => $value) {
                echo '<div class=\"ipv-system-info-item\">';
                echo '<span>' . esc_html($key) . '</span>';
                echo '<strong>' . esc_html($value) . '</strong>';
                echo '</div>';
            }
            echo '</div>';
        }
        echo '</div>';
    }

    private static function format_bytes($bytes) {
        if ($bytes < 1024) return $bytes . ' B';
        if ($bytes < 1024 * 1024) return round($bytes / 1024, 1) . ' KB';
        if ($bytes < 1024 * 1024 * 1024) return round($bytes / (1024 * 1024), 1) . ' MB';
        return round($bytes / (1024 * 1024 * 1024), 1) . ' GB';
    }

    /**
     * Inline CSS
     */
    private static function get_inline_css() {
        return <<<CSS
        <style>
        .ipv-tools-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }

        .ipv-tool-wide {
            grid-column: 1 / -1;
        }

        .ipv-tool-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            overflow: hidden;
        }

        .ipv-tool-header {
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .ipv-tool-header .dashicons {
            font-size: 24px;
            width: 24px;
            height: 24px;
        }

        .ipv-tool-header h2 {
            margin: 0;
            font-size: 18px;
            color: white;
        }

        .ipv-tool-body {
            padding: 20px;
        }

        .ipv-tool-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 16px;
        }

        .ipv-tool-status {
            margin-top: 16px;
            padding: 12px;
            border-radius: 4px;
            display: none;
        }

        .ipv-tool-status.show {
            display: block;
        }

        .ipv-tool-status.success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #059669;
        }

        .ipv-tool-status.error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #dc2626;
        }

        .ipv-tool-status.info {
            background: #dbeafe;
            color: #1e40af;
            border: 1px solid #3b82f6;
        }

        .ipv-cache-info, .ipv-db-info {
            background: #f9fafb;
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 12px;
        }

        .ipv-db-info div {
            margin: 4px 0;
        }

        .ipv-log-viewer {
            background: #1e1e1e;
            color: #d4d4d4;
            padding: 16px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            max-height: 500px;
            overflow-y: auto;
            margin-top: 12px;
        }

        .ipv-log-viewer .log-line {
            margin: 4px 0;
            padding: 4px;
            border-left: 3px solid transparent;
        }

        .ipv-log-viewer .log-error {
            border-left-color: #ef4444;
            background: rgba(239, 68, 68, 0.1);
        }

        .ipv-log-viewer .log-success {
            border-left-color: #10b981;
            background: rgba(16, 185, 129, 0.1);
        }

        .ipv-log-viewer .log-warning {
            border-left-color: #f59e0b;
            background: rgba(245, 158, 11, 0.1);
        }

        .ipv-diagnostic-results, .ipv-media-results {
            margin-top: 16px;
            display: none;
        }

        .ipv-diagnostic-results.show, .ipv-media-results.show {
            display: block;
        }

        .ipv-diagnostic-item {
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .ipv-diagnostic-item.pass {
            background: #d1fae5;
            border-left: 4px solid #10b981;
        }

        .ipv-diagnostic-item.fail {
            background: #fee2e2;
            border-left: 4px solid #ef4444;
        }

        .ipv-report-options {
            display: flex;
            flex-direction: column;
            gap: 8px;
            margin: 16px 0;
        }

        .ipv-system-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 16px;
        }

        .ipv-system-info-section {
            background: #f9fafb;
            padding: 16px;
            border-radius: 4px;
        }

        .ipv-system-info-section h3 {
            margin: 0 0 12px 0;
            font-size: 14px;
            font-weight: 600;
            color: #1f2937;
        }

        .ipv-system-info-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #e5e7eb;
            font-size: 13px;
        }

        .ipv-system-info-item:last-child {
            border-bottom: none;
        }

        .ipv-shortcode-options {
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin: 16px 0;
        }

        .ipv-generated-shortcode {
            background: #f9fafb;
            padding: 16px;
            border-radius: 4px;
            border: 2px dashed #d1d5db;
            margin: 16px 0;
        }

        .ipv-generated-shortcode code {
            font-size: 14px;
            color: #667eea;
            font-weight: 600;
        }

        .ipv-duplicates-list {
            margin-top: 12px;
            padding: 12px;
            background: #f9fafb;
            border-radius: 4px;
        }

        .ipv-duplicate-item {
            padding: 8px;
            margin: 4px 0;
            background: white;
            border-radius: 4px;
            display: flex;
            justify-content: space-between;
        }

        .ipv-duplicate-item .badge {
            background: #ef4444;
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 11px;
        }
        </style>
CSS;
    }

    /**
     * Inline JavaScript
     */
    private static function get_inline_js() {
        return <<<JS
        <script>
        jQuery(document).ready(function($) {
            // Helper function to show status messages
            function showStatus(selector, message, type) {
                $(selector).removeClass('success error info').addClass(type + ' show').text(message);
                setTimeout(function() {
                    $(selector).removeClass('show');
                }, 5000);
            }

            // Export Config
            $('#ipv-export-config').on('click', function() {
                $.post(ajaxurl, {action: 'ipv_export_config'}, function(res) {
                    if (res.success) {
                        const blob = new Blob([JSON.stringify(res.data, null, 2)], {type: 'application/json'});
                        const url = URL.createObjectURL(blob);
                        const a = document.createElement('a');
                        a.href = url;
                        a.download = 'ipv-config-' + Date.now() + '.json';
                        a.click();
                        showStatus('#export-import-status', 'Configurazione esportata!', 'success');
                    }
                });
            });

            // Import Config
            $('#ipv-import-file').on('change', function(e) {
                const file = e.target.files[0];
                if (!file) return;

                const reader = new FileReader();
                reader.onload = function(e) {
                    try {
                        const config = JSON.parse(e.target.result);
                        $.post(ajaxurl, {
                            action: 'ipv_import_config',
                            config: JSON.stringify(config)
                        }, function(res) {
                            showStatus('#export-import-status', res.data.message, res.success ? 'success' : 'error');
                            if (res.success) {
                                setTimeout(function() { location.reload(); }, 2000);
                            }
                        });
                    } catch (err) {
                        showStatus('#export-import-status', 'File JSON non valido', 'error');
                    }
                };
                reader.readAsText(file);
            });

            // Clear Cache
            $('#ipv-clear-cache-all, #ipv-clear-cache-ipv').on('click', function() {
                const type = $(this).attr('id').replace('ipv-clear-cache-', '');
                if (!confirm('Sei sicuro di voler pulire la cache?')) return;

                $.post(ajaxurl, {action: 'ipv_clear_cache', type: type}, function(res) {
                    showStatus('#cache-status', res.data.message, res.success ? 'success' : 'error');
                    if (res.success) {
                        setTimeout(function() { location.reload(); }, 2000);
                    }
                });
            });

            // Optimize DB
            $('#ipv-optimize-db').on('click', function() {
                $.post(ajaxurl, {action: 'ipv_optimize_db'}, function(res) {
                    showStatus('#db-status', res.data.message, res.success ? 'success' : 'error');
                });
            });

            // Clean Orphans
            $('#ipv-clean-orphans').on('click', function() {
                if (!confirm('Sei sicuro di voler eliminare i meta orfani?')) return;

                $.post(ajaxurl, {action: 'ipv_clean_orphans'}, function(res) {
                    showStatus('#db-status', res.data.message, res.success ? 'success' : 'error');
                    if (res.success) {
                        setTimeout(function() { location.reload(); }, 2000);
                    }
                });
            });

            // Check Media Duplicates
            $('#ipv-check-media-duplicates').on('click', function() {
                showStatus('#media-status', 'Ricerca in corso...', 'info');

                $.post(ajaxurl, {action: 'ipv_check_media_duplicates'}, function(res) {
                    showStatus('#media-status', res.data.message, res.success ? 'success' : 'error');
                    if (res.data.html) {
                        $('#media-results').addClass('show').html(res.data.html);
                    }
                });
            });

            // Clean Orphan Media
            $('#ipv-clean-orphan-media').on('click', function() {
                if (!confirm('Sei sicuro di voler eliminare i media orfani? Questa azione è irreversibile!')) return;

                showStatus('#media-status', 'Pulizia in corso...', 'info');

                $.post(ajaxurl, {action: 'ipv_clean_orphan_media'}, function(res) {
                    showStatus('#media-status', res.data.message, res.success ? 'success' : 'error');
                    if (res.success) {
                        setTimeout(function() { location.reload(); }, 2000);
                    }
                });
            });

            // Run Diagnostic
            $('#ipv-run-diagnostic').on('click', function() {
                $.post(ajaxurl, {action: 'ipv_run_diagnostic'}, function(res) {
                    if (res.success) {
                        $('#diagnostic-results').addClass('show').html(res.data.html);
                    }
                });
            });

            // Test APIs
            $('#ipv-test-apis').on('click', function() {
                $.post(ajaxurl, {action: 'ipv_test_apis'}, function(res) {
                    if (res.success) {
                        $('#diagnostic-results').addClass('show').html(res.data.html);
                    }
                });
            });

            // Refresh Logs
            $('#ipv-refresh-logs').on('click', function() {
                $.post(ajaxurl, {action: 'ipv_refresh_logs'}, function(res) {
                    if (res.success) {
                        $('#ipv-log-viewer').html(res.data.html);
                    }
                });
            });

            // Clear Logs
            $('#ipv-clear-logs').on('click', function() {
                if (!confirm('Sei sicuro di voler pulire i log?')) return;

                $.post(ajaxurl, {action: 'ipv_clear_logs'}, function(res) {
                    if (res.success) {
                        $('#ipv-log-viewer').html('<div class="log-line">Log puliti</div>');
                    }
                });
            });

            // Download Logs
            $('#ipv-download-logs').on('click', function() {
                $.post(ajaxurl, {action: 'ipv_download_logs'}, function(res) {
                    if (res.success) {
                        const blob = new Blob([res.data.content], {type: 'text/plain'});
                        const url = URL.createObjectURL(blob);
                        const a = document.createElement('a');
                        a.href = url;
                        a.download = res.data.filename;
                        a.click();
                    }
                });
            });

            // Generate CSV
            $('#ipv-generate-csv').on('click', function() {
                showStatus('#report-status', 'Generazione in corso...', 'info');

                $.post(ajaxurl, {action: 'ipv_generate_csv'}, function(res) {
                    if (res.success) {
                        const blob = new Blob([res.data.content], {type: 'text/csv'});
                        const url = URL.createObjectURL(blob);
                        const a = document.createElement('a');
                        a.href = url;
                        a.download = res.data.filename;
                        a.click();
                        showStatus('#report-status', 'Report CSV generato!', 'success');
                    }
                });
            });

            // Bulk Delete Drafts
            $('#ipv-bulk-delete-drafts').on('click', function() {
                if (!confirm('Sei sicuro di voler eliminare TUTTE le bozze? Questa azione è irreversibile!')) return;

                $.post(ajaxurl, {action: 'ipv_bulk_delete_drafts'}, function(res) {
                    showStatus('#bulk-status', res.data.message, res.success ? 'success' : 'error');
                });
            });

            // Bulk Publish All
            $('#ipv-bulk-publish-all').on('click', function() {
                if (!confirm('Sei sicuro di voler pubblicare TUTTI i video in bozza?')) return;

                $.post(ajaxurl, {action: 'ipv_bulk_publish_all'}, function(res) {
                    showStatus('#bulk-status', res.data.message, res.success ? 'success' : 'error');
                });
            });

            // Bulk Update Dates
            $('#ipv-bulk-update-dates').on('click', function() {
                if (!confirm('Sei sicuro di voler aggiornare le date di modifica di tutti i video?')) return;

                $.post(ajaxurl, {action: 'ipv_bulk_update_dates'}, function(res) {
                    showStatus('#bulk-status', res.data.message, res.success ? 'success' : 'error');
                });
            });

            // Shortcode Generator
            function updateShortcode() {
                const perpage = $('#sc-perpage').val();
                const published = $('#sc-published-only').is(':checked') ? ' status="publish"' : '';
                const sc = `[ipv_videos count="${perpage}"${published}]`;
                $('#generated-shortcode').text(sc);
            }

            $('#sc-perpage, #sc-published-only').on('change', updateShortcode);

            $('#ipv-copy-shortcode').on('click', function() {
                const sc = $('#generated-shortcode').text();
                navigator.clipboard.writeText(sc);
                $(this).text('✓ Copiato!');
                setTimeout(() => $(this).html('<span class="dashicons dashicons-admin-page"></span> Copia Shortcode'), 2000);
            });

            // Check Orphan Videos
            $('#ipv-check-orphan-videos').on('click', function() {
                showStatus('#orphan-videos-status', 'Ricerca video orfani in corso...', 'info');
                $('#orphan-videos-results').html('');

                $.post(ajaxurl, {action: 'ipv_check_orphan_videos'}, function(res) {
                    showStatus('#orphan-videos-status', res.data.message, res.success ? (res.data.count > 0 ? 'error' : 'success') : 'error');
                    if (res.data.html) {
                        $('#orphan-videos-results').addClass('show').html(res.data.html);
                    }
                    if (res.data.count > 0) {
                        $('#ipv-repair-orphan-videos, #ipv-delete-orphan-videos').prop('disabled', false);
                    }
                });
            });

            // Repair Orphan Videos
            $('#ipv-repair-orphan-videos').on('click', function() {
                if (!confirm('Vuoi riassociare i video orfani alla queue? Diventeranno visibili nel Video Manager.')) return;

                showStatus('#orphan-videos-status', 'Riparazione in corso...', 'info');

                $.post(ajaxurl, {action: 'ipv_repair_orphan_videos'}, function(res) {
                    showStatus('#orphan-videos-status', res.data.message, res.success ? 'success' : 'error');
                    if (res.success) {
                        setTimeout(function() { location.reload(); }, 2000);
                    }
                });
            });

            // Delete Orphan Videos
            $('#ipv-delete-orphan-videos').on('click', function() {
                if (!confirm('⚠️ ATTENZIONE! Sei sicuro di voler ELIMINARE DEFINITIVAMENTE tutti i video orfani?\n\nQuesta azione è IRREVERSIBILE e rimuoverà i post dal database!\n\nConfermi?')) return;

                showStatus('#orphan-videos-status', 'Eliminazione in corso...', 'info');

                $.post(ajaxurl, {action: 'ipv_delete_orphan_videos'}, function(res) {
                    showStatus('#orphan-videos-status', res.data.message, res.success ? 'success' : 'error');
                    if (res.success) {
                        setTimeout(function() { location.reload(); }, 2000);
                    }
                });
            });

            // Deep Clean Database (DANGER ZONE!)
            $('#ipv-deep-clean-database').on('click', function() {
                // Triple confirmation with clear warnings
                if (!confirm('🚨 ZONA PERICOLOSA! 🚨\n\nStai per ELIMINARE DEFINITIVAMENTE:\n• Tutti i post ipv_video\n• Tutti i metadati\n• Tutta la coda processing\n• Tutte le relazioni taxonomie\n\nQuesta azione è IRREVERSIBILE!\n\nSei ASSOLUTAMENTE SICURO?\n\n(Clicca Annulla per fermarti ora!)')) {
                    return;
                }

                // Second confirmation
                if (!confirm('⚠️ SECONDA CONFERMA ⚠️\n\nHai capito che:\n\n1. TUTTO verrà eliminato dal database\n2. NON potrai recuperare i dati\n3. Dovrai reimportare tutti i video da zero\n\nVuoi davvero continuare?')) {
                    return;
                }

                // Third confirmation - type confirmation
                var typedConfirm = prompt('🛑 ULTIMA CONFERMA 🛑\n\nPer procedere con la PULIZIA TOTALE DEL DATABASE,\nscrivi esattamente questa parola:\n\nELIMINA\n\n(Maiuscolo, senza errori)');

                if (typedConfirm !== 'ELIMINA') {
                    alert('❌ Operazione annullata!\n\nLa parola digitata non corrisponde.\nIl database NON è stato modificato.');
                    return;
                }

                // All confirmations passed - proceed with deep clean
                showStatus('#deep-clean-status', '⚠️ PULIZIA PROFONDA IN CORSO... Attendere, non chiudere la pagina!', 'info');
                $(this).prop('disabled', true).text('🔄 Eliminazione in corso...');

                $.post(ajaxurl, {action: 'ipv_deep_clean_database'}, function(res) {
                    if (res.success) {
                        showStatus('#deep-clean-status', res.data.message, 'success');
                        alert('✅ DATABASE COMPLETAMENTE PULITO!\n\n' + res.data.message + '\n\nLa pagina si ricaricherà tra 3 secondi...');
                        setTimeout(function() { location.reload(); }, 3000);
                    } else {
                        showStatus('#deep-clean-status', res.data.message, 'error');
                        $('#ipv-deep-clean-database').prop('disabled', false).html('<span class="dashicons dashicons-trash"></span> ELIMINA TUTTO IL DATABASE VIDEO');
                        alert('❌ ERRORE!\n\n' + res.data.message);
                    }
                });
            });
        });
        </script>
JS;
    }
}
