<?php
/**
 * Admin Actions Handler
 *
 * Gestisce le azioni admin come rigenerazione AI, trascrizione e esportazione Notion
 */

if (!defined('ABSPATH')) {
    exit;
}

class IPV_Admin_Actions {

    /**
     * Constructor
     */
    public function __construct() {
        // Register admin action handlers
        add_action('admin_post_ipv_regenerate_ai', [$this, 'handle_regenerate_ai']);
        add_action('admin_post_ipv_regenerate_transcript', [$this, 'handle_regenerate_transcript']);
        add_action('admin_post_ipv_export_notion', [$this, 'handle_export_notion']);

        // Register bulk action notices
        add_action('admin_notices', [$this, 'show_bulk_action_notices']);
    }

    /**
     * Handle regenerate AI content action
     */
    public function handle_regenerate_ai() {
        // Check user capability
        if (!current_user_can('manage_options')) {
            wp_die('Non hai i permessi per eseguire questa azione.');
        }

        // Get post ID
        $post_id = isset($_GET['post_id']) ? intval($_GET['post_id']) : 0;

        if (!$post_id) {
            wp_die('ID post non valido.');
        }

        // Verify nonce
        if (!wp_verify_nonce($_GET['_wpnonce'], 'ipv_regenerate_ai_' . $post_id)) {
            wp_die('Nonce verification failed.');
        }

        // Get transcript
        $transcript = get_post_meta($post_id, '_ipv_transcript', true);

        if (!$transcript) {
            wp_redirect(add_query_arg([
                'post' => $post_id,
                'action' => 'edit',
                'ipv_error' => 'no_transcript'
            ], admin_url('post.php')));
            exit;
        }

        // Get video data
        $video_data = [
            'video_id' => get_post_meta($post_id, '_ipv_video_id', true),
            'title' => get_post_meta($post_id, '_ipv_video_title', true),
            'description' => get_post_meta($post_id, '_ipv_video_description', true),
            'channel_title' => get_post_meta($post_id, '_ipv_channel_title', true),
            'channel_id' => get_post_meta($post_id, '_ipv_channel_id', true),
            'category_id' => get_post_meta($post_id, '_ipv_category_id', true),
            'tags' => get_post_meta($post_id, '_ipv_tags', true),
            'duration' => get_post_meta($post_id, '_ipv_duration', true),
            'published_at' => get_post_meta($post_id, '_ipv_published_at', true)
        ];

        // Generate new AI content
        $openai_api = new IPV_OpenAI_API();
        $ai_content = $openai_api->generate_content($transcript, $video_data);

        if (is_wp_error($ai_content)) {
            wp_redirect(add_query_arg([
                'post' => $post_id,
                'action' => 'edit',
                'ipv_error' => 'ai_generation_failed',
                'error_message' => urlencode($ai_content->get_error_message())
            ], admin_url('post.php')));
            exit;
        }

        // Save AI content
        $this->save_ai_content($post_id, $ai_content);

        // Update post with new content
        $content = $this->build_post_content($video_data, $ai_content);

        wp_update_post([
            'ID' => $post_id,
            'post_title' => $ai_content['title'] ?? get_the_title($post_id),
            'post_content' => $content,
            'post_excerpt' => $ai_content['description'] ?? ''
        ]);

        // Apply taxonomies
        $this->apply_taxonomies_from_ai($post_id, $ai_content);

        // Redirect back with success message
        wp_redirect(add_query_arg([
            'post' => $post_id,
            'action' => 'edit',
            'ipv_message' => 'ai_regenerated'
        ], admin_url('post.php')));
        exit;
    }

