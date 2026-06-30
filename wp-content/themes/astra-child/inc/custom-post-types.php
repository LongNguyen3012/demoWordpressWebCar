<?php
/**
 * Custom Post Types
 */
function register_car_post_type() {
    $args = array(
        'public' => true,
        'has_archive' => true,
        'rewrite' => array('slug' => 'cars'), 
        'supports' => array(
            'title',
            'editor',      
            'excerpt',     
            'thumbnail',   
        ),
        'labels' => array(
            'name' => 'Cars',
            'singular_name' => 'Car',
            'add_new' => 'Add New Car',
            'add_new_item' => 'Add New Car',
            'edit_item' => 'Edit Car',
            'view_item' => 'View Car',
            'search_items' => 'Search Cars',
            'not_found' => 'No cars found',
            'not_found_in_trash' => 'No cars found in Trash',
        ),
        'menu_icon' => 'dashicons-car', 
    );
    register_post_type('car', $args);
}
add_action('init', 'register_car_post_type');

function register_team_post_type() {
    register_post_type('team', array(
        'labels' => array(
            'name'               => 'Team Members',
            'singular_name'      => 'Team Member',
            'add_new'            => 'Add New',
            'add_new_item'       => 'Add New Team Member',
            'edit_item'          => 'Edit Team Member',
            'new_item'           => 'New Team Member',
            'view_item'          => 'View Team Member',
            'search_items'       => 'Search Team Members',
            'not_found'          => 'No team members found',
            'not_found_in_trash' => 'No team members found in Trash'
        ), 
        'public'             => true,
        'publicly_queryable' => false,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'query_var'          => false,
        'rewrite'            => false,
        'capability_type'    => 'post',
        'has_archive'        => false,
        'hierarchical'       => false,
        'menu_position'      => 20,
        'menu_icon'          => 'dashicons-groups',
        'supports'           => array('title', 'thumbnail')
    ));
}
add_action('init', 'register_team_post_type');

function register_banner_post_type() {
    $args = array(
        'public' => true,
        'has_archive' => true,
        'rewrite' => array('slug' => 'banners'),
        'publicly_queryable' => true,
        'supports' => array('title', 'excerpt', 'thumbnail', 'editor', 'custom-fields', 'page-attributes'),
        'labels' => array(
            'name' => 'Banners',
            'singular_name' => 'Banner',
            'add_new' => 'Add New Banner',
            'add_new_item' => 'Add New Banner',
            'edit_item' => 'Edit Banner',
            'view_item' => 'View Banner',
            'search_items' => 'Search Banners',
            'not_found' => 'No banners found',
            'not_found_in_trash' => 'No banners found in Trash',
        ),
        'menu_icon' => 'dashicons-images-alt2',
        'show_in_menu' => true,
        'exclude_from_search' => true,
    );
    register_post_type('banner', $args);
}
add_action('init', 'register_banner_post_type');
