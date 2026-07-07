<?php

add_action('init', 'redirect_default_login');
function redirect_default_login() {
    if (strpos($_SERVER['REQUEST_URI'], 'wp-login.php') !== false &&
        !isset($_POST['log']) &&
        (!isset($_GET['action']) || $_GET['action'] !== 'logout')) {
        wp_safe_redirect(home_url('/'));
        exit;
    }
}

add_action('init', 'handle_custom_login_url');
function handle_custom_login_url() {
    $custom_slug = 'long-secret-login';
    if (strpos($_SERVER['REQUEST_URI'], $custom_slug) !== false) {
        require_once ABSPATH . 'wp-login.php';
        exit;
    }
}

add_action('login_init', 'prevent_default_login_action');
function prevent_default_login_action($action) {
    $custom_slug = 'long-secret-login';
    if ($action === 'login' && strpos($_SERVER['REQUEST_URI'], $custom_slug) === false) {
        wp_safe_redirect(home_url('/'));
        exit;
    }
}