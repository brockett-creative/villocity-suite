<?php
/**
 * Module Name: VilloCity Native Navigation Menu
 * Description: Universal, multi-tenant CSS-driven navigation menu with Executive Mega Menu engine, restored dropdown chevrons, active state highlights, collapsible mobile accordions, and full ADA/WCAG 2.1 AA compliance.
 * Version:     2.3.1 (Desktop Dropdown Full-Width Fix & Layout Isolation)
 * Author:      Brockett Creative Group, Inc.
 * Text Domain: villocity-suite
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

// 1. AUTO-LOAD ADMIN SETTINGS INTERFACE IN WP DASHBOARD
if ( is_admin() && file_exists( __DIR__ . '/admin.php' ) ) {
    require_once __DIR__ . '/admin.php';
}

// 2. REGISTER DEDICATED NAV LOCATION
add_action( 'after_setup_theme', function() {
    register_nav_menus( array(
        'villocity_primary_menu' => __( 'VilloCity Primary Navigation', 'villocity-suite' ),
    ) );
}, 20 );

// ==========================================
// 3. ADA / WCAG 2.1 LEVEL AA MEGA MENU WALKER
// ==========================================
if ( ! class_exists( 'VilloCity_Mega_Menu_Walker' ) ) {
    class VilloCity_Mega_Menu_Walker extends Walker_Nav_Menu {

        private $is_mega = false;
        private $column_count = 3;

        public function start_lvl( &$output, $depth = 0, $args = null ) {
            $indent = str_repeat("\t", $depth);

            if ( 0 === $depth ) {
                if ( $this->is_mega ) {
                    $output .= "\n{$indent}<div class=\"voc-mega-dropdown-panel\" role=\"region\" aria-label=\"Mega Menu Panel\">\n";
                    $output .= "{$indent}\t<div class=\"voc-mega-grid voc-mega-col-{$this->column_count}\">\n";
                } else {
                    $output .= "\n{$indent}<ul class=\"sub-menu\" role=\"menu\">\n";
                }
            } else {
                $output .= "\n{$indent}<ul class=\"sub-menu-nested\">\n";
            }
        }

        public function end_lvl( &$output, $depth = 0, $args = null ) {
            $indent = str_repeat("\t", $depth);

            if ( 0 === $depth ) {
                if ( $this->is_mega ) {
                    $output .= "{$indent}\t</div>\n{$indent}</div>\n";
                } else {
                    $output .= "{$indent}</ul>\n";
                }
            } else {
                $output .= "{$indent}</ul>\n";
            }
        }

        public function start_el( &$output, $item, $depth = 0, $args = null, $id = 0 ) {
            $indent = ( $depth ) ? str_repeat( "\t", $depth ) : '';

            $classes = empty( $item->classes ) ? array() : (array) $item->classes;
            $classes[] = 'menu-item-' . $item->ID;

            if ( 0 === $depth ) {
                $this->is_mega = false;
                $this->column_count = 3;

                foreach ( $classes as $class ) {
                    if ( strpos( $class, 'voc-mega-' ) === 0 || $class === 'voc-mega-menu' ) {
                        $this->is_mega = true;
                        if ( $class === 'voc-mega-2-col' ) $this->column_count = 2;
                        if ( $class === 'voc-mega-4-col' ) $this->column_count = 4;
                        break;
                    }
                }

                if ( $this->is_mega ) {
                    $classes[] = 'has-mega-menu';
                }
            }

            $class_names = implode( ' ', apply_filters( 'nav_menu_css_class', array_filter( $classes ), $item, $args, $depth ) );
            $class_names = $class_names ? ' class="' . esc_attr( $class_names ) . '"' : '';

            $id_attr = apply_filters( 'nav_menu_item_id', 'menu-item-'. $item->ID, $item, $args, $depth );
            $id_attr = $id_attr ? ' id="' . esc_attr( $id_attr ) . '"' : '';

            if ( 0 === $depth && $this->is_mega ) {
                $output .= $indent . '<li' . $id_attr . $class_names . '>';
            } elseif ( 1 === $depth && $this->is_mega ) {
                $output .= $indent . '<div class="voc-mega-column-card">';
            } else {
                $output .= $indent . '<li' . $id_attr . $class_names . '>';
            }

            $atts = array();
            $atts['title']  = ! empty( $item->attr_title ) ? $item->attr_title : '';
            $atts['target'] = ! empty( $item->target )     ? $item->target     : '';
            if ( '_blank' === $item->target ) {
                $atts['rel'] = 'noopener noreferrer';
            }
            $atts['href']   = ! empty( $item->url )        ? $item->url        : '';

            if ( 0 === $depth && in_array( 'menu-item-has-children', $classes, true ) ) {
                $atts['aria-expanded'] = 'false';
                $atts['aria-haspopup'] = 'true';
            }

            if ( in_array( 'current-menu-item', $classes, true ) ) {
                $atts['aria-current'] = 'page';
            }

            $atts = apply_filters( 'nav_menu_link_attributes', $atts, $item, $args, $depth );

            $attributes = '';
            foreach ( $atts as $attr => $value ) {
                if ( ! empty( $value ) ) {
                    $value = ( 'href' === $attr ) ? esc_url( $value ) : esc_attr( $value );
                    $attributes .= ' ' . $attr . '="' . $value . '"';
                }
            }

            $title = apply_filters( 'the_title', $item->title, $item->ID );
            $title = apply_filters( 'nav_menu_item_title', $title, $item, $args, $depth );

            $has_children = in_array( 'menu-item-has-children', $classes, true ) || $this->is_mega;

            $item_output = $args->before ?? '';

            if ( 1 === $depth && $this->is_mega ) {
                $item_output .= '<a' . $attributes . ' class="voc-mega-column-header">';
                $item_output .= '<span>' . ($args->link_before ?? '') . $title . ($args->link_after ?? '') . '</span>';
                $item_output .= '<span class="voc-mega-header-arrow">&rarr;</span></a>';
            } else {
                $item_output .= '<div class="voc-menu-item-wrapper">';
                $item_output .= '<a' . $attributes . ' class="voc-mega-link-item">';
                $item_output .= '<span class="voc-mega-link-title">' . ($args->link_before ?? '') . $title . ($args->link_after ?? '') . '</span>';

                if ( $this->is_mega && ! empty( $item->description ) ) {
                    $item_output .= '<span class="voc-menu-desc">' . esc_html( $item->description ) . '</span>';
                }

                $item_output .= '</a>';

                if ( $has_children && ( 0 === $depth || ( 1 === $depth && ! $this->is_mega ) ) ) {
                    $item_output .= '<button type="button" class="voc-mobile-submenu-toggle" aria-expanded="false" aria-label="Toggle Submenu for ' . esc_attr( $item->title ) . '">';
                    $item_output .= '<span class="voc-toggle-icon" aria-hidden="true">+</span>';
                    $item_output .= '</button>';
                }

                $item_output .= '</div>';
            }

            $item_output .= $args->after ?? '';

            $output .= apply_filters( 'walker_nav_menu_start_el', $item_output, $item, $depth, $args );
        }

        public function end_el( &$output, $item, $depth = 0, $args = null ) {
            $indent = ( $depth ) ? str_repeat( "\t", $depth ) : '';

            if ( 1 === $depth && $this->is_mega ) {
                $output .= "{$indent}</div>\n";
            } else {
                $output .= "{$indent}</li>\n";
            }
        }
    }
}

// 4. RENDER SHORTCODE: [villocity_nav_menu]
if ( ! function_exists( 'villocity_render_nav_menu_shortcode' ) ) {
    function villocity_render_nav_menu_shortcode( $atts = array() ) {
        $custom_logo_id = get_theme_mod( 'custom_logo' );
        $default_logo   = $custom_logo_id ? wp_get_attachment_image_url( $custom_logo_id, 'medium' ) : '';

        // Hub Settings Pull
        $saved_logo_url     = get_option( 'villocity_nav_logo_url', $default_logo );
        $mobile_logo_height = get_option( 'villocity_nav_mobile_logo_height', '110' );

        // Dynamic Universal Search Modal Wording
        $search_title = get_option( 'villocity_nav_search_title', 'Site Search' );
        $search_desc  = get_option( 'villocity_nav_search_desc', 'Search across pages, articles, events, directory listings, and public records...' );
        $search_ph    = get_option( 'villocity_nav_search_placeholder', 'Search everything...' );

        $atts = shortcode_atts( array(
            'menu_id'     => '',
            'show_search' => 'true',
            'logo_url'    => $saved_logo_url
        ), $atts, 'villocity_nav_menu' );

        $primary_brand   = get_option( 'villocity_global_primary', '#860038' );
        $nav_text_col    = get_option( 'villocity_nav_text_color', '#ffffff' );
        $nav_font_size   = get_option( 'villocity_nav_font_size', '17' );
        $nav_font_weight = get_option( 'villocity_nav_font_weight', '500' );
        $nav_transform   = get_option( 'villocity_nav_text_transform', 'none' );
        
        $dropdown_bg     = get_option( 'villocity_nav_dropdown_bg', '#ffffff' );
        $dropdown_text   = get_option( 'villocity_nav_dropdown_text', '#0f172a' );
        $selected_menu   = get_option( 'villocity_nav_selected_menu', '' );

        $target_menu     = ! empty( $atts['menu_id'] ) ? $atts['menu_id'] : $selected_menu;
        $site_search_url = home_url( '/' );

        ob_start();
        ?>
        <style>
            /* ADA Screen Reader Utility */
            .voc-sr-only {
                position: absolute !important;
                width: 1px !important;
                height: 1px !important;
                padding: 0 !important;
                margin: -1px !important;
                overflow: hidden !important;
                clip: rect(0, 0, 0, 0) !important;
                white-space: nowrap !important;
                border: 0 !important;
            }

            .voc-nav-bar-wrap {
                --voc-nav-primary: <?php echo esc_attr( $primary_brand ); ?>;
                --voc-nav-text: <?php echo esc_attr( $nav_text_col ); ?>;
                --voc-nav-size: <?php echo esc_attr( $nav_font_size ); ?>px;
                --voc-nav-weight: <?php echo esc_attr( $nav_font_weight ); ?>;
                --voc-nav-transform: <?php echo esc_attr( $nav_transform ); ?>;
                
                --voc-drop-bg: <?php echo esc_attr( $dropdown_bg ); ?>;
                --voc-drop-text: <?php echo esc_attr( $dropdown_text ); ?>;
                --voc-mobile-logo-h: <?php echo esc_attr( $mobile_logo_height ); ?>px;
                
                background: transparent !important;
                position: relative;
                width: 100%;
                font-family: var(--voc-font-family, inherit) !important;
                box-sizing: border-box;
                z-index: 999;
            }

            .voc-nav-inner {
                max-width: 1200px;
                margin: 0 auto;
                display: flex;
                align-items: center;
                justify-content: space-between;
                padding: 0 16px;
                min-height: 50px;
            }

            /* --- DESKTOP NAVIGATION --- */
            .voc-desktop-nav-container { display: block; position: relative; }

            .voc-desktop-menu-ul {
                list-style: none !important;
                margin: 0 !important;
                padding: 0 !important;
                display: flex;
                align-items: center;
                gap: 28px;
            }

            .voc-desktop-menu-ul > li { position: relative; padding: 12px 0; list-style: none !important; }

            /* Desktop Menu Item Layout Rules (Eliminates Right White Gap) */
            .voc-desktop-menu-ul .voc-menu-item-wrapper {
                display: block;
                width: 100%;
            }

            .voc-mobile-submenu-toggle { display: none !important; }

            .voc-desktop-menu-ul > li > .voc-menu-item-wrapper > a {
                color: var(--voc-nav-text) !important;
                font-size: var(--voc-nav-size) !important;
                font-weight: var(--voc-nav-weight) !important;
                text-transform: var(--voc-nav-transform) !important;
                text-decoration: none !important;
                display: inline-flex;
                align-items: center;
                gap: 6px;
                letter-spacing: 0.02em;
                transition: color 0.2s ease, opacity 0.2s ease;
                min-height: 44px;
            }

            .voc-desktop-menu-ul > li:hover > .voc-menu-item-wrapper > a { opacity: 0.85 !important; }

            /* Top-Level Active Item State */
            .voc-desktop-menu-ul > li.current-menu-item > .voc-menu-item-wrapper > a,
            .voc-desktop-menu-ul > li.current-menu-parent > .voc-menu-item-wrapper > a,
            .voc-desktop-menu-ul > li.current-menu-ancestor > .voc-menu-item-wrapper > a {
                font-weight: 800 !important;
                opacity: 1 !important;
                border-bottom: 2px solid var(--voc-nav-text);
            }

            /* Chevron Down Indicator for Submenus & Mega Menus */
            .voc-desktop-menu-ul li.menu-item-has-children > .voc-menu-item-wrapper > a::after,
            .voc-desktop-menu-ul li.has-mega-menu > .voc-menu-item-wrapper > a::after {
                content: '';
                display: inline-block;
                width: 6px;
                height: 6px;
                border-right: 2px solid currentColor;
                border-bottom: 2px solid currentColor;
                transform: rotate(45deg);
                margin-left: 4px;
                margin-top: -3px;
                transition: transform 0.2s ease;
            }

            .voc-desktop-menu-ul li.menu-item-has-children:hover > .voc-menu-item-wrapper > a::after,
            .voc-desktop-menu-ul li.menu-item-has-children:focus-within > .voc-menu-item-wrapper > a::after,
            .voc-desktop-menu-ul li.has-mega-menu:hover > .voc-menu-item-wrapper > a::after,
            .voc-desktop-menu-ul li.has-mega-menu:focus-within > .voc-menu-item-wrapper > a::after {
                transform: rotate(225deg);
                margin-top: 3px;
            }

            /* --- DESKTOP MEGA MENU DROPDOWN PANEL --- */
            .voc-desktop-menu-ul li.has-mega-menu { position: static !important; }

            .voc-mega-dropdown-panel {
                position: absolute;
                top: 100%;
                left: 0;
                right: 0;
                width: 100%;
                background: #ffffff;
                border-radius: 20px;
                box-shadow: 0 25px 50px -12px rgba(15, 23, 42, 0.18), 0 0 0 1px rgba(15, 23, 42, 0.05);
                border-top: 4px solid var(--voc-nav-primary);
                padding: 32px !important;
                box-sizing: border-box;
                opacity: 0;
                visibility: hidden;
                transform: translateY(12px);
                transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
                z-index: 1000;
            }

            .voc-desktop-menu-ul li.has-mega-menu:hover .voc-mega-dropdown-panel,
            .voc-desktop-menu-ul li.has-mega-menu:focus-within .voc-mega-dropdown-panel {
                opacity: 1;
                visibility: visible;
                transform: translateY(0);
            }

            .voc-mega-grid { display: grid; gap: 24px; }
            .voc-mega-col-2 { grid-template-columns: repeat(2, 1fr); }
            .voc-mega-col-3 { grid-template-columns: repeat(3, 1fr); }
            .voc-mega-col-4 { grid-template-columns: repeat(4, 1fr); }

            .voc-mega-column-card {
                background: #f8fafc;
                border: 1px solid #e2e8f0;
                border-radius: 16px;
                padding: 24px;
                display: flex;
                flex-direction: column;
                transition: transform 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease, background-color 0.2s ease;
            }

            .voc-mega-column-card:hover {
                background: #ffffff;
                border-color: var(--voc-nav-primary);
                transform: translateY(-2px);
                box-shadow: 0 12px 25px rgba(0, 0, 0, 0.06);
            }

            .voc-mega-column-header {
                font-size: 14px !important;
                font-weight: 800 !important;
                color: var(--voc-nav-primary) !important;
                text-transform: uppercase !important;
                letter-spacing: 0.08em !important;
                margin: 0 0 16px 0 !important;
                padding-bottom: 10px;
                border-bottom: 2px solid #e2e8f0;
                text-decoration: none !important;
                display: flex !important;
                align-items: center;
                justify-content: space-between;
                transition: color 0.15s ease;
            }

            .voc-mega-header-arrow {
                font-size: 16px;
                opacity: 0.5;
                transition: transform 0.2s ease, opacity 0.2s ease;
            }

            .voc-mega-column-card:hover .voc-mega-header-arrow {
                opacity: 1;
                transform: translateX(4px);
            }

            .voc-mega-column-card ul.sub-menu-nested {
                list-style: none !important;
                padding: 0 !important;
                margin: 0 !important;
                display: flex;
                flex-direction: column;
                gap: 6px;
            }

            .voc-mega-column-card ul.sub-menu-nested li { list-style: none !important; }

            .voc-mega-column-card ul.sub-menu-nested li a.voc-mega-link-item {
                color: #1e293b !important;
                font-size: 14px !important;
                font-weight: 600 !important;
                padding: 10px 14px !important;
                display: block !important;
                border-radius: 10px;
                text-decoration: none !important;
                transition: all 0.15s ease;
            }

            .voc-mega-column-card ul.sub-menu-nested li a.voc-mega-link-item:hover,
            .voc-mega-column-card ul.sub-menu-nested li a.voc-mega-link-item:focus {
                background: #f1f5f9 !important;
                color: var(--voc-nav-primary) !important;
                padding-left: 18px !important;
            }

            .voc-mega-column-card ul.sub-menu-nested li.current-menu-item a.voc-mega-link-item,
            .voc-mega-column-card ul.sub-menu-nested li.current_page_item a.voc-mega-link-item {
                background: var(--voc-nav-primary) !important;
                color: #ffffff !important;
                font-weight: 800 !important;
            }

            .voc-menu-desc {
                display: block;
                font-size: 12px;
                color: #64748b;
                font-weight: 400;
                margin-top: 3px;
                line-height: 1.4;
            }

            /* --- DESKTOP STANDARD SUBMENUS (Full 100% Width Fix) --- */
            .voc-desktop-menu-ul ul.sub-menu {
                position: absolute;
                top: 100%;
                left: 0;
                min-width: 260px;
                background: var(--voc-drop-bg);
                border-radius: 12px;
                box-shadow: 0 15px 30px rgba(0,0,0,0.12);
                border: 1px solid #e2e8f0;
                padding: 8px 0 !important;
                list-style: none !important;
                margin: 0 !important;
                opacity: 0;
                visibility: hidden;
                transform: translateY(8px);
                transition: all 0.2s ease;
                z-index: 1000;
            }

            .voc-desktop-menu-ul ul.sub-menu li {
                list-style: none !important;
                width: 100%;
                box-sizing: border-box;
            }

            .voc-desktop-menu-ul li:hover > ul.sub-menu,
            .voc-desktop-menu-ul li:focus-within > ul.sub-menu {
                opacity: 1;
                visibility: visible;
                transform: translateY(0);
            }

            .voc-desktop-menu-ul ul.sub-menu li .voc-menu-item-wrapper {
                width: 100%;
                display: block;
            }

            .voc-desktop-menu-ul ul.sub-menu li .voc-menu-item-wrapper a {
                color: var(--voc-drop-text) !important;
                font-size: 14px !important;
                font-weight: 500 !important;
                padding: 12px 18px !important;
                display: block !important;
                width: 100% !important;
                box-sizing: border-box !important;
                text-decoration: none !important;
                min-height: 44px;
                transition: background 0.15s ease, color 0.15s ease;
            }

            .voc-desktop-menu-ul ul.sub-menu li .voc-menu-item-wrapper a:hover,
            .voc-desktop-menu-ul ul.sub-menu li .voc-menu-item-wrapper a:focus {
                background-color: rgba(0,0,0,0.05) !important;
                color: var(--voc-nav-primary) !important;
            }

            .voc-desktop-menu-ul ul.sub-menu li.current-menu-item .voc-menu-item-wrapper a,
            .voc-desktop-menu-ul ul.sub-menu li.current_page_item .voc-menu-item-wrapper a {
                background-color: var(--voc-nav-primary) !important;
                color: #ffffff !important;
                font-weight: 700 !important;
            }

            /* ADA Focus Indicators */
            .voc-desktop-menu-ul > li > .voc-menu-item-wrapper > a:focus-visible,
            .voc-mega-column-header:focus-visible,
            .voc-mega-column-card ul.sub-menu-nested li a:focus-visible,
            .voc-search-toggle-btn:focus-visible,
            .voc-mobile-hamburger:focus-visible,
            .voc-drawer-close:focus-visible,
            .voc-nav-search-close:focus-visible,
            .voc-mobile-submenu-toggle:focus-visible {
                outline: 3px solid #60a5fa !important;
                outline-offset: 3px !important;
                border-radius: 6px;
            }

            /* Search Trigger */
            .voc-nav-right { display: flex; align-items: center; }

            .voc-search-toggle-btn {
                background: transparent !important;
                border: none !important;
                color: var(--voc-nav-text) !important;
                cursor: pointer;
                padding: 10px;
                min-width: 44px;
                min-height: 44px;
                display: flex;
                align-items: center;
                justify-content: center;
            }

            /* Search Modal Overlay */
            .voc-nav-search-overlay {
                position: fixed;
                inset: 0;
                background: rgba(15, 23, 42, 0.88);
                backdrop-filter: blur(6px);
                z-index: 999999;
                display: none;
                align-items: center;
                justify-content: center;
                padding: 20px;
                box-sizing: border-box;
            }
            .voc-nav-search-overlay.is-active { display: flex; }
            .voc-nav-search-box {
                background: #ffffff;
                border-radius: 20px;
                padding: 32px;
                width: 100%;
                max-width: 620px;
                box-shadow: 0 20px 50px rgba(0,0,0,0.3);
                position: relative;
            }
            .voc-nav-search-close {
                position: absolute;
                top: 20px; right: 20px;
                background: #f1f5f9 !important;
                border: none !important;
                width: 44px; height: 44px;
                border-radius: 50%;
                font-size: 20px;
                color: #0f172a !important;
                cursor: pointer;
                display: flex; align-items: center; justify-content: center;
            }

            /* --- COLLAPSIBLE MOBILE ACCORDION (SUBMENUS COLLAPSED BY DEFAULT) --- */
            .voc-mobile-hamburger, 
            .voc-offcanvas-overlay, 
            .voc-offcanvas-drawer { 
                display: none !important; 
            }

            @media (max-width: 1023px) {
                .voc-desktop-nav-container { display: none !important; }

                .voc-mobile-hamburger {
                    display: flex !important;
                    background: transparent !important;
                    border: none !important;
                    color: var(--voc-nav-text) !important;
                    cursor: pointer;
                    min-width: 44px;
                    min-height: 44px;
                    padding: 10px;
                    flex-direction: column;
                    justify-content: center;
                    gap: 5px;
                }
                .voc-mobile-hamburger span {
                    display: block;
                    width: 24px;
                    height: 2px;
                    background-color: currentColor;
                    border-radius: 2px;
                }

                .voc-offcanvas-overlay {
                    display: block !important;
                    position: fixed;
                    inset: 0;
                    background: rgba(0, 0, 0, 0.6);
                    opacity: 0;
                    visibility: hidden;
                    transition: all 0.3s ease;
                    z-index: 99998;
                }
                .voc-offcanvas-overlay.is-active { opacity: 1; visibility: visible; }

                .voc-offcanvas-drawer {
                    display: flex !important;
                    position: fixed;
                    top: 0; bottom: 0; left: 0;
                    width: 85%;
                    max-width: 380px;
                    background-color: var(--voc-nav-primary) !important;
                    color: #ffffff !important;
                    z-index: 99999;
                    transform: translateX(-100%);
                    transition: transform 0.3s ease;
                    flex-direction: column;
                    overflow-y: auto;
                    box-shadow: 10px 0 30px rgba(0,0,0,0.3);
                }
                .voc-offcanvas-drawer.is-active { transform: translateX(0); }

                .voc-drawer-header {
                    padding: 20px;
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    border-bottom: 1px solid rgba(255, 255, 255, 0.15);
                }
                .voc-drawer-logo img {
                    max-height: var(--voc-mobile-logo-h) !important;
                    width: auto !important;
                    display: block;
                    object-fit: contain;
                }

                .voc-drawer-close {
                    background: rgba(255, 255, 255, 0.15) !important;
                    border: 1px solid rgba(255, 255, 255, 0.25) !important;
                    color: #ffffff !important;
                    width: 44px;
                    height: 44px;
                    border-radius: 12px;
                    cursor: pointer;
                    display: flex !important;
                    align-items: center;
                    justify-content: center;
                    padding: 0 !important;
                }

                .voc-mobile-menu-ul,
                .voc-mobile-menu-ul ul,
                .voc-mobile-menu-ul ul.sub-menu,
                .voc-mobile-menu-ul ul.sub-menu-nested {
                    list-style: none !important;
                    list-style-type: none !important;
                    margin: 0 !important;
                    padding: 0 !important;
                }

                .voc-mobile-menu-ul li {
                    list-style: none !important;
                    list-style-type: none !important;
                }

                .voc-mobile-menu-ul {
                    padding: 12px 0 40px 0 !important;
                    display: flex !important;
                    flex-direction: column;
                }

                .voc-mobile-menu-ul > li > .voc-menu-item-wrapper {
                    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
                    display: flex !important;
                    justify-content: space-between;
                    align-items: center;
                }

                .voc-mobile-menu-ul li .voc-menu-item-wrapper a {
                    font-size: 18px !important;
                    font-weight: 700 !important;
                    color: #ffffff !important;
                    text-decoration: none !important;
                    padding: 14px 20px !important;
                    display: flex !important;
                    align-items: center;
                    flex: 1;
                    min-height: 44px;
                    background: transparent !important;
                }

                .voc-mobile-submenu-toggle {
                    display: flex !important;
                    align-items: center;
                    justify-content: center;
                    background: transparent !important;
                    border: none !important;
                    color: #ffffff !important;
                    font-size: 20px !important;
                    font-weight: 700 !important;
                    min-width: 44px;
                    min-height: 44px;
                    cursor: pointer;
                    padding: 0 16px !important;
                    border-left: 1px solid rgba(255, 255, 255, 0.08) !important;
                }

                .voc-mobile-menu-ul .voc-mega-dropdown-panel,
                .voc-mobile-menu-ul ul.sub-menu,
                .voc-mobile-menu-ul ul.sub-menu-nested {
                    display: none !important;
                    position: static !important;
                    opacity: 1 !important;
                    visibility: visible !important;
                    transform: none !important;
                    background: rgba(0, 0, 0, 0.12) !important;
                    box-shadow: none !important;
                    border: none !important;
                    padding: 4px 0 !important;
                }

                .voc-mobile-menu-ul li.is-submenu-open > .voc-mega-dropdown-panel,
                .voc-mobile-menu-ul li.is-submenu-open > ul.sub-menu,
                .voc-mobile-menu-ul li.is-submenu-open > .voc-menu-item-wrapper + ul.sub-menu,
                .voc-mobile-menu-ul .voc-mega-column-card.is-submenu-open > ul.sub-menu-nested {
                    display: flex !important;
                    flex-direction: column;
                }

                .voc-mobile-menu-ul .voc-mega-grid {
                    display: flex !important;
                    flex-direction: column;
                    gap: 0;
                }

                .voc-mobile-menu-ul .voc-mega-column-card {
                    background: transparent !important;
                    border: none !important;
                    padding: 0 !important;
                    box-shadow: none !important;
                    transform: none !important;
                }

                .voc-mobile-menu-ul .voc-mega-column-header {
                    color: rgba(255, 255, 255, 0.9) !important;
                    border-bottom: 1px solid rgba(255, 255, 255, 0.08) !important;
                    font-size: 14px !important;
                    padding: 12px 20px 12px 32px !important;
                    margin: 0 !important;
                    text-transform: uppercase !important;
                }

                .voc-mobile-menu-ul ul.sub-menu li .voc-menu-item-wrapper a,
                .voc-mobile-menu-ul ul.sub-menu-nested li a.voc-mega-link-item {
                    font-size: 15px !important;
                    font-weight: 500 !important;
                    color: rgba(255, 255, 255, 0.85) !important;
                    padding: 10px 20px 10px 44px !important;
                    display: block !important;
                    border-bottom: 1px solid rgba(255, 255, 255, 0.04);
                    background: transparent !important;
                    text-decoration: none !important;
                }

                .voc-mobile-menu-ul .voc-menu-desc {
                    color: rgba(255, 255, 255, 0.6) !important;
                }
            }
        </style>

        <div class="voc-nav-bar-wrap" role="navigation" aria-label="<?php esc_attr_e( 'Primary Navigation', 'villocity-suite' ); ?>">
            <div class="voc-nav-inner">
                <!-- Mobile Hamburger Trigger -->
                <button class="voc-mobile-hamburger" id="voc-menu-open-btn" aria-label="<?php esc_attr_e( 'Open Navigation Menu', 'villocity-suite' ); ?>" aria-expanded="false" aria-controls="voc-nav-drawer">
                    <span></span><span></span><span></span>
                </button>

                <!-- Desktop Navigation Stream -->
                <div class="voc-desktop-nav-container">
                    <?php
                    $desktop_args = array(
                        'container'   => false,
                        'menu_class'  => 'voc-desktop-menu-ul',
                        'menu_id'     => 'voc-desktop-nav-ul',
                        'walker'      => new VilloCity_Mega_Menu_Walker(),
                        'fallback_cb' => false,
                        'echo'        => false
                    );

                    if ( ! empty( $target_menu ) ) {
                        $desktop_args['menu'] = $target_menu;
                    } else {
                        $desktop_args['theme_location'] = 'villocity_primary_menu';
                    }

                    $rendered_desktop = wp_nav_menu( $desktop_args );
                    echo $rendered_desktop ? $rendered_desktop : '<ul class="voc-desktop-menu-ul"><li><div class="voc-menu-item-wrapper"><a href="' . esc_url( home_url('/') ) . '">Home</a></div></li></ul>';
                    ?>
                </div>

                <!-- Mobile Drawer Backdrop -->
                <div class="voc-offcanvas-overlay" id="voc-nav-overlay" tabindex="-1"></div>

                <!-- Mobile Drawer Panel -->
                <div class="voc-offcanvas-drawer" id="voc-nav-drawer" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'Mobile Navigation Menu', 'villocity-suite' ); ?>">
                    <div class="voc-drawer-header">
                        <a href="<?php echo esc_url( home_url('/') ); ?>" class="voc-drawer-logo">
                            <?php if ( ! empty( $atts['logo_url'] ) ) : ?>
                                <img src="<?php echo esc_url( $atts['logo_url'] ); ?>" alt="<?php echo esc_attr( get_bloginfo('name') ); ?> Home">
                            <?php else : ?>
                                <span style="font-weight:800; font-size:20px; color:#ffffff;"><?php echo esc_html( get_bloginfo('name') ); ?></span>
                            <?php endif; ?>
                        </a>
                        <button class="voc-drawer-close" id="voc-menu-close-btn" aria-label="<?php esc_attr_e( 'Close Navigation Menu', 'villocity-suite' ); ?>">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <line x1="18" y1="6" x2="6" y2="18"></line>
                                <line x1="6" y1="6" x2="18" y2="18"></line>
                            </svg>
                        </button>
                    </div>

                    <?php
                    $mobile_args = array(
                        'container'   => false,
                        'menu_class'  => 'voc-mobile-menu-ul',
                        'menu_id'     => 'voc-mobile-nav-ul',
                        'walker'      => new VilloCity_Mega_Menu_Walker(),
                        'fallback_cb' => false,
                        'echo'        => false
                    );

                    if ( ! empty( $target_menu ) ) {
                        $mobile_args['menu'] = $target_menu;
                    } else {
                        $mobile_args['theme_location'] = 'villocity_primary_menu';
                    }

                    $rendered_mobile = wp_nav_menu( $mobile_args );
                    echo $rendered_mobile ? $rendered_mobile : '<ul class="voc-mobile-menu-ul"><li><div class="voc-menu-item-wrapper"><a href="' . esc_url( home_url('/') ) . '">Home</a></div></li></ul>';
                    ?>
                </div>

                <!-- Universal Search Trigger -->
                <?php if ( 'true' === $atts['show_search'] ) : ?>
                <div class="voc-nav-right">
                    <button type="button" class="voc-search-toggle-btn" id="voc-open-nav-search" aria-label="<?php esc_attr_e( 'Search Site', 'villocity-suite' ); ?>" aria-expanded="false" aria-controls="voc-nav-search-modal">
                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                    </button>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Universal Site Search Modal Overlay -->
        <div class="voc-nav-search-overlay" id="voc-nav-search-modal" role="dialog" aria-modal="true" aria-labelledby="voc-search-title">
            <div class="voc-nav-search-box">
                <button type="button" class="voc-nav-search-close" id="voc-close-nav-search" aria-label="<?php esc_attr_e( 'Close Search Modal', 'villocity-suite' ); ?>">&times;</button>
                <h2 id="voc-search-title" style="margin:0 0 8px 0; color:#0f172a; font-size:22px; font-weight:800;"><?php echo esc_html($search_title); ?></h2>
                <p id="voc-search-desc" style="margin:0 0 20px 0; font-size:14px; color:#64748b;"><?php echo esc_html($search_desc); ?></p>
                
                <form action="<?php echo esc_url($site_search_url); ?>" method="get" role="search" style="display:flex; gap:10px;">
                    <label for="voc-nav-search-input" class="voc-sr-only"><?php esc_html_e( 'Search Keyword', 'villocity-suite' ); ?></label>
                    <input type="search" name="s" id="voc-nav-search-input" placeholder="<?php echo esc_attr($search_ph); ?>" class="voc-gemini-input" style="flex:1;" aria-describedby="voc-search-desc" required>
                    <button type="submit" class="voc-gemini-btn-primary" style="white-space:nowrap; min-height:44px;"><?php esc_html_e( 'Search', 'villocity-suite' ); ?></button>
                </form>
            </div>
        </div>

        <script>
        document.addEventListener("DOMContentLoaded", function() {
            function trapFocus(element) {
                const focusables = element.querySelectorAll('a[href], button, input, textarea, select, [tabindex]:not([-1])');
                if (focusables.length === 0) return;
                const first = focusables[0];
                const last = focusables[focusables.length - 1];

                element.addEventListener('keydown', function(e) {
                    if (e.key === 'Tab') {
                        if (e.shiftKey && document.activeElement === first) {
                            last.focus();
                            e.preventDefault();
                        } else if (!e.shiftKey && document.activeElement === last) {
                            first.focus();
                            e.preventDefault();
                        }
                    }
                });
            }

            const openBtn   = document.getElementById("voc-menu-open-btn");
            const closeBtn  = document.getElementById("voc-menu-close-btn");
            const overlay   = document.getElementById("voc-nav-overlay");
            const drawer    = document.getElementById("voc-nav-drawer");

            function openDrawer() {
                if (drawer && overlay) {
                    drawer.classList.add("is-active");
                    overlay.classList.add("is-active");
                    openBtn.setAttribute("aria-expanded", "true");
                    document.body.style.overflow = "hidden";
                    closeBtn.focus();
                    trapFocus(drawer);
                }
            }

            function closeDrawer() {
                if (drawer && overlay) {
                    drawer.classList.remove("is-active");
                    overlay.classList.remove("is-active");
                    openBtn.setAttribute("aria-expanded", "false");
                    document.body.style.overflow = "";
                    openBtn.focus();
                }
            }

            if (openBtn)  openBtn.addEventListener("click", openDrawer);
            if (closeBtn) closeBtn.addEventListener("click", closeDrawer);
            if (overlay)  overlay.addEventListener("click", closeDrawer);

            // Accessible Mobile Accordion Submenu Toggles
            document.querySelectorAll(".voc-mobile-submenu-toggle").forEach(button => {
                button.addEventListener("click", function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    const parentLi = this.closest("li, .voc-mega-column-card");
                    const icon = this.querySelector(".voc-toggle-icon");
                    
                    if (parentLi) {
                        const isOpen = parentLi.classList.contains("is-submenu-open");
                        
                        if (isOpen) {
                            parentLi.classList.remove("is-submenu-open");
                            this.setAttribute("aria-expanded", "false");
                            if (icon) icon.textContent = "+";
                        } else {
                            parentLi.classList.add("is-submenu-open");
                            this.setAttribute("aria-expanded", "true");
                            if (icon) icon.textContent = "−";
                        }
                    }
                });
            });

            const openSearch  = document.getElementById("voc-open-nav-search");
            const closeSearch = document.getElementById("voc-close-nav-search");
            const searchModal = document.getElementById("voc-nav-search-modal");
            const searchInput = document.getElementById("voc-nav-search-input");

            function openModal() {
                if (searchModal) {
                    searchModal.classList.add("is-active");
                    openSearch.setAttribute("aria-expanded", "true");
                    if (searchInput) setTimeout(() => searchInput.focus(), 100);
                    trapFocus(searchModal);
                }
            }

            function closeModal() {
                if (searchModal) {
                    searchModal.classList.remove("is-active");
                    openSearch.setAttribute("aria-expanded", "false");
                    openSearch.focus();
                }
            }

            if (openSearch)  openSearch.addEventListener("click", openModal);
            if (closeSearch) closeSearch.addEventListener("click", closeModal);
            if (searchModal) {
                searchModal.addEventListener("click", function(e) {
                    if (e.target === searchModal) closeModal();
                });
            }

            // Keyboard Accessibility for Mega Menus
            const megaParentLinks = document.querySelectorAll(".has-mega-menu > .voc-menu-item-wrapper > a");
            megaParentLinks.forEach(link => {
                link.addEventListener("focus", function() {
                    this.setAttribute("aria-expanded", "true");
                });
            });

            document.addEventListener("keydown", function(e) {
                if (e.key === "Escape") {
                    if (searchModal && searchModal.classList.contains("is-active")) closeModal();
                    if (drawer && drawer.classList.contains("is-active")) closeDrawer();
                }
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }
}

add_shortcode( 'villocity_nav_menu', 'villocity_render_nav_menu_shortcode' );