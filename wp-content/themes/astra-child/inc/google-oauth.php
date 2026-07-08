<?php
/**
 * Google OAuth Login Handler
 */

if (!defined('GOOGLE_CLIENT_ID')) {
    $client_id = getenv('GOOGLE_CLIENT_ID');
    if ($client_id) {
        define('GOOGLE_CLIENT_ID', $client_id);
    }
}
if (!defined('GOOGLE_CLIENT_SECRET')) {
    $client_secret = getenv('GOOGLE_CLIENT_SECRET');
    if ($client_secret) {
        define('GOOGLE_CLIENT_SECRET', $client_secret);
    }
}

$client_id = getenv('GOOGLE_CLIENT_ID') ?: (defined('GOOGLE_CLIENT_ID') ? GOOGLE_CLIENT_ID : '');
$client_secret = getenv('GOOGLE_CLIENT_SECRET') ?: (defined('GOOGLE_CLIENT_SECRET') ? GOOGLE_CLIENT_SECRET : '');

if (empty($client_id) || empty($client_secret)) {
    error_log('Google OAuth credentials missing.');
    return;
}

add_action('init', 'handle_google_oauth_callback');
function handle_google_oauth_callback() {
    global $client_id, $client_secret; // <-- Make them accessible inside the function

    if (empty($_GET['code']) || empty($_GET['state']) || empty($_GET['google_oauth'])) {
        return;
    }

    if (!wp_verify_nonce($_GET['state'], 'google_oauth_state')) {
        wp_die('Invalid request.');
    }

    $code = $_GET['code'];
    $token_response = google_exchange_code_for_token($code, $client_id, $client_secret);

    if (empty($token_response['access_token'])) {
        wp_die('Failed to get access token.');
    }

    $user_info = google_fetch_user_info($token_response['access_token']);

    if (empty($user_info['email'])) {
        wp_die('Failed to get user email.');
    }

    $email = sanitize_email($user_info['email']);
    $existing_user = get_user_by('email', $email);

    if ($existing_user) {
        wp_set_auth_cookie($existing_user->ID);
        update_user_meta($existing_user->ID, 'email_verified', '1');
        wp_redirect(home_url('/'));
        exit;
    }

    $key = 'google_signup_' . md5($email);
    set_transient($key, array(
        'email'        => $email,
        'display_name' => sanitize_text_field($user_info['name'] ?? $email),
        'verified'     => true
    ), 300);

    setcookie('google_signup_email', $email, time() + 300, COOKIEPATH, COOKIE_DOMAIN);

    $register_url = add_query_arg('google_oauth', '1', get_permalink(get_page_by_path('register')));
    wp_redirect($register_url);
    exit;
}

function google_exchange_code_for_token($code, $client_id, $client_secret) {
    $url = 'https://oauth2.googleapis.com/token';
    $body = array(
        'code'          => $code,
        'client_id'     => $client_id,
        'client_secret' => $client_secret,
        'redirect_uri'  => home_url('/?google_oauth=1'),
        'grant_type'    => 'authorization_code'
    );

    $response = wp_remote_post($url, array('body' => $body));
    if (is_wp_error($response)) {
        return array();
    }

    $body = wp_remote_retrieve_body($response);
    return json_decode($body, true);
}

function google_fetch_user_info($access_token) {
    $url = 'https://www.googleapis.com/oauth2/v2/userinfo?access_token=' . urlencode($access_token);
    $response = wp_remote_get($url);
    if (is_wp_error($response)) {
        return array();
    }
    $body = wp_remote_retrieve_body($response);
    return json_decode($body, true);
}