<?php
/**
 * Single Post Template - Influencer Child Theme
 * Ottimizzato per video IPV Production System Pro
 *
 * Questo template è OPZIONALE. Il child theme funziona anche senza questo file
 * grazie ai filter in functions.php. Usa questo template solo se vuoi
 * un controllo maggiore sul layout dei post video.
 */

get_header();
get_template_part('framework/templates/site', 'titlebar');

// Verifica se è un post video IPV
$is_video_post = false;
$video_id = '';
if (have_posts()) {
    the_post();
    $video_id = get_post_meta(get_the_ID(), '_ipv_video_id', true);
    $is_video_post = !empty($video_id);
    rewind_posts();
}
?>

<main id="bt_main" class="bt-site-main <?php echo $is_video_post ? 'bt-video-post' : ''; ?>">
    <div class="bt-main-content-ss">
        <div class="bt-container">
            <div class="bt-main-post-row">
                <div class="bt-main-post-col">
                    <?php
                    while (have_posts()) : the_post();
                    ?>
                        <div class="bt-main-post <?php echo $is_video_post ? 'bt-ipv-video-post' : ''; ?>">

                            <?php if ($is_video_post): ?>
                                <!-- VIDEO POST LAYOUT -->

                                <?php
                                // Se il plugin IPV è attivo, usa le sue funzioni
                                if (class_exists('IPV_Theme_Integration')) {
                                    // Video Player Embed
                                    IPV_Theme_Integration::the_video_player();

                                    // Video Meta Bar
                                    IPV_Theme_Integration::the_video_meta();
                                }
                                ?>

                                <!-- Post Content (AI Generated) -->
                                <div class="bt-post-content">
                                    <?php the_content(); ?>
                                </div>

                                <?php
                                // Pagination per contenuti lunghi
                                wp_link_pages([
                                    'before' => '<div class="page-links">' . esc_html__('Pages:', 'influencers'),
                                    'after'  => '</div>',
                                ]);
                                ?>

                            <?php else: ?>
                                <!-- STANDARD POST LAYOUT -->
                                <?php get_template_part('framework/templates/post'); ?>
                            <?php endif; ?>

                        </div>

                        <?php
                        // Tags, Share, Author, Related - Per TUTTI i post
                        echo influencers_tags_render();
                        echo influencers_share_render();
                        echo influencers_author_render();
                        echo influencers_related_posts();

                        // Comments
                        if (comments_open() || get_comments_number()) {
                            comments_template();
                        }
                        ?>

                    <?php
                    endwhile;
                    ?>
                </div>

                <div class="bt-sidebar-col">
                    <div class="bt-sidebar">
                        <?php
                        // La sidebar viene automaticamente switchata per video post
                        // tramite filter in functions.php
                        if (is_active_sidebar('main-sidebar')) {
                            dynamic_sidebar('main-sidebar');
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php get_template_part('framework/templates/social', 'media-channels'); ?>
</main><!-- #main -->

<?php get_footer(); ?>
