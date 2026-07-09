<?php
class CCF_DB {
    private static $table_name;

    public static function init() {
        global $wpdb;
        self::$table_name = $wpdb->prefix . 'contact_submissions';
        self::maybe_upgrade();
    }

    public static function create_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'contact_submissions';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id int(11) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            email varchar(255) NOT NULL,
            message text NOT NULL,
            file_url varchar(500) DEFAULT '',
            status varchar(20) DEFAULT 'unread',
            submitted_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_status (status),
            KEY idx_submitted_at (submitted_at),
            KEY idx_email (email)
        ) $charset_collate;";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );
    }

    private static function maybe_upgrade() {
        global $wpdb;
        $row = $wpdb->get_results( "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = '" . self::$table_name . "' AND column_name = 'status'" );
        if ( empty( $row ) ) {
            $wpdb->query( "ALTER TABLE " . self::$table_name . " ADD COLUMN status varchar(20) DEFAULT 'unread'" );
        }
    }

    public static function insert( $data ) {
        global $wpdb;
        $result = $wpdb->insert(
            self::$table_name,
            [
                'name'     => $data['name'],
                'email'    => $data['email'],
                'message'  => $data['message'],
                'file_url' => $data['file_url'],
                'status'   => 'unread',
            ]
        );
        if ( $result === false ) {
            error_log( 'CCF DB insert failed: ' . $wpdb->last_error );
            return false;
        }
        self::clear_cache();
        return $wpdb->insert_id;
    }

    public static function get_submissions( $page = 1, $per_page = 20, $orderby = 'submitted_at', $order = 'DESC' ) {
        global $wpdb;
        $offset = ( $page - 1 ) * $per_page;

        // Whitelist allowed columns and order direction.
        $allowed_orderby = [ 'id', 'name', 'email', 'submitted_at', 'status' ];
        if ( ! in_array( $orderby, $allowed_orderby, true ) ) {
            $orderby = 'submitted_at';
        }
        $order = strtoupper( $order ) === 'ASC' ? 'ASC' : 'DESC';

        // Escape for SQL safety.
        $orderby = esc_sql( $orderby );
        $order   = esc_sql( $order );

        $cache_key = 'ccf_submissions_page_' . $page . '_' . $per_page . '_' . $orderby . '_' . $order;
        $cached = get_transient( $cache_key );
        if ( false !== $cached ) {
            return $cached;
        }

        // Build ORDER BY separately – do NOT use placeholders for column/order.
        $sql = sprintf(
            "SELECT * FROM %s ORDER BY `%s` %s LIMIT %%d OFFSET %%d",
            self::$table_name,
            $orderby,
            $order
        );

        $prepared = $wpdb->prepare( $sql, $per_page, $offset );
        $items = $wpdb->get_results( $prepared );

        // Log any database error for debugging.
        if ( $wpdb->last_error ) {
            error_log( 'CCF DB query error: ' . $wpdb->last_error . ' SQL: ' . $prepared );
            $items = [];
        }

        $total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM " . self::$table_name );

        $data = [
            'items' => $items,
            'total' => $total,
        ];
        set_transient( $cache_key, $data, 300 );
        return $data;
    }

    public static function get_counts() {
        global $wpdb;
        $total = $wpdb->get_var( "SELECT COUNT(*) FROM " . self::$table_name );
        $unread = $wpdb->get_var( "SELECT COUNT(*) FROM " . self::$table_name . " WHERE status = 'unread'" );
        return [ 'total' => (int) $total, 'unread' => (int) $unread ];
    }

    public static function get_submission( $id ) {
        global $wpdb;
        return $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM " . self::$table_name . " WHERE id = %d", $id )
        );
    }

    public static function update( $id, $data ) {
        global $wpdb;
        $result = $wpdb->update(
            self::$table_name,
            $data,
            [ 'id' => $id ]
        );
        if ( $result !== false ) {
            self::clear_cache();
        }
        return $result;
    }

    public static function delete( $ids ) {
        global $wpdb;
        if ( empty( $ids ) ) {
            return;
        }
        $placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
        $wpdb->query(
            $wpdb->prepare( "DELETE FROM " . self::$table_name . " WHERE id IN ($placeholders)", $ids )
        );
        self::clear_cache();
    }

    public static function bulk_update_status( $ids, $status ) {
        global $wpdb;
        if ( empty( $ids ) ) {
            return;
        }
        $placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
        $wpdb->query(
            $wpdb->prepare( "UPDATE " . self::$table_name . " SET status = %s WHERE id IN ($placeholders)", array_merge( [ $status ], $ids ) )
        );
        self::clear_cache();
    }

    private static function clear_cache() {
        global $wpdb;
        $wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_ccf_submissions_page_%'" );
        $wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_ccf_submissions_page_%'" );
    }
}