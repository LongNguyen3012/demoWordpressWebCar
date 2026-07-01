<?php
/**
 * Menu Functions
 * Custom menu builder with translations
 */

/**
 * Build a custom menu with translations
 */
function get_translated_menu($menu_name = 'Primary Menu') {
    $menu = wp_get_nav_menu_object($menu_name);
    if (!$menu) {
        return '<p>Menu not found.</p>';
    }
    
    $menu_items = wp_get_nav_menu_items($menu->term_id);
    if (!$menu_items) {
        return '<p>No menu items.</p>';
    }
    
    // Build tree
    $menu_tree = array();
    $children = array();
    
    foreach ($menu_items as $item) {
        if ($item->menu_item_parent == 0) {
            $menu_tree[] = $item;
        } else {
            $children[$item->menu_item_parent][] = $item;
        }
    }
    
    // Output with Astra's CSS classes
    $output = '<ul class="main-header-menu ast-nav-menu ast-flex ast-flex-wrap submenu-with-border">';
    foreach ($menu_tree as $item) {
        $output .= build_menu_item_html($item, $children);
    }
    $output .= '</ul>';
    
    return $output;
}

function build_menu_item_html($item, $children) {
    $has_children = isset($children[$item->ID]);
    $classes = 'menu-item';
    $classes .= ' ast-menu-item';
    if ($has_children) {
        $classes .= ' menu-item-has-children';
    }
    
    // Add active class if current page
    if (is_object($item) && isset($item->url) && (strpos($_SERVER['REQUEST_URI'], $item->url) !== false || is_front_page() && $item->url == home_url('/'))) {
        $classes .= ' current-menu-item';
    }
    
    $translated_title = __t($item->title, $item->title);
    
    $html = '<li class="' . $classes . '">';
    $html .= '<a href="' . esc_url($item->url) . '" class="menu-link">' . esc_html($translated_title) . '</a>';
    
    if ($has_children) {
        $html .= '<button class="ast-menu-toggle" aria-expanded="false" style="display: block;">';
        $html .= '<span class="screen-reader-text">Menu Toggle</span>';
        $html .= '<span class="ast-icon icon-arrow">▼</span>';
        $html .= '</button>';
        $html .= '<ul class="sub-menu">';
        foreach ($children[$item->ID] as $child) {
            $html .= build_submenu_item_html($child, $children);
        }
        $html .= '</ul>';
    }
    
    $html .= '</li>';
    return $html;
}

function build_submenu_item_html($item, $children) {
    $has_children = isset($children[$item->ID]);
    $classes = 'menu-item';
    $classes .= ' ast-menu-item';
    if ($has_children) {
        $classes .= ' menu-item-has-children';
    }
    
    $translated_title = __t($item->title, $item->title);
    
    $html = '<li class="' . $classes . '">';
    $html .= '<a href="' . esc_url($item->url) . '" class="menu-link">' . esc_html($translated_title) . '</a>';
    
    if ($has_children) {
        $html .= '<button class="ast-menu-toggle" aria-expanded="false" style="display: block;">';
        $html .= '<span class="screen-reader-text">Menu Toggle</span>';
        $html .= '<span class="ast-icon icon-arrow">▼</span>';
        $html .= '</button>';
        $html .= '<ul class="sub-menu">';
        foreach ($children[$item->ID] as $child) {
            $html .= build_submenu_item_html($child, $children);
        }
        $html .= '</ul>';
    }
    
    $html .= '</li>';
    return $html;
}
