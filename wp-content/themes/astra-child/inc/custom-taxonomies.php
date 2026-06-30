<?php
/**
 * Custom taxonomies
 */
function register_car_taxonomies() {
    register_taxonomy('car_brand', 'car', array(
        'labels' => array(
            'name'              => 'Brands',
            'singular_name'     => 'Brand',
            'search_items'      => 'Search Brands',
            'all_items'         => 'All Brands',
            'parent_item'       => 'Parent Brand',
            'parent_item_colon' => 'Parent Brand:',
            'edit_item'         => 'Edit Brand',
            'update_item'       => 'Update Brand',
            'add_new_item'      => 'Add New Brand',
            'new_item_name'     => 'New Brand Name',
            'menu_name'         => 'Brands',
        ),
        'public'            => true,
        'hierarchical'      => true,
        'show_admin_column' => true,
        'query_var'         => true,
        'rewrite'           => array('slug' => 'brand'),
    ));

    register_taxonomy('car_fuel', 'car', array(
        'labels' => array(
            'name'              => 'Fuel Types',
            'singular_name'     => 'Fuel Type',
            'search_items'      => 'Search Fuel Types',
            'all_items'         => 'All Fuel Types',
            'edit_item'         => 'Edit Fuel Type',
            'update_item'       => 'Update Fuel Type',
            'add_new_item'      => 'Add New Fuel Type',
            'new_item_name'     => 'New Fuel Type Name',
            'menu_name'         => 'Fuel Types',
        ),
        'public'            => true,
        'hierarchical'      => true,
        'show_admin_column' => true,
        'query_var'         => true,
        'rewrite'           => array('slug' => 'fuel'),
    ));
}
add_action('init', 'register_car_taxonomies');