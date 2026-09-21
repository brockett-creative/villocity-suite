<?php
/**
 * Module Name: VilloCity Alert Bar & Ticker
 * Description: ADA-compliant accessibility alert bar with severity levels, action buttons, dismissal memory, and admin settings page.
 * Version:     2.9.0 (Integrated Admin Settings Panel & Shortcodes)
 * Author:      Brockett Creative Group, Inc.
 * Author URI:  https://brockettcreative.com
 * Text Domain: villocity-suite
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

// ==========================================
// 1. FRONTEND SHORTCODE RENDERER
// ==========================================
if ( ! function_exists( 'villocity_render_alert_bar' ) ) {
    function villocity_render_alert_bar( $atts = array() ) {
        // Module activation check
        $active_modules = get_option( 'villocity_active_modules', array() );
        if ( is_array( $active_modules ) && ! in_array( 'alert_bar', $active_modules, true ) ) {
            return '';
        }

        $atts = shortcode_atts( array(
            'id'       => '',
            'type'     => '',
            'text'     => '',
            'btn_text' => '',
            'btn_url'  => '',
        ), $atts );

        $alert_enabled = get_option( 'villocity_alert_enabled', 'yes' );
        $alert_text    = ! empty( $atts['text'] ) ? $atts['text'] : get_option( 'villocity_alert_text', 'Farmers\' Market Held in the Village Green Every Thursday.' );
        $alert_type    = ! empty( $atts['type'] ) ? $atts['type'] : get_option( 'villocity_alert_type', 'info' );
        $btn_text      = ! empty( $atts['btn_text'] ) ? $atts['btn_text'] : get_option( 'villocity_alert_btn_text', '' );
        $btn_url       = ! empty( $atts['btn_url'] ) ? $atts['btn_url'] : get_option( 'villocity_alert_btn_url', '' );

        if ( $alert_enabled !== 'yes' || empty( trim( $alert_text ) ) ) {
            return '';
        }

        $primary_brand = get_option( 'villocity_global_primary', '#860038' );
        
        switch ( $alert_type ) {
            case 'critical':
                $bg_color   = '#dc2626';
                $text_color = '#ffffff';
                $icon_svg   = function_exists('villocity_get_icon') ? villocity_get_icon('alert') : '⚠️';
                break;
            case 'warning':
                $bg_color   = '#d97706';
                $text_color = '#ffffff';
                $icon_svg   = function_exists('villocity_get_icon') ? villocity_get_icon('alert') : '⚠️';
                break;
            case 'info':
            default:
                $bg_color   = ! empty( get_option('villocity_alert_bg') ) ? get_option('villocity_alert_bg') : $primary_brand;
                $text_color = '#ffffff';
                $icon_svg   = function_exists('villocity_get_icon') ? villocity_get_icon('check') : 'ℹ️';
                break;
        }

        $bar_id = 'voc_alert_' . substr( md5( $alert_text . $alert_type ), 0, 8 );

        $html = '<style>
            .voc-alert-bar-wrap {
                width: 100%;
                background-color: ' . esc_attr( $bg_color ) . ';
                color: ' . esc_attr( $text_color ) . ';
                padding: 12px 20px;
                font-size: 15px;
                font-weight: 700;
                font-family: var(--voc-font-family, inherit) !important;
                box-sizing: border-box;
                position: relative;
                z-index: 99;
                display: flex;
                align-items: center;
                justify-content: center;
                min-height: 48px;
                box-shadow: inset 0 -2px 0 rgba(0,0,0,0.12);
                transition: opacity 0.25s ease, max-height 0.25s ease;
            }
            .voc-alert-bar-inner {
                max-width: 1100px;
                margin: 0 auto;
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 14px;
                flex-wrap: wrap;
                width: 100%;
                padding-right: 36px;
            }
            .voc-alert-bar-text {
                margin: 0;
                padding: 0;
                line-height: 1.4;
                color: ' . esc_attr( $text_color ) . ' !important;
                display: inline-flex;
                align-items: center;
                gap: 8px;
                letter-spacing: 0.2px;
            }
            .voc-alert-btn {
                background: rgba(255, 255, 255, 0.25);
                color: ' . esc_attr( $text_color ) . ' !important;
                padding: 5px 14px;
                border-radius: 20px;
                text-decoration: none !important;
                font-size: 12px;
                font-weight: 800;
                text-transform: uppercase;
                letter-spacing: 0.6px;
                border: 1px solid rgba(255, 255, 255, 0.5);
                transition: background 0.2s, transform 0.2s;
                display: inline-flex;
                align-items: center;
            }
            .voc-alert-btn:hover { 
                background: rgba(255, 255, 255, 0.45); 
                transform: translateY(-1px);
            }
            .voc-alert-dismiss {
                position: absolute;
                right: 14px;
                top: 50%;
                transform: translateY(-50%);
                background: transparent !important;
                border: none !important;
                color: ' . esc_attr( $text_color ) . ' !important;
                cursor: pointer;
                opacity: 0.85;
                padding: 4px !important;
                margin: 0 !important;
                line-height: 1 !important;
                height: 28px;
                width: 28px;
                display: flex;
                align-items: center;
                justify-content: center;
                border-radius: 50%;
                box-shadow: none !important;
                transition: background 0.2s, opacity 0.2s;
            }
            .voc-alert-dismiss:hover { 
                opacity: 1; 
                background: rgba(0, 0, 0, 0.18) !important; 
            }
            .voc-alert-dismiss svg { 
                width: 16px; 
                height: 16px; 
                stroke-width: 2.5; 
                stroke: currentColor; 
                fill: none; 
            }
        </style>';

        $html .= '<div class="voc-alert-bar-wrap" id="' . esc_attr( $bar_id ) . '" role="region" aria-label="Municipal Alert Bar">
            <div class="voc-alert-bar-inner">
                <span class="voc-alert-bar-text">
                    ' . $icon_svg . ' ' . esc_html( $alert_text ) . '
                </span>';

        if ( ! empty( $btn_text ) && ! empty( $btn_url ) ) {
            $html .= '<a href="' . esc_url( $btn_url ) . '" class="voc-alert-btn">' . esc_html( $btn_text ) . ' &rarr;</a>';
        }

        $html .= '</div>
            <button type="button" class="voc-alert-dismiss" aria-label="Dismiss Alert" onclick="vocDismissAlert(\'' . esc_js( $bar_id ) . '\')">
                <svg viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
            </button>
        </div>';

        $html .= '<script>
        (function() {
            var barId = "' . esc_js( $bar_id ) . '";
            var dismissedAt = localStorage.getItem(barId);
            if (dismissedAt && (Date.now() - parseInt(dismissedAt, 10)) < 86400000) {
                var el = document.getElementById(barId);
                if (el) el.style.display = "none";
            }
        })();

        if (typeof vocDismissAlert !== "function") {
            function vocDismissAlert(id) {
                var el = document.getElementById(id);
                if (el) {
                    el.style.opacity = "0";
                    setTimeout(function() { el.style.display = "none"; }, 250);
                }
                localStorage.setItem(id, Date.now().toString());
            }
        }
        </script>';

        return $html;
    }
}

add_action( 'init', function() {
    add_shortcode( 'villocity_alert_bar', 'villocity_render_alert_bar' );
    add_shortcode( 'villocity_ticker', 'villocity_render_alert_bar' );
});

// ==========================================
// 2. ADMIN SETTINGS PAGE RENDERER
// ==========================================
if ( ! function_exists( 'voc_alert_bar_render_admin_page' ) ) {
    function voc_alert_bar_render_admin_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $message = '';

        // Form Submission Handling
        if ( isset( $_POST['voc_save_alert_nonce'] ) && wp_verify_nonce( $_POST['voc_save_alert_nonce'], 'voc_save_alert' ) ) {
            update_option( 'villocity_alert_enabled', isset( $_POST['villocity_alert_enabled'] ) ? 'yes' : 'no' );
            if ( isset( $_POST['villocity_alert_text'] ) ) { update_option( 'villocity_alert_text', sanitize_text_field( $_POST['villocity_alert_text'] ) ); }
            if ( isset( $_POST['villocity_alert_type'] ) ) { update_option( 'villocity_alert_type', sanitize_text_field( $_POST['villocity_alert_type'] ) ); }
            if ( isset( $_POST['villocity_alert_btn_text'] ) ) { update_option( 'villocity_alert_btn_text', sanitize_text_field( $_POST['villocity_alert_btn_text'] ) ); }
            if ( isset( $_POST['villocity_alert_btn_url'] ) ) { update_option( 'villocity_alert_btn_url', esc_url_raw( $_POST['villocity_alert_btn_url'] ) ); }
            if ( isset( $_POST['villocity_alert_bg'] ) ) { update_option( 'villocity_alert_bg', sanitize_hex_color( $_POST['villocity_alert_bg'] ) ); }

            if ( function_exists( 'villocity_clear_all_caches' ) ) {
                villocity_clear_all_caches();
            }

            $message = '<div class="voc-gemini-notice"><h3>' . (function_exists('villocity_get_icon') ? villocity_get_icon('check') : '✓') . ' Alert Bar Configuration Saved</h3><p style="margin:0;">Public notice bar updated and site caches purged.</p></div>';
        }

        $enabled   = get_option( 'villocity_alert_enabled', 'yes' );
        $text      = get_option( 'villocity_alert_text', 'Farmers\' Market Held in the Village Green Every Thursday.' );
        $type      = get_option( 'villocity_alert_type', 'info' );
        $btn_text  = get_option( 'villocity_alert_btn_text', '' );
        $btn_url   = get_option( 'villocity_alert_btn_url', '' );
        $custom_bg = get_option( 'villocity_alert_bg', '' );

        echo '<div class="voc-gemini-dashboard">' . $message;

        if ( function_exists( 'villocity_render_dashboard_header' ) ) {
            villocity_render_dashboard_header( 'ADA Alert Bar & Ticker', 'Configure municipal emergency banners, accessibility tickers, and call-to-action buttons.', 'alert' );
        }

        echo '
        <form method="post" style="margin-top: 24px;">
            ' . wp_nonce_field( 'voc_save_alert', 'voc_save_alert_nonce', true, false ) . '

            <div class="voc-gemini-card">
                <h3>' . (function_exists('villocity_get_icon') ? villocity_get_icon('alert') : '⚠️') . ' Public Notice Settings</h3>

                <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:14px; padding:18px 22px; margin-bottom:24px;">
                    <label style="font-weight:700; cursor:pointer; display:flex; align-items:center; gap:12px; color:#0f172a; font-size:15px;">
                        <input type="checkbox" name="villocity_alert_enabled" value="yes" ' . checked( $enabled, 'yes', false ) . ' style="width:20px; height:20px;">
                        <span>Enable Public Notice Bar Site-Wide</span>
                    </label>
                    <p style="margin:6px 0 0 32px; font-size:13px; color:#64748b;">Displays at the top of pages or via the shortcodes <code>[villocity_alert_bar]</code> and <code>[villocity_ticker]</code>.</p>
                </div>

                <table class="voc-gemini-data-table" style="max-width:750px;">
                    <tr>
                        <th style="width:220px;">Alert Severity Level</th>
                        <td>
                            <select name="villocity_alert_type" class="voc-gemini-select">
                                <option value="info" ' . selected( $type, 'info', false ) . '>Informational (Custom or Brand Background)</option>
                                <option value="warning" ' . selected( $type, 'warning', false ) . '>Warning Notice (Amber Background)</option>
                                <option value="critical" ' . selected( $type, 'critical', false ) . '>Critical / Emergency (Red Background)</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th>Notice Message Text</th>
                        <td>
                            <textarea name="villocity_alert_text" class="voc-gemini-textarea" rows="3" placeholder="Enter notice headline...">' . esc_textarea( $text ) . '</textarea>
                        </td>
                    </tr>
                    <tr>
                        <th>Action Button Label</th>
                        <td>
                            <input type="text" name="villocity_alert_btn_text" value="' . esc_attr( $btn_text ) . '" class="voc-gemini-input" placeholder="e.g. Learn More, View Schedule">
                        </td>
                    </tr>
                    <tr>
                        <th>Action Button Link URL</th>
                        <td>
                            <input type="url" name="villocity_alert_btn_url" value="' . esc_attr( $btn_url ) . '" class="voc-gemini-input" placeholder="https://example.com/details">
                        </td>
                    </tr>
                    <tr>
                        <th>Custom Background (Info Level)</th>
                        <td>
                            <input type="color" name="villocity_alert_bg" value="' . esc_attr( $custom_bg ? $custom_bg : '#860038' ) . '" style="width:44px; height:44px; border:none; border-radius:10px; cursor:pointer;">
                        </td>
                    </tr>
                </table>

                <div style="margin-top: 32px; padding-top: 24px; border-top: 1px solid #f1f5f9;">
                    <button type="submit" class="voc-gemini-btn-primary">' . (function_exists('villocity_get_icon') ? villocity_get_icon('check') : '✓') . ' Save Notice Configuration</button>
                </div>
            </div>
        </form>

        <div class="voc-gemini-card">
            <h3>' . (function_exists('villocity_get_icon') ? villocity_get_icon('tag') : '🏷') . ' Integration Shortcodes</h3>
            <p style="color:#64748b; margin-top:0;">Paste either shortcode into Elementor, Gutenberg, or theme header templates:</p>
            <div style="display:flex; gap:12px; flex-wrap:wrap; margin-top:16px;">
                <code style="padding:10px 16px; background:#f1f5f9; border-radius:10px; font-size:14px; font-weight:700;">[villocity_alert_bar]</code>
                <code style="padding:10px 16px; background:#f1f5f9; border-radius:6px; font-size:14px; font-weight:700;">[villocity_ticker]</code>
            </div>
        </div>
        </div>';
    }
}