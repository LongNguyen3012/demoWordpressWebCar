<?php
/**
 * Single Banner Template
 */

get_header();
?>

<div class="single-banner">
    <div class="container">
        
        <?php if (have_posts()) : while (have_posts()) : the_post(); ?>
            
            <p><a href="<?php echo home_url('/'); ?>">← Back to Home</a></p>

            <h1><?php the_title(); ?></h1>
            
            <?php if (has_excerpt()) : ?>
                <p class="banner-subtitle"><?php echo get_the_excerpt(); ?></p>
            <?php endif; ?>
            
            <?php if (has_post_thumbnail()) : ?>
                <div class="banner-featured-image">
                    <?php the_post_thumbnail('large'); ?>
                </div>
            <?php endif; ?>
            
            <div class="banner-content">
                <?php the_content(); ?>
            </div>
            
        <?php endwhile; endif; ?>
        
    </div>
</div>

<?php
get_footer();