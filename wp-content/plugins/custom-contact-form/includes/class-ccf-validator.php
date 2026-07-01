<?php
/**
 * Class CCF_Validator
 * Handles validation of contact form submissions.
 */
class CCF_Validator {
    private $data;
    private $errors = [];

    public function __construct( $data ) {
        $this->data = $data;
    }

    /**
     * Validate the entire submission.
     *
     * @return bool
     */
    public function validate() {
        $this->validate_name();
        $this->validate_email();
        $this->validate_message();
        return empty( $this->errors );
    }

    private function validate_name() {
        $name = $this->data['name'] ?? '';
        if ( empty( $name ) ) {
            $this->errors['name'] = 'Full name is required.';
        } elseif ( strlen( $name ) > 100 ) {
            $this->errors['name'] = 'Name must not exceed 100 characters.';
        }
    }

    private function validate_email() {
        $email = $this->data['email'] ?? '';
        if ( empty( $email ) ) {
            $this->errors['email'] = 'Email address is required.';
        } elseif ( ! filter_var( $email, FILTER_VALIDATE_EMAIL ) ) {
            $this->errors['email'] = 'Invalid email format.';
        }
    }

    private function validate_message() {
        $message = $this->data['message'] ?? '';
        if ( empty( $message ) ) {
            $this->errors['message'] = 'Message is required.';
        } elseif ( strlen( $message ) < 10 ) {
            $this->errors['message'] = 'Message must be at least 10 characters long.';
        }
    }

    public function get_errors() {
        return $this->errors;
    }
    
    public static function sanitize_submission() {
        return [
            'name'    => sanitize_text_field( $_POST['ccf_name'] ?? '' ),
            'email'   => sanitize_email( $_POST['ccf_email'] ?? '' ),
            'message' => sanitize_textarea_field( $_POST['ccf_message'] ?? '' ),
        ];
    }
}