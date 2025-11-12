<?php
/**
 * Influencer Child Theme - IPV Integration
 * Functions and definitions
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Enqueue parent and child theme styles
 */
add_action('wp_enqueue_scripts', 'influencer_child_ipv_enqueue_styles');
function influencer_child_ipv_enqueue_styles() {
    // Parent theme style
    wp_enqueue_style('influencer-parent', get_template_directory_uri() . '/style.css', [], wp_get_theme()->parent()->get('Version'));

    // Child theme style
    wp_enqueue_style('influencer-child', get_stylesheet_uri(), ['influencer-parent'], wp_get_theme()->get('Version'));
}

/**
 * IPV Pro: Personalizza prompt OpenAI per stile Influencer
 */
add_filter('ipv_pro_openai_prompt', 'influencer_customize_ipv_prompt', 10, 2);
function influencer_customize_ipv_prompt($prompt, $video_data) {
    $influencer_instructions = "

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
📱 ISTRUZIONI STILE TEMA INFLUENCER
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

TONO E STILE:
✓ Professionale ma accessibile e coinvolgente
✓ Usa linguaggio diretto, come se parlassi con un amico
✓ Ottimizzato per lettura su mobile e tablet
✓ Struttura contenuto in sezioni visualmente distinte

FORMATTAZIONE:
✓ Usa emoji pertinenti nei titoli delle sezioni (non esagerare)
✓ Paragrafi brevi (max 3-4 righe)
✓ Liste puntate per concetti chiave
✓ Quote box per citazioni importanti
✓ Sezioni ben definite con titoli H2 e H3

STRUTTURA CONTENUTO:
1. Intro coinvolgente (2-3 frasi)
2. Punti chiave del video (lista)
3. Analisi approfondita (paragrafi ben spaziati)
4. Conclusioni e takeaway
5. Call-to-action per engagement

ENGAGEMENT:
✓ Termina sempre con domanda aperta ai lettori
✓ Includi invito a commentare
✓ Menziona link social e canali

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
";

    return $prompt . $influencer_instructions;
}

/**
 * IPV Pro: Aggiungi sezione CTA finale
 */
