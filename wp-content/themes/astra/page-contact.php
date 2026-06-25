<?php
/**
 * Template Name: Contact
 */

get_header();
?>

<div class="contact-page">
    <div class="container">
        <h1><?php echo car_demo_text('contact'); ?></h1>
        <?php echo do_shortcode('[custom_contact_form]'); ?>
    </div>
</div>

<?php
get_footer();
