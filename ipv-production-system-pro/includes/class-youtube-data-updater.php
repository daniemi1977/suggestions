<?php
/**
 * YouTube Data Updater
 *
 * Aggiorna giornalmente i dati dei video (views, likes, comments) da YouTube Data v3 API
 */

if (!defined('ABSPATH')) {
    exit;
}

class IPV_YouTube_Data_Updater {

    /**
     * Constructor
     */
    public function __construct() {
        // Hook cron job
        add_action('ipv_pro_update_youtube_data', [$this, 'update_all_videos_data']);
    }

    /**
     * Schedule daily cron job
     */
    public static function schedule_cron() {
        if (!wp_next_scheduled('ipv_pro_update_youtube_data')) {
            wp_schedule_event(time(), 'daily', 'ipv_pro_update_youtube_data');
        }
    }

    /**
     * Unschedule cron job
     */
    public static function unschedule_cron() {
        $timestamp = wp_next_scheduled('ipv_pro_update_youtube_data');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'ipv_pro_update_youtube_data');
        }
    }

    /**
     * Update YouTube data for all published videos
     */
    public function update_all_videos_data() {
        $args = [
            'post_type' => 'ipv_video',
            'post_status' => ['publish', 'draft'],
            'posts_per_page' => -1,
            'meta_query' => [
                [
                    'key' => '_ipv_video_id',
                    'compare' => 'EXISTS'
                ]
            ]
        ];

        $videos = get_posts($args);
        $updated_count = 0;
        $error_count = 0;

        foreach ($videos as $video) {
            $result = $this->update_video_data($video->ID);

            if (is_wp_error($result)) {
                $error_count++;
                error_log(sprintf(
                    '[IPV YouTube Updater] Error updating video #%d: %s',
                    $video->ID,
                    $result->get_error_message()
                ));
            } else {
                $updated_count++;
            }

            // Avoid API rate limits
            sleep(1);
        }

        error_log(sprintf(
            '[IPV YouTube Updater] Daily update completed: %d updated, %d errors',
            $updated_count,
            $error_count
        ));

        update_option('ipv_pro_last_youtube_update', [
            'timestamp' => current_time('mysql'),
            'updated' => $updated_count,
            'errors' => $error_count
        ]);
    }

    /**
     * Update YouTube data for single video
     *
     * @param int $post_id
     * @return bool|WP_Error
     */
    public function update_video_data($post_id) {
        $video_id = get_post_meta($post_id, '_ipv_video_id', true);

        if (empty($video_id)) {
            return new WP_Error('no_video_id', 'Video ID non trovato');
        }

        // Fetch fresh data from YouTube
        $youtube_api = new IPV_YouTube_API();
        $video_url = "https://www.youtube.com/watch?v={$video_id}";
        $video_data = $youtube_api->fetch_video_data($video_url);

        if (is_wp_error($video_data)) {
            return $video_data;
        }

        // Update only dynamic fields (views, likes, comments)
        $updated_fields = [
            '_ipv_view_count' => $video_data['view_count'],
            '_ipv_like_count' => $video_data['like_count'],
            '_ipv_comment_count' => $video_data['comment_count'],
            '_ipv_last_youtube_update' => current_time('mysql')
        ];

        foreach ($updated_fields as $key => $value) {
            update_post_meta($post_id, $key, $value);
        }

        return true;
    }

    /**
     * Get last update info
     */
    public static function get_last_update_info() {
        $info = get_option('ipv_pro_last_youtube_update', [
            'timestamp' => null,
            'updated' => 0,
            'errors' => 0
        ]);

        if ($info['timestamp']) {
            $info['human_time'] = human_time_diff(strtotime($info['timestamp']), current_time('timestamp')) . ' fa';
        } else {
            $info['human_time'] = 'Mai';
        }

        return $info;
    }

    /**
     * Get next scheduled update time
     */
    public static function get_next_update() {
        $next = wp_next_scheduled('ipv_pro_update_youtube_data');

        if (!$next) {
            return 'Non programmato';
        }

        return human_time_diff($next, current_time('timestamp')) . ' ';
    }

    /**
     * Manual update trigger
     *
     * @param int|null $post_id Optional post ID, null for all videos
     */
    public static function manual_update($post_id = null) {
        $updater = new self();

        if ($post_id) {
            return $updater->update_video_data($post_id);
        } else {
            $updater->update_all_videos_data();
            return true;
        }
    }
}