add_filter('ipv_pro_generated_content', 'influencer_add_cta_section', 10, 2);
function influencer_add_cta_section($content, $post_id) {
    $cta = '

---

## 💬 Partecipa alla Discussione!

**Cosa ne pensi di questo argomento?** Lascia un commento qui sotto e condividi la tua opinione! Ogni contributo è prezioso e arricchisce la nostra community.

### 🔔 Non Perderti i Prossimi Contenuti

**Iscriviti al canale** per ricevere notifiche sui nuovi video e articoli:
👉 [Iscriviti su YouTube](https://www.youtube.com/@ilpuntodivista)

### 📱 Seguici sui Social

Rimani connesso e scopri contenuti esclusivi:

- **Telegram**: [t.me/ilpuntodivista](https://t.me/ilpuntodivista) - Aggiornamenti in tempo reale
- **Facebook**: [facebook.com/ilpuntodivista](https://facebook.com/ilpuntodivista) - Community attiva
- **Instagram**: [@ilpuntodivista](https://instagram.com/ilpuntodivista) - Stories e dietro le quinte

### ❤️ Supporta il Canale

Se apprezzi i nostri contenuti, considera di supportarci:
- 👍 Metti Mi Piace ai video
- 💬 Commenta e condividi
- ⭐ Lascia una recensione

**Grazie per far parte della community Il Punto di Vista!** 🙏

';

    return $content . $cta;
}

/**
 * IPV Pro: Imposta automaticamente featured image da thumbnail YouTube
 */
add_action('ipv_pro_after_video_processing', 'influencer_auto_set_featured_image', 10, 2);
function influencer_auto_set_featured_image($post_id, $video_data) {
    // Verifica se già presente
    if (has_post_thumbnail($post_id)) {
        return;
    }

    // Ottieni thumbnail maxres
    $thumbnail_url = get_post_meta($post_id, '_ipv_thumbnail_maxres', true);

    if (empty($thumbnail_url)) {
        return;
    }

    // Scarica e imposta come featured image
    require_once(ABSPATH . 'wp-admin/includes/media.php');
    require_once(ABSPATH . 'wp-admin/includes/file.php');
    require_once(ABSPATH . 'wp-admin/includes/image.php');

    $tmp = download_url($thumbnail_url);

    if (is_wp_error($tmp)) {
        return;
    }

    $file_array = [
        'name' => 'youtube-thumbnail-' . $post_id . '.jpg',
        'tmp_name' => $tmp
    ];

    $attachment_id = media_handle_sideload($file_array, $post_id, 'Thumbnail YouTube');

    if (!is_wp_error($attachment_id)) {
        set_post_thumbnail($post_id, $attachment_id);
    }

    @unlink($file_array['tmp_name']);
}

/**
 * IPV Pro: Aggiungi schema markup per video
 */
add_action('wp_head', 'influencer_add_video_schema');
function influencer_add_video_schema() {
    if (!is_single()) {
        return;
    }

    $post_id = get_the_ID();
    $video_url = get_post_meta($post_id, '_ipv_video_url', true);

    if (empty($video_url)) {
        return;
    }

    $video_id = get_post_meta($post_id, '_ipv_video_id', true);
    $thumbnail = get_post_meta($post_id, '_ipv_thumbnail_maxres', true);
    $duration = get_post_meta($post_id, '_ipv_duration', true);
    $published = get_post_meta($post_id, '_ipv_published_at', true);

    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'VideoObject',
        'name' => get_the_title(),
        'description' => get_the_excerpt(),
        'thumbnailUrl' => $thumbnail,
        'uploadDate' => $published,
        'contentUrl' => $video_url,
        'embedUrl' => 'https://www.youtube.com/embed/' . $video_id,
    ];

    if ($duration) {
        $schema['duration'] = 'PT' . $duration . 'S';
    }

    echo '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . '</script>' . "\n";
}

/**
 * Registra sidebar custom per video
 */
add_action('widgets_init', 'influencer_register_video_sidebar');
function influencer_register_video_sidebar() {
    register_sidebar([
        'name' => 'Video Sidebar Influencer',
        'id' => 'influencer-video-sidebar',
        'description' => 'Sidebar personalizzata per pagine video IPV',
        'before_widget' => '<div id="%1$s" class="widget ipv-widget %2$s">',
        'after_widget' => '</div>',
        'before_title' => '<h3 class="widget-title">',
        'after_title' => '</h3>',
    ]);
}

/**
 * Usa sidebar video personalizzata per post video
 */
add_filter('sidebars_widgets', 'influencer_use_video_sidebar');
function influencer_use_video_sidebar($sidebars_widgets) {
    if (!is_single()) {
        return $sidebars_widgets;
    }

    $post_id = get_the_ID();
    $is_video = get_post_meta($post_id, '_ipv_video_id', true);

    if ($is_video && isset($sidebars_widgets['influencer-video-sidebar'])) {
        $sidebars_widgets['sidebar-1'] = $sidebars_widgets['influencer-video-sidebar'];
    }

    return $sidebars_widgets;
}

/**
 * Aggiungi classe body per post video
 */
add_filter('body_class', 'influencer_video_body_class');
function influencer_video_body_class($classes) {
    if (is_single()) {
        $video_id = get_post_meta(get_the_ID(), '_ipv_video_id', true);
        if (!empty($video_id)) {
            $classes[] = 'influencer-theme';
            $classes[] = 'ipv-video-post';
        }
    }
    return $classes;
}

/**
 * Custom excerpt length per video cards
 */
add_filter('excerpt_length', 'influencer_video_excerpt_length', 999);
function influencer_video_excerpt_length($length) {
    if (is_archive() || is_home()) {
        $video_id = get_post_meta(get_the_ID(), '_ipv_video_id', true);
        if (!empty($video_id)) {
            return 20; // Più breve per video cards
        }
    }
    return $length;
}

/**
 * Aggiungi meta box info video nell'editor
 */
add_action('add_meta_boxes', 'influencer_add_video_meta_box');
function influencer_add_video_meta_box() {
    add_meta_box(
        'ipv_video_info',
        '📹 Informazioni Video IPV',
        'influencer_render_video_meta_box',
        'post',
        'side',
        'high'
    );
}

function influencer_render_video_meta_box($post) {
    $video_id = get_post_meta($post->ID, '_ipv_video_id', true);

    if (empty($video_id)) {
        echo '<p>Questo non è un post video IPV.</p>';
        return;
    }

    $video_url = get_post_meta($post->ID, '_ipv_video_url', true);
    $channel = get_post_meta($post->ID, '_ipv_channel_title', true);
    $views = get_post_meta($post->ID, '_ipv_view_count', true);
    $likes = get_post_meta($post->ID, '_ipv_like_count', true);
    $duration = get_post_meta($post->ID, '_ipv_duration', true);

    ?>
    <div class="ipv-meta-box">
        <p><strong>🆔 Video ID:</strong><br><?php echo esc_html($video_id); ?></p>

        <?php if ($video_url): ?>
        <p><strong>🔗 URL Originale:</strong><br>
            <a href="<?php echo esc_url($video_url); ?>" target="_blank">Apri su YouTube</a>
        </p>
        <?php endif; ?>

        <?php if ($channel): ?>
        <p><strong>📺 Canale:</strong><br><?php echo esc_html($channel); ?></p>
        <?php endif; ?>

        <?php if ($views): ?>
        <p><strong>👁️ Visualizzazioni:</strong><br><?php echo number_format((int)$views); ?></p>
        <?php endif; ?>

        <?php if ($likes): ?>
        <p><strong>👍 Mi Piace:</strong><br><?php echo number_format((int)$likes); ?></p>
        <?php endif; ?>

        <?php if ($duration): ?>
        <p><strong>⏱️ Durata:</strong><br><?php echo gmdate("H:i:s", $duration); ?></p>
        <?php endif; ?>

        <hr>
        <p><em>Importato da IPV Production System Pro</em></p>
    </div>
    <style>
        .ipv-meta-box p { margin: 10px 0; font-size: 13px; }
        .ipv-meta-box strong { color: #667eea; }
    </style>
    <?php
}

/**
 * Notifica email personalizzata per nuovi video
 */
add_action('ipv_pro_after_video_processing', 'influencer_send_video_notification', 20, 2);
function influencer_send_video_notification($post_id, $video_data) {
    // Verifica se notifiche abilitate
    $enabled = get_option('ipv_pro_auto_import_email_notifications', false);

    if (!$enabled) {
        return;
    }

    $admin_email = get_option('admin_email');
    $post_title = get_the_title($post_id);
    $post_url = get_permalink($post_id);
    $edit_url = get_edit_post_link($post_id);

    $subject = '📹 Nuovo Video Pubblicato: ' . $post_title;

    $message = "
Ciao,

Un nuovo video è stato importato e pubblicato sul tuo sito:

📹 Titolo: {$post_title}
🔗 URL: {$post_url}
✏️ Modifica: {$edit_url}

Il contenuto è stato generato automaticamente da IPV Production System Pro.
Ti consigliamo di rivedere il post prima della pubblicazione definitiva.

---
Questo messaggio è stato inviato automaticamente da IPV Production System Pro
Tema: Influencer Child
";

    wp_mail($admin_email, $subject, $message);
}

/**
 * Shortcode personalizzato: Griglia video recenti Influencer
 */
add_shortcode('influencer_video_grid', 'influencer_video_grid_shortcode');
function influencer_video_grid_shortcode($atts) {
    $atts = shortcode_atts([
        'count' => 6,
        'columns' => 3,
        'category' => ''
    ], $atts);

    // Usa shortcode IPV base con classe wrapper personalizzata
    $content = do_shortcode('[ipv_recent_videos count="' . $atts['count'] . '" columns="' . $atts['columns'] . '" category="' . $atts['category'] . '"]');

    return '<div class="influencer-video-section">' . $content . '</div>';
}

/**
 * Aggiungi supporto per funzionalità WordPress
 */
add_action('after_setup_theme', 'influencer_child_setup');
function influencer_child_setup() {
    // Post thumbnails (se non già abilitato)
    add_theme_support('post-thumbnails');

    // HTML5 support
    add_theme_support('html5', ['search-form', 'comment-form', 'comment-list', 'gallery', 'caption']);

    // Title tag
    add_theme_support('title-tag');

    // Custom logo
    add_theme_support('custom-logo', [
        'height' => 100,
        'width' => 400,
        'flex-height' => true,
        'flex-width' => true,
    ]);
}

/**
 * Ottimizzazione performance: Lazy load immagini video
 */
add_filter('wp_get_attachment_image_attributes', 'influencer_add_lazy_load', 10, 2);
function influencer_add_lazy_load($attr, $attachment) {
    if (!isset($attr['loading'])) {
        $attr['loading'] = 'lazy';
    }
    return $attr;
}

/**
 * Debug: Log importazione video (solo in development)
 */
if (defined('WP_DEBUG') && WP_DEBUG) {
    add_action('ipv_pro_before_video_processing', 'influencer_debug_video_import', 10, 2);
    function influencer_debug_video_import($post_id, $video_url) {
        error_log('IPV: Inizio elaborazione video - Post ID: ' . $post_id . ', URL: ' . $video_url);
    }

    add_action('ipv_pro_after_video_processing', 'influencer_debug_video_complete', 10, 2);
    function influencer_debug_video_complete($post_id, $video_data) {
        error_log('IPV: Elaborazione completata - Post ID: ' . $post_id);
    }
}
