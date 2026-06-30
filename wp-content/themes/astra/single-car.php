<?php
get_header();
?>

<div class="single-car">
    <div class="container">
        
        <?php if (have_posts()) : while (have_posts()) : the_post(); ?>
            
            <p><a href="<?php echo home_url('/'); ?>">← Back to Home</a></p>

            <h1><?php the_title(); ?></h1>
            
            <?php if (has_post_thumbnail()) : ?>
                <div class="car-featured-image">
                    <?php the_post_thumbnail('large'); ?>
                </div>
            <?php endif; ?>
            
            <?php
            $brands = get_the_terms(get_the_ID(), 'car_brand');
            if ($brands && !is_wp_error($brands)) {
                echo '<p><strong>Brand:</strong> ';
                $brand_list = array();
                foreach ($brands as $brand) {
                    $brand_list[] = '<a href="' . get_term_link($brand) . '">' . esc_html($brand->name) . '</a>';
                }
                echo implode(', ', $brand_list);
                echo '</p>';
            }

            $fuels = get_the_terms(get_the_ID(), 'car_fuel');
            if ($fuels && !is_wp_error($fuels)) {
                echo '<p><strong>Fuel:</strong> ';
                $fuel_list = array();
                foreach ($fuels as $fuel) {
                    $fuel_list[] = '<a href="' . get_term_link($fuel) . '">' . esc_html($fuel->name) . '</a>';
                }
                echo implode(', ', $fuel_list);
                echo '</p>';
            }
            ?>
            
            <div class="car-details">
                <?php the_content(); ?>
            </div>
            
        <?php endwhile; endif; ?>
        
    </div>
</div>

<?php
get_footer();