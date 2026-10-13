<?php
/**
 * Every module this plugin ships. To add a module: create modules/<Name>/<Name>Module.php
 * extending BCG\VilloCity\Module, then add its class here.
 *
 * Module IDs deliberately match the 2.x plugin's IDs so each site's existing
 * selection carries over when it updates.
 *
 * @package VilloCity
 */

defined( 'ABSPATH' ) || exit;

return array(
	\BCG\VilloCity\Modules\AlertBar\AlertBarModule::class,
	\BCG\VilloCity\Modules\Resources\ResourcesModule::class,
);
