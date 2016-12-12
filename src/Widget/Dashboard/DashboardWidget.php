<?php # -*- coding: utf-8 -*-

namespace Inpsyde\MultilingualPress\Widget\Dashboard;

/**
 * Dashboard widget.
 *
 * @package Inpsyde\MultilingualPress\Widget\Dashboard
 * @since   3.0.0
 */
class DashboardWidget {

	/**
	 * @var array
	 */
	private $callback_args;

	/**
	 * @var string
	 */
	private $capability;

	/**
	 * @var callable
	 */
	private $control_callback;

	/**
	 * @var string
	 */
	private $id;

	/**
	 * @var string
	 */
	private $name;

	/**
	 * @var View
	 */
	private $view;

	/**
	 * Constructor. Sets up the properties.
	 *
	 * @since 3.0.0
	 *
	 * @param string   $id               Widget ID.
	 * @param string   $name             Widget name.
	 * @param View     $view             View object.
	 * @param string   $capability       Optional. Capability required to view the widget. Defaults to empty string.
	 * @param array    $callback_args    Optional. Callback arguments. Defaults to empty array.
	 * @param callable $control_callback Optional. Control callback. Defaults to null.
	 */
	public function __construct(
		$id,
		$name,
		View $view,
		$capability = '',
		array $callback_args = [],
		callable $control_callback = null
	) {

		$this->id = (string) $id;

		$this->name = (string) $name;

		$this->view = $view;

		$this->capability = (string) $capability;

		$this->callback_args = $callback_args;

		$this->control_callback = $control_callback;
	}

	/**
	 * Registers the widget.
	 *
	 * @since 3.0.0
	 *
	 * @return bool Whether or not the widget was registered successfully.
	 */
	public function register() {

		if ( $this->capability && ! current_user_can( $this->capability ) ) {
			return false;
		}

		return add_action( 'wp_dashboard_setup', function () {

			wp_add_dashboard_widget(
				$this->id,
				$this->name,
				[ $this->view, 'render' ],
				$this->control_callback,
				$this->callback_args
			);
		} );
	}
}
