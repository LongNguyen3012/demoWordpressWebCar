<?php
add_action('wp_enqueue_scripts', 'enqueue_register_script');
function enqueue_register_script() {
    if (!is_page_template('page-register.php')) {
        return;
    }
    wp_enqueue_script(
        'register-script',
        get_stylesheet_directory_uri() . '/assets/js/register.js',
        array(),
        '1.0',
        true
    );

    wp_localize_script('register-script', 'registerL10n', array(
        'weak'       => __t('strength_weak', 'Weak'),
        'fair'       => __t('strength_fair', 'Fair'),
        'good'       => __t('strength_good', 'Good'),
        'strong'     => __t('strength_strong', 'Strong'),
        'very_strong'=> __t('strength_very_strong', 'Very Strong'),
        'length'     => __t('register_password_length', 'at least 8 characters'),
        'uppercase'  => __t('register_password_uppercase', 'uppercase letter'),
        'lowercase'  => __t('register_password_lowercase', 'lowercase letter'),
        'number'     => __t('register_password_number', 'number'),
        'special'    => __t('register_password_special', 'special character'),
        'required'   => __t('register_password_required', 'Password must meet all requirements above.'),
        'too_weak'   => __t('register_password_too_weak', 'Password is too weak. Please choose a longer password with mixed characters, or a passphrase.'),
        'mismatch'   => __t('register_password_mismatch', 'Passwords do not match.'),
        'taken'      => __t('register_username_taken', 'Username already taken.'),
        'available'  => __t('register_username_available', 'Username available.'),
        'ajax_url'   => admin_url('admin-ajax.php'),
        'nonce'      => wp_create_nonce('register_action')
    ));
}