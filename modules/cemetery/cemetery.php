<?php
/**
 * Module Name: VilloCity Cemetery Directory & Plot Locator
 * Module URI:  https://villocity.org
 * Description: High-performance grave search engine with interactive SVG section mapping, CSV bulk import, mock data generator, and section filters.
 * Version:     1.4.0 (Consolidated Single-File Build)
 * Author:      Brockett Creative Group, Inc.
 * Author URI:  https://brockettcreative.com
 * Copyright:   © 2026 Brockett Creative Group, Inc. All Rights Reserved.
 * Text Domain: villocity-suite
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

// ==========================================
// 1. CPT & TAXONOMY REGISTRATION
// ==========================================
if ( ! function_exists( 'voc_register_cemetery_cpt' ) ) {
    add_action( 'init', 'voc_register_cemetery_cpt', 10 );
    function voc_register_cemetery_cpt() {
        if ( ! post_type_exists( 'voc_grave' ) ) {
            register_post_type( 'voc_grave', array(
                'labels' => array(
                    'name'          => 'Grave Records',
                    'singular_name' => 'Grave Record',
                    'add_new_item'  => 'Add Grave Record',
                    'edit_item'     => 'Edit Record',
                    'all_items'     => 'All Records',
                    'menu_name'     => 'Cemetery Directory'
                ),
                'public'             => true,
                'publicly_queryable' => true,
                'show_ui'            => true,
                'show_in_menu'       => false,
                'supports'           => array( 'title', 'editor' ),
                'rewrite'            => array( 'slug' => 'grave-record' ),
            ) );
        }

        if ( ! taxonomy_exists( 'voc_cemetery_section' ) ) {
            register_taxonomy( 'voc_cemetery_section', 'voc_grave', array(
                'labels'        => array( 'name' => 'Cemetery Sections' ),
                'hierarchical'  => true,
                'show_ui'       => true,
                'rewrite'       => array( 'slug' => 'cemetery-section' ),
            ) );
        }
    }
}

// ==========================================
// 2. ADMIN MENU ROUTING & SETTINGS HUB
// ==========================================
if ( ! function_exists( 'voc_cemetery_add_admin_menu' ) ) {
    add_action( 'admin_menu', 'voc_cemetery_add_admin_menu', 25 );
    function voc_cemetery_add_admin_menu() {
        if ( function_exists( 'menu_page_url' ) && menu_page_url( 'villocity-hub', false ) ) {
            add_submenu_page(
                'villocity-hub',
                'Cemetery Directory',
                'Cemetery Directory',
                'manage_options',
                'voc-cemetery-hub',
                'voc_render_cemetery_admin_hub'
            );
        }
    }
}

if ( ! function_exists( 'voc_render_cemetery_admin_hub' ) ) {
    function voc_render_cemetery_admin_hub() {
        if ( ! current_user_can( 'manage_options' ) ) { return; }

        $message = '';

        // Generate Mock Data Routine
        if ( isset( $_POST['voc_generate_cemetery_mock_nonce'] ) && wp_verify_nonce( $_POST['voc_generate_cemetery_mock_nonce'], 'voc_generate_cemetery_mock' ) ) {
            $mock_records = array(
                array( 'name' => 'Eleanor Vance', 'section' => 'Section A', 'lot' => '101', 'date' => '1942-04-12', 'veteran' => 'no' ),
                array( 'name' => 'Captain Henry Miller', 'section' => 'Section A', 'lot' => '102-B', 'date' => '1918-11-11', 'veteran' => 'yes' ),
                array( 'name' => 'Clara Barton Woods', 'section' => 'Section B', 'lot' => '204', 'date' => '1965-08-30', 'veteran' => 'no' ),
                array( 'name' => 'Sgt. Thomas Sterling', 'section' => 'Section B', 'lot' => '205-A', 'date' => '1945-06-06', 'veteran' => 'yes' ),
                array( 'name' => 'Margaret Hamilton', 'section' => 'Section C', 'lot' => '312', 'date' => '1988-01-15', 'veteran' => 'no' ),
                array( 'name' => 'William Harrison Frost', 'section' => 'Section C', 'lot' => '315', 'date' => '1923-03-22', 'veteran' => 'no' ),
                array( 'name' => 'Lt. James Oliver', 'section' => 'Section A', 'lot' => '108', 'date' => '1971-09-04', 'veteran' => 'yes' ),
                array( 'name' => 'Dorothy Parker Reed', 'section' => 'Section D', 'lot' => '401', 'date' => '2001-12-19', 'veteran' => 'no' ),
                array( 'name' => 'Arthur Pendelton', 'section' => 'Section D', 'lot' => '405-C', 'date' => '1954-05-30', 'veteran' => 'no' ),
                array( 'name' => 'Cpl. Robert E. Lee Jr.', 'section' => 'Section B', 'lot' => '210', 'date' => '1952-07-27', 'veteran' => 'yes' ),
            );

            $created = 0;
            foreach ( $mock_records as $mock ) {
                $post_id = wp_insert_post( array(
                    'post_title'  => $mock['name'],
                    'post_type'   => 'voc_grave',
                    'post_status' => 'publish',
                ) );

                if ( ! is_wp_error( $post_id ) ) {
                    update_post_meta( $post_id, '_lot_number', $mock['lot'] );
                    update_post_meta( $post_id, '_interment_date', $mock['date'] );
                    update_post_meta( $post_id, '_veteran_status', $mock['veteran'] );
                    wp_set_object_terms( $post_id, $mock['section'], 'voc_cemetery_section' );
                    $created++;
                }
            }
            $message = '<div class="voc-gemini-notice"><h3>' . (function_exists('villocity_get_icon') ? villocity_get_icon('check') : '✓') . ' Mock Data Generated</h3><p style="margin:0;">Created <strong>' . $created . '</strong> sample burial records across multiple sections.</p></div>';
        }

        // Save Map Settings
        if ( isset( $_POST['voc_save_cemetery_map_nonce'] ) && wp_verify_nonce( $_POST['voc_save_cemetery_map_nonce'], 'voc_save_cemetery_map' ) ) {
            if ( isset( $_POST['voc_cemetery_map_image'] ) ) {
                update_option( 'voc_cemetery_map_image', esc_url_raw( $_POST['voc_cemetery_map_image'] ) );
            }
            if ( isset( $_POST['voc_cemetery_svg_polygons'] ) ) {
                update_option( 'voc_cemetery_svg_polygons', wp_unslash( $_POST['voc_cemetery_svg_polygons'] ) );
            }
            $message = '<div class="voc-gemini-notice"><h3>' . (function_exists('villocity_get_icon') ? villocity_get_icon('check') : '✓') . ' Map Settings Saved</h3><p style="margin:0;">Interactive section coordinates updated successfully.</p></div>';
        }

        // Process CSV Upload
        if ( isset( $_POST['voc_import_csv_nonce'] ) && wp_verify_nonce( $_POST['voc_import_csv_nonce'], 'voc_import_csv_action' ) ) {
            if ( ! empty( $_FILES['cemetery_csv']['tmp_name'] ) ) {
                $file = $_FILES['cemetery_csv']['tmp_name'];
                $handle = fopen( $file, 'r' );

                if ( $handle !== false ) {
                    fgetcsv( $handle, 1000, ',' ); // Skip Header row
                    $imported_count = 0;

                    while ( ( $data = fgetcsv( $handle, 1000, ',' ) ) !== false ) {
                        $full_name = ! empty( $data[0] ) ? sanitize_text_field( $data[0] ) : '';
                        $section   = ! empty( $data[1] ) ? sanitize_text_field( $data[1] ) : 'General';
                        $lot_no    = ! empty( $data[2] ) ? sanitize_text_field( $data[2] ) : '';
                        $date      = ! empty( $data[3] ) ? sanitize_text_field( $data[3] ) : '';
                        $veteran   = ! empty( $data[4] ) && strtolower( trim( $data[4] ) ) === 'yes' ? 'yes' : 'no';

                        if ( ! empty( $full_name ) ) {
                            $post_id = wp_insert_post( array(
                                'post_title'  => $full_name,
                                'post_type'   => 'voc_grave',
                                'post_status' => 'publish',
                            ) );

                            if ( ! is_wp_error( $post_id ) ) {
                                update_post_meta( $post_id, '_lot_number', $lot_no );
                                update_post_meta( $post_id, '_interment_date', $date );
                                update_post_meta( $post_id, '_veteran_status', $veteran );

                                if ( ! empty( $section ) ) {
                                    wp_set_object_terms( $post_id, $section, 'voc_cemetery_section' );
                                }
                                $imported_count++;
                            }
                        }
                    }
                    fclose( $handle );
                    $message = '<div class="voc-gemini-notice"><h3>' . (function_exists('villocity_get_icon') ? villocity_get_icon('check') : '✓') . ' CSV Import Complete</h3><p style="margin:0;">Imported <strong>' . $imported_count . '</strong> burial records.</p></div>';
                }
            }
        }

        // Metrics Queries
        $published_posts = wp_count_posts( 'voc_grave' );
        $total_records   = isset($published_posts->publish) ? $published_posts->publish : 0;
        $sections_count  = wp_count_terms( array( 'taxonomy' => 'voc_cemetery_section', 'hide_empty' => false ) );
        if ( is_wp_error( $sections_count ) ) { $sections_count = 0; }

        $veterans_query = new WP_Query( array(
            'post_type'      => 'voc_grave',
            'meta_key'       => '_veteran_status',
            'meta_value'     => 'yes',
            'posts_per_page' => -1,
            'fields'         => 'ids'
        ) );
        $veteran_count = $veterans_query->found_posts;

        $map_img      = get_option( 'voc_cemetery_map_image', '' );
        $map_status   = ! empty( $map_img ) ? 'Active Map' : 'Needs Image';

        $primary_col  = get_option( 'villocity_global_primary', '#860038' );
        $svg_polygons = get_option( 'voc_cemetery_svg_polygons', '<polygon class="voc-section-path" data-section="section-a" data-name="Section A" points="100,100 300,100 300,300 100,300" />' );

        echo '<div class="voc-gemini-dashboard">' . $message;

        if ( function_exists( 'villocity_render_dashboard_header' ) ) {
            villocity_render_dashboard_header( 'Cemetery Directory Hub', 'Manage section maps, grave records, and CSV imports.', 'store', admin_url( 'post-new.php?post_type=voc_grave' ) );
        }

        // Stats Grid
        echo '<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 16px; margin: 24px 0 28px 0;">';

        echo '<div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.02);">
            <div style="font-size: 11px; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 6px;">Total Burial Records</div>
            <div style="font-size: 28px; font-weight: 800; color: ' . esc_attr($primary_col) . '; line-height: 1;">' . number_format($total_records) . '</div>
            <div style="font-size: 12px; color: #94a3b8; margin-top: 6px;">Indexed in Database</div>
        </div>';

        echo '<div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.02);">
            <div style="font-size: 11px; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 6px;">Mapped Sections</div>
            <div style="font-size: 28px; font-weight: 800; color: #0f172a; line-height: 1;">' . number_format($sections_count) . '</div>
            <div style="font-size: 12px; color: #94a3b8; margin-top: 6px;">Taxonomy Categories</div>
        </div>';

        echo '<div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.02);">
            <div style="font-size: 11px; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 6px;">Honored Veterans</div>
            <div style="font-size: 28px; font-weight: 800; color: #1e40af; line-height: 1;">' . number_format($veteran_count) . '</div>
            <div style="font-size: 12px; color: #94a3b8; margin-top: 6px;">Military Service Flagged</div>
        </div>';

        echo '<div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.02);">
            <div style="font-size: 11px; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 6px;">Interactive Overlay</div>
            <div style="font-size: 18px; font-weight: 800; color: ' . (!empty($map_img) ? '#166534' : '#b45309') . '; margin-top: 6px;">' . esc_html($map_status) . '</div>
            <div style="font-size: 12px; color: #94a3b8; margin-top: 6px;">' . (!empty($map_img) ? 'Ground Map Connected' : 'Upload image in Map Setup') . '</div>
        </div>';

        echo '</div>'; // End Stats Grid

        // Navigation Tabs
        echo '<div class="voc-gemini-tabs">
            <button type="button" class="voc-gemini-tab active" onclick="voc_cemetery_switch_tab(event, \'voc-cemetery-tab-records\')">' . (function_exists('villocity_get_icon') ? villocity_get_icon('users') : '👥') . ' Burial Records (' . number_format($total_records) . ')</button>
            <button type="button" class="voc-gemini-tab" onclick="voc_cemetery_switch_tab(event, \'voc-cemetery-tab-map\')">' . (function_exists('villocity_get_icon') ? villocity_get_icon('map-pin') : '📍') . ' Map Setup & Guide</button>
            <button type="button" class="voc-gemini-tab" onclick="voc_cemetery_switch_tab(event, \'voc-cemetery-tab-import\')">' . (function_exists('villocity_get_icon') ? villocity_get_icon('plus') : '+') . ' CSV & Mock Data</button>
            <button type="button" class="voc-gemini-tab" onclick="voc_cemetery_switch_tab(event, \'voc-cemetery-tab-shortcodes\')">' . (function_exists('villocity_get_icon') ? villocity_get_icon('check') : '✓') . ' Shortcodes</button>
        </div>';

        // TAB 1: LIVE BURIAL RECORDS LISTING TABLE
        echo '<div id="voc-cemetery-tab-records" class="voc-tab-content" style="display:block;">
            <div class="voc-gemini-card" style="padding:0; overflow:hidden;">
                <table class="voc-gemini-data-table">
                    <thead>
                        <tr>
                            <th style="padding-left:20px;">Name</th>
                            <th>Lot Number</th>
                            <th>Section</th>
                            <th>Interment Date</th>
                            <th>Veteran Status</th>
                            <th style="text-align:right; padding-right:20px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>';

                    $live_records = new WP_Query( array(
                        'post_type'      => 'voc_grave',
                        'post_status'    => 'publish',
                        'posts_per_page' => 20,
                        'orderby'        => 'title',
                        'order'          => 'ASC'
                    ) );

                    if ( $live_records->have_posts() ) {
                        while ( $live_records->have_posts() ) {
                            $live_records->the_post();
                            $r_id   = get_the_ID();
                            $lot    = get_post_meta( $r_id, '_lot_number', true ) ?: '-';
                            $date   = get_post_meta( $r_id, '_interment_date', true ) ?: '-';
                            $is_vet = get_post_meta( $r_id, '_veteran_status', true ) === 'yes';
                            $terms  = wp_get_post_terms( $r_id, 'voc_cemetery_section' );
                            $sec    = ( ! empty( $terms ) && ! is_wp_error( $terms ) ) ? $terms[0]->name : 'General';

                            echo '<tr>
                                <td style="padding-left:20px;"><strong style="color:#0f172a;">' . esc_html( get_the_title() ) . '</strong></td>
                                <td><code>Lot ' . esc_html($lot) . '</code></td>
                                <td><span style="background:var(--voc-accent, #fadceb); color:var(--voc-primary, #860038); padding:4px 10px; border-radius:12px; font-weight:700; font-size:11px;">' . esc_html($sec) . '</span></td>
                                <td>' . esc_html($date) . '</td>
                                <td>' . ($is_vet ? '<span style="background:#dbeafe; color:#1e40af; padding:4px 10px; border-radius:12px; font-weight:700; font-size:11px;">🇺🇸 Veteran</span>' : '<span style="color:#94a3b8;">—</span>') . '</td>
                                <td style="text-align:right; padding-right:20px;">
                                    <a href="' . esc_url( get_edit_post_link($r_id) ) . '" class="voc-gemini-btn-secondary" style="padding:4px 10px !important;">Edit</a>
                                </td>
                            </tr>';
                        }
                        wp_reset_postdata();
                    } else {
                        echo '<tr><td colspan="6" style="text-align:center; padding:30px; color:#64748b;">No burial records found. Import CSV data or click "Generate Mock Data" to populate sample listings.</td></tr>';
                    }

        echo '      </tbody>
                </table>
            </div>
        </div>';

        // TAB 2: MAP GUIDE
        echo '<div id="voc-cemetery-tab-map" class="voc-tab-content" style="display:none;">
            <div class="voc-gemini-card">
                <h3>Step-by-Step Map Setup Guide</h3>
                <ol style="line-height:1.8; color:#475569; padding-left:20px;">
                    <li><strong>Upload Ground Map Image:</strong> Paste the direct image URL for your aerial photograph or drawn section diagram below.</li>
                    <li><strong>Categorize Sections:</strong> Go to <em>Cemetery Directory &rarr; Cemetery Sections</em> in WordPress and create terms matching your map (e.g. <code>section-a</code>, <code>section-b</code>).</li>
                    <li><strong>Draw Section Polygons:</strong> Open your map image in <a href="https://editor.method.ac/" target="_blank">SVG-Edit</a>. Draw a polygon shape over each cemetery section.</li>
                    <li><strong>Paste Polygon HTML:</strong> Copy the generated <code>&lt;polygon&gt;</code> lines into the code box below. Assign the matching taxonomy slug to <code>data-section="..."</code>.</li>
                </ol>

                <form method="post" style="margin-top:28px;">
                    ' . wp_nonce_field( 'voc_save_cemetery_map', 'voc_save_cemetery_map_nonce', true, false ) . '

                    <div style="margin-bottom:20px;">
                        <label style="display:block; font-weight:700; margin-bottom:8px;">Background Map Image URL</label>
                        <input type="url" name="voc_cemetery_map_image" value="' . esc_attr( $map_img ) . '" class="voc-gemini-input" placeholder="https://...">
                    </div>

                    <div style="margin-bottom:20px;">
                        <label style="display:block; font-weight:700; margin-bottom:8px;">Interactive SVG Polygon Code</label>
                        <textarea name="voc_cemetery_svg_polygons" rows="6" class="voc-gemini-textarea" style="font-family:monospace; font-size:13px;">' . esc_textarea( $svg_polygons ) . '</textarea>
                        <p style="font-size:12px; color:#64748b; margin-top:4px;">Example: <code>&lt;polygon class="voc-section-path" data-section="section-a" data-name="Section A - West Lawn" points="100,100 300,100 300,300 100,300" /&gt;</code></p>
                    </div>

                    <button type="submit" class="voc-gemini-btn-primary">' . (function_exists('villocity_get_icon') ? villocity_get_icon('check') : '✓') . ' Save Map Configuration</button>
                </form>
            </div>
        </div>';

        // TAB 3: CSV IMPORTER & MOCK DATA GENERATOR
        echo '<div id="voc-cemetery-tab-import" class="voc-tab-content" style="display:none;">
            <div style="display:flex; gap:20px; flex-wrap:wrap;">
                
                <div class="voc-gemini-card" style="flex:2; min-width:320px;">
                    <h3>Bulk CSV Importer</h3>
                    <p style="color:#64748b; margin-top:0;">Upload a spreadsheet formatted as follows:</p>
                    <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:12px; margin-bottom:20px; font-family:monospace; font-size:12px;">
                        Full Name, Section, Lot Number, Interment Date, Veteran<br>
                        John Doe, Section A, Lot 104, 1984-05-12, Yes
                    </div>
                    <form method="post" enctype="multipart/form-data">
                        ' . wp_nonce_field( 'voc_import_csv_action', 'voc_import_csv_nonce', true, false ) . '
                        <div style="margin-bottom:20px;">
                            <input type="file" name="cemetery_csv" accept=".csv" required class="voc-gemini-input" style="padding:8px !important;">
                        </div>
                        <button type="submit" class="voc-gemini-btn-primary">Start Bulk Import</button>
                    </form>
                </div>

                <div class="voc-gemini-card" style="flex:1; min-width:280px; background:#f8fafc; border:1px dashed #cbd5e1;">
                    <h3>Testing & Demo Data</h3>
                    <p style="color:#64748b; margin-top:0;">Instantly generate 10 sample burial records across Sections A–D to test frontend search, filters, and map overlays.</p>
                    <form method="post" style="margin-top:20px;">
                        ' . wp_nonce_field( 'voc_generate_cemetery_mock', 'voc_generate_cemetery_mock_nonce', true, false ) . '
                        <button type="submit" class="voc-gemini-btn-secondary" style="width:100%; justify-content:center;">' . (function_exists('villocity_get_icon') ? villocity_get_icon('plus') : '+') . ' Generate Sample Records</button>
                    </form>
                </div>

            </div>
        </div>';

        // TAB 4: SHORTCODES
        echo '<div id="voc-cemetery-tab-shortcodes" class="voc-tab-content" style="display:none;">
            <div class="voc-gemini-card">
                <h3>Directory & Map Shortcode</h3>
                <p style="color:#64748b; margin-top:0;">Paste this shortcode onto any page or Elementor container:</p>
                <code style="padding:10px 16px; background:#f1f5f9; border-radius:8px; font-size:15px; font-weight:700;">[villocity_cemetery_directory]</code>
            </div>
        </div>';

        echo '</div>'; // End Dashboard Container

        echo '<script>
        function voc_cemetery_switch_tab(event, tabId) {
            if (event) { event.preventDefault(); }
            var contents = document.getElementsByClassName("voc-tab-content");
            for (var i = 0; i < contents.length; i++) { contents[i].style.display = "none"; }
            var tabs = document.getElementsByClassName("voc-gemini-tab");
            for (var j = 0; j < tabs.length; j++) { tabs[j].classList.remove("active"); }
            var target = document.getElementById(tabId);
            if (target) { target.style.display = "block"; }
            if (event && event.currentTarget) { event.currentTarget.classList.add("active"); }
        }
        </script>';
    }
}

// ==========================================
// 3. FRONTEND SEARCH & MAP SHORTCODE
// ==========================================
if ( ! function_exists( 'voc_render_cemetery_directory' ) ) {
    function voc_render_cemetery_directory( $atts = array() ) {
        $primary_col = get_option( 'villocity_global_primary', '#860038' );
        $accent_col  = get_option( 'villocity_global_accent', '#fadceb' );

        $map_img      = get_option( 'voc_cemetery_map_image', '' );
        $svg_polygons = get_option( 'voc_cemetery_svg_polygons', '' );

        $paged = max( 1, get_query_var( 'paged' ) ? get_query_var( 'paged' ) : ( get_query_var( 'page' ) ? get_query_var( 'page' ) : 1 ) );
        $search_term = isset( $_GET['grave_search'] ) ? sanitize_text_field( $_GET['grave_search'] ) : '';
        $section_filter = isset( $_GET['grave_section'] ) ? sanitize_text_field( $_GET['grave_section'] ) : '';

        $query_args = array(
            'post_type'      => 'voc_grave',
            'post_status'    => 'publish',
            'posts_per_page' => 25,
            'paged'          => $paged,
            'orderby'        => 'title',
            'order'          => 'ASC'
        );

        if ( ! empty( $search_term ) ) { $query_args['s'] = $search_term; }

        if ( ! empty( $section_filter ) ) {
            $query_args['tax_query'] = array(
                array(
                    'taxonomy' => 'voc_cemetery_section',
                    'field'    => 'slug',
                    'terms'    => $section_filter,
                )
            );
        }

        $records_query = new WP_Query( $query_args );
        $sections = get_terms( array( 'taxonomy' => 'voc_cemetery_section', 'hide_empty' => true ) );

        $html = '<style>
            .voc-cemetery-wrap { font-family: var(--voc-font-family, inherit) !important; max-width: 1200px; margin: 0 auto; color: var(--voc-text, #1c1917); }
            .voc-map-container { position: relative; width: 100%; margin-bottom: 28px; background: #fff; border: 1px solid var(--voc-border, #e2e8f0); border-radius: var(--voc-radius, 16px); overflow: hidden; box-shadow: var(--voc-shadow, 0 4px 15px rgba(0,0,0,0.03)); }
            .voc-map-wrapper { position: relative; width: 100%; padding-bottom: 56.25%; }
            .voc-map-bg { position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover; }
            .voc-map-svg { position: absolute; top: 0; left: 0; width: 100%; height: 100%; z-index: 2; }
            .voc-section-path { fill: rgba(134, 0, 56, 0.18); stroke: ' . esc_attr($primary_col) . '; stroke-width: 2; cursor: pointer; transition: all 0.25s ease; }
            .voc-section-path:hover, .voc-section-path:focus { fill: rgba(134, 0, 56, 0.45); outline: none; }
            .voc-section-path.active-section { fill: ' . esc_attr($primary_col) . ' !important; fill-opacity: 0.65; stroke: #ffffff; stroke-width: 3; }
            .voc-map-info-box { position: absolute; bottom: 16px; left: 16px; background: rgba(255,255,255,0.95); backdrop-filter: blur(8px); padding: 10px 18px; border-radius: 10px; font-weight: 700; font-size: 14px; color: #0f172a; border: 1px solid #cbd5e1; z-index: 3; }
            .voc-cemetery-bar { background: var(--voc-bg, #fafaf9); padding: 24px; border-radius: var(--voc-radius, 16px); border: 1px solid var(--voc-border, #e2e8f0); margin-bottom: 28px; display: flex; gap: 16px; flex-wrap: wrap; }
            .voc-cemetery-table { width: 100%; border-collapse: collapse; background: #ffffff; border-radius: 12px; overflow: hidden; border: 1px solid var(--voc-border, #e2e8f0); }
            .voc-cemetery-table th { background: #f1f5f9; padding: 14px 18px; text-align: left; font-size: 13px; color: #475569; font-weight: 800; text-transform: uppercase; }
            .voc-cemetery-table td { padding: 14px 18px; border-bottom: 1px solid #f1f5f9; font-size: 15px; color: #0f172a; }
            .voc-cemetery-table tr:hover td { background: #f8fafc; }
            .voc-badge-section { background: ' . esc_attr($accent_col) . '; color: ' . esc_attr($primary_col) . '; padding: 4px 10px; border-radius: 20px; font-weight: 700; font-size: 12px; display: inline-block; }
            .voc-badge-veteran { background: #dbeafe; color: #1e40af; padding: 4px 10px; border-radius: 20px; font-weight: 700; font-size: 12px; display: inline-block; margin-left: 6px; }
            .voc-pagination { display: flex; justify-content: space-between; align-items: center; margin-top: 24px; font-size: 14px; }
            
            @media (max-width: 640px) {
                .voc-cemetery-table th:nth-child(3), .voc-cemetery-table td:nth-child(3) { display: none; }
                .voc-cemetery-bar { flex-direction: column; }
            }
        </style>';

        $html .= '<div class="voc-cemetery-wrap" role="region" aria-label="Cemetery Burial Records Directory">';

        // Render Interactive Map Viewer
        if ( ! empty( $map_img ) ) {
            $html .= '<div class="voc-map-container">
                <div class="voc-map-wrapper">
                    <img src="' . esc_url( $map_img ) . '" class="voc-map-bg" alt="Aerial layout map of the cemetery grounds">
                    <svg class="voc-map-svg" viewBox="0 0 1000 562.5" preserveAspectRatio="none" role="img" aria-label="Interactive map sections">
                        ' . $svg_polygons . '
                    </svg>
                    <div class="voc-map-info-box" id="voc-map-hover-info" aria-live="polite">Click or select a section to filter interment records</div>
                </div>
            </div>';
        }

        // Search Controls
        $html .= '<form method="get" id="voc-cemetery-form" class="voc-cemetery-bar" role="search" aria-label="Search Burial Directory">
            <div style="flex:2; min-width:240px; position:relative;">
                <label for="grave_search_input" class="screen-reader-text" style="position:absolute; width:1px; height:1px; overflow:hidden;">Search by name or lot</label>
                <input type="text" id="grave_search_input" name="grave_search" value="' . esc_attr( $search_term ) . '" class="voc-gemini-input" placeholder="Search by deceased name, lot number, or year..." style="min-height:44px;">
            </div>
            <div style="flex:1; min-width:180px;">
                <label for="voc_grave_section_select" class="screen-reader-text" style="position:absolute; width:1px; height:1px; overflow:hidden;">Filter by cemetery section</label>
                <select name="grave_section" id="voc_grave_section_select" class="voc-gemini-select" style="min-height:44px;">
                    <option value="">All Cemetery Sections</option>';
                    if ( ! is_wp_error( $sections ) && ! empty( $sections ) ) {
                        foreach ( $sections as $sec ) {
                            $html .= '<option value="' . esc_attr( $sec->slug ) . '" ' . selected( $section_filter, $sec->slug, false ) . '>' . esc_html( $sec->name ) . '</option>';
                        }
                    }
        $html .= '  </select>
            </div>
            <button type="submit" class="voc-gemini-btn-primary" style="min-height:44px; padding:0 24px;">Search Records</button>
        </form>';

        // Records Table
        $html .= '<table class="voc-cemetery-table" aria-label="Burial Records List">
            <thead>
                <tr>
                    <th scope="col">Interment Date</th>
                    <th scope="col">Deceased Name</th>
                    <th scope="col">Lot Number</th>
                    <th scope="col">Section</th>
                </tr>
            </thead>
            <tbody>';

        if ( $records_query->have_posts() ) {
            while ( $records_query->have_posts() ) {
                $records_query->the_post();
                $id           = get_the_ID();
                $date         = get_post_meta( $id, '_interment_date', true ) ?: get_the_date( 'm/d/Y' );
                $lot          = get_post_meta( $id, '_lot_number', true ) ?: '-';
                $is_vet       = get_post_meta( $id, '_veteran_status', true ) === 'yes';
                $sec_terms    = wp_get_post_terms( $id, 'voc_cemetery_section' );
                $section_label= ( ! empty( $sec_terms ) && ! is_wp_error( $sec_terms ) ) ? $sec_terms[0]->name : 'General';

                // Schema.org Microdata Structured Tagging for AEO/SEO
                $html .= '<tr itemscope itemtype="https://schema.org/Grave">';
                $html .= '<td><time itemprop="deathDate" datetime="' . esc_attr($date) . '"><strong>' . esc_html( $date ) . '</strong></time></td>';
                $html .= '<td>
                    <a href="' . get_permalink() . '" itemprop="url" style="color:' . esc_attr($primary_col) . '; text-decoration:none; font-weight:700;">
                        <span itemprop="name">' . esc_html( get_the_title() ) . '</span>
                    </a>' . 
                    ($is_vet ? '<span class="voc-badge-veteran" title="U.S. Military Veteran">🇺🇸 Veteran</span>' : '') . 
                '</td>';
                $html .= '<td>Lot ' . esc_html( $lot ) . '</td>';
                $html .= '<td><span class="voc-badge-section">' . esc_html( $section_label ) . '</span></td>';
                $html .= '</tr>';
            }
            wp_reset_postdata();
        } else {
            $html .= '<tr><td colspan="4" style="text-align:center; padding:40px; color:#64748b;">No interment records found matching your query.</td></tr>';
        }

        $html .= '</tbody></table>';

        // Pagination
        if ( $records_query->max_num_pages > 1 ) {
            $html .= '<div class="voc-pagination">
                <div>Page <strong>' . $paged . '</strong> of <strong>' . $records_query->max_num_pages . '</strong></div>
                <div>' . paginate_links( array(
                    'total'   => $records_query->max_num_pages,
                    'current' => $paged,
                    'format'  => '?paged=%#%',
                ) ) . '</div>
            </div>';
        }

        // Map Click JavaScript
        $html .= '<script>
        document.addEventListener("DOMContentLoaded", function() {
            const paths = document.querySelectorAll(".voc-section-path");
            const infoBox = document.getElementById("voc-map-hover-info");
            const select = document.getElementById("voc_grave_section_select");
            const form = document.getElementById("voc-cemetery-form");

            paths.forEach(path => {
                path.addEventListener("mouseenter", function() {
                    if (infoBox) infoBox.innerText = this.getAttribute("data-name") || "Cemetery Section";
                });
                path.addEventListener("click", function() {
                    paths.forEach(p => p.classList.remove("active-section"));
                    this.classList.add("active-section");
                    const sec = this.getAttribute("data-section");
                    if (select && form) {
                        select.value = sec;
                        form.submit();
                    }
                });
            });
        });
        </script>';

        $html .= '</div>';
        return $html;
    }
}

add_action( 'init', function() {
    if ( ! shortcode_exists( 'villocity_cemetery_directory' ) ) {
        add_shortcode( 'villocity_cemetery_directory', 'voc_render_cemetery_directory' );
    }
} );