<?php
/**
 * Module Name: VilloCity Board & Staff Directory
 * Module URI:  https://villocity.org
 * Description: Manages municipal/organizational directories, board members, and staff profiles.
 * Version:     1.4.0
 * Last Updated: September 4, 2026
 * Author:      Brockett Creative Group, Inc.
 * Author URI:  https://brockettcreative.com
 * Copyright:   © 2026 Brockett Creative Group, Inc. All Rights Reserved.
 * Text Domain: villocity-suite
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define Module Path
define( 'VOC_STAFF_DIR', plugin_dir_path( __FILE__ ) );

// ==========================================
// 1. REGISTER CUSTOM POST TYPE
// ==========================================
if ( ! function_exists( 'voc_register_staff_cpt' ) ) {
    add_action( 'init', 'voc_register_staff_cpt' );
    function voc_register_staff_cpt() {
        register_post_type( 'bcg_staff', array(
            'labels' => array(
                'name'          => 'Board & Staff',
                'singular_name' => 'Member',
                'add_new'       => 'Add New Member',
                'add_new_item'  => 'Add New Member',
                'edit_item'     => 'Edit Member'
            ),
            'public'             => true,
            'publicly_queryable' => true,
            'show_ui'            => true,
            'show_in_menu'       => false, 
            'has_archive'        => false,
            'rewrite'            => array( 'slug' => 'directory-profile', 'with_front' => false ),
            'supports'           => array( 'title', 'editor', 'thumbnail' ),
        ) );
    }
}

// ==========================================
// 2. INCLUDE SUB-MODULE COMPONENTS
// ==========================================
// Load Backend Admin & Meta Boxes
if ( file_exists( VOC_STAFF_DIR . 'admin.php' ) ) {
    require_once VOC_STAFF_DIR . 'admin.php';
}

// Load Public Shortcode & Frontend Renderer
if ( file_exists( VOC_STAFF_DIR . 'frontend.php' ) ) {
    require_once VOC_STAFF_DIR . 'frontend.php';
}