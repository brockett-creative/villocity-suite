<?php
/**
 * Module Name: VilloCity Local News (Frontend)
 * Module URI:  https://villocity.org
 * Description: Public news feeds, non-blocking RSS background engine, REST tracking endpoints, sticky sidebar grid cards, and automated weekly HTML email digest.
 * Version:     2.4.2 (Fixed Pagination Script & Category Filter Engine)
 * Author:      Brockett Creative Group, Inc.
 * Author URI:  https://brockettcreative.com
 * Text Domain: villocity-suite
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

// ==========================================
// 1. CRON SYNC & BOOTSTRAP
// ==========================================
if ( ! function_exists( 'voc_news_check_cron_jobs' ) ) {
    add_action( 'admin_init', 'voc_news_check_cron_jobs' );
    function voc_news_check_cron_jobs() {
        $sync_freq = get_option( 'voc_news_cron_schedule', 'twicedaily' );
        if ( $sync_freq !== 'disabled' && ! wp_next_scheduled( 'voc_news_scheduled_sync_hook' ) ) {
            wp_schedule_event( time(), $sync_freq, 'voc_news_scheduled_sync_hook' );
        }
        if ( ! wp_next_scheduled( 'voc_news_weekly_digest_hook' ) ) {
            wp_schedule_event( time(), 'weekly', 'voc_news_weekly_digest_hook' );
        }
    }
}

if ( ! function_exists( 'voc_news_run_cron_sync' ) ) {
    add_action( 'voc_news_scheduled_sync_hook', 'voc_news_run_cron_sync' );
    function voc_news_run_cron_sync() {
        delete_transient( 'voc_news_plugin_cache_v651' );
        voc_news_get_stored_articles(); 
    }
}

// ==========================================
// 2. HELPER: RETRIEVE & FORMAT RSS SOURCES
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
            update_option('voc_news_rss_sources', $source_list);
            return $source_list;
        }

        if ( is_array( $raw_sources ) ) {
            return $raw_sources;
        }

        return array();
    }
}

// ==========================================
// 3. REST API: OUTBOUND CLICK TRACKING
// ==========================================
if ( ! function_exists( 'voc_news_log_click' ) ) {
    add_action( 'rest_api_init', function () {
        register_rest_route( 'voc-news/v1', '/track-click', array(
            'methods'  => 'POST',
            'callback' => 'voc_news_log_click',
            'permission_callback' => '__return_true',
        ) );
    } );

    function voc_news_log_click( WP_REST_Request $request ) {
        $hash = sanitize_text_field( $request->get_param( 'hash' ) );
        if ( empty( $hash ) ) {
            return new WP_Error( 'missing_hash', 'Hash parameter is required', array( 'status' => 400 ) );
        }

        $database_option_key = 'voc_saved_news_articles_db';
        $stored_articles = get_option( $database_option_key, array() );
        $updated = false;

        foreach ( $stored_articles as &$article ) {
            if ( md5( $article['link'] . $article['title'] ) === $hash ) {
                if ( ! isset( $article['clicks'] ) ) {
                    $article['clicks'] = 0;
                }
                $article['clicks']++;
                $updated = true;
                break;
            }
        }

        if ( $updated ) {
            update_option( $database_option_key, array_values( $stored_articles ), false );
            return rest_ensure_response( array( 'success' => true ) );
        }

        return new WP_Error( 'not_found', 'Article not found', array( 'status' => 404 ) );
    }
}

// ==========================================
// 4. HELPER: CORE NEWS AGGREGATION ENGINE (BACKGROUND ONLY)
// ==========================================
if ( ! function_exists( 'voc_news_get_stored_articles' ) ) {
    function voc_news_get_stored_articles() {
        return get_option( 'voc_saved_news_articles_db', array() );
    }
}

// ==========================================
// 5. MAIN FEED SHORTCODE: [villocity_local_news]
// ==========================================
if ( ! function_exists( 'voc_plugin_render_live_news_feed' ) ) {
    function voc_plugin_render_live_news_feed() {
        
        $is_elementor_editor = ( isset( $_GET['action'] ) && $_GET['action'] === 'elementor' ) || 
                               isset( $_GET['elementor-preview'] ) || 
                               ( isset( $_POST['action'] ) && $_POST['action'] === 'elementor_ajax' );
                               
        if ( $is_elementor_editor ) {
            return '<div style="padding: 30px; background: #fdf2f7; border: 2px dashed #fadceb; border-radius: 8px; color: #860038; font-family: sans-serif; text-align: center;">
                        <strong style="font-size: 18px;">📰 Live News Feed Placeholder</strong><br>
                        <span style="font-size: 14px; color: #a30044; display: block; margin-top: 8px;">(The feed is paused while you are in Elementor to prevent editor timeouts.)</span>
                    </div>';
        }

        $layout_style = get_option('villocity_global_layout_style', 'boxed');
        $primary_col  = get_option('villocity_global_primary', get_option('voc_news_primary_color', '#860038'));
        $accent_col   = get_option('villocity_global_accent', get_option('voc_news_accent_color', '#fadceb'));
        $pinned_bg    = get_option('voc_news_pinned_bg', '#fdf2f7');
        $text_col     = get_option('villocity_global_text', get_option('voc_news_text_color', '#1c1917'));
        $feed_bg      = get_option('villocity_global_bg', get_option('voc_news_bg_color', '#fafaf9'));

        $btn_bg         = get_option('villocity_global_btn_bg', get_option('voc_news_btn_bg', '#860038'));
        $btn_text       = get_option('villocity_global_btn_text', get_option('voc_news_btn_text', '#ffffff'));
        $btn_hover_bg   = get_option('villocity_global_btn_hover_bg', get_option('voc_news_btn_hover_bg', '#3a6690'));
        $btn_hover_text = get_option('villocity_global_btn_hover_text', get_option('voc_news_btn_hover_text', '#ffffff'));

        $main_title    = get_option('voc_news_header_title', 'Clinton Local News');
        $main_subtitle = get_option('voc_news_header_subtitle', 'Live updates and headlines from around Clinton and Town of Kirkland.');

        $raw_tabs = get_option('voc_news_category_tabs', "Sports | sports, warriors, ccs, athletics, game, score\nCollege | hamilton college, pioneers, university\nTown | clinton, kirkland, clark mills, village, board");
        $tab_lines = array_filter( array_map( 'trim', explode( "\n", $raw_tabs ) ) );
        $category_pills_html = '<button class="voc-category-pill active" data-keywords="" aria-pressed="true">All News</button>';
        foreach ($tab_lines as $line) {
            $parts = explode('|', $line);
            if (count($parts) >= 2) {
                $tab_name = trim($parts[0]);
                $tab_kws = trim($parts[1]);
                $category_pills_html .= '<button class="voc-category-pill" data-keywords="' . esc_attr($tab_kws) . '" aria-pressed="false">' . esc_html($tab_name) . '</button>';
            }
        }

        // FAST LOCAL LOOKUP ONLY (0ms load time)
        $stored_articles = get_option( 'voc_saved_news_articles_db', array() );
        $source_list     = voc_news_get_parsed_sources();

        // Popular stories sidebar
        $popular_articles = $stored_articles;
        usort( $popular_articles, function($a, $b) {
            return (isset($b['clicks']) ? (int)$b['clicks'] : 0) <=> (isset($a['clicks']) ? (int)$a['clicks'] : 0);
        });
        $top_popular = array_slice( array_filter($popular_articles, function($art){ return !empty($art['clicks']); }), 0, 4 );

        $popular_html = '';
        if ( !empty($top_popular) ) {
            $popular_html .= '
            <div class="voc-sidebar-card" style="margin-bottom: 24px;" role="region" aria-label="Most Popular News">
                <h4 style="font-size: 20px; font-weight: 700; color: var(--voc-text); margin: 0 0 20px 0; padding-bottom: 12px; border-bottom: 1px solid #e7e5e4; display: flex; align-items: center; gap: 6px;">' . (function_exists('villocity_get_icon') ? villocity_get_icon('chart') : '') . ' Most Popular</h4>
                <ul class="voc-source-list">';
            foreach ($top_popular as $pop) {
                $hash = md5($pop['link'] . $pop['title']);
                $popular_html .= '
                    <li style="margin-bottom: 12px; display: block;">
                        <a href="'.esc_url($pop['link']).'" data-hash="'.esc_attr($hash).'" class="voc-sidebar-link voc-track-click" style="display:block; line-height: 1.4; font-size: 14px; font-weight: 600;" target="_blank" rel="noopener noreferrer">'.esc_html($pop['title']).'</a>
                        <div style="font-size: 11px; color: #78716c; margin-top: 4px; font-weight: 500;">'.esc_html($pop['source']).' &bull; '.esc_html($pop['clicks']).' reads</div>
                    </li>';
            }
            $popular_html .= '
                </ul>
            </div>';
        }

        // Source directory sidebar
        $sidebar_links = array();
        foreach ( $source_list as $src ) {
            if ( ! isset( $sidebar_links[$src['url']] ) ) {
                $sidebar_links[$src['url']] = $src['name'];
            }
        }

        $sidebar_html = '';
        foreach ( $sidebar_links as $url => $name ) {
            $clean_name = str_replace(array(' News', ' Sports'), '', $name);
            $sidebar_html .= '
                <li style="display: flex; align-items: center; gap: 8px; margin: 0; padding: 0;">
                    <span style="width: 8px; height: 8px; border-radius: 50%; background-color: var(--voc-primary); display: inline-block; flex-shrink: 0;" aria-hidden="true"></span> 
                    <a href="' . esc_url($url) . '" target="_blank" rel="nofollow noopener noreferrer" class="voc-sidebar-link">
                        ' . esc_html($clean_name) . '
                    </a>
                </li>';
        }

        $output = '
        <style>
            .voc-news-wrap { --voc-primary: ' . esc_attr($primary_col) . '; --voc-accent: ' . esc_attr($accent_col) . '; --voc-pinned-bg: ' . esc_attr($pinned_bg) . '; --voc-text: ' . esc_attr($text_col) . '; --voc-bg: ' . esc_attr($feed_bg) . '; --voc-btn-bg: ' . esc_attr($btn_bg) . '; --voc-btn-text: ' . esc_attr($btn_text) . '; --voc-btn-hover-bg: ' . esc_attr($btn_hover_bg) . '; --voc-btn-hover-text: ' . esc_attr($btn_hover_text) . '; font-family: var(--voc-font-family, inherit) !important; background-color: var(--voc-bg); color: var(--voc-text); border-radius: 16px; border: 1px solid #e7e5e4; box-shadow: 0 4px 20px rgba(0,0,0,0.03); min-height: 400px; }
            .voc-news-header { background-color: rgba(0,0,0,0.02); padding: 32px 24px; border-bottom: 1px solid #e7e5e4; border-top-left-radius: 16px; border-top-right-radius: 16px; }
            @media (min-width: 768px) { .voc-news-header { padding: 48px; } }
            .voc-badge-label { color: var(--voc-primary); font-weight: 700; letter-spacing: 0.05em; text-transform: uppercase; font-size: 14px; display: flex; align-items: center; gap: 6px; margin-bottom: 8px; }
            .voc-news-title { font-size: 36px; font-weight: 800; color: var(--voc-primary); margin: 0 0 8px 0 !important; line-height: 1.1; font-family: var(--voc-font-family, inherit) !important; }
            @media (min-width: 768px) { .voc-news-title { font-size: 48px; } }
            .voc-news-subtitle { font-size: 18px; color: var(--voc-text); opacity: 0.8; margin: 0 !important; }
            
            .voc-body-grid { padding: 24px; display: flex; flex-direction: column; gap: 32px; position: relative; }
            
            @media (min-width: 1024px) { 
                .voc-body-grid { 
                    padding: 40px; 
                    display: grid !important; 
                    grid-template-columns: 2fr 1fr !important; 
                    gap: 32px !important; 
                    align-items: start !important; 
                } 
                .voc-main-col { width: 100% !important; } 
                .voc-side-col { 
                    width: 100% !important; 
                    position: -webkit-sticky !important;
                    position: sticky !important; 
                    top: 110px !important; 
                    z-index: 10 !important;
                } 
            }
            
            .voc-section-h3 { font-size: 24px; font-weight: 700; color: var(--voc-text); margin: 0 0 16px 0 !important; padding-bottom: 12px; border-bottom: 1px solid #e7e5e4; display: flex; align-items: center; gap: 6px; font-family: var(--voc-font-family, inherit) !important; }
            
            .voc-meta-bar { display: flex; align-items: center; gap: 10px; margin-bottom: 12px; font-size: 13px; font-weight: 600; flex-wrap: wrap; }
            .voc-source-tag { background: ' . esc_attr($accent_col) . ' !important; color: ' . esc_attr($primary_col) . ' !important; font-size: 13px !important; font-weight: 700 !important; padding: 6px 16px !important; border-radius: 20px !important; display: inline-flex !important; align-items: center !important; gap: 6px !important; margin-bottom: 0 !important; }
            .voc-pinned-tag { background-color: var(--voc-primary) !important; color: #ffffff !important; font-size: 12px !important; font-weight: 700 !important; padding: 6px 16px !important; border-radius: 20px !important; display: inline-flex !important; align-items: center !important; gap: 6px !important; margin-bottom: 0 !important; }
            .voc-date-tag { color: #64748b; font-size: 13px; font-weight: 500; display: inline-flex; align-items: center; gap: 4px; }

            .voc-category-filters { display: flex; gap: 8px; margin-bottom: 24px; flex-wrap: wrap; }
            .voc-category-pill { background-color: transparent !important; color: var(--voc-text) !important; border: 1px solid #d6d3d1 !important; padding: 6px 18px !important; border-radius: 24px !important; font-size: 13px !important; font-weight: 600 !important; cursor: pointer !important; transition: all 0.2s ease !important; }
            .voc-category-pill.active { background-color: var(--voc-btn-bg) !important; color: var(--voc-btn-text) !important; border-color: var(--voc-btn-bg) !important; }
            
            .voc-news-article { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 24px; margin-bottom: 24px; display: flex; flex-direction: column; gap: 20px; transition: transform 0.2s, box-shadow 0.2s; text-decoration: none; color: inherit; }
            .voc-news-article.is-pinned-article { background-color: var(--voc-pinned-bg); border: 1px solid var(--voc-accent); }
            .voc-news-article:hover { transform: translateY(-3px); box-shadow: 0 10px 25px rgba(0,0,0,0.05); }
            @media (min-width: 640px) { .voc-news-article { flex-direction: row; } .voc-thumb-wrap { width: 33.333%; flex-shrink: 0; } .voc-content-wrap { width: 66.666%; } }
            .voc-thumb-box { display: block; overflow: hidden; border-radius: 8px; height: 160px; width: 100%; background-color: #e7e5e4; position: relative; }
            .voc-thumb-box img { width: 100%; height: 100%; object-fit: cover; position: absolute; top:0; left:0; }
            .voc-art-title { font-size: 20px; font-weight: 700; color: var(--voc-primary); margin: 0 0 8px 0 !important; line-height: 1.3; font-family: var(--voc-font-family, inherit) !important; }
            .voc-art-excerpt { font-size: 15px; color: #475569; margin: 0 !important; line-height: 1.5; }
            
            .voc-sidebar-card { background-color: #ffffff; padding: 28px; border-radius: 16px; border: 1px solid #e7e5e4; box-shadow: 0 2px 10px rgba(0,0,0,0.02); }
            .voc-sidebar-link { color: var(--voc-text); text-decoration: none; font-weight: 600; font-size: 15px; transition: color 0.2s; }
            .voc-sidebar-link:hover { color: var(--voc-primary); }
            .voc-source-list { list-style: none !important; padding: 0 !important; margin: 0 !important; display: flex; flex-direction: column; gap: 14px; }

            .voc-pagination-btn { background-color: var(--voc-btn-bg) !important; color: var(--voc-btn-text) !important; border: none !important; font-weight: 600 !important; padding: 12px 24px !important; border-radius: 24px !important; cursor: pointer !important; text-decoration: none !important; display: inline-flex !important; align-items: center !important; gap: 6px !important; transition: all 0.2s ease !important; font-size: 14px !important; }
            .voc-pagination-btn:hover:not(:disabled) { background-color: var(--voc-btn-hover-bg) !important; color: var(--voc-btn-hover-text) !important; box-shadow: 0 4px 12px rgba(0,0,0,0.1) !important; }
            .voc-pagination-btn:disabled { background-color: #f1f5f9 !important; color: #94a3b8 !important; cursor: not-allowed !important; box-shadow: none !important; }
        </style>

        <div id="villocity-news-container" class="voc-news-wrap voc-style-' . esc_attr($layout_style) . '" role="region" aria-label="' . esc_attr($main_title) . ' Feed">
          <div class="voc-news-header">
            <div>
              <div class="voc-badge-label">
                ' . (function_exists('villocity_get_icon') ? villocity_get_icon('news') : '') . '
                <span>Community Updates</span>
              </div>
              <h2 class="voc-news-title">' . esc_html($main_title) . '</h2>
              <p class="voc-news-subtitle">' . esc_html($main_subtitle) . '</p>
            </div>
          </div>

          <div class="voc-body-grid">
            <div class="voc-main-col">
              <h3 class="voc-section-h3">
                ' . (function_exists('villocity_get_icon') ? villocity_get_icon('rss') : '') . '
                <span>Latest Headlines</span>
              </h3>
              
              <div style="position:relative; margin-bottom:20px;">
                <span style="position:absolute; left:16px; top:50%; transform:translateY(-50%); color:#94a3b8; display:flex;">' . (function_exists('villocity_get_icon') ? villocity_get_icon('search') : '🔍') . '</span>
                <input type="text" id="voc-news-search" placeholder="Search local news, topics, or dates..." style="width:100%; padding:12px 16px 12px 48px; border:1px solid #cbd5e1; border-radius:12px; font-size:15px; background:#ffffff; box-sizing:border-box;" autocomplete="off">
              </div>

              <div class="voc-category-filters" aria-label="Filter news by category">
                  ' . $category_pills_html . '
              </div>

              <div id="voc-article-list">';

        if ( empty( $stored_articles ) ) {
            $output .= '<p style="padding:20px; color:#64748b;">No local news stories currently available.</p>';
        } else {
            foreach ( $stored_articles as $index => $article ) {
                $has_image = ! empty( $article['thumbnail'] );
                $is_pinned = ! empty( $article['is_pinned'] );
                $hash = md5( $article['link'] . $article['title'] );
                
                $star_icon  = function_exists('villocity_get_icon') ? villocity_get_icon('star') : '★';
                $tag_icon   = function_exists('villocity_get_icon') ? villocity_get_icon('tag') : '';
                $clock_icon = function_exists('villocity_get_icon') ? villocity_get_icon('clock') : '🕒';
                
                $badge_icon  = $is_pinned ? $star_icon : $tag_icon;
                $badge_label = $is_pinned ? 'Featured &bull; ' . esc_html($article['source']) : esc_html($article['source']);

                $image_block = '';
                if ( $has_image ) {
                    $image_block = '
                    <div class="voc-thumb-wrap">
                      <a href="' . esc_url( $article['link'] ) . '" target="_blank" rel="noopener noreferrer" class="voc-thumb-box voc-track-click" data-hash="' . esc_attr($hash) . '">
                        <img src="' . esc_url( $article['thumbnail'] ) . '" alt="' . esc_attr($article['title']) . '" />
                      </a>
                    </div>';
                }

                $full_text = isset($article['full_text']) ? $article['full_text'] : '';
                $search_text = esc_attr( strtolower( $article['title'] . ' ' . $article['source'] . ' ' . $article['excerpt'] . ' ' . $full_text . ' ' . $article['display_date'] ) );

                $output .= '
                <article class="voc-news-article ' . ($is_pinned ? 'is-pinned-article' : '') . '" data-search="' . $search_text . '" data-source="' . esc_attr(strtolower($article['source'])) . '" style="display: ' . ($index < 10 ? 'flex' : 'none') . ';">
                  ' . $image_block . '
                  <div class="' . ($has_image ? 'voc-content-wrap' : 'w-full') . '" style="display: flex; flex-direction: column; justify-content: center;">
                    <div class="voc-meta-bar">
                      <span class="' . ($is_pinned ? 'voc-pinned-tag' : 'voc-source-tag') . '">' . $badge_icon . ' ' . $badge_label . '</span>
                      <span class="voc-date-tag">' . $clock_icon . ' ' . esc_html($article['display_date']) . '</span>
                    </div>
                    <h4 class="voc-art-title">
                      <a href="' . esc_url( $article['link'] ) . '" class="voc-track-click" data-hash="' . esc_attr($hash) . '" target="_blank" rel="noopener noreferrer" style="color:inherit; text-decoration:none;">
                        ' . esc_html( $article['title'] ) . '
                      </a>
                    </h4>
                    <p class="voc-art-excerpt">' . esc_html( $article['excerpt'] ) . '</p>
                  </div>
                </article>';
            }

            $output .= '
            <nav id="voc-pagination-controls" style="display: ' . (count($stored_articles) > 10 ? 'flex' : 'none') . '; justify-content: space-between; align-items: center; margin-top: 40px; padding-top: 24px; border-top: 1px solid #e7e5e4;">
                <button id="voc-prev-page" class="voc-pagination-btn" disabled>&larr; Newer News</button>
                <span id="voc-page-info" style="color: var(--voc-text); font-weight: 600; font-size: 15px;">Page 1</span>
                <button id="voc-next-page" class="voc-pagination-btn">Older News &rarr;</button>
            </nav>';
        }

        $output .= '
              </div>
            </div>

            <div class="voc-side-col">
              ' . $popular_html . '
              <div class="voc-sidebar-card" role="region" aria-label="News Outlets Directory">
                <h4 style="font-size: 20px; font-weight: 700; color: var(--voc-text); margin: 0 0 20px 0; padding-bottom: 12px; border-bottom: 1px solid #e7e5e4; display: flex; align-items: center; gap: 6px;">' . (function_exists('villocity_get_icon') ? villocity_get_icon('rss') : '') . ' Our News Sources</h4>
                <ul class="voc-source-list">
                  ' . $sidebar_html . '
                </ul>
              </div>
            </div>
          </div>
          
          <div class="voc-disclaimer" role="note" style="font-size: 13px; color: #78716c; font-style: italic; margin-top: 40px; padding-top: 24px; border-top: 1px solid #e7e5e4; line-height: 1.5;">
              <strong>Disclaimer:</strong> This news feed is provided as a public service, aggregating content automatically from third-party sources. The site owner does not verify the accuracy of these articles, endorse the authors, or guarantee the validity of the information presented.
          </div>
        </div>

        <script>
        document.addEventListener("DOMContentLoaded", function() {
            const articles = Array.from(document.querySelectorAll("#voc-article-list .voc-news-article"));
            const searchInput = document.getElementById("voc-news-search");
            const filterPills = document.querySelectorAll(".voc-category-pill");
            const prevBtn = document.getElementById("voc-prev-page");
            const nextBtn = document.getElementById("voc-next-page");
            const pageInfo = document.getElementById("voc-page-info");
            const restEndpoint = "' . esc_url_raw(rest_url('voc-news/v1/track-click')) . '";

            let currentPage = 1;
            const itemsPerPage = 10;
            let activeKeywords = [];
            let activeSearchTerm = "";

            function updateFeed() {
                let visibleArticles = articles.filter(article => {
                    const searchData = (article.getAttribute("data-search") || "").toLowerCase();
                    const matchesSearch = activeSearchTerm === "" || searchData.includes(activeSearchTerm);
                    
                    let matchesCategory = true;
                    if (activeKeywords.length > 0) {
                        matchesCategory = activeKeywords.some(kw => searchData.includes(kw));
                    }
                    
                    return matchesSearch && matchesCategory;
                });

                const totalPages = Math.ceil(visibleArticles.length / itemsPerPage) || 1;
                if (currentPage > totalPages) currentPage = totalPages;

                const startIdx = (currentPage - 1) * itemsPerPage;
                const endIdx = startIdx + itemsPerPage;

                articles.forEach(art => art.style.display = "none");

                visibleArticles.forEach((art, idx) => {
                    if (idx >= startIdx && idx < endIdx) {
                        art.style.display = "flex";
                    }
                });

                if (pageInfo) pageInfo.textContent = "Page " + currentPage + " of " + totalPages;
                if (prevBtn) prevBtn.disabled = currentPage <= 1;
                if (nextBtn) nextBtn.disabled = currentPage >= totalPages;
            }

            filterPills.forEach(pill => {
                pill.addEventListener("click", function(e) {
                    e.preventDefault();
                    filterPills.forEach(p => {
                        p.classList.remove("active");
                        p.setAttribute("aria-pressed", "false");
                    });
                    this.classList.add("active");
                    this.setAttribute("aria-pressed", "true");

                    const kwStr = (this.getAttribute("data-keywords") || "").toLowerCase().trim();
                    activeKeywords = kwStr ? kwStr.split(",").map(k => k.trim()) : [];
                    currentPage = 1;
                    updateFeed();
                });
            });

            if (searchInput) {
                searchInput.addEventListener("input", function() {
                    activeSearchTerm = this.value.toLowerCase().trim();
                    currentPage = 1;
                    updateFeed();
                });
            }

            if (prevBtn) {
                prevBtn.addEventListener("click", function(e) {
                    e.preventDefault();
                    if (currentPage > 1) {
                        currentPage--;
                        updateFeed();
                        document.getElementById("villocity-news-container").scrollIntoView({ behavior: "smooth" });
                    }
                });
            }

            if (nextBtn) {
                nextBtn.addEventListener("click", function(e) {
                    e.preventDefault();
                    currentPage++;
                    updateFeed();
                    document.getElementById("villocity-news-container").scrollIntoView({ behavior: "smooth" });
                });
            }

            document.querySelectorAll(".voc-track-click").forEach(link => {
                link.addEventListener("click", function() {
                    const hash = this.getAttribute("data-hash");
                    if (hash && restEndpoint) {
                        fetch(restEndpoint, {
                            method: "POST",
                            headers: { "Content-Type": "application/json" },
                            body: JSON.stringify({ hash: hash })
                        }).catch(() => {});
                    }
                });
            });

            updateFeed();
        });
        </script>';

        return $output;
    }
}

// ==========================================
// 5. HOMEPAGE MINI-FEED SHORTCODE
// ==========================================
if ( ! function_exists( 'voc_plugin_render_latest_news_mini' ) ) {
    function voc_plugin_render_latest_news_mini( $atts = array() ) {
        $atts = shortcode_atts( array(
            'count'  => 3,
            'link'   => '/news/',
            'layout' => 'compact'
        ), $atts, 'villocity_latest_news' );

        $primary_col = get_option('villocity_global_primary', get_option('voc_news_primary_color', '#860038'));
        $accent_col  = get_option('villocity_global_accent', get_option('voc_news_accent_color', '#fadceb'));
        $pinned_bg   = get_option('voc_news_pinned_bg', '#fdf2f7');
        $text_col    = get_option('villocity_global_text', get_option('voc_news_text_color', '#1c1917'));
        $feed_bg     = get_option('villocity_global_bg', get_option('voc_news_bg_color', '#fafaf9'));

        $btn_bg   = get_option('villocity_global_btn_bg', get_option('voc_news_btn_bg', '#860038'));
        $btn_text = get_option('villocity_global_btn_text', get_option('voc_news_btn_text', '#ffffff'));
        
        $mini_title = get_option('voc_news_mini_title', 'Latest Local Headlines');

        $stored_articles = voc_news_get_stored_articles();
        $articles = array_slice( $stored_articles, 0, intval($atts['count']) );

        $output = '
        <style>
            .voc-mini-container-wrap, .voc-mini-wrap {
                width: 100% !important;
                box-sizing: border-box !important;
            }

            .voc-mini-wrap {
                --voc-primary: ' . esc_attr($primary_col) . ';
                --voc-accent: ' . esc_attr($accent_col) . ';
                --voc-pinned-bg: ' . esc_attr($pinned_bg) . ';
                --voc-text: ' . esc_attr($text_col) . ';
                --voc-bg: ' . esc_attr($feed_bg) . ';
                --voc-btn-bg: ' . esc_attr($btn_bg) . ';
                --voc-btn-text: ' . esc_attr($btn_text) . ';

                font-family: var(--voc-font-family, inherit) !important;
                background-color: #ffffff;
                border-radius: 16px;
                padding: 28px;
                border: 1px solid #e7e5e4;
                box-shadow: 0 4px 20px rgba(0,0,0,0.03);
                min-height: 280px;
            }

            .voc-mini-header { 
                display: flex !important; 
                flex-direction: row !important; 
                justify-content: space-between !important; 
                align-items: center !important; 
                border-bottom: 1px solid #e7e5e4 !important; 
                padding-bottom: 20px !important; 
                margin-bottom: 24px !important; 
                gap: 12px !important; 
                flex-wrap: wrap !important;
            }

            .voc-mini-grid { 
                display: flex !important; 
                flex-direction: column !important; 
                gap: 16px !important; 
                width: 100% !important;
            }

            .voc-mini-card { 
                display: flex !important; 
                flex-direction: row !important; 
                align-items: center !important; 
                width: 100% !important;
                max-width: 100% !important;
                background-color: var(--voc-bg) !important; 
                border: 1px solid #e7e5e4 !important; 
                border-radius: 12px !important; 
                padding: 12px !important;
                box-sizing: border-box !important;
                text-decoration: none !important; 
                color: inherit !important; 
                transition: box-shadow 0.2s, transform 0.2s !important; 
            }
            .voc-mini-card.is-pinned-card { background-color: var(--voc-pinned-bg) !important; border-color: var(--voc-accent) !important; }
            .voc-mini-card:hover { box-shadow: 0 8px 20px rgba(0,0,0,0.06) !important; transform: translateY(-2px) !important; }
            
            .voc-mini-thumb { 
                width: 80px !important; 
                height: 80px !important; 
                min-width: 80px !important; 
                max-width: 80px !important;
                border-radius: 8px !important; 
                overflow: hidden !important; 
                display: block !important; 
                background-color: #e7e5e4 !important; 
                position: relative !important; 
                flex-shrink: 0 !important; 
            }
            .voc-mini-thumb img { 
                width: 100% !important; 
                height: 100% !important; 
                position: absolute !important; 
                top: 0 !important; 
                left: 0 !important; 
                object-fit: cover !important; 
                display: block !important; 
            }
            
            .voc-mini-body { 
                padding: 0 0 0 12px !important; 
                display: flex !important; 
                flex-direction: column !important; 
                flex-grow: 1 !important; 
                min-width: 0 !important;
            }
            .voc-mini-title { 
                font-size: 15px !important; 
                font-weight: 700 !important; 
                color: var(--voc-text) !important; 
                margin: 0 0 4px 0 !important; 
                line-height: 1.3 !important; 
                display: -webkit-box !important;
                -webkit-line-clamp: 2 !important;
                -webkit-box-orient: vertical !important;
                overflow: hidden !important;
            }

            .voc-custom-btn { 
                background-color: var(--voc-btn-bg) !important; 
                color: var(--voc-btn-text) !important; 
                border: none !important; 
                font-weight: 700 !important; 
                padding: 10px 20px !important; 
                border-radius: 24px !important; 
                cursor: pointer !important; 
                text-decoration: none !important; 
                display: inline-flex !important; 
                align-items: center !important; 
                gap: 6px !important; 
                white-space: nowrap !important;
                font-size: 13px !important;
                transition: opacity 0.2s !important;
            }
            .voc-custom-btn:hover { opacity: 0.9 !important; }
        </style>

        <div class="voc-mini-container-wrap">
            <div class="voc-mini-wrap" role="region" aria-label="Latest Local News Mini Feed">
                
                <div class="voc-mini-header">
                    <div>
                        <span style="color: var(--voc-primary); font-weight: 700; letter-spacing: 0.05em; text-transform: uppercase; font-size: 12px; display: block; margin-bottom: 4px;">STAY CONNECTED</span>
                        <h3 style="font-size: 26px; font-weight: 800; color: #0f172a; margin: 0; line-height: 1.2;">' . esc_html($mini_title) . '</h3>
                    </div>
                    <a href="' . esc_url($atts['link']) . '" class="voc-custom-btn">
                        View All News &rarr;
                    </a>
                </div>';

        if ( empty( $articles ) ) {
            $output .= '<p style="color: #64748b; font-size: 16px; margin: 0;">No recent news available.</p>';
        } else {
            $output .= '<div class="voc-mini-grid">';
            foreach ( $articles as $article ) {
                $image_tag = '';
                $safe_title = esc_attr($article['title']);
                $is_pinned = ! empty( $article['is_pinned'] );
                $pinned_card_class = $is_pinned ? 'is-pinned-card' : '';
                $pinned_badge = $is_pinned ? '<span style="background-color: var(--voc-primary); color: #fff; padding: 2px 6px; border-radius: 20px; font-size: 10px;">📌 Pinned</span>' : '';
                $hash = md5( $article['link'] . $article['title'] );

                if ( ! empty( $article['thumbnail'] ) ) {
                    $image_tag = '
                    <a href="' . esc_url( $article['link'] ) . '" target="_blank" rel="noopener noreferrer" class="voc-mini-thumb voc-track-click" data-hash="' . esc_attr($hash) . '" tabindex="-1" aria-hidden="true">
                        <img src="' . esc_url( $article['thumbnail'] ) . '" alt="' . $safe_title . '" onerror="this.parentElement.style.display=\'none\'" />
                    </a>';
                }

                $output .= '
                <article class="voc-mini-card ' . $pinned_card_class . '">
                    ' . $image_tag . '
                    <div class="voc-mini-body">
                        <div style="display: flex; justify-content: space-between; align-items: center; gap: 8px; margin-bottom: 4px; font-size: 11px; font-weight: 600;">
                            <div>' . $pinned_badge . ' <span style="background-color: var(--voc-accent); color: var(--voc-primary); padding: 2px 8px; border-radius: 20px;">' . esc_html( $article['source'] ) . '</span></div>
                            <span style="color: #64748b; font-weight: 500;">' . esc_html( $article['display_date'] ) . '</span>
                        </div>
                        <h4 class="voc-mini-title">
                            <a href="' . esc_url( $article['link'] ) . '" class="voc-track-click" data-hash="' . esc_attr($hash) . '" target="_blank" rel="noopener noreferrer" style="color:inherit; text-decoration:none;">
                                ' . esc_html( $article['title'] ) . '
                            </a>
                        </h4>
                    </div>
                </article>';
            }
            $output .= '</div>';
        }

        $output .= '
            </div>
        </div>';

        return $output;
    }
}

// SHORTCODE REGISTRATION
add_action('init', function() {
    add_shortcode('villocity_local_news', 'voc_plugin_render_live_news_feed');
    add_shortcode('villocity_latest_news', 'voc_plugin_render_latest_news_mini');
    add_shortcode('villocity_news', 'voc_plugin_render_latest_news_mini');
});