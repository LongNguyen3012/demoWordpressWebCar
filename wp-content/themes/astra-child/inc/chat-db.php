<?php
function chat_create_tables() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    $table_rooms = $wpdb->prefix . 'chat_rooms';
    $table_messages = $wpdb->prefix . 'chat_messages';
    $table_members = $wpdb->prefix . 'chat_room_members';
    $table_reactions = $wpdb->prefix . 'chat_reactions';
    $table_read = $wpdb->prefix . 'chat_read_receipts';
    $table_notifications = $wpdb->prefix . 'chat_notifications';

    $sql_rooms = "CREATE TABLE $table_rooms (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        name VARCHAR(255) DEFAULT '',
        type ENUM('direct','group') NOT NULL DEFAULT 'group',
        created_by BIGINT(20) UNSIGNED NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) $charset_collate;";

    $sql_messages = "CREATE TABLE $table_messages (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        room_id BIGINT(20) UNSIGNED NOT NULL,
        user_id BIGINT(20) UNSIGNED NOT NULL,
        message TEXT NOT NULL,
        attachment TEXT NULL DEFAULT NULL,
        edited_at DATETIME NULL,
        deleted_at DATETIME NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY room_id (room_id),
        KEY user_id (user_id),
        KEY created_at (created_at)
    ) $charset_collate;";

    $sql_members = "CREATE TABLE $table_members (
        room_id BIGINT(20) UNSIGNED NOT NULL,
        user_id BIGINT(20) UNSIGNED NOT NULL,
        joined_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (room_id, user_id),
        KEY user_id (user_id)
    ) $charset_collate;";

    $sql_reactions = "CREATE TABLE $table_reactions (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        message_id BIGINT(20) UNSIGNED NOT NULL,
        user_id BIGINT(20) UNSIGNED NOT NULL,
        reaction VARCHAR(32) NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY message_user (message_id, user_id)
    ) $charset_collate;";

    $sql_read = "CREATE TABLE $table_read (
        room_id BIGINT(20) UNSIGNED NOT NULL,
        user_id BIGINT(20) UNSIGNED NOT NULL,
        last_read_message_id BIGINT(20) UNSIGNED NOT NULL,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (room_id, user_id)
    ) $charset_collate;";

    $sql_notifications = "CREATE TABLE $table_notifications (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id BIGINT(20) UNSIGNED NOT NULL,
        room_id BIGINT(20) UNSIGNED NULL DEFAULT NULL,
        type VARCHAR(50) NOT NULL DEFAULT 'message',
        content TEXT NOT NULL,
        link VARCHAR(255) DEFAULT '',
        count INT DEFAULT 1,
        is_read TINYINT(1) DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY user_id (user_id),
        KEY room_id (room_id),
        KEY is_read (is_read)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql_rooms);
    dbDelta($sql_messages);
    dbDelta($sql_members);
    dbDelta($sql_reactions);
    dbDelta($sql_read);
    dbDelta($sql_notifications);
}

function chat_upgrade_tables() {
    global $wpdb;
    $table_messages = $wpdb->prefix . 'chat_messages';
    $table_notifications = $wpdb->prefix . 'chat_notifications';
    $wpdb->query("ALTER TABLE $table_messages ADD COLUMN edited_at DATETIME NULL AFTER message");
    $wpdb->query("ALTER TABLE $table_messages ADD COLUMN deleted_at DATETIME NULL AFTER edited_at");
    $wpdb->query("ALTER TABLE $table_messages ADD COLUMN attachment TEXT NULL DEFAULT NULL AFTER message");

    // Add room_id and count columns to notifications (if not exists)
    if (!$wpdb->get_var("SHOW COLUMNS FROM $table_notifications LIKE 'room_id'")) {
        $wpdb->query("ALTER TABLE $table_notifications ADD COLUMN room_id BIGINT(20) UNSIGNED NULL DEFAULT NULL AFTER user_id");
    }
    if (!$wpdb->get_var("SHOW COLUMNS FROM $table_notifications LIKE 'count'")) {
        $wpdb->query("ALTER TABLE $table_notifications ADD COLUMN count INT DEFAULT 1 AFTER link");
    }
}

