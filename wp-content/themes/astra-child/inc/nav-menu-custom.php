<?php

add_filter('wp_nav_menu_objects', 'add_cars_archive_menu_item', 10, 2);
function add_cars_archive_menu_item($items, $args) {
    if ($args->theme_location !== 'primary') {
        return $items;
    }

    $car_archive_url = get_post_type_archive_link('car');
    if (empty($car_archive_url)) {
        return $items;
    }

    foreach ($items as $item) {
        if ($item->url === $car_archive_url) {
            return $items;
        }
    }

    $new_item = new stdClass();
    $new_item->ID = 999999;
    $new_item->db_id = 0;
    $new_item->title = __t('nav_cars', 'Cars');
    $new_item->url = $car_archive_url;
    $new_item->menu_order = 100;
    $new_item->menu_item_parent = 0;
    $new_item->type = 'custom';
    $new_item->object = 'custom';
    $new_item->object_id = 0;
    $new_item->classes = array('menu-item', 'menu-item-type-custom');
    $new_item->target = '';
    $new_item->attr_title = '';
    $new_item->description = '';
    $new_item->xfn = '';
    $new_item->current = false;

    $position = 2;
    array_splice($items, $position, 0, array($new_item));

    return $items;
}

add_filter('wp_nav_menu_items', 'add_language_switcher_items', 10, 2);
function add_language_switcher_items($items, $args) {
    if ($args->theme_location !== 'primary') {
        return $items;
    }

    $lang = Language::get_instance();
    $current_lang = $lang->get_current_language();
    $available = $lang->get_available_languages();
    $names = $lang->language_names;

    if (count($available) <= 1) {
        return $items;
    }

    $switcher = '<li class="menu-item menu-item-has-children language-switcher">';
    $switcher .= '<a href="#" class="menu-link">' . esc_html($names[$current_lang]) . ' <span class="ast-icon icon-arrow">▼</span></a>';
    $switcher .= '<ul class="sub-menu">';

    foreach ($available as $code) {
        if ($code === $current_lang) {
            continue;
        }
        $url = add_query_arg('lang', $code, home_url($_SERVER['REQUEST_URI']));
        $switcher .= '<li class="menu-item"><a href="' . esc_url($url) . '" class="menu-link">' . esc_html($names[$code]) . '</a></li>';
    }

    $switcher .= '</ul></li>';

    return $items . $switcher;
}

add_filter('wp_nav_menu_objects', 'add_chat_menu_item_object', 10, 2);
function add_chat_menu_item_object($items, $args) {
    if ($args->theme_location !== 'primary') {
        return $items;
    }
    $chat_page = get_page_by_path('chat');
    if (!$chat_page) {
        return $items;
    }
    $chat_url = get_permalink($chat_page);
    // Avoid duplicates
    foreach ($items as $item) {
        if (isset($item->url) && $item->url === $chat_url) {
            return $items;
        }
    }
    $new_item = new stdClass();
    $new_item->ID = 999998;
    $new_item->db_id = 0;
    $new_item->title = __t('nav_chat', 'Live Chat');
    $new_item->url = $chat_url;
    $new_item->menu_order = 101;
    $new_item->menu_item_parent = 0;
    $new_item->type = 'custom';
    $new_item->object = 'custom';
    $new_item->object_id = 0;
    $new_item->classes = array('menu-item', 'menu-item-type-custom');
    $new_item->target = '';
    $new_item->attr_title = '';
    $new_item->description = '';
    $new_item->xfn = '';
    $new_item->current = false;
    // Append at the end
    $items[] = $new_item;
    return $items;
}

add_filter('wp_nav_menu_items', 'add_account_menu_item', 10, 2);
function add_account_menu_item($items, $args) {
    if ($args->theme_location !== 'primary') {
        return $items;
    }

    $login_page = get_permalink(get_page_by_path('login'));
    $register_page = get_permalink(get_page_by_path('register'));
    $profile_page = get_permalink(get_page_by_path('profile'));

    if (is_user_logged_in()) {
        $logout_url = wp_logout_url(home_url('/'));
        $items .= '<li class="menu-item menu-item-has-children">';
        $items .= '<a href="#" class="menu-link">' . __t('nav_account', 'Account') . ' <span class="ast-icon icon-arrow">▼</span></a>';
        $items .= '<ul class="sub-menu">';
        if ($profile_page) {
            $items .= '<li class="menu-item"><a href="' . esc_url($profile_page) . '" class="menu-link">' . __t('profile_title', 'Profile') . '</a></li>';
        }
        $items .= '<li class="menu-item"><a href="' . esc_url($logout_url) . '" class="menu-link">' . __t('login_logout', 'Log Out') . '</a></li>';
        $items .= '</ul></li>';
    } else {
        if (!$login_page || !$register_page) {
            return $items;
        }
        $items .= '<li class="menu-item menu-item-has-children">';
        $items .= '<a href="#" class="menu-link">' . __t('nav_account', 'Account') . ' <span class="ast-icon icon-arrow">▼</span></a>';
        $items .= '<ul class="sub-menu">';
        $items .= '<li class="menu-item"><a href="' . esc_url($login_page) . '" class="menu-link">' . __t('login_title', 'Log In') . '</a></li>';
        $items .= '<li class="menu-item"><a href="' . esc_url($register_page) . '" class="menu-link">' . __t('register_title', 'Create Account') . '</a></li>';
        $items .= '</ul></li>';
    }

    return $items;
}

add_filter('wp_nav_menu_items', 'add_notification_bell_menu_item', 9, 2); // priority before account (10)
function add_notification_bell_menu_item($items, $args) {
    if ($args->theme_location !== 'primary') {
        return $items;
    }
    if (!is_user_logged_in()) {
        return $items;
    }

    $bell = '<li id="notification-bell-wrapper" class="menu-item" style="position:relative;">';
    $bell .= '<a id="notification-bell" href="#" style="font-size:1.4rem; position:relative; text-decoration:none; color:#333;">🔔';
    $bell .= '<span id="notification-badge" style="display:none; position:absolute; top:-8px; right:-8px; background:#d63638; color:#fff; border-radius:50%; padding:2px 6px; font-size:0.7rem; font-weight:bold; min-width:18px; text-align:center;"></span>';
    $bell .= '</a>';
    $bell .= '<div id="notification-dropdown" style="display:none; position:absolute; top:100%; right:0; width:360px; max-height:400px; overflow-y:auto; background:#fff; border:1px solid #ddd; border-radius:4px; box-shadow:0 4px 12px rgba(0,0,0,0.15); z-index:9999;">';
    $bell .= '<ul id="notification-list" style="list-style:none; margin:0; padding:0;"></ul>';
    $bell .= '</div>';
    $bell .= '</li>';


    return $items . $bell;
}