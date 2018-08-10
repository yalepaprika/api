<?php
/*
Plugin Name:  Paprika! Fold Post Type
Description:  Register custom post type for Paprika! Folds
Version:      1.0.0
Author:       Seth Thompson
Author URI:   https://github.com/s3ththompson
License:      MIT License
*/

function create_fold_custom_type() {
    $args = array(
        'labels'        => array(
            'name'                  => 'Folds',
            'singular_name'         => 'Fold',
            'menu_name'             => 'Folds',
            'name_admin_bar'        => 'Fold',
            'add_new'               => 'Add New',
            'add_new_item'          => 'Add New Fold',
            'new_item'              => 'New Fold',
            'edit_item'             => 'Edit Fold',
            'view_item'             => 'View Fold',
            'all_items'             => 'All Folds',
            'search_items'          => 'Search Folds',
            'parent_item_colon'     => 'Parent Folds',
            'not_found'             => 'No folds found.',
            'not_found_in_trash'    => 'No folds found in Trash.'
        ),
        'menu_icon'     => 'dashicons-book',
        'menu_position' => 5,
        'public'        => true,
        'has_archive'   => true,
        'show_in_rest'  => true,
        'rest_base' => 'folds'
    );
    register_post_type( 'fold', $args );
}

add_action( 'init', 'create_fold_custom_type' );