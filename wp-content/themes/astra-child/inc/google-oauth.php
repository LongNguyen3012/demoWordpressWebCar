<?php
/**
 * Handle Google OAuth callback
 */

$secret_file = __DIR__ . '/google-secret.php';
if (file_exists($secret_file)) {
    require_once $secret_file;
}

add_action('init', 'handle_google_oauth_callback');
function handle_google_oauth_callback() {
    if (empty($_GET['code']) || empty($_GET['state']) || empty($_GET['google_oauth'])) {
        return;
    }

    if (!wp_verify_nonce($_GET['state'], 'google_oauth_state')) {
        wp_die('Invalid request.');
    }

    $code = $_GET['code'];
    $token_response = google_exchange_code_for_token($code);

    if (empty($token_response['access_token'])) {
        wp_die('Failed to get access token.');
    }

    $user_info = google_fetch_user_info($token_response['access_token']);

    if (empty($user_info['email'])) {
        wp_die('Failed to get user email.');
    }

    $user_id = google_login_or_create_user($user_info);

    if ($user_id) {
        wp_set_auth_cookie($user_id);
        wp_redirect(home_url('/'));
        exit;
    } else {
        wp_die('Could not log you in.');
    }
}

function google_exchange_code_for_token($code) {
    $url = 'https://oauth2.googleapis.com/token';
    $body = array(
        'code'          => $code,
        'client_id'     => GOOGLE_CLIENT_ID,
        'client_secret' => GOOGLE_CLIENT_SECRET,
        'redirect_uri'  => home_url('/?google_oauth=1'),
        'grant_type'    => 'authorization_code'
    );

    $response = wp_remote_post($url, array(
        'body' => $body
    ));

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

function google_login_or_create_user($user_info) {
    $email = sanitize_email($user_info['email']);
    $user = get_user_by('email', $email);

    if ($user) {
        return $user->ID;
    }

    $username = sanitize_user($user_info['email']);
    if (username_exists($username)) {
        $i = 1;
        while (username_exists($username . $i)) {
            $i++;
        }
        $username = $username . $i;
    }

    $user_id = wp_insert_user(array(
        'user_login' => $username,
        'user_email' => $email,
        'user_pass'  => wp_generate_password(),
        'display_name' => sanitize_text_field($user_info['name'] ?? $username),
        'role'       => 'subscriber' 
    ));

    if (is_wp_error($user_id)) {
        return false;
    }

    update_user_meta($user_id, 'email_verified', '1');

    return $user_id;
}