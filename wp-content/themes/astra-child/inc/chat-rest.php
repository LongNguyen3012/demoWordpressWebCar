<?php
add_action('rest_api_init', 'chat_register_routes');
function chat_register_routes() {
    register_rest_route('mytheme/v1', '/chat/messages', array(
        'methods'  => 'POST',
        'callback' => 'chat_rest_post_message',
        'permission_callback' => '__return_true'
    ));
    register_rest_route('mytheme/v1', '/chat/rooms', array(
        'methods'  => 'GET',
        'callback' => 'chat_rest_get_rooms',
        'permission_callback' => '__return_true'
    ));
    register_rest_route('mytheme/v1', '/chat/rooms', array(
        'methods'  => 'POST',
        'callback' => 'chat_rest_create_room',
        'permission_callback' => '__return_true'
    ));
    register_rest_route('mytheme/v1', '/chat/rooms/(?P<id>\d+)/members', array(
        'methods'  => 'POST',
        'callback' => 'chat_rest_add_member',
        'permission_callback' => '__return_true'
    ));
    register_rest_route('mytheme/v1', '/chat/rooms/(?P<id>\d+)/messages', array(
        'methods'  => 'GET',
        'callback' => 'chat_rest_get_room_messages',
        'permission_callback' => '__return_true'
    ));
    register_rest_route('mytheme/v1', '/chat/message/(?P<id>\d+)', array(
        'methods'  => 'GET',
        'callback' => 'chat_rest_get_single_message',
        'permission_callback' => '__return_true'
    ));
    register_rest_route('mytheme/v1', '/chat/users', array(
        'methods'  => 'GET',
        'callback' => 'chat_rest_get_users',
        'permission_callback' => '__return_true'
    ));
    register_rest_route('mytheme/v1', '/chat/messages/(?P<id>\d+)', array(
        'methods'  => 'PUT',
        'callback' => 'chat_rest_update_message',
        'permission_callback' => '__return_true'
    ));
    register_rest_route('mytheme/v1', '/chat/messages/(?P<id>\d+)', array(
        'methods'  => 'DELETE',
        'callback' => 'chat_rest_delete_message',
        'permission_callback' => '__return_true'
    ));
}

function chat_rest_get_users($request) {
    if (!is_user_logged_in()) {
        return new WP_Error('not_logged_in', 'Not logged in', array('status' => 401));
    }
    $search = $request->get_param('search') ?: '';
    $users = chat_get_users($search);
    return rest_ensure_response($users);
}

function chat_rest_get_rooms($request) {
    $user_id = get_current_user_id();
    if (!$user_id) {
        return new WP_Error('not_logged_in', 'User not logged in', array('status' => 401));
    }
    $rooms = chat_get_user_rooms($user_id);
    return rest_ensure_response($rooms);
}

function chat_rest_create_room($request) {
    $user_id = get_current_user_id();
    if (!$user_id) {
        return new WP_Error('not_logged_in', 'User not logged in', array('status' => 401));
    }
    $name = sanitize_text_field($request->get_param('name'));
    $members = (array) $request->get_param('members');
    if (empty($name) || empty($members)) {
        return new WP_Error('invalid_data', 'Name and members are required', array('status' => 400));
    }
    if (!in_array($user_id, $members)) {
        $members[] = $user_id;
    }
    $room_id = chat_create_room($name, 'group', $user_id);
    foreach ($members as $uid) {
        chat_add_member($room_id, (int)$uid);
    }
    $room_data = array('id' => $room_id, 'name' => $name, 'type' => 'group', 'created_by' => $user_id);
    chat_notify_websocket_new_room($room_data);
    return rest_ensure_response(array('id' => $room_id));
}

function chat_rest_add_member($request) {
    $user_id = get_current_user_id();
    if (!$user_id) {
        return new WP_Error('not_logged_in', 'User not logged in', array('status' => 401));
    }
    $room_id = (int) $request->get_param('id');
    $target_user = (int) $request->get_param('user_id');
    if (!chat_is_user_member_of_room($user_id, $room_id)) {
        return new WP_Error('forbidden', 'You are not a member of this room', array('status' => 403));
    }
    chat_add_member($room_id, $target_user);
    return rest_ensure_response(array('success' => true));
}

function chat_rest_get_room_messages($request) {
    $user_id = get_current_user_id();
    if (!$user_id) {
        return new WP_Error('not_logged_in', 'User not logged in', array('status' => 401));
    }
    $room_id = (int) $request->get_param('id');
    if (!chat_is_user_member_of_room($user_id, $room_id)) {
        return new WP_Error('forbidden', 'You are not a member of this room', array('status' => 403));
    }
    $limit = $request->get_param('limit') ?: 50;
    $offset = $request->get_param('offset') ?: 0;
    $is_admin = current_user_can('manage_options');
    $messages = chat_get_messages_by_room($room_id, $limit, $offset, $is_admin);
    return rest_ensure_response(array_reverse($messages));
}

