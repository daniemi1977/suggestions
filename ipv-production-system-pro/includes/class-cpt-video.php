<?php
/**
 * Custom Post Type - IPV Video
 *
 * Registra il CPT 'ipv_video' con tassonomie, metabox e colonne admin personalizzate
 */

if (!defined('ABSPATH')) {
    exit;
}

class IPV_CPT_Video {

    /**
     * Constructor
     */
    public function __construct() {
        add_action('init', [$this, 'register_post_type']);
        add_action('init', [$this, 'register_taxonomies']);

        // Admin columns
        add_filter('manage_ipv_video_posts_columns', [$this, 'add_admin_columns']);
        add_action('manage_ipv_video_posts_custom_column', [$this, 'render_admin_columns'], 10, 2);
        add_filter('manage_edit-ipv_video_sortable_columns', [$this, 'sortable_columns']);

        // Admin filters
        add_action('restrict_manage_posts', [$this, 'add_admin_filters']);
        add_filter('parse_query', [$this, 'filter_admin_query']);

        // Bulk actions
        add_filter('bulk_actions-edit-ipv_video', [$this, 'add_bulk_actions']);
        add_filter('handle_bulk_actions-edit-ipv_video', [$this, 'handle_bulk_actions'], 10, 3);

        // Row actions
        add_filter('post_row_actions', [$this, 'add_row_actions'], 10, 2);

        // Metaboxes
        add_action('add_meta_boxes', [$this, 'add_metaboxes']);
        add_action('save_post_ipv_video', [$this, 'save_metabox_data']);

        // Updated messages
        add_filter('post_updated_messages', [$this, 'updated_messages']);
    }

    /**
     * Register Custom Post Type
     */
    public function register_post_type() {
        $labels = [
            'name' => 'Video IPV',
            'singular_name' => 'Video IPV',
            'menu_name' => 'Video IPV',
            'add_new' => 'Aggiungi Video',
            'add_new_item' => 'Aggiungi Nuovo Video',
            'edit_item' => 'Modifica Video',
            'new_item' => 'Nuovo Video',
            'view_item' => 'Visualizza Video',
            'view_items' => 'Visualizza Video',
            'search_items' => 'Cerca Video',
            'not_found' => 'Nessun video trovato',
            'not_found_in_trash' => 'Nessun video nel cestino',
            'all_items' => 'Tutti i Video',
            'archives' => 'Archivio Video',
            'attributes' => 'Attributi Video',
            'insert_into_item' => 'Inserisci nel video',
            'uploaded_to_this_item' => 'Caricato in questo video',
            'featured_image' => 'Thumbnail Video',
            'set_featured_image' => 'Imposta thumbnail',
            'remove_featured_image' => 'Rimuovi thumbnail',
            'use_featured_image' => 'Usa come thumbnail',
            'filter_items_list' => 'Filtra video',
            'items_list_navigation' => 'Navigazione video',
            'items_list' => 'Elenco video'
        ];

        $args = [
            'labels' => $labels,
            'public' => true,
            'publicly_queryable' => true,
            'show_ui' => true,
            'show_in_menu' => false, // Controlled via IPV menu
            'show_in_nav_menus' => true,
            'show_in_admin_bar' => true,
            'show_in_rest' => true,
            'query_var' => true,
            'rewrite' => ['slug' => 'video', 'with_front' => false],
            'capability_type' => 'post',
            'has_archive' => true,
            'hierarchical' => false,
            'menu_position' => 5,
            'menu_icon' => 'dashicons-video-alt3',
            'supports' => [
                'title',
                'editor',
                'excerpt',
                'thumbnail',
                'author',
                'comments',
                'revisions',
                'custom-fields'
            ],
            'taxonomies' => ['category', 'post_tag']
        ];

        register_post_type('ipv_video', $args);
    }

