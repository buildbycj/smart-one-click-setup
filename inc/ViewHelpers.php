<?php
namespace SMARTOCS;

/**
 * Static functions used in the SMARTOCS plugin views.
 *
 * @package smartocs
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ViewHelpers {
	/**
	 * The HTML output of the plugin page header.
	 *
	 * @return string HTML output.
	 */
	public static function plugin_header_output() {
		$plugin_title = '<div class="smartocs__title-container">
			<h1 class="smartocs__title-container-title">' . esc_html__( 'Smart One Click Setup', 'smart-one-click-setup' ) . '</h1>
				<a href="https://smartocs.buildbycj.com/#user-guide" target="_blank" rel="noopener noreferrer">
				<img class="smartocs__title-container-icon" src="' . esc_url( SMARTOCS_URL . 'assets/images/icons/question-circle.svg' ) . '" alt="' . esc_attr__( 'Questionmark icon', 'smart-one-click-setup' ) . '">
				</a>
		</div>';

		// Display the plugin title (can be replaced with custom title text through the filter below).
		return Helpers::apply_filters( 'smartocs/plugin_page_title', $plugin_title );
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
			$smartocs          = SmartOneClickSetup::get_instance();
			$selected_data = $smartocs->import_files[ $selected ];
			$name          = ! empty( $selected_data['import_file_name'] ) ? $selected_data['import_file_name'] : $name;
			$screenshot    = ! empty( $selected_data['import_preview_image_url'] ) ? $selected_data['import_preview_image_url'] : $screenshot;
		}

		$screenshot_html = $screenshot 
			? '<div class="screenshot"><img src="' . esc_url( $screenshot ) . '" alt="' . esc_attr__( 'Theme screenshot', 'smart-one-click-setup' ) . '" /></div>'
			: '<div class="screenshot blank"></div>';

		$version_html = '';
		if ( ! empty( $version ) ) {
			$version_html = '<span class="smartocs__theme-separator">---</span>
				<span class="smartocs__theme-version">';
			/* translators: %s: Theme version number. */
			$version_html .= esc_html__( 'Version %s', 'smart-one-click-setup' );
			$version_html .= '</span>';
			$version_html = sprintf( $version_html, esc_html( $version ) );
		}

		$author_html = '';
		if ( ! empty( $author ) ) {
			if ( ! empty( $author_uri ) ) {
				$author_html = '<div class="smartocs__theme-author"><a href="' . esc_url( $author_uri ) . '" target="_blank" rel="noopener noreferrer">' . esc_html( $author ) . '</a></div>';
			} else {
				$author_html = '<div class="smartocs__theme-author">' . esc_html( $author ) . '</div>';
			}
		}

		$description_html = ! empty( $description ) 
			? '<div class="smartocs__theme-description">' . esc_html( $description ) . '</div>'
			: '';

		$output = '<div class="smartocs__card smartocs__card--theme">
			<div class="smartocs__card-content">' . $screenshot_html . '</div>
				<div class="smartocs__card-footer">
					<div class="smartocs__theme-title-version">
					<h3 class="smartocs__theme-name">' . esc_html( $name ) . '</h3>' . $version_html . '
				</div>' . $author_html . $description_html . '
			</div>
		</div>';

		return $output;
	}
}
