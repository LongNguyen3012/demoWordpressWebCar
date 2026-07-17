<?php

if (!defined('ABSPATH')) {
    exit;
}

function email_queue_create_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'email_queue';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id BIGINT(20) UNSIGNED NOT NULL,
        type VARCHAR(50) NOT NULL,
        post_id BIGINT(20) UNSIGNED NOT NULL,
        content TEXT NOT NULL,
        link VARCHAR(255) NOT NULL,
        sent TINYINT(1) DEFAULT 0,
        queued_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        sent_at DATETIME NULL,
        PRIMARY KEY (id),
        KEY user_id (user_id),
        KEY sent (sent),
        KEY queued_at (queued_at)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
add_action('after_switch_theme', 'email_queue_create_table');

function email_queue_add($user_id, $type, $post_id, $content, $link) {
    global $wpdb;
    $table = $wpdb->prefix . 'email_queue';

    $wpdb->insert(
        $table,
        array(
            'user_id'   => $user_id,
            'type'      => $type,
            'post_id'   => $post_id,
            'content'   => $content,
            'link'      => $link,
            'sent'      => 0,
            'queued_at' => current_time('mysql'),
        )
    );
}

function email_queue_user_opted_in($user_id, $type) {
    $meta_key = 'notify_email_' . $type;
    $value = get_user_meta($user_id, $meta_key, true);
    return ($value === 'yes');
}

function email_queue_get_unsubscribe_hash($user_id) {
    return wp_hash($user_id . '|' . AUTH_SALT);
}

function email_queue_verify_unsubscribe_hash($user_id, $hash) {
    $expected = email_queue_get_unsubscribe_hash($user_id);
    return hash_equals($expected, $hash);
}

function email_queue_send_digest($user_id, $items) {
    $user = get_userdata($user_id);
    if (!$user) {
        return false;
    }

    if (empty($items)) {
        return false;
    }

    $site_name = get_bloginfo('name');
    $subject = sprintf(__t('email_digest_subject', '[%s] New updates for you!'), $site_name);

    $html = email_queue_build_html_digest($user, $items, $subject);
    $plain = email_queue_build_plain_digest($user, $items, $subject);

    $headers = array('Content-Type: text/html; charset=UTF-8');

    return wp_mail($user->user_email, $subject, $html, $headers);
}

function email_queue_build_html_digest($user, $items, $subject) {
    $site_name = get_bloginfo('name');
    $unsubscribe_url = home_url('/wp-json/mytheme/v1/unsubscribe?user_id=' . $user->ID . '&hash=' . email_queue_get_unsubscribe_hash($user->ID));
    $settings_url = get_permalink(get_page_by_path('settings'));

    $grouped = array();
    foreach ($items as $item) {
        $type = $item->type;
        if (!isset($grouped[$type])) {
            $grouped[$type] = array();
        }
        $grouped[$type][] = $item;
    }

    $type_labels = array(
        'new_car'    => __('Cars', 'astra-child'),
        'new_banner' => __('Banners', 'astra-child'),
        'new_team'   => __('Team Members', 'astra-child'),
    );

    $items_html = '';
    foreach ($grouped as $type => $type_items) {
        $label = isset($type_labels[$type]) ? $type_labels[$type] : $type;
        $items_html .= '<h3 style="margin-top:20px; color:#333;">' . esc_html($label) . '</h3>';
        $items_html .= '<ul style="list-style:none; padding:0;">';
        foreach ($type_items as $item) {
            $items_html .= '<li style="padding:8px 0; border-bottom:1px solid #eee;">';
            $items_html .= '<a href="' . esc_url($item->link) . '" style="color:#2C2C2C; text-decoration:none; font-weight:500;">' . esc_html($item->content) . '</a>';
            $items_html .= '</li>';
        }
        $items_html .= '</ul>';
    }

    ob_start();
    ?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo esc_html($subject); ?></title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background: #f9f9f9; padding: 30px; border-radius: 8px; border: 1px solid #eee;">
        <h1 style="margin:0 0 10px; font-size:24px; color:#2C2C2C;"><?php echo esc_html($site_name); ?></h1>
        <p style="font-size:16px; color:#555;"><?php printf(__t('email_digest_greeting', 'Hi %s,'), esc_html($user->display_name)); ?></p>
        <p style="font-size:16px; color:#555;"><?php _te('email_digest_intro', 'Here are the latest updates from our site:'); ?></p>

        <div style="background:#fff; padding:20px; border-radius:4px; border:1px solid #eee; margin:20px 0;">
            <?php echo $items_html; ?>
        </div>

        <p style="font-size:14px; color:#999; margin-top:20px;">
            <?php _te('email_digest_footer', 'You received this email because you subscribed to notifications on our site.'); ?>
        </p>

        <p style="font-size:14px; margin-top:20px;">
            <a href="<?php echo esc_url($settings_url); ?>" style="color:#2C2C2C; text-decoration:underline;">
                <?php _te('email_digest_manage', 'Manage your email preferences'); ?>
            </a>
            &nbsp;|&nbsp;
            <a href="<?php echo esc_url($unsubscribe_url); ?>" style="color:#d63638; text-decoration:underline;">
                <?php _te('email_digest_unsubscribe', 'Unsubscribe from all emails'); ?>
            </a>
        </p>
    </div>
</body>
</html>
    <?php
    return ob_get_clean();
}

