<?php
/**
 * Module Name: VilloCity Local Resources Directory
 * Description: Clean card grid for municipal, civic, educational, and community resource links with SVG icon picker.
 * Version:     1.0.0
 * Author:      Brockett Creative Group, Inc.
 * Text Domain: villocity-suite
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

// ==========================================
// 1. REGISTER CPT FOR RESOURCES
// ==========================================
if ( ! function_exists( 'voc_register_resources_cpt' ) ) {
    add_action( 'init', 'voc_register_resources_cpt', 5 );
    function voc_register_resources_cpt() {
        register_post_type( 'voc_resource', array(
            'labels' => array(
                'name'               => 'Community Resources',
                'singular_name'      => 'Resource Link',
                'add_new'            => 'Add Resource',
                'add_new_item'       => 'Add New Resource Link',
                'edit_item'          => 'Edit Resource Link',
                'all_items'          => 'All Resources',
                'menu_name'          => 'Resources'
            ),
            'public'              => true,
            'publicly_queryable'  => false,
            'show_ui'             => true,
            'show_in_menu'        => false,
            'has_archive'         => false,
            'supports'            => array( 'title', 'editor', 'page-attributes' ),
        ) );
    }
}

// Meta Box for Custom Resource Fields
add_action( 'add_meta_boxes', function() {
    add_meta_box( 'voc_resource_fields', 'Resource Link Details', 'voc_resource_fields_callback', 'voc_resource', 'normal', 'high' );
});

function voc_resource_fields_callback( $post ) {
    wp_nonce_field( 'voc_save_resource_meta', 'voc_resource_meta_nonce' );

    $url      = get_post_meta( $post->ID, '_resource_url', true );
    $btn_text = get_post_meta( $post->ID, '_resource_btn_text', true ) ?: 'Official Site';
    $icon     = get_post_meta( $post->ID, '_resource_icon', true ) ?: 'building';

    $available_icons = array(
        'building' => 'Government / Capitol',
        'store'    => 'Store / Commerce',
        'archive'  => 'History / Museum',
        'book'     => 'Library / Education',
        'shield'   => 'Police / Public Safety',
        'globe'    => 'Globe / Web',
        'users'    => 'Community / People',
        'phone'    => 'Phone / Contact',
        'map-pin'  => 'Map / Location',
    );
    ?>
    <p>
        <label><strong>Icon Style:</strong></label><br>
        <select name="_resource_icon" class="voc-gemini-select" style="max-width:300px; margin-top:6px;">
            <?php foreach ( $available_icons as $key => $label ) : ?>
                <option value="<?php echo esc_attr($key); ?>" <?php selected( $icon, $key ); ?>><?php echo esc_html($label); ?></option>
            <?php endforeach; ?>
        </select>
    </p>
    <p>
        <label><strong>Destination URL (External or Internal):</strong></label><br>
        <input type="url" name="_resource_url" value="<?php echo esc_attr( $url ); ?>" class="voc-gemini-input" style="width:100%; margin-top:6px;" placeholder="https://">
    </p>
    <p>
        <label><strong>Button Link Text:</strong></label><br>
        <input type="text" name="_resource_btn_text" value="' . esc_attr( $btn_text ) . '" class="voc-gemini-input" style="width:100%; margin-top:6px;" placeholder="Official Site">
    </p>
    <?php
}

add_action( 'save_post', function( $post_id ) {
    if ( ! isset( $_POST['voc_resource_meta_nonce'] ) || ! wp_verify_nonce( $_POST['voc_resource_meta_nonce'], 'voc_save_resource_meta' ) ) return;
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;

    if ( isset( $_POST['_resource_url'] ) ) update_post_meta( $post_id, '_resource_url', esc_url_raw( $_POST['_resource_url'] ) );
    if ( isset( $_POST['_resource_btn_text'] ) ) update_post_meta( $post_id, '_resource_btn_text', sanitize_text_field( $_POST['_resource_btn_text'] ) );
    if ( isset( $_POST['_resource_icon'] ) ) update_post_meta( $post_id, '_resource_icon', sanitize_text_field( $_POST['_resource_icon'] ) );
});

// ==========================================
// 2. FRONTEND SHORTCODE RENDERER
// ==========================================
if ( ! function_exists( 'villocity_render_resources_grid' ) ) {
    function villocity_render_resources_grid( $atts = array() ) {
        $active_modules = get_option( 'villocity_active_modules', array() );
        if ( is_array( $active_modules ) && ! in_array( 'resources', $active_modules, true ) ) return '';

        $atts = shortcode_atts( array( 'columns' => '4' ), $atts );

        $resources = get_posts( array(
            'post_type'      => 'voc_resource',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'orderby'        => 'menu_order title',
            'order'          => 'ASC',
        ) );

        if ( empty( $resources ) ) return '';

        $primary_col = get_option( 'villocity_global_primary', '#860038' );

        $html = '<style>
            .voc-resources-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 24px; margin: 24px 0; }
            .voc-resource-card { background: #ffffff; border-radius: 18px; padding: 32px 24px; border: 1px solid #e2e8f0; text-align: center; box-shadow: 0 4px 15px rgba(0,0,0,0.03); transition: transform 0.2s ease, box-shadow 0.2s ease; display: flex; flex-direction: column; justify-content: space-between; align-items: center; }
            .voc-resource-card:hover { transform: translateY(-3px); box-shadow: 0 10px 25px rgba(0,0,0,0.07); }
            .voc-resource-icon { color: ' . esc_attr($primary_col) . '; margin-bottom: 16px; }
            .voc-resource-icon svg { width: 38px !important; height: 38px !important; stroke-width: 1.8; }
            .voc-resource-title { font-size: 1.25rem; font-weight: 800; color: #0f172a; margin: 0 0 12px 0; line-height: 1.3; }
            .voc-resource-desc { font-size: 14px; color: #64748b; margin: 0 0 24px 0; line-height: 1.5; flex-grow: 1; }
            .voc-resource-link { color: ' . esc_attr($primary_col) . ' !important; font-weight: 700; font-size: 14px; text-decoration: none !important; display: inline-flex; align-items: center; gap: 6px; transition: gap 0.2s; }
            .voc-resource-link:hover { gap: 10px; }
        </style>';

        $html .= '<div class="voc-resources-grid">';
        foreach ( $resources as $res ) {
            $url      = get_post_meta( $res->ID, '_resource_url', true ) ?: '#';
            $btn_text = get_post_meta( $res->ID, '_resource_btn_text', true ) ?: 'Official Site';
            $icon_key = get_post_meta( $res->ID, '_resource_icon', true ) ?: 'building';
            
            $icon_svg = function_exists('villocity_get_icon') ? villocity_get_icon($icon_key) : '';

            $html .= '<div class="voc-resource-card">
                <div>
                    <div class="voc-resource-icon">' . $icon_svg . '</div>
                    <h3 class="voc-resource-title">' . esc_html( $res->post_title ) . '</h3>
                    <p class="voc-resource-desc">' . esc_html( $res->post_content ) . '</p>
                </div>
                <a href="' . esc_url( $url ) . '" target="_blank" class="voc-resource-link">' . esc_html( $btn_text ) . ' &rarr;</a>
            </div>';
        }
        $html .= '</div>';

        return $html;
    }
}
add_shortcode( 'villocity_resources', 'villocity_render_resources_grid' );

// ==========================================
// 3. ADMIN SETTINGS & MANAGEMENT PAGE
// ==========================================
if ( ! function_exists( 'voc_resources_render_admin_page' ) ) {
    function voc_resources_render_admin_page() {
        if ( ! current_user_can( 'manage_options' ) ) return;

        $add_new_url = admin_url( 'post-new.php?post_type=voc_resource' );
        $all_cpt_url = admin_url( 'edit.php?post_type=voc_resource' );

        echo '<div class="voc-gemini-dashboard">';
        if ( function_exists( 'villocity_render_dashboard_header' ) ) {
            villocity_render_dashboard_header( 'Community Resources Hub', 'Manage direct links to municipal sites, civic institutions, and local services.', 'building', $add_new_url );
        }

        $resources = get_posts( array( 'post_type' => 'voc_resource', 'post_status' => 'any', 'posts_per_page' => -1 ) );

        echo '
        <div class="voc-gemini-card" style="margin-top:24px;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
                <h3 style="margin:0; border:none; padding:0;">Configured Resource Cards</h3>
                <a href="' . esc_url( $all_cpt_url ) . '" class="voc-gemini-btn-secondary">View CPT Table</a>
            </div>

            <table class="voc-gemini-data-table">
                <thead>
                    <tr>
                        <th>Resource Title</th>
                        <th>Target Link</th>
                        <th>Button Text</th>
                        <th style="text-align:right;">Actions</th>
                    </tr>
                </thead>
                <tbody>';
                if ( ! empty( $resources ) ) {
                    foreach ( $resources as $res ) {
                        $url      = get_post_meta( $res->ID, '_resource_url', true );
                        $btn_text = get_post_meta( $res->ID, '_resource_btn_text', true ) ?: 'Official Site';
                        $edit_link = get_edit_post_link( $res->ID );

                        echo '<tr>
                            <td><strong>' . esc_html( $res->post_title ) . '</strong></td>
                            <td><a href="' . esc_url( $url ) . '" target="_blank" style="color:#64748b; font-size:13px;">' . esc_html( $url ) . '</a></td>
                            <td><span class="voc-pill-badge voc-pill-green">' . esc_html( $btn_text ) . '</span></td>
                            <td style="text-align:right;">
                                <a href="' . esc_url( $edit_link ) . '" class="voc-gemini-btn-secondary" style="padding:4px 12px !important;">Edit</a>
                            </td>
                        </tr>';
                    }
                } else {
                    echo '<tr><td colspan="4" style="text-align:center; padding:30px;">No resources created yet. Click "Add New" above.</td></tr>';
                }
        echo '  </tbody>
            </table>
        </div>

        <div class="voc-gemini-card">
            <h3>' . (function_exists('villocity_get_icon') ? villocity_get_icon('tag') : '🏷') . ' Display Shortcode</h3>
            <p style="color:#64748b; margin-top:0;">Output this grid on any page using Elementor or the WordPress editor:</p>
            <code style="padding:10px 16px; background:#f1f5f9; border-radius:10px; font-size:14px; font-weight:700; display:inline-block;">[villocity_resources]</code>
        </div>
        </div>';
    }
}