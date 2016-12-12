<?php # -*- coding: utf-8 -*-

namespace Inpsyde\MultilingualPress\Relations\Post;

use Inpsyde\MultilingualPress\Asset\AssetManager;
use Inpsyde\MultilingualPress\Relations\Post\Search\Search;
use Inpsyde\MultilingualPress\Relations\Post\Search\SearchController;
use Inpsyde\MultilingualPress\Relations\Post\Search\SearchResultsView;

/**
 * Relationship control view to be used within the Translation meta box.
 *
 * @package Inpsyde\MultilingualPress\Relations\Post
 * @since   3.0.0
 */
class RelationshipControlView {

	/**
	 * @var bool
	 */
	private static $is_script_localized = false;

	/**
	 * @var AssetManager
	 */
	private $asset_manager;

	/**
	 * @var string
	 */
	private $default_action = 'stay';

	/**
	 * @var SearchResultsView
	 */
	private $search_results;

	/**
	 * Constructor. Sets up the properties.
	 *
	 * @since 3.0.0
	 *
	 * @param SearchResultsView $search_results Search results view object.
	 * @param AssetManager      $asset_manager  Asset manager object.
	 */
	public function __construct( SearchResultsView $search_results, AssetManager $asset_manager ) {

		$this->search_results = $search_results;

		$this->asset_manager = $asset_manager;
	}

	/**
	 * Renders the markup.
	 *
	 * @since   3.0.0
	 * @wp-hook TODO: Reference (to-be-defined) class constant of Translation meta box.
	 *
	 * @param RelationshipContext $context Relationship context data object.
	 *
	 * @return void
	 */
	public function render( RelationshipContext $context ) {

		$remote_post_id = $context->remote_post_id();

		$remote_site_id = $context->remote_site_id();

		$action_container_id = "mlp-rc-action-container-{$remote_site_id}";

		$actions = [
			$this->default_action                      => __( 'Leave as is', 'multilingual-press' ),
			RelationshipController::ACTION_CONNECT_NEW => __( 'Create new post', 'multilingual-press' ),
		];
		if ( $remote_post_id ) {
			$actions[ RelationshipController::ACTION_DISCONNECT ] = __( 'Remove relationship', 'multilingual-press' );
		}

		$action_search_id = "mlp-rc-action-{$remote_site_id}-search";

		$search_container_id = "mlp-rc-search-container-{$remote_site_id}";

		$search_input_id = "mlp-rc-search-{$remote_site_id}";
		?>
		<div class="mlp-relationship-control" data-remote-post-id="<?php echo esc_attr( $remote_post_id ); ?>"
			data-remote-site-id="<?php echo esc_attr( $remote_site_id ); ?>"
			data-results-selector="#mlp-rc-search-results-<?php echo esc_attr( $remote_site_id ); ?>"
			data-source-post-id="<?php echo esc_attr( $context->source_post_id() ); ?>"
			data-source-site-id="<?php echo esc_attr( $context->source_site_id() ); ?>">
			<button type="button" name="mlp_rc_<?php echo esc_attr( $remote_site_id ); ?>"
				class="button secondary mlp-rc-button mlp-click-toggler"
				data-toggle-target="#<?php echo esc_attr( $action_container_id ); ?>">
				<?php esc_html_e( 'Change Relationship', 'multilingual-press' ); ?>
			</button>
			<div id="<?php echo esc_attr( $action_container_id ); ?>" class='hidden'>
				<div class="mlp-rc-settings">
					<div class="mlp-rc-actions">
						<?php array_walk( $actions, [ $this, 'render_radio_input' ], $remote_site_id ); ?>
						<p>
							<label for="<?php echo esc_attr( $action_search_id ); ?>">
								<input
									type="radio"
									name="mlp_rc_action[<?php echo esc_attr( $remote_site_id ); ?>]"
									value="<?php echo esc_attr( RelationshipController::ACTION_CONNECT_EXISTING ); ?>"
									id="<?php echo esc_attr( $action_search_id ); ?>"
									class="mlp-state-toggler"
									data-toggle-target="#<?php echo esc_attr( $search_container_id ); ?>">
								<?php esc_html_e( 'Select existing post &hellip;', 'multilingual-press' ) ?>
							</label>
						</p>
					</div>
					<div id="<?php echo esc_attr( $search_container_id ); ?>" class="mlp-rc-search-container">
						<label for="<?php echo esc_attr( $search_input_id ); ?>">
							<?php esc_html_e( 'Live search', 'multilingual-press' ); ?>
						</label>
						<input type="search" id="<?php echo esc_attr( $search_input_id ); ?>" class="mlp-rc-search">
						<ul id="mlp-rc-search-results-<?php echo esc_attr( $remote_site_id ); ?>"
							class="mlp-rc-search-results">
							<?php $this->search_results->render( $context ); ?>
						</ul>
					</div>
				</div>
				<p>
					<button type="button" class="button button-primary mlp-save-relationship-button">
						<?php esc_attr_e( 'Save and reload this page', 'multilingual-press' ); ?>
					</button>
					<span class="description">
						<?php esc_html_e( 'Please save other changes first separately.', 'multilingual-press' ); ?>
					</span>
				</p>
			</div>
		</div>
		<?php
		$this->localize_script();
	}

	/**
	 * Makes the relationship control settings available for JavaScript.
	 *
	 * @return void
	 */
	private function localize_script() {

		if ( self::$is_script_localized ) {
			return;
		}

		$this->asset_manager->add_script_data( 'multilingualpress-admin', 'mlpRelationshipControlSettings', [
			'actionConnectExisting' => RelationshipController::ACTION_CONNECT_EXISTING,
			'actionConnectNew'      => RelationshipController::ACTION_CONNECT_NEW,
			'actionDisconnect'      => RelationshipController::ACTION_DISCONNECT,
			'l10n'                  => [
				'noPostSelected'       => __( 'Please select a post.', 'multilingual-press' ),
				'unsavedRelationships' => __(
					'You have unsaved changes in your post relationships. The changes you made will be lost if you navigate away from this page.',
					'multilingual-press'
				),
			],
		] );

		/**
		 * Filters the minimum number of characters required to fire the live search.
		 *
		 * @param int $threshold Minimum number of characters required to fire the live search.
		 */
		$threshold = (int) apply_filters( 'multilingualpress.relationship_control_search_threshold', 3 );

		$this->asset_manager->add_script_data( 'multilingualpress-admin', 'mlpLiveSearchSettings', [
			'action'    => SearchController::ACTION,
			'argName'   => Search::ARG_NAME,
			'threshold' => max( 1, $threshold ),
		] );

		self::$is_script_localized = true;
	}

	/**
	 * Renders the radio input markup according to the given data.
	 *
	 * @param string $label          Label text.
	 * @param string $value          Input value.
	 * @param int    $remote_site_id Remote site ID.
	 *
	 * @return void
	 */
	private function render_radio_input( $label, $value, $remote_site_id ) {

		$id = "mlp-rc-action-{$remote_site_id}-{$value}";
		?>
		<p>
			<label for="<?php echo esc_attr( $id ); ?>">
				<input type="radio" name="mlp_rc_action[<?php echo esc_attr( $remote_site_id ); ?>]"
					value="<?php echo esc_attr( $value ); ?>" id="<?php echo esc_attr( $id ); ?>"
					<?php selected( $value, $this->default_action ); ?>>
				<?php echo esc_html( $label ); ?>
			</label>
		</p>
		<?php
	}
}
