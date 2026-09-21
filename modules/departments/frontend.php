<?php
/**
 * Module Name: VilloCity Departments Module (Frontend)
 * Description: Public single department templates, structured Schema JSON-LD data, personnel roster queries, and grid shortcodes.
 * Version:     1.5.1 (Clean Hover Back Button Match)
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

// ==========================================
// 1. THEME HOOKS & SCHEMA JSON-LD (SEO/GEO/AEO)
// ==========================================
add_action( 'wp_head', function() {
    if ( is_singular( 'voc_department' ) ) {
        $id         = get_the_ID();
        $dept_title = get_the_title();
        $phone      = get_post_meta( $id, '_dept_phone', true );
        $email      = get_post_meta( $id, '_dept_email', true );
        $location   = get_post_meta( $id, '_dept_location', true );
        $site_name  = get_bloginfo( 'name' );

        $schema = array(
            '@context'    => 'https://schema.org',
            '@type'       => 'GovernmentOffice',
            'name'        => $dept_title . ' - ' . $site_name,
            'url'         => get_permalink( $id ),
            'parentOrganization' => array(
                '@type' => 'GovernmentOrganization',
                'name'  => $site_name
            )
        );

        if ( ! empty( $phone ) ) { $schema['telephone'] = $phone; }
        if ( ! empty( $email ) ) { $schema['email'] = $email; }
        if ( ! empty( $location ) ) {
            $schema['address'] = array(
                '@type' => 'PostalAddress',
                'streetAddress' => $location
            );
        }

        echo '<script type="application/ld+json">' . json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT ) . '</script>' . "\n";
    }
} );

// Suppress duplicate theme single headings safely
add_action( 'wp', function() {
    if ( is_singular( 'voc_department' ) ) {
        add_filter( 'astra_the_title_enabled', '__return_false' );
        remove_action( 'astra_single_header', 'astra_single_header_top' );
    }
} );

// ==========================================
// 2. SINGLE DEPARTMENT TEMPLATE
// ==========================================
if ( ! function_exists( 'voc_department_single_template' ) ) {
    add_filter( 'the_content', 'voc_department_single_template' );
    function voc_department_single_template( $content ) {
        if ( is_singular( 'voc_department' ) && in_the_loop() && is_main_query() ) {
            $id         = get_the_ID();
            $dept_title = get_the_title();
            $phone      = get_post_meta( $id, '_dept_phone', true );
            $email      = get_post_meta( $id, '_dept_email', true );
            $hours      = get_post_meta( $id, '_dept_hours', true );
            $location   = get_post_meta( $id, '_dept_location', true );
            $head_name  = get_post_meta( $id, '_dept_head_name', true );
            $head_title = get_post_meta( $id, '_dept_head_title', true );
            $docs       = get_post_meta( $id, '_dept_documents', true );

            $primary_col = get_option('villocity_global_primary', '#860038');
            $departments_url = home_url( '/departments/' );

            // Image Graphic Cascade: CPT Featured Image -> Customizer Logo -> Favicon -> Scaled SVG
            $dept_thumb     = get_the_post_thumbnail_url( $id, 'medium' );
            $custom_logo_id = get_theme_mod( 'custom_logo' );
            $site_logo_url  = $custom_logo_id ? wp_get_attachment_image_url( $custom_logo_id, 'medium' ) : '';
            $favicon_url    = get_site_icon_url( 180 );

            $header_graphic = '';
            if ( $dept_thumb ) {
                $header_graphic = '<img src="' . esc_url($dept_thumb) . '" alt="' . esc_attr($dept_title) . '" class="voc-dept-logo-img">';
            } elseif ( $site_logo_url ) {
                $header_graphic = '<img src="' . esc_url($site_logo_url) . '" alt="' . esc_attr(get_bloginfo('name')) . '" class="voc-dept-logo-img">';
            } elseif ( $favicon_url ) {
                $header_graphic = '<img src="' . esc_url($favicon_url) . '" alt="' . esc_attr($dept_title) . ' Icon" class="voc-dept-favicon-img">';
            } else {
                $icon_svg = function_exists('villocity_get_icon') ? villocity_get_icon('building') : '🏛️';
                $header_graphic = '<div class="voc-dept-icon-graphic" aria-hidden="true">' . $icon_svg . '</div>';
            }

            // Personnel Roster Query
            $staff_ids = get_posts( array(
                'post_type'      => 'bcg_staff',
                'post_status'    => 'publish',
                'posts_per_page' => -1,
                'fields'         => 'ids',
                'no_found_rows'  => true,
                'meta_query'     => array(
                    'relation' => 'OR',
                    array( 'key' => '_staff_department_id', 'value' => $id, 'compare' => '=' ),
                    array( 'key' => '_staff_department', 'value' => $dept_title, 'compare' => '=' )
                )
            ) );

            $staff_html = '';
            if ( ! empty( $staff_ids ) ) {
                $staff_html .= '<section aria-label="Department Personnel" style="margin-top: 48px; padding-top: 32px; border-top: 1px solid #e2e8f0;">';
                $staff_html .= '<h2 style="font-size: 22px; font-weight: 800; color: var(--voc-primary); margin-bottom: 20px; display: flex; align-items: center; gap: 8px;">' . villocity_get_icon('users') . ' Department Personnel</h2>';
                $staff_html .= '<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 16px;">';

                foreach ( $staff_ids as $s_id ) {
                    $s_name  = get_the_title($s_id);
                    $s_title = get_post_meta($s_id, '_staff_title', true);
                    $s_phone = get_post_meta($s_id, '_staff_phone', true);
                    $s_email = get_post_meta($s_id, '_staff_email', true);
                    $s_img   = get_the_post_thumbnail_url($s_id, 'thumbnail');

                    $staff_html .= '
                    <article style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 16px; display: flex; gap: 14px; align-items: center; box-shadow: 0 2px 8px rgba(0,0,0,0.02);">
                        ' . ($s_img ? '<img src="' . esc_url($s_img) . '" alt="' . esc_attr($s_name) . '" style="width: 60px; height: 60px; border-radius: 50%; object-fit: cover; flex-shrink: 0;">' : '<div style="width: 60px; height: 60px; border-radius: 50%; background: var(--voc-accent); color: var(--voc-primary); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">' . villocity_get_icon('user') . '</div>') . '
                        <div>
                            <strong style="font-size: 15px; display: block; color: #0f172a;">' . esc_html($s_name) . '</strong>
                            ' . (!empty($s_title) ? '<span style="font-size: 12px; color: #64748b; display: block;">' . esc_html($s_title) . '</span>' : '') . '
                            ' . (!empty($s_phone) ? '<a href="tel:' . preg_replace('/[^0-9+]/', '', $s_phone) . '" style="font-size: 12px; color: #475569; text-decoration:none; display: flex; align-items: center; gap: 4px; margin-top: 4px;">' . villocity_get_icon('phone') . ' ' . esc_html($s_phone) . '</a>' : '') . '
                            ' . (!empty($s_email) ? '<a href="mailto:' . esc_attr($s_email) . '" aria-label="Email ' . esc_attr($s_name) . '" style="font-size: 12px; color: var(--voc-primary); text-decoration: none; display: flex; align-items: center; gap: 4px; margin-top: 2px;">' . villocity_get_icon('mail') . ' Email Staff</a>' : '') . '
                        </div>
                    </article>';
                }

                $staff_html .= '</div></section>';
            }

            // Public Forms Block
            $docs_html = '';
            if ( ! empty( $docs ) && is_array( $docs ) ) {
                $docs_html .= '<section aria-label="Forms and Public Documents" style="margin-top: 48px; padding-top: 32px; border-top: 1px solid #e2e8f0;">';
                $docs_html .= '<h2 style="font-size: 22px; font-weight: 800; color: var(--voc-primary); margin-bottom: 20px; display: flex; align-items: center; gap: 8px;">' . villocity_get_icon('archive') . ' Forms & Public Documents</h2>';
                $docs_html .= '<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 16px;">';

                foreach ( $docs as $doc ) {
                    $docs_html .= '
                    <a href="' . esc_url($doc['url']) . '" target="_blank" rel="noopener noreferrer" aria-label="Download ' . esc_attr($doc['title']) . ' PDF" style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 16px; display: flex; align-items: center; justify-content: space-between; text-decoration: none; color: #0f172a; font-weight: 600; font-size: 14px; transition: border-color 0.2s, box-shadow 0.2s;">
                        <span style="display: flex; align-items: center; gap: 8px;">' . villocity_get_icon('tag') . ' ' . esc_html($doc['title']) . '</span>
                        <span style="color: var(--voc-primary); font-size: 12px; font-weight: 700;">Download &rarr;</span>
                    </a>';
                }

                $docs_html .= '</div></section>';
            }

            $html = '
            <style>
                body.single-voc_department .ast-single-post-order,
                body.single-voc_department .entry-header:not(.voc-header-box),
                body.single-voc_department header.entry-header,
                body.single-voc_department h1.entry-title {
                    display: none !important;
                }

                .voc-dept-single { 
                    font-family: var(--voc-font-family, inherit) !important; 
                    color: var(--voc-text, inherit); 
                    max-width: 1050px; 
                    margin: 6em auto !important; 
                    padding: 0 20px;
                    box-sizing: border-box;
                }

                .voc-dept-back-btn { 
                    background: transparent !important; 
                    color: #64748b !important; 
                    padding: 8px 0 !important; 
                    font-weight: 600 !important; 
                    font-size: 14px !important; 
                    text-decoration: none !important; 
                    display: inline-flex !important; 
                    align-items: center !important; 
                    gap: 8px !important; 
                    border: none !important; 
                    outline: none !important;
                    box-shadow: none !important;
                    transition: color 0.2s ease !important; 
                    margin-bottom: 24px; 
                    text-transform: uppercase; 
                    letter-spacing: 0.05em; 
                }
                .voc-dept-back-btn:hover, .voc-dept-back-btn:focus-visible { 
                    color: ' . esc_attr($primary_col) . ' !important; 
                    border: none !important;
                    outline: none !important;
                    box-shadow: none !important;
                }

                .voc-dept-header-box { 
                    background: #ffffff; 
                    border: 1px solid #e2e8f0; 
                    border-radius: 20px; 
                    padding: 36px; 
                    margin-bottom: 40px; 
                    box-shadow: 0 4px 24px rgba(0,0,0,0.04); 
                    display: flex; 
                    flex-wrap: wrap; 
                    gap: 32px; 
                    align-items: center; 
                    justify-content: space-between;
                }

                .voc-dept-info-side { flex: 1; min-width: 280px; }
                
                .voc-dept-identity-flex {
                    display: flex;
                    align-items: center;
                    gap: 28px;
                }

                .voc-dept-graphic-wrap {
                    padding-right: 28px;
                    border-right: 1px solid #e2e8f0;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    flex-shrink: 0;
                }

                .voc-dept-logo-img {
                    max-width: 140px;
                    max-height: 70px;
                    width: auto;
                    height: auto;
                    object-fit: contain;
                    display: block;
                }

                .voc-dept-favicon-img {
                    width: 52px;
                    height: 52px;
                    border-radius: 12px;
                    object-fit: contain;
                    display: block;
                }

                .voc-dept-icon-graphic {
                    width: 60px;
                    height: 60px;
                    border-radius: 14px;
                    background: var(--voc-accent);
                    color: var(--voc-primary);
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    flex-shrink: 0;
                }
                .voc-dept-icon-graphic svg, .voc-dept-icon-graphic .villocity-icon {
                    width: 30px !important;
                    height: 30px !important;
                    stroke-width: 2px !important;
                    stroke: currentColor !important;
                    margin: 0 !important;
                }

                .voc-dept-badge { color: var(--voc-primary); font-weight: 800; font-size: 11px; text-transform: uppercase; letter-spacing: 0.08em; display: inline-block; margin-bottom: 4px; }
                .voc-dept-main-title { font-size: 32px !important; font-weight: 800 !important; color: var(--voc-primary) !important; margin: 2px 0 6px 0 !important; line-height: 1.15 !important; font-family: var(--voc-font-family, inherit) !important; }
                .voc-dept-head-lead { margin: 0; font-size: 15px; color: #475569; font-weight: 500; }

                .voc-dept-contact-panel { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 16px; padding: 20px 24px; min-width: 280px; flex: 0 1 360px; display: grid; grid-template-columns: 1fr; gap: 14px; }
                @media (min-width: 640px) and (max-width: 991px) { .voc-dept-contact-panel { grid-template-columns: repeat(2, 1fr); flex: 1 1 100%; } }

                .voc-dept-contact-row { display: flex; align-items: center; gap: 12px; font-size: 14px; font-weight: 600; color: #1e293b; }
                .voc-dept-icon-badge { width: 36px; height: 36px; border-radius: 10px; background: var(--voc-accent); color: var(--voc-primary); display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
                .voc-dept-icon-badge svg, .voc-dept-icon-badge .villocity-icon { width: 18px !important; height: 18px !important; stroke: currentColor !important; margin: 0 !important; }
                .voc-dept-contact-link { color: #0f172a; text-decoration: none; transition: color 0.2s ease; min-height: 44px; display: inline-flex; align-items: center; }
                .voc-dept-contact-link:hover, .voc-dept-contact-link:focus-visible { color: var(--voc-primary); text-decoration: underline; }

                @media (max-width: 640px) {
                    .voc-dept-identity-flex { flex-direction: column; align-items: flex-start; gap: 16px; }
                    .voc-dept-graphic-wrap { border-right: none; border-bottom: 1px solid #e2e8f0; padding-right: 0; padding-bottom: 16px; width: 100%; justify-content: flex-start; }
                }
            </style>

            <article class="voc-dept-single">
                <nav aria-label="Breadcrumb navigation">
                    <a href="' . esc_url($departments_url) . '" onclick="if(document.referrer && document.referrer.includes(window.location.hostname)) { window.history.back(); return false; }" class="voc-dept-back-btn">&larr; Back to All Departments</a>
                </nav>

                <header class="voc-dept-header-box voc-header-box">
                    <div class="voc-dept-info-side">
                        <div class="voc-dept-identity-flex">
                            <div class="voc-dept-graphic-wrap">
                                ' . $header_graphic . '
                            </div>
                            <div>
                                <span class="voc-dept-badge">Municipal Department</span>
                                <h1 class="voc-dept-main-title">' . esc_html($dept_title) . '</h1>
                                ' . (!empty($head_name) ? '<p class="voc-dept-head-lead"><strong>' . esc_html($head_title ? $head_title : 'Department Head') . ':</strong> ' . esc_html($head_name) . '</p>' : '') . '
                            </div>
                        </div>
                    </div>

                    <div class="voc-dept-contact-panel" aria-label="Department Contact Details">
                        ' . (!empty($phone) ? '
                        <div class="voc-dept-contact-row">
                            <div class="voc-dept-icon-badge" aria-hidden="true">' . villocity_get_icon('phone') . '</div>
                            <div>
                                <span style="display:block; font-size:11px; text-transform:uppercase; color:#64748b; font-weight:700;">Phone</span>
                                <a href="tel:' . preg_replace('/[^0-9+]/', '', $phone) . '" class="voc-dept-contact-link" aria-label="Call ' . esc_attr($dept_title) . ' at ' . esc_attr($phone) . '">' . esc_html($phone) . '</a>
                            </div>
                        </div>' : '') . '

                        ' . (!empty($email) ? '
                        <div class="voc-dept-contact-row">
                            <div class="voc-dept-icon-badge" aria-hidden="true">' . villocity_get_icon('mail') . '</div>
                            <div style="min-width:0; overflow:hidden;">
                                <span style="display:block; font-size:11px; text-transform:uppercase; color:#64748b; font-weight:700;">Email</span>
                                <a href="mailto:' . esc_attr($email) . '" class="voc-dept-contact-link" aria-label="Email ' . esc_attr($dept_title) . ' at ' . esc_attr($email) . '" style="display:block; text-overflow:ellipsis; overflow:hidden; white-space:nowrap;">' . esc_html($email) . '</a>
                            </div>
                        </div>' : '') . '

                        ' . (!empty($hours) ? '
                        <div class="voc-dept-contact-row">
                            <div class="voc-dept-icon-badge" aria-hidden="true">' . villocity_get_icon('clock') . '</div>
                            <div>
                                <span style="display:block; font-size:11px; text-transform:uppercase; color:#64748b; font-weight:700;">Hours</span>
                                <span>' . esc_html($hours) . '</span>
                            </div>
                        </div>' : '') . '

                        ' . (!empty($location) ? '
                        <div class="voc-dept-contact-row">
                            <div class="voc-dept-icon-badge" aria-hidden="true">' . villocity_get_icon('map-pin') . '</div>
                            <div>
                                <span style="display:block; font-size:11px; text-transform:uppercase; color:#64748b; font-weight:700;">Location</span>
                                <span>' . esc_html($location) . '</span>
                            </div>
                        </div>' : '') . '
                    </div>
                </header>

                <div class="voc-dept-content" style="font-size: 16px; line-height: 1.8; color: #334155;">
                    ' . $content . '
                </div>

                ' . $docs_html . '
                ' . $staff_html . '
            </article>';

            return $html;
        }

        return $content;
    }
}

// ==========================================
// 3. FRONTEND SHORTCODE: [villocity_departments]
// ==========================================
if ( ! function_exists( 'voc_render_departments_grid' ) ) {
    add_shortcode( 'villocity_departments', 'voc_render_departments_grid' );
    function voc_render_departments_grid( $atts ) {
        $query = new WP_Query( array(
            'post_type'      => 'voc_department',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'orderby'        => 'title',
            'order'          => 'ASC'
        ) );

        $html = '
        <style>
            .voc-dept-wrap { font-family: var(--voc-font-family, inherit) !important; color: var(--voc-text, inherit); max-width: 1200px; margin: 0 auto; }
            .voc-dept-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; }
            .voc-dept-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 24px; display: flex; flex-direction: column; transition: transform 0.2s ease, box-shadow 0.2s ease; box-shadow: 0 4px 15px rgba(0,0,0,0.03); }
            .voc-dept-card:hover { transform: translateY(-3px); box-shadow: 0 10px 25px rgba(0,0,0,0.06); }
            .voc-dept-title { font-size: 20px; font-weight: 800; color: var(--voc-primary); margin: 0 0 12px 0; }
            .voc-dept-meta { font-size: 13px; color: #475569; display: flex; flex-direction: column; gap: 8px; margin-bottom: 16px; }
            .voc-dept-meta-row { display: flex; align-items: center; gap: 6px; }
            .voc-dept-btn { background: var(--voc-primary); color: #ffffff !important; padding: 10px 20px; border-radius: 20px; font-size: 13px; font-weight: 700; text-align: center; text-decoration: none !important; margin-top: auto; }
        </style>

        <div class="voc-dept-wrap">
            <div class="voc-dept-grid">';

        if ( $query->have_posts() ) {
            while ( $query->have_posts() ) {
                $query->the_post();
                $id        = get_the_ID();
                $phone     = get_post_meta($id, '_dept_phone', true);
                $location  = get_post_meta($id, '_dept_location', true);
                $head_name = get_post_meta($id, '_dept_head_name', true);
                $permalink = get_permalink($id);

                $html .= '
                <article class="voc-dept-card">
                    <h2 class="voc-dept-title">' . esc_html(get_the_title()) . '</h2>
                    <div class="voc-dept-meta">
                        ' . (!empty($head_name) ? '<div class="voc-dept-meta-row">' . villocity_get_icon('user') . ' <span><strong>Contact:</strong> ' . esc_html($head_name) . '</span></div>' : '') . '
                        ' . (!empty($phone) ? '<div class="voc-dept-meta-row">' . villocity_get_icon('phone') . ' <span><strong>Phone:</strong> ' . esc_html($phone) . '</span></div>' : '') . '
                        ' . (!empty($location) ? '<div class="voc-dept-meta-row">' . villocity_get_icon('map-pin') . ' <span><strong>Office:</strong> ' . esc_html($location) . '</span></div>' : '') . '
                    </div>
                    <a href="' . esc_url($permalink) . '" class="voc-dept-btn" aria-label="View details for ' . esc_attr(get_the_title()) . '">Department Info & Services &rarr;</a>
                </article>';
            }
            wp_reset_postdata();
        } else {
            $html .= '<p>No departments listed at this time.</p>';
        }

        $html .= '</div></div>';
        return $html;
    }
}