<?php
add_filter('login_redirect', 'custom_login_redirect', 10, 3);
function custom_login_redirect($redirect_to, $request, $user) {
    if (isset($user->roles) && is_array($user->roles)) {
        if (in_array('administrator', $user->roles)) {
            return admin_url();
        }
    }
    return home_url('/');
}

add_filter('logout_redirect', 'custom_logout_redirect', 10, 2);
function custom_logout_redirect($redirect_to, $request) {
    $login_page = get_permalink(get_page_by_path('login'));
    return $login_page ? $login_page : home_url('/');
}

function get_login_page_url() {
    $login_page = get_page_by_path('login');
    if ($login_page) {
        return get_permalink($login_page);
    }
    return wp_login_url();
}
