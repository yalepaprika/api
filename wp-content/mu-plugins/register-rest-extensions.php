<?php
/*
Plugin Name:  Register CFS REST Fields
Description:  Register CFS fields in REST API for all Paprika! custom post types
Version:      1.0.0
Author:       Seth Thompson
Author URI:   https://github.com/s3ththompson
License:      MIT License
*/

function get_relationships($ids, $type, $request) {
    if (empty($ids)) return [];
    $query_result = get_posts(
        array(
            'numberposts' => -1,
            'include' => $ids,
            'post_type'   => $type
        )
    );
    $controller = new WP_REST_Posts_Controller($type);
    $posts = array();
    foreach ( $query_result as $post ) {
        $data    = $controller->prepare_item_for_response( $post, $request );
        $posts[] = $controller->prepare_response_for_collection( $data );
    }
    return array_map(function($post) {
        $post->meta = CFS()->get( false, $post->ID, array( 'format' => 'raw' ) );
        return $post;
    }, $posts);
}

function get_folds_from_volume($volume, $request) {
    $query_result = get_posts(
        array(
            'numberposts' => -1,
            'meta_key' => 'volume',
            'meta_value'   => $volume,
            'post_type' => 'fold'
        )
    );
    $controller = new WP_REST_Posts_Controller('fold');
    $posts = array();
    foreach ( $query_result as $post ) {
        $data    = $controller->prepare_item_for_response( $post, $request );
        $posts[] = $controller->prepare_response_for_collection( $data );
    }
    return array_map(function($post) {
        $post->meta = CFS()->get( false, $post->ID, array( 'format' => 'raw' ) );
        return $post;
    }, $posts);
}

function post_add_meta() {
    register_rest_field(
        'post',
        'meta',
        array(
            'get_callback'    => function( $object, $field_name, $request ) {
                $fields = CFS()->get( false, $object[ 'id' ], array( 'format' => 'raw' ) );
                $fields['contributors'] = (isset($fields['contributors'])) ? get_relationships($fields['contributors'], 'contributor', $request) : [];
                if (isset($fields['fold'])) {
                    $folds = get_relationships($fields['fold'], 'fold', $request);
                    if (count($folds) > 0) {
                        $fields['fold'] = $folds[0];
                    } else {
                        unset($fields['fold']);
                    }
                }
                return $fields;
            },
            'update_callback' => null,
            'schema'          => null,
        )
    );
}

function get_fold_meta($id) {
    $fields = CFS()->get( false, $id, array( 'format' => 'raw' ) );
    $fields['fold_editors'] = (isset($fields['fold_editors'])) ? get_relationships($fields['fold_editors'], 'contributor', $request) : [];
    $fields['coordinating_editors'] = (isset($fields['coordinating_editors'])) ? get_relationships($fields['coordinating_editors'], 'contributor', $request) : [];
    $fields['graphic_designer'] = (isset($fields['graphic_designer'])) ? get_relationships($fields['graphic_designer'], 'contributor', $request) : [];
    $fields['web_editor'] = (isset($fields['web_editor'])) ? get_relationships($fields['web_editor'], 'contributor', $request) : [];
    $fields['publishers'] = (isset($fields['publishers'])) ? get_relationships($fields['publishers'], 'contributor', $request) : [];
    $fields['positions_editor'] = (isset($fields['positions_editor'])) ? get_relationships($fields['positions_editor'], 'contributor', $request) : [];
    $post_ids = CFS()->get_reverse_related($id, array(
        'post_type' => 'post'
    ));
    $posts = get_relationships($post_ids, 'post', $request);
    $fields['posts'] = array_map(function($post) {
        $post->meta['contributors'] = (isset($post->meta['contributors'])) ? get_relationships($post->meta['contributors'], 'contributor', $request) : [];
        return $post;
    }, $posts);
    $fields['volume_folds'] = (isset($fields['volume'])) ? get_folds_from_volume($fields['volume'], $request) : [];
    return $fields;
}

function fold_add_meta() {
    register_rest_field(
        'fold',
        'meta',
        array(
            'get_callback'    => function( $object, $field_name, $request ) {
                return get_fold_meta($object['id']);
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
            'get_callback'    => function( $object, $field_name, $request ) {
                $fields = CFS()->get( false, $object[ 'id' ], array( 'format' => 'raw' ) );
                $related_ids = CFS()->get_reverse_related($object[ 'id' ]);
                $related_posts = get_relationships(
                    $related_ids,
                    array(
                        'post',
                        'fold'
                    ),
                    $request
                );
                $fields['related_posts'] = array_map(function($post) {
                    if ($post->post_type == 'post') {
                        $post->meta['contributors'] = get_relationships($post->meta['contributors'], 'contributor', $request);
                    }
                    return $post;
                }, $related_posts);
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
            'get_callback'    => function( $object, $field_name, $request ) {
                $fields = CFS()->get( false, $object[ 'id' ], array( 'format' => 'raw' ) );
                $fields['coordinating_editors'] = (isset($fields['coordinating_editors'])) ? get_relationships($fields['coordinating_editors'], 'contributor', $request) : [];
    
                $fields['publishers'] = (isset($fields['publishers'])) ? get_relationships($fields['publishers'], 'contributor', $request) : [];
                $fields['web_editor'] = (isset($fields['web_editor'])) ? get_relationships($fields['web_editor'], 'contributor', $request) : [];
                $fields['graphic_design_liason'] = (isset($fields['graphic_design_liason'])) ? get_relationships($fields['graphic_design_liason'], 'contributor', $request) : [];
                $fields['site_design'] = (isset($fields['site_design'])) ? get_relationships($fields['site_design'], 'contributor', $request) : [];
                $fields['site_development'] = (isset($fields['site_development'])) ? get_relationships($fields['site_development'], 'contributor', $request) : [];
                return $fields;
            },
            'update_callback' => null,
            'schema'          => null,
        )
    );
}

function home() {
    $most_recent = get_posts(
        array(
            'post_type' => 'fold',
            'orderby'   => 'meta_value_num',
            'meta_key'  => 'volume'
        ))[0];
    $meta = get_post_meta($most_recent->ID);
    $folds = get_folds_from_volume($meta['volume'], $request);
    return array_map(function($fold) {
        $fold->meta = get_fold_meta($fold->ID);
        return $fold;
    }, $folds);
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
    $content = preg_replace('/ style=("|\')(.*?)("|\')/','',$content);
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
            write_log($meta);
            write_log($post_data);
            CFS()->save( $meta, $post_data );
        }

    }   
}, 10, 3);

add_action("rest_insert_post", function ($post, $request, $creating) {
    
    $metas = $request->get_param("cfs");

    if (is_array($metas)) {

        foreach ($metas as $meta) {
            $post_data = array( 'ID' => $post->ID );
            write_log($meta);
            write_log($post_data);
            CFS()->save( $meta, $post_data );
        }

    }   
}, 10, 3);