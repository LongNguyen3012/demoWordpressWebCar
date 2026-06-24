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

    <!-- ===== 3. SECTIONS SLIDER (Repeater) ===== -->
    <?php if (have_rows('home_sections')): ?>
    <section class="sections-grid-wrapper">
        <div class="sections-grid">
            <?php while (have_rows('home_sections')): the_row(); 
                $title = get_sub_field('section_title');
                $content = get_sub_field('section_content');
                $image = get_sub_field('section_image');
                $layout = get_sub_field('section_layout') ?: 'medium';
            ?>
                <div class="section-card layout-<?php echo esc_attr($layout); ?>">
                    <?php if ($image): ?>
                    <div class="card-image">
                        <img src="<?php echo esc_url(wp_get_attachment_url($image)); ?>" alt="<?php echo esc_attr($title); ?>">
                    </div>
                    <?php endif; ?>
                    <div class="card-content">
                        <h3><?php echo esc_html($title); ?></h3>
                        <p><?php echo esc_html($content); ?></p>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </section>
    <?php else: ?>
    <section class="sections-grid-wrapper">
        <div class="sections-grid" style="text-align:center; padding:40px; background:#f9f9f9;">
            <p>No sections added yet. Admin: Edit the Home page and add rows in the Repeater.</p>
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