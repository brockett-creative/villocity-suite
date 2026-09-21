<?php
/**
 * Module Name: VilloCity Digital Archives (Admin)
 * Module URI:  https://villocity.org
 * Description: Backend administration, artifact meta boxes, and Digital Archives Hub dashboard.
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
if ( ! function_exists( 'bcg_history_add_meta_boxes' ) ) {
    add_action( 'add_meta_boxes', 'bcg_history_add_meta_boxes' );
    function bcg_history_add_meta_boxes() {
        add_meta_box(
            'bcg_artifact_meta_box',
            villocity_get_icon('archive') . ' Artifact & Archive Details',
            'bcg_history_render_meta_box',
            'bcg_artifact',
            'normal',
            'high'
        );
    }
}

if ( ! function_exists( 'bcg_history_render_meta_box' ) ) {
    function bcg_history_render_meta_box( $post ) {
        wp_nonce_field( 'bcg_history_save_meta', 'bcg_history_meta_nonce' );

        $era        = get_post_meta( $post->ID, '_artifact_era', true );
        $collection = get_post_meta( $post->ID, '_artifact_collection', true );
        $type       = get_post_meta( $post->ID, '_artifact_type', true );
        $donor      = get_post_meta( $post->ID, '_artifact_donor', true );

        echo '<div style="display:flex; flex-wrap:wrap; gap:20px; padding:10px 0;">';
        
        echo '<div style="flex:1; min-width:250px;">';
        echo '<p style="margin-top:0;"><strong>' . villocity_get_icon('clock') . 'Era / Decade</strong><br>';
        echo '<input type="text" name="bcg_artifact_era" value="'.esc_attr($era).'" style="width:100%; max-width:100%;" placeholder="e.g., 1920s, Civil War, 18th Century"></p>';

        echo '<p><strong>' . villocity_get_icon('tag') . 'Artifact Type</strong><br>';
        echo '<select name="bcg_artifact_type" style="width:100%; max-width:100%;">';
        echo '<option value="Photograph" ' . selected($type, 'Photograph', false) . '>Photograph / Image</option>';
        echo '<option value="Document" ' . selected($type, 'Document', false) . '>Historical Document</option>';
        echo '<option value="Physical Object" ' . selected($type, 'Physical Object', false) . '>Physical Object / Relic</option>';
        echo '<option value="Map" ' . selected($type, 'Map', false) . '>Map / Blueprint</option>';
        echo '</select></p>';
        echo '</div>';

        echo '<div style="flex:1; min-width:250px;">';
        echo '<p style="margin-top:0;"><strong>' . villocity_get_icon('archive') . 'Collection Name</strong><br>';
        echo '<input type="text" name="bcg_artifact_collection" value="'.esc_attr($collection).'" style="width:100%; max-width:100%;" placeholder="e.g., The Smith Family Letters"></p>';

        echo '<p><strong>' . villocity_get_icon('user') . 'Donated By / Source</strong><br>';
        echo '<input type="text" name="bcg_artifact_donor" value="'.esc_attr($donor).'" style="width:100%; max-width:100%;" placeholder="e.g., Donated by Jane Doe (2023)"></p>';
        echo '</div>';

        echo '</div>'; 
    }
}

if ( ! function_exists( 'bcg_history_save_meta_box_data' ) ) {
    add_action( 'save_post_bcg_artifact', 'bcg_history_save_meta_box_data' );
    function bcg_history_save_meta_box_data( $post_id ) {
        if ( ! isset( $_POST['bcg_history_meta_nonce'] ) || ! wp_verify_nonce( $_POST['bcg_history_meta_nonce'], 'bcg_history_save_meta' ) ) return;
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
        if ( ! current_user_can( 'edit_post', $post_id ) ) return;

        update_post_meta( $post_id, '_artifact_era', sanitize_text_field( $_POST['bcg_artifact_era'] ) );
        update_post_meta( $post_id, '_artifact_collection', sanitize_text_field( $_POST['bcg_artifact_collection'] ) );
        update_post_meta( $post_id, '_artifact_type', sanitize_text_field( $_POST['bcg_artifact_type'] ) );
        update_post_meta( $post_id, '_artifact_donor', sanitize_text_field( $_POST['bcg_artifact_donor'] ) );
    }
}

// ==========================================
// 2. WP ADMIN DASHBOARD MENU & MODULE UI
// ==========================================
if ( ! function_exists( 'bcg_history_add_menu' ) ) {
    add_action( 'admin_menu', 'bcg_history_add_menu', 20 );
    function bcg_history_add_menu() {
        if ( menu_page_url( 'villocity-hub', false ) ) {
            add_submenu_page(
                'villocity-hub',
                'Digital Archives',
                'Digital Archives',
                'manage_options',
                'bcg-history-hub',
                'bcg_render_history_dashboard'
            );
        }
    }
}

if ( ! function_exists( 'bcg_render_history_dashboard' ) ) {
    function bcg_render_history_dashboard() {
        if ( ! current_user_can( 'manage_options' ) ) return;

        $message = '';
        $primary_col = get_option('villocity_global_primary', '#860038');

        $active_query = new WP_Query( array( 'post_type' => 'bcg_artifact', 'post_status' => 'publish', 'posts_per_page' => -1 ) );

        echo '<div class="voc-gemini-dashboard">' . $message;
        
        villocity_render_dashboard_header( 
            'Digital Archives Hub', 
            'Manage the historical society collection, artifacts, and exhibits.', 
            'archive', 
            admin_url('post-new.php?post_type=bcg_artifact') 
        );

        echo '
            <div style="display: flex; gap: 16px; margin-bottom: 30px; flex-wrap: wrap;">
                <div class="voc-gemini-card" style="margin-bottom:0; padding:20px; flex: 1; min-width: 300px;">
                    <strong style="color: ' . esc_attr($primary_col) . '; display:block; margin-bottom:6px;">Main Archive Gallery Shortcode</strong>
                    <code style="display:inline-block; padding:4px 8px; background:#f1f5f9; border-radius:6px; font-size:14px; border:1px solid #e2e8f0; color:#0f172a;">[villocity_digital_archives]</code>
                    <p style="margin: 8px 0 0 0; font-size: 13px; color: #64748b;">Displays the full, searchable digital archive database.</p>
                </div>
                <div class="voc-gemini-card" style="margin-bottom:0; padding:20px; flex: 1; min-width: 300px;">
                    <strong style="color: ' . esc_attr($primary_col) . '; display:block; margin-bottom:6px;">Homepage "Recent" Widget</strong>
                    <code style="display:inline-block; padding:4px 8px; background:#f1f5f9; border-radius:6px; font-size:14px; border:1px solid #e2e8f0; color:#0f172a;">[villocity_recent_artifacts count="3"]</code>
                    <p style="margin: 8px 0 0 0; font-size: 13px; color: #64748b;">Shows a mini grid of the latest entries for the homepage.</p>
                </div>
            </div>

            <div class="voc-gemini-card" style="padding: 0;">
                <table class="voc-gemini-data-table">
                    <thead><tr><th>Artifact Details</th><th>Era</th><th>Type</th><th style="text-align:right;">Actions</th></tr></thead>
                    <tbody>';
                    if ( $active_query->have_posts() ) {
                        while ( $active_query->have_posts() ) {
                            $active_query->the_post();
                            $id = get_the_ID();
                            $era  = get_post_meta($id, '_artifact_era', true);
                            $type = get_post_meta($id, '_artifact_type', true);
                            
                            echo '<tr>
                                <td><strong><a href="' . esc_url(get_permalink($id)) . '" target="_blank" style="text-decoration:none; color:' . esc_attr($primary_col) . ';">' . esc_html(get_the_title()) . '</a></strong></td>
                                <td><span style="font-size:13px; font-weight:600; color:#92400e;">' . esc_html($era) . '</span></td>
                                <td><span style="background:#f1f5f9; color:#475569; padding:4px 10px; border-radius:12px; font-size:12px; font-weight:600;">' . esc_html($type) . '</span></td>
                                <td style="text-align:right;">
                                    <a href="' . esc_url(get_edit_post_link($id)) . '" class="voc-gemini-btn-secondary">Edit</a>
                                </td>
                            </tr>';
                        }
                    } else {
                        echo '<tr><td colspan="4" style="text-align:center; padding:40px; color:#64748b;">No artifacts added to the digital archive yet.</td></tr>';
                    }
        echo '          </tbody>
                </table>
            </div>
        </div>
        
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                var searchInput = document.getElementById("bcg-admin-search");
                if(searchInput) {
                    searchInput.addEventListener("input", function() {
                        var query = this.value.toLowerCase().trim();
                        var rows = document.querySelectorAll(".voc-gemini-data-table tbody tr");
                        rows.forEach(function(row) {
                            var text = row.innerText.toLowerCase();
                            if (text.indexOf(query) > -1) {
                                row.style.display = "";
                            } else {
                                row.style.display = "none";
                            }
                        });
                    });
                }
            });
        </script>';
    }
}