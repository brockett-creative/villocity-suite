<?php
/**
 * Module Name: VilloCity Local News & Announcements
 * Module URI:  https://villocity.org
 * Description: Aggregates RSS feeds, manages email digests, and outputs adaptive news shortcodes.
 * Version:     2.2.5
 * Last Updated: September 7, 2026
 * Author:      Brockett Creative Group, Inc.
 * Author URI:  https://brockettcreative.com
 * Text Domain: villocity-suite
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! defined( 'VOC_NEWS_DIR' ) ) {
    define( 'VOC_NEWS_DIR', plugin_dir_path( __FILE__ ) );
}

// ==========================================
// 1. REGISTER CPT
// ==========================================
if ( ! function_exists( 'bcg_register_news_cpt' ) ) {
    add_action( 'init', 'bcg_register_news_cpt', 5 );
    function bcg_register_news_cpt() {
        register_post_type( 'bcg_news', array(
            'labels' => array(
                'name'          => 'Local News',
                'singular_name' => 'News Article',
                'add_new'       => 'Add Article',
                'add_new_item'  => 'Add New Article',
                'edit_item'     => 'Edit Article',
                'all_items'     => 'All Articles',
                'menu_name'     => 'News'
            ),
            'public'             => true,
            'publicly_queryable' => true,
            'show_ui'            => true,
            'show_in_menu'       => false,
            'has_archive'        => false,
            'rewrite'            => array( 'slug' => 'news-item', 'with_front' => false ),
            'supports'           => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
        ) );
    }
}

// ==========================================
// 2. INCLUDE SUB-MODULE COMPONENTS
// ==========================================
if ( is_admin() && file_exists( VOC_NEWS_DIR . 'admin.php' ) ) {
    require_once VOC_NEWS_DIR . 'admin.php';
}

if ( file_exists( VOC_NEWS_DIR . 'frontend.php' ) ) {
    require_once VOC_NEWS_DIR . 'frontend.php';
}