<?php
/**
 * Module Name: VilloCity Timeline & Interactive History
 * Description: Executive chronological timeline engine with active scroll line highlighting, scroll-triggered pulsing milestone badges & cards, top/bottom end caps, card arrows, and WP Media Picker.
 * Version:     1.3.0
 * Last Updated: September 10, 2026
 * Author:      Brockett Creative Group, Inc.
 * Text Domain: villocity-suite
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

// ==========================================
// 1. CPT & TAXONOMY REGISTRATION
// ==========================================
add_action( 'init', function() {
    register_post_type( 'voc_timeline_event', array(
        'labels' => array(
            'name'          => 'Timeline Events',
            'singular_name' => 'Timeline Event',
            'add_new_item'  => 'Add Timeline Event',
            'edit_item'     => 'Edit Timeline Event',
            'menu_name'     => 'Timeline'
        ),
        'public'             => true,
        'publicly_queryable' => true,
        'show_ui'            => true,
        'show_in_menu'       => false,
        'supports'           => array( 'title', 'editor', 'thumbnail' ),
        'rewrite'            => array( 'slug' => 'timeline-event' ),
    ) );

    register_taxonomy( 'voc_timeline_era', 'voc_timeline_event', array(
        'labels'        => array( 'name' => 'Eras / Epochs' ),
        'hierarchical'  => true,
        'show_ui'       => true,
        'rewrite'       => array( 'slug' => 'timeline-era' ),
    ) );
} );

// ==========================================
// 2. META BOXES (YEAR, ORDER, FULL-BLEED & MEDIA PICKER)
// ==========================================
add_action( 'add_meta_boxes', function() {
    add_meta_box( 'voc_timeline_meta_box', 'Timeline Milestone Details & Layout Options', function( $post ) {
        wp_nonce_field( 'voc_timeline_save_meta', 'voc_timeline_nonce' );

        $year_display  = get_post_meta( $post->ID, '_timeline_year_display', true );
        $sort_year     = get_post_meta( $post->ID, '_timeline_sort_year', true );
        $custom_order  = get_post_meta( $post->ID, '_timeline_custom_order', true );
        $sub_headline  = get_post_meta( $post->ID, '_timeline_sub_headline', true );
        $full_bleed    = get_post_meta( $post->ID, '_timeline_full_bleed', true );
        $figures_json  = get_post_meta( $post->ID, '_timeline_figures', true );
        $figures       = is_array($figures_json) ? $figures_json : array();

        echo '<div style="display:flex; flex-wrap:wrap; gap:20px; padding:10px 0;">';

        // Left Col
        echo '<div style="flex:1; min-width:280px;">';
        echo '<p style="margin-top:0;"><label><strong>Display Date / Period Label:</strong></label><br>';
        echo '<input type="text" name="voc_timeline_year_display" value="' . esc_attr($year_display) . '" class="widefat" placeholder="e.g. 1888 - Current or 1920s"></p>';

        echo '<div style="display:flex; gap:10px;">';
        echo '<p style="flex:1; margin:0;"><label><strong>Numeric Auto-Sort Year:</strong></label><br>';
        echo '<input type="number" name="voc_timeline_sort_year" value="' . esc_attr($sort_year) . '" class="widefat" placeholder="e.g. 1888"></p>';

        echo '<p style="flex:1; margin:0;"><label><strong>Manual Order Weight:</strong></label><br>';
        echo '<input type="number" name="voc_timeline_custom_order" value="' . esc_attr($custom_order) . '" class="widefat" placeholder="0 (Override)"></p>';
        echo '</div>';

        echo '<p><label><strong>Sub-Headline / Tagline:</strong></label><br>';
        echo '<input type="text" name="voc_timeline_sub_headline" value="' . esc_attr($sub_headline) . '" class="widefat" placeholder="e.g. PROHIBITION SURVIVAL"></p>';

        echo '<p style="background:#f0f9ff; border-left:4px solid #3b82f6; padding:10px;"><label><input type="checkbox" name="voc_timeline_full_bleed" value="yes" ' . checked($full_bleed, 'yes', false) . '> <strong>Full-Width Card Image (Full Bleed)</strong></label><br><span style="font-size:12px; color:#64748b;">Spans the featured image to the full width of the milestone card.</span></p>';
        echo '</div>';

        // Right Col: Key Figures Sub-Tree with WP Media Picker
        echo '<div style="flex:1; min-width:280px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:14px;">';
        echo '<strong style="color:#0f172a; display:block; margin-bottom:6px;">Key Figures / Leadership Tree</strong>';
        echo '<p style="font-size:12px; color:#64748b; margin:0 0 10px 0;">Add small circular profile cards inside this timeline milestone using the media picker.</p>';

        echo '<div id="voc-figures-container">';
        if ( !empty($figures) ) {
            foreach ( $figures as $fig ) {
                echo '<div class="voc-figure-row" style="display:flex; gap:6px; margin-bottom:8px; align-items:center;">';
                echo '<input type="text" name="voc_fig_name[]" value="' . esc_attr($fig['name']) . '" placeholder="Name" style="flex:2;">';
                echo '<input type="text" name="voc_fig_year[]" value="' . esc_attr($fig['year']) . '" placeholder="Year" style="flex:1;">';
                echo '<input type="text" name="voc_fig_img[]" value="' . esc_attr($fig['img']) . '" placeholder="Photo URL" class="voc-fig-img-input" style="flex:2;">';
                echo '<button type="button" class="button voc-pick-img-btn">Select</button>';
                echo '<button type="button" class="button" onclick="this.parentElement.remove()">✕</button>';
                echo '</div>';
            }
        } else {
            echo '<div class="voc-figure-row" style="display:flex; gap:6px; margin-bottom:8px; align-items:center;">';
            echo '<input type="text" name="voc_fig_name[]" placeholder="Name" style="flex:2;">';
            echo '<input type="text" name="voc_fig_year[]" placeholder="Year" style="flex:1;">';
            echo '<input type="text" name="voc_fig_img[]" placeholder="Photo URL" class="voc-fig-img-input" style="flex:2;">';
            echo '<button type="button" class="button voc-pick-img-btn">Select</button>';
            echo '<button type="button" class="button" onclick="this.parentElement.remove()">✕</button>';
            echo '</div>';
        }
        echo '</div>';
        echo '<button type="button" class="button button-secondary" onclick="voc_add_figure_row()" style="margin-top:6px;">+ Add Key Figure</button>';
        echo '</div>';

        echo '</div>';

        echo '<script>
        function voc_add_figure_row() {
            var row = document.createElement("div");
            row.className = "voc-figure-row";
            row.style.cssText = "display:flex; gap:6px; margin-bottom:8px; align-items:center;";
            row.innerHTML = \'<input type="text" name="voc_fig_name[]" placeholder="Name" style="flex:2;">\' +
                            \'<input type="text" name="voc_fig_year[]" placeholder="Year" style="flex:1;">\' +
                            \'<input type="text" name="voc_fig_img[]" placeholder="Photo URL" class="voc-fig-img-input" style="flex:2;">\' +
                            \'<button type="button" class="button voc-pick-img-btn">Select</button>\' +
                            \'<button type="button" class="button" onclick="this.parentElement.remove()">✕</button>\';
            document.getElementById("voc-figures-container").appendChild(row);
        }

        document.addEventListener("click", function(e) {
            if (e.target && e.target.classList.contains("voc-pick-img-btn")) {
                e.preventDefault();
                var btn = e.target;
                var input = btn.previousElementSibling;
                var customUploader = wp.media({
                    title: "Choose Profile Photo",
                    button: { text: "Use Photo" },
                    multiple: false
                }).on("select", function() {
                    var attachment = customUploader.state().get("selection").first().toJSON();
                    input.value = attachment.url;
                }).open();
            }
        });
        </script>';
    }, 'voc_timeline_event', 'normal', 'high' );
} );

add_action( 'save_post_voc_timeline_event', function( $post_id ) {
    if ( ! isset( $_POST['voc_timeline_nonce'] ) || ! wp_verify_nonce( $_POST['voc_timeline_nonce'], 'voc_timeline_save_meta' ) ) { return; }
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) { return; }

    update_post_meta( $post_id, '_timeline_year_display', sanitize_text_field( $_POST['voc_timeline_year_display'] ) );
    update_post_meta( $post_id, '_timeline_sort_year', (int) $_POST['voc_timeline_sort_year'] );
    update_post_meta( $post_id, '_timeline_custom_order', (int) $_POST['voc_timeline_custom_order'] );
    update_post_meta( $post_id, '_timeline_sub_headline', sanitize_text_field( $_POST['voc_timeline_sub_headline'] ) );
    update_post_meta( $post_id, '_timeline_full_bleed', isset($_POST['voc_timeline_full_bleed']) ? 'yes' : 'no' );

    $figures = array();
    if ( isset($_POST['voc_fig_name']) && is_array($_POST['voc_fig_name']) ) {
        $names = $_POST['voc_fig_name'];
        $years = $_POST['voc_fig_year'];
        $imgs  = $_POST['voc_fig_img'];
        for ( $i = 0; $i < count($names); $i++ ) {
            $n = sanitize_text_field($names[$i]);
            $y = sanitize_text_field($years[$i]);
            $im = esc_url_raw($imgs[$i]);
            if ( ! empty($n) ) {
                $figures[] = array( 'name' => $n, 'year' => $y, 'img' => $im );
            }
        }
    }
    update_post_meta( $post_id, '_timeline_figures', $figures );
} );

// ==========================================
// 3. ADMIN HUB ROUTING & MOCK GENERATOR
// ==========================================
add_action( 'admin_menu', function() {
    if ( menu_page_url( 'villocity-hub', false ) ) {
        add_submenu_page(
            'villocity-hub',
            'Interactive Timeline',
            'Timeline & History',
            'manage_options',
            'voc-timeline-hub',
            'voc_render_timeline_admin_hub'
        );
    }
}, 26 );

function voc_render_timeline_admin_hub() {
    if ( ! current_user_can( 'manage_options' ) ) { return; }

    $message = '';

    if ( isset($_POST['voc_action_generate_mock_timeline']) && wp_verify_nonce($_POST['voc_timeline_admin_nonce'], 'voc_timeline_admin_action') ) {
        voc_generate_mock_timeline_data();
        $message = '<div class="voc-gemini-notice"><h3>✓ Mock History Timeline Generated</h3><p style="margin:0;">Created historical milestones with leadership figures, images, and era tabs.</p></div>';
    }

    $primary_col = get_option( 'villocity_global_primary', '#860038' );
    $total_items = wp_count_posts( 'voc_timeline_event' )->publish;

    echo '<div class="voc-gemini-dashboard">' . $message;

    if ( function_exists( 'villocity_render_dashboard_header' ) ) {
        villocity_render_dashboard_header( 'Interactive History Timeline Hub', 'Manage historical milestones, leadership figures, era eras, and visual archives.', 'archive', admin_url( 'post-new.php?post_type=voc_timeline_event' ) );
    }

    echo '<div class="voc-gemini-tabs" style="margin-top:24px;">
        <button type="button" class="voc-gemini-tab active" onclick="voc_switch_tab(event, \'voc-tab-timeline-items\')">Milestones (' . $total_items . ')</button>
        <button type="button" class="voc-gemini-tab" onclick="voc_switch_tab(event, \'voc-tab-timeline-shortcodes\')">Shortcodes & Integration</button>
        <button type="button" class="voc-gemini-tab" onclick="voc_switch_tab(event, \'voc-tab-timeline-tools\')">Demo Data Generator</button>
    </div>';

    // TAB 1: LIST
    echo '<div id="voc-tab-timeline-items" class="voc-tab-content" style="display:block;">
        <div class="voc-gemini-card" style="padding:0;">
            <table class="voc-gemini-data-table">
                <thead><tr><th style="width:60px;">Photo</th><th>Year / Label</th><th>Headline</th><th>Era</th><th style="text-align:right;">Action</th></tr></thead>
                <tbody>';
                $items = get_posts( array( 'post_type' => 'voc_timeline_event', 'posts_per_page' => -1, 'meta_key' => '_timeline_sort_year', 'orderby' => 'meta_value_num', 'order' => 'ASC' ) );
                if ( ! empty( $items ) ) {
                    foreach ( $items as $it ) {
                        $year_lbl = get_post_meta( $it->ID, '_timeline_year_display', true );
                        $sub_head = get_post_meta( $it->ID, '_timeline_sub_headline', true );
                        $cat_terms = wp_get_post_terms( $it->ID, 'voc_timeline_era' );
                        $cat_label = ! empty( $cat_terms ) ? $cat_terms[0]->name : 'General';
                        $thumb = get_the_post_thumbnail_url( $it->ID, 'thumbnail' );

                        echo '<tr>
                            <td>' . ($thumb ? '<img src="' . esc_url($thumb) . '" style="width:40px; height:40px; border-radius:8px; object-fit:cover;">' : '<div style="width:40px; height:40px; background:#f1f5f9; border-radius:8px;"></div>') . '</td>
                            <td><strong style="color:' . esc_attr($primary_col) . ';">' . esc_html( $year_lbl ) . '</strong></td>
                            <td><strong>' . esc_html( $it->post_title ) . '</strong><br><span style="font-size:12px; color:#64748b;">' . esc_html($sub_head) . '</span></td>
                            <td>' . esc_html( $cat_label ) . '</td>
                            <td style="text-align:right;"><a href="' . esc_url( get_edit_post_link( $it->ID ) ) . '" class="voc-gemini-btn-secondary">Edit Milestone</a></td>
                        </tr>';
                    }
                } else {
                    echo '<tr><td colspan="5" style="text-align:center; padding:40px; color:#64748b;">No timeline milestones added yet. Click "Demo Data Generator" tab to populate sample history.</td></tr>';
                }
    echo '      </tbody>
            </table>
        </div>
    </div>';

    // TAB 2: SHORTCODES
    echo '<div id="voc-tab-timeline-shortcodes" class="voc-tab-content" style="display:none;">
        <div style="display: flex; gap: 16px; flex-wrap: wrap;">
            <div class="voc-gemini-card" style="flex:1; min-width:300px;">
                <strong style="color:' . esc_attr($primary_col) . '; display:block; margin-bottom:6px;">Interactive Timeline Shortcode</strong>
                <code style="display:inline-block; padding:8px 12px; background:#f1f5f9; border-radius:6px; font-size:14px; font-weight:700;">[villocity_timeline]</code>
                <p style="font-size:13px; color:#64748b; margin-top:8px;">Renders an executive, alternating interactive timeline with centered solid badges, pulse-glowing active milestones, card arrows, auto-sorting, full-bleed images, and scroll line fills.</p>
            </div>
        </div>
    </div>';

    // TAB 3: DEMO GENERATOR
    echo '<div id="voc-tab-timeline-tools" class="voc-tab-content" style="display:none;">
        <div class="voc-gemini-card" style="max-width:600px;">
            <h3>⚡ 1-Click Mock Timeline Generator</h3>
            <p style="color:#64748b; font-size:14px;">Generates sample historical eras (Founding, Prohibition, Modern Era) complete with alternating cards, leadership portraits, and vintage photography.</p>
            <form method="post">
                ' . wp_nonce_field( 'voc_timeline_admin_action', 'voc_timeline_admin_nonce', true, false ) . '
                <input type="hidden" name="voc_action_generate_mock_timeline" value="1">
                <button type="submit" class="voc-gemini-btn-primary">Generate Mock History Timeline</button>
            </form>
        </div>
    </div>';

    echo '</div>';

    echo '<script>
    function voc_switch_tab(event, tabId) {
        if (event) { event.preventDefault(); }
        var contents = document.getElementsByClassName("voc-tab-content");
        for (var i = 0; i < contents.length; i++) { contents[i].style.display = "none"; }
        var tabs = document.getElementsByClassName("voc-gemini-tab");
        for (var j = 0; j < tabs.length; j++) { tabs[j].classList.remove("active"); }
        var targetContent = document.getElementById(tabId);
        if (targetContent) { targetContent.style.display = "block"; }
        if (event && event.currentTarget) { event.currentTarget.classList.add("active"); }
        localStorage.setItem("voc_timeline_active_tab", tabId);
    }

    document.addEventListener("DOMContentLoaded", function() {
        var savedTab = localStorage.getItem("voc_timeline_active_tab");
        if (savedTab && document.getElementById(savedTab)) {
            voc_switch_tab(null, savedTab);
        }
    });
    </script>';
}

// ==========================================
// 4. FRONTEND EXECUTIVE TIMELINE SHORTCODE
// ==========================================
if ( ! function_exists( 'voc_render_timeline' ) ) {
    function voc_render_timeline( $atts = array() ) {
        
        $is_elementor_editor = ( isset( $_GET['action'] ) && $_GET['action'] === 'elementor' ) || 
                               isset( $_GET['elementor-preview'] ) || 
                               ( isset( $_POST['action'] ) && $_POST['action'] === 'elementor_ajax' );
                               
        if ( $is_elementor_editor ) {
            return '<div style="padding: 30px; background: #f8fafc; border: 2px dashed #cbd5e1; border-radius: 12px; color: #860038; font-family: sans-serif; text-align: center;">
                        <strong style="font-size: 18px;">🕒 Interactive History Timeline Placeholder</strong><br>
                        <span style="font-size: 14px; color: #64748b; display: block; margin-top: 8px;">(Interactive scroll-driven animations and line fills are paused inside Elementor editor.)</span>
                    </div>';
        }

        $primary_col = get_option( 'villocity_global_primary', '#860038' );
        $accent_col  = get_option( 'villocity_global_accent', '#fadceb' );

        $items = get_posts( array(
            'post_type'      => 'voc_timeline_event',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'meta_key'       => '_timeline_sort_year',
            'orderby'        => 'meta_value_num',
            'order'          => 'ASC',
            'no_found_rows'  => true,
            'update_post_term_cache' => true,
            'update_post_meta_cache' => true
        ) );

        if ( empty( $items ) ) {
            return '<p style="text-align:center; color:#64748b; padding:20px;">No timeline milestones recorded.</p>';
        }

        $eras = get_terms( array( 'taxonomy' => 'voc_timeline_era', 'hide_empty' => true ) );

        $clock_icon_svg = function_exists('villocity_get_icon') ? villocity_get_icon('clock') : '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>';

        $html = '<style>
            .voc-tl-wrapper { max-width: 1100px; margin: 40px auto; font-family: var(--voc-font-family, inherit) !important; color: #1e293b; position: relative; }
            
            /* Era Scrubber Nav */
            .voc-tl-scrubber { display: flex; gap: 10px; overflow-x: auto; padding-bottom: 16px; margin-bottom: 48px; justify-content: center; scrollbar-width: none; }
            .voc-tl-scrubber::-webkit-scrollbar { display: none; }
            .voc-tl-scrubber-btn { 
                background: #ffffff !important; color: #0f172a !important; border: 1px solid #cbd5e1 !important; padding: 10px 24px !important; border-radius: 30px !important; font-size: 14px !important; font-weight: 700 !important; cursor: pointer !important; white-space: nowrap !important; transition: all 0.2s ease !important; box-shadow: 0 2px 6px rgba(0,0,0,0.02) !important; outline: none !important;
            }
            .voc-tl-scrubber-btn:hover { border-color: ' . esc_attr($primary_col) . ' !important; color: ' . esc_attr($primary_col) . ' !important; }
            .voc-tl-scrubber-btn.active { background: ' . esc_attr($primary_col) . ' !important; color: #ffffff !important; border-color: ' . esc_attr($primary_col) . ' !important; box-shadow: 0 4px 14px rgba(0,0,0,0.12) !important; }

            /* Timeline Central Line Structure & Dynamic Scroll Line */
            .voc-tl-container { position: relative; padding: 40px 0 60px 0; min-height: 400px; }
            .voc-tl-spine-bg { position: absolute; top: 0; bottom: 0; left: 50%; width: 4px; background: #e2e8f0; transform: translateX(-50%); border-radius: 2px; }
            .voc-tl-spine-fill { position: absolute; top: 0; left: 50%; width: 4px; background: ' . esc_attr($primary_col) . '; transform: translateX(-50%); border-radius: 2px; height: 0%; transition: height 0.1s linear; }

            /* Perfectly Symmetrical Start & End Cap Nodes */
            .voc-tl-start-cap { 
                position: absolute; top: 0; left: 50%; width: 24px !important; height: 24px !important; min-width: 24px !important; max-width: 24px !important; border-radius: 50% !important; background: #ffffff !important; border: 4px solid ' . esc_attr($primary_col) . ' !important; transform: translateX(-50%) !important; box-shadow: 0 4px 10px rgba(0,0,0,0.1) !important; z-index: 6; box-sizing: border-box !important; 
            }
            .voc-tl-end-cap { 
                position: absolute; bottom: 0; left: 50%; width: 24px !important; height: 24px !important; min-width: 24px !important; max-width: 24px !important; border-radius: 50% !important; background: #ffffff !important; border: 4px solid ' . esc_attr($primary_col) . ' !important; transform: translateX(-50%) !important; box-shadow: 0 4px 10px rgba(0,0,0,0.1) !important; z-index: 6; box-sizing: border-box !important; 
            }

            /* Milestone Item Row */
            .voc-tl-item { position: relative; width: 50%; padding: 10px 40px; box-sizing: border-box; margin-bottom: 50px; opacity: 1; transform: translate3d(0, 0, 0); transition: opacity 0.4s ease-out, transform 0.4s ease-out; will-change: opacity, transform; }
            
            .voc-tl-item.left { left: 0; text-align: right; }
            .voc-tl-item.right { left: 50%; text-align: left; }

            /* Perfectly Centered Circle Icon Badge */
            .voc-tl-icon-badge { 
                position: absolute; top: 22px; width: 44px; height: 44px; border-radius: 50%; background: #94a3b8; color: #ffffff !important; border: 4px solid #ffffff !important; box-shadow: 0 4px 12px rgba(0,0,0,0.12); z-index: 5; box-sizing: border-box; padding: 0 !important; margin: 0 !important; transition: all 0.3s ease;
            }
            .voc-tl-icon-badge .villocity-icon, .voc-tl-icon-badge svg { 
                position: absolute !important; top: 50% !important; left: 50% !important; transform: translate(-50%, -50%) !important; width: 20px !important; height: 20px !important; min-width: 20px !important; max-width: 20px !important; stroke: #ffffff !important; margin: 0 !important; padding: 0 !important; display: block !important; flex-shrink: 0 !important; 
            }

            /* Scroll Progress Passed State */
            .voc-tl-icon-badge.passed {
                background: ' . esc_attr($primary_col) . ' !important;
            }

            /* Pulsing Glow Active Trigger State */
            .voc-tl-icon-badge.active-pulse {
                background: ' . esc_attr($primary_col) . ' !important;
                animation: vocBadgePulse 1.8s infinite cubic-bezier(0.66, 0, 0, 1) !important;
            }

            @keyframes vocBadgePulse {
                0% {
                    box-shadow: 0 0 0 0 rgba(134, 0, 56, 0.6);
                    transform: scale(1.08);
                }
                50% {
                    box-shadow: 0 0 0 14px rgba(134, 0, 56, 0);
                    transform: scale(1);
                }
                100% {
                    box-shadow: 0 0 0 0 rgba(134, 0, 56, 0);
                    transform: scale(1.08);
                }
            }

            .voc-tl-item.left .voc-tl-icon-badge { right: -22px; }
            .voc-tl-item.right .voc-tl-icon-badge { left: -22px; }

            /* Milestone Content Card with Arrow Notch Pointer */
            .voc-tl-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 20px; padding: 28px; box-shadow: 0 6px 20px rgba(0,0,0,0.04); position: relative; transition: border-color 0.4s ease, box-shadow 0.4s ease, transform 0.2s ease; overflow: visible; }
            .voc-tl-card:hover { transform: translateY(-4px); box-shadow: 0 12px 30px rgba(0,0,0,0.08); }
            
            .voc-tl-card.active-pulse {
                border-color: ' . esc_attr($primary_col) . ' !important;
                box-shadow: 0 10px 30px rgba(134, 0, 56, 0.12) !important;
            }

            /* Card Directional Pointer Arrow Notch */
            .voc-tl-card::before { 
                content: ""; position: absolute; top: 32px; width: 0; height: 0; border-style: solid; z-index: 4; 
            }
            .voc-tl-card::after { 
                content: ""; position: absolute; top: 31px; width: 0; height: 0; border-style: solid; z-index: 3; 
            }

            .voc-tl-item.left .voc-tl-card::before { 
                right: -10px; border-width: 10px 0 10px 10px; border-color: transparent transparent transparent #ffffff; 
            }
            .voc-tl-item.left .voc-tl-card::after { 
                right: -11px; border-width: 11px 0 11px 11px; border-color: transparent transparent transparent #e2e8f0; 
            }

            .voc-tl-item.right .voc-tl-card::before { 
                left: -10px; border-width: 10px 10px 10px 0; border-color: transparent #ffffff transparent transparent; 
            }
            .voc-tl-item.right .voc-tl-card::after { 
                left: -11px; border-width: 11px 11px 11px 0; border-color: transparent #e2e8f0 transparent transparent; 
            }

            /* Full Bleed Image Option */
            .voc-tl-card.is-full-bleed { padding-top: 0 !important; border-top-left-radius: 20px; border-top-right-radius: 20px; }
            .voc-tl-card.is-full-bleed .voc-tl-featured-img { width: calc(100% + 56px) !important; margin-left: -28px !important; margin-right: -28px !important; margin-top: 0 !important; border-radius: 20px 20px 0 0 !important; border: none !important; max-height: 320px !important; }
            .voc-tl-card.is-full-bleed .voc-tl-card-inner { padding-top: 20px; }

            /* Typography */
            .voc-tl-year-label { font-size: 26px; font-weight: 900; color: ' . esc_attr($primary_col) . '; line-height: 1.1; margin-bottom: 4px; display: block; font-family: var(--voc-font-family, inherit) !important; }
            .voc-tl-subhead { font-size: 13px; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 14px; display: block; }

            .voc-tl-featured-img { width: 100%; height: auto; max-height: 280px; object-fit: cover; border-radius: 12px; margin-bottom: 16px; border: 1px solid #f1f5f9; display: block; }
            .voc-tl-body-text { font-size: 15px; color: #475569; line-height: 1.7; margin-bottom: 16px; }

            /* Key Figures Sub-Tree Row */
            .voc-tl-figures-row { display: flex; gap: 16px; justify-content: center; flex-wrap: wrap; margin-top: 20px; padding-top: 16px; border-top: 1px dashed #cbd5e1; }
            .voc-tl-fig-card { text-align: center; max-width: 110px; }
            .voc-tl-fig-avatar { width: 70px; height: 70px; border-radius: 50%; object-fit: cover; border: 3px solid ' . esc_attr($primary_col) . '; margin: 0 auto 6px auto; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
            .voc-tl-fig-name { font-size: 13px; font-weight: 800; color: #0f172a; margin: 0; line-height: 1.2; }
            .voc-tl-fig-year { font-size: 11px; font-weight: 600; color: #64748b; }

            /* Mobile Responsive Fallback */
            @media (max-width: 768px) {
                .voc-tl-spine-bg, .voc-tl-spine-fill { left: 24px; }
                .voc-tl-start-cap, .voc-tl-end-cap { left: 24px; }
                .voc-tl-item { width: 100%; padding-left: 64px; padding-right: 16px; text-align: left !important; }
                .voc-tl-item.right { left: 0; }
                .voc-tl-item.left .voc-tl-icon-badge, .voc-tl-item.right .voc-tl-icon-badge { left: 2px; right: auto; }
                
                .voc-tl-item.left .voc-tl-card::before, .voc-tl-item.right .voc-tl-card::before { left: -10px; right: auto; border-width: 10px 10px 10px 0; border-color: transparent #ffffff transparent transparent; }
                .voc-tl-item.left .voc-tl-card::after, .voc-tl-item.right .voc-tl-card::after { left: -11px; right: auto; border-width: 11px 11px 11px 0; border-color: transparent #e2e8f0 transparent transparent; }
            }
        </style>';

        $html .= '<div class="voc-tl-wrapper">';

        // Era Filter Scrubber
        if ( ! is_wp_error($eras) && ! empty($eras) ) {
            $html .= '<div class="voc-tl-scrubber">';
            $html .= '<button type="button" class="voc-tl-scrubber-btn active" onclick="voc_filter_timeline(\'all\', this)">Full History</button>';
            foreach ( $eras as $era ) {
                $html .= '<button type="button" class="voc-tl-scrubber-btn" onclick="voc_filter_timeline(\'' . esc_attr($era->slug) . '\', this)">' . esc_html($era->name) . '</button>';
            }
            $html .= '</div>';
        }

        // Timeline Container & Dynamic Spine Lines
        $html .= '<div class="voc-tl-container" id="voc-tl-main-container">';
        $html .= '<div class="voc-tl-start-cap"></div>';
        $html .= '<div class="voc-tl-spine-bg"></div>';
        $html .= '<div class="voc-tl-spine-fill" id="voc-tl-spine-fill"></div>';

        $side = 'left';
        foreach ( $items as $it ) {
            $year_lbl   = get_post_meta( $it->ID, '_timeline_year_display', true ) ?: get_the_date( 'Y', $it->ID );
            $sub_head   = get_post_meta( $it->ID, '_timeline_sub_headline', true );
            $full_bleed = get_post_meta( $it->ID, '_timeline_full_bleed', true ) === 'yes';
            $figures    = get_post_meta( $it->ID, '_timeline_figures', true );
            
            $thumb_id   = get_post_thumbnail_id( $it->ID );
            $thumb_html = $thumb_id ? wp_get_attachment_image( $thumb_id, 'large', false, array( 'class' => 'voc-tl-featured-img', 'loading' => 'lazy' ) ) : '';

            $era_terms = wp_get_post_terms( $it->ID, 'voc_timeline_era' );
            $era_slug  = ! empty($era_terms) ? $era_terms[0]->slug : 'general';

            $html .= '<div class="voc-tl-item ' . $side . ' voc-era-' . esc_attr($era_slug) . '">';
            $html .= '<div class="voc-tl-icon-badge">' . $clock_icon_svg . '</div>';

            $html .= '<div class="voc-tl-card' . ($full_bleed ? ' is-full-bleed' : '') . '">';
            
            if ( $full_bleed && $thumb_html ) {
                $html .= $thumb_html;
            }

            $html .= '<div class="voc-tl-card-inner">';
            $html .= '<span class="voc-tl-year-label">' . esc_html($year_lbl) . '</span>';
            if ( ! empty($sub_head) ) {
                $html .= '<span class="voc-tl-subhead">' . esc_html($sub_head) . '</span>';
            }

            if ( ! $full_bleed && $thumb_html ) {
                $html .= $thumb_html;
            }

            $html .= '<h3 style="font-size:20px; font-weight:800; margin:0 0 10px 0; color:#0f172a;">' . esc_html($it->post_title) . '</h3>';
            
            $html .= '<div class="voc-tl-body-text">' . wp_kses_post( wpautop( $it->post_content ) ) . '</div>';

            // Key Figures Sub-Tree Row
            if ( is_array($figures) && ! empty($figures) ) {
                $html .= '<div class="voc-tl-figures-row">';
                foreach ( $figures as $fig ) {
                    $html .= '<div class="voc-tl-fig-card">';
                    if ( ! empty($fig['img']) ) {
                        $html .= '<img src="' . esc_url($fig['img']) . '" class="voc-tl-fig-avatar" alt="' . esc_attr($fig['name']) . '" loading="lazy">';
                    }
                    $html .= '<p class="voc-tl-fig-name">' . esc_html($fig['name']) . '</p>';
                    if ( ! empty($fig['year']) ) {
                        $html .= '<span class="voc-tl-fig-year">' . esc_html($fig['year']) . '</span>';
                    }
                    $html .= '</div>';
                }
                $html .= '</div>';
            }

            $html .= '</div>'; // End Card Inner
            $html .= '</div>'; // End Card
            $html .= '</div>'; // End Item

            $side = ( $side === 'left' ) ? 'right' : 'left';
        }

        // Terminal Bottom Point Cap Node
        $html .= '<div class="voc-tl-end-cap"></div>';

        $html .= '</div>'; // End Container
        $html .= '</div>'; // End Wrapper

        // Scroll Line Fill Progress & Active Milestone Pulse Script
        $html .= '<script>
        document.addEventListener("DOMContentLoaded", function() {
            var container = document.getElementById("voc-tl-main-container");
            var fillLine = document.getElementById("voc-tl-spine-fill");
            var items = document.querySelectorAll(".voc-tl-item");
            var ticking = false;

            function updateSpineFill() {
                if (!container || !fillLine) return;
                var rect = container.getBoundingClientRect();
                var windowHeight = window.innerHeight;
                
                var totalHeight = container.offsetHeight;
                var currentTop = (windowHeight / 2) - rect.top;
                
                var percentage = (currentTop / totalHeight) * 100;
                percentage = Math.max(0, Math.min(100, percentage));
                
                fillLine.style.height = percentage + "%";

                // Pulse Effect Triggering Logic
                var fillBottomPx = rect.top + (totalHeight * (percentage / 100));
                var activeFound = false;

                items.forEach(function(item) {
                    var badge = item.querySelector(".voc-tl-icon-badge");
                    var card  = item.querySelector(".voc-tl-card");
                    if (!badge || !card) return;

                    var badgeRect = badge.getBoundingClientRect();
                    var badgeCenterPx = badgeRect.top + (badgeRect.height / 2);

                    if (fillBottomPx >= badgeCenterPx) {
                        badge.classList.add("passed");
                        
                        // Active pulse triggers on the milestone currently crossed by the scroll line
                        if (!activeFound && fillBottomPx - badgeCenterPx < 250) {
                            badge.classList.add("active-pulse");
                            card.classList.add("active-pulse");
                            activeFound = true;
                        } else {
                            badge.classList.remove("active-pulse");
                            card.classList.remove("active-pulse");
                        }
                    } else {
                        badge.classList.remove("passed", "active-pulse");
                        card.classList.remove("active-pulse");
                    }
                });

                ticking = false;
            }

            window.addEventListener("scroll", function() {
                if (!ticking) {
                    window.requestAnimationFrame(updateSpineFill);
                    ticking = true;
                }
            }, { passive: true });

            window.addEventListener("resize", updateSpineFill, { passive: true });
            updateSpineFill();
        });

        function voc_filter_timeline(eraSlug, btn) {
            var items = document.querySelectorAll(".voc-tl-item");
            items.forEach(function(item) {
                if ( eraSlug === "all" || item.classList.contains("voc-era-" + eraSlug) ) {
                    item.style.display = "block";
                } else {
                    item.style.display = "none";
                }
            });

            var btns = document.querySelectorAll(".voc-tl-scrubber-btn");
            btns.forEach(function(b) { b.classList.remove("active"); });
            if (btn) btn.classList.add("active");
        }
        </script>';

        return $html;
    }
}

add_action( 'init', function() {
    add_shortcode( 'villocity_timeline', 'voc_render_timeline' );
} );

// ==========================================
// 5. MOCK DATA GENERATOR FUNCTION
// ==========================================
function voc_generate_mock_timeline_data() {
    $eras = array( 'Founding Era', 'Prohibition Era', 'Modern Era' );
    $era_ids = array();
    foreach ( $eras as $e_name ) {
        $term = wp_insert_term( $e_name, 'voc_timeline_era' );
        $t_id = ! is_wp_error( $term ) ? $term['term_id'] : get_term_by('name', $e_name, 'voc_timeline_era')->term_id;
        if ( $t_id ) { $era_ids[$e_name] = $t_id; }
    }

    $mock_milestones = array(
        array(
            'title'      => 'Founding & Family Roots',
            'desc'       => 'Established in 1888 as a community staple, pioneered through three generations of family leadership and dedication to quality.',
            'year_lbl'   => '1888 - Current',
            'sort_yr'    => 1888,
            'subhead'    => 'Family-Owned Tradition',
            'full_bleed' => 'no',
            'era'        => 'Founding Era',
            'figures'    => array(
                array('name' => 'F.X. Matt', 'year' => '1888', 'img' => 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?auto=format&fit=crop&w=150&q=80'),
                array('name' => 'Walter J. Matt', 'year' => '1951', 'img' => 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?auto=format&fit=crop&w=150&q=80')
            )
        ),
        array(
            'title'      => 'Saranac 1888 Released',
            'desc'       => 'F.X. Matt begins to focus on the burgeoning craft movement and releases a new lager called Saranac 1888, named for the upcoming 100th anniversary of the brewery.',
            'year_lbl'   => '1920 - 1933',
            'sort_yr'    => 1920,
            'subhead'    => 'Prohibition Survival',
            'full_bleed' => 'yes',
            'era'        => 'Prohibition Era',
            'figures'    => array()
        ),
        array(
            'title'      => 'Digital Era & Expansion',
            'desc'       => 'Modernizing production while expanding distribution nationwide, setting community standards for sustainability and heritage.',
            'year_lbl'   => '2026 & Beyond',
            'sort_yr'    => 2026,
            'subhead'    => 'Next Generation',
            'full_bleed' => 'no',
            'era'        => 'Modern Era',
            'figures'    => array()
        )
    );

    foreach ( $mock_milestones as $m ) {
        $post_id = wp_insert_post( array(
            'post_title'   => $m['title'],
            'post_content' => $m['desc'],
            'post_status'  => 'publish',
            'post_type'    => 'voc_timeline_event'
        ) );

        if ( $post_id && ! is_wp_error($post_id) ) {
            if ( isset($era_ids[$m['era']]) ) {
                wp_set_object_terms( $post_id, (int)$era_ids[$m['era']], 'voc_timeline_era' );
            }
            update_post_meta( $post_id, '_timeline_year_display', $m['year_lbl'] );
            update_post_meta( $post_id, '_timeline_sort_year', $m['sort_yr'] );
            update_post_meta( $post_id, '_timeline_sub_headline', $m['subhead'] );
            update_post_meta( $post_id, '_timeline_full_bleed', $m['full_bleed'] );
            update_post_meta( $post_id, '_timeline_figures', $m['figures'] );
        }
    }
}