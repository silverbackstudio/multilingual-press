<?php # -*- coding: utf-8 -*-

use Inpsyde\MultilingualPress\Common\Properties;

if ( is_admin() ) {
	add_action( 'mlp_and_wp_loaded', 'mlp_feature_term_translator', 1000 );
}

/**
 * @param Properties $data Plugin data.
 *
 * @return bool
 */
function mlp_feature_term_translator( Properties $data ) {

	$controller = new Mlp_Term_Translation_Controller( $data->get( 'content_relations' ) );

	return $controller->setup();
}
