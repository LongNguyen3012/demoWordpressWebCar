<?php
/**
 * Plugin Name: Custom Contact Form
 * Description: A custom contact form with file upload, email notification, admin view, edit, and status management.
 * Version: 2.4
 * Author: Long Nguyen
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require_once( ABSPATH . 'wp-admin/includes/file.php' );

@ini_set('upload_max_filesize', '10M');
@ini_set('post_max_size', '10M');
@ini_set('max_execution_time', 300);
@ini_set('memory_limit', '128M');

function ccf_enqueue_admin_assets($hook) {
    if ( strpos($hook, 'ccf-submissions') === false ) {
        return;
    }
    wp_enqueue_style(
        'ccf-admin-css',
        plugin_dir_url( __FILE__ ) . 'assets/css/admin.css',
        array(),
        '1.0'
    );
}
add_action( 'admin_enqueue_scripts', 'ccf_enqueue_admin_assets' );

function ccf_create_table() {
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
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );
}
register_activation_hook( __FILE__, 'ccf_create_table' );

function ccf_update_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'contact_submissions';
    $row = $wpdb->get_results( "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = '$table_name' AND column_name = 'status'" );
    if (empty($row)) {
        $wpdb->query("ALTER TABLE $table_name ADD COLUMN status varchar(20) DEFAULT 'unread'");
    }
}
add_action('plugins_loaded', 'ccf_update_table');

function ccf_shortcode() {
    $success = false;
    $error = false;
    
    if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['ccf_nonce'] ) ) {
        error_log('📩 Form submitted. Nonce present.');
        
        if ( wp_verify_nonce( $_POST['ccf_nonce'], 'ccf_form_action' ) ) {
            error_log('✅ Nonce verified.');
            
            $name    = sanitize_text_field( $_POST['ccf_name'] );
            $email   = sanitize_email( $_POST['ccf_email'] );
            $message = sanitize_textarea_field( $_POST['ccf_message'] );
            
            error_log('📝 Name: ' . $name);
            error_log('📝 Email: ' . $email);
            error_log('📝 Message length: ' . strlen($message));
            
            if ( ! empty( $name ) && ! empty( $email ) && ! empty( $message ) ) {
                error_log('Validation passed.');
                
                $file_url = '';
                if ( ! empty( $_FILES['ccf_file']['name'] ) ) {
                    error_log('📎 File uploaded: ' . $_FILES['ccf_file']['name']);
                    $allowed_types = array( 'image/jpeg', 'image/png', 'application/pdf', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document' );
                    $file_type = wp_check_filetype( $_FILES['ccf_file']['name'] );
                    
                    if ( ! in_array( $file_type['type'], $allowed_types ) ) {
                        error_log('File type not allowed: ' . $file_type['type']);
                        $error = true;
                    } else {
                        $upload = wp_handle_upload( $_FILES['ccf_file'], array( 'test_form' => false ) );
                        if ( ! isset( $upload['error'] ) ) {
                            $file_url = $upload['url'];
                            error_log('File uploaded successfully: ' . $file_url);
                        } else {
                            error_log('File upload error: ' . $upload['error']);
                            $error = true;
                        }
                    }
                }
                
                if ( ! $error ) {
                    error_log('No errors, proceeding to save and send email.');
                    
                    global $wpdb;
                    $table_name = $wpdb->prefix . 'contact_submissions';
                    $wpdb->insert(
                        $table_name,
                        array(
                            'name'     => $name,
                            'email'    => $email,
                            'message'  => $message,
                            'file_url' => $file_url,
                            'status'   => 'unread',
                        )
                    );
                    
                    error_log('💾 Database insert done. Insert ID: ' . $wpdb->insert_id);
                    
                    $admin_users = get_users( array( 'role' => 'administrator' ) );
                    $to = array();
                    foreach ( $admin_users as $user ) {
                        $to[] = $user->user_email;
                    }
                    
                    $subject = 'New Contact Form Submission from ' . $name;
                    
                    $headers = array(
                        'From: Contact Form <noreply@' . $_SERVER['HTTP_HOST'] . '>',
                        'Content-Type: text/html; charset=UTF-8',
                        'Reply-To: ' . $email
                    );
                    
                    $body = '<html><body style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; background: #f9f9f9;">';
                    $body .= '<div style="background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">';
                    $body .= '<h2 style="color: #333; margin-top: 0; border-bottom: 2px solid #eee; padding-bottom: 15px;">📬 New Contact Form Submission</h2>';
                    $body .= '<table style="width: 100%; border-collapse: collapse;">';
                    $body .= '<tr><td style="padding: 10px 0; font-weight: bold; width: 100px;">Name:</td><td style="padding: 10px 0;">' . esc_html($name) . '</td></tr>';
                    $body .= '<tr><td style="padding: 10px 0; font-weight: bold;">Email:</td><td style="padding: 10px 0;"><a href="mailto:' . esc_attr($email) . '">' . esc_html($email) . '</a></td></tr>';
                    $body .= '<tr><td style="padding: 10px 0; font-weight: bold; vertical-align: top;">Message:</td><td style="padding: 10px 0;">' . nl2br(esc_html($message)) . '</td></tr>';
                    if ( $file_url ) {
                        $body .= '<tr><td style="padding: 10px 0; font-weight: bold;">File:</td><td style="padding: 10px 0;"><a href="' . esc_url($file_url) . '" target="_blank">📎 Download File</a></td></tr>';
                    }
                    $body .= '<tr><td style="padding: 10px 0; font-weight: bold;">Submitted:</td><td style="padding: 10px 0;">' . date('Y-m-d H:i:s') . '</td></tr>';
                    $body .= '</table>';
                    $body .= '<p style="margin-top: 30px; padding-top: 15px; border-top: 2px solid #eee; font-size: 12px; color: #999;">';
                    $body .= 'This email was sent from your contact form. To manage submissions, go to the <a href="' . admin_url('admin.php?page=ccf-submissions') . '">Contact Submissions</a> page.';
                    $body .= '</p>';
                    $body .= '</div></body></html>';
                    
                    error_log('📧 About to send email to: ' . print_r($to, true));
                    error_log('📧 Subject: ' . $subject);
                    
                    $mail_sent = wp_mail( $to, $subject, $body, $headers );
                    
                    if ( $mail_sent ) {
                        error_log('wp_mail() returned TRUE - email sent successfully!');
                    } else {
                        error_log('wp_mail() returned FALSE - email failed!');
                    }
                    
                    $success = true;
                } else {
                    error_log('File validation error prevented saving.');
                }
            } else {
                error_log('Validation failed: one or more required fields empty.');
                $error = true;
            }
        } else {
            error_log('Nonce verification failed.');
            $error = true;
        }
    }
    
    ob_start();
    ?>
    <div class="custom-contact-form">
        
        <?php if ( $success ) : ?>
            <div class="contact-success">
                ✅ Thank you! Your message has been sent successfully.
            </div>
        <?php endif; ?>
        
        <?php if ( $error ) : ?>
            <div class="contact-error">
                ⚠️ There was an error submitting your form. Please fill in all required fields and try again.
            </div>
        <?php endif; ?>
        
        <form method="post" enctype="multipart/form-data" action="">
            <?php wp_nonce_field( 'ccf_form_action', 'ccf_nonce' ); ?>
            
            <div class="form-group">
                <label for="ccf_name">Full Name *</label>
                <input type="text" id="ccf_name" name="ccf_name" required>
            </div>
            
            <div class="form-group">
                <label for="ccf_email">Email Address *</label>
                <input type="email" id="ccf_email" name="ccf_email" required>
            </div>
            
            <div class="form-group">
                <label for="ccf_message">Message *</label>
                <textarea id="ccf_message" name="ccf_message" rows="5" required></textarea>
            </div>
            
            <div class="form-group">
                <label for="ccf_file">Upload File (JPG, PNG, DOCX, PDF - Max 5MB)</label>
                <input type="file" id="ccf_file" name="ccf_file" accept=".jpg,.jpeg,.png,.docx,.pdf">
            </div>
            
            <button type="submit" class="btn-submit">Send Message</button>
        </form>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode( 'custom_contact_form', 'ccf_shortcode' );

function ccf_admin_menu() {
    add_menu_page(
        'Contact Submissions',
        'Contact Submissions',
        'manage_options',
        'ccf-submissions',
        'ccf_admin_page',
        'dashicons-email',
        30
    );
}
add_action( 'admin_menu', 'ccf_admin_menu' );

function ccf_handle_bulk_actions() {
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
    
    if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'ccf_bulk_action' ) ) {
        wp_die( 'Security check failed.' );
    }
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'contact_submissions';
    $ids = array_map( 'intval', explode( ',', $_GET['ids'] ) );
    $action = sanitize_text_field( $_GET['action'] );
    
    if ( $action === 'delete' ) {
        $wpdb->query( "DELETE FROM $table_name WHERE id IN (" . implode( ',', $ids ) . ")" );
    } elseif ( $action === 'mark_read' ) {
        $wpdb->query( "UPDATE $table_name SET status = 'read' WHERE id IN (" . implode( ',', $ids ) . ")" );
    } elseif ( $action === 'mark_unread' ) {
        $wpdb->query( "UPDATE $table_name SET status = 'unread' WHERE id IN (" . implode( ',', $ids ) . ")" );
    } elseif ( $action === 'mark_reviewed' ) {
        $wpdb->query( "UPDATE $table_name SET status = 'reviewed' WHERE id IN (" . implode( ',', $ids ) . ")" );
    } elseif ( $action === 'mark_completed' ) {
        $wpdb->query( "UPDATE $table_name SET status = 'completed' WHERE id IN (" . implode( ',', $ids ) . ")" );
    }
    
    wp_redirect( add_query_arg( 'updated', '1', remove_query_arg( array( 'action', 'ids', '_wpnonce' ) ) ) );
    exit;
}
add_action( 'admin_init', 'ccf_handle_bulk_actions' );

function ccf_handle_single_edit() {
    if ( ! isset( $_POST['ccf_edit_submit'] ) || ! isset( $_POST['ccf_edit_nonce'] ) ) {
        return;
    }

    if ( ! isset( $_GET['page'] ) || $_GET['page'] !== 'ccf-submissions' ) {
        return;
    }
    if ( ! isset( $_GET['id'] ) || ! current_user_can( 'manage_options' ) ) {
        return;
    }
    if ( ! wp_verify_nonce( $_POST['ccf_edit_nonce'], 'ccf_edit_submission' ) ) {
        wp_die( 'Security check failed.' );
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'contact_submissions';
    $id = intval( $_GET['id'] );

    $name    = sanitize_text_field( $_POST['ccf_name'] );
    $email   = sanitize_email( $_POST['ccf_email'] );
    $message = sanitize_textarea_field( $_POST['ccf_message'] );
    $file_url = esc_url_raw( $_POST['ccf_file_url'] );
    $status  = sanitize_text_field( $_POST['ccf_status'] );

    $wpdb->update(
        $table_name,
        array(
            'name'     => $name,
            'email'    => $email,
            'message'  => $message,
            'file_url' => $file_url,
            'status'   => $status,
        ),
        array( 'id' => $id )
    );

    wp_redirect( add_query_arg( 'updated', '1', remove_query_arg( array( 'action', 'id', '_wpnonce' ) ) ) );
    exit;
}
add_action( 'admin_init', 'ccf_handle_single_edit' );

function ccf_admin_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'contact_submissions';

    if ( isset( $_GET['action'] ) && $_GET['action'] === 'edit' && isset( $_GET['id'] ) ) {
        $id = intval( $_GET['id'] );
        $row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_name WHERE id = %d", $id ) );
        if ( ! $row ) {
            echo '<div class="wrap"><h1>Error</h1><p>Submission not found.</p></div>';
            return;
        }
        ?>
        <div class="wrap">
            <h1>Edit Submission #<?php echo esc_html( $row->id ); ?></h1>
            <div class="ccf-edit-form">
                <form method="post" action="<?php echo esc_url( add_query_arg( array( 'action' => 'edit', 'id' => $row->id ) ) ); ?>">
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
                                    if ( in_array( $ext, array( 'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg' ) ) ) : ?>
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
                    <a href="<?php echo esc_url( remove_query_arg( array( 'action', 'id', '_wpnonce' ) ) ); ?>" class="button">Cancel</a>
                </form>
            </div>
        </div>
        <?php
        return;
    }

    $per_page = 20;
    $current_page = isset( $_GET['paged'] ) ? max( 1, intval( $_GET['paged'] ) ) : 1;
    $offset = ( $current_page - 1 ) * $per_page;
    
    $total_items = $wpdb->get_var( "SELECT COUNT(*) FROM $table_name" );
    $total_pages = ceil( $total_items / $per_page );
    
    $results = $wpdb->get_results( 
        $wpdb->prepare( 
            "SELECT * FROM $table_name ORDER BY submitted_at DESC LIMIT %d OFFSET %d",
            $per_page, 
            $offset 
        ) 
    );
    
    $unread_count = $wpdb->get_var( "SELECT COUNT(*) FROM $table_name WHERE status = 'unread'" );
    ?>
    <div class="wrap">
        <h1>
            Contact Submissions
            <?php if ( $unread_count > 0 ) : ?>
                <span class="ccf-unread-count"><?php echo esc_html( $unread_count ); ?> new</span>
            <?php endif; ?>
        </h1>
        
        <?php if ( isset( $_GET['updated'] ) ) : ?>
            <div class="notice notice-success is-dismissible">
                <p>Action completed successfully.</p>
            </div>
        <?php endif; ?>
        
        <?php if ( empty( $results ) ) : ?>
            <p>No submissions yet.</p>
        <?php else : ?>
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
                            echo paginate_links( array(
                                'base'      => add_query_arg( 'paged', '%#%' ),
                                'format'    => '',
                                'prev_text' => '&laquo;',
                                'next_text' => '&raquo;',
                                'total'     => $total_pages,
                                'current'   => $current_page,
                            ) );
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
                                        <br><small><a href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'action' => 'edit', 'id' => $row->id ), remove_query_arg( array( 'ids', '_wpnonce' ) ) ), 'ccf_edit_submission' ) ); ?>">View full message</a></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ( $row->file_url ) : 
                                        $ext = strtolower( pathinfo( $row->file_url, PATHINFO_EXTENSION ) );
                                        if ( in_array( $ext, array( 'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg' ) ) ) : ?>
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
                                    <a href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'action' => 'edit', 'id' => $row->id ), remove_query_arg( array( 'ids', '_wpnonce' ) ) ), 'ccf_edit_submission' ) ); ?>" class="button button-small">Edit</a>
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
                            echo paginate_links( array(
                                'base'      => add_query_arg( 'paged', '%#%' ),
                                'format'    => '',
                                'prev_text' => '&laquo;',
                                'next_text' => '&raquo;',
                                'total'     => $total_pages,
                                'current'   => $current_page,
                            ) );
                            ?>
                        </div>
                    </div>
                <?php endif; ?>
            </form>
        <?php endif; ?>
    </div>
    <?php
}