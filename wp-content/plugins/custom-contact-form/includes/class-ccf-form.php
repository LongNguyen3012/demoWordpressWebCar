<?php
/**
 * Class CCF_Form
 * Handles the front‑end shortcode and form processing.
 */
class CCF_Form {
    public static function init() {
        add_shortcode( 'custom_contact_form', [ __CLASS__, 'shortcode' ] );
    }

    public static function shortcode() {
        if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['ccf_nonce'] ) ) {
            return self::process_submission();
        }
        return self::render_form();
    }

    private static function process_submission() {
        if ( ! wp_verify_nonce( $_POST['ccf_nonce'], 'ccf_form_action' ) ) {
            error_log( 'CCF: Nonce verification failed.' );
            return self::render_form( 'error', 'Security check failed. Please try again.' );
        }

        $data = CCF_Validator::sanitize_submission();
        $validator = new CCF_Validator( $data );
        if ( ! $validator->validate() ) {
            $errors = $validator->get_errors();
            $message = 'Please correct the following errors: ' . implode( ', ', $errors );
            return self::render_form( 'error', $message );
        }

        $file_url = self::process_file_upload();
        if ( $file_url === false ) {
            return self::render_form( 'error', 'File upload failed. Please check file type and size.' );
        }
        $data['file_url'] = $file_url;

        $id = CCF_DB::insert( $data );
        if ( $id === false ) {
            return self::render_form( 'error', 'Could not save your submission. Please try again.' );
        }

        $email_sent = CCF_Mailer::send( $data );
        if ( ! $email_sent ) {
            error_log( 'CCF: Email failed for submission ID ' . $id );
        }

        return self::render_form( 'success', '✅ Thank you! Your message has been sent successfully.' );
    }

    private static function process_file_upload() {
        if ( empty( $_FILES['ccf_file']['name'] ) ) {
            return ''; 
        }

        $allowed_types = [
            'image/jpeg', 'image/png',
            'application/pdf',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        ];
        $file_type = wp_check_filetype( $_FILES['ccf_file']['name'] );
        if ( ! in_array( $file_type['type'], $allowed_types, true ) ) {
            error_log( 'CCF: File type not allowed: ' . $file_type['type'] );
            return false;
        }

        $upload = wp_handle_upload( $_FILES['ccf_file'], [ 'test_form' => false ] );
        if ( isset( $upload['error'] ) ) {
            error_log( 'CCF: File upload error: ' . $upload['error'] );
            return false;
        }
        return $upload['url'];
    }

    private static function render_form( $status = null, $message = null ) {
        ob_start();
        ?>
        <div class="custom-contact-form">
            <?php if ( $status === 'success' ) : ?>
                <div class="contact-success"><?php echo esc_html( $message ? $message : __t('contact_success') ); ?></div>
            <?php elseif ( $status === 'error' ) : ?>
                <div class="contact-error"><?php echo esc_html( $message ? $message : __t('contact_error') ); ?></div>
            <?php endif; ?>

            <form method="post" enctype="multipart/form-data" action="">
                <?php wp_nonce_field( 'ccf_form_action', 'ccf_nonce' ); ?>
                <div class="form-group">
                    <label for="ccf_name"><?php _te('contact_name'); ?> *</label>
                    <input type="text" id="ccf_name" name="ccf_name" required>
                </div>
                <div class="form-group">
                    <label for="ccf_email"><?php _te('contact_email'); ?> *</label>
                    <input type="email" id="ccf_email" name="ccf_email" required>
                </div>
                <div class="form-group">
                    <label for="ccf_message"><?php _te('contact_message'); ?> *</label>
                    <textarea id="ccf_message" name="ccf_message" rows="5" required></textarea>
                </div>
                <div class="form-group">
                    <label for="ccf_file"><?php _te('contact_file'); ?></label>
                    <input type="file" id="ccf_file" name="ccf_file" accept=".jpg,.jpeg,.png,.docx,.pdf">
                </div>
                <button type="submit" class="btn-submit"><?php _te('btn_send'); ?></button>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }
}