function chat_rest_post_message($request) {
    $user_id = get_current_user_id();
    if (!$user_id) {
        return new WP_Error('not_logged_in', 'User not logged in', array('status' => 401));
    }
    $room_id = (int) $request->get_param('room_id');
    $message = sanitize_textarea_field($request->get_param('message'));
    if (empty($room_id) || empty($message)) {
        return new WP_Error('missing_fields', 'room_id and message are required', array('status' => 400));
    }
    if (!chat_is_user_member_of_room($user_id, $room_id)) {
        return new WP_Error('forbidden', 'You are not a member of this room', array('status' => 403));
    }
    $last_sent = get_transient('chat_last_sent_' . $user_id);
    if ($last_sent && time() - $last_sent < 5) {
        return new WP_Error('rate_limit', 'Please wait before sending another message', array('status' => 429));
    }
    set_transient('chat_last_sent_' . $user_id, time(), 5);
    $inserted = chat_insert_message($room_id, $user_id, $message);
    if (!$inserted) {
        return new WP_Error('db_error', 'Could not save message', array('status' => 500));
    }
    global $wpdb;
    $message_id = $wpdb->insert_id;
    chat_notify_websocket_server($message_id);
    return rest_ensure_response(array('success' => true, 'id' => $message_id));
}

function chat_rest_update_message($request) {
    $user_id = get_current_user_id();
    if (!$user_id) {
        return new WP_Error('not_logged_in', 'Not logged in', array('status' => 401));
    }
    $message_id = (int) $request->get_param('id');
    $new_message = sanitize_textarea_field($request->get_param('message'));
    if (empty($new_message)) {
        return new WP_Error('empty_message', 'Message cannot be empty', array('status' => 400));
    }
    $updated = chat_update_message($message_id, $new_message, $user_id);
    if (!$updated) {
        return new WP_Error('update_failed', 'Could not update message', array('status' => 403));
    }
    global $wpdb;
    $table = $wpdb->prefix . 'chat_messages';
    $message = $wpdb->get_row($wpdb->prepare(
        "SELECT m.*, u.display_name as user_name
         FROM $table m
         JOIN {$wpdb->users} u ON m.user_id = u.ID
         WHERE m.id = %d",
        $message_id
    ));
    chat_notify_websocket_update_message($message);
    return rest_ensure_response($message);
}

function chat_rest_delete_message($request) {
    $user_id = get_current_user_id();
    if (!$user_id) {
        return new WP_Error('not_logged_in', 'Not logged in', array('status' => 401));
    }
    $message_id = (int) $request->get_param('id');
    $deleted = chat_delete_message($message_id, $user_id);
    if (!$deleted) {
        return new WP_Error('delete_failed', 'Could not delete message', array('status' => 403));
    }
    global $wpdb;
    $table = $wpdb->prefix . 'chat_messages';
    $message = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $message_id));
    chat_notify_websocket_delete_message($message);
    return rest_ensure_response(array('success' => true));
}

function chat_rest_get_single_message($request) {
    $id = (int) $request->get_param('id');
    global $wpdb;
    $table = $wpdb->prefix . 'chat_messages';
    $message = $wpdb->get_row($wpdb->prepare(
        "SELECT m.*, u.display_name as user_name
         FROM $table m
         JOIN {$wpdb->users} u ON m.user_id = u.ID
         WHERE m.id = %d",
        $id
    ));
    if (!$message) {
        return new WP_Error('not_found', 'Message not found', array('status' => 404));
    }
    return rest_ensure_response($message);
}

function chat_notify_websocket_server($message_id) {
    $node_url = defined('CHAT_NODE_SERVER_URL') ? CHAT_NODE_SERVER_URL : 'http://localhost:3000/new-message';
    wp_remote_post($node_url, array(
        'body'    => json_encode(array('message_id' => $message_id)),
        'headers' => array('Content-Type' => 'application/json'),
        'timeout' => 0.01,
    ));
}

function chat_notify_websocket_new_room($room_data) {
    $node_url = defined('CHAT_NODE_SERVER_URL') ? CHAT_NODE_SERVER_URL : 'http://localhost:3000/new-room';
    wp_remote_post($node_url, array(
        'body'    => json_encode(array('room' => $room_data)),
        'headers' => array('Content-Type' => 'application/json'),
        'timeout' => 0.01,
    ));
}

function chat_notify_websocket_update_message($message) {
    $node_url = defined('CHAT_NODE_SERVER_URL') ? CHAT_NODE_SERVER_URL : 'http://localhost:3000/update-message';
    wp_remote_post($node_url, array(
        'body'    => json_encode(array('message' => $message)),
        'headers' => array('Content-Type' => 'application/json'),
        'timeout' => 0.01,
    ));
}

function chat_notify_websocket_delete_message($message) {
    $node_url = defined('CHAT_NODE_SERVER_URL') ? CHAT_NODE_SERVER_URL : 'http://localhost:3000/delete-message';
    wp_remote_post($node_url, array(
        'body'    => json_encode(array('message' => $message)),
        'headers' => array('Content-Type' => 'application/json'),
        'timeout' => 0.01,
    ));
}