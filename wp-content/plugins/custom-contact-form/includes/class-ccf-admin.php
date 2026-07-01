<?php
/**
 * Class CCF_Admin
 * Handles all admin-facing functionality.
 */
class CCF_Admin {
    public static function init() {
        add_action( 'admin_menu', [ __CLASS__, 'register_menu' ] );
        add_action( 'admin_init', [ __CLASS__, 'handle_bulk_actions' ] );
        add_action( 'admin_init', [ __CLASS__, 'handle_single_edit' ] );
        add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue_assets' ] );
    }

    public static function enqueue_assets( $hook ) {
        if ( strpos( $hook, 'ccf-submissions' ) === false ) {
            return;
        }
        wp_enqueue_style(
            'ccf-admin-css',
            CCF_PLUGIN_URL . 'assets/css/admin.css',
            [],
            CCF_VERSION
        );
    }

    public static function register_menu() {
        add_menu_page(
            'Contact Submissions',
            'Contact Submissions',
            'manage_options',
            'ccf-submissions',
            [ __CLASS__, 'render_admin_page' ],
            'dashicons-email',
            30
        );
    }

    /**
     * Render the main admin page (list or edit).
     */
    public static function render_admin_page() {
        // If editing, show edit form.
        if ( isset( $_GET['action'] ) && $_GET['action'] === 'edit' && isset( $_GET['id'] ) ) {
            self::render_edit_form();
            return;
        }
        // Otherwise show list table.
        self::render_list_table();
    }

    /**
     * Render the list table with pagination.
     */
    private static function render_list_table() {
        global $wpdb;
        $current_page = isset( $_GET['paged'] ) ? max( 1, intval( $_GET['paged'] ) ) : 1;
        $per_page = 20;

        $results = CCF_DB::get_submissions( $current_page, $per_page );
        $counts = CCF_DB::get_counts();
        $total_items = $counts['total'];
        $unread_count = $counts['unread'];
        $total_pages = ceil( $total_items / $per_page );

        ?>
        <div class="wrap">
            <h1>
                Contact Submissions
                <?php if ( $unread_count > 0 ) : ?>
                    <span class="ccf-unread-count"><?php echo esc_html( $unread_count ); ?> new</span>
                <?php endif; ?>
            </h1>

            <?php if ( isset( $_GET['updated'] ) ) : ?>
                <div class="notice notice-success is-dismissible"><p>Action completed successfully.</p></div>
            <?php endif; ?>

            <?php if ( empty( $results ) ) : ?>
                <p>No submissions yet.</p>
                <?php return; ?>
            <?php endif; ?>

            <form method="get" action="">
                <input type="hidden" name="page" value="ccf-submissions">
                <div class="tablenav top">
                    <div class="alignleft actions bulkactions">
                        <select name="action" id="bulk-action-selector-top">
                            <option value="-1">Bulk actions</option>
                            <option value="mark_read">Mark as Read</option>
                            <option value="mark_unread">Mark as Unread</option>
                            <option value="mark_reviewed">Mark as Reviewed</option>
                            <option value="mark_completed">Mark as Completed</option>
                            <option value="delete">Delete</option>
                        </select>
                        <input type="submit" id="doaction" class="button action" value="Apply">
                    </div>

                    <?php if ( $total_pages > 1 ) : ?>
                        <div class="tablenav-pages">
                            <?php
                            echo paginate_links( [
                                'base'      => add_query_arg( 'paged', '%#%' ),
                                'format'    => '',
                                'prev_text' => '&laquo;',
                                'next_text' => '&raquo;',
                                'total'     => $total_pages,
                                'current'   => $current_page,
                            ] );
                            ?>
                        </div>
                    <?php endif; ?>
                </div>

                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <td id="cb" class="manage-column column-cb check-column">
                                <input id="cb-select-all-1" type="checkbox">
                            </td>
                            <th>ID</th>
                            <th>Status</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Message</th>
                            <th>File</th>
                            <th>Submitted</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $results as $row ) : ?>
                            <tr class="contact-submission-<?php echo esc_attr( $row->status ); ?>">
                                <th scope="row" class="check-column">
                                    <input type="checkbox" name="ids[]" value="<?php echo esc_attr( $row->id ); ?>">
                                </th>
                                <td><?php echo esc_html( $row->id ); ?></td>
                                <td>
                                    <span class="ccf-status-dot ccf-status-dot-<?php echo esc_attr( $row->status ); ?>"></span>
                                    <?php echo ucfirst( esc_html( $row->status ) ); ?>
                                </td>
                                <td><strong><?php echo esc_html( $row->name ); ?></strong></td>
                                <td><a href="mailto:<?php echo esc_attr( $row->email ); ?>"><?php echo esc_html( $row->email ); ?></a></td>
                                <td>
                                    <?php echo esc_html( wp_trim_words( $row->message, 30, '...' ) ); ?>
                                    <?php if ( strlen( $row->message ) > 200 ) : ?>
                                        <br><small><a href="<?php echo esc_url( wp_nonce_url( add_query_arg( [ 'action' => 'edit', 'id' => $row->id ] ), 'ccf_edit_submission' ) ); ?>">View full message</a></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ( $row->file_url ) :
                                        $ext = strtolower( pathinfo( $row->file_url, PATHINFO_EXTENSION ) );
                                        if ( in_array( $ext, [ 'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg' ], true ) ) : ?>
                                            <a href="<?php echo esc_url( $row->file_url ); ?>" target="_blank" class="ccf-image-preview-link">
                                                <img src="<?php echo esc_url( $row->file_url ); ?>" alt="Image" />
                                            </a>
                                        <?php else : ?>
                                            <a href="<?php echo esc_url( $row->file_url ); ?>" target="_blank">Download</a>
                                        <?php endif; ?>
                                    <?php else : ?>
                                        —
                                    <?php endif; ?>
                                </td>
                                <td><?php echo esc_html( $row->submitted_at ); ?></td>
                                <td>
                                    <a href="<?php echo esc_url( wp_nonce_url( add_query_arg( [ 'action' => 'edit', 'id' => $row->id ] ), 'ccf_edit_submission' ) ); ?>" class="button button-small">Edit</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php wp_nonce_field( 'ccf_bulk_action', '_wpnonce' ); ?>

