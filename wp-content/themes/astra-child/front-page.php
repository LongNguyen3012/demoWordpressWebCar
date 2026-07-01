<?php
/**
 * Front page template for car demo site
 */

get_header();
?>

<div class="custom-homepage">
    
    <!-- ===== HERO BANNER SLIDER ===== -->
    <?php 
    $banners_query = new WP_Query(array(
        'post_type' => 'banner',
        'posts_per_page' => -1,
        'orderby' => 'menu_order',
        'order' => 'ASC',
    ));

    if ($banners_query->have_posts()) : ?>
        <div class="hero-slider">
            <?php while ($banners_query->have_posts()) : $banners_query->the_post(); 
                
                $image_url = get_the_post_thumbnail_url(get_the_ID(), 'full');
                $bg_style = $image_url ? 'background-image: url(' . esc_url($image_url) . ');' : 'background-color: #1a1a1a;';
                
                $button_text = get_post_meta(get_the_ID(), 'banner_button_text', true);
                $button_url = get_post_meta(get_the_ID(), 'banner_button_url', true);
                
                if (empty($button_text)) {
                    $button_text = __t('btn_learn_more');
                }
            ?>
                <div class="hero-slide" style="<?php echo $bg_style; ?> background-size: cover; background-position: center;">
                    
                    <div class="hero-content-wrapper">
                        <div class="container">
                            <h1><?php the_title(); ?></h1>
                            <p><?php echo get_the_excerpt(); ?></p>
                        </div>
                    </div>
                    
                    <?php if (!empty($button_url)) : ?>
                        <div class="hero-button-bottom-left">
                            <a href="<?php echo esc_url($button_url); ?>" class="btn-primary">
                                <?php echo esc_html($button_text); ?>
                            </a>
                        </div>
                    <?php endif; ?>
                    
                    <a href="<?php the_permalink(); ?>" class="hero-slide-link"></a>
                    
                </div>
            <?php endwhile; ?>
        </div>
        <?php wp_reset_postdata(); ?>
    <?php else: ?>
        <section class="hero-banner" style="background-color: #1a1a1a;">
            <div class="container">
                <h1 style="color: #fff;"><?php _te('home_title'); ?></h1>
                <p style="color: #ccc;"><?php _te('home_subtitle'); ?></p>
            </div>
        </section>
    <?php endif; ?>

    <!-- ===== CARS SLIDER ===== -->
    <?php 
    $cars_query = new WP_Query(array(
        'post_type' => 'car',
        'posts_per_page' => -1,
        'orderby' => 'date',
        'order' => 'ASC',
    ));

    if ($cars_query->have_posts()) : ?>
    <section class="cars-grid-wrapper">
        <h2 class="section-title" style="text-align:center;"><?php _te('cars_title'); ?></h2>
        <div class="cars-grid">
            <?php while ($cars_query->have_posts()) : $cars_query->the_post(); 
                $title = get_the_title();
                $excerpt = get_the_excerpt();
                $image_url = get_the_post_thumbnail_url(get_the_ID(), 'medium');
                if (empty($image_url)) {
                    $content = get_the_content();
                    $patterns = array(
                        '/<img.+src=[\'"]([^\'"]+)[\'"].*>/i',
                        '/<figure.+?<img.+src=[\'"]([^\'"]+)[\'"].*>/i',
                        '/<div.+?<img.+src=[\'"]([^\'"]+)[\'"].*>/i',
                    );
                    foreach ($patterns as $pattern) {
                        preg_match($pattern, $content, $matches);
                        if (!empty($matches[1])) {
                            $image_url = $matches[1];
                            break;
                        }
                    }
                }
                
                $brands = get_the_terms(get_the_ID(), 'car_brand');
                $brand = $brands && !is_wp_error($brands) ? esc_html($brands[0]->name) : '';
                
                $fuels = get_the_terms(get_the_ID(), 'car_fuel');
                $fuel = $fuels && !is_wp_error($fuels) ? esc_html($fuels[0]->name) : '';
            ?>
                <a href="<?php the_permalink(); ?>" class="section-card-link">
                    <div class="section-card layout-medium">
                        <?php if ($image_url): ?>
                        <div class="card-image">
                            <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($title); ?>">
                        </div>
                        <?php endif; ?>
                        <div class="card-content">
                            <h3><?php echo esc_html($title); ?></h3>
                            <p><?php echo esc_html($excerpt); ?></p>
                            <?php if ($brand) : ?>
                                <p><strong><?php _te('cars_brand', 'Brand'); ?>:</strong> <?php echo $brand; ?></p>
                            <?php endif; ?>
                            <?php if ($fuel) : ?>
                                <p><strong><?php _te('cars_fuel', 'Fuel'); ?>:</strong> <?php echo $fuel; ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </a>
            <?php endwhile; ?>
        </div>
    </section>
    <?php wp_reset_postdata(); ?>
    <?php else: ?>
    <section class="cars-grid-wrapper">
        <div class="cars-grid" style="text-align:center; padding:40px; background:#f9f9f9;">
            <p><?php _te('cars_no_cars'); ?></p>
        </div>
    </section>
    <?php endif; ?>

    <!-- ===== NEWS SECTION ===== -->
    <section class="news-section">
        <div class="container">
            <h2 class="section-title"><?php _te('news_title'); ?></h2>
            <div class="news-list">
                <?php 
                $news_query = new WP_Query(array('posts_per_page' => 3, 'post_type' => 'post'));
                if ($news_query->have_posts()) :
                    while ($news_query->have_posts()) : $news_query->the_post(); ?>
                        <div class="news-item">
                            <a href="<?php the_permalink(); ?>">
                                <span class="news-title"><?php the_title(); ?></span>
                                <span class="news-date"><?php echo get_the_date(); ?></span>
                            </a>
                        </div>
                    <?php endwhile; 
                    wp_reset_postdata();
                else: ?>
                    <p><?php _te('news_no_posts'); ?></p>
                <?php endif; ?>
            </div>
            
            <div style="text-align: center; margin-top: 30px;">
                <a href="<?php echo get_permalink( get_option( 'page_for_posts' ) ); ?>" class="btn-primary" style="background: #2C2C2C; color: #fff; padding: 12px 40px; text-decoration: none; text-transform: uppercase; letter-spacing: 2px; display: inline-block;">
                    <?php _te('btn_view_all'); ?> →
                </a>
            </div>
        </div>
    </section>

</div>

<?php
get_footer();