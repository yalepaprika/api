<?php
/*
Plugin Name:  Register CFS REST Fields
Description:  Register CFS fields in REST API for all Paprika! custom post types
Version:      1.0.0
Author:       Seth Thompson
Author URI:   https://github.com/s3ththompson
License:      MIT License
*/

function get_folds_from_volume($volume) {
    $posts = get_posts(
        array(
            'numberposts' => -1,
            'meta_key' => 'volume',
            'meta_value'   => $volume,
            'post_type' => 'fold'
        )
    );
    return array_map(function($post) {
        return $post->ID;
    }, $posts);
}

function post_add_meta() {
    register_rest_field(
        'post',
        'meta',
        array(
            'get_callback'    => function( $object, $field_name ) {
                $fields = CFS()->get( false, $object[ 'id' ], array( 'format' => 'raw' ) );
                return $fields;
            },
            'update_callback' => null,
            'schema'          => null,
        )
    );
}

function fold_add_meta() {
    register_rest_field(
        'fold',
        'meta',
        array(
            'get_callback'    => function( $object, $field_name ) {
                $fields = CFS()->get( false, $object['id'], array( 'format' => 'raw' ) );
                $post_ids = CFS()->get_reverse_related($object['id'], array(
                    'post_type' => 'post'
                ));
                $fields['posts'] = $post_ids;
                $fields['volume_folds'] = (isset($fields['volume'])) ? get_folds_from_volume($fields['volume']) : [];
                return $fields;
            },
            'update_callback' => null,
            'schema'          => null,
        )
    );
}

function contributor_add_meta() {
    register_rest_field(
        'contributor',
        'meta',
        array(
            'get_callback'    => function( $object, $field_name ) {
                $fields = CFS()->get( false, $object[ 'id' ], array( 'format' => 'raw' ) );
                $folds_edited = CFS()->get_reverse_related($object[ 'id' ], array(
                    'field_name' => 'fold_editors',
                    'post_type' => 'fold'
                ));
                $folds_designed = CFS()->get_reverse_related($object[ 'id' ], array(
                    'field_name' => 'graphic_designer',
                    'post_type' => 'fold'
                ));
                $posts_contributed = CFS()->get_reverse_related($object[ 'id' ], array(
                    'field_name' => 'contributors',
                    'post_type' => 'post'
                ));
                $fields['folds_edited'] = $folds_edited;
                $fields['folds_designed'] = $folds_designed;
                $fields['posts_contributed'] = $posts_contributed;
                return $fields;
            },
            'update_callback' => null,
            'schema'          => null,
        )
    );
}

function page_add_meta() {
    register_rest_field(
        'page',
        'meta',
        array(
            'get_callback'    => function( $object, $field_name ) {
                $fields = CFS()->get( false, $object[ 'id' ], array( 'format' => 'raw' ) );
                return $fields;
            },
            'update_callback' => null,
            'schema'          => null,
        )
    );
}

function home($object, $field_name) {
    $most_recent = get_posts(
        array(
            'post_type' => 'fold',
            'orderby'   => 'meta_value_num',
            'meta_key'  => 'volume'
        ))[0];
    $meta = get_post_meta($most_recent->ID);
    $folds = get_folds_from_volume($meta['volume']);
    return $folds;
}

function add_home_route() {
    register_rest_route( 'paprika/v1', '/home', array(
        'methods' => 'GET',
        'callback' => 'home',
    ) );
}

add_action( 'rest_api_init', 'post_add_meta' );
add_action( 'rest_api_init', 'fold_add_meta' );
add_action( 'rest_api_init', 'contributor_add_meta' );
add_action( 'rest_api_init', 'page_add_meta' );
add_action( 'rest_api_init', 'add_home_route' );

add_filter('the_content', function( $content ){
    //--Remove all inline styles--
    $content = preg_replace('/(<[^>]*) style=("[^"]+"|\'[^\']+\')([^>]*>)/i', '$1$3', $content);
    return $content;
}, 20);

function fold_meta_request_params( $args, $request ) {
        $args += array(
            'meta_key'   => $request['meta_key'],
            'meta_value' => $request['meta_value'],
            'meta_query' => $request['meta_query'],
        );
        return $args;
}

add_filter( 'rest_fold_query', 'fold_meta_request_params', 99, 2 );

if ( ! function_exists('write_log')) {
   function write_log ( $log )  {
      if ( is_array( $log ) || is_object( $log ) ) {
         error_log( print_r( $log, true ) );
      } else {
         error_log( $log );
      }
   }
}

add_action("rest_insert_contributor", function ($post, $request, $creating) {
    $metas = $request->get_param("cfs");
    if (is_array($metas)) {
        foreach ($metas as $meta) {
            $post_data = array( 'ID' => $post->ID );
            CFS()->save( $meta, $post_data );
        }
    }   
}, 10, 3);

add_action("rest_insert_post", function ($post, $request, $creating) {
    $metas = $request->get_param("cfs");
    if (is_array($metas)) {

        foreach ($metas as $meta) {
            $post_data = array( 'ID' => $post->ID );
            CFS()->save( $meta, $post_data );
        }
    }   
}, 10, 3);