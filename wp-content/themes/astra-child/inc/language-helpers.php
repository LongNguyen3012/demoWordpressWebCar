<?php
/**
 * Language Helper Functions
 */
function __t($key, $default = '') {
    $lang = Language::get_instance();
    return $lang->get($key, $default);
}

function _te($key, $default = '') {
    echo esc_html(__t($key, $default));
}

function get_current_lang() {
    $lang = Language::get_instance();
    return $lang->get_current_language();
}

function get_language_switcher() {
    $lang = Language::get_instance();
    $output = '<div class="language-switcher-inline">';
    foreach ($lang->get_available_languages() as $code) {
        if ($code === $lang->get_current_language()) {
            $output .= '<span class="lang-active">' . esc_html($lang->get_language_name($code)) . '</span> ';
        } else {
            $output .= '<a href="' . esc_url($lang->get_switch_url($code)) . '">' . esc_html($lang->get_language_name($code)) . '</a> ';
        }
    }
    $output .= '</div>';
    return $output;
}

function get_language_switcher_frontend() {
    $lang = Language::get_instance();
    $current_lang = $lang->get_current_language();
    $available = $lang->get_available_languages();
    $names = $lang->language_names;

    $output = '<div class="language-switcher">';
    foreach ($available as $code) {
        $url = add_query_arg('lang', $code, $_SERVER['REQUEST_URI']);
        $class = ($code === $current_lang) ? 'active' : '';
        $output .= '<a href="' . esc_url($url) . '" class="' . $class . '">' . esc_html($names[$code]) . '</a>';
        if ($code !== end($available)) {
            $output .= ' | ';
        }
    }
    $output .= '</div>';
    return $output;
}

function get_translated_field($post_id, $field, $lang = null, $fallback = true) {
    if (!$lang) {
        $lang = get_current_lang();
    }
    $default_lang = 'en';
    
    $field_map = array(
        'title'   => 'post_title',
        'content' => 'post_content',
        'excerpt' => 'post_excerpt',
    );
    $real_field = isset($field_map[$field]) ? $field_map[$field] : $field;
    
    if ($lang === $default_lang) {
        return get_post_field($real_field, $post_id);
    }
    
    $meta_key = '_' . $field . '_' . $lang;
    $translation = get_post_meta($post_id, $meta_key, true);
    if (!empty($translation)) {
        return $translation;
    }
    
    if ($fallback) {
        return get_post_field($real_field, $post_id);
    }
    return '';
}

function get_translated_title($post_id, $lang = null) {
    return get_translated_field($post_id, 'title', $lang);
}

function get_translated_content($post_id, $lang = null) {
    return get_translated_field($post_id, 'content', $lang);
}

function get_translated_excerpt($post_id, $lang = null) {
    return get_translated_field($post_id, 'excerpt', $lang);
}

function get_translated_meta($post_id, $meta_key, $lang = null) {
    if (!$lang) {
        $lang = get_current_lang();
    }
    $default_lang = 'en';
    if ($lang === $default_lang) {
        return get_post_meta($post_id, $meta_key, true);
    }
    $translated_key = $meta_key . '_' . $lang;
    $translation = get_post_meta($post_id, $translated_key, true);
    if (!empty($translation)) {
        return $translation;
    }
    return get_post_meta($post_id, $meta_key, true);
}

