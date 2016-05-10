<?php # -*- coding: utf-8 -*-

use Inpsyde\MultilingualPress\Common\Properties;

add_action( 'inpsyde_mlp_loaded', 'mlp_feature_redirect' );

/**
 * Initializes the redirect controller.
 *
 * @param Properties $data Plugin data.
 *
 * @return void
 */
function mlp_feature_redirect( Properties $data ) {

	$redirect = new Mlp_Redirect( $data->get( 'module_manager' ), $data->get( 'language_api' ), null );

	if ( $redirect->setup() ) {
		$user = new Mlp_Redirect_User_Settings();
		$user->setup();
	}
}
