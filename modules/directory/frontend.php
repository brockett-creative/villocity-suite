<?php
/**
 * Module Name: VilloCity Business Directory (Frontend)
 * Module URI:  https://villocity.org
 * Description: Public directory feed, Super Search filter engine, submission forms, single business renderer, and category grid cards.
 * Version:     1.6.5 (Super Search Deep Metadata & JS Attribute Expansion)
 * Author:      Brockett Creative Group, Inc.
 * Text Domain: villocity-suite
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

// ==========================================
// 1. SEO TAXONOMY TEMPLATE INJECTOR
// ==========================================
if ( ! function_exists( 'bcg_directory_tax_template' ) ) {
    add_filter('template_include', 'bcg_directory_tax_template', 99);
    function bcg_directory_tax_template($template) {
        if ( is_tax('bcg_business_category') ) {
            $term = get_queried_object();
            get_header();
            $primary_col = get_option('villocity_global_primary', '#860038');
            
            echo '<div style="max-width:1200px; margin: 60px auto; padding: 0 20px; font-family: var(--voc-font-family, inherit) !important;">';
            echo '<h1 style="font-size: 40px; font-weight: 800; color: ' . esc_attr($primary_col) . '; margin: 0 0 16px 0; line-height: 1.1; font-family: var(--voc-font-family, inherit) !important;">' . esc_html($term->name) . '</h1>';
            
            if ( !empty($term->description) ) {
                echo '<div style="font-size: 18px; color: #475569; margin: 0 0 32px 0; line-height: 1.6; max-width: 800px;">' . apply_filters('the_content', $term->description) . '</div>';
            }
            
            $safe_name = str_replace('"', '&quot;', $term->name);
            echo do_shortcode('[villocity_business_directory category="' . $safe_name . '" term_slug="' . $term->slug . '" hide_header="true"]');
            echo '</div>';
            get_footer();
            exit; 
        }
        return $template;
    }
}

// ==========================================
// 2. SINGLE BUSINESS TEMPLATE WITH ACTION BUTTON ICONS
// ==========================================
if ( ! function_exists( 'bcg_directory_single_page_template' ) ) {
    add_filter( 'the_content', 'bcg_directory_single_page_template' );
    function bcg_directory_single_page_template( $content ) {
        if ( is_singular( 'bcg_directory' ) && in_the_loop() && is_main_query() ) {
            $id       = get_the_ID();
            $category = str_replace(array('&amp;', '&#038;'), '&', wp_specialchars_decode(get_post_meta( $id, '_business_category', true ), ENT_QUOTES));
            $location = str_replace(array('&amp;', '&#038;'), '&', wp_specialchars_decode(get_post_meta( $id, '_business_location', true ), ENT_QUOTES));
            
            $address  = get_post_meta( $id, '_business_address', true );
            $phone    = get_post_meta( $id, '_business_phone', true );
            $hours    = get_post_meta( $id, '_business_hours', true );
            $website  = get_post_meta( $id, '_business_website_url', true );
            
            $prod_serv= get_post_meta( $id, '_business_product_service', true );
            $add_info = get_post_meta( $id, '_business_additional_info', true );
            $social   = get_post_meta( $id, '_business_social_link', true );
            $google_l = get_post_meta( $id, '_business_google_link', true );
            $loc_owned= get_post_meta( $id, '_business_locally_owned', true );
            $time_yr  = get_post_meta( $id, '_business_time_of_year', true );
            $seasonal = get_post_meta( $id, '_business_seasonal_dates', true );
            
            $default_img = get_option('bcg_directory_default_image', '');
            $img      = get_the_post_thumbnail_url( $id, 'large' ) ?: $default_img;
            $logo_id  = get_post_meta( $id, '_business_logo_id', true );
            $logo_url = $logo_id ? wp_get_attachment_url($logo_id) : '';

            $track_class = current_user_can( 'manage_options' ) ? '' : ' bcg-track-action';

            $primary_col = get_option('villocity_global_primary', '#860038');
            $text_col    = get_option('villocity_global_text', '#334155');
            
            $referer_url   = wp_get_referer();
            $directory_url = ($referer_url && strpos($referer_url, home_url()) !== false) 
                ? $referer_url 
                : home_url( '/business-directory/' );
                
            $map_url = 'https://www.google.com/maps/search/?api=1&query=' . urlencode($address);

            // Safe Icon Fetching Helper
            $phone_icon    = function_exists('villocity_get_icon') ? villocity_get_icon('phone') : '📞';
            $map_icon      = function_exists('villocity_get_icon') ? villocity_get_icon('map-pin') : '📍';
            $clock_icon    = function_exists('villocity_get_icon') ? villocity_get_icon('clock') : '🕒';
            $archive_icon  = function_exists('villocity_get_icon') ? villocity_get_icon('archive') : '📅';
            $tag_icon      = function_exists('villocity_get_icon') ? villocity_get_icon('tag') : '🏷️';
            $globe_icon    = function_exists('villocity_get_icon') ? villocity_get_icon('globe') : '🌐';
            $users_icon    = function_exists('villocity_get_icon') ? villocity_get_icon('users') : '👥';
            $ext_link_icon = function_exists('villocity_get_icon') ? villocity_get_icon('external-link') : '↗️';

            $custom_html = '';
            $ad_top    = get_option('bcg_directory_ad_top', '');
            $ad_bottom = get_option('bcg_directory_ad_bottom', '');

            if ( !empty($ad_top) ) {
                $custom_html .= '<div class="voc-ad-container voc-ad-top" style="max-width: 1200px; margin: 2em auto 20px auto; text-align: center;">' . do_shortcode(wp_unslash($ad_top)) . '</div>';
            }

            $custom_html .= '
            <style>
                h1.entry-title, h1.page-title, h1.post-title, .entry-title, .page-title, .post-title, header.entry-header { display: none !important; }
                .bcg-single-container { font-family: var(--voc-font-family, inherit) !important; color: ' . esc_attr($text_col) . '; max-width: 1200px; margin: 3em auto; position:relative; padding: 0 20px; box-sizing: border-box; }
                
                .bcg-back-btn { background: transparent !important; color: #64748b !important; padding: 8px 0 !important; font-weight: 600 !important; font-size: 14px !important; text-decoration: none !important; display: inline-flex !important; align-items: center !important; gap: 8px !important; border: none; transition: 0.2s; margin-bottom: 24px; text-transform: uppercase; letter-spacing: 0.05em; min-height: 44px; }
                .bcg-back-btn:hover { color: ' . esc_attr($primary_col) . ' !important; }
                
                .bcg-header-flex { display: flex; align-items: center; gap: 24px; flex-wrap: wrap; margin-bottom: 24px; }
                .bcg-single-logo { width: 110px; height: 110px; object-fit: contain; background: #ffffff; border: 1px solid #f1f5f9; border-radius: 20px; padding: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.04); flex-shrink: 0; }
                .bcg-single-title { font-size: 40px !important; font-weight: 800 !important; color: #0f172a !important; margin: 0 0 8px 0 !important; line-height: 1.15 !important; letter-spacing: -0.02em; font-family: var(--voc-font-family, inherit) !important; }
                
                .bcg-single-layout { display: grid; grid-template-columns: 1fr; gap: 40px; align-items: start; margin-top: 24px; }
                @media (min-width: 992px) { .bcg-single-layout { grid-template-columns: 1fr 400px; } }
                
                .bcg-featured-image { width: 100%; height: auto; max-height: 500px; object-fit: cover; border-radius: 20px; margin-bottom: 32px; box-shadow: 0 12px 30px rgba(0,0,0,0.05); border: 1px solid #e2e8f0; }
                .bcg-single-desc { font-size: 16px; line-height: 1.8; color: #475569; margin-bottom: 40px; }
                
                .bcg-sidebar-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 20px; padding: 32px; box-shadow: 0 10px 30px rgba(0,0,0,0.03); position: sticky; top: 40px; }
                .bcg-meta-list { display: flex; flex-direction: column; gap: 20px; margin-bottom: 28px; }
                .bcg-meta-item { display: flex; align-items: flex-start; gap: 12px; font-size: 15px; color: #334155; line-height: 1.5; }
                .bcg-meta-icon { width: 20px; height: 20px; color: ' . esc_attr($primary_col) . '; flex-shrink: 0; margin-top: 2px; display: flex; align-items: center; justify-content: center; }
                .bcg-meta-icon svg { width: 18px !important; height: 18px !important; stroke: currentColor !important; fill: none !important; }
                .bcg-meta-label { display: block; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #94a3b8; margin-bottom: 2px; }
                
                .bcg-btn-solid { background: ' . esc_attr($primary_col) . ' !important; color: #ffffff !important; padding: 14px 20px !important; border-radius: 12px !important; font-weight: 700 !important; font-size: 14px !important; text-decoration: none !important; display: flex !important; justify-content: center !important; align-items: center !important; gap: 8px !important; transition: 0.2s !important; border: none; text-align: center; min-height: 44px; }
                .bcg-btn-solid:hover { opacity: 0.92; transform: translateY(-1px); }
                
                .bcg-btn-outline { background: #f8fafc !important; color: #0f172a !important; border: 1px solid #cbd5e1 !important; padding: 14px 20px !important; border-radius: 12px !important; font-weight: 700 !important; font-size: 14px !important; text-decoration: none !important; display: flex !important; justify-content: center !important; align-items: center !important; gap: 8px !important; transition: 0.2s !important; text-align: center; min-height: 44px; }
                .bcg-btn-outline:hover { background: #f1f5f9 !important; border-color: #94a3b8 !important; }
                
                .bcg-btn-solid .villocity-icon, .bcg-btn-outline .villocity-icon { margin: 0 !important; }

                .bcg-info-block { background: #f8fafc; border-radius: 14px; padding: 20px; margin-top: 24px; border: 1px dashed #cbd5e1; }
                .bcg-map-frame { margin-top: 28px; border-radius: 16px; overflow: hidden; border: 1px solid #cbd5e1; height: 320px; width: 100%; }
            </style>

            <article class="bcg-single-container" aria-label="' . esc_attr(get_the_title()) . ' Profile" itemscope itemtype="https://schema.org/LocalBusiness">
                <nav aria-label="Breadcrumb">
                    <a href="' . esc_url($directory_url) . '" onclick="if(document.referrer && document.referrer.includes(window.location.hostname)) { window.history.back(); return false; }" class="bcg-back-btn">&larr; Back to Directory</a>
                </nav>

                <div class="bcg-header-flex">
                    ' . ($logo_url ? '<img src="' . esc_url($logo_url) . '" class="bcg-single-logo" alt="Logo for ' . esc_attr(get_the_title()) . '" itemprop="logo">' : '') . '
                    <div>
                        <h1 class="bcg-single-title" itemprop="name">' . esc_html(get_the_title()) . '</h1>
                        <div style="font-size:15px; color:#64748b; font-weight:500;">
                            <span itemprop="category">' . esc_html($category) . '</span>' . 
                            ($location ? ' &bull; <span itemprop="addressLocality">' . esc_html($location) . '</span>' : '') . 
                            ($loc_owned === 'yes' ? ' &bull; <strong style="color:#1e40af;">Locally Owned</strong>' : '') . 
                        '</div>
                    </div>
                </div>

                <div class="bcg-single-layout">
                    <div>
                        ' . ($img ? '<img src="' . esc_url($img) . '" class="bcg-featured-image" alt="Featured photo of ' . esc_attr(get_the_title()) . '" itemprop="image">' : '') . '
                        <div class="bcg-single-desc" itemprop="description">' . $content . '</div>
                    </div>
                    
                    <aside>
                        <div class="bcg-sidebar-card">
                            <div class="bcg-meta-list">
                                ' . (!empty($phone) ? '<div class="bcg-meta-item"><div class="bcg-meta-icon">' . $phone_icon . '</div><div><span class="bcg-meta-label">Phone</span><a href="tel:' . preg_replace('/[^0-9+]/', '', $phone) . '" itemprop="telephone" style="color:inherit; text-decoration:none; font-weight:600;">' . esc_html($phone) . '</a></div></div>' : '') . '
                                ' . (!empty($address) ? '<div class="bcg-meta-item"><div class="bcg-meta-icon">' . $map_icon . '</div><div><span class="bcg-meta-label">Address</span><span itemprop="address">' . esc_html($address) . '</span></div></div>' : '') . '
                                ' . (!empty($hours) ? '<div class="bcg-meta-item"><div class="bcg-meta-icon">' . $clock_icon . '</div><div><span class="bcg-meta-label">Hours of Operation</span><span itemprop="openingHours">' . esc_html($hours) . '</span></div></div>' : '') . '
                                ' . (!empty($time_yr) ? '<div class="bcg-meta-item"><div class="bcg-meta-icon">' . $archive_icon . '</div><div><span class="bcg-meta-label">Availability</span>' . esc_html($time_yr) . ($time_yr === 'Seasonal' && !empty($seasonal) ? ' (' . esc_html($seasonal) . ')' : '') . '</div></div>' : '') . '
                                ' . (!empty($prod_serv) ? '<div class="bcg-meta-item"><div class="bcg-meta-icon">' . $tag_icon . '</div><div><span class="bcg-meta-label">Products & Services</span>' . esc_html($prod_serv) . '</div></div>' : '') . '
                            </div>
                            
                            <div style="display:flex; flex-direction:column; gap:10px;">
                                ' . (!empty($website) ? '<a href="' . esc_url($website) . '" target="_blank" rel="nofollow ugc noopener noreferrer" itemprop="url" class="bcg-btn-solid ' . $track_class . '" data-id="' . esc_attr($id) . '">' . $globe_icon . ' Visit Official Website</a>' : '') . '
                                ' . (!empty($social) ? '<a href="' . esc_url($social) . '" target="_blank" rel="nofollow ugc noopener noreferrer" class="bcg-btn-outline ' . $track_class . '" data-id="' . esc_attr($id) . '">' . $users_icon . ' Social Media Link</a>' : '') . '
                                ' . (!empty($google_l) ? '<a href="' . esc_url($google_l) . '" target="_blank" rel="nofollow ugc noopener noreferrer" class="bcg-btn-outline ' . $track_class . '" data-id="' . esc_attr($id) . '">' . $ext_link_icon . ' Google Business Profile</a>' : '') . '
                                ' . (!empty($address) ? '<a href="' . esc_url($map_url) . '" target="_blank" rel="noopener noreferrer" class="bcg-btn-outline ' . $track_class . '" data-id="' . esc_attr($id) . '">' . $map_icon . ' Get Directions</a>' : '') . '
                            </div>';

                            if (!empty($add_info)) {
                                $custom_html .= '
                                <div class="bcg-info-block">
                                    <span class="bcg-meta-label" style="color:' . esc_attr($primary_col) . ';">Additional Information</span>
                                    <div style="font-size:14px; color:#475569; line-height:1.6;">' . nl2br(esc_html($add_info)) . '</div>
                                </div>';
                            }

                            if (!empty($address)) {
                                $custom_html .= '
                                <div class="bcg-map-frame">
                                    <iframe width="100%" height="100%" frameborder="0" scrolling="no" marginheight="0" marginwidth="0" src="https://maps.google.com/maps?q=' . urlencode($address . (!empty($location) && strpos(strtolower($address), strtolower($location)) === false ? ', ' . $location : '')) . '&t=m&z=15&output=embed&iwloc=near" aria-label="Google Map location for ' . esc_attr(get_the_title()) . '"></iframe>
                                </div>';
                            }

            $custom_html .= '
                        </div>
                    </aside>
                </div>
            </article>';

            if ( !empty($ad_bottom) ) {
                $custom_html .= '<div class="voc-ad-container voc-ad-bottom" style="max-width: 1200px; margin: 40px auto 2em auto; text-align: center;">' . do_shortcode(wp_unslash($ad_bottom)) . '</div>';
            }

            if ( shortcode_exists( 'villocity_restaurant_menu' ) ) {
                $linked_menu_html = do_shortcode('[villocity_restaurant_menu business_id="' . $id . '"]');
                if ( strpos($linked_menu_html, 'No menu items') === false ) {
                    $custom_html .= '<div style="max-width: 1200px; margin: 40px auto 0 auto; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 20px; padding: 32px; box-sizing: border-box;">
                        <h2 style="font-size: 24px; font-weight: 800; color: ' . esc_attr($primary_col) . '; margin-top: 0; font-family: var(--voc-font-family, inherit) !important;">Digital Restaurant Menu</h2>' 
                        . $linked_menu_html . 
                    '</div>';
                }
            }

            return $custom_html;
        }

        return $content;
    }
}

// ==========================================
// 3. FRONT-END SUBMISSION HANDLER
// ==========================================
if ( ! function_exists( 'bcg_handle_business_submission' ) ) {
    add_action( 'admin_post_nopriv_bcg_submit_business', 'bcg_handle_business_submission' );
    add_action( 'admin_post_bcg_submit_business', 'bcg_handle_business_submission' );

    function bcg_handle_business_submission() {
        if ( ! isset( $_POST['bcg_submit_nonce'] ) || ! wp_verify_nonce( $_POST['bcg_submit_nonce'], 'bcg_submit_action' ) ) {
            wp_die( 'Security check failed.' );
        }

        $title    = sanitize_text_field( $_POST['business_title'] );
        $desc     = sanitize_textarea_field( $_POST['business_desc'] );
        $category = sanitize_text_field( $_POST['business_category'] );
        $location = sanitize_text_field( $_POST['business_location'] );
        $address  = sanitize_text_field( $_POST['business_address'] );
        $phone    = sanitize_text_field( $_POST['business_phone'] );
        $email    = sanitize_email( $_POST['business_email'] );

        $post_id = wp_insert_post( array( 'post_title' => $title, 'post_content' => $desc, 'post_type' => 'bcg_directory', 'post_status' => 'pending' ) );

        if ( ! is_wp_error( $post_id ) ) {
            update_post_meta( $post_id, '_business_category', $category );
            wp_set_object_terms( $post_id, $category, 'bcg_business_category' );
            update_post_meta( $post_id, '_business_location', $location );
            update_post_meta( $post_id, '_business_address', $address );
            update_post_meta( $post_id, '_business_phone', $phone );
            update_post_meta( $post_id, '_business_email', $email );

            $referer_url = wp_get_referer();
            $base_url = $referer_url ? strtok($referer_url, '?') : home_url();
            wp_redirect( add_query_arg( 'listing_success', '1', $base_url ) );
            exit;
        }
    }
}

// ==========================================
// 4. DIRECTORY GRID SHORTCODE (SUPER SEARCH ENHANCED)
// ==========================================
if ( ! function_exists( 'bcg_render_business_directory' ) ) {
    function bcg_render_business_directory($atts = array()) {
        $shortcode_args = shortcode_atts( array(
            'category'    => 'all',
            'term_slug'   => '',
            'hide_header' => 'false'
        ), $atts );

        $primary_col = get_option('villocity_global_primary', '#860038');
        $accent_col  = get_option('villocity_global_accent', '#fadceb');
        $default_img = get_option('bcg_directory_default_image', '');

        $is_category_view = ( is_tax('bcg_business_category') || ! empty($shortcode_args['term_slug']) || ($shortcode_args['category'] !== 'all') );

        $query_args = array(
            'post_type'        => 'bcg_directory',
            'post_status'      => 'publish',
            'posts_per_page'   => -1,
            'suppress_filters' => true,
            'no_found_rows'    => true
        );

        if ( ! empty( $shortcode_args['term_slug'] ) ) {
            $query_args['tax_query'] = array(
                array(
                    'taxonomy' => 'bcg_business_category',
                    'field'    => 'slug',
                    'terms'    => sanitize_title( $shortcode_args['term_slug'] ),
                )
            );
        }

        $directory_query = new WP_Query( $query_args );
        $posts_array = array();

        if ( $directory_query->have_posts() ) {
            $posts_array = $directory_query->posts;

            usort( $posts_array, function( $a, $b ) {
                $feat_a = get_post_meta( $a->ID, '_business_featured', true ) === 'yes' ? 1 : 0;
                $feat_b = get_post_meta( $b->ID, '_business_featured', true ) === 'yes' ? 1 : 0;
                if ( $feat_a !== $feat_b ) {
                    return $feat_b <=> $feat_a;
                }
                return strcmp( $a->post_title, $b->post_title );
            });
        }

        $categories = get_terms( array(
            'taxonomy'   => 'bcg_business_category',
            'hide_empty' => true,
        ) );

        ob_start();
        ?>
        <style>
            .bcg-dir-wrap { font-family: var(--voc-font-family, inherit) !important; color: #1c1917; max-width: 1200px; margin: 0 auto; box-sizing: border-box; }
            .bcg-dir-wrap * { box-sizing: border-box; }
            .bcg-dir-wrap .bcg-search-bar { background: #f8fafc; padding: 24px; border-radius: 16px; margin-bottom: 32px; border: 1px solid #e2e8f0; display: flex; gap: 16px; flex-wrap: wrap; }
            .bcg-dir-wrap .bcg-search-input-wrap { flex: 2; min-width: 250px; display: flex; align-items: center; background: #fff; border: 1px solid #cbd5e1; border-radius: 10px; padding: 12px 16px; }
            .bcg-dir-wrap .bcg-search-input-wrap input { border: none !important; outline: none !important; width: 100% !important; background: transparent !important; font-size: 15px !important; box-shadow: none !important; height: auto !important; }
            .bcg-dir-wrap .bcg-search-select-wrap { flex: 1; min-width: 200px; display: flex; align-items: center; background: #fff; border: 1px solid #cbd5e1; border-radius: 10px; padding: 0 12px; }
            .bcg-dir-wrap .bcg-search-select-wrap select { border: none !important; outline: none !important; width: 100% !important; background: transparent !important; font-size: 15px !important; box-shadow: none !important; height: 46px !important; color: #334155; font-weight: 500; cursor: pointer; }

            .bcg-dir-wrap .bcg-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 24px; }
            .bcg-dir-wrap .bcg-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; display: flex; flex-direction: column; transition: transform 0.2s, box-shadow 0.2s; text-decoration: none !important; color: inherit !important; position: relative; }
            .bcg-dir-wrap .bcg-card:hover { transform: translateY(-4px); box-shadow: 0 10px 25px rgba(0,0,0,0.05); }
            .bcg-dir-wrap .bcg-card-img-wrap { width: 100%; height: 200px; background: #f1f5f9; position: relative; overflow: hidden; flex-shrink: 0; }
            .bcg-dir-wrap .bcg-card-img { width: 100% !important; height: 100% !important; object-fit: cover !important; position: absolute; top:0; left:0; margin:0 !important; padding:0 !important; }
            .bcg-dir-wrap .bcg-card-body { padding: 20px; display: flex; flex-direction: column; flex-grow: 1; position: relative; z-index: 2; }
            .bcg-dir-wrap .bcg-card-title { font-size: 20px !important; font-weight: 700 !important; margin: 0 0 8px 0 !important; color: <?php echo esc_attr($primary_col); ?> !important; font-family: var(--voc-font-family, inherit) !important; line-height: 1.3 !important; }
            .bcg-dir-wrap .bcg-badge { background: <?php echo esc_attr($accent_col); ?>; color: <?php echo esc_attr($primary_col); ?>; font-size: 12px; font-weight: 700; padding: 6px 14px; border-radius: 20px; display: inline-flex; align-items: center; margin-bottom: 12px; gap: 6px; align-self: flex-start; }
            .bcg-dir-wrap .bcg-card.bcg-featured { grid-column: 1 / -1; border: 2px solid <?php echo esc_attr($primary_col); ?>; }
            @media (min-width: 768px) {
                .bcg-dir-wrap .bcg-card.bcg-featured { flex-direction: row; }
                .bcg-dir-wrap .bcg-card.bcg-featured .bcg-card-img-wrap { width: 45%; height: auto; min-height: 260px; }
                .bcg-dir-wrap .bcg-card.bcg-featured .bcg-card-body { width: 55%; padding: 32px; }
            }
        </style>

        <div class="bcg-dir-wrap" role="region" aria-label="Local Business Directory Grid">
            <div class="bcg-search-bar" role="search" aria-label="Search Business Directory">
                <div class="bcg-search-input-wrap">
                    <span style="margin-right:10px; display:flex;"><?php echo function_exists('villocity_get_icon') ? villocity_get_icon('search') : '🔍'; ?></span>
                    <input type="text" id="bcg-dir-search-input" placeholder="Search by business name, category, or keywords..." aria-label="Search business directory by keyword">
                </div>

                <?php if ( ! is_wp_error( $categories ) && ! empty( $categories ) ) : ?>
                <div class="bcg-search-select-wrap">
                    <select id="bcg-dir-category-select" aria-label="Filter directory by category">
                        <option value="<?php echo esc_url(home_url('/business-directory/')); ?>">All Categories</option>
                        <?php foreach ( $categories as $cat ) : 
                            $is_selected = ( ! empty($shortcode_args['term_slug']) && $shortcode_args['term_slug'] === $cat->slug ) || is_tax('bcg_business_category', $cat->term_id);
                        ?>
                            <option value="<?php echo esc_url( get_term_link( $cat ) ); ?>" <?php selected($is_selected, true); ?>><?php echo esc_html( $cat->name ); ?> (<?php echo $cat->count; ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
            </div>

            <div class="bcg-grid" id="bcg-directory-grid">
            <?php
            $rendered_ids = array();

            if ( ! empty( $posts_array ) ) :
                foreach ( $posts_array as $p ) :
                    $id = $p->ID;

                    if ( in_array( $id, $rendered_ids, true ) ) {
                        continue;
                    }
                    $rendered_ids[] = $id;

                    $title       = $p->post_title;
                    $permalink   = get_permalink($id);
                    $category    = get_post_meta( $id, '_business_category', true );
                    $location    = get_post_meta( $id, '_business_location', true );
                    $tags        = get_post_meta( $id, '_business_tags', true );
                    $phone       = get_post_meta( $id, '_business_phone', true );
                    $address     = get_post_meta( $id, '_business_address', true );
                    
                    // DEEP SEARCH METADATA EXTRACTION
                    $prod_serv   = get_post_meta( $id, '_business_product_service', true );
                    $add_info    = get_post_meta( $id, '_business_additional_info', true );
                    $keywords    = get_post_meta( $id, '_business_keywords', true );
                    $tagline     = get_post_meta( $id, '_business_tagline', true );
                    $post_body   = $p->post_content;

                    $is_featured = get_post_meta( $id, '_business_featured', true ) === 'yes';
                    $img         = get_the_post_thumbnail_url( $id, 'medium_large' ) ?: $default_img;

                    // Expanded search data string for instant JS filtering
                    $search_data = esc_attr( strtolower( implode( ' ', array_filter( array(
                        $title,
                        $category,
                        $location,
                        $tags,
                        $keywords,
                        $tagline,
                        $prod_serv,
                        $add_info,
                        $address,
                        $post_body
                    ) ) ) ) );

                    $badge_html = '';
                    if ( $is_featured ) {
                        $star_icon  = function_exists('villocity_get_icon') ? villocity_get_icon('star') : '★';
                        $feat_label = $is_category_view ? 'Featured' : 'Featured &bull; ' . esc_html($category);
                        $badge_html = '<span class="bcg-badge">' . $star_icon . ' ' . $feat_label . '</span>';
                    } elseif ( ! $is_category_view && ! empty($category) ) {
                        $tag_icon   = function_exists('villocity_get_icon') ? villocity_get_icon('tag') : '';
                        $badge_html = '<span class="bcg-badge">' . $tag_icon . ' ' . esc_html($category) . '</span>';
                    }
                    ?>
                    <a href="<?php echo esc_url($permalink); ?>" class="bcg-card<?php echo $is_featured ? ' bcg-featured' : ''; ?>" data-search="<?php echo $search_data; ?>" itemscope itemtype="https://schema.org/LocalBusiness">
                        <?php if ($img) : ?>
                            <div class="bcg-card-img-wrap"><img src="<?php echo esc_url($img); ?>" class="bcg-card-img" alt="<?php echo esc_attr($title); ?>" itemprop="image"></div>
                        <?php endif; ?>
                        <div class="bcg-card-body">
                            <?php echo $badge_html; ?>
                            <h3 class="bcg-card-title" itemprop="name"><?php echo esc_html($title); ?></h3>
                            <?php if ($is_featured) : ?>
                                <div style="font-size:13px; color:#475569; margin-bottom:12px; display:flex; flex-direction:column; gap:4px;">
                                    <?php if (!empty($phone)) echo '<div>' . (function_exists('villocity_get_icon') ? villocity_get_icon('phone') : '') . ' <span itemprop="telephone">' . esc_html($phone) . '</span></div>'; ?>
                                    <?php if (!empty($address)) echo '<div>' . (function_exists('villocity_get_icon') ? villocity_get_icon('map-pin') : '') . ' <span itemprop="address">' . esc_html($address) . '</span></div>'; ?>
                                </div>
                            <?php endif; ?>
                            <p style="font-size:14px; color:#475569; margin-bottom:12px;" itemprop="description"><?php echo esc_html(wp_trim_words($p->post_content, 22, '...')); ?></p>
                            <div style="color:#1e40af; font-weight:700; font-size:13px; margin-top:auto;">VIEW PROFILE &rarr;</div>
                        </div>
                    </a>
                <?php
                endforeach;
            else :
                echo '<p style="padding:20px; color:#64748b;">No business listings currently found.</p>';
            endif;
            ?>
            </div>
        </div>

        <script>
        document.addEventListener("DOMContentLoaded", function() {
            const searchInput = document.getElementById("bcg-dir-search-input");
            const categorySelect = document.getElementById("bcg-dir-category-select");
            const cards = document.querySelectorAll("#bcg-directory-grid .bcg-card");

            if (categorySelect) {
                categorySelect.addEventListener("change", function() {
                    const targetUrl = this.value;
                    if (targetUrl) {
                        window.location.href = targetUrl;
                    }
                });
            }

            function filterListings() {
                const term = searchInput ? searchInput.value.toLowerCase().trim() : "";

                cards.forEach(card => {
                    const searchData = card.getAttribute("data-search") || "";
                    if (term === "" || searchData.includes(term)) {
                        card.style.display = "flex";
                    } else {
                        card.style.display = "none";
                    }
                });
            }

            if (searchInput && cards.length > 0) {
                searchInput.addEventListener("input", filterListings);
            }
        });
        </script>
        <?php
        return ob_get_clean();
    }
}

// ==========================================
// 5. HOMEPAGE CATEGORY CARDS SHORTCODE
// ==========================================
if ( ! function_exists( 'bcg_render_directory_category_cards' ) ) {
    function bcg_render_directory_category_cards( $atts = array() ) {
        $primary_col = get_option('villocity_global_primary', '#860038');
        
        $terms = get_terms( array( 
            'taxonomy'   => 'bcg_business_category', 
            'hide_empty' => true 
        ) ); 
        
        if ( is_wp_error( $terms ) || empty( $terms ) ) {
            return '<p style="text-align:center; color:#64748b;">No categories available.</p>';
        }

        ob_start();
        ?>
        <style>
            .bcg-app-cards-wrap { 
                display: grid; 
                grid-template-columns: repeat(2, 1fr); 
                gap: 16px; 
                font-family: var(--voc-font-family, inherit) !important; 
                margin: 0 auto; 
                max-width: 1200px; 
                box-sizing: border-box; 
            }
            @media (min-width: 480px) { .bcg-app-cards-wrap { grid-template-columns: repeat(3, 1fr); gap: 20px; } }
            @media (min-width: 768px) { .bcg-app-cards-wrap { grid-template-columns: repeat(4, 1fr); gap: 20px; } }
            
            .bcg-app-card { 
                background: #ffffff; 
                border: 1px solid #e2e8f0; 
                border-radius: 16px; 
                padding: 24px 16px; 
                text-align: center; 
                text-decoration: none !important; 
                display: flex; 
                flex-direction: column; 
                align-items: center; 
                transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1); 
                box-shadow: 0 2px 6px rgba(0, 0, 0, 0.02); 
                box-sizing: border-box; 
                position: relative;
            }
            .bcg-app-card:hover, .bcg-app-card:focus-visible { 
                transform: translateY(-4px); 
                border-color: <?php echo esc_attr($primary_col); ?>; 
                box-shadow: 0 12px 24px -6px rgba(134, 0, 56, 0.12); 
                outline: none; 
            }
            
            /* Soft Circular Icon Badge */
            .bcg-app-card-icon { 
                width: 54px; 
                height: 54px; 
                border-radius: 50%; 
                background-color: rgba(134, 0, 56, 0.06); 
                color: <?php echo esc_attr($primary_col); ?>; 
                margin-bottom: 14px; 
                transition: all 0.25s ease; 
                display: flex; 
                justify-content: center; 
                align-items: center; 
            }
            .bcg-app-card-icon i, .bcg-app-card-icon svg { 
                font-size: 22px; 
            }
            .bcg-app-card:hover .bcg-app-card-icon { 
                background-color: <?php echo esc_attr($primary_col); ?>; 
                color: #ffffff; 
                transform: scale(1.05);
            }
            
            .bcg-app-card-title { 
                font-size: 16px !important; 
                font-weight: 800 !important; 
                color: #0f172a !important; 
                line-height: 1.25 !important; 
                margin: 0 0 4px 0 !important; 
                font-family: var(--voc-font-family, inherit) !important; 
            }
            .bcg-app-card-count { 
                font-size: 13px; 
                color: #64748b; 
                font-weight: 500; 
                margin-bottom: 16px; 
            }
            
            .bcg-visit-link { 
                font-size: 13px; 
                font-weight: 700; 
                color: <?php echo esc_attr($primary_col); ?>; 
                display: inline-flex; 
                align-items: center; 
                gap: 4px; 
                margin-top: auto; 
                transition: gap 0.2s ease;
            }
            .bcg-app-card:hover .bcg-visit-link { 
                gap: 8px; 
            }

            /* Fix Mobile Ad Overlap */
            .bcg-app-card-ad { 
                background: transparent !important; 
                border: none !important; 
                box-shadow: none !important; 
                display: flex; 
                flex-direction: column; 
                align-items: center; 
                justify-content: center; 
                min-height: 250px !important; 
                padding: 12px 0; 
                box-sizing: border-box; 
                overflow: visible !important; 
                text-align: center; 
                grid-column: 1 / -1; /* Spans full grid width on mobile to avoid card collisions */
            }
            @media (min-width: 768px) {
                .bcg-app-card-ad {
                    grid-column: auto; /* Restores normal single-card slot in 4-column desktop layout */
                }
            }
            .bcg-app-card-ad img { max-width: 100% !important; height: auto !important; border-radius: 0 !important; }
            .bcg-app-card-ad iframe { min-width: 120px !important; min-height: 240px !important; border-radius: 0 !important; }
        </style>

        <div class="bcg-app-cards-wrap" role="region" aria-label="Business Directory Categories">
        <?php
        foreach ( $terms as $term ) {
            $name       = esc_html(str_replace(array('&amp;', '&#038;'), '&', wp_specialchars_decode($term->name, ENT_QUOTES)));
            $link       = esc_url(get_term_link($term));
            $count      = $term->count;
            $count_text = $count . ' Listing' . ($count !== 1 ? 's' : '');
            
            // Fetch category icon saved in WP Admin term meta
            $saved_icon = get_term_meta( $term->term_id, 'category_icon', true );
            
            if ( ! empty( $saved_icon ) ) {
                $icon_html = '<i class="' . esc_attr( $saved_icon ) . '"></i>';
            } else {
                // Default FontAwesome icon per slug keyword
                $slug = $term->slug;
                $fa_class = 'fa-solid fa-tag';
                
                if ( strpos($slug, 'apparel') !== false || strpos($slug, 'accessory') !== false ) { $fa_class = 'fa-solid fa-shirt'; }
                elseif ( strpos($slug, 'art') !== false || strpos($slug, 'entertainment') !== false || strpos($slug, 'recreation') !== false ) { $fa_class = 'fa-solid fa-palette'; }
                elseif ( strpos($slug, 'church') !== false ) { $fa_class = 'fa-solid fa-church'; }
                elseif ( strpos($slug, 'community') !== false || strpos($slug, 'education') !== false ) { $fa_class = 'fa-solid fa-graduation-cap'; }
                elseif ( strpos($slug, 'dining') !== false || strpos($slug, 'food') !== false || strpos($slug, 'drink') !== false ) { $fa_class = 'fa-solid fa-utensils'; }
                elseif ( strpos($slug, 'farm') !== false ) { $fa_class = 'fa-solid fa-tractor'; }
                elseif ( strpos($slug, 'gift') !== false || strpos($slug, 'antique') !== false ) { $fa_class = 'fa-solid fa-gift'; }
                elseif ( strpos($slug, 'grocery') !== false || strpos($slug, 'convenience') !== false ) { $fa_class = 'fa-solid fa-basket-shopping'; }
                elseif ( strpos($slug, 'health') !== false || strpos($slug, 'wellness') !== false || strpos($slug, 'beauty') !== false ) { $fa_class = 'fa-solid fa-heart-pulse'; }
                elseif ( strpos($slug, 'home') !== false || strpos($slug, 'service') !== false ) { $fa_class = 'fa-solid fa-wrench'; }
                elseif ( strpos($slug, 'lodging') !== false || strpos($slug, 'travel') !== false ) { $fa_class = 'fa-solid fa-hotel'; }
                elseif ( strpos($slug, 'professional') !== false ) { $fa_class = 'fa-solid fa-briefcase'; }
                elseif ( strpos($slug, 'real-estate') !== false || strpos($slug, 'property') !== false ) { $fa_class = 'fa-solid fa-house'; }

                $icon_html = '<i class="' . esc_attr($fa_class) . '"></i>';
            }
            ?>
            <a href="<?php echo $link; ?>" class="bcg-app-card" aria-label="View <?php echo $name; ?> directory category">
                <div class="bcg-app-card-icon"><?php echo $icon_html; ?></div>
                <h3 class="bcg-app-card-title"><?php echo $name; ?></h3>
                <span class="bcg-app-card-count"><?php echo $count_text; ?></span>
                <div class="bcg-visit-link">Visit Guide &rarr;</div>
            </a>
            <?php
        }
        
        $grid_ad_raw = get_option('bcg_directory_ad_grid', '');
        if ( ! empty( $grid_ad_raw ) && ! empty( trim( $grid_ad_raw ) ) ) {
            $parsed_ad = do_shortcode( wp_unslash( $grid_ad_raw ) );
            if ( ! empty( trim( $parsed_ad ) ) ) {
                echo '<div class="bcg-app-card-ad">' . $parsed_ad . '</div>';
            }
        }
        ?>
        </div>
        <?php
        return ob_get_clean();
    }
}

