<?php
/**
 * Module Name: VilloCity Digital Archives (Frontend)
 * Module URI:  https://villocity.org
 * Description: Public digital archive gallery, era/type filtering, single artifact renderer, and homepage widgets.
 * Version:     2.1.0
 * Last Updated: September 5, 2026
 * Author:      Brockett Creative Group, Inc.
 * Author URI:  https://brockettcreative.com
 * Copyright:   © 2026 Brockett Creative Group, Inc. All Rights Reserved.
 * Text Domain: villocity-suite
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

// ==========================================
// 1. SINGLE ARTIFACT TEMPLATE
// ==========================================
if ( ! function_exists( 'bcg_history_single_page_template' ) ) {
    add_filter( 'the_content', 'bcg_history_single_page_template' );
    function bcg_history_single_page_template( $content ) {
        if ( is_singular( 'bcg_artifact' ) && in_the_loop() && is_main_query() ) {
            $id         = get_the_ID();
            $era        = get_post_meta( $id, '_artifact_era', true );
            $collection = get_post_meta( $id, '_artifact_collection', true );
            $type       = get_post_meta( $id, '_artifact_type', true );
            $donor      = get_post_meta( $id, '_artifact_donor', true );
            $img        = get_the_post_thumbnail_url( $id, 'full' );

            $primary_col = get_option('villocity_global_primary', '#860038');
            $accent_col  = get_option('villocity_global_accent', '#fadceb');
            $text_col    = get_option('villocity_global_text', '#1c1917');
            $bg_col      = get_option('villocity_global_bg', '#ffffff');

            $referer_url = wp_get_referer();
            $archive_url = ($referer_url && strpos($referer_url, home_url()) !== false) 
                ? $referer_url 
                : home_url( '/digital-archives/' );

            $custom_html = '
            <style>
                h1.entry-title, h1.page-title, h1.post-title, .entry-title, .page-title, .post-title, header.entry-header { display: none !important; }
                .bcg-single-wrap { --bcg-primary: ' . esc_attr($primary_col) . '; --bcg-accent: ' . esc_attr($accent_col) . '; --bcg-text: ' . esc_attr($text_col) . '; --bcg-bg: ' . esc_attr($bg_col) . '; font-family: var(--voc-font-family, inherit) !important; background: var(--bcg-bg); color: var(--bcg-text); border-radius: 16px; border: 1px solid #e7e5e4; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.04); max-width: 900px; margin: 20px auto; }
                .bcg-single-hero { width: 100%; max-height: 600px; object-fit: contain; background: #1c1917; display: block; padding: 20px; box-sizing: border-box; }
                .bcg-single-body { padding: 32px; }
                .bcg-single-top-nav { margin-bottom: 20px; }
                .bcg-single-badge { background: var(--bcg-accent); color: var(--bcg-primary); font-size: 12px; font-weight: 700; padding: 6px 14px; border-radius: 20px; display: inline-flex; align-items: center; margin-bottom: 16px; text-transform: uppercase; letter-spacing: 0.05em; gap: 6px; }
                h1.bcg-single-title { font-size: 36px !important; font-weight: 800 !important; color: var(--bcg-text) !important; margin: 0 0 16px 0 !important; line-height: 1.2 !important; display: block !important; font-family: var(--voc-font-family, inherit) !important; }
                .bcg-single-meta-card { background: #fafaf9; border: 1px solid #e7e5e4; border-radius: 12px; padding: 20px; margin-bottom: 24px; display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
                .bcg-single-meta-row { font-size: 14px; font-weight: 600; display: flex; align-items: center; gap: 8px; color: var(--bcg-text); }
                .bcg-single-desc { font-size: 16px; line-height: 1.8; color: var(--bcg-text); margin-bottom: 32px; opacity: 0.9; border-top: 1px solid #e7e5e4; padding-top: 24px; }
                .bcg-single-btn-secondary { background: #f0f4f8 !important; color: #1c1917 !important; font-weight: 600 !important; text-decoration: none !important; padding: 10px 20px !important; border-radius: 24px !important; display: inline-flex !important; align-items: center !important; gap: 8px !important; transition: all 0.2s ease !important; font-size: 14px !important; }
                .bcg-single-btn-secondary:hover { background: #e7e5e4 !important; }
            </style>

            <article class="bcg-single-wrap">
                ' . ($img ? '<a href="'.esc_url($img).'" target="_blank"><img src="' . esc_url($img) . '" class="bcg-single-hero" alt="' . esc_attr(get_the_title()) . '"></a>' : '') . '
                <div class="bcg-single-body">
                    <nav class="bcg-single-top-nav">
                        <a href="' . esc_url($archive_url) . '" onclick="if(document.referrer && document.referrer.includes(window.location.hostname)) { window.history.back(); return false; }" class="bcg-single-btn-secondary">&larr; Back to Digital Archives</a>
                    </nav>

                    <div style="display:flex; gap:8px; flex-wrap:wrap;">
                        <span class="bcg-single-badge">' . villocity_get_icon('camera') . esc_html($type) . '</span>
                        ' . (!empty($era) ? '<span class="bcg-single-badge" style="background:#fef3c7; color:#92400e;">' . villocity_get_icon('clock') . esc_html($era) . '</span>' : '') . '
                    </div>
                    
                    <h1 class="bcg-single-title">' . esc_html(get_the_title()) . '</h1>

                    <div class="bcg-single-meta-card">
                        ' . (!empty($collection) ? '<div class="bcg-single-meta-row">' . villocity_get_icon('archive') . ' <span>Collection: ' . esc_html($collection) . '</span></div>' : '') . '
                        ' . (!empty($donor) ? '<div class="bcg-single-meta-row">' . villocity_get_icon('user') . ' <span>Source: ' . esc_html($donor) . '</span></div>' : '') . '
                    </div>

                    <div class="bcg-single-desc">
                        ' . $content . '
                    </div>
                </div>
            </article>';

            return $custom_html;
        }
        return $content;
    }
}

// ==========================================
// 2. SHORTCODE: MAIN DIGITAL ARCHIVES GALLERY
// ==========================================
if ( ! function_exists( 'bcg_render_digital_archives' ) ) {
    function bcg_render_digital_archives() {
        $primary_col = get_option('villocity_global_primary', '#860038');
        $accent_col  = get_option('villocity_global_accent', '#fadceb');
        $text_col    = get_option('villocity_global_text', '#1c1917');
        $bg_col      = get_option('villocity_global_bg', '#fafaf9');

        $query = new WP_Query( array(
            'post_type'        => 'bcg_artifact',
            'post_status'      => 'publish',
            'posts_per_page'   => -1,
            'orderby'          => 'date',
            'order'            => 'DESC',
            'suppress_filters' => true
        ));

        $eras = array();
        $types = array();
        $posts_list = array();
        $rendered_ids = array();

        if ( $query->have_posts() ) {
            foreach ( $query->posts as $p ) {
                if ( in_array( $p->ID, $rendered_ids, true ) ) {
                    continue;
                }
                $rendered_ids[] = $p->ID;
                $posts_list[]   = $p;

                $e = get_post_meta($p->ID, '_artifact_era', true);
                $t = get_post_meta($p->ID, '_artifact_type', true);
                if (!empty($e) && !in_array($e, $eras)) $eras[] = $e;
                if (!empty($t) && !in_array($t, $types)) $types[] = $t;
            }
        }
        sort($eras);
        sort($types);

        $html = '<style>
            .bcg-feed-wrap { --bcg-primary: ' . esc_attr($primary_col) . '; --bcg-accent: ' . esc_attr($accent_col) . '; --bcg-text: ' . esc_attr($text_col) . '; --bcg-bg: ' . esc_attr($bg_col) . '; font-family: var(--voc-font-family, inherit) !important; background-color: var(--bcg-bg); color: var(--bcg-text); border-radius: 16px; overflow: hidden; border: 1px solid #e7e5e4; box-shadow: 0 4px 20px rgba(0,0,0,0.03); max-width: 1200px; margin: 0 auto; }
            .bcg-feed-header { background-color: rgba(0,0,0,0.02); padding: 32px 24px; border-bottom: 1px solid #e7e5e4; text-align:center; }
            .bcg-badge-label { color: var(--bcg-primary); font-weight: 700; letter-spacing: 0.05em; text-transform: uppercase; font-size: 14px; display: inline-flex; align-items: center; gap: 8px; margin-bottom: 12px; }
            .bcg-feed-title { font-size: 36px; font-weight: 800; color: var(--bcg-primary); margin: 0 0 8px 0 !important; line-height: 1.1; font-family: var(--voc-font-family, inherit) !important; }
            .bcg-feed-subtitle { font-size: 18px; color: var(--bcg-text); opacity: 0.8; margin: 0 auto !important; max-width: 600px; }

            .bcg-feed-body { padding: 24px; }
            @media (min-width: 1024px) { .bcg-feed-body { padding: 40px; } }

            .bcg-search-container { margin-bottom: 24px; }
            .bcg-search-input-wrap { display: flex; align-items: center; background: #fff; border: 1px solid #d6d3d1; border-radius: 12px; padding: 12px 16px; transition: border-color 0.2s; }
            .bcg-search-input-wrap:focus-within { border-color: var(--bcg-primary); box-shadow: 0 0 0 3px var(--bcg-accent); }
            .bcg-search-input-wrap input { border: none !important; outline: none !important; width: 100% !important; background: transparent !important; font-size: 16px !important; margin: 0 !important; padding: 0 !important; color: #1c1917 !important; }
            
            .bcg-filters { display: flex; gap: 12px; margin-bottom: 32px; flex-wrap: wrap; background: #fff; padding: 16px; border-radius: 12px; border: 1px solid #e7e5e4; }
            .bcg-filters-select { flex:1; min-width: 200px; padding: 10px 14px; border: 1px solid #d6d3d1; border-radius: 8px; font-size: 14px; font-family: var(--voc-font-family, inherit) !important; color: #1c1917; cursor: pointer; transition: 0.2s; }
            .bcg-filters-select:focus { outline: none; border-color: var(--bcg-primary); }

            .bcg-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 24px; align-items: start; }
            .bcg-card { background: #fff; border: 1px solid #e7e5e4; border-radius: 12px; overflow: hidden; display: flex; flex-direction: column; box-shadow: 0 4px 15px rgba(0,0,0,0.03); transition: transform 0.2s; position: relative; text-decoration: none; color: inherit; }
            .bcg-card:hover { transform: translateY(-4px); box-shadow: 0 8px 25px rgba(0,0,0,0.08); }
            
            .bcg-card-img-wrap { width: 100%; aspect-ratio: 4/3; background: #e7e5e4; overflow: hidden; display: block; position: relative; }
            .bcg-card-img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.4s ease; display: block; }
            .bcg-card:hover .bcg-card-img { transform: scale(1.05); }
            
            .bcg-card-body { padding: 16px; display: flex; flex-direction: column; }
            .bcg-card-era { font-size: 12px; font-weight: 700; color: var(--bcg-primary); text-transform: uppercase; margin-bottom: 4px; letter-spacing: 0.05em; }
            .bcg-card-title { font-size: 18px; font-weight: 800; margin: 0 0 8px 0; color: var(--bcg-text); line-height: 1.3; font-family: var(--voc-font-family, inherit) !important; }
            .bcg-card:hover .bcg-card-title { color: var(--bcg-primary); }
            .bcg-card-meta { font-size: 13px; color: var(--bcg-text); opacity: 0.7; }
        </style>';

        $html .= '<div class="bcg-feed-wrap" role="region" aria-label="Digital Archives & Exhibits">';
        $html .= '<div class="bcg-feed-header">
              <div class="bcg-badge-label">' . villocity_get_icon('archive') . '<span>Historical Society</span></div>
              <h1 class="bcg-feed-title">Digital Archives</h1>
              <p class="bcg-feed-subtitle">Explore our curated collection of historical photographs, documents, and artifacts.</p>
        </div>';
        
        $html .= '<div class="bcg-feed-body">';

        // Search & Filters
        $html .= '<div class="bcg-search-container">
            <div class="bcg-search-input-wrap">
                <span style="color:#64748b; margin-right:10px; display:flex;">' . villocity_get_icon('search') . '</span>
                <input type="text" id="bcg-archive-search" placeholder="Search the archives..." autocomplete="off">
            </div>
        </div>';

        $html .= '<div class="bcg-filters">
            <select id="bcg-filter-type" class="bcg-filters-select">
                <option value="all">All Artifact Types</option>';
                foreach($types as $t) { $html .= '<option value="'.esc_attr($t).'">'.esc_html($t).'</option>'; }
        $html .= '</select>
            <select id="bcg-filter-era" class="bcg-filters-select">
                <option value="all">All Eras & Decades</option>';
                foreach($eras as $e) { $html .= '<option value="'.esc_attr($e).'">'.esc_html($e).'</option>'; }
        $html .= '</select>
        </div>';

        $html .= '<div class="bcg-grid" id="bcg-archive-grid">';

        if ( ! empty( $posts_list ) ) {
            foreach ( $posts_list as $p ) {
                $id = $p->ID;
                $era  = get_post_meta( $id, '_artifact_era', true );
                $type = get_post_meta( $id, '_artifact_type', true );
                $img  = get_the_post_thumbnail_url( $id, 'medium_large' );
                $permalink = get_permalink( $id );

                $html .= '<a href="' . esc_url($permalink) . '" class="bcg-card" data-type="' . esc_attr($type) . '" data-era="' . esc_attr($era) . '" data-title="' . esc_attr($p->post_title) . '">
                    ' . ($img ? '<div class="bcg-card-img-wrap"><img src="'.esc_url($img).'" class="bcg-card-img" alt="' . esc_attr($p->post_title) . '"></div>' : '<div class="bcg-card-img-wrap" style="display:flex; align-items:center; justify-content:center; color:#a8a29e;">' . villocity_get_icon('camera') . '</div>') . '
                    <div class="bcg-card-body">
                        <div class="bcg-card-era">' . esc_html($era) . '</div>
                        <h2 class="bcg-card-title">' . esc_html($p->post_title) . '</h2>
                        <div class="bcg-card-meta">' . esc_html($type) . '</div>
                    </div>
                </a>';
            }
        } else {
            $html .= '<p style="color: #78716c; font-weight: 500;">No artifacts have been digitized yet.</p>';
        }

        $html .= '</div></div></div>';

        $html .= '<script>
            document.addEventListener("DOMContentLoaded", function() {
                const searchInput = document.getElementById("bcg-archive-search");
                const typeFilter = document.getElementById("bcg-filter-type");
                const eraFilter = document.getElementById("bcg-filter-era");
                const cards = document.querySelectorAll(".bcg-card");

                function filterArchives() {
                    const query = searchInput ? searchInput.value.toLowerCase().trim() : "";
                    const type = typeFilter ? typeFilter.value : "all";
                    const era = eraFilter ? eraFilter.value : "all";

                    cards.forEach(card => {
                        const title = card.getAttribute("data-title").toLowerCase();
                        const cType = card.getAttribute("data-type");
                        const cEra = card.getAttribute("data-era");

                        const matchesSearch = !query || title.includes(query);
                        const matchesType = (type === "all" || cType === type);
                        const matchesEra = (era === "all" || cEra === era);

                        if (matchesSearch && matchesType && matchesEra) {
                            card.style.display = "flex";
                        } else {
                            card.style.display = "none";
                        }
                    });
                }

                if(searchInput) searchInput.addEventListener("input", filterArchives);
                if(typeFilter) typeFilter.addEventListener("change", filterArchives);
                if(eraFilter) eraFilter.addEventListener("change", filterArchives);
            });
        </script>';

        return $html;
    }
}
add_action('init', function() {
    add_shortcode( 'villocity_digital_archives', 'bcg_render_digital_archives' );
});

// ==========================================
// 3. SHORTCODE: HOMEPAGE RECENT ARTIFACTS WIDGET
// ==========================================
if ( ! function_exists( 'bcg_render_recent_artifacts' ) ) {
    function bcg_render_recent_artifacts($atts) {
        $args = shortcode_atts( array( 'count' => 3, 'link' => '/digital-archives/' ), $atts );

        $primary_col = get_option('villocity_global_primary', '#860038');
        $text_col    = get_option('villocity_global_text', '#1c1917');

        $query = new WP_Query( array(
            'post_type'        => 'bcg_artifact',
            'post_status'      => 'publish',
            'posts_per_page'   => (int) $args['count'],
            'orderby'          => 'date',
            'order'            => 'DESC',
            'suppress_filters' => true
        ));

        $html = '<style>
            .bcg-recent-wrap { font-family: var(--voc-font-family, inherit) !important; }
            .bcg-recent-header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 24px; border-bottom: 2px solid #e7e5e4; padding-bottom: 12px; }
            .bcg-recent-title { font-size: 24px; font-weight: 800; color: ' . esc_attr($primary_col) . '; margin: 0; font-family: var(--voc-font-family, inherit) !important; }
            .bcg-recent-link { font-size: 14px; font-weight: 600; color: ' . esc_attr($text_col) . '; text-decoration: none; transition: 0.2s; }
            .bcg-recent-link:hover { color: ' . esc_attr($primary_col) . '; }
            .bcg-recent-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 16px; }
            .bcg-recent-card { display: block; border-radius: 12px; overflow: hidden; position: relative; text-decoration: none; background: #e7e5e4; aspect-ratio: 4/3; }
            .bcg-recent-img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.4s; }
            .bcg-recent-overlay { position: absolute; bottom: 0; left: 0; right: 0; background: linear-gradient(transparent, rgba(0,0,0,0.8)); padding: 20px 16px 16px; color: #fff; }
            .bcg-recent-card:hover .bcg-recent-img { transform: scale(1.05); }
            .bcg-recent-card-title { font-size: 15px; font-weight: 700; margin: 0 0 4px 0; text-shadow: 0 2px 4px rgba(0,0,0,0.5); font-family: var(--voc-font-family, inherit) !important; }
            .bcg-recent-card-era { font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: #cbd5e1; }
        </style>';

        $html .= '<div class="bcg-recent-wrap">';
        $html .= '<div class="bcg-recent-header">';
        $html .= '<h3 class="bcg-recent-title">From the Archives</h3>';
        $html .= '<a href="' . esc_url($args['link']) . '" class="bcg-recent-link">Explore Collection &rarr;</a>';
        $html .= '</div>';
        $html .= '<div class="bcg-recent-grid">';

        if ( $query->have_posts() ) {
            foreach ( $query->posts as $p ) {
                $id = $p->ID;
                $era = get_post_meta( $id, '_artifact_era', true );
                $img = get_the_post_thumbnail_url( $id, 'medium_large' );
                $permalink = get_permalink( $id );

                $html .= '<a href="' . esc_url($permalink) . '" class="bcg-recent-card">';
                if ($img) {
                    $html .= '<img src="' . esc_url($img) . '" class="bcg-recent-img" alt="' . esc_attr($p->post_title) . '">';
                }
                $html .= '<div class="bcg-recent-overlay">';
                $html .= '<h4 class="bcg-recent-card-title">' . esc_html($p->post_title) . '</h4>';
                if ($era) { $html .= '<div class="bcg-recent-card-era">' . esc_html($era) . '</div>'; }
                $html .= '</div></a>';
            }
            wp_reset_postdata();
        } else {
            $html .= '<p style="color: #78716c;">Archives coming soon.</p>';
        }
        $html .= '</div></div>';

        return $html;
    }
}
add_action('init', function() {
    add_shortcode( 'villocity_recent_artifacts', 'bcg_render_recent_artifacts' );
});