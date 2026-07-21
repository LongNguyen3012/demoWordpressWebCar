<?php
add_action('rest_api_init', 'chat_register_routes');
function chat_register_routes() {
    register_rest_route('mytheme/v1', '/chat/message/(?P<id>\d+)', array(
        'methods'  => 'GET',
        'callback' => 'chat_rest_get_single_message',
        'permission_callback' => '__return_true'
    ));

    register_rest_route('mytheme/v1', '/chat/messages', array(
        'methods'  => 'POST',
        'callback' => 'chat_rest_post_message',
        'permission_callback' => 'chat_rest_require_login'
    ));
    register_rest_route('mytheme/v1', '/chat/rooms', array(
        'methods'  => 'GET',
        'callback' => 'chat_rest_get_rooms',
        'permission_callback' => 'chat_rest_require_login'
    ));
    register_rest_route('mytheme/v1', '/chat/rooms', array(
        'methods'  => 'POST',
        'callback' => 'chat_rest_create_room',
        'permission_callback' => 'chat_rest_require_login'
    ));
    register_rest_route('mytheme/v1', '/chat/rooms/(?P<id>\d+)/members', array(
        'methods'  => 'POST',
        'callback' => 'chat_rest_add_member',
        'permission_callback' => 'chat_rest_require_login'
    ));
    register_rest_route('mytheme/v1', '/chat/rooms/(?P<id>\d+)/messages', array(
        'methods'  => 'GET',
        'callback' => 'chat_rest_get_room_messages',
        'permission_callback' => 'chat_rest_require_login'
    ));
    register_rest_route('mytheme/v1', '/chat/users', array(
        'methods'  => 'GET',
        'callback' => 'chat_rest_get_users',
        'permission_callback' => 'chat_rest_require_login'
    ));
    register_rest_route('mytheme/v1', '/chat/messages/(?P<id>\d+)', array(
        'methods'  => 'PUT',
        'callback' => 'chat_rest_update_message',
        'permission_callback' => 'chat_rest_require_login'
    ));
    register_rest_route('mytheme/v1', '/chat/messages/(?P<id>\d+)', array(
        'methods'  => 'DELETE',
        'callback' => 'chat_rest_delete_message',
        'permission_callback' => 'chat_rest_require_login'
    ));
    register_rest_route('mytheme/v1', '/chat/messages/(?P<id>\d+)/reactions', array(
        'methods'  => 'POST',
        'callback' => 'chat_rest_add_reaction',
        'permission_callback' => 'chat_rest_require_login'
    ));
    register_rest_route('mytheme/v1', '/chat/messages/(?P<id>\d+)/reactions/(?P<reaction>[^/]+)', array(
        'methods'  => 'DELETE',
        'callback' => 'chat_rest_remove_reaction',
        'permission_callback' => 'chat_rest_require_login'
    ));
    register_rest_route('mytheme/v1', '/chat/rooms/(?P<id>\d+)/read', array(
        'methods'  => 'POST',
        'callback' => 'chat_rest_mark_read',
        'permission_callback' => 'chat_rest_require_login'
    ));
    register_rest_route('mytheme/v1', '/chat/direct/(?P<user_id>\d+)', array(
        'methods'  => 'GET',
        'callback' => 'chat_rest_get_direct_room',
        'permission_callback' => 'chat_rest_require_login'
    ));
    register_rest_route('mytheme/v1', '/chat/rooms/(?P<id>\d+)/leave', array(
        'methods'  => 'POST',
        'callback' => 'chat_rest_leave_room',
        'permission_callback' => 'chat_rest_require_login'
    ));
    register_rest_route('mytheme/v1', '/chat/rooms/(?P<id>\d+)/invite', array(
        'methods'  => 'POST',
        'callback' => 'chat_rest_invite_user',
        'permission_callback' => 'chat_rest_require_login'
    ));

    register_rest_route('mytheme/v1', '/chat/notifications', array(
        'methods'  => 'GET',
        'callback' => 'chat_rest_get_notifications',
        'permission_callback' => 'chat_rest_require_login'
    ));
    register_rest_route('mytheme/v1', '/chat/notifications/(?P<id>\d+)/read', array(
        'methods'  => 'POST',
        'callback' => 'chat_rest_mark_notification_read',
        'permission_callback' => 'chat_rest_require_login'
    ));
    register_rest_route('mytheme/v1', '/chat/notifications/read-all', array(
        'methods'  => 'POST',
        'callback' => 'chat_rest_mark_all_notifications_read',
        'permission_callback' => 'chat_rest_require_login'
    ));
    register_rest_route('mytheme/v1', '/chat/notifications/(?P<id>\d+)', array(
        'methods'  => 'DELETE',
        'callback' => 'chat_rest_delete_notification',
        'permission_callback' => 'chat_rest_require_login'
    ));
    register_rest_route('mytheme/v1', '/chat/notifications/clear-read', array(
        'methods'  => 'POST',
        'callback' => 'chat_rest_clear_read_notifications',
        'permission_callback' => 'chat_rest_require_login'
    ));

    register_rest_route('mytheme/v1', '/chat/upload', array(
        'methods'  => 'POST',
        'callback' => 'chat_rest_upload_file',
        'permission_callback' => 'chat_rest_require_login'
    ));

    register_rest_route('mytheme/v1', '/chat/messages/search', array(
        'methods'  => 'GET',
        'callback' => 'chat_rest_search_messages',
        'permission_callback' => 'chat_rest_require_login'
    ));
}

