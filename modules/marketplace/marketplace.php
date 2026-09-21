<?php
/**
 * Module Name: VilloCity Live Marketplace
 * Module URI:  https://villocity.org
 * Description: Community board for Yard Sales, Farm Stands, and local exchanges with Google Maps and secure edit links.
 * Version:     2.3.0
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
define( 'VOC_MARKETPLACE_DIR', plugin_dir_path( __FILE__ ) );

// ==========================================
// 1. HELPER FUNCTIONS
// ==========================================
if ( ! function_exists( 'bcg_listings_get_categories' ) ) {
    function bcg_listings_get_categories() {
        $default_cats = 'General Household, Produce & Eggs, Baked Goods, Crafts & Hobbies, Plants & Garden, Furniture, Tools & Equipment';
        $cats_string = get_option('bcg_listings_custom_categories', $default_cats);
        $cats_array = explode(',', $cats_string);
        return array_filter(array_map('trim', $cats_array));
    }
}

// ==========================================
// 2. REGISTER CUSTOM POST TYPE
// ==========================================
if ( ! function_exists( 'bcg_register_listing_cpt' ) ) {
    add_action( 'init', 'bcg_register_listing_cpt', 5 );
    function bcg_register_listing_cpt() {
        register_post_type( 'bcg_listing', array(
            'labels' => array(
                'name'               => 'Live Marketplace',
                'singular_name'      => 'Listing',
                'add_new'            => 'Add New Listing',
                'add_new_item'       => 'Add New Listing',
                'edit_item'          => 'Edit Listing'
            ),
            'public'             => true,
            'publicly_queryable' => true,
            'show_ui'            => true,
            'show_in_menu'       => false, 
            'has_archive'        => false,
            'rewrite'            => array( 'slug' => 'marketplace-item', 'with_front' => false ),
            'supports'           => array( 'title', 'editor', 'thumbnail' ),
        ) );
    }
}

if ( ! function_exists( 'villocity_marketplace_flush_rewrite' ) ) {
    add_action('admin_init', 'villocity_marketplace_flush_rewrite');
    function villocity_marketplace_flush_rewrite() {
        if (!get_transient('villocity_marketplace_flush')) {
            flush_rewrite_rules();
            set_transient('villocity_marketplace_flush', true, 86400);
        }
    }
}

// ==========================================
// 3. INCLUDE SUB-MODULE COMPONENTS
// ==========================================
// Load Admin Dashboard, Settings & Meta Boxes
if ( is_admin() && file_exists( VOC_MARKETPLACE_DIR . 'admin.php' ) ) {
    require_once VOC_MARKETPLACE_DIR . 'admin.php';
}

// Load Public Marketplace Feed, Single View, Forms & Shortcodes
if ( file_exists( VOC_MARKETPLACE_DIR . 'frontend.php' ) ) {
    require_once VOC_MARKETPLACE_DIR . 'frontend.php';
}