                <?php if ( $total_pages > 1 ) : ?>
                    <div class="tablenav bottom">
                        <div class="tablenav-pages">
                            <?php
                            echo paginate_links( [
                                'base'      => add_query_arg( 'paged', '%#%' ),
                                'format'    => '',
                                'prev_text' => '&laquo;',
                                'next_text' => '&raquo;',
                                'total'     => $total_pages,
                                'current'   => $current_page,
                            ] );
                            ?>
                        </div>
                    </div>
                <?php endif; ?>
            </form>
        </div>
        <?php
    }

    /**
     * Render the edit form for a single submission.
     */
    private static function render_edit_form() {
        $id = intval( $_GET['id'] );
        $row = CCF_DB::get_submission( $id );
        if ( ! $row ) {
            echo '<div class="wrap"><h1>Error</h1><p>Submission not found.</p></div>';
            return;
        }
        ?>
        <div class="wrap">
            <h1>Edit Submission #<?php echo esc_html( $row->id ); ?></h1>
            <div class="ccf-edit-form">
                <form method="post" action="<?php echo esc_url( add_query_arg( [ 'action' => 'edit', 'id' => $row->id ] ) ); ?>">
                    <?php wp_nonce_field( 'ccf_edit_submission', 'ccf_edit_nonce' ); ?>
                    <table class="form-table">
                        <tr>
                            <th><label for="ccf_name">Full Name</label></th>
                            <td><input type="text" id="ccf_name" name="ccf_name" value="<?php echo esc_attr( $row->name ); ?>" /></td>
                        </tr>
                        <tr>
                            <th><label for="ccf_email">Email Address</label></th>
                            <td><input type="email" id="ccf_email" name="ccf_email" value="<?php echo esc_attr( $row->email ); ?>" /></td>
                        </tr>
                        <tr>
                            <th><label for="ccf_message">Message</label></th>
                            <td><textarea id="ccf_message" name="ccf_message" rows="12"><?php echo esc_textarea( $row->message ); ?></textarea></td>
                        </tr>
                        <tr>
                            <th><label for="ccf_file_url">File</label></th>
                            <td>
                                <?php if ( $row->file_url ) :
                                    $ext = strtolower( pathinfo( $row->file_url, PATHINFO_EXTENSION ) );
                                    if ( in_array( $ext, [ 'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg' ], true ) ) : ?>
                                        <div style="margin-bottom: 10px; background: #f8f8f8; padding: 10px; border: 1px solid #ddd; display: inline-block;">
                                            <img src="<?php echo esc_url( $row->file_url ); ?>" style="max-width: 200px; max-height: 200px; display: block;" />
                                        </div>
                                    <?php endif; ?>
                                <?php endif; ?>
                                <input type="url" id="ccf_file_url" name="ccf_file_url" value="<?php echo esc_attr( $row->file_url ); ?>" style="width: 100%; max-width: 600px;" />
                                <p class="description">Enter a file URL or leave empty to remove.</p>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="ccf_status">Status</label></th>
                            <td>
                                <select id="ccf_status" name="ccf_status">
                                    <option value="unread" <?php selected( $row->status, 'unread' ); ?>>Unread</option>
                                    <option value="read" <?php selected( $row->status, 'read' ); ?>>Read</option>
                                    <option value="reviewed" <?php selected( $row->status, 'reviewed' ); ?>>Reviewed</option>
                                    <option value="completed" <?php selected( $row->status, 'completed' ); ?>>Completed</option>
                                </select>
                            </td>
                        </tr>
                    </table>
                    <input type="submit" name="ccf_edit_submit" value="Update Submission" class="button button-primary" />
                    <a href="<?php echo esc_url( remove_query_arg( [ 'action', 'id', '_wpnonce' ] ) ); ?>" class="button">Cancel</a>
                </form>
            </div>
        </div>
        <?php
    }

    /**
     * Handle bulk actions from the list table.
     */
    public static function handle_bulk_actions() {
        if ( ! isset( $_GET['page'] ) || $_GET['page'] !== 'ccf-submissions' ) {
            return;
        }
        if ( ! isset( $_GET['action'] ) || empty( $_GET['action'] ) ) {
            return;
        }
        if ( ! isset( $_GET['ids'] ) || empty( $_GET['ids'] ) ) {
            return;
        }
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        check_admin_referer( 'ccf_bulk_action', '_wpnonce' );

        $ids = array_map( 'intval', (array) $_GET['ids'] );
        $action = sanitize_text_field( $_GET['action'] );

        switch ( $action ) {
            case 'delete':
                CCF_DB::delete( $ids );
                break;
            case 'mark_read':
                CCF_DB::bulk_update_status( $ids, 'read' );
                break;
            case 'mark_unread':
                CCF_DB::bulk_update_status( $ids, 'unread' );
                break;
            case 'mark_reviewed':
                CCF_DB::bulk_update_status( $ids, 'reviewed' );
                break;
            case 'mark_completed':
                CCF_DB::bulk_update_status( $ids, 'completed' );
                break;
            default:
                // Unknown action.
                break;
        }

        wp_redirect( add_query_arg( 'updated', '1', remove_query_arg( [ 'action', 'ids', '_wpnonce' ] ) ) );
        exit;
    }

    /**
     * Handle single edit form submission.
     */
    public static function handle_single_edit() {
        if ( ! isset( $_POST['ccf_edit_submit'] ) || ! isset( $_POST['ccf_edit_nonce'] ) ) {
            return;
        }
        if ( ! isset( $_GET['page'] ) || $_GET['page'] !== 'ccf-submissions' ) {
            return;
        }
        if ( ! isset( $_GET['id'] ) || ! current_user_can( 'manage_options' ) ) {
            return;
        }
        check_admin_referer( 'ccf_edit_submission', 'ccf_edit_nonce' );

        $id = intval( $_GET['id'] );
        $data = [
            'name'     => sanitize_text_field( $_POST['ccf_name'] ),
            'email'    => sanitize_email( $_POST['ccf_email'] ),
            'message'  => sanitize_textarea_field( $_POST['ccf_message'] ),
            'file_url' => esc_url_raw( $_POST['ccf_file_url'] ),
            'status'   => sanitize_text_field( $_POST['ccf_status'] ),
        ];

        CCF_DB::update( $id, $data );

        wp_redirect( add_query_arg( 'updated', '1', remove_query_arg( [ 'action', 'id', '_wpnonce' ] ) ) );
        exit;
    }
}