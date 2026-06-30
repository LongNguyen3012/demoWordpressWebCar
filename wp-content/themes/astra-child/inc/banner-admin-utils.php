<?php
/**
 * Admin Utilities for Banner Post Type
 */

function custom_fields_placeholder_script() {
    $screen = get_current_screen();
    if ($screen->post_type === 'banner') {
        ?>
        <script>
        jQuery(document).ready(function($) {
            $('#newmeta #metakeyselect option[value="newmeta"]').text('Enter meta key');
            $('#postcustom').before('<p><strong>Tip:</strong> Use <code>banner_button_url</code> for the URL, and <code>banner_button_text</code> for the button text.</p>');
        });
        </script>
        <?php
    }
}
add_action('admin_footer', 'custom_fields_placeholder_script');

function banner_admin_notice() {
    global $post;
    if ($post && $post->post_type === 'banner') {
        ?>
        <div class="notice notice-info" style="margin: 20px 0;">
            <p><strong>Important:</strong> Your Banner post <strong>must have a Featured Image</strong> set. The Featured Image will be used as the banner background on the homepage.</p>
        </div>
        <?php
    }
}
add_action('admin_notices', 'banner_admin_notice');


add_action('wp_insert_post', 'validate_banner_featured_image_on_save', 10, 3);
function validate_banner_featured_image_on_save($post_id, $post, $update) {
    if ($post->post_type !== 'banner') {
        return;
    }
    if ($post->post_status !== 'publish') {
        return;
    }
    if ( ! has_post_thumbnail( $post_id ) ) {
        remove_action('wp_insert_post', 'validate_banner_featured_image_on_save', 10, 3);
        wp_update_post(array(
            'ID' => $post_id,
            'post_status' => 'draft'
        ));
        set_transient('banner_publish_featured_error_' . $post_id, true, 60);
    }
}

add_action('admin_notices', function() {
    global $post;
    if ($post && $post->post_type === 'banner') {
        if (get_transient('banner_publish_featured_error_' . $post->ID)) {
            delete_transient('banner_publish_featured_error_' . $post->ID);
            ?>
            <div class="notice notice-error is-dismissible">
                <p><strong>Cannot Publish:</strong> A Banner post must have a <strong>Featured Image</strong> set. Please set a Featured Image in the sidebar and try again.</p>
            </div>
            <?php
        }
    }
});
