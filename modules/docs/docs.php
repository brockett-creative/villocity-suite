<?php
/**
 * Module Name: VilloCity Documentation Hub
 * Module URI:  https://villocity.org
 * Description: Master reference guide, shortcode cheat sheets, and user manuals for managing suite modules.
 * Version:     2.1.0
 * Last Updated: September 5, 2026
 * Author:      Brockett Creative Group, Inc.
 * Author URI:  https://brockettcreative.com
 * Copyright:   © 2026 Brockett Creative Group, Inc. All Rights Reserved.
 * Text Domain: villocity-suite
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define Local Module Directory Path
define( 'VOC_DOCS_DIR', plugin_dir_path( __FILE__ ) );

// ==========================================
// INCLUDE SUB-MODULE COMPONENTS
// ==========================================
// Load Admin Dashboard & Menu
if ( is_admin() && file_exists( VOC_DOCS_DIR . 'admin.php' ) ) {
    require_once VOC_DOCS_DIR . 'admin.php';
}

// Load Frontend Component (Lightweight stub for suite structure consistency)
if ( file_exists( VOC_DOCS_DIR . 'frontend.php' ) ) {
    require_once VOC_DOCS_DIR . 'frontend.php';
}