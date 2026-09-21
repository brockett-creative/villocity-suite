<?php
/**
 * Module Name: VilloCity Destinations & Tourism (Admin)
 * Module URI:  https://villocity.org
 * Description: Location coordinates, admission meta boxes, dashboard statistics widget, and Destinations Hub admin interface.
 * Version:     1.3.1
 * Last Updated: September 5, 2026
 * Author:      Brockett Creative Group, Inc.
 * Author URI:  https://brockettcreative.com
 * Copyright:   © 2026 Brockett Creative Group, Inc. All Rights Reserved.
 * Text Domain: villocity-suite
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

// ==========================================
// 1. BACKEND META BOXES
// ==========================================
if ( ! function_exists( 'voc_destinations_add_meta_boxes' ) ) {
    add_action( 'add_meta_boxes', 'voc_destinations_add_meta_boxes' );
    function voc_destinations_add_meta_boxes() {
        add_meta_box(
            'voc_destination_meta_box',
            villocity_get_icon('map-pin') . ' Destination & Map Details',
            'voc_destinations_render_meta_box',
            'voc_destination',
            'normal',
            'high'
        );
    }
}

if ( ! function_exists( 'voc_destinations_render_meta_box' ) ) {
    function voc_destinations_render_meta_box( $post ) {
        wp_nonce_field( 'voc_destinations_save_meta', 'voc_destinations_meta_nonce' );

        $address   = get_post_meta( $post->ID, '_dest_address', true );
        $lat       = get_post_meta( $post->ID, '_dest_lat', true );
        $lng       = get_post_meta( $post->ID, '_dest_lng', true );
        $phone     = get_post_meta( $post->ID, '_dest_phone', true );
        $website   = get_post_meta( $post->ID, '_dest_website', true );
        $hours     = get_post_meta( $post->ID, '_dest_hours', true );
        $admission = get_post_meta( $post->ID, '_dest_admission', true );
        $featured  = get_post_meta( $post->ID, '_dest_featured', true );

        echo '<div style="display:flex; flex-wrap:wrap; gap:20px; padding:10px 0; font-family: var(--voc-font-family, inherit) !important;">';
        
        echo '<div style="flex:1; min-width:250px;">';
        echo '<p style="margin-top:0;"><strong>' . villocity_get_icon('map-pin') . ' Physical Address</strong><br><input type="text" name="voc_dest_address" value="'.esc_attr($address).'" style="width:100%;" placeholder="e.g. 100 Monument Ave, Fort Stanwix"></p>';
        echo '<p><strong>Latitude</strong><br><input type="text" name="voc_dest_lat" value="'.esc_attr($lat).'" style="width:100%;" placeholder="e.g. 43.2101"></p>';
        echo '<p><strong>Longitude</strong><br><input type="text" name="voc_dest_lng" value="'.esc_attr($lng).'" style="width:100%;" placeholder="e.g. -75.4552"></p>';
        echo '</div>';

        echo '<div style="flex:1; min-width:250px;">';
        echo '<p style="margin-top:0;"><strong>' . villocity_get_icon('phone') . ' Phone Number</strong><br><input type="text" name="voc_dest_phone" value="'.esc_attr($phone).'" style="width:100%;" placeholder="(315) 555-0199"></p>';
        echo '<p><strong>' . villocity_get_icon('globe') . ' Official Website URL</strong><br><input type="url" name="voc_dest_website" value="'.esc_attr($website).'" style="width:100%;" placeholder="https://..."></p>';
        echo '<p><strong>' . villocity_get_icon('clock') . ' Operating Hours / Seasonality</strong><br><input type="text" name="voc_dest_hours" value="'.esc_attr($hours).'" style="width:100%;" placeholder="e.g. May - Oct: Wed-Sun 10am-4pm"></p>';
        echo '<p><strong>' . villocity_get_icon('tag') . ' Admission Fee / Details</strong><br><input type="text" name="voc_dest_admission" value="'.esc_attr($admission).'" style="width:100%;" placeholder="e.g. $5 Adults, Kids Free"></p>';
        echo '</div>';

        echo '<div style="flex:1; min-width:250px; background:#f0fdf4; padding:15px; border-left:4px solid #16a34a; border-radius:4px;">';
        echo '<p style="margin-top:0;"><label><input type="checkbox" name="voc_dest_featured" value="yes" '.checked($featured, 'yes', false).'> <strong>' . villocity_get_icon('star') . ' Feature Attraction at Top</strong></label></p>';
        echo '<p style="color:#166534; font-size:13px; margin-bottom:0;">Featured destinations are highlighted on the main tourism map and given top priority.</p>';
        echo '</div>';

        echo '</div>';
    }
}

if ( ! function_exists( 'voc_destinations_save_meta_box' ) ) {
    add_action( 'save_post_voc_destination', 'voc_destinations_save_meta_box' );
    function voc_destinations_save_meta_box( $post_id ) {
        if ( ! isset( $_POST['voc_destinations_meta_nonce'] ) || ! wp_verify_nonce( $_POST['voc_destinations_meta_nonce'], 'voc_destinations_save_meta' ) ) return;
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;

        update_post_meta( $post_id, '_dest_address', sanitize_text_field( $_POST['voc_dest_address'] ) );
        update_post_meta( $post_id, '_dest_lat', sanitize_text_field( $_POST['voc_dest_lat'] ) );
        update_post_meta( $post_id, '_dest_lng', sanitize_text_field( $_POST['voc_dest_lng'] ) );
        update_post_meta( $post_id, '_dest_phone', sanitize_text_field( $_POST['voc_dest_phone'] ) );
        update_post_meta( $post_id, '_dest_website', esc_url_raw( $_POST['voc_dest_website'] ) );
        update_post_meta( $post_id, '_dest_hours', sanitize_text_field( $_POST['voc_dest_hours'] ) );
        update_post_meta( $post_id, '_dest_admission', sanitize_text_field( $_POST['voc_dest_admission'] ) );
        update_post_meta( $post_id, '_dest_featured', isset($_POST['voc_dest_featured']) ? 'yes' : 'no' );
    }
}

// ==========================================
// 2. CONSOLIDATED SUBMENU ROUTING IN HUB
// ==========================================
if ( ! function_exists( 'voc_destinations_add_hub_menu' ) ) {
    add_action( 'admin_menu', 'voc_destinations_add_hub_menu', 26 );
    function voc_destinations_add_hub_menu() {
        if ( menu_page_url( 'villocity-hub', false ) ) {
            add_submenu_page(
                'villocity-hub',
                'Destinations & Tourism Settings',
                'Destinations',
                'manage_options',
                'voc-destinations-hub',
                'voc_render_destinations_dashboard'
            );
        }
    }
}

// ==========================================
// 3. TABBED DESTINATIONS SETTINGS DASHBOARD
// ==========================================
if ( ! function_exists( 'voc_render_destinations_dashboard' ) ) {
    function voc_render_destinations_dashboard() {
        if ( ! current_user_can( 'manage_options' ) ) return;

        $message = '';

        if ( isset($_GET['imported']) && $_GET['imported'] === '1' ) {
            $message = '<div class="voc-gemini-notice"><h3>' . villocity_get_icon('check') . ' Sample Content Loaded</h3><p style="margin:0;">5 sample destinations, counties, and themes were successfully imported.</p></div>';
        }

        if ( isset($_POST['voc_dest_action_nonce']) && wp_verify_nonce($_POST['voc_dest_action_nonce'], 'voc_dest_action') ) {
            if ( isset($_POST['action_add_county']) && ! empty($_POST['new_county_name']) ) {
                wp_insert_term( sanitize_text_field($_POST['new_county_name']), 'voc_destination_county' );
                $message = '<div class="voc-gemini-notice"><h3>' . villocity_get_icon('check') . ' County Added</h3><p style="margin:0;">New tourism county added successfully.</p></div>';
            }
            if ( isset($_POST['action_add_theme']) && ! empty($_POST['new_theme_name']) ) {
                wp_insert_term( sanitize_text_field($_POST['new_theme_name']), 'voc_destination_theme' );
                $message = '<div class="voc-gemini-notice"><h3>' . villocity_get_icon('check') . ' Theme/Era Added</h3><p style="margin:0;">New theme/era category added successfully.</p></div>';
            }
            if ( isset($_POST['action_delete_term']) && ! empty($_POST['term_id']) && ! empty($_POST['taxonomy']) ) {
                wp_delete_term( (int) $_POST['term_id'], sanitize_text_field($_POST['taxonomy']) );
                $message = '<div class="voc-gemini-notice" style="background:#fef2f2; border-color:#fecaca; color:#991b1b;"><h3>' . villocity_get_icon('trash') . ' Category Removed</h3><p style="margin:0;">Category successfully deleted.</p></div>';
            }
        }

        $active_query   = new WP_Query( array( 'post_type' => 'voc_destination', 'post_status' => 'publish', 'posts_per_page' => -1 ) );
        $counties       = get_terms( array( 'taxonomy' => 'voc_destination_county', 'hide_empty' => false ) );
        $themes         = get_terms( array( 'taxonomy' => 'voc_destination_theme', 'hide_empty' => false ) );
        $primary_col    = get_option('villocity_global_primary', '#860038');

        echo '<div class="voc-gemini-dashboard">' . $message;
        
        villocity_render_dashboard_header( 
            'Destinations & Tourism Hub', 
            'Manage regional attractions, tourism counties, and historical themes.', 
            'map-pin', 
            admin_url('post-new.php?post_type=voc_destination') 
        );

        echo '
            <div style="display: flex; gap: 16px; margin-bottom: 30px; flex-wrap: wrap;">
                <div class="voc-gemini-card" style="margin-bottom:0; padding:20px; flex: 1; min-width: 300px;">
                    <strong style="color: ' . esc_attr($primary_col) . '; display:block; margin-bottom:6px;">Main Tourism Map & Grid Shortcode</strong>
                    <code style="display:inline-block; padding:4px 8px; background:#f1f5f9; border-radius:6px; font-size:14px; border:1px solid #e2e8f0; color:#0f172a;">[villocity_destinations]</code>
                    <p style="margin: 8px 0 0 0; font-size: 13px; color: #64748b;">Displays Leaflet interactive map, county filter bar, and cards grid.</p>
                </div>
                <div class="voc-gemini-card" style="margin-bottom:0; padding:20px; flex: 0 0 auto; display:flex; align-items:center;">
                    <a href="' . esc_url( admin_url('admin.php?page=voc-destinations-hub&generate_voc_destinations_dummy=1') ) . '" class="voc-gemini-btn-secondary">' . villocity_get_icon('plus') . ' Import 5 Sample Destinations</a>
                </div>
            </div>

            <div class="voc-gemini-tabs">
                <button class="voc-gemini-tab active" onclick="voc_switch_tab(event, \'voc-tab-destinations\')">' . villocity_get_icon('map-pin') . ' Active Destinations (' . $active_query->post_count . ')</button>
                <button class="voc-gemini-tab" onclick="voc_switch_tab(event, \'voc-tab-counties\')">' . villocity_get_icon('globe') . ' Tourism Counties (' . (is_wp_error($counties) ? 0 : count($counties)) . ')</button>
                <button class="voc-gemini-tab" onclick="voc_switch_tab(event, \'voc-tab-themes\')">' . villocity_get_icon('tag') . ' Themes & Eras (' . (is_wp_error($themes) ? 0 : count($themes)) . ')</button>
            </div>

            <!-- TAB 1: ACTIVE DESTINATIONS -->
            <div id="voc-tab-destinations" class="voc-tab-content" style="display: block;">
                <div class="voc-gemini-card" style="padding: 0;">
                    <table class="voc-gemini-data-table">
                        <thead><tr><th>Attraction Name</th><th>Address</th><th>Featured</th><th style="text-align:right;">Actions</th></tr></thead>
                        <tbody>';
                        if ( $active_query->have_posts() ) {
                            while ( $active_query->have_posts() ) {
                                $active_query->the_post();
                                $id = get_the_ID();
                                $is_feat = get_post_meta($id, '_dest_featured', true) === 'yes';
                                echo '<tr>
                                    <td><strong><a href="' . esc_url(get_permalink($id)) . '" target="_blank" style="text-decoration:none; color:' . esc_attr($primary_col) . ';">' . esc_html(get_the_title()) . '</a></strong></td>
                                    <td>' . esc_html(get_post_meta($id, '_dest_address', true) ?: 'No address set') . '</td>
                                    <td>' . ($is_feat ? '<span style="background:#fffbeb; color:#92400e; padding:4px 10px; border-radius:12px; font-size:12px; font-weight:700;">★ Featured</span>' : '<span style="color:#94a3b8;">Standard</span>') . '</td>
                                    <td style="text-align:right;">
                                        <a href="' . esc_url(get_edit_post_link($id)) . '" class="voc-gemini-btn-secondary">Edit</a>
                                    </td>
                                </tr>';
                            }
                        } else {
                            echo '<tr><td colspan="4" style="text-align:center; padding:40px; color:#64748b;">No destinations posted yet.</td></tr>';
                        }
        echo '          </tbody>
                    </table>
                </div>
            </div>

            <!-- TAB 2: TOURISM COUNTIES MANAGER -->
            <div id="voc-tab-counties" class="voc-tab-content" style="display: none;">
                <div class="voc-gemini-card">
                    <h3>' . villocity_get_icon('globe') . ' Tourism Counties</h3>
                    <p style="color:#64748b; margin-top:0;">Manage counties used for geographical search and cross-promotions.</p>
                    
                    <form method="post" style="margin-bottom:24px; display:flex; gap:12px; max-width:500px;">
                        ' . wp_nonce_field( 'voc_dest_action', 'voc_dest_action_nonce', true, false ) . '
                        <input type="hidden" name="action_add_county" value="1">
                        <input type="text" name="new_county_name" class="voc-gemini-input" placeholder="e.g. Herkimer County" required>
                        <button type="submit" class="voc-gemini-btn-primary" style="white-space:nowrap;">' . villocity_get_icon('plus') . ' Add County</button>
                    </form>

                    <table class="voc-gemini-data-table">
                        <thead><tr><th>County Name</th><th>Slug</th><th>Assigned Destinations</th><th style="text-align:right;">Actions</th></tr></thead>
                        <tbody>';
                        if ( ! is_wp_error($counties) && ! empty($counties) ) {
                            foreach ( $counties as $c ) {
                                echo '<tr>
                                    <td><strong>' . esc_html($c->name) . '</strong></td>
                                    <td><code>' . esc_html($c->slug) . '</code></td>
                                    <td>' . esc_html($c->count) . ' sites</td>
                                    <td style="text-align:right;">
                                        <form method="post" style="display:inline;" onsubmit="return confirm(\'Delete this county?\');">
                                            ' . wp_nonce_field( 'voc_dest_action', 'voc_dest_action_nonce', true, false ) . '
                                            <input type="hidden" name="action_delete_term" value="1">
                                            <input type="hidden" name="term_id" value="' . $c->term_id . '">
                                            <input type="hidden" name="taxonomy" value="voc_destination_county">
                                            <button type="submit" class="voc-gemini-btn-danger">' . villocity_get_icon('trash') . ' Delete</button>
                                        </form>
                                    </td>
                                </tr>';
                            }
                        } else {
                            echo '<tr><td colspan="4" style="text-align:center; padding:30px; color:#64748b;">No counties created yet.</td></tr>';
                        }
        echo '          </tbody>
                    </table>
                </div>
            </div>

            <!-- TAB 3: THEMES & ERAS MANAGER -->
            <div id="voc-tab-themes" class="voc-tab-content" style="display: none;">
                <div class="voc-gemini-card">
                    <h3>' . villocity_get_icon('tag') . ' Historical Themes & Eras</h3>
                    <p style="color:#64748b; margin-top:0;">Categorize attractions by period or focus (e.g. Revolutionary War, Erie Canal, Industrial History).</p>

                    <form method="post" style="margin-bottom:24px; display:flex; gap:12px; max-width:500px;">
                        ' . wp_nonce_field( 'voc_dest_action', 'voc_dest_action_nonce', true, false ) . '
                        <input type="hidden" name="action_add_theme" value="1">
                        <input type="text" name="new_theme_name" class="voc-gemini-input" placeholder="e.g. Revolutionary War" required>
                        <button type="submit" class="voc-gemini-btn-primary" style="white-space:nowrap;">' . villocity_get_icon('plus') . ' Add Theme</button>
                    </form>

                    <table class="voc-gemini-data-table">
                        <thead><tr><th>Theme / Era Name</th><th>Slug</th><th>Assigned Destinations</th><th style="text-align:right;">Actions</th></tr></thead>
                        <tbody>';
                        if ( ! is_wp_error($themes) && ! empty($themes) ) {
                            foreach ( $themes as $t ) {
                                echo '<tr>
                                    <td><strong>' . esc_html($t->name) . '</strong></td>
                                    <td><code>' . esc_html($t->slug) . '</code></td>
                                    <td>' . esc_html($t->count) . ' sites</td>
                                    <td style="text-align:right;">
                                        <form method="post" style="display:inline;" onsubmit="return confirm(\'Delete this theme?\');">
                                            ' . wp_nonce_field( 'voc_dest_action', 'voc_dest_action_nonce', true, false ) . '
                                            <input type="hidden" name="action_delete_term" value="1">
                                            <input type="hidden" name="term_id" value="' . $t->term_id . '">
                                            <input type="hidden" name="taxonomy" value="voc_destination_theme">
                                            <button type="submit" class="voc-gemini-btn-danger">' . villocity_get_icon('trash') . ' Delete</button>
                                        </form>
                                    </td>
                                </tr>';
                            }
                        } else {
                            echo '<tr><td colspan="4" style="text-align:center; padding:30px; color:#64748b;">No themes created yet.</td></tr>';
                        }
        echo '          </tbody>
                    </table>
                </div>
            </div>

        </div>

        <script>
            function voc_switch_tab(event, tabId) {
                if(event) event.preventDefault();
                var contents = document.getElementsByClassName("voc-tab-content");
                for (var i = 0; i < contents.length; i++) { contents[i].style.display = "none"; }
                var tabs = document.getElementsByClassName("voc-gemini-tab");
                for (var i = 0; i < tabs.length; i++) { tabs[i].classList.remove("active"); }
                document.getElementById(tabId).style.display = "block";
                
                if(event) {
                    event.currentTarget.classList.add("active");
                } else {
                    var activeLink = document.querySelector(".voc-gemini-tab[onclick*=\'" + tabId + "\']");
                    if (activeLink) activeLink.classList.add("active");
                }
                localStorage.setItem("voc_dest_active_tab", tabId);
            }

            document.addEventListener("DOMContentLoaded", function() {
                var activeTab = localStorage.getItem("voc_dest_active_tab");
                if (activeTab && document.getElementById(activeTab)) {
                    voc_switch_tab(null, activeTab);
                }
            });
        </script>';
    }
}

// ==========================================
// 4. WORDPRESS MAIN DASHBOARD STATISTICS WIDGET
// ==========================================
if ( ! function_exists( 'voc_destinations_add_dashboard_widget' ) ) {
    add_action( 'wp_dashboard_setup', 'voc_destinations_add_dashboard_widget' );
    function voc_destinations_add_dashboard_widget() {
        if ( current_user_can( 'manage_options' ) ) {
            wp_add_dashboard_widget(
                'voc_destinations_dashboard_widget',
                '📍 Live Destinations - At a Glance',
                'voc_destinations_render_dashboard_widget'
            );
        }
    }
}

if ( ! function_exists( 'voc_destinations_render_dashboard_widget' ) ) {
    function voc_destinations_render_dashboard_widget() {
        $active_query   = new WP_Query( array( 'post_type' => 'voc_destination', 'post_status' => 'publish', 'posts_per_page' => -1 ) );
        $featured_query = new WP_Query( array( 'post_type' => 'voc_destination', 'post_status' => 'publish', 'meta_key' => '_dest_featured', 'meta_value' => 'yes', 'posts_per_page' => -1 ) );
        $counties       = get_terms( array( 'taxonomy' => 'voc_destination_county', 'hide_empty' => false ) );

        $total_active   = $active_query->post_count;
        $total_featured = $featured_query->post_count;
        $total_counties = ! is_wp_error( $counties ) ? count( $counties ) : 0;

        $primary_col    = get_option('villocity_global_primary', '#860038');
        $manage_url     = admin_url( 'admin.php?page=voc-destinations-hub' );
        ?>
        <style>
            .voc-dest-widget-grid { display: flex; gap: 10px; margin-bottom: 16px; text-align: center; font-family: var(--voc-font-family, inherit) !important; }
            .voc-dest-widget-stat { flex: 1; background: #f8f9fa; border: 1px solid #e1e3e1; border-radius: 12px; padding: 12px 6px; }
            .voc-dest-widget-stat strong { display: block; font-size: 20px; color: <?php echo esc_attr($primary_col); ?>; font-weight: 700; line-height: 1.2; }
            .voc-dest-widget-stat span { font-size: 11px; color: #50575e; text-transform: uppercase; font-weight: 600; }
            .voc-dest-widget-footer { border-top: 1px solid #f0f4f8; padding-top: 12px; text-align: right; }
            .voc-dest-widget-btn { display: inline-block; background: <?php echo esc_attr($primary_col); ?>; color: #ffffff !important; text-decoration: none !important; font-weight: 500; padding: 8px 18px; border-radius: 20px; font-size: 13px; transition: opacity 0.2s; }
            .voc-dest-widget-btn:hover { opacity: 0.9; }
        </style>

        <div class="voc-dest-widget-grid">
            <div class="voc-dest-widget-stat">
                <strong><?php echo esc_html( $total_active ); ?></strong>
                <span>Destinations</span>
            </div>
            <div class="voc-dest-widget-stat">
                <strong><?php echo esc_html( $total_featured ); ?></strong>
                <span>Featured</span>
            </div>
            <div class="voc-dest-widget-stat">
                <strong><?php echo esc_html( $total_counties ); ?></strong>
                <span>Counties</span>
            </div>
        </div>

        <div class="voc-dest-widget-footer">
            <a href="<?php echo esc_url( $manage_url ); ?>" class="voc-dest-widget-btn">Manage Destinations &rarr;</a>
        </div>
        <?php
    }
}