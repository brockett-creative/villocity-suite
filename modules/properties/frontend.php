<?php
/**
 * Module Name: VilloCity Land & Buildings (Frontend)
 * Module URI:  https://villocity.org
 * Description: Public available property grid, specs pills, site plan links, and real estate layout.
 * Version:     2.1.0
 * Last Updated: September 5, 2026
 * Author:      Brockett Creative Group, Inc.
 * Author URI:  https://brockettcreative.com
 * Copyright:   © 2026 Brockett Creative Group, Inc. All Rights Reserved.
 * Text Domain: villocity-suite
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

// ==========================================
// 1. FRONTEND SHORTCODE: [villocity_available_properties]
// ==========================================
if ( ! function_exists( 'voc_render_properties_grid' ) ) {
    function voc_render_properties_grid( $atts ) {
        $primary_col = get_option('villocity_global_primary', '#860038');
        $accent_col  = get_option('villocity_global_accent', '#fadceb');
        $text_col    = get_option('villocity_global_text', '#1c1917');

        $query = new WP_Query( array(
            'post_type'        => 'voc_property',
            'post_status'      => 'publish',
            'posts_per_page'   => -1,
            'orderby'          => 'title',
            'order'            => 'ASC',
            'suppress_filters' => true
        ) );

        $rendered_ids = array();

        $html = '<style>
            .voc-prop-wrap { font-family: var(--voc-font-family, inherit) !important; color: ' . esc_attr($text_col) . '; max-width: 1200px; margin: 0 auto; box-sizing: border-box; }
            .voc-prop-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 24px; }
            .voc-prop-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 16px; overflow: hidden; display: flex; flex-direction: column; transition: transform 0.2s, box-shadow 0.2s; text-decoration: none; color: inherit; box-shadow: 0 4px 15px rgba(0,0,0,0.03); position: relative; }
            .voc-prop-card:hover { transform: translateY(-4px); box-shadow: 0 10px 25px rgba(0,0,0,0.06); }
            .voc-prop-img-wrap { width: 100%; height: 220px; background: #e2e8f0; position: relative; overflow: hidden; flex-shrink: 0; }
            .voc-prop-img { width: 100% !important; height: 100% !important; object-fit: cover !important; position: absolute; top:0; left:0; margin:0 !important; padding:0 !important; }
            .voc-prop-badge { position: absolute; top: 12px; left: 12px; background: ' . esc_attr($primary_col) . '; color: #fff; padding: 6px 14px; border-radius: 20px; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing:0.05em; display: inline-flex; align-items: center; gap: 4px; z-index: 2; }
            .voc-prop-body { padding: 20px; display: flex; flex-direction: column; flex-grow: 1; }
            .voc-prop-title { font-size: 20px !important; font-weight: 800 !important; color: ' . esc_attr($primary_col) . ' !important; margin: 0 0 8px 0 !important; line-height: 1.3 !important; font-family: var(--voc-font-family, inherit) !important; }
            .voc-prop-specs { display: flex; gap: 8px; font-size: 13px; font-weight: 600; color: #475569; margin-bottom: 12px; flex-wrap: wrap; }
            .voc-prop-spec-pill { background: ' . esc_attr($accent_col) . '; color: ' . esc_attr($primary_col) . '; padding: 4px 10px; border-radius: 8px; display: inline-flex; align-items: center; gap: 4px; }
            .voc-prop-desc { font-size: 14px; color: #475569; line-height: 1.5; margin-bottom: 16px; flex-grow: 1; }
            .voc-prop-btn { background: ' . esc_attr($primary_col) . ' !important; color: #ffffff !important; padding: 12px 24px !important; border-radius: 24px !important; font-size: 13px !important; font-weight: 700 !important; text-align: center; text-decoration: none !important; margin-top: auto; display: inline-flex; align-items: center; justify-content: center; gap: 6px; transition: all 0.2s ease; }
            .voc-prop-btn:hover { opacity: 0.9; box-shadow: 0 4px 12px rgba(0,0,0,0.15); }
        </style>';

        $html .= '<div class="voc-prop-wrap"><div class="voc-prop-grid">';

        if ( $query->have_posts() ) {
            while ( $query->have_posts() ) {
                $query->the_post();
                $id = get_the_ID();

                // DEDUPLICATION GUARD
                if ( in_array( $id, $rendered_ids, true ) ) {
                    continue;
                }
                $rendered_ids[] = $id;

                $type      = get_post_meta($id, '_prop_listing_type', true);
                $acres     = get_post_meta($id, '_prop_acreage', true);
                $sqft      = get_post_meta($id, '_prop_sqft', true);
                $municip   = get_post_meta($id, '_prop_municipality', true);
                $img       = get_the_post_thumbnail_url($id, 'medium_large');
                $permalink = get_permalink($id);

                $html .= '<div class="voc-prop-card">
                    <div class="voc-prop-img-wrap">
                        ' . ($img ? '<img src="' . esc_url($img) . '" class="voc-prop-img" alt="' . esc_attr(get_the_title()) . '">' : '<div style="padding:40px; text-align:center; color:#64748b;">' . villocity_get_icon('building') . ' No Image</div>') . '
                        <span class="voc-prop-badge">' . esc_html(!empty($type) ? $type : 'Available') . '</span>
                    </div>
                    <div class="voc-prop-body">
                        <h3 class="voc-prop-title">' . esc_html(get_the_title()) . '</h3>
                        <div class="voc-prop-specs">
                            ' . (!empty($municip) ? '<span class="voc-prop-spec-pill">' . villocity_get_icon('map-pin') . ' ' . esc_html($municip) . '</span>' : '') . '
                            ' . (!empty($acres) ? '<span class="voc-prop-spec-pill">' . villocity_get_icon('tag') . ' ' . esc_html($acres) . '</span>' : '') . '
                            ' . (!empty($sqft) ? '<span class="voc-prop-spec-pill">' . villocity_get_icon('building') . ' ' . esc_html($sqft) . '</span>' : '') . '
                        </div>
                        <p class="voc-prop-desc">' . esc_html(wp_trim_words(get_the_excerpt(), 18, '...')) . '</p>
                        <a href="' . esc_url($permalink) . '" class="voc-prop-btn">View Site Specifications &rarr;</a>
                    </div>
                </div>';
            }
            wp_reset_postdata();
        } else {
            $html .= '<p style="color: #64748b;">No available properties posted at this time.</p>';
        }

        $html .= '</div></div>';
        return $html;
    }
}

add_action('init', function() {
    add_shortcode( 'villocity_available_properties', 'voc_render_properties_grid' );
});