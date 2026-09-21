<?php
/**
 * Module Name: VilloCity Departments Module (Admin)
 * Description: Handles WP-Admin meta box inputs, document attachment repeaters, post saves, and dashboard widgets.
 * Version:     1.5.0
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

// ==========================================
// 1. BACKEND META BOXES
// ==========================================
if ( ! function_exists( 'voc_departments_add_meta_boxes' ) ) {
    add_action( 'add_meta_boxes', 'voc_departments_add_meta_boxes' );
    function voc_departments_add_meta_boxes() {
        add_meta_box(
            'voc_department_meta_box',
            villocity_get_icon('building') . ' Department Information & Contacts',
            'voc_department_render_meta_box',
            'voc_department',
            'normal',
            'high'
        );

        add_meta_box(
            'voc_department_docs_box',
            villocity_get_icon('archive') . ' Public Forms & Downloadable Documents',
            'voc_department_render_docs_meta_box',
            'voc_department',
            'normal',
            'default'
        );
    }
}

if ( ! function_exists( 'voc_department_render_meta_box' ) ) {
    function voc_department_render_meta_box( $post ) {
        wp_nonce_field( 'voc_department_save_meta', 'voc_department_meta_nonce' );

        $phone     = get_post_meta( $post->ID, '_dept_phone', true );
        $email     = get_post_meta( $post->ID, '_dept_email', true );
        $hours     = get_post_meta( $post->ID, '_dept_hours', true );
        $location  = get_post_meta( $post->ID, '_dept_location', true );
        $head_name = get_post_meta( $post->ID, '_dept_head_name', true );
        $head_title= get_post_meta( $post->ID, '_dept_head_title', true );

        echo '<div style="display:flex; flex-wrap:wrap; gap:20px; padding:10px 0;">';
        echo '<div style="flex:1; min-width:250px;"><p><strong>Department Head Name</strong><br><input type="text" name="voc_dept_head_name" value="'.esc_attr($head_name).'" style="width:100%;" placeholder="e.g. John Doe"></p><p><strong>Department Head Title</strong><br><input type="text" name="voc_dept_head_title" value="'.esc_attr($head_title).'" style="width:100%;" placeholder="e.g. Highway Superintendent"></p></div>';
        echo '<div style="flex:1; min-width:250px;"><p><strong>Phone Number</strong><br><input type="text" name="voc_dept_phone" value="'.esc_attr($phone).'" style="width:100%;" placeholder="(315) 555-0199"></p><p><strong>Email Address</strong><br><input type="email" name="voc_dept_email" value="'.esc_attr($email).'" style="width:100%;" placeholder="dept@villageofclinton.com"></p></div>';
        echo '<div style="flex:1; min-width:250px;"><p><strong>Office Hours</strong><br><input type="text" name="voc_dept_hours" value="'.esc_attr($hours).'" style="width:100%;" placeholder="Mon-Fri 8:00 AM - 4:00 PM"></p><p><strong>Physical Location / Address</strong><br><input type="text" name="voc_dept_location" value="'.esc_attr($location).'" style="width:100%;" placeholder="Municipal Building, Room 102"></p></div>';
        echo '</div>';
    }
}

if ( ! function_exists( 'voc_department_render_docs_meta_box' ) ) {
    function voc_department_render_docs_meta_box( $post ) {
        $docs = get_post_meta( $post->ID, '_dept_documents', true );
        if ( ! is_array( $docs ) ) { $docs = array(); }

        echo '<p style="color:#64748b; font-size:13px;">Attach downloadable applications, permits, or informational PDFs associated with this department.</p>';
        echo '<div id="voc-dept-docs-container">';

        if ( ! empty( $docs ) ) {
            foreach ( $docs as $i => $doc ) {
                echo '<div class="voc-doc-row" style="background:#f8fafc; border:1px solid #e2e8f0; padding:12px 16px; border-radius:10px; margin-bottom:12px; display:flex; gap:12px; align-items:center; flex-wrap:wrap;"><div style="flex:1; min-width:180px;"><input type="text" name="voc_dept_doc_title[]" value="' . esc_attr($doc['title']) . '" placeholder="Document Title" style="width:100%;"></div><div style="flex:1.5; min-width:220px;"><input type="url" name="voc_dept_doc_url[]" value="' . esc_attr($doc['url']) . '" placeholder="PDF File URL" style="width:100%;"></div><button type="button" class="button voc-remove-doc-row" style="color:#b91c1c; border-color:#fecaca; background:#fef2f2;">Remove</button></div>';
            }
        }

        echo '</div>';
        echo '<button type="button" class="button button-secondary" id="voc-add-doc-row" style="margin-top:8px;">+ Add Document Link</button>';
        echo '<script>document.addEventListener("DOMContentLoaded", function() { const container = document.getElementById("voc-dept-docs-container"); const addBtn = document.getElementById("voc-add-doc-row"); if (addBtn) { addBtn.addEventListener("click", function(e) { e.preventDefault(); const row = document.createElement("div"); row.className = "voc-doc-row"; row.style.cssText = "background:#f8fafc; border:1px solid #e2e8f0; padding:12px 16px; border-radius:10px; margin-bottom:12px; display:flex; gap:12px; align-items:center; flex-wrap:wrap;"; row.innerHTML = `<div style="flex:1; min-width:180px;"><input type="text" name="voc_dept_doc_title[]" placeholder="Document Title" style="width:100%;"></div><div style="flex:1.5; min-width:220px;"><input type="url" name="voc_dept_doc_url[]" placeholder="PDF File URL" style="width:100%;"></div><button type="button" class="button voc-remove-doc-row" style="color:#b91c1c; border-color:#fecaca; background:#fef2f2;">Remove</button>`; container.appendChild(row); }); } if (container) { container.addEventListener("click", function(e) { if (e.target.classList.contains("voc-remove-doc-row")) { e.preventDefault(); e.target.closest(".voc-doc-row").remove(); } }); } });</script>';
    }
}

if ( ! function_exists( 'voc_department_save_meta_box' ) ) {
    add_action( 'save_post_voc_department', 'voc_department_save_meta_box' );
    function voc_department_save_meta_box( $post_id ) {
        if ( ! isset( $_POST['voc_department_meta_nonce'] ) || ! wp_verify_nonce( $_POST['voc_department_meta_nonce'], 'voc_department_save_meta' ) ) return;
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;

        update_post_meta( $post_id, '_dept_phone', sanitize_text_field( $_POST['voc_dept_phone'] ) );
        update_post_meta( $post_id, '_dept_email', sanitize_email( $_POST['voc_dept_email'] ) );
        update_post_meta( $post_id, '_dept_hours', sanitize_text_field( $_POST['voc_dept_hours'] ) );
        update_post_meta( $post_id, '_dept_location', sanitize_text_field( $_POST['voc_dept_location'] ) );
        update_post_meta( $post_id, '_dept_head_name', sanitize_text_field( $_POST['voc_dept_head_name'] ) );
        update_post_meta( $post_id, '_dept_head_title', sanitize_text_field( $_POST['voc_dept_head_title'] ) );

        $clean_docs = array();
        if ( ! empty( $_POST['voc_dept_doc_title'] ) && is_array( $_POST['voc_dept_doc_title'] ) ) {
            $titles = $_POST['voc_dept_doc_title'];
            $urls   = $_POST['voc_dept_doc_url'];

            for ( $i = 0; $i < count( $titles ); $i++ ) {
                $t = sanitize_text_field( $titles[$i] );
                $u = esc_url_raw( $urls[$i] );
                if ( ! empty( $t ) && ! empty( $u ) ) {
                    $clean_docs[] = array( 'title' => $t, 'url' => $u );
                }
            }
        }
        update_post_meta( $post_id, '_dept_documents', $clean_docs );
    }
}

// ==========================================
// 2. REGISTER DEPARTMENTS HUB IN VILLOCITY
// ==========================================
if ( ! function_exists( 'voc_departments_add_hub_menu' ) ) {
    add_action( 'admin_menu', 'voc_departments_add_hub_menu', 26 );
    function voc_departments_add_hub_menu() {
        if ( menu_page_url( 'villocity-hub', false ) ) {
            add_submenu_page(
                'villocity-hub',
                'Departments Hub',
                'Departments',
                'manage_options',
                'voc-departments-hub',
                'voc_departments_render_admin_dashboard'
            );
        }
    }
}

function voc_departments_render_admin_dashboard() {
    if ( ! current_user_can( 'manage_options' ) ) return;

    $primary_col = get_option('villocity_global_primary', '#860038');
    $dept_count  = wp_count_posts('voc_department')->publish ?? 0;
    $staff_count = wp_count_posts('bcg_staff')->publish ?? 0;

    echo '<div class="voc-gemini-dashboard">';
    villocity_render_dashboard_header( 'Departments Hub', 'Manage municipal departments, contacts, forms, and roster connections.', 'building', admin_url('post-new.php?post_type=voc_department') );

    echo '
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 16px; margin-bottom: 24px;">
        <div class="voc-gemini-card" style="margin-bottom:0;">
            <h3>' . villocity_get_icon('building') . ' Active Departments</h3>
            <span style="font-size: 32px; font-weight: 800; color: ' . esc_attr($primary_col) . ';">' . esc_html($dept_count) . '</span>
            <p style="margin: 4px 0 0 0; color: #64748b; font-size: 13px;">Published department pages</p>
        </div>
        <div class="voc-gemini-card" style="margin-bottom:0;">
            <h3>' . villocity_get_icon('users') . ' Total Personnel</h3>
            <span style="font-size: 32px; font-weight: 800; color: ' . esc_attr($primary_col) . ';">' . esc_html($staff_count) . '</span>
            <p style="margin: 4px 0 0 0; color: #64748b; font-size: 13px;">Staff & officials in database</p>
        </div>
    </div>

    <div class="voc-gemini-card">
        <h3>' . villocity_get_icon('pin') . ' Department Shortcode</h3>
        <p style="color:#64748b;">Place this shortcode on any page to display the public department directory grid:</p>
        <code style="background:#f1f5f9; padding:8px 14px; border-radius:8px; font-size:15px; display:inline-block;">[villocity_departments]</code>
    </div>

    <div style="margin-top:20px;">
        <a href="' . esc_url(admin_url('edit.php?post_type=voc_department')) . '" class="voc-gemini-btn-primary">&rarr; Manage Department Pages</a>
    </div>
    </div>';
}