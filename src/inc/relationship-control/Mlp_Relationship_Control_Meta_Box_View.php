<?php # -*- coding: utf-8 -*-

/**
 * Render the control elements for the relationship control feature.
 */
class Mlp_Relationship_Control_Meta_Box_View {

	/**
	 * @var Mlp_Relationship_Control_Data
	 */
	private $data;

	/**
	 * @var WP_Post
	 */
	private $post;

	/**
	 * @var int
	 */
	private $remote_post_id = 0;

	/**
	 * @var int
	 */
	private $remote_site_id;

	/**
	 * @var int
	 */
	private $site_id;

	/**
	 * @var string
	 */
	private $search_input_id;

	/**
	 * @var Mlp_Updatable
	 */
	private $updater;

	/**
	 * @param Mlp_Relationship_Control_Data $data
	 * @param Mlp_Updatable                 $updater
	 */
	public function __construct( Mlp_Relationship_Control_Data $data, Mlp_Updatable $updater ) {

		global $hook_suffix;

		$this->data = $data;

		$this->updater = $updater;

		$this->post = $data->get_source_post();

		$this->remote_post_id = $data->get_remote_post_id();

		$this->remote_site_id = $data->get_remote_site_id();

		$this->search_input_id = "mlp_post_search_$this->remote_site_id";

		$this->site_id = get_current_blog_id();

		add_action( "admin_footer-$hook_suffix", array( $this, 'print_jquery' ) );
	}

	public function render() {

		$action_selector_id = "mlp_rsc_action_container_$this->remote_site_id";

		$search_selector_id = "mlp_rsc_search_container_$this->remote_site_id";

		$actions = array(
			'stay' => esc_html__( 'Leave as is', 'multilingualpress' ),
			'new'  => esc_html__( 'Create new post', 'multilingualpress' ),
		);

		if ( $this->remote_post_id ) {
			$actions[ 'disconnect' ] = esc_html__( 'Remove relationship', 'multilingualpress' );
		}
		?>
		<div class="mlp-relationship-control-box" style="margin: .5em 0 .5em auto">
			<?php
			printf(
				'<button type="button" class="button secondary mlp-rsc-button" name="mlp_rsc_%2$d"
					data-toggle_selector="#%3$s" data-search_box_id="%4$s">%1$s</button>',
				esc_html__( 'Change relationship', 'multilingualpress' ),
				$this->remote_site_id,
				$action_selector_id,
				$search_selector_id
			);
			?>
			<div id="<?php echo $action_selector_id; ?>" class="hidden">
				<div class="mlp_rsc_action_list" style="float: left; width: 20em">
					<?php foreach ( $actions as $key => $label ) : ?>
						<p>
							<?php
							$this->print_radio(
								$key,
								$label,
								'stay',
								'mlp_rsc_action[' . $this->remote_site_id . ']',
								'mlp_rsc_input_id_' . $this->remote_site_id
							);
							?>
						</p>
					<?php endforeach; ?>
					<p>
						<label for="mlp_rsc_input_id_<?php echo $this->remote_site_id; ?>_search" class="mlp_toggler">
							<input
								type="radio"
								name="mlp_rsc_action[<?php echo $this->remote_site_id; ?>]"
								value="search"
								id="mlp_rsc_input_id_<?php echo $this->remote_site_id; ?>_search"
								data-toggle_selector="#<?php echo $search_selector_id; ?>">
							<?php esc_html_e( 'Select existing post &hellip;', 'multilingualpress' ); ?>
						</label>
					</p>
				</div>
				<div id="<?php echo $search_selector_id; ?>" style="display: none; float: left; max-width: 30em">
					<label for="<?php echo $this->search_input_id; ?>">
						<?php esc_html_e( 'Live search', 'multilingualpress' ); ?>
					</label>
					<?php echo $this->get_search_input( $this->search_input_id ); ?>
					<ul class="mlp_search_results" id="mlp_search_results_<?php echo $this->remote_site_id; ?>">
						<?php $this->updater->update( 'default.remote.posts' ); ?>
					</ul>
				</div>
				<p class="clear">
					<input type="submit" <?php echo $this->get_id_values(); ?>
						class="button button-primary mlp_rsc_save_reload"
						value="<?php esc_attr_e( 'Save and reload this page', 'multilingualpress' ); ?>">
					<span class="description">
						<?php esc_html_e( 'Please save other changes first separately.', 'multilingualpress' ); ?>
					</span>
				</p>
			</div>
		</div>
		<?php
	}

	/**
	 * @param string $key
	 * @param string $label
	 * @param string $selected
	 * @param string $name
	 * @param string $id_base
	 *
	 * @return void
	 */
	private function print_radio(
		$key,
		$label,
		$selected,
		$name,
		$id_base
	) {

		printf(
			'<label for="%5$s_%1$s">
				<input type="radio" name="%4$s" id="%5$s_%1$s" value="%1$s"%3$s>
				%2$s
			</label>',
			$key,
			$label,
			selected( $name, $selected, FALSE ),
			$name,
			$id_base
		);
	}

	/**
	 * @param string $id
	 *
	 * @return string
	 */
	private function get_search_input( $id ) {

		return '<input type="search" class="mlp_search_field" id="' . $id . '"' . $this->get_id_values() . '>';
	}

	/**
	 * Return the data attributes as string.
	 *
	 * @return string
	 */
	private function get_id_values() {

		$str = '';

		$data = array(
			'source_post_id' => $this->post->ID,
			'source_site_id' => $this->site_id,
			'remote_site_id' => $this->remote_site_id,
			'remote_post_id' => $this->remote_post_id,
		);

		foreach ( $data as $key => $value ) {
			$str .= " data-$key='$value'";
		}

		return $str;
	}

	public function print_jquery() {

		echo <<<JQUERY
<script>
	jQuery( '.mlp_search_field' ).mlp_search( {
		action          : 'mlp_rsc_search',
		remote_site_id  : {$this->remote_site_id},
		result_container: '#mlp_search_results_{$this->remote_site_id}',
		search_field    : '#{$this->search_input_id}'
	} );
</script>
JQUERY;
	}

}
