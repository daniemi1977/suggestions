<?php
/**
 * Settings Page
 *
 * Advanced settings with API testing and category mapping
 */

if (!defined('ABSPATH')) {
    exit;
}

class IPV_Settings {

    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_init', [$this, 'register_settings']);
    }

    /**
     * Register settings
     */
    public function register_settings() {
        // API Settings
        register_setting('ipv_pro_settings', 'ipv_pro_youtube_api_key');
        register_setting('ipv_pro_settings', 'ipv_pro_supadata_api_key');
        register_setting('ipv_pro_settings', 'ipv_pro_openai_api_key');
        register_setting('ipv_pro_settings', 'ipv_pro_openai_model');

        // Transcription Settings
        register_setting('ipv_pro_settings', 'ipv_pro_transcript_mode');
        register_setting('ipv_pro_settings', 'ipv_pro_transcript_timeout');
        register_setting('ipv_pro_settings', 'ipv_pro_max_retry');

        // Category Mapping
        register_setting('ipv_pro_settings', 'ipv_pro_category_mapping');
        register_setting('ipv_pro_settings', 'ipv_pro_default_category');

        // Import Settings
        register_setting('ipv_pro_settings', 'ipv_pro_auto_publish');
        register_setting('ipv_pro_settings', 'ipv_pro_batch_size');

        // RSS Auto-Import Settings
        register_setting('ipv_pro_settings', 'ipv_pro_rss_feed_url');
        register_setting('ipv_pro_settings', 'ipv_pro_auto_import_enabled');
        register_setting('ipv_pro_settings', 'ipv_pro_auto_import_interval');
        register_setting('ipv_pro_settings', 'ipv_pro_auto_import_max_videos');
        register_setting('ipv_pro_settings', 'ipv_pro_auto_import_email_notifications');
        register_setting('ipv_pro_settings', 'ipv_pro_auto_import_notification_email');
    }

    /**
     * Render settings page
     */
    public function render_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        // Handle form submission
        if (isset($_POST['ipv_pro_save_settings'])) {
            check_admin_referer('ipv_pro_settings');
            $this->save_settings();
            echo '<div class="notice notice-success"><p>Impostazioni salvate con successo!</p></div>';
        }

        ?>
        <div class="wrap ipv-pro-settings">
            <h1>⚙️ Impostazioni IPV Production Pro</h1>

            <form method="post" action="">
                <?php wp_nonce_field('ipv_pro_settings'); ?>

                <!-- API Settings -->
                <div class="ipv-settings-section">
                    <h2>🔑 API Keys</h2>
                    <p>Configura le chiavi API per YouTube, SupaData e OpenAI.</p>

                    <table class="form-table">
                        <!-- YouTube API -->
                        <tr>
                            <th scope="row">
                                <label for="youtube_api_key">YouTube Data API v3</label>
                            </th>
                            <td>
                                <input type="text"
                                       id="youtube_api_key"
                                       name="ipv_pro_youtube_api_key"
                                       value="<?php echo esc_attr(get_option('ipv_pro_youtube_api_key')); ?>"
                                       class="regular-text"
                                       placeholder="AIza...">
                                <button type="button" class="button button-secondary ipv-test-api" data-api="youtube">
                                    Test Connessione
                                </button>
                                <div class="ipv-api-test-result" data-api="youtube"></div>
                                <p class="description">
                                    Ottieni la tua API key da <a href="https://console.cloud.google.com/apis/credentials" target="_blank">Google Cloud Console</a>
                                </p>
                            </td>
                        </tr>

                        <!-- SupaData API -->
                        <tr>
                            <th scope="row">
                                <label for="supadata_api_key">SupaData Transcription API</label>
                            </th>
                            <td>
                                <input type="text"
                                       id="supadata_api_key"
                                       name="ipv_pro_supadata_api_key"
                                       value="<?php echo esc_attr(get_option('ipv_pro_supadata_api_key')); ?>"
                                       class="regular-text"
                                       placeholder="sk_...">
                                <button type="button" class="button button-secondary ipv-test-api" data-api="supadata">
                                    Test Connessione
                                </button>
                                <div class="ipv-api-test-result" data-api="supadata"></div>
                                <p class="description">
                                    Ottieni la tua API key da <a href="https://supadata.ai" target="_blank">SupaData</a>
                                </p>
                            </td>
                        </tr>

                        <!-- OpenAI API -->
                        <tr>
                            <th scope="row">
                                <label for="openai_api_key">OpenAI API</label>
                            </th>
                            <td>
                                <input type="text"
                                       id="openai_api_key"
                                       name="ipv_pro_openai_api_key"
                                       value="<?php echo esc_attr(get_option('ipv_pro_openai_api_key')); ?>"
                                       class="regular-text"
                                       placeholder="sk-...">
                                <button type="button" class="button button-secondary ipv-test-api" data-api="openai">
                                    Test Connessione
                                </button>
                                <div class="ipv-api-test-result" data-api="openai"></div>
                                <p class="description">
                                    Ottieni la tua API key da <a href="https://platform.openai.com/api-keys" target="_blank">OpenAI Platform</a>
                                </p>
                            </td>
                        </tr>

                        <!-- OpenAI Model -->
                        <tr>
                            <th scope="row">
                                <label for="openai_model">Modello OpenAI</label>
                            </th>
                            <td>
                                <select id="openai_model" name="ipv_pro_openai_model">
                                    <option value="gpt-4-turbo-preview" <?php selected(get_option('ipv_pro_openai_model'), 'gpt-4-turbo-preview'); ?>>
                                        GPT-4 Turbo (Consigliato)
                                    </option>
                                    <option value="gpt-4" <?php selected(get_option('ipv_pro_openai_model'), 'gpt-4'); ?>>
                                        GPT-4
                                    </option>
                                    <option value="gpt-3.5-turbo" <?php selected(get_option('ipv_pro_openai_model'), 'gpt-3.5-turbo'); ?>>
                                        GPT-3.5 Turbo (Economico)
                                    </option>
                                </select>
                                <p class="description">
                                    GPT-4 Turbo offre la migliore qualità per contenuti lunghi e complessi.
                                </p>
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- Transcription Settings -->
                <div class="ipv-settings-section">
                    <h2>🎙️ Impostazioni Trascrizione</h2>

                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="transcript_mode">Modalità Trascrizione</label>
                            </th>
                            <td>
                                <select id="transcript_mode" name="ipv_pro_transcript_mode">
                                    <option value="auto" <?php selected(get_option('ipv_pro_transcript_mode'), 'auto'); ?>>
                                        Automatica (Native → Generata)
                                    </option>
                                    <option value="native_only" <?php selected(get_option('ipv_pro_transcript_mode'), 'native_only'); ?>>
                                        Solo Sottotitoli Nativi
                                    </option>
                                    <option value="generate" <?php selected(get_option('ipv_pro_transcript_mode'), 'generate'); ?>>
                                        Sempre Generata (AI)
                                    </option>
                                </select>
                                <p class="description">
                                    <strong>Automatica:</strong> Prova prima i sottotitoli nativi, poi genera con AI se non disponibili<br>
                                    <strong>Solo Nativi:</strong> Usa solo sottotitoli YouTube (più veloce ma non sempre disponibili)<br>
                                    <strong>Generata:</strong> Genera sempre trascrizione AI (più lento ma più accurato)
                                </p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label for="transcript_timeout">Timeout Trascrizione</label>
                            </th>
                            <td>
                                <input type="number"
                                       id="transcript_timeout"
                                       name="ipv_pro_transcript_timeout"
                                       value="<?php echo esc_attr(get_option('ipv_pro_transcript_timeout', 300)); ?>"
                                       min="60"
                                       max="600"
                                       step="30">
                                <span>secondi</span>
                                <p class="description">
                                    Tempo massimo di attesa per generazione trascrizione (consigliato: 300s per video lunghi)
                                </p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label for="max_retry">Tentativi Massimi</label>
                            </th>
                            <td>
                                <input type="number"
                                       id="max_retry"
                                       name="ipv_pro_max_retry"
                                       value="<?php echo esc_attr(get_option('ipv_pro_max_retry', 3)); ?>"
                                       min="1"
                                       max="10">
                                <p class="description">
                                    Numero massimo di tentativi in caso di errore
                                </p>
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- Category Mapping -->
                <div class="ipv-settings-section">
                    <h2>📁 Mappatura Categorie YouTube → WordPress</h2>
                    <p>Associa le categorie YouTube alle categorie WordPress corrispondenti.</p>

                    <table class="form-table">
                        <?php
                        $mapping = get_option('ipv_pro_category_mapping', []);
                        $wp_categories = get_categories(['hide_empty' => false]);

                        $youtube_categories = [
                            '1' => 'Film & Animation',
                            '2' => 'Autos & Vehicles',
                            '10' => 'Music',
                            '15' => 'Pets & Animals',
                            '17' => 'Sports',
                            '19' => 'Travel & Events',
                            '20' => 'Gaming',
                            '22' => 'People & Blogs',
                            '23' => 'Comedy',
                            '24' => 'Entertainment',
                            '25' => 'News & Politics',
                            '26' => 'Howto & Style',
                            '27' => 'Education',
                            '28' => 'Science & Technology',
                            '29' => 'Nonprofits & Activism'
                        ];

                        foreach ($youtube_categories as $yt_id => $yt_name):
                            $selected_wp_cat = isset($mapping[$yt_id]) ? $mapping[$yt_id] : 0;
                        ?>
                        <tr>
                            <th scope="row">
                                <label><?php echo esc_html($yt_name); ?></label>
                            </th>
                            <td>
                                <select name="ipv_pro_category_mapping[<?php echo esc_attr($yt_id); ?>]">
                                    <option value="0">-- Usa Categoria Default --</option>
                                    <?php foreach ($wp_categories as $wp_cat): ?>
                                        <option value="<?php echo esc_attr($wp_cat->term_id); ?>"
                                                <?php selected($selected_wp_cat, $wp_cat->term_id); ?>>
                                            <?php echo esc_html($wp_cat->name); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        <?php endforeach; ?>

                        <tr>
                            <th scope="row">
                                <label for="default_category">Categoria Default</label>
                            </th>
                            <td>
                                <select id="default_category" name="ipv_pro_default_category">
                                    <?php
                                    $default_cat = get_option('ipv_pro_default_category', 1);
                                    foreach ($wp_categories as $wp_cat):
                                    ?>
                                        <option value="<?php echo esc_attr($wp_cat->term_id); ?>"
                                                <?php selected($default_cat, $wp_cat->term_id); ?>>
                                            <?php echo esc_html($wp_cat->name); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="description">
                                    Categoria usata quando non c'è mappatura specifica
                                </p>
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- Import Settings -->
                <div class="ipv-settings-section">
                    <h2>📥 Impostazioni Importazione</h2>

                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="auto_publish">Pubblicazione Automatica</label>
                            </th>
                            <td>
                                <label>
                                    <input type="checkbox"
                                           id="auto_publish"
                                           name="ipv_pro_auto_publish"
                                           value="1"
                                           <?php checked(get_option('ipv_pro_auto_publish'), 1); ?>>
                                    Pubblica automaticamente i video elaborati
                                </label>
                                <p class="description">
                                    Se disabilitato, i video rimarranno in bozza per revisione manuale
                                </p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label for="batch_size">Dimensione Batch</label>
                            </th>
                            <td>
                                <input type="number"
                                       id="batch_size"
                                       name="ipv_pro_batch_size"
                                       value="<?php echo esc_attr(get_option('ipv_pro_batch_size', 5)); ?>"
                                       min="1"
                                       max="20">
                                <span>video per elaborazione</span>
                                <p class="description">
                                    Numero di video elaborati simultaneamente dal cron job
                                </p>
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- RSS Auto-Import Settings -->
                <div class="ipv-settings-section">
                    <h2>📡 RSS Auto-Import</h2>
                    <p>Configura l'importazione automatica dei nuovi video dal feed RSS del canale YouTube.</p>

                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="rss_feed_url">RSS Feed URL</label>
                            </th>
                            <td>
                                <input type="url"
                                       id="rss_feed_url"
                                       name="ipv_pro_rss_feed_url"
                                       value="<?php echo esc_attr(get_option('ipv_pro_rss_feed_url')); ?>"
                                       class="regular-text"
                                       placeholder="https://www.youtube.com/feeds/videos.xml?channel_id=...">
                                <button type="button" class="button button-secondary ipv-test-rss-feed">
                                    Test Feed
                                </button>
                                <div class="ipv-rss-test-result"></div>
                                <p class="description">
                                    URL del feed RSS/Atom del canale YouTube (formato: https://www.youtube.com/feeds/videos.xml?channel_id=CHANNEL_ID)
                                </p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label for="auto_import_enabled">Abilita Auto-Import</label>
                            </th>
                            <td>
                                <label>
                                    <input type="checkbox"
                                           id="auto_import_enabled"
                                           name="ipv_pro_auto_import_enabled"
                                           value="1"
                                           <?php checked(get_option('ipv_pro_auto_import_enabled'), 1); ?>>
                                    Controlla automaticamente il feed per nuovi video
                                </label>
                                <p class="description">
                                    Il sistema controllerà periodicamente il feed RSS e importerà automaticamente i nuovi video
                                </p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label for="auto_import_interval">Intervallo Controllo</label>
                            </th>
                            <td>
                                <input type="number"
                                       id="auto_import_interval"
                                       name="ipv_pro_auto_import_interval"
                                       value="<?php echo esc_attr(get_option('ipv_pro_auto_import_interval', 60)); ?>"
                                       min="15"
                                       max="1440">
                                <span>minuti</span>
                                <p class="description">
                                    Frequenza del controllo feed (minimo 15 minuti, massimo 24 ore)
                                </p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label for="auto_import_max_videos">Max Video per Controllo</label>
                            </th>
                            <td>
                                <input type="number"
                                       id="auto_import_max_videos"
                                       name="ipv_pro_auto_import_max_videos"
                                       value="<?php echo esc_attr(get_option('ipv_pro_auto_import_max_videos', 10)); ?>"
                                       min="1"
                                       max="50">
                                <span>video</span>
                                <p class="description">
                                    Numero massimo di nuovi video da importare per ogni controllo
                                </p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label for="auto_import_email_notifications">Notifiche Email</label>
                            </th>
                            <td>
                                <label>
                                    <input type="checkbox"
                                           id="auto_import_email_notifications"
                                           name="ipv_pro_auto_import_email_notifications"
                                           value="1"
                                           <?php checked(get_option('ipv_pro_auto_import_email_notifications'), 1); ?>>
                                    Invia email quando vengono importati nuovi video
                                </label>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label for="auto_import_notification_email">Email Notifiche</label>
                            </th>
                            <td>
                                <input type="email"
                                       id="auto_import_notification_email"
                                       name="ipv_pro_auto_import_notification_email"
                                       value="<?php echo esc_attr(get_option('ipv_pro_auto_import_notification_email', get_option('admin_email'))); ?>"
                                       class="regular-text">
                                <p class="description">
                                    Indirizzo email per le notifiche di auto-import
                                </p>
                            </td>
                        </tr>

                        <?php
                        $rss_auto_import = IPV_Production_System_Pro::get_instance()->rss_auto_import;
                        $last_check = $rss_auto_import->get_last_check();
                        $next_check = $rss_auto_import->get_next_check();
                        ?>

                        <tr>
                            <th scope="row">Stato Auto-Import</th>
                            <td>
                                <p>
                                    <strong>Ultimo controllo:</strong> <?php echo $last_check ? $last_check : 'Mai eseguito'; ?><br>
                                    <strong>Prossimo controllo:</strong> <?php echo $next_check; ?>
                                </p>
                                <button type="button" class="button button-secondary ipv-manual-import-check">
                                    Esegui Controllo Manuale
                                </button>
                                <div class="ipv-manual-check-result"></div>
                            </td>
                        </tr>
                    </table>
                </div>

                <?php submit_button('Salva Impostazioni', 'primary', 'ipv_pro_save_settings'); ?>
            </form>
        </div>
        <?php
    }

    /**
     * Save settings
     */
    private function save_settings() {
        if (!current_user_can('manage_options')) {
            return;
        }

        // API Keys
        if (isset($_POST['ipv_pro_youtube_api_key'])) {
            update_option('ipv_pro_youtube_api_key', sanitize_text_field($_POST['ipv_pro_youtube_api_key']));
        }

        if (isset($_POST['ipv_pro_supadata_api_key'])) {
            update_option('ipv_pro_supadata_api_key', sanitize_text_field($_POST['ipv_pro_supadata_api_key']));
        }

        if (isset($_POST['ipv_pro_openai_api_key'])) {
            update_option('ipv_pro_openai_api_key', sanitize_text_field($_POST['ipv_pro_openai_api_key']));
        }

        if (isset($_POST['ipv_pro_openai_model'])) {
            update_option('ipv_pro_openai_model', sanitize_text_field($_POST['ipv_pro_openai_model']));
        }

        // Transcription
        if (isset($_POST['ipv_pro_transcript_mode'])) {
            update_option('ipv_pro_transcript_mode', sanitize_text_field($_POST['ipv_pro_transcript_mode']));
        }

        if (isset($_POST['ipv_pro_transcript_timeout'])) {
            update_option('ipv_pro_transcript_timeout', absint($_POST['ipv_pro_transcript_timeout']));
        }

        if (isset($_POST['ipv_pro_max_retry'])) {
            update_option('ipv_pro_max_retry', absint($_POST['ipv_pro_max_retry']));
        }

        // Category Mapping
        if (isset($_POST['ipv_pro_category_mapping'])) {
            $mapping = array_map('absint', $_POST['ipv_pro_category_mapping']);
            update_option('ipv_pro_category_mapping', $mapping);
        }

        if (isset($_POST['ipv_pro_default_category'])) {
            update_option('ipv_pro_default_category', absint($_POST['ipv_pro_default_category']));
        }

        // Import Settings
        update_option('ipv_pro_auto_publish', isset($_POST['ipv_pro_auto_publish']) ? 1 : 0);

        if (isset($_POST['ipv_pro_batch_size'])) {
            update_option('ipv_pro_batch_size', absint($_POST['ipv_pro_batch_size']));
        }

        // RSS Auto-Import Settings
        if (isset($_POST['ipv_pro_rss_feed_url'])) {
            update_option('ipv_pro_rss_feed_url', esc_url_raw($_POST['ipv_pro_rss_feed_url']));
        }

        update_option('ipv_pro_auto_import_enabled', isset($_POST['ipv_pro_auto_import_enabled']) ? 1 : 0);
        update_option('ipv_pro_auto_import_email_notifications', isset($_POST['ipv_pro_auto_import_email_notifications']) ? 1 : 0);

        if (isset($_POST['ipv_pro_auto_import_interval'])) {
            $interval = absint($_POST['ipv_pro_auto_import_interval']);
            // Clamp between 15 and 1440 minutes
            $interval = max(15, min(1440, $interval));
            update_option('ipv_pro_auto_import_interval', $interval);
        }

        if (isset($_POST['ipv_pro_auto_import_max_videos'])) {
            $max_videos = absint($_POST['ipv_pro_auto_import_max_videos']);
            // Clamp between 1 and 50
            $max_videos = max(1, min(50, $max_videos));
            update_option('ipv_pro_auto_import_max_videos', $max_videos);
        }

        if (isset($_POST['ipv_pro_auto_import_notification_email'])) {
            update_option('ipv_pro_auto_import_notification_email', sanitize_email($_POST['ipv_pro_auto_import_notification_email']));
        }
    }
}
