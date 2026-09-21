<?php
/**
 * VilloCity Module: Live Events (Admin & Backend)
 * Description: Backend administration, meta box controls, dashboard widgets, shortcodes manager, migration tools, and annual recurring event settings.
 * Version:     2.0.0 (Annual Events Shortcode Integration & ADA Compliance)
 * Text Domain: villocity-suite
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

// ==========================================
// 1. BACKEND META BOXES (NATIVE WP EDITING)
// ==========================================
if ( ! function_exists( 'bcg_events_add_meta_boxes' ) ) {
    add_action( 'add_meta_boxes', 'bcg_events_add_meta_boxes' );
    function bcg_events_add_meta_boxes() {
        add_meta_box('bcg_event_meta_box', (function_exists('villocity_get_icon') ? villocity_get_icon('calendar') : '📅') . ' Event Details & Schedule', 'bcg_events_render_meta_box', 'bcg_event', 'normal', 'high');
    }
}

if ( ! function_exists( 'bcg_events_render_meta_box' ) ) {
    function bcg_events_render_meta_box( $post ) {
        wp_nonce_field( 'bcg_events_save_meta', 'bcg_events_meta_nonce' );

        $category   = get_post_meta( $post->ID, '_event_category', true );
        $start_date = get_post_meta( $post->ID, '_event_start_date', true );
        $end_date   = get_post_meta( $post->ID, '_event_end_date', true );
        $hours      = get_post_meta( $post->ID, '_event_hours', true );
        $address    = get_post_meta( $post->ID, '_event_address', true );
        $website    = get_post_meta( $post->ID, '_event_website_url', true );
        $email      = get_post_meta( $post->ID, '_event_email', true );
        $is_annual  = get_post_meta( $post->ID, '_event_is_annual', true );
        $timeframe  = get_post_meta( $post->ID, '_event_annual_timeframe', true );
        $status     = get_post_meta( $post->ID, '_event_status', true );
        $host       = get_post_meta( $post->ID, '_event_host', true );
        
        if(empty($status)) $status = 'Active';

        echo '<style>
            .bcg-tooltip-wrap { position: relative; display: inline-block; margin-left: 6px; cursor: help; vertical-align: middle; }
            .bcg-tooltip-icon { background: #cbd5e1; color: #334155; border-radius: 50%; width: 16px; height: 16px; display: inline-flex; align-items: center; justify-content: center; font-size: 11px; font-weight: bold; font-family: monospace; font-style: normal; }
            .bcg-tooltip-text { visibility: hidden; opacity: 0; width: 220px; background-color: #1e293b; color: #fff; text-align: center; border-radius: 6px; padding: 8px; position: absolute; z-index: 10; bottom: 125%; left: 50%; transform: translateX(-50%); font-size: 12px; font-weight: normal; line-height: 1.4; transition: opacity 0.2s; box-shadow: 0 4px 6px rgba(0,0,0,0.1); pointer-events: none; }
            .bcg-tooltip-text::after { content: ""; position: absolute; top: 100%; left: 50%; margin-left: -5px; border-width: 5px; border-style: solid; border-color: #1e293b transparent transparent transparent; }
            .bcg-tooltip-wrap:hover .bcg-tooltip-text { visibility: visible; opacity: 1; }
        </style>';

        echo '<div style="display:flex; flex-wrap:wrap; gap:20px; padding:10px 0;">';
        
        echo '<div style="flex:1; min-width:250px;">';
        echo '<p style="margin-top:0;"><label for="bcg_event_status"><strong>' . (function_exists('villocity_get_icon') ? villocity_get_icon('alert') : '') . 'Event Status</strong></label> <div class="bcg-tooltip-wrap"><i class="bcg-tooltip-icon">i</i><span class="bcg-tooltip-text">Need to cancel or move indoors? Change your status here.</span></div><br>';
        echo '<select id="bcg_event_status" name="bcg_event_status" style="width:100%; max-width:100%;">';
        echo '<option value="Active" ' . selected($status, 'Active', false) . '>Active (On Schedule)</option>';
        echo '<option value="Cancelled" ' . selected($status, 'Cancelled', false) . '>Cancelled</option>';
        echo '<option value="Postponed" ' . selected($status, 'Postponed', false) . '>Postponed</option>';
        echo '<option value="Moved Indoors" ' . selected($status, 'Moved Indoors', false) . '>Moved Indoors</option>';
        echo '</select></p>';

        echo '<p><label for="bcg_event_category"><strong>' . (function_exists('villocity_get_icon') ? villocity_get_icon('tag') : '') . 'Category</strong></label><br>';
        echo '<select id="bcg_event_category" name="bcg_event_category" style="width:100%; max-width:100%;">';
        echo '<option value="Festival" ' . selected($category, 'Festival', false) . '>Festival / Fair</option>';
        echo '<option value="Music & Concerts" ' . selected($category, 'Music & Concerts', false) . '>Music & Concerts</option>';
        echo '<option value="Family & Kids" ' . selected($category, 'Family & Kids', false) . '>Family & Kids</option>';
        echo '<option value="Civic & Meeting" ' . selected($category, 'Civic & Meeting', false) . '>Civic & Meeting</option>';
        echo '<option value="Fundraiser" ' . selected($category, 'Fundraiser', false) . '>Fundraiser / Charity</option>';
        echo '<option value="Sports & Recreation" ' . selected($category, 'Sports & Recreation', false) . '>Sports & Recreation</option>';
        echo '<option value="Other" ' . selected($category, 'Other', false) . '>Other</option>';
        echo '</select></p>';

        echo '<p><label for="bcg_event_host"><strong>' . (function_exists('villocity_get_icon') ? villocity_get_icon('user') : '') . 'Hosted By / Organizer</strong></label><br>';
        echo '<input type="text" id="bcg_event_host" name="bcg_event_host" value="'.esc_attr($host).'" style="width:100%; max-width:100%;"></p>';
        echo '</div>';

        echo '<div style="flex:1; min-width:250px;">';
        echo '<p style="margin-top:0;"><label for="bcg_event_start_date"><strong>' . (function_exists('villocity_get_icon') ? villocity_get_icon('calendar') : '') . 'Start Date</strong></label><br>';
        echo '<input type="date" id="bcg_event_start_date" name="bcg_event_start_date" value="'.esc_attr($start_date).'" style="width:100%; max-width:100%;"></p>';
        echo '<p><label for="bcg_event_end_date"><strong>' . (function_exists('villocity_get_icon') ? villocity_get_icon('calendar') : '') . 'End Date</strong></label> <div class="bcg-tooltip-wrap"><i class="bcg-tooltip-icon">i</i><span class="bcg-tooltip-text">When should this event auto-archive and drop off the calendar?</span></div><br>';
        echo '<input type="date" id="bcg_event_end_date" name="bcg_event_end_date" value="'.esc_attr($end_date).'" style="width:100%; max-width:100%;"></p>';
        echo '<p><label for="bcg_event_hours"><strong>' . (function_exists('villocity_get_icon') ? villocity_get_icon('clock') : '') . 'Detailed Hours / Schedule</strong></label><br>';
        echo '<input type="text" id="bcg_event_hours" name="bcg_event_hours" value="'.esc_attr($hours).'" style="width:100%; max-width:100%;"></p>';
        echo '</div>';

        echo '<div style="flex:1; min-width:250px; background:#fdfae8; padding:15px; border-left:4px solid #f59e0b; border-radius:4px;">';
        echo '<p style="margin-top:0;"><label><input type="checkbox" name="bcg_event_is_annual" value="yes" '.checked($is_annual, 'yes', false).'> <strong style="color:#92400e;">' . (function_exists('villocity_get_icon') ? villocity_get_icon('star') : '') . 'Yearly Event / Annual Tradition</strong></label> <div class="bcg-tooltip-wrap"><i class="bcg-tooltip-icon">i</i><span class="bcg-tooltip-text">Only use this for yearly events. For weekly events, use the date range above.</span></div></p>';
        echo '<p><label for="bcg_event_annual_timeframe"><strong>General Timeframe / Recurring Details</strong></label> <div class="bcg-tooltip-wrap"><i class="bcg-tooltip-icon">i</i><span class="bcg-tooltip-text">e.g., "Friday & Saturday After Thanksgiving". Displayed on homepage feeds.</span></div><br>';
        echo '<input type="text" id="bcg_event_annual_timeframe" name="bcg_event_annual_timeframe" value="'.esc_attr($timeframe).'" style="width:100%; max-width:100%;" placeholder="e.g., Early June"></p>';
        echo '</div>';

        echo '<div style="flex:1; min-width:250px;">';
        echo '<p style="margin-top:0;"><label for="bcg_event_address"><strong>' . (function_exists('villocity_get_icon') ? villocity_get_icon('map-pin') : '') . 'Location / Address</strong></label><br>';
        echo '<input type="text" id="bcg_event_address" name="bcg_event_address" value="'.esc_attr($address).'" style="width:100%; max-width:100%;"></p>';
        echo '<p><label for="bcg_event_website_url"><strong>' . (function_exists('villocity_get_icon') ? villocity_get_icon('globe') : '') . 'Website URL</strong></label><br>';
        echo '<input type="url" id="bcg_event_website_url" name="bcg_event_website_url" value="'.esc_attr($website).'" style="width:100%; max-width:100%;"></p>';
        echo '<p><label for="bcg_event_email"><strong>' . (function_exists('villocity_get_icon') ? villocity_get_icon('mail') : '') . 'Organizer Email</strong> (Hidden)</label> <div class="bcg-tooltip-wrap"><i class="bcg-tooltip-icon">i</i><span class="bcg-tooltip-text">Hidden from public. Used for sending the secure magic edit link.</span></div><br>';
        echo '<input type="email" id="bcg_event_email" name="bcg_event_email" value="'.esc_attr($email).'" style="width:100%; max-width:100%;"></p>';
        echo '</div>';

        echo '</div>'; 
    }
}

if ( ! function_exists( 'bcg_events_save_meta_box_data' ) ) {
    add_action( 'save_post_bcg_event', 'bcg_events_save_meta_box_data' );
    function bcg_events_save_meta_box_data( $post_id ) {
        if ( ! isset( $_POST['bcg_events_meta_nonce'] ) || ! wp_verify_nonce( $_POST['bcg_events_meta_nonce'], 'bcg_events_save_meta' ) ) return;
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
        if ( ! current_user_can( 'edit_post', $post_id ) ) return;

        update_post_meta( $post_id, '_event_status', sanitize_text_field( $_POST['bcg_event_status'] ) );
        update_post_meta( $post_id, '_event_category', sanitize_text_field( $_POST['bcg_event_category'] ) );
        update_post_meta( $post_id, '_event_host', sanitize_text_field( $_POST['bcg_event_host'] ) );
        update_post_meta( $post_id, '_event_start_date', sanitize_text_field( $_POST['bcg_event_start_date'] ) );
        
        $end_date = sanitize_text_field( $_POST['bcg_event_end_date'] );
        if (empty($end_date)) { $end_date = sanitize_text_field( $_POST['bcg_event_start_date'] ); }
        update_post_meta( $post_id, '_event_end_date', $end_date );
        
        update_post_meta( $post_id, '_event_hours', sanitize_text_field( $_POST['bcg_event_hours'] ) );
        update_post_meta( $post_id, '_event_address', sanitize_text_field( $_POST['bcg_event_address'] ) );
        update_post_meta( $post_id, '_event_website_url', esc_url_raw( $_POST['bcg_event_website_url'] ) );
        update_post_meta( $post_id, '_event_email', sanitize_email( $_POST['bcg_event_email'] ) );
        
        $is_annual = isset($_POST['bcg_event_is_annual']) ? 'yes' : 'no';
        update_post_meta( $post_id, '_event_is_annual', $is_annual );
        update_post_meta( $post_id, '_event_annual_timeframe', sanitize_text_field( $_POST['bcg_event_annual_timeframe'] ) );
    }
}

// ==========================================
// 2. DASHBOARD MENU & TABS
// ==========================================
if ( ! function_exists( 'bcg_events_add_menu' ) ) {
    add_action( 'admin_menu', 'bcg_events_add_menu', 20 );
    function bcg_events_add_menu() {
        if ( menu_page_url( 'villocity-hub', false ) ) {
            add_submenu_page('villocity-hub', 'Live Events Settings', 'Live Events', 'manage_options', 'bcg-events-hub', 'bcg_render_events_dashboard');
        } else {
            add_menu_page('Live Events Settings', 'Live Events', 'manage_options', 'bcg-events-hub', 'bcg_render_events_dashboard', 'https://www.brockettcreative.com/wp-content/uploads/2026/08/Villocity-Logo-icon-wht.png', 32);
        }
    }
}

if ( ! function_exists( 'bcg_events_add_dashboard_widget' ) ) {
    add_action( 'wp_dashboard_setup', 'bcg_events_add_dashboard_widget' );
    function bcg_events_add_dashboard_widget() {
        if ( current_user_can( 'manage_options' ) ) {
            wp_add_dashboard_widget('bcg_events_dashboard_widget', '📅 Live Events - At a Glance', 'bcg_events_render_dashboard_widget');
        }
    }
}

if ( ! function_exists( 'bcg_events_render_dashboard_widget' ) ) {
    function bcg_events_render_dashboard_widget() {
        $today = current_time('Y-m-d');
        $pending_query = new WP_Query( array( 'post_type' => 'bcg_event', 'post_status' => 'pending', 'posts_per_page' => -1 ) );
        $active_query  = new WP_Query( array( 'post_type' => 'bcg_event', 'post_status' => 'publish', 'posts_per_page' => -1, 'meta_query' => array(array('key' => '_event_end_date', 'value' => $today, 'compare' => '>=', 'type' => 'DATE'))) );

        $total_views = 0; $total_clicks = 0; $active_posts = $active_query->posts;

        if ( !empty($active_posts) ) {
            foreach ( $active_posts as $post ) {
                $total_views += (int) get_post_meta( $post->ID, '_event_views', true );
                $total_clicks += (int) get_post_meta( $post->ID, '_event_clicks', true );
            }
            usort( $active_posts, function($a, $b) {
                $score_a = ((int)get_post_meta($a->ID, '_event_views', true)) + ((int)get_post_meta($a->ID, '_event_clicks', true) * 2);
                $score_b = ((int)get_post_meta($b->ID, '_event_views', true)) + ((int)get_post_meta($b->ID, '_event_clicks', true) * 2);
                return $score_b <=> $score_a;
            });
            $top_3 = array_slice( $active_posts, 0, 3 );
        } else {
            $top_3 = array();
        }

        $dashboard_url = admin_url( 'admin.php?page=bcg-events-hub' );
        $primary_col = get_option('villocity_global_primary', get_option('bcg_events_primary_color', '#860038')); 
        $accent_col  = get_option('villocity_global_accent', get_option('bcg_events_accent_color', '#fadceb')); 
        ?>
        <style>
            .bcg-widget-grid { display: flex; gap: 10px; margin-bottom: 16px; text-align: center; }
            .bcg-widget-stat { flex: 1; background: #f8f9fa; border: 1px solid #e1e3e1; border-radius: 12px; padding: 12px 6px; }
            .bcg-widget-stat strong { display: block; font-size: 18px; color: <?php echo $primary_col; ?>; font-weight: 700; line-height: 1.2; }
            .bcg-widget-stat span { font-size: 11px; color: #50575e; text-transform: uppercase; font-weight: 600; }
            .bcg-widget-top { margin-bottom: 16px; }
            .bcg-widget-top-title { font-weight: 600; font-size: 12px; color: #1d2327; text-transform: uppercase; margin-bottom: 8px; border-bottom: 1px solid #f0f4f8; padding-bottom: 6px; }
            .bcg-widget-item { font-size: 13px; margin-bottom: 8px; display: flex; justify-content: space-between; gap: 10px; align-items: center; }
            .bcg-widget-item a { text-decoration: none; font-weight: 500; color: <?php echo $primary_col; ?>; text-overflow: ellipsis; overflow: hidden; white-space: nowrap; max-width: 60%; }
            .bcg-widget-item a:hover { text-decoration: underline; }
            .bcg-widget-badge { background: <?php echo $accent_col; ?>; color: <?php echo $primary_col; ?>; font-weight: 600; font-size: 11px; padding: 2px 8px; border-radius: 10px; flex-shrink: 0; display: inline-flex; align-items: center; gap: 4px; }
            .bcg-widget-alert { background: #fef3c7; border: 1px solid #fde68a; color: #92400e; padding: 8px 12px; border-radius: 8px; font-size: 13px; font-weight: 600; margin-bottom: 16px; text-align: center; }
            .bcg-widget-footer { border-top: 1px solid #f0f4f8; padding-top: 12px; text-align: right; }
            .bcg-widget-btn { display: inline-block; background: <?php echo $primary_col; ?>; color: #ffffff !important; text-decoration: none !important; font-weight: 500; padding: 8px 18px; border-radius: 20px; font-size: 13px; transition: opacity 0.2s; }
            .bcg-widget-btn:hover { opacity: 0.9; }
        </style>

        <div class="bcg-widget-grid">
            <div class="bcg-widget-stat"><strong><?php echo esc_html( $total_views ); ?></strong><span>Page Views</span></div>
            <div class="bcg-widget-stat"><strong><?php echo esc_html( $active_query->post_count ); ?></strong><span>Upcoming Live</span></div>
            <div class="bcg-widget-stat"><strong><?php echo esc_html( $pending_query->post_count ); ?></strong><span>Pending</span></div>
        </div>

        <?php if ( $pending_query->post_count > 0 ) : ?>
            <div class="bcg-widget-alert">⏳ You have <?php echo esc_html( $pending_query->post_count ); ?> new event submission(s) awaiting approval!</div>
        <?php endif; ?>

        <?php if ( ! empty( $top_3 ) ) : ?>
            <div class="bcg-widget-top">
                <div class="bcg-widget-top-title">🔥 Top Performing Events</div>
                <?php foreach ( $top_3 as $p ) : $v = (int)get_post_meta($p->ID, '_event_views', true); ?>
                    <div class="bcg-widget-item">
                        <a href="<?php echo esc_url( get_permalink($p->ID) ); ?>" target="_blank" title="<?php echo esc_attr( $p->post_title ); ?>"><?php echo esc_html( $p->post_title ); ?></a>
                        <span class="bcg-widget-badge">👁️ <?php echo $v; ?> views</span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="bcg-widget-footer">
            <a href="<?php echo esc_url( $dashboard_url ); ?>" class="bcg-widget-btn">Open Live Events Hub &rarr;</a>
        </div>
        <?php
    }
}

if ( ! function_exists( 'bcg_render_events_dashboard' ) ) {
    function bcg_render_events_dashboard() {
        if ( ! current_user_can( 'manage_options' ) ) return;

        if ( function_exists('bcg_events_auto_rollover') ) {
            bcg_events_auto_rollover();
        }

        $message = '';
        $today_ts = strtotime(date('Y-m-d', current_time('timestamp')));

        if ( isset( $_POST['bcg_action_nonce'] ) && wp_verify_nonce( $_POST['bcg_action_nonce'], 'bcg_events_action' ) ) {
            
            if ( isset( $_POST['action_save_settings'] ) ) {
                update_option( 'bcg_events_promo_text', sanitize_text_field( $_POST['bcg_events_promo_text'] ) );
                update_option( 'bcg_events_stripe_url', esc_url_raw( $_POST['bcg_events_stripe_url'] ) );
                update_option( 'bcg_events_admin_email', sanitize_email( $_POST['bcg_events_admin_email'] ) );
                
                if ( isset( $_POST['bcg_events_ad_top'] ) ) { update_option( 'bcg_events_ad_top', wp_unslash($_POST['bcg_events_ad_top']) ); }
                if ( isset( $_POST['bcg_events_ad_bottom'] ) ) { update_option( 'bcg_events_ad_bottom', wp_unslash($_POST['bcg_events_ad_bottom']) ); }

                $message = '<div class="voc-gemini-notice"><h3>' . (function_exists('villocity_get_icon') ? villocity_get_icon('check') : '✓') . ' Settings Saved</h3><p style="margin:0;">Configuration updated successfully.</p></div>';
            }

            if ( isset( $_POST['action_approve_event'] ) ) {
                $post_id = (int) $_POST['post_id'];
                wp_update_post( array( 'ID' => $post_id, 'post_status' => 'publish' ) );
                $message = '<div class="voc-gemini-notice"><h3>' . (function_exists('villocity_get_icon') ? villocity_get_icon('check') : '✓') . ' Event Published</h3><p style="margin:0;">The event is now live on the site calendar.</p></div>';
            }

            if ( isset( $_POST['action_trash_event'] ) ) {
                $post_id = (int) $_POST['post_id'];
                wp_trash_post( $post_id );
                $message = '<div class="voc-gemini-notice" style="background:#fef2f2; border-color:#fecaca; color:#991b1b;"><h3>' . (function_exists('villocity_get_icon') ? villocity_get_icon('trash') : '🗑') . ' Event Trashed</h3><p style="margin:0;">The event was moved to the trash bin.</p></div>';
            }

            if ( isset( $_POST['action_untrash_event'] ) ) {
                $post_id = (int) $_POST['post_id'];
                wp_untrash_post( $post_id );
                $message = '<div class="voc-gemini-notice"><h3>' . (function_exists('villocity_get_icon') ? villocity_get_icon('refresh') : '🔄') . ' Event Restored</h3><p style="margin:0;">The event was restored from the trash bin.</p></div>';
            }

            if ( isset( $_POST['action_delete_event'] ) ) {
                $post_id = (int) $_POST['post_id'];
                wp_delete_post( $post_id, true );
                $message = '<div class="voc-gemini-notice" style="background:#fef2f2; border-color:#fecaca; color:#991b1b;"><h3>' . (function_exists('villocity_get_icon') ? villocity_get_icon('check') : '✓') . ' Event Deleted</h3><p style="margin:0;">The event was permanently deleted.</p></div>';
            }

            if ( isset( $_POST['action_toggle_feature'] ) ) {
                $post_id = (int) $_POST['post_id'];
                $status = sanitize_text_field($_POST['feature_status']);
                update_post_meta( $post_id, '_event_featured', $status );
                $message = '<div class="voc-gemini-notice"><h3>' . (function_exists('villocity_get_icon') ? villocity_get_icon('star') : '★') . ' Event Updated</h3><p style="margin:0;">The featured status has been updated successfully.</p></div>';
            }

            if ( isset( $_POST['action_resend_link'] ) ) {
                $post_id = (int) $_POST['post_id'];
                $post = get_post($post_id);
                $email = get_post_meta($post_id, '_event_email', true);
                $token = get_post_meta($post_id, '_event_edit_token', true);

                if ( empty($token) ) {
                    $token = wp_generate_password( 24, false, false );
                    update_post_meta( $post_id, '_event_edit_token', $token );
                }

                if ( ! empty($email) ) {
                    $site_name = get_bloginfo('name');
                    $magic_link = add_query_arg( array('edit_id' => $post_id, 'token' => $token), home_url('/events/') );
                    
                    $user_subject = 'Your Secure Edit Link: ' . $post->post_title;
                    $user_body = "Hello,\n\nAn admin at {$site_name} has resent your secure edit link for the event: \"{$post->post_title}\".\n\n";
                    $user_body .= "You can securely manage your event anytime using your private magic link below:\n\n";
                    $user_body .= $magic_link . "\n\n(Please do not share this link with anyone else).\n\nThank you,\nThe {$site_name} Team";
                    
                    wp_mail( $email, $user_subject, $user_body );
                    $message = '<div class="voc-gemini-notice"><h3>' . (function_exists('villocity_get_icon') ? villocity_get_icon('mail') : '✉') . ' Email Sent</h3><p style="margin:0;">The secure edit link has been successfully resent to ' . esc_html($email) . '.</p></div>';
                } else {
                    $message = '<div class="voc-gemini-notice" style="background:#fef2f2; border-color:#fecaca; color:#991b1b;"><h3>' . (function_exists('villocity_get_icon') ? villocity_get_icon('alert') : '⚠') . ' Error</h3><p style="margin:0;">No email address found for this event.</p></div>';
                }
            }

            if ( isset( $_POST['action_migrate_old_events'] ) ) {
                $old_post_type      = !empty($_POST['old_post_type_slug']) ? sanitize_text_field($_POST['old_post_type_slug']) : 'events';
                $force_remigrate    = isset($_POST['force_remigrate']) ? true : false;
                
                $old_events = get_posts( array(
                    'post_type'   => $old_post_type,
                    'numberposts' => -1,
                    'post_status' => array('publish', 'pending', 'draft', 'future', 'private', 'trash')
                ));

                $total_found = count($old_events);

                if ( $total_found === 0 ) {
                    $message = '<div class="voc-gemini-notice" style="background:#fef2f2; border-color:#fecaca; color:#991b1b;"><h3>' . (function_exists('villocity_get_icon') ? villocity_get_icon('alert') : '⚠') . ' No Events Found</h3><p style="margin:0;">We scanned your database for the post type slug <strong><code>' . esc_html($old_post_type) . '</code></strong> but found 0 records.</p></div>';
                } else {
                    $count = 0; $skipped = 0;
                    foreach ( $old_events as $old ) {
                        if ( ! $force_remigrate && get_post_meta( $old->ID, '_migrated_to_bcg_events', true ) ) { $skipped++; continue; }

                        $old_desc = get_post_meta($old->ID, 'event_description', true);
                        if ( empty($old_desc) ) { $old_desc = $old->post_content; }

                        $new_id = wp_insert_post( array('post_title' => $old->post_title, 'post_content' => $old_desc, 'post_status' => $old->post_status, 'post_type' => 'bcg_event') );

                        if ( $new_id && ! is_wp_error( $new_id ) ) {
                            $old_start_raw = get_post_meta($old->ID, 'event_start_date', true);
                            $old_end_raw   = get_post_meta($old->ID, 'event_end_date', true);
                            $old_type  = get_post_meta($old->ID, 'event_type', true);
                            $old_recur = get_post_meta($old->ID, 'event_recurrence', true);
                            if ($old_type === 'recurring') {
                                update_post_meta( $new_id, '_event_is_annual', 'yes' );
                                update_post_meta( $new_id, '_event_annual_timeframe', $old_recur );
                            } else {
                                update_post_meta( $new_id, '_event_is_annual', 'no' );
                            }
                            
                            $new_start = $old_start_raw ? date('Y-m-d', strtotime($old_start_raw)) : '';
                            $new_end = $old_end_raw ? date('Y-m-d', strtotime($old_end_raw)) : $new_start; 

                            $hours_arr = array();
                            if ( $s = get_post_meta($old->ID, 'event_start_time', true) ) $hours_arr[] = date('g:i a', strtotime($s));
                            if ( $e = get_post_meta($old->ID, 'event_end_time', true) ) $hours_arr[] = date('g:i a', strtotime($e));

                            $old_loc = get_post_meta($old->ID, 'event_location', true);
                            $old_addr = get_post_meta($old->ID, 'event_address', true);
                            $full_addr = ($old_loc && $old_addr) ? $old_loc . ', ' . $old_addr : ($old_addr ? $old_addr : $old_loc);
                            
                            update_post_meta( $new_id, '_event_start_date', $new_start );
                            update_post_meta( $new_id, '_event_end_date', $new_end );
                            update_post_meta( $new_id, '_event_hours', implode(' - ', $hours_arr) );
                            update_post_meta( $new_id, '_event_address', $full_addr );
                            update_post_meta( $new_id, '_event_website_url', get_post_meta($old->ID, 'event_ticket_link', true) );
                            update_post_meta( $new_id, '_event_category', 'Other' ); 
                            update_post_meta( $new_id, '_event_views', 0 );
                            update_post_meta( $new_id, '_event_clicks', 0 );
                            update_post_meta( $new_id, '_event_status', 'Active' );

                            $old_feat = get_post_meta($old->ID, 'featured_event', true);
                            update_post_meta( $new_id, '_event_featured', ($old_feat == '1' || $old_feat == true) ? 'yes' : 'no' );

                            $old_img = get_post_meta($old->ID, 'event_image', true);
                            $old_img_url = '';
                            if ( is_array($old_img) && isset($old_img['url']) ) { $old_img_url = $old_img['url']; } 
                            elseif ( is_numeric($old_img) ) { $old_img_url = wp_get_attachment_url($old_img); set_post_thumbnail($new_id, $old_img); } 
                            else if ( is_string($old_img) ) { $old_img_url = $old_img; }

                            if ( ! has_post_thumbnail($new_id) && !empty($old_img_url) ) {
                                $attach_id = attachment_url_to_postid($old_img_url);
                                if ($attach_id) { set_post_thumbnail($new_id, $attach_id); } 
                                else { update_post_meta( $new_id, '_migrated_acf_image_url', $old_img_url ); }
                            }

                            update_post_meta( $old->ID, '_migrated_to_bcg_events', 'yes' );
                            $count++;
                        }
                    }
                    $message = '<div class="voc-gemini-notice"><h3>' . (function_exists('villocity_get_icon') ? villocity_get_icon('check') : '✓') . ' Migration Complete!</h3><p style="margin:0;">Scanned <strong>' . $total_found . '</strong> total old events. Successfully migrated <strong>' . $count . '</strong> into the new calendar. (Skipped ' . $skipped . ' already migrated).</p></div>';
                }
            }
        }

        $promo_text = get_option( 'bcg_events_promo_text', 'Add your event to the Community Calendar for FREE!' );
        $stripe_url = get_option( 'bcg_events_stripe_url', '' );
        $admin_email= get_option( 'bcg_events_admin_email', get_option('admin_email') );
        
        $primary_col    = get_option('villocity_global_primary', get_option('bcg_events_primary_color', '#860038'));
        $accent_col     = get_option('villocity_global_accent', get_option('bcg_events_accent_color', '#fadceb'));

        $pending_query = new WP_Query( array( 'post_type' => 'bcg_event', 'post_status' => 'pending', 'posts_per_page' => -1 ) );
        $all_admin_query = new WP_Query( array( 'post_type' => 'bcg_event', 'post_status' => 'publish', 'posts_per_page' => -1 ) );

        $active_posts = array(); $annual_posts = array();
        
        if ( $all_admin_query->have_posts() ) {
            foreach ( $all_admin_query->posts as $p ) {
                $start = get_post_meta($p->ID, '_event_start_date', true);
                $end   = get_post_meta($p->ID, '_event_end_date', true);
                if (empty($end)) $end = $start;
                
                $is_annual = get_post_meta($p->ID, '_event_is_annual', true) === 'yes';
                $start_ts = (!empty($start) && strtotime($start)) ? strtotime($start) : 0;
                $end_ts   = (!empty($end) && strtotime($end)) ? strtotime($end) : $start_ts;
                if (date('Y', $start_ts) === '1970') { $start_ts = 0; $end_ts = 0; }

                if ($start_ts !== 0 && ($end_ts >= $today_ts || $start_ts >= $today_ts)) { $active_posts[] = $p; } 
                else { if ($is_annual || $start_ts === 0) { $annual_posts[] = $p; } }
            }
        }

        usort($active_posts, function($a, $b) use ($today_ts) {
            $feat_a = get_post_meta($a->ID, '_event_featured', true) === 'yes' ? 1 : 0;
            $feat_b = get_post_meta($b->ID, '_event_featured', true) === 'yes' ? 1 : 0;
            if ($feat_a !== $feat_b) return $feat_b <=> $feat_a;

            $start_a = get_post_meta($a->ID, '_event_start_date', true); $start_b = get_post_meta($b->ID, '_event_start_date', true);
            $end_a   = get_post_meta($a->ID, '_event_end_date', true); $end_b   = get_post_meta($b->ID, '_event_end_date', true);
            
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

        usort($annual_posts, function($a, $b) {
            $feat_a = get_post_meta($a->ID, '_event_featured', true) === 'yes' ? 1 : 0;
            $feat_b = get_post_meta($b->ID, '_event_featured', true) === 'yes' ? 1 : 0;
            if ($feat_a !== $feat_b) return $feat_b <=> $feat_a;
            
            $date_a = get_post_meta($a->ID, '_event_start_date', true); $date_b = get_post_meta($b->ID, '_event_start_date', true);
            $md_a = empty($date_a) ? '9999' : date('md', strtotime($date_a)); $md_b = empty($date_b) ? '9999' : date('md', strtotime($date_b));
            return strcmp($md_a, $md_b);
        });

        $today_string = date('Y-m-d', current_time('timestamp'));
        $past_query  = new WP_Query( array( 'post_type' => 'bcg_event', 'post_status' => 'publish', 'posts_per_page' => 50, 'meta_key' => '_event_start_date', 'orderby' => 'meta_value', 'order' => 'DESC', 'meta_query' => array('relation' => 'AND', array('key' => '_event_is_annual', 'value' => 'yes', 'compare' => '!='), array('key' => '_event_end_date', 'value' => $today_string, 'compare' => '<', 'type' => 'DATE')) ) );
        $trash_query = new WP_Query( array( 'post_type' => 'bcg_event', 'post_status' => 'trash', 'posts_per_page' => -1 ) );

        echo '<div class="voc-gemini-dashboard">' . $message;
        if ( function_exists( 'villocity_render_dashboard_header' ) ) {
            villocity_render_dashboard_header( 'Live Events Hub', 'Review event submissions, manage the community calendar, and track performance.', 'calendar', admin_url('post-new.php?post_type=bcg_event') );
        }

        // TABS NAVIGATION (Shortcodes Tab Included & ADA Compliant)
        echo '
            <style>
                .voc-gemini-tab:focus-visible, .voc-gemini-btn-primary:focus-visible, .voc-gemini-btn-secondary:focus-visible {
                    outline: 3px solid #60a5fa !important;
                    outline-offset: 2px !important;
                }
            </style>
            <div class="voc-gemini-tabs" role="tablist" aria-label="Event Hub Tabs">
                <button type="button" class="voc-gemini-tab active" role="tab" aria-selected="true" onclick="voc_switch_tab(event, \'voc-tab-pending\')">' . (function_exists('villocity_get_icon') ? villocity_get_icon('hourglass') : '⏳') . ' Pending Approvals (' . $pending_query->post_count . ')</button>
                <button type="button" class="voc-gemini-tab" role="tab" aria-selected="false" onclick="voc_switch_tab(event, \'voc-tab-active\')">' . (function_exists('villocity_get_icon') ? villocity_get_icon('calendar') : '📅') . ' Live Events (' . count($active_posts) . ')</button>
                <button type="button" class="voc-gemini-tab" role="tab" aria-selected="false" onclick="voc_switch_tab(event, \'voc-tab-shortcodes\')">' . (function_exists('villocity_get_icon') ? villocity_get_icon('check') : '✓') . ' Shortcodes</button>
                <button type="button" class="voc-gemini-tab" role="tab" aria-selected="false" onclick="voc_switch_tab(event, \'voc-tab-tbd\')">' . (function_exists('villocity_get_icon') ? villocity_get_icon('star') : '★') . ' Annual Events (' . count($annual_posts) . ')</button>
                <button type="button" class="voc-gemini-tab" role="tab" aria-selected="false" onclick="voc_switch_tab(event, \'voc-tab-past\')">' . (function_exists('villocity_get_icon') ? villocity_get_icon('clock') : '🕒') . ' Past Events (' . $past_query->post_count . ')</button>
                <button type="button" class="voc-gemini-tab" role="tab" aria-selected="false" onclick="voc_switch_tab(event, \'voc-tab-trash\')">' . (function_exists('villocity_get_icon') ? villocity_get_icon('trash') : '🗑') . ' Trash Bin (' . $trash_query->post_count . ')</button>
                <button type="button" class="voc-gemini-tab" role="tab" aria-selected="false" onclick="voc_switch_tab(event, \'voc-tab-settings\')">' . (function_exists('villocity_get_icon') ? villocity_get_icon('settings') : '⚙') . ' Settings & Migration</button>
            </div>';

        // TAB 1: PENDING
        echo '
        <div id="voc-tab-pending" class="voc-tab-content" role="tabpanel" style="display: block;">
            <div class="voc-gemini-card" style="padding: 0;">
                <table class="voc-gemini-data-table">
                    <thead><tr><th>Submitted By</th><th>Event Details</th><th>Category</th><th style="text-align:right;">Actions</th></tr></thead>
                    <tbody>';
                    if ( $pending_query->have_posts() ) {
                        while ( $pending_query->have_posts() ) {
                            $pending_query->the_post();
                            $id = get_the_ID();
                            $date = get_post_meta($id, '_event_start_date', true);
                            echo '<tr class="bcg-admin-row">
                                <td>' . get_the_date() . '<br><span style="font-size:12px; color:#64748b;">' . esc_html(get_post_meta($id, '_event_email', true)) . '</span></td>
                                <td><strong><a href="' . esc_url(get_permalink($id)) . '" target="_blank" style="text-decoration:none; color:' . esc_attr($primary_col) . ';">' . esc_html(get_the_title()) . '</a></strong><br><span style="font-size:12px; color:#64748b;">Starts: ' . esc_html($date) . '</span></td>
                                <td><span style="background:' . esc_attr($accent_col) . '; color:' . esc_attr($primary_col) . '; padding:4px 10px; border-radius:12px; font-size:12px;">' . esc_html(get_post_meta($id, '_event_category', true)) . '</span></td>
                                <td style="text-align:right; white-space: nowrap;">
                                    <a href="' . esc_url(get_edit_post_link($id)) . '" class="voc-gemini-btn-secondary" style="margin-right:4px;">Edit</a>
                                    <form method="post" style="display:inline-block; margin-right:4px;">
                                        ' . wp_nonce_field( 'bcg_events_action', 'bcg_action_nonce', true, false ) . '
                                        <input type="hidden" name="action_approve_event" value="1">
                                        <input type="hidden" name="post_id" value="' . $id . '">
                                        <button type="submit" class="voc-gemini-btn-secondary" style="background:#15803d !important; color:#fff !important; border:none !important;">Approve</button>
                                    </form>
                                    <form method="post" style="display:inline-block;">
                                        ' . wp_nonce_field( 'bcg_events_action', 'bcg_action_nonce', true, false ) . '
                                        <input type="hidden" name="action_trash_event" value="1">
                                        <input type="hidden" name="post_id" value="' . $id . '">
                                        <input type="submit" class="voc-gemini-btn-danger" value="Reject">
                                    </form>
                                </td>
                            </tr>';
                        }
                    } else {
                        echo '<tr><td colspan="4" style="text-align:center; padding:40px; color:#64748b;">No pending events right now.</td></tr>';
                    }
        echo '      </tbody>
                </table>
            </div>
        </div>';

        // TAB 2: ACTIVE (UPCOMING & ONGOING)
        echo '
        <div id="voc-tab-active" class="voc-tab-content" role="tabpanel" style="display: none;">
            <div class="voc-gemini-card" style="padding: 0;">
                <table class="voc-gemini-data-table">
                    <thead><tr><th>Event Details</th><th>Status</th><th>' . (function_exists('villocity_get_icon') ? villocity_get_icon('eye') : '👁') . ' Views</th><th>' . (function_exists('villocity_get_icon') ? villocity_get_icon('pointer') : '🖱') . ' Clicks</th><th style="text-align:right;">Actions</th></tr></thead>
                    <tbody>';
                    if ( ! empty($active_posts) ) {
                        foreach ( $active_posts as $post_item ) {
                            $id = $post_item->ID;
                            $views = (int) get_post_meta($id, '_event_views', true);
                            $clicks = (int) get_post_meta($id, '_event_clicks', true);
                            $start = get_post_meta($id, '_event_start_date', true);
                            $end   = get_post_meta($id, '_event_end_date', true);
                            
                            $is_featured = get_post_meta($id, '_event_featured', true) === 'yes';
                            $feat_action = $is_featured ? 'no' : 'yes';
                            $feat_label  = $is_featured ? 'Unfeature' : (function_exists('villocity_get_icon') ? villocity_get_icon('star') : '★') . 'Feature';
                            
                            $status = get_post_meta($id, '_event_status', true);
                            if(empty($status)) $status = 'Active';

                            $date_text = 'Starts: ' . esc_html($start);
                            $start_ts = (!empty($start) && strtotime($start)) ? strtotime($start) : 0;
                            $end_ts   = (!empty($end) && strtotime($end)) ? strtotime($end) : $start_ts;
                            
                            if ($start_ts < $today_ts && $end_ts >= $today_ts && $start_ts !== $end_ts) {
                                $dow = (int) date('w', $start_ts);
                                $today_dow = (int) date('w', $today_ts);
                                $display_ts = $today_ts;
                                if ($dow !== $today_dow) {
                                    $days_ahead = ($dow - $today_dow + 7) % 7;
                                    $display_ts = strtotime("+$days_ahead days", $today_ts);
                                }
                                $date_text = 'Next Date: ' . date('Y-m-d', $display_ts);
                            }
                            
                            echo '<tr class="bcg-admin-row">
                                <td><strong><a href="' . esc_url(get_permalink($id)) . '" target="_blank" style="text-decoration:none; color:' . esc_attr($primary_col) . ';">' . esc_html(get_the_title($id)) . '</a></strong><br><span style="font-size:12px; color:#64748b;">' . $date_text . '</span></td>
                                <td><span style="background:' . esc_attr($accent_col) . '; color:' . esc_attr($primary_col) . '; padding:4px 10px; border-radius:12px; font-size:12px;">' . esc_html($status) . '</span></td>
                                <td><strong style="color:' . esc_attr($primary_col) . '; background:' . esc_attr($accent_col) . '; padding:4px 12px; border-radius:16px;">' . $views . ' views</strong></td>
                                <td><strong style="color:#15803d; background:#dcfce7; padding:4px 12px; border-radius:16px;">' . $clicks . ' clicks</strong></td>
                                <td style="text-align:right; white-space:nowrap;">
                                    <form method="post" style="display:inline-block; margin-right:4px;">
                                        ' . wp_nonce_field( 'bcg_events_action', 'bcg_action_nonce', true, false ) . '
                                        <input type="hidden" name="action_toggle_feature" value="1">
                                        <input type="hidden" name="post_id" value="' . $id . '">
                                        <input type="hidden" name="feature_status" value="' . $feat_action . '">
                                        <button type="submit" class="voc-gemini-btn-secondary" style="background:#fffbeb !important; color:#92400e !important; border:1px solid #fde68a !important;">' . $feat_label . '</button>
                                    </form>
                                    <a href="' . esc_url(get_edit_post_link($id)) . '" class="voc-gemini-btn-secondary" style="margin-right:4px;">Edit</a>
                                    <form method="post" style="display:inline-block; margin-right:4px;" onsubmit="return confirm(\'Resend the secure edit link to the organizer?\');">
                                        ' . wp_nonce_field( 'bcg_events_action', 'bcg_action_nonce', true, false ) . '
                                        <input type="hidden" name="action_resend_link" value="1">
                                        <input type="hidden" name="post_id" value="' . $id . '">
                                        <input type="submit" class="voc-gemini-btn-secondary" value="Resend Link">
                                    </form>
                                    <form method="post" style="display:inline-block;" onsubmit="return confirm(\'Remove this active event?\');">
                                        ' . wp_nonce_field( 'bcg_events_action', 'bcg_action_nonce', true, false ) . '
                                        <input type="hidden" name="action_trash_event" value="1">
                                        <input type="hidden" name="post_id" value="' . $id . '">
                                        <input type="submit" class="voc-gemini-btn-danger" value="Trash">
                                    </form>
                                </td>
                            </tr>';
                        }
                    } else {
                        echo '<tr><td colspan="5" style="text-align:center; padding:40px; color:#64748b;">No active events right now.</td></tr>';
                    }
        echo '      </tbody>
                </table>
            </div>
        </div>';

        // TAB 3: SHORTCODES TAB (INCLUDES ANNUAL EVENTS SHORTCODE)
        echo '
        <div id="voc-tab-shortcodes" class="voc-tab-content" role="tabpanel" style="display: none;">
            <div style="display: flex; gap: 16px; flex-wrap: wrap;">
                <div class="voc-gemini-card" style="flex: 1; min-width: 300px;">
                    <strong style="color: ' . esc_attr($primary_col) . '; display:block; margin-bottom:6px;">Submission Form Shortcode</strong>
                    <code style="display:inline-block; padding:8px 12px; background:#f1f5f9; border-radius:6px; font-size:14px; font-weight:700;">[villocity_submit_event]</code>
                    <p style="margin: 8px 0 0 0; font-size: 13px; color: #64748b;">Embeds the public event submission form directly onto any page.</p>
                </div>
                <div class="voc-gemini-card" style="flex: 1; min-width: 300px;">
                    <strong style="color: ' . esc_attr($primary_col) . '; display:block; margin-bottom:6px;">Public Calendar Feed Shortcode</strong>
                    <code style="display:inline-block; padding:8px 12px; background:#f1f5f9; border-radius:6px; font-size:14px; font-weight:700;">[villocity_live_events]</code>
                    <p style="margin: 8px 0 0 0; font-size: 13px; color: #64748b;">Renders the full interactive calendar grid with search, filters, and submit popup.</p>
                </div>
                <div class="voc-gemini-card" style="flex: 1; min-width: 300px;">
                    <strong style="color: ' . esc_attr($primary_col) . '; display:block; margin-bottom:6px;">Annual & Recurring Events Feed Shortcode</strong>
                    <code style="display:inline-block; padding:8px 12px; background:#f1f5f9; border-radius:6px; font-size:14px; font-weight:700;">[villocity_annual_events]</code>
                    <p style="margin: 8px 0 0 0; font-size: 13px; color: #64748b;">Renders a clean, divider-separated homepage list of recurring traditions and annual community events.</p>
                    <p style="margin: 6px 0 0 0; font-size: 12px; color: #94a3b8;">Optional Attributes: <code>char_limit="90"</code>, <code>title="Annual Events"</code>, <code>subtitle="RECURRING EVENTS"</code></p>
                </div>
            </div>
        </div>';

        // TAB 4: ANNUAL TRADITIONS (TBD)
        echo '
        <div id="voc-tab-tbd" class="voc-tab-content" role="tabpanel" style="display: none;">
            <div class="voc-gemini-card" style="padding: 0;">
                <table class="voc-gemini-data-table">
                    <thead><tr><th>Event Details</th><th>Timeframe</th><th>' . (function_exists('villocity_get_icon') ? villocity_get_icon('eye') : '👁') . ' Views</th><th style="text-align:right;">Actions</th></tr></thead>
                    <tbody>';
                    if ( ! empty($annual_posts) ) {
                        foreach ( $annual_posts as $post_item ) {
                            $id = $post_item->ID;
                            $views = (int) get_post_meta($id, '_event_views', true);
                            $timeframe = get_post_meta($id, '_event_annual_timeframe', true);
                            echo '<tr class="bcg-admin-row">
                                <td><strong><a href="' . esc_url(get_permalink($id)) . '" target="_blank" style="text-decoration:none; color:' . esc_attr($primary_col) . ';">' . esc_html(get_the_title($id)) . '</a></strong><br><span style="font-size:12px; color:#92400e;">' . (function_exists('villocity_get_icon') ? villocity_get_icon('star') : '★') . ' Annual Event</span></td>
                                <td><span style="font-size:14px; font-weight:500;">' . esc_html($timeframe) . '</span></td>
                                <td><strong style="color:' . esc_attr($primary_col) . '; background:' . esc_attr($accent_col) . '; padding:4px 12px; border-radius:16px;">' . $views . ' views</strong></td>
                                <td style="text-align:right; white-space:nowrap;">
                                    <a href="' . esc_url(get_edit_post_link($id)) . '" class="voc-gemini-btn-secondary" style="background:#15803d !important; color:#fff !important; border:none !important; margin-right:4px;">Edit Event</a>
                                    <form method="post" style="display:inline-block;" onsubmit="return confirm(\'Permanently trash this annual event?\');">
                                        ' . wp_nonce_field( 'bcg_events_action', 'bcg_action_nonce', true, false ) . '
                                        <input type="hidden" name="action_trash_event" value="1">
                                        <input type="hidden" name="post_id" value="' . $id . '">
                                        <input type="submit" class="voc-gemini-btn-danger" value="Trash">
                                    </form>
                                </td>
                            </tr>';
                        }
                    } else {
                        echo '<tr><td colspan="4" style="text-align:center; padding:40px; color:#64748b;">No annual events currently found.</td></tr>';
                    }
        echo '      </tbody>
                </table>
            </div>
        </div>';

        // TAB 5: PAST EVENTS
        echo '
        <div id="voc-tab-past" class="voc-tab-content" role="tabpanel" style="display: none;">
            <div class="voc-gemini-card" style="padding: 0;">
                <table class="voc-gemini-data-table">
                    <thead><tr><th>Event Details</th><th>Category</th><th>' . (function_exists('villocity_get_icon') ? villocity_get_icon('eye') : '👁') . ' Views</th><th>' . (function_exists('villocity_get_icon') ? villocity_get_icon('pointer') : '🖱') . ' Clicks</th><th style="text-align:right;">Actions</th></tr></thead>
                    <tbody>';
                    if ( $past_query->have_posts() ) {
                        while ( $past_query->have_posts() ) {
                            $past_query->the_post();
                            $id = get_the_ID();
                            $views = (int) get_post_meta($id, '_event_views', true);
                            $clicks = (int) get_post_meta($id, '_event_clicks', true);
                            $date = get_post_meta($id, '_event_start_date', true);
                            echo '<tr class="bcg-admin-row">
                                <td><strong><a href="' . esc_url(get_permalink($id)) . '" target="_blank" style="text-decoration:none; color:' . esc_attr($primary_col) . ';">' . esc_html(get_the_title()) . '</a></strong><br><span style="font-size:12px; color:#64748b;">Ended: ' . esc_html($date) . '</span></td>
                                <td><span style="background:#f1f5f9; color:#475569; padding:4px 10px; border-radius:12px; font-size:12px;">' . esc_html(get_post_meta($id, '_event_category', true)) . '</span></td>
                                <td><strong style="color:#475569;">' . $views . '</strong></td>
                                <td><strong style="color:#475569;">' . $clicks . '</strong></td>
                                <td style="text-align:right; white-space:nowrap;">
                                    <a href="' . esc_url(get_edit_post_link($id)) . '" class="voc-gemini-btn-secondary" style="margin-right:4px;">Edit</a>
                                    <form method="post" style="display:inline-block;" onsubmit="return confirm(\'Move this archived event to the Trash Bin?\');">
                                        ' . wp_nonce_field( 'bcg_events_action', 'bcg_action_nonce', true, false ) . '
                                        <input type="hidden" name="action_trash_event" value="1">
                                        <input type="hidden" name="post_id" value="' . $id . '">
                                        <input type="submit" class="voc-gemini-btn-danger" value="Trash">
                                    </form>
                                </td>
                            </tr>';
                        }
                    } else {
                        echo '<tr><td colspan="5" style="text-align:center; padding:40px; color:#64748b;">No past events found.</td></tr>';
                    }
        echo '      </tbody>
                </table>
            </div>
        </div>';

        // TAB 6: TRASH
        echo '
        <div id="voc-tab-trash" class="voc-tab-content" role="tabpanel" style="display: none;">
            <div class="voc-gemini-card" style="padding: 0;">
                <div style="padding: 16px; border-bottom: 1px solid #e2e8f0; display:flex; align-items:center; justify-content:space-between; gap: 8px; background: #f8fafc; border-radius: 20px 20px 0 0;">
                    <span style="font-weight: 600; color: #475569; font-size: 14px;">Deleted Events</span>
                </div>
                <table class="voc-gemini-data-table">
                    <thead><tr><th>Event Details</th><th>Category</th><th style="text-align:right;">Actions</th></tr></thead>
                    <tbody>';
                    if ( $trash_query->have_posts() ) {
                        while ( $trash_query->have_posts() ) {
                            $trash_query->the_post();
                            $id = get_the_ID();
                            $date = get_post_meta($id, '_event_start_date', true);
                            echo '<tr class="bcg-admin-row">
                                <td><strong style="color:#991b1b; text-decoration:line-through;">' . esc_html(get_the_title()) . '</strong><br><span style="font-size:12px; color:#64748b;">Event Date: ' . esc_html($date) . '</span></td>
                                <td><span style="background:#f1f5f9; color:#475569; padding:4px 10px; border-radius:12px; font-size:12px;">' . esc_html(get_post_meta($id, '_event_category', true)) . '</span></td>
                                <td style="text-align:right; white-space:nowrap;">
                                    <form method="post" style="display:inline-block; margin-right:4px;">
                                        ' . wp_nonce_field( 'bcg_events_action', 'bcg_action_nonce', true, false ) . '
                                        <input type="hidden" name="action_untrash_event" value="1">
                                        <input type="hidden" name="post_id" value="' . $id . '">
                                        <button type="submit" class="voc-gemini-btn-secondary">' . (function_exists('villocity_get_icon') ? villocity_get_icon('refresh') : '🔄') . ' Restore</button>
                                    </form>
                                    <form method="post" style="display:inline-block;" onsubmit="return confirm(\'Permanently delete this event? This cannot be undone.\');">
                                        ' . wp_nonce_field( 'bcg_events_action', 'bcg_action_nonce', true, false ) . '
                                        <input type="hidden" name="action_delete_event" value="1">
                                        <input type="hidden" name="post_id" value="' . $id . '">
                                        <button type="submit" class="voc-gemini-btn-danger">' . (function_exists('villocity_get_icon') ? villocity_get_icon('trash') : '🗑') . ' Delete</button>
                                    </form>
                                </td>
                            </tr>';
                        }
                    } else {
                        echo '<tr><td colspan="3" style="text-align:center; padding:40px; color:#64748b;">The trash bin is empty.</td></tr>';
                    }
        echo '      </tbody>
                </table>
            </div>
        </div>';

        // TAB 7: SETTINGS & MIGRATION
        echo '
        <div id="voc-tab-settings" class="voc-tab-content" role="tabpanel" style="display: none;">
            <div class="voc-gemini-card">
                <form method="post">
                    ' . wp_nonce_field( 'bcg_events_action', 'bcg_action_nonce', true, false ) . '
                    <input type="hidden" name="action_save_settings" value="1">
                    
                    <div class="voc-gemini-notice" style="margin-bottom: 24px;">
                        <h3 style="margin-bottom:0;">' . (function_exists('villocity_get_icon') ? villocity_get_icon('settings') : '⚙') . ' Note:</h3>
                        <p style="margin-top:4px;">Brand colors and button styling are managed globally in the <strong>VilloCity Hub &rarr; Global Settings</strong>.</p>
                    </div>

                    <h3>' . (function_exists('villocity_get_icon') ? villocity_get_icon('alert') : '⚠') . ' Promo Banner Text</h3>
                    <p style="color:#64748b; font-size: 14px;">Displays a highlighted banner above the submission form. Leave blank to hide.</p>
                    <div style="margin-bottom: 30px;">
                        <label for="bcg_events_promo_text" class="voc-sr-only">Promo Banner Text</label>
                        <input id="bcg_events_promo_text" type="text" name="bcg_events_promo_text" value="' . esc_attr($promo_text) . '" class="voc-gemini-input" placeholder="e.g. Free listings for the first 30 days!">
                    </div>

                    <h3 style="margin-top:40px;">' . (function_exists('villocity_get_icon') ? villocity_get_icon('globe') : '🌐') . ' Stripe Payment Integration</h3>
                    <div style="margin-bottom: 30px;">
                        <label for="bcg_events_stripe_url" style="display:block; font-weight:600; margin-bottom:8px;">Stripe Payment Link URL</label>
                        <input id="bcg_events_stripe_url" type="url" name="bcg_events_stripe_url" value="' . esc_attr($stripe_url) . '" class="voc-gemini-input" placeholder="https://buy.stripe.com/...">
                        <p style="margin:6px 0 0 0; font-size:13px; color:' . esc_attr($primary_col) . '; font-weight: 500;">Tip: If this is left blank, the submit button will automatically switch to "Free Mode" and skip the redirect.</p>
                    </div>

                    <h3 style="margin-top:40px;">' . (function_exists('villocity_get_icon') ? villocity_get_icon('mail') : '✉') . ' Notification Alerts</h3>
                    <div style="margin-bottom: 30px;">
                        <label for="bcg_events_admin_email" style="display:block; font-weight:600; margin-bottom:8px;">Admin Email Address</label>
                        <input id="bcg_events_admin_email" type="email" name="bcg_events_admin_email" value="' . esc_attr($admin_email) . '" class="voc-gemini-input">
                    </div>
                    
                    <h3 style="margin-top:40px;">' . (function_exists('villocity_get_icon') ? villocity_get_icon('chart') : '📊') . ' Global Ad Injection</h3>
                    <p style="color:#64748b; font-size: 14px;">Paste Google AdSense scripts, HTML, or shortcodes to display above or below single event pages.</p>
                    <div style="margin-bottom: 20px;">
                        <label for="bcg_events_ad_top" style="display:block; font-weight:600; margin-bottom:8px;">Top of Single Event Page</label>
                        <textarea id="bcg_events_ad_top" name="bcg_events_ad_top" class="voc-gemini-textarea" rows="4" placeholder="<script>...</script>">' . esc_textarea(get_option('bcg_events_ad_top', '')) . '</textarea>
                    </div>
                    <div style="margin-bottom: 30px;">
                        <label for="bcg_events_ad_bottom" style="display:block; font-weight:600; margin-bottom:8px;">Bottom of Single Event Page</label>
                        <textarea id="bcg_events_ad_bottom" name="bcg_events_ad_bottom" class="voc-gemini-textarea" rows="4" placeholder="<script>...</script>">' . esc_textarea(get_option('bcg_events_ad_bottom', '')) . '</textarea>
                    </div>

                    <div style="margin-top: 40px; padding-top: 24px; border-top: 1px solid #e2e8f0;">
                        <button type="submit" class="voc-gemini-btn-primary">' . (function_exists('villocity_get_icon') ? villocity_get_icon('check') : '✓') . ' Save Configuration</button>
                    </div>
                </form>
                
                <div style="margin-top: 60px; padding: 30px; border-radius: 16px; background: #fffbeb; border: 1px solid #fde68a;">
                    <h3 style="margin-top:0; color:#92400e;">' . (function_exists('villocity_get_icon') ? villocity_get_icon('refresh') : '🔄') . ' 1-Click Data Migration Tool</h3>
                    <p style="color:#92400e; font-size:14px; margin-bottom: 20px;">Use this tool to automatically copy all events from your old plugin into this new system.</p>
                    <form method="post" onsubmit="return confirm(\'Are you sure you want to run the migration? This will pull your old events into the new calendar.\');">
                        ' . wp_nonce_field( 'bcg_events_action', 'bcg_action_nonce', true, false ) . '
                        <input type="hidden" name="action_migrate_old_events" value="1">
                        <label for="old_post_type_slug" style="display:block; font-weight:600; margin-bottom:6px; color:#92400e;">Old Post Type Slug:</label>
                        <input id="old_post_type_slug" type="text" name="old_post_type_slug" value="events" class="voc-gemini-input" style="width:150px; display:inline-block; margin-bottom:12px;"><br>
                        <label style="display:flex; align-items:center; gap:8px; font-weight:500; color:#92400e; margin-bottom:20px; cursor:pointer;">
                            <input type="checkbox" name="force_remigrate" value="1" style="width:18px; height:18px;"> Force Re-Migrate (Ignore previous migration flags)
                        </label>
                        <button type="submit" class="voc-gemini-btn-secondary" style="background:#f59e0b !important; color:#fff !important; border:none !important;">' . (function_exists('villocity_get_icon') ? villocity_get_icon('refresh') : '🔄') . ' Run Migration Script</button>
                    </form>
                </div>

            </div>
        </div>

        </div>';

        // REUSABLE TAB SWITCHING SCRIPT
        echo '<script>
            function voc_switch_tab(event, tabId) {
                if(event) event.preventDefault();
                var contents = document.getElementsByClassName("voc-tab-content");
                for (var i = 0; i < contents.length; i++) {
                    contents[i].style.display = "none";
                }
                var tabs = document.getElementsByClassName("voc-gemini-tab");
                for (var i = 0; i < tabs.length; i++) {
                    tabs[i].classList.remove("active");
                    tabs[i].setAttribute("aria-selected", "false");
                }
                document.getElementById(tabId).style.display = "block";
                
                if(event) {
                    event.currentTarget.classList.add("active");
                    event.currentTarget.setAttribute("aria-selected", "true");
                } else {
                    var activeLink = document.querySelector(".voc-gemini-tab[onclick*=\'" + tabId + "\']");
                    if (activeLink) {
                        activeLink.classList.add("active");
                        activeLink.setAttribute("aria-selected", "true");
                    }
                }
                localStorage.setItem("bcg_events_active_tab", tabId);
            }
            
            document.addEventListener("DOMContentLoaded", function() {
                var activeTab = localStorage.getItem("bcg_events_active_tab");
                if (activeTab && document.getElementById(activeTab)) {
                    voc_switch_tab(null, activeTab);
                }

                var searchInput = document.getElementById("bcg-admin-search");
                if(searchInput) {
                    searchInput.addEventListener("input", function() {
                        var query = this.value.toLowerCase().trim();
                        var rows = document.querySelectorAll(".voc-gemini-data-table tbody tr.bcg-admin-row");
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