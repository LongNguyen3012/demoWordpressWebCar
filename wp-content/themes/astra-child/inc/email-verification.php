<?php

/**
 * Check if we should send the verification email.
 * Limits to one email per user per 60 seconds.
 */
function can_send_verification_email($user_id, $cooldown = 60) {
    $key = 'last_verification_sent_' . $user_id;
    $last_sent = get_transient($key);
    if ($last_sent !== false) {
        return false;
    }
    set_transient($key, time(), $cooldown);
    return true;
}

/**
 * General rate limit for resend (3 per hour).
 */
function check_verification_rate_limit($user_id, $limit = 3, $time_window = 3600) {
    $key = 'verify_attempts_' . $user_id;
    $data = get_transient($key);

    if ($data === false) {
        set_transient($key, array('count' => 1, 'first_attempt' => time()), $time_window);
        return true;
    }

    $data['count']++;
    if ($data['count'] > $limit) {
        return false;
    }

    set_transient($key, $data, $time_window);
    return true;
}

add_action('init', 'handle_verification_code_submission');
function handle_verification_code_submission() {
    if (empty($_POST['verify_code']) || empty($_POST['user_id']) || empty($_POST['verification_nonce'])) {
        return;
    }

    if (!wp_verify_nonce($_POST['verification_nonce'], 'verify_code_action')) {
        wp_die(__t('verification_nonce_error', 'Security check failed.'));
    }

    $user_id = intval($_POST['user_id']);
    $user = get_userdata($user_id);
    if (!$user) {
        wp_die(__t('verification_invalid_user', 'Invalid user.'));
    }

    $stored_code = get_user_meta($user_id, 'verification_code', true);
    $expires = get_user_meta($user_id, 'verification_expires', true);

    if (empty($stored_code) || time() > $expires) {
        delete_user_meta($user_id, 'verification_code');
        delete_user_meta($user_id, 'verification_expires');
        wp_die(__t('verification_expired', 'Verification code has expired. Please request a new one.'));
    }

    $submitted_code = strtoupper(sanitize_text_field($_POST['verify_code']));
    if ($stored_code !== $submitted_code) {
        wp_die(__t('verification_invalid_code', 'Invalid verification code.'));
    }

    update_user_meta($user_id, 'email_verified', '1');
    delete_user_meta($user_id, 'verification_code');
    delete_user_meta($user_id, 'verification_expires');

    // Get the redirect destination.
    $redirect_to = isset($_POST['redirect_to']) ? esc_url_raw($_POST['redirect_to']) : home_url('/');

    wp_set_auth_cookie($user_id);
    wp_redirect($redirect_to);
    exit;
}

add_action('init', 'handle_resend_verification_code');
function handle_resend_verification_code() {
    if (empty($_GET['resend_code']) || empty($_GET['user_id'])) {
        return;
    }

    $user_id = intval($_GET['user_id']);
    $user = get_userdata($user_id);
    if (!$user) {
        wp_die(__t('verification_invalid_user', 'Invalid user.'));
    }

    $verified = get_user_meta($user_id, 'email_verified', true);
    if ($verified === '1') {
        wp_die(__t('verification_already_verified', 'Email already verified.'));
    }

    if (!check_verification_rate_limit($user_id)) {
        wp_die(__t('verification_rate_limit', 'You have requested too many verification codes. Please try again later.'));
    }

    send_verification_code_email($user);

    $verify_page = get_page_by_path('email-verify');
    $url = $verify_page
        ? add_query_arg(array('resent' => 1, 'user_id' => $user->ID), get_permalink($verify_page))
        : home_url('/');

    wp_redirect($url);
    exit;
}

function send_verification_code_email($user) {
    if (!can_send_verification_email($user->ID)) {
        return;
    }

    $code = strtoupper(wp_generate_password(6, false, false));
    update_user_meta($user->ID, 'verification_code', $code);
    update_user_meta($user->ID, 'verification_expires', time() + 900);

    $site_name = get_bloginfo('name');
    $verify_page = get_page_by_path('email-verify');
    $verify_url = $verify_page ? add_query_arg('user_id', $user->ID, get_permalink($verify_page)) : home_url('/');

    $subject = sprintf(__t('verification_subject', 'Verify your email address on %s'), $site_name);
    $message = sprintf(
        __t('verification_message_code', "Hi %s,\n\nYour verification code is: %s\n\nEnter this code on the verification page:\n%s\n\nThis code expires in 15 minutes.\n\nIf you did not create an account, please ignore this email."),
        $user->display_name,
        $code,
        $verify_url
    );

    wp_mail($user->user_email, $subject, $message);
}

function send_verification_on_register($user_id) {
    $user = get_userdata($user_id);
    if (!$user) {
        return;
    }
    update_user_meta($user_id, 'email_verified', '0');
    send_verification_code_email($user);
}

add_action('admin_notices', 'show_unverified_user_notice');
function show_unverified_user_notice() {
    $user_id = get_current_user_id();
    if (!user_can($user_id, 'manage_options')) {
        return;
    }
    $unverified = get_users(array('meta_key' => 'email_verified', 'meta_value' => '0', 'meta_compare' => '!='));
    if ($unverified) {
        echo '<div class="notice notice-warning"><p>' . sprintf(__t('admin_unverified_users', 'There are %d unverified users. They can log in but have not verified their email.'), count($unverified)) . '</p></div>';
    }
}