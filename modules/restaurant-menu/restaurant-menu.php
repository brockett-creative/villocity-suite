<?php
/**
 * Module Name: VilloCity Restaurant Menu
 * Description: Executive digital restaurant menu engine with Category Hero Banners, multi-size portion pricing, food photos, standardized suite-wide pill tabs, and 1-Click Mock Data Generation.
 * Version:     1.3.1
 * Last Updated: September 9, 2026
 * Author:      Brockett Creative Group, Inc.
 * Text Domain: villocity-suite
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

// ==========================================
// 1. CPT & TAXONOMY REGISTRATION WITH META
// ==========================================
add_action( 'init', function() {
    register_post_type( 'voc_menu_item', array(
        'labels' => array(
            'name'          => 'Menu Items',
            'singular_name' => 'Menu Item',
            'add_new_item'  => 'Add Menu Item',
            'edit_item'     => 'Edit Item',
            'menu_name'     => 'Restaurant Menu'
        ),
        'public'             => true,
        'publicly_queryable' => true,
        'show_ui'            => true,
        'show_in_menu'       => false, // Managed in VilloCity Hub
        'supports'           => array( 'title', 'editor', 'thumbnail' ),
        'rewrite'            => array( 'slug' => 'menu-item' ),
    ) );

    register_taxonomy( 'voc_menu_category', 'voc_menu_item', array(
        'labels'        => array( 'name' => 'Menu Categories' ),
        'hierarchical'  => true,
        'show_ui'       => true,
        'rewrite'       => array( 'slug' => 'menu-category' ),
    ) );
} );

// Add Hero Image field to Menu Category taxonomy
add_action( 'voc_menu_category_add_form_fields', function() {
    echo '<div class="form-field">
        <label for="voc_cat_hero_url">Category Hero Banner Image URL</label>
        <input type="url" name="voc_cat_hero_url" id="voc_cat_hero_url" placeholder="https://example.com/banner.jpg">
        <p>Optional hero image displayed at the top when this category tab is active.</p>
    </div>';
} );

add_action( 'voc_menu_category_edit_form_fields', function($term) {
    $hero_url = get_term_meta( $term->term_id, '_voc_cat_hero_url', true );
    echo '<tr class="form-field">
        <th scope="row"><label for="voc_cat_hero_url">Category Hero Banner URL</label></th>
        <td>
            <input type="url" name="voc_cat_hero_url" id="voc_cat_hero_url" value="' . esc_attr($hero_url) . '" class="widefat">
            <p class="description">Optional hero image banner displayed when this category tab is active.</p>
        </td>
    </tr>';
} );

add_action( 'created_voc_menu_category', 'voc_save_menu_cat_meta' );
add_action( 'edited_voc_menu_category', 'voc_save_menu_cat_meta' );
function voc_save_menu_cat_meta( $term_id ) {
    if ( isset( $_POST['voc_cat_hero_url'] ) ) {
        update_term_meta( $term_id, '_voc_cat_hero_url', esc_url_raw( $_POST['voc_cat_hero_url'] ) );
    }
}

// ==========================================
// 2. META BOXES (PRICING VARIANTS & DETAILS)
// ==========================================
add_action( 'add_meta_boxes', function() {
    add_meta_box( 'voc_menu_item_details', 'Menu Item Details & Pricing Options', function( $post ) {
        wp_nonce_field( 'voc_menu_save_meta', 'voc_menu_nonce' );

        $price_single = get_post_meta( $post->ID, '_menu_price', true );
        $variants     = get_post_meta( $post->ID, '_menu_price_variants', true );
        $dietary      = get_post_meta( $post->ID, '_menu_dietary', true );
        $is_special   = get_post_meta( $post->ID, '_menu_is_special', true );
        $linked_biz   = get_post_meta( $post->ID, '_menu_linked_business_id', true );

        $directory_listings = get_posts( array(
            'post_type'      => 'bcg_directory',
            'posts_per_page' => -1,
            'post_status'    => 'publish'
        ) );

        echo '<div style="display:flex; flex-wrap:wrap; gap:20px; padding:10px 0;">';

        // Pricing Left Col
        echo '<div style="flex:1; min-width:280px;">';
        echo '<p style="margin-top:0;"><label><strong>Standard / Single Price ($):</strong></label><br>';
        echo '<input type="text" name="voc_menu_price" value="' . esc_attr($price_single) . '" class="widefat" placeholder="e.g. 14.99"></p>';

        echo '<div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:14px; margin-top:14px;">';
        echo '<strong style="color:#0f172a; display:block; margin-bottom:6px;">Multi-Size / Portion Pricing Options</strong>';
        echo '<p style="font-size:12px; color:#64748b; margin:0 0 10px 0;">Enter portion and price pairs (e.g. Small / Large, Glass / Bottle).</p>';

        echo '<div id="voc-variants-container">';
        if ( is_array($variants) && !empty($variants) ) {
            foreach ( $variants as $v ) {
                echo '<div class="voc-variant-row" style="display:flex; gap:8px; margin-bottom:8px;">';
                echo '<input type="text" name="voc_variant_label[]" value="' . esc_attr($v['label']) . '" placeholder="Size (e.g. Small)" style="flex:2;">';
                echo '<input type="text" name="voc_variant_price[]" value="' . esc_attr($v['price']) . '" placeholder="Price (e.g. 9.99)" style="flex:1;">';
                echo '<button type="button" class="button" onclick="this.parentElement.remove()">✕</button>';
                echo '</div>';
            }
        } else {
            echo '<div class="voc-variant-row" style="display:flex; gap:8px; margin-bottom:8px;">';
            echo '<input type="text" name="voc_variant_label[]" placeholder="Size (e.g. Small)" style="flex:2;">';
            echo '<input type="text" name="voc_variant_price[]" placeholder="Price (e.g. 9.99)" style="flex:1;">';
            echo '<button type="button" class="button" onclick="this.parentElement.remove()">✕</button>';
            echo '</div>';
        }
        echo '</div>';
        echo '<button type="button" class="button button-secondary" onclick="voc_add_variant_row()" style="margin-top:6px;">+ Add Size Option</button>';
        echo '</div>';
        echo '</div>';

        // Details Right Col
        echo '<div style="flex:1; min-width:280px;">';
        echo '<p style="margin-top:0;"><label><strong>Dietary Badges (Comma-separated):</strong></label><br>';
        echo '<input type="text" name="voc_menu_dietary" value="' . esc_attr($dietary) . '" class="widefat" placeholder="e.g. Gluten-Free, Vegan, Spicy"></p>';

        echo '<p style="background:#fefce8; border-left:4px solid #eab308; padding:10px;"><label><input type="checkbox" name="voc_menu_is_special" value="yes" ' . checked($is_special, 'yes', false) . '> <strong>Highlight as Chef Special / Popular Dish</strong></label></p>';

        if ( ! empty( $directory_listings ) ) {
            echo '<p><label><strong>Attach to Directory Business Listing:</strong></label><br>';
            echo '<select name="voc_menu_linked_business_id" class="widefat">';
            echo '<option value="">-- Standalone Restaurant Menu --</option>';
            foreach ( $directory_listings as $biz ) {
                echo '<option value="' . $biz->ID . '" ' . selected($linked_biz, $biz->ID, false) . '>' . esc_html($biz->post_title) . '</option>';
            }
            echo '</select></p>';
        }
        echo '</div>';

        echo '</div>';

        echo '<script>
        function voc_add_variant_row() {
            var row = document.createElement("div");
            row.className = "voc-variant-row";
            row.style.cssText = "display:flex; gap:8px; margin-bottom:8px;";
            row.innerHTML = \'<input type="text" name="voc_variant_label[]" placeholder="Size (e.g. Large)" style="flex:2;">\' +
                            \'<input type="text" name="voc_variant_price[]" placeholder="Price (e.g. 14.99)" style="flex:1;">\' +
                            \'<button type="button" class="button" onclick="this.parentElement.remove()">✕</button>\';
            document.getElementById("voc-variants-container").appendChild(row);
        }
        </script>';
    }, 'voc_menu_item', 'normal', 'high' );
} );

add_action( 'save_post_voc_menu_item', function( $post_id ) {
    if ( ! isset( $_POST['voc_menu_nonce'] ) || ! wp_verify_nonce( $_POST['voc_menu_nonce'], 'voc_menu_save_meta' ) ) { return; }
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) { return; }

    update_post_meta( $post_id, '_menu_price', sanitize_text_field( $_POST['voc_menu_price'] ) );
    update_post_meta( $post_id, '_menu_dietary', sanitize_text_field( $_POST['voc_menu_dietary'] ) );
    update_post_meta( $post_id, '_menu_is_special', isset($_POST['voc_menu_is_special']) ? 'yes' : 'no' );
    update_post_meta( $post_id, '_menu_linked_business_id', sanitize_text_field( $_POST['voc_menu_linked_business_id'] ) );

    $variants = array();
    if ( isset($_POST['voc_variant_label']) && is_array($_POST['voc_variant_label']) ) {
        $labels = $_POST['voc_variant_label'];
        $prices = $_POST['voc_variant_price'];
        for ( $i = 0; $i < count($labels); $i++ ) {
            $l = sanitize_text_field($labels[$i]);
            $p = sanitize_text_field($prices[$i]);
            if ( ! empty($l) && ! empty($p) ) {
                $variants[] = array( 'label' => $l, 'price' => $p );
            }
        }
    }
    update_post_meta( $post_id, '_menu_price_variants', $variants );
} );

// ==========================================
// 3. ADMIN HUB ROUTING & MOCK GENERATOR
// ==========================================
add_action( 'admin_menu', function() {
    if ( menu_page_url( 'villocity-hub', false ) ) {
        add_submenu_page(
            'villocity-hub',
            'Restaurant Menu',
            'Restaurant Menu',
            'manage_options',
            'voc-menu-hub',
            'voc_render_menu_admin_hub'
        );
    }
}, 25 );

function voc_render_menu_admin_hub() {
    if ( ! current_user_can( 'manage_options' ) ) { return; }

    $message = '';

    if ( isset($_POST['voc_action_generate_mock_menu']) && wp_verify_nonce($_POST['voc_menu_admin_nonce'], 'voc_menu_admin_action') ) {
        voc_generate_mock_restaurant_menu();
        $message = '<div class="voc-gemini-notice"><h3>✓ Mock Menu Generated</h3><p style="margin:0;">Created categories with hero banners and sample dishes with multi-size pricing.</p></div>';
    }

    $primary_col = get_option( 'villocity_global_primary', '#860038' );
    $total_items = wp_count_posts( 'voc_menu_item' )->publish;

    echo '<div class="voc-gemini-dashboard">' . $message;

    if ( function_exists( 'villocity_render_dashboard_header' ) ) {
        villocity_render_dashboard_header( 'Digital Restaurant Menu Hub', 'Manage dishes, portion sizes, prices, dietary tags, and category hero banners.', 'store', admin_url( 'post-new.php?post_type=voc_menu_item' ) );
    }

    echo '<div class="voc-gemini-tabs" style="margin-top:24px;">
        <button type="button" class="voc-gemini-tab active" onclick="voc_switch_tab(event, \'voc-tab-menu-items\')">Menu Items (' . $total_items . ')</button>
        <button type="button" class="voc-gemini-tab" onclick="voc_switch_tab(event, \'voc-tab-menu-shortcodes\')">Shortcodes & Integration</button>
        <button type="button" class="voc-gemini-tab" onclick="voc_switch_tab(event, \'voc-tab-menu-tools\')">Demo Data Generator</button>
    </div>';

    // TAB 1: MENU ITEMS LIST
    echo '<div id="voc-tab-menu-items" class="voc-tab-content" style="display:block;">
        <div class="voc-gemini-card" style="padding:0;">
            <table class="voc-gemini-data-table">
                <thead><tr><th style="width:60px;">Photo</th><th>Dish / Item Name</th><th>Category</th><th>Price / Variants</th><th>Attached Business</th><th style="text-align:right;">Action</th></tr></thead>
                <tbody>';
                $items = get_posts( array( 'post_type' => 'voc_menu_item', 'posts_per_page' => -1 ) );
                if ( ! empty( $items ) ) {
                    foreach ( $items as $it ) {
                        $p_single = get_post_meta( $it->ID, '_menu_price', true );
                        $variants = get_post_meta( $it->ID, '_menu_price_variants', true );
                        
                        $price_display = '';
                        if ( is_array($variants) && !empty($variants) ) {
                            $var_strs = array();
                            foreach($variants as $v) { $var_strs[] = esc_html($v['label']) . ': $' . esc_html($v['price']); }
                            $price_display = implode(', ', $var_strs);
                        } else {
                            $price_display = !empty($p_single) ? '$' . esc_html($p_single) : '<em>Free / TBD</em>';
                        }

                        $cat_terms = wp_get_post_terms( $it->ID, 'voc_menu_category' );
                        $cat_label = ! empty( $cat_terms ) ? $cat_terms[0]->name : 'General';
                        $biz_id = get_post_meta( $it->ID, '_menu_linked_business_id', true );
                        $biz_name = $biz_id ? get_the_title( $biz_id ) : '<em>Standalone (All)</em>';
                        $thumb = get_the_post_thumbnail_url( $it->ID, 'thumbnail' );

                        echo '<tr>
                            <td>' . ($thumb ? '<img src="' . esc_url($thumb) . '" style="width:40px; height:40px; border-radius:8px; object-fit:cover;">' : '<div style="width:40px; height:40px; background:#f1f5f9; border-radius:8px;"></div>') . '</td>
                            <td><strong>' . esc_html( $it->post_title ) . '</strong></td>
                            <td>' . esc_html( $cat_label ) . '</td>
                            <td><strong>' . $price_display . '</strong></td>
                            <td>' . $biz_name . '</td>
                            <td style="text-align:right;"><a href="' . esc_url( get_edit_post_link( $it->ID ) ) . '" class="voc-gemini-btn-secondary">Edit Item</a></td>
                        </tr>';
                    }
                } else {
                    echo '<tr><td colspan="6" style="text-align:center; padding:40px; color:#64748b;">No menu items added yet. Click "Demo Data Generator" tab to populate test dishes.</td></tr>';
                }
    echo '      </tbody>
            </table>
        </div>
    </div>';

    // TAB 2: SHORTCODES
    echo '<div id="voc-tab-menu-shortcodes" class="voc-tab-content" style="display:none;">
        <div style="display: flex; gap: 16px; flex-wrap: wrap;">
            <div class="voc-gemini-card" style="flex:1; min-width:300px;">
                <strong style="color:' . esc_attr($primary_col) . '; display:block; margin-bottom:6px;">Standalone Menu Shortcode</strong>
                <code style="display:inline-block; padding:8px 12px; background:#f1f5f9; border-radius:6px; font-size:14px; font-weight:700;">[villocity_restaurant_menu]</code>
                <p style="font-size:13px; color:#64748b; margin-top:8px;">Renders the full categorized restaurant menu with hero banners, size pricing, and photos.</p>
            </div>
            <div class="voc-gemini-card" style="flex:1; min-width:300px;">
                <strong style="color:' . esc_attr($primary_col) . '; display:block; margin-bottom:6px;">Directory Listing Menu Shortcode</strong>
                <code style="display:inline-block; padding:8px 12px; background:#f1f5f9; border-radius:6px; font-size:14px; font-weight:700;">[villocity_restaurant_menu business_id="123"]</code>
                <p style="font-size:13px; color:#64748b; margin-top:8px;">Renders menu items attached to a specific Directory business profile.</p>
            </div>
        </div>
    </div>';

    // TAB 3: DEMO DATA GENERATOR
    echo '<div id="voc-tab-menu-tools" class="voc-tab-content" style="display:none;">
        <div class="voc-gemini-card" style="max-width:600px;">
            <h3>⚡ 1-Click Mock Menu Generator</h3>
            <p style="color:#64748b; font-size:14px;">Generates sample menu categories (Appetizers, Artisanal Pizza, Cocktails) complete with hero banner images, food photos, dietary tags, and multi-size portion pricing (e.g. Small/Large, Glass/Bottle).</p>
            <form method="post">
                ' . wp_nonce_field( 'voc_menu_admin_action', 'voc_menu_admin_nonce', true, false ) . '
                <input type="hidden" name="voc_action_generate_mock_menu" value="1">
                <button type="submit" class="voc-gemini-btn-primary">Generate Mock Menu Items</button>
            </form>
        </div>
    </div>';

    echo '</div>';

    // TAB SWITCHER JAVASCRIPT
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
        localStorage.setItem("voc_menu_active_tab", tabId);
    }

    document.addEventListener("DOMContentLoaded", function() {
        var savedTab = localStorage.getItem("voc_menu_active_tab");
        if (savedTab && document.getElementById(savedTab)) {
            voc_switch_tab(null, savedTab);
        }
    });
    </script>';
}

// ==========================================
// 4. FRONTEND EXECUTIVE MENU SHORTCODE RENDERER
// ==========================================
if ( ! function_exists( 'voc_render_restaurant_menu' ) ) {
    function voc_render_restaurant_menu( $atts = array() ) {
        $args = shortcode_atts( array(
            'business_id' => '',
        ), $atts );

        $primary_col = get_option( 'villocity_global_primary', '#860038' );
        $accent_col  = get_option( 'villocity_global_accent', '#fadceb' );

        $query_args = array(
            'post_type'      => 'voc_menu_item',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'orderby'        => 'title',
            'order'          => 'ASC'
        );

        if ( ! empty( $args['business_id'] ) ) {
            $query_args['meta_key']   = '_menu_linked_business_id';
            $query_args['meta_value'] = sanitize_text_field( $args['business_id'] );
        }

        $items = get_posts( $query_args );

        if ( empty( $items ) ) {
            return '<p style="text-align:center; color:#64748b; padding:20px;">No menu items currently available.</p>';
        }

        $categorized = array();
        foreach ( $items as $it ) {
            $terms = wp_get_post_terms( $it->ID, 'voc_menu_category' );
            $cat_term = ! empty( $terms ) ? $terms[0] : null;
            $cat_name = $cat_term ? $cat_term->name : 'Main Menu';
            
            if ( ! isset($categorized[ $cat_name ]) ) {
                $categorized[ $cat_name ] = array(
                    'term'  => $cat_term,
                    'items' => array()
                );
            }
            $categorized[ $cat_name ]['items'][] = $it;
        }

        $unique_id = 'voc_menu_' . rand(1000, 9999);

        // STANDARDIZED SUITE-WIDE PILL NAV STYLING
        $html = '<style>
            .voc-menu-wrapper { max-width: 1100px; margin: 0 auto; font-family: var(--voc-font-family, inherit) !important; color: #1e293b; }
            
            /* Standardized VilloCity Suite Category Pill Navigation */
            .voc-menu-nav { display: flex; gap: 10px; overflow-x: auto; padding-bottom: 16px; margin-bottom: 32px; scrollbar-width: none; }
            .voc-menu-nav::-webkit-scrollbar { display: none; }
            .voc-menu-nav-item { 
                background: #ffffff !important; 
                color: #0f172a !important; 
                border: 1px solid #cbd5e1 !important; 
                padding: 10px 24px !important; 
                border-radius: 30px !important; 
                font-size: 14px !important; 
                font-weight: 700 !important; 
                cursor: pointer !important; 
                white-space: nowrap !important; 
                transition: all 0.2s ease !important; 
                box-shadow: 0 2px 6px rgba(0,0,0,0.02) !important; 
                outline: none !important;
            }
            .voc-menu-nav-item:hover {
                border-color: ' . esc_attr($primary_col) . ' !important;
                color: ' . esc_attr($primary_col) . ' !important;
            }
            .voc-menu-nav-item.active { 
                background: ' . esc_attr($primary_col) . ' !important; 
                color: #ffffff !important; 
                border-color: ' . esc_attr($primary_col) . ' !important; 
                box-shadow: 0 4px 14px rgba(0,0,0,0.12) !important; 
            }
            
            .voc-menu-cat-section { display: none; }
            .voc-menu-cat-section.active { display: block; animation: vocFadeIn 0.3s ease-in-out; }
            @keyframes vocFadeIn { from { opacity: 0; transform: translateY(6px); } to { opacity: 1; transform: translateY(0); } }

            /* Category Hero Banner */
            .voc-menu-hero-banner { width: 100%; height: 220px; border-radius: 20px; overflow: hidden; position: relative; margin-bottom: 28px; background-size: cover; background-position: center; display: flex; align-items: flex-end; padding: 24px; box-sizing: border-box; }
            .voc-menu-hero-banner::after { content: ""; position: absolute; inset: 0; background: linear-gradient(180deg, rgba(0,0,0,0) 0%, rgba(15,23,42,0.85) 100%); z-index: 1; }
            .voc-menu-hero-title { position: relative; z-index: 2; color: #ffffff; font-size: 28px; font-weight: 800; margin: 0; text-shadow: 0 2px 8px rgba(0,0,0,0.3); font-family: var(--voc-font-family, inherit) !important; }

            .voc-menu-grid { display: grid; grid-template-columns: 1fr; gap: 24px; }
            @media(min-width: 768px) { .voc-menu-grid { grid-template-columns: repeat(2, 1fr); } }

            .voc-menu-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 20px; display: flex; gap: 18px; position: relative; transition: transform 0.2s, box-shadow 0.2s; box-shadow: 0 2px 10px rgba(0,0,0,0.02); }
            .voc-menu-card:hover { transform: translateY(-2px); box-shadow: 0 10px 25px rgba(0,0,0,0.06); }
            .voc-menu-card.is-special { border: 2px solid ' . esc_attr($primary_col) . '; background: linear-gradient(180deg, #ffffff 0%, #fdf8f6 100%); }

            .voc-menu-img-wrap { width: 95px; height: 95px; border-radius: 12px; overflow: hidden; flex-shrink: 0; background: #f1f5f9; border: 1px solid #e2e8f0; }
            .voc-menu-img-wrap img { width: 100%; height: 100%; object-fit: cover; }

            .voc-menu-content { flex: 1; display: flex; flex-direction: column; }
            .voc-menu-header-row { display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; margin-bottom: 6px; }
            .voc-menu-dish-title { font-size: 18px; font-weight: 800; color: #0f172a; margin: 0; line-height: 1.2; font-family: var(--voc-font-family, inherit) !important; }
            .voc-menu-dish-desc { font-size: 14px; color: #64748b; line-height: 1.5; margin: 0 0 12px 0; }

            .voc-menu-prices-wrap { text-align: right; flex-shrink: 0; }
            .voc-menu-single-price { font-size: 18px; font-weight: 800; color: ' . esc_attr($primary_col) . '; }
            
            .voc-menu-variant-list { display: flex; flex-direction: column; gap: 3px; align-items: flex-end; }
            .voc-menu-variant-pill { font-size: 12px; color: #475569; background: #f1f5f9; padding: 2px 8px; border-radius: 6px; white-space: nowrap; }
            .voc-menu-variant-pill strong { color: ' . esc_attr($primary_col) . '; font-weight: 800; }

            .voc-menu-badges { display: flex; gap: 6px; flex-wrap: wrap; margin-top: auto; }
            .voc-menu-badge-pill { background: ' . esc_attr($accent_col) . '; color: ' . esc_attr($primary_col) . '; font-size: 11px; font-weight: 700; padding: 3px 10px; border-radius: 12px; }
            .voc-menu-badge-special { background: ' . esc_attr($primary_col) . '; color: #ffffff; font-size: 11px; font-weight: 700; padding: 3px 10px; border-radius: 12px; }
        </style>';

        $html .= '<div class="voc-menu-wrapper" id="' . esc_attr($unique_id) . '">';

        // Category Filter Tabs
        $html .= '<div class="voc-menu-nav">';
        $first_cat = true;
        foreach ( array_keys($categorized) as $cat_title ) {
            $cat_slug = 'cat_' . sanitize_title($cat_title);
            $html .= '<button type="button" class="voc-menu-nav-item' . ($first_cat ? ' active' : '') . '" onclick="voc_switch_menu_cat(\'' . esc_attr($unique_id) . '\', \'' . esc_attr($cat_slug) . '\', this)">' . esc_html($cat_title) . '</button>';
            $first_cat = false;
        }
        $html .= '</div>';

        // Categorized Dish Sections with Hero Banner Support
        $first_cat = true;
        foreach ( $categorized as $cat_title => $data ) {
            $cat_slug = 'cat_' . sanitize_title($cat_title);
            $cat_term = $data['term'];
            $dish_list = $data['items'];

            $hero_url = $cat_term ? get_term_meta( $cat_term->term_id, '_voc_cat_hero_url', true ) : '';

            $html .= '<div class="voc-menu-cat-section ' . esc_attr($cat_slug) . ($first_cat ? ' active' : '') . '">';

            // Category Hero Banner Rendering
            if ( ! empty($hero_url) ) {
                $html .= '<div class="voc-menu-hero-banner" style="background-image: url(\'' . esc_url($hero_url) . '\');">';
                $html .= '<h3 class="voc-menu-hero-title">' . esc_html($cat_title) . '</h3>';
                $html .= '</div>';
            }

            $html .= '<div class="voc-menu-grid">';

            foreach ( $dish_list as $dish ) {
                $price_single = get_post_meta( $dish->ID, '_menu_price', true );
                $variants     = get_post_meta( $dish->ID, '_menu_price_variants', true );
                $dietary      = get_post_meta( $dish->ID, '_menu_dietary', true );
                $is_special   = get_post_meta( $dish->ID, '_menu_is_special', true ) === 'yes';
                $thumb        = get_the_post_thumbnail_url( $dish->ID, 'medium' );
                $badges       = ! empty( $dietary ) ? explode( ',', $dietary ) : array();

                $html .= '<div class="voc-menu-card' . ($is_special ? ' is-special' : '') . '">';

                if ( $thumb ) {
                    $html .= '<div class="voc-menu-img-wrap"><img src="' . esc_url($thumb) . '" alt="' . esc_attr($dish->post_title) . '"></div>';
                }

                $html .= '<div class="voc-menu-content">';
                $html .= '<div class="voc-menu-header-row">';
                $html .= '<h4 class="voc-menu-dish-title">' . esc_html( $dish->post_title ) . '</h4>';

                // Price & Variant Display
                $html .= '<div class="voc-menu-prices-wrap">';
                if ( is_array($variants) && ! empty($variants) ) {
                    $html .= '<div class="voc-menu-variant-list">';
                    foreach ( $variants as $v ) {
                        $html .= '<span class="voc-menu-variant-pill">' . esc_html($v['label']) . ': <strong>$' . esc_html($v['price']) . '</strong></span>';
                    }
                    $html .= '</div>';
                } elseif ( ! empty($price_single) ) {
                    $html .= '<span class="voc-menu-single-price">$' . esc_html($price_single) . '</span>';
                }
                $html .= '</div>';

                $html .= '</div>';

                if ( ! empty($dish->post_content) ) {
                    $html .= '<p class="voc-menu-dish-desc">' . esc_html( $dish->post_content ) . '</p>';
                }

                if ( $is_special || ! empty( $badges ) ) {
                    $html .= '<div class="voc-menu-badges">';
                    if ( $is_special ) {
                        $html .= '<span class="voc-menu-badge-special">★ Chef Special</span>';
                    }
                    foreach ( $badges as $b ) {
                        $html .= '<span class="voc-menu-badge-pill">' . esc_html( trim( $b ) ) . '</span>';
                    }
                    $html .= '</div>';
                }

                $html .= '</div>';
                $html .= '</div>';
            }

            $html .= '</div>'; // End Grid
            $html .= '</div>'; // End Section
            $first_cat = false;
        }

        $html .= '</div>';

        $html .= '<script>
        function voc_switch_menu_cat(wrapperId, catClass, btn) {
            var wrap = document.getElementById(wrapperId);
            if(!wrap) return;
            var sections = wrap.querySelectorAll(".voc-menu-cat-section");
            sections.forEach(function(s) { s.classList.remove("active"); });
            var target = wrap.querySelector("." + catClass);
            if(target) target.classList.add("active");
            var btns = wrap.querySelectorAll(".voc-menu-nav-item");
            btns.forEach(function(b) { b.classList.remove("active"); });
            if(btn) btn.classList.add("active");
        }
        </script>';

        return $html;
    }
}

add_action( 'init', function() {
    add_shortcode( 'villocity_restaurant_menu', 'voc_render_restaurant_menu' );
} );

// ==========================================
// 5. MOCK DATA GENERATOR FUNCTION
// ==========================================
function voc_generate_mock_restaurant_menu() {
    $categories = array(
        'Appetizers' => 'https://images.unsplash.com/photo-1541544741938-0af808871cc0?auto=format&fit=crop&w=1200&q=80',
        'Artisanal Pizza' => 'https://images.unsplash.com/photo-1513104890138-7c749659a591?auto=format&fit=crop&w=1200&q=80',
        'Craft Cocktails' => 'https://images.unsplash.com/photo-1514362545857-3bc16c4c7d1b?auto=format&fit=crop&w=1200&q=80'
    );

    $cat_ids = array();
    foreach ( $categories as $name => $hero_url ) {
        $term = wp_insert_term( $name, 'voc_menu_category' );
        $t_id = ! is_wp_error( $term ) ? $term['term_id'] : get_term_by('name', $name, 'voc_menu_category')->term_id;
        if ( $t_id ) {
            update_term_meta( $t_id, '_voc_cat_hero_url', $hero_url );
            $cat_ids[$name] = $t_id;
        }
    }

    $mock_dishes = array(
        array(
            'title'     => 'Truffle & Parmesan Arancini',
            'desc'      => 'Crispy risotto spheres stuffed with wild mushrooms, fontina cheese, and black truffle aioli.',
            'cat'       => 'Appetizers',
            'price'     => '14.95',
            'variants'  => array(),
            'dietary'   => 'Vegetarian, Chef Choice',
            'special'   => 'yes'
        ),
        array(
            'title'     => 'Neapolitan Margherita',
            'desc'      => 'San Marzano tomato sauce, fresh buffalo mozzarella, basil leaves, and cold-pressed olive oil.',
            'cat'       => 'Artisanal Pizza',
            'price'     => '',
            'variants'  => array(
                array('label' => '12" Personal', 'price' => '16.50'),
                array('label' => '16" Shareable', 'price' => '22.00')
            ),
            'dietary'   => 'Vegetarian',
            'special'   => 'no'
        ),
        array(
            'title'     => 'Smoked Old Fashioned',
            'desc'      => 'Small batch bourbon, Angostura bitters, Luxardo cherry, and wood-smoked orange peel.',
            'cat'       => 'Craft Cocktails',
            'price'     => '',
            'variants'  => array(
                array('label' => 'Glass', 'price' => '15.00'),
                array('label' => 'Carafe (Serves 4)', 'price' => '48.00')
            ),
            'dietary'   => 'House Specialty',
            'special'   => 'yes'
        )
    );

    foreach ( $mock_dishes as $d ) {
        $post_id = wp_insert_post( array(
            'post_title'   => $d['title'],
            'post_content' => $d['desc'],
            'post_status'  => 'publish',
            'post_type'    => 'voc_menu_item'
        ) );

        if ( $post_id && ! is_wp_error($post_id) ) {
            if ( isset($cat_ids[$d['cat']]) ) {
                wp_set_object_terms( $post_id, (int)$cat_ids[$d['cat']], 'voc_menu_category' );
            }
            update_post_meta( $post_id, '_menu_price', $d['price'] );
            update_post_meta( $post_id, '_menu_price_variants', $d['variants'] );
            update_post_meta( $post_id, '_menu_dietary', $d['dietary'] );
            update_post_meta( $post_id, '_menu_is_special', $d['special'] );
        }
    }
}