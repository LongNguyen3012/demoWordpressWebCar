<?php

function add_term_meta_for_language() {
    $taxonomies = array('category', 'car_brand', 'car_fuel');
    foreach ($taxonomies as $tax) {
        add_action($tax . '_add_form_fields', 'add_language_term_meta_fields');
        add_action($tax . '_edit_form_fields', 'edit_language_term_meta_fields');
    }
}
add_action('init', 'add_term_meta_for_language');

function add_language_term_meta_fields($taxonomy) {
    ?>
    <div class="form-field term-language-wrap">
        <label for="term_language">Default Language</label>
        <select name="term_language" id="term_language">
            <?php foreach (Language::get_instance()->get_available_languages() as $code) : ?>
                <option value="<?php echo esc_attr($code); ?>"><?php echo esc_html(Language::get_instance()->get_language_name($code)); ?></option>
            <?php endforeach; ?>
        </select>
        <p class="description">Select the language for this term.</p>
    </div>
    <?php
}

function edit_language_term_meta_fields($term) {
    $lang = get_term_meta($term->term_id, '_language', true);
    ?>
    <tr class="form-field term-language-wrap">
        <th scope="row"><label for="term_language">Default Language</label></th>
        <td>
            <select name="term_language" id="term_language">
                <?php foreach (Language::get_instance()->get_available_languages() as $code) : ?>
                    <option value="<?php echo esc_attr($code); ?>" <?php selected($lang, $code); ?>>
                        <?php echo esc_html(Language::get_instance()->get_language_name($code)); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <p class="description">Select the language for this term.</p>
        </td>
    </tr>
    <?php
}

function save_term_language_meta($term_id) {
    if (isset($_POST['term_language'])) {
        update_term_meta($term_id, '_language', sanitize_text_field($_POST['term_language']));
    }
}
add_action('create_term', 'save_term_language_meta');
add_action('edit_term', 'save_term_language_meta');