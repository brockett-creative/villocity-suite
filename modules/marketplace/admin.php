<?php
/**
 * Module Name: VilloCity Live Marketplace (Admin)
 * Description: Backend meta boxes, WP dashboard widgets, shortcodes tab, 1-click migration, and Marketplace Hub settings.
 * Version:     2.3.2
 * Last Updated: September 10, 2026
 * Author:      Brockett Creative Group, Inc.
 * Author URI:  https://brockettcreative.com
 * Copyright:   © 2026 Brockett Creative Group, Inc. All Rights Reserved.
 * Text Domain: villocity-suite
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

// ==========================================
// 1. BACKEND META BOXES
// ==========================================
if ( ! function_exists( 'bcg_listings_add_meta_boxes' ) ) {
    add_action( 'add_meta_boxes', 'bcg_listings_add_meta_boxes' );
    function bcg_listings_add_meta_boxes() {
        add_meta_box(
            'bcg_listing_meta_box',
            villocity_get_icon('bag') . ' Listing Details',
            'bcg_listings_render_meta_box',
            'bcg_listing',
            'normal',
            'high'
        );
    }
}

if ( ! function_exists( 'bcg_listings_render_meta_box' ) ) {
    function bcg_listings_render_meta_box( $post ) {
        wp_nonce_field( 'bcg_listings_save_meta', 'bcg_listings_meta_nonce' );

        $type     = get_post_meta( $post->ID, '_listing_type', true );
        $category = get_post_meta( $post->ID, '_listing_category', true );
        $address  = get_post_meta( $post->ID, '_listing_address', true );
        $hours    = get_post_meta( $post->ID, '_listing_hours', true );
        $email    = get_post_meta( $post->ID, '_listing_email', true );
        $tags     = get_post_meta( $post->ID, '_listing_tags', true );
        $featured = get_post_meta( $post->ID, '_listing_featured', true );
        $website  = get_post_meta( $post->ID, '_listing_website_url', true );

        if(empty($type)) $type = 'Yard Sale';

        echo '<div style="display:flex; flex-wrap:wrap; gap:20px; padding:10px 0;">';
        
        echo '<div style="flex:1; min-width:250px;">';
        echo '<p><strong>' . villocity_get_icon('tag') . 'Listing Type</strong><br>';
        echo '<select name="bcg_listing_type" style="width:100%; max-width:100%;">';
        echo '<option value="Yard Sale" ' . selected($type, 'Yard Sale', false) . '>Yard / Garage Sale</option>';
        echo '<option value="Roadside Stand" ' . selected($type, 'Roadside Stand', false) . '>Roadside Stand / Farm</option>';
        echo '</select></p>';

        echo '<p><strong>' . villocity_get_icon('tag') . 'Category</strong><br>';
        echo '<select name="bcg_listing_category" style="width:100%; max-width:100%;">';
        if ( function_exists('bcg_listings_get_categories') ) {
            foreach(bcg_listings_get_categories() as $cat) {
                echo '<option value="'.esc_attr($cat).'" ' . selected($category, $cat, false) . '>'.esc_html($cat).'</option>';
            }
        }
        echo '</select></p>';

        echo '<p><strong>' . villocity_get_icon('search') . 'Tags / Keywords</strong><br>';
        echo '<input type="text" name="bcg_listing_tags" value="'.esc_attr($tags).'" style="width:100%; max-width:100%;" placeholder="e.g. tools, furniture, fresh corn"></p>';
        echo '</div>';

        echo '<div style="flex:1; min-width:250px;">';
        echo '<p style="margin-top:0;"><strong>' . villocity_get_icon('map-pin') . 'Address / Location</strong><br>';
        echo '<input type="text" name="bcg_listing_address" value="'.esc_attr($address).'" style="width:100%; max-width:100%;"></p>';
        echo '<p><strong>' . villocity_get_icon('clock') . 'Dates & Hours</strong><br>';
        echo '<input type="text" name="bcg_listing_hours" value="'.esc_attr($hours).'" style="width:100%; max-width:100%;" placeholder="e.g. Sat & Sun, 8am - 2pm"></p>';
        echo '<p><strong>' . villocity_get_icon('globe') . 'Website / Facebook Page URL</strong><br>';
        echo '<input type="url" name="bcg_listing_website_url" value="'.esc_attr($website).'" style="width:100%; max-width:100%;" placeholder="https://..."></p>';
        echo '<p><strong>' . villocity_get_icon('mail') . 'Submitter Email</strong> (Hidden)<br>';
        echo '<input type="email" name="bcg_listing_email" value="'.esc_attr($email).'" style="width:100%; max-width:100%;"></p>';
        echo '</div>';

        echo '<div style="flex:1; min-width:250px; background:#fdfae8; padding:15px; border-left:4px solid #f59e0b; border-radius:4px;">';
        echo '<p style="margin-top:0;"><label><input type="checkbox" name="bcg_listing_featured" value="yes" '.checked($featured, 'yes', false).'> <strong style="color:#92400e;">' . villocity_get_icon('star') . 'Featured Listing (Paid)</strong></label></p>';
        echo '<p style="color:#92400e; font-size:13px; margin-bottom:0;">Checking this expands the listing to a full-width featured card at the top of the marketplace.</p>';
        echo '</div>';

        echo '</div>'; 
    }
}

if ( ! function_exists( 'bcg_listings_save_meta_box_data' ) ) {
    add_action( 'save_post_bcg_listing', 'bcg_listings_save_meta_box_data' );
    function bcg_listings_save_meta_box_data( $post_id ) {
        if ( ! isset( $_POST['bcg_listings_meta_nonce'] ) || ! wp_verify_nonce( $_POST['bcg_listings_meta_nonce'], 'bcg_listings_save_meta' ) ) return;
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
        if ( ! current_user_can( 'edit_post', $post_id ) ) return;

        update_post_meta( $post_id, '_listing_type', sanitize_text_field( $_POST['bcg_listing_type'] ) );
        update_post_meta( $post_id, '_listing_category', sanitize_text_field( $_POST['bcg_listing_category'] ) );
        update_post_meta( $post_id, '_listing_tags', sanitize_text_field( $_POST['bcg_listing_tags'] ) );
        update_post_meta( $post_id, '_listing_address', sanitize_text_field( $_POST['bcg_listing_address'] ) );
        update_post_meta( $post_id, '_listing_hours', sanitize_text_field( $_POST['bcg_listing_hours'] ) );
        update_post_meta( $post_id, '_listing_email', sanitize_email( $_POST['bcg_listing_email'] ) );
        update_post_meta( $post_id, '_listing_website_url', esc_url_raw( $_POST['bcg_listing_website_url'] ) );
        
        $is_featured = isset($_POST['bcg_listing_featured']) ? 'yes' : 'no';
        update_post_meta( $post_id, '_listing_featured', $is_featured );
    }
}

// ==========================================
// 2. DASHBOARD MENU & TABS
// ==========================================
if ( ! function_exists( 'bcg_listings_add_menu' ) ) {
    add_action( 'admin_menu', 'bcg_listings_add_menu', 20 );
    function bcg_listings_add_menu() {
        if ( menu_page_url( 'villocity-hub', false ) ) {
            add_submenu_page(
                'villocity-hub',
                'Live Marketplace Settings',
                'Live Marketplace',
                'manage_options',
                'bcg-listings-hub',
                'bcg_render_listings_dashboard'
            );
        }
    }
}

if ( ! function_exists( 'bcg_render_listings_dashboard' ) ) {
    function bcg_render_listings_dashboard() {
        if ( ! current_user_can( 'manage_options' ) ) return;

        $message = '';

        if ( isset( $_POST['bcg_action_nonce'] ) && wp_verify_nonce( $_POST['bcg_action_nonce'], 'bcg_listings_action' ) ) {
            
            if ( isset( $_POST['action_save_settings'] ) ) {
                update_option( 'bcg_listings_promo_text', sanitize_text_field( $_POST['bcg_listings_promo_text'] ) );
                update_option( 'bcg_listings_stripe_url', esc_url_raw( $_POST['bcg_listings_stripe_url'] ) );
                update_option( 'bcg_listings_admin_email', sanitize_email( $_POST['bcg_listings_admin_email'] ) );
                update_option( 'bcg_listings_custom_categories', sanitize_text_field( $_POST['bcg_listings_custom_categories'] ) );
                
                $message = '<div class="voc-gemini-notice"><h3>' . villocity_get_icon('check') . ' Settings Saved</h3><p style="margin:0;">Marketplace settings updated successfully.</p></div>';
            }

            if ( isset( $_POST['action_approve_listing'] ) ) {
                $post_id = (int) $_POST['post_id'];
                wp_update_post( array( 'ID' => $post_id, 'post_status' => 'publish' ) );
                $message = '<div class="voc-gemini-notice"><h3>' . villocity_get_icon('check') . ' Listing Published</h3><p style="margin:0;">The listing is now live.</p></div>';
            }

            if ( isset( $_POST['action_trash_listing'] ) ) {
                $post_id = (int) $_POST['post_id'];
                wp_trash_post( $post_id );
                $message = '<div class="voc-gemini-notice" style="background:#fef2f2; border-color:#fecaca; color:#991b1b;"><h3>' . villocity_get_icon('trash') . ' Listing Trashed</h3><p style="margin:0;">The listing was moved to the trash bin.</p></div>';
            }
            
            if ( isset( $_POST['action_toggle_feature'] ) ) {
                $post_id = (int) $_POST['post_id'];
                $status = sanitize_text_field($_POST['feature_status']);
                update_post_meta( $post_id, '_listing_featured', $status );
                $message = '<div class="voc-gemini-notice"><h3>' . villocity_get_icon('star') . ' Listing Updated</h3><p style="margin:0;">The featured status has been updated successfully.</p></div>';
            }

            if ( isset( $_POST['action_resend_link'] ) ) {
                $post_id = (int) $_POST['post_id'];
                $post = get_post($post_id);
                $email = get_post_meta($post_id, '_listing_email', true);
                $token = get_post_meta($post_id, '_listing_edit_token', true);

                if ( empty($token) ) {
                    $token = wp_generate_password( 24, false, false );
                    update_post_meta( $post_id, '_listing_edit_token', $token );
                }

                if ( ! empty($email) ) {
                    $site_name = get_bloginfo('name');
                    $magic_link = add_query_arg( array('edit_id' => $post_id, 'token' => $token), home_url('/marketplace/') );
                    
                    $user_subject = 'Your Secure Edit Link: ' . $post->post_title;
                    $user_body = "Hello,\n\nAn admin at {$site_name} has resent your secure edit link for the listing: \"{$post->post_title}\".\n\n";
                    $user_body .= "You can securely manage your listing anytime using your private magic link below:\n\n";
                    $user_body .= $magic_link . "\n\n(Please do not share this link with anyone else).\n\nThank you,\nThe {$site_name} Team";
                    
                    wp_mail( $email, $user_subject, $user_body );
                    $message = '<div class="voc-gemini-notice"><h3>' . villocity_get_icon('mail') . ' Email Sent</h3><p style="margin:0;">The secure edit link has been successfully resent to ' . esc_html($email) . '.</p></div>';
                } else {
                    $message = '<div class="voc-gemini-notice" style="background:#fef2f2; border-color:#fecaca; color:#991b1b;"><h3>' . villocity_get_icon('alert') . ' Error</h3><p style="margin:0;">No email address found for this listing.</p></div>';
                }
            }
        }

        $promo_text = get_option( 'bcg_listings_promo_text', 'Launch Promo: All community listings are 100% FREE for our first 30 days!' );
        $stripe_url = get_option( 'bcg_listings_stripe_url', '' );
        $admin_email= get_option( 'bcg_listings_admin_email', get_option('admin_email') );
        
        $default_cats = 'General Household, Produce & Eggs, Baked Goods, Crafts & Hobbies, Plants & Garden, Furniture, Tools & Equipment';
        $custom_cats  = get_option( 'bcg_listings_custom_categories', $default_cats );
        
        $primary_col = get_option('villocity_global_primary', get_option('bcg_listings_primary_color', '#860038'));
        $accent_col  = get_option('villocity_global_accent', get_option('bcg_listings_accent_color', '#fadceb'));

        $pending_query = new WP_Query( array( 'post_type' => 'bcg_listing', 'post_status' => 'pending', 'posts_per_page' => -1 ) );
        $active_query  = new WP_Query( array( 'post_type' => 'bcg_listing', 'post_status' => 'publish', 'posts_per_page' => -1 ) );

        echo '<div class="voc-gemini-dashboard">' . $message;
        
        villocity_render_dashboard_header( 
            'Live Marketplace Hub', 
            'Review resident submissions, manage categories, Stripe flows, and analytics.', 
            'bag', 
            admin_url('post-new.php?post_type=bcg_listing') 
        );

        echo '
            <div class="voc-gemini-tabs" style="margin-top:24px;">
                <button class="voc-gemini-tab active" onclick="voc_switch_tab(event, \'voc-tab-pending\')">' . villocity_get_icon('clock') . ' Pending Approvals (' . $pending_query->post_count . ')</button>
                <button class="voc-gemini-tab" onclick="voc_switch_tab(event, \'voc-tab-active\')">' . villocity_get_icon('check') . ' Active Listings (' . $active_query->post_count . ')</button>
                <button class="voc-gemini-tab" onclick="voc_switch_tab(event, \'voc-tab-shortcodes\')">' . villocity_get_icon('check') . ' Shortcodes</button>
                <button class="voc-gemini-tab" onclick="voc_switch_tab(event, \'voc-tab-settings\')">' . villocity_get_icon('settings') . ' Settings & Categories</button>
            </div>

            <!-- TAB: PENDING -->
            <div id="voc-tab-pending" class="voc-tab-content" style="display: block;">
                <div class="voc-gemini-card" style="padding: 0;">
                    <table class="voc-gemini-data-table">
                        <thead><tr><th>Submitted</th><th>Details</th><th>Type / Category</th><th style="text-align:right;">Actions</th></tr></thead>
                        <tbody>';
                        if ( $pending_query->have_posts() ) {
                            while ( $pending_query->have_posts() ) {
                                $pending_query->the_post();
                                $id = get_the_ID();
                                echo '<tr>
                                    <td>' . get_the_date() . '<br><span style="font-size:12px; color:#64748b;">' . esc_html(get_post_meta($id, '_listing_email', true)) . '</span></td>
                                    <td><strong><a href="' . esc_url(get_permalink($id)) . '" target="_blank" style="text-decoration:none; color:' . esc_attr($primary_col) . ';">' . esc_html(get_the_title()) . '</a></strong><br><span style="font-size:12px; color:#64748b;">' . villocity_get_icon('map-pin') . ' ' . esc_html(get_post_meta($id, '_listing_address', true)) . '</span></td>
                                    <td><span style="background:' . esc_attr($accent_col) . '; color:' . esc_attr($primary_col) . '; padding:4px 10px; border-radius:12px; font-size:12px;">' . esc_html(get_post_meta($id, '_listing_type', true)) . '</span></td>
                                    <td style="text-align:right; white-space: nowrap;">
                                        <a href="' . esc_url(get_edit_post_link($id)) . '" class="voc-gemini-btn-secondary" style="margin-right:4px;">Edit</a>
                                        <form method="post" style="display:inline-block; margin-right:4px;" onsubmit="return confirm(\'Resend the secure edit link to the owner?\');">
                                            ' . wp_nonce_field( 'bcg_listings_action', 'bcg_action_nonce', true, false ) . '
                                            <input type="hidden" name="action_resend_link" value="1">
                                            <input type="hidden" name="post_id" value="' . $id . '">
                                            <input type="submit" class="voc-gemini-btn-secondary" value="Resend Link">
                                        </form>
                                        <form method="post" style="display:inline-block; margin-right:4px;">
                                            ' . wp_nonce_field( 'bcg_listings_action', 'bcg_action_nonce', true, false ) . '
                                            <input type="hidden" name="action_approve_listing" value="1">
                                            <input type="hidden" name="post_id" value="' . $id . '">
                                            <input type="submit" class="voc-gemini-btn-primary" style="background:#15803d !important;" value="Approve">
                                        </form>
                                        <form method="post" style="display:inline-block;">
                                            ' . wp_nonce_field( 'bcg_listings_action', 'bcg_action_nonce', true, false ) . '
                                            <input type="hidden" name="action_trash_listing" value="1">
                                            <input type="hidden" name="post_id" value="' . $id . '">
                                            <input type="submit" class="voc-gemini-btn-danger" value="Reject">
                                        </form>
                                    </td>
                                </tr>';
                            }
                        } else {
                            echo '<tr><td colspan="4" style="text-align:center; padding:40px; color:#64748b;">No pending submissions right now.</td></tr>';
                        }
        echo '          </tbody>
                    </table>
                </div>
            </div>

            <!-- TAB: ACTIVE -->
            <div id="voc-tab-active" class="voc-tab-content" style="display: none;">
                <div class="voc-gemini-card" style="padding: 0;">
                    <table class="voc-gemini-data-table">
                        <thead><tr><th>Details</th><th>Type / Category</th><th>Views</th><th>Map Clicks</th><th style="text-align:right;">Actions</th></tr></thead>
                        <tbody>';
                        if ( $active_query->have_posts() ) {
                            while ( $active_query->have_posts() ) {
                                $active_query->the_post();
                                $id = get_the_ID();
                                $views = (int) get_post_meta($id, '_listing_views', true);
                                $dirs  = (int) get_post_meta($id, '_listing_dir_clicks', true);
                                
                                $is_featured = get_post_meta($id, '_listing_featured', true) === 'yes';
                                $feat_action = $is_featured ? 'no' : 'yes';
                                $feat_label  = $is_featured ? 'Unfeature' : villocity_get_icon('star') . 'Feature';

                                echo '<tr>
                                    <td><strong><a href="' . esc_url(get_permalink($id)) . '" target="_blank" style="text-decoration:none; color:' . esc_attr($primary_col) . ';">' . esc_html(get_the_title()) . '</a></strong><br><span style="font-size:12px; color:#64748b;">' . esc_html(get_post_meta($id, '_listing_address', true)) . '</span></td>
                                    <td><span style="background:' . esc_attr($accent_col) . '; color:' . esc_attr($primary_col) . '; padding:4px 10px; border-radius:12px; font-size:12px;">' . esc_html(get_post_meta($id, '_listing_type', true)) . '</span></td>
                                    <td><strong style="color:' . esc_attr($primary_col) . '; background:' . esc_attr($accent_col) . '; padding:4px 12px; border-radius:16px;">' . $views . ' views</strong></td>
                                    <td><strong style="color:#15803d; background:#dcfce7; padding:4px 12px; border-radius:16px;">' . $dirs . ' clicks</strong></td>
                                    <td style="text-align:right; white-space:nowrap;">
                                        <form method="post" style="display:inline-block; margin-right:4px;">
                                            ' . wp_nonce_field( 'bcg_listings_action', 'bcg_action_nonce', true, false ) . '
                                            <input type="hidden" name="action_toggle_feature" value="1">
                                            <input type="hidden" name="post_id" value="' . $id . '">
                                            <input type="hidden" name="feature_status" value="' . $feat_action . '">
                                            <button type="submit" class="voc-gemini-btn-secondary" style="background:#fffbeb !important; color:#92400e !important; border:1px solid #fde68a !important;">' . $feat_label . '</button>
                                        </form>
                                        <a href="' . esc_url(get_edit_post_link($id)) . '" class="voc-gemini-btn-secondary" style="margin-right:4px;">Edit</a>
                                        <form method="post" style="display:inline-block; margin-right:4px;" onsubmit="return confirm(\'Resend the secure edit link to the owner?\');">
                                            ' . wp_nonce_field( 'bcg_listings_action', 'bcg_action_nonce', true, false ) . '
                                            <input type="hidden" name="action_resend_link" value="1">
                                            <input type="hidden" name="post_id" value="' . $id . '">
                                            <input type="submit" class="voc-gemini-btn-secondary" value="Resend Link">
                                        </form>
                                        <form method="post" style="display:inline-block;" onsubmit="return confirm(\'Remove this active listing?\');">
                                            ' . wp_nonce_field( 'bcg_listings_action', 'bcg_action_nonce', true, false ) . '
                                            <input type="hidden" name="action_trash_listing" value="1">
                                            <input type="hidden" name="post_id" value="' . $id . '">
                                            <input type="submit" class="voc-gemini-btn-danger" value="Trash">
                                        </form>
                                    </td>
                                </tr>';
                            }
                        } else {
                            echo '<tr><td colspan="5" style="text-align:center; padding:40px; color:#64748b;">No active listings on the site.</td></tr>';
                        }
        echo '          </tbody>
                    </table>
                </div>
            </div>

            <!-- TAB: SHORTCODES -->
            <div id="voc-tab-shortcodes" class="voc-tab-content" style="display: none;">
                <div style="display: flex; gap: 16px; flex-wrap: wrap;">
                    <div class="voc-gemini-card" style="flex: 1; min-width: 300px;">
                        <strong style="color: ' . esc_attr($primary_col) . '; display:block; margin-bottom:6px;">Submission Form Shortcode</strong>
                        <code style="display:inline-block; padding:8px 12px; background:#f1f5f9; border-radius:6px; font-size:14px; font-weight:700;">[villocity_submit_listing]</code>
                        <p style="margin: 8px 0 0 0; font-size: 13px; color: #64748b;">Embeds the public resident onboarding form directly onto any page.</p>
                    </div>
                    <div class="voc-gemini-card" style="flex: 1; min-width: 300px;">
                        <strong style="color: ' . esc_attr($primary_col) . '; display:block; margin-bottom:6px;">Public Feed Shortcode</strong>
                        <code style="display:inline-block; padding:8px 12px; background:#f1f5f9; border-radius:6px; font-size:14px; font-weight:700;">[villocity_local_listings]</code>
                        <p style="margin: 8px 0 0 0; font-size: 13px; color: #64748b;">Displays the interactive marketplace grid and includes the pop-up submission launcher.</p>
                    </div>
                </div>
            </div>

            <!-- TAB: SETTINGS -->
            <div id="voc-tab-settings" class="voc-tab-content" style="display: none;">
                <div class="voc-gemini-card">
                    <form method="post">
                        ' . wp_nonce_field( 'bcg_listings_action', 'bcg_action_nonce', true, false ) . '
                        <input type="hidden" name="action_save_settings" value="1">
                        
                        <div class="voc-gemini-notice" style="margin-bottom: 24px;">
                            <h3 style="margin-bottom:0;">' . villocity_get_icon('settings') . ' Note:</h3>
                            <p style="margin-top:4px;">Brand colors and button styling are managed globally in the <strong>VilloCity Hub &rarr; Global Settings</strong>.</p>
                        </div>

                        <h3 style="margin-top:0;">' . villocity_get_icon('tag') . ' Dynamic Categories Manager</h3>
                        <p style="color:#64748b; font-size:14px;">Add, edit, or remove item categories for sales and stands. Separate each category with a comma.</p>
                        <div style="margin-bottom: 30px;">
                            <textarea name="bcg_listings_custom_categories" class="voc-gemini-textarea" rows="3">' . esc_textarea($custom_cats) . '</textarea>
                        </div>

                        <h3 style="margin-top:40px;">' . villocity_get_icon('alert') . ' Promo Banner Text</h3>
                        <p style="color:#64748b; font-size:14px;">Displays a highlighted banner above the submission form. Leave blank to hide.</p>
                        <div style="margin-bottom: 30px;">
                            <input type="text" name="bcg_listings_promo_text" value="' . esc_attr($promo_text) . '" class="voc-gemini-input" placeholder="e.g. Free listings for the first 30 days!">
                        </div>

                        <h3 style="margin-top:40px;">' . villocity_get_icon('globe') . ' Stripe Payment Integration</h3>
                        <p style="color:#64748b; font-size:14px;">Where should users be sent after clicking submit to pay their fee?</p>
                        <div style="margin-bottom: 30px;">
                            <label style="display:block; font-weight:600; margin-bottom:8px;">Stripe Payment Link URL</label>
                            <input type="url" name="bcg_listings_stripe_url" value="' . esc_attr($stripe_url) . '" class="voc-gemini-input" placeholder="https://buy.stripe.com/...">
                            <p style="margin:6px 0 0 0; font-size:13px; color:' . esc_attr($primary_col) . '; font-weight: 500;">Tip: If this is left blank, the submit button will automatically switch to "Free Mode" and skip the redirect.</p>
                        </div>

                        <h3 style="margin-top:40px;">' . villocity_get_icon('mail') . ' Notification Alerts</h3>
                        <div style="margin-bottom: 30px;">
                            <label style="display:block; font-weight:600; margin-bottom:8px;">Admin Email Address</label>
                            <input type="email" name="bcg_listings_admin_email" value="' . esc_attr($admin_email) . '" class="voc-gemini-input">
                        </div>

                        <div style="margin-top: 40px; padding-top: 24px; border-top: 1px solid #f0f4f8;">
                            <button type="submit" class="voc-gemini-btn-primary">' . villocity_get_icon('check') . ' Save Configuration</button>
                        </div>
                    </form>
                </div>
            </div>

        <script>
            function voc_switch_tab(event, tabId) {
                if(event) event.preventDefault();
                var contents = document.getElementsByClassName("voc-tab-content");
                for (var i = 0; i < contents.length; i++) { contents[i].style.display = "none"; }
                var tabs = document.getElementsByClassName("voc-gemini-tab");
                for (var i = 0; i < tabs.length; i++) { tabs[i].classList.remove("active"); }
                
                var targetContent = document.getElementById(tabId);
                if (targetContent) targetContent.style.display = "block";
                
                if(event) {
                    event.currentTarget.classList.add("active");
                } else {
                    var activeLink = document.querySelector(".voc-gemini-tab[onclick*=\'" + tabId + "\']");
                    if (activeLink) activeLink.classList.add("active");
                }
                localStorage.setItem("bcg_marketplace_active_tab", tabId);
            }

            document.addEventListener("DOMContentLoaded", function() {
                var activeTab = localStorage.getItem("bcg_marketplace_active_tab");
                if (activeTab && document.getElementById(activeTab)) {
                    voc_switch_tab(null, activeTab);
                }
            });
        </script>';
    }
}

// ==========================================
// 3. WORDPRESS DASHBOARD AT-A-GLANCE WIDGET
// ==========================================
add_action('wp_dashboard_setup', 'voc_register_marketplace_dashboard_widget');
function voc_register_marketplace_dashboard_widget() {
    wp_add_dashboard_widget(
        'voc_marketplace_dashboard_widget',
        'Marketplace - At a Glance',
        'voc_render_marketplace_dashboard_widget'
    );
}

function voc_render_marketplace_dashboard_widget() {
    $primary_col = get_option('villocity_global_primary', '#860038');
    
    $total_items = wp_count_posts('bcg_listing')->publish;
    $pending_items = wp_count_posts('bcg_listing')->pending;

    $recent_items = get_posts(array(
        'post_type'      => 'bcg_listing',
        'posts_per_page' => 3,
        'post_status'    => 'publish'
    ));

    echo '<style>
        .voc-mkt-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; margin-bottom: 16px; text-align: center; }
        .voc-mkt-stat { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 12px 8px; }
        .voc-mkt-num { font-size: 22px; font-weight: 800; color: ' . esc_attr($primary_col) . '; line-height: 1; margin-bottom: 4px; }
        .voc-mkt-lbl { font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; }
        .voc-mkt-list { list-style: none; padding: 0; margin: 0 0 16px 0; }
        .voc-mkt-list li { display: flex; justify-content: space-between; font-size: 13px; font-weight: 600; padding: 6px 0; border-bottom: 1px solid #f1f5f9; }
        .voc-mkt-btn { display: block; text-align: center; background: ' . esc_attr($primary_col) . '; color: #fff !important; text-decoration: none !important; font-weight: 700; padding: 10px; border-radius: 20px; font-size: 13px; }
    </style>';

    echo '<div class="voc-mkt-grid">
        <div class="voc-mkt-stat"><div class="voc-mkt-num">' . number_format($total_items) . '</div><div class="voc-mkt-lbl">Active Postings</div></div>
        <div class="voc-mkt-stat"><div class="voc-mkt-num">' . number_format($pending_items) . '</div><div class="voc-mkt-lbl">Pending Review</div></div>
    </div>';

    echo '<strong style="display:block; margin-bottom:8px; font-size:12px; text-transform:uppercase; color:#64748b;">🛍️ Recent Community Postings</strong>';
    echo '<ul class="voc-mkt-list">';
    if (!empty($recent_items)) {
        foreach ($recent_items as $item) {
            $price = get_post_meta($item->ID, '_listing_price', true);
            $price_lbl = !empty($price) ? '$' . esc_html($price) : 'Free';
            echo '<li><span>' . esc_html($item->post_title) . '</span><span style="color:#166534; font-weight:700;">' . $price_lbl . '</span></li>';
        }
    } else {
        echo '<li style="color:#94a3b8;">No marketplace items posted yet.</li>';
    }
    echo '</ul>';

    echo '<a href="' . admin_url('admin.php?page=bcg-listings-hub') . '" class="voc-mkt-btn">Open Marketplace Hub &rarr;</a>';
}