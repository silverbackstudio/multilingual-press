<?php # -*- coding: utf-8 -*-

namespace Inpsyde\MultilingualPress\Asset;

use Inpsyde\MultilingualPress\Common\Locations;

/**
 * Factory for various asset objects.
 *
 * @package Inpsyde\MultilingualPress\Asset
 * @since   3.0.0
 */
class AssetFactory {

	/**
	 * @var string
	 */
	private $internal_script_path;

	/**
	 * @var string
	 */
	private $internal_script_url;

	/**
	 * @var string
	 */
	private $internal_style_path;

	/**
	 * @var string
	 */
	private $internal_style_url;

	/**
	 * Constructor. Sets up the properties.
	 *
	 * @since 3.0.0
	 *
	 * @param Locations $internal_locations MultilingualPress-specific locations object.
	 */
	public function __construct( Locations $internal_locations ) {

		$this->internal_script_path = $internal_locations->get( 'js', Locations::TYPE_PATH );

		$this->internal_script_url = $internal_locations->get( 'js', Locations::TYPE_URL );

		$this->internal_style_path = $internal_locations->get( 'css', Locations::TYPE_PATH );

		$this->internal_style_url = $internal_locations->get( 'css', Locations::TYPE_URL );
	}

	/**
	 * Returns a new script object, instantiated according to the given arguments.
	 *
	 * @since 3.0.0
	 *
	 * @param string      $handle       The handle.
	 * @param string      $file         File name.
	 * @param string[]    $dependencies Optional. The dependencies.
	 * @param string|null $version      Optional. Version of the file. Defaults to empty string.
	 *
	 * @return Script Script object.
	 */
	public function create_internal_script(
		$handle,
		$file,
		array $dependencies = [],
		$version = ''
	) {

		return DebugAwareScript::from_location(
			$handle,
			new AssetLocation(
				$file,
				$this->internal_script_path,
				$this->internal_script_url
			),
			$dependencies,
			$version
		);
	}

	/**
	 * Returns a new style object, instantiated according to the given arguments.
	 *
	 * @since 3.0.0
	 *
	 * @param string      $handle       The handle.
	 * @param string      $file         File name.
	 * @param string[]    $dependencies Optional. The dependencies.
	 * @param string|null $version      Optional. Version of the file. Defaults to empty string.
	 * @param string      $media        Optional. Style media data. Defaults to 'all'.
	 *
	 * @return Style Style object.
	 */
	public function create_internal_style(
		$handle,
		$file,
		array $dependencies = [],
		$version = '',
		$media = 'all'
	) {

		return DebugAwareStyle::from_location(
			$handle,
			new AssetLocation(
				$file,
				$this->internal_style_path,
				$this->internal_style_url
			),
			$dependencies,
			$version,
			$media
		);
	}

	// TODO: Add methods for non-internal assets...
}
