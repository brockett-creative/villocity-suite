<?php
/**
 * Module Name: VilloCity Land & Buildings (Available Properties)
 * Module URI:  https://villocity.org
 * Description: Real Estate and Available Property directory for regional economic development and site selection.
 * Version:     2.1.0
 * Last Updated: September 5, 2026
 * Author:      Brockett Creative Group, Inc.
 * Author URI:  https://brockettcreative.com
 * Copyright:   © 2026 Brockett Creative Group, Inc. All Rights Reserved.
 * Text Domain: villocity-suite
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define Local Module Directory Path
define( 'VOC_PROPERTIES_DIR', plugin_dir_path( __FILE__ ) );

// ==========================================
// 1. REGISTER CPT & TAXONOMY
// ==========================================
if ( ! function_exists( 'voc_register_properties_cpt' ) ) {
    add_action( 'init', 'voc_register_properties_cpt', 5 );
    function voc_register_properties_cpt() {
        register_post_type( 'voc_property', array(
            'labels' => array(
                'name'               => 'Available Properties',
                'singular_name'      => 'Property',
                'add_new'            => 'Add New Property',
                'add_new_item'       => 'Add New Property',
                'edit_item'          => 'Edit Property'
            ),
            'public'             => true,
            'publicly_queryable' => true,
            'show_ui'            => true,
            'show_in_menu'       => false, // Nested in VilloCity Hub
            'has_archive'        => false,
            'rewrite'            => array( 'slug' => 'available-property', 'with_front' => false ),
            'supports'           => array( 'title', 'editor', 'thumbnail' ),
        ) );

        register_taxonomy( 'voc_property_category', array( 'voc_property' ), array(
            'hierarchical'      => true,
            'labels'            => array(
                'name'          => 'Property Categories',
                'singular_name' => 'Category',
                'menu_name'      => 'Categories',
            ),
            'show_ui'           => true,
            'show_admin_column' => true,
            'show_in_rest'      => true,
            'query_var'         => true,
            'rewrite'            => array( 'slug' => 'property-category' ),
        ));
    }
}

// Default Categories Helper
if ( ! function_exists( 'voc_get_property_categories' ) ) {
    function voc_get_property_categories() {
        $terms = get_terms( array( 'taxonomy' => 'voc_property_category', 'hide_empty' => false ) );
        if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
            return wp_list_pluck( $terms, 'name' );
        }
        return array('Shovel-Ready Site', 'Industrial Building', 'Commercial Space', 'Flex / Office Space', 'Land / Acreage');
    }
}

// ==========================================
// 2. INCLUDE SUB-MODULE COMPONENTS
// ==========================================
// Load Admin Dashboard, Submenus & Meta Boxes
if ( is_admin() && file_exists( VOC_PROPERTIES_DIR . 'admin.php' ) ) {
    require_once VOC_PROPERTIES_DIR . 'admin.php';
}

// Load Public Property Grid & Shortcodes
if ( file_exists( VOC_PROPERTIES_DIR . 'frontend.php' ) ) {
    require_once VOC_PROPERTIES_DIR . 'frontend.php';
}