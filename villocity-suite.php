<?php
/**
 * Plugin Name: VilloCity Community Suite
 * Plugin URI:  https://villocity.org
 * Description: Executive white-label community hub platform with integrated analytics, event management, local marketplace, business directory, IDA properties, destinations, accessibility alert bar, cemetery plot directory, digital restaurant menus, community resources grid, and interactive history timelines.
 * Version:     2.9.29 (Actionable Pulse Pending Links & Dynamic Module Visibility)
 * Author:      Brockett Creative Group, Inc.
 * Author URI:  https://brockettcreative.com
 * Text Domain: villocity-suite
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define Global Paths
define( 'VILLOCITY_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'VILLOCITY_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// ==========================================
// 0. MASTER MODULE REGISTRY & DIAGNOSTICS HELPER (ALPHABETIZED)
// ==========================================
if ( ! function_exists( 'villocity_get_registered_modules' ) ) {
    function villocity_get_registered_modules() {
        return array(
            'alert_bar' => array(
                'name' => 'ADA Alert Bar & Ticker',
                'desc' => 'Scheduled public notices with screen-reader compliance and pause controls.',
                'file' => 'modules/alert-bar/alert-bar.php',
                'type' => 'standard',
            ),
            'properties' => array(
                'name' => 'Available Properties & Sites',
                'desc' => 'IDA Real Estate directory with zoning specs, utilities, and site maps.',
                'file' => 'modules/properties/properties.php',
                'type' => 'standard',
            ),
            'staff' => array(
                'name' => 'Board & Staff Directory',
                'desc' => 'Manage municipal officials, department staff bios, and public contact info.',
                'file' => 'modules/staff/staff.php',
                'type' => 'standard',
            ),
            'directory' => array(
                'name' => 'Business Directory',
                'desc' => 'Categorized local business directory with Super Search & sticky sidebars.',
                'file' => 'modules/directory/directory.php',
                'type' => 'standard',
            ),
            'cemetery' => array(
                'name' => 'Cemetery Directory',
                'desc' => 'Searchable grave records, interactive section maps, and CSV bulk importer.',
                'file' => 'modules/cemetery/cemetery.php',
                'type' => 'standard',
            ),
            'resources' => array(
                'name' => 'Community Resources Grid',
                'desc' => 'Display clean cards for town halls, libraries, and civic site links.',
                'file' => 'modules/resources/resources.php',
                'type' => 'standard',
            ),
            'destinations' => array(
                'name' => 'Destinations & Tourism',
                'desc' => 'Interactive tourism maps, historical attractions, and cross-promotion engines.',
                'file' => 'modules/destinations/destinations.php',
                'type' => 'standard',
            ),
            'history' => array(
                'name' => 'Digital Archives & Exhibits',
                'desc' => 'Historical Society module for preserving digitized artifacts and photos.',
                'file' => 'modules/history/history.php',
                'type' => 'standard',
            ),
            'restaurant-menu' => array(
                'name' => 'Digital Restaurant Menu',
                'desc' => 'Standalone digital menu engine or connected to Directory merchant listings.',
                'file' => 'modules/restaurant-menu/restaurant-menu.php',
                'type' => 'standard',
            ),
            'timeline' => array(
                'name' => 'Interactive History Timeline',
                'desc' => 'Visual chronological timeline engine for community milestones and historical events.',
                'file' => 'modules/timeline/timeline.php',
                'type' => 'standard',
            ),
            'events' => array(
                'name' => 'Live Events Calendar',
                'desc' => 'Community calendar feeds, ICS downloads, and submission links.',
                'file' => 'modules/events/events.php',
                'type' => 'standard',
            ),
            'news' => array(
                'name' => 'Live News Feed',
                'desc' => 'Aggregates local headlines via RSS, WP-Cron, and automated digests.',
                'file' => 'modules/news/news.php',
                'type' => 'standard',
            ),
            'marketplace' => array(
                'name' => 'Local Marketplace',
                'desc' => 'Community board for Yard Sales and Farm Stands with Google Maps.',
                'file' => 'modules/marketplace/marketplace.php',
                'type' => 'standard',
            ),
            'departments' => array(
                'name' => 'Municipal Departments Hub',
                'desc' => 'Centralized pages for town services, contacts, forms, and personnel rosters.',
                'file' => 'modules/departments/departments.php',
                'type' => 'standard',
            ),
            'nav-menu' => array(
                'name' => 'Native Navigation Menu',
                'desc' => 'Fast, lightweight CSS-driven menu bar replacing ElementsKit JS overhead.',
                'file' => 'modules/nav-menu/nav-menu.php',
                'type' => 'standard',
            ),
            'docs' => array(
                'name' => 'Public Documents & Archives',
                'desc' => 'Searchable repository for board minutes, local laws, agendas, and PDF forms.',
                'file' => 'modules/docs/docs.php',
                'type' => 'standard',
            ),
            'wp-login' => array(
                'name' => 'White Label WP Login',
                'desc' => 'Customizes /wp-login.php with deep VilloCity branding and media selection controls.',
                'file' => 'modules/wp-login/wp-login.php',
                'type' => 'standard',
            ),

            // Dedicated Custom Client Add-Ons
            'custom_utility_portal' => array(
                'name' => 'Water & Utility Payment Portal',
                'desc' => 'Bespoke municipal bill pay and meter reading integration.',
                'file' => 'modules/custom-utility-portal/custom-utility-portal.php',
                'type' => 'custom',
            ),
        );
    }
}

if ( ! function_exists( 'villocity_is_module_installed' ) ) {
    function villocity_is_module_installed( $module_info ) {
        if ( empty( $module_info['file'] ) ) return true;
        
        $clean_file = sanitize_text_field( $module_info['file'] );
        $possible_paths = array(
            VILLOCITY_PLUGIN_DIR . $clean_file,
            VILLOCITY_PLUGIN_DIR . str_replace('-', '_', $clean_file),
            VILLOCITY_PLUGIN_DIR . str_replace('_', '-', $clean_file),
        );

        foreach ( $possible_paths as $p ) {
            if ( file_exists( $p ) ) { return true; }
        }
        return false;
    }
}

// ==========================================
// 1. MASTER SVG ICON LIBRARY & UNIFIED STYLING
// ==========================================
if ( ! function_exists( 'villocity_get_icon' ) ) {
    function villocity_get_icon( $name ) {
        $base = '<svg class="villocity-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width: 18px !important; height: 18px !important; min-width: 18px !important; max-width: 18px !important; display: inline-block !important; vertical-align: -2px !important; flex-shrink: 0 !important; margin: 0 4px 0 0 !important; padding: 0 !important;">';
        
        $icons = array(
            'store'          => '<path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline>',
            'phone'          => '<path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>',
            'clock'          => '<circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 16 14"></polyline>',
            'map-pin'        => '<path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle>',
            'globe'          => '<circle cx="12" cy="12" r="10"></circle><line x1="2" y1="12" x2="22" y2="12"></line><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10z"></path>',
            'mail'           => '<path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline>',
            'check'          => '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline>',
            'x-circle'       => '<circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line>',
            'tag'            => '<path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"></path><line x1="7" y1="7" x2="7.01" y2="7"></line>',
            'eye'            => '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle>',
            'trash'          => '<polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>',
            'settings'       => '<circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>',
            'plus'           => '<line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line>',
            'star'           => '<polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>',
            'search'         => '<circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line>',
            'bag'            => '<path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path><line x1="3" y1="6" x2="21" y2="6"></line><path d="M16 10a4 4 0 0 1-8 0"></path>',
            'calendar'       => '<rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line>',
            'users'          => '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path>',
            'user'           => '<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle>',
            'alert'          => '<path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line>',
            'news'           => '<path d="M4 22h16a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2H8a2 2 0 0 0-2 2v16a2 2 0 0 1-2 2Zm0 0a2 2 0 0 1-2-2v-9c0-1.1.9-2 2-2h2"></path><path d="M18 14h-8"></path><path d="M15 18h-5"></path>',
            'archive'        => '<polyline points="21 8 21 21 3 21 3 8"></polyline><rect x="1" y="3" width="22" height="5"></rect><line x1="10" y1="12" x2="14" y2="12"></line>',
            'building'       => '<path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18Z"></path><path d="M6 12H4a2 2 0 0 0-2 2v8h20v-8a2 2 0 0 0-2-2h-2"></path><path d="M10 6h4"></path><path d="M10 10h4"></path><path d="M10 14h4"></path><path d="M10 18h4"></path>',
            'book'           => '<path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1-2.5-2.5Z"></path><path d="M6.5 6H20"></path>',
            'shield'         => '<path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.8 17 5 19 5a1 1 0 0 1 1 1z"></path>',
            'trending-up'    => '<polyline points="23 6 13.5 15.5 8.5 10.5 1 18"></polyline><polyline points="17 6 23 6 23 12"></polyline>',
            'filter'         => '<polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon>',
            'external-link'  => '<path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21" y2="3"></line>',
            'google'         => '<path d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2zm0 18a8 8 0 1 1 8-8 8 8 0 0 1-8 8z"></path><path d="M12 6a6 6 0 1 0 6 6h-6v-2h4a4 4 0 1 0-4 4 4 4 0 0 0 3.8-2.7h2.1A6 6 0 0 1 12 18a6 6 0 1 1 0-12z"></path>'
        );
        return isset( $icons[ $name ] ) ? $base . $icons[ $name ] . '</svg>' : '';
    }
}

// Global Interaction Logger Helper
if ( ! function_exists( 'villocity_log_interaction' ) ) {
    function villocity_log_interaction( $post_id, $meta_key, $event_type ) {
        global $wpdb;
        $current = (int) get_post_meta( $post_id, $meta_key, true );
        update_post_meta( $post_id, $meta_key, $current + 1 );

        $table_name = $wpdb->prefix . 'villocity_activity_log';
        if ( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) === $table_name ) {
            $wpdb->insert(
                $table_name,
                array(
                    'post_id'    => (int) $post_id,
                    'event_type' => sanitize_text_field( $event_type ),
                    'created_at' => current_time( 'mysql' ),
                ),
                array( '%d', '%s', '%s' )
            );
        }
    }
}

// ==========================================
// UNIVERSAL WHITE-LABEL SEARCH FILTER HOOK
// ==========================================
add_action( 'pre_get_posts', 'villocity_universal_white_label_search' );
function villocity_universal_white_label_search( $query ) {
    if ( ! is_admin() && $query->is_main_query() && $query->is_search() ) {
        
        $search_term = '';
        if ( isset( $_GET['s'] ) && ! empty( $_GET['s'] ) ) {
            $search_term = sanitize_text_field( $_GET['s'] );
        } elseif ( isset( $_GET['bcg_search'] ) && ! empty( $_GET['bcg_search'] ) ) {
            $search_term = sanitize_text_field( $_GET['bcg_search'] );
        }

        if ( ! empty( $search_term ) ) {
            $query->set( 's', $search_term );
        }

        $query->set( 'post_status', 'publish' );
        
        $default_types = array( 'page', 'post', 'bcg_directory', 'bcg_event', 'bcg_listing', 'voc_department', 'voc_resource' );
        $allowed_types = get_option( 'villocity_search_post_types', $default_types );
        
        if ( is_array( $allowed_types ) && ! empty( $allowed_types ) ) {
            $query->set( 'post_type', $allowed_types );
        }
    }
}

// ==========================================
// UNIVERSAL WHITE-LABEL SEARCH RESULTS ENGINE
// ==========================================
add_filter( 'template_include', 'villocity_render_universal_search_page', 99 );
function villocity_render_universal_search_page( $template ) {
    $enable_override = get_option( 'villocity_enable_search_template', '1' );

    if ( ( is_search() || isset( $_GET['s'] ) || isset( $_GET['bcg_search'] ) ) && ! is_admin() && '1' === $enable_override ) {
        
        $raw_query = isset( $_GET['s'] ) ? $_GET['s'] : ( isset( $_GET['bcg_search'] ) ? $_GET['bcg_search'] : '' );
        $search_query = sanitize_text_field( wp_unslash( $raw_query ) );

        if ( empty( trim( $search_query ) ) && ! is_search() ) {
            return $template;
        }

        get_header();

        $primary_col = get_option( 'villocity_global_primary', '#860038' );
        $accent_col  = get_option( 'villocity_global_accent', '#fadceb' );
        $text_col    = get_option( 'villocity_global_text', '#1c1917' );
        $site_name   = get_bloginfo( 'name' );

        $paged = max( 1, get_query_var( 'paged', 1 ) );
        $allowed_types = get_option( 'villocity_search_post_types', array( 'page', 'post', 'bcg_directory', 'bcg_event', 'bcg_listing', 'voc_department', 'voc_resource' ) );

        $search_args = array(
            's'              => $search_query,
            'post_type'      => $allowed_types,
            'post_status'    => 'publish',
            'posts_per_page' => 12,
            'paged'          => $paged,
        );

        $results_query = new WP_Query( $search_args );

        ?>
        <style id="villocity-search-results-styles">
            .voc-search-results-wrap {
                font-family: var(--voc-font-family, inherit) !important;
                color: <?php echo esc_attr( $text_col ); ?> !important;
                max-width: 1200px;
                margin: 50px auto;
                padding: 0 20px;
                box-sizing: border-box;
            }
            .voc-search-header-card {
                background: #ffffff;
                border: 1px solid #e2e8f0;
                border-radius: 20px;
                padding: 32px;
                margin-bottom: 32px;
                box-shadow: 0 10px 25px rgba(0,0,0,0.03);
            }
            .voc-search-header-title {
                font-size: 32px !important;
                font-weight: 800 !important;
                color: <?php echo esc_attr( $primary_col ); ?> !important;
                margin: 0 0 8px 0 !important;
                line-height: 1.2 !important;
                font-family: var(--voc-font-family, inherit) !important;
            }
            .voc-search-header-meta {
                font-size: 15px;
                color: #64748b;
                margin-bottom: 20px;
            }
            .voc-search-form-inline {
                display: flex;
                gap: 12px;
                max-width: 600px;
                flex-wrap: wrap;
            }
            .voc-search-results-grid {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
                gap: 24px;
            }
            .voc-search-card {
                background: #ffffff;
                border: 1px solid #e2e8f0;
                border-radius: 16px;
                padding: 24px;
                display: flex;
                flex-direction: column;
                transition: transform 0.2s ease, box-shadow 0.2s ease;
                text-decoration: none !important;
                color: inherit !important;
                position: relative;
            }
            .voc-search-card:hover {
                transform: translateY(-4px);
                box-shadow: 0 12px 25px rgba(0,0,0,0.06);
                border-color: <?php echo esc_attr( $primary_col ); ?>;
            }
            .voc-search-type-badge {
                background: <?php echo esc_attr( $accent_col ); ?>;
                color: <?php echo esc_attr( $primary_col ); ?>;
                font-size: 11px;
                font-weight: 800;
                padding: 4px 12px;
                border-radius: 20px;
                text-transform: uppercase;
                letter-spacing: 0.05em;
                align-self: flex-start;
                margin-bottom: 12px;
            }
            .voc-search-card-title {
                font-size: 18px !important;
                font-weight: 700 !important;
                margin: 0 0 8px 0 !important;
                color: #0f172a !important;
                line-height: 1.3 !important;
                font-family: var(--voc-font-family, inherit) !important;
            }
            .voc-search-card-excerpt {
                font-size: 14px;
                color: #475569;
                line-height: 1.6;
                margin-bottom: 16px;
            }
            .voc-search-card-link {
                font-size: 13px;
                font-weight: 700;
                color: <?php echo esc_attr( $primary_col ); ?>;
                margin-top: auto;
                display: inline-flex;
                align-items: center;
                gap: 4px;
            }
            .voc-search-pagination {
                margin-top: 40px;
                display: flex;
                justify-content: center;
                gap: 8px;
            }
            .voc-search-pagination a, .voc-search-pagination span {
                padding: 8px 16px;
                border-radius: 8px;
                background: #ffffff;
                border: 1px solid #cbd5e1;
                color: #0f172a;
                text-decoration: none;
                font-weight: 600;
                font-size: 14px;
            }
            .voc-search-pagination span.current {
                background: <?php echo esc_attr( $primary_col ); ?>;
                color: #ffffff;
                border-color: <?php echo esc_attr( $primary_col ); ?>;
            }
        </style>

        <main class="voc-search-results-wrap" role="main" aria-label="Search Results">
            <div class="voc-search-header-card">
                <h1 class="voc-search-header-title">Search Results</h1>
                <p class="voc-search-header-meta">
                    Showing <?php echo intval( $results_query->found_posts ); ?> result<?php echo $results_query->found_posts !== 1 ? 's' : ''; ?> for <strong style="color: #0f172a;">"<?php echo esc_html( $search_query ); ?>"</strong> across <?php echo esc_html( $site_name ); ?>
                </p>

                <form action="<?php echo esc_url( home_url( '/' ) ); ?>" method="get" class="voc-search-form-inline" role="search">
                    <label for="voc-search-page-input" class="voc-sr-only">Search Keyword</label>
                    <input type="search" name="s" id="voc-search-page-input" value="<?php echo esc_attr( $search_query ); ?>" class="voc-gemini-input" style="flex:1;" required>
                    <button type="submit" class="voc-gemini-btn-primary">Update Search</button>
                </form>
            </div>

            <div class="voc-search-results-grid">
                <?php if ( $results_query->have_posts() ) : ?>
                    <?php while ( $results_query->have_posts() ) : $results_query->the_post(); 
                        $post_type_obj = get_post_type_object( get_post_type() );
                        $type_label    = $post_type_obj ? $post_type_obj->labels->singular_name : 'Page';
                    ?>
                        <article class="voc-search-card">
                            <span class="voc-search-type-badge"><?php echo esc_html( $type_label ); ?></span>
                            <h2 class="voc-search-card-title"><?php the_title(); ?></h2>
                            <div class="voc-search-card-excerpt">
                                <?php echo esc_html( wp_trim_words( get_the_excerpt() ?: get_the_content(), 22, '...' ) ); ?>
                            </div>
                            <a href="<?php the_permalink(); ?>" class="voc-search-card-link" aria-label="View <?php echo esc_attr( get_the_title() ); ?>">
                                View Entry &rarr;
                            </a>
                        </article>
                    <?php endwhile; ?>
                    <?php wp_reset_postdata(); ?>
                <?php else : ?>
                    <div style="grid-column: 1 / -1; background: #ffffff; border: 1px dashed #cbd5e1; border-radius: 16px; padding: 40px; text-align: center;">
                        <h3 style="margin: 0 0 8px 0; color: #0f172a; font-size: 20px;">No Results Found</h3>
                        <p style="color: #64748b; margin: 0;">We couldn't find anything matching "<strong><?php echo esc_html( $search_query ); ?></strong>". Try searching for alternative keywords or categories.</p>
                    </div>
                <?php endif; ?>
            </div>

            <div class="voc-search-pagination">
                <?php
                echo paginate_links( array(
                    'total'     => $results_query->max_num_pages,
                    'current'   => $paged,
                    'mid_size'  => 2,
                    'prev_text' => '&larr; Previous',
                    'next_text' => 'Next &rarr;',
                ) );
                ?>
            </div>
        </main>
        <?php
        get_footer();
        exit;
    }
    return $template;
}

// ==========================================
// 2. DYNAMIC MODULE AUTO-LOADER
// ==========================================
$villocity_modules = get_option('villocity_active_modules', array(
    'news', 'events', 'marketplace', 'directory', 
    'properties', 'destinations', 'history', 'staff', 
    'departments', 'alert_bar', 'cemetery', 'restaurant-menu', 
    'timeline', 'resources', 'docs', 'nav-menu', 'wp-login'
));

if ( is_array( $villocity_modules ) ) {
    foreach ( $villocity_modules as $module_slug ) {
        $clean_slug = sanitize_title( $module_slug );           
        $raw_slug   = str_replace( '-', '_', $clean_slug );     
        
        $possible_paths = array(
            VILLOCITY_PLUGIN_DIR . "modules/{$clean_slug}/{$clean_slug}.php",
            VILLOCITY_PLUGIN_DIR . "modules/{$raw_slug}/{$raw_slug}.php",
            VILLOCITY_PLUGIN_DIR . "modules/{$clean_slug}/{$raw_slug}.php",
            VILLOCITY_PLUGIN_DIR . "modules/{$raw_slug}/{$clean_slug}.php",
            VILLOCITY_PLUGIN_DIR . "modules/{$clean_slug}/index.php",
            VILLOCITY_PLUGIN_DIR . "modules/module-{$clean_slug}.php",
            VILLOCITY_PLUGIN_DIR . "modules/module-{$raw_slug}.php",
        );

        foreach ( $possible_paths as $path ) {
            if ( file_exists( $path ) ) {
                require_once $path;
                break;
            }
        }
    }
}

// ==========================================
// ALWAYS-ACTIVE SELF-HEALING SHORTCODE REGISTRATION
// ==========================================
add_action( 'init', function() {
    if ( ! shortcode_exists( 'villocity_alert_bar' ) ) {
        add_shortcode( 'villocity_alert_bar', function( $atts = array() ) {
            if ( function_exists( 'villocity_render_alert_bar' ) ) {
                return villocity_render_alert_bar( $atts );
            }
            $file = VILLOCITY_PLUGIN_DIR . 'modules/alert-bar/alert-bar.php';
            if ( file_exists( $file ) ) {
                require_once $file;
                if ( function_exists( 'villocity_render_alert_bar' ) ) {
                    return villocity_render_alert_bar( $atts );
                }
            }
            return '';
        } );
    }

    if ( ! shortcode_exists( 'villocity_ticker' ) ) {
        add_shortcode( 'villocity_ticker', function( $atts = array() ) {
            if ( function_exists( 'villocity_render_alert_bar' ) ) {
                return villocity_render_alert_bar( $atts );
            }
            $file = VILLOCITY_PLUGIN_DIR . 'modules/alert-bar/alert-bar.php';
            if ( file_exists( $file ) ) {
                require_once $file;
                if ( function_exists( 'villocity_render_alert_bar' ) ) {
                    return villocity_render_alert_bar( $atts );
                }
            }
            return '';
        } );
    }

    if ( ! shortcode_exists( 'villocity_resources' ) ) {
        add_shortcode( 'villocity_resources', function( $atts = array() ) {
            if ( function_exists( 'villocity_render_resources_grid' ) ) {
                return villocity_render_resources_grid( $atts );
            }
            $file = VILLOCITY_PLUGIN_DIR . 'modules/resources/resources.php';
            if ( file_exists( $file ) ) {
                require_once $file;
                if ( function_exists( 'villocity_render_resources_grid' ) ) {
                    return villocity_render_resources_grid( $atts );
                }
            }
            return '';
        } );
    }
}, 1 );

// ==========================================
// 3. MASTER FRONTEND & ADMIN CSS INHERITANCE
// ==========================================
add_action( 'wp_head', 'villocity_global_frontend_css', 1 );
function villocity_global_frontend_css() {
    $inherit_theme = get_option( 'villocity_inherit_theme_styles', '1' );

    $g_primary = get_option( 'villocity_global_primary', '#860038' );
    $g_accent  = get_option( 'villocity_global_accent', '#fadceb' );
    $g_text    = get_option( 'villocity_global_text', '#1c1917' );
    $g_bg      = get_option( 'villocity_global_bg', '#fafaf9' );

    echo '<style id="villocity-global-variables">
        :root {';
            if ( $inherit_theme === '1' ) {
                echo '
                --voc-primary: var(--e-global-color-primary, ' . esc_attr($g_primary) . ');
                --voc-accent: var(--e-global-color-accent, ' . esc_attr($g_accent) . ');
                --voc-text: var(--e-global-color-text, ' . esc_attr($g_text) . ');
                --voc-bg: ' . esc_attr($g_bg) . ';
                --voc-border: #e2e8f0;
                --voc-radius: 16px;
                --voc-shadow: 0 4px 15px rgba(0,0,0,0.03);
                --voc-font-family: inherit;';
            } else {
                echo '
                --voc-primary: ' . esc_attr($g_primary) . ';
                --voc-accent: ' . esc_attr($g_accent) . ';
                --voc-text: ' . esc_attr($g_text) . ';
                --voc-bg: ' . esc_attr($g_bg) . ';
                --voc-border: #e2e8f0;
                --voc-radius: 16px;
                --voc-shadow: 0 4px 15px rgba(0,0,0,0.03);
                --voc-font-family: "Montserrat", -apple-system, BlinkMacSystemFont, sans-serif;';
            }
    echo '
        }

        .villocity-icon { 
            width: 18px !important; 
            height: 18px !important; 
            min-width: 18px !important; 
            max-width: 18px !important; 
            display: inline-block !important; 
            vertical-align: -2px !important; 
            stroke-width: 2 !important; 
            fill: none !important; 
            stroke: currentColor !important; 
            flex-shrink: 0 !important; 
            margin-right: 6px; 
        }

        .voc-news-wrap, .voc-dept-wrap, .voc-prop-wrap, .voc-mini-wrap, .voc-dept-single, .voc-dest-wrap, .voc-dest-single, .voc-cemetery-wrap, .voc-menu-wrap, .voc-tl-wrapper, .bcg-dir-wrap, .bcg-single-container, .voc-resources-grid {
            font-family: var(--voc-font-family) !important;
            color: var(--voc-text) !important;
        }
    </style>';
}

add_action( 'admin_head', 'villocity_admin_global_assets' );
function villocity_admin_global_assets() {
    $screen = get_current_screen();
    if ( ! $screen || ( strpos( $screen->id, 'villocity' ) === false && strpos( $screen->id, 'bcg-' ) === false && strpos( $screen->id, 'voc-' ) === false ) ) {
        return;
    }

    $g_primary = get_option('villocity_global_primary', '#860038');
    $g_accent  = get_option('villocity_global_accent', '#fadceb');

    echo '<style>
        .villocity-icon { width: 18px !important; height: 18px !important; min-width: 18px !important; max-width: 18px !important; display: inline-block !important; vertical-align: -2px !important; stroke-width: 2 !important; fill: none !important; stroke: currentColor !important; flex-shrink: 0 !important; margin-right: 6px; }
        .voc-gemini-dashboard { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; max-width: 1140px; margin: 30px 20px 20px 0; color: #0f172a; }
        .voc-gemini-dashboard * { box-sizing: border-box; }
        
        .voc-gemini-header { margin-bottom: 28px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; border-bottom: 1px solid #e2e8f0; padding-bottom: 24px; }
        .voc-gemini-header h1 { font-size: 2.1rem; font-weight: 800; letter-spacing: -0.6px; margin: 0 0 6px 0; color: #0f172a; display: flex; align-items: center; gap: 12px; line-height: 1.2; flex-wrap: wrap; }
        .voc-gemini-header h1 img { height: 38px !important; object-fit: contain; margin-right: 8px; vertical-align: middle; }
        .voc-gemini-header p { font-size: 15px; color: #64748b; margin: 0; }
        
        .voc-pulse-container { background: #0d111d; padding: 28px; border-radius: 24px; box-shadow: 0 10px 30px rgba(0,0,0,0.15); margin-bottom: 28px; color: #ffffff; }
        .voc-dark-header h1 { font-size: 2rem; font-weight: 800; color: #ffffff; margin: 0 0 4px 0; letter-spacing: -0.5px; }
        .voc-dark-header p { font-size: 14px; color: #94a3b8; margin: 0; }

        .voc-pulse-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 16px; margin-top: 24px; }
        .voc-pulse-card { background: #161c2e; border: 1px solid #232d48; border-radius: 18px; padding: 20px; position: relative; }
        .voc-pulse-card.voc-featured-card { background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); border: 1px solid #3b82f6; }
        .voc-pulse-card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }
        .voc-pulse-icon { width: 38px; height: 38px; border-radius: 10px; background: rgba(255,255,255,0.05); color: #60a5fa; display: flex; align-items: center; justify-content: center; }
        .voc-pulse-number { font-size: 32px; font-weight: 800; color: #ffffff; line-height: 1; margin-bottom: 6px; }
        .voc-pulse-label { font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.08em; color: #94a3b8; }
        .voc-pulse-meta { font-size: 12px; color: #94a3b8; display: flex; justify-content: space-between; align-items: center; margin-top: 8px; border-top: 1px solid #232d48; padding-top: 8px; }
        
        .voc-pill-badge { font-size: 11px; font-weight: 800; padding: 4px 10px; border-radius: 20px; display: inline-flex; align-items: center; gap: 4px; }
        .voc-pill-green { background: rgba(34, 197, 94, 0.15); color: #4ade80; border: 1px solid rgba(34, 197, 94, 0.3); }

        .voc-gemini-tabs { display: flex; gap: 6px; background: #f1f5f9; padding: 6px; border-radius: 16px; margin-bottom: 28px; flex-wrap: wrap; border: 1px solid #e2e8f0; }
        .voc-gemini-tab, .voc-tab-btn { padding: 10px 20px !important; border-radius: 12px !important; border: none !important; background: transparent; color: #64748b !important; font-size: 14px !important; font-weight: 700 !important; cursor: pointer; transition: all 0.2s ease; display: inline-flex !important; align-items: center !important; gap: 8px !important; text-decoration: none !important; box-shadow: none !important; line-height: 1.2 !important; }
        .voc-gemini-tab:hover, .voc-tab-btn:hover { color: #0f172a !important; background: rgba(255,255,255,0.6); }
        .voc-gemini-tab.active, .voc-tab-btn.active { background: #ffffff !important; color: ' . esc_attr($g_primary) . ' !important; box-shadow: 0 4px 12px rgba(0,0,0,0.06) !important; }

        .voc-gemini-notice { background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 14px; padding: 16px 22px; margin-bottom: 28px; color: #166534; box-shadow: 0 2px 8px rgba(0,0,0,0.02); }
        .voc-gemini-notice h3 { margin: 0 0 4px 0; font-size: 16px; font-weight: 700; display: flex; align-items: center; gap: 8px; }
        
        .voc-gemini-card { background: #ffffff; border-radius: 20px; padding: 28px; box-shadow: 0 4px 20px rgba(0,0,0,0.03); margin-bottom: 28px; border: 1px solid #e2e8f0; }
        .voc-gemini-card h3 { font-size: 1.25rem; font-weight: 800; margin: 0 0 16px 0; color: #0f172a; border-bottom: 1px solid #f1f5f9; padding-bottom: 14px; display: flex; align-items: center; gap: 8px; }
        
        .voc-gemini-input, .voc-gemini-textarea, .voc-gemini-select { width: 100%; padding: 11px 16px !important; border-radius: 10px !important; border: 1px solid #cbd5e1 !important; font-size: 14px !important; background: #fff !important; color: #0f172a !important; transition: 0.2s; }
        .voc-gemini-input:focus, .voc-gemini-textarea:focus, .voc-gemini-select:focus { border-color: ' . esc_attr($g_primary) . ' !important; outline: 3px solid ' . esc_attr($g_accent) . ' !important; }
        
        .voc-gemini-btn-primary { background: ' . esc_attr($g_primary) . ' !important; color: #ffffff !important; border: none !important; border-radius: 20px !important; padding: 11px 24px !important; font-size: 14px !important; font-weight: 700 !important; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; text-decoration: none !important; transition: all 0.2s ease; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
        .voc-gemini-btn-primary:hover { opacity: 0.95; transform: translateY(-1px); box-shadow: 0 4px 14px rgba(0,0,0,0.12); }
        
        .voc-gemini-btn-secondary { background: #f1f5f9 !important; color: #334155 !important; border: 1px solid #cbd5e1 !important; border-radius: 20px !important; padding: 9px 20px !important; font-size: 13px !important; font-weight: 600 !important; cursor: pointer; text-decoration: none !important; display: inline-flex; align-items: center; gap: 6px; transition: 0.2s; }
        .voc-gemini-btn-secondary:hover { background: #e2e8f0 !important; color: #0f172a !important; }
        
        .voc-gemini-data-table { width: 100%; border-collapse: collapse; }
        .voc-gemini-data-table th { text-align: left; padding: 14px 16px; border-bottom: 2px solid #e2e8f0; font-size: 12px; color: #64748b; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em; }
        .voc-gemini-data-table td { padding: 14px 16px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; font-size: 14px; }
        .voc-gemini-data-table tr:hover td { background: #f8fafc; }

        /* Dynamic Module Diagnostic Styles */
        .voc-module-card { border: 1px solid #e2e8f0; border-radius: 14px; padding: 20px; background: #ffffff; position: relative; transition: all 0.2s ease; }
        .voc-module-card.is-active { border-color: ' . esc_attr($g_primary) . '; background: #f8fafc; }
        .voc-module-card.is-uninstalled { background: #f1f5f9; border-color: #cbd5e1; opacity: 0.75; }
        .voc-module-card.is-uninstalled * { color: #64748b !important; }
        .voc-badge-uninstalled { background: #e2e8f0; color: #475569; font-size: 10px; font-weight: 800; padding: 3px 8px; border-radius: 12px; text-transform: uppercase; letter-spacing: 0.05em; }
        .voc-badge-custom { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; font-size: 10px; font-weight: 800; padding: 3px 8px; border-radius: 12px; text-transform: uppercase; }
    </style>';
}

if ( ! function_exists( 'villocity_render_dashboard_header' ) ) {
    function villocity_render_dashboard_header( $title, $subtitle, $icon_name = 'settings', $add_new_url = '' ) {
        echo '
        <div class="voc-gemini-header">
            <div>
                <h1>
                    <img src="https://www.brockettcreative.com/wp-content/uploads/2026/08/Villocity-Logo.png" alt="VilloCity Logo">
                    <span>' . esc_html($title) . '</span>
                </h1>
                <p>' . esc_html($subtitle) . '</p>
            </div>
            <div style="display:flex; align-items:center; gap:12px; flex-wrap:wrap;">
                <div style="position:relative;">
                    <span style="position:absolute; left:12px; top:50%; transform:translateY(-50%); color:#94a3b8; display:flex;">' . villocity_get_icon('search') . '</span>
                    <input type="text" id="bcg-admin-search" placeholder="Search..." class="voc-gemini-input" style="margin:0; padding-left:36px !important; min-width:220px;">
                </div>
                ' . (!empty($add_new_url) ? '<a href="' . esc_url($add_new_url) . '" class="voc-gemini-btn-primary">' . villocity_get_icon('plus') . ' Add New</a>' : '') . '
            </div>
        </div>';
    }
}

// ==========================================
// 4. MASTER WP ADMIN MENU (VilloCity Hub)
// ==========================================
add_action( 'admin_menu', 'villocity_register_main_menu', 1 );
function villocity_register_main_menu() {
    add_menu_page(
        'VilloCity Hub',             
        'VilloCity',                    
        'manage_options',               
        'villocity-hub',             
        'villocity_render_main_dashboard', 
        'https://www.brockettcreative.com/wp-content/uploads/2026/08/Villocity-Logo-icon-wht.png',                  
        0
    );

    add_submenu_page(
        'villocity-hub',
        'Executive Hub',
        'Executive Hub',
        'manage_options',
        'villocity-hub',
        'villocity_render_main_dashboard'
    );

    $active_modules = get_option( 'villocity_active_modules', array() );

    // Submenu Registration for Alert Bar
    if ( is_array( $active_modules ) && in_array( 'alert_bar', $active_modules, true ) ) {
        add_submenu_page(
            'villocity-hub',
            'Alert Bar Settings',
            'Alert Bar',
            'manage_options',
            'voc-alert-bar-settings',
            function() {
                $targets = array(
                    VILLOCITY_PLUGIN_DIR . 'modules/alert-bar/alert-bar.php',
                    VILLOCITY_PLUGIN_DIR . 'modules/alert_bar/alert_bar.php',
                );

                foreach ( $targets as $file ) {
                    if ( file_exists( $file ) ) { require_once $file; }
                }

                if ( function_exists( 'voc_alert_bar_render_admin_page' ) ) {
                    voc_alert_bar_render_admin_page();
                } else {
                    echo '<div class="wrap"><h2>Alert Bar Settings</h2><p>Error: Could not locate <code>voc_alert_bar_render_admin_page()</code>. Verify module path in <code>modules/alert-bar/alert-bar.php</code>.</p></div>';
                }
            }
        );
    }

    // Submenu Registration for Community Resources
    if ( is_array( $active_modules ) && in_array( 'resources', $active_modules, true ) ) {
        add_submenu_page(
            'villocity-hub',
            'Community Resources',
            'Resources',
            'manage_options',
            'voc-resources-settings',
            function() {
                $targets = array(
                    VILLOCITY_PLUGIN_DIR . 'modules/resources/resources.php',
                );

                foreach ( $targets as $file ) {
                    if ( file_exists( $file ) ) { require_once $file; }
                }

                if ( function_exists( 'voc_resources_render_admin_page' ) ) {
                    voc_resources_render_admin_page();
                } else {
                    echo '<div class="wrap"><h2>Community Resources</h2><p>Error: Could not locate <code>voc_resources_render_admin_page()</code>. Verify module path in <code>modules/resources/resources.php</code>.</p></div>';
                }
            }
        );
    }
}

add_action( 'admin_menu', 'villocity_reorder_admin_menu_absolute_top', 99999 );
function villocity_reorder_admin_menu_absolute_top() {
    global $menu;
    
    if ( empty( $menu ) || ! is_array( $menu ) ) {
        return;
    }

    $villocity_item = null;
    $villocity_key  = null;

    foreach ( $menu as $key => $item ) {
        if ( isset( $item[2] ) && $item[2] === 'villocity-hub' ) {
            $villocity_item = $item;
            $villocity_key  = $key;
            break;
        }
    }

    if ( null !== $villocity_item && null !== $villocity_key ) {
        unset( $menu[ $villocity_key ] );
        array_unshift( $menu, $villocity_item );
    }
}

add_action('admin_head', 'villocity_admin_menu_css');
function villocity_admin_menu_css() {
    echo '<style>
        #adminmenu img[src*="Villocity-Logo-icon"] {
            max-width: 20px !important;
            max-height: 20px !important;
            width: 20px !important;
            height: 20px !important;
            padding-top: 6px !important;
            object-fit: contain !important;
            opacity: 0.9 !important;
        }
        #adminmenu li:hover img[src*="Villocity-Logo-icon"],
        #adminmenu li.wp-has-current-submenu img[src*="Villocity-Logo-icon"] {
            opacity: 1 !important;
        }
    </style>';
}

// ==========================================
// 5. GLOBAL DASHBOARD UI (EXECUTIVE PULSE, ACTIVITY LOG & BRANDING)
// ==========================================
function villocity_render_main_dashboard() {
    if ( ! current_user_can( 'manage_options' ) ) return;
    global $wpdb;

    $message = '';

    // Handle One-Click White-Label Presets
    if ( isset( $_POST['voc_apply_preset'] ) && check_admin_referer( 'villocity_save_global', 'villocity_save_global_nonce' ) ) {
        $preset = sanitize_text_field( $_POST['voc_apply_preset'] );
        if ( $preset === 'municipality' ) {
            update_option( 'villocity_nav_search_title', 'Municipal Site Search' );
            update_option( 'villocity_nav_search_desc', 'Search town services, forms, board agendas, events, or local listings...' );
            update_option( 'villocity_nav_search_placeholder', 'e.g., Water bill, tax collector, permits, events...' );
            update_option( 'villocity_search_post_types', array( 'page', 'post', 'bcg_directory', 'bcg_event', 'voc_department' ) );
        } elseif ( $preset === 'chamber' ) {
            update_option( 'villocity_nav_search_title', 'Chamber & Business Search' );
            update_option( 'villocity_nav_search_desc', 'Search local merchants, member perks, directory profiles, and business networking events...' );
            update_option( 'villocity_nav_search_placeholder', 'e.g., Dining, accounting, retail, chamber events...' );
            update_option( 'villocity_search_post_types', array( 'page', 'post', 'bcg_directory', 'bcg_event' ) );
        } elseif ( $preset === 'universal' ) {
            update_option( 'villocity_nav_search_title', 'Site Search' );
            update_option( 'villocity_nav_search_desc', 'Search across pages, articles, events, directory listings, and public records...' );
            update_option( 'villocity_nav_search_placeholder', 'Search everything...' );
            update_option( 'villocity_search_post_types', array( 'page', 'post', 'bcg_directory', 'bcg_event', 'bcg_listing' ) );
        }
        villocity_clear_all_caches();
        $message = '<div class="voc-gemini-notice"><h3>' . villocity_get_icon('check') . ' Preset Applied</h3><p style="margin:0;">White-label preset configuration loaded & LiteSpeed Cache purged.</p></div>';
    }

    if ( isset( $_POST['villocity_save_global_nonce'] ) && wp_verify_nonce( $_POST['villocity_save_global_nonce'], 'villocity_save_global' ) ) {
        $active_modules = isset($_POST['villocity_active_modules']) && is_array($_POST['villocity_active_modules']) ? array_map('sanitize_text_field', $_POST['villocity_active_modules']) : array();
        update_option( 'villocity_active_modules', $active_modules );

        update_option( 'villocity_inherit_theme_styles', isset($_POST['villocity_inherit_theme_styles']) ? '1' : '0' );
        update_option( 'villocity_enable_search_template', isset($_POST['villocity_enable_search_template']) ? '1' : '0' );

        if ( isset( $_POST['villocity_global_layout_style'] ) ) { update_option( 'villocity_global_layout_style', sanitize_text_field( $_POST['villocity_global_layout_style'] ) ); }

        if ( isset( $_POST['villocity_global_primary'] ) ) { update_option( 'villocity_global_primary', sanitize_hex_color( $_POST['villocity_global_primary'] ) ); }
        if ( isset( $_POST['villocity_global_accent'] ) ) { update_option( 'villocity_global_accent', sanitize_hex_color( $_POST['villocity_global_accent'] ) ); }
        if ( isset( $_POST['villocity_global_text'] ) ) { update_option( 'villocity_global_text', sanitize_hex_color( $_POST['villocity_global_text'] ) ); }
        if ( isset( $_POST['villocity_global_bg'] ) ) { update_option( 'villocity_global_bg', sanitize_hex_color( $_POST['villocity_global_bg'] ) ); }

        // Save Custom White-Label Search Texts
        if ( isset( $_POST['villocity_nav_search_title'] ) ) { update_option( 'villocity_nav_search_title', sanitize_text_field( $_POST['villocity_nav_search_title'] ) ); }
        if ( isset( $_POST['villocity_nav_search_desc'] ) ) { update_option( 'villocity_nav_search_desc', sanitize_text_field( $_POST['villocity_nav_search_desc'] ) ); }
        if ( isset( $_POST['villocity_nav_search_placeholder'] ) ) { update_option( 'villocity_nav_search_placeholder', sanitize_text_field( $_POST['villocity_nav_search_placeholder'] ) ); }

        // --- PURGE LITESPEED & ALL SITE CACHES ON MODULE SAVE ---
        villocity_clear_all_caches();

        $message = '<div class="voc-gemini-notice"><h3>' . villocity_get_icon('check') . ' Executive Settings Saved</h3><p style="margin:0;">Global platform configurations updated & LiteSpeed Cache purged successfully.</p></div>';
    }

    $current_modules = get_option('villocity_active_modules', array('news', 'events', 'marketplace', 'directory', 'properties', 'destinations', 'history', 'staff', 'departments', 'alert_bar', 'cemetery', 'restaurant-menu', 'timeline', 'resources', 'docs', 'nav-menu', 'wp-login'));
    $inherit_theme   = get_option('villocity_inherit_theme_styles', '1');
    $enable_search_tpl = get_option('villocity_enable_search_template', '1');

    $g_layout  = get_option('villocity_global_layout_style', 'boxed');
    $g_primary = get_option('villocity_global_primary', '#860038');
    $g_accent  = get_option('villocity_global_accent', '#fadceb');
    $g_text    = get_option('villocity_global_text', '#1c1917');
    $g_bg      = get_option('villocity_global_bg', '#fafaf9');

    $s_title = get_option( 'villocity_nav_search_title', 'Site Search' );
    $s_desc  = get_option( 'villocity_nav_search_desc', 'Search across pages, articles, events, directory listings, and public records...' );
    $s_ph    = get_option( 'villocity_nav_search_placeholder', 'Search everything...' );

    echo '<div class="voc-gemini-dashboard">' . $message;
    
    villocity_render_dashboard_header( 'VilloCity Executive Hub', 'High-level performance metrics, module activity, and brand management.', 'settings' );

    // Inline Styles for Tinted Icon Badges & Live Activity Log
    echo '
    <style>
        .voc-icon-badge {
            width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center;
        }
        .voc-icon-badge.blue { background: rgba(59, 130, 246, 0.15); color: #60a5fa; }
        .voc-icon-badge.teal { background: rgba(20, 184, 166, 0.15); color: #2dd4bf; }
        .voc-icon-badge.rose { background: rgba(244, 63, 94, 0.15); color: #fb7185; }
        .voc-icon-badge.purple { background: rgba(168, 85, 247, 0.15); color: #c084fc; }
        .voc-icon-badge.amber { background: rgba(245, 158, 11, 0.15); color: #fbbf24; }

        .voc-activity-log-card {
            background: #111827; border: 1px solid #1e293b; border-radius: 18px; padding: 24px; margin-top: 28px;
        }
        .voc-activity-row {
            display: flex; justify-content: space-between; align-items: center; padding: 12px 0; border-bottom: 1px solid #1e293b; font-size: 14px;
        }
        .voc-activity-row:last-child { border-bottom: none; }
        .voc-dot { width: 8px; height: 8px; border-radius: 50%; display: inline-block; margin-right: 10px; }
        .voc-dot.green { background: #34d399; box-shadow: 0 0 8px rgba(52, 211, 153, 0.6); }
        .voc-dot.red { background: #f87171; box-shadow: 0 0 8px rgba(248, 113, 113, 0.6); }
        .voc-dot.blue { background: #60a5fa; box-shadow: 0 0 8px rgba(96, 165, 250, 0.6); }
    </style>';

    // COMMUNITY PULSE GRID (DYNAMICALLY HIDDEN FOR DISABLED MODULES WITH ACTIONABLE PENDING LINKS)
    echo '
    <div class="voc-pulse-container">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; border-bottom: 1px solid #232d48; padding-bottom: 20px;">
            <div class="voc-dark-header">
                <h1>Community Pulse</h1>
                <p>Real-Time Module Overview & Platform Status Summary</p>
            </div>
        </div>

        <div class="voc-pulse-grid">';

            // 1. LIVE NEWS FEED
            if ( in_array('news', $current_modules, true) && villocity_is_module_installed(array('file' => 'modules/news/news.php')) ) {
                $news_db  = get_option('voc_saved_news_articles_db', array());
                $n_active = count($news_db);
                $n_clicks = 0;
                foreach($news_db as $n) { $n_clicks += (int)($n['clicks'] ?? 0); }
                
                echo '
                <div class="voc-pulse-card">
                    <div class="voc-pulse-card-header">
                        <span class="voc-pulse-label">Live News Feed</span>
                        <div class="voc-icon-badge purple">' . villocity_get_icon('news') . '</div>
                    </div>
                    <div class="voc-pulse-number">' . number_format($n_active) . '</div>
                    <div class="voc-pulse-meta">
                        <span>Total Clicks:</span>
                        <strong style="color: #60a5fa;">' . number_format($n_clicks) . '</strong>
                    </div>
                </div>';
            }

            // 2. BUSINESS DIRECTORY
            if ( in_array('directory', $current_modules, true) && villocity_is_module_installed(array('file' => 'modules/directory/directory.php')) ) {
                $d_count = (int) $wpdb->get_var( "SELECT COUNT(ID) FROM {$wpdb->posts} WHERE post_type = 'bcg_directory' AND post_status = 'publish'" );
                $d_pen   = (int) $wpdb->get_var( "SELECT COUNT(ID) FROM {$wpdb->posts} WHERE post_type = 'bcg_directory' AND post_status = 'pending'" );
                $d_url   = admin_url('admin.php?page=bcg-directory-hub#voc-tab-pending');

                echo '
                <div class="voc-pulse-card">
                    <div class="voc-pulse-card-header">
                        <span class="voc-pulse-label">Business Directory</span>
                        <div class="voc-icon-badge teal">' . villocity_get_icon('store') . '</div>
                    </div>
                    <div class="voc-pulse-number">' . number_format($d_count) . '</div>
                    <div class="voc-pulse-meta">
                        <span>Pending Submissions:</span>
                        <a href="' . esc_url($d_url) . '" style="color: ' . ($d_pen > 0 ? '#f87171' : '#4ade80') . '; text-decoration: underline; font-weight:700;">' . number_format($d_pen) . ' Review &rarr;</a>
                    </div>
                </div>';
            }

            // 3. EVENTS CALENDAR
            if ( in_array('events', $current_modules, true) && villocity_is_module_installed(array('file' => 'modules/events/events.php')) ) {
                $e_pub = (int) $wpdb->get_var( "SELECT COUNT(ID) FROM {$wpdb->posts} WHERE post_type = 'bcg_event' AND post_status = 'publish'" );
                $e_pen = (int) $wpdb->get_var( "SELECT COUNT(ID) FROM {$wpdb->posts} WHERE post_type = 'bcg_event' AND post_status = 'pending'" );
                $e_url = admin_url('admin.php?page=bcg-events-hub#voc-tab-pending');

                echo '
                <div class="voc-pulse-card">
                    <div class="voc-pulse-card-header">
                        <span class="voc-pulse-label">Events Calendar</span>
                        <div class="voc-icon-badge blue">' . villocity_get_icon('calendar') . '</div>
                    </div>
                    <div class="voc-pulse-number">' . number_format($e_pub) . '</div>
                    <div class="voc-pulse-meta">
                        <span>Pending Submissions:</span>
                        <a href="' . esc_url($e_url) . '" style="color: ' . ($e_pen > 0 ? '#f87171' : '#94a3b8') . '; text-decoration: underline; font-weight:700;">' . number_format($e_pen) . ' Review &rarr;</a>
                    </div>
                </div>';
            }

            // 4. MARKETPLACE
            if ( in_array('marketplace', $current_modules, true) && villocity_is_module_installed(array('file' => 'modules/marketplace/marketplace.php')) ) {
                $m_pub = (int) $wpdb->get_var( "SELECT COUNT(ID) FROM {$wpdb->posts} WHERE post_type = 'bcg_listing' AND post_status = 'publish'" );
                $m_pen = (int) $wpdb->get_var( "SELECT COUNT(ID) FROM {$wpdb->posts} WHERE post_type = 'bcg_listing' AND post_status = 'pending'" );
                $m_url = admin_url('admin.php?page=bcg-marketplace-hub#voc-tab-pending');

                echo '
                <div class="voc-pulse-card">
                    <div class="voc-pulse-card-header">
                        <span class="voc-pulse-label">Marketplace</span>
                        <div class="voc-icon-badge amber">' . villocity_get_icon('bag') . '</div>
                    </div>
                    <div class="voc-pulse-number">' . number_format($m_pub) . '</div>
                    <div class="voc-pulse-meta">
                        <span>Pending Postings:</span>
                        <a href="' . esc_url($m_url) . '" style="color: ' . ($m_pen > 0 ? '#f87171' : '#94a3b8') . '; text-decoration: underline; font-weight:700;">' . number_format($m_pen) . ' Review &rarr;</a>
                    </div>
                </div>';
            }

            // 5. ADA ALERT BAR
            if ( in_array('alert_bar', $current_modules, true) && villocity_is_module_installed(array('file' => 'modules/alert-bar/alert-bar.php')) ) {
                $voc_alerts = get_posts( array( 'post_type' => 'voc_alert', 'post_status' => 'publish', 'posts_per_page' => -1 ) );
                $voc_active_count = count($voc_alerts);

                echo '
                <div class="voc-pulse-card">
                    <div class="voc-pulse-card-header">
                        <span class="voc-pulse-label">ADA Alert Bar</span>
                        <div class="voc-icon-badge rose">' . villocity_get_icon('alert') . '</div>
                    </div>
                    <div class="voc-pulse-number">' . number_format($voc_active_count) . '</div>
                    <div class="voc-pulse-meta">
                        <span>Status:</span>
                        <strong style="color: #4ade80;">Active Banners</strong>
                    </div>
                </div>';
            }

            // 6. DESTINATIONS & TOURISM
            if ( in_array('destinations', $current_modules, true) && villocity_is_module_installed(array('file' => 'modules/destinations/destinations.php')) ) {
                $dest_count = (int) $wpdb->get_var( "SELECT COUNT(ID) FROM {$wpdb->posts} WHERE post_type = 'voc_destination' AND post_status = 'publish'" );
                $dest_pen   = (int) $wpdb->get_var( "SELECT COUNT(ID) FROM {$wpdb->posts} WHERE post_type = 'voc_destination' AND post_status = 'pending'" );
                $dest_url   = admin_url('edit.php?post_status=pending&post_type=voc_destination');

                echo '
                <div class="voc-pulse-card">
                    <div class="voc-pulse-card-header">
                        <span class="voc-pulse-label">Destinations & Tourism</span>
                        <div class="voc-icon-badge teal">' . villocity_get_icon('map-pin') . '</div>
                    </div>
                    <div class="voc-pulse-number">' . number_format($dest_count) . '</div>
                    <div class="voc-pulse-meta">
                        <span>Pending Review:</span>
                        <a href="' . esc_url($dest_url) . '" style="color: ' . ($dest_pen > 0 ? '#f87171' : '#4ade80') . '; text-decoration: underline; font-weight:700;">' . number_format($dest_pen) . ' Review &rarr;</a>
                    </div>
                </div>';
            }

            // 7. AVAILABLE PROPERTIES & SITES
            if ( in_array('properties', $current_modules, true) && villocity_is_module_installed(array('file' => 'modules/properties/properties.php')) ) {
                $prop_count = (int) $wpdb->get_var( "SELECT COUNT(ID) FROM {$wpdb->posts} WHERE post_type = 'voc_property' AND post_status = 'publish'" );
                $prop_pen   = (int) $wpdb->get_var( "SELECT COUNT(ID) FROM {$wpdb->posts} WHERE post_type = 'voc_property' AND post_status = 'pending'" );
                $prop_url   = admin_url('edit.php?post_status=pending&post_type=voc_property');

                echo '
                <div class="voc-pulse-card">
                    <div class="voc-pulse-card-header">
                        <span class="voc-pulse-label">Available Properties</span>
                        <div class="voc-icon-badge blue">' . villocity_get_icon('building') . '</div>
                    </div>
                    <div class="voc-pulse-number">' . number_format($prop_count) . '</div>
                    <div class="voc-pulse-meta">
                        <span>Pending Listings:</span>
                        <a href="' . esc_url($prop_url) . '" style="color: ' . ($prop_pen > 0 ? '#f87171' : '#4ade80') . '; text-decoration: underline; font-weight:700;">' . number_format($prop_pen) . ' Review &rarr;</a>
                    </div>
                </div>';
            }

            // 8. BOARD & STAFF DIRECTORY
            if ( in_array('staff', $current_modules, true) && villocity_is_module_installed(array('file' => 'modules/staff/staff.php')) ) {
                $staff_count = (int) $wpdb->get_var( "SELECT COUNT(ID) FROM {$wpdb->posts} WHERE post_type = 'bcg_staff' AND post_status = 'publish'" );
                $staff_url   = admin_url('edit.php?post_type=bcg_staff');

                echo '
                <div class="voc-pulse-card">
                    <div class="voc-pulse-card-header">
                        <span class="voc-pulse-label">Board & Staff Directory</span>
                        <div class="voc-icon-badge blue">' . villocity_get_icon('user') . '</div>
                    </div>
                    <div class="voc-pulse-number">' . number_format($staff_count) . '</div>
                    <div class="voc-pulse-meta">
                        <span>Active Roster:</span>
                        <a href="' . esc_url($staff_url) . '" style="color: #60a5fa; text-decoration: underline; font-weight:700;">Manage Staff &rarr;</a>
                    </div>
                </div>';
            }

            // 9. DIGITAL ARCHIVES & HISTORY
            if ( in_array('history', $current_modules, true) && villocity_is_module_installed(array('file' => 'modules/history/history.php')) ) {
                $hist_count = (int) $wpdb->get_var( "SELECT COUNT(ID) FROM {$wpdb->posts} WHERE post_type = 'bcg_artifact' AND post_status = 'publish'" );
                $hist_url   = admin_url('edit.php?post_type=bcg_artifact');

                echo '
                <div class="voc-pulse-card">
                    <div class="voc-pulse-card-header">
                        <span class="voc-pulse-label">Archives & Exhibits</span>
                        <div class="voc-icon-badge amber">' . villocity_get_icon('book') . '</div>
                    </div>
                    <div class="voc-pulse-number">' . number_format($hist_count) . '</div>
                    <div class="voc-pulse-meta">
                        <span>Digitized Artifacts:</span>
                        <a href="' . esc_url($hist_url) . '" style="color: #60a5fa; text-decoration: underline; font-weight:700;">View Collection &rarr;</a>
                    </div>
                </div>';
            }

            // 10. CEMETERY DIRECTORY
            if ( in_array('cemetery', $current_modules, true) && villocity_is_module_installed(array('file' => 'modules/cemetery/cemetery.php')) ) {
                $cem_count = (int) $wpdb->get_var( "SELECT COUNT(ID) FROM {$wpdb->posts} WHERE post_type = 'voc_grave' AND post_status = 'publish'" );
                $cem_sections = count(get_terms( array( 'taxonomy' => 'voc_cemetery_section', 'hide_empty' => false ) ));

                echo '
                <div class="voc-pulse-card">
                    <div class="voc-pulse-card-header">
                        <span class="voc-pulse-label">Cemetery Directory</span>
                        <div class="voc-icon-badge blue">' . villocity_get_icon('archive') . '</div>
                    </div>
                    <div class="voc-pulse-number">' . number_format($cem_count) . '</div>
                    <div class="voc-pulse-meta">
                        <span>Sections Mapped:</span>
                        <strong style="color: #60a5fa;">' . number_format($cem_sections) . '</strong>
                    </div>
                </div>';
            }

    echo '
        </div>
    </div>';

    // LIVE ACTIVITY LOG COMPONENT
    echo '
    <div class="voc-activity-log-card">
        <span style="font-size: 11px; font-weight: 800; color: #64748b; letter-spacing: 0.08em; text-transform: uppercase; display: block; margin-bottom: 16px;">LIVE ACTIVITY LOG</span>';

        $recent_posts = get_posts( array(
            'post_type'      => array( 'bcg_event', 'bcg_listing', 'voc_alert', 'bcg_news' ),
            'post_status'    => array( 'publish', 'pending' ),
            'posts_per_page' => 4,
            'orderby'        => 'date',
            'order'          => 'DESC',
        ) );

        if ( ! empty( $recent_posts ) ) {
            foreach ( $recent_posts as $post_item ) {
                $pt_label = 'Submission';
                $dot_color = 'green';

                if ( $post_item->post_type === 'bcg_event' ) {
                    $pt_label = ($post_item->post_status === 'pending') ? 'Event Pending Review' : 'New Event Submitted';
                    $dot_color = ($post_item->post_status === 'pending') ? 'red' : 'green';
                } elseif ( $post_item->post_type === 'voc_alert' ) {
                    $pt_label = 'Global Alert Active';
                    $dot_color = 'red';
                } elseif ( $post_item->post_type === 'bcg_listing' ) {
                    $pt_label = 'Marketplace Post';
                    $dot_color = 'blue';
                }

                $time_diff = human_time_diff( strtotime($post_item->post_date), current_time('timestamp') ) . ' ago';

                echo '
                <div class="voc-activity-row">
                    <div>
                        <span class="voc-dot ' . $dot_color . '"></span>
                        <strong style="color: #f8fafc;">' . esc_html($pt_label) . ':</strong>
                        <span style="color: #cbd5e1; margin-left: 6px;">"' . esc_html($post_item->post_title) . '"</span>
                    </div>
                    <span style="color: #64748b; font-size: 13px;">' . esc_html($time_diff) . '</span>
                </div>';
            }
        } else {
            echo '<div style="color: #64748b; font-size: 13px; padding: 12px 0;">No recent community activity logged.</div>';
        }

    echo '
    </div>';

    // MODULE MANAGER, BRAND CONFIGURATION & WHITE-LABEL SETUP FORM
    echo '
    <form method="post" style="margin-top: 28px;">
        ' . wp_nonce_field( 'villocity_save_global', 'villocity_save_global_nonce', true, false ) . '

        <!-- DYNAMIC MODULE MANAGER WITH FILE DIAGNOSTICS & CUSTOM CLIENT SECTION -->
        <div class="voc-gemini-card">
            <h3>' . villocity_get_icon('settings') . ' Platform Module Manager</h3>
            <p style="color:#64748b; margin-top:0;">Enable active platform modules or request additional features from Brockett Creative Group.</p>

            <!-- 1. STANDARD PLATFORM MODULES -->
            <h4 style="margin:24px 0 12px 0; color:#0f172a; font-size:16px;">Standard Modules</h4>
            <div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap:16px;">';

                $all_registered = villocity_get_registered_modules();
                foreach ( $all_registered as $slug => $mod ) {
                    if ( 'standard' !== $mod['type'] ) continue;

                    $is_installed = villocity_is_module_installed( $mod );
                    $is_active    = in_array( $slug, $current_modules, true ) && $is_installed;

                    echo '<div class="voc-module-card ' . ($is_installed ? ($is_active ? 'is-active' : '') : 'is-uninstalled') . '">
                        <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:8px;">
                            <label style="font-weight:700; font-size:15px; color:#0f172a; display:flex; align-items:center; gap:8px; cursor:' . ($is_installed ? 'pointer' : 'default') . ';">';
                                if ( $is_installed ) {
                                    echo '<input type="checkbox" name="villocity_active_modules[]" value="' . esc_attr($slug) . '" ' . checked($is_active, true, false) . ' style="width:18px; height:18px;">';
                                } else {
                                    echo '<span class="voc-badge-uninstalled">Not Installed</span>';
                                }
                                echo '<span>' . esc_html($mod['name']) . '</span>
                            </label>
                        </div>
                        
                        <p style="margin:4px 0 12px 0; font-size:13px; color:#64748b; line-height:1.4;">' . esc_html($mod['desc']) . '</p>';

                        if ( ! $is_installed ) {
                            echo '<a href="https://www.brockettcreative.com/support/" target="_blank" class="voc-gemini-btn-secondary" style="font-size:11px !important; padding:4px 10px !important;">'
                                . villocity_get_icon('mail') . ' Request Module Add-On
                            </a>';
                        }
                    echo '</div>';
                }

    echo '  </div>

            <!-- 2. CUSTOM CLIENT-SPECIFIC MODULES -->
            <h4 style="margin:32px 0 12px 0; color:#0f172a; font-size:16px; display:flex; align-items:center; gap:8px;">
                <span>Custom Client Add-Ons</span>
                <span class="voc-badge-custom">Bespoke Integrations</span>
            </h4>';

            $custom_installed_count = 0;
            foreach ( $all_registered as $slug => $mod ) {
                if ( 'custom' === $mod['type'] && villocity_is_module_installed( $mod ) ) {
                    $custom_installed_count++;
                }
            }

            if ( $custom_installed_count === 0 ) {
                echo '<div style="background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%); border: 1px dashed #f59e0b; border-radius: 16px; padding: 24px; text-align: center; margin-bottom: 24px;">
                    <div style="width: 44px; height: 44px; background: rgba(245, 158, 11, 0.2); color: #d97706; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 12px;">'
                        . villocity_get_icon('plus') .
                    '</div>
                    <h4 style="margin: 0 0 6px 0; font-size: 17px; font-weight: 800; color: #92400e;">Have a Custom Feature Idea for Your Site?</h4>
                    <p style="margin: 0 0 16px 0; font-size: 14px; color: #b45309; max-width: 600px; display: inline-block; line-height: 1.5;">
                        Need a bespoke municipal tax calculator, specialized police blotter, harbor slip booking system, or custom API feed? Our engineering team can build it directly into your VilloCity Suite.
                    </p>
                    <div>
                        <a href="https://www.brockettcreative.com/support/" target="_blank" class="voc-gemini-btn-primary" style="background: #d97706 !important; border: none !important;">'
                            . villocity_get_icon('mail') . ' Submit Custom Feature Request &rarr;
                        </a>
                    </div>
                </div>';
            } else {
                echo '<div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap:16px;">';
                foreach ( $all_registered as $slug => $mod ) {
                    if ( 'custom' !== $mod['type'] ) continue;

                    $is_installed = villocity_is_module_installed( $mod );
                    $is_active    = in_array( $slug, $current_modules, true ) && $is_installed;

                    echo '<div class="voc-module-card ' . ($is_installed ? ($is_active ? 'is-active' : '') : 'is-uninstalled') . '">
                        <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:8px;">
                            <label style="font-weight:700; font-size:15px; color:#0f172a; display:flex; align-items:center; gap:8px; cursor:' . ($is_installed ? 'pointer' : 'default') . ';">';
                                if ( $is_installed ) {
                                    echo '<input type="checkbox" name="villocity_active_modules[]" value="' . esc_attr($slug) . '" ' . checked($is_active, true, false) . ' style="width:18px; height:18px;">';
                                } else {
                                    echo '<span class="voc-badge-uninstalled">Available Custom Add-On</span>';
                                }
                                echo '<span>' . esc_html($mod['name']) . '</span>
                            </label>
                        </div>
                        
                        <p style="margin:4px 0 12px 0; font-size:13px; color:#64748b; line-height:1.4;">' . esc_html($mod['desc']) . '</p>';

                        if ( ! $is_installed ) {
                            echo '<a href="https://www.brockettcreative.com/support/" target="_blank" class="voc-gemini-btn-secondary" style="font-size:11px !important; padding:4px 10px !important;">'
                                . villocity_get_icon('mail') . ' Contact Brockett Creative Group
                            </a>';
                        }
                    echo '</div>';
                }
                echo '</div>';
            }

    echo '</div>

        <!-- WHITE-LABEL ENGINE SETTINGS & ONE-CLICK PRESETS (BELOW MODULE MANAGER) -->
        <div class="voc-gemini-card">
            <h3>' . villocity_get_icon('shield') . ' White-Label System Setup</h3>
            <p style="color:#64748b; margin-top:0;">Configure universal search text, template overrides, and apply preset setups for different organization types.</p>
            
            <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:14px; padding:20px; margin-bottom:24px;">
                <strong style="font-size:14px; color:#0f172a; display:block; margin-bottom:12px;">One-Click Organization Presets</strong>
                <div style="display:flex; gap:12px; flex-wrap:wrap;">
                    <button type="submit" name="voc_apply_preset" value="municipality" class="voc-gemini-btn-secondary" onclick="return confirm(\'Load Municipal Government preset wording?\');">Municipal Government</button>
                    <button type="submit" name="voc_apply_preset" value="chamber" class="voc-gemini-btn-secondary" onclick="return confirm(\'Load Chamber of Commerce preset wording?\');">Chamber of Commerce</button>
                    <button type="submit" name="voc_apply_preset" value="universal" class="voc-gemini-btn-secondary" onclick="return confirm(\'Load Universal Community Hub preset wording?\');">Universal Community Hub</button>
                </div>
            </div>

            <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:14px; padding:18px 22px; margin-bottom:24px;">
                <label style="font-weight:600; cursor:pointer; display:flex; align-items:center; gap:12px; color:#0f172a;">
                    <input type="checkbox" name="villocity_enable_search_template" value="1" ' . checked($enable_search_tpl, '1', false) . ' style="width:20px; height:20px;">
                    <span>Enable Built-in VilloCity Search Results Template (Recommended)</span>
                </label>
                <p style="margin:6px 0 0 32px; font-size:13px; color:#64748b;">When checked (default), VilloCity renders an ADA-compliant search results page matching your global colors without requiring theme builder templates.</p>
            </div>

            <table class="voc-gemini-data-table" style="max-width:750px;">
                <tr>
                    <th style="width:240px;">Search Modal Header</th>
                    <td><input type="text" name="villocity_nav_search_title" value="' . esc_attr($s_title) . '" class="voc-gemini-input"></td>
                </tr>
                <tr>
                    <th>Search Modal Subtitle</th>
                    <td><input type="text" name="villocity_nav_search_desc" value="' . esc_attr($s_desc) . '" class="voc-gemini-input"></td>
                </tr>
                <tr>
                    <th>Input Placeholder Text</th>
                    <td><input type="text" name="villocity_nav_search_placeholder" value="' . esc_attr($s_ph) . '" class="voc-gemini-input"></td>
                </tr>
            </table>
        </div>

        <div class="voc-gemini-card">
            <h3>' . villocity_get_icon('star') . ' Global Brand Styling</h3>
            <p style="color:#64748b; margin-top:0;">Configure global typography inheritance and white-label theme style overrides.</p>
            
            <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:14px; padding:18px 22px; margin-bottom:24px;">
                <label style="font-weight:600; cursor:pointer; display:flex; align-items:center; gap:12px; color:#0f172a;">
                    <input type="checkbox" name="villocity_inherit_theme_styles" value="1" ' . checked($inherit_theme, '1', false) . ' style="width:20px; height:20px;">
                    <span>Inherit Parent Theme & Elementor Typography / Palette</span>
                </label>
                <p style="margin:6px 0 0 32px; font-size:13px; color:#64748b;">When active, modules automatically adopt Elementor Global Colors and parent theme typography.</p>
            </div>

            <table class="voc-gemini-data-table" style="max-width:650px;">
                <tr>
                    <th style="width:240px;">Frontend Layout Style</th>
                    <td>
                        <select name="villocity_global_layout_style" class="voc-gemini-select" style="max-width:320px;">
                            <option value="boxed" ' . selected( $g_layout, 'boxed', false ) . '>Boxed Cards (Borders & Backgrounds)</option>
                            <option value="minimal-grid" ' . selected( $g_layout, 'minimal-grid', false ) . '>Minimal Grid (No Container Background, Boxed Cards)</option>
                            <option value="clean" ' . selected( $g_layout, 'clean', false ) . '>Clean List (Directory Style)</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th>Primary Brand Color</th>
                    <td><input type="color" name="villocity_global_primary" value="' . esc_attr( $g_primary ) . '" style="width:44px; height:44px; border:none; border-radius:10px; cursor:pointer;"></td>
                </tr>
                <tr>
                    <th>Light Accent Background</th>
                    <td><input type="color" name="villocity_global_accent" value="' . esc_attr( $g_accent ) . '" style="width:44px; height:44px; border:none; border-radius:10px; cursor:pointer;"></td>
                </tr>
                <tr>
                    <th>Base Text Color</th>
                    <td><input type="color" name="villocity_global_text" value="' . esc_attr( $g_text ) . '" style="width:44px; height:44px; border:none; border-radius:8px; cursor:pointer;"></td>
                </tr>
                <tr>
                    <th>Feed Container Background</th>
                    <td><input type="color" name="villocity_global_bg" value="' . esc_attr( $g_bg ) . '" style="width:44px; height:44px; border:none; border-radius:8px; cursor:pointer;"></td>
                </tr>
            </table>

            <div style="margin-top: 32px; padding-top: 24px; border-top: 1px solid #f1f5f9;">
                <button type="submit" class="voc-gemini-btn-primary">' . villocity_get_icon('check') . ' Save Global Configuration</button>
            </div>
        </div>
    </form>';
}

// ==========================================
// 6. SMART CACHE PURGER
// ==========================================
if ( ! function_exists( 'villocity_clear_all_caches' ) ) {
    function villocity_clear_all_caches() {
        if ( has_action( 'litespeed_purge_all' ) ) {
            do_action( 'litespeed_purge_all' );
        } elseif ( class_exists( 'LiteSpeed_Cache_API' ) && method_exists( 'LiteSpeed_Cache_API', 'purge_all' ) ) {
            LiteSpeed_Cache_API::purge_all();
        }

        if ( function_exists( 'rocket_clean_domain' ) ) { rocket_clean_domain(); }
        if ( function_exists( 'w3tc_flush_all' ) ) { w3tc_flush_all(); }
        if ( function_exists( 'sg_cachepress_purge_cache' ) ) { sg_cachepress_purge_cache(); }
    }
}

add_action( 'save_post', 'villocity_trigger_cache_on_post_update', 10, 3 );
add_action( 'trashed_post', 'villocity_trigger_cache_on_post_update' );
add_action( 'untrashed_post', 'villocity_trigger_cache_on_post_update' );
add_action( 'deleted_post', 'villocity_trigger_cache_on_post_update' );

function villocity_trigger_cache_on_post_update( $post_id ) {
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
    
    $post_type = get_post_type( $post_id );
    $villocity_cpts = array( 'bcg_news', 'bcg_event', 'bcg_listing', 'bcg_directory', 'voc_property', 'voc_destination', 'bcg_artifact', 'bcg_staff', 'voc_department', 'voc_alert', 'voc_grave', 'voc_menu_item', 'voc_timeline_event', 'voc_resource' );
    
    if ( in_array( $post_type, $villocity_cpts, true ) ) {
        villocity_clear_all_caches();
    }
}

add_action( 'updated_option', 'villocity_purge_cache_on_option_change', 10, 3 );
function villocity_purge_cache_on_option_change( $option_name, $old_value, $new_value ) {
    if ( strpos( $option_name, 'villocity_' ) === 0 || strpos( $option_name, 'voc_' ) === 0 ) {
        villocity_clear_all_caches();
    }
}

// ==========================================
// 7. FORCE VILLOCITY TO TOP VIA ADMIN CSS
// ==========================================
add_action( 'admin_head', 'villocity_force_top_menu_css' );
function villocity_force_top_menu_css() {
    ?>
    <style>
        #adminmenu {
            display: flex !important;
            flex-direction: column !important;
        }
        #toplevel_page_villocity-hub {
            order: -1 !important;
        }
    </style>
    <?php
}

// ==========================================
// 8. PORTABLE ACTIVATION ROUTINE & TABLE CREATION
// ==========================================
register_activation_hook( __FILE__, 'villocity_suite_activate' );
function villocity_suite_activate() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'villocity_activity_log';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        post_id bigint(20) NOT NULL,
        event_type varchar(50) NOT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id),
        KEY post_id (post_id),
        KEY event_type (event_type),
        KEY created_at (created_at)
    ) $charset_collate;";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );

    if ( function_exists( 'voc_register_departments_cpt' ) ) { voc_register_departments_cpt(); }
    if ( function_exists( 'bcg_register_directory_cpt' ) ) { bcg_register_directory_cpt(); }
    if ( function_exists( 'voc_register_properties_cpt' ) ) { voc_register_properties_cpt(); }
    if ( function_exists( 'voc_register_destinations_cpt' ) ) { voc_register_destinations_cpt(); }
    if ( function_exists( 'voc_register_resources_cpt' ) ) { voc_register_resources_cpt(); }
    
    flush_rewrite_rules();
}

// ==========================================
// 9. DEFAULT SCREEN REDIRECT TO VILLOCITY HUB
// ==========================================
add_filter( 'login_redirect', 'villocity_default_login_redirect', 10, 3 );
function villocity_default_login_redirect( $redirect_to, $request, $user ) {
    if ( isset( $user->roles ) && is_array( $user->roles ) ) {
        if ( in_array( 'administrator', $user->roles, true ) ) {
            return admin_url( 'admin.php?page=villocity-hub' );
        }
    }
    return $redirect_to;
}

add_action( 'admin_init', 'villocity_redirect_dashboard_to_hub' );
function villocity_redirect_dashboard_to_hub() {
    global $pagenow;
    
    if ( $pagenow === 'index.php' && ! isset( $_GET['page'] ) ) {
        wp_safe_redirect( admin_url( 'admin.php?page=villocity-hub' ) );
        exit;
    }
}

// ==========================================
// 10. UNIVERSAL MULTI-MODULE INTERACTION TRACKER
// ==========================================
add_action( 'wp_ajax_villocity_track_global_interaction', 'villocity_track_global_interaction_callback' );
add_action( 'wp_ajax_nopriv_villocity_track_global_interaction', 'villocity_track_global_interaction_callback' );

function villocity_track_global_interaction_callback() {
    $post_id    = isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : 0;
    $event_type = isset( $_POST['event_type'] ) ? sanitize_text_field( $_POST['event_type'] ) : 'page_view';
    
    if ( $post_id > 0 ) {
        $post_type = get_post_type( $post_id );
        
        $valid_types = array( 
            'bcg_news', 'bcg_event', 'bcg_listing', 'bcg_directory', 
            'voc_property', 'voc_destination', 'voc_department', 
            'voc_alert', 'voc_resource', 'voc_grave', 'voc_menu_item' 
        );

        if ( in_array( $post_type, $valid_types, true ) ) {
            $meta_keys = array(
                'bcg_news'      => '_news_clicks',
                'bcg_event'     => '_event_views',
                'bcg_listing'   => '_listing_views',
                'bcg_directory' => '_business_views',
                'voc_alert'     => '_alert_views',
                'voc_property'  => '_property_views',
                'voc_resource'  => '_resource_clicks',
            );

            $meta_key = isset( $meta_keys[$post_type] ) ? $meta_keys[$post_type] : '_module_views';
            $current   = (int) get_post_meta( $post_id, $meta_key, true );
            update_post_meta( $post_id, $meta_key, $current + 1 );

            if ( function_exists( 'villocity_log_interaction' ) ) {
                villocity_log_interaction( $post_id, $meta_key, $event_type );
            }

            wp_send_json_success();
        }
    }
    wp_send_json_error();
}

add_action( 'wp_footer', 'villocity_inject_universal_tracker_script' );
function villocity_inject_universal_tracker_script() {
    if ( is_admin() ) return;
    
    $post_id = get_the_ID();
    ?>
    <script id="villocity-global-tracker-js">
    (function() {
        function runTracker() {
            <?php if ( is_singular( array( 'bcg_news', 'bcg_event', 'bcg_listing', 'bcg_directory', 'voc_property', 'voc_destination', 'voc_department' ) ) && $post_id ) : ?>
                const formData = new FormData();
                formData.append("action", "villocity_track_global_interaction");
                formData.append("post_id", "<?php echo (int)$post_id; ?>");
                formData.append("event_type", "view_singular");
                navigator.sendBeacon("<?php echo admin_url('admin-ajax.php'); ?>", formData);
            <?php endif; ?>

            document.addEventListener("click", function(e) {
                const card = e.target.closest("[data-voc-id], [data-id], .voc-news-card, .voc-resource-card, .bcg-card, .voc-event-card");
                if (card) {
                    const id = card.getAttribute("data-voc-id") || card.getAttribute("data-id");
                    if (id) {
                        const formData = new FormData();
                        formData.append("action", "villocity_track_global_interaction");
                        formData.append("post_id", id);
                        formData.append("event_type", "click_card");
                        navigator.sendBeacon("<?php echo admin_url('admin-ajax.php'); ?>", formData);
                    }
                }
            });
        }

        if ('requestIdleCallback' in window) {
            requestIdleCallback(runTracker);
        } else {
            setTimeout(runTracker, 0);
        }
    })();
    </script>
    <?php
}