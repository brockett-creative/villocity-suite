<?php
/**
 * Runs when the plugin is deleted from the Plugins screen.
 *
 * By default this keeps all content and settings, because deleting and re-installing
 * the plugin (e.g. to fix a broken update) must never wipe a client's data.
 * To really remove VilloCity settings, add this to wp-config.php before deleting:
 *
 *     define( 'VILLOCITY_UNINSTALL_DELETE_DATA', true );
 *
 * Posts created by modules (resources, events...) are never deleted automatically.
 *
 * @package VilloCity
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

if ( ! defined( 'VILLOCITY_UNINSTALL_DELETE_DATA' ) || ! VILLOCITY_UNINSTALL_DELETE_DATA ) {
	return;
}

foreach ( array( 'villocity_enabled_modules', 'villocity_db_version', 'villocity_branding', 'villocity_alert_bar', 'villocity_flush_rewrite_rules' ) as $villocity_option ) {
	delete_option( $villocity_option );
}
