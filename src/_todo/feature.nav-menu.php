<?php # -*- coding: utf-8 -*-

use Inpsyde\MultilingualPress\Common\Properties;

add_action( 'inpsyde_mlp_loaded', 'mlp_nav_menu_init' );

/**
 * @param Properties $data Plugin data.
 *
 * @return void
 */
function mlp_nav_menu_init( Properties $data ) {

	$controller = new Mlp_Nav_Menu_Controller(
		$data->get( 'language_api' ),
		$data->get( 'assets' )
	);
	$controller->initialize();

	if ( is_admin() ) {
		$controller->backend_setup();
	} else {
		add_action( 'template_redirect', [ $controller, 'frontend_setup' ] );
	}
}