    /**
     * Register Custom Taxonomies
     */
    public function register_taxonomies() {
        // Taxonomy: Topics
        register_taxonomy('ipv_topic', 'ipv_video', [
            'labels' => [
                'name' => 'Argomenti',
                'singular_name' => 'Argomento',
                'menu_name' => 'Argomenti',
                'all_items' => 'Tutti gli Argomenti',
                'edit_item' => 'Modifica Argomento',
                'view_item' => 'Visualizza Argomento',
                'update_item' => 'Aggiorna Argomento',
                'add_new_item' => 'Aggiungi Argomento',
                'new_item_name' => 'Nuovo Argomento',
                'search_items' => 'Cerca Argomenti',
                'popular_items' => 'Argomenti Popolari',
                'not_found' => 'Nessun argomento trovato'
            ],
            'hierarchical' => false,
            'public' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'show_in_nav_menus' => true,
            'show_in_rest' => true,
            'show_admin_column' => true,
            'rewrite' => ['slug' => 'argomento']
        ]);

        // Taxonomy: Guests
        register_taxonomy('ipv_guest', 'ipv_video', [
            'labels' => [
                'name' => 'Ospiti',
                'singular_name' => 'Ospite',
                'menu_name' => 'Ospiti',
                'all_items' => 'Tutti gli Ospiti',
                'edit_item' => 'Modifica Ospite',
                'view_item' => 'Visualizza Ospite',
                'update_item' => 'Aggiorna Ospite',
                'add_new_item' => 'Aggiungi Ospite',
                'new_item_name' => 'Nuovo Ospite',
                'search_items' => 'Cerca Ospiti',
                'popular_items' => 'Ospiti Popolari',
                'not_found' => 'Nessun ospite trovato'
            ],
            'hierarchical' => false,
            'public' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'show_in_nav_menus' => true,
            'show_in_rest' => true,
            'show_admin_column' => true,
            'rewrite' => ['slug' => 'ospite']
        ]);

        // Taxonomy: Channel Themes
        register_taxonomy('ipv_channel_theme', 'ipv_video', [
            'labels' => [
                'name' => 'Temi del Canale',
                'singular_name' => 'Tema del Canale',
                'menu_name' => 'Temi Canale',
                'all_items' => 'Tutti i Temi',
                'edit_item' => 'Modifica Tema',
                'view_item' => 'Visualizza Tema',
                'update_item' => 'Aggiorna Tema',
                'add_new_item' => 'Aggiungi Tema',
                'new_item_name' => 'Nuovo Tema',
                'search_items' => 'Cerca Temi',
                'popular_items' => 'Temi Popolari',
                'not_found' => 'Nessun tema trovato'
            ],
            'hierarchical' => true,
            'public' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'show_in_nav_menus' => true,
            'show_in_rest' => true,
            'show_admin_column' => true,
            'rewrite' => ['slug' => 'tema-canale']
        ]);
    }

    /**
     * Add custom admin columns
     */
    public function add_admin_columns($columns) {
        $new_columns = [];

        $new_columns['cb'] = $columns['cb'];
        $new_columns['thumbnail'] = '🖼️ Thumb';
        $new_columns['title'] = 'Titolo';
        $new_columns['video_data'] = '📹 Dati Video';
        $new_columns['duration'] = '⏱️ Durata';
        $new_columns['views'] = '👁️ Visualizzazioni';
        $new_columns['status'] = '⚡ Stato Elaborazione';
        $new_columns['ipv_topic'] = 'Argomenti';
        $new_columns['ipv_guest'] = 'Ospiti';
        $new_columns['date'] = 'Data Pubblicazione YouTube';

        return $new_columns;
    }

