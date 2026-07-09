<?php
if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class CCF_List_Table extends WP_List_Table {

    /**
     * Constructor – call parent and set default columns.
     */
    public function __construct() {
        parent::__construct( [
            'singular' => 'submission',
            'plural'   => 'submissions',
            'ajax'     => false,
        ] );
    }

    /**
     * Define columns for the table.
     */
    public function get_columns() {
        // Use plain strings to avoid translation issues.
        return [
            'cb'           => '<input type="checkbox" />',
            'id'           => 'ID',
            'status'       => 'Status',
            'name'         => 'Name',
            'email'        => 'Email',
            'message'      => 'Message',
            'file_url'     => 'File',
            'submitted_at' => 'Submitted',
            'actions'      => 'Actions',
        ];
    }

    /**
     * Define sortable columns.
     */
    public function get_sortable_columns() {
        return [
            'id'           => [ 'id', false ],
            'name'         => [ 'name', false ],
            'email'        => [ 'email', false ],
            'submitted_at' => [ 'submitted_at', true ],
        ];
    }

    /**
     * Bulk actions.
     */
    public function get_bulk_actions() {
        return [
            'delete'        => 'Delete',
            'mark_read'     => 'Mark as Read',
            'mark_unread'   => 'Mark as Unread',
            'mark_reviewed' => 'Mark as Reviewed',
            'mark_completed'=> 'Mark as Completed',
        ];
    }

    /**
     * Prepare the items.
     */
    public function prepare_items() {
        $per_page = $this->get_items_per_page( 'ccf_submissions_per_page', 20 );
        $current_page = $this->get_pagenum();
        $orderby = isset( $_GET['orderby'] ) ? sanitize_sql_orderby( $_GET['orderby'] ) : 'submitted_at';
        $order = isset( $_GET['order'] ) && strtoupper( $_GET['order'] ) === 'ASC' ? 'ASC' : 'DESC';

        $data = CCF_DB::get_submissions( $current_page, $per_page, $orderby, $order );

        $this->items = $data['items'];
        $total_items = $data['total'];

        $this->set_pagination_args( [
            'total_items' => $total_items,
            'per_page'    => $per_page,
            'total_pages' => ceil( $total_items / $per_page ),
        ] );

        // Explicitly set column headers (fixes missing columns issue).
        $this->_column_headers = [
            $this->get_columns(),
            [], // Hidden columns (none)
            $this->get_sortable_columns(),
        ];
    }

    /**
     * Default column output.
     */
    protected function column_default( $item, $column_name ) {
        switch ( $column_name ) {
            case 'id':
            case 'name':
            case 'email':
            case 'submitted_at':
                return esc_html( $item->$column_name );
            case 'status':
                $dot = '<span class="ccf-status-dot ccf-status-dot-' . esc_attr( $item->status ) . '"></span>';
                return $dot . ' ' . ucfirst( esc_html( $item->status ) );
            case 'message':
                return esc_html( wp_trim_words( $item->message, 30, '…' ) );
            case 'file_url':
                if ( empty( $item->file_url ) ) {
                    return '—';
                }
                $ext = strtolower( pathinfo( $item->file_url, PATHINFO_EXTENSION ) );
                if ( in_array( $ext, [ 'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg' ] ) ) {
                    return '<a href="' . esc_url( $item->file_url ) . '" target="_blank" class="ccf-image-preview-link">'
                           . '<img src="' . esc_url( $item->file_url ) . '" style="max-width:60px;max-height:60px;" />'
                           . '</a>';
                } else {
                    return '<a href="' . esc_url( $item->file_url ) . '" target="_blank">Download</a>';
                }
            case 'actions':
                $edit_url = wp_nonce_url(
                    add_query_arg( [ 'action' => 'edit', 'id' => $item->id ] ),
                    'ccf_edit_submission'
                );
                return '<a href="' . esc_url( $edit_url ) . '" class="button button-small">Edit</a>';
            default:
                return print_r( $item, true );
        }
    }

    /**
     * Checkbox column.
     */
    protected function column_cb( $item ) {
        return '<input type="checkbox" name="ids[]" value="' . esc_attr( $item->id ) . '" />';
    }

    /**
     * Override display to add inline styles.
     */
    public function display() {
        echo '<style>
            .ccf-status-dot { display: inline-block; width: 10px; height: 10px; border-radius: 50%; margin-right: 5px; }
            .ccf-status-dot-unread { background: #d63638; }
            .ccf-status-dot-read { background: #46b450; }
            .ccf-status-dot-reviewed { background: #ffb900; }
            .ccf-status-dot-completed { background: #007cba; }
            .ccf-image-preview-link { display: inline-block; border: 1px solid #ddd; border-radius: 4px; padding: 2px; background: #fff; line-height: 0; }
            .ccf-image-preview-link img { max-width: 60px; max-height: 60px; object-fit: cover; border-radius: 2px; display: block; }
        </style>';
        parent::display();
    }
}