function chat_rest_require_login() {
    if (is_user_logged_in()) {
        return true;
    }
    return new WP_Error('unauthorized', 'You must be logged in.', array('status' => 401));
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
    // For direct rooms, override the name with the other participant's name
    foreach ($rooms as $room) {
        if ($room->type === 'direct') {
            $members = chat_get_room_members($room->id);
            $other_user_id = array_filter($members, function($id) use ($user_id) {
                return (int)$id !== (int)$user_id;
            });
            $other_user_id = reset($other_user_id);
            if ($other_user_id) {
                $other_user = get_userdata($other_user_id);
                if ($other_user) {
                    $room->name = $other_user->display_name;
                }
            }
        }
    }
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
    $limit = $request->get_param('limit') ?: 30;
    $offset = $request->get_param('offset') ?: 0;
    $before = $request->get_param('before') ? (int)$request->get_param('before') : null;
    $is_admin = current_user_can('manage_options');
    $messages = chat_get_messages_by_room($room_id, $limit, $offset, $is_admin, $before);

    $message_ids = array_column($messages, 'id');
    $reactions_by_msg = [];
    if (!empty($message_ids)) {
        $reactions_by_msg = chat_get_reactions_for_messages($message_ids);
    }
    foreach ($messages as &$msg) {
        $msg->reactions = isset($reactions_by_msg[$msg->id]) ? $reactions_by_msg[$msg->id] : [];
        if ($msg->attachment) {
            $msg->attachment = json_decode($msg->attachment, true);
        }
    }

    $has_more = false;
    if (count($messages) === $limit) {
        $oldest_id = $messages[count($messages) - 1]->id;
        $has_more = true;
    }

    $last_read = chat_get_user_last_read($room_id, $user_id);

    return rest_ensure_response([
        'messages' => array_reverse($messages),
        'last_read_message_id' => $last_read ? (int)$last_read : null,
        'has_more' => $has_more
    ]);
}

