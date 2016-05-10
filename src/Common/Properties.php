<?php # -*- coding: utf-8 -*-

namespace Inpsyde\MultilingualPress\Common;

/**
 * Interface for simple property objects.
 *
 * @package Inpsyde\MultilingualPress\Common
 */
interface Properties {

	/**
	 * Sets the property with the given name to the given value.
	 *
	 * @param string $name  Property name.
	 * @param mixed  $value The new value.
	 *
	 * @return Properties
	 */
	public function set( $name, $value );

	/**
	 * Returns the value of the property with the given name.
	 *
	 * @param string $name Property name.
	 *
	 * @return mixed
	 */
	public function get( $name );

	/**
	 * Checks if the property with the given name exists.
	 *
	 * @param string $name Property name.
	 *
	 * @return bool
	 */
	public function has( $name );

	/**
	 * Deletes the property with the given name.
	 *
	 * @param string $name Property name.
	 *
	 * @return Properties
	 */
	public function delete( $name );

	/**
	 * Sets the given parent object. Properties of this object will be inherited.
	 *
	 * @param Properties $parent Parent property object.
	 *
	 * @return Properties
	 */
	public function set_parent( Properties $parent );

	/**
	 * Checks if this object has a parent.
	 *
	 * @return bool
	 */
	public function has_parent();

	/**
	 * Locks write access to this object.
	 *
	 * @return Properties
	 */
	public function freeze();

	/**
	 * Checks if this object has been set read-only.
	 *
	 * @return bool
	 */
	public function is_frozen();
}
