<?php
/**
 * Class CCF_Mailer
 * Builds and sends email notifications.
 */
class CCF_Mailer {
    /**
     * @param array $data 
     * @return bool 
     */
    public static function send( $data ) {
        $admin_emails = self::get_admin_emails();
        if ( empty( $admin_emails ) ) {
            error_log( 'CCF Mailer: No admin emails found.' );
            return false;
        }

        $subject = 'New Contact Form Submission from ' . $data['name'];
        $headers = [
            'From: Contact Form <noreply@' . $_SERVER['HTTP_HOST'] . '>',
            'Content-Type: text/html; charset=UTF-8',
            'Reply-To: ' . $data['email']
        ];
        $body = self::build_email_body( $data );

        error_log( 'CCF Mailer: Sending to ' . implode( ', ', $admin_emails ) );
        return wp_mail( $admin_emails, $subject, $body, $headers );
    }

    private static function get_admin_emails() {
        $users = get_users( [ 'role__in' => [ 'administrator' ] ] );
        return array_map( function( $user ) {
            return $user->user_email;
        }, $users );
    }

    private static function build_email_body( $data ) {
        $name    = esc_html( $data['name'] );
        $email   = esc_html( $data['email'] );
        $message = nl2br( esc_html( $data['message'] ) );
        $file_url = $data['file_url'] ? esc_url( $data['file_url'] ) : '';

        $body = '<html><body style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; background: #f9f9f9;">';
        $body .= '<div style="background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">';
        $body .= '<h2 style="color: #333; margin-top: 0; border-bottom: 2px solid #eee; padding-bottom: 15px;">📬 New Contact Form Submission</h2>';
        $body .= '<table style="width: 100%; border-collapse: collapse;">';
        $body .= '<tr><td style="padding: 10px 0; font-weight: bold; width: 100px;">Name:</td><td style="padding: 10px 0;">' . $name . '</td></tr>';
        $body .= '<tr><td style="padding: 10px 0; font-weight: bold;">Email:</td><td style="padding: 10px 0;"><a href="mailto:' . $data['email'] . '">' . $email . '</a></td></tr>';
        $body .= '<tr><td style="padding: 10px 0; font-weight: bold; vertical-align: top;">Message:</td><td style="padding: 10px 0;">' . $message . '</td></tr>';
        if ( $file_url ) {
            $body .= '<tr><td style="padding: 10px 0; font-weight: bold;">File:</td><td style="padding: 10px 0;"><a href="' . $file_url . '" target="_blank">📎 Download File</a></td></tr>';
        }
        $body .= '<tr><td style="padding: 10px 0; font-weight: bold;">Submitted:</td><td style="padding: 10px 0;">' . date( 'Y-m-d H:i:s' ) . '</td></tr>';
        $body .= '</table>';
        $body .= '<p style="margin-top: 30px; padding-top: 15px; border-top: 2px solid #eee; font-size: 12px; color: #999;">';
        $body .= 'This email was sent from your contact form. To manage submissions, go to the <a href="' . admin_url( 'admin.php?page=ccf-submissions' ) . '">Contact Submissions</a> page.';
        $body .= '</p></div></body></html>';
        return $body;
    }
}