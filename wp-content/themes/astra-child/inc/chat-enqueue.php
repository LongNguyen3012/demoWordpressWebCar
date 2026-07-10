<?php
add_action('wp_enqueue_scripts', 'chat_enqueue_scripts');
function chat_enqueue_scripts() {
    if (!is_page('chat')) {
        return;
    }
    wp_enqueue_script(
        'chat-js',
        get_stylesheet_directory_uri() . '/assets/js/chat.js',
        array(),
        '3.2',
        true
    );
    wp_localize_script('chat-js', 'chatSettings', array(
        'restUrl'      => rest_url('mytheme/v1/chat'),
        'nonce'        => wp_create_nonce('wp_rest'),
        'websocketUrl' => defined('CHAT_WS_URL') ? CHAT_WS_URL : 'ws://localhost:8080',
        'userId'       => get_current_user_id(),
        'userName'     => wp_get_current_user()->display_name,
        'isAdmin'      => current_user_can('manage_options'),
    ));
}