function chat_create_reactions_and_read_tables() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    $table_reactions = $wpdb->prefix . 'chat_reactions';
    $table_read = $wpdb->prefix . 'chat_read_receipts';
    $table_notifications = $wpdb->prefix . 'chat_notifications';

    $sql_reactions = "CREATE TABLE $table_reactions (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        message_id BIGINT(20) UNSIGNED NOT NULL,
        user_id BIGINT(20) UNSIGNED NOT NULL,
        reaction VARCHAR(32) NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY message_user (message_id, user_id)
    ) $charset_collate;";

    $sql_read = "CREATE TABLE $table_read (
        room_id BIGINT(20) UNSIGNED NOT NULL,
        user_id BIGINT(20) UNSIGNED NOT NULL,
        last_read_message_id BIGINT(20) UNSIGNED NOT NULL,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (room_id, user_id)
    ) $charset_collate;";

    $sql_notifications = "CREATE TABLE $table_notifications (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id BIGINT(20) UNSIGNED NOT NULL,
        room_id BIGINT(20) UNSIGNED NULL DEFAULT NULL,
        type VARCHAR(50) NOT NULL DEFAULT 'message',
        content TEXT NOT NULL,
        link VARCHAR(255) DEFAULT '',
        count INT DEFAULT 1,
        is_read TINYINT(1) DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY user_id (user_id),
        KEY room_id (room_id),
        KEY is_read (is_read)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql_reactions);
    dbDelta($sql_read);
    dbDelta($sql_notifications);
}

add_action('admin_init', function() {
    if (!get_option('chat_tables_created')) {
        chat_create_tables();
        update_option('chat_tables_created', 1);
    }
    if (get_option('chat_tables_created') && !get_option('chat_tables_upgraded')) {
        chat_upgrade_tables();
        update_option('chat_tables_upgraded', 1);
    }
    if (!get_option('chat_reactions_tables_created')) {
        chat_create_reactions_and_read_tables();
        update_option('chat_reactions_tables_created', 1);
    }
});

add_action('after_switch_theme', function() {
    chat_create_tables();
    chat_upgrade_tables();
    chat_create_reactions_and_read_tables();
    update_option('chat_tables_created', 1);
    update_option('chat_tables_upgraded', 1);
    update_option('chat_reactions_tables_created', 1);
});

add_action('init', function() {
    global $wpdb;
    $table_reactions = $wpdb->prefix . 'chat_reactions';
    $table_read = $wpdb->prefix . 'chat_read_receipts';
    $table_notifications = $wpdb->prefix . 'chat_notifications';
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_reactions'") != $table_reactions ||
        $wpdb->get_var("SHOW TABLES LIKE '$table_read'") != $table_read ||
        $wpdb->get_var("SHOW TABLES LIKE '$table_notifications'") != $table_notifications) {
        chat_create_reactions_and_read_tables();
        update_option('chat_reactions_tables_created', 1);
    }
});

function chat_create_room($name, $type, $created_by) {
    global $wpdb;
    $wpdb->insert(
        $wpdb->prefix . 'chat_rooms',
        array(
            'name'       => $name,
            'type'       => $type,
            'created_by' => $created_by,
        )
    );
    return $wpdb->insert_id;
}

function chat_add_member($room_id, $user_id) {
    global $wpdb;
    $wpdb->replace(
        $wpdb->prefix . 'chat_room_members',
        array(
            'room_id' => $room_id,
            'user_id' => $user_id,
        )
    );
}

function chat_get_user_rooms($user_id) {
    global $wpdb;
    $table_rooms = $wpdb->prefix . 'chat_rooms';
    $table_members = $wpdb->prefix . 'chat_room_members';
    return $wpdb->get_results($wpdb->prepare(
        "SELECT r.* FROM $table_rooms r
         JOIN $table_members m ON r.id = m.room_id
         WHERE m.user_id = %d
         ORDER BY r.created_at DESC",
        $user_id
    ));
}

