<?php
/**
 * Translation Filters
 */

// ============================================
// MENU TRANSLATIONS
// ============================================
add_filter('nav_menu_item_title', 'translate_menu_item_title', 10, 4);
function translate_menu_item_title($title, $item, $args, $depth) {
    $lang = isset($_GET['lang']) ? $_GET['lang'] : 'en';
    if ($lang === 'en') {
        return $title;
    }
    return __t($title, $title);
}

// ============================================
// NEWS TRANSLATIONS
// ============================================
add_action('wp', function() {
    if (is_home()) {
        ob_start(function($html) {
            $search = 'All News';
            $replace = __t('All News', 'All News');
            $html = str_replace($search, $replace, $html);
            return $html;
        });
    }
}, 1);

add_filter('astra_default_strings', function($strings) {
    if (isset($strings['string-blog-no-posts'])) {
        $strings['string-blog-no-posts'] = __t('news_no_posts', 'No news articles found.');
    }
    return $strings;
});

add_filter('the_posts_pagination_args', function($args) {
    $args['prev_text'] = __t('← Previous', '← Previous');
    $args['next_text'] = __t('Next →', 'Next →');
    return $args;
});

// ============================================
// SEARCH PLACEHOLDER
// ============================================
add_filter('get_search_form', function($form) {
    // Replace the placeholder text
    $form = str_replace('Search news...', __t('Search news...', 'Search news...'), $form);
    $form = str_replace('Search', __t('Search', 'Search'), $form);
    return $form;
});

// ============================================
// ALL CATEGORIES - OUTPUT BUFFER (FALLBACK)
// ============================================
add_action('wp', function() {
    if (is_home() || is_archive()) {
        ob_start(function($html) {
            $search = 'All Categories';
            $replace = __t('All Categories', 'All Categories');
            return str_replace($search, $replace, $html);
        });
    }
}, 1);

// ============================================
// PRESERVE LANGUAGE IN ALL URLS
// ============================================
add_filter('the_permalink', function($url) {
    $lang = isset($_GET['lang']) ? $_GET['lang'] : '';
    if ($lang) {
        $url = add_query_arg('lang', $lang, $url);
    }
    return $url;
});

add_filter('get_pagenum_link', function($url) {
    $lang = isset($_GET['lang']) ? $_GET['lang'] : '';
    if ($lang) {
        $url = add_query_arg('lang', $lang, $url);
    }
    return $url;
});