function chat_rest_post_message($request) {
    $user_id = get_current_user_id();
    if (!$user_id) {
        return new WP_Error('not_logged_in', 'User not logged in', array('status' => 401));
    }
    $room_id = (int) $request->get_param('room_id');
    $message = sanitize_textarea_field($request->get_param('message'));
    $attachment = $request->get_param('attachment');
    if (empty($room_id) || (empty($message) && empty($attachment))) {
        return new WP_Error('missing_fields', 'room_id and message or attachment are required', array('status' => 400));
    }
    if (!chat_is_user_member_of_room($user_id, $room_id)) {
        return new WP_Error('forbidden', 'You are not a member of this room', array('status' => 403));
    }
    $last_sent = get_transient('chat_last_sent_' . $user_id);
    if ($last_sent && time() - $last_sent < 5) {
        return new WP_Error('rate_limit', 'Please wait before sending another message', array('status' => 429));
    }
    set_transient('chat_last_sent_' . $user_id, time(), 5);
    $inserted = chat_insert_message($room_id, $user_id, $message, $attachment);
    if (!$inserted) {
        return new WP_Error('db_error', 'Could not save message', array('status' => 500));
    }
    global $wpdb;
    $message_id = $wpdb->insert_id;
    chat_notify_websocket_server($message_id);

    $members = chat_get_room_members($room_id);
    $current_user = $user_id;
    $user_name = wp_get_current_user()->display_name;
    $chat_page = get_page_by_path('chat');
    $chat_url = $chat_page ? get_permalink($chat_page) : home_url('/');
    foreach ($members as $member) {
        if ((int)$member === (int)$current_user) continue;
        // Get room name for content
        $room = $wpdb->get_row($wpdb->prepare("SELECT name, type FROM {$wpdb->prefix}chat_rooms WHERE id = %d", $room_id));
        $room_name = $room->name ?: 'Room';
        if ($room->type === 'direct') {
            $other_user = get_userdata($member);
            $room_name = $other_user ? $other_user->display_name : 'User';
            $content = sprintf(__t('notification_new_message_direct', '%s sent you a message'), $user_name);
        } else {
            $content = sprintf(__t('notification_new_message_group', '%s sent a message in %s'), $user_name, $room_name);
        }
        $link = add_query_arg('room', $room_id, $chat_url);
        chat_create_notification($member, 'message', $content, $link, $room_id);
    }
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
    global $wpdb;
    $table = $wpdb->prefix . 'chat_messages';
    $message = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $message_id));
    if (!$message) {
        return new WP_Error('not_found', 'Message not found', array('status' => 404));
    }
    if ($message->user_id != $user_id && !current_user_can('manage_options')) {
        error_log('Update message forbidden: user ' . $user_id . ' tried to edit message ' . $message_id . ' owned by ' . $message->user_id);
        return new WP_Error('forbidden', 'You cannot edit this message', array('status' => 403));
    }
    $updated = chat_update_message($message_id, $new_message, $user_id);
    if (!$updated) {
        error_log('Update message failed for ID ' . $message_id);
        return new WP_Error('update_failed', 'Could not update message', array('status' => 500));
    }
    $updated_message = $wpdb->get_row($wpdb->prepare(
        "SELECT m.*, u.display_name as user_name
         FROM $table m
         JOIN {$wpdb->users} u ON m.user_id = u.ID
         WHERE m.id = %d",
        $message_id
    ));
    chat_notify_websocket_update_message($updated_message);
    return rest_ensure_response($updated_message);
}

function chat_rest_delete_message($request) {
    $user_id = get_current_user_id();
    if (!$user_id) {
        return new WP_Error('not_logged_in', 'Not logged in', array('status' => 401));
    }
    $message_id = (int) $request->get_param('id');
    global $wpdb;
    $table = $wpdb->prefix . 'chat_messages';
    $message = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $message_id));
    if (!$message) {
        return new WP_Error('not_found', 'Message not found', array('status' => 404));
    }
    if ($message->user_id != $user_id && !current_user_can('manage_options')) {
        error_log('Delete message forbidden: user ' . $user_id . ' tried to delete message ' . $message_id . ' owned by ' . $message->user_id);
        return new WP_Error('forbidden', 'You cannot delete this message', array('status' => 403));
    }
    $deleted = chat_delete_message($message_id, $user_id);
    if (!$deleted) {
        error_log('Delete message failed for ID ' . $message_id);
        return new WP_Error('delete_failed', 'Could not delete message', array('status' => 500));
    }
    $updated = $wpdb->get_row($wpdb->prepare(
        "SELECT m.*, u.display_name as user_name
         FROM $table m
         JOIN {$wpdb->users} u ON m.user_id = u.ID
         WHERE m.id = %d",
        $message_id
    ));
    chat_notify_websocket_delete_message($message);
    return rest_ensure_response($updated);
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
    if ($message->attachment) {
        $message->attachment = json_decode($message->attachment, true);
    }
    return rest_ensure_response($message);
}

