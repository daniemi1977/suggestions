# Guida Integrazione con Tema Influencer

Guida completa per integrare **IPV Production System Pro** con il tema **Influencer** di WordPress.

## 📋 Indice

1. [Panoramica](#panoramica)
2. [Prerequisiti](#prerequisiti)
3. [Installazione Plugin](#installazione-plugin)
4. [Integrazione Template](#integrazione-template)
5. [Personalizzazione Stile](#personalizzazione-stile)
6. [Widget e Shortcode](#widget-e-shortcode)
7. [Hook Personalizzati](#hook-personalizzati)
8. [Ottimizzazioni Performance](#ottimizzazioni-performance)
9. [Troubleshooting](#troubleshooting)

---

## 🎯 Panoramica

Questo plugin è stato progettato per funzionare con qualsiasi tema WordPress, incluso il tema Influencer. L'integrazione permette di:

- ✅ Importare automaticamente video YouTube come post
- ✅ Generare contenuti AI ottimizzati per il tema
- ✅ Mantenere lo stile grafico del tema Influencer
- ✅ Aggiungere widget personalizzati per video recenti
- ✅ Ottimizzare SEO e performance

---

## 📦 Prerequisiti

### Plugin Richiesti
- **IPV Production System Pro** v2.0.0+
- WordPress 5.8+
- PHP 7.4+

### Tema
- **Influencer Theme** (qualsiasi versione compatibile con Gutenberg)

### API Keys Necessarie
- YouTube Data API v3
- SupaData Transcription API
- OpenAI API (GPT-4o-mini consigliato)

---

## 🚀 Installazione Plugin

### Metodo 1: Upload ZIP
1. Vai su **WordPress Admin → Plugin → Aggiungi nuovo**
2. Click su **Carica plugin**
3. Seleziona `ipv-production-system-pro-v2.0.0-COMPLETE.zip`
4. Click **Installa ora**
5. Click **Attiva**

### Metodo 2: FTP
1. Carica la cartella `ipv-production-system-pro` in `/wp-content/plugins/`
2. Vai su **WordPress Admin → Plugin**
3. Attiva **IPV Production System Pro**

### Configurazione Iniziale
1. Vai su **IPV Production → Impostazioni**
2. Inserisci le API Keys:
   - YouTube API Key
   - SupaData API Key
   - OpenAI API Key
3. Testa le connessioni cliccando sui bottoni "Testa Connessione"
4. Configura le impostazioni:
   - Modalità trascrizione: `Auto` (consigliato)
   - Modello OpenAI: `gpt-4o-mini`
   - Pubblicazione automatica: Abilitata/Disabilitata secondo preferenza
   - Mapping categorie YouTube → WordPress

---

## 🎨 Integrazione Template

### Opzione A: Utilizzare i Template del Plugin (Consigliato)

Il plugin include un sistema di template che funziona automaticamente con qualsiasi tema. Non serve modificare i file del tema Influencer.

**Il plugin utilizza automaticamente:**
- Template WordPress standard (`single.php`, `archive.php`)
- Custom fields per metadati video
- Shortcode per embed video YouTube

### Opzione B: Template Personalizzati per Tema Influencer

Se vuoi template specifici per il tema Influencer, crea questi file nella cartella del tema child:

#### 1. Single Post Template per Video (`single-video.php`)

```php
<?php
/**
 * Template per post video importati da IPV Production System Pro
 * Compatibile con Tema Influencer
 */

get_header(); ?>

<main id="main" class="site-main influencer-video-content">
    <?php while (have_posts()) : the_post();
        // Recupera metadati video
        $video_url = get_post_meta(get_the_ID(), '_ipv_video_url', true);
        $video_id = get_post_meta(get_the_ID(), '_ipv_video_id', true);
        $channel = get_post_meta(get_the_ID(), '_ipv_channel_title', true);
        $views = get_post_meta(get_the_ID(), '_ipv_view_count', true);
        $duration = get_post_meta(get_the_ID(), '_ipv_duration', true);
        $published = get_post_meta(get_the_ID(), '_ipv_published_at', true);
    ?>

    <article id="post-<?php the_ID(); ?>" <?php post_class('ipv-video-post'); ?>>

        <!-- Video Header -->
        <header class="entry-header">
            <h1 class="entry-title"><?php the_title(); ?></h1>

            <!-- Video Meta -->
            <div class="ipv-video-meta">
                <?php if ($channel): ?>
                    <span class="ipv-channel">📺 <?php echo esc_html($channel); ?></span>
                <?php endif; ?>

                <?php if ($views): ?>
                    <span class="ipv-views">👁️ <?php echo number_format($views); ?> visualizzazioni</span>
                <?php endif; ?>

                <?php if ($duration): ?>
                    <span class="ipv-duration">⏱️ <?php echo gmdate("H:i:s", $duration); ?></span>
                <?php endif; ?>

                <span class="ipv-date">📅 <?php echo get_the_date(); ?></span>
            </div>
        </header>

        <!-- Video Embed -->
        <?php if ($video_url): ?>
        <div class="ipv-video-container">
            <div class="ipv-video-responsive">
                <iframe
                    src="https://www.youtube.com/embed/<?php echo esc_attr($video_id); ?>"
                    frameborder="0"
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                    allowfullscreen>
                </iframe>
            </div>
        </div>
        <?php endif; ?>

        <!-- Post Content (AI Generated) -->
        <div class="entry-content">
            <?php the_content(); ?>
        </div>

        <!-- Post Footer -->
        <footer class="entry-footer">
            <?php
            // Tags
            the_tags('<div class="ipv-tags">🏷️ ', ', ', '</div>');

            // Categorie
            $categories = get_the_category();
            if ($categories) {
                echo '<div class="ipv-categories">📁 ';
                foreach ($categories as $category) {
                    echo '<a href="' . get_category_link($category->term_id) . '">' . $category->name . '</a> ';
                }
                echo '</div>';
            }

            // Link originale YouTube
            if ($video_url) {
                echo '<div class="ipv-source">';
                echo '<a href="' . esc_url($video_url) . '" target="_blank" rel="noopener">🔗 Guarda su YouTube</a>';
                echo '</div>';
            }
            ?>
        </footer>

    </article>

    <?php endwhile; ?>
</main>

<?php get_sidebar(); ?>
<?php get_footer(); ?>
```

#### 2. Archive Template per Video (`archive-video.php`)

```php
<?php
/**
 * Archive Template per video IPV
 * Compatibile con Tema Influencer
 */

get_header(); ?>

<div class="influencer-archive-video">
    <header class="page-header">
        <h1 class="page-title">📹 Video Recenti</h1>
    </header>

    <div class="ipv-video-grid">
        <?php if (have_posts()) :
            while (have_posts()) : the_post();
                $video_id = get_post_meta(get_the_ID(), '_ipv_video_id', true);
                $thumbnail = get_post_meta(get_the_ID(), '_ipv_thumbnail_maxres', true);
                $views = get_post_meta(get_the_ID(), '_ipv_view_count', true);
        ?>

        <article class="ipv-video-card">
            <a href="<?php the_permalink(); ?>" class="ipv-video-thumb">
                <?php if ($thumbnail): ?>
                    <img src="<?php echo esc_url($thumbnail); ?>" alt="<?php the_title_attribute(); ?>">
                <?php else: ?>
                    <?php the_post_thumbnail('large'); ?>
                <?php endif; ?>
            </a>

            <div class="ipv-video-info">
                <h2 class="ipv-video-title">
                    <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                </h2>

                <div class="ipv-video-excerpt">
                    <?php echo wp_trim_words(get_the_excerpt(), 20); ?>
                </div>

                <div class="ipv-video-stats">
                    <?php if ($views): ?>
                        <span>👁️ <?php echo number_format($views); ?></span>
                    <?php endif; ?>
                    <span>📅 <?php echo get_the_date(); ?></span>
                </div>
            </div>
        </article>

        <?php endwhile; ?>
    </div>

    <?php
    // Paginazione
    the_posts_pagination(array(
        'prev_text' => '← Precedenti',
        'next_text' => 'Successivi →',
    ));
    ?>

    <?php else: ?>
        <p>Nessun video disponibile al momento.</p>
    <?php endif; ?>
</div>

<?php get_footer(); ?>
```

### Dove Mettere i Template

**Opzione 1: Child Theme (Consigliato)**
```
/wp-content/themes/influencer-child/
    ├── single-video.php
    ├── archive-video.php
    └── functions.php
```

**Opzione 2: Plugin Template Override**
```
/wp-content/plugins/ipv-production-system-pro/templates/
    ├── single-video.php
    └── archive-video.php
```

---

## 🎨 Personalizzazione Stile

### CSS Personalizzato per Tema Influencer

Aggiungi questo CSS nel file `style.css` del child theme o in **Aspetto → Personalizza → CSS Aggiuntivo**:

```css
/* ========================================
   IPV Production System Pro - Tema Influencer
   ======================================== */

/* Video Container Responsive */
.ipv-video-container {
    position: relative;
    margin: 30px 0;
    background: #000;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 10px 40px rgba(0,0,0,0.2);
}

.ipv-video-responsive {
    position: relative;
    padding-bottom: 56.25%; /* 16:9 ratio */
    height: 0;
    overflow: hidden;
}

.ipv-video-responsive iframe {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
}

/* Video Meta */
.ipv-video-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    margin: 20px 0;
    padding: 15px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 8px;
    color: #fff;
    font-size: 14px;
}

.ipv-video-meta span {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 5px 12px;
    background: rgba(255,255,255,0.2);
    border-radius: 20px;
    backdrop-filter: blur(10px);
}

/* Video Grid Archive */
.ipv-video-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 30px;
    margin: 30px 0;
}

.ipv-video-card {
    background: #fff;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.ipv-video-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 30px rgba(0,0,0,0.15);
}

.ipv-video-thumb {
    display: block;
    position: relative;
    overflow: hidden;
    padding-top: 56.25%; /* 16:9 ratio */
}

.ipv-video-thumb img {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.ipv-video-card:hover .ipv-video-thumb img {
    transform: scale(1.05);
}

.ipv-video-info {
    padding: 20px;
}

.ipv-video-title {
    font-size: 18px;
    font-weight: 700;
    margin: 0 0 10px 0;
    line-height: 1.4;
}

.ipv-video-title a {
    color: #333;
    text-decoration: none;
    transition: color 0.2s ease;
}

.ipv-video-title a:hover {
    color: #667eea;
}

.ipv-video-excerpt {
    font-size: 14px;
    color: #666;
    margin-bottom: 15px;
    line-height: 1.6;
}

.ipv-video-stats {
    display: flex;
    gap: 15px;
    font-size: 13px;
    color: #999;
}

/* Tags e Categories */
.ipv-tags,
.ipv-categories {
    margin: 15px 0;
    font-size: 14px;
}

.ipv-tags a,
.ipv-categories a {
    display: inline-block;
    padding: 5px 12px;
    margin: 0 5px 5px 0;
    background: #f0f0f0;
    border-radius: 20px;
    color: #666;
    text-decoration: none;
    transition: all 0.2s ease;
}

.ipv-tags a:hover,
.ipv-categories a:hover {
    background: #667eea;
    color: #fff;
}

/* Source Link */
.ipv-source {
    margin-top: 20px;
    padding-top: 20px;
    border-top: 1px solid #eee;
}

.ipv-source a {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 20px;
    background: #FF0000;
    color: #fff;
    text-decoration: none;
    border-radius: 8px;
    font-weight: 600;
    transition: background 0.2s ease;
}

.ipv-source a:hover {
    background: #cc0000;
}

/* Responsive */
@media (max-width: 768px) {
    .ipv-video-grid {
        grid-template-columns: 1fr;
    }

    .ipv-video-meta {
        flex-direction: column;
        gap: 10px;
    }
}
```

---

## 📱 Widget e Shortcode

### Widget: Video Recenti

Il plugin include un widget "Video Recenti IPV" che puoi aggiungere alla sidebar del tema Influencer:

1. Vai su **Aspetto → Widget**
2. Trascina **"IPV Video Recenti"** nella sidebar desiderata
3. Configura:
   - Titolo widget
   - Numero di video da mostrare (default: 5)
   - Mostra thumbnail (sì/no)
   - Mostra statistiche (sì/no)

### Shortcode Disponibili

#### `[ipv_video_player]`
Mostra il player video YouTube con controlli personalizzati.

```php
// Nel content o in un template
echo do_shortcode('[ipv_video_player id="' . get_the_ID() . '"]');
```

**Parametri:**
- `id` - ID del post (default: post corrente)
- `autoplay` - Avvio automatico (default: 0)
- `controls` - Mostra controlli (default: 1)

**Esempio:**
```php
[ipv_video_player id="123" autoplay="0" controls="1"]
```

#### `[ipv_recent_videos]`
Mostra griglia video recenti.

```php
[ipv_recent_videos count="6" columns="3"]
```

**Parametri:**
- `count` - Numero video (default: 6)
- `columns` - Colonne griglia (default: 3)
- `category` - Filtra per categoria ID

**Esempio:**
```php
[ipv_recent_videos count="9" columns="3" category="5"]
```

#### `[ipv_video_stats]`
Mostra statistiche video.

```php
[ipv_video_stats id="123"]
```

---

## 🔌 Hook Personalizzati

### Actions

Il plugin fornisce hook per personalizzare il comportamento:

```php
// In functions.php del child theme

// Prima dell'elaborazione video
add_action('ipv_pro_before_video_processing', function($post_id, $video_url) {
    // Codice personalizzato
}, 10, 2);

// Dopo completamento elaborazione
add_action('ipv_pro_after_video_processing', function($post_id, $video_data) {
    // Invia notifica, aggiorna cache, etc.
}, 10, 2);

// Prima della generazione contenuti AI
add_action('ipv_pro_before_ai_generation', function($post_id, $transcript) {
    // Modifica trascrizione, aggiungi context, etc.
}, 10, 2);
```

### Filters

```php
// Modifica prompt OpenAI
add_filter('ipv_pro_openai_prompt', function($prompt, $video_data) {
    // Personalizza prompt per il tema Influencer
    $custom_instructions = "\nStile: professionale e coinvolgente per influencer.";
    return $prompt . $custom_instructions;
}, 10, 2);

// Modifica contenuto generato
add_filter('ipv_pro_generated_content', function($content, $post_id) {
    // Aggiungi sezioni personalizzate
    $content .= "\n\n" . ipv_get_influencer_cta();
    return $content;
}, 10, 2);

// Modifica meta dati video
add_filter('ipv_pro_video_metadata', function($meta, $youtube_data) {
    // Aggiungi metadati custom per tema Influencer
    $meta['influencer_featured'] = true;
    return $meta;
}, 10, 2);
```

### Esempio Completo: Integrazione Child Theme

```php
<?php
/**
 * Child Theme Functions - Tema Influencer + IPV Pro
 * File: /wp-content/themes/influencer-child/functions.php
 */

// Enqueue parent theme style
add_action('wp_enqueue_scripts', 'influencer_child_enqueue_styles');
function influencer_child_enqueue_styles() {
    wp_enqueue_style('parent-style', get_template_directory_uri() . '/style.css');
    wp_enqueue_style('child-style', get_stylesheet_uri(), array('parent-style'));
}

// IPV Pro: Personalizza prompt per Influencer theme
add_filter('ipv_pro_openai_prompt', 'influencer_customize_ipv_prompt', 10, 2);
function influencer_customize_ipv_prompt($prompt, $video_data) {
    $influencer_style = "

ISTRUZIONI STILE PER TEMA INFLUENCER:
- Usa un tono professionale ma coinvolgente
- Struttura contenuto in sezioni ben definite
- Aggiungi emoji pertinenti ai titoli delle sezioni
- Termina con una call-to-action per engagement
- Ottimizza per lettura su mobile
";

    return $prompt . $influencer_style;
}

// IPV Pro: Aggiungi CTA finale ai post
add_filter('ipv_pro_generated_content', 'influencer_add_cta_to_content', 10, 2);
function influencer_add_cta_to_content($content, $post_id) {
    $cta = '

---

## 💬 Cosa ne pensi?

Lascia un commento qui sotto e condividi la tua opinione! Non dimenticare di iscriverti al canale per non perdere i prossimi video.

**Seguici anche sui social:**
- 📱 Telegram: [t.me/ilpuntodivista](https://t.me/ilpuntodivista)
- 📘 Facebook: [facebook.com/ilpuntodivista](https://facebook.com/ilpuntodivista)
- 📸 Instagram: [@ilpuntodivista](https://instagram.com/ilpuntodivista)
';

    return $content . $cta;
}

// IPV Pro: Abilita thumbnail automatiche
add_action('ipv_pro_after_video_processing', 'influencer_set_featured_image', 10, 2);
function influencer_set_featured_image($post_id, $video_data) {
    // Verifica se thumbnail già impostata
    if (has_post_thumbnail($post_id)) {
        return;
    }

    // Usa thumbnail maxres di YouTube
    $thumbnail_url = get_post_meta($post_id, '_ipv_thumbnail_maxres', true);
    if (!$thumbnail_url) {
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
        'name' => 'youtube-thumb-' . $post_id . '.jpg',
        'tmp_name' => $tmp
    ];

    $attachment_id = media_handle_sideload($file_array, $post_id);

    if (!is_wp_error($attachment_id)) {
        set_post_thumbnail($post_id, $attachment_id);
    }

    @unlink($file_array['tmp_name']);
}

// Widget personalizzato: Video Sidebar Influencer
add_action('widgets_init', 'influencer_ipv_widgets');
function influencer_ipv_widgets() {
    register_sidebar(array(
        'name' => 'Video Sidebar Influencer',
        'id' => 'influencer-video-sidebar',
        'description' => 'Sidebar personalizzata per video IPV',
        'before_widget' => '<div class="influencer-video-widget">',
        'after_widget' => '</div>',
        'before_title' => '<h3 class="widget-title">',
        'after_title' => '</h3>',
    ));
}
```

---

## ⚡ Ottimizzazioni Performance

### Caching

Aggiungi caching per chiamate API e contenuti generati:

```php
// In functions.php

// Cache trascrizioni SupaData
add_filter('ipv_pro_cache_transcript', '__return_true');

// Cache contenuti OpenAI
add_filter('ipv_pro_cache_ai_content', '__return_true');

// Imposta durata cache (in secondi)
add_filter('ipv_pro_cache_duration', function() {
    return 7 * DAY_IN_SECONDS; // 1 settimana
});
```

### Lazy Loading

Abilita lazy loading per video embed:

```php
// Lazy load video YouTube
add_filter('ipv_pro_video_lazyload', '__return_true');
```

### CDN per Thumbnail

Usa CDN per thumbnail YouTube:

```php
// Ottimizza caricamento thumbnail
add_filter('ipv_pro_thumbnail_cdn', function($url) {
    // Usa CDN di immagini (es. Cloudflare, Cloudinary)
    return str_replace('i.ytimg.com', 'cdn.yourdomain.com', $url);
});
```

---

## 🐛 Troubleshooting

### Problema: Video non si vedono nel tema

**Soluzione 1:** Verifica che il tema supporti custom post type
```php
// In functions.php
add_theme_support('post-thumbnails');
add_post_type_support('post', 'thumbnail');
```

**Soluzione 2:** Flush rewrite rules
```php
// In functions.php (temporaneamente)
flush_rewrite_rules();
```

### Problema: Stile CSS non si applica

**Soluzione:** Aumenta specificità CSS o usa `!important`
```css
/* Più specifico */
.influencer-theme .ipv-video-container {
    /* stili */
}
```

### Problema: Widget non appare

**Soluzione:** Verifica che la sidebar sia registrata
```php
// Controlla sidebars attive
global $wp_registered_sidebars;
print_r($wp_registered_sidebars);
```

### Problema: Shortcode non funziona

**Soluzione:** Verifica che il plugin sia attivo e shortcode registrato
```php
// Testa shortcode
global $shortcode_tags;
if (isset($shortcode_tags['ipv_video_player'])) {
    echo "Shortcode registrato!";
}
```

### Problema: Errori JavaScript

**Soluzione:** Verifica conflitti jQuery
```php
// Dequeue script conflittuali
add_action('wp_enqueue_scripts', function() {
    wp_deregister_script('jquery');
    wp_register_script('jquery', 'https://code.jquery.com/jquery-3.6.0.min.js');
}, 100);
```

---

## 📞 Supporto

Per assistenza con l'integrazione:

1. **Documentazione Plugin:** Leggi `README.md` nella cartella plugin
2. **Verifica Configurazione:** Vai su **IPV Production → Impostazioni** e testa le API
3. **Log Errori:** Abilita `WP_DEBUG` in `wp-config.php`
4. **Queue Manager:** Controlla stato elaborazioni in **IPV Production → Video Manager**

---

## ✅ Checklist Post-Integrazione

- [ ] Plugin installato e attivato
- [ ] API keys configurate e testate
- [ ] Mapping categorie YouTube → WordPress configurato
- [ ] Template personalizzati creati (se necessario)
- [ ] CSS personalizzato aggiunto al child theme
- [ ] Widget "Video Recenti IPV" aggiunto alla sidebar
- [ ] Hook e filter personalizzati implementati
- [ ] Test import singolo video completato
- [ ] RSS auto-import configurato e testato
- [ ] Performance e caching ottimizzati
- [ ] Backup database e file eseguito

---

## 🎉 Risultato Finale

Dopo aver completato questa integrazione, avrai:

✅ **Sistema completamente automatizzato** per importare video YouTube
✅ **Design perfettamente integrato** con il tema Influencer
✅ **Contenuti AI ottimizzati** per engagement e SEO
✅ **Widget e shortcode** per massima flessibilità
✅ **Performance ottimizzate** con caching e lazy loading

Il tuo sito sarà pronto per pubblicare contenuti video in modo automatico, mantenendo la qualità e lo stile del tema Influencer!

---

**IPV Production System Pro v2.0.0**
*Developed for Il Punto di Vista Channel*
🌐 [ilpuntodivistachannel.com](https://ilpuntodivistachannel.com)
