<?php
/*
Plugin Name:  Paprika! Contributor Post Type
Plugin URI:   https://github.com/yalepaprika/api
Description:  Register custom post type for Paprika! Contributors
Version:      1.0.0
Author:       Seth Thompson
Author URI:   https://github.com/s3ththompson
License:      MIT License
*/

function create_contributor_custom_type() {
    $args = array(
        'labels'        => array(
            'name'                  => 'Contributors',
            'singular_name'         => 'Contributor',
            'menu_name'             => 'Contributors',
            'name_admin_bar'        => 'Contributor',
            'add_new'               => 'Add New',
            'add_new_item'          => 'Add New Contributor',
            'new_item'              => 'New Contributor',
            'edit_item'             => 'Edit Contributor',
            'view_item'             => 'View Contributor',
            'all_items'             => 'All Contributors',
            'search_items'          => 'Search Contributors',
            'parent_item_colon'     => 'Parent Contributors',
            'not_found'             => 'No contributors found.',
            'not_found_in_trash'    => 'No contributors found in Trash.'
        ),
        'menu_icon'     => 'dashicons-groups',
        'menu_position' => 7,
        'public'        => true,
        'has_archive'   => true,
        'show_in_rest'  => true,
        'rest_base'     => 'contributors'
    );
    register_post_type( 'contributor', $args );
}

function custom_enter_title( $input ) {
    if ( 'contributor' === get_post_type() ) {
        return 'Enter full name here';
    }
    return $input;
}

add_action( 'init', 'create_contributor_custom_type' );
add_filter( 'enter_title_here', 'custom_enter_title' );
