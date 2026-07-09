<?php
get_header();

$scraper = new News_Scraper();
$data = $scraper->fetch_all();
$last_updated = $data['last_updated'] ?? '';
?>
<div class="scraped-news-page">
    <div class="container">
        <div class="page-header">
            <h1><?php _te('scraped_news_title', 'Latest Automotive News'); ?></h1>
            <?php if ($last_updated) : ?>
                <p class="last-updated">
                    <?php _te('scraped_last_updated', 'Last updated'); ?>: <?php echo esc_html($last_updated); ?>
                </p>
            <?php endif; ?>
        </div>

        <div class="scraped-news-search">
            <input type="text" id="scraped-news-search" placeholder="<?php _te('scraped_search_placeholder', 'Search news...'); ?>" />
        </div>

        <?php echo do_shortcode('[advanced_car_news]'); ?>
    </div>
</div>
<?php get_footer(); ?>  