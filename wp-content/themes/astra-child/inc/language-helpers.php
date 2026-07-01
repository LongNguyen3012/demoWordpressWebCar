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