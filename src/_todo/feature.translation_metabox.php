<?php # -*- coding: utf-8 -*-

use Inpsyde\MultilingualPress\Common\Properties;

add_action( 'inpsyde_mlp_loaded', 'mlp_feature_translation_metabox' );

/**
 * @param Properties $data
 * @return void
 */
function mlp_feature_translation_metabox( Properties $data ) {

	new Mlp_Translation_Metabox( $data );

	if ( 'POST' !== $_SERVER[ 'REQUEST_METHOD' ] )
		return;

	$switcher = new Mlp_Global_Switcher( Mlp_Global_Switcher::TYPE_POST );

	add_action( 'mlp_before_post_synchronization', [ $switcher, 'strip' ] );
	add_action( 'mlp_after_post_synchronization',  [ $switcher, 'fill' ] );
}
