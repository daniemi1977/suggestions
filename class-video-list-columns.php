<?php
/**
 * Video IPV - Custom Admin List Columns
 *
 * Questo file aggiunge colonne personalizzate alla lista dei video IPV
 * nell'admin di WordPress, come mostrato nello screenshot:
 * - Thumbnail
 * - Title
 * - YouTube ID
 * - Transcript (indicatore)
 * - Description (indicatore)
 * - Views
 * - Date
 *
 * @package IPV_Production_System_Pro
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class IPV_Video_List_Columns {

    /**
     * Initialize the class
     */
    public static function init() {
        // Add custom columns
        add_filter( 'manage_ipv_video_posts_columns', [ __CLASS__, 'set_custom_columns' ] );

        // Populate custom columns
        add_action( 'manage_ipv_video_posts_custom_column', [ __CLASS__, 'render_custom_column' ], 10, 2 );

        // Make columns sortable
        add_filter( 'manage_edit-ipv_video_sortable_columns', [ __CLASS__, 'set_sortable_columns' ] );

        // Handle column sorting
        add_action( 'pre_get_posts', [ __CLASS__, 'handle_column_sorting' ] );

        // Add admin styles
        add_action( 'admin_head', [ __CLASS__, 'admin_styles' ] );
    }

    /**
     * Define custom columns for the video list
     *
     * @param array $columns Existing columns
     * @return array Modified columns
     */
    public static function set_custom_columns( $columns ) {
        $new_columns = [];

        // Checkbox column
        if ( isset( $columns['cb'] ) ) {
            $new_columns['cb'] = $columns['cb'];
        }

        // Thumbnail column
        $new_columns['thumbnail'] = '<span class="dashicons dashicons-format-image" title="Thumbnail"></span>';

        // Title column
        $new_columns['title'] = __( 'Title', 'ipv-production' );

        // YouTube ID column
        $new_columns['youtube_id'] = '<span class="dashicons dashicons-youtube" title="YouTube ID"></span> ' . __( 'YouTube ID', 'ipv-production' );

        // Transcript column (indicator)
        $new_columns['transcript'] = '<span class="dashicons dashicons-media-document" title="Transcript"></span> ' . __( 'Transcript', 'ipv-production' );

        // Description column (indicator)
        $new_columns['description'] = '<span class="dashicons dashicons-text" title="Description"></span> ' . __( 'Description', 'ipv-production' );

        // Views column
        $new_columns['views'] = '<span class="dashicons dashicons-visibility" title="Views"></span> ' . __( 'Views', 'ipv-production' );

        // Date column
        $new_columns['date'] = __( 'Date', 'ipv-production' );

        return $new_columns;
    }

    /**
     * Render custom column content
     *
     * @param string $column  Column name
     * @param int    $post_id Post ID
     */
    public static function render_custom_column( $column, $post_id ) {
        switch ( $column ) {
            case 'thumbnail':
                self::render_thumbnail( $post_id );
                break;

            case 'youtube_id':
                self::render_youtube_id( $post_id );
                break;

            case 'transcript':
                self::render_transcript_indicator( $post_id );
                break;

            case 'description':
                self::render_description_indicator( $post_id );
                break;

            case 'views':
                self::render_views( $post_id );
                break;
        }
    }

    /**
     * Render thumbnail column
     *
     * @param int $post_id Post ID
     */
    private static function render_thumbnail( $post_id ) {
        $thumbnail = get_the_post_thumbnail( $post_id, [ 80, 60 ], [ 'class' => 'ipv-video-thumbnail' ] );

        if ( $thumbnail ) {
            echo $thumbnail;
        } else {
            // Try to get YouTube thumbnail if no featured image
            $video_id = get_post_meta( $post_id, '_ipv_video_id', true );
            if ( $video_id ) {
                echo '<img src="https://img.youtube.com/vi/' . esc_attr( $video_id ) . '/mqdefault.jpg" class="ipv-video-thumbnail" width="80" height="60" alt="">';
            } else {
                echo '<span class="dashicons dashicons-video-alt3 ipv-no-thumbnail"></span>';
            }
        }
    }

    /**
     * Render YouTube ID column
     *
     * @param int $post_id Post ID
     */
    private static function render_youtube_id( $post_id ) {
        $video_id = get_post_meta( $post_id, '_ipv_video_id', true );

        if ( $video_id ) {
            echo '<a href="https://www.youtube.com/watch?v=' . esc_attr( $video_id ) . '" target="_blank" rel="noopener" class="ipv-youtube-link">';
            echo esc_html( $video_id );
            echo '</a>';
        } else {
            echo '<span class="ipv-no-data">&mdash;</span>';
        }
    }

    /**
     * Render transcript indicator
     *
     * @param int $post_id Post ID
     */
    private static function render_transcript_indicator( $post_id ) {
        $transcript = get_post_meta( $post_id, '_ipv_transcript', true );

        if ( ! empty( $transcript ) ) {
            echo '<span class="ipv-indicator ipv-indicator-success" title="' . esc_attr__( 'Transcript available', 'ipv-production' ) . '">&#x25CF;</span>';
        } else {
            echo '<span class="ipv-indicator ipv-indicator-error" title="' . esc_attr__( 'No transcript', 'ipv-production' ) . '">&#x25CB;</span>';
        }
    }

    /**
     * Render description indicator
     *
     * @param int $post_id Post ID
     */
    private static function render_description_indicator( $post_id ) {
        $description = get_post_meta( $post_id, '_ipv_ai_description', true );

        // Also check post content as fallback
        if ( empty( $description ) ) {
            $post = get_post( $post_id );
            $description = $post->post_content;
        }

        if ( ! empty( $description ) ) {
            echo '<span class="ipv-indicator ipv-indicator-success" title="' . esc_attr__( 'Description available', 'ipv-production' ) . '">&#x25CF;</span>';
        } else {
            echo '<span class="ipv-indicator ipv-indicator-error" title="' . esc_attr__( 'No description', 'ipv-production' ) . '">&#x25CB;</span>';
        }
    }

    /**
     * Render views count
     *
     * @param int $post_id Post ID
     */
    private static function render_views( $post_id ) {
        $views = get_post_meta( $post_id, '_ipv_views', true );

        if ( $views !== '' && $views !== false ) {
            echo '<span class="ipv-views-count">' . number_format_i18n( intval( $views ) ) . '</span>';
        } else {
            echo '<span class="ipv-views-count">0</span>';
        }
    }

    /**
     * Set sortable columns
     *
     * @param array $columns Sortable columns
     * @return array Modified sortable columns
     */
    public static function set_sortable_columns( $columns ) {
        $columns['views'] = 'views';
        $columns['youtube_id'] = 'youtube_id';
        return $columns;
    }

    /**
     * Handle custom column sorting
     *
     * @param WP_Query $query The query object
     */
    public static function handle_column_sorting( $query ) {
        if ( ! is_admin() || ! $query->is_main_query() ) {
            return;
        }

        $orderby = $query->get( 'orderby' );

        if ( 'views' === $orderby ) {
            $query->set( 'meta_key', '_ipv_views' );
            $query->set( 'orderby', 'meta_value_num' );
        }

        if ( 'youtube_id' === $orderby ) {
            $query->set( 'meta_key', '_ipv_video_id' );
            $query->set( 'orderby', 'meta_value' );
        }
    }

    /**
     * Add admin styles for the video list
     */
    public static function admin_styles() {
        $screen = get_current_screen();

        if ( ! $screen || $screen->id !== 'edit-ipv_video' ) {
            return;
        }

        ?>
        <style>
            /* Thumbnail column */
            .column-thumbnail {
                width: 80px;
            }

            .ipv-video-thumbnail {
                border-radius: 4px;
                object-fit: cover;
            }

            .ipv-no-thumbnail {
                font-size: 40px;
                color: #ccc;
                width: 80px;
                height: 60px;
                display: flex;
                align-items: center;
                justify-content: center;
            }

            /* YouTube ID column */
            .column-youtube_id {
                width: 140px;
            }

            .ipv-youtube-link {
                text-decoration: none;
                font-family: monospace;
                font-size: 12px;
            }

            .ipv-youtube-link:hover {
                text-decoration: underline;
            }

            /* Indicator columns */
            .column-transcript,
            .column-description {
                width: 100px;
                text-align: center;
            }

            .ipv-indicator {
                font-size: 20px;
                line-height: 1;
            }

            .ipv-indicator-success {
                color: #00a32a;
            }

            .ipv-indicator-error {
                color: #d63638;
            }

            /* Views column */
            .column-views {
                width: 80px;
                text-align: center;
            }

            .ipv-views-count {
                font-weight: 500;
            }

            /* No data placeholder */
            .ipv-no-data {
                color: #999;
            }

            /* Column header icons */
            .column-thumbnail .dashicons,
            .column-youtube_id .dashicons,
            .column-transcript .dashicons,
            .column-description .dashicons,
            .column-views .dashicons {
                vertical-align: middle;
                color: #646970;
            }

            /* Responsive adjustments */
            @media screen and (max-width: 782px) {
                .column-youtube_id,
                .column-transcript,
                .column-description,
                .column-views {
                    display: none;
                }
            }
        </style>
        <?php
    }
}

// Initialize the class
add_action( 'admin_init', [ 'IPV_Video_List_Columns', 'init' ] );
