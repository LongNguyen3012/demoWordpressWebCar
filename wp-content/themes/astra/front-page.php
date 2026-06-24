<?php
/**
 * Front page template for car demo site
 */

get_header();
?>

<div class="custom-homepage">
    
    <!-- ===== 1. HERO BANNER ===== -->
    <section class="hero-banner" style="background-image: url('<?php echo esc_url(get_field('banner_background_image')); ?>');">
        <div class="container">
            <h1><?php echo esc_html(get_field('banner_title')); ?></h1>
            <p><?php echo esc_html(get_field('banner_subtitle')); ?></p>
            <?php 
            $button_url = get_field('banner_button_url');
            $button_text = get_field('banner_button_text');
            if ($button_url && $button_text): ?>
                <a href="<?php echo esc_url($button_url); ?>" class="btn-primary"><?php echo esc_html($button_text); ?></a>
            <?php endif; ?>
        </div>
    </section>

    <!-- ===== 2. INTRODUCTION ===== -->
    <?php if (get_field('intro_title') || get_field('intro_text')): ?>
    <section class="intro-section">
        <div class="container">
            <h2 class="section-title"><?php echo esc_html(get_field('intro_title')); ?></h2>
            <p class="intro-text"><?php echo esc_html(get_field('intro_text')); ?></p>
        </div>
    </section>
    <?php endif; ?>

    <!-- ===== 3. SECTIONS GRID ===== -->
     <?php 
    $sections_query = new WP_Query(array(
        'post_type' => 'section',
        'posts_per_page' => -1, // Show all sections
        'orderby' => 'date',
        'order' => 'ASC',
    ));

    if ($sections_query->have_posts()) : ?>
    <section class="sections-grid-wrapper">
        <div class="sections-grid">
            <?php while ($sections_query->have_posts()) : $sections_query->the_post(); 
                $title = get_the_title();
                $excerpt = get_the_excerpt(); // Short description for the card
                $image = get_the_post_thumbnail_url(get_the_ID(), 'medium');
                $layout = 'medium'; // You can add an ACF field to CPT later if you want sizes
            ?>
                <div class="section-card layout-<?php echo esc_attr($layout); ?>">
                    <?php if ($image): ?>
                    <a href="<?php the_permalink(); ?>">
                        <div class="card-image">
                            <img src="<?php echo esc_url($image); ?>" alt="<?php echo esc_attr($title); ?>">
                        </div>
                    </a>
                    <?php endif; ?>
                    <div class="card-content">
                        <h3><a href="<?php the_permalink(); ?>"><?php echo esc_html($title); ?></a></h3>
                        <p><?php echo esc_html($excerpt); ?></p>
                        <a href="<?php the_permalink(); ?>" class="read-more">View Details →</a>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </section>
    <?php wp_reset_postdata(); ?>
    <?php else: ?>
    <section class="sections-grid-wrapper">
        <div class="sections-grid" style="text-align:center; padding:40px; background:#f9f9f9;">
            <p>No sections added yet. <strong>Admin:</strong> Go to <strong>Sections → Add New</strong> to create some.</p>
        </div>
    </section>
    <?php endif; ?>

    <!-- ===== 4. NEWS SECTION ===== -->
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
            
            <!-- View All Button -->
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