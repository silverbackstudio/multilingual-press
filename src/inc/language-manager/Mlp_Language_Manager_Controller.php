<?php # -*- coding: utf-8 -*-

use Inpsyde\MultilingualPress\Common\Nonce\WPNonce;
use Inpsyde\MultilingualPress\Database\Table;
use Inpsyde\MultilingualPress\Database\Table\LanguagesTable;
use Inpsyde\MultilingualPress\Database\WPDBTableInstaller;

/**
 * Class Mlp_Language_Manager_Controller
 *
 * Control settings page for Language Manager table.
 *
 * @version 2014.07.17
 * @author  Inpsyde GmbH, toscho
 * @license GPL
 */
class Mlp_Language_Manager_Controller implements Mlp_Updatable {

	/**
	 * @var Inpsyde_Property_List_Interface
	 */
	private $plugin_data;

	/**
	 * @var Mlp_Language_Manager_Options_Page_Data
	 */
	private $setting;

	/**
	 * @var Mlp_Language_Manager_Page_View
	 */
	private $view;

	/**
	 * @var WPNonce
	 */
	private $nonce;

	/**
	 * @var string
	 */
	private $page_title;

	/**
	 * @var Mlp_Language_Manager_Pagination_Data
	 */
	private $pagination_data;

	/**
	 * @var Mlp_Table_Pagination_View
	 */
	private $pagination_view;

	/**
	 * @var string
	 */
	private $reset_action = 'mlp_reset_language_table';

	/**
	 * @var wpdb
	 */
	private $wpdb;

	/**
	 * Constructor.
	 *
	 * @param Inpsyde_Property_List_Interface $data
	 * @param Mlp_Data_Access                 $database
	 * @param wpdb                            $wpdb
	 */
	public function __construct(
		Inpsyde_Property_List_Interface $data,
		Mlp_Data_Access                 $database,
		wpdb                            $wpdb
		) {

		$this->plugin_data     = $data;
		$this->wpdb = $wpdb;
		$this->page_title      = __( 'Language Manager', 'multilingual-press' );
		$this->pagination_data = new Mlp_Language_Manager_Pagination_Data( $database );
		$this->setting       = new Mlp_Language_Manager_Options_Page_Data(
			$this->page_title,
			$this->plugin_data->get( 'type_factory' )
		);

		$this->nonce = new WPNonce( $this->setting->action() );

		$this->view            = new Mlp_Language_Manager_Page_View(
			$this->setting,
			$this,
			$this->pagination_data,
			$this->nonce
		);

		$updater = new Mlp_Language_Updater(
			$this->pagination_data,
			new Mlp_Array_Diff( $this->get_columns() ),
			$this->plugin_data->get( 'languages' ),
			$this->nonce
		);

		add_action(
			'admin_post_mlp_update_languages',
			[ $updater, 'update_languages' ]
		);
		add_action(
			'network_admin_menu',
			[ $this, 'register_page' ], 50
		);
		add_action(
			"admin_post_{$this->reset_action}",
			[ $this, 'reset_table' ]
		);
	}

	/**
	 * @return void
	 */
	public function register_page() {

		$page_id = add_submenu_page(
			'settings.php',
			$this->page_title,
			$this->page_title,
			'manage_network_options',
			'language-manager',
			[ $this->view, 'render' ]
		);

		add_action( "load-$page_id", [ $this, 'enqueue_style' ] );
	}

	/**
	 * Enqueue style.
	 *
	 * @wp-hook load-{$page}
	 *
	 * @return void
	 */
	public function enqueue_style() {

		// TODO: Check why this is not needed. The CSS must've been enqueued somewhere before already...
		//$assets = $this->plugin_data->get( 'assets' );
		//$assets->provide( 'mlp_admin_css' );
	}

	/**
	 * @param string $name
	 *
	 * @return mixed|void Either a value, or void for actions.
	 */
	public function update( $name ) {

		if ( 'before_form' === $name ) {
			$this->before_form();

			return;
		}

		if ( 'before_table' === $name ) {
			$this->before_table();

			return;
		}

		if ( 'show_table' === $name ) {
			$this->show_table();

			return;
		}

		if ( 'after_table' === $name ) {
			$this->after_table();

			return;
		}

		if ( 'after_form' === $name ) {
			$this->after_form();
		}
	}

	/**
	 * @return void
	 */
	private function get_reset_table_link() {

		$request = remove_query_arg( 'msg', wp_unslash( $_SERVER['REQUEST_URI'] ) );
		$url     = add_query_arg( [
			'action'               => $this->reset_action,
			$this->nonce->action() => (string) $this->nonce,
			'_wp_http_referer'     => esc_attr( $request )
		], (string) $this->setting->url() );
		?>
		<p>
			<a href="<?php echo esc_url( $url ); ?>" class="delete submitdelete" style="color:red">
				<?php esc_html_e( 'Reset table to default values', 'multilingual-press' ); ?>
			</a>
		</p>
		<?php
	}

