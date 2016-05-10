<?php # -*- coding: utf-8 -*-

use Inpsyde\MultilingualPress\Common\Properties;

add_action( 'inpsyde_mlp_loaded', 'mlp_feature_quicklink' );

/**
 * @param Properties $data Plugin data.
 *
 * @return void
 */
function mlp_feature_quicklink( Properties $data ) {

	$controller = new Mlp_Quicklink(
		$data->get( 'module_manager' ),
		$data->get( 'language_api' ),
		$data->get( 'assets' )
	);
	$controller->initialize();
}
