<?php
/**
 * Module Name: VilloCity Destinations & Tourism (Frontend)
 * Module URI:  https://villocity.org
 * Description: Public map/grid views, county filters, TouristAttraction JSON-LD Schema, top split-hero single view layout with expanded Leaflet mini-map.
 * Version:     1.8.0
 * Last Updated: September 5, 2026
 * Author:      Brockett Creative Group, Inc.
 * Author URI:  https://brockettcreative.com
 * Copyright:   © 2026 Brockett Creative Group, Inc. All Rights Reserved.
 * Text Domain: villocity-suite
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

// ==========================================
// 1. LOAD LEAFLET MAP ASSETS
// ==========================================
if ( ! function_exists( 'voc_destinations_enqueue_map_assets' ) ) {
    add_action( 'wp_enqueue_scripts', 'voc_destinations_enqueue_map_assets' );
    function voc_destinations_enqueue_map_assets() {
        wp_enqueue_style( 'leaflet-css', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css', array(), '1.9.4' );
        wp_enqueue_script( 'leaflet-js', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', array(), '1.9.4', true );
    }
}

// ==========================================
// 2. SINGLE DESTINATION TEMPLATE + EXPANDED MAP LAYOUT
// ==========================================
if ( ! function_exists( 'voc_destination_single_template' ) ) {
    add_filter( 'the_content', 'voc_destination_single_template' );
    function voc_destination_single_template( $content ) {
        if ( is_singular( 'voc_destination' ) && in_the_loop() && is_main_query() ) {
            $id        = get_the_ID();
            $address   = get_post_meta( $id, '_dest_address', true );
            $lat       = get_post_meta( $id, '_dest_lat', true );
            $lng       = get_post_meta( $id, '_dest_lng', true );
            $phone     = get_post_meta( $id, '_dest_phone', true );
            $website   = get_post_meta( $id, '_dest_website', true );
            $hours     = get_post_meta( $id, '_dest_hours', true );
            $admission = get_post_meta( $id, '_dest_admission', true );
            $img       = get_the_post_thumbnail_url( $id, 'large' );

            $counties  = wp_get_post_terms( $id, 'voc_destination_county', array( 'fields' => 'names' ) );
            $themes    = wp_get_post_terms( $id, 'voc_destination_theme', array( 'fields' => 'names' ) );

            $county_str = ! is_wp_error( $counties ) && ! empty( $counties ) ? implode( ', ', $counties ) : '';
            $theme_str  = ! is_wp_error( $themes ) && ! empty( $themes ) ? implode( ', ', $themes ) : '';

            $primary_col = get_option('villocity_global_primary', '#860038');
            $accent_col  = get_option('villocity_global_accent', '#fadceb');
            $text_col    = get_option('villocity_global_text', '#1c1917');

            $referer_url  = wp_get_referer();
            $back_url     = ($referer_url && strpos($referer_url, home_url()) !== false) 
                ? $referer_url 
                : home_url( '/destinations/' );

            $map_url = 'https://www.google.com/maps/search/?api=1&query=' . urlencode($address);

            // SEO / AEO / GEO TouristAttraction Schema.org JSON-LD
            $schema_data = array(
                '@context'    => 'https://schema.org',
                '@type'       => 'TouristAttraction',
                'name'        => get_the_title(),
                'description' => wp_strip_all_tags( $content ),
                'url'         => get_permalink(),
                'telephone'   => $phone,
                'address'     => array(
                    '@type'         => 'PostalAddress',
                    'streetAddress' => $address
                )
            );

            if ( ! empty($lat) && ! empty($lng) ) {
                $schema_data['geo'] = array(
                    '@type'     => 'GeoCoordinates',
                    'latitude'  => (float) $lat,
                    'longitude' => (float) $lng
                );
            }

            if ( $img ) $schema_data['image'] = $img;
            if ( $website ) $schema_data['sameAs'] = $website;

            // Cross-promotion query
            $related_args = array(
                'post_type'      => 'voc_destination',
                'post_status'    => 'publish',
                'posts_per_page' => 3,
                'post__not_in'   => array($id),
                'orderby'        => 'rand'
            );

            if ( ! empty($counties) && ! is_wp_error($counties) ) {
                $related_args['tax_query'] = array(
                    array(
                        'taxonomy' => 'voc_destination_county',
                        'field'    => 'name',
                        'terms'    => $counties,
                    )
                );
            }

            $related_query = new WP_Query( $related_args );

            $custom_html = '
            <script type="application/ld+json">
            ' . wp_json_encode( $schema_data ) . '
            </script>

            <style>
                h1.entry-title, h1.page-title, h1.post-title { display: none !important; }
                .voc-dest-single { font-family: var(--voc-font-family, inherit) !important; color: ' . esc_attr($text_col) . '; max-width: 1200px; margin: 30px auto; box-sizing: border-box; }
                .voc-dest-single-badge { background: ' . esc_attr($accent_col) . '; color: ' . esc_attr($primary_col) . '; font-size: 13px; font-weight: 700; padding: 6px 14px; border-radius: 20px; display: inline-flex; align-items: center; gap: 6px; margin-bottom: 12px; }
                .voc-dest-single-title { font-size: 38px !important; font-weight: 800 !important; color: ' . esc_attr($primary_col) . ' !important; margin: 0 0 24px 0 !important; line-height: 1.15 !important; font-family: var(--voc-font-family, inherit) !important; }
                
                /* Top Split Hero Section */
                .voc-dest-top-hero { display: flex; gap: 32px; flex-wrap: wrap; margin-bottom: 36px; align-items: flex-start; }
                .voc-dest-hero-main-column { flex: 1 1 500px; min-width: 320px; }
                .voc-dest-single-hero { width: 100%; height: 380px; object-fit: cover; border-radius: 16px; box-shadow: 0 4px 18px rgba(0,0,0,0.06); border: 1px solid #e2e8f0; margin-bottom: 28px; }

                /* Attraction Details Right Panel */
                .voc-dest-hero-card { flex: 1 1 380px; min-width: 300px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 24px; box-shadow: 0 4px 16px rgba(0,0,0,0.03); display: flex; flex-direction: column; }
                .voc-dest-info-row { display: flex; align-items: flex-start; gap: 12px; margin-bottom: 14px; font-size: 14px; color: #334155; line-height: 1.45; }
                .voc-dest-info-icon { color: ' . esc_attr($primary_col) . '; flex-shrink: 0; margin-top: 2px; }
                
                /* Taller Interactive Map */
                .voc-dest-single-map { width: 100%; height: 240px; border-radius: 12px; overflow: hidden; border: 1px solid #cbd5e1; margin-top: 18px; }

                .voc-dest-btn-grid { display: flex; gap: 10px; margin-top: 16px; flex-wrap: wrap; }
                .voc-dest-btn { background: ' . esc_attr($primary_col) . ' !important; color: #fff !important; flex: 1; min-width: 150px; text-align: center; justify-content: center; padding: 10px 16px !important; border-radius: 10px !important; font-size: 13px !important; font-weight: 700 !important; text-decoration: none !important; display: inline-flex; align-items: center; gap: 6px; transition: transform 0.2s, opacity 0.2s; box-sizing: border-box; }
                .voc-dest-btn:hover { opacity: 0.95; transform: translateY(-1px); }

                .voc-cross-promo { margin-top: 50px; padding-top: 32px; border-top: 2px solid #e2e8f0; }
                .voc-cross-title { font-size: 24px; font-weight: 800; color: ' . esc_attr($primary_col) . '; margin-bottom: 20px; font-family: var(--voc-font-family, inherit) !important; }
                .voc-cross-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px; }
                .voc-cross-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; text-decoration: none; color: inherit; transition: transform 0.2s, box-shadow 0.2s; }
                .voc-cross-card:hover { transform: translateY(-3px); box-shadow: 0 8px 20px rgba(0,0,0,0.06); }
                .voc-cross-img { width: 100%; height: 160px; object-fit: cover; }
                .voc-cross-body { padding: 16px; }
            </style>

            <article class="voc-dest-single" aria-label="' . esc_attr(get_the_title()) . ' Profile">
                <nav style="margin-bottom:20px;" aria-label="Breadcrumb">
                    <a href="' . esc_url($back_url) . '" onclick="if(document.referrer && document.referrer.includes(window.location.hostname)) { window.history.back(); return false; }" style="text-decoration:none; font-weight:600; color:#64748b; font-size:14px;">&larr; Back to Destinations</a>
                </nav>

                <span class="voc-dest-single-badge">' . (function_exists('villocity_get_icon') ? villocity_get_icon('map-pin') : '') . ' ' . esc_html($county_str ?: 'Regional Site') . ($theme_str ? ' &bull; ' . esc_html($theme_str) : '') . '</span>
                <h1 class="voc-dest-single-title">' . esc_html(get_the_title()) . '</h1>

                <!-- TOP SPLIT SECTION -->
                <div class="voc-dest-top-hero">
                    <!-- LEFT COLUMN: IMAGE AND DESCRIPTION BELOW -->
                    <div class="voc-dest-hero-main-column">
                        ' . ($img ? '<img src="' . esc_url($img) . '" class="voc-dest-single-hero" alt="Featured photo of ' . esc_attr(get_the_title()) . '">' : '') . '
                        <div style="font-size:16px; line-height:1.8; color:#334155;">' . $content . '</div>
                    </div>

                    <!-- RIGHT COLUMN: ATTRACTION DETAILS & TALLER MAP CARD -->
                    <div class="voc-dest-hero-card">
                        <div>
                            <h3 style="font-size:18px; font-weight:800; margin:0 0 16px 0; color:' . esc_attr($primary_col) . '; border-bottom:1px solid #f1f5f9; padding-bottom:10px;">Attraction Details</h3>

                            ' . (!empty($address) ? '<div class="voc-dest-info-row"><span class="voc-dest-info-icon">' . (function_exists('villocity_get_icon') ? villocity_get_icon('map-pin') : '') . '</span> <div><strong>Location:</strong> ' . esc_html($address) . '</div></div>' : '') . '
                            ' . (!empty($hours) ? '<div class="voc-dest-info-row"><span class="voc-dest-info-icon">' . (function_exists('villocity_get_icon') ? villocity_get_icon('clock') : '') . '</span> <div><strong>Hours:</strong> ' . esc_html($hours) . '</div></div>' : '') . '
                            ' . (!empty($admission) ? '<div class="voc-dest-info-row"><span class="voc-dest-info-icon">' . (function_exists('villocity_get_icon') ? villocity_get_icon('tag') : '') . '</span> <div><strong>Admission:</strong> ' . esc_html($admission) . '</div></div>' : '') . '
                            ' . (!empty($phone) ? '<div class="voc-dest-info-row"><span class="voc-dest-info-icon">' . (function_exists('villocity_get_icon') ? villocity_get_icon('phone') : '') . '</span> <div><strong>Phone:</strong> <a href="tel:' . preg_replace('/[^0-9+]/', '', $phone) . '" style="color:inherit; text-decoration:none;">' . esc_html($phone) . '</a></div></div>' : '') . '
                        </div>

                        <div style="margin-top:auto;">
                            <div class="voc-dest-btn-grid">
                                ' . (!empty($address) ? '<a href="' . esc_url($map_url) . '" target="_blank" rel="noopener noreferrer" class="voc-dest-btn">' . (function_exists('villocity_get_icon') ? villocity_get_icon('map-pin') : '') . ' Get Directions</a>' : '') . '
                                ' . (!empty($website) ? '<a href="' . esc_url($website) . '" target="_blank" rel="nofollow ugc noopener noreferrer" class="voc-dest-btn" style="background:#f1f5f9 !important; color:#0f172a !important; border:1px solid #cbd5e1 !important;">' . (function_exists('villocity_get_icon') ? villocity_get_icon('globe') : '') . ' Website</a>' : '') . '
                            </div>
                            ' . (!empty($lat) && !empty($lng) ? '<div id="voc-single-dest-map" class="voc-dest-single-map"></div>' : '') . '
                        </div>
                    </div>
                </div>';

            // SINGLE PIN MAP SCRIPT
            if ( ! empty($lat) && ! empty($lng) ) {
                $custom_html .= '<script>
                document.addEventListener("DOMContentLoaded", function() {
                    if (typeof L !== "undefined" && document.getElementById("voc-single-dest-map")) {
                        const singleMap = L.map("voc-single-dest-map", { scrollWheelZoom: false }).setView([' . (float)$lat . ', ' . (float)$lng . '], 13);
                        L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
                            maxZoom: 18,
                            attribution: "&copy; OpenStreetMap contributors"
                        }).addTo(singleMap);
                        L.marker([' . (float)$lat . ', ' . (float)$lng . ']).addTo(singleMap)
                            .bindPopup("<strong>' . esc_js(get_the_title()) . '</strong><br>' . esc_js($address) . '").openPopup();
                    }
                });
                </script>';
            }

            if ( $related_query->have_posts() ) {
                $custom_html .= '<div class="voc-cross-promo" role="region" aria-label="Nearby and Similar Attractions">
                    <h2 class="voc-cross-title">Explore Nearby & Similar Destinations</h2>
                    <div class="voc-cross-grid">';
                while ( $related_query->have_posts() ) {
                    $related_query->the_post();
                    $r_id = get_the_ID();
                    $r_img = get_the_post_thumbnail_url($r_id, 'medium');
                    $r_address = get_post_meta($r_id, '_dest_address', true);

                    $custom_html .= '<a href="' . esc_url(get_permalink($r_id)) . '" class="voc-cross-card">
                        ' . ($r_img ? '<img src="' . esc_url($r_img) . '" class="voc-cross-img" alt="' . esc_attr(get_the_title()) . '">' : '') . '
                        <div class="voc-cross-body">
                            <h3 style="font-size:16px; font-weight:700; margin:0 0 6px 0; color:' . esc_attr($primary_col) . ';">' . esc_html(get_the_title()) . '</h3>
                            <p style="font-size:13px; color:#64748b; margin:0;">' . esc_html($r_address) . '</p>
                        </div>
                    </a>';
                }
                wp_reset_postdata();
                $custom_html .= '</div></div>';
            }

            $custom_html .= '</article>';
            return $custom_html;
        }
        return $content;
    }
}

