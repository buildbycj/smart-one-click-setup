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
				<h3><?php echo esc_html( $name ); ?></h3>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}
}
