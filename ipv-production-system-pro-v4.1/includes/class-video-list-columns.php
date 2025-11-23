<?php
/**
 * IPV Production System Pro - Video List Columns
 *
 * Gestione colonne personalizzate nella lista admin dei video:
 * - Thumbnail YouTube con link
 * - Durata video formattata
 * - Visualizzazioni (views) con formato abbreviato
 * - Stato elaborazione (trascrizione/AI)
 * - Ordinamento custom
 *
 * @package IPV_Production_System_Pro
 * @version 4.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class IPV_Prod_Video_List_Columns {

    /**
     * Post type slug
     */
    const POST_TYPE = 'video_ipv';

    /**
     * Inizializza la classe
     */
    public static function init() {
        add_filter( 'manage_' . self::POST_TYPE . '_posts_columns', [ __CLASS__, 'define_columns' ] );
        add_action( 'manage_' . self::POST_TYPE . '_posts_custom_column', [ __CLASS__, 'render_column' ], 10, 2 );
        add_filter( 'manage_edit-' . self::POST_TYPE . '_sortable_columns', [ __CLASS__, 'sortable_columns' ] );
        add_action( 'pre_get_posts', [ __CLASS__, 'handle_sorting' ] );
        add_action( 'admin_head', [ __CLASS__, 'column_styles' ] );
    }

    /**
     * Definisce le colonne della lista
     *
     * @param array $columns Colonne esistenti
     * @return array Colonne modificate
     */
    public static function define_columns( $columns ) {
        $new_columns = [];

        // Checkbox
        if ( isset( $columns['cb'] ) ) {
            $new_columns['cb'] = $columns['cb'];
        }

        // Thumbnail prima del titolo
        $new_columns['ipv_thumbnail'] = '<span class="dashicons dashicons-format-image" title="Anteprima"></span>';

        // Titolo
        if ( isset( $columns['title'] ) ) {
            $new_columns['title'] = $columns['title'];
        }

        // Colonne custom
        $new_columns['ipv_video_id']  = 'Video ID';
        $new_columns['ipv_duration']  = '<span class="dashicons dashicons-clock" title="Durata"></span>';
        $new_columns['ipv_views']     = '<span class="dashicons dashicons-visibility" title="Visualizzazioni"></span>';
        $new_columns['ipv_likes']     = '<span class="dashicons dashicons-thumbs-up" title="Like"></span>';
        $new_columns['ipv_status']    = 'Stato';
        $new_columns['ipv_source']    = 'Fonte';

        // Categorie e tag
        if ( isset( $columns['taxonomy-video_category'] ) ) {
            $new_columns['taxonomy-video_category'] = $columns['taxonomy-video_category'];
        }
        if ( isset( $columns['taxonomy-video_tag'] ) ) {
            $new_columns['taxonomy-video_tag'] = $columns['taxonomy-video_tag'];
        }

        // Data
        if ( isset( $columns['date'] ) ) {
            $new_columns['date'] = $columns['date'];
        }

        return $new_columns;
    }

    /**
     * Renderizza il contenuto delle colonne custom
     *
     * @param string $column  Nome della colonna
     * @param int    $post_id Post ID
     */
    public static function render_column( $column, $post_id ) {
        switch ( $column ) {
            case 'ipv_thumbnail':
                self::render_thumbnail_column( $post_id );
                break;

            case 'ipv_video_id':
                self::render_video_id_column( $post_id );
                break;

            case 'ipv_duration':
                self::render_duration_column( $post_id );
                break;

            case 'ipv_views':
                self::render_views_column( $post_id );
                break;

            case 'ipv_likes':
                self::render_likes_column( $post_id );
                break;

            case 'ipv_status':
                self::render_status_column( $post_id );
                break;

            case 'ipv_source':
                self::render_source_column( $post_id );
                break;
        }
    }

    /**
     * Renderizza la colonna thumbnail
     */
    protected static function render_thumbnail_column( $post_id ) {
        $thumbnail = get_post_meta( $post_id, '_ipv_yt_thumbnail_url', true );
        $video_id  = get_post_meta( $post_id, '_ipv_video_id', true );

        if ( $thumbnail ) {
            printf(
                '<a href="https://www.youtube.com/watch?v=%s" target="_blank" title="Apri su YouTube">
                    <img src="%s" width="80" height="45" style="border-radius:4px;object-fit:cover;">
                </a>',
                esc_attr( $video_id ),
                esc_url( $thumbnail )
            );
        } else {
            echo '<span class="dashicons dashicons-video-alt3" style="font-size:40px;width:80px;height:45px;color:#ccc;text-align:center;line-height:45px;"></span>';
        }
    }

    /**
     * Renderizza la colonna Video ID
     */
    protected static function render_video_id_column( $post_id ) {
        $video_id = get_post_meta( $post_id, '_ipv_video_id', true );
        if ( $video_id ) {
            printf(
                '<code style="font-size:11px;">%s</code>
                <br><a href="https://www.youtube.com/watch?v=%s" target="_blank" class="row-actions visible" style="font-size:11px;">
                    <span class="dashicons dashicons-external" style="font-size:12px;width:12px;height:12px;vertical-align:middle;"></span> YouTube
                </a>',
                esc_html( $video_id ),
                esc_attr( $video_id )
            );
        } else {
            echo '<span style="color:#999;">—</span>';
        }
    }

    /**
     * Renderizza la colonna durata
     */
    protected static function render_duration_column( $post_id ) {
        $duration = get_post_meta( $post_id, '_ipv_yt_duration_formatted', true );
        if ( $duration ) {
            printf( '<code style="font-size:12px;font-weight:600;">%s</code>', esc_html( $duration ) );
        } else {
            echo '<span style="color:#999;">—</span>';
        }
    }

    /**
     * Renderizza la colonna visualizzazioni
     */
    protected static function render_views_column( $post_id ) {
        $views = get_post_meta( $post_id, '_ipv_yt_view_count', true );
        if ( $views ) {
            $formatted = self::format_number( $views );
            printf(
                '<span style="font-weight:600;color:#2271b1;" title="%s visualizzazioni">%s</span>',
                number_format_i18n( $views ),
                esc_html( $formatted )
            );
        } else {
            echo '<span style="color:#999;">—</span>';
        }
    }

    /**
     * Renderizza la colonna like
     */
    protected static function render_likes_column( $post_id ) {
        $likes = get_post_meta( $post_id, '_ipv_yt_like_count', true );
        if ( $likes ) {
            $formatted = self::format_number( $likes );
            printf(
                '<span style="font-weight:600;color:#46b450;" title="%s like">%s</span>',
                number_format_i18n( $likes ),
                esc_html( $formatted )
            );
        } else {
            echo '<span style="color:#999;">—</span>';
        }
    }

    /**
     * Renderizza la colonna stato
     */
    protected static function render_status_column( $post_id ) {
        $has_transcript = get_post_meta( $post_id, '_ipv_transcript', true );
        $has_ai_desc    = get_post_meta( $post_id, '_ipv_ai_description', true );
        $has_thumbnail  = has_post_thumbnail( $post_id );

        echo '<div style="display:flex;gap:3px;flex-wrap:wrap;">';

        // Badge trascrizione
        if ( $has_transcript ) {
            echo '<span class="ipv-badge ipv-badge-success" title="Trascrizione presente">T</span>';
        } else {
            echo '<span class="ipv-badge ipv-badge-warning" title="Trascrizione mancante">T</span>';
        }

        // Badge AI
        if ( $has_ai_desc ) {
            echo '<span class="ipv-badge ipv-badge-info" title="Descrizione AI generata">AI</span>';
        } else {
            echo '<span class="ipv-badge ipv-badge-muted" title="Descrizione AI mancante">AI</span>';
        }

        // Badge thumbnail
        if ( $has_thumbnail ) {
            echo '<span class="ipv-badge ipv-badge-success" title="Featured image presente">IMG</span>';
        }

        echo '</div>';
    }

    /**
     * Renderizza la colonna fonte
     */
    protected static function render_source_column( $post_id ) {
        $source = get_post_meta( $post_id, '_ipv_source', true );

        $sources = [
            'manual'  => [ 'label' => 'Manuale', 'color' => '#6c757d' ],
            'rss'     => [ 'label' => 'RSS', 'color' => '#fd7e14' ],
            'bulk'    => [ 'label' => 'Bulk', 'color' => '#6f42c1' ],
            'playlist'=> [ 'label' => 'Playlist', 'color' => '#20c997' ],
        ];

        if ( isset( $sources[ $source ] ) ) {
            printf(
                '<span style="background:%s;color:#fff;padding:2px 6px;border-radius:3px;font-size:11px;">%s</span>',
                esc_attr( $sources[ $source ]['color'] ),
                esc_html( $sources[ $source ]['label'] )
            );
        } else {
            echo '<span style="color:#999;">—</span>';
        }
    }

    /**
     * Definisce le colonne ordinabili
     *
     * @param array $columns Colonne ordinabili
     * @return array Colonne modificate
     */
    public static function sortable_columns( $columns ) {
        $columns['ipv_views']    = 'ipv_views';
        $columns['ipv_likes']    = 'ipv_likes';
        $columns['ipv_duration'] = 'ipv_duration';
        return $columns;
    }

    /**
     * Gestisce l'ordinamento per colonne custom
     *
     * @param WP_Query $query Query principale
     */
    public static function handle_sorting( $query ) {
        if ( ! is_admin() || ! $query->is_main_query() ) {
            return;
        }

        if ( $query->get( 'post_type' ) !== self::POST_TYPE ) {
            return;
        }

        $orderby = $query->get( 'orderby' );

        switch ( $orderby ) {
            case 'ipv_views':
                $query->set( 'meta_key', '_ipv_yt_view_count' );
                $query->set( 'orderby', 'meta_value_num' );
                break;

            case 'ipv_likes':
                $query->set( 'meta_key', '_ipv_yt_like_count' );
                $query->set( 'orderby', 'meta_value_num' );
                break;

            case 'ipv_duration':
                $query->set( 'meta_key', '_ipv_yt_duration_seconds' );
                $query->set( 'orderby', 'meta_value_num' );
                break;
        }
    }

    /**
     * Aggiunge gli stili CSS per le colonne
     */
    public static function column_styles() {
        $screen = get_current_screen();
        if ( ! $screen || $screen->post_type !== self::POST_TYPE ) {
            return;
        }
        ?>
        <style>
            .column-ipv_thumbnail { width: 90px; }
            .column-ipv_video_id { width: 120px; }
            .column-ipv_duration { width: 70px; text-align: center; }
            .column-ipv_views { width: 70px; text-align: center; }
            .column-ipv_likes { width: 70px; text-align: center; }
            .column-ipv_status { width: 100px; }
            .column-ipv_source { width: 80px; }

            .ipv-badge {
                display: inline-block;
                padding: 2px 5px;
                border-radius: 3px;
                font-size: 10px;
                font-weight: 600;
                text-transform: uppercase;
            }
            .ipv-badge-success { background: #d4edda; color: #155724; }
            .ipv-badge-warning { background: #fff3cd; color: #856404; }
            .ipv-badge-info { background: #cce5ff; color: #004085; }
            .ipv-badge-muted { background: #e9ecef; color: #6c757d; }

            /* Header icone */
            .column-ipv_thumbnail .dashicons,
            .column-ipv_duration .dashicons,
            .column-ipv_views .dashicons,
            .column-ipv_likes .dashicons {
                color: #666;
            }
        </style>
        <?php
    }

    /**
     * Formatta numeri grandi in formato abbreviato
     *
     * @param int $number Numero da formattare
     * @return string Numero formattato (es. 1.2M, 45K)
     */
    protected static function format_number( $number ) {
        if ( $number >= 1000000 ) {
            return number_format( $number / 1000000, 1 ) . 'M';
        }
        if ( $number >= 1000 ) {
            return number_format( $number / 1000, 1 ) . 'K';
        }
        return number_format_i18n( $number );
    }
}

// Inizializza la classe
IPV_Prod_Video_List_Columns::init();
