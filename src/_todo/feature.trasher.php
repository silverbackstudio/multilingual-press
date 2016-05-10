<?php # -*- coding: utf-8 -*-

use Inpsyde\MultilingualPress\Common\Properties;

add_action( 'mlp_and_wp_loaded', 'mlp_feature_trasher' );

/**
 * @param Properties $data Plugin data.
 *
 * @return void
 */
function mlp_feature_trasher( Properties $data ) {

	$controller = new Mlp_Trasher( $data->get( 'module_manager' ) );
	$controller->initialize();
}
