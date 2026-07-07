<?php

add_action('wp_ajax_check_username', 'ajax_check_username');
add_action('wp_ajax_nopriv_check_username', 'ajax_check_username');

function ajax_check_username() {
    check_ajax_referer('register_action', 'nonce');

    $username = sanitize_user($_POST['username']);
    $response = array();

    if (empty($username)) {
        $response['available'] = false;
        $response['message'] = __('Username is required.', 'astra-child');
    } elseif (username_exists($username)) {
        $response['available'] = false;
        $response['message'] = __t('register_username_taken', 'Username already taken.');
    } else {
        $response['available'] = true;
        $response['message'] = __t('register_username_available', 'Username available.');
    }

    wp_send_json($response);
}