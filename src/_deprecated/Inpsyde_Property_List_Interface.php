<?php # -*- coding: utf-8 -*-

use Inpsyde\MultilingualPress\Common\Properties;

_deprecated_file( 'Inpsyde_Property_List_Interface', '3.0.0', 'Inpsyde\MultilingualPress\Common\Properties' );

interface Inpsyde_Property_List_Interface extends Properties {

	/**
	 * Wrapper for set().
	 *
	 * @param string $name  Property name.
	 * @param mixed  $value The new value.
	 *
	 * @return Properties|WP_Error
	 */
	public function __set( $name, $value );

	/**
	 * Wrapper for get().
	 *
	 * @param string $name Property name.
	 *
	 * @return mixed|null
	 */
	public function __get( $name );

	/**
	 * Wrapper for has().
	 *
	 * @param string $name Property name.
	 *
	 * @return bool
	 */
	public function __isset( $name );
}
