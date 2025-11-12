<?php
/**
 * Theme Integration Helper
 *
 * Provides functions and hooks for seamless theme integration
 * Specifically optimized for Influencer theme but works with any WordPress theme
 */

if (!defined('ABSPATH')) {
    exit;
}

class IPV_Theme_Integration {

    /**
     * Constructor
     */
    public function __construct() {
        // Register widget
        add_action('widgets_init', [$this, 'register_widgets']);

        // Register shortcodes
        add_action('init', [$this, 'register_shortcodes']);

        // Enqueue frontend styles
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets']);

        // Add custom body classes
        add_filter('body_class', [$this, 'add_body_classes']);

        // Enhance video post content
        add_filter('the_content', [$this, 'enhance_video_content']);
    }

    /**
     * Register widgets
     */
    public function register_widgets() {
        register_widget('IPV_Recent_Videos_Widget');
    }

    /**
     * Register shortcodes
     */
    public function register_shortcodes() {
        add_shortcode('ipv_video_player', [$this, 'shortcode_video_player']);
        add_shortcode('ipv_recent_videos', [$this, 'shortcode_recent_videos']);
        add_shortcode('ipv_video_stats', [$this, 'shortcode_video_stats']);
        add_shortcode('ipv_video_embed', [$this, 'shortcode_video_embed']);
    }

    /**
     * Enqueue frontend assets
     */
    public function enqueue_frontend_assets() {
        // Only load on single posts with video metadata
        if (is_single() && $this->is_video_post()) {
            wp_enqueue_style(
                'ipv-pro-frontend',
                IPV_PRO_PLUGIN_URL . 'assets/css/frontend.css',
                [],
                IPV_PRO_VERSION
            );

            wp_enqueue_script(
                'ipv-pro-frontend',
                IPV_PRO_PLUGIN_URL . 'assets/js/frontend.js',
                ['jquery'],
                IPV_PRO_VERSION,
                true
            );
        }
    }

    /**
     * Add custom body classes
     */
    public function add_body_classes($classes) {
        if ($this->is_video_post()) {
            $classes[] = 'ipv-video-post';
            $classes[] = 'ipv-theme-integration';
        }
        return $classes;
    }

    /**
     * Check if current post is a video post
     */
    private function is_video_post($post_id = null) {
        if (!$post_id) {
            $post_id = get_the_ID();
        }

        $video_url = get_post_meta($post_id, '_ipv_video_url', true);
        return !empty($video_url);
    }

    /**
     * Enhance video content with automatic embeds
     */
    public function enhance_video_content($content) {
        if (!is_single() || !$this->is_video_post()) {
            return $content;
        }

        // Get video metadata
        $video_id = get_post_meta(get_the_ID(), '_ipv_video_id', true);
        $video_url = get_post_meta(get_the_ID(), '_ipv_video_url', true);

        if (!$video_id) {
            return $content;
        }

        // Build video embed HTML
        $video_embed = $this->build_video_embed($video_id);
        $video_meta = $this->build_video_meta(get_the_ID());

        // Prepend video before content
        $enhanced_content = $video_meta . $video_embed . $content;

        return apply_filters('ipv_enhanced_content', $enhanced_content, get_the_ID());
    }

