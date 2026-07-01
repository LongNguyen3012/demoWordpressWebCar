<?php
/**
 * Translate menu items using Astra's nav walker filter
 */

add_filter('nav_menu_item_title', 'translate_menu_item_title', 10, 4);
function translate_menu_item_title($title, $item, $args, $depth) {
    return __t($title, $title);
}