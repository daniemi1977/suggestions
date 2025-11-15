<?php
/**
 * Chat Class
 * Handles chat interface, AI integration with RAG, and conversation management
 */

if (!defined('ABSPATH')) exit;

class CWAU_Chat {

    /**
     * Render chat shortcode
     */
    public static function shortcode($atts) {
        if (!get_option('cwau_chat_enabled', 1)) {
            return '';
        }

        ob_start();
        self::render_chat();
        return ob_get_clean();
    }

    /**
     * Maybe show chat widget in footer
     */
    public static function maybe_widget() {
        if (!get_option('cwau_chat_enabled', 1)) {
            return;
        }

        $position = get_option('cwau_chat_position', 'bottom-right');
        if ($position === 'shortcode') {
            return;
        }

        echo '<div class="cwau-floating cwau-' . esc_attr($position) . '">';
        self::render_chat();
        echo '</div>';
    }

    /**
     * Enqueue frontend assets
     */
    public static function enqueue_frontend_assets() {
        if (!get_option('cwau_chat_enabled', 1)) {
            return;
        }

        wp_enqueue_style('cwau-chat', CWAU_URL . 'assets/chat-frontend.css', array(), CWAU_VERSION);
        wp_enqueue_script('cwau-chat', CWAU_URL . 'assets/chat-frontend.js', array('jquery'), CWAU_VERSION, true);

        // Get or create session ID
        if (!session_id()) {
            session_start();
        }
        $session_id = session_id() ?: uniqid('cwau_', true);

        wp_localize_script('cwau-chat', 'cwau_chat', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('cwau_chat_nonce'),
            'session_id' => $session_id,
            'quick_replies' => get_option('cwau_quick_replies', array(
                'Informazioni prodotto',
                'Stato ordine',
                'Assistenza tecnica',
                'Parla con operatore'
            ))
        ));
    }

    /**
     * Render chat interface
     */
    private static function render_chat() {
        $title = esc_html(get_option('cwau_chat_title', 'Shop Assistant'));
        $placeholder = esc_html(get_option('cwau_chat_placeholder', 'Come posso aiutarti?'));
        $bg_color = esc_attr(get_option('cwau_chat_bg', '#2563eb'));
        $text_color = esc_attr(get_option('cwau_chat_color', '#ffffff'));
        $quick_replies = get_option('cwau_quick_replies', array());
        ?>
        <div class="cwau-chat-widget" style="--primary-color: <?php echo $bg_color; ?>; --text-color: <?php echo $text_color; ?>">
            <div class="cwau-chat-header">
                <div class="cwau-header-content">
                    <div class="cwau-avatar">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z"/>
                        </svg>
                    </div>
                    <div class="cwau-title-area">
                        <span class="cwau-chat-title"><?php echo $title; ?></span>
                        <span class="cwau-status online">Online</span>
                    </div>
                </div>
                <button class="cwau-chat-minimize" aria-label="Minimize">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M19,13H5V11H19V13Z"/>
                    </svg>
                </button>
            </div>

            <div class="cwau-chat-messages">
                <div class="cwau-message ai-message">
                    <div class="message-avatar">AI</div>
                    <div class="message-content">
                        <p>Ciao! 👋 Sono il tuo assistente digitale. Come posso aiutarti oggi?</p>
                    </div>
                </div>
            </div>

            <?php if (!empty($quick_replies)): ?>
            <div class="cwau-quick-replies">
                <?php foreach ($quick_replies as $reply): ?>
                    <button class="quick-reply" data-text="<?php echo esc_attr($reply); ?>">
                        <?php echo esc_html($reply); ?>
                    </button>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <div class="cwau-typing-indicator" style="display: none;">
                <div class="typing-dot"></div>
                <div class="typing-dot"></div>
                <div class="typing-dot"></div>
            </div>

            <div class="cwau-chat-input-area">
                <input type="text" class="cwau-chat-input" placeholder="<?php echo $placeholder; ?>" autocomplete="off">
                <button class="cwau-send-button" aria-label="Send">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M2,21L23,12L2,3V10L17,12L2,14V21Z"/>
                    </svg>
                </button>
            </div>

            <div class="cwau-powered-by">
                Powered by Claude & OpenAI
            </div>
        </div>
        <?php
    }

    /**
     * AJAX handler for chat messages
     */
    public static function ajax_chat() {
        check_ajax_referer('cwau_chat_nonce', 'nonce');

        $message = isset($_POST['message']) ? sanitize_textarea_field($_POST['message']) : '';
        $session_id = isset($_POST['session_id']) ? sanitize_text_field($_POST['session_id']) : '';

        if (empty($message) || empty($session_id)) {
            wp_send_json_error(array('message' => 'Messaggio o sessione non validi'));
        }

        // Get or create conversation
        $conversation = CWAU_Database::get_conversation_by_session($session_id);

        if (!$conversation) {
            $user_id = get_current_user_id();
            $user_email = is_user_logged_in() ? wp_get_current_user()->user_email : null;
            $conversation_id = CWAU_Database::create_conversation($session_id, $user_id, $user_email);
        } else {
            $conversation_id = $conversation->id;
        }

        // Save user message
        if (get_option('cwau_save_conversations', 1)) {
            CWAU_Database::add_message($conversation_id, 'user', $message);
        }

        // Check for escalation keywords
        if (self::should_escalate($message)) {
            CWAU_Operators::auto_escalate($conversation_id, $message);
            $response = 'Ho notato che potresti aver bisogno di assistenza specializzata. Un operatore sarà con te a breve. Nel frattempo, posso comunque provare ad aiutarti.';
        } else {
            // Get conversation history
            $history = CWAU_Database::get_conversation_messages($conversation_id, 10);

            // Generate AI response with RAG
            $response = self::generate_ai_response($message, $history);

            // Analyze sentiment
            $sentiment = self::analyze_sentiment($message);
            if ($sentiment !== 'neutral') {
                CWAU_Database::update_conversation_sentiment($conversation_id, $sentiment);
            }
        }

        // Save AI response
        if (get_option('cwau_save_conversations', 1)) {
            CWAU_Database::add_message($conversation_id, 'ai', $response);
        }

        wp_send_json_success(array(
            'message' => $response,
            'conversation_id' => $conversation_id
        ));
    }

    /**
     * Generate AI response with RAG integration
     */
    private static function generate_ai_response($user_message, $history = array()) {
        $preferred_ai = get_option('cwau_preferred_ai', 'openai');

        // Get RAG context
        $rag_context = CWAU_RAG::get_context_for_query($user_message);

        // Build conversation history
        $messages = array();

        // System prompt
        $system_prompt = self::get_system_prompt($user_message);
        if (!empty($rag_context)) {
            $system_prompt .= $rag_context;
        }

        // Add conversation history
        foreach ($history as $msg) {
            $role = $msg->sender === 'user' ? 'user' : 'assistant';
            $messages[] = array(
                'role' => $role,
                'content' => $msg->message
            );
        }

        // Add current message
        $messages[] = array(
            'role' => 'user',
            'content' => $user_message
        );

        // Call appropriate AI
        if ($preferred_ai === 'anthropic') {
            return self::call_anthropic_api($system_prompt, $messages);
        } else {
            return self::call_openai_api($system_prompt, $messages);
        }
    }

    /**
     * Get system prompt based on message content
     */
    private static function get_system_prompt($message) {
        $templates = get_option('cwau_prompt_templates', array());

        // Detect scenario
        $message_lower = strtolower($message);

        if (strpos($message_lower, 'prodotto') !== false || strpos($message_lower, 'prezzo') !== false) {
            return $templates['product_inquiry'] ?? $templates['default'];
        } elseif (strpos($message_lower, 'ordine') !== false || strpos($message_lower, 'spedizione') !== false) {
            return $templates['order_support'] ?? $templates['default'];
        } elseif (strpos($message_lower, 'problema') !== false || strpos($message_lower, 'lamentela') !== false) {
            return $templates['complaint'] ?? $templates['default'];
        }

        return $templates['default'] ?? 'Sei un assistente esperto di e-commerce. Aiuta i clienti in modo professionale e amichevole.';
    }

    /**
     * Call OpenAI API
     */
    private static function call_openai_api($system_prompt, $messages) {
        $api_key = get_option('cwau_openai_api_key');
        if (empty($api_key)) {
            return 'Mi dispiace, il servizio di chat non è configurato correttamente.';
        }

        $url = 'https://api.openai.com/v1/chat/completions';

        // Prepend system message
        array_unshift($messages, array(
            'role' => 'system',
            'content' => $system_prompt
        ));

        $body = json_encode(array(
            'model' => 'gpt-4o-mini',
            'messages' => $messages,
            'temperature' => 0.7,
            'max_tokens' => 500
        ));

        $response = wp_remote_post($url, array(
            'headers' => array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $api_key
            ),
            'body' => $body,
            'timeout' => 30
        ));

        if (is_wp_error($response)) {
            error_log('OpenAI API Error: ' . $response->get_error_message());
            return 'Mi dispiace, si è verificato un errore. Riprova tra poco.';
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (isset($body['error'])) {
            error_log('OpenAI API Error: ' . $body['error']['message']);
            return 'Mi dispiace, si è verificato un errore. Riprova tra poco.';
        }

        return $body['choices'][0]['message']['content'] ?? 'Mi dispiace, non ho ricevuto una risposta valida.';
    }

    /**
     * Call Anthropic (Claude) API
     */
    private static function call_anthropic_api($system_prompt, $messages) {
        $api_key = get_option('cwau_anthropic_api_key');
        if (empty($api_key)) {
            return 'Mi dispiace, il servizio di chat non è configurato correttamente.';
        }

        $url = 'https://api.anthropic.com/v1/messages';

        $body = json_encode(array(
            'model' => 'claude-3-5-sonnet-20241022',
            'max_tokens' => 1024,
            'system' => $system_prompt,
            'messages' => $messages
        ));

        $response = wp_remote_post($url, array(
            'headers' => array(
                'Content-Type' => 'application/json',
                'x-api-key' => $api_key,
                'anthropic-version' => '2023-06-01'
            ),
            'body' => $body,
            'timeout' => 30
        ));

        if (is_wp_error($response)) {
            error_log('Anthropic API Error: ' . $response->get_error_message());
            return 'Mi dispiace, si è verificato un errore. Riprova tra poco.';
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (isset($body['error'])) {
            error_log('Anthropic API Error: ' . json_encode($body['error']));
            return 'Mi dispiace, si è verificato un errore. Riprova tra poco.';
        }

        return $body['content'][0]['text'] ?? 'Mi dispiace, non ho ricevuto una risposta valida.';
    }

    /**
     * Check if message should trigger escalation
     */
    private static function should_escalate($message) {
        $keywords = get_option('cwau_escalation_keywords', '');
        if (empty($keywords)) {
            return false;
        }

        $keywords_array = array_map('trim', explode("\n", strtolower($keywords)));
        $message_lower = strtolower($message);

        foreach ($keywords_array as $keyword) {
            if (!empty($keyword) && strpos($message_lower, $keyword) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Analyze message sentiment (simple implementation)
     */
    private static function analyze_sentiment($message) {
        $message_lower = strtolower($message);

        // Negative keywords
        $negative = array('problema', 'male', 'cattivo', 'pessimo', 'difettoso', 'rotto', 'non funziona', 'deluso', 'arrabbiato');
        foreach ($negative as $word) {
            if (strpos($message_lower, $word) !== false) {
                return 'negative';
            }
        }

        // Positive keywords
        $positive = array('grazie', 'ottimo', 'perfetto', 'eccellente', 'fantastico', 'meraviglioso', 'soddisfatto');
        foreach ($positive as $word) {
            if (strpos($message_lower, $word) !== false) {
                return 'positive';
            }
        }

        return 'neutral';
    }
}