function chat_get_room_members($room_id) {
    global $wpdb;
    $table_members = $wpdb->prefix . 'chat_room_members';
    return $wpdb->get_col($wpdb->prepare(
        "SELECT user_id FROM $table_members WHERE room_id = %d",
        $room_id
    ));
}

function chat_get_messages_by_room($room_id, $limit = 50, $offset = 0, $is_admin = false, $before = null) {
    global $wpdb;
    $table_messages = $wpdb->prefix . 'chat_messages';
    $deleted_condition = $is_admin ? '' : 'AND m.deleted_at IS NULL';
    $before_condition = '';
    $params = array($room_id);
    if ($before !== null) {
        $before_condition = 'AND m.id < %d';
        $params[] = $before;
    }
    $sql = $wpdb->prepare(
        "SELECT m.*, u.display_name as user_name
         FROM $table_messages m
         JOIN {$wpdb->users} u ON m.user_id = u.ID
         WHERE m.room_id = %d $deleted_condition $before_condition
         ORDER BY m.created_at DESC
         LIMIT %d OFFSET %d",
        array_merge($params, array($limit, $offset))
    );
    return $wpdb->get_results($sql);
}

function chat_insert_message($room_id, $user_id, $message, $attachment = null) {
    global $wpdb;
    $data = array(
        'room_id'   => $room_id,
        'user_id'   => $user_id,
        'message'   => $message,
        'created_at'=> current_time('mysql'),
    );
    if ($attachment !== null) {
        $data['attachment'] = json_encode($attachment);
    }
    return $wpdb->insert(
        $wpdb->prefix . 'chat_messages',
        $data
    );
}

function chat_update_message($message_id, $new_message, $user_id) {
    global $wpdb;
    $table = $wpdb->prefix . 'chat_messages';
    $message = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $message_id));
    if (!$message || ($message->user_id != $user_id && !current_user_can('manage_options'))) {
        return false;
    }
    $result = $wpdb->update(
        $table,
        array(
            'message'   => $new_message,
            'edited_at' => current_time('mysql'),
        ),
        array('id' => $message_id)
    );
    if ($result === false) {
        error_log('Chat update failed: ' . $wpdb->last_error);
    }
    return $result !== false;
}

function chat_delete_message($message_id, $user_id) {
    global $wpdb;
    $table = $wpdb->prefix . 'chat_messages';
    $message = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $message_id));
    if (!$message || ($message->user_id != $user_id && !current_user_can('manage_options'))) {
        return false;
    }
    $result = $wpdb->update(
        $table,
        array('deleted_at' => current_time('mysql')),
        array('id' => $message_id)
    );
    if ($result === false) {
        error_log('Chat delete failed: ' . $wpdb->last_error);
    }
    return $result !== false;
}

function chat_get_direct_room($user1, $user2) {
    global $wpdb;
    $table_rooms = $wpdb->prefix . 'chat_rooms';
    $table_members = $wpdb->prefix . 'chat_room_members';
    $sql = "SELECT r.id
            FROM $table_rooms r
            JOIN $table_members m ON r.id = m.room_id
            WHERE r.type = 'direct'
            GROUP BY r.id
            HAVING COUNT(m.user_id) = 2
            AND SUM(m.user_id = %d) = 1
            AND SUM(m.user_id = %d) = 1";
    $room_id = $wpdb->get_var($wpdb->prepare($sql, $user1, $user2));
    if ($room_id) return (int)$room_id;
    $other_user = get_userdata($user2);
    $name = $other_user ? $other_user->display_name : 'Direct';
    $room_id = chat_create_room($name, 'direct', $user1);
    chat_add_member($room_id, $user1);
    chat_add_member($room_id, $user2);
    $room_data = array('id' => $room_id, 'name' => $name, 'type' => 'direct', 'created_by' => $user1);
    chat_notify_websocket_new_room($room_data);
    return $room_id;
}