    /**
     * Handle regenerate transcript action
     */
    public function handle_regenerate_transcript() {
        // Check user capability
        if (!current_user_can('manage_options')) {
            wp_die('Non hai i permessi per eseguire questa azione.');
        }

        // Get post ID
        $post_id = isset($_GET['post_id']) ? intval($_GET['post_id']) : 0;

        if (!$post_id) {
            wp_die('ID post non valido.');
        }

        // Verify nonce
        if (!wp_verify_nonce($_GET['_wpnonce'], 'ipv_regenerate_transcript_' . $post_id)) {
            wp_die('Nonce verification failed.');
        }

        // Get video URL
        $video_url = get_post_meta($post_id, '_ipv_video_url', true);

        if (!$video_url) {
            wp_redirect(add_query_arg([
                'post' => $post_id,
                'action' => 'edit',
                'ipv_error' => 'no_video_url'
            ], admin_url('post.php')));
            exit;
        }

        // Generate new transcript
        $supadata_api = new IPV_SupaData_API();
        $transcript = $supadata_api->generate_transcript($video_url);

        if (is_wp_error($transcript)) {
            wp_redirect(add_query_arg([
                'post' => $post_id,
                'action' => 'edit',
                'ipv_error' => 'transcript_generation_failed',
                'error_message' => urlencode($transcript->get_error_message())
            ], admin_url('post.php')));
            exit;
        }

        // Save transcript
        $formatted_transcript = $supadata_api->format_transcript($transcript);
        update_post_meta($post_id, '_ipv_transcript', $formatted_transcript);
        update_post_meta($post_id, '_ipv_transcript_stats', $supadata_api->get_stats($transcript));

        // Redirect back with success message
        wp_redirect(add_query_arg([
            'post' => $post_id,
            'action' => 'edit',
            'ipv_message' => 'transcript_regenerated'
        ], admin_url('post.php')));
        exit;
    }

    /**
     * Handle export to Notion action
     */
    public function handle_export_notion() {
        // Check user capability
        if (!current_user_can('manage_options')) {
            wp_die('Non hai i permessi per eseguire questa azione.');
        }

        // Get post ID
        $post_id = isset($_GET['post_id']) ? intval($_GET['post_id']) : 0;

        if (!$post_id) {
            wp_die('ID post non valido.');
        }

        // Verify nonce
        if (!wp_verify_nonce($_GET['_wpnonce'], 'ipv_export_notion_' . $post_id)) {
            wp_die('Nonce verification failed.');
        }

        // Get post data
        $post = get_post($post_id);
        $video_data = [
            'video_id' => get_post_meta($post_id, '_ipv_video_id', true),
            'url' => get_post_meta($post_id, '_ipv_video_url', true),
            'title' => $post->post_title,
            'description' => get_post_meta($post_id, '_ipv_ai_description', true),
            'transcript' => get_post_meta($post_id, '_ipv_transcript', true),
            'topics' => get_post_meta($post_id, '_ipv_ai_topics', true),
            'guests' => get_post_meta($post_id, '_ipv_ai_guests', true),
            'timestamps' => get_post_meta($post_id, '_ipv_ai_timestamps', true),
            'quotes' => get_post_meta($post_id, '_ipv_ai_quotes', true),
            'references' => get_post_meta($post_id, '_ipv_ai_references', true),
            'sponsor' => get_post_meta($post_id, '_ipv_ai_sponsor', true)
        ];

        // Build Notion-formatted export
        $notion_export = $this->build_notion_export($video_data);

        // Save export
        update_post_meta($post_id, '_ipv_notion_export', $notion_export);

        // Download as file
        header('Content-Type: text/markdown; charset=utf-8');
        header('Content-Disposition: attachment; filename="notion-export-' . $video_data['video_id'] . '.md"');
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');

        echo $notion_export;
        exit;
    }