    /**
     * Render custom admin columns
     */
    public function render_admin_columns($column, $post_id) {
        switch ($column) {
            case 'thumbnail':
                $thumbnail = get_post_meta($post_id, '_ipv_thumbnail_url', true);
                if ($thumbnail) {
                    echo '<img src="' . esc_url($thumbnail) . '" style="width: 120px; height: auto; border-radius: 4px;" />';
                } elseif (has_post_thumbnail($post_id)) {
                    echo get_the_post_thumbnail($post_id, [120, 90]);
                } else {
                    echo '<span style="opacity: 0.5;">—</span>';
                }
                break;

            case 'video_data':
                $video_id = get_post_meta($post_id, '_ipv_video_id', true);
                $channel = get_post_meta($post_id, '_ipv_channel_title', true);
                $category_id = get_post_meta($post_id, '_ipv_category_id', true);

                if ($video_id) {
                    echo '<div style="font-size: 11px; line-height: 1.6;">';
                    echo '<strong>ID:</strong> <code>' . esc_html($video_id) . '</code><br>';
                    if ($channel) {
                        echo '<strong>Canale:</strong> ' . esc_html($channel) . '<br>';
                    }
                    if ($category_id) {
                        echo '<strong>Categoria YT:</strong> ' . esc_html($this->get_youtube_category_name($category_id));
                    }
                    echo '</div>';
                } else {
                    echo '<span style="opacity: 0.5;">—</span>';
                }
                break;

            case 'duration':
                $duration = get_post_meta($post_id, '_ipv_duration', true);
                if ($duration) {
                    echo '<strong>' . esc_html($this->format_duration($duration)) . '</strong>';
                } else {
                    echo '<span style="opacity: 0.5;">—</span>';
                }
                break;

            case 'views':
                $views = get_post_meta($post_id, '_ipv_view_count', true);
                $likes = get_post_meta($post_id, '_ipv_like_count', true);
                $comments = get_post_meta($post_id, '_ipv_comment_count', true);

                if ($views) {
                    echo '<div style="font-size: 11px; line-height: 1.6;">';
                    echo '<strong>' . number_format_i18n($views) . '</strong> views<br>';
                    if ($likes) {
                        echo '👍 ' . number_format_i18n($likes) . '<br>';
                    }
                    if ($comments) {
                        echo '💬 ' . number_format_i18n($comments);
                    }
                    echo '</div>';
                } else {
                    echo '<span style="opacity: 0.5;">—</span>';
                }
                break;

            case 'status':
                $processing_status = get_post_meta($post_id, '_ipv_processing_status', true);
                $error = get_post_meta($post_id, '_ipv_processing_error', true);

                $badges = [
                    'pending' => ['label' => '⏳ In Coda', 'color' => '#f0ad4e'],
                    'importing' => ['label' => '⬇️ Importazione', 'color' => '#5bc0de'],
                    'transcribing' => ['label' => '📝 Trascrizione', 'color' => '#5bc0de'],
                    'generating_ai' => ['label' => '🤖 AI Generation', 'color' => '#5bc0de'],
                    'finalizing' => ['label' => '🏁 Finalizzazione', 'color' => '#5bc0de'],
                    'completed' => ['label' => '✅ Completato', 'color' => '#5cb85c'],
                    'failed' => ['label' => '❌ Errore', 'color' => '#d9534f']
                ];

                $badge = isset($badges[$processing_status]) ? $badges[$processing_status] : ['label' => '—', 'color' => '#999'];

                echo '<span style="display: inline-block; padding: 4px 8px; background: ' . esc_attr($badge['color']) . '; color: white; border-radius: 3px; font-size: 11px; font-weight: bold;">';
                echo esc_html($badge['label']);
                echo '</span>';

                if ($error) {
                    echo '<br><small style="color: #d9534f; font-size: 10px;" title="' . esc_attr($error) . '">⚠️ ' . esc_html(wp_trim_words($error, 10)) . '</small>';
                }
                break;
        }
    }

    /**
     * Make columns sortable
     */
    public function sortable_columns($columns) {
        $columns['views'] = 'views';
        $columns['duration'] = 'duration';
        return $columns;
    }

    /**
     * Add admin filters
     */
    public function add_admin_filters($post_type) {
        if ($post_type !== 'ipv_video') {
            return;
        }

        // Filter by processing status
        $current_status = isset($_GET['processing_status']) ? $_GET['processing_status'] : '';

        echo '<select name="processing_status">';
        echo '<option value="">Tutti gli stati</option>';

        $statuses = [
            'pending' => '⏳ In Coda',
            'importing' => '⬇️ Importazione',
            'transcribing' => '📝 Trascrizione',
            'generating_ai' => '🤖 AI Generation',
            'completed' => '✅ Completato',
            'failed' => '❌ Errore'
        ];

        foreach ($statuses as $value => $label) {
            printf(
                '<option value="%s"%s>%s</option>',
                esc_attr($value),
                selected($current_status, $value, false),
                esc_html($label)
            );
        }

        echo '</select>';
    }

