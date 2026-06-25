<?php
/**
 * Plugin Name: Custom Contact Form
 * Description: A custom contact form with file upload, email notification, and admin view.
 * Version: 1.0
 * Author: Your Name
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Load WordPress file upload functions
require_once( ABSPATH . 'wp-admin/includes/file.php' );

// Increase PHP limits
@ini_set('upload_max_filesize', '10M');
@ini_set('post_max_size', '10M');
@ini_set('max_execution_time', 300);
@ini_set('memory_limit', '128M');

// ==================================================
// CREATE TABLE ON ACTIVATION
// ==================================================
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
        submitted_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );
}
register_activation_hook( __FILE__, 'ccf_create_table' );

// ==================================================
// SHORTCODE WITH SAME-PAGE PROCESSING
// ==================================================
function ccf_shortcode() {
    $success = false;
    $error = false;
    
    if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['ccf_nonce'] ) ) {
        if ( wp_verify_nonce( $_POST['ccf_nonce'], 'ccf_form_action' ) ) {
            $name    = sanitize_text_field( $_POST['ccf_name'] );
            $email   = sanitize_email( $_POST['ccf_email'] );
            $message = sanitize_textarea_field( $_POST['ccf_message'] );
            
            if ( ! empty( $name ) && ! empty( $email ) && ! empty( $message ) ) {
                $file_url = '';
                if ( ! empty( $_FILES['ccf_file']['name'] ) ) {
                    $allowed_types = array( 'image/jpeg', 'image/png', 'application/pdf', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document' );
                    $file_type = wp_check_filetype( $_FILES['ccf_file']['name'] );
                    
                    if ( ! in_array( $file_type['type'], $allowed_types ) ) {
                        $error = true;
                    } else {
                        $upload = wp_handle_upload( $_FILES['ccf_file'], array( 'test_form' => false ) );
                        if ( ! isset( $upload['error'] ) ) {
                            $file_url = $upload['url'];
                        } else {
                            $error = true;
                        }
                    }
                }
                
                if ( ! $error ) {
                    global $wpdb;
                    $table_name = $wpdb->prefix . 'contact_submissions';
                    $wpdb->insert(
                        $table_name,
                        array(
                            'name'     => $name,
                            'email'    => $email,
                            'message'  => $message,
                            'file_url' => $file_url,
                        )
                    );
                    
                    $to      = get_option( 'admin_email' );
                    $subject = 'New Contact Form Submission from ' . $name;
                    $body    = "You have a new message.\n\n";
                    $body   .= "Name: $name\n";
                    $body   .= "Email: $email\n\n";
                    $body   .= "Message:\n$message\n\n";
                    if ( $file_url ) {
                        $body .= "File: $file_url\n";
                    }
                    wp_mail( $to, $subject, $body, array( 'Reply-To: ' . $email ) );
                    
                    $success = true;
                }
            } else {
                $error = true;
            }
        } else {
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

// ==================================================
// ADMIN MENU
// ==================================================
function ccf_admin_menu() {
    add_submenu_page(
        'tools.php',
        'Contact Submissions',
        'Contact Submissions',
        'manage_options',
        'ccf-submissions',
        'ccf_admin_page'
    );
}
add_action( 'admin_menu', 'ccf_admin_menu' );

function ccf_admin_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'contact_submissions';
    $results    = $wpdb->get_results( "SELECT * FROM $table_name ORDER BY submitted_at DESC" );
    ?>
    <div class="wrap">
        <h1>Contact Submissions</h1>
        <?php if ( empty( $results ) ) : ?>
            <p>No submissions yet.</p>
        <?php else : ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Message</th>
                        <th>File</th>
                        <th>Submitted</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $results as $row ) : ?>
                        <tr>
                            <td><?php echo esc_html( $row->id ); ?></td>
                            <td><?php echo esc_html( $row->name ); ?></td>
                            <td><a href="mailto:<?php echo esc_attr( $row->email ); ?>"><?php echo esc_html( $row->email ); ?></a></td>
                            <td><?php echo esc_html( wp_trim_words( $row->message, 20 ) ); ?></td>
                            <td>
                                <?php if ( $row->file_url ) : ?>
                                    <a href="<?php echo esc_url( $row->file_url ); ?>" target="_blank">Download</a>
                                <?php else : ?>
                                    —
                                <?php endif; ?>
                            </td>
                            <td><?php echo esc_html( $row->submitted_at ); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    <?php
}