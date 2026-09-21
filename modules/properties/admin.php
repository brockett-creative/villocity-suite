<?php
/**
 * Module Name: VilloCity Land & Buildings (Admin)
 * Module URI:  https://villocity.org
 * Description: Real Estate specifications meta boxes and VilloCity Hub submenu integration.
 * Version:     2.1.0
 * Last Updated: September 5, 2026
 * Author:      Brockett Creative Group, Inc.
 * Author URI:  https://brockettcreative.com
 * Copyright:   © 2026 Brockett Creative Group, Inc. All Rights Reserved.
 * Text Domain: villocity-suite
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

// ==========================================
// 1. BACKEND META BOXES
// ==========================================
if ( ! function_exists( 'voc_properties_add_meta_boxes' ) ) {
    add_action( 'add_meta_boxes', 'voc_properties_add_meta_boxes' );
    function voc_properties_add_meta_boxes() {
        add_meta_box(
            'voc_property_meta_box',
            villocity_get_icon('building') . ' Property & Real Estate Specifications',
            'voc_property_render_meta_box',
            'voc_property',
            'normal',
            'high'
        );
    }
}

if ( ! function_exists( 'voc_property_render_meta_box' ) ) {
    function voc_property_render_meta_box( $post ) {
        wp_nonce_field( 'voc_property_save_meta', 'voc_property_meta_nonce' );

        $type      = get_post_meta( $post->ID, '_prop_listing_type', true );
        $price     = get_post_meta( $post->ID, '_prop_price', true );
        $acres     = get_post_meta( $post->ID, '_prop_acreage', true );
        $sqft      = get_post_meta( $post->ID, '_prop_sqft', true );
        $zoning    = get_post_meta( $post->ID, '_prop_zoning', true );
        $address   = get_post_meta( $post->ID, '_prop_address', true );
        $municip   = get_post_meta( $post->ID, '_prop_municipality', true );
        $utilities = get_post_meta( $post->ID, '_prop_utilities', true );
        $doc_url   = get_post_meta( $post->ID, '_prop_doc_url', true );
        $featured  = get_post_meta( $post->ID, '_prop_featured', true );

        echo '<div style="display:flex; flex-wrap:wrap; gap:20px; padding:10px 0;">';
        
        echo '<div style="flex:1; min-width:250px;">';
        echo '<p><strong>' . villocity_get_icon('tag') . ' Listing Type</strong><br><select name="voc_prop_listing_type" style="width:100%;"><option value="For Sale" '.selected($type, 'For Sale', false).'>For Sale</option><option value="For Lease" '.selected($type, 'For Lease', false).'>For Lease</option><option value="Sale or Lease" '.selected($type, 'Sale or Lease', false).'>Sale or Lease</option></select></p>';
        echo '<p><strong>' . villocity_get_icon('bag') . ' Price / Rates</strong><br><input type="text" name="voc_prop_price" value="'.esc_attr($price).'" style="width:100%;" placeholder="e.g. $1,200,000 or Contact IDA"></p>';
        echo '<p><strong>' . villocity_get_icon('map-pin') . ' Acreage (Land Size)</strong><br><input type="text" name="voc_prop_acreage" value="'.esc_attr($acres).'" style="width:100%;" placeholder="e.g. 45 Acres"></p>';
        echo '<p><strong>' . villocity_get_icon('building') . ' Building Sq. Ft.</strong><br><input type="text" name="voc_prop_sqft" value="'.esc_attr($sqft).'" style="width:100%;" placeholder="e.g. 65,100 SF"></p>';
        echo '</div>';

        echo '<div style="flex:1; min-width:250px;">';
        echo '<p><strong>' . villocity_get_icon('map-pin') . ' Municipality / Location</strong><br><input type="text" name="voc_prop_municipality" value="'.esc_attr($municip).'" style="width:100%;" placeholder="e.g. Frankfort, NY"></p>';
        echo '<p><strong>' . villocity_get_icon('map-pin') . ' Physical Address</strong><br><input type="text" name="voc_prop_address" value="'.esc_attr($address).'" style="width:100%;" placeholder="126 Industrial Park Dr"></p>';
        echo '<p><strong>' . villocity_get_icon('tag') . ' Zoning Designation</strong><br><input type="text" name="voc_prop_zoning" value="'.esc_attr($zoning).'" style="width:100%;" placeholder="e.g. Heavy Industrial / Commercial"></p>';
        echo '<p><strong>' . villocity_get_icon('info') . ' Utilities Available</strong><br><input type="text" name="voc_prop_utilities" value="'.esc_attr($utilities).'" style="width:100%;" placeholder="e.g. 3-Phase Electric, Municipal Water/Sewer"></p>';
        echo '</div>';

        echo '<div style="flex:1; min-width:250px;">';
        echo '<p><strong>' . villocity_get_icon('globe') . ' PDF Document / Site Plan Link</strong><br><input type="url" name="voc_prop_doc_url" value="'.esc_attr($doc_url).'" style="width:100%;" placeholder="https://... PDF URL"></p>';
        echo '<p><label><input type="checkbox" name="voc_prop_featured" value="yes" '.checked($featured, 'yes', false).'> <strong>' . villocity_get_icon('star') . ' Feature Property at Top</strong></label></p>';
        echo '</div>';

        echo '</div>';
    }
}

if ( ! function_exists( 'voc_property_save_meta_box' ) ) {
    add_action( 'save_post_voc_property', 'voc_property_save_meta_box' );
    function voc_property_save_meta_box( $post_id ) {
        if ( ! isset( $_POST['voc_property_meta_nonce'] ) || ! wp_verify_nonce( $_POST['voc_property_meta_nonce'], 'voc_property_save_meta' ) ) return;
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;

        update_post_meta( $post_id, '_prop_listing_type', sanitize_text_field( $_POST['voc_prop_listing_type'] ) );
        update_post_meta( $post_id, '_prop_price', sanitize_text_field( $_POST['voc_prop_price'] ) );
        update_post_meta( $post_id, '_prop_acreage', sanitize_text_field( $_POST['voc_prop_acreage'] ) );
        update_post_meta( $post_id, '_prop_sqft', sanitize_text_field( $_POST['voc_prop_sqft'] ) );
        update_post_meta( $post_id, '_prop_zoning', sanitize_text_field( $_POST['voc_prop_zoning'] ) );
        update_post_meta( $post_id, '_prop_address', sanitize_text_field( $_POST['voc_prop_address'] ) );
        update_post_meta( $post_id, '_prop_municipality', sanitize_text_field( $_POST['voc_prop_municipality'] ) );
        update_post_meta( $post_id, '_prop_utilities', sanitize_text_field( $_POST['voc_prop_utilities'] ) );
        update_post_meta( $post_id, '_prop_doc_url', esc_url_raw( $_POST['voc_prop_doc_url'] ) );
        update_post_meta( $post_id, '_prop_featured', isset($_POST['voc_prop_featured']) ? 'yes' : 'no' );
    }
}

// ==========================================
// 2. SUBMENU IN VILLOCITY HUB
// ==========================================
if ( ! function_exists( 'voc_property_add_hub_menu' ) ) {
    add_action( 'admin_menu', 'voc_property_add_hub_menu', 25 );
    function voc_property_add_hub_menu() {
        if ( menu_page_url( 'villocity-hub', false ) ) {
            add_submenu_page(
                'villocity-hub',
                'Available Properties',
                'Available Properties',
                'manage_options',
                'edit.php?post_type=voc_property'
            );
            add_submenu_page(
                'villocity-hub',
                'Property Categories',
                'Property Categories',
                'manage_options',
                'edit-tags.php?taxonomy=voc_property_category&post_type=voc_property'
            );
        }
    }
}