	/**
	 * @return void
	 */
	public function reset_table() {

		\Inpsyde\MultilingualPress\check_admin_referer( $this->nonce );

		$table_prefix = $this->wpdb->base_prefix;

		$table = new LanguagesTable( $table_prefix );

		$installer = new WPDBTableInstaller( $table );
		$installer->uninstall();
		$installer->install();

		/**
		 * Runs after having reset the database table.
		 *
		 * @param Table $table Languages table object.
		 */
		do_action( 'mlp_reset_table_done', $table );

		$url = add_query_arg( 'msg', 'resettable', $_REQUEST[ '_wp_http_referer' ] );
		wp_safe_redirect( $url );
		\Inpsyde\MultilingualPress\call_exit();
	}

	/**
	 * @return void
	 */
	private function before_form() {

		if ( ! empty( $_GET['msg'] ) ) {
			echo $this->get_update_message();
		}
	}

	/**
	 * Get message text for success notice.
	 *
	 * @return string
	 */
	private function get_update_message() {

		$type  = strtok( $_GET[ 'msg' ], '-' );
		$num   = (int) strtok( '-' );
		$num_f = number_format_i18n( $num );
		$text  = '';

		if ( 'updated' === $type ) {
			$text = sprintf(
				_n(
					'One language changed.',
					'%s languages changed.',
					$num,
					'multilingual-press'
				),
				$num_f
			);
		}
		if ( 'resettable' === $type ) {
			$text = esc_html__(
				'Table reset to default values.',
				'multilingual-press'
			);
		}

		if ( '' === $text )
			return '';

		return '<div class="updated"><p>' . esc_html( $text ) . '</p></div>';
	}

	/**
	 * @return void
	 */
	private function after_form() {

		?>
		<p class="description" style="padding-top:20px;clear:both">
			<?php
			esc_html_e(
				'Languages are sorted descending by priority and ascending by their English name.',
				'multilingual-press'
			);
			?>
		</p>

		<p class="description">
			<?php
			esc_html_e(
				'If you change the priority of a language to a higher value, it will show up on an earlier page.',
				'multilingual-press'
			);
			?>
		</p>
		<?php
		if ( isset( $_GET['msg'] ) && 'resettable' === $_GET['msg'] ) {
			return;
		}

		$this->get_reset_table_link();
	}

	/**
	 * @return void
	 */
	private function before_table() {

		?>
		<div class="tablenav top">
			<?php $this->get_pagination_object()->print_pagination(); ?>
		</div>
		<?php
	}

	/**
	 * @return void
	 */
	private function after_table() {

		?>
		<div class="tablenav bottom">
			<?php $this->get_pagination_object()->print_pagination(); ?>
		</div>
		<?php
	}

	/**
	 * @return Mlp_Table_Pagination_View
	 */
	private function get_pagination_object() {

		if ( ! is_a( $this->pagination_view, 'Mlp_Table_Pagination_View' ) )
			$this->pagination_view = new Mlp_Table_Pagination_View( $this->pagination_data );

		return $this->pagination_view;
	}

	/**
	 * @return void
	 */
	private function show_table() {

		$view = new Mlp_Admin_Table_View (
			$this->plugin_data->get( 'languages' ),
			$this->pagination_data,
			$this->get_columns(),
			'mlp-language-manager-table',
			'languages'
		);
		$view->show_table();
	}

	/**
	 * @return array
	 */
	private function get_columns() {
		return [
			'native_name' => [
				'header'     => __( 'Native name', 'multilingual-press' ),
				'type'       => 'input_text',
				'attributes' => [ 'size' => 20 ],
			],
			'english_name' => [
				'header'     => __( 'English name', 'multilingual-press' ),
				'type'       => 'input_text',
				'attributes' => [ 'size' => 20 ],
			],
			'is_rtl' => [
				'header'     => __( 'RTL', 'multilingual-press' ),
				'type'       => 'input_checkbox',
				'attributes' => [ 'size' => 20 ],
			],
			'http_name' => [
				'header'     => __( 'HTTP', 'multilingual-press' ),
				'type'       => 'input_text',
				'attributes' => [ 'size' => 5 ],
			],
			'iso_639_1' => [
				'header'     => __( 'ISO&#160;639-1', 'multilingual-press' ),
				'type'       => 'input_text',
				'attributes' => [ 'size' => 5 ],
			],
			'iso_639_2' => [
				'header'     => __( 'ISO&#160;639-2', 'multilingual-press' ),
				'type'       => 'input_text',
				'attributes' => [ 'size' => 5 ],
			],
			'wp_locale' => [
				'header'     => __( 'wp_locale', 'multilingual-press' ),
				'type'       => 'input_text',
				'attributes' => [ 'size' => 5 ],
			],
			'priority' => [
				'header'     => __( 'Priority', 'multilingual-press' ),
				'type'       => 'input_number',
				'attributes' => [
					'min'  => 1,
					'max'  => 10,
					'size' => 3,
				 ],
			],
		];
	}
}
