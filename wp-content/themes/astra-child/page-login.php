<?php
/**
 * Template Name: Login
 */
get_header();

if (is_user_logged_in()) {
    echo '<div class="login-page"><div class="container"><div class="login-wrapper">';
    echo '<h1>' . __t('login_title', 'Log In') . '</h1>';
    echo '<p>' . __t('login_already_logged_in', 'You are already logged in.') . '</p>';
    echo '<a href="' . wp_logout_url(home_url('/')) . '" class="btn-primary">' . __t('login_logout', 'Log Out') . '</a>';
    echo '</div></div></div>';
    get_footer();
    exit;
}

$login_page_url = get_permalink(get_page_by_path('login'));
$register_page_url = get_permalink(get_page_by_path('register'));
?>
<div class="login-page">
    <div class="container">
        <div class="login-wrapper">
            <h1><?php _te('login_title', 'Log In'); ?></h1>

            <?php
            // Display login errors.
            if (isset($_GET['login']) && $_GET['login'] === 'failed') {
                echo '<p class="login-error">' . __t('login_error', 'Invalid username or password.') . '</p>';
            }
            // Display Google OAuth error if any.
            if (get_transient('google_oauth_error')) {
                echo '<p class="login-error" style="color:#d63638;">' . get_transient('google_oauth_error') . '</p>';
                delete_transient('google_oauth_error');
            }
            ?>

            <?php
            $redirect = isset($_GET['redirect_to']) ? $_GET['redirect_to'] : home_url('/');
            $args = array(
                'redirect' => $redirect,
                'form_id'  => 'custom-login-form',
                'label_username' => __t('login_username', 'Username or Email'),
                'label_password' => __t('login_password', 'Password'),
                'label_remember' => __t('login_remember', 'Remember Me'),
                'label_log_in'   => __t('login_submit', 'Log In'),
                'remember'       => true
            );
            wp_login_form($args);
            ?>

            <p class="lost-password-link">
                <a href="<?php echo wp_lostpassword_url(); ?>">
                    <?php _te('login_lost_password', 'Lost your password?'); ?>
                </a>
            </p>

            <?php if (defined('GOOGLE_CLIENT_ID') && GOOGLE_CLIENT_ID) : ?>
                <div class="login-separator">
                    <span><?php _te('login_or', 'OR'); ?></span>
                </div>

                <?php
                $google_oauth_url = 'https://accounts.google.com/o/oauth2/auth?' . http_build_query(array(
                    'client_id'     => GOOGLE_CLIENT_ID,
                    'redirect_uri'  => home_url('/?google_oauth=1'),
                    'response_type' => 'code',
                    'scope'         => 'email profile',
                    'state'         => wp_create_nonce('google_oauth_state')
                ));
                ?>
                <div class="social-login-buttons">
                    <a href="<?php echo esc_url($google_oauth_url); ?>" class="google-login-btn">
                        <svg width="20" height="20" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M47.532 24.5528C47.532 22.9214 47.3997 21.2811 47.1175 19.6761H24.48V28.9181H37.4434C36.9055 31.8988 35.177 34.5356 32.6461 36.2111V42.2078H40.3801C45.0077 37.8751 47.532 31.7212 47.532 24.5528Z" fill="#4285F4"/>
                            <path d="M24.48 48.0016C30.9529 48.0016 36.4116 45.5504 40.3888 42.2078L32.6549 36.2111C30.5031 37.675 27.7252 38.5039 24.4888 38.5039C18.2275 38.5039 12.9187 34.2798 11.0139 28.6006H3.03296V34.7825C7.10718 42.6868 15.4056 48.0016 24.48 48.0016Z" fill="#34A853"/>
                            <path d="M11.0051 28.6006C10.3349 26.1156 10.3349 23.4922 11.0051 21.0072V14.8253H3.03296C0.901194 19.0653 0.337646 23.9985 1.56087 28.6006H11.0051Z" fill="#FBBC04"/>
                            <path d="M24.48 10.1752C27.0834 10.1347 29.5797 11.1718 31.4214 13.0749L38.3404 6.1558C35.8192 3.79374 32.4681 2.48869 29.0195 2.52654C23.9451 2.52654 15.6076 7.84142 11.0051 14.8253L11.0051 21.0072C12.9187 15.328 18.2275 11.1039 24.48 10.1752Z" fill="#EA4335"/>
                        </svg>
                        <?php _te('login_google', 'Sign in with Google'); ?>
                    </a>
                </div>
            <?php endif; ?>

            <p class="register-link">
                <?php _te('register_no_account', 'Don\'t have an account?'); ?>
                <a href="<?php echo esc_url($register_page_url); ?>" class="btn-primary register-btn">
                    <?php _te('register_title', 'Create Account'); ?>
                </a>
            </p>
        </div>
    </div>
</div>
<?php get_footer(); ?>