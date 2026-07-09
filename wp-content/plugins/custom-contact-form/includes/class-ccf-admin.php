<?php
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

    public static function render_admin_page() {
        require_once CCF_PLUGIN_DIR . 'includes/class-ccf-list-table.php';
        $table = new CCF_List_Table();
        $table->prepare_items();

        ?>
        <div class="wrap">
            <h1>
                <?php _e( 'Contact Submissions', 'astra-child' ); ?>
                <?php
                $counts = CCF_DB::get_counts();
                if ( $counts['unread'] > 0 ) {
                    echo ' <span class="ccf-unread-count">' . esc_html( $counts['unread'] ) . ' ' . __( 'new', 'astra-child' ) . '</span>';
                }
                ?>
            </h1>

            <?php if ( isset( $_GET['updated'] ) ) : ?>
                <div class="notice notice-success is-dismissible"><p><?php _e( 'Action completed successfully.', 'astra-child' ); ?></p></div>
            <?php endif; ?>

            <form method="get">
                <input type="hidden" name="page" value="ccf-submissions" />
                <?php $table->display(); ?>
            </form>
        </div>
        <?php
    }

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
                break;
        }

        wp_redirect( add_query_arg( 'updated', '1', remove_query_arg( [ 'action', 'ids', '_wpnonce' ] ) ) );
        exit;
    }

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