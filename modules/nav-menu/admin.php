<?php
/**
 * Module Name: VilloCity Native Navigation Menu - Admin & Mega Menu Meta Engine
 * Description: Handles custom menu item fields and dynamic class injection for Mega Menu layouts.
 * Version:     2.0.2 (Fatal Error Resolution & Safe Execution Guard)
 * Author:      Brockett Creative Group, Inc.
 * Text Domain: villocity-suite
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// 1. Render "VilloCity Menu Display Layout" Dropdown in WP Menu Editor
add_action( 'wp_nav_menu_item_custom_fields', 'villocity_add_mega_menu_custom_fields', 10, 4 );
function villocity_add_mega_menu_custom_fields( $item_id, $item, $depth, $args ) {
    // Only display Mega Menu options on top-level menu items (Depth 0)
    if ( 0 !== (int) $depth ) {
        return;
    }

    $mega_layout = get_post_meta( $item_id, '_voc_mega_menu_layout', true );
    if ( empty( $mega_layout ) ) {
        $mega_layout = 'none';
    }
    ?>
    <div class="field-voc-mega-layout description-wide" style="margin: 12px 0; padding: 12px; background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 8px; box-sizing: border-box;">
        <label for="edit-menu-item-voc-mega-layout-<?php echo esc_attr( $item_id ); ?>" style="font-weight: 700; color: #860038; display: block; margin-bottom: 4px;">
            <?php esc_html_e( 'VilloCity Menu Display Layout', 'villocity-suite' ); ?>
        </label>
        <select id="edit-menu-item-voc-mega-layout-<?php echo esc_attr( $item_id ); ?>" name="voc_mega_menu_layout[<?php echo esc_attr( $item_id ); ?>]" class="widefat">
            <option value="none" <?php selected( $mega_layout, 'none' ); ?>><?php esc_html_e( 'Standard Dropdown Menu', 'villocity-suite' ); ?></option>
            <option value="voc-mega-2-col" <?php selected( $mega_layout, 'voc-mega-2-col' ); ?>><?php esc_html_e( 'Mega Menu (2 Columns)', 'villocity-suite' ); ?></option>
            <option value="voc-mega-3-col" <?php selected( $mega_layout, 'voc-mega-3-col' ); ?>><?php esc_html_e( 'Mega Menu (3 Columns - Recommended)', 'villocity-suite' ); ?></option>
            <option value="voc-mega-4-col" <?php selected( $mega_layout, 'voc-mega-4-col' ); ?>><?php esc_html_e( 'Mega Menu (4 Columns)', 'villocity-suite' ); ?></option>
        </select>
        <span class="description" style="font-size: 11px; color: #64748b; margin-top: 4px; display: block;">
            <?php esc_html_e( 'Selecting a Mega Menu layout converts child menu items into multi-column category headers.', 'villocity-suite' ); ?>
        </span>
    </div>
    <?php
}

// 2. Save Custom Choice & Delete Meta When Standard Selected
add_action( 'wp_update_nav_menu_item', 'villocity_save_mega_menu_custom_fields', 10, 3 );
function villocity_save_mega_menu_custom_fields( $menu_id, $menu_item_db_id, $args ) {
    // Verify Nonce & Permissions
    if ( ! current_user_can( 'edit_theme_options' ) ) {
        return;
    }

    if ( isset( $_POST['voc_mega_menu_layout'][ $menu_item_db_id ] ) ) {
        $layout = sanitize_text_field( $_POST['voc_mega_menu_layout'][ $menu_item_db_id ] );
        
        if ( 'none' === $layout || empty( $layout ) ) {
            delete_post_meta( $menu_item_db_id, '_voc_mega_menu_layout' );
        } else {
            update_post_meta( $menu_item_db_id, '_voc_mega_menu_layout', $layout );
        }
    }
}

// 3. Safe Execution Injection: Filter Menu Classes at Setup
add_filter( 'wp_setup_nav_menu_item', 'villocity_inject_mega_menu_classes_safe' );
function villocity_inject_mega_menu_classes_safe( $menu_item ) {
    if ( ! is_object( $menu_item ) || ! isset( $menu_item->ID ) || $menu_item->ID <= 0 ) {
        return $menu_item;
    }

    // Do not run on non-post menu items or draft setup objects
    if ( isset( $menu_item->post_type ) && 'nav_menu_item' !== $menu_item->post_type ) {
        return $menu_item;
    }

    $mega_classes = array( 'voc-mega-menu', 'voc-mega-2-col', 'voc-mega-3-col', 'voc-mega-4-col', 'has-mega-menu' );
    $mega_layout  = get_post_meta( $menu_item->ID, '_voc_mega_menu_layout', true );

    if ( ! is_array( $menu_item->classes ) ) {
        $menu_item->classes = array();
    }

    if ( ! empty( $mega_layout ) && 'none' !== $mega_layout ) {
        $menu_item->classes[] = 'voc-mega-menu';
        $menu_item->classes[] = $mega_layout;
        $menu_item->classes[] = 'has-mega-menu';
        $menu_item->classes   = array_unique( $menu_item->classes );
    } else {
        // Strictly strip mega menu classes if set to 'none'
        $menu_item->classes = array_diff( $menu_item->classes, $mega_classes );
    }

    return $menu_item;
}