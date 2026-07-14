<?php
get_header();
?>

<div class="custom-homepage">
    
    <?php 
    $banners_query = new WP_Query(array(
        'post_type' => 'banner',
        'posts_per_page' => -1,
        'orderby' => 'menu_order',
        'order' => 'ASC',
    ));

    if ($banners_query->have_posts()) : ?>
        <div class="hero-slider-wrapper">
            <div class="hero-slider">
                <?php while ($banners_query->have_posts()) : $banners_query->the_post(); 
                    $image_url = get_the_post_thumbnail_url(get_the_ID(), 'full');
                    $bg_style = $image_url ? 'background-image: url(' . esc_url($image_url) . ');' : 'background-color: #1a1a1a;';
                    $button_text = get_translated_meta(get_the_ID(), 'banner_button_text');
                    $button_url = get_translated_meta(get_the_ID(), 'banner_button_url');
                    if (empty($button_text)) $button_text = __t('btn_learn_more');
                    $permalink = get_permalink();
                ?>
                    <div class="hero-slide" style="<?php echo $bg_style; ?> background-size: contain; background-position: center; background-repeat: no-repeat; background-color: #1a1a1a;">
                        <a href="<?php echo esc_url($permalink); ?>" class="hero-slide-link"></a>
                        <div class="hero-content-wrapper">
                            <div class="container">
                                <h1><?php echo esc_html(get_translated_title(get_the_ID())); ?></h1>
                                <p><?php echo esc_html(get_translated_excerpt(get_the_ID())); ?></p>
                                <?php if (!empty($button_url)) : ?>
                                    <a href="<?php echo esc_url($button_url); ?>" class="btn-primary"><?php echo esc_html($button_text); ?></a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
            <button class="hero-prev">‹</button>
            <button class="hero-next">›</button>
            <div class="hero-dots"></div>
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
                $title = get_translated_title(get_the_ID());
                $excerpt = get_translated_excerpt(get_the_ID());
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
                $brand = '';
                if ($brands && !is_wp_error($brands)) {
                    $brand = esc_html(get_translated_term_name($brands[0]->term_id));
                }

                $fuels = get_the_terms(get_the_ID(), 'car_fuel');
                $fuel = '';
                if ($fuels && !is_wp_error($fuels)) {
                    $fuel = esc_html(get_translated_term_name($fuels[0]->term_id));
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
                                <span class="news-title"><?php echo esc_html(get_translated_title(get_the_ID())); ?></span>
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

    <section class="scraped-news-section">
        <div class="container">
            <h2 class="section-title"><?php _te('scraped_news_title_home', 'Latest Automotive News'); ?></h2>
            
            <?php 
            if (class_exists('News_Scraper')) {
                $scraper = new News_Scraper();
                $data = $scraper->fetch_all();
                error_log('Data from scraper: ' . print_r($data, true));

                if (!empty($data) && !empty($data['articles'])) {
                    $all_articles = [];
                    foreach ($data['articles'] as $category_articles) {
                        $all_articles = array_merge($all_articles, $category_articles);
                    }
                    usort($all_articles, function($a, $b) {
                        return strcmp($a['time'] ?? '', $b['time'] ?? '');
                    });
                    $home_articles = array_slice($all_articles, 0, 5);
                    ?>
                    <div class="news-list scraped-news-list">
                        <?php foreach ($home_articles as $item) : ?>
                            <div class="news-item scraped-news-item">
                                <a href="<?php echo esc_url($item['link']); ?>" target="_blank" rel="noopener noreferrer">
                                    <span class="news-title"><?php echo esc_html($item['title']); ?></span>
                                    <span class="news-date">
                                        <?php echo esc_html($item['time'] ?: ''); ?>
                                        <span class="news-source-badge"><?php _te('scraper_source', 'Source'); ?>: <?php echo esc_html($item['source']); ?></span>
                                    </span>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php
                } else {
                    echo '<p>' . __t('scraped_no_news', 'No car news available at the moment.') . '</p>';
                }
            } else {
                echo '<p>' . __t('scraped_no_news', 'No car news available at the moment.') . '</p>';
            }
            ?>

            <div style="text-align: center; margin-top: 30px;">
                <a href="<?php echo get_permalink(get_page_by_path('scraped-news')); ?>" class="btn-primary" style="background: #2C2C2C; color: #fff; padding: 12px 40px; text-decoration: none; text-transform: uppercase; letter-spacing: 2px; display: inline-block;">
                    <?php _te('scraped_view_all', 'View All Automotive News'); ?> →
                </a>
            </div>
        </div>
    </section>

    <!-- ===== NEW GAME CTA SECTION (Option A) ===== -->
    <?php
    // Ensure the helper function exists (fallback if not defined)
    if (!function_exists('get_game_page_url')) {
        function get_game_page_url() {
            $pages = get_pages(array(
                'meta_key'   => '_wp_page_template',
                'meta_value' => 'page-game.php',
                'number'     => 1,
            ));
            if (!empty($pages)) {
                return get_permalink($pages[0]->ID);
            }
            $slug_page = get_page_by_path('driving-game');
            if ($slug_page) {
                return get_permalink($slug_page);
            }
            $slug_page = get_page_by_path('game');
            if ($slug_page) {
                return get_permalink($slug_page);
            }
            return '#';
        }
    }
    ?>
    <section class="game-cta-section">
        <div class="container" style="text-align:center; padding:60px 20px; background:#f0f0f0; border-radius:8px; margin:40px 0;">
            <h2 style="font-size:2rem; margin-bottom:20px;"><?php _te('game_cta_title', 'Ready for a Midnight Drive?'); ?></h2>
            <p style="font-size:1.2rem; margin-bottom:30px; color:#555;"><?php _te('game_cta_desc', 'Test your reflexes in our fast‑paced driving game. How far can you go?'); ?></p>
            <a href="<?php echo esc_url(get_game_page_url()); ?>" class="btn-primary" style="padding:16px 50px; font-size:1.2rem; text-transform:uppercase; letter-spacing:2px; background:#2C2C2C; color:#fff; text-decoration:none; border-radius:6px; display:inline-block;">
                <?php _te('game_play_now', 'Play Now →'); ?>
            </a>
        </div>
    </section>

</div><!-- .custom-homepage -->

<?php
get_footer();