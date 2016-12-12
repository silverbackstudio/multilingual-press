<?php # -*- coding: utf-8 -*-

use Inpsyde\MultilingualPress\API\Languages;
use Inpsyde\MultilingualPress\API\SiteRelations;
use Inpsyde\MultilingualPress\Common\Nonce\Nonce;
use Inpsyde\MultilingualPress\Common\Type\Setting;

/**
 * Content of the per-site settings tab
 *
 * @version 2014.07.04
 * @author  Inpsyde GmbH, toscho
 * @license GPL
 */
class Mlp_Network_Site_Settings_Tab_Content {

	/**
	 * @var int
	 */
	private $blog_id;

	/**
	 * @var Languages
	 */
	private $languages;

	/**
	 * @var Nonce
	 */
	private $nonce;

	/**
	 * @var SiteRelations
	 */
	private $relations;

	/**
	 * @var Setting
	 */
	private $setting;

	/**
	 * Constructor. Set up the properties.
	 *
	 * @param Languages     $languages Languages API object.
	 * @param Setting       $setting   Options page data.
	 * @param int           $blog_id   Blog ID
	 * @param SiteRelations $relations Site relations.
	 * @param Nonce         $nonce     Nonce object.
	 */
	public function __construct(
		Languages $languages,
		Setting $setting,
		$blog_id,
		SiteRelations $relations,
		Nonce $nonce
	) {

		$this->languages = $languages;

		$this->setting = $setting;

		$this->blog_id = $blog_id;

		$this->relations = $relations;

		$this->nonce = $nonce;
	}

	/**
	 * Print tab content and provide two hooks.
	 *
	 * @return void
	 */
	public function render_content() {

		?>
		<form action="<?php echo $this->setting->url(); ?>" method="post">
			<input type="hidden" name="action" value="<?php echo esc_attr( $this->setting->action() ); ?>" />
			<input type="hidden" name="id" value="<?php echo esc_attr( $this->blog_id ); ?>" />
			<?php
			echo \Inpsyde\MultilingualPress\nonce_field( $this->nonce );

			$siteoption = get_site_option( 'inpsyde_multilingual', [] );
			echo '<table class="form-table mlp-admin-settings-table">';
			$this->show_language_options( $siteoption, $this->languages->get_all_languages() );
			$this->show_blog_relationships( $siteoption );

			/**
			 * Runs at the end of but still inside the site settings table.
			 *
			 * @param int $blog_id Blog ID.
			 */
			do_action( 'mlp_blogs_add_fields', $this->blog_id );

			if ( has_action( 'mlp_blogs_add_fields_secondary' ) ) {
				_doing_it_wrong(
					'mlp_blogs_add_fields_secondary',
					'mlp_blogs_add_fields_secondary is deprecated, use mlp_blogs_add_fields instead.',
					'2.1'
				);
			}
			/**
			 * @see mlp_blogs_add_fields
			 * @deprecated
			 */
			do_action( 'mlp_blogs_add_fields_secondary', $this->blog_id );

			echo '</table>';

			submit_button();
			?>
		</form>
		<?php
	}