    /**
     * Build video embed HTML
     */
    private function build_video_embed($video_id, $args = []) {
        $defaults = [
            'autoplay' => 0,
            'controls' => 1,
            'rel' => 0,
            'modestbranding' => 1,
            'width' => '100%',
            'height' => '100%'
        ];

        $args = wp_parse_args($args, $defaults);

        $embed_url = add_query_arg([
            'autoplay' => $args['autoplay'],
            'controls' => $args['controls'],
            'rel' => $args['rel'],
            'modestbranding' => $args['modestbranding']
        ], 'https://www.youtube.com/embed/' . $video_id);

        ob_start();
        ?>
        <div class="ipv-video-container">
            <div class="ipv-video-responsive">
                <iframe
                    src="<?php echo esc_url($embed_url); ?>"
                    width="<?php echo esc_attr($args['width']); ?>"
                    height="<?php echo esc_attr($args['height']); ?>"
                    frameborder="0"
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                    allowfullscreen
                    loading="lazy">
                </iframe>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Build video meta HTML
     */
    private function build_video_meta($post_id) {
        $channel = get_post_meta($post_id, '_ipv_channel_title', true);
        $views = get_post_meta($post_id, '_ipv_view_count', true);
        $likes = get_post_meta($post_id, '_ipv_like_count', true);
        $duration = get_post_meta($post_id, '_ipv_duration', true);
        $published = get_post_meta($post_id, '_ipv_published_at', true);

        if (!$channel && !$views) {
            return '';
        }

        ob_start();
        ?>
        <div class="ipv-video-meta">
            <?php if ($channel): ?>
                <span class="ipv-meta-item ipv-channel">
                    <span class="ipv-icon">📺</span>
                    <span class="ipv-text"><?php echo esc_html($channel); ?></span>
                </span>
            <?php endif; ?>

            <?php if ($views): ?>
                <span class="ipv-meta-item ipv-views">
                    <span class="ipv-icon">👁️</span>
                    <span class="ipv-text"><?php echo number_format((int)$views); ?> visualizzazioni</span>
                </span>
            <?php endif; ?>

            <?php if ($likes): ?>
                <span class="ipv-meta-item ipv-likes">
                    <span class="ipv-icon">👍</span>
                    <span class="ipv-text"><?php echo number_format((int)$likes); ?> like</span>
                </span>
            <?php endif; ?>

            <?php if ($duration): ?>
                <span class="ipv-meta-item ipv-duration">
                    <span class="ipv-icon">⏱️</span>
                    <span class="ipv-text"><?php echo $this->format_duration($duration); ?></span>
                </span>
            <?php endif; ?>

            <?php if ($published): ?>
                <span class="ipv-meta-item ipv-published">
                    <span class="ipv-icon">📅</span>
                    <span class="ipv-text"><?php echo date_i18n('j F Y', strtotime($published)); ?></span>
                </span>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Format duration in seconds to readable format
     */
    private function format_duration($seconds) {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $secs = $seconds % 60;

        if ($hours > 0) {
            return sprintf('%d:%02d:%02d', $hours, $minutes, $secs);
        } else {
            return sprintf('%d:%02d', $minutes, $secs);
        }
    }

    /**
     * Shortcode: Video Player
     * Usage: [ipv_video_player id="123" autoplay="0"]
     */
    public function shortcode_video_player($atts) {
        $atts = shortcode_atts([
            'id' => get_the_ID(),
            'autoplay' => 0,
            'controls' => 1,
            'width' => '100%',
            'height' => '100%'
        ], $atts);

        $video_id = get_post_meta($atts['id'], '_ipv_video_id', true);

        if (!$video_id) {
            return '<p class="ipv-error">Video non trovato.</p>';
        }

        return $this->build_video_embed($video_id, $atts);
    }

    /**
     * Shortcode: Recent Videos
     * Usage: [ipv_recent_videos count="6" columns="3" category="5"]
     */
    public function shortcode_recent_videos($atts) {
        $atts = shortcode_atts([
            'count' => 6,
            'columns' => 3,
            'category' => '',
            'orderby' => 'date',
            'order' => 'DESC'
        ], $atts);

        $args = [
            'post_type' => 'post',
            'posts_per_page' => (int)$atts['count'],
            'orderby' => $atts['orderby'],
            'order' => $atts['order'],
            'meta_query' => [
                [
                    'key' => '_ipv_video_id',
                    'compare' => 'EXISTS'
                ]
            ]
        ];

        if (!empty($atts['category'])) {
            $args['cat'] = (int)$atts['category'];
        }

        $query = new WP_Query($args);

        if (!$query->have_posts()) {
            return '<p class="ipv-no-videos">Nessun video disponibile.</p>';
        }

        $columns_class = 'ipv-grid-cols-' . (int)$atts['columns'];

        ob_start();
        ?>
        <div class="ipv-recent-videos ipv-video-grid <?php echo esc_attr($columns_class); ?>">
            <?php while ($query->have_posts()) : $query->the_post();
                $video_id = get_post_meta(get_the_ID(), '_ipv_video_id', true);
                $thumbnail = get_post_meta(get_the_ID(), '_ipv_thumbnail_maxres', true);
                $views = get_post_meta(get_the_ID(), '_ipv_view_count', true);
                $duration = get_post_meta(get_the_ID(), '_ipv_duration', true);
            ?>
                <article class="ipv-video-card">
                    <a href="<?php the_permalink(); ?>" class="ipv-video-thumb">
                        <?php if ($thumbnail): ?>
                            <img src="<?php echo esc_url($thumbnail); ?>" alt="<?php the_title_attribute(); ?>" loading="lazy">
                        <?php elseif (has_post_thumbnail()): ?>
                            <?php the_post_thumbnail('large'); ?>
                        <?php else: ?>
                            <img src="https://img.youtube.com/vi/<?php echo esc_attr($video_id); ?>/maxresdefault.jpg" alt="<?php the_title_attribute(); ?>" loading="lazy">
                        <?php endif; ?>

                        <?php if ($duration): ?>
                            <span class="ipv-duration-badge"><?php echo $this->format_duration($duration); ?></span>
                        <?php endif; ?>
                    </a>

                    <div class="ipv-video-info">
                        <h3 class="ipv-video-title">
                            <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                        </h3>

                        <div class="ipv-video-excerpt">
                            <?php echo wp_trim_words(get_the_excerpt(), 20); ?>
                        </div>

                        <div class="ipv-video-stats">
                            <?php if ($views): ?>
                                <span class="ipv-stat-views">👁️ <?php echo number_format((int)$views); ?></span>
                            <?php endif; ?>
                            <span class="ipv-stat-date">📅 <?php echo get_the_date(); ?></span>
                        </div>
                    </div>
                </article>
            <?php endwhile; ?>
        </div>
        <?php
        wp_reset_postdata();
        return ob_get_clean();
    }

    /**
     * Shortcode: Video Stats
     * Usage: [ipv_video_stats id="123"]
     */
    public function shortcode_video_stats($atts) {
        $atts = shortcode_atts([
            'id' => get_the_ID()
        ], $atts);

        $post_id = (int)$atts['id'];

        if (!$this->is_video_post($post_id)) {
            return '<p class="ipv-error">Nessuna statistica disponibile.</p>';
        }

        $views = get_post_meta($post_id, '_ipv_view_count', true);
        $likes = get_post_meta($post_id, '_ipv_like_count', true);
        $comments = get_post_meta($post_id, '_ipv_comment_count', true);

        ob_start();
        ?>
        <div class="ipv-video-stats-box">
            <?php if ($views): ?>
                <div class="ipv-stat">
                    <span class="ipv-stat-icon">👁️</span>
                    <span class="ipv-stat-value"><?php echo number_format((int)$views); ?></span>
                    <span class="ipv-stat-label">Visualizzazioni</span>
                </div>
            <?php endif; ?>

            <?php if ($likes): ?>
                <div class="ipv-stat">
                    <span class="ipv-stat-icon">👍</span>
                    <span class="ipv-stat-value"><?php echo number_format((int)$likes); ?></span>
                    <span class="ipv-stat-label">Mi piace</span>
                </div>
            <?php endif; ?>

            <?php if ($comments): ?>
                <div class="ipv-stat">
                    <span class="ipv-stat-icon">💬</span>
                    <span class="ipv-stat-value"><?php echo number_format((int)$comments); ?></span>
                    <span class="ipv-stat-label">Commenti</span>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Video Embed (simple version)
     * Usage: [ipv_video_embed url="https://youtube.com/watch?v=xxxxx"]
     */
    public function shortcode_video_embed($atts) {
        $atts = shortcode_atts([
            'url' => '',
            'width' => '100%',
            'height' => '100%'
        ], $atts);

        if (empty($atts['url'])) {
            return '<p class="ipv-error">URL video richiesto.</p>';
        }

        // Extract video ID from URL
        $youtube_api = new IPV_YouTube_API();
        $video_id = $youtube_api->extract_video_id($atts['url']);

        if (!$video_id) {
            return '<p class="ipv-error">URL YouTube non valido.</p>';
        }

        return $this->build_video_embed($video_id, $atts);
    }

    /**
     * Get video metadata for template use
     */
    public static function get_video_meta($post_id = null) {
        if (!$post_id) {
            $post_id = get_the_ID();
        }

        return [
            'video_id' => get_post_meta($post_id, '_ipv_video_id', true),
            'video_url' => get_post_meta($post_id, '_ipv_video_url', true),
            'channel_id' => get_post_meta($post_id, '_ipv_channel_id', true),
            'channel_title' => get_post_meta($post_id, '_ipv_channel_title', true),
            'view_count' => get_post_meta($post_id, '_ipv_view_count', true),
            'like_count' => get_post_meta($post_id, '_ipv_like_count', true),
            'comment_count' => get_post_meta($post_id, '_ipv_comment_count', true),
            'duration' => get_post_meta($post_id, '_ipv_duration', true),
            'published_at' => get_post_meta($post_id, '_ipv_published_at', true),
            'thumbnail_default' => get_post_meta($post_id, '_ipv_thumbnail_default', true),
            'thumbnail_medium' => get_post_meta($post_id, '_ipv_thumbnail_medium', true),
            'thumbnail_high' => get_post_meta($post_id, '_ipv_thumbnail_high', true),
            'thumbnail_maxres' => get_post_meta($post_id, '_ipv_thumbnail_maxres', true),
        ];
    }

    /**
     * Template function: Display video player
     */
    public static function the_video_player($post_id = null, $args = []) {
        if (!$post_id) {
            $post_id = get_the_ID();
        }

        $video_id = get_post_meta($post_id, '_ipv_video_id', true);

        if (!$video_id) {
            return;
        }

        $integration = new self();
        echo $integration->build_video_embed($video_id, $args);
    }

    /**
     * Template function: Display video meta
     */
    public static function the_video_meta($post_id = null) {
        if (!$post_id) {
            $post_id = get_the_ID();
        }

        $integration = new self();
        echo $integration->build_video_meta($post_id);
    }
}

/**
 * Widget: Recent Videos
 */
class IPV_Recent_Videos_Widget extends WP_Widget {

    public function __construct() {
        parent::__construct(
            'ipv_recent_videos',
            'IPV Video Recenti',
            ['description' => 'Mostra i video importati più recenti']
        );
    }

    public function widget($args, $instance) {
        $title = !empty($instance['title']) ? $instance['title'] : 'Video Recenti';
        $count = !empty($instance['count']) ? (int)$instance['count'] : 5;
        $show_thumb = isset($instance['show_thumb']) ? (bool)$instance['show_thumb'] : true;
        $show_stats = isset($instance['show_stats']) ? (bool)$instance['show_stats'] : true;

        echo $args['before_widget'];

        if ($title) {
            echo $args['before_title'] . esc_html($title) . $args['after_title'];
        }

        // Query video posts
        $query = new WP_Query([
            'post_type' => 'post',
            'posts_per_page' => $count,
            'meta_query' => [
                [
                    'key' => '_ipv_video_id',
                    'compare' => 'EXISTS'
                ]
            ]
        ]);

        if ($query->have_posts()) {
            echo '<ul class="ipv-widget-video-list">';

            while ($query->have_posts()) {
                $query->the_post();
                $thumbnail = get_post_meta(get_the_ID(), '_ipv_thumbnail_default', true);
                $views = get_post_meta(get_the_ID(), '_ipv_view_count', true);

                echo '<li class="ipv-widget-video-item">';

                if ($show_thumb && $thumbnail) {
                    echo '<a href="' . get_permalink() . '" class="ipv-widget-thumb">';
                    echo '<img src="' . esc_url($thumbnail) . '" alt="' . get_the_title() . '" loading="lazy">';
                    echo '</a>';
                }

                echo '<div class="ipv-widget-info">';
                echo '<a href="' . get_permalink() . '" class="ipv-widget-title">' . get_the_title() . '</a>';

                if ($show_stats && $views) {
                    echo '<span class="ipv-widget-views">👁️ ' . number_format((int)$views) . '</span>';
                }

                echo '</div>';
                echo '</li>';
            }

            echo '</ul>';

            wp_reset_postdata();
        } else {
            echo '<p>Nessun video disponibile.</p>';
        }

        echo $args['after_widget'];
    }

    public function form($instance) {
        $title = !empty($instance['title']) ? $instance['title'] : 'Video Recenti';
        $count = !empty($instance['count']) ? (int)$instance['count'] : 5;
        $show_thumb = isset($instance['show_thumb']) ? (bool)$instance['show_thumb'] : true;
        $show_stats = isset($instance['show_stats']) ? (bool)$instance['show_stats'] : true;
        ?>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('title')); ?>">Titolo:</label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>" name="<?php echo esc_attr($this->get_field_name('title')); ?>" type="text" value="<?php echo esc_attr($title); ?>">
        </p>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('count')); ?>">Numero video:</label>
            <input class="tiny-text" id="<?php echo esc_attr($this->get_field_id('count')); ?>" name="<?php echo esc_attr($this->get_field_name('count')); ?>" type="number" value="<?php echo esc_attr($count); ?>" min="1" max="20">
        </p>
        <p>
            <input class="checkbox" type="checkbox" <?php checked($show_thumb); ?> id="<?php echo esc_attr($this->get_field_id('show_thumb')); ?>" name="<?php echo esc_attr($this->get_field_name('show_thumb')); ?>" />
            <label for="<?php echo esc_attr($this->get_field_id('show_thumb')); ?>">Mostra thumbnail</label>
        </p>
        <p>
            <input class="checkbox" type="checkbox" <?php checked($show_stats); ?> id="<?php echo esc_attr($this->get_field_id('show_stats')); ?>" name="<?php echo esc_attr($this->get_field_name('show_stats')); ?>" />
            <label for="<?php echo esc_attr($this->get_field_id('show_stats')); ?>">Mostra statistiche</label>
        </p>
        <?php
    }

    public function update($new_instance, $old_instance) {
        $instance = [];
        $instance['title'] = (!empty($new_instance['title'])) ? sanitize_text_field($new_instance['title']) : '';
        $instance['count'] = (!empty($new_instance['count'])) ? absint($new_instance['count']) : 5;
        $instance['show_thumb'] = isset($new_instance['show_thumb']);
        $instance['show_stats'] = isset($new_instance['show_stats']);
        return $instance;
    }
}
