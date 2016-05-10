<?php # -*- coding: utf-8 -*-

use Inpsyde\MultilingualPress\Common\Properties;

add_action( 'inpsyde_mlp_loaded', 'mlp_feature_alternative_language_title' );

/**
 * Sets up the feature.
 *
 * @param Properties $data Plugin data.
 *
 * @return void
 */
function mlp_feature_alternative_language_title( Properties $data ) {

	$controller = new Mlp_Alternative_Language_Title(
		new Mlp_Alternative_Language_Title_Module( $data->get( 'module_manager' ) ),
		new Mlp_Admin_Bar_Customizer()
	);
	$controller->setup();
}