function chat_is_user_member_of_room($user_id, $room_id) {
    global $wpdb;
    $count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}chat_room_members WHERE room_id = %d AND user_id = %d",
        $room_id, $user_id
    ));
    return $count > 0;
}

function chat_get_users($search = '') {
    global $wpdb;
    $like = '%' . $wpdb->esc_like($search) . '%';
    return $wpdb->get_results($wpdb->prepare(
        "SELECT ID, user_login, display_name, user_email
         FROM {$wpdb->users}
         WHERE user_login LIKE %s OR display_name LIKE %s OR user_email LIKE %s
         ORDER BY display_name ASC
         LIMIT 20",
        $like, $like, $like
    ));
}

function chat_add_reaction($message_id, $user_id, $reaction) {
    global $wpdb;
    $table = $wpdb->prefix . 'chat_reactions';
    $wpdb->replace($table, array(
        'message_id' => $message_id,
        'user_id' => $user_id,
        'reaction' => $reaction
    ));
    return $wpdb->insert_id;
}

function chat_remove_reaction($message_id, $user_id) {
    global $wpdb;
    $table = $wpdb->prefix . 'chat_reactions';
    return $wpdb->delete($table, array('message_id' => $message_id, 'user_id' => $user_id));
}

function chat_get_reactions_by_message($message_id) {
    global $wpdb;
    $table = $wpdb->prefix . 'chat_reactions';
    return $wpdb->get_results($wpdb->prepare("SELECT * FROM $table WHERE message_id = %d", $message_id));
}

function chat_mark_read($room_id, $user_id, $last_message_id) {
    global $wpdb;
    $table = $wpdb->prefix . 'chat_read_receipts';
    $wpdb->replace($table, array(
        'room_id' => $room_id,
        'user_id' => $user_id,
        'last_read_message_id' => $last_message_id
    ));
}

function chat_get_read_receipts($room_id) {
    global $wpdb;
    $table = $wpdb->prefix . 'chat_read_receipts';
    return $wpdb->get_results($wpdb->prepare("SELECT * FROM $table WHERE room_id = %d", $room_id));
}

function chat_get_reactions_for_messages($message_ids) {
    global $wpdb;
    if (empty($message_ids)) return [];
    $ids = implode(',', array_map('intval', $message_ids));
    $table = $wpdb->prefix . 'chat_reactions';
    $results = $wpdb->get_results("
        SELECT message_id, reaction, COUNT(*) as count
        FROM $table
        WHERE message_id IN ($ids)
        GROUP BY message_id, reaction
    ");
    $grouped = [];
    foreach ($results as $row) {
        $grouped[$row->message_id][] = (object) [
            'reaction' => $row->reaction,
            'count'    => (int)$row->count,
        ];
    }
    return $grouped;
}

function chat_get_user_last_read($room_id, $user_id) {
    global $wpdb;
    $table = $wpdb->prefix . 'chat_read_receipts';
    return $wpdb->get_var($wpdb->prepare(
        "SELECT last_read_message_id FROM $table WHERE room_id = %d AND user_id = %d",
        $room_id, $user_id
    ));
}

function chat_remove_member($room_id, $user_id) {
    global $wpdb;
    return $wpdb->delete($wpdb->prefix . 'chat_room_members', array('room_id' => $room_id, 'user_id' => $user_id));
}

function chat_create_notification($user_id, $type, $content, $link = '', $room_id = null) {
    global $wpdb;
    $table = $wpdb->prefix . 'chat_notifications';

    if ($type === 'message' && $room_id !== null) {
        // Check for existing unread notification for this user, room, type
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT id, count FROM $table WHERE user_id = %d AND room_id = %d AND type = 'message' AND is_read = 0",
            $user_id, $room_id
        ));
        if ($existing) {
            // Increment count and update timestamp
            $wpdb->update(
                $table,
                array(
                    'count' => $existing->count + 1,
                    'created_at' => current_time('mysql')
                ),
                array('id' => $existing->id)
            );
            $notif_id = $existing->id;
            // Build updated notification object
            $notif = (object) array(
                'id'         => $notif_id,
                'user_id'    => $user_id,
                'room_id'    => $room_id,
                'type'       => $type,
                'content'    => $content,
                'link'       => $link,
                'count'      => $existing->count + 1,
                'is_read'    => 0,
                'created_at' => current_time('mysql')
            );
            error_log('[DEBUG] Updated existing notification ID ' . $notif_id . ' count ' . $notif->count);
            chat_notify_websocket_notification($user_id, $notif);
            return $notif_id;
        }
    }

    // Insert new notification
    $wpdb->insert(
        $table,
        array(
            'user_id'    => $user_id,
            'room_id'    => $room_id,
            'type'       => $type,
            'content'    => $content,
            'link'       => $link,
            'count'      => 1,
            'is_read'    => 0,
            'created_at' => current_time('mysql')
        )
    );
    $notif_id = $wpdb->insert_id;
    if ($notif_id) {
        error_log('[DEBUG] Notification inserted, ID ' . $notif_id . ' for user ' . $user_id);
        $notif = (object) array(
            'id'         => $notif_id,
            'user_id'    => $user_id,
            'room_id'    => $room_id,
            'type'       => $type,
            'content'    => $content,
            'link'       => $link,
            'count'      => 1,
            'is_read'    => 0,
            'created_at' => current_time('mysql')
        );
        error_log('[DEBUG] Calling chat_notify_websocket_notification for user ' . $user_id);
        chat_notify_websocket_notification($user_id, $notif);
    } else {
        error_log('[DEBUG] Notification insert failed for user ' . $user_id);
    }
    return $notif_id;
}

