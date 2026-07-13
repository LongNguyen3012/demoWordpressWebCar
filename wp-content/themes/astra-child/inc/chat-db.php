<?php
function chat_create_tables() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    $table_rooms = $wpdb->prefix . 'chat_rooms';
    $table_messages = $wpdb->prefix . 'chat_messages';
    $table_members = $wpdb->prefix . 'chat_room_members';
    $table_reactions = $wpdb->prefix . 'chat_reactions';
    $table_read = $wpdb->prefix . 'chat_read_receipts';

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

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql_rooms);
    dbDelta($sql_messages);
    dbDelta($sql_members);
    dbDelta($sql_reactions);
    dbDelta($sql_read);
}

function chat_upgrade_tables() {
    global $wpdb;
    $table_messages = $wpdb->prefix . 'chat_messages';
    $wpdb->query("ALTER TABLE $table_messages ADD COLUMN edited_at DATETIME NULL AFTER message");
    $wpdb->query("ALTER TABLE $table_messages ADD COLUMN deleted_at DATETIME NULL AFTER edited_at");
}

function chat_create_reactions_and_read_tables() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    $table_reactions = $wpdb->prefix . 'chat_reactions';
    $table_read = $wpdb->prefix . 'chat_read_receipts';

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

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql_reactions);
    dbDelta($sql_read);
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
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_reactions'") != $table_reactions ||
        $wpdb->get_var("SHOW TABLES LIKE '$table_read'") != $table_read) {
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

function chat_get_messages_by_room($room_id, $limit = 50, $offset = 0, $is_admin = false) {
    global $wpdb;
    $table_messages = $wpdb->prefix . 'chat_messages';
    $deleted_condition = $is_admin ? '' : 'AND m.deleted_at IS NULL';
    return $wpdb->get_results($wpdb->prepare(
        "SELECT m.*, u.display_name as user_name
         FROM $table_messages m
         JOIN {$wpdb->users} u ON m.user_id = u.ID
         WHERE m.room_id = %d $deleted_condition
         ORDER BY m.created_at DESC
         LIMIT %d OFFSET %d",
        $room_id, $limit, $offset
    ));
}

function chat_insert_message($room_id, $user_id, $message) {
    global $wpdb;
    return $wpdb->insert(
        $wpdb->prefix . 'chat_messages',
        array(
            'room_id'   => $room_id,
            'user_id'   => $user_id,
            'message'   => $message,
            'created_at'=> current_time('mysql'),
        )
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
    $sql = "SELECT r.id FROM $table_rooms r
            JOIN $table_members m1 ON r.id = m1.room_id AND m1.user_id = %d
            JOIN $table_members m2 ON r.id = m2.room_id AND m2.user_id = %d
            WHERE r.type = 'direct'
            GROUP BY r.id
            HAVING COUNT(DISTINCT m1.user_id) = 2 AND COUNT(DISTINCT m2.user_id) = 2";
    $room_id = $wpdb->get_var($wpdb->prepare($sql, $user1, $user2));
    if ($room_id) return (int)$room_id;
    $room_id = chat_create_room('', 'direct', $user1);
    chat_add_member($room_id, $user1);
    chat_add_member($room_id, $user2);
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