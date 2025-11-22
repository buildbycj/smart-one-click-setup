<?php
/**
 * Static functions used in the SOCS plugin views.
 *
 * @package socs
 */

namespace SOCS;

class ViewHelpers {
	/**
	 * The HTML output of the plugin page header.
	 *
	 * @return string HTML output.
	 */
	public static function plugin_header_output() {
		ob_start(); ?>
		<div class="socs__title-container">
			<h1 class="socs__title-container-title"><?php esc_html_e( 'Smart One Click Setup', 'smart-one-click-setup' ); ?></h1>
			<a href="https://socs.buildbycj.com/#user-guide" target="_blank" rel="noopener noreferrer">
				<img class="socs__title-container-icon" src="<?php echo esc_url( SOCS_URL . 'assets/images/icons/question-circle.svg' ); ?>" alt="<?php esc_attr_e( 'Questionmark icon', 'smart-one-click-setup' ); ?>">
			</a>
		</div>
		<?php
		$plugin_title = ob_get_clean();

		// Display the plugin title (can be replaced with custom title text through the filter below).
		return Helpers::apply_filters( 'socs/plugin_page_title', $plugin_title );
	}

	/**
	 * The HTML output of a small card with theme screenshot and title.
	 *
	 * @return string HTML output.
	 */
	public static function small_theme_card( $selected = null ) {
		$theme      = wp_get_theme();
		$screenshot = $theme->get_screenshot();
		$name       = $theme->name;
		$author     = $theme->get( 'Author' );
		$author_uri = $theme->get( 'AuthorURI' );
		$version    = $theme->get( 'Version' );
		$description = $theme->get( 'Description' );

		if ( isset( $selected ) ) {
			$socs          = SmartOneClickSetup::get_instance();
			$selected_data = $socs->import_files[ $selected ];
			$name          = ! empty( $selected_data['import_file_name'] ) ? $selected_data['import_file_name'] : $name;
			$screenshot    = ! empty( $selected_data['import_preview_image_url'] ) ? $selected_data['import_preview_image_url'] : $screenshot;
		}

		ob_start(); ?>
		<div class="socs__card socs__card--theme">
			<div class="socs__card-content">
				<?php if ( $screenshot ) : ?>
					<div class="screenshot"><img src="<?php echo esc_url( $screenshot ); ?>" alt="<?php esc_attr_e( 'Theme screenshot', 'smart-one-click-setup' ); ?>" /></div>
				<?php else : ?>
					<div class="screenshot blank"></div>
				<?php endif; ?>
			</div>
			<div class="socs__card-footer">
				<div class="socs__theme-title-version">
					<h3 class="socs__theme-name"><?php echo esc_html( $name ); ?></h3>
					<?php if ( ! empty( $version ) ) : ?>
						<span class="socs__theme-separator">---</span>
						<span class="socs__theme-version">
							<?php
							/* translators: %s: Theme version number. */
							printf( esc_html__( 'Version %s', 'smart-one-click-setup' ), esc_html( $version ) );
							?>
						</span>
					<?php endif; ?>
				</div>
				<?php if ( ! empty( $author ) ) : ?>
					<div class="socs__theme-author">
						<?php if ( ! empty( $author_uri ) ) : ?>
							<a href="<?php echo esc_url( $author_uri ); ?>" target="_blank" rel="noopener noreferrer">
								<?php echo esc_html( $author ); ?>
							</a>
						<?php else : ?>
							<?php echo esc_html( $author ); ?>
						<?php endif; ?>
					</div>
				<?php endif; ?>
				<?php if ( ! empty( $description ) ) : ?>
					<div class="socs__theme-description">
						<?php echo esc_html( $description ); ?>
					</div>
				<?php endif; ?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}
}
