<?php
add_action('wp_enqueue_scripts', 'game_enqueue_scripts');
function game_enqueue_scripts() {
    if (!is_page_template('page-game.php')) {
        return;
    }

    wp_enqueue_style('game-style', get_stylesheet_directory_uri() . '/assets/css/game.css', array(), '1.0');

    wp_enqueue_script('game-script', get_stylesheet_directory_uri() . '/assets/js/game.js', array(), '1.0', true);

    wp_localize_script('game-script', 'gameL10n', array(
        'score'       => __t('game_score', 'Score'),
        'gameOver'    => __t('game_over', 'Game Over'),
        'finalScore'  => __t('game_final_score', 'Final Score'),
        'restart'     => __t('game_restart', 'Restart'),
        'nonce'       => wp_create_nonce('game_highscore'),
        'ajaxUrl'     => admin_url('admin-ajax.php'),
        'isLoggedIn'  => is_user_logged_in(),
    ));
}

function get_game_page_url() {
    $pages = get_pages(array(
        'meta_key'   => '_wp_page_template',
        'meta_value' => 'page-game.php',
        'number'     => 1,
    ));
    if (!empty($pages)) {
        return get_permalink($pages[0]->ID);
    }
    $slug_page = get_page_by_path('driving-game');
    if ($slug_page) {
        return get_permalink($slug_page);
    }
    $slug_page = get_page_by_path('game');
    if ($slug_page) {
        return get_permalink($slug_page);
    }
    return '#';
}