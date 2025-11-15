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
     * @param array $rss_metadata Optional RSS feed metadata (categories, speakers, hashtags, etc.)
     * @return int|WP_Error Queue item ID or error
     */
    public function add_to_queue($video_url, $rss_metadata = []) {
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

        // Create draft post with CPT ipv_video
        $post_id = wp_insert_post([
            'post_title' => !empty($rss_metadata['title']) ? $rss_metadata['title'] : 'Video YouTube (processing...)',
            'post_status' => 'draft',
            'post_type' => 'ipv_video'
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

        // Save RSS metadata if provided
        if (!empty($rss_metadata)) {
            update_post_meta($post_id, '_ipv_rss_metadata', $rss_metadata);

            // Apply early taxonomy assignment from RSS data
            $this->apply_taxonomies_from_rss($post_id, $rss_metadata);
        }

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
     * Save AI generated content (v1.9.4 - 18 sections)
     */
    private function save_ai_content($post_id, $ai_content) {
        // Save full AI content
        update_post_meta($post_id, '_ipv_ai_content_full', $ai_content['full_content']);

        // Save individual sections (v1.9.4)
        $sections = [
            'title', 'description', 'sponsor', 'timestamps', 'topics',
            'guests', 'persone_menzionate', 'regia', 'eventi', 'quotes',
            'references', 'links', 'related_themes', 'related_videos',
            'donazioni', 'abbonati', 'social_media', 'contatti', 'temi_canale', 'hashtags'
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
        $content = $this->build_post_content($post_id, $video_data, $ai_content);

        // Get AI title or fallback to YouTube title
        $post_title = !empty($ai_content['title']) ? $ai_content['title'] : $video_data['title'];

        // Update post
        $post_data = [
            'ID' => $post_id,
            'post_title' => $post_title,
            'post_content' => $content,
            'post_excerpt' => !empty($ai_content['description']) ? $ai_content['description'] : ''
        ];

        // Auto-publish if enabled
        if (get_option('ipv_pro_auto_publish', false)) {
            $post_data['post_status'] = 'publish';
        }

        wp_update_post($post_data);

        // Apply taxonomies from AI content
        $this->apply_taxonomies_from_ai($post_id, $ai_content);
    }

    /**
     * Apply taxonomies from AI content
     */
    private function apply_taxonomies_from_ai($post_id, $ai_content) {
        // Extract and assign topics
        if (!empty($ai_content['topics'])) {
            $topics = $this->extract_terms_from_text($ai_content['topics']);
            if (!empty($topics)) {
                wp_set_object_terms($post_id, $topics, 'ipv_topic');
            }
        }

        // Extract and assign guests
        if (!empty($ai_content['guests'])) {
            $guests = $this->extract_terms_from_text($ai_content['guests']);
            if (!empty($guests)) {
                wp_set_object_terms($post_id, $guests, 'ipv_guest');
            }
        }

        // Extract and assign channel themes
        if (!empty($ai_content['temi_canale'])) {
            $themes = $this->extract_terms_from_text($ai_content['temi_canale']);
            if (!empty($themes)) {
                wp_set_object_terms($post_id, $themes, 'ipv_channel_theme');
            }
        }

        // Extract and assign hashtags as post tags
        if (!empty($ai_content['hashtags'])) {
            $hashtags = explode(' ', $ai_content['hashtags']);
            $tags = array_map(function($tag) {
                return ltrim($tag, '#');
            }, $hashtags);
            $tags = array_filter($tags);
            if (!empty($tags)) {
                wp_set_post_tags($post_id, $tags);
            }
        }
    }

    /**
     * Extract terms from text (comma or newline separated)
     */
    private function extract_terms_from_text($text) {
        // Remove markdown list markers
        $text = preg_replace('/^[\-\*\+]\s+/m', '', $text);
        $text = preg_replace('/^\d+\.\s+/m', '', $text);

        // Split by comma or newline
        $items = preg_split('/[,\n]+/', $text);

        // Clean and filter
        $items = array_map('trim', $items);
        $items = array_filter($items);
        $items = array_unique($items);

        return array_values($items);
    }

    /**
     * Apply taxonomies from RSS feed metadata (early assignment)
     * This runs BEFORE full YouTube API import and AI processing
     *
     * @param int $post_id WordPress post ID
     * @param array $rss_metadata RSS feed data (speakers, hashtags, topics, category)
     */
    private function apply_taxonomies_from_rss($post_id, $rss_metadata) {
        // Assign speakers/guests to ipv_guest taxonomy AND categories
        if (!empty($rss_metadata['speakers']) && is_array($rss_metadata['speakers'])) {
            $speakers = $rss_metadata['speakers'];

            // Assign to ipv_guest taxonomy
            wp_set_object_terms($post_id, $speakers, 'ipv_guest', false);

            // Also create/assign WordPress categories for speakers
            $speaker_cat_ids = [];
            foreach ($speakers as $speaker) {
                $cat = get_term_by('name', $speaker, 'category');
                if (!$cat) {
                    // Create category if doesn't exist
                    $result = wp_insert_term($speaker, 'category');
                    if (!is_wp_error($result)) {
                        $speaker_cat_ids[] = $result['term_id'];
                    }
                } else {
                    $speaker_cat_ids[] = $cat->term_id;
                }
            }

            if (!empty($speaker_cat_ids)) {
                wp_set_post_categories($post_id, $speaker_cat_ids, true); // true = append
            }
        }

        // Assign topics to ipv_topic taxonomy
        if (!empty($rss_metadata['topics']) && is_array($rss_metadata['topics'])) {
            wp_set_object_terms($post_id, $rss_metadata['topics'], 'ipv_topic', false);
        }

        // Assign hashtags to WordPress tags
        if (!empty($rss_metadata['hashtags']) && is_array($rss_metadata['hashtags'])) {
            wp_set_post_tags($post_id, $rss_metadata['hashtags'], false);
        }

        // Assign RSS category to WordPress category
        if (!empty($rss_metadata['category'])) {
            $category = $rss_metadata['category'];

            // Try to find or create category
            $cat = get_term_by('name', $category, 'category');
            if (!$cat) {
                // Create category if doesn't exist
                $result = wp_insert_term($category, 'category');
                if (!is_wp_error($result)) {
                    wp_set_post_categories($post_id, [$result['term_id']], true); // append
                }
            } else {
                wp_set_post_categories($post_id, [$cat->term_id], true); // append
            }
        }

        // Assign channel name to ipv_channel_theme taxonomy
        if (!empty($rss_metadata['author'])) {
            wp_set_object_terms($post_id, [$rss_metadata['author']], 'ipv_channel_theme', false);
        }

        // Log taxonomy assignment
        error_log(sprintf(
            '[IPV RSS Auto-Taxonomy] Post %d - Assigned: Speakers=%d, Topics=%d, Tags=%d, Categories=%s',
            $post_id,
            !empty($rss_metadata['speakers']) ? count($rss_metadata['speakers']) : 0,
            !empty($rss_metadata['topics']) ? count($rss_metadata['topics']) : 0,
            !empty($rss_metadata['hashtags']) ? count($rss_metadata['hashtags']) : 0,
            !empty($rss_metadata['category']) ? $rss_metadata['category'] : 'none'
        ));
    }

    /**
     * Build final post content from all sections (Notion-style format)
     */
    private function build_post_content($post_id, $video_data, $ai_content) {
        $video_id = $video_data['video_id'];
        $video_url = "https://www.youtube.com/watch?v={$video_id}";

        // Get transcript
        $transcript = get_post_meta($post_id, '_ipv_transcript', true);

        // Build Notion-style markdown content
        $content = '';

        // Video embed (Gutenberg block)
        $content .= "<!-- wp:embed {\"url\":\"{$video_url}\",\"type\":\"video\",\"providerNameSlug\":\"youtube\"} -->\n";
        $content .= "<figure class=\"wp-block-embed is-type-video is-provider-youtube wp-block-embed-youtube\">\n";
        $content .= "<div class=\"wp-block-embed__wrapper\">\n";
        $content .= "{$video_url}\n";
        $content .= "</div>\n";
        $content .= "</figure>\n";
        $content .= "<!-- /wp:embed -->\n\n";

        $content .= "---\n\n";

        // Video Info Box
        $content .= "**🎬 Video URL:** [{$video_url}]({$video_url})\n";
        $content .= "**📹 Video ID:** `{$video_id}`\n";
        if (!empty($video_data['channel_title'])) {
            $content .= "**📺 Canale:** {$video_data['channel_title']}\n";
        }
        if (!empty($video_data['duration'])) {
            $content .= "**⏱️ Durata:** {$this->format_duration($video_data['duration'])}\n";
        }
        $content .= "\n---\n\n";

        // Description
        if (!empty($ai_content['description'])) {
            $content .= "## 📝 Descrizione\n\n";
            $content .= $ai_content['description'] . "\n\n";
        }

        // Topics
        if (!empty($ai_content['topics'])) {
            $content .= "## 🎯 Argomenti Trattati\n\n";
            $content .= $ai_content['topics'] . "\n\n";
        }

        // Guests
        if (!empty($ai_content['guests'])) {
            $content .= "## 👥 Ospiti\n\n";
            $content .= $ai_content['guests'] . "\n\n";
        }

        // Timestamps
        if (!empty($ai_content['timestamps'])) {
            $content .= "## ⏱️ Capitoli del Video\n\n";
            $content .= $ai_content['timestamps'] . "\n\n";
        }

        // Sponsor
        if (!empty($ai_content['sponsor'])) {
            $content .= "## 💼 Sponsor\n\n";
            $content .= $ai_content['sponsor'] . "\n\n";
        }

        // Quotes
        if (!empty($ai_content['quotes'])) {
            $content .= "## 💬 Citazioni Principali\n\n";
            $content .= $ai_content['quotes'] . "\n\n";
        }

        // Events
        if (!empty($ai_content['eventi'])) {
            $content .= "## 📅 Eventi Menzionati\n\n";
            $content .= $ai_content['eventi'] . "\n\n";
        }

        // People Mentioned
        if (!empty($ai_content['persone_menzionate'])) {
            $content .= "## 👤 Persone Menzionate\n\n";
            $content .= $ai_content['persone_menzionate'] . "\n\n";
        }

        // References
        if (!empty($ai_content['references'])) {
            $content .= "## 🔗 Riferimenti e Link\n\n";
            $content .= $ai_content['references'] . "\n\n";
        }

        // Related Videos
        if (!empty($ai_content['related_videos'])) {
            $content .= "## 📺 Video Correlati\n\n";
            $content .= $ai_content['related_videos'] . "\n\n";
        }

        // Full AI Content (if available and different from sections)
        if (!empty($ai_content['full_content'])) {
            $content .= "## 📄 Contenuto Completo\n\n";
            $content .= $ai_content['full_content'] . "\n\n";
        }

        // TRANSCRIPT (always include if available)
        if (!empty($transcript)) {
            $content .= "---\n\n";
            $content .= "## 📄 Trascrizione Completa\n\n";
            $content .= "```\n";
            $content .= $transcript . "\n";
            $content .= "```\n\n";
        }

        $content .= "---\n\n";
        $content .= "*Contenuto generato automaticamente dal sistema editoriale*\n";

        return $content;
    }

    /**
     * Format duration from ISO 8601 to readable format
     */
    private function format_duration($duration) {
        if (empty($duration)) {
            return '—';
        }

        // Parse ISO 8601 duration (PT1H2M3S)
        preg_match('/PT(?:(\d+)H)?(?:(\d+)M)?(?:(\d+)S)?/', $duration, $matches);

        $hours = isset($matches[1]) ? (int)$matches[1] : 0;
        $minutes = isset($matches[2]) ? (int)$matches[2] : 0;
        $seconds = isset($matches[3]) ? (int)$matches[3] : 0;

        if ($hours > 0) {
            return sprintf('%d:%02d:%02d', $hours, $minutes, $seconds);
        } else {
            return sprintf('%d:%02d', $minutes, $seconds);
        }
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
     * Auto-Repair Orphan Videos
     *
     * Automatically finds and repairs videos that exist as posts but are not in the queue.
     * This solves the problem of "invisible videos" after plugin updates or incomplete imports.
     *
     * @return array Results with count of repaired videos
     */
    public function auto_repair_orphan_videos() {
        global $wpdb;

        // Check if auto-repair is enabled
        if (!get_option('ipv_pro_auto_repair_orphans', 1)) {
            return [
                'enabled' => false,
                'repaired' => 0,
                'message' => 'Auto-repair disabilitato nelle impostazioni'
            ];
        }

        error_log('[IPV Auto-Repair] Starting automatic orphan video repair...');

        // Find orphan posts (posts without queue entry)
        $orphan_posts = $wpdb->get_results("
            SELECT p.ID, p.post_title
            FROM {$wpdb->posts} p
            LEFT JOIN {$this->table_name} q ON p.ID = q.post_id
            WHERE p.post_type = 'ipv_video'
            AND q.id IS NULL
            LIMIT 100
        ");

        if (empty($orphan_posts)) {
            error_log('[IPV Auto-Repair] No orphan videos found. Database is clean!');
            return [
                'enabled' => true,
                'repaired' => 0,
                'message' => 'Nessun video orfano trovato'
            ];
        }

        $repaired = 0;
        $errors = 0;

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
                error_log(sprintf(
                    '[IPV Auto-Repair] SKIP - Cannot repair post %d ("%s"): no video URL found',
                    $post->ID,
                    $post->post_title
                ));
                $errors++;
                continue;
            }

            // Re-add to queue as completed (to make it visible)
            $result = $wpdb->insert(
                $this->table_name,
                [
                    'post_id' => $post->ID,
                    'video_url' => $video_url,
                    'status' => 'completed',
                    'current_step' => 'auto_repaired',
                    'retry_count' => 0,
                    'created_at' => current_time('mysql'),
                    'updated_at' => current_time('mysql')
                ],
                ['%d', '%s', '%s', '%s', '%d', '%s', '%s']
            );

            if ($result !== false) {
                $repaired++;
                error_log(sprintf(
                    '[IPV Auto-Repair] ✅ REPAIRED - Post %d ("%s") now visible in Video Manager',
                    $post->ID,
                    $post->post_title
                ));
            } else {
                $errors++;
                error_log(sprintf(
                    '[IPV Auto-Repair] ❌ ERROR - Failed to repair post %d ("%s"): %s',
                    $post->ID,
                    $post->post_title,
                    $wpdb->last_error
                ));
            }
        }

        $message = sprintf(
            'Auto-repair completato: %d video riparati, %d errori',
            $repaired,
            $errors
        );

        error_log("[IPV Auto-Repair] {$message}");

        return [
            'enabled' => true,
            'repaired' => $repaired,
            'errors' => $errors,
            'message' => $message
        ];
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
