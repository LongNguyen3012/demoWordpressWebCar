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
                
                // ===== GET THE FEATURED IMAGE ONLY =====
                $image_url = get_the_post_thumbnail_url(get_the_ID(), 'full');
                
                // If no featured image, use a dark background
                $bg_style = $image_url ? 'background-image: url(' . esc_url($image_url) . ');' : 'background-color: #1a1a1a;';
                
                // ===== GET THE BUTTON DATA =====
                $button_text = get_post_meta(get_the_ID(), 'banner_button_text', true);
                $button_url = get_post_meta(get_the_ID(), 'banner_button_url', true);
                
                if (empty($button_text)) {
                    $button_text = 'Learn More';
                }
            ?>
                <div class="hero-slide" style="<?php echo $bg_style; ?> background-size: cover; background-position: center;">
                    
                    <!-- ===== CONTENT (Title + Subtitle) ===== -->
                    <div class="hero-content-wrapper">
                        <div class="container">
                            <h1><?php the_title(); ?></h1>
                            <p><?php echo get_the_excerpt(); ?></p>
                        </div>
                    </div>
                    
                    <!-- ===== BUTTON ===== -->
                    <?php if (!empty($button_url)) : ?>
                        <div class="hero-button-bottom-left">
                            <a href="<?php echo esc_url($button_url); ?>" class="btn-primary">
                                <?php echo esc_html($button_text); ?>
                            </a>
                        </div>
                    <?php endif; ?>
                    
                    <!-- ===== PERMALINK LINK ===== -->
                    <a href="<?php the_permalink(); ?>" class="hero-slide-link"></a>
                    
                </div>
            <?php endwhile; ?>
        </div>
        <?php wp_reset_postdata(); ?>
    <?php else: ?>
        <section class="hero-banner" style="background-color: #1a1a1a;">
            <div class="container">
                <h1 style="color: #fff;">Add your first Banner</h1>
                <p style="color: #ccc;">Go to Banners → Add New in the admin.</p>
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
            <p>No cars added yet. Admin: Go to cars → Add New to create some.</p>
        </div>
    </section>
    <?php endif; ?>

    <!-- ===== NEWS SECTION ===== -->
    <section class="news-section">
        <div class="container">
            <h2 class="section-title">Latest News</h2>
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
                endif; ?>
            </div>
            
            <div style="text-align: center; margin-top: 30px;">
                <a href="<?php echo get_permalink( get_option( 'page_for_posts' ) ); ?>" class="btn-primary" style="background: #2C2C2C; color: #fff; padding: 12px 40px; text-decoration: none; text-transform: uppercase; letter-spacing: 2px; display: inline-block;">
                    View All News →
                </a>
            </div>
        </div>
    </section>

</div>

<?php
get_footer();