    /**
     * Filter admin query
     */
    public function filter_admin_query($query) {
        global $pagenow;

        if ($pagenow !== 'edit.php' || !isset($_GET['post_type']) || $_GET['post_type'] !== 'ipv_video') {
            return;
        }

        // Filter by processing status
        if (isset($_GET['processing_status']) && !empty($_GET['processing_status'])) {
            $query->set('meta_query', [
                [
                    'key' => '_ipv_processing_status',
                    'value' => sanitize_text_field($_GET['processing_status']),
                    'compare' => '='
                ]
            ]);
        }

        // Sort by views
        if (isset($_GET['orderby']) && $_GET['orderby'] === 'views') {
            $query->set('meta_key', '_ipv_view_count');
            $query->set('orderby', 'meta_value_num');
        }

        // Sort by duration
        if (isset($_GET['orderby']) && $_GET['orderby'] === 'duration') {
            $query->set('meta_key', '_ipv_duration');
            $query->set('orderby', 'meta_value');
        }
    }

    /**
     * Add bulk actions
     */
    public function add_bulk_actions($actions) {
        $actions['regenerate_content'] = '🔄 Rigenera Contenuti AI';
        $actions['regenerate_transcript'] = '📝 Rigenera Trascrizione';
        $actions['export_notion'] = '📋 Esporta per Notion';
        return $actions;
    }

    /**
     * Handle bulk actions
     */
    public function handle_bulk_actions($redirect_to, $action, $post_ids) {
        if (!in_array($action, ['regenerate_content', 'regenerate_transcript', 'export_notion'])) {
            return $redirect_to;
        }

        $processed = 0;

        foreach ($post_ids as $post_id) {
            switch ($action) {
                case 'regenerate_content':
                    $this->regenerate_ai_content($post_id);
                    $processed++;
                    break;

                case 'regenerate_transcript':
                    $this->regenerate_transcript($post_id);
                    $processed++;
                    break;

                case 'export_notion':
                    $this->export_to_notion($post_id);
                    $processed++;
                    break;
            }
        }

        $redirect_to = add_query_arg([
            'bulk_action' => $action,
            'processed' => $processed
        ], $redirect_to);

        return $redirect_to;
    }

    /**
     * Add row actions
     */
    public function add_row_actions($actions, $post) {
        if ($post->post_type !== 'ipv_video') {
            return $actions;
        }

        $video_id = get_post_meta($post->ID, '_ipv_video_id', true);

        if ($video_id) {
            $actions['view_youtube'] = sprintf(
                '<a href="https://www.youtube.com/watch?v=%s" target="_blank">🎬 Vedi su YouTube</a>',
                esc_attr($video_id)
            );
        }

        $actions['regenerate_ai'] = sprintf(
            '<a href="%s">🔄 Rigenera AI</a>',
            wp_nonce_url(admin_url('admin-post.php?action=ipv_regenerate_ai&post_id=' . $post->ID), 'ipv_regenerate_ai_' . $post->ID)
        );

        $actions['export_notion'] = sprintf(
            '<a href="%s">📋 Esporta Notion</a>',
            wp_nonce_url(admin_url('admin-post.php?action=ipv_export_notion&post_id=' . $post->ID), 'ipv_export_notion_' . $post->ID)
        );

        return $actions;
    }

    /**
     * Add metaboxes
     */
    public function add_metaboxes() {
        add_meta_box(
            'ipv_video_data',
            '📹 Dati Video YouTube',
            [$this, 'render_video_data_metabox'],
            'ipv_video',
            'side',
            'high'
        );

        add_meta_box(
            'ipv_processing_data',
            '⚙️ Elaborazione e AI',
            [$this, 'render_processing_metabox'],
            'ipv_video',
            'normal',
            'high'
        );

        add_meta_box(
            'ipv_transcript',
            '📝 Trascrizione',
            [$this, 'render_transcript_metabox'],
            'ipv_video',
            'normal',
            'default'
        );
    }

