<?php
get_header();
?>

<div class="single-banner">
    <div class="container">
        
        <?php if (have_posts()) : while (have_posts()) : the_post(); ?>
            
            <p><a href="<?php echo home_url('/'); ?>">← <?php _te('back_to_home', 'Back to Home'); ?></a></p>

            <h1><?php echo esc_html(get_translated_title(get_the_ID())); ?></h1>
            
            <?php if (has_excerpt()) : ?>
                <p class="banner-subtitle"><?php echo esc_html(get_translated_excerpt(get_the_ID())); ?></p>
            <?php endif; ?>
            
            <?php if (has_post_thumbnail()) : ?>
                <div class="banner-featured-image">
                    <?php the_post_thumbnail('large'); ?>
                </div>
            <?php endif; ?>
            
            <div class="banner-content">
                <?php echo apply_filters('the_content', get_translated_content(get_the_ID())); ?>
            </div>
            
        <?php endwhile; endif; ?>
        
    </div>
</div>

<?php
get_footer();