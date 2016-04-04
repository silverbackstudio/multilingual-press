<?php # -*- coding: utf-8 -*-

add_action( 'inpsyde_mlp_loaded', 'mlp_feature_admin_bar_switcher' );

/**
 * Sets up the feature.
 *
 * @param Inpsyde_Property_List_Interface $data Plugin data.
 *
 * @return void
 */
function mlp_feature_admin_bar_switcher( Inpsyde_Property_List_Interface $data ) {

	$controller = new Mlp_Admin_Bar_Switcher();
	$controller->setup();
}
