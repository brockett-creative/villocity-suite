<?php
/**
 * Module Name: VilloCity Live Marketplace (Frontend)
 * Module URI:  https://villocity.org
 * Description: Public marketplace feed, live search filters, popup submission forms, and single item template.
 * Version:     2.3.0
 * Last Updated: September 5, 2026
 * Author:      Brockett Creative Group, Inc.
 * Author URI:  https://brockettcreative.com
 * Copyright:   © 2026 Brockett Creative Group, Inc. All Rights Reserved.
 * Text Domain: villocity-suite
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

// ==========================================
// 1. PAGE VIEW TRACKER & REST API
// ==========================================
if ( ! function_exists( 'bcg_track_listing_views' ) ) {
    add_action( 'wp', 'bcg_track_listing_views' );
    function bcg_track_listing_views() {
        if ( is_singular( 'bcg_listing' ) && ! current_user_can( 'manage_options' ) ) {
            global $post;
            static $bcg_tracked = false; 
            if ( ! $bcg_tracked && isset($post->ID) ) {
                $views = (int) get_post_meta( $post->ID, '_listing_views', true );
                update_post_meta( $post->ID, '_listing_views', $views + 1 );
                $bcg_tracked = true;
            }
        }
    }
}

if ( ! function_exists( 'bcg_listings_log_click' ) ) {
    add_action( 'rest_api_init', function () {
        register_rest_route( 'bcg-listings/v1', '/track-click', array(
            'methods'  => 'POST',
            'callback' => 'bcg_listings_log_click',
            'permission_callback' => '__return_true',
        ) );
    } );

    function bcg_listings_log_click( WP_REST_Request $request ) {
        $post_id = (int) $request->get_param( 'post_id' );
        if ( $post_id ) {
            $dirs = (int) get_post_meta( $post_id, '_listing_dir_clicks', true );
            update_post_meta( $post_id, '_listing_dir_clicks', $dirs + 1 );
            return rest_ensure_response( array( 'success' => true ) );
        }
        return new WP_Error( 'not_found', 'Invalid ID', array( 'status' => 400 ) );
    }
}

// ==========================================
// 2. SINGLE LISTING TEMPLATE
// ==========================================
if ( ! function_exists( 'bcg_listing_single_page_template' ) ) {
    add_filter( 'the_content', 'bcg_listing_single_page_template' );
    function bcg_listing_single_page_template( $content ) {
        if ( is_singular( 'bcg_listing' ) && in_the_loop() && is_main_query() ) {
            $id       = get_the_ID();
            $type     = get_post_meta( $id, '_listing_type', true );
            $category = get_post_meta( $id, '_listing_category', true );
            $address  = get_post_meta( $id, '_listing_address', true );
            $hours    = get_post_meta( $id, '_listing_hours', true );
            $tags     = get_post_meta( $id, '_listing_tags', true );
            $website  = get_post_meta( $id, '_listing_website_url', true );
            $img      = get_the_post_thumbnail_url( $id, 'large' );

            $track_class = current_user_can( 'manage_options' ) ? '' : ' bcg-track-dir';

            $layout_style   = get_option('villocity_global_layout_style', 'boxed');
            $primary_col    = get_option('villocity_global_primary', get_option('bcg_listings_primary_color', '#860038'));
            $accent_col     = get_option('villocity_global_accent', get_option('bcg_listings_accent_color', '#fadceb'));
            $text_col       = get_option('villocity_global_text', get_option('bcg_listings_text_color', '#1c1917'));
            $bg_col         = get_option('villocity_global_bg', get_option('bcg_listings_bg_color', '#ffffff'));
            $btn_bg         = get_option('villocity_global_btn_bg', get_option('bcg_listings_btn_bg', '#860038'));
            $btn_text       = get_option('villocity_global_btn_text', get_option('bcg_listings_btn_text', '#ffffff'));
            $btn_hover_bg   = get_option('villocity_global_btn_hover_bg', get_option('bcg_listings_btn_hover_bg', '#3a6690'));
            $btn_hover_text = get_option('villocity_global_btn_hover_text', get_option('bcg_listings_btn_hover_text', '#ffffff'));

            $permalink = get_permalink();
            $encoded_url = urlencode($permalink);
            $encoded_title = urlencode(get_the_title());
            
            $map_url = 'https://www.google.com/maps/search/?api=1&query=' . urlencode($address);
            $marketplace_url = home_url( '/marketplace/' );

            $share_fb = 'https://www.facebook.com/sharer/sharer.php?u=' . $encoded_url;
            $share_tw = 'https://twitter.com/intent/tweet?url=' . $encoded_url . '&text=' . $encoded_title;
            $share_em = 'mailto:?subject=Local Listing: ' . $encoded_title . '&body=Check out this local listing I found:%0D%0A' . $encoded_url;

            $schema_data = array(
                '@context'    => 'https://schema.org',
                '@type'       => ($type === 'Roadside Stand' ? 'LocalBusiness' : 'Event'),
                'name'        => get_the_title(),
                'description' => wp_strip_all_tags( $content ),
                'url'         => $permalink,
                'address'     => array(
                    '@type'           => 'PostalAddress',
                    'streetAddress' => $address
                )
            );
            if ( $img ) $schema_data['image'] = $img;
            if ( ! empty( $website ) ) $schema_data['sameAs'] = $website;

            $custom_html = '
            <script type="application/ld+json">
            ' . wp_json_encode( $schema_data ) . '
            </script>

            <style>
                h1.entry-title, h1.page-title, h1.post-title, .entry-title, .page-title, .post-title, header.entry-header, .ast-single-post-title { display: none !important; }

                .bcg-single-wrap {
                    --bcg-primary: ' . esc_attr($primary_col) . ';
                    --bcg-accent: ' . esc_attr($accent_col) . ';
                    --bcg-text: ' . esc_attr($text_col) . ';
                    --bcg-bg: ' . esc_attr($bg_col) . ';
                    --bcg-btn-bg: ' . esc_attr($btn_bg) . ';
                    --bcg-btn-text: ' . esc_attr($btn_text) . ';
                    --bcg-btn-hover-bg: ' . esc_attr($btn_hover_bg) . ';
                    --bcg-btn-hover-text: ' . esc_attr($btn_hover_text) . ';

                    font-family: var(--voc-font-family, inherit) !important;
                    background: var(--bcg-bg);
                    color: var(--bcg-text);
                    border-radius: 16px;
                    border: 1px solid #e7e5e4;
                    overflow: hidden;
                    box-shadow: 0 4px 20px rgba(0,0,0,0.04);
                    max-width: 800px;
                    margin: 20px auto;
                }
                
                .bcg-single-wrap.bcg-style-clean, .bcg-single-wrap.bcg-style-minimal-grid { background: transparent; border: none; box-shadow: none; padding-top: 0; }
                .bcg-single-wrap.bcg-style-clean .bcg-single-hero, .bcg-single-wrap.bcg-style-minimal-grid .bcg-single-hero { border-radius: 16px; margin-bottom: 24px; }
                .bcg-single-wrap.bcg-style-clean .bcg-single-body, .bcg-single-wrap.bcg-style-minimal-grid .bcg-single-body { padding: 0; }
                .bcg-single-wrap.bcg-style-clean .bcg-single-meta-card, .bcg-single-wrap.bcg-style-minimal-grid .bcg-single-meta-card { background: transparent; border: 1px solid #e7e5e4; box-shadow: none; }

                .bcg-single-hero { width: 100%; height: 350px; object-fit: cover; background: #e7e5e4; display:block; }
                .bcg-single-body { padding: 32px; }
                .bcg-single-top-nav { margin-bottom: 20px; }
                .bcg-single-badge { background: var(--bcg-accent); color: var(--bcg-primary); font-size: 12px; font-weight: 700; padding: 6px 14px; border-radius: 20px; display: inline-flex; align-items: center; margin-bottom: 16px; text-transform: uppercase; letter-spacing: 0.05em; gap: 6px; }
                
                h1.bcg-single-title { font-size: 32px !important; font-weight: 800 !important; color: var(--bcg-text) !important; margin: 0 0 16px 0 !important; line-height: 1.2 !important; display: block !important; font-family: var(--voc-font-family, inherit) !important; }
                
                .bcg-single-meta-card { background: #fafaf9; border: 1px solid #e7e5e4; border-radius: 12px; padding: 20px; margin-bottom: 24px; display: flex; flex-direction: column; gap: 12px; }
                .bcg-single-meta-row { font-size: 15px; font-weight: 600; display: flex; align-items: flex-start; gap: 10px; color: var(--bcg-text); }
                .bcg-single-desc { font-size: 16px; line-height: 1.6; color: var(--bcg-text); margin-bottom: 32px; opacity: 0.9; }
                .bcg-single-actions { display: flex; gap: 12px; flex-wrap: wrap; }
                
                .bcg-single-btn-primary { background: var(--bcg-btn-bg) !important; color: var(--bcg-btn-text) !important; font-weight: 600 !important; text-decoration: none !important; padding: 12px 24px !important; border-radius: 24px !important; display: inline-flex !important; align-items: center !important; gap: 8px !important; transition: all 0.2s ease !important; font-size: 14px !important; }
                .bcg-single-btn-primary:hover, .bcg-single-btn-primary:focus-visible { background: var(--bcg-btn-hover-bg) !important; color: var(--bcg-btn-hover-text) !important; box-shadow: 0 4px 12px rgba(0,0,0,0.15) !important; }
                .bcg-single-btn-secondary { background: #f0f4f8 !important; color: #1c1917 !important; font-weight: 600 !important; text-decoration: none !important; padding: 12px 24px !important; border-radius: 24px !important; display: inline-flex !important; align-items: center !important; gap: 8px !important; transition: all 0.2s ease !important; font-size: 14px !important; }
                .bcg-single-btn-secondary:hover, .bcg-single-btn-secondary:focus-visible { background: #e7e5e4 !important; }
                
                .bcg-tag-pill { background: #f1f5f9; color: #475569; font-size: 12px; font-weight: 600; padding: 4px 10px; border-radius: 12px; display: inline-block; margin-right: 6px; margin-top: 6px; }

                .bcg-share-row { display: flex; align-items: center; gap: 12px; margin-top: 32px; padding-top: 24px; border-top: 1px solid #e7e5e4; }
                .bcg-share-label { font-weight: 700; font-size: 14px; color: var(--bcg-text); opacity: 0.9; }
                .bcg-share-btn { display: inline-flex; align-items: center; justify-content: center; width: 38px; height: 38px; border-radius: 50%; background: #f0f4f8; color: #1c1917 !important; text-decoration: none !important; transition: 0.2s; }
                .bcg-share-btn:hover, .bcg-share-btn:focus-visible { background: var(--bcg-primary); color: #fff !important; transform: translateY(-2px); box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
            </style>

            <article class="bcg-single-wrap bcg-style-' . esc_attr($layout_style) . '" aria-label="' . esc_attr(get_the_title()) . ' Details">
                ' . ($img ? '<img src="' . esc_url($img) . '" class="bcg-single-hero" alt="Listing photo for ' . esc_attr(get_the_title()) . '">' : '') . '
                <div class="bcg-single-body">
                    <nav class="bcg-single-top-nav" aria-label="Breadcrumb">
                        <a href="' . esc_url($marketplace_url) . '" class="bcg-single-btn-secondary">&larr; Back to Marketplace</a>
                    </nav>

                    <span class="bcg-single-badge">' . villocity_get_icon('bag') . esc_html($type) . ' &bull; ' . esc_html($category) . '</span>
                    <h1 class="bcg-single-title">' . esc_html(get_the_title()) . '</h1>

                    <div class="bcg-single-meta-card">
                        <div class="bcg-single-meta-row">' . villocity_get_icon('map-pin') . ' <strong>Location:</strong> <span>' . esc_html($address) . '</span></div>
                        <div class="bcg-single-meta-row">' . villocity_get_icon('clock') . ' <strong>Hours:</strong> <span>' . esc_html($hours) . '</span></div>
                    </div>

                    <div class="bcg-single-desc">
                        ' . $content . '
                    </div>';

            if ( ! empty($tags) ) {
                $tag_list = explode(',', $tags);
                $custom_html .= '<div style="margin-bottom:24px;"><strong>Tags:</strong><br>';
                foreach($tag_list as $t) {
                    if(!empty(trim($t))) {
                        $custom_html .= '<span class="bcg-tag-pill">#' . esc_html(trim($t)) . '</span>';
                    }
                }
                $custom_html .= '</div>';
            }

            $custom_html .= '<div class="bcg-single-actions">
                        <a href="' . esc_url($map_url) . '" target="_blank" rel="noopener noreferrer" class="bcg-single-btn-primary' . $track_class . '" data-id="' . esc_attr($id) . '">' . villocity_get_icon('map-pin') . ' Get Directions</a>';
                        
            if ( ! empty( $website ) ) {
                $custom_html .= '<a href="' . esc_url($website) . '" target="_blank" rel="nofollow ugc noopener noreferrer" class="bcg-single-btn-primary' . $track_class . '" data-id="' . esc_attr($id) . '">' . villocity_get_icon('globe') . ' Visit Website / Facebook Page</a>';
            }

            $custom_html .= '</div>

                    <div class="bcg-share-row" aria-label="Share this listing">
                        <span class="bcg-share-label">Share:</span>
                        <a href="' . esc_url($share_fb) . '" target="_blank" rel="noopener noreferrer" class="bcg-share-btn" aria-label="Share ' . esc_attr(get_the_title()) . ' on Facebook">
                            <svg class="villocity-icon" style="margin:0;" viewBox="0 0 24 24" fill="currentColor"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"></path></svg>
                        </a>
                        <a href="' . esc_url($share_tw) . '" target="_blank" rel="noopener noreferrer" class="bcg-share-btn" aria-label="Share ' . esc_attr(get_the_title()) . ' on X">
                            <svg class="villocity-icon" style="margin:0;" viewBox="0 0 24 24" fill="currentColor"><path d="M23 3a10.9 10.9 0 0 1-3.14 1.53 4.48 4.48 0 0 0-7.86 3v1A10.66 10.66 0 0 1 3 4s-4 9 5 13a11.64 11.64 0 0 1-7 2c9 5 20 0 20-11.5a4.5 4.5 0 0 0-.08-.83A7.72 7.72 0 0 0 23 3z"></path></svg>
                        </a>
                        <a href="' . esc_url($share_em) . '" class="bcg-share-btn" aria-label="Email this listing">
                            ' . villocity_get_icon('mail') . '
                        </a>
                    </div>
                </div>
            </article>

            <script>
            document.addEventListener("DOMContentLoaded", function() {
                const isAdmin = ' . (current_user_can('manage_options') ? 'true' : 'false') . ';
                const dirLink = document.querySelector(".bcg-single-wrap .bcg-track-dir");
                if(dirLink) {
                    dirLink.addEventListener("click", function() {
                        if (!isAdmin) {
                            const post_id = this.getAttribute("data-id");
                            fetch("/wp-json/bcg-listings/v1/track-click", {
                                method: "POST",
                                headers: { "Content-Type": "application/json" },
                                body: JSON.stringify({ post_id: post_id })
                            });
                        }
                    });
                }
            });
            </script>';

            return $custom_html;
        }

        return $content;
    }
}

// ==========================================
// 3. FRONT-END SUBMISSION HANDLER
// ==========================================
if ( ! function_exists( 'bcg_handle_listing_submission' ) ) {
    add_action( 'admin_post_nopriv_bcg_submit_listing', 'bcg_handle_listing_submission' );
    add_action( 'admin_post_bcg_submit_listing', 'bcg_handle_listing_submission' );

    function bcg_handle_listing_submission() {
        if ( ! isset( $_POST['bcg_submit_nonce'] ) || ! wp_verify_nonce( $_POST['bcg_submit_nonce'], 'bcg_submit_action' ) ) {
            wp_die( 'Security check failed.' );
        }

        if ( empty( $_POST['listing_terms'] ) ) {
            wp_die( 'You must agree to the Terms of Service to submit a listing.' );
        }

        $title    = sanitize_text_field( $_POST['listing_title'] );
        $desc     = sanitize_textarea_field( $_POST['listing_desc'] );
        $type     = sanitize_text_field( $_POST['listing_type'] );
        $category = sanitize_text_field( $_POST['listing_category'] );
        $address  = sanitize_text_field( $_POST['listing_address'] );
        $hours    = sanitize_text_field( $_POST['listing_hours'] );
        $email    = sanitize_email( $_POST['listing_email'] );
        $tags     = sanitize_text_field( $_POST['listing_tags'] );
        $website  = esc_url_raw( $_POST['listing_website_url'] );

        $is_edit = false;
        $post_id = 0;
        
        if ( ! empty( $_POST['edit_id'] ) && ! empty( $_POST['edit_token'] ) ) {
            $edit_id = (int) $_POST['edit_id'];
            $valid_token = get_post_meta( $edit_id, '_listing_edit_token', true );
            
            if ( $valid_token && $valid_token === $_POST['edit_token'] ) {
                $is_edit = true;
                $post_id = $edit_id;
            } else {
                wp_die('Invalid or expired security token.');
            }
        }

        if ( $is_edit ) {
            wp_update_post( array( 'ID' => $post_id, 'post_title' => $title, 'post_content' => $desc ) );
        } else {
            $post_id = wp_insert_post( array( 'post_title' => $title, 'post_content' => $desc, 'post_type' => 'bcg_listing', 'post_status' => 'pending' ) );
        }

        if ( ! is_wp_error( $post_id ) ) {
            update_post_meta( $post_id, '_listing_type', $type );
            update_post_meta( $post_id, '_listing_category', $category );
            update_post_meta( $post_id, '_listing_address', $address );
            update_post_meta( $post_id, '_listing_hours', $hours );
            update_post_meta( $post_id, '_listing_email', $email );
            update_post_meta( $post_id, '_listing_tags', $tags );
            update_post_meta( $post_id, '_listing_website_url', $website );
            
            if ( ! $is_edit ) {
                update_post_meta( $post_id, '_listing_views', 0 );
                update_post_meta( $post_id, '_listing_dir_clicks', 0 );
                update_post_meta( $post_id, '_listing_featured', 'no' );
                $token = wp_generate_password( 24, false, false );
                update_post_meta( $post_id, '_listing_edit_token', $token );
            }

            if ( ! empty( $_FILES['listing_image']['name'] ) ) {
                require_once( ABSPATH . 'wp-admin/includes/image.php' );
                require_once( ABSPATH . 'wp-admin/includes/file.php' );
                require_once( ABSPATH . 'wp-admin/includes/media.php' );
                
                $attachment_id = media_handle_upload( 'listing_image', $post_id );
                if ( ! is_wp_error( $attachment_id ) ) {
                    set_post_thumbnail( $post_id, $attachment_id );
                }
            }

            $site_name = get_bloginfo('name');
            $admin_email = get_option( 'bcg_listings_admin_email', get_option('admin_email') );
            $referer_url = wp_get_referer();
            $base_url = $referer_url ? strtok($referer_url, '?') : home_url();

            if ( $is_edit ) {
                wp_mail( $admin_email, 'Listing Updated: ' . $title, "A resident has updated their existing listing:\n\nTitle: {$title}\nSubmitted by: {$email}\n\nYou can review changes in the dashboard." );
                wp_mail( $email, 'Listing Updated Successfully', "Your listing ({$title}) has been updated successfully on the {$site_name} Marketplace!" );
                wp_redirect( add_query_arg( 'listing_updated', '1', $base_url ) );
                exit;
            } else {
                $magic_link = add_query_arg( array('edit_id' => $post_id, 'token' => $token), $base_url );
                
                wp_mail( $admin_email, 'Action Required: New Listing - ' . $title, "A new {$type} listing has been submitted.\n\nTitle: {$title}\nSubmitted by: {$email}\n\nPlease review in the WP Dashboard." );
                
                $user_body = "Hello,\n\nWe have received your listing: \"{$title}\" for the {$site_name} Marketplace. It is currently pending review.\n\n";
                $user_body .= "Need to update your hours or swap your photo? You can securely manage your listing anytime using your private magic link below:\n\n";
                $user_body .= $magic_link . "\n\n(Please do not share this link with anyone else).\n\nThank you,\nThe {$site_name} Team";
                
                wp_mail( $email, 'Your listing has been received! (' . $title . ')', $user_body );

                $redirect_url = get_option( 'bcg_listings_stripe_url', '' );
                if ( empty( $redirect_url ) ) {
                    $redirect_url = add_query_arg( 'listing_success', '1', $base_url );
                }
                wp_redirect( $redirect_url );
                exit;
            }
        }
    }
}

// ==========================================
// 4. SHORTCODE: SUBMISSION FORM
// ==========================================
if ( ! function_exists( 'bcg_render_submission_form' ) ) {
    function bcg_render_submission_form( $atts = array() ) {
        $args = shortcode_atts( array(
            'is_modal' => 'false' 
        ), $atts );

        $primary_col    = get_option('villocity_global_primary', get_option('bcg_listings_primary_color', '#860038'));
        $accent_col     = get_option('villocity_global_accent', get_option('bcg_listings_accent_color', '#fadceb'));
        $text_col       = get_option('villocity_global_text', get_option('bcg_listings_text_color', '#1c1917'));
        $bg_col         = get_option('villocity_global_bg', get_option('bcg_listings_bg_color', '#ffffff'));
        $btn_bg         = get_option('villocity_global_btn_bg', get_option('bcg_listings_btn_bg', '#860038'));
        $btn_text       = get_option('villocity_global_btn_text', get_option('bcg_listings_btn_text', '#ffffff'));
        $btn_hover_bg   = get_option('villocity_global_btn_hover_bg', get_option('bcg_listings_btn_hover_bg', '#3a6690'));
        $btn_hover_text = get_option('villocity_global_btn_hover_text', get_option('bcg_listings_btn_hover_text', '#ffffff'));
        
        $promo_text     = get_option('bcg_listings_promo_text', 'Launch Promo: All community listings are 100% FREE for our first 30 days!');
        $stripe_url     = get_option('bcg_listings_stripe_url', '');
        
        $is_edit = false;
        $edit_id = 0;
        $edit_token = '';
        $e_title = $e_type = $e_cat = $e_addr = $e_hours = $e_desc = $e_email = $e_tags = $e_web = '';
        
        if ( isset($_GET['edit_id']) && isset($_GET['token']) ) {
            $edit_id = (int) $_GET['edit_id'];
            $edit_token = sanitize_text_field($_GET['token']);
            $valid_token = get_post_meta($edit_id, '_listing_edit_token', true);
            
            if ( $valid_token === $edit_token && get_post_type($edit_id) === 'bcg_listing' ) {
                $is_edit = true;
                $post = get_post($edit_id);
                $e_title = $post->post_title;
                $e_desc  = $post->post_content;
                $e_type  = get_post_meta($edit_id, '_listing_type', true);
                $e_cat   = get_post_meta($edit_id, '_listing_category', true);
                $e_addr  = get_post_meta($edit_id, '_listing_address', true);
                $e_hours = get_post_meta($edit_id, '_listing_hours', true);
                $e_email = get_post_meta($edit_id, '_listing_email', true);
                $e_tags  = get_post_meta($edit_id, '_listing_tags', true);
                $e_web   = get_post_meta($edit_id, '_listing_website_url', true);
            }
        }

        $btn_label = $is_edit ? 'Update Listing' : (empty( $stripe_url ) ? 'Submit Listing' : 'Submit & Proceed to Payment');
        
        $html = '<style>
            .bcg-form-wrap { 
                --bcg-primary: ' . esc_attr($primary_col) . ';
                --bcg-accent: ' . esc_attr($accent_col) . ';
                --bcg-text: ' . esc_attr($text_col) . ';
                --bcg-bg: ' . esc_attr($bg_col) . ';
                --bcg-btn-bg: ' . esc_attr($btn_bg) . ';
                --bcg-btn-text: ' . esc_attr($btn_text) . ';
                --bcg-btn-hover-bg: ' . esc_attr($btn_hover_bg) . ';
                --bcg-btn-hover-text: ' . esc_attr($btn_hover_text) . ';
                background: var(--bcg-bg); padding: 32px; border-radius: 16px; font-family: var(--voc-font-family, inherit) !important; max-width: 700px; margin: 0 auto; box-sizing: border-box; color: var(--bcg-text); 
            }
            .bcg-form-wrap:not(.in-modal) { border: 1px solid #e7e5e4; box-shadow: 0 4px 20px rgba(0,0,0,0.03); }
            .bcg-form-wrap label.bcg-field-label { display: block; font-weight: 600; margin-bottom: 8px; color: var(--bcg-text); font-size: 14px; }
            .bcg-form-input { width: 100%; padding: 12px 16px; border: 1px solid #d6d3d1; border-radius: 12px; margin-bottom: 24px; font-size: 16px; box-sizing: border-box; background: #fff; color: #1c1917; transition: border-color 0.2s, box-shadow 0.2s; }
            .bcg-form-input:focus-visible { outline: 3px solid var(--bcg-primary); border-color: var(--bcg-primary); box-shadow: 0 0 0 3px var(--bcg-accent); }
            .bcg-submit-btn { background: var(--bcg-btn-bg) !important; color: var(--bcg-btn-text) !important; font-weight: 600; border: none; padding: 14px 24px; border-radius: 24px !important; cursor: pointer; width: 100%; font-size: 16px; transition: all 0.2s ease; }
            .bcg-submit-btn:hover, .bcg-submit-btn:focus-visible { background: var(--bcg-btn-hover-bg) !important; color: var(--bcg-btn-hover-text) !important; box-shadow: 0 4px 12px rgba(0,0,0,0.15); }
            .bcg-success-msg { background: #e8fce8; border: 1px solid #b6e3b6; color: #166534; padding: 16px; border-radius: 12px; margin-bottom: 24px; font-weight: bold; text-align: center; }
            .bcg-promo-banner { background: var(--bcg-accent); color: var(--bcg-primary); border: 1px solid var(--bcg-primary); padding: 16px; border-radius: 12px; margin-bottom: 24px; font-weight: 700; text-align: center; font-size: 15px; display: flex; align-items: center; justify-content: center; gap: 8px;}
            .bcg-terms-box { margin-bottom: 24px; background: #fafaf9; padding: 16px; border-radius: 12px; border: 1px solid #e7e5e4; }
            .bcg-terms-label { display: flex; gap: 12px; align-items: flex-start; cursor: pointer; font-weight: 500; font-size: 13px; color: var(--bcg-text); line-height: 1.4; margin: 0; }
            .bcg-terms-label input { width: 18px; height: 18px; margin-top: 2px; cursor: pointer; flex-shrink: 0; }
        </style>';

        $wrapper_class = $args['is_modal'] === 'true' ? 'bcg-form-wrap in-modal' : 'bcg-form-wrap';
        $html .= '<div class="' . $wrapper_class . '">';
        
        if ( $args['is_modal'] !== 'true' ) {
            if ( isset( $_GET['listing_success'] ) ) {
                $html .= '<div class="bcg-success-msg" role="status">' . villocity_get_icon('check') . 'Success! Your listing has been submitted and is pending review.</div>';
            } elseif ( isset( $_GET['listing_updated'] ) ) {
                $html .= '<div class="bcg-success-msg" role="status">' . villocity_get_icon('check') . 'Success! Your listing was updated securely.</div>';
            }
        }

        if ( ! empty( $promo_text ) && ! $is_edit ) {
            $html .= '<div class="bcg-promo-banner" role="region" aria-label="Promotion">' . villocity_get_icon('tag') . esc_html( $promo_text ) . '</div>';
        }

        $html .= '<form action="' . esc_url( admin_url('admin-post.php') ) . '" method="POST" enctype="multipart/form-data">
            ' . wp_nonce_field( 'bcg_submit_action', 'bcg_submit_nonce', true, false ) . '
            <input type="hidden" name="action" value="bcg_submit_listing">';

        if ( $is_edit ) {
            $html .= '<input type="hidden" name="edit_id" value="' . esc_attr($edit_id) . '">';
            $html .= '<input type="hidden" name="edit_token" value="' . esc_attr($edit_token) . '">';
            $html .= '<div style="margin-bottom:20px; font-weight:bold; color:var(--bcg-primary); display:flex; align-items:center; gap:6px;">' . villocity_get_icon('edit') . 'You are editing a live listing.</div>';
        }
            
        $html .= '<label for="bcg_listing_title" class="bcg-field-label">Listing Title / Name *</label>
            <input type="text" id="bcg_listing_title" name="listing_title" class="bcg-form-input" required aria-required="true" value="' . esc_attr($e_title) . '" placeholder="e.g., Smith Family Yard Sale">
            
            <div style="display: flex; gap: 20px; flex-wrap: wrap;">
                <div style="flex: 1; min-width: 200px;">
                    <label for="bcg_listing_type" class="bcg-field-label">Listing Type *</label>
                    <select id="bcg_listing_type" name="listing_type" class="bcg-form-input" required aria-required="true">
                        <option value="Yard Sale" ' . selected($e_type, 'Yard Sale', false) . '>Yard / Garage Sale</option>
                        <option value="Roadside Stand" ' . selected($e_type, 'Roadside Stand', false) . '>Roadside Stand / Farm</option>
                    </select>
                </div>
                <div style="flex: 1; min-width: 200px;">
                    <label for="bcg_listing_category" class="bcg-field-label">Category *</label>
                    <select id="bcg_listing_category" name="listing_category" class="bcg-form-input" required aria-required="true">';
                    foreach(bcg_listings_get_categories() as $cat) {
                        $html .= '<option value="'.esc_attr($cat).'" ' . selected($e_cat, $cat, false) . '>'.esc_html($cat).'</option>';
                    }
        $html .= '  </select>
                </div>
            </div>

            <label for="bcg_listing_tags" class="bcg-field-label">Tags / Search Keywords</label>
            <input type="text" id="bcg_listing_tags" name="listing_tags" class="bcg-form-input" value="' . esc_attr($e_tags) . '" placeholder="e.g. tools, furniture, antiques, fresh corn">

            <label for="bcg_listing_address" class="bcg-field-label">Address / Location *</label>
            <input type="text" id="bcg_listing_address" name="listing_address" class="bcg-form-input" required aria-required="true" value="' . esc_attr($e_addr) . '" placeholder="e.g., 123 Main St, Clinton">

            <label for="bcg_listing_hours" class="bcg-field-label">Dates & Hours *</label>
            <input type="text" id="bcg_listing_hours" name="listing_hours" class="bcg-form-input" required aria-required="true" value="' . esc_attr($e_hours) . '" placeholder="e.g., Sat & Sun, 8am - 2pm">

            <label for="bcg_listing_website_url" class="bcg-field-label">Website or Facebook Page Link (Optional)</label>
            <input type="url" id="bcg_listing_website_url" name="listing_website_url" class="bcg-form-input" value="' . esc_attr($e_web) . '" placeholder="https://facebook.com/yourpage or https://...">

            <label for="bcg_listing_desc" class="bcg-field-label">Description *</label>
            <textarea id="bcg_listing_desc" name="listing_desc" class="bcg-form-input" rows="4" required aria-required="true" placeholder="Tell us what you are selling...">' . esc_textarea($e_desc) . '</textarea>

            <label for="bcg_listing_email" class="bcg-field-label">Contact Email (Hidden from public) *</label>
            <input type="email" id="bcg_listing_email" name="listing_email" class="bcg-form-input" required aria-required="true" value="' . esc_attr($e_email) . '" placeholder="Used for receipt and notifications">

            <label for="bcg_listing_image" class="bcg-field-label">Upload a Photo ' . ($is_edit ? '(Optional)' : '(Required) *') . '</label>
            <div style="font-size: 13px; color: var(--bcg-text); opacity: 0.8; margin-bottom: 8px;">Recommended: Landscape (horizontal) orientation, at least 800x600 pixels. ' . ($is_edit ? 'Leave blank to keep current photo.' : '') . '</div>
            <input type="file" id="bcg_listing_image" name="listing_image" class="bcg-form-input" accept="image/jpeg, image/png, image/webp" ' . ($is_edit ? '' : 'required aria-required="true"') . ' style="padding-bottom: 35px;">

            <div class="bcg-terms-box">
                <label class="bcg-terms-label">
                    <input type="checkbox" name="listing_terms" required aria-required="true">
                    <span><strong>I agree to the Terms of Service.</strong> I acknowledge that I am solely responsible for the items I sell, complying with all New York State Cottage Food laws, and obtaining any necessary local yard sale permits or zoning approvals. I understand this listing fee does not serve as a municipal permit.</span>
                </label>
            </div>

            <button type="submit" class="bcg-submit-btn">' . esc_html($btn_label) . '</button>
        </form></div>';

        return $html;
    }
}
add_action('init', function() {
    add_shortcode( 'villocity_submit_listing', 'bcg_render_submission_form' );
});

// ==========================================
// 5. SHORTCODE: PUBLIC MARKETPLACE GRID
// ==========================================
if ( ! function_exists( 'bcg_render_local_listings' ) ) {
    function bcg_render_local_listings() {
        $layout_style   = get_option('villocity_global_layout_style', 'boxed');
        $primary_col    = get_option('villocity_global_primary', get_option('bcg_listings_primary_color', '#860038'));
        $accent_col     = get_option('villocity_global_accent', get_option('bcg_listings_accent_color', '#fadceb'));
        $text_col       = get_option('villocity_global_text', get_option('bcg_listings_text_color', '#1c1917'));
        $bg_col         = get_option('villocity_global_bg', get_option('bcg_listings_bg_color', '#fafaf9'));
        $btn_bg         = get_option('villocity_global_btn_bg', get_option('bcg_listings_btn_bg', '#860038'));
        $btn_text       = get_option('villocity_global_btn_text', get_option('bcg_listings_btn_text', '#ffffff'));
        $btn_hover_bg   = get_option('villocity_global_btn_hover_bg', get_option('bcg_listings_btn_hover_bg', '#3a6690'));
        $btn_hover_text = get_option('villocity_global_btn_hover_text', get_option('bcg_listings_btn_hover_text', '#ffffff'));
        
        $track_class = current_user_can( 'manage_options' ) ? '' : ' bcg-track-dir';
        
        $args = array(
            'post_type'        => 'bcg_listing',
            'post_status'      => 'publish',
            'posts_per_page'   => -1,
            'suppress_filters' => true
        );
        $query = new WP_Query( $args );

        $html = '<style>
            .bcg-feed-wrap { 
                --bcg-primary: ' . esc_attr($primary_col) . ';
                --bcg-accent: ' . esc_attr($accent_col) . ';
                --bcg-text: ' . esc_attr($text_col) . ';
                --bcg-bg: ' . esc_attr($bg_col) . ';
                --bcg-btn-bg: ' . esc_attr($btn_bg) . ';
                --bcg-btn-text: ' . esc_attr($btn_text) . ';
                --bcg-btn-hover-bg: ' . esc_attr($btn_hover_bg) . ';
                --bcg-btn-hover-text: ' . esc_attr($btn_hover_text) . ';
                font-family: var(--voc-font-family, inherit) !important; background-color: var(--bcg-bg); color: var(--bcg-text); border-radius: 16px; overflow: hidden; border: 1px solid #e7e5e4; box-shadow: 0 4px 20px rgba(0,0,0,0.03); 
            }
            
            .bcg-feed-header { background-color: rgba(0,0,0,0.02); padding: 32px 24px; border-bottom: 1px solid #e7e5e4; }
            @media (min-width: 768px) { .bcg-feed-header { padding: 48px; } }
            .bcg-badge-label { color: var(--bcg-primary); font-weight: 700; letter-spacing: 0.05em; text-transform: uppercase; font-size: 14px; display: flex; align-items: center; gap: 8px; margin-bottom: 8px; }
            .bcg-feed-title { font-size: 36px; font-weight: 800; color: var(--bcg-primary); margin: 0 0 8px 0 !important; line-height: 1.1; font-family: var(--voc-font-family, inherit) !important; }
            @media (min-width: 768px) { .bcg-feed-title { font-size: 48px; } }
            .bcg-feed-subtitle { font-size: 18px; color: var(--bcg-text); opacity: 0.8; margin: 0 !important; }

            .bcg-feed-body { padding: 24px; }
            @media (min-width: 1024px) { .bcg-feed-body { padding: 40px; } }

            .bcg-search-container { position: relative; margin-bottom: 20px; }
            .bcg-search-input-wrap { display: flex; align-items: center; background: #fff; border: 1px solid #d6d3d1; border-radius: 12px; padding: 12px 16px; box-shadow: 0 2px 8px rgba(0,0,0,0.02); transition: border-color 0.2s; }
            .bcg-search-input-wrap:focus-within { border-color: var(--bcg-primary); box-shadow: 0 0 0 3px var(--bcg-accent); }
            .bcg-search-input-wrap input { border: none !important; outline: none !important; width: 100% !important; background: transparent !important; font-size: 16px !important; margin: 0 !important; padding: 0 !important; color: #1c1917 !important; }
            
            .bcg-filters { display: flex; align-items: center; gap: 12px; margin-bottom: 32px; flex-wrap: wrap; background: #fff; padding: 16px; border-radius: 12px; border: 1px solid #e7e5e4; }
            .bcg-pill-group { display: flex; gap: 8px; flex-wrap: wrap; flex-grow: 1; }
            .bcg-filters-select { min-width: 180px; padding: 8px 14px; border: 1px solid #d6d3d1; border-radius: 8px; font-size: 14px; font-family: var(--voc-font-family, inherit) !important; color: #1c1917; cursor: pointer; transition: border-color 0.2s; }

            .bcg-pill {
                background-color: transparent !important; color: var(--bcg-text) !important; border: 1px solid #d6d3d1 !important; padding: 6px 16px !important; border-radius: 24px !important; font-size: 13px !important; font-weight: 600 !important; cursor: pointer !important; transition: all 0.2s ease !important;
            }
            .bcg-pill:hover, .bcg-pill:focus-visible { background-color: var(--bcg-btn-hover-bg) !important; color: var(--bcg-btn-hover-text) !important; border-color: var(--bcg-btn-hover-bg) !important; }
            .bcg-pill.active { background-color: var(--bcg-btn-bg) !important; color: var(--bcg-btn-text) !important; border-color: var(--bcg-btn-bg) !important; box-shadow: 0 2px 6px rgba(0,0,0,0.15) !important; }
            
            .bcg-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 24px; }
            .bcg-card { background: #fff; border: 1px solid #e7e5e4; border-radius: 16px; overflow: hidden; display: flex; flex-direction: column; box-shadow: 0 4px 15px rgba(0,0,0,0.03); transition: transform 0.2s; position: relative; }
            .bcg-card:hover { transform: translateY(-3px); box-shadow: 0 8px 25px rgba(0,0,0,0.06); }
            
            .bcg-card-img-wrap { position: relative; height: 200px; width: 100%; overflow: hidden; background: #e7e5e4; flex-shrink: 0; display: block; }
            .bcg-card-img-wrap a { display: block; width: 100%; height: 100%; position: absolute; top: 0; left: 0; right: 0; bottom: 0; }
            .bcg-card-img { height: 100% !important; width: 100% !important; object-fit: cover !important; transition: transform 0.3s; display: block !important; margin:0 !important; padding:0 !important; }
            .bcg-card:hover .bcg-card-img { transform: scale(1.05); }
            
            .bcg-card-body { padding: 20px; display: flex; flex-direction: column; flex-grow: 1; }
            .bcg-badge { background: var(--bcg-accent); color: var(--bcg-primary); font-size: 11px; font-weight: bold; padding: 4px 10px; border-radius: 12px; display: inline-flex; align-items: center; margin-bottom: 12px; align-self: flex-start; gap: 4px; }
            .bcg-card-title { font-size: 20px; font-weight: 800; margin: 0 0 10px 0; color: var(--bcg-text); line-height: 1.3; font-family: var(--voc-font-family, inherit) !important; }
            .bcg-card-title a { color: inherit; text-decoration: none; }
            .bcg-card-meta { font-size: 13px; color: var(--bcg-text); opacity: 0.8; margin-bottom: 6px; display: flex; align-items: center; gap: 6px; font-weight: 500; }
            .bcg-card-desc { font-size: 14px; color: var(--bcg-text); line-height: 1.5; margin: 12px 0; flex-grow: 1; opacity: 0.9; }
            .bcg-card-actions { display: flex; gap: 8px; margin-top: auto; }

            .bcg-card.bcg-featured { grid-column: 1 / -1; order: -1; border: 2px solid var(--bcg-primary); box-shadow: 0 8px 30px rgba(0,0,0,0.08); align-items: stretch; }
            .bcg-card.bcg-featured .bcg-badge { background: var(--bcg-primary); color: var(--bcg-btn-text); }
            @media (min-width: 768px) {
                .bcg-card.bcg-featured { flex-direction: row; align-items: stretch; }
                .bcg-card.bcg-featured .bcg-card-img-wrap { width: 50% !important; height: auto !important; min-height: 320px !important; position: relative; display: block; }
                .bcg-card.bcg-featured .bcg-card-img-wrap a { position: absolute; top: 0; left: 0; right: 0; bottom: 0; width: 100%; height: 100%; }
                .bcg-card.bcg-featured .bcg-card-img { position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover; }
                .bcg-card.bcg-featured .bcg-card-body { width: 50%; justify-content: center; padding: 40px; }
                .bcg-card.bcg-featured .bcg-card-title { font-size: 28px; margin-bottom: 16px; }
                .bcg-card.bcg-featured .bcg-card-desc { font-size: 16px; }
            }

            .bcg-card-btn, .bcg-open-modal-btn, .bcg-card-btn-sec { 
                background-color: var(--bcg-btn-bg) !important; color: var(--bcg-btn-text) !important; border: none !important; font-weight: 600 !important; padding: 10px 20px !important; border-radius: 24px !important; cursor: pointer !important; text-decoration: none !important; display: inline-flex !important; align-items: center !important; justify-content: center !important; gap: 6px !important; font-size: 13px !important; transition: all 0.2s ease !important; flex: 1;
            }
            .bcg-card-btn-sec { background-color: #f0f4f8 !important; color: #1c1917 !important; }
            .bcg-card-btn:hover, .bcg-open-modal-btn:hover { background-color: var(--bcg-btn-hover-bg) !important; color: var(--bcg-btn-hover-text) !important; box-shadow: 0 4px 12px rgba(0,0,0,0.15) !important; }
            .bcg-card-btn-sec:hover { background-color: #e7e5e4 !important; }
            
            .bcg-disclaimer { font-size: 13px; color: #78716c; font-style: italic; margin-top: 40px; padding-top: 24px; border-top: 1px solid #e7e5e4; line-height: 1.5; }

            .bcg-modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 999999; display: none; align-items: center; justify-content: center; padding: 20px; box-sizing: border-box; backdrop-filter: blur(4px); }
            .bcg-modal-overlay.bcg-show { display: flex; }
            .bcg-modal-content { background: #fff; border-radius: 16px; width: 100%; max-width: 700px; max-height: 90vh; overflow-y: auto; position: relative; box-shadow: 0 10px 40px rgba(0,0,0,0.2); }
            .bcg-modal-close { position: absolute; top: 16px; right: 16px; background: #f0f4f8; border: none; font-size: 24px; line-height: 1; border-radius: 50%; width: 36px; height: 36px; cursor: pointer; color: #1c1917; display: flex; align-items: center; justify-content: center; transition: 0.2s; z-index: 10; }
            .bcg-modal-close:hover { background: #e7e5e4; }
            
            .bcg-success-banner { background: #e8fce8; border: 1px solid #b6e3b6; color: #166534; padding: 16px; border-radius: 12px; margin-bottom: 24px; font-weight: bold; text-align: center; font-size: 15px; }
        </style>';

        $html .= '<div class="bcg-feed-wrap bcg-style-' . esc_attr($layout_style) . '" role="region" aria-label="Local Stands and Sales Marketplace">';
        
        $html .= '
        <div class="bcg-feed-header">
            <div>
              <div class="bcg-badge-label">
                ' . villocity_get_icon('bag') . '
                <span>Community Directory</span>
              </div>
              <h1 class="bcg-feed-title">' . esc_html(get_option('voc_mkt_feed_title', 'Local Stands & Sales')) . '</h1>
              <p class="bcg-feed-subtitle">' . esc_html(get_option('voc_mkt_feed_desc', 'Discover neighborhood yard sales and roadside farm stands.')) . '</p>
            </div>
        </div>';
        
        $html .= '<div class="bcg-feed-body">';

        if ( isset( $_GET['listing_success'] ) ) {
            $html .= '<div class="bcg-success-banner" role="status">' . villocity_get_icon('check') . ' Success! Your listing has been submitted and is pending review.</div>';
        } elseif ( isset( $_GET['listing_updated'] ) ) {
            $html .= '<div class="bcg-success-banner" role="status">' . villocity_get_icon('check') . ' Success! Your listing was securely updated.</div>';
        }

        // Live Search Controls
        $html .= '<div class="bcg-search-container">
            <div class="bcg-search-input-wrap">
                <span style="color:#64748b; margin-right:10px; display:flex;">' . villocity_get_icon('search') . '</span>
                <input type="text" id="bcg-listings-search-input" placeholder="Search for items, categories, or keywords (e.g. tools, clothing, eggs)..." autocomplete="off">
            </div>
        </div>';

        // Filters
        $html .= '<div class="bcg-filters" aria-label="Filter listings by type and category">
            <div class="bcg-pill-group">
                <button class="bcg-pill active" data-filter="all" aria-pressed="true">All Listings</button>
                <button class="bcg-pill" data-filter="Yard Sale" aria-pressed="false">Yard Sales</button>
                <button class="bcg-pill" data-filter="Roadside Stand" aria-pressed="false">Roadside Stands</button>
            </div>
            <select id="bcg-category-dropdown" class="bcg-filters-select" aria-label="Filter by Category">
                <option value="all">All Categories</option>';
                foreach(bcg_listings_get_categories() as $cat) {
                    $html .= '<option value="'.esc_attr($cat).'">'.esc_html($cat).'</option>';
                }
        $html .= '</select>
        </div>';

        $html .= '<div class="bcg-grid" id="bcg-listings-grid">';

        $rendered_ids = array();

        if ( $query->have_posts() ) {
            while ( $query->have_posts() ) {
                $query->the_post();
                $id = get_the_ID();

                // Deduplication
                if ( in_array( $id, $rendered_ids, true ) ) {
                    continue;
                }
                $rendered_ids[] = $id;

                $type     = get_post_meta( $id, '_listing_type', true );
                $category = get_post_meta( $id, '_listing_category', true );
                $address  = get_post_meta( $id, '_listing_address', true );
                $hours    = get_post_meta( $id, '_listing_hours', true );
                $tags     = get_post_meta( $id, '_listing_tags', true );

                $is_featured = get_post_meta( $id, '_listing_featured', true ) === 'yes';
                $featured_class = $is_featured ? ' bcg-featured' : '';
                $badge_icon = $is_featured ? villocity_get_icon('star') . 'Featured &bull; ' : '';
                
                $img = get_the_post_thumbnail_url( $id, 'medium_large' );
                $permalink = get_permalink( $id );
                $map_url = 'https://www.google.com/maps/search/?api=1&query=' . urlencode($address);

                $clean_excerpt = wp_trim_words( wp_strip_all_tags( get_the_content() ), 22, '...' );

                $html .= '<article class="bcg-card' . $featured_class . '" data-type="' . esc_attr($type) . '" data-category="' . esc_attr($category) . '" data-tags="' . esc_attr($tags) . '" data-title="' . esc_attr(get_the_title()) . '">
                    ' . ($img ? '<div class="bcg-card-img-wrap"><a href="'.esc_url($permalink).'"><img src="'.esc_url($img).'" class="bcg-card-img" alt="Listing photo for ' . esc_attr(get_the_title()) . '"></a></div>' : '') . '
                    <div class="bcg-card-body">
                        <span class="bcg-badge">' . $badge_icon . esc_html($type) . ' &bull; ' . esc_html($category) . '</span>
                        <h2 class="bcg-card-title"><a href="' . esc_url($permalink) . '">' . esc_html(get_the_title()) . '</a></h2>
                        <div class="bcg-card-meta">' . villocity_get_icon('map-pin') . ' ' . esc_html($address) . '</div>
                        <div class="bcg-card-meta">' . villocity_get_icon('clock') . ' ' . esc_html($hours) . '</div>
                        <p class="bcg-card-desc">' . esc_html($clean_excerpt) . '</p>
                        <div class="bcg-card-actions">
                            <a href="' . esc_url($permalink) . '" class="bcg-card-btn-sec">' . villocity_get_icon('eye') . ' Details</a>
                            <a href="' . esc_url($map_url) . '" target="_blank" rel="noopener noreferrer" class="bcg-card-btn' . $track_class . '" data-id="'.esc_attr($id).'">' . villocity_get_icon('map-pin') . ' Directions</a>
                        </div>
                    </div>
                </article>';
            }
            wp_reset_postdata();
        } else {
            $html .= '<p style="color: #78716c; font-weight: 500;">No active listings at this time.</p>';
        }

        $html .= '</div>';

        $html .= '<div class="bcg-disclaimer" role="note">
            <strong>Disclaimer:</strong> This community board is provided as a public service. The site owner does not verify the accuracy of these listings, endorse the sellers, or guarantee the quality or safety of any items or food sold. All transactions are strictly between the buyer and seller.
        </div>';

        $html .= '<div style="text-align: center; margin-top: 32px;">
            <button id="bcg-open-modal" class="bcg-open-modal-btn" style="padding: 14px 28px !important; font-size: 15px !important;" aria-haspopup="dialog" aria-controls="bcg-submission-modal">
                ' . villocity_get_icon('plus') . '
                Submit Your Listing
            </button>
        </div>';
        
        $html .= '</div>'; 
        $html .= '</div>'; 

        $html .= '
        <div id="bcg-submission-modal" class="bcg-modal-overlay" role="dialog" aria-modal="true" aria-labelledby="bcg-modal-title">
            <div class="bcg-modal-content">
                <button id="bcg-close-modal" class="bcg-modal-close" aria-label="Close modal">&times;</button>
                <div id="bcg-modal-title" class="bcg-sr-only">Submit or Edit a Listing</div>
                ' . bcg_render_submission_form( array('is_modal' => 'true') ) . '
            </div>
        </div>';

        $html .= '<script>
            document.addEventListener("DOMContentLoaded", function() {
                const isAdmin = ' . (current_user_can("manage_options") ? "true" : "false") . ';

                const pills = document.querySelectorAll(".bcg-pill");
                const catDropdown = document.getElementById("bcg-category-dropdown");
                const searchInput = document.getElementById("bcg-listings-search-input");
                const cards = document.querySelectorAll(".bcg-card");

                let currentType = "all";
                let currentCat = "all";
                let currentQuery = "";

                function filterListings() {
                    cards.forEach(card => {
                        const title = card.getAttribute("data-title") ? card.getAttribute("data-title").toLowerCase() : "";
                        const type = card.getAttribute("data-type") ? card.getAttribute("data-type") : "";
                        const category = card.getAttribute("data-category") ? card.getAttribute("data-category") : "";
                        const tags = card.getAttribute("data-tags") ? card.getAttribute("data-tags").toLowerCase() : "";
                        const desc = card.querySelector(".bcg-card-desc") ? card.querySelector(".bcg-card-desc").textContent.toLowerCase() : "";

                        const matchesSearch = !currentQuery || title.includes(currentQuery) || tags.includes(currentQuery) || desc.includes(currentQuery);
                        const matchesType = (currentType === "all" || type === currentType);
                        const matchesCategory = (currentCat === "all" || category === currentCat);

                        if (matchesSearch && matchesType && matchesCategory) {
                            card.style.display = "flex";
                        } else {
                            card.style.display = "none";
                        }
                    });
                }

                pills.forEach(pill => {
                    pill.addEventListener("click", function() {
                        pills.forEach(p => { p.classList.remove("active"); p.setAttribute("aria-pressed", "false"); });
                        this.classList.add("active");
                        this.setAttribute("aria-pressed", "true");
                        currentType = this.getAttribute("data-filter");
                        filterListings();
                    });
                });

                if(catDropdown) {
                    catDropdown.addEventListener("change", function() {
                        currentCat = this.value;
                        filterListings();
                    });
                }

                if(searchInput) {
                    searchInput.addEventListener("input", function(e) {
                        currentQuery = e.target.value.toLowerCase().trim();
                        filterListings();
                    });
                }

                const dirLinks = document.querySelectorAll(".bcg-track-dir");
                dirLinks.forEach(link => {
                    link.addEventListener("click", function() {
                        if (!isAdmin) {
                            const post_id = this.getAttribute("data-id");
                            fetch("/wp-json/bcg-listings/v1/track-click", {
                                method: "POST",
                                headers: { "Content-Type": "application/json" },
                                body: JSON.stringify({ post_id: post_id })
                            });
                        }
                    });
                });

                const modal = document.getElementById("bcg-submission-modal");
                const openBtn = document.getElementById("bcg-open-modal");
                const closeBtn = document.getElementById("bcg-close-modal");

                function openModal() {
                    if(modal) {
                        modal.classList.add("bcg-show");
                        document.body.style.overflow = "hidden";
                        if(closeBtn) closeBtn.focus();
                    }
                }

                if (window.location.search.includes("edit_id=") && window.location.search.includes("token=")) {
                    openModal();
                }

                if(openBtn && modal && closeBtn) {
                    openBtn.addEventListener("click", openModal);
                    
                    closeBtn.addEventListener("click", function() {
                        modal.classList.remove("bcg-show");
                        document.body.style.overflow = "";
                        openBtn.focus();
                    });
                    
                    modal.addEventListener("click", function(e) {
                        if(e.target === modal) {
                            modal.classList.remove("bcg-show");
                            document.body.style.overflow = "";
                            openBtn.focus();
                        }
                    });

                    document.addEventListener("keydown", function(e) {
                        if (e.key === "Escape" && modal.classList.contains("bcg-show")) {
                            modal.classList.remove("bcg-show");
                            document.body.style.overflow = "";
                            openBtn.focus();
                        }
                    });
                }
            });
        </script>';

        return $html;
    }
}

add_action('init', function() {
    add_shortcode( 'villocity_local_listings', 'bcg_render_local_listings' );
});