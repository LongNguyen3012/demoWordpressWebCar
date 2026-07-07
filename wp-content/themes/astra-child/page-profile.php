<?php
/**
 * Template Name: Profile
 */
get_header();

if (!is_user_logged_in()) {
    wp_redirect(get_permalink(get_page_by_path('login')));
    exit;
}

$user_id = get_current_user_id();
$user = get_userdata($user_id);
$error = '';
$success = '';
$edit_mode = isset($_GET['edit']) && $_GET['edit'] === '1';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['profile_update'])) {
    if (!wp_verify_nonce($_POST['profile_nonce'], 'profile_update_action')) {
        $error = __t('profile_nonce_error', 'Security check failed.');
    } else {
        $new_username = sanitize_user($_POST['username']);
        $new_email = sanitize_email($_POST['email']);

        if ($new_username !== $user->user_login && username_exists($new_username)) {
            $error .= '<br>' . __t('profile_username_taken', 'Username already taken.');
        }

        if ($new_email !== $user->user_email && email_exists($new_email)) {
            $error .= '<br>' . __t('profile_email_taken', 'Email already registered.');
        }

        if (empty($error)) {
            $update_data = array('ID' => $user_id);
            if ($new_username !== $user->user_login) {
                $update_data['user_login'] = $new_username;
            }
            if ($new_email !== $user->user_email) {
                $update_data['user_email'] = $new_email;
                update_user_meta($user_id, 'email_verified', '0');
                send_verification_on_register($user_id);
                $success = __t('profile_email_changed_verify', 'Email updated. A new verification code has been sent to your new email.');
            }

            if (!empty($update_data) && $update_data !== array('ID' => $user_id)) {
                $result = wp_update_user($update_data);
                if (is_wp_error($result)) {
                    $error = $result->get_error_message();
                } elseif (empty($success)) {
                    $success = __t('profile_updated', 'Profile updated successfully.');
                }
                $user = get_userdata($user_id);
                // After successful update, exit edit mode.
                $edit_mode = false;
            }
        }
    }
}

$verified = get_user_meta($user_id, 'email_verified', true);
$verify_page = get_page_by_path('email-verify');
if ($verify_page) {
    $redirect_to = get_permalink(get_page_by_path('profile'));
    $verify_url = add_query_arg(array(
        'user_id' => $user_id,
        'redirect_to' => $redirect_to
    ), get_permalink($verify_page));
} else {
    $verify_url = '';
}
?>
<style>
.profile-page .profile-wrapper { max-width: 100%; margin: 0 auto; padding: 20px 0; }
@media (min-width: 768px) { .profile-wrapper { max-width: 650px; padding: 40px 0; } }

.profile-wrapper .field-row {
    display: flex;
    align-items: center;
    padding: 12px 0;
    border-bottom: 1px solid #eee;
}
.profile-wrapper .field-label {
    font-weight: 600;
    width: 120px;
    flex-shrink: 0;
}
.profile-wrapper .field-value {
    flex: 1;
    padding: 6px 0;
}
.profile-wrapper .field-value input[type="text"],
.profile-wrapper .field-value input[type="email"] {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    box-sizing: border-box;
}
.profile-wrapper .field-value input:disabled {
    background: transparent;
    border: none;
    padding: 6px 0;
    color: #333;
    cursor: default;
}

.profile-wrapper .btn-primary {
    margin-top: 20px;
    padding: 12px 30px;
    background: #2C2C2C;
    color: #fff;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 14px;
    text-transform: uppercase;
    letter-spacing: 2px;
    transition: background 0.3s;
}
.profile-wrapper .btn-primary:hover { background: #000; }
.profile-wrapper .btn-secondary {
    background: #ccc;
    color: #333;
    margin-left: 10px;
}
.profile-wrapper .btn-secondary:hover { background: #bbb; }
.profile-wrapper .btn-edit {
    background: #0073aa;
    margin-top: 20px;
}
.profile-wrapper .btn-edit:hover { background: #005a87; }

.profile-wrapper .unverified-warning {
    background: #fce8e8;
    border-left: 4px solid #d63638;
    padding: 15px;
    margin-bottom: 20px;
}
.profile-wrapper .unverified-warning p { margin: 0; }

.profile-error { color: #d63638; }
.profile-success { color: #0a7e3c; }

.profile-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    margin-top: 20px;
    align-items: center;
}
.profile-actions .btn-primary { margin-top: 0; }
</style>

<div class="profile-page">
    <div class="container">
        <div class="profile-wrapper">
            <h1><?php _te('profile_title', 'Your Profile'); ?></h1>

            <?php if ($verified !== '1') : ?>
                <div class="unverified-warning">
                    <p style="color:#d63638; margin:0;">
                        <?php _te('profile_unverified_warning', 'Your email address is not verified.'); ?>
                        <?php if ($verify_url) : ?>
                            <br>
                            <a href="<?php echo esc_url($verify_url); ?>">
                                <?php _te('profile_verify_now', 'Verify your email now'); ?>
                            </a>
                        <?php endif; ?>
                    </p>
                </div>
            <?php endif; ?>

            <?php if ($error) : ?>
                <p class="profile-error"><?php echo $error; ?></p>
            <?php endif; ?>
            <?php if ($success) : ?>
                <p class="profile-success"><?php echo $success; ?></p>
            <?php endif; ?>

            <form method="post" id="profile-form">
                <?php wp_nonce_field('profile_update_action', 'profile_nonce'); ?>

                <div class="field-row">
                    <span class="field-label"><?php _te('register_username', 'Username'); ?></span>
                    <span class="field-value">
                        <?php if ($edit_mode) : ?>
                            <input type="text" name="username" id="profile_username"
                                   value="<?php echo esc_attr($user->user_login); ?>" required />
                        <?php else : ?>
                            <strong><?php echo esc_html($user->user_login); ?></strong>
                        <?php endif; ?>
                    </span>
                </div>

                <div class="field-row">
                    <span class="field-label"><?php _te('register_email', 'Email'); ?></span>
                    <span class="field-value">
                        <?php if ($edit_mode) : ?>
                            <input type="email" name="email" id="profile_email"
                                   value="<?php echo esc_attr($user->user_email); ?>" required />
                        <?php else : ?>
                            <strong><?php echo esc_html($user->user_email); ?></strong>
                        <?php endif; ?>
                    </span>
                </div>

                <div class="profile-actions">
                    <?php if ($edit_mode) : ?>
                        <button type="submit" name="profile_update" class="btn-primary">
                            <?php _te('profile_save_changes', 'Save Changes'); ?>
                        </button>
                        <a href="<?php echo get_permalink(get_page_by_path('profile')); ?>" class="btn-primary btn-secondary">
                            <?php _te('profile_cancel', 'Cancel'); ?>
                        </a>
                    <?php else : ?>
                        <a href="<?php echo add_query_arg('edit', '1', get_permalink(get_page_by_path('profile'))); ?>" class="btn-primary btn-edit">
                            <?php _te('profile_edit', 'Edit Profile'); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
</div>
<?php get_footer(); ?>