<?php
/**
 * Hooks the plugin into WordPress's normal "update available" flow, using GitHub releases.
 *
 * @package VilloCity
 */

namespace BCG\VilloCity;

use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

defined( 'ABSPATH' ) || exit;

/**
 * Wraps Plugin Update Checker (https://github.com/YahnisElsts/plugin-update-checker).
 *
 * How it works: every ~12 hours each site asks GitHub for the latest release of the
 * repo. If its tag (e.g. v3.1.0) is newer than VILLOCITY_VERSION, WordPress shows the
 * normal "update available" notice and installs the villocity-suite.zip attached to
 * that release.
 *
 * Optional wp-config.php constants:
 *   VILLOCITY_UPDATE_REPO   Repo URL, if it ever moves.
 *   VILLOCITY_GITHUB_TOKEN  Read-only token. Only needed while the repo is private.
 *   VILLOCITY_DISABLE_UPDATES  true on local/dev copies so they never self-update.
 */
final class Updater {

	private const DEFAULT_REPO = 'https://github.com/brockett-creative/villocity-suite/';

	/**
	 * Register the update checker.
	 */
	public function register(): void {
		if ( defined( 'VILLOCITY_DISABLE_UPDATES' ) && VILLOCITY_DISABLE_UPDATES ) {
			return;
		}

		$loader = VILLOCITY_DIR . 'vendor/yahnis-elsts/plugin-update-checker/plugin-update-checker.php';
		if ( ! is_readable( $loader ) ) {
			// Running from a git checkout without `composer install`. Nothing to do.
			return;
		}
		require_once $loader;

		$repo = defined( 'VILLOCITY_UPDATE_REPO' ) ? (string) constant( 'VILLOCITY_UPDATE_REPO' ) : self::DEFAULT_REPO;

		$checker = PucFactory::buildUpdateChecker( $repo, VILLOCITY_FILE, 'villocity-suite' );

		// Install the built zip attached to the release and never GitHub's auto-generated
		// source zip (wrong folder name, no vendor/ directory, includes dev files).
		// 2 = Api::REQUIRE_RELEASE_ASSETS; the literal avoids depending on PUC's versioned namespace.
		$checker->getVcsApi()->enableReleaseAssets( '/^villocity-suite\.zip$/i', 2 );

		if ( defined( 'VILLOCITY_GITHUB_TOKEN' ) && constant( 'VILLOCITY_GITHUB_TOKEN' ) ) {
			$checker->setAuthentication( (string) constant( 'VILLOCITY_GITHUB_TOKEN' ) );
		}
	}
}