function chat_rest_get_direct_room($request) {
    $user_id = get_current_user_id();
    $other_user_id = (int) $request->get_param('user_id');
    if (!$other_user_id || $other_user_id == $user_id) {
        return new WP_Error('invalid_user', 'Invalid user', array('status' => 400));
    }
    $room_id = chat_get_direct_room($user_id, $other_user_id);
    if (!$room_id) {
        return new WP_Error('db_error', 'Could not create room', array('status' => 500));
    }
    global $wpdb;
    $room = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}chat_rooms WHERE id = %d", $room_id));
    return rest_ensure_response($room);
}

function chat_rest_leave_room($request) {
    $user_id = get_current_user_id();
    $room_id = (int) $request->get_param('id');
    if (!chat_is_user_member_of_room($user_id, $room_id)) {
        return new WP_Error('not_member', 'You are not a member of this room', array('status' => 403));
    }
    global $wpdb;
    $room = $wpdb->get_row($wpdb->prepare("SELECT type FROM {$wpdb->prefix}chat_rooms WHERE id = %d", $room_id));
    if ($room && $room->type === 'direct') {
        return new WP_Error('cannot_leave', 'Cannot leave a direct chat', array('status' => 400));
    }
    $removed = chat_remove_member($room_id, $user_id);
    if (!$removed) {
        return new WP_Error('db_error', 'Could not leave room', array('status' => 500));
    }
    chat_notify_websocket_member_removed($room_id, $user_id);

    $members = chat_get_room_members($room_id);
    $user_name = wp_get_current_user()->display_name;
    $room_name = $room->name ?: 'Room';
    $chat_page = get_page_by_path('chat');
    $chat_url = $chat_page ? get_permalink($chat_page) : home_url('/');
    foreach ($members as $member) {
        if ((int)$member === (int)$user_id) continue;
        $content = sprintf(__t('notification_left', '%s left the room "%s"'), $user_name, $room_name);
        chat_create_notification($member, 'leave', $content, $chat_url);
    }

    return rest_ensure_response(array('success' => true));
}

function chat_rest_invite_user($request) {
    $user_id = get_current_user_id();
    $room_id = (int) $request->get_param('id');
    $target_user_id = (int) $request->get_param('user_id');
    if (!chat_is_user_member_of_room($user_id, $room_id)) {
        return new WP_Error('not_member', 'You are not a member of this room', array('status' => 403));
    }
    if (chat_is_user_member_of_room($target_user_id, $room_id)) {
        return new WP_Error('already_member', 'User is already a member', array('status' => 400));
    }
    chat_add_member($room_id, $target_user_id);
    chat_notify_websocket_member_added($room_id, $target_user_id);

    $inviter_name = wp_get_current_user()->display_name;
    global $wpdb;
    $room = $wpdb->get_row($wpdb->prepare("SELECT name FROM {$wpdb->prefix}chat_rooms WHERE id = %d", $room_id));
    $room_name = $room->name ?: 'Room';
    $chat_page = get_page_by_path('chat');
    $chat_url = $chat_page ? get_permalink($chat_page) : home_url('/');
    $content = sprintf(__t('notification_invited', '%s invited you to join "%s"'), $inviter_name, $room_name);
    $link = add_query_arg('room', $room_id, $chat_url);
    chat_create_notification($target_user_id, 'invite', $content, $link);

    return rest_ensure_response(array('success' => true));
}

function chat_rest_get_notifications($request) {
    $user_id = get_current_user_id();
    global $wpdb;
    $table = $wpdb->prefix . 'chat_notifications';
    $notifications = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $table WHERE user_id = %d ORDER BY created_at DESC LIMIT 50",
        $user_id
    ));
    $unread_count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table WHERE user_id = %d AND is_read = 0",
        $user_id
    ));
    return rest_ensure_response(array(
        'notifications' => $notifications,
        'unread_count'  => (int)$unread_count
    ));
}

function chat_rest_mark_notification_read($request) {
    $user_id = get_current_user_id();
    $notif_id = (int) $request->get_param('id');
    global $wpdb;
    $table = $wpdb->prefix . 'chat_notifications';
    $result = $wpdb->update(
        $table,
        array('is_read' => 1),
        array('id' => $notif_id, 'user_id' => $user_id)
    );
    if ($result === false) {
        return new WP_Error('db_error', 'Could not update', array('status' => 500));
    }
    return rest_ensure_response(array('success' => true));
}

