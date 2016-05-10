<?php # -*- coding: utf-8 -*-

use Inpsyde\MultilingualPress\Common\Properties;

if ( is_admin() ) {
	add_action( 'inpsyde_mlp_loaded', 'mlp_feature_user_backend_language', 0 );
}

/**
 * @param Properties $data Plugin data.
 *
 * @return void
 */
function mlp_feature_user_backend_language( Properties $data ) {

	$user_lang = new Mlp_User_Backend_Language( $data->get( 'module_manager' ) );
	$user_lang->setup();
}
