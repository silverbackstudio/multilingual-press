<?php # -*- coding: utf-8 -*-

namespace Inpsyde\MultilingualPress\Common\Admin;

/**
 * Model for an action link, for example, for the Plugins page in the Network Admin.
 *
 * @package Inpsyde\MultilingualPress\Common\Admin
 * @since   3.0.0
 */
class ActionLink {

	/**
	 * @var callable
	 */
	private $add_callback;

	/**
	 * @var string
	 */
	private $html;

	/**
	 * @var string
	 */
	private $id;

	/**
	 * Constructor. Sets up the properties.
	 *
	 * @since 3.0.0
	 *
	 * @param string   $id              Link ID.
	 * @param string   $html            Link HTML.
	 * @param callable $add_callback    Optional. Callback to handle adding the link. Defaults to null.
	 */
	public function __construct( $id, $html, callable $add_callback = null ) {

		$this->id = (string) $id;

		$this->html = (string) $html;

		$this->add_callback = $add_callback;
	}

	/**
	 * Registers the link by using the given WordPress hook.
	 *
	 * @since 3.0.0
	 *
	 * @param string $hook The name of a WordPress filter.
	 *
	 * @return void
	 */
	public function register( $hook ) {

		add_filter( $hook, [ $this, 'add' ] );
	}

	/**
	 * Adds the link.
	 *
	 * @since 3.0.0
	 *
	 * @param array $links The current links.
	 *
	 * @return array All links.
	 */
	public function add( array $links ) {

		if ( is_callable( $this->add_callback ) ) {
			return (array) call_user_func( [ $this, 'add_callback' ], $links, $this->id, $this->html );
		}

		return array_merge( $links, [ $this->id => $this->html ] );
	}
}