function chat_rest_mark_all_notifications_read($request) {
    $user_id = get_current_user_id();
    global $wpdb;
    $table = $wpdb->prefix . 'chat_notifications';
    $result = $wpdb->update(
        $table,
        array('is_read' => 1),
        array('user_id' => $user_id, 'is_read' => 0)
    );
    if ($result === false) {
        return new WP_Error('db_error', 'Could not update', array('status' => 500));
    }
    return rest_ensure_response(array('success' => true));
}

// --- WebSocket broadcast functions with attachment decoding ---

function chat_notify_websocket_notification($user_id, $notification) {
    $base = defined('CHAT_NODE_SERVER_URL') ? CHAT_NODE_SERVER_URL : 'http://localhost:3000';
    $node_url = $base . '/new-notification';
    error_log('Attempting to notify Node for user ' . $user_id . ' at ' . $node_url);
    $response = wp_remote_post($node_url, array(
        'body'    => json_encode(array('user_id' => $user_id, 'notification' => $notification)),
        'headers' => array('Content-Type' => 'application/json'),
        'timeout' => 0.5,
    ));
    if (is_wp_error($response)) {
        error_log('Node notification failed: ' . $response->get_error_message());
    } else {
        $code = wp_remote_retrieve_response_code($response);
        error_log('Node notification response: HTTP ' . $code);
    }
}

function chat_notify_websocket_server($message_id) {
    global $wpdb;
    $table = $wpdb->prefix . 'chat_messages';
    $message = $wpdb->get_row($wpdb->prepare(
        "SELECT m.*, u.display_name as user_name
         FROM $table m
         JOIN {$wpdb->users} u ON m.user_id = u.ID
         WHERE m.id = %d",
        $message_id
    ));
    if (!$message) {
        error_log('[chat] No message found for ID ' . $message_id);
        return;
    }
    if ($message->attachment) {
        $message->attachment = json_decode($message->attachment, true);
    }

    $base = defined('CHAT_NODE_SERVER_URL') ? CHAT_NODE_SERVER_URL : 'http://localhost:3000';
    $node_url = $base . '/new-message';
    error_log('[chat] Sending to Node at: ' . $node_url);

    $response = wp_remote_post($node_url, array(
        'body'    => json_encode(array('message' => $message)),
        'headers' => array('Content-Type' => 'application/json'),
        'timeout' => 0.5,
    ));

    if (is_wp_error($response)) {
        error_log('[chat] Node request FAILED: ' . $response->get_error_message());
    } else {
        $code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        error_log('[chat] Node response: HTTP ' . $code . ' - ' . $body);
    }
}

function chat_notify_websocket_new_room($room_data) {
    $base = defined('CHAT_NODE_SERVER_URL') ? CHAT_NODE_SERVER_URL : 'http://localhost:3000';
    $node_url = $base . '/new-room';
    $response = wp_remote_post($node_url, array(
        'body'    => json_encode(array('room' => $room_data)),
        'headers' => array('Content-Type' => 'application/json'),
        'timeout' => 0.5,
    ));
    if (is_wp_error($response)) {
        error_log('New room notification failed: ' . $response->get_error_message());
    }
}

function chat_notify_websocket_update_message($message) {
    if (isset($message->attachment) && $message->attachment) {
        $message->attachment = json_decode($message->attachment, true);
    }
    $base = defined('CHAT_NODE_SERVER_URL') ? CHAT_NODE_SERVER_URL : 'http://localhost:3000';
    $node_url = $base . '/update-message';
    $response = wp_remote_post($node_url, array(
        'body'    => json_encode(array('message' => $message)),
        'headers' => array('Content-Type' => 'application/json'),
        'timeout' => 0.5,
    ));
    if (is_wp_error($response)) {
        error_log('Update message notification failed: ' . $response->get_error_message());
    }
}

function chat_notify_websocket_delete_message($message) {
    if (isset($message->attachment) && $message->attachment) {
        $message->attachment = json_decode($message->attachment, true);
    }
    $base = defined('CHAT_NODE_SERVER_URL') ? CHAT_NODE_SERVER_URL : 'http://localhost:3000';
    $node_url = $base . '/delete-message';
    $response = wp_remote_post($node_url, array(
        'body'    => json_encode(array('message' => $message)),
        'headers' => array('Content-Type' => 'application/json'),
        'timeout' => 0.5,
    ));
    if (is_wp_error($response)) {
        error_log('Delete message notification failed: ' . $response->get_error_message());
    }
}

