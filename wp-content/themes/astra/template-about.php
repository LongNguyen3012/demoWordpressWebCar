<?php
/**
 * Template Name: About Us
 */
get_header();
?>

<div class="about-page">

    <section class="about-content">
        <div class="container">
            <?php if (have_posts()) : while (have_posts()) : the_post(); ?>
                <div class="entry-content">
                    <?php the_content(); ?>
                </div>
            <?php endwhile; endif; ?>
        </div>
    </section>

    <section class="about-team">
        <div class="container">
            <h2>Our Team</h2>
            <div class="team-grid">
                <?php
                $team_query = new WP_Query(array(
                    'post_type'      => 'team',
                    'posts_per_page' => -1,
                    'orderby'        => 'menu_order',
                    'order'          => 'ASC'
                ));
                if ($team_query->have_posts()) :
                    while ($team_query->have_posts()) : $team_query->the_post();
                        $position = get_post_meta(get_the_ID(), '_team_position', true);
                        $thumb_id = get_post_thumbnail_id(get_the_ID());
                        $image    = $thumb_id ? wp_get_attachment_image_url($thumb_id, 'medium') : '';
                        if (!$image) {
                            $image = 'https://via.placeholder.com/150';
                        }
                        ?>
                        <div class="team-member">
                            <img src="<?php echo esc_url($image); ?>" alt="<?php the_title_attribute(); ?>">
                            <h3><?php the_title(); ?></h3>
                            <p><?php echo esc_html($position); ?></p>
                        </div>
                    <?php endwhile;
                    wp_reset_postdata();
                else : ?>
                    <p>No team members yet. Add some via <strong>Team Members</strong> in the admin.</p>
                <?php endif; ?>
            </div>
        </div>
    </section>

</div>

<?php get_footer(); ?>