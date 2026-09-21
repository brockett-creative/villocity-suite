<?php
/**
 * Module Name: VilloCity Digital Archives
 * Module URI:  https://villocity.org
 * Description: Historical Society module for preserving digitized artifacts, photos, documents, and historical collections.
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

// Define Local Module Path
define( 'VOC_HISTORY_DIR', plugin_dir_path( __FILE__ ) );

// ==========================================
// 1. REGISTER CUSTOM POST TYPE
// ==========================================
if ( ! function_exists( 'bcg_register_history_cpt' ) ) {
    add_action( 'init', 'bcg_register_history_cpt', 5 );
    function bcg_register_history_cpt() {
        register_post_type( 'bcg_artifact', array(
            'labels' => array(
                'name'               => 'Digital Archives',
                'singular_name'      => 'Artifact',
                'add_new'            => 'Add New Artifact',
                'add_new_item'       => 'Add New Artifact',
                'edit_item'          => 'Edit Artifact'
            ),
            'public'             => true,
            'publicly_queryable' => true,
            'show_ui'            => true,
            'show_in_menu'       => false, 
            'has_archive'        => false,
            'rewrite'            => array( 'slug' => 'archive-item', 'with_front' => false ),
            'supports'           => array( 'title', 'editor', 'thumbnail' ),
        ) );
    }
}

// ==========================================
// 2. INCLUDE SUB-MODULE COMPONENTS
// ==========================================
// Load Admin Dashboard, Settings & Meta Boxes
if ( is_admin() && file_exists( VOC_HISTORY_DIR . 'admin.php' ) ) {
    require_once VOC_HISTORY_DIR . 'admin.php';
}

// Load Public Archives Gallery, Single Artifact Renderer & Shortcodes
if ( file_exists( VOC_HISTORY_DIR . 'frontend.php' ) ) {
    require_once VOC_HISTORY_DIR . 'frontend.php';
}