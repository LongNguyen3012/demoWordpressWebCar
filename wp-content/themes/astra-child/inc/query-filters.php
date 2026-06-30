<?php
/**
 * Query Filters for news
 */

add_action('pre_get_posts', 'custom_news_filter');
function custom_news_filter($query) {
    if (!is_admin() && $query->is_main_query() && (is_archive() || is_home())) {
        if (isset($_GET['cat']) && !empty($_GET['cat'])) {
            $query->set('cat', intval($_GET['cat']));
        }
        if (isset($_GET['s']) && !empty($_GET['s'])) {
            $query->set('s', sanitize_text_field($_GET['s']));
            $query->set('post_type', 'post');
        }
    }
}