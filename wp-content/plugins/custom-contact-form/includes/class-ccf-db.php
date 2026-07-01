<?php
/**
 * Class CCF_DB
 * Handles database operations and caching.
 */
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

    /**
     * @param array $data 
     * @return int|false 
     */
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

    /**
     * @param int $page 
     * @param int $per_page 
     * @return array 
     */
    public static function get_submissions( $page = 1, $per_page = 20 ) {
        global $wpdb;
        $offset = ( $page - 1 ) * $per_page;
        $cache_key = 'ccf_submissions_page_' . $page . '_' . $per_page;

        $results = get_transient( $cache_key );
        if ( false !== $results ) {
            return $results;
        }

        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM " . self::$table_name . " ORDER BY submitted_at DESC LIMIT %d OFFSET %d",
                $per_page,
                $offset
            )
        );
        set_transient( $cache_key, $results, 300 ); // 5 minutes
        return $results;
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

    /**
     * @param array $ids 
     */
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

    /**
     * @param array $ids 
     * @param string $status 
     */
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