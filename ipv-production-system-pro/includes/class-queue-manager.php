<?php
/**
 * Queue Manager
 *
 * Manages the processing queue for video imports
 * Pipeline: YouTube Import → Transcription → AI Generation → Complete
 */

if (!defined('ABSPATH')) {
    exit;
}

class IPV_Queue_Manager {

    /**
     * Queue table name
     */
    private $table_name;

    /**
     * Max retry attempts
     */
    private $max_retry;

    /**
     * API instances
     */
    private $youtube_api;
    private $supadata_api;
    private $openai_api;

    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'ipv_processing_queue';
        $this->max_retry = (int)get_option('ipv_pro_max_retry', 3);

        // Get API instances
        $this->youtube_api = new IPV_YouTube_API();
        $this->supadata_api = new IPV_SupaData_API();
        $this->openai_api = new IPV_OpenAI_API();
    }

    /**
     * Add video to queue
     *
     * @param string $video_url YouTube video URL
     * @return int|WP_Error Queue item ID or error
     */
    public function add_to_queue($video_url) {
        global $wpdb;

        // Validate URL
        $video_id = $this->youtube_api->extract_video_id($video_url);

        if (!$video_id) {
            return new WP_Error('invalid_url', 'URL YouTube non valido');
        }

        // Check if already in queue
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$this->table_name} WHERE video_url = %s AND status != 'failed'",
            $video_url
        ));

        if ($existing) {
            return new WP_Error('already_queued', 'Video già in coda');
        }

        // Check if post already exists
        $existing_post = $wpdb->get_var($wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_ipv_video_id' AND meta_value = %s",
            $video_id
        ));

        if ($existing_post) {
            return new WP_Error('already_imported', 'Video già importato (Post ID: ' . $existing_post . ')');
        }

        // Create draft post
        $post_id = wp_insert_post([
            'post_title' => 'Video YouTube (processing...)',
            'post_status' => 'draft',
            'post_type' => 'post'
        ]);

        if (is_wp_error($post_id)) {
            return $post_id;
        }

        // Add to queue
        $result = $wpdb->insert(
            $this->table_name,
            [
                'post_id' => $post_id,
                'video_url' => $video_url,
                'status' => 'pending',
                'current_step' => 'queued',
                'retry_count' => 0,
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql')
            ],
            ['%d', '%s', '%s', '%s', '%d', '%s', '%s']
        );

        if ($result === false) {
            wp_delete_post($post_id, true);
            return new WP_Error('db_error', 'Errore database: ' . $wpdb->last_error);
        }

        // Save initial meta
        update_post_meta($post_id, '_ipv_video_url', $video_url);
        update_post_meta($post_id, '_ipv_video_id', $video_id);
        update_post_meta($post_id, '_ipv_processing_status', 'pending');
        update_post_meta($post_id, '_ipv_queue_id', $wpdb->insert_id);

        return $wpdb->insert_id;
    }

    /**
     * Add multiple videos to queue (bulk import)
     *
     * @param array $video_urls Array of YouTube URLs
     * @return array Results with success/error for each URL
     */
    public function bulk_add_to_queue($video_urls) {
        $results = [];

        foreach ($video_urls as $url) {
            $url = trim($url);

            if (empty($url)) {
                continue;
            }

            $result = $this->add_to_queue($url);

            if (is_wp_error($result)) {
                $results[] = [
                    'url' => $url,
                    'success' => false,
                    'message' => $result->get_error_message()
                ];
            } else {
                $results[] = [
                    'url' => $url,
                    'success' => true,
                    'queue_id' => $result
                ];
            }
        }

        return $results;
    }

    /**
     * Process queue (called by cron)
     */
    public function process_queue() {
        global $wpdb;

        $batch_size = (int)get_option('ipv_pro_batch_size', 5);

        // Get pending items
        $items = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->table_name}
            WHERE status = 'pending'
            ORDER BY created_at ASC
            LIMIT %d",
            $batch_size
        ));

        foreach ($items as $item) {
            $this->process_item($item);
        }
    }

    /**
     * Process single queue item
     *
     * Pipeline: Step 1 → Step 2 → Step 3 → Step 4
     */
    public function process_item($item) {
        global $wpdb;

        $queue_id = $item->id;
        $post_id = $item->post_id;
        $video_url = $item->video_url;

        try {
            // Update status to processing
            $this->update_queue_status($queue_id, 'processing', 'Inizio elaborazione');

            // STEP 1: Import YouTube Data
            $this->update_queue_status($queue_id, 'processing', 'Importazione dati YouTube');
            update_post_meta($post_id, '_ipv_processing_status', 'importing');

            $video_data = $this->youtube_api->fetch_video_data($video_url);

            if (is_wp_error($video_data)) {
                throw new Exception('YouTube Import: ' . $video_data->get_error_message());
            }

            // Save video metadata
            $this->save_video_metadata($post_id, $video_data);

            // STEP 2: Generate Transcript
            $this->update_queue_status($queue_id, 'processing', 'Generazione trascrizione');
            update_post_meta($post_id, '_ipv_processing_status', 'transcribing');

            $transcript = $this->supadata_api->generate_transcript($video_url);

            if (is_wp_error($transcript)) {
                throw new Exception('Trascrizione: ' . $transcript->get_error_message());
            }

            // Save transcript
            $formatted_transcript = $this->supadata_api->format_transcript($transcript);
            update_post_meta($post_id, '_ipv_transcript', $formatted_transcript);
            update_post_meta($post_id, '_ipv_transcript_stats', $this->supadata_api->get_stats($transcript));

            // STEP 3: Generate AI Content
            $this->update_queue_status($queue_id, 'processing', 'Generazione contenuti AI');
            update_post_meta($post_id, '_ipv_processing_status', 'generating_ai');

            $ai_content = $this->openai_api->generate_content($transcript, $video_data);

            if (is_wp_error($ai_content)) {
                throw new Exception('OpenAI: ' . $ai_content->get_error_message());
            }

            // Save AI content
            $this->save_ai_content($post_id, $ai_content);

            // STEP 4: Finalize Post
            $this->update_queue_status($queue_id, 'processing', 'Finalizzazione post');
            update_post_meta($post_id, '_ipv_processing_status', 'finalizing');

            $this->finalize_post($post_id, $video_data, $ai_content);

            // Mark as completed
            $this->update_queue_status($queue_id, 'completed', 'Elaborazione completata');
            update_post_meta($post_id, '_ipv_processing_status', 'completed');
            delete_post_meta($post_id, '_ipv_processing_error');

            // Log success
            $this->log_processing('success', $queue_id, $post_id, 'Video elaborato con successo');

        } catch (Exception $e) {
            // Handle error
            $error_message = $e->getMessage();

            $retry_count = (int)$item->retry_count;

            if ($retry_count < $this->max_retry) {
                // Retry
                $wpdb->update(
                    $this->table_name,
                    [
                        'status' => 'pending',
                        'current_step' => 'retry_scheduled',
                        'error_message' => $error_message,
                        'retry_count' => $retry_count + 1,
                        'updated_at' => current_time('mysql')
                    ],
                    ['id' => $queue_id],
                    ['%s', '%s', '%s', '%d', '%s'],
                    ['%d']
                );

                update_post_meta($post_id, '_ipv_processing_error', $error_message);
            } else {
                // Max retries reached - mark as failed
                $this->update_queue_status($queue_id, 'failed', 'Errore: ' . $error_message);
                update_post_meta($post_id, '_ipv_processing_status', 'failed');
                update_post_meta($post_id, '_ipv_processing_error', $error_message);
            }

            // Log error
            $this->log_processing('error', $queue_id, $post_id, $error_message);
        }
    }

    /**
     * Save video metadata to post
     */
    private function save_video_metadata($post_id, $video_data) {
        // Update post with YouTube data
        wp_update_post([
            'ID' => $post_id,
            'post_title' => $video_data['title'],
            'post_date' => date('Y-m-d H:i:s', strtotime($video_data['published_at']))
        ]);

        // Save all metadata
        $meta_fields = [
            '_ipv_video_id' => $video_data['video_id'],
            '_ipv_video_title' => $video_data['title'],
            '_ipv_video_description' => $video_data['description'],
            '_ipv_channel_id' => $video_data['channel_id'],
            '_ipv_channel_title' => $video_data['channel_title'],
            '_ipv_category_id' => $video_data['category_id'],
            '_ipv_duration' => $video_data['duration'],
            '_ipv_view_count' => $video_data['view_count'],
            '_ipv_like_count' => $video_data['like_count'],
            '_ipv_comment_count' => $video_data['comment_count'],
            '_ipv_tags' => $video_data['tags'],
            '_ipv_thumbnail_url' => $video_data['thumbnail_maxres']
        ];

        foreach ($meta_fields as $key => $value) {
            update_post_meta($post_id, $key, $value);
        }

        // Set WordPress category
        $wp_category = $this->youtube_api->get_wordpress_category($video_data['category_id']);
        wp_set_post_categories($post_id, [$wp_category]);

        // Download and set featured image
        $attachment_id = $this->youtube_api->attach_thumbnail($video_data['thumbnail_maxres'], $post_id);
        if ($attachment_id) {
            set_post_thumbnail($post_id, $attachment_id);
        }
    }

    /**
     * Save AI generated content
     */
    private function save_ai_content($post_id, $ai_content) {
        // Save full AI content
        update_post_meta($post_id, '_ipv_ai_content_full', $ai_content['full_content']);

        // Save individual sections
        $sections = [
            'title', 'description_short', 'sponsor', 'timestamps', 'topics',
            'guests', 'quotes', 'references', 'related_themes', 'related_videos',
            'links', 'social_media', 'useful_links', 'disclaimer', 'hashtags'
        ];

        foreach ($sections as $section) {
            if (isset($ai_content[$section])) {
                update_post_meta($post_id, '_ipv_ai_' . $section, $ai_content[$section]);
            }
        }
    }

    /**
     * Finalize post with all content
     */
    private function finalize_post($post_id, $video_data, $ai_content) {
        // Build post content
        $content = $this->build_post_content($video_data, $ai_content);

        // Get AI title or fallback to YouTube title
        $post_title = !empty($ai_content['title']) ? $ai_content['title'] : $video_data['title'];

        // Update post
        $post_data = [
            'ID' => $post_id,
            'post_title' => $post_title,
            'post_content' => $content,
            'post_excerpt' => !empty($ai_content['description_short']) ? $ai_content['description_short'] : ''
        ];

        // Auto-publish if enabled
        if (get_option('ipv_pro_auto_publish', false)) {
            $post_data['post_status'] = 'publish';
        }

        wp_update_post($post_data);

        // Add hashtags as tags
        if (!empty($ai_content['hashtags'])) {
            $hashtags = explode(' ', $ai_content['hashtags']);
            $tags = array_map(function($tag) {
                return ltrim($tag, '#');
            }, $hashtags);
            wp_set_post_tags($post_id, $tags);
        }
    }

    /**
     * Build final post content from all sections
     */
    private function build_post_content($video_data, $ai_content) {
        $video_id = $video_data['video_id'];

        $content = '';

        // Video embed
        $content .= "<!-- wp:embed {\"url\":\"https://www.youtube.com/watch?v={$video_id}\",\"type\":\"video\",\"providerNameSlug\":\"youtube\"} -->\n";
        $content .= "<figure class=\"wp-block-embed is-type-video is-provider-youtube wp-block-embed-youtube\">\n";
        $content .= "<div class=\"wp-block-embed__wrapper\">\n";
        $content .= "https://www.youtube.com/watch?v={$video_id}\n";
        $content .= "</div>\n";
        $content .= "</figure>\n";
        $content .= "<!-- /wp:embed -->\n\n";

        // Description
        if (!empty($ai_content['description_short'])) {
            $content .= $ai_content['description_short'] . "\n\n";
        }

        // Sponsor
        if (!empty($ai_content['sponsor'])) {
            $content .= "---\n\n";
            $content .= $ai_content['sponsor'] . "\n\n";
            $content .= "---\n\n";
        }

        // Timestamps
        if (!empty($ai_content['timestamps'])) {
            $content .= "## 📍 Capitoli del Video\n\n";
            $content .= $ai_content['timestamps'] . "\n\n";
        }

        // Topics
        if (!empty($ai_content['topics'])) {
            $content .= "## 🎯 Argomenti Trattati\n\n";
            $content .= $ai_content['topics'] . "\n\n";
        }

        // Use full AI content if available
        if (!empty($ai_content['full_content'])) {
            $content .= "\n\n---\n\n";
            $content .= $ai_content['full_content'];
        }

        return $content;
    }

    /**
     * Update queue item status
     */
    private function update_queue_status($queue_id, $status, $current_step, $error_message = null) {
        global $wpdb;

        $data = [
            'status' => $status,
            'current_step' => $current_step,
            'updated_at' => current_time('mysql')
        ];

        if ($error_message !== null) {
            $data['error_message'] = $error_message;
        }

        $wpdb->update(
            $this->table_name,
            $data,
            ['id' => $queue_id],
            ['%s', '%s', '%s'],
            ['%d']
        );
    }

    /**
     * Get queue statistics
     */
    public function get_stats() {
        global $wpdb;

        $stats = $wpdb->get_row("
            SELECT
                COUNT(*) as total,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END) as processing,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed
            FROM {$this->table_name}
        ", ARRAY_A);

        return $stats;
    }

    /**
     * Get queue items
     */
    public function get_queue_items($status = null, $limit = 50, $offset = 0) {
        global $wpdb;

        $where = $status ? $wpdb->prepare("WHERE status = %s", $status) : "";

        $items = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->table_name} {$where} ORDER BY created_at DESC LIMIT %d OFFSET %d",
            $limit,
            $offset
        ));

        return $items;
    }

    /**
     * Delete queue item
     */
    public function delete_item($queue_id) {
        global $wpdb;

        return $wpdb->delete($this->table_name, ['id' => $queue_id], ['%d']);
    }

    /**
     * Retry failed item
     */
    public function retry_item($queue_id) {
        global $wpdb;

        return $wpdb->update(
            $this->table_name,
            [
                'status' => 'pending',
                'current_step' => 'retry_manual',
                'retry_count' => 0,
                'error_message' => null,
                'updated_at' => current_time('mysql')
            ],
            ['id' => $queue_id],
            ['%s', '%s', '%d', '%s', '%s'],
            ['%d']
        );
    }

    /**
     * Log processing event
     */
    private function log_processing($type, $queue_id, $post_id, $message) {
        // Simple logging - could be expanded to custom table
        error_log(sprintf(
            '[IPV Pro] %s - Queue #%d, Post #%d: %s',
            strtoupper($type),
            $queue_id,
            $post_id,
            $message
        ));
    }
}