    /**
     * Show bulk action notices
     */
    public function show_bulk_action_notices() {
        // Success message for AI regeneration
        if (isset($_GET['ipv_message']) && $_GET['ipv_message'] === 'ai_regenerated') {
            echo '<div class="notice notice-success is-dismissible"><p><strong>✅ Contenuti AI rigenerati con successo!</strong></p></div>';
        }

        // Success message for transcript regeneration
        if (isset($_GET['ipv_message']) && $_GET['ipv_message'] === 'transcript_regenerated') {
            echo '<div class="notice notice-success is-dismissible"><p><strong>✅ Trascrizione rigenerata con successo!</strong></p></div>';
        }

        // Bulk action success
        if (isset($_GET['bulk_action']) && isset($_GET['processed'])) {
            $action = sanitize_text_field($_GET['bulk_action']);
            $processed = intval($_GET['processed']);

            $messages = [
                'regenerate_content' => sprintf('✅ Contenuti AI rigenerati per %d video.', $processed),
                'regenerate_transcript' => sprintf('✅ Trascrizione rigenerata per %d video.', $processed),
                'export_notion' => sprintf('✅ %d video esportati per Notion.', $processed)
            ];

            if (isset($messages[$action])) {
                echo '<div class="notice notice-success is-dismissible"><p><strong>' . esc_html($messages[$action]) . '</strong></p></div>';
            }
        }

        // Error messages
        if (isset($_GET['ipv_error'])) {
            $error = sanitize_text_field($_GET['ipv_error']);
            $error_message = isset($_GET['error_message']) ? urldecode($_GET['error_message']) : '';

            $errors = [
                'no_transcript' => '⚠️ Nessuna trascrizione disponibile per rigenerare l\'AI.',
                'no_video_url' => '⚠️ URL video non disponibile.',
                'ai_generation_failed' => '❌ Errore nella generazione AI: ' . esc_html($error_message),
                'transcript_generation_failed' => '❌ Errore nella generazione trascrizione: ' . esc_html($error_message)
            ];

            if (isset($errors[$error])) {
                echo '<div class="notice notice-error is-dismissible"><p><strong>' . $errors[$error] . '</strong></p></div>';
            }
        }
    }

    /**
     * Save AI content
     */
    private function save_ai_content($post_id, $ai_content) {
        // Save full AI content
        update_post_meta($post_id, '_ipv_ai_content_full', $ai_content['full_content']);

        // Save individual sections
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
     * Build post content from AI sections
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
        if (!empty($ai_content['description'])) {
            $content .= $ai_content['description'] . "\n\n";
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
     * Extract terms from text
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
     * Build Notion export
     */
    private function build_notion_export($video_data) {
        $export = "# " . $video_data['title'] . "\n\n";
        $export .= "**🎬 Video URL:** " . $video_data['url'] . "\n";
        $export .= "**📹 Video ID:** `" . $video_data['video_id'] . "`\n\n";
        $export .= "---\n\n";

        if ($video_data['description']) {
            $export .= "## 📝 Descrizione\n\n";
            $export .= $video_data['description'] . "\n\n";
        }

        if ($video_data['topics']) {
            $export .= "## 🎯 Argomenti\n\n";
            $export .= $video_data['topics'] . "\n\n";
        }

        if ($video_data['guests']) {
            $export .= "## 👥 Ospiti\n\n";
            $export .= $video_data['guests'] . "\n\n";
        }

        if ($video_data['timestamps']) {
            $export .= "## ⏱️ Capitoli\n\n";
            $export .= $video_data['timestamps'] . "\n\n";
        }

        if ($video_data['quotes']) {
            $export .= "## 💬 Citazioni\n\n";
            $export .= $video_data['quotes'] . "\n\n";
        }

        if ($video_data['sponsor']) {
            $export .= "## 💼 Sponsor\n\n";
            $export .= $video_data['sponsor'] . "\n\n";
        }

        if ($video_data['references']) {
            $export .= "## 🔗 Riferimenti\n\n";
            $export .= $video_data['references'] . "\n\n";
        }

        if ($video_data['transcript']) {
            $export .= "## 📄 Trascrizione Completa\n\n";
            $export .= "```\n";
            $export .= $video_data['transcript'] . "\n";
            $export .= "```\n\n";
        }

        $export .= "---\n\n";
        $export .= "*Esportato da IPV Production System Pro*\n";

        return $export;
    }
}
