<?php

add_filter('nav_menu_item_title', 'translate_menu_item_title', 10, 4);
function translate_menu_item_title($title, $item, $args, $depth) {
    if ($item->ID === 'language-switcher' || $item->ID === 99999) {
        $lang = Language::get_instance();
        return esc_html($lang->get_language_name($lang->get_current_language()));
    }
    $current_lang = Language::get_instance()->get_current_language();
    if ($current_lang === 'en') {
        return $title;
    }
    return __t($title, $title);
}

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

add_filter('the_title', 'translate_archive_post_titles', 10, 2);
function translate_archive_post_titles($title, $post_id) {
    if (is_home() && in_the_loop() && !is_admin()) {
        $translated = get_translated_title($post_id);
        if (!empty($translated)) {
            return $translated;
        }
    }
    return $title;
}

add_filter('get_the_excerpt', 'translate_archive_post_excerpts', 10, 2);
function translate_archive_post_excerpts($excerpt, $post) {
    if (is_home() && in_the_loop() && !is_admin()) {
        $translated = get_translated_excerpt($post->ID);
        if (!empty($translated)) {
            return $translated;
        }
    }
    return $excerpt;
}

add_action('wp', function() {
    if (!is_home()) return;
    ob_start(function($html) {
        $current_lang = Language::get_instance()->get_current_language();
        if ($current_lang === 'en') {
            return $html;
        }
        $html = str_replace('All News', __t('All News', 'All News'), $html);
        $html = str_replace('All Categories', __t('All Categories', 'All Categories'), $html);
        $html = str_replace('Read More →', __t('news_read_more', 'Read More →'), $html);
        $placeholder = __t('Search news...', 'Search news...');
        $html = preg_replace('/placeholder="[^"]*"/', 'placeholder="' . esc_attr($placeholder) . '"', $html);
        return $html;
    });
}, 1);

add_filter('wp_nav_menu', 'add_language_switcher_to_menu', 10, 2);
function add_language_switcher_to_menu($nav_menu, $args) {
    if ($args->theme_location !== 'primary' && $args->menu_id !== 'ast-hf-menu-1') {
        return $nav_menu;
    }
    $lang = Language::get_instance();
    $current_lang = $lang->get_current_language();
    $available = $lang->get_available_languages();
    if (count($available) <= 1) {
        return $nav_menu;
    }
    $switcher = '<li class="menu-item menu-item-language menu-item-has-children">';
    $switcher .= '<a href="#" class="menu-link">' . esc_html($lang->get_language_name($current_lang)) . ' <span class="ast-icon icon-arrow">▼</span></a>';
    $switcher .= '<ul class="sub-menu">';
    foreach ($available as $code) {
        if ($code === $current_lang) continue;
        $url = add_query_arg('lang', $code, home_url($_SERVER['REQUEST_URI']));
        $switcher .= '<li class="menu-item"><a href="' . esc_url($url) . '" class="menu-link">' . esc_html($lang->get_language_name($code)) . '</a></li>';
    }
    $switcher .= '</ul></li>';
    return preg_replace('/<\/ul>/', $switcher . '</ul>', $nav_menu);
}

add_filter('get_terms', 'translate_term_names_in_get_terms', 10, 3);
function translate_term_names_in_get_terms($terms, $taxonomies, $args) {
    if (is_admin()) {
        return $terms;
    }
    $lang = get_current_lang();
    if ($lang === 'en') {
        return $terms;
    }
    foreach ($terms as $term) {
        if (is_object($term) && property_exists($term, 'term_id')) {
            $translated = get_translated_term_name($term->term_id, $lang);
            if (!empty($translated)) {
                $term->name = $translated;
            }
        }
    }
    return $terms;
}