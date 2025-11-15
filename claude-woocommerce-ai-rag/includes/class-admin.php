<?php
/**
 * Admin Panel Class
 * Handles all admin interface and settings
 */

if (!defined('ABSPATH')) exit;

class CWAU_Admin {

    /**
     * Register settings
     */
    public static function register_settings() {
        $settings = array(
            'cwau_openai_api_key',
            'cwau_anthropic_api_key',
            'cwau_preferred_ai',
            'cwau_chat_enabled',
            'cwau_chat_position',
            'cwau_chat_title',
            'cwau_chat_placeholder',
            'cwau_chat_bg',
            'cwau_chat_color',
            'cwau_save_conversations',
            'cwau_operator_email',
            'cwau_rag_enabled',
            'cwau_rag_top_k',
            'cwau_prompt_templates',
            'cwau_quick_replies',
            'cwau_escalation_keywords'
        );

        foreach ($settings as $setting) {
            register_setting('cwau_settings_group', $setting);
        }
    }

    /**
     * Register admin menu
     */
    public static function register_menu() {
        add_menu_page(
            'Claude AI RAG',
            'Claude AI RAG',
            'manage_options',
            'cwau',
            array(__CLASS__, 'render_page'),
            'dashicons-format-chat',
            56
        );

        add_submenu_page(
            'cwau',
            'Analytics',
            'Analytics',
            'manage_options',
            'cwau-analytics',
            array(__CLASS__, 'render_analytics')
        );

        add_submenu_page(
            'cwau',
            'Conversazioni',
            'Conversazioni',
            'manage_options',
            'cwau-conversations',
            array(__CLASS__, 'render_conversations')
        );

        add_submenu_page(
            'cwau',
            'Escalazioni',
            'Escalazioni',
            'manage_options',
            'cwau-escalations',
            array(__CLASS__, 'render_escalations')
        );
    }

    /**
     * Enqueue admin assets
     */
    public static function enqueue_assets($hook) {
        if (strpos($hook, 'cwau') === false) {
            return;
        }

        wp_enqueue_style('cwau-admin', CWAU_URL . 'assets/admin.css', array(), CWAU_VERSION);
        wp_enqueue_script('cwau-admin', CWAU_URL . 'assets/admin.js', array('jquery'), CWAU_VERSION, true);
        wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js', array(), '3.9.1', true);

        wp_localize_script('cwau-admin', 'cwau_admin', array(
            'nonce' => wp_create_nonce('cwau_admin_nonce'),
            'ajax_url' => admin_url('admin-ajax.php')
        ));
    }

    /**
     * Render main admin page
     */
    public static function render_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        if (isset($_POST['submit']) && check_admin_referer('cwau_settings_group-options')) {
            self::handle_form_submission();
            echo '<div class="notice notice-success is-dismissible"><p>Impostazioni salvate con successo!</p></div>';
        }

        $tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'settings';
        ?>
        <div class="wrap cwau-admin-wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

            <nav class="nav-tab-wrapper">
                <a href="?page=cwau&tab=settings" class="nav-tab <?php echo $tab === 'settings' ? 'nav-tab-active' : ''; ?>">
                    Impostazioni
                </a>
                <a href="?page=cwau&tab=prompts" class="nav-tab <?php echo $tab === 'prompts' ? 'nav-tab-active' : ''; ?>">
                    Prompt Templates
                </a>
                <a href="?page=cwau&tab=operators" class="nav-tab <?php echo $tab === 'operators' ? 'nav-tab-active' : ''; ?>">
                    Operatori
                </a>
                <a href="?page=cwau&tab=rag" class="nav-tab <?php echo $tab === 'rag' ? 'nav-tab-active' : ''; ?>">
                    RAG System
                </a>
                <a href="?page=cwau&tab=appearance" class="nav-tab <?php echo $tab === 'appearance' ? 'nav-tab-active' : ''; ?>">
                    Aspetto
                </a>
            </nav>

