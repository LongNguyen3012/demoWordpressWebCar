<?php

get_header();

if (!is_user_logged_in()) {
    wp_redirect(get_permalink(get_page_by_path('login')));
    exit;
}

$user_id = get_current_user_id();
$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['settings_update'])) {
    if (!wp_verify_nonce($_POST['settings_nonce'], 'settings_update_action')) {
        $error = __t('settings_nonce_error', 'Security check failed.');
    } else {
        $notify_car = isset($_POST['notify_new_car']) ? 'yes' : 'no';
        update_user_meta($user_id, 'notify_new_car', $notify_car);

        $email_car   = isset($_POST['notify_email_car']) ? 'yes' : 'no';
        $email_banner = isset($_POST['notify_email_banner']) ? 'yes' : 'no';
        $email_team  = isset($_POST['notify_email_team']) ? 'yes' : 'no';

        update_user_meta($user_id, 'notify_email_car', $email_car);
        update_user_meta($user_id, 'notify_email_banner', $email_banner);
        update_user_meta($user_id, 'notify_email_team', $email_team);

        $success = __t('settings_saved', 'Settings saved successfully.');
    }
}

$notify_car = get_user_meta($user_id, 'notify_new_car', true);
if ($notify_car === '') {
    $notify_car = 'no';
}

$email_car   = get_user_meta($user_id, 'notify_email_car', true);
$email_banner = get_user_meta($user_id, 'notify_email_banner', true);
$email_team  = get_user_meta($user_id, 'notify_email_team', true);

if ($email_car === '') {
    $email_car = 'no';
}
if ($email_banner === '') {
    $email_banner = 'no';
}
if ($email_team === '') {
    $email_team = 'no';
}
?>
<style>
.settings-page .settings-wrapper { max-width: 650px; margin: 0 auto; padding: 40px 0; }
.settings-page .field-row {
    display: flex;
    align-items: center;
    padding: 12px 0;
    border-bottom: 1px solid #eee;
}
.settings-page .field-label {
    font-weight: 600;
    width: 200px;
    flex-shrink: 0;
}
.settings-page .field-value {
    flex: 1;
    padding: 6px 0;
}
.settings-page .field-value input[type="checkbox"] {
    width: 20px;
    height: 20px;
    margin-right: 10px;
    cursor: pointer;
}
.settings-page .field-value label {
    cursor: pointer;
}
.settings-page .field-value .note {
    font-size: 0.9em;
    color: #666;
    margin-top: 4px;
}
.settings-page .field-value .email-label {
    display: block;
    margin-left: 30px;
    font-size: 0.9em;
    color: #555;
}
.settings-page .field-value .email-label input[type="checkbox"] {
    width: 16px;
    height: 16px;
    margin-right: 8px;
}
.settings-page .btn-primary {
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
.settings-page .btn-primary:hover { background: #000; }
.settings-error { color: #d63638; }
.settings-success { color: #0a7e3c; }
.settings-subsection {
    padding-left: 30px;
    margin-top: 5px;
    border-left: 2px solid #eee;
}
.settings-subsection .field-row {
    padding: 6px 0;
    border-bottom: none;
}
</style>

<div class="settings-page">
    <div class="container">
        <div class="settings-wrapper">
            <h1><?php _te('settings_title', 'Your Settings'); ?></h1>

            <?php if ($error) : ?>
                <p class="settings-error"><?php echo $error; ?></p>
            <?php endif; ?>
            <?php if ($success) : ?>
                <p class="settings-success"><?php echo $success; ?></p>
            <?php endif; ?>

            <form method="post">
                <?php wp_nonce_field('settings_update_action', 'settings_nonce'); ?>

                <h2 style="margin-top:30px;"><?php _te('settings_notifications', 'Notification Preferences'); ?></h2>

                <div class="field-row">
                    <span class="field-label"><?php _te('settings_cars_label', 'New Cars'); ?></span>
                    <span class="field-value">
                        <label>
                            <input type="checkbox" name="notify_new_car" value="yes" <?php checked($notify_car, 'yes'); ?>>
                            <?php _te('settings_cars_desc', 'Receive in-app notifications when a new car is added.'); ?>
                        </label>
                        <div class="settings-subsection">
                            <div class="field-row">
                                <span class="field-value">
                                    <label class="email-label">
                                        <input type="checkbox" name="notify_email_car" value="yes" <?php checked($email_car, 'yes'); ?>>
                                        <?php _te('settings_email_cars_desc', 'Also send me email notifications for new cars.'); ?>
                                    </label>
                                </span>
                            </div>
                        </div>
                    </span>
                </div>

                <?php if (current_user_can('editor') || current_user_can('administrator')) : ?>
                    <div class="field-row" style="opacity:0.85; background:#f9f9f9; padding-left:10px;">
                        <span class="field-label"><?php _te('settings_banners_teams_label', 'Banners'); ?></span>
                        <span class="field-value">
                            <em><?php _te('settings_banners_teams_desc', 'You are automatically notified about new Banners (admin/editor only).'); ?></em>
                            <div class="settings-subsection">
                                <div class="field-row">
                                    <span class="field-value">
                                        <label class="email-label">
                                            <input type="checkbox" name="notify_email_banner" value="yes" <?php checked($email_banner, 'yes'); ?>>
                                            <?php _te('settings_email_banner_desc', 'Also send me email notifications for new banners.'); ?>
                                        </label>
                                    </span>
                                </div>
                            </div>
                        </span>
                    </div>

                    <div class="field-row" style="opacity:0.85; background:#f9f9f9; padding-left:10px;">
                        <span class="field-label"><?php _te('settings_teams_label', 'Team Members'); ?></span>
                        <span class="field-value">
                            <em><?php _te('settings_teams_desc', 'You are automatically notified about new Team Members (admin/editor only).'); ?></em>
                            <div class="settings-subsection">
                                <div class="field-row">
                                    <span class="field-value">
                                        <label class="email-label">
                                            <input type="checkbox" name="notify_email_team" value="yes" <?php checked($email_team, 'yes'); ?>>
                                            <?php _te('settings_email_team_desc', 'Also send me email notifications for new team members.'); ?>
                                        </label>
                                    </span>
                                </div>
                            </div>
                        </span>
                    </div>
                <?php endif; ?>

                <button type="submit" name="settings_update" class="btn-primary">
                    <?php _te('settings_save_button', 'Save Settings'); ?>
                </button>
            </form>

            <?php if (current_user_can('manage_options')) : ?>
                <div style="margin-top:30px; padding:20px; background:#f0f7ff; border-radius:4px; border:1px solid #c5d9ed;">
                    <h3 style="margin:0 0 10px;"><?php _te('settings_admin_title', 'Admin Tools'); ?></h3>
                    <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                        <input type="hidden" name="action" value="email_queue_process">
                        <button type="submit" class="btn-primary" style="padding:8px 20px; font-size:12px; margin-top:0;">
                            <?php _te('settings_process_queue', 'Process Email Queue Now'); ?>
                        </button>
                    </form>
                    <p style="font-size:12px; color:#666; margin:5px 0 0;">
                        <?php _te('settings_queue_note', 'Manually process the email queue for testing purposes.'); ?>
                    </p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php get_footer(); ?>