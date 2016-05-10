<?php # -*- coding: utf-8 -*-

namespace Inpsyde\MultilingualPress\Core;

use Inpsyde\MultilingualPress\Common\Properties;
use WP_Error;

/**
 * Property object holding all relevant plugin data.
 *
 * @package Inpsyde\MultilingualPress\Core
 */
class PluginProperties implements Properties {

	/**
	 * @var array
	 */
	private $deleted = [];

	/**
	 * @var bool
	 */
	private $is_frozen = false;

	/**
	 * @TODO: Adapt class.
	 * @var \Mlp_Internal_Locations
	 */
	private $locations;

	/**
	 * @var Properties
	 */
	private $parent;

	/**
	 * @var array
	 */
	private $properties = [];

	/**
	 * Sets the property with the given name to the given value.
	 *
	 * @param string $name  Property name.
	 * @param mixed  $value The new value.
	 *
	 * @return Properties|WP_Error
	 */
	public function set( $name, $value ) {

		if ( $this->is_frozen ) {
			return $this->stop( 'This object has been frozen. You cannot set properties anymore.' );
		}

		if ( 'locations' === $name ) {
			// TODO: Adapt class.
			if ( is_a( $value, '\Mlp_Internal_Locations' ) ) {
				$this->locations = $value;
			}
			// TODO: Decide what to do in case of an invalid value (i.e., no \Mlp_Internal_Locations implementation).
		} else {
			$this->properties[ $name ] = $value;
		}

		unset( $this->deleted[ $name ] );

		return $this;
	}

	/**
	 * Returns the value of the property with the given name.
	 *
	 * @param string $name Property name.
	 *
	 * @return mixed|null
	 */
	public function get( $name ) {

		switch ( $name ) {
			case 'locations':
				return $this->locations;

			case 'css_url':
				return $this->locations->get_dir( 'css', 'url' );

			case 'js_url':
				return $this->locations->get_dir( 'js', 'url' );

			case 'flag_url':
				return $this->locations->get_dir( 'flags', 'url' );

			case 'flag_path':
				return $this->locations->get_dir( 'flags', 'path' );

			case 'image_url':
				return $this->locations->get_dir( 'images', 'url' );

			case 'plugin_dir_path':
				return $this->locations->get_dir( 'plugin', 'path' );

			case 'plugin_url':
				return $this->locations->get_dir( 'plugin', 'url' );
		}

		if ( isset( $this->properties[ $name ] ) ) {
			return $this->properties[ $name ];
		}

		if ( isset( $this->deleted[ $name ] ) ) {
			return null;
		}

		if ( $this->parent ) {
			return $this->parent->get( $name );
		}

		return null;
	}

	/**
	 * Checks if the property with the given name exists.
	 *
	 * @param string $name Property name.
	 *
	 * @return bool
	 */
	public function has( $name ) {

		if ( isset( $this->properties[ $name ] ) ) {
			return true;
		}

		if ( isset( $this->deleted[ $name ] ) ) {
			return false;
		}

		if ( $this->parent ) {
			return $this->parent->has( $name );
		}

		return false;
	}

	/**
	 * Deletes the property with the given name.
	 *
	 * @param string $name Property name.
	 *
	 * @return Properties|WP_Error
	 */
	public function delete( $name ) {

		if ( $this->is_frozen ) {
			return $this->stop( 'This object has been frozen. You cannot delete properties anymore.' );
		}

		$this->deleted[ $name ] = true;

		unset( $this->properties[ $name ] );

		return $this;
	}

	/**
	 * Sets the given parent object. Properties of this object will be inherited.
	 *
	 * @param Properties $parent Parent property object.
	 *
	 * @return Properties|WP_Error
	 */
	public function set_parent( Properties $parent ) {

		if ( $this->is_frozen ) {
			return $this->stop( 'This object has been frozen. You cannot change the parent anymore.' );
		}

		$this->parent = $parent;

		return $this;
	}

	/**
	 * Checks if this object has a parent.
	 *
	 * @return bool
	 */
	public function has_parent() {

		return isset( $this->parent );
	}

	/**
	 * Locks write access to this object.
	 *
	 * @return Properties
	 */
	public function freeze() {

		$this->is_frozen = true;

		return $this;
	}

	/**
	 * Checks if this object has been set read-only.
	 *
	 * @return bool
	 */
	public function is_frozen() {

		return $this->is_frozen;
	}

	/**
	 * Used for attempts to write to a frozen instance.
	 *
	 * @param string $message Error message. Always be specific.
	 * @param string $code    Optional. Error code. Re-use the same code to group error messages. Defaults to __CLASS__.
	 *
	 * @return WP_Error
	 */
	private function stop( $message, $code = '' ) {

		return \Mlp_WP_Error_Factory::create( $code ?: __CLASS__, $message );
	}
}
