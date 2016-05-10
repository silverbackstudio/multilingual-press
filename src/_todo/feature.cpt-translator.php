<?php # -*- coding: utf-8 -*-

use Inpsyde\MultilingualPress\Common\Properties;

add_action( 'inpsyde_mlp_loaded', 'mlp_feature_cpt_translator', 8 );

/**
 * Init the CPT filter routine.
 *
 * @param Properties $data
 * @return void
 */
function mlp_feature_cpt_translator( Properties $data ) {
	new Mlp_Cpt_Translator( $data );
}
