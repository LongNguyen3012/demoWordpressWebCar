<?php

function custom_languages_admin_menu() {
    add_options_page(
        'Languages',
        'Languages',
        'manage_options',
        'custom-languages',
        'custom_languages_admin_page'
    );
}
add_action('admin_menu', 'custom_languages_admin_menu');

function custom_languages_admin_page() {
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized');
    }

    if (isset($_POST['add_language']) && check_admin_referer('custom_languages_action')) {
        $code = sanitize_text_field($_POST['lang_code']);
        $name = sanitize_text_field($_POST['lang_name']);
        $languages = get_option('custom_languages', ['en' => 'English']);
        if (!isset($languages[$code]) && !empty($code) && !empty($name)) {
            $languages[$code] = $name;
            update_option('custom_languages', $languages);
            echo '<div class="notice notice-success"><p>Language added.</p></div>';
        }
    }

    if (isset($_GET['remove']) && check_admin_referer('remove_lang')) {
        $code = sanitize_text_field($_GET['remove']);
        $languages = get_option('custom_languages', ['en' => 'English']);
        if ($code !== 'en') { 
            unset($languages[$code]);
            update_option('custom_languages', $languages);
            echo '<div class="notice notice-success"><p>Language removed.</p></div>';
        }
    }

    $languages = get_option('custom_languages', ['en' => 'English']);
    ?>
    <div class="wrap">
        <h1>Languages</h1>
        <form method="post">
            <?php wp_nonce_field('custom_languages_action'); ?>
            <table class="form-table">
                <tr>
                    <th><label for="lang_code">Language Code</label></th>
                    <td><input type="text" name="lang_code" id="lang_code" placeholder="e.g., fr" required /></td>
                </tr>
                <tr>
                    <th><label for="lang_name">Language Name</label></th>
                    <td><input type="text" name="lang_name" id="lang_name" placeholder="e.g., Français" required /></td>
                </tr>
            </table>
            <p class="submit"><input type="submit" name="add_language" class="button-primary" value="Add Language" /></p>
        </form>
        <h2>Existing Languages</h2>
        <table class="wp-list-table widefat fixed striped">
            <thead><tr><th>Code</th><th>Name</th><th>Actions</th></tr></thead>
            <tbody>
                <?php foreach ($languages as $code => $name) : ?>
                    <tr>
                        <td><?php echo esc_html($code); ?></td>
                        <td><?php echo esc_html($name); ?></td>
                        <td>
                            <?php if ($code !== 'en') : ?>
                                <a href="<?php echo wp_nonce_url(add_query_arg('remove', $code), 'remove_lang'); ?>" class="button button-small">Remove</a>
                            <?php else : ?>
                                Default
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <p><em>Note: For static translations (menu, buttons, etc.), you need to create a corresponding PHP file in <code>/languages/</code> (e.g., <code>fr.php</code>).</em></p>
    </div>
    <?php
}