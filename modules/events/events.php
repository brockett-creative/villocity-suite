<?php
/**
 * Module Name: VilloCity Live Events Calendar & Festivals
 * Module URI:  https://villocity.org
 * Description: Manages community calendar, Google/Apple ICS exports, festival extension, and mini-feed shortcodes.
 * Version:     3.1.0
 * Last Updated: September 4, 2026
 * Author:      Brockett Creative Group, Inc.
 * Author URI:  https://brockettcreative.com
 * Copyright:   © 2026 Brockett Creative Group, Inc. All Rights Reserved.
 * Text Domain: villocity-suite
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define Local Module Path
define( 'VOC_EVENTS_DIR', plugin_dir_path( __FILE__ ) );

// ==========================================
// 1. REGISTER CUSTOM POST TYPE
// ==========================================
if ( ! function_exists( 'bcg_register_event_cpt' ) ) {
    add_action( 'init', 'bcg_register_event_cpt', 5 );
    function bcg_register_event_cpt() {
        register_post_type( 'bcg_event', array(
            'labels' => array(
                'name'               => 'Live Events',
                'singular_name'      => 'Event',
                'add_new'            => 'Add New Event',
                'add_new_item'       => 'Add New Event',
                'edit_item'          => 'Edit Event',
                'all_items'          => 'All Events',
                'menu_name'          => 'Events Calendar'
            ),
            'public'             => true,
            'publicly_queryable' => true,
            'show_ui'            => true,
            'show_in_menu'       => false, 
            'has_archive'        => false,
            'rewrite'            => array( 'slug' => 'event-item', 'with_front' => false ),
            'supports'           => array( 'title', 'editor', 'thumbnail' ),
        ) );
    }
}

// ==========================================
// 2. INCLUDE SUB-MODULE COMPONENTS
// ==========================================
// Load Admin Hub, Settings & Metaboxes
if ( is_admin() && file_exists( VOC_EVENTS_DIR . 'admin.php' ) ) {
    require_once VOC_EVENTS_DIR . 'admin.php';
}

// Load Frontend Calendar Renderers & Public Views
if ( file_exists( VOC_EVENTS_DIR . 'frontend.php' ) ) {
    require_once VOC_EVENTS_DIR . 'frontend.php';
}

// Load Festival Multi-Day Extension
if ( file_exists( VOC_EVENTS_DIR . 'festival.php' ) ) {
    require_once VOC_EVENTS_DIR . 'festival.php';
}

// ==========================================
// 3. AUTO-ROLLOVER ENGINE FOR ANNUAL EVENTS
// ==========================================
if ( ! function_exists( 'bcg_events_auto_rollover' ) ) {
    function bcg_events_auto_rollover() {
        $today = current_time('Y-m-d');
        
        $passed_annual = get_posts(array(
            'post_type'      => 'bcg_event',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'meta_query'     => array(
                array('key' => '_event_is_annual', 'value' => 'yes', 'compare' => '=')
            )
        ));
        
        foreach ($passed_annual as $p) {
            $start = get_post_meta($p->ID, '_event_start_date', true);
            $end   = get_post_meta($p->ID, '_event_end_date', true);
            
            if (!empty($start)) {
                if (empty($end)) $end = $start;
                
                $start_time   = strtotime($start);
                $end_time     = strtotime($end);
                $current_time = strtotime($today);
                
                if ($end_time < $current_time) {
                    while ($end_time < $current_time) {
                        $start_time = strtotime('+1 year', $start_time);
                        $end_time   = strtotime('+1 year', $end_time);
                    }
                    
                    update_post_meta($p->ID, '_event_start_date', date('Y-m-d', $start_time));
                    update_post_meta($p->ID, '_event_end_date', date('Y-m-d', $end_time));
                    update_post_meta($p->ID, '_event_status', 'Active');
                }
            }
        }
    }
}

// ==========================================
// 4. STRICT PAGE VIEW TRACKER & REST API
// ==========================================
if ( ! function_exists( 'bcg_track_event_views' ) ) {
    add_action( 'wp', 'bcg_track_event_views' );
    function bcg_track_event_views() {
        if ( is_singular( 'bcg_event' ) && ! current_user_can( 'manage_options' ) ) {
            global $post;
            static $bcg_tracked = false; 
            if ( ! $bcg_tracked && isset($post->ID) ) {
                $views = (int) get_post_meta( $post->ID, '_event_views', true );
                update_post_meta( $post->ID, '_event_views', $views + 1 );
                $bcg_tracked = true;
            }
        }
    }
}

if ( ! function_exists( 'bcg_events_log_action' ) ) {
    add_action( 'rest_api_init', function () {
        register_rest_route( 'bcg-events/v1', '/track-action', array(
            'methods'  => 'POST',
            'callback' => 'bcg_events_log_action',
            'permission_callback' => '__return_true',
        ) );
    } );

    function bcg_events_log_action( WP_REST_Request $request ) {
        $post_id = (int) $request->get_param( 'post_id' );
        if ( $post_id ) {
            $clicks = (int) get_post_meta( $post_id, '_event_clicks', true );
            update_post_meta( $post_id, '_event_clicks', $clicks + 1 );
            return rest_ensure_response( array( 'success' => true ) );
        }
        return new WP_Error( 'not_found', 'Invalid ID', array( 'status' => 400 ) );
    }
}

// ==========================================
// 5. HOMEPAGE MINI-FEED SHORTCODE: [villocity_upcoming_events]
// ==========================================
if ( ! function_exists( 'voc_plugin_render_upcoming_events_mini' ) ) {
    function voc_plugin_render_upcoming_events_mini( $atts ) {
        $active_modules = get_option( 'villocity_active_modules', array() );
        if ( is_array( $active_modules ) && ! in_array( 'events', $active_modules ) ) {
            return '';
        }

        $atts = shortcode_atts( array(
            'count' => 3,
            'link'  => '/calendar/'
        ), $atts, 'villocity_upcoming_events' );

        $max_count   = intval( $atts['count'] );
        $primary_col = get_option('villocity_global_primary', get_option('bcg_events_primary_color', '#860038'));
        $accent_col  = get_option('villocity_global_accent', get_option('bcg_events_accent_color', '#fadceb'));
        $text_col    = get_option('villocity_global_text', get_option('bcg_events_text_color', '#1c1917'));
        $feed_bg     = get_option('villocity_global_bg', get_option('bcg_events_bg_color', '#fafaf9'));

        $btn_bg   = get_option('villocity_global_btn_bg', $primary_col);
        $btn_text = get_option('villocity_global_btn_text', '#ffffff');

        $mini_title = get_option('voc_events_mini_title', 'Upcoming Local Events');

        // Run core rollover engine
        if ( function_exists( 'bcg_events_auto_rollover' ) ) {
            bcg_events_auto_rollover();
        }

        $today_ts = strtotime(date('Y-m-d', current_time('timestamp')));
        
        $all_query = new WP_Query( array(
            'post_type'        => 'bcg_event', 
            'post_status'      => 'publish', 
            'posts_per_page'   => -1,
            'suppress_filters' => true 
        ) );

        $active_posts = array();
        if ( $all_query->have_posts() ) {
            foreach ( $all_query->posts as $p ) {
                if ( isset( $active_posts[$p->ID] ) ) {
                    continue;
                }

                $start = get_post_meta($p->ID, '_event_start_date', true);
                $end   = get_post_meta($p->ID, '_event_end_date', true);
                if (empty($end)) $end = $start;
                
                $start_ts = (!empty($start) && strtotime($start)) ? strtotime($start) : 0;
                $end_ts   = (!empty($end) && strtotime($end)) ? strtotime($end) : $start_ts;
                if (date('Y', $start_ts) === '1970') { $start_ts = 0; $end_ts = 0; }

                if ($start_ts !== 0 && ($end_ts >= $today_ts || $start_ts >= $today_ts)) {
                    $active_posts[$p->ID] = $p;
                }
            }
        }

        $active_posts = array_values($active_posts);

        // Dynamic Sort Logic
        usort($active_posts, function($a, $b) use ($today_ts) {
            $feat_a = get_post_meta($a->ID, '_event_featured', true) === 'yes' ? 1 : 0;
            $feat_b = get_post_meta($b->ID, '_event_featured', true) === 'yes' ? 1 : 0;
            if ($feat_a !== $feat_b) return $feat_b <=> $feat_a;

            $start_a = get_post_meta($a->ID, '_event_start_date', true); 
            $start_b = get_post_meta($b->ID, '_event_start_date', true); 
            $end_a   = get_post_meta($a->ID, '_event_end_date', true); 
            $end_b   = get_post_meta($b->ID, '_event_end_date', true); 
            
            $s_a = (!empty($start_a) && strtotime($start_a)) ? strtotime($start_a) : PHP_INT_MAX; 
            $s_b = (!empty($start_b) && strtotime($start_b)) ? strtotime($start_b) : PHP_INT_MAX; 
            $e_a = (!empty($end_a) && strtotime($end_a)) ? strtotime($end_a) : $s_a; 
            $e_b = (!empty($end_b) && strtotime($end_b)) ? strtotime($end_b) : $s_b; 
            
            $eff_a = $s_a;
            if ($s_a < $today_ts && $e_a >= $today_ts && $s_a !== $e_a) {
                $dow_a = (int) date('w', $s_a); $today_dow = (int) date('w', $today_ts);
                if ($dow_a === $today_dow) { $eff_a = $today_ts; } else {
                    $days_ahead = ($dow_a - $today_dow + 7) % 7;
                    $next_date  = strtotime("+$days_ahead days", $today_ts);
                    $eff_a      = ($next_date <= $e_a) ? $next_date : $e_a;
                }
            }

            $eff_b = $s_b;
            if ($s_b < $today_ts && $e_b >= $today_ts && $s_b !== $e_b) {
                $dow_b = (int) date('w', $s_b); $today_dow = (int) date('w', $today_ts);
                if ($dow_b === $today_dow) { $eff_b = $today_ts; } else {
                    $days_ahead = ($dow_b - $today_dow + 7) % 7;
                    $next_date  = strtotime("+$days_ahead days", $today_ts);
                    $eff_b      = ($next_date <= $e_b) ? $next_date : $e_b;
                }
            }

            if ($eff_a === $eff_b) { return strtotime($b->post_date) <=> strtotime($a->post_date); }
            return $eff_a <=> $eff_b;
        });

        $top_events = array_slice( $active_posts, 0, $max_count );

        $output = '
        <style>
            .voc-mini-events-wrap {
                --voc-primary: ' . esc_attr($primary_col) . ';
                --voc-accent: ' . esc_attr($accent_col) . ';
                --voc-text: ' . esc_attr($text_col) . ';
                --voc-bg: ' . esc_attr($feed_bg) . ';
                --voc-btn-bg: ' . esc_attr($btn_bg) . ';
                --voc-btn-text: ' . esc_attr($btn_text) . ';

                font-family: var(--voc-font-family, inherit) !important;
                background-color: #ffffff;
                border-radius: 16px;
                padding: 24px;
                border: 1px solid #e7e5e4;
                box-shadow: 0 4px 20px rgba(0,0,0,0.03);
                width: 100%;
                box-sizing: border-box;
            }

            .voc-mini-events-header { 
                display: flex !important; 
                flex-direction: row !important; 
                justify-content: space-between !important; 
                align-items: center !important; 
                border-bottom: 1px solid #e7e5e4 !important; 
                padding-bottom: 20px !important; 
                margin-bottom: 24px !important; 
                gap: 12px !important; 
                flex-wrap: wrap !important;
            }

            .voc-mini-events-grid { 
                display: flex !important; 
                flex-direction: column !important; 
                gap: 16px !important; 
                width: 100% !important;
            }

            .voc-mini-event-card { 
                display: flex !important; 
                flex-direction: row !important; 
                align-items: center !important; 
                width: 100% !important;
                background-color: var(--voc-bg) !important; 
                border: 1px solid #e7e5e4 !important; 
                border-radius: 12px !important; 
                padding: 12px !important;
                box-sizing: border-box !important;
                text-decoration: none !important; 
                color: inherit !important; 
                transition: box-shadow 0.2s, transform 0.2s !important; 
            }
            .voc-mini-event-card:hover { box-shadow: 0 8px 20px rgba(0,0,0,0.06) !important; transform: translateY(-2px) !important; }
            
            .voc-mini-event-thumb { 
                width: 80px !important; 
                height: 80px !important; 
                min-width: 80px !important; 
                max-width: 80px !important;
                border-radius: 8px !important; 
                overflow: hidden !important; 
                display: flex !important; 
                align-items: center !important;
                justify-content: center !important;
                background-color: var(--voc-primary) !important; 
                color: #ffffff !important;
                position: relative !important; 
                flex-shrink: 0 !important; 
            }
            .voc-mini-event-thumb img { 
                width: 100% !important; 
                height: 100% !important; 
                position: absolute !important; 
                top: 0 !important; 
                left: 0 !important; 
                object-fit: cover !important; 
                display: block !important; 
            }

            .voc-mini-event-datebox {
                text-align: center;
                line-height: 1.1;
            }
            .voc-mini-event-datebox .voc-m-month { font-size: 11px; text-transform: uppercase; font-weight: 800; letter-spacing: 0.05em; display: block; }
            .voc-mini-event-datebox .voc-m-day { font-size: 22px; font-weight: 800; display: block; margin-top: 2px; }
            
            .voc-mini-event-body { 
                padding: 0 0 0 16px !important; 
                display: flex !important; 
                flex-direction: column !important; 
                flex-grow: 1 !important; 
                min-width: 0 !important;
            }

            .voc-mini-event-date-row {
                display: flex !important;
                align-items: center !important;
                gap: 6px !important;
                color: #64748b !important;
                font-size: 14px !important;
                font-weight: 600 !important;
                margin-bottom: 4px !important;
            }

            .voc-mini-event-title { 
                font-size: 15px !important; 
                font-weight: 700 !important; 
                color: var(--voc-text) !important; 
                margin: 0 !important; 
                line-height: 1.3 !important; 
                display: -webkit-box !important;
                -webkit-line-clamp: 2 !important;
                -webkit-box-orient: vertical !important;
                overflow: hidden !important;
            }

            .voc-mini-event-btn { 
                background-color: var(--voc-btn-bg) !important; 
                color: var(--voc-btn-text) !important; 
                border: none !important; 
                font-weight: 600 !important; 
                padding: 8px 16px !important; 
                border-radius: 24px !important; 
                cursor: pointer !important; 
                text-decoration: none !important; 
                display: inline-flex !important; 
                align-items: center !important; 
                gap: 6px !important; 
                white-space: nowrap !important;
                font-size: 13px !important;
            }
        </style>

        <div class="voc-mini-events-wrap" role="region" aria-label="Upcoming Events Mini Feed">
            <div class="voc-mini-events-header">
                <div>
                    <span style="color: var(--voc-primary); font-weight: 700; letter-spacing: 0.05em; text-transform: uppercase; font-size: 12px; display: block; margin-bottom: 4px;">STAY CONNECTED</span>
                    <h3 style="font-size: 22px; font-weight: 800; color: var(--voc-text); margin: 0; line-height: 1.2;">' . esc_html($mini_title) . '</h3>
                </div>
                <a href="' . esc_url($atts['link']) . '" class="voc-mini-event-btn">
                    View All Events &rarr;
                </a>
            </div>';

        if ( empty( $top_events ) ) {
            $output .= '<p style="color: var(--voc-text); opacity: 0.8; font-size: 14px; margin: 0;">No events currently scheduled.</p>';
        } else {
            $output .= '<div class="voc-mini-events-grid">';
            foreach ( $top_events as $event_post ) {
                $id        = $event_post->ID;
                $title     = get_the_title( $id );
                $link      = get_permalink( $id );
                $start_raw = get_post_meta( $id, '_event_start_date', true );
                $end_raw   = get_post_meta( $id, '_event_end_date', true );
                if (empty($end_raw)) $end_raw = $start_raw;

                $start_ts   = (!empty($start_raw) && strtotime($start_raw)) ? strtotime($start_raw) : $today_ts;
                $end_ts     = (!empty($end_raw) && strtotime($end_raw)) ? strtotime($end_raw) : $start_ts;
                $is_ongoing = ($start_ts < $today_ts && $end_ts >= $today_ts && $start_ts !== $end_ts);

                $display_ts = $start_ts;
                if ($is_ongoing) {
                    $dow = (int) date('w', $start_ts); $today_dow = (int) date('w', $today_ts);
                    if ($dow === $today_dow) { $display_ts = $today_ts; } else {
                        $days_ahead = ($dow - $today_dow + 7) % 7;
                        $next_date  = strtotime("+$days_ahead days", $today_ts);
                        $display_ts = ($next_date <= $end_ts) ? $next_date : $end_ts;
                    }
                }

                $disp_date  = date( 'M j, Y', $display_ts );
                $month_abbr = date( 'M', $display_ts );
                $day_num    = date( 'j', $display_ts );

                $img = get_the_post_thumbnail_url($id, 'medium_large');
                if (empty($img)) {
                    $img = get_the_post_thumbnail_url($id, 'full');
                }
                if (empty($img)) {
                    $migrated_img = get_post_meta($id, '_migrated_acf_image_url', true);
                    if (is_array($migrated_img) && !empty($migrated_img['url'])) { 
                        $img = $migrated_img['url']; 
                    } else if (is_string($migrated_img) && !empty($migrated_img)) { 
                        $img = $migrated_img; 
                    }
                }

                $output .= '
                <article class="voc-mini-event-card">
                    <a href="' . esc_url($link) . '" class="voc-mini-event-thumb" tabindex="-1" aria-hidden="true">';
                
                if ( ! empty($img) ) {
                    $output .= '<img src="' . esc_url($img) . '" alt="' . esc_attr($title) . '" />';
                } else {
                    $output .= '
                    <div class="voc-mini-event-datebox">
                        <span class="voc-m-month">' . esc_html($month_abbr) . '</span>
                        <span class="voc-m-day">' . esc_html($day_num) . '</span>
                    </div>';
                }

                $output .= '
                    </a>
                    <div class="voc-mini-event-body">
                        <div class="voc-mini-event-date-row">
                            ' . (function_exists('villocity_get_icon') ? villocity_get_icon('clock') : '') . '
                            <span>' . esc_html($disp_date) . '</span>
                        </div>
                        <h4 class="voc-mini-event-title">
                            <a href="' . esc_url($link) . '" style="color:inherit; text-decoration:none;">
                                ' . esc_html($title) . '
                            </a>
                        </h4>
                    </div>
                </article>';
            }
            $output .= '</div>';
        }

        $output .= '</div>';

        return $output;
    }
}
add_action( 'init', function() {
    add_shortcode('villocity_upcoming_events', 'voc_plugin_render_upcoming_events_mini');
});