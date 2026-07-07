<?php
/**
 * Template Name: Verify Email
 */
get_header();

$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;

if ($user_id === 0 && is_user_logged_in()) {
    $user_id = get_current_user_id();
}

$redirect_to = isset($_GET['redirect_to']) ? esc_url_raw(urldecode($_GET['redirect_to'])) : '';
$user = get_userdata($user_id);
$resent = isset($_GET['resent']) && $_GET['resent'] === '1';

if (!$user) {
    echo '<div class="container"><p>' . __t('verification_invalid_user', 'Invalid user.') . '</p></div>';
    get_footer();
    exit;
}

$verified = get_user_meta($user_id, 'email_verified', true);
if ($verified === '1') {
    echo '<div class="container"><p>' . __t('verification_already_verified', 'Email already verified.') . '</p>';
    echo '<a href="' . get_permalink(get_page_by_path('login')) . '">' . __t('register_go_to_login', 'Go to Login') . '</a></div>';
    get_footer();
    exit;
}

if ($resent) {
    echo '<p style="color:#0a7e3c;">' . __t('verification_resent', 'A new verification code has been sent to your email.') . '</p>';
}
?>
<style>
.verify-page .verify-wrapper {
    max-width: 500px;
    margin: 40px auto;
    padding: 30px;
    background: #f9f9f9;
    border-radius: 8px;
    text-align: center;
}
.verify-page input[type="text"] {
    display: block;
    width: 200px;
    margin: 20px auto;
    padding: 12px;
    font-size: 1.5rem;
    text-align: center;
    letter-spacing: 8px;
    border: 2px solid #ddd;
    border-radius: 4px;
    text-transform: uppercase;
}
.verify-page .btn-primary {
    display: inline-block;
    background: #2C2C2C;
    color: #fff;
    padding: 12px 35px;
    text-decoration: none;
    text-transform: uppercase;
    letter-spacing: 2px;
    font-size: 14px;
    font-weight: 600;
    border: none;
    cursor: pointer;
    transition: 0.3s ease;
}
.verify-page .btn-primary:hover {
    background: #000;
}
.verify-page .resend-link {
    margin-top: 20px;
    display: block;
}
</style>
<div class="verify-page">
    <div class="container">
        <div class="verify-wrapper">
            <h1><?php _te('verification_title', 'Verify Your Email'); ?></h1>
            <p><?php _te('verification_instruction', 'Enter the 6-character code sent to your email.'); ?></p>

            <form method="post">
                <?php wp_nonce_field('verify_code_action', 'verification_nonce'); ?>
                <input type="hidden" name="user_id" value="<?php echo esc_attr($user_id); ?>" />
                <input type="hidden" name="redirect_to" value="<?php echo esc_attr($redirect_to); ?>" />
                <input type="text" name="verify_code" id="verify_code" maxlength="6" required autofocus />
                <p><input type="submit" class="btn-primary" value="<?php _te('verification_submit', 'Verify'); ?>" /></p>
            </form>

            <p class="resend-link">
                <?php _te('verification_no_code', 'Didn\'t receive a code?'); ?>
                <a href="<?php
                    $resend_args = array('resend_code' => 1, 'user_id' => $user_id);
                    if (!empty($redirect_to)) {
                        $resend_args['redirect_to'] = urlencode($redirect_to);
                    }
                    echo add_query_arg($resend_args, home_url('/'));
                ?>">
                    <?php _te('verification_resend', 'Resend code'); ?>
                </a>
            </p>
        </div>
    </div>
</div>
<?php get_footer(); ?>