<?php
/**
 * Module Name: VilloCity Departments Module
 * Module URI:  https://villocity.org
 * Description: Core loader for municipal departments, custom post types, and modular file routing.
 * Version:     1.5.0
 * Author:      Brockett Creative Group, Inc.
 * Text Domain: villocity-suite
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

define( 'VOC_DEPARTMENTS_PATH', plugin_dir_path( __FILE__ ) );

// ==========================================
// 1. REGISTER CPT
// ==========================================
if ( ! function_exists( 'voc_register_departments_cpt' ) ) {
    add_action( 'init', 'voc_register_departments_cpt' );
    function voc_register_departments_cpt() {
        register_post_type( 'voc_department', array(
            'labels' => array(
                'name'               => 'Departments',
                'singular_name'      => 'Department',
                'add_new'            => 'Add New Department',
                'add_new_item'       => 'Add New Department',
                'edit_item'          => 'Edit Department'
            ),
            'public'             => true,
            'publicly_queryable' => true,
            'show_ui'            => true,
            'show_in_menu'       => false, // Nested in VilloCity Hub
            'has_archive'        => false,
            'rewrite'            => array( 'slug' => 'department', 'with_front' => false ),
            'supports'           => array( 'title', 'editor', 'thumbnail' ),
        ) );
    }
}

// ==========================================
// 2. MODULAR INCLUDES
// ==========================================
if ( is_admin() ) {
    require_once VOC_DEPARTMENTS_PATH . 'admin.php';
}

require_once VOC_DEPARTMENTS_PATH . 'frontend.php';