function chat_notify_websocket_reaction($message_id, $user_id, $reaction, $action) {
    $base = defined('CHAT_NODE_SERVER_URL') ? CHAT_NODE_SERVER_URL : 'http://localhost:3000';
    $node_url = $base . '/reaction';
    $response = wp_remote_post($node_url, array(
        'body'    => json_encode(array('message_id' => $message_id, 'user_id' => $user_id, 'reaction' => $reaction, 'action' => $action)),
        'headers' => array('Content-Type' => 'application/json'),
        'timeout' => 0.5,
    ));
    if (is_wp_error($response)) {
        error_log('Reaction notification failed: ' . $response->get_error_message());
    }
}

function chat_notify_websocket_read($room_id, $user_id, $last_message_id) {
    $base = defined('CHAT_NODE_SERVER_URL') ? CHAT_NODE_SERVER_URL : 'http://localhost:3000';
    $node_url = $base . '/read';
    $response = wp_remote_post($node_url, array(
        'body'    => json_encode(array('room_id' => $room_id, 'user_id' => $user_id, 'last_message_id' => $last_message_id)),
        'headers' => array('Content-Type' => 'application/json'),
        'timeout' => 0.5,
    ));
    if (is_wp_error($response)) {
        error_log('Read receipt notification failed: ' . $response->get_error_message());
    }
}

function chat_notify_websocket_member_added($room_id, $user_id) {
    $base = defined('CHAT_NODE_SERVER_URL') ? CHAT_NODE_SERVER_URL : 'http://localhost:3000';
    $node_url = $base . '/member-added';
    wp_remote_post($node_url, array(
        'body'    => json_encode(array('room_id' => $room_id, 'user_id' => $user_id)),
        'headers' => array('Content-Type' => 'application/json'),
        'timeout' => 0.5,
    ));
}

function chat_notify_websocket_member_removed($room_id, $user_id) {
    $base = defined('CHAT_NODE_SERVER_URL') ? CHAT_NODE_SERVER_URL : 'http://localhost:3000';
    $node_url = $base . '/member-removed';
    wp_remote_post($node_url, array(
        'body'    => json_encode(array('room_id' => $room_id, 'user_id' => $user_id)),
        'headers' => array('Content-Type' => 'application/json'),
        'timeout' => 0.5,
    ));
}

// --- Reactions and Read (unchanged) ---

function chat_rest_add_reaction($request) {
    $user_id = get_current_user_id();
    if (!$user_id) {
        return new WP_Error('not_logged_in', 'Not logged in', array('status' => 401));
    }
    $message_id = (int) $request->get_param('id');
    $reaction = sanitize_text_field($request->get_param('reaction'));
    if (empty($reaction)) {
        return new WP_Error('missing_reaction', 'Reaction is required', array('status' => 400));
    }
    $inserted = chat_add_reaction($message_id, $user_id, $reaction);
    chat_notify_websocket_reaction($message_id, $user_id, $reaction, 'add');
    return rest_ensure_response(array('success' => true, 'id' => $inserted));
}

function chat_rest_remove_reaction($request) {
    $user_id = get_current_user_id();
    if (!$user_id) {
        return new WP_Error('not_logged_in', 'Not logged in', array('status' => 401));
    }
    $message_id = (int) $request->get_param('id');
    $reaction = urldecode($request->get_param('reaction'));
    if (empty($reaction)) {
        return new WP_Error('missing_reaction', 'Reaction is required', array('status' => 400));
    }
    global $wpdb;
    $table = $wpdb->prefix . 'chat_reactions';
    $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE message_id = %d AND user_id = %d AND reaction = %s", $message_id, $user_id, $reaction));
    if (!$row) {
        return new WP_Error('not_found', 'Reaction not found', array('status' => 404));
    }
    $deleted = $wpdb->delete($table, array('id' => $row->id));
    if (!$deleted) {
        return new WP_Error('delete_failed', 'Could not delete reaction', array('status' => 500));
    }
    chat_notify_websocket_reaction($message_id, $user_id, $reaction, 'remove');
    return rest_ensure_response(array('success' => true));
}

