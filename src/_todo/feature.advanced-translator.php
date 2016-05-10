<?php # -*- coding: utf-8 -*-

use Inpsyde\MultilingualPress\Common\Properties;

add_action( 'inpsyde_mlp_loaded', 'mlp_feature_advanced_translator', 9 );

/**
 * Init the advanced translator.
 *
 * @param Properties $data
 * @return void
 */
function mlp_feature_advanced_translator( Properties $data ) {
	new Mlp_Advanced_Translator( $data );
}
