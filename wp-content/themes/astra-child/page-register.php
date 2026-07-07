<?php
/**
 * Template Name: Register
 */
get_header();

$error = '';
$success = false;

// If the form was submitted, process it.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register_submit'])) {
    // Check nonce.
    if (!wp_verify_nonce($_POST['register_nonce'], 'register_action')) {
        $error = __t('register_nonce_error', 'Security check failed.');
    } else {
        $username = sanitize_user($_POST['username']);
        $email = sanitize_email($_POST['email']);
        $password = $_POST['password'];
        $confirm = $_POST['password_confirm'];

        $password_errors = array();

        if (strlen($password) < 8) {
            $password_errors[] = __t('register_password_length', 'Password must be at least 8 characters long.');
        }
        if (!preg_match('/[A-Z]/', $password)) {
            $password_errors[] = __t('register_password_uppercase', 'Password must contain at least one uppercase letter.');
        }
        if (!preg_match('/[a-z]/', $password)) {
            $password_errors[] = __t('register_password_lowercase', 'Password must contain at least one lowercase letter.');
        }
        if (!preg_match('/[0-9]/', $password)) {
            $password_errors[] = __t('register_password_number', 'Password must contain at least one number.');
        }
        if (!preg_match('/[^a-zA-Z0-9]/', $password)) {
            $password_errors[] = __t('register_password_special', 'Password must contain at least one special character (e.g., !@#$%^&*).');
        }

        if (empty($username) || empty($email) || empty($password)) {
            $error = __t('register_empty_fields', 'All fields are required.');
        } elseif (!is_email($email)) {
            $error = __t('register_invalid_email', 'Please enter a valid email address.');
        } elseif (username_exists($username)) {
            $error = __t('register_username_taken', 'Username already taken.');
        } elseif (email_exists($email)) {
            $error = __t('register_email_exists', 'Email already registered.');
        } elseif ($password !== $confirm) {
            $error = __t('register_password_mismatch', 'Passwords do not match.');
        } elseif (!empty($password_errors)) {
            $error = implode('<br>', $password_errors);
        } else {
            $strength = password_strength($password, array($username, $email));
            if ($strength['score'] < 2) {
                $error = __t('register_password_too_weak', 'Password is too weak. Please choose a longer password with mixed characters, or a passphrase.');
            } else {
                $user_id = wp_insert_user(array(
                    'user_login' => $username,
                    'user_email' => $email,
                    'user_pass'  => $password,
                    'role'       => 'subscriber'
                ));

                if (is_wp_error($user_id)) {
                    $error = $user_id->get_error_message();
                } else {
                    send_verification_on_register($user_id);

                    $verify_page = get_page_by_path('email-verify');
                    if ($verify_page) {
                        $redirect_url = add_query_arg('user_id', $user_id, get_permalink($verify_page));
                    } else {
                        $redirect_url = home_url('/');
                    }

                    wp_safe_redirect($redirect_url);
                    exit;
                }
            }
        }
    }
}

?>
<style>
.register-form .password-container { position: relative; }
.register-form .password-error { color: #d63638; font-size: 0.85rem; margin-top: 4px; min-height: 1.2em; }
.register-form .strength-meter { height: 6px; background: #ddd; border-radius: 3px; margin-top: 8px; overflow: hidden; transition: background 0.3s; }
.register-form .strength-meter .strength-bar { height: 100%; width: 0%; border-radius: 3px; transition: width 0.3s, background 0.3s; }
.register-form .strength-label { font-size: 0.8rem; font-weight: 600; margin-top: 4px; min-height: 1.2em; }
.register-form .requirements-list { list-style: none; padding: 0; margin: 6px 0 0 0; font-size: 0.85rem; color: #d63638; }
.register-form .requirements-list li::before { content: "✗ "; }
.register-error { color: #d63638; background: #fce8e8; padding: 10px 15px; border-radius: 4px; border-left: 4px solid #d63638; margin-bottom: 20px; }
.register-success { color: #0a7e3c; background: #e8f5ed; padding: 10px 15px; border-radius: 4px; border-left: 4px solid #0a7e3c; margin-bottom: 20px; }
.dummy-field { position: absolute; left: -9999px; top: -9999px; width: 1px; height: 1px; opacity: 0; pointer-events: none; }
</style>
<div class="register-page">
    <div class="container">
        <div class="register-wrapper">
            <h1><?php _te('register_title', 'Create Account'); ?></h1>

            <?php if ($success) : ?>
                <p class="register-success"><?php _te('register_success', 'Registration successful. You are now logged in.'); ?></p>
            <?php else : ?>
                <?php if ($error) : ?>
                    <p class="register-error"><?php echo $error; ?></p>
                <?php endif; ?>

                <?php if (is_user_logged_in()) : ?>
                    <p><?php _te('register_already_logged', 'You are already logged in.'); ?></p>
                    <a href="<?php echo home_url('/'); ?>" class="btn-primary"><?php _te('back_to_home', 'Back to Home'); ?></a>
                <?php else : ?>
                    <form method="post" class="register-form" id="register-form">
                        <?php wp_nonce_field('register_action', 'register_nonce'); ?>
                        <input type="text" class="dummy-field" aria-hidden="true" />
                        <input type="password" class="dummy-field" aria-hidden="true" />

                        <p>
                            <label for="reg_username"><?php _te('register_username', 'Username'); ?></label>
                            <input type="text" name="username" id="reg_username"
                                   value="<?php echo isset($_POST['username']) ? esc_attr($_POST['username']) : ''; ?>"
                                   autocomplete="new-username" required />
                            <div id="username-message" style="font-size:0.85rem; margin-top:4px; min-height:1.2em;"></div>
                        </p>
                        <p>
                            <label for="reg_email"><?php _te('register_email', 'Email'); ?></label>
                            <input type="email" name="email" id="reg_email"
                                   value="<?php echo isset($_POST['email']) ? esc_attr($_POST['email']) : ''; ?>"
                                   autocomplete="off" required />
                        </p>
                        <p class="password-container">
                            <label for="reg_password"><?php _te('register_password', 'Password'); ?></label>
                            <input type="password" name="password" id="reg_password"
                                   autocomplete="new-password" required />
                            <ul class="requirements-list" id="password-requirements"></ul>
                            <div class="password-error" id="password-error"></div>
                            <div class="strength-meter">
                                <div class="strength-bar" id="strength-bar"></div>
                            </div>
                            <div class="strength-label" id="strength-label"></div>
                        </p>
                        <p>
                            <label for="reg_password_confirm"><?php _te('register_password_confirm', 'Confirm Password'); ?></label>
                            <input type="password" name="password_confirm" id="reg_password_confirm"
                                   autocomplete="new-password" required />
                        </p>
                        <p>
                            <input type="submit" name="register_submit" value="<?php _te('register_submit', 'Register'); ?>" class="btn-primary" id="register-submit" />
                        </p>
                    </form>
                    <p class="login-link"><?php _te('register_already_account', 'Already have an account?'); ?> <a href="<?php echo get_permalink(get_page_by_path('login')); ?>"><?php _te('login_title', 'Log In'); ?></a></p>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php get_footer(); ?>