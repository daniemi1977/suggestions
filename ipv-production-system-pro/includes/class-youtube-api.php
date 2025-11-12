<?php
/**
 * YouTube API Integration
 *
 * Handles fetching video metadata from YouTube Data API v3
 */

if (!defined('ABSPATH')) {
    exit;
}

class IPV_YouTube_API {

    /**
     * API endpoint
     */
    private $api_endpoint = 'https://www.googleapis.com/youtube/v3/videos';

    /**
     * Get API key from settings
     */
    private function get_api_key() {
        return get_option('ipv_pro_youtube_api_key', '');
    }

    /**
     * Extract video ID from YouTube URL
     */
    public function extract_video_id($url) {
        $pattern = '/(?:youtube\.com\/(?:[^\/\n\s]+\/\S+\/|(?:v|e(?:mbed)?)\/|\S*?[?&]v=)|youtu\.be\/)([a-zA-Z0-9_-]{11})/';

        if (preg_match($pattern, $url, $matches)) {
            return $matches[1];
        }

        return false;
    }

    /**
     * Fetch video data from YouTube API
     *
     * @param string $video_url YouTube video URL
     * @return array|WP_Error Video data or error
     */
    public function fetch_video_data($video_url) {
        $api_key = $this->get_api_key();

        if (empty($api_key)) {
            return new WP_Error('no_api_key', 'YouTube API key non configurata');
        }

        // Extract video ID
        $video_id = $this->extract_video_id($video_url);

        if (!$video_id) {
            return new WP_Error('invalid_url', 'URL YouTube non valido');
        }

        // Build API request
        $request_url = add_query_arg([
            'part' => 'snippet,contentDetails,statistics',
            'id' => $video_id,
            'key' => $api_key
        ], $this->api_endpoint);

        // Make request
        $response = wp_remote_get($request_url, [
            'timeout' => 30,
            'headers' => [
                'Accept' => 'application/json'
            ]
        ]);

        // Check for errors
        if (is_wp_error($response)) {
            return new WP_Error('api_error', 'Errore connessione API: ' . $response->get_error_message());
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if ($response_code !== 200) {
            $error_message = isset($data['error']['message']) ? $data['error']['message'] : 'Errore sconosciuto';
            return new WP_Error('api_error', 'YouTube API error: ' . $error_message);
        }

        if (empty($data['items'])) {
            return new WP_Error('not_found', 'Video non trovato su YouTube');
        }

        // Parse video data
        $video = $data['items'][0];

        return [
            'video_id' => $video_id,
            'title' => $video['snippet']['title'],
            'description' => $video['snippet']['description'],
            'published_at' => $video['snippet']['publishedAt'],
            'channel_id' => $video['snippet']['channelId'],
            'channel_title' => $video['snippet']['channelTitle'],
            'category_id' => $video['snippet']['categoryId'],
            'tags' => isset($video['snippet']['tags']) ? $video['snippet']['tags'] : [],
            'duration' => $this->parse_duration($video['contentDetails']['duration']),
            'thumbnail_default' => $video['snippet']['thumbnails']['default']['url'],
            'thumbnail_medium' => $video['snippet']['thumbnails']['medium']['url'],
            'thumbnail_high' => $video['snippet']['thumbnails']['high']['url'],
            'thumbnail_maxres' => isset($video['snippet']['thumbnails']['maxres']) ? $video['snippet']['thumbnails']['maxres']['url'] : $video['snippet']['thumbnails']['high']['url'],
            'view_count' => isset($video['statistics']['viewCount']) ? (int)$video['statistics']['viewCount'] : 0,
            'like_count' => isset($video['statistics']['likeCount']) ? (int)$video['statistics']['likeCount'] : 0,
            'comment_count' => isset($video['statistics']['commentCount']) ? (int)$video['statistics']['commentCount'] : 0,
        ];
    }

    /**
     * Parse ISO 8601 duration to seconds
     */
    private function parse_duration($duration) {
        preg_match('/PT(?:(\d+)H)?(?:(\d+)M)?(?:(\d+)S)?/', $duration, $matches);

        $hours = isset($matches[1]) ? (int)$matches[1] : 0;
        $minutes = isset($matches[2]) ? (int)$matches[2] : 0;
        $seconds = isset($matches[3]) ? (int)$matches[3] : 0;

        return ($hours * 3600) + ($minutes * 60) + $seconds;
    }

    /**
     * Test API connection
     *
     * @return array Result with status and message
     */
    public function test_connection() {
        $api_key = $this->get_api_key();

        if (empty($api_key)) {
            return [
                'success' => false,
                'message' => 'API key non configurata'
            ];
        }

        // Test with a known video ID (YouTube's test video)
        $test_url = 'https://www.youtube.com/watch?v=jNQXAC9IVRw'; // "Me at the zoo" - first YouTube video

        $result = $this->fetch_video_data($test_url);

        if (is_wp_error($result)) {
            return [
                'success' => false,
                'message' => $result->get_error_message()
            ];
        }

        return [
            'success' => true,
            'message' => 'Connessione riuscita! API key valida.'
        ];
    }

    /**
     * Get WordPress category from YouTube category
     */
    public function get_wordpress_category($youtube_category_id) {
        $mapping = get_option('ipv_pro_category_mapping', []);

        if (isset($mapping[$youtube_category_id]) && $mapping[$youtube_category_id] > 0) {
            return $mapping[$youtube_category_id];
        }

        return get_option('ipv_pro_default_category', 1);
    }

    /**
     * Download and attach thumbnail to post
     *
     * @param string $image_url Thumbnail URL
     * @param int $post_id Post ID
     * @return int|false Attachment ID or false on failure
     */
    public function attach_thumbnail($image_url, $post_id) {
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');

        // Download image
        $tmp = download_url($image_url);

        if (is_wp_error($tmp)) {
            return false;
        }

        // Prepare file array
        $file_array = [
            'name' => basename($image_url) . '.jpg',
            'tmp_name' => $tmp
        ];

        // Upload to media library
        $attachment_id = media_handle_sideload($file_array, $post_id);

        // Clean up temp file
        if (is_wp_error($attachment_id)) {
            @unlink($file_array['tmp_name']);
            return false;
        }

        return $attachment_id;
    }
}
