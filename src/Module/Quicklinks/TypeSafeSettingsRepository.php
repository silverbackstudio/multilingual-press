<?php # -*- coding: utf-8 -*-

namespace Inpsyde\MultilingualPress\Module\Quicklinks;

/**
 * Type-safe quicklinks settings repository implementation.
 *
 * @package Inpsyde\MultilingualPress\Module\Quicklinks
 * @since   3.0.0
 */
final class TypeSafeSettingsRepository implements SettingsRepository {

	/**
	 * @var string[]
	 */
	private $available_positions;

	/**
	 * Returns all available positions.
	 *
	 * @since 3.0.0
	 *
	 * @return string[] An array with position setting values as keys and position names as values.
	 */
	public function get_available_positions() {

		if ( ! $this->available_positions ) {
			$this->available_positions = [
				SettingsRepository::POSITION_TOP_LEFT     => __( 'Top left', 'multilingual-press' ),
				SettingsRepository::POSITION_TOP_RIGHT    => __( 'Top right', 'multilingual-press' ),
				SettingsRepository::POSITION_BOTTOM_LEFT  => __( 'Bottom left', 'multilingual-press' ),
				SettingsRepository::POSITION_BOTTOM_RIGHT => __( 'Bottom right', 'multilingual-press' ),
			];
		}

		return $this->available_positions;
	}

	/**
	 * Returns the currently selected position.
	 *
	 * @since 3.0.0
	 *
	 * @return string The currently selected position.
	 */
	public function get_current_position() {

		$settings = get_site_option( SettingsRepository::OPTION );

		$valid_positions = array_keys( $this->get_available_positions() );

		if (
			! empty( $settings['mlp_quicklink_position'] )
			&& in_array( $settings['mlp_quicklink_position'], $valid_positions, true )
		) {
			return $settings['mlp_quicklink_position'];
		}

		return in_array( SettingsRepository::POSITION_TOP_RIGHT, $valid_positions, true )
			? SettingsRepository::POSITION_TOP_RIGHT
			: (string) array_pop( $valid_positions );
	}

	/**
	 * Sets the position to the given value.
	 *
	 * @since 3.0.0
	 *
	 * @param string $position Position value.
	 *
	 * @return bool Whether or not the position was set successfully.
	 */
	public function set_position( $position ) {

		$settings = get_site_option( SettingsRepository::OPTION );

		$settings['mlp_quicklink_position'] = (string) $position;

		return update_site_option( SettingsRepository::OPTION, $settings );
	}
}