function chat_rest_mark_read($request) {
    $user_id = get_current_user_id();
    if (!$user_id) {
        return new WP_Error('not_logged_in', 'Not logged in', array('status' => 401));
    }
    $room_id = (int) $request->get_param('id');
    $last_message_id = (int) $request->get_param('last_message_id');
    if (!$last_message_id) {
        return new WP_Error('missing_last_message', 'last_message_id required', array('status' => 400));
    }
    chat_mark_read($room_id, $user_id, $last_message_id);
    chat_notify_websocket_read($room_id, $user_id, $last_message_id);
    return rest_ensure_response(array('success' => true));
}

function chat_rest_delete_notification($request) {
    $user_id = get_current_user_id();
    $notif_id = (int) $request->get_param('id');
    global $wpdb;
    $table = $wpdb->prefix . 'chat_notifications';
    $result = $wpdb->delete(
        $table,
        array('id' => $notif_id, 'user_id' => $user_id)
    );
    if ($result === false) {
        return new WP_Error('db_error', 'Could not delete notification', array('status' => 500));
    }
    if ($result === 0) {
        return new WP_Error('not_found', 'Notification not found', array('status' => 404));
    }
    return rest_ensure_response(array('success' => true));
}

function chat_rest_clear_read_notifications($request) {
    $user_id = get_current_user_id();
    global $wpdb;
    $table = $wpdb->prefix . 'chat_notifications';
    $result = $wpdb->delete(
        $table,
        array('user_id' => $user_id, 'is_read' => 1)
    );
    if ($result === false) {
        return new WP_Error('db_error', 'Could not clear read notifications', array('status' => 500));
    }
    return rest_ensure_response(array('success' => true, 'deleted_count' => (int)$result));
}

// File upload endpoint
function chat_rest_upload_file($request) {
    $user_id = get_current_user_id();
    if (!$user_id) {
        return new WP_Error('not_logged_in', 'User not logged in', array('status' => 401));
    }

    if (empty($_FILES['file'])) {
        return new WP_Error('no_file', 'No file uploaded', array('status' => 400));
    }

    $file = $_FILES['file'];
    $allowed_types = array('image/jpeg', 'image/png', 'image/gif', 'image/webp', 'application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mime_type, $allowed_types)) {
        return new WP_Error('invalid_type', 'File type not allowed. Allowed: JPG, PNG, GIF, WebP, PDF, DOC, DOCX', array('status' => 400));
    }

    require_once(ABSPATH . 'wp-admin/includes/file.php');
    require_once(ABSPATH . 'wp-admin/includes/media.php');
    require_once(ABSPATH . 'wp-admin/includes/image.php');

    $attachment_id = media_handle_upload('file', 0);
    if (is_wp_error($attachment_id)) {
        return new WP_Error('upload_failed', $attachment_id->get_error_message(), array('status' => 500));
    }

    $attachment_url = wp_get_attachment_url($attachment_id);
    $attachment_data = get_post($attachment_id);
    $attachment = array(
        'id'   => $attachment_id,
        'url'  => $attachment_url,
        'name' => $attachment_data->post_title,
        'type' => $mime_type,
        'size' => filesize(get_attached_file($attachment_id)),
    );

    return rest_ensure_response(array('success' => true, 'attachment' => $attachment));
}

function chat_rest_search_messages($request) {
    $user_id = get_current_user_id();
    if (!$user_id) {
        return new WP_Error('not_logged_in', 'User not logged in', array('status' => 401));
    }

    $query = trim($request->get_param('q'));
    if (empty($query)) {
        return new WP_Error('missing_query', 'Search query is required', array('status' => 400));
    }

    if (strlen($query) < 2) {
        return new WP_Error('query_too_short', 'Search query must be at least 2 characters', array('status' => 400));
    }

    $room_id = $request->get_param('room_id') ? (int)$request->get_param('room_id') : null;
    $limit = min(100, $request->get_param('limit') ? (int)$request->get_param('limit') : 20);
    $is_admin = current_user_can('manage_options');

    $messages = chat_search_messages($user_id, $query, $room_id, $limit, $is_admin);

    if (is_wp_error($messages)) {
        return $messages;
    }

    return rest_ensure_response($messages);
}