<?php
/**
 * AJAX Handlers
 *
 * Handles all AJAX requests from admin interface
 */

if (!defined('ABSPATH')) {
    exit;
}

class IPV_Ajax_Handlers {

    /**
     * Constructor
     */
    public function __construct() {
        // Test API connections
        add_action('wp_ajax_ipv_test_youtube_api', [$this, 'test_youtube_api']);
        add_action('wp_ajax_ipv_test_supadata_api', [$this, 'test_supadata_api']);
        add_action('wp_ajax_ipv_test_openai_api', [$this, 'test_openai_api']);

        // Queue operations
        add_action('wp_ajax_ipv_import_single', [$this, 'import_single_video']);
        add_action('wp_ajax_ipv_import_bulk', [$this, 'import_bulk_videos']);
        add_action('wp_ajax_ipv_retry_video', [$this, 'retry_video']);
        add_action('wp_ajax_ipv_delete_video', [$this, 'delete_video']);

        // Get stats
        add_action('wp_ajax_ipv_get_stats', [$this, 'get_stats']);
        add_action('wp_ajax_ipv_get_queue_status', [$this, 'get_queue_status']);
    }

    /**
     * Verify nonce for all AJAX requests
     */
    private function verify_nonce() {
        if (!check_ajax_referer('ipv_pro_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => 'Nonce verification failed']);
            exit;
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Insufficient permissions']);
            exit;
        }
    }

    /**
     * Test YouTube API connection
     */
    public function test_youtube_api() {
        $this->verify_nonce();

        $youtube_api = new IPV_YouTube_API();
        $result = $youtube_api->test_connection();

        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }

    /**
     * Test SupaData API connection
     */
    public function test_supadata_api() {
        $this->verify_nonce();

        $supadata_api = new IPV_SupaData_API();
        $result = $supadata_api->test_connection();

        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }

    /**
     * Test OpenAI API connection
     */
    public function test_openai_api() {
        $this->verify_nonce();

        $openai_api = new IPV_OpenAI_API();
        $result = $openai_api->test_connection();

        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }

    /**
     * Import single video
     */
    public function import_single_video() {
        $this->verify_nonce();

        $video_url = isset($_POST['video_url']) ? sanitize_text_field($_POST['video_url']) : '';

        if (empty($video_url)) {
            wp_send_json_error(['message' => 'URL video mancante']);
        }

        $queue_manager = new IPV_Queue_Manager();
        $result = $queue_manager->add_to_queue($video_url);

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        }

        wp_send_json_success([
            'message' => 'Video aggiunto alla coda con successo!',
            'queue_id' => $result
        ]);
    }

    /**
     * Import bulk videos
     */
    public function import_bulk_videos() {
        $this->verify_nonce();

        $urls_text = isset($_POST['video_urls']) ? $_POST['video_urls'] : '';

        if (empty($urls_text)) {
            wp_send_json_error(['message' => 'Nessun URL fornito']);
        }

        // Parse URLs (one per line)
        $urls = array_filter(array_map('trim', explode("\n", $urls_text)));

        if (empty($urls)) {
            wp_send_json_error(['message' => 'Nessun URL valido trovato']);
        }

        if (count($urls) > 50) {
            wp_send_json_error(['message' => 'Massimo 50 video per importazione bulk']);
        }

        $queue_manager = new IPV_Queue_Manager();
        $results = $queue_manager->bulk_add_to_queue($urls);

        $success_count = 0;
        $error_count = 0;
        $details = [];

        foreach ($results as $result) {
            if ($result['success']) {
                $success_count++;
            } else {
                $error_count++;
            }
            $details[] = $result;
        }

        wp_send_json_success([
            'message' => sprintf(
                'Importazione completata: %d video aggiunti, %d errori',
                $success_count,
                $error_count
            ),
            'success_count' => $success_count,
            'error_count' => $error_count,
            'details' => $details
        ]);
    }

    /**
     * Retry failed video
     */
    public function retry_video() {
        $this->verify_nonce();

        $queue_id = isset($_POST['queue_id']) ? absint($_POST['queue_id']) : 0;

        if (!$queue_id) {
            wp_send_json_error(['message' => 'ID coda mancante']);
        }

        $queue_manager = new IPV_Queue_Manager();
        $result = $queue_manager->retry_item($queue_id);

        if ($result === false) {
            wp_send_json_error(['message' => 'Errore durante il retry']);
        }

        wp_send_json_success([
            'message' => 'Video rimesso in coda per l\'elaborazione'
        ]);
    }

    /**
     * Delete video from queue
     */
    public function delete_video() {
        $this->verify_nonce();

        $queue_id = isset($_POST['queue_id']) ? absint($_POST['queue_id']) : 0;
        $post_id = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;
        $delete_post = isset($_POST['delete_post']) ? (bool)$_POST['delete_post'] : false;

        if (!$queue_id) {
            wp_send_json_error(['message' => 'ID coda mancante']);
        }

        $queue_manager = new IPV_Queue_Manager();
        $result = $queue_manager->delete_item($queue_id);

        if ($result === false) {
            wp_send_json_error(['message' => 'Errore durante l\'eliminazione dalla coda']);
        }

        // Optionally delete post
        if ($delete_post && $post_id) {
            wp_delete_post($post_id, true);
        }

        wp_send_json_success([
            'message' => 'Video eliminato dalla coda' . ($delete_post ? ' e post cancellato' : '')
        ]);
    }

    /**
     * Get queue statistics
     */
    public function get_stats() {
        $this->verify_nonce();

        $queue_manager = new IPV_Queue_Manager();
        $stats = $queue_manager->get_stats();

        wp_send_json_success($stats);
    }

    /**
     * Get queue status (for live updates)
     */
    public function get_queue_status() {
        $this->verify_nonce();

        $queue_manager = new IPV_Queue_Manager();
        $items = $queue_manager->get_queue_items('processing', 10, 0);

        $processing_items = [];

        foreach ($items as $item) {
            $post = get_post($item->post_id);
            $processing_items[] = [
                'id' => $item->id,
                'post_id' => $item->post_id,
                'title' => $post ? $post->post_title : 'Post #' . $item->post_id,
                'status' => $item->status,
                'current_step' => $item->current_step,
                'retry_count' => $item->retry_count
            ];
        }

        wp_send_json_success([
            'processing_items' => $processing_items,
            'count' => count($processing_items)
        ]);
    }
}