	/**
	 * @param  array $site_option
	 * @param  object[] $languages
	 * @return void
	 */
	private function show_language_options( $site_option, $languages ) {

		// Custom names are now set in the Language Manager
		//$lang_title = isset( $siteoption[ $this->blog_id ][ 'text' ] ) ? stripslashes( $siteoption[ $this->blog_id ][ 'text' ] ) : '';
		$selected        = isset( $site_option[ $this->blog_id ][ 'lang' ] ) ? $site_option[ $this->blog_id ][ 'lang' ]	: '';
		$blogoption_flag = get_blog_option( $this->blog_id, 'inpsyde_multilingual_flag_url' );

		// Sanitize lang title
		$lang_title = isset( $site_option[ $this->blog_id ][ 'text' ] ) ? stripslashes( $site_option[ $this->blog_id ][ 'text' ] ) : '';
		?>
		<tr class="form-field">
			<th scope="row">
				<label for="inpsyde_multilingual_lang">
					<?php
					esc_html_e( 'Language', 'multilingual-press' );
					?>
				</label>
			</th>
			<td>
				<select name="inpsyde_multilingual_lang" id="inpsyde_multilingual_lang" autocomplete="off">
					<option value="-1"><?php esc_html_e( 'choose language', 'multilingual-press' ); ?></option>
					<?php
					foreach ( $languages as $language ) {

						$language_code = str_replace( '-', '_', $language->http_name );

						// missing HTTP code
						if ( empty ( $language_code ) ) {
							continue;
						}

						$language_name = esc_html( $this->get_language_name( $language ) );
						$select        = selected( $selected, $language_code, FALSE );
						echo '<option value="' . esc_attr( $language_code ) . '" ' . $select . '>' . esc_html( $language_name ) . '</option>';
					}
					?>
				</select>
			</td>
		</tr>
		<tr class="form-field">
			<th scope="row">
				<label for="inpsyde_multilingual_text">
					<?php
					esc_html_e( 'Alternative language title', 'multilingual-press' );
					?>
				</label>
			</th>
			<td>
				<input class="regular-text" type="text" id="inpsyde_multilingual_text" name="inpsyde_multilingual_text"
					value="<?php echo esc_attr( $lang_title ); ?>" />
				<p class="description">
					<?php esc_html_e( 'Enter a title here that you want to be displayed in the frontend instead of the default one (i.e. "My English Site")',
					                  'multilingual-press' ); ?>
				</p>
			</td>
		</tr>
		<tr class="form-field">
			<th scope="row">
				<label for="inpsyde_multilingual_flag_url">
					<?php
					esc_html_e( 'Flag image URL', 'multilingual-press' );
					?>
				</label>
			</th>
			<td>
				<input
					class="regular-text"
					type="url"
					id="inpsyde_multilingual_flag_url"
					name="inpsyde_multilingual_flag_url"
					value="<?php echo esc_url( $blogoption_flag ); ?>"
					placeholder="http://example.com/flag.png"
				/>
			</td>
		</tr>
		<?php
	}

	/**
	 * @param object $language
	 * @return string
	 */
	private function get_language_name( $language ) {

		$parts = [];

		if ( ! empty ( $language->english_name ) )
			$parts[] = $language->english_name;

		if ( ! empty ( $language->native_name ) )
			$parts[] = $language->native_name;

		$parts = array_unique( $parts );

		return join( '/', $parts );
	}

	/**
	 * @param array $site_option
	 * @return void
	 */
	private function show_blog_relationships( $site_option ) {

		if ( ! is_array( $site_option ) ) {
			return;
		}

		unset ( $site_option[ $this->blog_id ] );

		if ( empty ( $site_option ) ) {
			return;
		}

		?>
		<tr class="form-field">
			<th scope="row"><?php esc_html_e( 'Relationships', 'multilingual-press' ); ?></th>
			<td>
				<?php
				foreach ( $site_option as $blog_id => $meta ) {

					$blog_id = (int) $blog_id;
					// Get blog display name
					switch_to_blog( $blog_id );
					$blog_name = get_bloginfo( 'Name' );
					restore_current_blog();

					// Get current settings
					$related_blogs = $this->relations->get_related_site_ids( $this->blog_id );
					$checked       = checked( TRUE, in_array( $blog_id, $related_blogs ), FALSE );
					$id            = 'related_blog_' . $blog_id;
					?>
					<p>
						<label for="<?php echo esc_attr( $id ); ?>">
							<input id="<?php echo esc_attr( $id ); ?>" <?php echo esc_attr( $checked ); ?>
								type="checkbox" name="related_blogs[]" value="<?php echo esc_attr( $blog_id ) ?>" />
							<?php echo esc_html( $blog_name ); ?>
							-
							<?php echo esc_html( \Inpsyde\MultilingualPress\get_site_language( $blog_id, false ) ); ?>
						</label>
					</p>
					<?php
				}
				?>
				<p class="description">
					<?php
					esc_html_e(
						'You can connect this site only to sites with an assigned language. Other sites will not show up here.',
						'multilingual-press'
					);
					?>
				</p>
			</td>
		</tr>
		<?php
	}

}
