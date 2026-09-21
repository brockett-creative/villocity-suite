<?php
/**
 * Module Name: VilloCity Business Directory
 * Module URI:  https://villocity.org
 * Description: Categorized local business directory with Super Search (Meta & Taxonomy Deep Search), sticky sidebars, and claimed listing tools.
 * Version:     1.3.2 (Super Search Meta & Taxonomy Query Integration)
 * Last Updated: September 16, 2026
 * Author:      Brockett Creative Group, Inc.
 * Author URI:  https://brockettcreative.com
 * Copyright:   © 2026 Brockett Creative Group, Inc. All Rights Reserved.
 * Text Domain: villocity-suite
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define Local Module Path
define( 'VOC_DIRECTORY_DIR', plugin_dir_path( __FILE__ ) );

// ==========================================
// 1. HELPER FUNCTIONS & CATEGORIES
// ==========================================
if ( ! function_exists( 'bcg_directory_get_categories' ) ) {
    function bcg_directory_get_categories() {
        $terms = get_terms( array( 'taxonomy' => 'bcg_business_category', 'hide_empty' => false ) );
        if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
            $cats = wp_list_pluck( $terms, 'name' );
            return array_map(function($c) { return str_replace(array('&amp;', '&#038;'), '&', wp_specialchars_decode($c, ENT_QUOTES)); }, $cats);
        }
        return array('Dining & Food', 'Retail & Shopping', 'Professional Services', 'Arts & Entertainment');
    }
}

if ( ! function_exists( 'bcg_directory_get_locations' ) ) {
    function bcg_directory_get_locations() {
        $default_locs = 'Village of Clinton, Town of Kirkland, Clark Mills, Franklin Springs, Deansboro';
        $locs_string = get_option('bcg_directory_custom_locations', $default_locs);
        $locs_array = explode(',', $locs_string);
        return array_filter(array_map(function($l) { 
            return str_replace(array('&amp;', '&#038;'), '&', wp_specialchars_decode(trim($l), ENT_QUOTES)); 
        }, $locs_array));
    }
}

// Legacy helper bridge to maintain backwards compatibility
if ( ! function_exists( 'bcg_directory_get_icon' ) ) {
    function bcg_directory_get_icon($name) {
        if ( function_exists('villocity_get_icon') ) {
            return villocity_get_icon($name);
        }
        return '';
    }
}

// ==========================================
// 2. REGISTER CPT & TAXONOMY
// ==========================================
if ( ! function_exists( 'bcg_register_directory_cpt' ) ) {
    add_action( 'init', 'bcg_register_directory_cpt', 5 );
    function bcg_register_directory_cpt() {
        register_post_type( 'bcg_directory', array(
            'labels' => array(
                'name'               => 'Business Directory',
                'singular_name'      => 'Business',
                'add_new'            => 'Add New Business',
                'add_new_item'       => 'Add New Business',
                'edit_item'          => 'Edit Business'
            ),
            'public'              => true,
            'publicly_queryable' => true,
            'show_ui'             => true,
            'show_in_menu'        => false, 
            'has_archive'         => false,
            'rewrite'            => array( 'slug' => 'business', 'with_front' => false ),
            'supports'           => array( 'title', 'editor', 'thumbnail' ),
        ) );

        register_taxonomy( 'bcg_business_category', array( 'bcg_directory' ), array(
            'hierarchical'      => true,
            'labels'            => array(
                'name'              => 'Directory Categories',
                'singular_name'     => 'Category',
                'menu_name'         => 'Categories',
            ),
            'show_ui'           => true,
            'show_admin_column' => true,
            'show_in_rest'      => true,
            'query_var'         => true,
            'rewrite'           => array( 'slug' => 'business-category' ),
        ));

        if ( ! get_option('bcg_directory_tax_migrated') ) {
            $posts = get_posts(array('post_type' => 'bcg_directory', 'numberposts' => -1, 'post_status' => 'any'));
            foreach($posts as $p) {
                $c = get_post_meta($p->ID, '_business_category', true);
                if($c) { wp_set_object_terms($p->ID, $c, 'bcg_business_category'); }
            }
            update_option('bcg_directory_tax_migrated', true);
            flush_rewrite_rules();
        }
    }
}

// ==========================================
// 3. DIRECTORY SUPER SEARCH (META & TAXONOMY EXTENSION)
// ==========================================
if ( ! function_exists( 'bcg_directory_super_search_query' ) ) {
    add_action( 'pre_get_posts', 'bcg_directory_super_search_query', 15 );
    function bcg_directory_super_search_query( $query ) {
        // Only modify Directory queries on frontend search or directory page requests
        if ( is_admin() || ! $query->is_main_query() ) {
            return;
        }

        $post_type = $query->get( 'post_type' );
        if ( $post_type !== 'bcg_directory' && ! ( is_array( $post_type ) && in_array( 'bcg_directory', $post_type, true ) ) ) {
            return;
        }

        $search_term = $query->get( 's' );
        if ( empty( $search_term ) && isset( $_GET['bcg_search'] ) ) {
            $search_term = sanitize_text_field( $_GET['bcg_search'] );
        }

        if ( empty( trim( $search_term ) ) ) {
            return;
        }

        // 1. Fetch Post IDs matching Category Terms
        $term_post_ids = get_posts( array(
            'post_type'      => 'bcg_directory',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'tax_query'      => array(
                array(
                    'taxonomy' => 'bcg_business_category',
                    'field'    => 'name',
                    'terms'    => $search_term,
                    'operator' => 'LIKE',
                ),
            ),
        ) );

        // 2. Fetch Post IDs matching Custom Meta (Products/Services, Taglines, Keywords)
        $meta_post_ids = get_posts( array(
            'post_type'      => 'bcg_directory',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'meta_query'     => array(
                'relation' => 'OR',
                array( 'key' => '_business_services', 'value' => $search_term, 'compare' => 'LIKE' ),
                array( 'key' => '_products_services', 'value' => $search_term, 'compare' => 'LIKE' ),
                array( 'key' => '_services',          'value' => $search_term, 'compare' => 'LIKE' ),
                array( 'key' => '_business_keywords', 'value' => $search_term, 'compare' => 'LIKE' ),
                array( 'key' => '_business_tags',     'value' => $search_term, 'compare' => 'LIKE' ),
                array( 'key' => '_keywords',          'value' => $search_term, 'compare' => 'LIKE' ),
                array( 'key' => '_business_tagline',  'value' => $search_term, 'compare' => 'LIKE' ),
                array( 'key' => '_business_subtitle', 'value' => $search_term, 'compare' => 'LIKE' ),
            ),
        ) );

        // 3. Fetch Post IDs matching standard Title and Post Content
        $title_post_ids = get_posts( array(
            'post_type'      => 'bcg_directory',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'fields'         => 'ids',
            's'              => $search_term,
        ) );

        // Merge all matching IDs into an array
        $all_matched_ids = array_unique( array_merge( $term_post_ids, $meta_post_ids, $title_post_ids ) );

        if ( ! empty( $all_matched_ids ) ) {
            $query->set( 's', '' ); // Clear standard search string restriction
            $query->set( 'post__in', $all_matched_ids ); // Restrict search results to the merged ID array
        } else {
            // Force zero results if no meta, term, or title matched
            $query->set( 'post__in', array( 0 ) );
        }
    }
}

// ==========================================
// 4. INCLUDE SUB-MODULE COMPONENTS
// ==========================================
// Admin component
if ( is_admin() && file_exists( VOC_DIRECTORY_DIR . 'admin.php' ) ) {
    require_once VOC_DIRECTORY_DIR . 'admin.php';
}

// Frontend component (Always load so templates and shortcodes execute on public pages)
if ( file_exists( VOC_DIRECTORY_DIR . 'frontend.php' ) ) {
    require_once VOC_DIRECTORY_DIR . 'frontend.php';
}

// ==========================================
// 5. AUTO-CREATE DIRECTORY PAGE
// ==========================================
if ( ! function_exists( 'voc_directory_auto_create_page' ) ) {
    add_action( 'init', 'voc_directory_auto_create_page' );
    function voc_directory_auto_create_page() {
        // Only run for administrators to prevent unnecessary DB checks on every page load
        if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $page_title = 'Business Directory';
        $page_slug  = 'business-directory';

        // Check if page already exists by slug
        $page_check = get_page_by_path( $page_slug );

        if ( ! $page_check ) {
            $page_id = wp_insert_post( array(
                'post_title'     => $page_title,
                'post_name'      => $page_slug,
                'post_content'   => '', // Left empty; content is injected dynamically
                'post_status'    => 'publish',
                'post_type'      => 'page',
                'comment_status' => 'closed',
            ) );

            if ( $page_id && ! is_wp_error( $page_id ) ) {
                update_option( 'voc_directory_page_id', $page_id );
            }
        } else {
            update_option( 'voc_directory_page_id', $page_check->ID );
        }
    }
}