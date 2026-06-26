<?php
/**
 * Template Name: Contact
 */

get_header();
?>

<div class="contact-page">
    <div class="container">
        <h1>Contact</h1>
        <?php echo do_shortcode('[custom_contact_form]'); ?>
    </div>
</div>

<?php
get_footer();