function email_queue_build_plain_digest($user, $items, $subject) {
    $site_name = get_bloginfo('name');
    $settings_url = get_permalink(get_page_by_path('settings'));
    $unsubscribe_url = home_url('/wp-json/mytheme/v1/unsubscribe?user_id=' . $user->ID . '&hash=' . email_queue_get_unsubscribe_hash($user->ID));

    $lines = array();
    $lines[] = $site_name;
    $lines[] = str_repeat('=', strlen($site_name));
    $lines[] = '';
    $lines[] = sprintf(__t('email_digest_greeting', 'Hi %s,'), $user->display_name);
    $lines[] = __t('email_digest_intro', 'Here are the latest updates from our site:');
    $lines[] = '';

    $grouped = array();
    foreach ($items as $item) {
        $type = $item->type;
        if (!isset($grouped[$type])) {
            $grouped[$type] = array();
        }
        $grouped[$type][] = $item;
    }

    $type_labels = array(
        'new_car'    => __('Cars', 'astra-child'),
        'new_banner' => __('Banners', 'astra-child'),
        'new_team'   => __('Team Members', 'astra-child'),
    );

    foreach ($grouped as $type => $type_items) {
        $label = isset($type_labels[$type]) ? $type_labels[$type] : $type;
        $lines[] = $label . ':';
        $lines[] = str_repeat('-', strlen($label) + 1);
        foreach ($type_items as $item) {
            $lines[] = '  - ' . strip_tags($item->content) . ' (' . $item->link . ')';
        }
        $lines[] = '';
    }

    $lines[] = __t('email_digest_footer', 'You received this email because you subscribed to notifications on our site.');
    $lines[] = '';
    $lines[] = sprintf(__t('email_digest_manage', 'Manage your email preferences: %s'), $settings_url);
    $lines[] = sprintf(__t('email_digest_unsubscribe', 'Unsubscribe from all emails: %s'), $unsubscribe_url);

    return implode("\n", $lines);
}

function email_queue_process_batch() {
    global $wpdb;
    $table = $wpdb->prefix . 'email_queue';

    $items = $wpdb->get_results(
        "SELECT * FROM $table WHERE sent = 0 ORDER BY user_id, queued_at ASC"
    );

    if (empty($items)) {
        return;
    }

    $user_items = array();
    foreach ($items as $item) {
        if (!isset($user_items[$item->user_id])) {
            $user_items[$item->user_id] = array();
        }
        $user_items[$item->user_id][] = $item;
    }

    $sent_ids = array();

    foreach ($user_items as $user_id => $user_queue) {
        $success = email_queue_send_digest($user_id, $user_queue);

        if ($success) {
            $ids = array_map(function($item) { return $item->id; }, $user_queue);
            $ids_placeholder = implode(',', array_fill(0, count($ids), '%d'));
            $wpdb->query(
                $wpdb->prepare(
                    "UPDATE $table SET sent = 1, sent_at = %s WHERE id IN ($ids_placeholder)",
                    array_merge(array(current_time('mysql')), $ids)
                )
            );
            $sent_ids = array_merge($sent_ids, $ids);
        }
    }

    if (!empty($sent_ids)) {
        error_log('Email queue processed: ' . count($sent_ids) . ' emails sent.');
    }
}

function email_queue_schedule_cron() {
    if (!wp_next_scheduled('email_queue_cron_hook')) {
        wp_schedule_event(time(), 'every_five_minutes', 'email_queue_cron_hook');
        error_log('Email queue cron scheduled.');
    }
}
add_action('after_switch_theme', 'email_queue_schedule_cron');

function email_queue_unschedule_cron() {
    $timestamp = wp_next_scheduled('email_queue_cron_hook');
    if ($timestamp) {
        wp_unschedule_event($timestamp, 'email_queue_cron_hook');
    }
}
add_action('switch_theme', 'email_queue_unschedule_cron');

function email_queue_add_cron_interval($schedules) {
    $schedules['every_five_minutes'] = array(
        'interval' => 300,
        'display'  => __('Every 5 Minutes', 'astra-child'),
    );
    return $schedules;
}
add_filter('cron_schedules', 'email_queue_add_cron_interval');

add_action('email_queue_cron_hook', 'email_queue_process_batch');

function email_queue_process_manually() {
    if (!current_user_can('manage_options')) {
        return;
    }
    email_queue_process_batch();
    wp_redirect(add_query_arg('queue_processed', '1', wp_get_referer()));
    exit;
}
add_action('admin_post_email_queue_process', 'email_queue_process_manually');

function email_queue_admin_notice() {
    if (isset($_GET['queue_processed']) && current_user_can('manage_options')) {
        echo '<div class="notice notice-success"><p>' . __('Email queue processed successfully.', 'astra-child') . '</p></div>';
    }
}
add_action('admin_notices', 'email_queue_admin_notice');

function email_queue_ensure_cron_scheduled() {
    if (!wp_next_scheduled('email_queue_cron_hook')) {
        wp_schedule_event(time(), 'every_five_minutes', 'email_queue_cron_hook');
        error_log('Email queue cron scheduled automatically (on init).');
    }
}
add_action('init', 'email_queue_ensure_cron_scheduled');