<?php

if (!defined('ABSPATH')) {
    exit;
}

add_action('rest_api_init', function() {
    register_rest_route('mytheme/v1', '/unsubscribe', array(
        'methods'             => 'GET',
        'callback'            => 'email_unsubscribe_endpoint',
        'permission_callback' => '__return_true',
    ));
});

function email_unsubscribe_endpoint($request) {
    $user_id = $request->get_param('user_id');
    $hash    = $request->get_param('hash');

    if (empty($user_id) || empty($hash)) {
        return new WP_REST_Response(array(
            'success' => false,
            'message' => __('Missing required parameters.', 'astra-child'),
        ), 400);
    }

    $user_id = intval($user_id);
    $user = get_userdata($user_id);
    if (!$user) {
        return new WP_REST_Response(array(
            'success' => false,
            'message' => __('Invalid user.', 'astra-child'),
        ), 404);
    }

    if (!email_queue_verify_unsubscribe_hash($user_id, $hash)) {
        return new WP_REST_Response(array(
            'success' => false,
            'message' => __('Invalid unsubscribe link.', 'astra-child'),
        ), 403);
    }

    update_user_meta($user_id, 'notify_email_car', 'no');
    update_user_meta($user_id, 'notify_email_banner', 'no');
    update_user_meta($user_id, 'notify_email_team', 'no');

    return new WP_REST_Response(array(
        'success' => true,
        'message' => __('You have been unsubscribed from all email notifications.', 'astra-child'),
    ), 200);
}