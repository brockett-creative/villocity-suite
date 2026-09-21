<?php
/**
 * VilloCity Module: Live Events (Frontend)
 * Version: 3.2.0
 * Last Updated: September 10, 2026
 * Author: Brockett Creative Group, Inc.
 * Copyright: © 2026 Brockett Creative Group, Inc. All Rights Reserved.
 * 
 * Description: Handles the public-facing single event template, form shortcode, and calendar feed grids.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

// ==========================================
// 1. SINGLE EVENT TEMPLATE
// ==========================================
if ( ! function_exists( 'bcg_event_single_page_template' ) ) {
    add_filter( 'the_content', 'bcg_event_single_page_template' );
    function bcg_event_single_page_template( $content ) {
        if ( is_singular( 'bcg_event' ) && in_the_loop() && is_main_query() ) {
            $id         = get_the_ID();
            $category   = get_post_meta( $id, '_event_category', true );
            $host       = get_post_meta( $id, '_event_host', true );
            $address    = get_post_meta( $id, '_event_address', true );
            $hours      = get_post_meta( $id, '_event_hours', true );
            $start_date = get_post_meta( $id, '_event_start_date', true );
            $end_date   = get_post_meta( $id, '_event_end_date', true );
            $website    = get_post_meta( $id, '_event_website_url', true );
            $status     = get_post_meta( $id, '_event_status', true );
            $is_annual  = get_post_meta($id, '_event_is_annual', true) === 'yes';
            $timeframe  = get_post_meta($id, '_event_annual_timeframe', true);
            $ad_top     = get_option('bcg_events_ad_top', '');
            $ad_bottom  = get_option('bcg_events_ad_bottom', '');
            if (empty($status)) $status = 'Active';

            $img = get_the_post_thumbnail_url($id, 'large');
            if (empty($img)) {
                $migrated_img = get_post_meta($id, '_migrated_acf_image_url', true);
                if (is_array($migrated_img) && isset($migrated_img['url']) && strpos($migrated_img['url'], 'http') !== false) { $img = $migrated_img['url']; } 
                else if (is_string($migrated_img) && strpos($migrated_img, 'http') !== false) { $img = $migrated_img; }
            }

            $track_class = current_user_can('manage_options') ? '' : ' bcg-track-action';
            $layout_style   = get_option('villocity_global_layout_style', 'boxed');
            $primary_col    = get_option('villocity_global_primary', get_option('bcg_events_primary_color', '#860038')); 
            $accent_col     = get_option('villocity_global_accent', get_option('bcg_events_accent_color', '#fadceb')); 
            $text_col       = get_option('villocity_global_text', get_option('bcg_events_text_color', '#1c1917'));
            $bg_col         = get_option('villocity_global_bg', get_option('bcg_events_bg_color', '#ffffff'));
            $btn_bg         = get_option('villocity_global_btn_bg', get_option('bcg_events_btn_bg', '#860038'));
            $btn_text       = get_option('villocity_global_btn_text', get_option('bcg_events_btn_text', '#ffffff'));
            $btn_hover_bg   = get_option('villocity_global_btn_hover_bg', get_option('bcg_events_btn_hover_bg', '#3a6690'));
            $btn_hover_text = get_option('villocity_global_btn_hover_text', get_option('bcg_events_btn_hover_text', '#ffffff'));

            $permalink = get_permalink(); $encoded_url = urlencode($permalink); $encoded_title = urlencode(get_the_title());
            $map_url = 'https://www.google.com/maps/search/?api=1&query=' . urlencode($address);
            $hub_url = home_url( '/calendar/' ); 

            $today_ts = strtotime(date('Y-m-d', current_time('timestamp')));
            $start_ts = (!empty($start_date) && strtotime($start_date)) ? strtotime($start_date) : 0;
            $end_ts   = (!empty($end_date) && strtotime($end_date)) ? strtotime($end_date) : $start_ts;
            if (date('Y', $start_ts) === '1970') { $start_ts = 0; $end_ts = 0; }
            $is_tbd = ($start_ts === 0 || ($is_annual && $end_ts < $today_ts));
            
            $date_display = '';
            if ( $is_annual && $is_tbd ) {
                $date_display = villocity_get_icon('calendar') . ' <strong>Timeframe:</strong> <span>' . esc_html($timeframe) . ' (Dates TBD)</span>';
            } else {
                $formatted_dates = !empty($start_ts) ? date('M j, Y', $start_ts) : '';
                if ($start_ts !== $end_ts && !empty($end_ts)) $formatted_dates .= ' - ' . date('M j, Y', $end_ts);
                $date_display = '<div class="bcg-single-meta-row">' . villocity_get_icon('calendar') . ' <strong>Full Date Range:</strong> <span>' . esc_html($formatted_dates) . '</span></div>';
                
                // Prominent, Mobile-Friendly, ADA-Compliant Next Occurrence Callout
                if ($start_ts < $today_ts && $end_ts >= $today_ts && $start_ts !== $end_ts) {
                    $dow = (int) date('w', $start_ts); $today_dow = (int) date('w', $today_ts); $display_ts = $today_ts;
                    if ($dow !== $today_dow) {
                        $display_ts = strtotime("+" . (($dow - $today_dow + 7) % 7) . " days", $today_ts);
                    }
                    if ($display_ts <= $end_ts) {
                        $date_display .= ' <div class="bcg-next-occurrence-box" role="status" aria-label="Next Occurrence: ' . date('l, F j, Y', $display_ts) . '">
                            <span class="bcg-live-dot" aria-hidden="true"></span>
                            <span class="bcg-next-occurrence-label">Next Occurrence:</span>
                            <strong class="bcg-next-occurrence-value">' . date('l, M j', $display_ts) . '</strong>
                        </div>';
                    }
                }
            }

            $status_banner = '';
            if ($status === 'Cancelled') $status_banner = '<div class="bcg-single-status-alert bcg-alert-red">' . villocity_get_icon('x-circle') . ' This event is Cancelled.</div>';
            elseif ($status === 'Postponed') $status_banner = '<div class="bcg-single-status-alert bcg-alert-orange">' . villocity_get_icon('pause') . ' This event is Postponed.</div>';
            elseif ($status === 'Moved Indoors') $status_banner = '<div class="bcg-single-status-alert bcg-alert-blue">' . villocity_get_icon('cloud') . ' Moved Indoors.</div>';

            $custom_html = '';
            if ( !empty($ad_top) ) $custom_html .= '<div class="voc-ad-container voc-ad-top" style="max-width: 800px; margin: 2em auto 20px auto; text-align: center;">' . do_shortcode(wp_unslash($ad_top)) . '</div>';

            $custom_html .= '<style>
                h1.entry-title, h1.page-title, h1.post-title, .ast-single-post-title { display: none !important; }
                .bcg-single-wrap { --bcg-primary: ' . esc_attr($primary_col) . '; --bcg-accent: ' . esc_attr($accent_col) . '; --bcg-bg: ' . esc_attr($bg_col) . '; --bcg-text: ' . esc_attr($text_col) . '; font-family: var(--voc-font-family, inherit) !important; background: var(--bcg-bg); border-radius: 16px; border: 1px solid #e7e5e4; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.04); max-width: 800px; margin: 20px auto; color: var(--bcg-text); }
                .bcg-single-wrap.bcg-style-clean, .bcg-single-wrap.bcg-style-minimal-grid { background: transparent; border: none; box-shadow: none; padding-top: 0; }
                .bcg-single-wrap.bcg-style-clean .bcg-single-hero, .bcg-single-wrap.bcg-style-minimal-grid .bcg-single-hero { border-radius: 16px; margin-bottom: 24px; }
                .bcg-single-wrap.bcg-style-clean .bcg-single-body, .bcg-single-wrap.bcg-style-minimal-grid .bcg-single-body { padding: 0; }
                .bcg-single-wrap.bcg-style-clean .bcg-single-meta-card, .bcg-single-wrap.bcg-style-minimal-grid .bcg-single-meta-card { background: transparent; border: 1px solid #e7e5e4; box-shadow: none; }
                
                .bcg-single-hero { width: 100% !important; height: 350px !important; object-fit: cover !important; background: #e7e5e4 !important; display: block !important; margin: 0 !important; padding: 0 !important; border: none !important; }
                .bcg-placeholder-hero { width: 100% !important; height: 350px !important; background: linear-gradient(135deg, var(--bcg-accent), var(--bcg-bg)) !important; display: flex !important; align-items: center !important; justify-content: center !important; margin: 0 !important; padding: 0 !important; border: none !important; }
                .bcg-placeholder-hero svg { width: 120px !important; height: 120px !important; color: var(--bcg-primary); opacity: 0.1; }
                
                .bcg-single-body { padding: 32px; }
                .bcg-single-top-nav { margin-bottom: 20px; }
                .bcg-single-badge { background: var(--bcg-accent); color: var(--bcg-primary); font-size: 12px; font-weight: 700; padding: 6px 14px; border-radius: 20px; display: inline-flex; align-items: center; margin-bottom: 16px; text-transform: uppercase; gap: 6px; }
                
                h1.bcg-single-title { font-size: 32px !important; font-weight: 800 !important; margin: 0 0 16px 0 !important; line-height: 1.2 !important; color: var(--bcg-primary) !important; display: block !important; font-family: var(--voc-font-family, inherit) !important; }
                
                .bcg-single-status-alert { font-size: 15px; font-weight: 700; padding: 12px 20px; border-radius: 12px; margin-bottom: 24px; display: flex; align-items: center; gap: 8px; }
                .bcg-alert-red { background: #fee2e2; color: #991b1b; border: 1px solid #f87171; }
                .bcg-alert-orange { background: #ffedd5; color: #92400e; border: 1px solid #fbbf24; }
                .bcg-alert-blue { background: #dbeafe; color: #1e40af; border: 1px solid #93c5fd; }
                
                .bcg-single-meta-card { background: #fafaf9; border: 1px solid #e7e5e4; border-radius: 12px; padding: 20px; margin-bottom: 24px; display: flex; flex-direction: column; gap: 14px; }
                .bcg-single-meta-row { font-size: 15px; font-weight: 600; display: flex; align-items: flex-start; gap: 10px; color: var(--bcg-text); flex-wrap: wrap; }
                .bcg-single-meta-row span { opacity: 0.85; font-weight: 500; }

                /* Prominent, Mobile-Friendly & ADA Compliant Next Occurrence Box */
                .bcg-next-occurrence-box {
                    background: #dcfce7 !important;
                    border: 2px solid #166534 !important;
                    color: #14532d !important;
                    padding: 10px 16px !important;
                    border-radius: 12px !important;
                    display: inline-flex !important;
                    align-items: center !important;
                    gap: 10px !important;
                    font-size: 15px !important;
                    margin-top: 4px !important;
                    box-shadow: 0 2px 8px rgba(22, 101, 52, 0.08) !important;
                    width: fit-content !important;
                    max-width: 100% !important;
                    box-sizing: border-box !important;
                }
                .bcg-next-occurrence-label {
                    font-weight: 700 !important;
                    color: #14532d !important;
                }
                .bcg-next-occurrence-value {
                    font-weight: 800 !important;
                    color: #052e16 !important;
                }
                .bcg-live-dot {
                    width: 10px !important;
                    height: 10px !important;
                    background-color: #166534 !important;
                    border-radius: 50% !important;
                    display: inline-block !important;
                    flex-shrink: 0 !important;
                    animation: bcgDotPulse 1.8s infinite ease-in-out !important;
                }
                @keyframes bcgDotPulse {
                    0% { transform: scale(0.95); opacity: 1; box-shadow: 0 0 0 0 rgba(22, 101, 52, 0.7); }
                    70% { transform: scale(1.1); opacity: 0.8; box-shadow: 0 0 0 8px rgba(22, 101, 52, 0); }
                    100% { transform: scale(0.95); opacity: 1; box-shadow: 0 0 0 0 rgba(22, 101, 52, 0); }
                }

                @media (max-width: 640px) {
                    .bcg-next-occurrence-box {
                        width: 100% !important;
                        justify-content: flex-start !important;
                        flex-wrap: wrap !important;
                        padding: 12px 14px !important;
                    }
                }

                .bcg-single-desc { font-size: 16px; line-height: 1.6; margin-bottom: 32px; opacity: 0.9; color: var(--bcg-text); }
                .bcg-single-actions { display: flex; gap: 12px; flex-wrap: wrap; }
                .bcg-single-btn-primary { background: ' . esc_attr($btn_bg) . ' !important; color: ' . esc_attr($btn_text) . ' !important; font-weight: 600 !important; text-decoration: none !important; padding: 12px 24px !important; border-radius: 24px !important; display: inline-flex !important; align-items: center !important; gap: 6px !important; transition: all 0.2s ease !important; font-size: 14px !important; }
                .bcg-single-btn-primary:hover, .bcg-single-btn-primary:focus-visible { background: ' . esc_attr($btn_hover_bg) . ' !important; color: ' . esc_attr($btn_hover_text) . ' !important; box-shadow: 0 4px 12px rgba(0,0,0,0.15) !important; }
                .bcg-single-btn-secondary { background: #f0f4f8 !important; color: #1c1917 !important; font-weight: 600 !important; text-decoration: none !important; padding: 12px 24px !important; border-radius: 24px !important; display: inline-flex !important; align-items: center !important; gap: 8px !important; transition: all 0.2s ease !important; font-size: 14px !important; }
                .bcg-single-btn-secondary:hover, .bcg-single-btn-secondary:focus-visible { background: #e7e5e4 !important; }
                .bcg-share-row { display: flex; align-items: center; gap: 12px; margin-top: 32px; padding-top: 24px; border-top: 1px solid #e7e5e4; }
                .bcg-share-btn { display: inline-flex; align-items: center; justify-content: center; width: 38px; height: 38px; border-radius: 50%; background: #f0f4f8; color: #1c1917 !important; text-decoration: none !important; }
            </style>

            <article class="bcg-single-wrap bcg-style-' . esc_attr($layout_style) . '">
                ' . ($img ? '<img src="' . esc_url($img) . '" class="bcg-single-hero" style="' . ($status === 'Cancelled' ? 'filter: grayscale(100%) opacity(60%);' : '') . '">' : '<div class="bcg-placeholder-hero">' . villocity_get_icon('calendar') . '</div>') . '
                <div class="bcg-single-body">
                    <nav class="bcg-single-top-nav"><a href="' . esc_url($hub_url) . '" class="bcg-single-btn-secondary">&larr; Back to All Events</a></nav>
                    <span class="bcg-single-badge">' . villocity_get_icon('tag') . esc_html($category) . '</span>
                    <h1 class="bcg-single-title">' . esc_html(get_the_title()) . '</h1>
                    ' . $status_banner . '
                    <div class="bcg-single-meta-card">
                        ' . $date_display . '
                        ' . (!empty($hours) ? '<div class="bcg-single-meta-row">' . villocity_get_icon('clock') . ' <strong>Schedule:</strong> <span>' . esc_html($hours) . '</span></div>' : '') . '
                        ' . (!empty($address) ? '<div class="bcg-single-meta-row">' . villocity_get_icon('map-pin') . ' <strong>Location:</strong> <span>' . esc_html($address) . '</span></div>' : '') . '
                        ' . (!empty($host) ? '<div class="bcg-single-meta-row">' . villocity_get_icon('user') . ' <strong>Hosted By:</strong> <span>' . esc_html($host) . '</span></div>' : '') . '
                    </div>
                    <div class="bcg-single-desc">' . $content . '</div>
                    <div class="bcg-single-actions">
                        ' . (!empty($address) ? '<a href="' . esc_url($map_url) . '" target="_blank" class="bcg-single-btn-primary' . $track_class . '" data-id="' . esc_attr($id) . '" data-type="dir">' . villocity_get_icon('map-pin') . ' Get Directions</a>' : '') . '
                        ' . (!empty($website) ? '<a href="' . esc_url($website) . '" target="_blank" class="bcg-single-btn-primary' . $track_class . '" data-id="' . esc_attr($id) . '" data-type="web">' . villocity_get_icon('globe') . ' Visit Website</a>' : '') . '
                    </div>
                </div>
            </article>
            <script>
            document.addEventListener("DOMContentLoaded", function() {
                const isAdmin = ' . (current_user_can('manage_options') ? 'true' : 'false') . ';
                document.querySelectorAll(".bcg-single-wrap .bcg-track-action").forEach(link => {
                    link.addEventListener("click", function() {
                        if (!isAdmin) fetch("/wp-json/bcg-events/v1/track-action", { method: "POST", headers: { "Content-Type": "application/json" }, body: JSON.stringify({ post_id: this.getAttribute("data-id"), type: this.getAttribute("data-type") }) });
                    });
                });
            });
            </script>';

            if ( !empty($ad_bottom) ) {
                $custom_html .= '<div class="voc-ad-container voc-ad-bottom" style="max-width: 800px; margin: 3em auto 6em auto; text-align: center;">' . do_shortcode(wp_unslash($ad_bottom)) . '</div>';
            }
            return $custom_html;
        }
        return $content;
    }
}

// ==========================================
// 2. FRONT-END SUBMISSION / EDIT HANDLER
// ==========================================
if ( ! function_exists( 'bcg_handle_event_submission' ) ) {
    add_action( 'admin_post_nopriv_bcg_submit_event', 'bcg_handle_event_submission' );
    add_action( 'admin_post_bcg_submit_event', 'bcg_handle_event_submission' );
    function bcg_handle_event_submission() {
        if ( ! isset( $_POST['bcg_submit_nonce'] ) || ! wp_verify_nonce( $_POST['bcg_submit_nonce'], 'bcg_submit_action' ) ) wp_die( 'Security check failed.' );
        if ( ! empty( $_POST['voc_hp_website'] ) ) wp_die( 'Security Check: Automated spam submission detected.' );
        if ( empty( $_POST['event_terms'] ) ) wp_die( 'You must agree to the Terms & Conditions.' );

        $title      = sanitize_text_field( $_POST['event_title'] );
        $desc       = sanitize_textarea_field( $_POST['event_desc'] ); 
        $category   = sanitize_text_field( $_POST['event_category'] );
        $start_date = sanitize_text_field( $_POST['event_start_date'] );
        $end_date   = sanitize_text_field( $_POST['event_end_date'] );
        if (empty($end_date)) { $end_date = $start_date; }
        $hours      = sanitize_text_field( $_POST['event_hours'] );
        $address    = sanitize_text_field( $_POST['event_address'] );
        $website    = esc_url_raw( $_POST['event_website_url'] );
        $email      = sanitize_email( $_POST['event_email'] );
        $host       = sanitize_text_field( $_POST['event_host'] );
        $is_annual  = isset($_POST['event_is_annual']) ? 'yes' : 'no';
        $timeframe  = sanitize_text_field( $_POST['event_annual_timeframe'] );
        $status     = isset($_POST['event_status']) ? sanitize_text_field( $_POST['event_status'] ) : 'Active';

        $is_edit = false; $post_id = 0;
        if ( ! empty( $_POST['edit_id'] ) && ! empty( $_POST['edit_token'] ) ) {
            $edit_id = (int) $_POST['edit_id']; $valid_token = get_post_meta( $edit_id, '_event_edit_token', true );
            if ( $valid_token && $valid_token === $_POST['edit_token'] ) { $is_edit = true; $post_id = $edit_id; } else { wp_die('Invalid token.'); }
        }

        if ( $is_edit ) wp_update_post( array( 'ID' => $post_id, 'post_title' => $title, 'post_content' => $desc ) );
        else $post_id = wp_insert_post( array( 'post_title' => $title, 'post_content' => $desc, 'post_type' => 'bcg_event', 'post_status' => 'pending' ) );

        if ( ! is_wp_error( $post_id ) ) {
            update_post_meta( $post_id, '_event_category', $category ); update_post_meta( $post_id, '_event_start_date', $start_date );
            update_post_meta( $post_id, '_event_end_date', $end_date ); update_post_meta( $post_id, '_event_hours', $hours );
            update_post_meta( $post_id, '_event_address', $address ); update_post_meta( $post_id, '_event_website_url', $website );
            update_post_meta( $post_id, '_event_email', $email ); update_post_meta( $post_id, '_event_host', $host );
            update_post_meta( $post_id, '_event_is_annual', $is_annual ); update_post_meta( $post_id, '_event_annual_timeframe', $timeframe );
            update_post_meta( $post_id, '_event_status', $status );
            if ( ! $is_edit ) {
                update_post_meta($post_id, '_event_views', 0); update_post_meta($post_id, '_event_clicks', 0); update_post_meta($post_id, '_event_featured', 'no'); 
                $token = wp_generate_password( 24, false, false ); update_post_meta( $post_id, '_event_edit_token', $token );
            }
            if ( ! empty( $_FILES['event_image']['name'] ) ) {
                require_once( ABSPATH . 'wp-admin/includes/image.php' ); require_once( ABSPATH . 'wp-admin/includes/file.php' ); require_once( ABSPATH . 'wp-admin/includes/media.php' );
                $attachment_id = media_handle_upload( 'event_image', $post_id );
                if ( ! is_wp_error( $attachment_id ) ) set_post_thumbnail( $post_id, $attachment_id );
            }

            $site_name = get_bloginfo('name');
            $admin_email = get_option( 'bcg_events_admin_email', get_option('admin_email') );
            $referer_url = wp_get_referer();
            $base_url = $referer_url ? strtok($referer_url, '?') : home_url('/calendar/');

            if ( $is_edit ) {
                wp_mail( $admin_email, 'Event Updated: ' . $title, "A resident has updated their existing event:\n\nTitle: {$title}\nSubmitted by: {$email}\n\nYou can review changes in the dashboard." );
                wp_mail( $email, 'Event Updated Successfully', "Your event ({$title}) has been updated successfully on the {$site_name} Calendar!" );
                wp_redirect( add_query_arg( 'event_updated', '1', $base_url ) ); exit;
            } else {
                $magic_link = add_query_arg( array('edit_id' => $post_id, 'token' => $token), $base_url );
                $admin_body = "Hello Admin,\n\nA new event has been submitted and is waiting for your approval.\n\nTitle: {$title}\nSubmitted by: {$email}\n\nPlease review in the WP Dashboard.";
                wp_mail( $admin_email, 'Action Required: New Event - ' . $title, $admin_body );
                
                $user_body = "Hello,\n\nWe have received your event: \"{$title}\" for the {$site_name} Community Calendar. It is currently pending review.\n\n";
                $user_body .= "Need to update your schedule, update your event status, or swap your photo? You can securely manage your event anytime using your private magic link below:\n\n";
                $user_body .= $magic_link . "\n\n(Please do not share this link with anyone else).\n\nThank you,\nThe {$site_name} Team";
                wp_mail( $email, 'Your event has been received! (' . $title . ')', $user_body );

                $redirect_url = get_option( 'bcg_events_stripe_url', '' );
                if ( empty( $redirect_url ) ) $redirect_url = add_query_arg( 'event_success', '1', $base_url );
                wp_redirect( $redirect_url ); exit;
            }
        }
    }
}

// ==========================================
// 3. SHORTCODE: SUBMISSION FORM
// ==========================================
if ( ! function_exists( 'bcg_render_event_form' ) ) {
    function bcg_render_event_form( $atts = array() ) {
        $args = shortcode_atts( array( 'is_modal' => 'false' ), $atts );
        $primary_col = get_option('villocity_global_primary', '#860038'); 
        $btn_bg = get_option('villocity_global_btn_bg', '#860038');
        $btn_text = get_option('villocity_global_btn_text', '#ffffff');
        $btn_hover_bg = get_option('villocity_global_btn_hover_bg', '#3a6690');
        $btn_hover_text = get_option('villocity_global_btn_hover_text', '#ffffff');
        $promo_text  = get_option('bcg_events_promo_text', 'Add your event to the Community Calendar for FREE!');
        $stripe_url  = get_option('bcg_events_stripe_url', '');
        
        $is_edit = false; $edit_id = 0; $edit_token = '';
        $e_title = $e_cat = $e_start = $e_end = $e_hours = $e_addr = $e_web = $e_desc = $e_email = $e_timeframe = $e_host = '';
        $e_annual = 'no'; $e_status = 'Active';
        
        if ( isset($_GET['edit_id']) && isset($_GET['token']) ) {
            $edit_id = (int) $_GET['edit_id'];
            $edit_token = sanitize_text_field($_GET['token']);
            $valid_token = get_post_meta($edit_id, '_event_edit_token', true);
            if ( $valid_token === $edit_token && get_post_type($edit_id) === 'bcg_event' ) {
                $is_edit = true; $post = get_post($edit_id);
                $e_title = $post->post_title; $e_desc = $post->post_content;
                $e_cat = get_post_meta($edit_id, '_event_category', true); $e_start = get_post_meta($edit_id, '_event_start_date', true);
                $e_end = get_post_meta($edit_id, '_event_end_date', true); $e_hours = get_post_meta($edit_id, '_event_hours', true);
                $e_addr = get_post_meta($edit_id, '_event_address', true); $e_web = get_post_meta($edit_id, '_event_website_url', true);
                $e_email = get_post_meta($edit_id, '_event_email', true); $e_host = get_post_meta($edit_id, '_event_host', true);
                $e_annual = get_post_meta($edit_id, '_event_is_annual', true); $e_timeframe = get_post_meta($edit_id, '_event_annual_timeframe', true);
                $e_status = get_post_meta($edit_id, '_event_status', true); if(empty($e_status)) $e_status = 'Active';
            }
        }

        $btn_label = $is_edit ? 'Update Event' : (empty( $stripe_url ) ? 'Submit Event' : 'Submit & Proceed to Payment');
        
        $html = '<style>
            .bcg-form-wrap { font-family: var(--voc-font-family, inherit) !important; background: #fff; padding: 32px; border-radius: 16px; max-width: 700px; margin: 0 auto; box-sizing: border-box; }
            .bcg-form-wrap:not(.in-modal) { border: 1px solid #e7e5e4; box-shadow: 0 4px 20px rgba(0,0,0,0.03); }
            .bcg-form-wrap label.bcg-field-label { display: block; font-weight: 600; margin-bottom: 8px; font-size: 14px; }
            .bcg-form-input { width: 100%; padding: 12px 16px; border: 1px solid #d6d3d1; border-radius: 12px; margin-bottom: 24px; font-size: 16px; box-sizing: border-box; }
            .bcg-submit-btn { background: '.esc_attr($btn_bg).' !important; color: '.esc_attr($btn_text).' !important; font-weight: 600; border: none; padding: 14px 24px; border-radius: 24px !important; cursor: pointer; width: 100%; font-size: 16px; transition: all 0.2s ease; }
            .bcg-submit-btn:hover, .bcg-submit-btn:focus-visible { background: '.esc_attr($btn_hover_bg).' !important; color: '.esc_attr($btn_hover_text).' !important; }
            .bcg-success-msg { background: #e8fce8; border: 1px solid #b6e3b6; color: #166534; padding: 16px; border-radius: 12px; margin-bottom: 24px; font-weight: bold; text-align: center; }
        </style>';

        $html .= '<div class="bcg-form-wrap ' . ($args['is_modal'] === 'true' ? 'in-modal' : '') . '">';
        
        if ( $args['is_modal'] !== 'true' ) {
            if ( isset( $_GET['event_success'] ) ) $html .= '<div class="bcg-success-msg">' . villocity_get_icon('check') . 'Success! Your event has been submitted.</div>';
            elseif ( isset( $_GET['event_updated'] ) ) $html .= '<div class="bcg-success-msg">' . villocity_get_icon('check') . 'Success! Your event was updated securely.</div>';
        }

        $html .= '<form action="' . esc_url( admin_url('admin-post.php') ) . '" method="POST" enctype="multipart/form-data">';
        $html .= wp_nonce_field( 'bcg_submit_action', 'bcg_submit_nonce', true, false ) . '<input type="hidden" name="action" value="bcg_submit_event">';
        
        if ( $is_edit ) {
            $html .= '<input type="hidden" name="edit_id" value="' . esc_attr($edit_id) . '"><input type="hidden" name="edit_token" value="' . esc_attr($edit_token) . '">';
            $html .= '<div style="margin-bottom:20px; font-weight:bold; color:'.esc_attr($primary_col).';">' . villocity_get_icon('edit') . 'You are editing a live event.</div>';
        }

        $html .= '<label class="bcg-field-label">Event Name / Title *</label><input type="text" name="event_title" class="bcg-form-input" required value="' . esc_attr($e_title) . '">';
        $html .= '<div style="display:flex; gap:20px; flex-wrap:wrap;"><div style="flex:1; min-width:200px;"><label class="bcg-field-label">Category *</label><select name="event_category" class="bcg-form-input" required><option value="Festival" '.selected($e_cat, 'Festival', false).'>Festival</option><option value="Music & Concerts" '.selected($e_cat, 'Music & Concerts', false).'>Music & Concerts</option><option value="Family & Kids" '.selected($e_cat, 'Family & Kids', false).'>Family & Kids</option><option value="Civic & Meeting" '.selected($e_cat, 'Civic & Meeting', false).'>Civic & Meeting</option><option value="Fundraiser" '.selected($e_cat, 'Fundraiser', false).'>Fundraiser</option><option value="Sports & Recreation" '.selected($e_cat, 'Sports & Recreation', false).'>Sports & Recreation</option><option value="Other" '.selected($e_cat, 'Other', false).'>Other</option></select></div><div style="flex:1; min-width:200px;"><label class="bcg-field-label">Hosted By</label><input type="text" name="event_host" class="bcg-form-input" value="' . esc_attr($e_host) . '"></div></div>';
        $html .= '<div style="background:#fdfae8; border-left:4px solid #f59e0b; padding:15px; margin-bottom:24px;"><label><input type="checkbox" name="event_is_annual" id="event_is_annual" value="yes" '.checked($e_annual, 'yes', false).'> <strong>Yearly Event / Annual Tradition</strong></label><br><small>Do NOT check this for weekly recurring events. Only use this for yearly events. Checking this places it in the Annual tab and auto-renews it next year.</small></div>';
        $html .= '<div id="bcg_annual_fields" style="display:'.($e_annual === 'yes' ? 'block' : 'none').'; margin-bottom:24px;"><label class="bcg-field-label">General Timeframe *</label><input type="text" name="event_annual_timeframe" class="bcg-form-input" value="' . esc_attr($e_timeframe) . '"></div>';
        $html .= '<div style="display:flex; gap:20px; flex-wrap:wrap;"><div style="flex:1; min-width:200px;"><label class="bcg-field-label">Start Date</label><input type="date" name="event_start_date" class="bcg-form-input bcg-date-req" value="' . esc_attr($e_start) . '"></div><div style="flex:1; min-width:200px;"><label class="bcg-field-label">End Date</label><input type="date" name="event_end_date" class="bcg-form-input bcg-date-req" value="' . esc_attr($e_end) . '"></div></div>';
        $html .= '<label class="bcg-field-label">Detailed Hours / Schedule</label><textarea name="event_hours" class="bcg-form-input bcg-date-req" rows="2">' . esc_textarea($e_hours) . '</textarea>';
        $html .= '<label class="bcg-field-label">Location / Address *</label><input type="text" name="event_address" class="bcg-form-input" required value="' . esc_attr($e_addr) . '">';
        $html .= '<label class="bcg-field-label">Website URL (Optional)</label><input type="url" name="event_website_url" class="bcg-form-input" value="' . esc_attr($e_web) . '">';
        $html .= '<label class="bcg-field-label">Description *</label><textarea name="event_desc" class="bcg-form-input" required rows="4">' . esc_textarea($e_desc) . '</textarea>';
        $html .= '<label class="bcg-field-label">Contact Email (Hidden) *</label><input type="email" name="event_email" class="bcg-form-input" required value="' . esc_attr($e_email) . '">';
        $html .= '<label class="bcg-field-label">Upload a Photo</label><input type="file" name="event_image" class="bcg-form-input" accept="image/jpeg, image/png, image/webp" style="padding-bottom:35px;">';
        $html .= '<div style="margin-bottom:24px;"><label><input type="checkbox" name="event_terms" required> I agree to the Terms & Conditions.</label></div>';
        $html .= '<button type="submit" class="bcg-submit-btn">' . esc_html($btn_label) . '</button>';
        $html .= '</form></div>';

        $html .= '<script>
            document.addEventListener("DOMContentLoaded", function() {
                const annualCheckbox = document.getElementById("event_is_annual");
                const annualFields = document.getElementById("bcg_annual_fields");
                const dateInputs = document.querySelectorAll(".bcg-date-req");
                if(annualCheckbox) {
                    annualCheckbox.addEventListener("change", function() {
                        if(this.checked) { annualFields.style.display = "block"; dateInputs.forEach(d => d.removeAttribute("required")); } 
                        else { annualFields.style.display = "none"; dateInputs.forEach(d => d.setAttribute("required", "true")); }
                    });
                    if(annualCheckbox.checked) { dateInputs.forEach(d => d.removeAttribute("required")); }
                }
            });
        </script>';
        return $html;
    }
}
add_action('init', function() {
    add_shortcode( 'villocity_submit_event', 'bcg_render_event_form' );
});

// ==========================================
// 4. SHORTCODE: LIVE FEED DISPLAY & MODAL
// ==========================================
if ( ! function_exists( 'bcg_render_events_feed' ) ) {
    function bcg_render_events_feed() {
        if ( function_exists( 'bcg_events_auto_rollover' ) ) {
            bcg_events_auto_rollover();
        }
        
        $layout_style   = get_option('villocity_global_layout_style', 'boxed');
        $primary_col    = get_option('villocity_global_primary', get_option('bcg_events_primary_color', '#860038'));
        $accent_col     = get_option('villocity_global_accent', get_option('bcg_events_accent_color', '#fadceb'));
        $text_col       = get_option('villocity_global_text', get_option('bcg_events_text_color', '#1c1917'));
        $bg_col         = get_option('villocity_global_bg', get_option('bcg_events_bg_color', '#fafaf9'));
        $btn_bg         = get_option('villocity_global_btn_bg', get_option('bcg_events_btn_bg', '#860038'));
        $btn_text       = get_option('villocity_global_btn_text', get_option('bcg_events_btn_text', '#ffffff'));
        $btn_hover_bg   = get_option('villocity_global_btn_hover_bg', get_option('bcg_events_btn_hover_bg', '#3a6690'));
        $btn_hover_text = get_option('villocity_global_btn_hover_text', get_option('bcg_events_btn_hover_text', '#ffffff'));
        
        $track_class = current_user_can( 'manage_options' ) ? '' : ' bcg-track-action';
        
        $today_ts = strtotime(date('Y-m-d', current_time('timestamp')));
        $day_of_week = (int) date('w', $today_ts); 
        $days_to_sunday = ($day_of_week == 0) ? 0 : (7 - $day_of_week);
        $week_end_date = date('Y-m-d', strtotime("+$days_to_sunday days", $today_ts));
        
        $all_query = new WP_Query( array('post_type' => 'bcg_event', 'post_status' => 'publish', 'posts_per_page' => -1) );

        $active_posts = array(); $annual_posts = array();
        if ( $all_query->have_posts() ) {
            foreach ( $all_query->posts as $p ) {
                $start = get_post_meta($p->ID, '_event_start_date', true);
                $end   = get_post_meta($p->ID, '_event_end_date', true);
                if (empty($end)) $end = $start;
                
                $is_annual = get_post_meta($p->ID, '_event_is_annual', true) === 'yes';
                $start_ts = (!empty($start) && strtotime($start)) ? strtotime($start) : 0;
                $end_ts   = (!empty($end) && strtotime($end)) ? strtotime($end) : $start_ts;
                if (date('Y', $start_ts) === '1970') { $start_ts = 0; $end_ts = 0; }

                if ($start_ts !== 0 && ($end_ts >= $today_ts || $start_ts >= $today_ts)) {
                    $active_posts[] = $p;
                } else {
                    if ($is_annual || $start_ts === 0) { $annual_posts[] = $p; }
                }
            }
        }

        // SMART SORTING FOR ACTIVE POSTS
        usort($active_posts, function($a, $b) use ($today_ts) {
            $feat_a = get_post_meta($a->ID, '_event_featured', true) === 'yes' ? 1 : 0;
            $feat_b = get_post_meta($b->ID, '_event_featured', true) === 'yes' ? 1 : 0;
            if ($feat_a !== $feat_b) return $feat_b <=> $feat_a;

            $start_a = get_post_meta($a->ID, '_event_start_date', true); 
            $start_b = get_post_meta($b->ID, '_event_start_date', true); 
            $end_a = get_post_meta($a->ID, '_event_end_date', true); 
            $end_b = get_post_meta($b->ID, '_event_end_date', true); 
            
            $s_a = (!empty($start_a) && strtotime($start_a)) ? strtotime($start_a) : PHP_INT_MAX; 
            $s_b = (!empty($start_b) && strtotime($start_b)) ? strtotime($start_b) : PHP_INT_MAX; 
            $e_a = (!empty($end_a) && strtotime($end_a)) ? strtotime($end_a) : $s_a; 
            $e_b = (!empty($end_b) && strtotime($end_b)) ? strtotime($end_b) : $s_b; 
            
            $eff_a = $s_a;
            if ($s_a < $today_ts && $e_a >= $today_ts && $s_a !== $e_a) {
                $dow_a = (int) date('w', $s_a); $today_dow = (int) date('w', $today_ts);
                if ($dow_a === $today_dow) { $eff_a = $today_ts; } else {
                    $days_ahead = ($dow_a - $today_dow + 7) % 7;
                    $next_date = strtotime("+$days_ahead days", $today_ts);
                    $eff_a = ($next_date <= $e_a) ? $next_date : $e_a;
                }
            }

            $eff_b = $s_b;
            if ($s_b < $today_ts && $e_b >= $today_ts && $s_b !== $e_b) {
                $dow_b = (int) date('w', $s_b); $today_dow = (int) date('w', $today_ts);
                if ($dow_b === $today_dow) { $eff_b = $today_ts; } else {
                    $days_ahead = ($dow_b - $today_dow + 7) % 7;
                    $next_date = strtotime("+$days_ahead days", $today_ts);
                    $eff_b = ($next_date <= $e_b) ? $next_date : $e_b;
                }
            }

            if ($eff_a === $eff_b) { return strtotime($b->post_date) <=> strtotime($a->post_date); }
            return $eff_a <=> $eff_b;
        });

        // SORTING FOR ANNUAL EVENTS
        usort($annual_posts, function($a, $b) {
            $feat_a = get_post_meta($a->ID, '_event_featured', true) === 'yes' ? 1 : 0;
            $feat_b = get_post_meta($b->ID, '_event_featured', true) === 'yes' ? 1 : 0;
            if ($feat_a !== $feat_b) return $feat_b <=> $feat_a;
            
            $date_a = get_post_meta($a->ID, '_event_start_date', true); $date_b = get_post_meta($b->ID, '_event_start_date', true);
            $md_a = empty($date_a) ? '9999' : date('md', strtotime($date_a)); $md_b = empty($date_b) ? '9999' : date('md', strtotime($date_b));
            return strcmp($md_a, $md_b);
        });

        $html = '<style>
            .bcg-feed-wrap { 
                --bcg-primary: ' . esc_attr($primary_col) . '; 
                --bcg-accent: ' . esc_attr($accent_col) . '; 
                --bcg-text: ' . esc_attr($text_col) . '; 
                --bcg-bg: ' . esc_attr($bg_col) . '; 
                font-family: var(--voc-font-family, inherit) !important; background-color: var(--bcg-bg); color: var(--bcg-text); border-radius: 16px; overflow: hidden; border: 1px solid #e7e5e4; box-shadow: 0 4px 20px rgba(0,0,0,0.03); 
            }
            
            .bcg-feed-wrap.bcg-style-clean, .bcg-feed-wrap.bcg-style-minimal-grid { border: none !important; box-shadow: none !important; background: transparent !important; padding: 0 !important; }
            .bcg-feed-wrap.bcg-style-clean .bcg-feed-header, .bcg-feed-wrap.bcg-style-minimal-grid .bcg-feed-header { padding: 0 0 24px 0 !important; background: transparent !important; border-bottom: 2px solid #e7e5e4 !important; margin-bottom: 24px; }
            .bcg-feed-wrap.bcg-style-clean .bcg-feed-body, .bcg-feed-wrap.bcg-style-minimal-grid .bcg-feed-body { padding: 0 !important; }
            
            .bcg-feed-wrap.bcg-style-clean .bcg-grid { display: flex !important; flex-direction: column !important; gap: 32px !important; }
            .bcg-feed-wrap.bcg-style-clean .bcg-card { display: flex !important; flex-direction: row !important; align-items: flex-start !important; background: transparent !important; border: none !important; border-bottom: 1px solid #e7e5e4 !important; border-radius: 0 !important; box-shadow: none !important; padding: 0 0 32px 0 !important; gap: 32px; margin: 0 !important; transition: none !important; }
            .bcg-feed-wrap.bcg-style-clean .bcg-card:last-child { border-bottom: none !important; }
            .bcg-feed-wrap.bcg-style-clean .bcg-card:hover { transform: none !important; }
            .bcg-feed-wrap.bcg-style-clean .bcg-card-img-wrap { width: 280px !important; height: 200px !important; min-height: 200px !important; border-radius: 12px !important; margin: 0 !important; flex-shrink: 0; }
            .bcg-feed-wrap.bcg-style-clean .bcg-card-body { padding: 0 !important; flex-direction: column !important; width: 100%; display: flex; }
            .bcg-feed-wrap.bcg-style-clean .bcg-card-title { font-size: 24px !important; margin: 10px 0 !important; font-family: var(--voc-font-family, inherit) !important; }
            .bcg-feed-wrap.bcg-style-clean .bcg-card.bcg-featured { border-left: none !important; padding-left: 0 !important; background: transparent !important; }
            .bcg-feed-wrap.bcg-style-clean .bcg-card.bcg-featured .bcg-card-img-wrap { outline: 3px solid var(--bcg-primary); outline-offset: 2px; }
            
            @media (max-width: 768px) {
                .bcg-feed-wrap.bcg-style-clean .bcg-card { flex-direction: column !important; gap: 20px !important; align-items: stretch !important; padding: 0 0 32px 0 !important; }
                .bcg-feed-wrap.bcg-style-clean .bcg-card-img-wrap { width: 100% !important; height: 220px !important; }
                .bcg-feed-wrap.bcg-style-clean .bcg-card-btn { width: 100% !important; justify-content: center; }
            }

            .bcg-feed-header { background-color: rgba(0,0,0,0.02); padding: 32px 24px; border-bottom: 1px solid #e7e5e4; }
            @media (min-width: 768px) { .bcg-feed-header { padding: 48px; } }
            .bcg-badge-label { color: var(--bcg-primary); font-weight: 700; text-transform: uppercase; font-size: 14px; display: flex; align-items: center; gap: 8px; margin-bottom: 8px; }
            
            .bcg-feed-title { font-size: 36px; font-weight: 800; color: var(--bcg-primary) !important; margin: 0 0 8px 0 !important; line-height: 1.1; font-family: var(--voc-font-family, inherit) !important; }
            .bcg-section-title { font-size: 24px; font-weight: 800; color: var(--bcg-primary) !important; margin: 48px 0 24px 0; padding-bottom: 12px; border-bottom: 2px solid #e7e5e4; display: flex; align-items: center; gap: 8px; font-family: var(--voc-font-family, inherit) !important; }
            .bcg-card-title a { color: var(--bcg-primary) !important; text-decoration: none; }
            
            @media (min-width: 768px) { .bcg-feed-title { font-size: 48px; } }
            .bcg-feed-subtitle { font-size: 18px; color: var(--bcg-text); opacity: 0.8; margin: 0 !important; }
            .bcg-feed-body { padding: 24px; }
            @media (min-width: 1024px) { .bcg-feed-body { padding: 40px; } }

            .bcg-search-container { margin-bottom: 20px; }
            .bcg-search-input-wrap { display: flex; align-items: center; background: #fff; border: 1px solid #cbd5e1; border-radius: 12px; padding: 12px 16px; box-shadow: 0 2px 8px rgba(0,0,0,0.02); }
            .bcg-search-input-wrap input { border: none !important; width: 100% !important; background: transparent !important; font-size: 16px !important; outline: none !important; }
            
            .bcg-filters { display: flex; gap: 8px; margin-bottom: 32px; flex-wrap: wrap; }
            .bcg-pill { background: transparent !important; border: 1px solid #cbd5e1 !important; padding: 8px 18px !important; border-radius: 24px !important; font-size: 14px !important; font-weight: 600 !important; cursor: pointer !important; color: #475569 !important; transition: all 0.2s ease !important; display: inline-flex; align-items: center; gap: 6px; }
            .bcg-pill:hover, .bcg-pill:focus-visible { border-color: var(--bcg-primary) !important; color: var(--bcg-primary) !important; background: #fff !important; box-shadow: 0 2px 8px rgba(0,0,0,0.05) !important; }
            .bcg-pill.active { background: var(--bcg-primary) !important; color: #ffffff !important; border-color: var(--bcg-primary) !important; box-shadow: 0 4px 12px rgba(0,0,0,0.15) !important; }

            .bcg-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 24px; }
            .bcg-card { background: #fff; border: 1px solid #e7e5e4; border-radius: 16px; display: flex; flex-direction: column; overflow: hidden; position: relative; box-shadow: 0 4px 15px rgba(0,0,0,0.03); transition: transform 0.2s; }
            .bcg-card:hover { transform: translateY(-3px); box-shadow: 0 8px 25px rgba(0,0,0,0.06); }
            
            .bcg-card-img-wrap { position: relative !important; width: 100% !important; height: 220px !important; background: #e7e5e4 !important; flex-shrink: 0 !important; overflow: hidden !important; display: block !important; margin: 0 !important; padding: 0 !important; border: none !important; line-height: 0 !important; }
            .bcg-card-img { position: absolute !important; top: 0 !important; left: 0 !important; right: 0 !important; bottom: 0 !important; width: 100% !important; height: 100% !important; object-fit: cover !important; display: block !important; transition: transform 0.3s !important; margin: 0 !important; padding: 0 !important; border: none !important; max-width: none !important; }
            .bcg-card:not(.bcg-cancelled):hover .bcg-card-img { transform: scale(1.05) !important; }
            
            .bcg-placeholder-img { width: 100% !important; height: 100% !important; background: linear-gradient(135deg, var(--bcg-accent) 0%, var(--bcg-bg) 100%) !important; display: flex !important; align-items: center !important; justify-content: center !important; position: absolute !important; top: 0; left: 0; right: 0; bottom: 0; }
            .bcg-placeholder-icon svg { width: 80px !important; height: 80px !important; color: var(--bcg-primary); opacity: 0.15; }
            
            .bcg-card-date-badge { position: absolute; top: 12px; right: 12px; background: rgba(255,255,255,0.95); color: var(--bcg-primary); padding: 10px 14px; border-radius: 12px; font-weight: 800; font-size: 18px; text-align: center; line-height: 1.1; box-shadow: 0 4px 12px rgba(0,0,0,0.15); z-index: 20; }
            .bcg-card-date-badge span { display: block; font-size: 12px; text-transform: uppercase; font-weight: 700; color: #57534e; margin-bottom: 2px; }
            
            .bcg-card-body { padding: 20px; display: flex; flex-direction: column; flex-grow: 1; background: #fff; z-index: 3; }
            .bcg-badge { background: var(--bcg-accent); color: var(--bcg-primary); font-size: 11px; font-weight: bold; padding: 4px 10px; border-radius: 12px; display: inline-flex; align-items: center; margin-bottom: 12px; gap: 4px; align-self: flex-start; }
            .bcg-card-title { font-size: 20px; font-weight: 800; margin: 0 0 10px 0; line-height: 1.3; font-family: var(--voc-font-family, inherit) !important; }
            .bcg-card-title a:hover, .bcg-card-title a:focus-visible { outline: 2px solid var(--bcg-primary); }
            .bcg-card-desc { font-size: 14px; line-height: 1.5; margin: 12px 0; flex-grow: 1; opacity: 0.9; color: var(--bcg-text); }
            
            .bcg-card-btn { background-color: ' . esc_attr($btn_bg) . ' !important; color: ' . esc_attr($btn_text) . ' !important; padding: 10px 20px !important; border-radius: 24px !important; text-decoration: none !important; font-size: 14px !important; font-weight: 600 !important; border: none !important; display: inline-flex !important; justify-content: center !important; align-items: center !important; transition: all 0.2s ease !important; align-self: flex-start; }
            .bcg-card-btn:hover, .bcg-card-btn:focus-visible { background-color: ' . esc_attr($btn_hover_bg) . ' !important; color: ' . esc_attr($btn_hover_text) . ' !important; box-shadow: 0 4px 12px rgba(0,0,0,0.15) !important; }
            .bcg-open-modal-btn { background-color: ' . esc_attr($btn_bg) . ' !important; color: ' . esc_attr($btn_text) . ' !important; padding: 14px 28px !important; border-radius: 24px !important; font-weight: 600 !important; font-size: 15px !important; border: none !important; cursor: pointer !important; display: inline-flex !important; align-items: center !important; gap: 8px !important; transition: all 0.2s ease !important; }
            .bcg-open-modal-btn:hover, .bcg-open-modal-btn:focus-visible { background-color: ' . esc_attr($btn_hover_bg) . ' !important; color: ' . esc_attr($btn_hover_text) . ' !important; box-shadow: 0 4px 12px rgba(0,0,0,0.15) !important; }

            .bcg-card.bcg-featured { grid-column: 1 / -1; order: -1; border: 2px solid var(--bcg-primary); box-shadow: 0 8px 30px rgba(0,0,0,0.08); }
            @media(min-width:768px) { .bcg-card.bcg-featured { flex-direction: row; } .bcg-card.bcg-featured .bcg-card-img-wrap { width: 50% !important; height: auto !important; min-height: 320px !important; } .bcg-card.bcg-featured .bcg-card-body { width: 50%; justify-content: center; padding: 40px; } }
            
            .bcg-modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 999999; display: none; align-items: center; justify-content: center; padding: 20px; box-sizing: border-box; backdrop-filter: blur(4px); }
            .bcg-modal-overlay.bcg-show { display: flex; }
            .bcg-modal-content { background: #fff; border-radius: 16px; width: 100%; max-width: 700px; max-height: 90vh; display: flex; flex-direction: column; overflow: hidden; position: relative; box-shadow: 0 10px 40px rgba(0,0,0,0.2); }
            .bcg-modal-header { display: flex; justify-content: space-between; align-items: center; padding: 20px 32px; border-bottom: 1px solid #e7e5e4; background: #fafaf9; }
            .bcg-modal-title { font-size: 20px !important; font-weight: 800 !important; margin: 0 !important; display: flex; align-items: center; gap: 8px; color: var(--bcg-text) !important; font-family: var(--voc-font-family, inherit) !important; }
            .bcg-modal-close { background: transparent !important; border: none !important; font-size: 28px !important; cursor: pointer !important; color: #a8a29e !important; padding: 0 !important; transition: 0.2s !important; }
            .bcg-modal-close:hover { color: var(--bcg-primary) !important; transform: scale(1.1); }
            .bcg-modal-body { overflow-y: auto; padding: 0; }
        </style>';

        $html .= '<div class="bcg-feed-wrap bcg-style-' . esc_attr($layout_style) . '">';
        $html .= '<div class="bcg-feed-header"><div class="bcg-badge-label">'.villocity_get_icon('calendar').' <span>Community Calendar</span></div><h1 class="bcg-feed-title">Upcoming Events</h1></div>';
        $html .= '<div class="bcg-feed-body">';

        // SEARCH & FILTERS
        $html .= '<div class="bcg-search-container"><div class="bcg-search-input-wrap"><span style="color:#64748b; margin-right:10px; display:flex;">' . villocity_get_icon('search') . '</span><input type="text" id="bcg-events-search-input" placeholder="Search events, categories, or keywords..."></div></div>';
        $html .= '<nav class="bcg-filters"><button class="bcg-pill active" data-filter="all">All Events</button><button class="bcg-pill" data-filter="Week">This Week</button><button class="bcg-pill" data-filter="Ongoing">Ongoing</button><button class="bcg-pill" data-filter="Festival">Festivals</button><button class="bcg-pill" data-filter="Family & Kids">Family & Kids</button><button class="bcg-pill" data-filter="Music & Concerts">Music</button><button class="bcg-pill" data-filter="Annual">Annual Events</button></nav>';

        // ACTIVE POSTS LOOP
        $html .= '<div class="bcg-grid">';
        if ( ! empty($active_posts) ) {
            foreach ( $active_posts as $post_item ) {
                $id = $post_item->ID; $category = get_post_meta($id, '_event_category', true);
                $start = get_post_meta($id, '_event_start_date', true); $end = get_post_meta($id, '_event_end_date', true);
                if (empty($end)) $end = $start;
                $is_annual = get_post_meta($id, '_event_is_annual', true) === 'yes';
                $is_featured = get_post_meta($id, '_event_featured', true) === 'yes';
                
                $start_ts = (!empty($start) && strtotime($start)) ? strtotime($start) : 0;
                $end_ts   = (!empty($end) && strtotime($end)) ? strtotime($end) : $start_ts;
                
                $is_ongoing = ($start_ts < $today_ts && $end_ts >= $today_ts && $start_ts !== $end_ts);
                $is_week = ($start_ts <= strtotime("+$days_to_sunday days", $today_ts) && $end_ts >= $today_ts);
                
                $featured_class = $is_featured ? ' bcg-featured' : '';
                $badge_icon = $is_featured ? villocity_get_icon('star') . 'Featured &bull; ' : ($is_annual ? villocity_get_icon('refresh') . 'Annual &bull; ' : villocity_get_icon('tag'));
                
                $img = get_the_post_thumbnail_url($id, 'large');
                if (empty($img)) {
                    $migrated_img = get_post_meta($id, '_migrated_acf_image_url', true);
                    if (is_array($migrated_img) && isset($migrated_img['url']) && strpos($migrated_img['url'], 'http') !== false) { $img = $migrated_img['url']; } 
                    else if (is_string($migrated_img) && strpos($migrated_img, 'http') !== false) { $img = $migrated_img; } else { $img = false; }
                }

                $permalink = get_permalink($id);
                $clean_excerpt = wp_trim_words(wp_strip_all_tags($post_item->post_content), 22, '...');
                $filter_tags = esc_attr($category) . ' ' . ($is_annual ? 'Annual ' : '') . ($is_week ? 'Week ' : '') . ($is_ongoing ? 'Ongoing ' : '');

                $display_ts = $start_ts;
                if ($is_ongoing) {
                    $dow = (int) date('w', $start_ts); $today_dow = (int) date('w', $today_ts);
                    if ($dow === $today_dow) { $display_ts = $today_ts; } else {
                        $days_ahead = ($dow - $today_dow + 7) % 7;
                        $next_date = strtotime("+$days_ahead days", $today_ts);
                        $display_ts = ($next_date <= $end_ts) ? $next_date : $end_ts;
                    }
                }

                $date_badge = '<div class="bcg-card-date-badge"><span>' . date('M', $display_ts) . '</span>' . date('j', $display_ts) . '</div>';
                $img_html = $img ? '<div class="bcg-card-img-wrap"><a href="'.esc_url($permalink).'"><img src="'.esc_url($img).'" class="bcg-card-img"></a>' . $date_badge . '</div>' 
                                 : '<div class="bcg-card-img-wrap"><a href="'.esc_url($permalink).'"><div class="bcg-placeholder-img"><span class="bcg-placeholder-icon">' . villocity_get_icon('calendar') . '</span></div></a>' . $date_badge . '</div>';

                $html .= '<article class="bcg-card'.$featured_class.'" data-type="'.$filter_tags.'">
                    ' . $img_html . '
                    <div class="bcg-card-body">
                        <div class="bcg-card-content-stack">
                            <span class="bcg-badge">' . $badge_icon . esc_html($category) . '</span>
                            <h2 class="bcg-card-title"><a href="' . esc_url($permalink) . '">' . esc_html(get_the_title($id)) . '</a></h2>
                            <p class="bcg-card-desc">' . esc_html($clean_excerpt) . '</p>
                        </div>
                        <a href="' . esc_url($permalink) . '" class="bcg-card-btn' . $track_class . '" data-id="' . esc_attr($id) . '" data-type="view">View Details</a>
                    </div>
                </article>';
            }
        } else { $html .= '<p>There are no active events scheduled at this time.</p>'; }
        $html .= '</div>'; 

        // ANNUAL POSTS LOOP
        if ( ! empty($annual_posts) ) {
            $html .= '<h2 class="bcg-section-title" data-type="Annual">' . villocity_get_icon('refresh') . ' Annual Events</h2>';
            $html .= '<div class="bcg-grid" data-type="Annual">';
            foreach ( $annual_posts as $post_item ) {
                $id = $post_item->ID; $category = get_post_meta($id, '_event_category', true);
                $timeframe = get_post_meta($id, '_event_annual_timeframe', true);
                $is_featured = get_post_meta($id, '_event_featured', true) === 'yes';
                $featured_class = $is_featured ? ' bcg-featured' : '';
                
                $img = get_the_post_thumbnail_url($id, 'large');
                if (empty($img)) {
                    $migrated_img = get_post_meta($id, '_migrated_acf_image_url', true);
                    if (is_array($migrated_img) && isset($migrated_img['url']) && strpos($migrated_img['url'], 'http') !== false) { $img = $migrated_img['url']; } 
                    else if (is_string($migrated_img) && strpos($migrated_img, 'http') !== false) { $img = $migrated_img; } else { $img = false; }
                }

                $permalink = get_permalink($id);
                $clean_excerpt = wp_trim_words(wp_strip_all_tags($post_item->post_content), 22, '...');
                $date_badge = '<div class="bcg-card-date-badge"><span>Dates</span><br>TBD</div>';
                
                $img_html = $img ? '<div class="bcg-card-img-wrap"><a href="'.esc_url($permalink).'"><img src="'.esc_url($img).'" class="bcg-card-img"></a>' . $date_badge . '</div>' 
                                 : '<div class="bcg-card-img-wrap"><a href="'.esc_url($permalink).'"><div class="bcg-placeholder-img"><span class="bcg-placeholder-icon">' . villocity_get_icon('star') . '</span></div></a>' . $date_badge . '</div>';

                $html .= '<article class="bcg-card'.$featured_class.'" data-type="'.esc_attr($category).' Annual">
                    ' . $img_html . '
                    <div class="bcg-card-body">
                        <div class="bcg-card-content-stack">
                            <span class="bcg-badge" style="background:#fef3c7; color:#92400e;">' . ($is_featured ? villocity_get_icon('star').'Featured &bull; ' : '') . villocity_get_icon('star').'Annual Event</span>
                            <h2 class="bcg-card-title"><a href="' . esc_url($permalink) . '">' . esc_html(get_the_title($id)) . '</a></h2>
                            <div style="font-size:13px; margin-bottom:8px;">' . villocity_get_icon('calendar') . ' <strong>' . esc_html($timeframe) . '</strong></div>
                            <p class="bcg-card-desc">' . esc_html($clean_excerpt) . '</p>
                        </div>
                        <a href="' . esc_url($permalink) . '" class="bcg-card-btn' . $track_class . '" data-id="' . esc_attr($id) . '" data-type="view">View Details</a>
                    </div>
                </article>';
            }
            $html .= '</div>';
        }

        $html .= '<div style="text-align: center; margin-top: 40px;"><button id="bcg-open-modal" class="bcg-open-modal-btn">' . villocity_get_icon('plus') . ' Submit an Event</button></div>';

        // MODAL INJECTION
        $html .= '<div id="bcg-submission-modal" class="bcg-modal-overlay">
            <div class="bcg-modal-content">
                <div class="bcg-modal-header"><h2 class="bcg-modal-title">' . villocity_get_icon('calendar') . ' Submit or Edit Event</h2><button id="bcg-close-modal" class="bcg-modal-close">&times;</button></div>
                <div class="bcg-modal-body">' . bcg_render_event_form( array('is_modal' => 'true') ) . '</div>
            </div>
        </div>';

        // JS ENGINE
        $html .= '<script>
            document.addEventListener("DOMContentLoaded", function() {
                const isAdmin = ' . (current_user_can("manage_options") ? "true" : "false") . ';
                const pills = document.querySelectorAll(".bcg-pill");
                const cards = document.querySelectorAll(".bcg-card");
                const sectionTitles = document.querySelectorAll(".bcg-section-title");
                const searchInput = document.getElementById("bcg-events-search-input");
                let currentFilter = "all"; let searchQuery = "";

                function runFilters() {
                    cards.forEach(card => {
                        const typeMatches = currentFilter === "all" || card.getAttribute("data-type").includes(currentFilter);
                        const textContent = card.textContent.toLowerCase();
                        const searchMatches = searchQuery === "" || textContent.includes(searchQuery);
                        card.style.display = (typeMatches && searchMatches) ? "flex" : "none";
                    });
                    sectionTitles.forEach(title => {
                        const typeMatches = currentFilter === "all" || title.getAttribute("data-type").includes(currentFilter);
                        title.style.display = typeMatches ? "flex" : "none";
                    });
                }
                pills.forEach(pill => {
                    pill.addEventListener("click", function() {
                        pills.forEach(p => p.classList.remove("active"));
                        this.classList.add("active");
                        currentFilter = this.getAttribute("data-filter");
                        runFilters();
                    });
                });
                if (searchInput) {
                    searchInput.addEventListener("input", function(e) {
                        searchQuery = e.target.value.toLowerCase().trim();
                        runFilters();
                    });
                }

                document.querySelectorAll(".bcg-track-action").forEach(link => {
                    link.addEventListener("click", function() {
                        if (!isAdmin) fetch("/wp-json/bcg-events/v1/track-action", { method: "POST", headers: { "Content-Type": "application/json" }, body: JSON.stringify({ post_id: this.getAttribute("data-id"), type: this.getAttribute("data-type") }) });
                    });
                });

                const modal = document.getElementById("bcg-submission-modal");
                const openBtn = document.getElementById("bcg-open-modal");
                const closeBtn = document.getElementById("bcg-close-modal");
                function openModal() { if(modal) { modal.classList.add("bcg-show"); document.body.style.overflow = "hidden"; } }
                if (window.location.search.includes("edit_id=") && window.location.search.includes("token=")) { openModal(); }
                if(openBtn && modal && closeBtn) {
                    openBtn.addEventListener("click", openModal);
                    closeBtn.addEventListener("click", function() { modal.classList.remove("bcg-show"); document.body.style.overflow = ""; });
                    modal.addEventListener("click", function(e) { if(e.target === modal) { modal.classList.remove("bcg-show"); document.body.style.overflow = ""; } });
                    document.addEventListener("keydown", function(e) { if (e.key === "Escape" && modal.classList.contains("bcg-show")) { modal.classList.remove("bcg-show"); document.body.style.overflow = ""; } });
                }
            });
        </script>';
        $html .= '</div></div>';
        return $html;
    }
}
add_action('init', function() {
    add_shortcode( 'villocity_live_events', 'bcg_render_events_feed' );
});

// ==========================================
// 5. RENDER SHORTCODE: [villocity_annual_events]
// ==========================================
add_shortcode( 'villocity_annual_events', 'villocity_render_annual_events_shortcode' );
function villocity_render_annual_events_shortcode( $atts = array() ) {
    $primary_col = get_option( 'villocity_global_primary', '#860038' );
    $text_col    = get_option( 'villocity_global_text', '#1c1917' );

    $atts = shortcode_atts( array(
        'count'      => '-1',
        'title'      => 'Annual Events',
        'subtitle'   => 'RECURRING EVENTS',
        'char_limit' => '90' // Limits descriptions to match the screenshot length
    ), $atts, 'villocity_annual_events' );

    $limit = intval( $atts['char_limit'] );

    $args = array(
        'post_type'      => 'bcg_event',
        'post_status'    => 'publish',
        'posts_per_page' => intval( $atts['count'] ),
        'meta_query'     => array(
            'relation' => 'OR',
            array(
                'key'     => '_event_is_annual',
                'value'   => 'yes',
                'compare' => '='
            ),
            array(
                'key'     => '_event_recurring_text',
                'compare' => 'EXISTS'
            )
        )
    );

    $annual_query = new WP_Query( $args );

    ob_start();
    ?>
    <style id="villocity-annual-events-css">
        .voc-annual-events-wrap {
            font-family: var(--voc-font-family, inherit) !important;
            color: <?php echo esc_attr($text_col); ?> !important;
            max-width: 800px;
            margin: 20px 0;
        }
        .voc-annual-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
        }
        .voc-annual-icon {
    color: <?php echo esc_attr($primary_col); ?>;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.voc-annual-icon svg {
    width: 44px !important;
    height: 44px !important;
    min-width: 44px !important;
    min-height: 44px !important;
    stroke-width: 2.2 !important;
}
        .voc-annual-title {
            font-size: 32px !important;
            font-weight: 800 !important;
            color: #0f172a !important;
            margin: 0 !important;
            line-height: 1.1 !important;
        }
        .voc-annual-subtitle {
            font-size: 12px !important;
            font-weight: 800 !important;
            text-transform: uppercase !important;
            letter-spacing: 0.08em !important;
            color: #64748b !important;
            margin-bottom: 24px !important;
        }
        .voc-annual-list {
            list-style: none !important;
            padding: 0 !important;
            margin: 0 !important;
        }
        .voc-annual-item {
            padding: 18px 0;
            border-bottom: 1px solid #e2e8f0;
        }
        .voc-annual-item:first-child {
            padding-top: 0;
        }
        .voc-annual-item-name {
            font-size: 20px !important;
            font-weight: 800 !important;
            color: <?php echo esc_attr($primary_col); ?> !important;
            margin: 0 0 6px 0 !important;
            line-height: 1.2 !important;
        }
        .voc-annual-item-name a {
            color: inherit !important;
            text-decoration: none !important;
        }
        .voc-annual-item-name a:hover,
        .voc-annual-item-name a:focus-visible {
            text-decoration: underline !important;
        }
        .voc-annual-item-details {
            font-size: 15px !important;
            color: #475569 !important;
            line-height: 1.5 !important;
            margin: 0 !important;
        }
    </style>

    <section class="voc-annual-events-wrap" aria-labelledby="voc-annual-heading">
        <div class="voc-annual-header">
            <span class="voc-annual-icon">
                <?php echo function_exists('villocity_get_icon') ? villocity_get_icon('calendar') : '📅'; ?>
            </span>
            <h2 id="voc-annual-heading" class="voc-annual-title"><?php echo esc_html($atts['title']); ?></h2>
        </div>

        <div class="voc-annual-subtitle"><?php echo esc_html($atts['subtitle']); ?></div>

        <div class="voc-annual-list">
            <?php if ( $annual_query->have_posts() ) : ?>
                <?php while ( $annual_query->have_posts() ) : $annual_query->the_post(); 
                    $id = get_the_ID();
                    $raw_text = get_post_meta( $id, '_event_recurring_text', true );
                    if ( empty($raw_text) ) {
                        $raw_text = get_post_meta( $id, '_event_schedule_details', true );
                    }
                    if ( empty($raw_text) ) {
                        $raw_text = get_the_excerpt();
                    }

                    // Trim string to character limit cleanly without cutting off words abruptly
                    $trimmed_text = ( mb_strlen( $raw_text ) > $limit ) ? mb_strimwidth( $raw_text, 0, $limit, '...' ) : $raw_text;
                ?>
                    <article class="voc-annual-item">
                        <h3 class="voc-annual-item-name">
                            <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                        </h3>
                        <?php if ( ! empty($trimmed_text) ) : ?>
                            <p class="voc-annual-item-details"><?php echo esc_html($trimmed_text); ?></p>
                        <?php endif; ?>
                    </article>
                <?php endwhile; wp_reset_postdata(); ?>
            <?php else : ?>
                <!-- Clean Fallback Preset Items -->
                <article class="voc-annual-item">
                    <h3 class="voc-annual-item-name">4th of July Celebration</h3>
                    <p class="voc-annual-item-details">Parade: 7PM · Festivities on the Green: 7-9:00 PM · Fireworks: 9:30 PM (Dark)</p>
                </article>
                <article class="voc-annual-item">
                    <h3 class="voc-annual-item-name">5K Jingle Jog</h3>
                    <p class="voc-annual-item-details">Saturday Morning After Thanksgiving</p>
                </article>
                <article class="voc-annual-item">
                    <h3 class="voc-annual-item-name">Annual Shoppers’ Stroll & Holiday Parade</h3>
                    <p class="voc-annual-item-details">Friday & Saturday After Thanksgiving</p>
                </article>
                <article class="voc-annual-item">
                    <h3 class="voc-annual-item-name">Art Rocks</h3>
                    <p class="voc-annual-item-details">Biennial Event, Summer of Each Odd Year</p>
                </article>
                <article class="voc-annual-item">
                    <h3 class="voc-annual-item-name">Clark Mills Fireman’s Field Days</h3>
                    <p class="voc-annual-item-details">Mid June Annually</p>
                </article>
            <?php endif; ?>
        </div>
    </section>
    <?php
    return ob_get_clean();
}