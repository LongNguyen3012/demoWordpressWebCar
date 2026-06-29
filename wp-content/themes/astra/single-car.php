<?php
/**
 * Single Car Template
 */

get_header();
?>

<div class="single-section">
    <div class="container">
        
        <?php if (have_posts()) : while (have_posts()) : the_post(); ?>
            
            <p><a href="<?php echo home_url('/'); ?>">← Back to Home</a></p>

            <h1><?php the_title(); ?></h1>
            
            <?php if (has_post_thumbnail()) : ?>
                <div class="section-featured-image">
                    <?php the_post_thumbnail('large'); ?>
                </div>
            <?php endif; ?>
            
            <div class="section-car">
                <?php the_content(); ?>
            </div>
            
        <?php endwhile; endif; ?>
        
    </div>
</div>

<?php
get_footer();