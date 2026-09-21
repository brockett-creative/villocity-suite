<?php
/**
 * VilloCity Module: Board & Staff (Frontend)
 * Version: 1.3.0
 * Author: Brockett Creative Group, Inc.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

// ==========================================
// 1. SHORTCODE: STAFF DIRECTORY
// ==========================================
if ( ! function_exists( 'voc_render_staff_directory' ) ) {
    add_shortcode( 'villocity_staff', 'voc_render_staff_directory' );
    
    function voc_render_staff_directory( $atts ) {
        // Allow filtering: [villocity_staff group="Planning Board" classification="Elected Official" department="Highway"]
        $args = shortcode_atts( array(
            'group'          => '',
            'classification' => '',
            'department'     => ''
        ), $atts, 'villocity_staff' );

        $layout_style   = get_option('villocity_global_layout_style', 'boxed');
        $primary_col    = get_option('villocity_global_primary', '#860038');
        $accent_col     = get_option('villocity_global_accent', '#fadceb');
        $text_col       = get_option('villocity_global_text', '#1c1917');
        $bg_col         = get_option('villocity_global_bg', '#fafaf9');
        $show_mock      = get_option( 'voc_staff_show_mock', '0' );

        $query_args = array(
            'post_type'      => 'bcg_staff',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'meta_key'       => '_staff_order',
            'orderby'        => array( 'meta_value_num' => 'ASC', 'title' => 'ASC' )
        );

        // Build Meta Query dynamically if filters or mock exclusions are applied
        $meta_query = array( 'relation' => 'AND' );
        
        // Exclude mock staff unless explicitly enabled in staff settings
        if ( $show_mock !== '1' ) {
            $meta_query[] = array(
                'relation' => 'OR',
                array( 'key' => '_staff_is_mock', 'value' => '0', 'compare' => '=' ),
                array( 'key' => '_staff_is_mock', 'compare' => 'NOT EXISTS' )
            );
        }

        if ( ! empty( $args['group'] ) ) {
            $meta_query[] = array(
                'key'     => '_staff_group',
                'value'   => sanitize_text_field( $args['group'] ),
                'compare' => '='
            );
        }
        
        if ( ! empty( $args['classification'] ) ) {
            $meta_query[] = array(
                'key'     => '_staff_classification',
                'value'   => sanitize_text_field( $args['classification'] ),
                'compare' => '='
            );
        }

        if ( ! empty( $args['department'] ) ) {
            $meta_query[] = array(
                'relation' => 'OR',
                array( 'key' => '_staff_department', 'value' => sanitize_text_field( $args['department'] ), 'compare' => '=' ),
                array( 'key' => '_staff_department_id', 'value' => sanitize_text_field( $args['department'] ), 'compare' => '=' )
            );
        }
        
        if ( count($meta_query) > 1 || ( count($meta_query) === 1 && ! isset($meta_query['relation']) ) ) {
            $query_args['meta_query'] = $meta_query;
        }

        $staff_query = new WP_Query( $query_args );

        $html = '<style>
            .voc-staff-wrap { 
                --voc-primary: ' . esc_attr($primary_col) . '; 
                --voc-accent: ' . esc_attr($accent_col) . '; 
                --voc-text: ' . esc_attr($text_col) . '; 
                --voc-bg: ' . esc_attr($bg_col) . '; 
                font-family: var(--voc-font-family, inherit) !important; 
                margin: 20px 0;
            }
            
            .voc-staff-grid { 
                display: grid; 
                grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); 
                gap: 24px; 
            }

            .voc-staff-card { 
                background: var(--voc-bg); 
                border: 1px solid #e7e5e4; 
                border-radius: 16px; 
                overflow: hidden; 
                display: flex; 
                flex-direction: column; 
                box-shadow: 0 4px 15px rgba(0,0,0,0.03); 
                transition: transform 0.2s, box-shadow 0.2s; 
            }
            .voc-staff-card:hover { 
                transform: translateY(-3px); 
                box-shadow: 0 8px 25px rgba(0,0,0,0.06); 
            }

            /* Clean & Minimal Layout Adjustments */
            .voc-staff-wrap.voc-style-clean .voc-staff-card,
            .voc-staff-wrap.voc-style-minimal-grid .voc-staff-card {
                background: transparent;
                border: none;
                box-shadow: none;
            }
            .voc-staff-wrap.voc-style-clean .voc-staff-card:hover,
            .voc-staff-wrap.voc-style-minimal-grid .voc-staff-card:hover {
                transform: none;
            }

            .voc-staff-wrap.voc-style-clean .voc-staff-grid {
                display: flex;
                flex-direction: column;
                gap: 32px;
            }
            .voc-staff-wrap.voc-style-clean .voc-staff-card {
                flex-direction: row;
                align-items: center;
                border-bottom: 1px solid #e7e5e4;
                border-radius: 0;
                padding-bottom: 32px;
                gap: 24px;
            }
            .voc-staff-wrap.voc-style-clean .voc-staff-card:last-child {
                border-bottom: none;
            }

            .voc-staff-img-wrap { 
                width: 100%; 
                height: 250px; 
                background: #f1f5f9; 
                display: flex; 
                align-items: center; 
                justify-content: center; 
                overflow: hidden; 
                flex-shrink: 0;
            }
            .voc-staff-wrap.voc-style-clean .voc-staff-img-wrap {
                width: 150px;
                height: 150px;
                border-radius: 50%;
            }
            .voc-staff-img { 
                width: 100%; 
                height: 100%; 
                object-fit: cover; 
            }
            
            .voc-staff-body { 
                padding: 24px; 
                display: flex; 
                flex-direction: column; 
                flex-grow: 1; 
            }
            .voc-staff-wrap.voc-style-clean .voc-staff-body {
                padding: 0;
            }

            .voc-staff-name { 
                font-size: 22px; 
                font-weight: 800; 
                color: var(--voc-primary); 
                margin: 0 0 4px 0 !important; 
                line-height: 1.2; 
                font-family: var(--voc-font-family, inherit) !important;
            }
            .voc-staff-position { 
                font-size: 15px; 
                font-weight: 600; 
                color: var(--voc-text); 
                margin: 0 0 16px 0 !important; 
                opacity: 0.8; 
            }
            
            .voc-staff-badges {
                display: flex;
                flex-wrap: wrap;
                gap: 6px;
                margin-bottom: 16px;
            }
            .voc-staff-group-badge { 
                background: var(--voc-accent); 
                color: var(--voc-primary); 
                font-size: 11px; 
                font-weight: 700; 
                padding: 4px 10px; 
                border-radius: 12px; 
                display: inline-flex; 
                align-items: center;
                gap: 4px;
                text-transform: uppercase;
                letter-spacing: 0.05em;
            }
            .voc-staff-dept-badge {
                background: #f1f5f9;
                color: #334155;
                border: 1px solid #cbd5e1;
                font-size: 11px;
                font-weight: 700;
                padding: 4px 10px;
                border-radius: 12px;
                display: inline-flex;
                align-items: center;
                gap: 4px;
            }
            .voc-staff-class-badge {
                background: #f8fafc;
                color: #475569;
                border: 1px solid #e2e8f0;
                font-size: 11px;
                font-weight: 600;
                padding: 3px 10px;
                border-radius: 12px;
                display: inline-flex;
                align-items: center;
                gap: 4px;
            }
            
            .voc-staff-contact { 
                display: flex; 
                align-items: center; 
                gap: 8px; 
                font-size: 14px; 
                margin-bottom: 8px; 
                color: var(--voc-text); 
                opacity: 0.9; 
            }
            .voc-staff-contact a { 
                color: inherit; 
                text-decoration: none; 
                transition: color 0.2s; 
            }
            .voc-staff-contact a:hover { 
                color: var(--voc-primary); 
                text-decoration: underline; 
            }
            
            .voc-staff-linkedin { 
                margin-top: 16px; 
                display: inline-flex; 
            }
            .voc-staff-linkedin a { 
                display: flex; 
                align-items: center; 
                justify-content: center; 
                width: 36px; 
                height: 36px; 
                background: #f0f4f8; 
                color: #0077b5; 
                border-radius: 50%; 
                transition: all 0.2s; 
            }
            .voc-staff-linkedin a:hover { 
                background: #0077b5; 
                color: #fff; 
                transform: translateY(-2px); 
                box-shadow: 0 4px 10px rgba(0,0,0,0.1); 
            }

            @media (max-width: 640px) {
                .voc-staff-wrap.voc-style-clean .voc-staff-card {
                    flex-direction: column;
                    text-align: center;
                }
                .voc-staff-wrap.voc-style-clean .voc-staff-badges {
                    justify-content: center;
                }
                .voc-staff-wrap.voc-style-clean .voc-staff-contact {
                    justify-content: center;
                }
            }
        </style>';

        $html .= '<div class="voc-staff-wrap voc-style-' . esc_attr($layout_style) . '">';
        $html .= '<div class="voc-staff-grid">';

        $schema_items = array();

        if ( $staff_query->have_posts() ) {
            while ( $staff_query->have_posts() ) {
                $staff_query->the_post();
                $id = get_the_ID();
                $name           = get_the_title();
                $position       = get_post_meta( $id, '_staff_position', true );
                $group          = get_post_meta( $id, '_staff_group', true );
                $classification = get_post_meta( $id, '_staff_classification', true );
                $department     = get_post_meta( $id, '_staff_department', true );
                $email          = get_post_meta( $id, '_staff_email', true );
                $phone          = get_post_meta( $id, '_staff_phone', true );
                $linkedin       = get_post_meta( $id, '_staff_linkedin', true );
                
                $img = get_the_post_thumbnail_url( $id, 'medium_large' );

                // Add to Schema Data
                $schema_items[] = array(
                    '@type'       => 'Person',
                    'name'        => esc_html($name),
                    'jobTitle'    => esc_html($position),
                    'email'       => esc_html($email),
                    'telephone'   => esc_html($phone),
                    'sameAs'      => esc_url($linkedin),
                    'image'       => esc_url($img)
                );

                $html .= '<article class="voc-staff-card">';
                
                // Image or Placeholder
                $html .= '<div class="voc-staff-img-wrap">';
                if ( $img ) {
                    $html .= '<img src="' . esc_url($img) . '" class="voc-staff-img" alt="Headshot of ' . esc_attr($name) . '">';
                } else {
                    $html .= '<span style="color:#cbd5e1;">' . villocity_get_icon('user') . '</span>';
                }
                $html .= '</div>';

                // Profile Details
                $html .= '<div class="voc-staff-body">';
                
                // Badges
                $html .= '<div class="voc-staff-badges">';
                if ( ! empty($group) ) {
                    $html .= '<span class="voc-staff-group-badge">' . villocity_get_icon('users') . ' ' . esc_html($group) . '</span>';
                }
                if ( ! empty($department) ) {
                    $html .= '<span class="voc-staff-dept-badge">' . villocity_get_icon('building') . ' ' . esc_html($department) . '</span>';
                }
                if ( ! empty($classification) ) {
                    $html .= '<span class="voc-staff-class-badge">' . esc_html($classification) . '</span>';
                }
                $html .= '</div>';
                
                $html .= '<h3 class="voc-staff-name">' . esc_html($name) . '</h3>';
                
                if ( ! empty($position) ) {
                    $html .= '<p class="voc-staff-position">' . esc_html($position) . '</p>';
                }

                if ( ! empty($email) ) {
                    $html .= '<div class="voc-staff-contact">
                        ' . villocity_get_icon('mail') . '
                        <a href="mailto:' . esc_attr($email) . '" aria-label="Email ' . esc_attr($name) . '">' . esc_html($email) . '</a>
                    </div>';
                }

                if ( ! empty($phone) ) {
                    $html .= '<div class="voc-staff-contact">
                        ' . villocity_get_icon('phone') . '
                        <a href="tel:' . preg_replace('/[^0-9+]/', '', $phone) . '" aria-label="Call ' . esc_attr($name) . ' at ' . esc_attr($phone) . '">' . esc_html($phone) . '</a>
                    </div>';
                }

                if ( ! empty($linkedin) ) {
                    $html .= '<div class="voc-staff-linkedin">
                        <a href="' . esc_url($linkedin) . '" target="_blank" rel="noopener noreferrer" aria-label="Visit ' . esc_attr($name) . '\'s LinkedIn Profile">
                            ' . villocity_get_icon('globe') . '
                        </a>
                    </div>';
                }

                $html .= '</div>'; // End body
                $html .= '</article>';
            }
            wp_reset_postdata();
        } else {
            $html .= '<p style="color: #64748b;">No board or staff members found.</p>';
        }

        $html .= '</div></div>';

        // Inject SEO Schema Array
        if ( ! empty( $schema_items ) ) {
            $schema_json = array(
                '@context' => 'https://schema.org',
                '@type'    => 'ItemList',
                'itemListElement' => $schema_items
            );
            $html .= '<script type="application/ld+json">' . wp_json_encode( $schema_json ) . '</script>';
        }

        return $html;
    }
}