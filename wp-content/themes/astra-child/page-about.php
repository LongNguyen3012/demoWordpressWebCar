<?php
/**
 * Template Name: About Us
 * Description: Custom layout for the About page.
 */

get_header();
?>

<div class="about-page">
    <h1><?php the_title(); ?></h1>
    <?php the_content(); ?>
</div>

<?php
get_footer();