// ==========================================
// 3. MAIN DESTINATIONS GRID + MAP SHORTCODE
// ==========================================
if ( ! function_exists( 'voc_render_destinations_grid' ) ) {
    function voc_render_destinations_grid() {
        $primary_col = get_option('villocity_global_primary', '#860038');
        $accent_col  = get_option('villocity_global_accent', '#fadceb');
        $text_col    = get_option('villocity_global_text', '#1c1917');

        $query = new WP_Query( array(
            'post_type'        => 'voc_destination',
            'post_status'      => 'publish',
            'posts_per_page'   => -1,
            'orderby'          => 'title',
            'order'            => 'ASC',
            'suppress_filters' => true
        ));

        $counties = get_terms( array( 'taxonomy' => 'voc_destination_county', 'hide_empty' => true ) );

        $map_markers = array();
        $rendered_ids = array();

        $default_thumb = 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="600" height="400" viewBox="0 0 600 400"><rect width="100%" height="100%" fill="%23f1f5f9"/><text x="50%" y="50%" font-family="sans-serif" font-size="20" font-weight="bold" fill="%2394a3b8" text-anchor="middle" dominant-baseline="middle">VilloCity Destination</text></svg>';

        $html = '<style>
            .voc-dest-wrap { font-family: var(--voc-font-family, inherit) !important; color: ' . esc_attr($text_col) . '; max-width: 1200px; margin: 0 auto; box-sizing: border-box; }
            .voc-dest-map-wrapper { position: relative; margin-bottom: 28px; box-shadow: 0 4px 20px rgba(0,0,0,0.04); border-radius: 16px; overflow: hidden; border: 1px solid #e2e8f0; }
            .voc-dest-map-container { width: 100%; height: 420px; z-index: 1; cursor: pointer; }
            .voc-dest-map-hint { position: absolute; top: 14px; right: 14px; z-index: 1000; background: rgba(15, 23, 42, 0.8); color: #ffffff; font-size: 12px; font-weight: 600; padding: 6px 14px; border-radius: 20px; pointer-events: none; backdrop-filter: blur(4px); transition: all 0.3s ease; }
            
            .voc-dest-filter-bar { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 20px; margin-bottom: 32px; display: flex; gap: 16px; flex-wrap: wrap; box-shadow: 0 2px 12px rgba(0,0,0,0.02); }
            .voc-dest-filter-bar input, .voc-dest-filter-bar select { padding: 12px 16px !important; border: 1px solid #cbd5e1 !important; border-radius: 10px !important; font-size: 14px !important; background: #fff !important; color: #0f172a !important; outline: none; transition: border-color 0.2s, box-shadow 0.2s; }
            .voc-dest-filter-bar input:focus, .voc-dest-filter-bar select:focus { border-color: ' . esc_attr($primary_col) . ' !important; box-shadow: 0 0 0 3px ' . esc_attr($accent_col) . ' !important; }

            .voc-dest-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 28px; }
            
            .voc-dest-card-item { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; overflow: hidden; display: flex; flex-direction: column; text-decoration: none; color: inherit; transition: transform 0.25s ease, box-shadow 0.25s ease; position: relative; }
            .voc-dest-card-item:hover { transform: translateY(-5px); box-shadow: 0 12px 30px rgba(0,0,0,0.07); }
            
            .voc-dest-card-thumb-wrap { position: relative; width: 100%; height: 210px; overflow: hidden; background: #f8fafc; }
            .voc-dest-card-thumb { width: 100%; height: 100%; object-fit: cover; transition: transform 0.3s ease; }
            .voc-dest-card-item:hover .voc-dest-card-thumb { transform: scale(1.04); }
            
            .voc-dest-card-badge-feat { position: absolute; top: 12px; left: 12px; background: ' . esc_attr($primary_col) . '; color: #ffffff; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; padding: 5px 12px; border-radius: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.15); }
            .voc-dest-card-badge-county { position: absolute; bottom: 12px; left: 12px; background: rgba(255, 255, 255, 0.92); color: #0f172a; font-size: 12px; font-weight: 700; padding: 4px 10px; border-radius: 6px; backdrop-filter: blur(4px); display: flex; align-items: center; gap: 4px; box-shadow: 0 2px 6px rgba(0,0,0,0.1); }

            .voc-dest-card-body { padding: 22px; display: flex; flex-direction: column; flex-grow: 1; }
            .voc-dest-card-title { font-size: 20px !important; font-weight: 800 !important; margin: 0 0 10px 0 !important; color: ' . esc_attr($primary_col) . ' !important; line-height: 1.3 !important; font-family: var(--voc-font-family, inherit) !important; }
            .voc-dest-card-excerpt { font-size: 14px; color: #475569; line-height: 1.6; margin: 0 0 16px 0; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; }
            .voc-dest-card-address { font-size: 13px; color: #64748b; margin-bottom: 20px; display: flex; align-items: center; gap: 6px; font-weight: 500; }
            .voc-dest-card-footer { margin-top: auto; padding-top: 14px; border-top: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center; font-size: 13px; font-weight: 700; color: ' . esc_attr($primary_col) . '; }

            .voc-sr-only { position: absolute !important; width: 1px !important; height: 1px !important; padding: 0 !important; margin: -1px !important; overflow: hidden !important; clip: rect(0, 0, 0, 0) !important; white-space: nowrap !important; border: 0 !important; }
        </style>';

        $html .= '<div class="voc-dest-wrap" role="region" aria-label="Regional Destinations Directory">
            <!-- INTERACTIVE MAP DISPLAY -->
            <div class="voc-dest-map-wrapper">
                <div id="voc-destinations-map" class="voc-dest-map-container" aria-label="Interactive map showing destination pins"></div>
                <div id="voc-map-hint" class="voc-dest-map-hint">Click map to enable scroll zoom</div>
            </div>

            <!-- SEARCH & FILTER BAR -->
            <div class="voc-dest-filter-bar" aria-label="Filter destinations">
                <label for="voc-dest-search" class="voc-sr-only">Search destinations</label>
                <input type="text" id="voc-dest-search" placeholder="Search historical sites, themes, or towns..." style="flex:2; min-width:220px;">
                
                <label for="voc-dest-county-filter" class="voc-sr-only">Filter by County</label>
                <select id="voc-dest-county-filter" style="flex:1; min-width:180px;">
                    <option value="all">All Counties</option>';
                    if ( ! is_wp_error($counties) ) {
                        foreach($counties as $c) {
                            $html .= '<option value="' . esc_attr($c->slug) . '">' . esc_html($c->name) . '</option>';
                        }
                    }
        $html .= '</select>
            </div>

            <!-- DESTINATIONS DIRECTORY GRID -->
            <div class="voc-dest-grid" id="voc-dest-grid-container">';

        if ( $query->have_posts() ) {
            while ( $query->have_posts() ) {
                $query->the_post();
                $id = get_the_ID();

                if ( in_array( $id, $rendered_ids, true ) ) {
                    continue;
                }
                $rendered_ids[] = $id;

                $img = get_the_post_thumbnail_url($id, 'medium_large') ?: $default_thumb;
                $address = get_post_meta($id, '_dest_address', true);
                $lat = get_post_meta($id, '_dest_lat', true);
                $lng = get_post_meta($id, '_dest_lng', true);
                $is_featured = get_post_meta($id, '_dest_featured', true) === 'yes';

                $c_terms = wp_get_post_terms( $id, 'voc_destination_county', array('fields' => 'names') );
                $c_slugs_array = wp_get_post_terms( $id, 'voc_destination_county', array('fields' => 'slugs') );
                
                $county_display = ! is_wp_error($c_terms) && ! empty($c_terms) ? $c_terms[0] : 'Regional Attraction';
                $c_slugs = ! is_wp_error($c_slugs_array) ? implode(' ', $c_slugs_array) : '';

                $search_data = esc_attr( strtolower( get_the_title() . ' ' . $address . ' ' . $c_slugs ) );

                if ( ! empty($lat) && ! empty($lng) ) {
                    $map_markers[] = array(
                        'id'        => $id,
                        'title'     => get_the_title(),
                        'lat'       => (float) $lat,
                        'lng'       => (float) $lng,
                        'permalink' => get_permalink($id),
                        'address'   => $address,
                        'county'    => $c_slugs
                    );
                }

                $html .= '<a href="' . esc_url(get_permalink($id)) . '" class="voc-dest-card-item" data-id="' . esc_attr($id) . '" data-search="' . $search_data . '" data-county="' . esc_attr($c_slugs) . '">
                    <div class="voc-dest-card-thumb-wrap">
                        <img src="' . esc_url($img) . '" class="voc-dest-card-thumb" alt="' . esc_attr(get_the_title()) . '">
                        ' . ($is_featured ? '<span class="voc-dest-card-badge-feat">★ Featured</span>' : '') . '
                        <span class="voc-dest-card-badge-county">' . (function_exists('villocity_get_icon') ? villocity_get_icon('map-pin') : '') . ' ' . esc_html($county_display) . '</span>
                    </div>

                    <div class="voc-dest-card-body">
                        <h2 class="voc-dest-card-title">' . esc_html(get_the_title()) . '</h2>
                        
                        ' . (!empty($address) ? '<div class="voc-dest-card-address">' . (function_exists('villocity_get_icon') ? villocity_get_icon('map-pin') : '') . ' <span>' . esc_html($address) . '</span></div>' : '') . '

                        <p class="voc-dest-card-excerpt">' . esc_html(wp_trim_words(get_the_excerpt(), 18, '...')) . '</p>
                        
                        <div class="voc-dest-card-footer">
                            <span>EXPLORE ATTRACTION</span>
                            <span>&rarr;</span>
                        </div>
                    </div>
                </a>';
            }
            wp_reset_postdata();
        } else {
            $html .= '<p style="color:#64748b; grid-column: 1/-1; padding: 40px; text-align: center; background: #fff; border-radius: 16px; border: 1px solid #e2e8f0;">No tourism destinations currently listed.</p>';
        }

        $html .= '</div></div>';

        // Map & Filter Script
        $html .= '<script>
        document.addEventListener("DOMContentLoaded", function() {
            const markersData = ' . wp_json_encode($map_markers) . ';
            const search = document.getElementById("voc-dest-search");
            const county = document.getElementById("voc-dest-county-filter");
            const items = document.querySelectorAll("#voc-dest-grid-container .voc-dest-card-item");
            const mapContainer = document.getElementById("voc-destinations-map");
            const mapHint = document.getElementById("voc-map-hint");

            let map, mapMarkersGroup = [];

            if (typeof L !== "undefined" && mapContainer) {
                map = L.map("voc-destinations-map", {
                    scrollWheelZoom: false
                }).setView([43.1, -75.2], 9);

                L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
                    maxZoom: 18,
                    attribution: "&copy; OpenStreetMap contributors"
                }).addTo(map);

                map.on("click", function() {
                    if (!map.scrollWheelZoom.enabled()) {
                        map.scrollWheelZoom.enable();
                        if (mapHint) {
                            mapHint.textContent = "Scroll zoom enabled";
                            mapHint.style.background = "rgba(22, 101, 52, 0.85)";
                            setTimeout(function() { mapHint.style.opacity = "0"; }, 2500);
                        }
                    }
                });

                mapContainer.addEventListener("mouseleave", function() {
                    if (map.scrollWheelZoom.enabled()) {
                        map.scrollWheelZoom.disable();
                        if (mapHint) {
                            mapHint.textContent = "Click map to enable scroll zoom";
                            mapHint.style.background = "rgba(15, 23, 42, 0.8)";
                            mapHint.style.opacity = "1";
                        }
                    }
                });

                const bounds = [];

                markersData.forEach(item => {
                    const marker = L.marker([item.lat, item.lng]).addTo(map);
                    marker.bindPopup("<strong><a href=\'" + item.permalink + "\'>" + item.title + "</a></strong><br>" + item.address);
                    marker.destinationId = item.id;
                    marker.county = item.county;
                    marker.titleText = item.title.toLowerCase();
                    mapMarkersGroup.push(marker);
                    bounds.push([item.lat, item.lng]);
                });

                if (bounds.length > 0) {
                    map.fitBounds(bounds, { padding: [30, 30] });
                }
            }

            function filter() {
                const q = search ? search.value.toLowerCase().trim() : "";
                const c = county ? county.value : "all";

                items.forEach(item => {
                    const searchData = item.getAttribute("data-search") || "";
                    const itemCounty = item.getAttribute("data-county") || "";

                    const matchSearch = q === "" || searchData.includes(q);
                    const matchCounty = c === "all" || itemCounty.includes(c);

                    if (matchSearch && matchCounty) {
                        item.style.display = "flex";
                    } else {
                        item.style.display = "none";
                    }
                });

                if (mapMarkersGroup.length > 0) {
                    mapMarkersGroup.forEach(marker => {
                        const matchSearch = q === "" || marker.titleText.includes(q);
                        const matchCounty = c === "all" || marker.county.includes(c);

                        if (matchSearch && matchCounty) {
                            map.addLayer(marker);
                        } else {
                            map.removeLayer(marker);
                        }
                    });
                }
            }

            if (search) search.addEventListener("input", filter);
            if (county) county.addEventListener("change", filter);
        });
        </script>';

        return $html;
    }
}

add_action('init', function() {
    add_shortcode( 'villocity_destinations', 'voc_render_destinations_grid' );
});