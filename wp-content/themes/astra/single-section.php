<?php
/**
 * Single Section Template (Detailed View)
 */

get_header();
?>

<div class="single-section">
    <div class="container">
        
        <?php if (have_posts()) : while (have_posts()) : the_post(); ?>
            
            <!-- Back to Sections Link -->
            <p><a href="<?php echo home_url('/sections/'); ?>">← Back to all sections</a></p>

            <!-- Title -->
            <h1><?php the_title(); ?></h1>
            
            <!-- Featured Image (Full Size) -->
            <?php if (has_post_thumbnail()) : ?>
                <div class="section-featured-image">
                    <?php the_post_thumbnail('large'); ?>
                </div>
            <?php endif; ?>
            
            <!-- Excerpt (Short Description) -->
            <div class="section-excerpt">
                <p><strong><?php the_excerpt(); ?></strong></p>
            </div>
            
            <!-- Full Content (Detailed Description) -->
            <div class="section-content">
                <?php the_content(); ?>
            </div>
            
            <!-- Optional: Add custom fields later (e.g., Price, Link, etc.) -->
            
        <?php endwhile; endif; ?>
        
    </div>
</div>

<?php
get_footer();