    /**
     * Render video data metabox
     */
    public function render_video_data_metabox($post) {
        $video_id = get_post_meta($post->ID, '_ipv_video_id', true);
        $video_url = get_post_meta($post->ID, '_ipv_video_url', true);
        $channel = get_post_meta($post->ID, '_ipv_channel_title', true);
        $duration = get_post_meta($post->ID, '_ipv_duration', true);
        $views = get_post_meta($post->ID, '_ipv_view_count', true);
        $likes = get_post_meta($post->ID, '_ipv_like_count', true);
        $comments = get_post_meta($post->ID, '_ipv_comment_count', true);

        ?>
        <div style="line-height: 2;">
            <?php if ($video_id): ?>
                <p><strong>Video ID:</strong><br><code><?php echo esc_html($video_id); ?></code></p>
                <p><strong>URL:</strong><br><a href="<?php echo esc_url($video_url); ?>" target="_blank"><?php echo esc_html($video_url); ?></a></p>

                <?php if ($channel): ?>
                    <p><strong>Canale:</strong><br><?php echo esc_html($channel); ?></p>
                <?php endif; ?>

                <?php if ($duration): ?>
                    <p><strong>Durata:</strong><br><?php echo esc_html($this->format_duration($duration)); ?></p>
                <?php endif; ?>

                <hr>

                <?php if ($views): ?>
                    <p><strong>👁️ Visualizzazioni:</strong><br><?php echo number_format_i18n($views); ?></p>
                <?php endif; ?>

                <?php if ($likes): ?>
                    <p><strong>👍 Mi piace:</strong><br><?php echo number_format_i18n($likes); ?></p>
                <?php endif; ?>

                <?php if ($comments): ?>
                    <p><strong>💬 Commenti:</strong><br><?php echo number_format_i18n($comments); ?></p>
                <?php endif; ?>

                <hr>

                <p>
                    <a href="<?php echo esc_url($video_url); ?>" class="button button-secondary" target="_blank">
                        🎬 Apri su YouTube
                    </a>
                </p>
            <?php else: ?>
                <p style="opacity: 0.6;">Nessun dato video disponibile.</p>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Render processing metabox
     */
    public function render_processing_metabox($post) {
        $processing_status = get_post_meta($post->ID, '_ipv_processing_status', true);
        $processing_error = get_post_meta($post->ID, '_ipv_processing_error', true);
        $ai_content = get_post_meta($post->ID, '_ipv_ai_content_full', true);
        $queue_id = get_post_meta($post->ID, '_ipv_queue_id', true);

        ?>
        <div style="padding: 10px;">
            <p><strong>Stato Elaborazione:</strong>
                <span style="padding: 4px 10px; background: #0073aa; color: white; border-radius: 3px; font-weight: bold;">
                    <?php echo esc_html($processing_status ?: 'N/A'); ?>
                </span>
            </p>

            <?php if ($processing_error): ?>
                <div style="background: #fcf8e3; border-left: 4px solid #f0ad4e; padding: 12px; margin: 10px 0;">
                    <strong>⚠️ Errore:</strong><br>
                    <?php echo esc_html($processing_error); ?>
                </div>
            <?php endif; ?>

            <?php if ($queue_id): ?>
                <p><strong>Queue ID:</strong> #<?php echo esc_html($queue_id); ?></p>
            <?php endif; ?>

            <hr>

            <p><strong>Azioni Rapide:</strong></p>
            <p>
                <a href="<?php echo wp_nonce_url(admin_url('admin-post.php?action=ipv_regenerate_ai&post_id=' . $post->ID), 'ipv_regenerate_ai_' . $post->ID); ?>"
                   class="button button-primary">
                    🔄 Rigenera Contenuti AI
                </a>

                <a href="<?php echo wp_nonce_url(admin_url('admin-post.php?action=ipv_regenerate_transcript&post_id=' . $post->ID), 'ipv_regenerate_transcript_' . $post->ID); ?>"
                   class="button button-secondary">
                    📝 Rigenera Trascrizione
                </a>

                <a href="<?php echo wp_nonce_url(admin_url('admin-post.php?action=ipv_export_notion&post_id=' . $post->ID), 'ipv_export_notion_' . $post->ID); ?>"
                   class="button button-secondary">
                    📋 Esporta Notion
                </a>
            </p>

            <?php if ($ai_content): ?>
                <hr>
                <details>
                    <summary style="cursor: pointer; font-weight: bold;">📄 Vedi Contenuto AI Completo</summary>
                    <div style="max-height: 400px; overflow-y: auto; background: #f9f9f9; padding: 15px; margin-top: 10px; border-radius: 4px;">
                        <pre style="white-space: pre-wrap; font-size: 12px;"><?php echo esc_html($ai_content); ?></pre>
                    </div>
                </details>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Render transcript metabox
     */
    public function render_transcript_metabox($post) {
        $transcript = get_post_meta($post->ID, '_ipv_transcript', true);
        $transcript_stats = get_post_meta($post->ID, '_ipv_transcript_stats', true);

        ?>
        <div style="padding: 10px;">
            <?php if ($transcript_stats): ?>
                <p>
                    <strong>Statistiche:</strong>
                    <?php echo esc_html($transcript_stats); ?>
                </p>
            <?php endif; ?>

            <?php if ($transcript): ?>
                <div style="max-height: 400px; overflow-y: auto; background: #f9f9f9; padding: 15px; border-radius: 4px;">
                    <pre style="white-space: pre-wrap; font-size: 12px; line-height: 1.6;"><?php echo esc_html($transcript); ?></pre>
                </div>
            <?php else: ?>
                <p style="opacity: 0.6;">Nessuna trascrizione disponibile.</p>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Save metabox data (placeholder)
     */
    public function save_metabox_data($post_id) {
        // Meta data is saved automatically by the queue manager
        // This function is here for future custom fields
    }

    /**
     * Update post messages
     */
    public function updated_messages($messages) {
        $post = get_post();

        $messages['ipv_video'] = [
            0  => '',
            1  => 'Video aggiornato.',
            2  => 'Campo personalizzato aggiornato.',
            3  => 'Campo personalizzato eliminato.',
            4  => 'Video aggiornato.',
            5  => isset($_GET['revision']) ? sprintf('Video ripristinato alla revisione del %s', wp_post_revision_title((int) $_GET['revision'], false)) : false,
            6  => 'Video pubblicato.',
            7  => 'Video salvato.',
            8  => 'Video inviato.',
            9  => sprintf('Video programmato per: <strong>%1$s</strong>.', date_i18n('j M Y @ G:i', strtotime($post->post_date))),
            10 => 'Bozza video aggiornata.'
        ];

        return $messages;
    }

    /**
     * Helper: Format duration (PT1H2M3S to 1:02:03)
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
     * Helper: Get YouTube category name
     */
    private function get_youtube_category_name($category_id) {
        $categories = [
            '1' => 'Film & Animation',
            '2' => 'Autos & Vehicles',
            '10' => 'Music',
            '15' => 'Pets & Animals',
            '17' => 'Sports',
            '19' => 'Travel & Events',
            '20' => 'Gaming',
            '22' => 'People & Blogs',
            '23' => 'Comedy',
            '24' => 'Entertainment',
            '25' => 'News & Politics',
            '26' => 'Howto & Style',
            '27' => 'Education',
            '28' => 'Science & Technology',
            '29' => 'Nonprofits & Activism'
        ];

        return isset($categories[$category_id]) ? $categories[$category_id] : "ID $category_id";
    }

    /**
     * Regenerate AI content for a post
     */
    private function regenerate_ai_content($post_id) {
        $transcript = get_post_meta($post_id, '_ipv_transcript', true);
        $video_data = [
            'video_id' => get_post_meta($post_id, '_ipv_video_id', true),
            'title' => get_post_meta($post_id, '_ipv_video_title', true),
            'description' => get_post_meta($post_id, '_ipv_video_description', true),
            'channel_title' => get_post_meta($post_id, '_ipv_channel_title', true),
            'category_id' => get_post_meta($post_id, '_ipv_category_id', true),
            'tags' => get_post_meta($post_id, '_ipv_tags', true)
        ];

        if (!$transcript) {
            return new WP_Error('no_transcript', 'Nessuna trascrizione disponibile');
        }

        $openai_api = new IPV_OpenAI_API();
        $ai_content = $openai_api->generate_content($transcript, $video_data);

        if (!is_wp_error($ai_content)) {
            // Save new AI content
            update_post_meta($post_id, '_ipv_ai_content_full', $ai_content['full_content']);

            // Update individual sections
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

            // Update post with new AI content
            wp_update_post([
                'ID' => $post_id,
                'post_title' => $ai_content['title'] ?? get_the_title($post_id),
                'post_excerpt' => $ai_content['description'] ?? ''
            ]);

            // Update taxonomies from AI content
            $this->update_taxonomies_from_ai($post_id, $ai_content);
        }

        return $ai_content;
    }

    /**
     * Regenerate transcript for a post
     */
    private function regenerate_transcript($post_id) {
        $video_url = get_post_meta($post_id, '_ipv_video_url', true);

        if (!$video_url) {
            return new WP_Error('no_video_url', 'URL video non disponibile');
        }

        $supadata_api = new IPV_SupaData_API();
        $transcript = $supadata_api->generate_transcript($video_url);

        if (!is_wp_error($transcript)) {
            $formatted_transcript = $supadata_api->format_transcript($transcript);
            update_post_meta($post_id, '_ipv_transcript', $formatted_transcript);
            update_post_meta($post_id, '_ipv_transcript_stats', $supadata_api->get_stats($transcript));
        }

        return $transcript;
    }

    /**
     * Export post to Notion format
     */
    private function export_to_notion($post_id) {
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
            'references' => get_post_meta($post_id, '_ipv_ai_references', true)
        ];

        // Build Notion-formatted export
        $notion_export = "# " . $video_data['title'] . "\n\n";
        $notion_export .= "**Video URL:** " . $video_data['url'] . "\n\n";
        $notion_export .= "## Descrizione\n\n" . $video_data['description'] . "\n\n";

        if ($video_data['topics']) {
            $notion_export .= "## Argomenti\n\n" . $video_data['topics'] . "\n\n";
        }

        if ($video_data['guests']) {
            $notion_export .= "## Ospiti\n\n" . $video_data['guests'] . "\n\n";
        }

        if ($video_data['timestamps']) {
            $notion_export .= "## Capitoli\n\n" . $video_data['timestamps'] . "\n\n";
        }

        if ($video_data['quotes']) {
            $notion_export .= "## Citazioni\n\n" . $video_data['quotes'] . "\n\n";
        }

        if ($video_data['transcript']) {
            $notion_export .= "## Trascrizione\n\n" . $video_data['transcript'] . "\n\n";
        }

        // Save as downloadable file
        update_post_meta($post_id, '_ipv_notion_export', $notion_export);

        return $notion_export;
    }

    /**
     * Update taxonomies from AI content
     */
    private function update_taxonomies_from_ai($post_id, $ai_content) {
        // Extract and assign topics
        if (!empty($ai_content['topics'])) {
            $topics = $this->extract_items_from_text($ai_content['topics']);
            wp_set_object_terms($post_id, $topics, 'ipv_topic');
        }

        // Extract and assign guests
        if (!empty($ai_content['guests'])) {
            $guests = $this->extract_items_from_text($ai_content['guests']);
            wp_set_object_terms($post_id, $guests, 'ipv_guest');
        }

        // Extract and assign channel themes
        if (!empty($ai_content['temi_canale'])) {
            $themes = $this->extract_items_from_text($ai_content['temi_canale']);
            wp_set_object_terms($post_id, $themes, 'ipv_channel_theme');
        }

        // Extract and assign hashtags as post tags
        if (!empty($ai_content['hashtags'])) {
            $hashtags = explode(' ', $ai_content['hashtags']);
            $tags = array_map(function($tag) {
                return ltrim($tag, '#');
            }, $hashtags);
            wp_set_post_tags($post_id, $tags);
        }
    }

    /**
     * Extract items from text (comma or newline separated)
     */
    private function extract_items_from_text($text) {
        // Remove markdown list markers
        $text = preg_replace('/^[\-\*\+]\s+/m', '', $text);
        $text = preg_replace('/^\d+\.\s+/m', '', $text);

        // Split by comma or newline
        $items = preg_split('/[,\n]+/', $text);

        // Clean and filter
        $items = array_map('trim', $items);
        $items = array_filter($items);
        $items = array_unique($items);

        return $items;
    }
}
