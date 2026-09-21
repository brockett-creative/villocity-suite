<?php
/**
 * VilloCity Module: Board & Staff (Admin & Backend)
 * Version: 1.4.0
 * Author: Brockett Creative Group, Inc.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

// ==========================================
// 1. DYNAMIC HELPERS: GROUPS & CLASSIFICATIONS
// ==========================================
if ( ! function_exists( 'voc_staff_get_groups' ) ) {
    function voc_staff_get_groups() {
        $default = 'Board of Trustees, Planning Board, Zoning Board, Administration, Public Works';
        $groups_string = get_option('voc_staff_custom_groups', $default);
        $groups_array = explode(',', $groups_string);
        return array_filter(array_map('trim', $groups_array));
    }
}

if ( ! function_exists( 'voc_staff_get_classifications' ) ) {
    function voc_staff_get_classifications() {
        $default = 'Elected Official, Appointed Member, Municipal Staff';
        $class_string = get_option('voc_staff_custom_classifications', $default);
        $class_array = explode(',', $class_string);
        return array_filter(array_map('trim', $class_array));
    }
}

// ==========================================
// 2. BACKEND META BOXES
// ==========================================
if ( ! function_exists( 'voc_staff_add_meta_boxes' ) ) {
    add_action( 'add_meta_boxes', 'voc_staff_add_meta_boxes' );
    function voc_staff_add_meta_boxes() {
        add_meta_box('voc_staff_meta_box', villocity_get_icon('user') . ' Profile Details', 'voc_staff_render_meta_box', 'bcg_staff', 'normal', 'high');
        add_meta_box('voc_staff_dept_box', villocity_get_icon('building') . ' Assign to Department Page', 'voc_staff_render_dept_meta_box', 'bcg_staff', 'side', 'default');
    }
}

if ( ! function_exists( 'voc_staff_render_meta_box' ) ) {
    function voc_staff_render_meta_box( $post ) {
        wp_nonce_field( 'voc_staff_save_meta', 'voc_staff_meta_nonce' );

        $position       = get_post_meta( $post->ID, '_staff_position', true );
        $group          = get_post_meta( $post->ID, '_staff_group', true );
        $classification = get_post_meta( $post->ID, '_staff_classification', true );
        $email          = get_post_meta( $post->ID, '_staff_email', true );
        $phone          = get_post_meta( $post->ID, '_staff_phone', true );
        $linkedin       = get_post_meta( $post->ID, '_staff_linkedin', true );
        $order          = get_post_meta( $post->ID, '_staff_order', true );
        $is_mock        = get_post_meta( $post->ID, '_staff_is_mock', true );
        
        if ( $order === '' ) $order = '10';

        echo '<div style="display:flex; flex-wrap:wrap; gap:20px; padding:10px 0;">';
        
        echo '<div style="flex:1; min-width:250px;">';
        
        echo '<p style="margin-top:0;"><strong>' . villocity_get_icon('star') . ' Position / Title</strong><br>';
        echo '<input type="text" name="voc_staff_position" value="'.esc_attr($position).'" style="width:100%;" placeholder="e.g. Mayor, Village Clerk"></p>';

        echo '<p><strong>' . villocity_get_icon('tag') . ' Department / Group</strong><br>';
        echo '<select name="voc_staff_group" style="width:100%;">';
        echo '<option value="">-- Select Group --</option>';
        foreach(voc_staff_get_groups() as $grp) {
            echo '<option value="'.esc_attr($grp).'" ' . selected($group, $grp, false) . '>'.esc_html($grp).'</option>';
        }
        echo '</select></p>';

        echo '<p><strong>' . villocity_get_icon('users') . ' Role Classification</strong><br>';
        echo '<select name="voc_staff_classification" style="width:100%;">';
        echo '<option value="">-- Select Classification --</option>';
        foreach(voc_staff_get_classifications() as $cls) {
            echo '<option value="'.esc_attr($cls).'" ' . selected($classification, $cls, false) . '>'.esc_html($cls).'</option>';
        }
        echo '</select></p>';
        
        echo '<p><strong>' . villocity_get_icon('map-pin') . ' Display Order</strong><br>';
        echo '<input type="number" name="voc_staff_order" value="'.esc_attr($order).'" style="width:100px;">';
        echo '<br><small style="color:#64748b;">Lower numbers show first.</small></p>';
        echo '</div>';

        echo '<div style="flex:1; min-width:250px; background:#f8fafc; padding:15px; border-left:4px solid var(--voc-primary, #3b82f6); border-radius:4px;">';
        
        // MOCK DATA CHECKBOX
        echo '<div style="background:#fffbeb; border:1px solid #fde68a; padding:10px 12px; border-radius:8px; margin-bottom:16px;">';
        echo '<label style="font-weight:700; color:#b45309; cursor:pointer;">';
        echo '<input type="checkbox" name="voc_staff_is_mock" value="1" ' . checked($is_mock, '1', false) . '> Mark as Mock / Demo Staff';
        echo '</label>';
        echo '<p style="margin:4px 0 0 22px; font-size:12px; color:#b45309;">Mock staff render on the frontend only when enabled in settings.</p>';
        echo '</div>';

        echo '<p style="margin-top:0;"><strong>' . villocity_get_icon('mail') . ' Public Email Address</strong><br>';
        echo '<input type="email" name="voc_staff_email" value="'.esc_attr($email).'" style="width:100%;"></p>';

        echo '<p><strong>' . villocity_get_icon('phone') . ' Public Phone Number</strong><br>';
        echo '<input type="text" name="voc_staff_phone" value="'.esc_attr($phone).'" style="width:100%;" placeholder="e.g. (555) 123-4567"></p>';

        echo '<p><strong>' . villocity_get_icon('globe') . ' LinkedIn Profile URL</strong><br>';
        echo '<input type="url" name="voc_staff_linkedin" value="'.esc_attr($linkedin).'" style="width:100%;" placeholder="https://linkedin.com/in/..."></p>';
        echo '</div>';

        echo '</div>'; 
    }
}

// RENDER DEPARTMENT DROPDOWN METABOX
if ( ! function_exists( 'voc_staff_render_dept_meta_box' ) ) {
    function voc_staff_render_dept_meta_box( $post ) {
        wp_nonce_field( 'voc_staff_dept_save', 'voc_staff_dept_nonce' );
        $current_dept_id = get_post_meta( $post->ID, '_staff_department_id', true );

        $depts = get_posts( array(
            'post_type'      => 'voc_department',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'orderby'        => 'title',
            'order'          => 'ASC'
        ) );

        echo '<p style="margin-top:0; font-size:12px; color:#64748b;">Link this staff member to an official department page:</p>';
        echo '<select name="voc_staff_department_id" style="width:100%; padding:6px; border-radius:6px; border:1px solid #cbd5e1;">';
        echo '<option value="">-- No Department Page Linked --</option>';
        foreach ( $depts as $dept ) {
            echo '<option value="' . esc_attr( $dept->ID ) . '" ' . selected( $current_dept_id, $dept->ID, false ) . '>' . esc_html( $dept->post_title ) . '</option>';
        }
        echo '</select>';
    }
}

if ( ! function_exists( 'voc_staff_save_meta_box_data' ) ) {
    add_action( 'save_post_bcg_staff', 'voc_staff_save_meta_box_data' );
    function voc_staff_save_meta_box_data( $post_id ) {
        if ( isset( $_POST['voc_staff_meta_nonce'] ) && wp_verify_nonce( $_POST['voc_staff_meta_nonce'], 'voc_staff_save_meta' ) ) {
            if ( ! defined( 'DOING_AUTOSAVE' ) || ! DOING_AUTOSAVE ) {
                if ( current_user_can( 'edit_post', $post_id ) ) {
                    update_post_meta( $post_id, '_staff_position', sanitize_text_field( $_POST['voc_staff_position'] ) );
                    update_post_meta( $post_id, '_staff_group', sanitize_text_field( $_POST['voc_staff_group'] ) );
                    update_post_meta( $post_id, '_staff_classification', sanitize_text_field( $_POST['voc_staff_classification'] ) );
                    update_post_meta( $post_id, '_staff_email', sanitize_email( $_POST['voc_staff_email'] ) );
                    update_post_meta( $post_id, '_staff_phone', sanitize_text_field( $_POST['voc_staff_phone'] ) );
                    update_post_meta( $post_id, '_staff_linkedin', esc_url_raw( $_POST['voc_staff_linkedin'] ) );
                    update_post_meta( $post_id, '_staff_order', (int) $_POST['voc_staff_order'] );
                    update_post_meta( $post_id, '_staff_is_mock', isset($_POST['voc_staff_is_mock']) ? '1' : '0' );
                }
            }
        }

        if ( isset( $_POST['voc_staff_dept_nonce'] ) && wp_verify_nonce( $_POST['voc_staff_dept_nonce'], 'voc_staff_dept_save' ) ) {
            if ( ! defined( 'DOING_AUTOSAVE' ) || ! DOING_AUTOSAVE ) {
                if ( current_user_can( 'edit_post', $post_id ) ) {
                    if ( isset( $_POST['voc_staff_department_id'] ) ) {
                        $dept_id = intval( $_POST['voc_staff_department_id'] );
                        update_post_meta( $post_id, '_staff_department_id', $dept_id );
                        
                        $dept_post = get_post( $dept_id );
                        if ( $dept_post ) {
                            update_post_meta( $post_id, '_staff_department', $dept_post->post_title );
                        } else {
                            delete_post_meta( $post_id, '_staff_department' );
                        }
                    }
                }
            }
        }
    }
}

// ==========================================
// 3. DASHBOARD MENU & SETTINGS TAB
// ==========================================
if ( ! function_exists( 'voc_staff_add_menu' ) ) {
    add_action( 'admin_menu', 'voc_staff_add_menu', 22 );
    function voc_staff_add_menu() {
        if ( menu_page_url( 'villocity-hub', false ) ) {
            add_submenu_page('villocity-hub', 'Board & Staff Hub', 'Board & Staff', 'manage_options', 'bcg-staff-hub', 'voc_render_staff_dashboard');
        } else {
            add_menu_page('Board & Staff Hub', 'Board & Staff', 'manage_options', 'bcg-staff-hub', 'voc_render_staff_dashboard', 'dashicons-groups', 34);
        }
    }
}

if ( ! function_exists( 'voc_render_staff_dashboard' ) ) {
    function voc_render_staff_dashboard() {
        if ( ! current_user_can( 'manage_options' ) ) return;

        $message = '';

        if ( isset( $_POST['voc_staff_action_nonce'] ) && wp_verify_nonce( $_POST['voc_staff_action_nonce'], 'voc_staff_action' ) ) {
            
            if ( isset( $_POST['action_save_staff_settings'] ) ) {
                update_option( 'voc_staff_custom_groups', sanitize_text_field( $_POST['voc_staff_custom_groups'] ) );
                update_option( 'voc_staff_custom_classifications', sanitize_text_field( $_POST['voc_staff_custom_classifications'] ) );
                update_option( 'voc_staff_show_mock', isset($_POST['voc_staff_show_mock']) ? '1' : '0' );
                $message = '<div class="voc-gemini-notice"><h3>' . villocity_get_icon('check') . ' Settings Saved</h3><p style="margin:0;">Directory settings updated successfully.</p></div>';
            }

            if ( isset( $_POST['action_trash_member'] ) ) {
                wp_trash_post( (int) $_POST['post_id'] );
                $message = '<div class="voc-gemini-notice" style="background:#fef2f2; border-color:#fecaca; color:#991b1b;"><h3>' . villocity_get_icon('trash') . ' Member Trashed</h3><p style="margin:0;">The profile was moved to the trash bin.</p></div>';
            }
            if ( isset( $_POST['action_untrash_member'] ) ) {
                wp_untrash_post( (int) $_POST['post_id'] );
                $message = '<div class="voc-gemini-notice"><h3>' . villocity_get_icon('refresh') . ' Member Restored</h3><p style="margin:0;">The profile was restored.</p></div>';
            }
            if ( isset( $_POST['action_delete_member'] ) ) {
                wp_delete_post( (int) $_POST['post_id'], true );
                $message = '<div class="voc-gemini-notice" style="background:#fef2f2; border-color:#fecaca; color:#991b1b;"><h3>' . villocity_get_icon('check') . ' Member Deleted</h3><p style="margin:0;">The profile was permanently deleted.</p></div>';
            }
        }

        $active_query = new WP_Query( array( 
            'post_type'      => 'bcg_staff', 
            'post_status'    => 'publish', 
            'posts_per_page' => -1,
            'meta_key'       => '_staff_order',
            'orderby'        => array('meta_value_num' => 'ASC', 'title' => 'ASC')
        ));
        
        $trash_query            = new WP_Query( array( 'post_type' => 'bcg_staff', 'post_status' => 'trash', 'posts_per_page' => -1 ) );
        $custom_groups          = get_option( 'voc_staff_custom_groups', 'Board of Trustees, Planning Board, Zoning Board, Administration, Public Works' );
        $custom_classifications = get_option( 'voc_staff_custom_classifications', 'Elected Official, Appointed Member, Municipal Staff' );
        $show_mock              = get_option( 'voc_staff_show_mock', '0' );
        $primary_col            = get_option('villocity_global_primary', '#860038');

        echo '<div class="voc-gemini-dashboard">' . $message;
        
        villocity_render_dashboard_header( 'Board & Staff Directory', 'Manage town officials, staff bios, and contact information.', 'user', admin_url('post-new.php?post_type=bcg_staff') );

        echo '
            <div style="display: flex; gap: 16px; margin-bottom: 30px; flex-wrap: wrap;">
                <div class="voc-gemini-card" style="margin-bottom:0; padding:20px; flex: 1; min-width: 300px;">
                    <strong style="color: ' . esc_attr($primary_col) . '; display:block; margin-bottom:6px;">Display Entire Directory</strong>
                    <code style="display:inline-block; padding:4px 8px; background:#f1f5f9; border-radius:6px; font-size:14px; border:1px solid #e2e8f0; color:#0f172a;">[villocity_staff]</code>
                </div>
                <div class="voc-gemini-card" style="margin-bottom:0; padding:20px; flex: 1; min-width: 300px;">
                    <strong style="color: ' . esc_attr($primary_col) . '; display:block; margin-bottom:6px;">Display Specific Filters</strong>
                    <code style="display:inline-block; padding:4px 8px; background:#f1f5f9; border-radius:6px; font-size:14px; border:1px solid #e2e8f0; color:#0f172a;">[villocity_staff group="Planning Board"]</code>
                </div>
            </div>

            <div class="voc-gemini-tabs">
                <button class="voc-gemini-tab active" onclick="voc_switch_staff_tab(event, \'voc-staff-active\')">Active Members (' . $active_query->post_count . ')</button>
                <button class="voc-gemini-tab" onclick="voc_switch_staff_tab(event, \'voc-staff-groups\')">' . villocity_get_icon('settings') . ' Directory Options</button>
                <button class="voc-gemini-tab" onclick="voc_switch_staff_tab(event, \'voc-staff-trash\')">' . villocity_get_icon('trash') . ' Trash (' . $trash_query->post_count . ')</button>
            </div>

            <!-- TAB: ACTIVE MEMBERS -->
            <div id="voc-staff-active" class="voc-tab-content" style="display: block;">
                <div class="voc-gemini-card" style="padding: 0;">
                    <table class="voc-gemini-data-table">
                        <thead><tr><th style="width:80px; text-align:center;">Order</th><th>Profile Details</th><th>Group & Classification</th><th style="text-align:right;">Actions</th></tr></thead>
                        <tbody>';
                        if ( $active_query->have_posts() ) {
                            while ( $active_query->have_posts() ) {
                                $active_query->the_post();
                                $id = get_the_ID();
                                $order   = get_post_meta($id, '_staff_order', true);
                                $pos     = get_post_meta($id, '_staff_position', true);
                                $grp     = get_post_meta($id, '_staff_group', true);
                                $class   = get_post_meta($id, '_staff_classification', true);
                                $dept    = get_post_meta($id, '_staff_department', true);
                                $is_mock = get_post_meta($id, '_staff_is_mock', true);
                                
                                $img = get_the_post_thumbnail_url($id, 'thumbnail');
                                $img_html = $img ? '<img src="'.esc_url($img).'" style="width:40px; height:40px; border-radius:50%; object-fit:cover; border:1px solid #e2e8f0; flex-shrink:0;">' : '<div style="width:40px; height:40px; border-radius:50%; background:#f1f5f9; border:1px solid #e2e8f0; display:flex; align-items:center; justify-content:center; color:#94a3b8; flex-shrink:0;">' . villocity_get_icon('user') . '</div>';

                                echo '<tr class="bcg-admin-row">
                                    <td style="text-align:center;"><strong>' . esc_html($order) . '</strong></td>
                                    <td>
                                        <div style="display:flex; align-items:center; gap:12px;">
                                            ' . $img_html . '
                                            <div>
                                                <strong><a href="' . esc_url(get_edit_post_link($id)) . '" style="text-decoration:none; color:' . esc_attr($primary_col) . '; font-size:15px;">' . esc_html(get_the_title()) . '</a></strong>
                                                ' . ($is_mock === '1' ? ' <span style="background:#fffbeb; color:#b45309; border:1px solid #fde68a; font-size:10px; font-weight:700; padding:2px 6px; border-radius:10px;">🧪 MOCK</span>' : '') . '<br>
                                                <span style="font-size:12px; color:#64748b;">' . esc_html($pos) . '</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        ' . (!empty($grp) ? '<span style="background:#f8fafc; border:1px solid #e2e8f0; padding:4px 10px; border-radius:12px; font-size:12px; color:#475569; display:inline-block; margin-bottom:4px;">' . esc_html($grp) . '</span>' : '') . '
                                        ' . (!empty($dept) ? '<br><span style="font-size:11px; color:' . esc_attr($primary_col) . '; font-weight:600;">🏢 ' . esc_html($dept) . '</span>' : '') . '
                                        ' . (!empty($class) ? '<br><span style="font-size:11px; color:#94a3b8; font-weight:600; text-transform:uppercase;">' . esc_html($class) . '</span>' : '') . '
                                    </td>
                                    <td style="text-align:right; white-space: nowrap;">
                                        <a href="' . esc_url(get_edit_post_link($id)) . '" class="voc-gemini-btn-secondary" style="margin-right:4px;">Edit</a>
                                        <form method="post" style="display:inline-block;" onsubmit="return confirm(\'Move this member to the trash?\');">
                                            ' . wp_nonce_field( 'voc_staff_action', 'voc_staff_action_nonce', true, false ) . '
                                            <input type="hidden" name="action_trash_member" value="1">
                                            <input type="hidden" name="post_id" value="' . $id . '">
                                            <input type="submit" class="voc-gemini-btn-danger" value="Trash">
                                        </form>
                                    </td>
                                </tr>';
                            }
                        } else {
                            echo '<tr><td colspan="4" style="text-align:center; padding:40px; color:#64748b;">No members found in the directory.</td></tr>';
                        }
        echo '          </tbody>
                    </table>
                </div>
            </div>

            <!-- TAB: GROUPS & OPTIONS -->
            <div id="voc-staff-groups" class="voc-tab-content" style="display: none;">
                <div class="voc-gemini-card">
                    <form method="post">
                        ' . wp_nonce_field( 'voc_staff_action', 'voc_staff_action_nonce', true, false ) . '
                        <input type="hidden" name="action_save_staff_settings" value="1">

                        <h3 style="margin-top:0;">🧪 Mock Content Visibility</h3>
                        <div style="background:#fffbeb; border:1px solid #fde68a; padding:16px 20px; border-radius:12px; margin-bottom:24px;">
                            <label style="font-weight:700; cursor:pointer; display:flex; align-items:center; gap:10px; color:#b45309;">
                                <input type="checkbox" name="voc_staff_show_mock" value="1" ' . checked($show_mock, '1', false) . ' style="width:20px; height:20px;">
                                <span>Display Mock / Demo Staff Members on Frontend</span>
                            </label>
                            <p style="margin:6px 0 0 30px; font-size:13px; color:#b45309;">Uncheck this box when launching live sites. Staff marked as "Mock / Demo" will instantly disappear from public views.</p>
                        </div>

                        <h3>' . villocity_get_icon('tag') . ' Manage Custom Groups / Departments</h3>
                        <p style="color:#64748b; font-size:14px;">Define custom groups for staff and board members (e.g., Planning Board, DPW). <strong>Separate values with a comma.</strong></p>
                        <div style="margin-bottom: 24px;">
                            <textarea name="voc_staff_custom_groups" class="voc-gemini-textarea" rows="3">' . esc_textarea($custom_groups) . '</textarea>
                        </div>

                        <h3>' . villocity_get_icon('users') . ' Manage Role Classifications</h3>
                        <p style="color:#64748b; font-size:14px;">Define custom role classifications (e.g., Elected Official, Municipal Staff, Volunteer). <strong>Separate values with a comma.</strong></p>
                        <div style="margin-bottom: 24px;">
                            <textarea name="voc_staff_custom_classifications" class="voc-gemini-textarea" rows="3">' . esc_textarea($custom_classifications) . '</textarea>
                        </div>

                        <div style="margin-top: 20px; padding-top: 24px; border-top: 1px solid #e2e8f0;">
                            <button type="submit" class="voc-gemini-btn-primary">' . villocity_get_icon('check') . ' Save Directory Options</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- TAB: TRASH -->
            <div id="voc-staff-trash" class="voc-tab-content" style="display: none;">
                <div class="voc-gemini-card" style="padding: 0;">
                    <table class="voc-gemini-data-table">
                        <thead><tr><th>Profile Details</th><th>Group</th><th style="text-align:right;">Actions</th></tr></thead>
                        <tbody>';
                        if ( $trash_query->have_posts() ) {
                            while ( $trash_query->have_posts() ) {
                                $trash_query->the_post();
                                $id = get_the_ID();
                                echo '<tr>
                                    <td><strong style="color:#991b1b; text-decoration:line-through;">' . esc_html(get_the_title()) . '</strong></td>
                                    <td><span style="background:#f1f5f9; color:#475569; padding:4px 10px; border-radius:12px; font-size:12px;">' . esc_html(get_post_meta($id, '_staff_group', true)) . '</span></td>
                                    <td style="text-align:right; white-space:nowrap;">
                                        <form method="post" style="display:inline-block; margin-right:4px;">
                                            ' . wp_nonce_field( 'voc_staff_action', 'voc_staff_action_nonce', true, false ) . '
                                            <input type="hidden" name="action_untrash_member" value="1">
                                            <input type="hidden" name="post_id" value="' . $id . '">
                                            <button type="submit" class="voc-gemini-btn-secondary">' . villocity_get_icon('refresh') . ' Restore</button>
                                        </form>
                                        <form method="post" style="display:inline-block;" onsubmit="return confirm(\'Permanently delete this member? This cannot be undone.\');">
                                            ' . wp_nonce_field( 'voc_staff_action', 'voc_staff_action_nonce', true, false ) . '
                                            <input type="hidden" name="action_delete_member" value="1">
                                            <input type="hidden" name="post_id" value="' . $id . '">
                                            <button type="submit" class="voc-gemini-btn-danger">' . villocity_get_icon('trash') . ' Delete</button>
                                        </form>
                                    </td>
                                </tr>';
                            }
                        } else {
                            echo '<tr><td colspan="3" style="text-align:center; padding:40px; color:#64748b;">The trash bin is empty.</td></tr>';
                        }
        echo '          </tbody>
                    </table>
                </div>
            </div>
        </div>

        <script>
            function voc_switch_staff_tab(event, tabId) {
                if(event) event.preventDefault();
                var contents = document.getElementsByClassName("voc-tab-content");
                for (var i = 0; i < contents.length; i++) { contents[i].style.display = "none"; }
                var tabs = document.getElementsByClassName("voc-gemini-tab");
                for (var i = 0; i < tabs.length; i++) { tabs[i].classList.remove("active"); }
                document.getElementById(tabId).style.display = "block";
                if(event) { event.currentTarget.classList.add("active"); }
                localStorage.setItem("voc_staff_active_tab", tabId);
            }

            document.addEventListener("DOMContentLoaded", function() {
                var activeTab = localStorage.getItem("voc_staff_active_tab");
                if (activeTab && document.getElementById(activeTab)) {
                    voc_switch_staff_tab(null, activeTab);
                }
            });
        </script>
        ';
    }
}