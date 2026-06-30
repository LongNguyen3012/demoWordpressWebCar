<?php
/**
 * Meta boxes and custom fields
 */

// --- TEAM POSITION ---
function team_position_meta_box() {
    add_meta_box(
        'team_position',
        'Position / Role',
        'team_position_meta_box_callback',
        'team',
        'side',
        'default'
    );
}
add_action('add_meta_boxes', 'team_position_meta_box');

function team_position_meta_box_callback($post) {
    $value = get_post_meta($post->ID, '_team_position', true);
    echo '<input type="text" id="team_position_field" name="team_position_field" value="' . esc_attr($value) . '" style="width:100%;" />';
}

function save_team_position_meta($post_id) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (isset($_POST['team_position_field'])) {
        update_post_meta($post_id, '_team_position', sanitize_text_field($_POST['team_position_field']));
    }
}
add_action('save_post', 'save_team_position_meta');

// --- BANNER BUTTON ---
function banner_button_meta_box() {
    add_meta_box(
        'banner_button_meta_box',
        'Banner Button',
        'banner_button_meta_box_html',
        'banner',
        'normal',
        'default'
    );
}
add_action( 'add_meta_boxes', 'banner_button_meta_box' );

function banner_button_meta_box_html( $post ) {
    wp_nonce_field( 'banner_button_save', 'banner_button_nonce' );
    $button_text = get_post_meta( $post->ID, 'banner_button_text', true );
    $button_url = get_post_meta( $post->ID, 'banner_button_url', true );
    ?>
    <p>
        <label for="banner_button_text"><strong>Button Text</strong></label><br>
        <input type="text" id="banner_button_text" name="banner_button_text" value="<?php echo esc_attr( $button_text ); ?>" style="width:100%; padding:8px;" placeholder="e.g. Learn More" />
    </p>
    <p>
        <label for="banner_button_url"><strong>Button URL</strong></label><br>
        <input type="url" id="banner_button_url" name="banner_button_url" value="<?php echo esc_url( $button_url ); ?>" style="width:100%; padding:8px;" placeholder="https://example.com" />
    </p>
    <?php
}

function save_banner_button_meta_box( $post_id ) {
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
    if ( ! isset( $_POST['banner_button_nonce'] ) || ! wp_verify_nonce( $_POST['banner_button_nonce'], 'banner_button_save' ) ) return;
    if ( ! current_user_can( 'edit_post', $post_id ) ) return;

    if ( isset( $_POST['banner_button_text'] ) ) {
        update_post_meta( $post_id, 'banner_button_text', sanitize_text_field( $_POST['banner_button_text'] ) );
    }
    if ( isset( $_POST['banner_button_url'] ) ) {
        update_post_meta( $post_id, 'banner_button_url', esc_url_raw( $_POST['banner_button_url'] ) );
    }
}
add_action( 'save_post_banner', 'save_banner_button_meta_box' );