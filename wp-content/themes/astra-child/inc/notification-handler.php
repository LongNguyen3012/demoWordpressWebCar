<?php

if (!defined('ABSPATH')) {
    exit;
}

function send_sitewide_notification($post_id, $type) {
    if (get_post_meta($post_id, '_site_wide_notification_sent', true)) {
        error_log('[DEBUG] Notification already sent for post ' . $post_id);
        return;
    }

    $post = get_post($post_id);
    if (!$post || $post->post_status !== 'publish') {
        error_log('[DEBUG] Post not published or invalid, status: ' . ($post ? $post->post_status : 'null'));
        return;
    }

    $post_type = $post->post_type;
    $title     = get_the_title($post_id);
    $link      = get_permalink($post_id);

    switch ($post_type) {
        case 'car':
            $content = sprintf(__t('notify_new_car_content', 'New Car: %s'), $title);
            break;
        case 'banner':
            $content = sprintf(__t('notify_new_banner_content', 'New Banner: %s'), $title);
            break;
        case 'team':
            $content = sprintf(__t('notify_new_team_content', 'New Team Member: %s'), $title);
            break;
        default:
            error_log('[DEBUG] Unknown post type: ' . $post_type);
            return;
    }

    error_log('[DEBUG] Sending notification for post ' . $post_id . ', type: ' . $post_type);

    $recipients = [];

    if ($post_type === 'car') {
        $user_query = new WP_User_Query([
            'meta_query' => [
                [
                    'key'     => 'notify_new_car',
                    'value'   => 'yes',
                    'compare' => '=',
                ],
            ],
            'fields' => 'ID',
        ]);
        $recipients = $user_query->get_results();
        error_log('[DEBUG] Car recipients: ' . count($recipients));
    } else {
        $user_query = new WP_User_Query([
            'role__in' => ['administrator', 'editor'],
            'fields'   => 'ID',
        ]);
        $recipients = $user_query->get_results();
        error_log('[DEBUG] Admin/Editor recipients: ' . count($recipients));
    }

    if (empty($recipients)) {
        error_log('[DEBUG] No recipients found for post ' . $post_id);
        return;
    }

    $email_type = $post_type;

    foreach ($recipients as $user_id) {
        error_log('[DEBUG] Processing user ' . $user_id);
        chat_create_notification($user_id, $type, $content, $link);

        $opted_in = email_queue_user_opted_in($user_id, $email_type);
        error_log('[DEBUG] User ' . $user_id . ' email opt-in for ' . $email_type . ': ' . ($opted_in ? 'yes' : 'no'));
        if ($opted_in) {
            error_log('[DEBUG] Adding email queue for user ' . $user_id . ' post ' . $post_id);
            email_queue_add($user_id, $type, $post_id, $title, $link);
        }
    }

    update_post_meta($post_id, '_site_wide_notification_sent', 1);
    error_log('[DEBUG] Notification sent and marked for post ' . $post_id);
}

add_action('publish_car', function ($post_id) {
    error_log('[DEBUG] publish_car hook fired for post ' . $post_id);
    send_sitewide_notification($post_id, 'new_car');
});

add_action('publish_banner', function ($post_id) {
    error_log('[DEBUG] publish_banner hook fired for post ' . $post_id);
    send_sitewide_notification($post_id, 'new_banner');
});

add_action('publish_team', function ($post_id) {
    error_log('[DEBUG] publish_team hook fired for post ' . $post_id);
    send_sitewide_notification($post_id, 'new_team');
});