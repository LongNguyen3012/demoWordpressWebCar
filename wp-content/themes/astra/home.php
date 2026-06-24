<?php
/**
 * Main Blog / News page template
 * This is used when "Posts page" is set in Settings > Reading
 */

get_header();
?>

<div class="news-archive">
    <div class="container">
        
        <h1 class="page-title">All News</h1>
        
        <!-- ===== SEPARATED FILTER TABS + SEARCH ===== -->
        <div class="news-filters">
            <!-- Category Filter Tabs (Horizontal List) -->
            <ul class="filter-tabs">
                <li class="<?php echo !isset($_GET['cat']) ? 'active' : ''; ?>">
                    <a href="<?php echo get_permalink(get_option('page_for_posts')); ?>">All</a>
                </li>
                <?php 
                $categories = get_categories();
                foreach ($categories as $category) {
                    $active = (isset($_GET['cat']) && $_GET['cat'] == $category->term_id) ? 'active' : '';
                    echo '<li class="' . $active . '">';
                    echo '<a href="?cat=' . $category->term_id . '">' . $category->name . '</a>';
                    echo '</li>';
                }
                ?>
            </ul>

            <!-- Search Form (Separate) -->
            <div class="search-wrapper">
                <form method="get" action="<?php echo home_url('/'); ?>" class="search-form">
                    <input type="hidden" name="post_type" value="post" />
                    <input type="text" name="s" placeholder="Search news..." value="<?php echo get_search_query(); ?>" />
                    <button type="submit">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#333" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="11" cy="11" r="8"/>
                            <path d="M21 21l-4.35-4.35"/>
                        </svg>
                    </button>
                </form>
            </div>
        </div>

        <!-- ===== NEWS GRID ===== -->
        <?php if (have_posts()) : ?>
            <div class="news-grid">
                <?php while (have_posts()) : the_post(); ?>
                    <div class="news-card">
                        <?php if (has_post_thumbnail()) : ?>
                            <a href="<?php the_permalink(); ?>">
                                <?php the_post_thumbnail('medium'); ?>
                            </a>
                        <?php endif; ?>
                        <div class="news-card-body">
                            <span class="news-category"><?php echo get_the_category_list(', '); ?></span>
                            <h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
                            <p><?php echo wp_trim_words(get_the_excerpt(), 20, '...'); ?></p>
                            <a href="<?php the_permalink(); ?>" class="read-more">Read More →</a>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
            
            <!-- ===== PAGINATION ===== -->
            <div class="news-pagination">
                <?php the_posts_pagination(array(
                    'mid_size' => 2,
                    'prev_text' => '← Previous',
                    'next_text' => 'Next →',
                )); ?>
            </div>
            
        <?php else : ?>
            <p>No news found. Try adjusting your filters.</p>
        <?php endif; ?>
    </div>
</div>

<?php
get_footer();