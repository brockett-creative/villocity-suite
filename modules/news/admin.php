<?php
/**
 * Module Name: VilloCity Local News (Admin)
 * Module URI:  https://villocity.org
 * Description: WP Dashboard Widget, RSS source management, white-label settings, live diagnostic health reports, news hub administration, bulk management, restore, ADA compliance, and permanent trash purge tools.
 * Version:     2.9.5 (Dynamic Live RSS Health Diagnostics & Interactive Source Repeater)
 * Author:      Brockett Creative Group, Inc.
 * Author URI:  https://brockettcreative.com
 * Text Domain: villocity-suite
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

// ==========================================
// 0. GLOBAL HELPER: PARSE RSS SOURCES
// ==========================================
if ( ! function_exists( 'voc_news_get_parsed_sources' ) ) {
    function voc_news_get_parsed_sources() {
        $raw_sources = get_option( 'voc_news_rss_sources', false );
        $source_list = array();

        if ( $raw_sources === false || empty( $raw_sources ) ) {
            return array(
                array('name' => 'WKTV News', 'rss' => 'https://www.wktv.com/search/?f=rss&t=article&c=news/local', 'url' => 'https://www.wktv.com'),
                array('name' => 'WKTV Sports', 'rss' => 'https://www.wktv.com/search/?f=rss&t=article&c=sports', 'url' => 'https://www.wktv.com'),
                array('name' => 'Rome Sentinel', 'rss' => 'https://www.romesentinel.com/search/?f=rss&t=article&c=news', 'url' => 'https://www.romesentinel.com'),
                array('name' => 'CNY Homepage', 'rss' => 'https://www.cnyhomepage.com/feed/', 'url' => 'https://www.cnyhomepage.com'),
                array('name' => 'CNY Homepage Sports', 'rss' => 'https://www.cnyhomepage.com/sports/feed/', 'url' => 'https://www.cnyhomepage.com'),
                array('name' => 'Waterville Times', 'rss' => 'https://www.watervilletimes.com/blog-feed.xml', 'url' => 'https://www.watervilletimes.com'),
                array('name' => 'Mohawk Valley Living', 'rss' => 'https://mohawkvalleyliving.com/feed/', 'url' => 'https://mohawkvalleyliving.com')
            );
        }

        if ( is_string( $raw_sources ) ) {
            $source_lines = array_filter( array_map( 'trim', explode( "\n", $raw_sources ) ) );
            foreach ( $source_lines as $line ) {
                $parts = array_map( 'trim', explode( '|', $line ) );
                if ( count( $parts ) >= 2 ) {
                    $source_list[] = array(
                        'name' => $parts[0],
                        'rss'  => $parts[1],
                        'url'  => isset($parts[2]) && !empty($parts[2]) ? $parts[2] : 'https://' . parse_url($parts[1], PHP_URL_HOST)
                    );
                }
            }
            return $source_list;
        }

        if ( is_array( $raw_sources ) ) {
            return $raw_sources;
        }

        return array();
    }
}

// ==========================================
// 1. WORDPRESS MAIN DASHBOARD WIDGET
// ==========================================
if ( ! function_exists( 'voc_news_add_dashboard_widget' ) ) {
    add_action( 'wp_dashboard_setup', 'voc_news_add_dashboard_widget' );
    function voc_news_add_dashboard_widget() {
        if ( current_user_can( 'manage_options' ) ) {
            wp_add_dashboard_widget(
                'voc_news_dashboard_widget',
                '📰 Live News - At a Glance',
                'voc_news_render_dashboard_widget'
            );
        }
    }
}

if ( ! function_exists( 'voc_news_render_dashboard_widget' ) ) {
    function voc_news_render_dashboard_widget() {
        $stored_articles = get_option( 'voc_saved_news_articles_db', array() );
        $source_list     = function_exists('voc_news_get_parsed_sources') ? voc_news_get_parsed_sources() : array();

        $total_articles = count( $stored_articles );
        $total_sources  = count( $source_list );

        $total_clicks = 0;
        foreach ( $stored_articles as $art ) {
            if ( isset( $art['clicks'] ) ) {
                $total_clicks += (int) $art['clicks'];
            }
        }

        $top_articles = $stored_articles;
        usort( $top_articles, function($a, $b) {
            return (isset($b['clicks']) ? (int)$b['clicks'] : 0) <=> (isset($a['clicks']) ? (int)$a['clicks'] : 0);
        });
        $top_3 = array_slice( array_filter($top_articles, function($art){ return !empty($art['clicks']); }), 0, 3 );

        $dashboard_url = admin_url( 'admin.php?page=voc-news-settings' );
        $primary_col    = get_option('villocity_global_primary', get_option('voc_news_primary_color', '#860038'));
        $accent_col     = get_option('villocity_global_accent', get_option('voc_news_accent_color', '#fadceb'));
        ?>
        <style>
            .voc-widget-grid { display: flex; gap: 10px; margin-bottom: 16px; text-align: center; font-family: var(--voc-font-family, inherit) !important; }
            .voc-widget-stat { flex: 1; background: #f8f9fa; border: 1px solid #e1e3e1; border-radius: 12px; padding: 12px 6px; }
            .voc-widget-stat strong { display: block; font-size: 20px; color: <?php echo esc_attr($primary_col); ?>; font-weight: 700; line-height: 1.2; }
            .voc-widget-stat span { font-size: 11px; color: #50575e; text-transform: uppercase; font-weight: 600; }
            .voc-widget-top { margin-bottom: 16px; }
            .voc-widget-top-title { font-weight: 600; font-size: 12px; color: #1d2327; text-transform: uppercase; margin-bottom: 8px; border-bottom: 1px solid #f0f4f8; padding-bottom: 6px; }
            .voc-widget-item { font-size: 13px; margin-bottom: 8px; display: flex; justify-content: space-between; gap: 10px; align-items: center; }
            .voc-widget-item a { text-decoration: none; font-weight: 500; color: <?php echo esc_attr($primary_col); ?>; text-overflow: ellipsis; overflow: hidden; white-space: nowrap; max-width: 75%; }
            .voc-widget-item a:hover { text-decoration: underline; }
            .voc-widget-badge { background: <?php echo esc_attr($accent_col); ?>; color: <?php echo esc_attr($primary_col); ?>; font-weight: 600; font-size: 11px; padding: 2px 8px; border-radius: 10px; flex-shrink: 0; }
            .voc-widget-footer { border-top: 1px solid #f0f4f8; padding-top: 12px; text-align: right; }
            .voc-widget-btn { display: inline-block; background: <?php echo esc_attr($primary_col); ?>; color: #ffffff !important; text-decoration: none !important; font-weight: 500; padding: 8px 18px; border-radius: 20px; font-size: 13px; transition: opacity 0.2s; }
            .voc-widget-btn:hover { opacity: 0.9; }
        </style>

        <div class="voc-widget-grid">
            <div class="voc-widget-stat">
                <strong><?php echo esc_html( $total_clicks ); ?></strong>
                <span>Total Clicks</span>
            </div>
            <div class="voc-widget-stat">
                <strong><?php echo esc_html( $total_articles ); ?></strong>
                <span>Active Stories</span>
            </div>
            <div class="voc-widget-stat">
                <strong><?php echo esc_html( $total_sources ); ?></strong>
                <span>Feeds</span>
            </div>
        </div>

        <?php if ( ! empty( $top_3 ) ) : ?>
            <div class="voc-widget-top">
                <div class="voc-widget-top-title">🏆 Most Read Stories</div>
                <?php foreach ( $top_3 as $art ) : ?>
                    <div class="voc-widget-item">
                        <a href="<?php echo esc_url( $art['link'] ); ?>" target="_blank" title="<?php echo esc_attr( $art['title'] ); ?>"><?php echo esc_html( $art['title'] ); ?></a>
                        <span class="voc-widget-badge"><?php echo esc_html( $art['clicks'] ); ?> clicks</span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="voc-widget-footer">
            <a href="<?php echo esc_url( $dashboard_url ); ?>" class="voc-widget-btn">Open Live News Hub &rarr;</a>
        </div>
        <?php
    }
}

// ==========================================
// 2. WP ADMIN MENU ROUTING & SETTINGS
// ==========================================
if ( ! function_exists( 'voc_news_add_tools_menu' ) ) {
    add_action( 'admin_menu', 'voc_news_add_tools_menu', 20 );
    function voc_news_add_tools_menu() {
        if ( menu_page_url( 'villocity-hub', false ) ) {
            add_submenu_page(
                'villocity-hub',
                'Live News Settings',
                'Live News',
                'manage_options',
                'voc-news-settings',
                'voc_news_render_settings_page'
            );
        }
    }
}

if ( ! function_exists( 'voc_news_render_settings_page' ) ) {
    function voc_news_render_settings_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $message = '';
        $database_option_key = 'voc_saved_news_articles_db';

        // --- FORM HANDLING ---
        if ( isset( $_POST['voc_save_settings_nonce'] ) && wp_verify_nonce( $_POST['voc_save_settings_nonce'], 'voc_save_settings' ) ) {
            
            if ( isset( $_POST['voc_news_save_general'] ) ) {
                if ( isset( $_POST['voc_news_retention_days'] ) ) { update_option( 'voc_news_retention_days', max(1, (int) $_POST['voc_news_retention_days']) ); }
                if ( isset( $_POST['voc_news_include_keywords'] ) ) { update_option( 'voc_news_include_keywords', sanitize_text_field( $_POST['voc_news_include_keywords'] ) ); }
                if ( isset( $_POST['voc_news_exclude_keywords'] ) ) { update_option( 'voc_news_exclude_keywords', sanitize_text_field( $_POST['voc_news_exclude_keywords'] ) ); }
                if ( isset( $_POST['voc_news_category_tabs'] ) ) { update_option( 'voc_news_category_tabs', sanitize_textarea_field( $_POST['voc_news_category_tabs'] ) ); }
                
                $search_targets = isset($_POST['voc_news_search_targets']) && is_array($_POST['voc_news_search_targets']) ? array_map('sanitize_text_field', $_POST['voc_news_search_targets']) : array('title', 'desc');
                update_option( 'voc_news_search_targets', $search_targets );

                if ( isset( $_POST['voc_news_header_title'] ) ) { update_option( 'voc_news_header_title', sanitize_text_field( $_POST['voc_news_header_title'] ) ); }
                if ( isset( $_POST['voc_news_header_subtitle'] ) ) { update_option( 'voc_news_header_subtitle', sanitize_text_field( $_POST['voc_news_header_subtitle'] ) ); }
                if ( isset( $_POST['voc_news_mini_title'] ) ) { update_option( 'voc_news_mini_title', sanitize_text_field( $_POST['voc_news_mini_title'] ) ); }

                $old_schedule = get_option( 'voc_news_cron_schedule', 'twicedaily' );
                $new_schedule = isset( $_POST['voc_news_cron_schedule'] ) ? sanitize_text_field( $_POST['voc_news_cron_schedule'] ) : 'twicedaily';
                update_option( 'voc_news_cron_schedule', $new_schedule );

                if ( $old_schedule !== $new_schedule ) {
                    wp_clear_scheduled_hook( 'voc_news_scheduled_sync_hook' );
                    if ( $new_schedule !== 'disabled' ) { wp_schedule_event( time(), $new_schedule, 'voc_news_scheduled_sync_hook' ); }
                }

                update_option( 'voc_news_enable_digest', isset( $_POST['voc_news_enable_digest'] ) ? '1' : '0' );
                if ( isset( $_POST['voc_news_digest_email'] ) ) { update_option( 'voc_news_digest_email', sanitize_email( $_POST['voc_news_digest_email'] ) ); }

                $message = '<div class="voc-gemini-notice"><h3>' . (function_exists('villocity_get_icon') ? villocity_get_icon('check') : '✓') . ' Settings Saved</h3><p style="margin:0;">Your configurations have been updated.</p></div>';
                delete_transient( 'voc_news_plugin_cache_v651' );
                delete_transient( 'voc_news_feed_main_html' );
            }

            if ( isset( $_POST['voc_news_save_sources'] ) ) {
                $new_sources = array();
                if ( ! empty($_POST['voc_source_name']) && is_array($_POST['voc_source_name']) ) {
                    $names = $_POST['voc_source_name'];
                    $rsses = $_POST['voc_source_rss'];
                    $urls  = isset($_POST['voc_source_url']) && is_array($_POST['voc_source_url']) ? $_POST['voc_source_url'] : array();
                    
                    for ($i = 0; $i < count($names); $i++) {
                        $n = sanitize_text_field($names[$i]);
                        $r = esc_url_raw($rsses[$i]);
                        $u = isset($urls[$i]) ? esc_url_raw($urls[$i]) : '';
                        
                        if ( ! empty($n) && ! empty($r) ) {
                            if ( empty($u) ) { $u = 'https://' . parse_url($r, PHP_URL_HOST); }
                            $new_sources[] = array('name' => $n, 'rss' => $r, 'url' => $u);
                        }
                    }
                }
                
                update_option( 'voc_news_rss_sources', array_values($new_sources) );
                $message = '<div class="voc-gemini-notice"><h3>' . (function_exists('villocity_get_icon') ? villocity_get_icon('rss') : '📡') . ' Sources Saved</h3><p style="margin:0;">Monitored feeds updated.</p></div>';
                delete_transient( 'voc_news_plugin_cache_v651' );
                delete_transient( 'voc_news_feed_main_html' );
            }

            // --- BULK DELETE / TRASH ACTION HANDLER ---
            if ( isset( $_POST['voc_action_bulk_delete'] ) && ! empty( $_POST['voc_selected_hashes'] ) && is_array( $_POST['voc_selected_hashes'] ) ) {
                $hashes_to_delete = array_map( 'sanitize_text_field', $_POST['voc_selected_hashes'] );
                $stored_articles = get_option( $database_option_key, array() );
                $trashed_articles = get_option( 'voc_trashed_news_articles_db', array() );
                if ( ! is_array( $trashed_articles ) ) { $trashed_articles = array(); }

                $filtered_articles = array();
                $trashed_count = 0;

                foreach ( $stored_articles as $art ) {
                    $hash = md5( $art['link'] . $art['title'] );
                    if ( in_array( $hash, $hashes_to_delete, true ) ) {
                        $art['hash'] = $hash;
                        $art['trashed_date'] = date('M j, Y');
                        array_unshift( $trashed_articles, $art );
                        $trashed_count++;
                    } else {
                        $filtered_articles[] = $art;
                    }
                }

                update_option( $database_option_key, array_values( $filtered_articles ), false );
                update_option( 'voc_trashed_news_articles_db', array_values( $trashed_articles ), false );
                delete_transient( 'voc_news_plugin_cache_v651' );
                delete_transient( 'voc_news_feed_main_html' );

                $message = '<div class="voc-gemini-notice"><h3>' . (function_exists('villocity_get_icon') ? villocity_get_icon('trash') : '🗑') . ' Bulk Trash Completed</h3><p style="margin:0;">Moved ' . intval($trashed_count) . ' articles to trash.</p></div>';
            }

            // --- SINGLE RESTORE ACTION HANDLER ---
            if ( isset( $_POST['voc_action_restore_single'] ) && ! empty( $_POST['voc_restore_hash'] ) ) {
                $restore_hash = sanitize_text_field( $_POST['voc_restore_hash'] );
                $stored_articles = get_option( $database_option_key, array() );
                $trashed_articles = get_option( 'voc_trashed_news_articles_db', array() );
                if ( ! is_array( $trashed_articles ) ) { $trashed_articles = array(); }

                $filtered_trash = array();
                $restored_item = null;

                foreach ( $trashed_articles as $t_art ) {
                    $hash = md5( $t_art['link'] . $t_art['title'] );
                    if ( $hash === $restore_hash ) {
                        unset($t_art['hash'], $t_art['trashed_date']);
                        $restored_item = $t_art;
                    } else {
                        $filtered_trash[] = $t_art;
                    }
                }

                if ( $restored_item ) {
                    array_unshift( $stored_articles, $restored_item );
                    usort( $stored_articles, function($a, $b) {
                        return ($b['timestamp'] ?? 0) <=> ($a['timestamp'] ?? 0);
                    });
                }

                update_option( $database_option_key, array_values( $stored_articles ), false );
                update_option( 'voc_trashed_news_articles_db', array_values( $filtered_trash ), false );
                delete_transient( 'voc_news_plugin_cache_v651' );
                delete_transient( 'voc_news_feed_main_html' );

                $message = '<div class="voc-gemini-notice"><h3>' . (function_exists('villocity_get_icon') ? villocity_get_icon('check') : '✓') . ' Article Restored</h3><p style="margin:0;">Moved article back to live feed.</p></div>';
            }

            // --- BULK RESTORE ACTION HANDLER ---
            if ( isset( $_POST['voc_action_bulk_restore'] ) && ! empty( $_POST['voc_selected_restore_hashes'] ) && is_array( $_POST['voc_selected_restore_hashes'] ) ) {
                $hashes_to_restore = array_map( 'sanitize_text_field', $_POST['voc_selected_restore_hashes'] );
                $stored_articles = get_option( $database_option_key, array() );
                $trashed_articles = get_option( 'voc_trashed_news_articles_db', array() );
                if ( ! is_array( $trashed_articles ) ) { $trashed_articles = array(); }

                $filtered_trash = array();
                $restored_count = 0;

                foreach ( $trashed_articles as $t_art ) {
                    $hash = md5( $t_art['link'] . $t_art['title'] );
                    if ( in_array( $hash, $hashes_to_restore, true ) ) {
                        unset($t_art['hash'], $t_art['trashed_date']);
                        array_unshift( $stored_articles, $t_art );
                        $restored_count++;
                    } else {
                        $filtered_trash[] = $t_art;
                    }
                }

                usort( $stored_articles, function($a, $b) {
                    return ($b['timestamp'] ?? 0) <=> ($a['timestamp'] ?? 0);
                });

                update_option( $database_option_key, array_values( $stored_articles ), false );
                update_option( 'voc_trashed_news_articles_db', array_values( $filtered_trash ), false );
                delete_transient( 'voc_news_plugin_cache_v651' );
                delete_transient( 'voc_news_feed_main_html' );

                $message = '<div class="voc-gemini-notice"><h3>' . (function_exists('villocity_get_icon') ? villocity_get_icon('check') : '✓') . ' Bulk Restore Completed</h3><p style="margin:0;">Restored ' . intval($restored_count) . ' articles to live feed.</p></div>';
            }

            // --- SINGLE PERMANENT DELETE HANDLER ---
            if ( isset( $_POST['voc_action_delete_permanent_single'] ) && ! empty( $_POST['voc_perm_delete_hash'] ) ) {
                $perm_hash = sanitize_text_field( $_POST['voc_perm_delete_hash'] );
                $trashed_articles = get_option( 'voc_trashed_news_articles_db', array() );
                if ( ! is_array( $trashed_articles ) ) { $trashed_articles = array(); }

                $filtered_trash = array();
                foreach ( $trashed_articles as $t_art ) {
                    $hash = md5( $t_art['link'] . $t_art['title'] );
                    if ( $hash !== $perm_hash ) {
                        $filtered_trash[] = $t_art;
                    }
                }

                update_option( 'voc_trashed_news_articles_db', array_values( $filtered_trash ), false );
                $message = '<div class="voc-gemini-notice"><h3>Article Deleted Permanently</h3></div>';
            }

            // --- BULK PERMANENT DELETE HANDLER ---
            if ( isset( $_POST['voc_action_bulk_delete_permanent'] ) && ! empty( $_POST['voc_selected_restore_hashes'] ) && is_array( $_POST['voc_selected_restore_hashes'] ) ) {
                $hashes_to_perm_delete = array_map( 'sanitize_text_field', $_POST['voc_selected_restore_hashes'] );
                $trashed_articles = get_option( 'voc_trashed_news_articles_db', array() );
                if ( ! is_array( $trashed_articles ) ) { $trashed_articles = array(); }

                $filtered_trash = array();
                $deleted_count = 0;

                foreach ( $trashed_articles as $t_art ) {
                    $hash = md5( $t_art['link'] . $t_art['title'] );
                    if ( in_array( $hash, $hashes_to_perm_delete, true ) ) {
                        $deleted_count++;
                    } else {
                        $filtered_trash[] = $t_art;
                    }
                }

                update_option( 'voc_trashed_news_articles_db', array_values( $filtered_trash ), false );
                $message = '<div class="voc-gemini-notice"><h3>Permanently Deleted ' . intval($deleted_count) . ' Articles</h3></div>';
            }

            // --- EMPTY TRASH BIN HANDLER ---
            if ( isset( $_POST['voc_action_empty_trash'] ) ) {
                update_option( 'voc_trashed_news_articles_db', array(), false );
                $message = '<div class="voc-gemini-notice"><h3>Trash Bin Emptied Successfully</h3></div>';
            }

            if ( isset( $_POST['voc_action_delete_single'] ) && ! empty( $_POST['voc_delete_hash'] ) ) {
                $delete_hash = sanitize_text_field( $_POST['voc_delete_hash'] );
                $stored_articles = get_option( $database_option_key, array() );
                $trashed_articles = get_option( 'voc_trashed_news_articles_db', array() );
                if ( ! is_array( $trashed_articles ) ) { $trashed_articles = array(); }

                $filtered_articles = array();
                $trashed_item = null;
                foreach ( $stored_articles as $art ) {
                    $hash = md5( $art['link'] . $art['title'] );
                    if ( $hash === $delete_hash ) {
                        $art['hash'] = $hash;
                        $art['trashed_date'] = date('M j, Y');
                        $trashed_item = $art;
                        continue; 
                    }
                    $filtered_articles[] = $art;
                }

                if ( $trashed_item ) {
                    array_unshift( $trashed_articles, $trashed_item );
                }

                update_option( $database_option_key, array_values( $filtered_articles ), false );
                update_option( 'voc_trashed_news_articles_db', array_values( $trashed_articles ), false );
                delete_transient( 'voc_news_plugin_cache_v651' );
                delete_transient( 'voc_news_feed_main_html' );
                $message = '<div class="voc-gemini-notice"><h3>Article Moved to Trash</h3></div>';
            }

            if ( isset( $_POST['voc_action_force_sync'] ) ) {
                delete_transient( 'voc_news_plugin_cache_v651' );
                delete_transient( 'voc_news_feed_main_html' );
                if ( function_exists('voc_news_fetch_rss_feeds_background') ) { voc_news_fetch_rss_feeds_background(); }
                $message = '<div class="voc-gemini-notice"><h3>RSS Sync Executed</h3></div>';
            }
        }

        // --- FETCH DATA ---
        $current_days     = get_option( 'voc_news_retention_days', 120 );
        $current_includes = get_option( 'voc_news_include_keywords', 'clinton, kirkland, clark mills, clinton warriors, ccs sports, hamilton college' );
        $current_excludes = get_option( 'voc_news_exclude_keywords', 'classifieds, classified, help wanted, job listing, garage sale, obituary, obituaries' );
        $search_targets   = get_option( 'voc_news_search_targets', array('title', 'desc') );
        if ( ! is_array($search_targets) ) { $search_targets = array('title', 'desc'); }

        $cron_schedule = get_option( 'voc_news_cron_schedule', 'twicedaily' );
        $enable_digest = get_option( 'voc_news_enable_digest', '1' );
        $digest_email  = get_option( 'voc_news_digest_email', get_option('admin_email') );

        $primary_col   = get_option('villocity_global_primary', get_option('voc_news_primary_color', '#860038'));
        $main_title    = get_option('voc_news_header_title', 'Clinton Local News');
        $main_subtitle = get_option('voc_news_header_subtitle', 'Live updates and headlines from around Clinton and Town of Kirkland.');
        $mini_title    = get_option('voc_news_mini_title', 'Latest Local Headlines');

        $source_list     = function_exists('voc_news_get_parsed_sources') ? voc_news_get_parsed_sources() : array();
        $stored_articles = get_option( $database_option_key, array() );
        $article_count   = is_array( $stored_articles ) ? count( $stored_articles ) : 0;

        $trashed_articles = get_option( 'voc_trashed_news_articles_db', array() );
        $trash_count      = is_array( $trashed_articles ) ? count( $trashed_articles ) : 0;
        $feed_statuses    = get_option( 'voc_news_feed_statuses', array() );

        // --- RENDER DASHBOARD ---
        echo '<div class="voc-gemini-dashboard">' . $message;
        
        if ( function_exists('villocity_render_dashboard_header') ) {
            villocity_render_dashboard_header( 'Live News Hub', 'Manage aggregation rules, white-label text, background automation, and email digests.', 'news' );
        }

        // TABS NAVIGATION (ADA COMPLIANT FOCUS RINGS & CONTRAST)
        echo '
            <style>
                .voc-gemini-tab:focus-visible, .voc-gemini-btn-primary:focus-visible, .voc-gemini-btn-secondary:focus-visible {
                    outline: 3px solid #60a5fa !important;
                    outline-offset: 2px !important;
                }
            </style>
            <div class="voc-gemini-tabs" style="margin-top:24px;" role="tablist" aria-label="News Hub Management Tabs">
                <button type="button" class="voc-gemini-tab active" role="tab" aria-selected="true" onclick="voc_switch_tab(event, \'voc-tab-articles\')">' . (function_exists('villocity_get_icon') ? villocity_get_icon('globe') : '🌐') . ' Feed Content (' . esc_html($article_count) . ')</button>
                <button type="button" class="voc-gemini-tab" role="tab" aria-selected="false" onclick="voc_switch_tab(event, \'voc-tab-diagnostics\')">' . (function_exists('villocity_get_icon') ? villocity_get_icon('check') : '✓') . ' RSS Health Status</button>
                <button type="button" class="voc-gemini-tab" role="tab" aria-selected="false" onclick="voc_switch_tab(event, \'voc-tab-sources\')">' . (function_exists('villocity_get_icon') ? villocity_get_icon('rss') : '📡') . ' RSS Sources</button>
                <button type="button" class="voc-gemini-tab" role="tab" aria-selected="false" onclick="voc_switch_tab(event, \'voc-tab-settings\')">' . (function_exists('villocity_get_icon') ? villocity_get_icon('settings') : '⚙') . ' UI Settings</button>
                <button type="button" class="voc-gemini-tab" role="tab" aria-selected="false" onclick="voc_switch_tab(event, \'voc-tab-shortcodes\')">' . (function_exists('villocity_get_icon') ? villocity_get_icon('tag') : '🏷') . ' Shortcodes</button>
                <button type="button" class="voc-gemini-tab" role="tab" aria-selected="false" onclick="voc_switch_tab(event, \'voc-tab-automation\')">' . (function_exists('villocity_get_icon') ? villocity_get_icon('clock') : '🕒') . ' Automation & Digest</button>
                <button type="button" class="voc-gemini-tab" role="tab" aria-selected="false" onclick="voc_switch_tab(event, \'voc-tab-trash\')">' . (function_exists('villocity_get_icon') ? villocity_get_icon('trash') : '🗑') . ' Trash (' . esc_html($trash_count) . ')</button>
                <button type="button" class="voc-gemini-tab" role="tab" aria-selected="false" onclick="voc_switch_tab(event, \'voc-tab-analytics\')">' . (function_exists('villocity_get_icon') ? villocity_get_icon('chart') : '📊') . ' Analytics</button>
                <button type="button" class="voc-gemini-tab" role="tab" aria-selected="false" onclick="voc_switch_tab(event, \'voc-tab-tools\')">' . (function_exists('villocity_get_icon') ? villocity_get_icon('tool') : '🛠') . ' Data Tools</button>
            </div>';

        // TAB 1: CONTENT MANAGEMENT
        echo '
        <div id="voc-tab-articles" class="voc-tab-content" role="tabpanel" style="display: block;">
            <form method="post" id="voc-bulk-articles-form">
                ' . wp_nonce_field( 'voc_save_settings', 'voc_save_settings_nonce', true, false ) . '
                <input type="hidden" name="voc_action_bulk_delete" value="1">

                <div style="background:#ffffff; border:1px solid #e2e8f0; border-radius:14px; padding:14px 20px; margin-bottom:16px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
                    <div style="display:flex; align-items:center; gap:10px;">
                        <input type="checkbox" id="voc-select-all-articles" aria-label="Select all articles" style="width:18px; height:18px; cursor:pointer;">
                        <label for="voc-select-all-articles" style="font-weight:700; font-size:14px; color:#0f172a; cursor:pointer;">Select All Articles</label>
                        <span id="voc-selected-count" style="font-size:12px; color:#64748b; margin-left:8px;">(0 selected)</span>
                    </div>

                    <button type="submit" id="voc-bulk-delete-btn" class="voc-gemini-btn-secondary" style="background:#fee2e2 !important; color:#991b1b !important; border-color:#fca5a5 !important; opacity:0.5; cursor:not-allowed;" disabled onclick="return confirm(\'Are you sure you want to move the selected articles to trash?\');">
                        ' . (function_exists('villocity_get_icon') ? villocity_get_icon('trash') : '🗑') . ' Move Selected to Trash
                    </button>
                </div>

                <div class="voc-gemini-card" style="padding: 0; overflow: hidden;">
                    <table class="voc-gemini-data-table">
                        <thead>
                            <tr>
                                <th style="width:40px; padding-left:20px;"></th>
                                <th>Date</th>
                                <th>Source</th>
                                <th>Headline</th>
                                <th style="text-align:right; padding-right:20px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>';
                        if ( ! empty( $stored_articles ) ) {
                            foreach ( $stored_articles as $art ) {
                                $hash = md5( $art['link'] . $art['title'] );
                                echo '<tr>
                                    <td style="padding-left:20px;">
                                        <input type="checkbox" name="voc_selected_hashes[]" value="' . esc_attr( $hash ) . '" class="voc-article-checkbox" aria-label="Select ' . esc_attr($art['title']) . '" style="width:18px; height:18px; cursor:pointer;">
                                    </td>
                                    <td><strong>' . esc_html( $art['display_date'] ) . '</strong></td>
                                    <td><span style="background:#e0e7ff; color:#1e40af; padding:4px 10px; border-radius:12px; font-weight:700; font-size:11px;">' . esc_html( $art['source'] ) . '</span></td>
                                    <td><a href="' . esc_url( $art['link'] ) . '" target="_blank" style="text-decoration:none; font-weight:600; color:' . esc_attr($primary_col) . ';">' . esc_html( $art['title'] ) . '</a></td>
                                    <td style="text-align:right; padding-right:20px;">
                                        <form method="post" style="display:inline-block;">
                                            ' . wp_nonce_field( 'voc_save_settings', 'voc_save_settings_nonce', true, false ) . '
                                            <input type="hidden" name="voc_action_delete_single" value="1">
                                            <input type="hidden" name="voc_delete_hash" value="' . esc_attr( $hash ) . '">
                                            <button type="submit" class="voc-gemini-btn-secondary" aria-label="Move ' . esc_attr($art['title']) . ' to trash" style="padding:4px 10px !important; background:#fee2e2 !important; color:#991b1b !important; border-color:#fca5a5 !important;">Trash</button>
                                        </form>
                                    </td>
                                </tr>';
                            }
                        } else { echo '<tr><td colspan="5" style="text-align:center; padding:30px;">No stored articles.</td></tr>'; }
        echo '          </tbody>
                    </table>
                </div>
            </form>
        </div>';

        // TAB 2: LIVE RSS HEALTH STATUS
        echo '
        <div id="voc-tab-diagnostics" class="voc-tab-content" role="tabpanel" style="display: none;">
            <div class="voc-gemini-card">
                <div style="display:flex; justify-content:space-between; align-items:center; border-bottom: 1px solid #f1f5f9; padding-bottom: 14px; margin-bottom:16px;">
                    <h3 style="margin:0; border:none; padding:0;">' . (function_exists('villocity_get_icon') ? villocity_get_icon('check') : '✓') . ' RSS Feed Diagnostic Status</h3>
                    <form method="post" style="margin:0;">
                        ' . wp_nonce_field( 'voc_save_settings', 'voc_save_settings_nonce', true, false ) . '
                        <input type="hidden" name="voc_action_force_sync" value="1">
                        <button type="submit" class="voc-gemini-btn-secondary">' . (function_exists('villocity_get_icon') ? villocity_get_icon('refresh') : '🔄') . ' Force Feed Sync Now</button>
                    </form>
                </div>

                <table class="voc-gemini-data-table">
                    <thead>
                        <tr>
                            <th>Source Outlet</th>
                            <th>RSS Feed URL</th>
                            <th>Last Sync Check</th>
                            <th>Matched Items</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>';
                    if ( ! empty($source_list) ) {
                        foreach ( $source_list as $src ) {
                            $status_key = md5($src['rss']);
                            
                            // Perform real live check if no cached status exists
                            if ( ! isset( $feed_statuses[$status_key] ) ) {
                                $test_resp = wp_remote_get( $src['rss'], array( 'timeout' => 5, 'sslverify' => false ) );
                                $code = is_wp_error($test_resp) ? 0 : wp_remote_retrieve_response_code($test_resp);
                                $feed_statuses[$status_key] = array(
                                    'status'    => ($code >= 200 && $code < 300) ? 'success' : 'error',
                                    'message'   => ($code >= 200 && $code < 300) ? "HTTP {$code} OK" : (is_wp_error($test_resp) ? $test_resp->get_error_message() : "HTTP {$code} Fail"),
                                    'code'      => $code,
                                    'last_sync' => current_time('mysql'),
                                    'matched'   => 0
                                );
                            }

                            $info = $feed_statuses[$status_key];
                            $is_ok = isset($info['status']) && $info['status'] === 'success';

                            echo '<tr>
                                <td><strong>' . esc_html($src['name']) . '</strong></td>
                                <td><a href="' . esc_url($src['rss']) . '" target="_blank" style="font-size:12px; color:#64748b;">' . esc_html($src['rss']) . '</a></td>
                                <td>' . (!empty($info['last_sync']) ? esc_html($info['last_sync']) : 'Just Checked') . '</td>
                                <td><strong style="color:#860038;">' . (isset($info['matched']) ? intval($info['matched']) : 0) . ' articles</strong></td>
                                <td>';
                                if ($is_ok) {
                                    echo '<span style="background:#f0fdf4; color:#166534; padding:4px 10px; border-radius:12px; font-weight:700; font-size:12px;">Active (HTTP ' . esc_html($info['code']) . ')</span>';
                                } else {
                                    echo '<span style="background:#fef2f2; color:#991b1b; padding:4px 10px; border-radius:12px; font-weight:700; font-size:12px;">' . esc_html(isset($info['message']) ? $info['message'] : 'Connection Error') . '</span>';
                                }
                            echo '</td>
                            </tr>';
                        }
                    } else { echo '<tr><td colspan="5">No RSS feeds configured.</td></tr>'; }
        echo '      </tbody>
                </table>
            </div>
        </div>';

        // TAB 3: DYNAMIC REPEATER RSS SOURCES TAB
        echo '
        <div id="voc-tab-sources" class="voc-tab-content" role="tabpanel" style="display: none;">
            <div class="voc-gemini-card">
                <form method="post">
                    ' . wp_nonce_field( 'voc_save_settings', 'voc_save_settings_nonce', true, false ) . '
                    <input type="hidden" name="voc_news_save_sources" value="1">
                    <h3>Monitored RSS Endpoints</h3>
                    <p style="color:#64748b; margin-top:0;">Add, edit, or remove RSS feed URLs to aggregate automatically into your live news stream.</p>
                    
                    <div id="voc-repeater-container">';
                    foreach( $source_list as $i => $src ) {
                        echo '
                        <div class="voc-feed-item-card" style="background:#f8fafc; padding:16px; border-radius:12px; border:1px solid #e2e8f0; margin-bottom:12px; display:flex; gap:12px; flex-wrap:wrap; align-items:center;">
                            <div style="flex:1; min-width:180px;">
                                <label style="display:block; font-size:11px; font-weight:700; color:#64748b; margin-bottom:4px;">Outlet Name</label>
                                <input type="text" name="voc_source_name[]" value="' . esc_attr($src['name']) . '" class="voc-gemini-input" placeholder="e.g. WKTV News">
                            </div>
                            <div style="flex:2; min-width:260px;">
                                <label style="display:block; font-size:11px; font-weight:700; color:#64748b; margin-bottom:4px;">RSS Feed URL</label>
                                <input type="url" name="voc_source_rss[]" value="' . esc_attr($src['rss']) . '" class="voc-gemini-input" placeholder="https://domain.com/feed/">
                            </div>
                            <div style="flex:1; min-width:180px;">
                                <label style="display:block; font-size:11px; font-weight:700; color:#64748b; margin-bottom:4px;">Main Website URL</label>
                                <input type="url" name="voc_source_url[]" value="' . esc_attr($src['url']) . '" class="voc-gemini-input" placeholder="https://domain.com">
                            </div>
                            <div style="margin-top:20px;">
                                <button type="button" class="voc-gemini-btn-secondary" style="background:#fee2e2 !important; color:#991b1b !important; border-color:#fca5a5 !important;" onclick="this.closest(\'.voc-feed-item-card\').remove()">Remove</button>
                            </div>
                        </div>';
                    }
        echo '      </div>

                    <div style="margin-top:20px; display:flex; justify-content:space-between; align-items:center;">
                        <button type="button" id="voc-add-feed-btn" class="voc-gemini-btn-secondary">' . (function_exists('villocity_get_icon') ? villocity_get_icon('plus') : '+') . ' Add New RSS Source</button>
                        <button type="submit" class="voc-gemini-btn-primary">Save Sources</button>
                    </div>
                </form>
            </div>
        </div>';

        // TAB 4: UI SETTINGS
        echo '
        <div id="voc-tab-settings" class="voc-tab-content" role="tabpanel" style="display: none;">
            <div class="voc-gemini-card">
                <form method="post">
                    ' . wp_nonce_field( 'voc_save_settings', 'voc_save_settings_nonce', true, false ) . '
                    <input type="hidden" name="voc_news_save_general" value="1">
                    
                    <h3>' . (function_exists('villocity_get_icon') ? villocity_get_icon('settings') : '✏️') . ' White-Label Text & Retention</h3>
                    <table class="voc-gemini-data-table">
                        <tr>
                            <th style="width:250px;"><label for="voc_news_header_title">Main Feed Title</label></th>
                            <td><input id="voc_news_header_title" name="voc_news_header_title" type="text" value="' . esc_attr( $main_title ) . '" class="voc-gemini-input"></td>
                        </tr>
                        <tr>
                            <th><label for="voc_news_header_subtitle">Main Feed Subtitle</label></th>
                            <td><input id="voc_news_header_subtitle" name="voc_news_header_subtitle" type="text" value="' . esc_attr( $main_subtitle ) . '" class="voc-gemini-input"></td>
                        </tr>
                        <tr>
                            <th><label for="voc_news_mini_title">Mini-Feed Title</label></th>
                            <td><input id="voc_news_mini_title" name="voc_news_mini_title" type="text" value="' . esc_attr( $mini_title ) . '" class="voc-gemini-input"></td>
                        </tr>
                        <tr>
                            <th><label for="voc_news_retention_days">Retention Period (Days)</label></th>
                            <td><input id="voc_news_retention_days" name="voc_news_retention_days" type="number" value="' . esc_attr( $current_days ) . '" class="voc-gemini-input" style="max-width:120px;"></td>
                        </tr>
                        <tr>
                            <th><label for="voc_news_include_keywords">Must-Include Keywords</label></th>
                            <td><input id="voc_news_include_keywords" name="voc_news_include_keywords" type="text" value="' . esc_attr( $current_includes ) . '" class="voc-gemini-input"></td>
                        </tr>
                        <tr>
                            <th><label for="voc_news_exclude_keywords">Exclude Keywords</label></th>
                            <td><input id="voc_news_exclude_keywords" name="voc_news_exclude_keywords" type="text" value="' . esc_attr( $current_excludes ) . '" class="voc-gemini-input"></td>
                        </tr>
                    </table>

                    <div style="margin-top: 32px;">
                        <button type="submit" class="voc-gemini-btn-primary">' . (function_exists('villocity_get_icon') ? villocity_get_icon('check') : '✓') . ' Save Configuration</button>
                    </div>
                </form>
            </div>
        </div>';

        // TAB 5: SHORTCODES
        echo '
        <div id="voc-tab-shortcodes" class="voc-tab-content" role="tabpanel" style="display: none;">
            <div style="display: flex; gap: 16px; flex-wrap: wrap;">
                <div class="voc-gemini-card" style="flex: 1; min-width: 300px;">
                    <strong style="color: ' . esc_attr($primary_col) . '; display:block; margin-bottom:6px;">Main News Page Shortcode</strong>
                    <code style="display:inline-block; padding:8px 12px; background:#f1f5f9; border-radius:6px; font-size:14px; font-weight:700;">[villocity_local_news]</code>
                    <p style="margin: 8px 0 0 0; font-size: 13px; color: #64748b;">Full feed layout with sidebar, keyword search, category pills, and pagination.</p>
                </div>
                <div class="voc-gemini-card" style="flex: 1; min-width: 300px;">
                    <strong style="color: ' . esc_attr($primary_col) . '; display:block; margin-bottom:6px;">Homepage Mini-Feed Shortcode</strong>
                    <code style="display:inline-block; padding:8px 12px; background:#f1f5f9; border-radius:6px; font-size:14px; font-weight:700;">[villocity_latest_news count="3"]</code>
                    <p style="margin: 8px 0 0 0; font-size: 13px; color: #64748b;">Compact horizontal card widget designed for sidebars or homepage sections.</p>
                </div>
            </div>
        </div>';

        // TAB 6: AUTOMATION & DIGEST
        echo '
        <div id="voc-tab-automation" class="voc-tab-content" role="tabpanel" style="display: none;">
            <div class="voc-gemini-card">
                <form method="post">
                    ' . wp_nonce_field( 'voc_save_settings', 'voc_save_settings_nonce', true, false ) . '
                    <input type="hidden" name="voc_news_save_general" value="1">
                    <h3>WP-Cron Background Sync</h3>
                    <table class="voc-gemini-data-table">
                        <tr>
                            <th style="width:250px;"><label for="voc_news_cron_schedule">Fetch Frequency</label></th>
                            <td>
                                <select id="voc_news_cron_schedule" name="voc_news_cron_schedule" class="voc-gemini-select" style="max-width:250px;">
                                    <option value="hourly" ' . selected($cron_schedule, 'hourly', false) . '>Hourly</option>
                                    <option value="twicedaily" ' . selected($cron_schedule, 'twicedaily', false) . '>Twice Daily</option>
                                    <option value="daily" ' . selected($cron_schedule, 'daily', false) . '>Daily</option>
                                </select>
                            </td>
                        </tr>
                    </table>
                    <div style="margin-top:20px;">
                        <button type="submit" class="voc-gemini-btn-primary">Save Automation</button>
                    </div>
                </form>
            </div>
        </div>';

        // TAB 7: TRASH BIN
        echo '
        <div id="voc-tab-trash" class="voc-tab-content" role="tabpanel" style="display: none;">
            <div style="background:#ffffff; border:1px solid #e2e8f0; border-radius:14px; padding:14px 20px; margin-bottom:16px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
                <div style="display:flex; align-items:center; gap:10px;">
                    <input type="checkbox" id="voc-select-all-trash" aria-label="Select all trashed articles" style="width:18px; height:18px; cursor:pointer;">
                    <label for="voc-select-all-trash" style="font-weight:700; font-size:14px; color:#0f172a; cursor:pointer;">Select All Trashed Articles</label>
                    <span id="voc-restore-selected-count" style="font-size:12px; color:#64748b; margin-left:8px;">(0 selected)</span>
                </div>

                <div style="display:flex; gap:8px;">
                    <form method="post" id="voc-bulk-restore-form" style="margin:0;">
                        ' . wp_nonce_field( 'voc_save_settings', 'voc_save_settings_nonce', true, false ) . '
                        <input type="hidden" name="voc_action_bulk_restore" value="1">
                        <div id="voc-bulk-restore-inputs"></div>
                        <button type="submit" id="voc-bulk-restore-btn" class="voc-gemini-btn-secondary" style="background:#dcfce7 !important; color:#166534 !important; border-color:#86efac !important; opacity:0.5; cursor:not-allowed;" disabled>
                            ' . (function_exists('villocity_get_icon') ? villocity_get_icon('check') : '✓') . ' Restore Selected
                        </button>
                    </form>

                    <form method="post" id="voc-bulk-perm-delete-form" style="margin:0;">
                        ' . wp_nonce_field( 'voc_save_settings', 'voc_save_settings_nonce', true, false ) . '
                        <input type="hidden" name="voc_action_bulk_delete_permanent" value="1">
                        <div id="voc-bulk-perm-inputs"></div>
                        <button type="submit" id="voc-bulk-perm-btn" class="voc-gemini-btn-secondary" style="background:#fee2e2 !important; color:#991b1b !important; border-color:#fca5a5 !important; opacity:0.5; cursor:not-allowed;" disabled onclick="return confirm(\'Permanently delete selected articles? This action CANNOT be undone.\');">
                            ' . (function_exists('villocity_get_icon') ? villocity_get_icon('trash') : '🗑') . ' Delete Selected Forever
                        </button>
                    </form>

                    <form method="post" style="margin:0;">
                        ' . wp_nonce_field( 'voc_save_settings', 'voc_save_settings_nonce', true, false ) . '
                        <input type="hidden" name="voc_action_empty_trash" value="1">
                        <button type="submit" class="voc-gemini-btn-secondary" style="background:#991b1b !important; color:#ffffff !important; border-color:#7f1d1d !important;" onclick="return confirm(\'Are you sure you want to EMPTY THE ENTIRE TRASH BIN? This cannot be undone.\');">
                            Empty Trash Bin
                        </button>
                    </form>
                </div>
            </div>

            <div class="voc-gemini-card" style="padding:0; overflow:hidden;">
                <table class="voc-gemini-data-table">
                    <thead>
                        <tr>
                            <th style="width:40px; padding-left:20px;"></th>
                            <th>Trashed Date</th>
                            <th>Source</th>
                            <th>Headline</th>
                            <th style="text-align:right; padding-right:20px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>';
                    if ( ! empty( $trashed_articles ) ) {
                        foreach ( $trashed_articles as $t_art ) {
                            $t_hash = md5( $t_art['link'] . $t_art['title'] );
                            echo '<tr>
                                <td style="padding-left:20px;">
                                    <input type="checkbox" value="' . esc_attr( $t_hash ) . '" class="voc-trash-checkbox" aria-label="Select ' . esc_attr($t_art['title']) . '" style="width:18px; height:18px; cursor:pointer;">
                                </td>
                                <td>' . esc_html( $t_art['trashed_date'] ?? $t_art['display_date'] ) . '</td>
                                <td>' . esc_html( $t_art['source'] ) . '</td>
                                <td>' . esc_html( $t_art['title'] ) . '</td>
                                <td style="text-align:right; padding-right:20px; display:flex; gap:6px; justify-content:flex-end;">
                                    <form method="post" style="display:inline-block; margin:0;">
                                        ' . wp_nonce_field( 'voc_save_settings', 'voc_save_settings_nonce', true, false ) . '
                                        <input type="hidden" name="voc_action_restore_single" value="1">
                                        <input type="hidden" name="voc_restore_hash" value="' . esc_attr( $t_hash ) . '">
                                        <button type="submit" class="voc-gemini-btn-secondary" aria-label="Restore ' . esc_attr($t_art['title']) . '" style="padding:4px 10px !important; background:#dcfce7 !important; color:#166534 !important; border-color:#86efac !important;">Restore</button>
                                    </form>

                                    <form method="post" style="display:inline-block; margin:0;">
                                        ' . wp_nonce_field( 'voc_save_settings', 'voc_save_settings_nonce', true, false ) . '
                                        <input type="hidden" name="voc_action_delete_permanent_single" value="1">
                                        <input type="hidden" name="voc_perm_delete_hash" value="' . esc_attr( $t_hash ) . '">
                                        <button type="submit" class="voc-gemini-btn-secondary" aria-label="Delete ' . esc_attr($t_art['title']) . ' permanently" style="padding:4px 10px !important; background:#fee2e2 !important; color:#991b1b !important; border-color:#fca5a5 !important;" onclick="return confirm(\'Delete this article permanently?\');">Delete Forever</button>
                                    </form>
                                </td>
                            </tr>';
                        }
                    } else { echo '<tr><td colspan="5" style="text-align:center; padding:30px;">Trash bin is empty.</td></tr>'; }
        echo '      </tbody>
                </table>
            </div>
        </div>';

        // TAB 8: ANALYTICS
        echo '
        <div id="voc-tab-analytics" class="voc-tab-content" role="tabpanel" style="display: none;">
            <div class="voc-gemini-card" style="padding:0; overflow:hidden;">
                <table class="voc-gemini-data-table">
                    <thead><tr><th style="padding-left:20px;">Clicks</th><th>Source</th><th>Headline</th></tr></thead>
                    <tbody>';
                    if ( ! empty( $stored_articles ) ) {
                        foreach ( $stored_articles as $art ) {
                            if ( empty($art['clicks']) ) continue;
                            echo '<tr>
                                <td style="padding-left:20px;"><strong style="color:' . esc_attr($primary_col) . ';">' . (int)$art['clicks'] . '</strong></td>
                                <td>' . esc_html($art['source']) . '</td>
                                <td><a href="' . esc_url($art['link']) . '" target="_blank" style="text-decoration:none; color:#0f172a;">' . esc_html($art['title']) . '</a></td>
                            </tr>';
                        }
                    } else { echo '<tr><td colspan="3" style="text-align:center; padding:30px;">No click data recorded yet.</td></tr>'; }
        echo '      </tbody>
                </table>
            </div>
        </div>';

        // TAB 9: DATA TOOLS
        echo '
        <div id="voc-tab-tools" class="voc-tab-content" role="tabpanel" style="display: none;">
            <div class="voc-gemini-card" style="max-width:500px;">
                <h3>Feed Utilities</h3>
                <form method="post">
                    ' . wp_nonce_field( 'voc_save_settings', 'voc_save_settings_nonce', true, false ) . '
                    <input type="hidden" name="voc_action_force_sync" value="1">
                    <button type="submit" class="voc-gemini-btn-secondary">Force Fetch RSS Feeds Now</button>
                </form>
            </div>
        </div>';

        echo '</div>';

        // REUSABLE JS SWITCHING SCRIPT, REPEATER JS, & BULK SELECTION JS
        echo '<script>
        function voc_switch_tab(event, tabId) {
            if (event) { event.preventDefault(); }
            
            var contents = document.getElementsByClassName("voc-tab-content");
            for (var i = 0; i < contents.length; i++) {
                contents[i].style.display = "none";
            }
            
            var tabs = document.getElementsByClassName("voc-gemini-tab");
            for (var j = 0; j < tabs.length; j++) {
                tabs[j].classList.remove("active");
                tabs[j].setAttribute("aria-selected", "false");
            }
            
            var targetContent = document.getElementById(tabId);
            if (targetContent) {
                targetContent.style.display = "block";
            }
            
            if (event && event.currentTarget) {
                event.currentTarget.classList.add("active");
                event.currentTarget.setAttribute("aria-selected", "true");
            }
            
            localStorage.setItem("voc_news_active_tab", tabId);
        }

        document.addEventListener("DOMContentLoaded", function() {
            var savedTab = localStorage.getItem("voc_news_active_tab") || "voc-tab-articles";
            if (document.getElementById(savedTab)) {
                voc_switch_tab(null, savedTab);
            }

            // --- RSS Source Repeater Script ---
            var addFeedBtn = document.getElementById("voc-add-feed-btn");
            var repeaterContainer = document.getElementById("voc-repeater-container");

            if (addFeedBtn && repeaterContainer) {
                addFeedBtn.addEventListener("click", function() {
                    var card = document.createElement("div");
                    card.className = "voc-feed-item-card";
                    card.style.cssText = "background:#f8fafc; padding:16px; border-radius:12px; border:1px solid #e2e8f0; margin-bottom:12px; display:flex; gap:12px; flex-wrap:wrap; align-items:center;";
                    card.innerHTML = \'<div style="flex:1; min-width:180px;"><label style="display:block; font-size:11px; font-weight:700; color:#64748b; margin-bottom:4px;">Outlet Name</label><input type="text" name="voc_source_name[]" class="voc-gemini-input" placeholder="e.g. WKTV News"></div><div style="flex:2; min-width:260px;"><label style="display:block; font-size:11px; font-weight:700; color:#64748b; margin-bottom:4px;">RSS Feed URL</label><input type="url" name="voc_source_rss[]" class="voc-gemini-input" placeholder="https://domain.com/feed/"></div><div style="flex:1; min-width:180px;"><label style="display:block; font-size:11px; font-weight:700; color:#64748b; margin-bottom:4px;">Main Website URL</label><input type="url" name="voc_source_url[]" class="voc-gemini-input" placeholder="https://domain.com"></div><div style="margin-top:20px;"><button type="button" class="voc-gemini-btn-secondary" style="background:#fee2e2 !important; color:#991b1b !important; border-color:#fca5a5 !important;" onclick="this.closest(\\\'.voc-feed-item-card\\\').remove()">Remove</button></div>\';
                    repeaterContainer.appendChild(card);
                });
            }

            // --- Bulk Checkbox Handling (Active Feed) ---
            var selectAll = document.getElementById("voc-select-all-articles");
            var checkboxes = document.querySelectorAll(".voc-article-checkbox");
            var bulkBtn = document.getElementById("voc-bulk-delete-btn");
            var countLabel = document.getElementById("voc-selected-count");

            function updateBulkBtn() {
                var checkedCount = document.querySelectorAll(".voc-article-checkbox:checked").length;
                if (countLabel) countLabel.textContent = "(" + checkedCount + " selected)";
                if (bulkBtn) {
                    bulkBtn.disabled = (checkedCount === 0);
                    bulkBtn.style.opacity = (checkedCount > 0) ? "1" : "0.5";
                    bulkBtn.style.cursor = (checkedCount > 0) ? "pointer" : "not-allowed";
                }
            }

            if (selectAll) {
                selectAll.addEventListener("change", function() {
                    checkboxes.forEach(function(cb) { cb.checked = selectAll.checked; });
                    updateBulkBtn();
                });
            }

            checkboxes.forEach(function(cb) {
                cb.addEventListener("change", function() {
                    if (!this.checked && selectAll) selectAll.checked = false;
                    updateBulkBtn();
                });
            });

            // --- Bulk Checkbox Handling (Trash) ---
            var selectAllTrash = document.getElementById("voc-select-all-trash");
            var trashCheckboxes = document.querySelectorAll(".voc-trash-checkbox");
            var bulkRestoreBtn = document.getElementById("voc-bulk-restore-btn");
            var bulkPermBtn = document.getElementById("voc-bulk-perm-btn");
            var restoreCountLabel = document.getElementById("voc-restore-selected-count");

            function updateTrashBtns() {
                var checked = document.querySelectorAll(".voc-trash-checkbox:checked");
                var checkedCount = checked.length;
                
                if (restoreCountLabel) restoreCountLabel.textContent = "(" + checkedCount + " selected)";
                
                var restoreContainer = document.getElementById("voc-bulk-restore-inputs");
                var permContainer = document.getElementById("voc-bulk-perm-inputs");
                if (restoreContainer) restoreContainer.innerHTML = "";
                if (permContainer) permContainer.innerHTML = "";

                checked.forEach(function(cb) {
                    if (restoreContainer) {
                        var inp1 = document.createElement("input");
                        inp1.type = "hidden";
                        inp1.name = "voc_selected_restore_hashes[]";
                        inp1.value = cb.value;
                        restoreContainer.appendChild(inp1);
                    }
                    if (permContainer) {
                        var inp2 = document.createElement("input");
                        inp2.type = "hidden";
                        inp2.name = "voc_selected_restore_hashes[]";
                        inp2.value = cb.value;
                        permContainer.appendChild(inp2);
                    }
                });

                if (bulkRestoreBtn) {
                    bulkRestoreBtn.disabled = (checkedCount === 0);
                    bulkRestoreBtn.style.opacity = (checkedCount > 0) ? "1" : "0.5";
                    bulkRestoreBtn.style.cursor = (checkedCount > 0) ? "pointer" : "not-allowed";
                }

                if (bulkPermBtn) {
                    bulkPermBtn.disabled = (checkedCount === 0);
                    bulkPermBtn.style.opacity = (checkedCount > 0) ? "1" : "0.5";
                    bulkPermBtn.style.cursor = (checkedCount > 0) ? "pointer" : "not-allowed";
                }
            }

            if (selectAllTrash) {
                selectAllTrash.addEventListener("change", function() {
                    trashCheckboxes.forEach(function(cb) { cb.checked = selectAllTrash.checked; });
                    updateTrashBtns();
                });
            }

            trashCheckboxes.forEach(function(cb) {
                cb.addEventListener("change", function() {
                    if (!this.checked && selectAllTrash) selectAllTrash.checked = false;
                    updateTrashBtns();
                });
            });
        });
        </script>';
    }
}

// Background RSS Fetcher Function
if ( ! function_exists( 'voc_news_fetch_rss_feeds_background' ) ) {
    function voc_news_fetch_rss_feeds_background() {
        $retention_days = (int) get_option( 'voc_news_retention_days', 120 );
        if ( $retention_days <= 0 ) { $retention_days = 120; }

        $raw_includes = get_option( 'voc_news_include_keywords', '' );
        $local_keywords = array_filter( array_map( 'trim', explode( ',', strtolower( $raw_includes ) ) ) );

        $raw_excludes = get_option( 'voc_news_exclude_keywords', 'classifieds, help wanted, garage sale, obituary' );
        $blocked_keywords = array_filter( array_map( 'trim', explode( ',', strtolower( $raw_excludes ) ) ) );

        $source_list = function_exists('voc_news_get_parsed_sources') ? voc_news_get_parsed_sources() : array();
        
        $database_option_key = 'voc_saved_news_articles_db';
        $stored_articles = get_option( $database_option_key, array() );
        if ( ! is_array( $stored_articles ) ) { $stored_articles = array(); }

        $existing_hashes = array();
        foreach ( $stored_articles as $article ) {
            $existing_hashes[ md5( $article['link'] . $article['title'] ) ] = true;
        }

        $trashed_articles = get_option( 'voc_trashed_news_articles_db', array() );
        if ( is_array( $trashed_articles ) ) {
            foreach ( $trashed_articles as $t_art ) {
                $existing_hashes[ md5( $t_art['link'] . $t_art['title'] ) ] = true;
            }
        }

        $feed_statuses = get_option( 'voc_news_feed_statuses', array() );
        if ( ! is_array($feed_statuses) ) { $feed_statuses = array(); }

        foreach ( $source_list as $src ) {
            $url = $src['rss'];
            $sync_time = current_time('mysql');

            $response = wp_remote_get( $url, array( 'timeout' => 8, 'user-agent' => 'Mozilla/5.0', 'sslverify' => false ) );

            if ( is_wp_error( $response ) ) {
                $feed_statuses[md5($url)] = array(
                    'status'    => 'error',
                    'message'   => $response->get_error_message(),
                    'code'      => 0,
                    'last_sync' => $sync_time,
                    'matched'   => 0
                );
                continue;
            }

            $body = wp_remote_retrieve_body( $response );
            $http_code = wp_remote_retrieve_response_code( $response );

            if ( empty( $body ) ) {
                $feed_statuses[md5($url)] = array(
                    'status'    => 'error',
                    'message'   => "Empty response body (HTTP {$http_code})",
                    'code'      => $http_code,
                    'last_sync' => $sync_time,
                    'matched'   => 0
                );
                continue; 
            }

            libxml_use_internal_errors(true);
            $xml = @simplexml_load_string( trim($body), 'SimpleXMLElement', LIBXML_NOCDATA );
            $items = isset( $xml->channel->item ) ? $xml->channel->item : ( isset( $xml->entry ) ? $xml->entry : array() );

            if ( empty($items) ) {
                libxml_clear_errors();
                $feed_statuses[md5($url)] = array(
                    'status'    => 'error',
                    'message'   => "XML Parse Error or 0 items",
                    'code'      => $http_code,
                    'last_sync' => $sync_time,
                    'matched'   => 0
                );
                continue;
            }

            $feed_matches_count = 0;

            foreach ( $items as $item ) {
                $raw_title = (string) ( $item->title ?? '' );
                $link      = (string) ( $item->link ?? '' );
                if ( empty($raw_title) || empty($link) ) continue;

                $decoded_title = html_entity_decode( $raw_title, ENT_QUOTES | ENT_HTML5, 'UTF-8' );

                $hash = md5( $link . $raw_title );
                if ( isset( $existing_hashes[$hash] ) ) continue;

                $raw_desc = strip_tags( (string) ( $item->description ?? '' ) );
                $decoded_desc = html_entity_decode( $raw_desc, ENT_QUOTES | ENT_HTML5, 'UTF-8' );

                $pubDate   = (string) ( $item->pubDate ?? $item->published ?? '' );
                $timestamp = strtotime($pubDate) ?: time();

                $text_to_scan = strtolower( $decoded_title . ' ' . $decoded_desc );

                if ( ! empty( $blocked_keywords ) ) {
                    $is_blocked = false;
                    foreach ( $blocked_keywords as $blocked ) {
                        if ( ! empty( $blocked ) && strpos( $text_to_scan, $blocked ) !== false ) {
                            $is_blocked = true;
                            break;
                        }
                    }
                    if ( $is_blocked ) {
                        continue;
                    }
                }

                if ( ! empty( $local_keywords ) ) {
                    $has_include_match = false;
                    foreach ( $local_keywords as $required ) {
                        if ( ! empty( $required ) && strpos( $text_to_scan, $required ) !== false ) {
                            $has_include_match = true;
                            break;
                        }
                    }
                    if ( ! $has_include_match ) {
                        continue;
                    }
                }

                $thumbnail = '';
                if ( isset( $item->enclosure['url'] ) ) {
                    $thumbnail = (string) $item->enclosure['url'];
                } elseif ( preg_match( '/<img[^>]+src="([^">]+)"/', (string)($item->description ?? ''), $m ) ) {
                    $thumbnail = $m[1];
                }

                $stored_articles[] = array(
                    'title'        => $decoded_title,
                    'link'         => $link,
                    'timestamp'    => $timestamp,
                    'display_date' => date( 'M j, Y', $timestamp ),
                    'source'       => $src['name'],
                    'excerpt'      => wp_trim_words( $decoded_desc, 24, '...' ),
                    'full_text'    => $decoded_desc,
                    'thumbnail'    => $thumbnail,
                    'is_pinned'    => false,
                    'clicks'       => 0
                );

                $existing_hashes[$hash] = true;
                $feed_matches_count++;
            }
            libxml_clear_errors();

            $feed_statuses[md5($url)] = array(
                'status'    => 'success',
                'message'   => "HTTP {$http_code} OK (" . count($items) . " items scanned)",
                'code'      => $http_code,
                'last_sync' => $sync_time,
                'matched'   => $feed_matches_count
            );
        }

        update_option( 'voc_news_feed_statuses', $feed_statuses );

        usort( $stored_articles, function($a, $b) {
            return ($b['timestamp'] ?? 0) <=> ($a['timestamp'] ?? 0);
        });

        update_option( $database_option_key, array_values( array_slice($stored_articles, 0, 150) ), false );
    }
}