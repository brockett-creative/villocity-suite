<?php
/**
 * Module Name: VilloCity Business Directory (Admin)
 * Module URI:  https://villocity.org
 * Description: Backend administration, meta boxes, visual icon picker, metrics stats grid, time-framed analytics tab, individual listing views/clicks, WP Dashboard At-A-Glance widget, and VilloCity Hub Directory Dashboard with live header search, unified action pill buttons, and permanent trash purge tools.
 * Version:     1.6.0 (Permanent Trash Delete Tools & Live Table Search)
 * Author:      Brockett Creative Group, Inc.
 * Author URI:  https://brockettcreative.com
 * Text Domain: villocity-suite
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

// ==========================================
// 1. ADMIN MEDIA SCRIPTS & ICON PICKER
// ==========================================
if ( ! function_exists( 'bcg_directory_admin_scripts' ) ) {
    add_action('admin_enqueue_scripts', 'bcg_directory_admin_scripts');
    function bcg_directory_admin_scripts($hook) {
        if ( strpos($hook, 'bcg-directory-hub') !== false ) {
            wp_enqueue_media();
        }
        if ( isset($_GET['taxonomy']) && $_GET['taxonomy'] === 'bcg_business_category' ) {
            wp_enqueue_style( 'font-awesome-free', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css', array(), '6.4.0' );
        }
    }
}

if ( ! function_exists( 'bcg_get_curated_fa_icons' ) ) {
    function bcg_get_curated_fa_icons() {
        return array(
            'fas fa-tag', 'fas fa-utensils', 'fas fa-bed', 'fas fa-shirt', 'fas fa-guitar', 'fas fa-graduation-cap', 
            'fas fa-church', 'fas fa-gift', 'fas fa-shopping-cart', 'fas fa-leaf', 'fas fa-tools', 'fas fa-house', 
            'fas fa-building', 'fas fa-car', 'fas fa-paw', 'fas fa-coffee', 'fas fa-hamburger', 'fas fa-pizza-slice', 
            'fas fa-wine-glass', 'fas fa-beer', 'fas fa-book', 'fas fa-briefcase', 'fas fa-stethoscope', 'fas fa-heart', 
            'fas fa-tooth', 'fas fa-tree', 'fas fa-dumbbell', 'fas fa-bicycle', 'fas fa-palette', 'fas fa-camera', 
            'fas fa-scissors', 'fas fa-spa', 'fas fa-tractor', 'fas fa-seedling', 'fas fa-store', 'fas fa-cash-register', 
            'fas fa-landmark', 'fas fa-scale-balanced', 'fas fa-gavel', 'fas fa-users', 'fas fa-child', 'fas fa-wheelchair', 
            'fas fa-gas-pump', 'fas fa-truck', 'fas fa-bus', 'fas fa-motorcycle', 'fas fa-film', 'fas fa-ticket', 
            'fas fa-music', 'fas fa-campground', 'fas fa-fire', 'fas fa-water', 'fas fa-umbrella-beach', 'fas fa-wrench'
        );
    }
}

if ( ! function_exists( 'bcg_render_visual_icon_picker' ) ) {
    function bcg_render_visual_icon_picker($current_icon = '') {
        $icons = bcg_get_curated_fa_icons();
        $primary_col = get_option('villocity_global_primary', '#860038');
        
        echo '<style>
            .voc-icon-picker-wrap { margin-top: 8px; border: 1px solid #cbd5e1; padding: 12px; border-radius: 8px; background: #f8fafc; max-width: 400px; }
            .voc-icon-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(36px, 1fr)); gap: 8px; max-height: 200px; overflow-y: auto; padding-right: 8px; margin-top: 12px; }
            .voc-icon-option { height: 36px; background: #fff; border: 1px solid #e2e8f0; border-radius: 6px; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: 0.2s; color: #475569; font-size: 16px; }
            .voc-icon-option:hover { border-color: ' . esc_attr($primary_col) . '; color: ' . esc_attr($primary_col) . '; }
            .voc-icon-option.selected { background: ' . esc_attr($primary_col) . '; color: #fff; border-color: ' . esc_attr($primary_col) . '; }
            .voc-icon-input { width: 100%; max-width: 400px; padding: 8px; }
        </style>';

        echo '<input type="text" name="bcg_cat_icon" id="bcg_cat_icon" value="' . esc_attr($current_icon) . '" class="voc-icon-input" placeholder="e.g. fas fa-utensils">';
        
        echo '<div class="voc-icon-picker-wrap">';
        echo '<div style="font-size:12px; font-weight:600; color:#64748b;">Quick Select:</div>';
        echo '<div class="voc-icon-grid">';
        foreach($icons as $icon) {
            $selected_class = ($current_icon === $icon) ? ' selected' : '';
            echo '<div class="voc-icon-option' . $selected_class . '" data-icon="' . esc_attr($icon) . '" title="' . esc_attr($icon) . '"><i class="' . esc_attr($icon) . '"></i></div>';
        }
        echo '</div></div>';

        echo '<script>
            document.addEventListener("DOMContentLoaded", function() {
                const options = document.querySelectorAll(".voc-icon-option");
                const input = document.getElementById("bcg_cat_icon");
                if (!input) return;
                
                options.forEach(opt => {
                    opt.addEventListener("click", function() {
                        options.forEach(o => o.classList.remove("selected"));
                        this.classList.add("selected");
                        input.value = this.getAttribute("data-icon");
                    });
                });

                input.addEventListener("input", function() {
                    const val = this.value.trim();
                    options.forEach(o => {
                        if(o.getAttribute("data-icon") === val) { o.classList.add("selected"); }
                        else { o.classList.remove("selected"); }
                    });
                });
            });
        </script>';
    }
}

add_action( 'bcg_business_category_add_form_fields', function() {
    echo '<div class="form-field"><label for="bcg_cat_icon">Category Icon</label>';
    bcg_render_visual_icon_picker();
    echo '<p>Click an icon to select it, or type a custom Font Awesome class manually.</p></div>';
});

add_action( 'bcg_business_category_edit_form_fields', function($term) {
    $current_icon = get_term_meta( $term->term_id, '_bcg_cat_icon', true );
    echo '<tr class="form-field"><th scope="row" valign="top"><label for="bcg_cat_icon">Category Icon</label></th><td>';
    bcg_render_visual_icon_picker($current_icon);
    echo '<p class="description">Click an icon to select it, or type a custom Font Awesome class manually.</p></td></tr>';
});

add_action( 'created_bcg_business_category', 'bcg_dir_category_save_icon_meta' );
add_action( 'edited_bcg_business_category', 'bcg_dir_category_save_icon_meta' );
function bcg_dir_category_save_icon_meta( $term_id ) {
    if ( isset( $_POST['bcg_cat_icon'] ) ) {
        update_term_meta( $term_id, '_bcg_cat_icon', sanitize_text_field( $_POST['bcg_cat_icon'] ) );
    }
}

// ==========================================
// 2. BACKEND META BOXES
// ==========================================
if ( ! function_exists( 'bcg_directory_add_meta_boxes' ) ) {
    add_action( 'add_meta_boxes', 'bcg_directory_add_meta_boxes' );
    function bcg_directory_add_meta_boxes() {
        add_meta_box(
            'bcg_directory_meta_box',
            (function_exists('villocity_get_icon') ? villocity_get_icon('store') : '') . ' Business Details',
            'bcg_directory_render_meta_box',
            'bcg_directory',
            'normal',
            'high'
        );
    }
}

if ( ! function_exists( 'bcg_directory_render_meta_box' ) ) {
    function bcg_directory_render_meta_box( $post ) {
        wp_nonce_field( 'bcg_directory_save_meta', 'bcg_directory_meta_nonce' );

        $category = str_replace(array('&amp;', '&#038;'), '&', wp_specialchars_decode(get_post_meta( $post->ID, '_business_category', true ), ENT_QUOTES));
        $location = str_replace(array('&amp;', '&#038;'), '&', wp_specialchars_decode(get_post_meta( $post->ID, '_business_location', true ), ENT_QUOTES));
        
        $address   = get_post_meta( $post->ID, '_business_address', true );
        $phone     = get_post_meta( $post->ID, '_business_phone', true );
        $website   = get_post_meta( $post->ID, '_business_website_url', true );
        $social    = get_post_meta( $post->ID, '_business_social_link', true );
        $email     = get_post_meta( $post->ID, '_business_email', true );
        $hours     = get_post_meta( $post->ID, '_business_hours', true );
        $tags      = get_post_meta( $post->ID, '_business_tags', true );
        $prod_serv = get_post_meta( $post->ID, '_business_product_service', true );
        $add_info  = get_post_meta( $post->ID, '_business_additional_info', true );
        $google_l  = get_post_meta( $post->ID, '_business_google_link', true );
        
        $loc_owned = get_post_meta( $post->ID, '_business_locally_owned', true );
        $time_yr   = get_post_meta( $post->ID, '_business_time_of_year', true );
        $seasonal  = get_post_meta( $post->ID, '_business_seasonal_dates', true );
        
        $featured  = get_post_meta( $post->ID, '_business_featured', true );
        $verified  = get_post_meta( $post->ID, '_business_verified', true );

        echo '<div style="display:flex; flex-wrap:wrap; gap:20px; padding:10px 0;">';
        
        // Col 1
        echo '<div style="flex:1; min-width:250px;">';
        echo '<p style="margin-top:0;"><strong>' . (function_exists('villocity_get_icon') ? villocity_get_icon('tag') : '') . ' Category</strong><br>';
        echo '<input type="text" name="bcg_business_category" value="' . esc_attr($category) . '" style="width:100%; max-width:100%;" placeholder="e.g. Dining & Restaurants"></p>';

        echo '<p><strong>' . (function_exists('villocity_get_icon') ? villocity_get_icon('map-pin') : '') . ' Municipality / Location</strong><br>';
        echo '<input type="text" name="bcg_business_location" value="' . esc_attr($location) . '" style="width:100%; max-width:100%;" placeholder="e.g. Clinton, NY"></p>';

        echo '<p><strong>' . (function_exists('villocity_get_icon') ? villocity_get_icon('bag') : '') . ' Products / Services</strong><br>';
        echo '<textarea name="bcg_business_product_service" rows="2" style="width:100%; max-width:100%;">'.esc_textarea($prod_serv).'</textarea></p>';

        echo '<p><strong>' . (function_exists('villocity_get_icon') ? villocity_get_icon('search') : '') . ' Tags / Keywords</strong><br>';
        echo '<input type="text" name="bcg_business_tags" value="'.esc_attr($tags).'" style="width:100%; max-width:100%;" placeholder="e.g. coffee, accounting, boutique"></p>';
        echo '</div>';

        // Col 2
        echo '<div style="flex:1; min-width:250px;">';
        echo '<p style="margin-top:0;"><strong>Time of Year</strong><br>';
        echo '<select name="bcg_business_time_of_year" style="width:100%; max-width:100%;">';
        echo '<option value="All Year" ' . selected($time_yr, 'All Year', false) . '>All Year</option>';
        echo '<option value="Seasonal" ' . selected($time_yr, 'Seasonal', false) . '>Seasonal</option>';
        echo '</select></p>';

        echo '<p><strong>Seasonal Dates (If applicable)</strong><br>';
        echo '<input type="text" name="bcg_business_seasonal_dates" value="'.esc_attr($seasonal).'" style="width:100%; max-width:100%;" placeholder="e.g. May through October"></p>';

        echo '<p><strong>' . (function_exists('villocity_get_icon') ? villocity_get_icon('clock') : '') . ' Hours of Operation</strong><br>';
        echo '<input type="text" name="bcg_business_hours" value="'.esc_attr($hours).'" style="width:100%; max-width:100%;" placeholder="e.g. Mon-Fri 9am-5pm"></p>';
        
        echo '<p><strong>' . (function_exists('villocity_get_icon') ? villocity_get_icon('map-pin') : '') . ' Physical Address</strong><br>';
        echo '<input type="text" name="bcg_business_address" value="'.esc_attr($address).'" style="width:100%; max-width:100%;" placeholder="e.g., 123 Main St, Clinton, NY"></p>';
        
        echo '<p><strong>' . (function_exists('villocity_get_icon') ? villocity_get_icon('phone') : '') . ' Phone Number</strong><br>';
        echo '<input type="text" name="bcg_business_phone" value="'.esc_attr($phone).'" style="width:100%; max-width:100%;"></p>';
        echo '</div>';

        // Col 3
        echo '<div style="flex:1; min-width:250px;">';
        echo '<p style="margin-top:0;"><strong>' . (function_exists('villocity_get_icon') ? villocity_get_icon('globe') : '') . ' Website URL</strong><br>';
        echo '<input type="url" name="bcg_business_website_url" value="'.esc_attr($website).'" style="width:100%; max-width:100%;" placeholder="https://..."></p>';
        
        echo '<p><strong>' . (function_exists('villocity_get_icon') ? villocity_get_icon('globe') : '') . ' Social Media Link</strong><br>';
        echo '<input type="url" name="bcg_business_social_link" value="'.esc_attr($social).'" style="width:100%; max-width:100%;" placeholder="https://facebook.com/..."></p>';

        echo '<p><strong>' . (function_exists('villocity_get_icon') ? villocity_get_icon('store') : '') . ' Google Business Profile Link</strong><br>';
        echo '<input type="url" name="bcg_business_google_link" value="'.esc_attr($google_l).'" style="width:100%; max-width:100%;"></p>';

        echo '<p><strong>' . (function_exists('villocity_get_icon') ? villocity_get_icon('mail') : '') . ' Owner Email</strong> (Hidden)<br>';
        echo '<input type="email" name="bcg_business_email" value="'.esc_attr($email).'" style="width:100%; max-width:100%;"></p>';
        
        echo '<p><strong>Additional Info</strong><br>';
        echo '<textarea name="bcg_business_additional_info" rows="2" style="width:100%; max-width:100%;">'.esc_textarea($add_info).'</textarea></p>';
        echo '</div>';

        // Admin Toggles
        echo '<div style="flex:1; min-width:250px; background:#f0f9ff; padding:15px; border-left:4px solid #3b82f6; border-radius:4px;">';
        echo '<p style="margin-top:0;"><label><input type="checkbox" name="bcg_business_verified" value="yes" '.checked($verified, 'yes', false).'> <strong style="color:#1e40af;">Verified Business</strong></label></p>';
        echo '<p style="color:#1e40af; font-size:13px; margin-bottom:16px;">Places a "Verified" pill over the logo.</p>';
        
        echo '<p style="margin-top:0;"><label><input type="checkbox" name="bcg_business_locally_owned" value="yes" '.checked($loc_owned, 'yes', false).'> <strong style="color:#1e40af;">Locally Owned</strong></label></p>';
        echo '<p style="color:#1e40af; font-size:13px; margin-bottom:16px;">Highlights this business as locally owned.</p>';

        echo '<p style="margin-top:0;"><label><input type="checkbox" name="bcg_business_featured" value="yes" '.checked($featured, 'yes', false).'> <strong style="color:#1e40af;">Featured Business</strong></label></p>';
        echo '<p style="color:#1e40af; font-size:13px; margin-bottom:0;">Pins the business to the top with direct contact info.</p>';
        echo '</div></div>'; 
    }
}

if ( ! function_exists( 'bcg_directory_save_meta_box_data' ) ) {
    add_action( 'save_post_bcg_directory', 'bcg_directory_save_meta_box_data' );
    function bcg_directory_save_meta_box_data( $post_id ) {
        if ( ! isset( $_POST['bcg_directory_meta_nonce'] ) || ! wp_verify_nonce( $_POST['bcg_directory_meta_nonce'], 'bcg_directory_save_meta' ) ) return;
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
        if ( ! current_user_can( 'edit_post', $post_id ) ) return;

        update_post_meta( $post_id, '_business_category', sanitize_text_field( $_POST['bcg_business_category'] ) );
        update_post_meta( $post_id, '_business_location', sanitize_text_field( $_POST['bcg_business_location'] ) );
        update_post_meta( $post_id, '_business_tags', sanitize_text_field( $_POST['bcg_business_tags'] ) );
        update_post_meta( $post_id, '_business_address', sanitize_text_field( $_POST['bcg_business_address'] ) );
        update_post_meta( $post_id, '_business_phone', sanitize_text_field( $_POST['bcg_business_phone'] ) );
        update_post_meta( $post_id, '_business_hours', sanitize_text_field( $_POST['bcg_business_hours'] ) );
        update_post_meta( $post_id, '_business_email', sanitize_email( $_POST['bcg_business_email'] ) );
        update_post_meta( $post_id, '_business_website_url', esc_url_raw( $_POST['bcg_business_website_url'] ) );
        update_post_meta( $post_id, '_business_product_service', sanitize_textarea_field( $_POST['bcg_business_product_service'] ) );
        update_post_meta( $post_id, '_business_additional_info', sanitize_textarea_field( $_POST['bcg_business_additional_info'] ) );
        update_post_meta( $post_id, '_business_google_link', esc_url_raw( $_POST['bcg_business_google_link'] ) );
        update_post_meta( $post_id, '_business_social_link', esc_url_raw( $_POST['bcg_business_social_link'] ) );
        update_post_meta( $post_id, '_business_time_of_year', sanitize_text_field( $_POST['bcg_business_time_of_year'] ) );
        update_post_meta( $post_id, '_business_seasonal_dates', sanitize_text_field( $_POST['bcg_business_seasonal_dates'] ) );

        update_post_meta( $post_id, '_business_featured', isset($_POST['bcg_business_featured']) ? 'yes' : 'no' );
        update_post_meta( $post_id, '_business_locally_owned', isset($_POST['bcg_business_locally_owned']) ? 'yes' : 'no' );
        update_post_meta( $post_id, '_business_verified', isset($_POST['bcg_business_verified']) ? 'yes' : 'no' );
        
        if ( isset($_POST['bcg_business_category']) && ! empty($_POST['bcg_business_category']) ) {
            wp_set_object_terms( $post_id, sanitize_text_field( $_POST['bcg_business_category'] ), 'bcg_business_category' );
        }
    }
}

// ==========================================
// 3. ADMIN MENU & DASHBOARD ROUTING
// ==========================================
if ( ! function_exists( 'bcg_directory_add_menu' ) ) {
    add_action( 'admin_menu', 'bcg_directory_add_menu', 20 );
    function bcg_directory_add_menu() {
        if ( menu_page_url( 'villocity-hub', false ) ) {
            add_submenu_page('villocity-hub', 'Directory Hub', 'Directory Hub', 'manage_options', 'bcg-directory-hub', 'bcg_render_directory_dashboard');
            add_submenu_page('villocity-hub', 'Directory Categories', 'Directory Categories', 'manage_options', 'edit-tags.php?taxonomy=bcg_business_category&post_type=bcg_directory');
        }
    }
}

if ( ! function_exists( 'bcg_render_directory_dashboard' ) ) {
    function bcg_render_directory_dashboard() {
        if ( ! current_user_can( 'manage_options' ) ) return;

        $message = '';

        if ( isset( $_POST['bcg_action_nonce'] ) && wp_verify_nonce( $_POST['bcg_action_nonce'], 'bcg_directory_action' ) ) {
            if ( isset($_POST['bulk_action']) && !empty($_POST['bulk_action']) && !empty($_POST['business_ids']) ) {
                $action = sanitize_text_field($_POST['bulk_action']);
                $ids = array_map('intval', $_POST['business_ids']);
                $count = count($ids);

                if ( $action === 'trash' ) { foreach($ids as $i) wp_trash_post($i); $message = '<div class="voc-gemini-notice" style="background:#fce8e6; border-color:#fad2cf; color:#b3261e;"><h3>Items Trashed</h3><p style="margin:0;">' . $count . ' profiles moved to trash.</p></div>'; }
                elseif ( $action === 'restore' ) { foreach($ids as $i) wp_untrash_post($i); $message = '<div class="voc-gemini-notice"><h3>Items Restored</h3><p style="margin:0;">' . $count . ' profiles restored.</p></div>'; }
                elseif ( $action === 'delete_permanent' ) { foreach($ids as $i) wp_delete_post($i, true); $message = '<div class="voc-gemini-notice" style="background:#fce8e6; border-color:#fad2cf; color:#b3261e;"><h3>Items Deleted Permanently</h3><p style="margin:0;">' . $count . ' profiles permanently deleted.</p></div>'; }
                elseif ( $action === 'approve' ) { foreach($ids as $i) wp_update_post(array('ID'=>$i, 'post_status'=>'publish')); $message = '<div class="voc-gemini-notice"><h3>Items Approved</h3><p style="margin:0;">' . $count . ' profiles published.</p></div>'; }
            }

            if ( isset( $_POST['single_action'] ) ) {
                $parts = explode('_', sanitize_text_field($_POST['single_action']));
                $act = $parts[0]; $id = intval(end($parts));

                if ( $act === 'approve' ) { wp_update_post( array( 'ID' => $id, 'post_status' => 'publish' ) ); $message = '<div class="voc-gemini-notice"><h3>Approved</h3></div>'; }
                elseif ( $act === 'trash' ) { wp_trash_post( $id ); $message = '<div class="voc-gemini-notice" style="background:#fce8e6; border-color:#fad2cf; color:#b3261e;"><h3>Trashed</h3></div>'; }
                elseif ( $act === 'restore' ) { wp_untrash_post( $id ); $message = '<div class="voc-gemini-notice"><h3>Restored</h3></div>'; }
                elseif ( $act === 'delete' ) { wp_delete_post( $id, true ); $message = '<div class="voc-gemini-notice" style="background:#fce8e6; border-color:#fad2cf; color:#b3261e;"><h3>Deleted Permanently</h3></div>'; }
            }

            if ( isset( $_POST['action_save_settings'] ) ) {
                update_option( 'bcg_directory_default_image', esc_url_raw( $_POST['bcg_directory_default_image'] ) );
                if ( isset( $_POST['bcg_directory_ad_top'] ) ) { update_option( 'bcg_directory_ad_top', wp_unslash($_POST['bcg_directory_ad_top']) ); }
                if ( isset( $_POST['bcg_directory_ad_bottom'] ) ) { update_option( 'bcg_directory_ad_bottom', wp_unslash($_POST['bcg_directory_ad_bottom']) ); }
                if ( isset( $_POST['bcg_directory_ad_grid'] ) ) { update_option( 'bcg_directory_ad_grid', wp_unslash($_POST['bcg_directory_ad_grid']) ); }

                $message = '<div class="voc-gemini-notice"><h3>Settings Saved</h3></div>';
            }
        }

        $primary_col = get_option('villocity_global_primary', '#860038');
        $accent_col  = get_option('villocity_global_accent', '#fadceb');

        $pending_query = new WP_Query( array( 'post_type' => 'bcg_directory', 'post_status' => 'pending', 'posts_per_page' => -1 ) );
        $active_query  = new WP_Query( array( 'post_type' => 'bcg_directory', 'post_status' => 'publish', 'posts_per_page' => -1, 'orderby' => 'title', 'order' => 'ASC' ) );
        $trash_query   = new WP_Query( array( 'post_type' => 'bcg_directory', 'post_status' => 'trash', 'posts_per_page' => -1 ) );

        $categories_count = wp_count_terms( array( 'taxonomy' => 'bcg_business_category', 'hide_empty' => false ) );
        if ( is_wp_error( $categories_count ) ) { $categories_count = 0; }

        $local_owned_query = new WP_Query( array(
            'post_type'      => 'bcg_directory',
            'post_status'    => 'publish',
            'meta_key'       => '_business_locally_owned',
            'meta_value'     => 'yes',
            'posts_per_page' => -1,
            'fields'         => 'ids'
        ) );
        $local_owned_count = $local_owned_query->found_posts;

        echo '<div class="voc-gemini-dashboard">' . $message;
        if (function_exists('villocity_render_dashboard_header')) {
            villocity_render_dashboard_header( 'Business Directory Hub', 'Review business profiles, manage categories, locations, and layout.', 'store', admin_url('post-new.php?post_type=bcg_directory') );
        }

        // Add inline CSS for unified action buttons
        echo '<style>
            .voc-action-group {
                display: inline-flex;
                align-items: center;
                gap: 6px;
            }
            .voc-btn-trash-pill {
                background: #fef2f2 !important;
                color: #991b1b !important;
                border: 1px solid #fca5a5 !important;
                border-radius: 20px !important;
                padding: 6px 16px !important;
                font-size: 13px !important;
                font-weight: 600 !important;
                text-decoration: none !important;
                display: inline-flex;
                align-items: center;
                gap: 4px;
                transition: all 0.2s ease;
                cursor: pointer;
            }
            .voc-btn-trash-pill:hover {
                background: #fee2e2 !important;
                color: #7f1d1d !important;
                border-color: #f87171 !important;
            }
        </style>';

        // ==========================================
        // DIRECTORY METRICS STATS GRID
        // ==========================================
        echo '<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 16px; margin: 24px 0 28px 0;">';

        echo '<div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.02);">
            <div style="font-size: 11px; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 6px;">Active Listings</div>
            <div style="font-size: 28px; font-weight: 800; color: ' . esc_attr($primary_col) . '; line-height: 1;">' . number_format($active_query->post_count) . '</div>
            <div style="font-size: 12px; color: #94a3b8; margin-top: 6px;">Published Directory Profiles</div>
        </div>';

        echo '<div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.02);">
            <div style="font-size: 11px; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 6px;">Categories</div>
            <div style="font-size: 28px; font-weight: 800; color: #0f172a; line-height: 1;">' . number_format($categories_count) . '</div>
            <div style="font-size: 12px; color: #94a3b8; margin-top: 6px;">Industry Classifications</div>
        </div>';

        echo '<div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.02);">
            <div style="font-size: 11px; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 6px;">Locally Owned</div>
            <div style="font-size: 28px; font-weight: 800; color: #166534; line-height: 1;">' . number_format($local_owned_count) . '</div>
            <div style="font-size: 12px; color: #94a3b8; margin-top: 6px;">Community Merchant Flag</div>
        </div>';

        echo '<div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.02);">
            <div style="font-size: 11px; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 6px;">Pending Review</div>
            <div style="font-size: 28px; font-weight: 800; color: ' . ($pending_query->post_count > 0 ? '#b45309' : '#0f172a') . '; line-height: 1;">' . number_format($pending_query->post_count) . '</div>
            <div style="font-size: 12px; color: #94a3b8; margin-top: 6px;">' . ($pending_query->post_count > 0 ? 'Awaiting Merchant Approval' : 'No Submissions Pending') . '</div>
        </div>';

        echo '</div>'; // End Stats Grid

        // TABS NAVIGATION
        echo '
        <div class="voc-gemini-tabs">
            <button type="button" class="voc-gemini-tab active" onclick="voc_switch_tab(event, \'voc-tab-active\')">' . (function_exists('villocity_get_icon') ? villocity_get_icon('check') : '✓') . ' Active (' . $active_query->post_count . ')</button>
            <button type="button" class="voc-gemini-tab" onclick="voc_switch_tab(event, \'voc-tab-analytics\')">' . (function_exists('villocity_get_icon') ? villocity_get_icon('trending-up') : '📈') . ' Analytics & Insights</button>
            <button type="button" class="voc-gemini-tab" onclick="voc_switch_tab(event, \'voc-tab-shortcodes\')">' . (function_exists('villocity_get_icon') ? villocity_get_icon('check') : '✓') . ' Shortcodes</button>
            <button type="button" class="voc-gemini-tab" onclick="voc_switch_tab(event, \'voc-tab-pending\')">' . (function_exists('villocity_get_icon') ? villocity_get_icon('clock') : '🕒') . ' Pending (' . $pending_query->post_count . ')</button>
            <button type="button" class="voc-gemini-tab" onclick="voc_switch_tab(event, \'voc-tab-trash\')">' . (function_exists('villocity_get_icon') ? villocity_get_icon('trash') : '🗑') . ' Trash (' . $trash_query->post_count . ')</button>
            <button type="button" class="voc-gemini-tab" onclick="voc_switch_tab(event, \'voc-tab-settings\')">' . (function_exists('villocity_get_icon') ? villocity_get_icon('settings') : '⚙') . ' Settings & Migration</button>
        </div>';

        // TAB 1: ACTIVE LISTINGS WITH SEARCH DATA & UNIFIED BUTTONS
        echo '
        <div id="voc-tab-active" class="voc-tab-content" style="display: block;">
            <div class="voc-gemini-card" style="padding: 0;">
                <form method="post">
                    ' . wp_nonce_field( 'bcg_directory_action', 'bcg_action_nonce', true, false ) . '
                    <table class="voc-gemini-data-table" id="voc-directory-admin-table">
                        <thead>
                            <tr>
                                <th style="width:30px;"><input type="checkbox" onchange="voc_toggle_all(this, \'chk-active\')"></th>
                                <th>Business Name</th>
                                <th>Category</th>
                                <th>' . (function_exists('villocity_get_icon') ? villocity_get_icon('eye') : '👁') . ' Total Views</th>
                                <th>' . (function_exists('villocity_get_icon') ? villocity_get_icon('pointer') : '🖱') . ' Total Clicks</th>
                                <th style="text-align:right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>';
                        if ( $active_query->have_posts() ) {
                            while ( $active_query->have_posts() ) {
                                $active_query->the_post();
                                $id       = get_the_ID();
                                $title    = get_the_title();
                                $cat_text = get_post_meta($id, '_business_category', true);
                                $views    = (int) get_post_meta( $id, '_business_views', true );
                                $clicks   = (int) get_post_meta( $id, '_business_clicks', true );

                                $search_data = esc_attr( strtolower( $title . ' ' . $cat_text ) );

                                echo '<tr class="voc-dir-row" data-search="' . $search_data . '">
                                    <td><input type="checkbox" name="business_ids[]" value="' . $id . '" class="chk-active"></td>
                                    <td><strong><a href="' . esc_url(get_permalink($id)) . '" target="_blank" style="text-decoration:none; color:' . esc_attr($primary_col) . ';">' . esc_html($title) . '</a></strong></td>
                                    <td><span style="background:' . esc_attr($accent_col) . '; color:' . esc_attr($primary_col) . '; padding:4px 10px; border-radius:12px; font-size:12px;">' . esc_html($cat_text) . '</span></td>
                                    <td><strong style="color:' . esc_attr($primary_col) . '; background:' . esc_attr($accent_col) . '; padding:4px 12px; border-radius:16px;">' . $views . ' views</strong></td>
                                    <td><strong style="color:#15803d; background:#dcfce7; padding:4px 12px; border-radius:16px;">' . $clicks . ' clicks</strong></td>
                                    <td style="text-align:right;">
                                        <div class="voc-action-group">
                                            <a href="' . esc_url(get_edit_post_link($id)) . '" class="voc-gemini-btn-secondary">Edit</a>
                                            <button type="submit" name="single_action" value="trash_' . $id . '" class="voc-btn-trash-pill" onclick="return confirm(\'Move this business to trash?\');">Trash</button>
                                        </div>
                                    </td>
                                </tr>';
                            }
                        } else { echo '<tr><td colspan="6" style="text-align:center; padding:40px;">No active profiles.</td></tr>'; }
        echo '          </tbody>
                    </table>
                </form>
            </div>
        </div>';

        // TAB 2: ANALYTICS & INSIGHTS
        $timeframe = isset($_GET['timeframe']) ? sanitize_text_field($_GET['timeframe']) : 'all_time';
        
        $analytics_query = new WP_Query(array(
            'post_type'      => 'bcg_directory',
            'posts_per_page' => 10,
            'post_status'    => 'publish',
            'meta_key'       => '_business_views',
            'orderby'        => 'meta_value_num',
            'order'          => 'DESC'
        ));

        echo '
        <div id="voc-tab-analytics" class="voc-tab-content" style="display: none;">
            <div class="voc-gemini-card">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; flex-wrap:wrap; gap:12px;">
                    <h3>' . (function_exists('villocity_get_icon') ? villocity_get_icon('trending-up') : '📈') . ' Top Performing Merchants</h3>
                    <form method="get" style="display:flex; gap:10px; align-items:center;">
                        <input type="hidden" name="page" value="bcg-directory-hub">
                        <select name="timeframe" class="voc-gemini-select" style="width:auto;" onchange="this.form.submit()">
                            <option value="all_time" ' . selected($timeframe, 'all_time', false) . '>All-Time Cumulative</option>
                            <option value="90_days" ' . selected($timeframe, '90_days', false) . '>Last 90 Days</option>
                            <option value="30_days" ' . selected($timeframe, '30_days', false) . '>Last 30 Days</option>
                            <option value="7_days" ' . selected($timeframe, '7_days', false) . '>Last 7 Days</option>
                        </select>
                    </form>
                </div>
                
                <table class="voc-gemini-data-table">
                    <thead>
                        <tr>
                            <th>Merchant Name</th>
                            <th>Category</th>
                            <th>' . (function_exists('villocity_get_icon') ? villocity_get_icon('eye') : '👁') . ' Total Views</th>
                            <th>' . (function_exists('villocity_get_icon') ? villocity_get_icon('pointer') : '🖱') . ' Website / Phone Clicks</th>
                        </tr>
                    </thead>
                    <tbody>';
                    if ( $analytics_query->have_posts() ) {
                        while ( $analytics_query->have_posts() ) {
                            $analytics_query->the_post();
                            $a_id = get_the_ID();
                            $a_views  = (int) get_post_meta( $a_id, '_business_views', true );
                            $a_clicks = (int) get_post_meta( $a_id, '_business_clicks', true );

                            echo '<tr>
                                <td><strong>' . esc_html(get_the_title()) . '</strong></td>
                                <td>' . esc_html(get_post_meta($a_id, '_business_category', true)) . '</td>
                                <td><strong style="color:' . esc_attr($primary_col) . ';">' . number_format($a_views) . ' views</strong></td>
                                <td><strong style="color:#166534;">' . number_format($a_clicks) . ' clicks</strong></td>
                            </tr>';
                        }
                        wp_reset_postdata();
                    } else {
                        echo '<tr><td colspan="4" style="text-align:center; padding:30px;">No analytics data available for this timeframe.</td></tr>';
                    }
        echo '      </tbody>
                </table>
            </div>
        </div>';

        // TAB 3: SHORTCODES TAB
        echo '
        <div id="voc-tab-shortcodes" class="voc-tab-content" style="display: none;">
            <div style="display: flex; gap: 16px; flex-wrap: wrap;">
                <div class="voc-gemini-card" style="flex: 1; min-width: 300px;">
                    <strong style="color: ' . esc_attr($primary_col) . '; display:block; margin-bottom:6px;">Submission Form Shortcode</strong>
                    <code style="display:inline-block; padding:8px 12px; background:#f1f5f9; border-radius:6px; font-size:14px; font-weight:700;">[villocity_submit_business]</code>
                    <p style="margin: 8px 0 0 0; font-size: 13px; color: #64748b;">Public front-end merchant onboarding form.</p>
                </div>
                <div class="voc-gemini-card" style="flex: 1; min-width: 300px;">
                    <strong style="color: ' . esc_attr($primary_col) . '; display:block; margin-bottom:6px;">Directory Grid Shortcode</strong>
                    <code style="display:inline-block; padding:8px 12px; background:#f1f5f9; border-radius:6px; font-size:14px; font-weight:700;">[villocity_business_directory]</code>
                    <p style="margin: 8px 0 0 0; font-size: 13px; color: #64748b;">Full categorized directory with search filters and merchant cards.</p>
                </div>
            </div>
        </div>';

        // TAB 4: PENDING
        echo '
        <div id="voc-tab-pending" class="voc-tab-content" style="display: none;">
            <div class="voc-gemini-card" style="padding: 0;">
                <form method="post">
                    ' . wp_nonce_field( 'bcg_directory_action', 'bcg_action_nonce', true, false ) . '
                    <table class="voc-gemini-data-table">
                        <thead><tr><th>Business Name</th><th>Submitted Category</th><th style="text-align:right;">Actions</th></tr></thead>
                        <tbody>';
                        if ( $pending_query->have_posts() ) {
                            while ( $pending_query->have_posts() ) {
                                $pending_query->the_post();
                                $id = get_the_ID();
                                echo '<tr>
                                    <td><strong>' . esc_html(get_the_title()) . '</strong></td>
                                    <td>' . esc_html(get_post_meta($id, '_business_category', true)) . '</td>
                                    <td style="text-align:right;">
                                        <button type="submit" name="single_action" value="approve_' . $id . '" class="voc-gemini-btn-primary">Approve & Publish</button>
                                    </td>
                                </tr>';
                            }
                        } else { echo '<tr><td colspan="3" style="text-align:center; padding:40px;">No pending submissions.</td></tr>'; }
        echo '          </tbody>
                    </table>
                </form>
            </div>
        </div>';

        // TAB 5: TRASH WITH PERMANENT DELETE & RESTORE
        echo '
        <div id="voc-tab-trash" class="voc-tab-content" style="display: none;">
            <div class="voc-gemini-card" style="padding: 0;">
                <form method="post">
                    ' . wp_nonce_field( 'bcg_directory_action', 'bcg_action_nonce', true, false ) . '
                    <table class="voc-gemini-data-table">
                        <thead>
                            <tr>
                                <th>Business Name</th>
                                <th style="text-align:right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>';
                        if ( $trash_query->have_posts() ) {
                            while ( $trash_query->have_posts() ) {
                                $trash_query->the_post();
                                $id = get_the_ID();
                                echo '<tr>
                                    <td><strong>' . esc_html(get_the_title()) . '</strong></td>
                                    <td style="text-align:right;">
                                        <div class="voc-action-group">
                                            <button type="submit" name="single_action" value="restore_' . $id . '" class="voc-gemini-btn-secondary">Restore</button>
                                            <button type="submit" name="single_action" value="delete_' . $id . '" class="voc-btn-trash-pill" onclick="return confirm(\'Permanently delete this business? This action CANNOT be undone.\');">Delete Forever</button>
                                        </div>
                                    </td>
                                </tr>';
                            }
                        } else { echo '<tr><td colspan="2" style="text-align:center; padding:40px;">Trash is empty.</td></tr>'; }
        echo '          </tbody>
                    </table>
                </form>
            </div>
        </div>';

        // TAB 6: SETTINGS
        echo '
        <div id="voc-tab-settings" class="voc-tab-content" style="display: none;">
            <div class="voc-gemini-card">
                <form method="post">
                    ' . wp_nonce_field( 'bcg_directory_action', 'bcg_action_nonce', true, false ) . '
                    <input type="hidden" name="action_save_settings" value="1">
                    <h3>' . (function_exists('villocity_get_icon') ? villocity_get_icon('settings') : '') . ' Ad Slots & Fallback Options</h3>
                    
                    <table class="voc-gemini-data-table" style="max-width:800px; margin-top:20px;">
                        <tr><th style="width:240px;">Default Listing Image URL</th><td><input type="url" name="bcg_directory_default_image" value="' . esc_attr( get_option('bcg_directory_default_image', '') ) . '" class="voc-gemini-input"></td></tr>
                        <tr><th>Top Ad Content (HTML/Shortcode)</th><td><textarea name="bcg_directory_ad_top" rows="2" class="voc-gemini-textarea">' . esc_textarea( get_option('bcg_directory_ad_top', '') ) . '</textarea></td></tr>
                        <tr><th>Bottom Ad Content (HTML/Shortcode)</th><td><textarea name="bcg_directory_ad_bottom" rows="2" class="voc-gemini-textarea">' . esc_textarea( get_option('bcg_directory_ad_bottom', '') ) . '</textarea></td></tr>
                        <tr><th>Homepage Grid Ad Content</th><td><textarea name="bcg_directory_ad_grid" rows="2" class="voc-gemini-textarea">' . esc_textarea( get_option('bcg_directory_ad_grid', '') ) . '</textarea></td></tr>
                    </table>

                    <div style="margin-top:20px;">
                        <button type="submit" class="voc-gemini-btn-primary">' . (function_exists('villocity_get_icon') ? villocity_get_icon('check') : '✓') . ' Save Directory Settings</button>
                    </div>
                </form>
            </div>
        </div>';

        echo '</div>'; // End Dashboard Container

        // REUSABLE TAB SWITCHING & LIVE HEADER SEARCH SCRIPT
        echo '<script>
        function voc_switch_tab(event, tabId) {
            if (event) { event.preventDefault(); }
            
            var contents = document.getElementsByClassName("voc-tab-content");
            for (var i = 0; i < contents.length; i++) {
                contents[i].style.display = "none";
            }
            
            var tabs = document.getElementsByClassName("voc-gemini-tab");
            for (var j = 0; j < tabs.length; j++) {
                tabs[j].classList.remove("active");
            }
            
            var targetContent = document.getElementById(tabId);
            if (targetContent) {
                targetContent.style.display = "block";
            }
            
            if (event && event.currentTarget) {
                event.currentTarget.classList.add("active");
            }
            
            localStorage.setItem("bcg_directory_active_tab", tabId);
        }

        function voc_toggle_all(source, className) {
            var checkboxes = document.getElementsByClassName(className);
            for (var i = 0; i < checkboxes.length; i++) {
                checkboxes[i].checked = source.checked;
            }
        }

        document.addEventListener("DOMContentLoaded", function() {
            var savedTab = localStorage.getItem("bcg_directory_active_tab");
            if (savedTab && document.getElementById(savedTab)) {
                voc_switch_tab(null, savedTab);
                var activeBtn = document.querySelector(".voc-gemini-tab[onclick*=\'" + savedTab + "\']");
                if (activeBtn) {
                    var tabs = document.getElementsByClassName("voc-gemini-tab");
                    for (var k = 0; k < tabs.length; k++) { tabs[k].classList.remove("active"); }
                    activeBtn.classList.add("active");
                }
            }

            // Real-Time Header Search Functionality
            const searchInput = document.getElementById("bcg-admin-search");
            const rows = document.querySelectorAll("#voc-directory-admin-table .voc-dir-row");

            if (searchInput && rows.length > 0) {
                searchInput.addEventListener("input", function() {
                    const term = this.value.toLowerCase().trim();

                    rows.forEach(row => {
                        const searchData = row.getAttribute("data-search") || "";
                        if (term === "" || searchData.includes(term)) {
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

// ==========================================
// 4. WORDPRESS DASHBOARD AT-A-GLANCE WIDGET
// ==========================================
add_action('wp_dashboard_setup', 'voc_register_directory_dashboard_widget');
function voc_register_directory_dashboard_widget() {
    wp_add_dashboard_widget(
        'voc_directory_dashboard_widget',
        'Business Directory - At a Glance',
        'voc_render_directory_dashboard_widget'
    );
}

function voc_render_directory_dashboard_widget() {
    $primary_col = get_option('villocity_global_primary', '#860038');
    
    $total_listings = wp_count_posts('bcg_directory')->publish;
    $pending_listings = wp_count_posts('bcg_directory')->pending;
    
    $popular_query = new WP_Query(array(
        'post_type'      => 'bcg_directory',
        'posts_per_page' => 3,
        'post_status'    => 'publish',
        'meta_key'       => '_business_views',
        'orderby'        => 'meta_value_num',
        'order'          => 'DESC'
    ));

    echo '<style>
        .voc-widget-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; margin-bottom: 16px; text-align: center; }
        .voc-widget-stat { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 12px 8px; }
        .voc-widget-num { font-size: 22px; font-weight: 800; color: ' . esc_attr($primary_col) . '; line-height: 1; margin-bottom: 4px; }
        .voc-widget-lbl { font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; }
        .voc-widget-list { list-style: none; padding: 0; margin: 0 0 16px 0; }
        .voc-widget-list li { display: flex; justify-content: space-between; font-size: 13px; font-weight: 600; padding: 6px 0; border-bottom: 1px solid #f1f5f9; }
        .voc-widget-btn { display: block; text-align: center; background: ' . esc_attr($primary_col) . '; color: #fff !important; text-decoration: none !important; font-weight: 700; padding: 10px; border-radius: 20px; font-size: 13px; }
    </style>';

    echo '<div class="voc-widget-grid">
        <div class="voc-widget-stat"><div class="voc-widget-num">' . number_format($total_listings) . '</div><div class="voc-widget-lbl">Active Listings</div></div>
        <div class="voc-widget-stat"><div class="voc-widget-num">' . number_format($pending_listings) . '</div><div class="voc-widget-lbl">Pending Review</div></div>
    </div>';

    echo '<strong style="display:block; margin-bottom:8px; font-size:12px; text-transform:uppercase; color:#64748b;">🏆 Most Viewed Merchants</strong>';
    echo '<ul class="voc-widget-list">';
    if ($popular_query->have_posts()) {
        while ($popular_query->have_posts()) {
            $popular_query->the_post();
            $views = (int) get_post_meta(get_the_ID(), '_business_views', true);
            echo '<li><span>' . esc_html(get_the_title()) . '</span><span style="color:' . esc_attr($primary_col) . '; font-weight:700;">' . $views . ' views</span></li>';
        }
        wp_reset_postdata();
    } else {
        echo '<li style="color:#94a3b8;">No merchant views recorded yet.</li>';
    }
    echo '</ul>';

    echo '<a href="' . admin_url('admin.php?page=bcg-directory-hub') . '" class="voc-widget-btn">Open Directory Hub &rarr;</a>';
}