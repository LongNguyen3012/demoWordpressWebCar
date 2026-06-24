<?php
/**
 * Single Post Template (only for standard posts)
 */

get_header();
?>

<div class="single-news">
    <div class="container">
        
        <?php if (have_posts()) : while (have_posts()) : the_post(); ?>
            
            <h1><?php the_title(); ?></h1>
            <div class="post-meta">
                <span><?php echo get_the_date(); ?></span>
                <span>| Categories: <?php echo get_the_category_list(', '); ?></span>
            </div>
            
            <?php if (has_post_thumbnail()) : ?>
                <div class="post-thumbnail">
                    <?php the_post_thumbnail('large'); ?>
                </div>
            <?php endif; ?>
            
            <div class="post-content">
                <?php the_content(); ?>
            </div>
            
            <?php
            $categories = wp_get_post_categories(get_the_ID());
            if (!empty($categories)) {
                $related_args = array(
                    'category__in' => $categories,
                    'post__not_in' => array(get_the_ID()),
                    'posts_per_page' => 4,
                    'orderby' => 'rand',
                );

                $related_query = new WP_Query($related_args);
                if ($related_query->have_posts()) : ?>
                    <div class="related-posts">
                        <h3>Related News</h3>
                        <div class="related-grid">
                            <?php while ($related_query->have_posts()) : $related_query->the_post(); ?>
                                <div class="related-card">
                                    <a href="<?php the_permalink(); ?>">
                                        <?php if (has_post_thumbnail()) : ?>
                                            <?php the_post_thumbnail('medium'); ?>
                                        <?php endif; ?>
                                        <h4><?php the_title(); ?></h4>
                                    </a>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    </div>
                    <?php wp_reset_postdata(); ?>
                <?php endif;
            }
            ?>
            
        <?php endwhile; endif; ?>
    </div>
</div>

<?php
get_footer();