<?php # -*- coding: utf-8 -*-

use Inpsyde\MultilingualPress\Common\Properties;

_deprecated_file( 'Mlp_Plugin_Properties', '3.0.0', 'Inpsyde\MultilingualPress\Core\PluginProperties' );

class Mlp_Plugin_Properties extends Inpsyde\MultilingualPress\Core\PluginProperties {

	/**
	 * Wrapper for set().
	 *
	 * @param string $name  Property name.
	 * @param mixed  $value The new value.
	 *
	 * @return Properties|WP_Error
	 */
	public function __set( $name, $value ) {

		_doing_it_wrong( __METHOD__, 'Use set( $name, $value ) instead.', '3.0.0' );

		return $this->set( $name, $value );
	}

	/**
	 * Wrapper for get().
	 *
	 * @param string $name Property name.
	 *
	 * @return mixed|null
	 */
	public function __get( $name ) {

		_doing_it_wrong( __METHOD__, 'Use get( $name ) instead.', '3.0.0' );

		return $this->get( $name );
	}

	/**
	 * Wrapper for has().
	 *
	 * @param string $name Property name.
	 *
	 * @return bool
	 */
	public function __isset( $name ) {

		_doing_it_wrong( __METHOD__, 'Use has( $name ) instead.', '3.0.0' );

		return $this->has( $name );
	}
}
