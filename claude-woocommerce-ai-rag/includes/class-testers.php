<?php
/**
 * API Testers Class
 * Handles testing of OpenAI and Anthropic API connections
 */

if (!defined('ABSPATH')) exit;

class CWAU_Testers {

    /**
     * Test OpenAI API connection
     */
    public static function test_openai() {
        check_ajax_referer('cwau_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permessi insufficienti'));
        }

        $api_key = get_option('cwau_openai_api_key');

        if (empty($api_key)) {
            wp_send_json_error(array('message' => 'API Key non configurata'));
        }

        // Test with a simple completion
        $url = 'https://api.openai.com/v1/chat/completions';

        $body = json_encode(array(
            'model' => 'gpt-4o-mini',
            'messages' => array(
                array('role' => 'user', 'content' => 'Test connection. Reply with: OK')
            ),
            'max_tokens' => 10
        ));

        $response = wp_remote_post($url, array(
            'headers' => array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $api_key
            ),
            'body' => $body,
            'timeout' => 15
        ));

        if (is_wp_error($response)) {
            wp_send_json_error(array(
                'message' => 'Errore di connessione: ' . $response->get_error_message()
            ));
        }

        $status_code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);

        if ($status_code === 200 && isset($body['choices'][0]['message'])) {
            wp_send_json_success(array(
                'message' => 'Connessione OpenAI riuscita!',
                'model' => $body['model'] ?? 'N/A',
                'response' => $body['choices'][0]['message']['content']
            ));
        } else {
            $error_message = 'Errore sconosciuto';
            if (isset($body['error']['message'])) {
                $error_message = $body['error']['message'];
            }

            wp_send_json_error(array(
                'message' => 'Test fallito: ' . $error_message,
                'status_code' => $status_code
            ));
        }
    }

    /**
     * Test Anthropic (Claude) API connection
     */
    public static function test_anthropic() {
        check_ajax_referer('cwau_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permessi insufficienti'));
        }

        $api_key = get_option('cwau_anthropic_api_key');

        if (empty($api_key)) {
            wp_send_json_error(array('message' => 'API Key non configurata'));
        }

        // Test with a simple message
        $url = 'https://api.anthropic.com/v1/messages';

        $body = json_encode(array(
            'model' => 'claude-3-5-sonnet-20241022',
            'max_tokens' => 50,
            'messages' => array(
                array('role' => 'user', 'content' => 'Test connection. Reply with: OK')
            )
        ));

        $response = wp_remote_post($url, array(
            'headers' => array(
                'Content-Type' => 'application/json',
                'x-api-key' => $api_key,
                'anthropic-version' => '2023-06-01'
            ),
            'body' => $body,
            'timeout' => 15
        ));

        if (is_wp_error($response)) {
            wp_send_json_error(array(
                'message' => 'Errore di connessione: ' . $response->get_error_message()
            ));
        }

        $status_code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);

        if ($status_code === 200 && isset($body['content'][0]['text'])) {
            wp_send_json_success(array(
                'message' => 'Connessione Anthropic (Claude) riuscita!',
                'model' => $body['model'] ?? 'N/A',
                'response' => $body['content'][0]['text']
            ));
        } else {
            $error_message = 'Errore sconosciuto';
            if (isset($body['error']['message'])) {
                $error_message = $body['error']['message'];
            }

            wp_send_json_error(array(
                'message' => 'Test fallito: ' . $error_message,
                'status_code' => $status_code
            ));
        }
    }

    /**
     * Test RAG system
     */
    public static function test_rag() {
        check_ajax_referer('cwau_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permessi insufficienti'));
        }

        $test_query = isset($_POST['query']) ? sanitize_text_field($_POST['query']) : 'prodotto economico';

        $results = CWAU_RAG::search_similar_products($test_query, 3);

        if (empty($results)) {
            wp_send_json_error(array('message' => 'Nessun risultato trovato. Assicurati di aver indicizzato i prodotti.'));
        }

        wp_send_json_success(array(
            'message' => 'Test RAG riuscito!',
            'query' => $test_query,
            'results' => $results
        ));
    }
}
