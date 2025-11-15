<?php
/**
 * RSS Auto-Import System
 *
 * Monitors YouTube RSS feed and automatically imports new videos
 */

if (!defined('ABSPATH')) {
    exit;
}

class IPV_RSS_Auto_Import {

    /**
     * Get RSS feed URL from settings
     */
    private function get_feed_url() {
        return get_option('ipv_pro_rss_feed_url', '');
    }

    /**
     * Check if auto-import is enabled
     */
    public function is_enabled() {
        return (bool)get_option('ipv_pro_auto_import_enabled', false);
    }

    /**
     * Get check interval in minutes
     */
    private function get_check_interval() {
        return (int)get_option('ipv_pro_auto_import_interval', 60);
    }

    /**
     * Get max videos to import per check
     */
    private function get_max_videos() {
        return (int)get_option('ipv_pro_auto_import_max_videos', 10);
    }

    /**
     * Fetch and parse RSS feed
     */
    public function fetch_feed() {
        $feed_url = $this->get_feed_url();

        if (empty($feed_url)) {
            return new WP_Error('no_feed_url', 'RSS Feed URL non configurato');
        }

        $response = wp_remote_get($feed_url, [
            'timeout' => 30,
            'headers' => [
                'User-Agent' => 'IPV Production System Pro/2.0'
            ]
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        $body = wp_remote_retrieve_body($response);

        // Parse XML
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($body);

        if ($xml === false) {
            return new WP_Error('invalid_xml', 'Feed RSS non valido');
        }

        return $xml;
    }

    /**
     * Extract video data from feed entry
     * Enhanced to extract categories, speakers, tags, and all available metadata
     */
    private function parse_feed_entry($entry) {
        // YouTube RSS uses Atom format with media namespace
        $media = $entry->children('http://search.yahoo.com/mrss/');
        $yt = $entry->children('http://www.youtube.com/xml/schemas/2015');

        $video_id = (string)$yt->videoId;
        $video_url = "https://www.youtube.com/watch?v={$video_id}";
        $title = (string)$entry->title;
        $published = (string)$entry->published;
        $author = (string)$entry->author->name;

        // Extract description from media:group
        $description = '';
        if (isset($media->group->description)) {
            $description = (string)$media->group->description;
        }

        // Extract category from media:group
        $category = '';
        if (isset($media->group->category)) {
            $category = (string)$media->group->category;
        }

        // Extract hashtags from description
        $hashtags = $this->extract_hashtags($description);

        // Extract speaker/guest names from title
        $speakers = $this->extract_speakers_from_title($title);

        // Extract topics/keywords from description (first sentence or key phrases)
        $topics = $this->extract_topics_from_description($description);

        // Get channel ID
        $channel_id = isset($yt->channelId) ? (string)$yt->channelId : '';

        // Get thumbnail (prefer maxres if available)
        $thumbnail = '';
        if (isset($media->group->thumbnail)) {
            $thumbnail = (string)$media->group->thumbnail[0]->attributes()->url;
        }

        // Get statistics if available
        $view_count = 0;
        if (isset($media->group->community->statistics)) {
            $stats = $media->group->community->statistics->attributes();
            if (isset($stats['views'])) {
                $view_count = (int)$stats['views'];
            }
        }

        return [
            'video_id' => $video_id,
            'video_url' => $video_url,
            'title' => $title,
            'description' => $description,
            'published_at' => $published,
            'author' => $author,
            'channel_id' => $channel_id,
            'category' => $category,
            'thumbnail' => $thumbnail,
            'view_count' => $view_count,
            'hashtags' => $hashtags,
            'speakers' => $speakers,
            'topics' => $topics
        ];
    }

    /**
     * Extract hashtags from text
     */
    private function extract_hashtags($text) {
        if (empty($text)) {
            return [];
        }

        // Match all hashtags (#word)
        preg_match_all('/#(\w+)/u', $text, $matches);

        if (empty($matches[1])) {
            return [];
        }

        // Clean and filter hashtags
        $hashtags = array_map('strtolower', $matches[1]);
        $hashtags = array_unique($hashtags);
        $hashtags = array_values($hashtags);

        return $hashtags;
    }

    /**
     * Extract speaker/guest names from video title
     * Common patterns:
     * - "con [Name]"
     * - "ft. [Name]"
     * - "feat. [Name]"
     * - "featuring [Name]"
     * - "ospite: [Name]"
     * - "guest: [Name]"
     * - "[Name] interview"
     */
    private function extract_speakers_from_title($title) {
        if (empty($title)) {
            return [];
        }

        $speakers = [];

        // Pattern 1: "con [Name]"
        if (preg_match('/\bcon\s+([A-Z][a-zA-ZÀ-ÿ\s]+?)(?:\s*[\|\-]|$)/u', $title, $matches)) {
            $speakers[] = trim($matches[1]);
        }

        // Pattern 2: "ft. [Name]" or "feat. [Name]"
        if (preg_match('/\b(?:ft\.|feat\.|featuring)\s+([A-Z][a-zA-ZÀ-ÿ\s]+?)(?:\s*[\|\-]|$)/u', $title, $matches)) {
            $speakers[] = trim($matches[1]);
        }

        // Pattern 3: "ospite: [Name]" or "guest: [Name]" (improved)
        if (preg_match('/\b(?:ospite|guest):\s*([A-Z][a-zA-ZÀ-ÿ]+(?:\s+[A-Z][a-zA-ZÀ-ÿ]+)*)/ui', $title, $matches)) {
            $speakers[] = trim($matches[1]);
        }

        // Pattern 4: "[Name] - Interview" or "[Name] Interview"
        if (preg_match('/^([A-Z][a-zA-ZÀ-ÿ\s]+?)\s*[\-\:]?\s*Interview/ui', $title, $matches)) {
            $speakers[] = trim($matches[1]);
        }

        // Clean duplicates and filter out common words
        $speakers = array_unique($speakers);
        $speakers = array_filter($speakers, function($name) {
            // Remove if contains common stopwords or too short
            $stopwords = ['on', 'about', 'in', 'the', 'a', 'an'];
            $words = explode(' ', strtolower($name));
            return strlen($name) > 3 && !in_array($words[0], $stopwords);
        });
        $speakers = array_values($speakers);

        return $speakers;
    }

    /**
     * Extract topics/keywords from description
     * Takes first meaningful sentences and common topic indicators
     */
    private function extract_topics_from_description($description) {
        if (empty($description)) {
            return [];
        }

        $topics = [];

        // Extract lines that start with topic markers
        $lines = explode("\n", $description);

        foreach ($lines as $line) {
            $line = trim($line);

            // Look for lines that indicate topics
            if (preg_match('/^(?:Argomenti?|Topics?|Temi?|In questo video):\s*(.+)/i', $line, $matches)) {
                // Get the matched content and remove newlines/hashtags
                $content = preg_replace('/\n.*$/', '', $matches[1]); // Remove everything after first newline
                $content = preg_replace('/#\w+/', '', $content); // Remove hashtags

                // Split by comma, semicolon, or bullet
                $items = preg_split('/[,;•]+/', $content);
                foreach ($items as $item) {
                    $item = trim($item);
                    if (!empty($item) && strlen($item) > 3 && strlen($item) < 100) {
                        $topics[] = $item;
                    }
                }
            }
        }

        // If no explicit topics found, try to extract from first sentences
        if (empty($topics)) {
            // Get first 200 chars, split by periods
            $excerpt = substr($description, 0, 200);
            $sentences = explode('.', $excerpt);

            if (!empty($sentences[0])) {
                // Look for noun phrases (capitalized words of 3+ letters)
                preg_match_all('/\b([A-Z][a-zA-ZÀ-ÿ]{2,}(?:\s+[A-Z][a-zA-ZÀ-ÿ]{2,})*)\b/', $sentences[0], $matches);

                if (!empty($matches[1])) {
                    $topics = array_slice($matches[1], 0, 5); // Max 5 topics
                }
            }
        }

        // Clean and deduplicate
        $topics = array_unique($topics);
        $topics = array_filter($topics, function($topic) {
            return strlen($topic) > 3 && strlen($topic) < 100;
        });
        $topics = array_values($topics);

        return $topics;
    }

    /**
     * Check if video already imported
     */
    private function is_video_imported($video_id) {
        global $wpdb;

        // Check in posts meta
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_ipv_video_id' AND meta_value = %s",
            $video_id
        ));

        if ($existing) {
            return true;
        }

        // Check in queue
        $queue_table = $wpdb->prefix . 'ipv_processing_queue';
        $in_queue = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$queue_table} WHERE video_url LIKE %s",
            '%' . $video_id . '%'
        ));

        return (bool)$in_queue;
    }

    /**
     * Check for new videos and import them
     */
    public function check_and_import() {
        if (!$this->is_enabled()) {
            return ['status' => 'disabled', 'message' => 'Auto-import disabilitato'];
        }

        $feed = $this->fetch_feed();

        if (is_wp_error($feed)) {
            return [
                'status' => 'error',
                'message' => $feed->get_error_message()
            ];
        }

        $imported = 0;
        $skipped = 0;
        $errors = 0;
        $max_videos = $this->get_max_videos();

        $queue_manager = new IPV_Queue_Manager();

        // Iterate through feed entries (newest first)
        foreach ($feed->entry as $entry) {
            if ($imported >= $max_videos) {
                break;
            }

            $video_data = $this->parse_feed_entry($entry);

            // Check if already imported
            if ($this->is_video_imported($video_data['video_id'])) {
                $skipped++;
                continue;
            }

            // Add to queue with RSS metadata
            $result = $queue_manager->add_to_queue($video_data['video_url'], $video_data);

            if (is_wp_error($result)) {
                $errors++;
                error_log(sprintf(
                    '[IPV Auto-Import] Error importing %s: %s',
                    $video_data['video_url'],
                    $result->get_error_message()
                ));
            } else {
                $imported++;
                error_log(sprintf(
                    '[IPV Auto-Import] Imported: %s (Queue ID: %d) - Speakers: %s, Hashtags: %s',
                    $video_data['title'],
                    $result,
                    !empty($video_data['speakers']) ? implode(', ', $video_data['speakers']) : 'none',
                    !empty($video_data['hashtags']) ? implode(', ', $video_data['hashtags']) : 'none'
                ));
            }
        }

        $result = [
            'status' => 'success',
            'imported' => $imported,
            'skipped' => $skipped,
            'errors' => $errors,
            'message' => sprintf(
                'Auto-import completato: %d nuovi video, %d già presenti, %d errori',
                $imported,
                $skipped,
                $errors
            )
        ];

        // Send email notification if enabled and new videos imported
        if ($imported > 0 && get_option('ipv_pro_auto_import_email_notifications', false)) {
            $this->send_notification($result);
        }

        // Update last check timestamp
        update_option('ipv_pro_auto_import_last_check', current_time('mysql'));

        return $result;
    }

    /**
     * Send email notification
     */
    private function send_notification($result) {
        $to = get_option('ipv_pro_auto_import_notification_email', get_option('admin_email'));
        $config = IPV_Channel_Config::get_config();

        $subject = sprintf(
            '[%s] %d nuovi video importati automaticamente',
            $config['channel_name'],
            $result['imported']
        );

        $message = sprintf(
            "Ciao,\n\n" .
            "Il sistema di auto-import ha rilevato e importato %d nuovi video dal canale %s.\n\n" .
            "Dettagli:\n" .
            "- Video importati: %d\n" .
            "- Video già presenti: %d\n" .
            "- Errori: %d\n\n" .
            "I video sono stati aggiunti alla coda di elaborazione e saranno processati automaticamente.\n\n" .
            "Puoi monitorare lo stato qui:\n" .
            "%s\n\n" .
            "---\n" .
            "IPV Production System Pro\n" .
            "Auto-Import System",
            $result['imported'],
            $config['channel_name'],
            $result['imported'],
            $result['skipped'],
            $result['errors'],
            admin_url('admin.php?page=ipv-video-manager')
        );

        wp_mail($to, $subject, $message);
    }

    /**
     * Get last check timestamp
     */
    public function get_last_check() {
        return get_option('ipv_pro_auto_import_last_check', null);
    }

    /**
     * Get next scheduled check time
     */
    public function get_next_check() {
        $last_check = $this->get_last_check();

        if (!$last_check) {
            return 'Mai eseguito';
        }

        $interval = $this->get_check_interval();
        $next_check = strtotime($last_check) + ($interval * 60);

        return date('Y-m-d H:i:s', $next_check);
    }

    /**
     * Manual trigger check
     */
    public function manual_check() {
        return $this->check_and_import();
    }

    /**
     * Get feed statistics
     */
    public function get_feed_stats() {
        $feed = $this->fetch_feed();

        if (is_wp_error($feed)) {
            return [
                'status' => 'error',
                'message' => $feed->get_error_message()
            ];
        }

        $total_videos = count($feed->entry);
        $latest_video = $this->parse_feed_entry($feed->entry[0]);

        return [
            'status' => 'ok',
            'total_in_feed' => $total_videos,
            'latest_video' => $latest_video,
            'feed_title' => (string)$feed->title,
            'feed_updated' => (string)$feed->updated
        ];
    }

    /**
     * Test feed connection
     */
    public function test_feed() {
        $feed_url = $this->get_feed_url();

        if (empty($feed_url)) {
            return [
                'success' => false,
                'message' => 'RSS Feed URL non configurato'
            ];
        }

        $feed = $this->fetch_feed();

        if (is_wp_error($feed)) {
            return [
                'success' => false,
                'message' => 'Errore: ' . $feed->get_error_message()
            ];
        }

        $stats = $this->get_feed_stats();

        return [
            'success' => true,
            'message' => sprintf(
                'Feed OK! Trovati %d video. Ultimo: "%s"',
                $stats['total_in_feed'],
                $stats['latest_video']['title']
            ),
            'stats' => $stats
        ];
    }
}
