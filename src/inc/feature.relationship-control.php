<?php # -*- coding: utf-8 -*-

if ( ! is_admin() ) {
	return;
}

add_action( 'mlp_and_wp_loaded', 'mlp_feature_relationship_control' );

/**
 * Init the relinking feature.
 *
 * @param Inpsyde_Property_List_Interface $plugin_data
 *
 * @return void
 */
function mlp_feature_relationship_control( Inpsyde_Property_List_Interface $plugin_data ) {

	$relationship_control = new Mlp_Relationship_Control( $plugin_data );
	$relationship_control->initialize();

	if ( 'POST' !== $_SERVER['REQUEST_METHOD'] ) {
		return;
	}

	$switcher = new Mlp_Global_Switcher( Mlp_Global_Switcher::TYPE_POST );

	add_action( 'mlp_before_post_synchronization', array( $switcher, 'strip' ) );
	add_action( 'mlp_after_post_synchronization', array( $switcher, 'fill' ) );
}