// Register shortcode variants
add_action('init', function() {
    add_shortcode( 'villocity_business_directory', 'bcg_render_business_directory' );
    add_shortcode( 'villocity_directory', 'bcg_render_business_directory' );
    add_shortcode( 'villocity_directory_categories', 'bcg_render_directory_category_cards' );
});

// ==========================================
// 6. AUTO-INJECT DIRECTORY CONTENT ON PAGE
// ==========================================
if ( ! function_exists( 'voc_directory_inject_page_content' ) ) {
    add_filter( 'the_content', 'voc_directory_inject_page_content', 10 );
    function voc_directory_inject_page_content( $content ) {
        if ( is_singular( 'page' ) && in_the_loop() && is_main_query() ) {
            $directory_page_id = get_option( 'voc_directory_page_id' );

            if ( (int) get_the_ID() === (int) $directory_page_id || is_page( 'business-directory' ) ) {
                if ( function_exists( 'bcg_render_business_directory' ) ) {
                    return bcg_render_business_directory( array() );
                }
            }
        }

        return $content;
    }
}

// ==========================================
// 7. FEATURED BUSINESS SPOTLIGHT SHORTCODE
// Shortcode: [villocity_featured_business]
// Options: mode="rotate|random|single", id="", interval="6000"
// ==========================================
add_shortcode( 'villocity_featured_business', 'villocity_render_featured_business_shortcode' );
function villocity_render_featured_business_shortcode( $atts = array() ) {
    $primary_col = get_option( 'villocity_global_primary', '#860038' );
    $accent_col  = get_option( 'villocity_global_accent', '#fadceb' );
    $text_col    = get_option( 'villocity_global_text', '#1c1917' );

    $atts = shortcode_atts( array(
        'mode'     => 'rotate', // 'rotate', 'random', or 'single'
        'id'       => '',       // Specific post ID if mode="single"
        'interval' => '6000',   // Rotation speed in milliseconds
        'title'    => 'Featured Local Merchant'
    ), $atts, 'villocity_featured_business' );

    // 1. Query Featured Businesses
    $query_args = array(
        'post_type'      => 'bcg_directory',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'meta_query'     => array(
            array(
                'key'     => '_business_featured',
                'value'   => 'yes',
                'compare' => '='
            )
        )
    );

    if ( ! empty( $atts['id'] ) ) {
        $query_args['p'] = intval( $atts['id'] );
    } elseif ( 'random' === $atts['mode'] ) {
        $query_args['orderby']        = 'rand';
        $query_args['posts_per_page'] = 1;
    }

    $featured_query = new WP_Query( $query_args );

    if ( ! $featured_query->have_posts() ) {
        return ''; // Quietly exit if no featured listings exist
    }

    $featured_posts = $featured_query->posts;
    $unique_id = 'voc-featured-spotlight-' . rand(1000, 9999);

    ob_start();
    ?>
    <style>
        .voc-spotlight-card-wrap {
            background: #ffffff;
            border: 2px solid <?php echo esc_attr( $accent_col ); ?>;
            border-radius: 20px;
            padding: 24px;
            margin-bottom: 32px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.04);
            font-family: var(--voc-font-family, inherit) !important;
            position: relative;
            overflow: hidden;
        }
        .voc-spotlight-badge {
            background: <?php echo esc_attr( $primary_col ); ?>;
            color: #ffffff;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            padding: 4px 12px;
            border-radius: 20px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin-bottom: 16px;
        }
        .voc-spotlight-slide {
            display: none;
            grid-template-columns: minmax(120px, 180px) 1fr;
            gap: 24px;
            align-items: center;
            transition: opacity 0.4s ease-in-out;
        }
        .voc-spotlight-slide.is-active {
            display: grid;
        }
        .voc-spotlight-logo-box {
            width: 100%;
            height: 140px;
            background: #f8fafc;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 12px;
            border: 1px solid #e2e8f0;
            box-sizing: border-box;
        }
        .voc-spotlight-logo-box img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }
        .voc-spotlight-content h3 {
            margin: 0 0 6px 0 !important;
            font-size: 22px !important;
            font-weight: 800 !important;
            color: #0f172a !important;
        }
        .voc-spotlight-content h3 a {
            color: inherit !important;
            text-decoration: none !important;
        }
        .voc-spotlight-content h3 a:hover,
        .voc-spotlight-content h3 a:focus-visible {
            color: <?php echo esc_attr( $primary_col ); ?> !important;
            text-decoration: underline !important;
        }
        .voc-spotlight-meta {
            font-size: 14px;
            color: #64748b;
            margin-bottom: 12px;
            display: flex;
            gap: 16px;
            flex-wrap: wrap;
        }
        .voc-spotlight-desc {
            font-size: 14px;
            color: #475569;
            line-height: 1.5;
            margin-bottom: 16px;
        }
        .voc-spotlight-actions {
            display: flex;
            gap: 12px;
            align-items: center;
            flex-wrap: wrap;
        }

        @media (max-width: 640px) {
            .voc-spotlight-slide {
                grid-template-columns: 1fr;
                text-align: center;
            }
            .voc-spotlight-meta, .voc-spotlight-actions {
                justify-content: center;
            }
        }
    </style>

    <div class="voc-spotlight-card-wrap" id="<?php echo esc_attr( $unique_id ); ?>" aria-label="<?php echo esc_attr( $atts['title'] ); ?>">
        <div class="voc-spotlight-badge">
            <?php echo function_exists('villocity_get_icon') ? villocity_get_icon('star') : '★'; ?>
            <?php echo esc_html( $atts['title'] ); ?>
        </div>

        <div class="voc-spotlight-slides-container">
            <?php foreach ( $featured_posts as $index => $post_item ) : 
                $post_id   = $post_item->ID;
                $category  = get_post_meta( $post_id, '_business_category', true );
                $address   = get_post_meta( $post_id, '_business_address', true );
                $phone     = get_post_meta( $post_id, '_business_phone', true );
                $website   = get_post_meta( $post_id, '_business_website_url', true );
                $prod_serv = get_post_meta( $post_id, '_business_product_service', true );
                $thumb_id  = get_post_thumbnail_id( $post_id );
                $thumb_url = $thumb_id ? wp_get_attachment_image_url( $thumb_id, 'medium' ) : get_option('bcg_directory_default_image', '');
            ?>
                <div class="voc-spotlight-slide <?php echo 0 === $index ? 'is-active' : ''; ?>" data-slide-index="<?php echo esc_attr( $index ); ?>">
                    <div class="voc-spotlight-logo-box">
                        <?php if ( ! empty( $thumb_url ) ) : ?>
                            <img src="<?php echo esc_url( $thumb_url ); ?>" alt="<?php echo esc_attr( $post_item->post_title ); ?> Logo" loading="lazy">
                        <?php else : ?>
                            <span style="font-size:32px; font-weight:800; color:<?php echo esc_attr($primary_col); ?>;"><?php echo esc_html( substr($post_item->post_title, 0, 1) ); ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="voc-spotlight-content">
                        <h3>
                            <a href="<?php echo esc_url( get_permalink( $post_id ) ); ?>"><?php echo esc_html( $post_item->post_title ); ?></a>
                        </h3>
                        <div class="voc-spotlight-meta">
                            <?php if ( ! empty( $category ) ) : ?>
                                <span><strong>Category:</strong> <?php echo esc_html( $category ); ?></span>
                            <?php endif; ?>
                            <?php if ( ! empty( $phone ) ) : ?>
                                <span><strong>Phone:</strong> <?php echo esc_html( $phone ); ?></span>
                            <?php endif; ?>
                        </div>
                        <?php if ( ! empty( $prod_serv ) ) : ?>
                            <p class="voc-spotlight-desc"><?php echo esc_html( wp_trim_words( $prod_serv, 20, '...' ) ); ?></p>
                        <?php endif; ?>

                        <div class="voc-spotlight-actions">
                            <a href="<?php echo esc_url( get_permalink( $post_id ) ); ?>" class="voc-gemini-btn-primary" aria-label="View <?php echo esc_attr( $post_item->post_title ); ?> profile">
                                View Listing &rarr;
                            </a>
                            <?php if ( ! empty( $website ) ) : ?>
                                <a href="<?php echo esc_url( $website ); ?>" target="_blank" rel="noopener" class="voc-gemini-btn-secondary" aria-label="Visit <?php echo esc_attr( $post_item->post_title ); ?> website">
                                    Visit Website
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <?php if ( 'rotate' === $atts['mode'] && count( $featured_posts ) > 1 ) : ?>
    <script>
    document.addEventListener("DOMContentLoaded", function() {
        const wrap = document.getElementById("<?php echo esc_js( $unique_id ); ?>");
        if (!wrap) return;

        const slides = wrap.querySelectorAll(".voc-spotlight-slide");
        let currentIndex = 0;
        const intervalTime = <?php echo intval( $atts['interval'] ); ?>;

        function showNextSlide() {
            slides[currentIndex].classList.remove("is-active");
            currentIndex = (currentIndex + 1) % slides.length;
            slides[currentIndex].classList.add("is-active");
        }

        let autoRotate = setInterval(showNextSlide, intervalTime);

        // Pause rotation when user hovers or focuses for accessibility
        wrap.addEventListener("mouseenter", () => clearInterval(autoRotate));
        wrap.addEventListener("mouseleave", () => autoRotate = setInterval(showNextSlide, intervalTime));
        wrap.addEventListener("focusin", () => clearInterval(autoRotate));
        wrap.addEventListener("focusout", () => autoRotate = setInterval(showNextSlide, intervalTime));
    });
    </script>
    <?php endif; ?>

    <?php
    return ob_get_clean();
}