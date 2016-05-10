<?php # -*- coding: utf-8 -*-

$parent_dir = dirname( dirname( __DIR__ ) ) . '/';

require_once $parent_dir . 'vendor/autoload.php';

$src_dir = $parent_dir . 'src/';

// TODO: Adapt paths.
require_once $src_dir . '_todo/autoload/Mlp_Load_Controller.php';

new Mlp_Load_Controller( $src_dir . '_todo' );