function chat_add_fulltext_index() {
    global $wpdb;
    $table = $wpdb->prefix . 'chat_messages';
    $index_exists = $wpdb->get_var("SHOW INDEX FROM $table WHERE Key_name = 'ft_message'");
    if (!$index_exists) {
        $wpdb->query("ALTER TABLE $table ADD FULLTEXT INDEX ft_message (message)");
        error_log('Fulltext index added to chat_messages.');
    }
}
add_action('admin_init', 'chat_add_fulltext_index');
add_action('after_switch_theme', 'chat_add_fulltext_index');

function chat_search_messages($user_id, $query, $room_id = null, $limit = 20, $is_admin = false) {
    global $wpdb;
    $table_messages = $wpdb->prefix . 'chat_messages';
    $table_rooms = $wpdb->prefix . 'chat_rooms';
    $table_members = $wpdb->prefix . 'chat_room_members';

    $like = '%' . $wpdb->esc_like($query) . '%';

    $sql = "SELECT m.*, u.display_name as user_name
            FROM $table_messages m
            JOIN {$wpdb->users} u ON m.user_id = u.ID
            WHERE 1=1";

    $params = array();

    if (!$is_admin) {
        $sql .= " AND m.deleted_at IS NULL";
    }

    if ($room_id) {
        $is_member = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_members WHERE room_id = %d AND user_id = %d",
            $room_id, $user_id
        ));
        if (!$is_member) {
            return new WP_Error('forbidden', 'You are not a member of this room', array('status' => 403));
        }
        $sql .= " AND m.room_id = %d";
        $params[] = $room_id;
    } else {
        $sql .= " AND m.room_id IN (SELECT room_id FROM $table_members WHERE user_id = %d)";
        $params[] = $user_id;
    }

    $sql .= " AND m.message LIKE %s";
    $params[] = $like;

    $sql .= " ORDER BY m.created_at DESC";

    $sql .= " LIMIT %d";
    $params[] = $limit;

    $prepared = $wpdb->prepare($sql, $params);
    $results = $wpdb->get_results($prepared);

    foreach ($results as &$msg) {
        if ($msg->attachment) {
            $msg->attachment = json_decode($msg->attachment, true);
        }
    }

    return $results;
}