            <form method="post" action="">
                <?php wp_nonce_field('cwau_settings_group-options'); ?>

                <div class="cwau-tab-content">
                    <?php
                    switch ($tab) {
                        case 'settings':
                            self::render_settings_tab();
                            break;
                        case 'prompts':
                            self::render_prompts_tab();
                            break;
                        case 'operators':
                            self::render_operators_tab();
                            break;
                        case 'rag':
                            self::render_rag_tab();
                            break;
                        case 'appearance':
                            self::render_appearance_tab();
                            break;
                    }
                    ?>
                </div>

                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    /**
     * Render settings tab
     */
    private static function render_settings_tab() {
        $stats = CWAU_RAG::get_stats();
        ?>
        <div class="cwau-section">
            <h2>Configurazione API</h2>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="cwau_openai_api_key">OpenAI API Key</label></th>
                    <td>
                        <input type="password" id="cwau_openai_api_key" name="cwau_openai_api_key"
                               value="<?php echo esc_attr(get_option('cwau_openai_api_key')); ?>"
                               class="regular-text" />
                        <button type="button" class="button" id="test-openai">Test Connessione</button>
                        <p class="description">Necessaria per embeddings RAG e chat (se selezionata)</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="cwau_anthropic_api_key">Anthropic (Claude) API Key</label></th>
                    <td>
                        <input type="password" id="cwau_anthropic_api_key" name="cwau_anthropic_api_key"
                               value="<?php echo esc_attr(get_option('cwau_anthropic_api_key')); ?>"
                               class="regular-text" />
                        <button type="button" class="button" id="test-anthropic">Test Connessione</button>
                        <p class="description">Opzionale: usa Claude per le risposte della chat</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="cwau_preferred_ai">AI Preferita per Chat</label></th>
                    <td>
                        <select id="cwau_preferred_ai" name="cwau_preferred_ai">
                            <option value="openai" <?php selected(get_option('cwau_preferred_ai', 'openai'), 'openai'); ?>>
                                OpenAI (GPT-4o-mini)
                            </option>
                            <option value="anthropic" <?php selected(get_option('cwau_preferred_ai'), 'anthropic'); ?>>
                                Anthropic (Claude 3.5 Sonnet)
                            </option>
                        </select>
                    </td>
                </tr>
            </table>
        </div>

        <div class="cwau-section">
            <h2>Impostazioni Chat</h2>
            <table class="form-table">
                <tr>
                    <th scope="row">Abilita Chat</th>
                    <td>
                        <label>
                            <input type="checkbox" name="cwau_chat_enabled" value="1"
                                   <?php checked(get_option('cwau_chat_enabled', 1), 1); ?> />
                            Mostra il widget di chat sul sito
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Salva Conversazioni</th>
                    <td>
                        <label>
                            <input type="checkbox" name="cwau_save_conversations" value="1"
                                   <?php checked(get_option('cwau_save_conversations', 1), 1); ?> />
                            Salva cronologia conversazioni nel database
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="cwau_chat_position">Posizione Widget</label></th>
                    <td>
                        <select id="cwau_chat_position" name="cwau_chat_position">
                            <option value="bottom-right" <?php selected(get_option('cwau_chat_position', 'bottom-right'), 'bottom-right'); ?>>
                                In basso a destra
                            </option>
                            <option value="bottom-left" <?php selected(get_option('cwau_chat_position'), 'bottom-left'); ?>>
                                In basso a sinistra
                            </option>
                            <option value="shortcode" <?php selected(get_option('cwau_chat_position'), 'shortcode'); ?>>
                                Solo shortcode [cwau_chat]
                            </option>
                        </select>
                    </td>
                </tr>
            </table>
        </div>

        <div class="cwau-section">
            <h2>Sistema RAG</h2>
            <table class="form-table">
                <tr>
                    <th scope="row">Abilita RAG</th>
                    <td>
                        <label>
                            <input type="checkbox" name="cwau_rag_enabled" value="1"
                                   <?php checked(get_option('cwau_rag_enabled', 1), 1); ?> />
                            Usa Retrieval-Augmented Generation per migliorare le risposte
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="cwau_rag_top_k">Prodotti da Recuperare (Top-K)</label></th>
                    <td>
                        <input type="number" id="cwau_rag_top_k" name="cwau_rag_top_k"
                               value="<?php echo esc_attr(get_option('cwau_rag_top_k', 5)); ?>"
                               min="1" max="20" step="1" />
                        <p class="description">Numero di prodotti rilevanti da includere nel contesto AI</p>
                    </td>
                </tr>
            </table>

            <div class="cwau-rag-stats-panel">
                <h3>Statistiche Indice RAG</h3>
                <div class="stats-grid">
                    <div class="stat-box">
                        <div class="stat-value"><?php echo $stats['total_products']; ?></div>
                        <div class="stat-label">Prodotti Totali</div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-value"><?php echo $stats['indexed_products']; ?></div>
                        <div class="stat-label">Prodotti Indicizzati</div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-value"><?php echo $stats['percentage']; ?>%</div>
                        <div class="stat-label">Completamento</div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render prompts tab
     */
    private static function render_prompts_tab() {
        $templates = get_option('cwau_prompt_templates', array());
        ?>
        <div class="cwau-section">
            <h2>Template Prompt Personalizzati</h2>
            <p class="description">Personalizza i prompt dell'AI in base al contesto della conversazione.</p>

            <table class="widefat">
                <thead>
                    <tr>
                        <th>Scenario</th>
                        <th>Prompt</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $scenarios = array(
                        'default' => 'Default / Generale',
                        'product_inquiry' => 'Domande sui Prodotti',
                        'order_support' => 'Supporto Ordini',
                        'complaint' => 'Reclami / Problemi'
                    );

                    foreach ($scenarios as $key => $label):
                    ?>
                    <tr>
                        <td><strong><?php echo esc_html($label); ?></strong></td>
                        <td>
                            <textarea name="cwau_prompt_templates[<?php echo esc_attr($key); ?>]"
                                      rows="4" style="width:100%"><?php
                                echo esc_textarea($templates[$key] ?? '');
                            ?></textarea>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="cwau-section">
            <h2>Risposte Rapide</h2>
            <p class="description">Pulsanti di risposta rapida mostrati nella chat (uno per riga)</p>
            <textarea name="cwau_quick_replies" rows="6" style="width:100%; max-width:600px"><?php
                $quick_replies = get_option('cwau_quick_replies', array());
                echo esc_textarea(is_array($quick_replies) ? implode("\n", $quick_replies) : '');
            ?></textarea>
        </div>
        <?php
    }

    /**
     * Render operators tab
     */
    private static function render_operators_tab() {
        ?>
        <div class="cwau-section">
            <h2>Configurazione Operatori</h2>

            <table class="form-table">
                <tr>
                    <th scope="row"><label for="cwau_operator_email">Email Operatore Principale</label></th>
                    <td>
                        <input type="email" id="cwau_operator_email" name="cwau_operator_email"
                               value="<?php echo esc_attr(get_option('cwau_operator_email', get_option('admin_email'))); ?>"
                               class="regular-text" />
                        <p class="description">Riceverà notifiche per le escalation</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="cwau_escalation_keywords">Parole Chiave Auto-Escalation</label></th>
                    <td>
                        <textarea id="cwau_escalation_keywords" name="cwau_escalation_keywords"
                                  rows="5" class="large-text" placeholder="Una parola per riga..."><?php
                            echo esc_textarea(get_option('cwau_escalation_keywords', ''));
                        ?></textarea>
                        <p class="description">
                            Quando l'utente usa queste parole, la conversazione viene automaticamente escalata ad un operatore.
                            <br>Esempi: rimborso, problema grave, manager, responsabile, operatore
                        </p>
                    </td>
                </tr>
            </table>
        </div>
        <?php
    }

    /**
     * Render RAG tab
     */
    private static function render_rag_tab() {
        $stats = CWAU_RAG::get_stats();
        ?>
        <div class="cwau-section">
            <h2>Sistema RAG (Retrieval-Augmented Generation)</h2>

            <div class="cwau-explanation-box">
                <h3>Come Funziona il RAG</h3>
                <ol>
                    <li><strong>Indicizzazione:</strong> I prodotti vengono convertiti in embeddings vettoriali usando OpenAI</li>
                    <li><strong>Recupero:</strong> Quando un utente fa una domanda, il sistema trova i prodotti più rilevanti</li>
                    <li><strong>Generazione:</strong> L'AI usa questi prodotti per dare risposte precise e contestuali</li>
                </ol>
                <p><strong>Benefici:</strong> Risposte più accurate, suggerimenti di prodotto pertinenti, riduzione di allucinazioni dell'AI</p>
            </div>

            <div class="cwau-rag-controls">
                <div class="button-group">
                    <button type="button" id="cwau-index-products" class="button button-primary">
                        <span class="dashicons dashicons-update"></span>
                        Indicizza Prodotti
                    </button>
                    <button type="button" id="cwau-reindex-all" class="button">
                        <span class="dashicons dashicons-admin-generic"></span>
                        Reindicizza Tutto
                    </button>
                    <button type="button" id="cwau-clear-index" class="button button-secondary">
                        <span class="dashicons dashicons-trash"></span>
                        Svuota Indice
                    </button>
                </div>

                <div id="cwau-rag-status" class="cwau-status-area"></div>
                <div id="cwau-rag-progress" class="cwau-progress-bar" style="display:none;">
                    <div class="progress-fill"></div>
                    <div class="progress-text">0%</div>
                </div>
            </div>

            <div class="cwau-rag-stats">
                <h3>Statistiche Indice</h3>
                <table class="widefat striped">
                    <tbody>
                        <tr>
                            <th>Prodotti Totali:</th>
                            <td><?php echo $stats['total_products']; ?></td>
                        </tr>
                        <tr>
                            <th>Prodotti Indicizzati:</th>
                            <td><?php echo $stats['indexed_products']; ?></td>
                        </tr>
                        <tr>
                            <th>Progresso:</th>
                            <td>
                                <div class="progress-container">
                                    <div class="progress-bar-static" style="width: <?php echo $stats['percentage']; ?>%"></div>
                                    <span><?php echo $stats['percentage']; ?>%</span>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
    }

    /**
     * Render appearance tab
     */
    private static function render_appearance_tab() {
        ?>
        <div class="cwau-section">
            <h2>Personalizzazione Aspetto</h2>

            <table class="form-table">
                <tr>
                    <th scope="row"><label for="cwau_chat_title">Titolo Chat</label></th>
                    <td>
                        <input type="text" id="cwau_chat_title" name="cwau_chat_title"
                               value="<?php echo esc_attr(get_option('cwau_chat_title', 'Shop Assistant')); ?>"
                               class="regular-text" />
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="cwau_chat_placeholder">Placeholder Input</label></th>
                    <td>
                        <input type="text" id="cwau_chat_placeholder" name="cwau_chat_placeholder"
                               value="<?php echo esc_attr(get_option('cwau_chat_placeholder', 'Come posso aiutarti?')); ?>"
                               class="regular-text" />
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="cwau_chat_bg">Colore Principale</label></th>
                    <td>
                        <input type="color" id="cwau_chat_bg" name="cwau_chat_bg"
                               value="<?php echo esc_attr(get_option('cwau_chat_bg', '#2563eb')); ?>" />
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="cwau_chat_color">Colore Testo Header</label></th>
                    <td>
                        <input type="color" id="cwau_chat_color" name="cwau_chat_color"
                               value="<?php echo esc_attr(get_option('cwau_chat_color', '#ffffff')); ?>" />
                    </td>
                </tr>
            </table>

            <div class="cwau-preview">
                <h3>Anteprima</h3>
                <div class="chat-preview-container">
                    <p>L'anteprima verrà mostrata sul frontend con le impostazioni attuali.</p>
                    <p>Usa lo shortcode <code>[cwau_chat]</code> per mostrare la chat in qualsiasi pagina.</p>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render analytics page
     */
    public static function render_analytics() {
        if (!current_user_can('manage_options')) {
            return;
        }

        ?>
        <div class="wrap cwau-admin-wrap">
            <h1>Analytics Chat</h1>

            <div id="cwau-analytics-dashboard">
                <div class="cwau-stats-grid">
                    <div class="cwau-stat-card">
                        <h3>Conversazioni Oggi</h3>
                        <div class="stat-number" id="conversations-today">
                            <span class="loading">Caricamento...</span>
                        </div>
                    </div>
                    <div class="cwau-stat-card">
                        <h3>Messaggi Totali</h3>
                        <div class="stat-number" id="messages-total">
                            <span class="loading">Caricamento...</span>
                        </div>
                    </div>
                    <div class="cwau-stat-card">
                        <h3>Escalazioni Attive</h3>
                        <div class="stat-number" id="escalations-count">
                            <span class="loading">Caricamento...</span>
                        </div>
                    </div>
                    <div class="cwau-stat-card">
                        <h3>Sentiment Medio</h3>
                        <div class="stat-number" id="avg-sentiment">
                            <span class="loading">Caricamento...</span>
                        </div>
                    </div>
                </div>

                <div class="cwau-chart-container">
                    <h2>Conversazioni Ultimi 7 Giorni</h2>
                    <canvas id="conversations-chart"></canvas>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render conversations page
     */
    public static function render_conversations() {
        if (!current_user_can('manage_options')) {
            return;
        }

        global $wpdb;
        $conversations = $wpdb->get_results("
            SELECT c.*, COUNT(m.id) as message_count
            FROM {$wpdb->prefix}cwau_conversations c
            LEFT JOIN {$wpdb->prefix}cwau_messages m ON c.id = m.conversation_id
            GROUP BY c.id
            ORDER BY c.started_at DESC
            LIMIT 50
        ");

        ?>
        <div class="wrap cwau-admin-wrap">
            <h1>Storico Conversazioni
                <a href="<?php echo admin_url('admin-ajax.php?action=cwau_export_conversations&nonce=' . wp_create_nonce('cwau_admin_nonce')); ?>"
                   class="button button-primary">
                    <span class="dashicons dashicons-download"></span> Esporta CSV
                </a>
            </h1>

            <table class="widefat striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Utente</th>
                        <th>Messaggi</th>
                        <th>Status</th>
                        <th>Sentiment</th>
                        <th>Inizio</th>
                        <th>Azioni</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($conversations)): ?>
                    <tr>
                        <td colspan="7" style="text-align:center;">Nessuna conversazione trovata</td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($conversations as $conv): ?>
                        <tr>
                            <td><?php echo $conv->id; ?></td>
                            <td><?php echo $conv->user_email ?: 'Anonimo'; ?></td>
                            <td><?php echo $conv->message_count; ?></td>
                            <td>
                                <span class="status-badge status-<?php echo $conv->status; ?>">
                                    <?php echo ucfirst($conv->status); ?>
                                </span>
                            </td>
                            <td>
                                <span class="sentiment-badge sentiment-<?php echo $conv->sentiment; ?>">
                                    <?php echo ucfirst($conv->sentiment); ?>
                                </span>
                            </td>
                            <td><?php echo date_i18n('d/m/Y H:i', strtotime($conv->started_at)); ?></td>
                            <td>
                                <button class="button button-small view-conversation"
                                        data-id="<?php echo $conv->id; ?>">
                                    Visualizza
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Modal for viewing conversation -->
        <div id="conversation-modal" class="cwau-modal" style="display:none;">
            <div class="cwau-modal-content">
                <span class="cwau-modal-close">&times;</span>
                <h2>Dettagli Conversazione</h2>
                <div id="conversation-messages"></div>
            </div>
        </div>
        <?php
    }

    /**
     * Render escalations page
     */
    public static function render_escalations() {
        if (!current_user_can('manage_options')) {
            return;
        }

        global $wpdb;
        $escalations = $wpdb->get_results("
            SELECT e.*, c.user_email, c.session_id
            FROM {$wpdb->prefix}cwau_escalations e
            LEFT JOIN {$wpdb->prefix}cwau_conversations c ON e.conversation_id = c.id
            ORDER BY e.created_at DESC
            LIMIT 50
        ");

        ?>
        <div class="wrap cwau-admin-wrap">
            <h1>Gestione Escalazioni</h1>

            <table class="widefat striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Conversazione</th>
                        <th>Utente</th>
                        <th>Motivo</th>
                        <th>Status</th>
                        <th>Assegnato a</th>
                        <th>Data</th>
                        <th>Azioni</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($escalations)): ?>
                    <tr>
                        <td colspan="8" style="text-align:center;">Nessuna escalazione trovata</td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($escalations as $esc): ?>
                        <tr>
                            <td><?php echo $esc->id; ?></td>
                            <td>#<?php echo $esc->conversation_id; ?></td>
                            <td><?php echo $esc->user_email ?: 'Anonimo'; ?></td>
                            <td><?php echo esc_html(substr($esc->reason, 0, 50)); ?>...</td>
                            <td>
                                <span class="status-badge status-<?php echo $esc->status; ?>">
                                    <?php echo ucfirst($esc->status); ?>
                                </span>
                            </td>
                            <td><?php echo $esc->assigned_to ?: '-'; ?></td>
                            <td><?php echo date_i18n('d/m/Y H:i', strtotime($esc->created_at)); ?></td>
                            <td>
                                <button class="button button-small view-escalation"
                                        data-id="<?php echo $esc->id; ?>">
                                    Dettagli
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    /**
     * Handle form submission
     */
    private static function handle_form_submission() {
        $text_fields = array(
            'cwau_openai_api_key',
            'cwau_anthropic_api_key',
            'cwau_preferred_ai',
            'cwau_chat_title',
            'cwau_chat_placeholder',
            'cwau_chat_bg',
            'cwau_chat_color',
            'cwau_chat_position',
            'cwau_operator_email',
            'cwau_escalation_keywords'
        );

        foreach ($text_fields as $field) {
            if (isset($_POST[$field])) {
                update_option($field, sanitize_text_field($_POST[$field]));
            }
        }

        // Numeric fields
        if (isset($_POST['cwau_rag_top_k'])) {
            update_option('cwau_rag_top_k', absint($_POST['cwau_rag_top_k']));
        }

        // Checkboxes
        update_option('cwau_chat_enabled', isset($_POST['cwau_chat_enabled']) ? 1 : 0);
        update_option('cwau_save_conversations', isset($_POST['cwau_save_conversations']) ? 1 : 0);
        update_option('cwau_rag_enabled', isset($_POST['cwau_rag_enabled']) ? 1 : 0);

        // Prompt templates
        if (isset($_POST['cwau_prompt_templates'])) {
            $templates = array();
            foreach ($_POST['cwau_prompt_templates'] as $key => $value) {
                $templates[sanitize_key($key)] = sanitize_textarea_field($value);
            }
            update_option('cwau_prompt_templates', $templates);
        }

        // Quick replies
        if (isset($_POST['cwau_quick_replies'])) {
            $quick_replies = array_filter(array_map('trim', explode("\n", $_POST['cwau_quick_replies'])));
            update_option('cwau_quick_replies', $quick_replies);
        }
    }
}
