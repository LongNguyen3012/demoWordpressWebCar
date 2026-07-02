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

// --- TRANSLATION META BOX ---
function add_translation_meta_boxes() {
    $post_types = ['post', 'car', 'banner', 'team'];
    foreach ($post_types as $post_type) {
        add_meta_box(
            'translation_meta_box',
            'Translations',
            'render_translation_meta_box',
            $post_type,
            'normal',
            'default'
        );
    }
}
add_action('add_meta_boxes', 'add_translation_meta_boxes');

function render_translation_meta_box($post) {
    wp_nonce_field('translation_meta_box', 'translation_meta_box_nonce');
    $lang = Language::get_instance();
    $available = $lang->get_available_languages();
    $default_lang = 'en';
    $other_langs = array_diff($available, [$default_lang]);

    $post_type = $post->post_type;
    ?>
    <div style="background: #f9f9f9; padding: 15px; border: 1px solid #ddd; border-radius: 4px;">
        <p><strong>Translate this <?php echo esc_html($post_type); ?> into other languages.</strong> Leave fields blank to use the default language.</p>

        <?php foreach ($other_langs as $code) : 
            $lang_name = $lang->get_language_name($code);
            $title_key = '_title_' . $code;
            $content_key = '_content_' . $code;
            $excerpt_key = '_excerpt_' . $code;
            $title_val = get_post_meta($post->ID, $title_key, true);
            $content_val = get_post_meta($post->ID, $content_key, true);
            $excerpt_val = get_post_meta($post->ID, $excerpt_key, true);
            // For team position and banner button, we handle separately
            $position_key = '_team_position_' . $code;
            $position_val = get_post_meta($post->ID, $position_key, true);
            $button_text_key = '_banner_button_text_' . $code;
            $button_url_key = '_banner_button_url_' . $code;
            $button_text_val = get_post_meta($post->ID, $button_text_key, true);
            $button_url_val = get_post_meta($post->ID, $button_url_key, true);
            ?>
            <div style="border-top: 2px solid #e0e0e0; padding-top: 15px; margin-top: 15px;">
                <h4 style="margin: 0 0 10px 0;"><?php echo esc_html($lang_name); ?> (<code><?php echo esc_html($code); ?></code>)</h4>
                <p>
                    <label for="title_<?php echo esc_attr($code); ?>"><strong>Title</strong></label><br>
                    <input type="text" id="title_<?php echo esc_attr($code); ?>" name="title_<?php echo esc_attr($code); ?>" value="<?php echo esc_attr($title_val); ?>" style="width:100%; padding:8px;" />
                </p>
                <?php if (in_array($post_type, ['post', 'car', 'banner'])) : ?>
                    <p>
                        <label for="content_<?php echo esc_attr($code); ?>"><strong>Content</strong></label><br>
                        <textarea id="content_<?php echo esc_attr($code); ?>" name="content_<?php echo esc_attr($code); ?>" rows="5" style="width:100%; padding:8px;"><?php echo esc_textarea($content_val); ?></textarea>
                    </p>
                <?php endif; ?>
                <?php if (in_array($post_type, ['post', 'car', 'banner'])) : ?>
                    <p>
                        <label for="excerpt_<?php echo esc_attr($code); ?>"><strong>Excerpt / Subtitle</strong></label><br>
                        <input type="text" id="excerpt_<?php echo esc_attr($code); ?>" name="excerpt_<?php echo esc_attr($code); ?>" value="<?php echo esc_attr($excerpt_val); ?>" style="width:100%; padding:8px;" />
                    </p>
                <?php endif; ?>
                <?php if ($post_type === 'team') : ?>
                    <p>
                        <label for="position_<?php echo esc_attr($code); ?>"><strong>Position</strong></label><br>
                        <input type="text" id="position_<?php echo esc_attr($code); ?>" name="position_<?php echo esc_attr($code); ?>" value="<?php echo esc_attr($position_val); ?>" style="width:100%; padding:8px;" />
                    </p>
                <?php endif; ?>
                <?php if ($post_type === 'banner') : ?>
                    <hr>
                    <p><strong>Banner Button</strong></p>
                    <p>
                        <label for="button_text_<?php echo esc_attr($code); ?>"><strong>Button Text</strong></label><br>
                        <input type="text" id="button_text_<?php echo esc_attr($code); ?>" name="button_text_<?php echo esc_attr($code); ?>" value="<?php echo esc_attr($button_text_val); ?>" style="width:100%; padding:8px;" />
                    </p>
                    <p>
                        <label for="button_url_<?php echo esc_attr($code); ?>"><strong>Button URL</strong></label><br>
                        <input type="url" id="button_url_<?php echo esc_attr($code); ?>" name="button_url_<?php echo esc_attr($code); ?>" value="<?php echo esc_url($button_url_val); ?>" style="width:100%; padding:8px;" />
                    </p>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
    <?php
}

function save_translation_meta_box($post_id) {
    if (!isset($_POST['translation_meta_box_nonce']) || !wp_verify_nonce($_POST['translation_meta_box_nonce'], 'translation_meta_box')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;

    $lang = Language::get_instance();
    $available = $lang->get_available_languages();
    $default_lang = 'en';
    $other_langs = array_diff($available, [$default_lang]);

    foreach ($other_langs as $code) {
        $fields = ['title_' . $code, 'content_' . $code, 'excerpt_' . $code, 'position_' . $code, 'button_text_' . $code, 'button_url_' . $code];
        foreach ($fields as $field) {
            if (isset($_POST[$field])) {
                $meta_key = '_' . $field;
                $value = sanitize_text_field($_POST[$field]);
                update_post_meta($post_id, $meta_key, $value);
            }
        }
    }
}
add_action('save_post', 'save_translation_meta_box');

function language_select_meta_box() {
    add_meta_box(
        'language_select',
        'Post Language',
        'render_language_select_meta_box',
        ['post', 'car', 'banner'],
        'side',
        'default'
    );
}
add_action('add_meta_boxes', 'language_select_meta_box');

function render_language_select_meta_box($post) {
    wp_nonce_field('language_select', 'language_select_nonce');
    $current_lang = get_post_meta($post->ID, '_language', true);
    $lang = Language::get_instance();
    $available = $lang->get_available_languages();
    ?>
    <p>
        <select name="post_language" id="post_language" style="width:100%; padding:8px;">
            <?php foreach ($available as $code) : ?>
                <option value="<?php echo esc_attr($code); ?>" <?php selected($current_lang, $code); ?>>
                    <?php echo esc_html($lang->get_language_name($code)); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </p>
    <?php
}

function save_language_select_meta_box($post_id) {
    if (!isset($_POST['language_select_nonce']) || !wp_verify_nonce($_POST['language_select_nonce'], 'language_select')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;
    if (isset($_POST['post_language'])) {
        update_post_meta($post_id, '_language', sanitize_text_field($_POST['post_language']));
    }
}
add_action('save_post', 'save_language_select_meta_box');

