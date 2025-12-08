<?php
/**
 * Template functions for theme developers.
 *
 * @package smartocs
 */

namespace SMARTOCS;

/**
 * Template function to display the Smart Import box in themes.
 *
 * This function can be called from any theme template to display
 * the Smart Import interface. It handles all necessary setup including
 * script/style enqueuing and permission checks.
 *
 * @since 1.0.0
 *
 * @param array $args {
 *     Optional. Array of arguments.
 *
 *     @type bool   $echo                    Whether to echo the output or return it. Default true.
 *     @type string $wrapper_class           Additional CSS class for the wrapper. Default empty.
 *     @type bool   $show_header             Whether to show the plugin header. Default true.
 *     @type bool   $show_sidebar            Whether to show the theme card sidebar. Default true.
 *     @type bool   $load_plugin_css         Whether to load plugin CSS. Default true.
 *     @type bool   $show_smart_import_tabs  Whether to show the smart import tabs. Default null (uses filter default).
 *     @type bool   $show_file_upload_header Whether to show the file upload container header. Default true.
 *     @type bool   $show_intro_text         Whether to show the intro text section. Default true.
 * }
 * @return string|void HTML output if $echo is false, void otherwise.
 */
function smartocs_display_smart_import( $args = array() ) {
	// Check if plugin is active.
	if ( ! class_exists( 'SMARTOCS\SmartOneClickSetup' ) ) {
		if ( current_user_can( 'manage_options' ) ) {
			echo '<div class="smartocs-notice notice notice-error"><p>';
			esc_html_e( 'Smart One Click Setup plugin is not active.', 'smart-one-click-setup' );
			echo '</p></div>';
		}
		return;
	}

	// Check user capabilities.
	$plugin_page_setup = Helpers::get_plugin_page_setup_data();
	if ( ! current_user_can( $plugin_page_setup['capability'] ) ) {
		return;
	}

	// Parse arguments.
	$defaults = array(
		'echo'                    => true,
		'wrapper_class'           => '',
		'show_header'             => true,
		'show_sidebar'             => true,
		'load_plugin_css'          => true, // Allow themes to disable plugin CSS.
		'show_smart_import_tabs'   => null, // null means use filter default, true/false to override.
		'show_file_upload_header'  => true, // Show the file upload container header.
		'show_intro_text'          => true, // Show the intro text section.
	);

	$args = wp_parse_args( $args, $defaults );

	// Allow filtering of arguments.
	$args = Helpers::apply_filters( 'smartocs/template_smart_import_args', $args );

	// Get plugin instance.
	$smartocs = SmartOneClickSetup::get_instance();

	// Enqueue scripts and styles (with option to skip CSS).
	$smartocs->enqueue_template_scripts( $args['load_plugin_css'] );

	// Build output as string (WordPress.org compatible - no output buffering).
	$output = '';

	try {
		// Wrapper classes.
		$wrapper_classes = array( 'smartocs', 'smartocs--smart-import', 'smartocs--theme-integration' );
		if ( ! empty( $args['wrapper_class'] ) ) {
			$wrapper_classes[] = sanitize_html_class( $args['wrapper_class'] );
		}

		// Allow themes to add custom wrapper classes.
		$wrapper_classes = Helpers::apply_filters( 'smartocs/template_smart_import_wrapper_classes', $wrapper_classes, $args );

		$output .= '<div class="' . esc_attr( implode( ' ', array_map( 'sanitize_html_class', $wrapper_classes ) ) ) . '" data-smartocs-theme-integration="true">';

		if ( $args['show_header'] ) {
			$output .= wp_kses_post( ViewHelpers::plugin_header_output() );
		}

		// Display warning if PHP safe mode is enabled.
		if ( ini_get( 'safe_mode' ) ) {
			$output .= sprintf(
				/* translators: %1$s - the opening div and paragraph HTML tags, %2$s and %3$s - strong HTML tags, %4$s - the closing div and paragraph HTML tags. */
				esc_html__( '%1$sWarning: your server is using %2$sPHP safe mode%3$s. This means that you might experience server timeout errors.%4$s', 'smart-one-click-setup' ),
				'<div class="notice  notice-warning  is-dismissible"><p>',
				'<strong>',
				'</strong>',
				'</p></div>'
			);
		}

		$output .= '<div class="smartocs__content-container">';
		$output .= '<div class="smartocs__admin-notices js-smartocs-admin-notices-container"></div>';

		$show_intro_text = Helpers::apply_filters( 'smartocs/show_intro_text', $args['show_intro_text'] );
		if ( $show_intro_text ) {
			// Build plugin intro text.
			$default_description = esc_html__( 'Import demo data from a ZIP file exported using Smart Export, or use predefined import configurations set up by your theme developer.', 'smart-one-click-setup' ) . ' ' . esc_html__( 'This will import your content, widgets, customizer settings, and more.', 'smart-one-click-setup' );
			$intro_description = Helpers::apply_filters( 'smartocs/intro_description_text', $default_description );
			
			$export_link_html = '';
			// Check if the export link should be shown.
			$show_export_link = Helpers::apply_filters( 'smartocs/show_export_link', true );
			if ( $show_export_link ) {
				// Get the export page URL.
				$plugin_page_setup = Helpers::get_plugin_page_setup_data();
				$export_page_url = menu_page_url( 'smartocs-smart-export', false );
				if ( empty( $export_page_url ) ) {
					$export_page_url = add_query_arg(
						array( 'page' => 'smartocs-smart-export' ),
						admin_url( $plugin_page_setup['parent_slug'] )
					);
				}
				$export_link_html = '<p class="smartocs-intro-actions">
					<a href="' . esc_url( $export_page_url ) . '" class="button button-secondary">' . esc_html__( 'Go to Smart Export', 'smart-one-click-setup' ) . '</a>
				</p>';
			}
			
			$plugin_intro_text = '<div class="smartocs__intro-text">
				<p class="about-description">' . wp_kses_post( $intro_description ) . '</p>' . $export_link_html . '
			</div>';

			// Display the plugin intro text (can be replaced with custom text through the filter below).
			$output .= wp_kses_post( Helpers::apply_filters( 'smartocs/plugin_intro_text', $plugin_intro_text ) );
		}

		$theme = wp_get_theme();

		$output .= '<div class="smartocs__content-container-content">';
		$output .= '<div class="smartocs__content-container-content--main">';
		$output .= '<div class="smartocs-smart-import-content js-smartocs-smart-import-content">';
		$output .= '<div class="smartocs__file-upload-container">';

		$show_file_upload_header = Helpers::apply_filters( 'smartocs/show_file_upload_header', $args['show_file_upload_header'] );
		if ( $show_file_upload_header ) {
			$output .= '<div class="smartocs__file-upload-container--header">';
			$output .= '<h2>' . esc_html__( 'Smart Import', 'smart-one-click-setup' ) . '</h2>';
			$output .= '</div>';
		}

		$predefined_imports = Helpers::apply_filters( 'smartocs/predefined_import_files', array() );
		$has_predefined_imports = ! empty( $predefined_imports );
		// Use argument value if provided, otherwise use filter default.
		$tabs_default = null !== $args['show_smart_import_tabs'] ? $args['show_smart_import_tabs'] : $has_predefined_imports;
		$show_tabs = Helpers::apply_filters( 'smartocs/show_smart_import_tabs', $tabs_default );

		if ( $has_predefined_imports && $show_tabs ) {
			$output .= '<div class="smartocs-smart-import-tabs">';
			$output .= '<button type="button" class="smartocs-smart-import-tab active button" data-tab="predefined">';
			$output .= esc_html__( 'Predefined Import', 'smart-one-click-setup' );
			$output .= '</button>';
			$output .= '<button type="button" class="smartocs-smart-import-tab button" data-tab="upload">';
			$output .= esc_html__( 'Upload ZIP File', 'smart-one-click-setup' );
			$output .= '</button>';
			$output .= '</div>';
		}

		if ( $has_predefined_imports ) {
			$output .= '<div class="smartocs-smart-import-tab-content active" data-tab-content="predefined">';
			if ( ! empty( $predefined_imports ) ) {
				$output .= '<div class="smartocs__gl js-smartocs-gl">';
				$output .= '<div class="smartocs__gl-item-container js-smartocs-gl-item-container">';
				foreach ( $predefined_imports as $index => $import ) {
					/* translators: %d: The demo import number. */
					$import_name = ! empty( $import['name'] ) ? $import['name'] : sprintf( esc_html__( 'Demo Import %d', 'smart-one-click-setup' ), $index + 1 );
					$import_description = ! empty( $import['description'] ) ? $import['description'] : '';
					$import_preview = ! empty( $import['preview_image'] ) ? $import['preview_image'] : '';
					$import_preview_url = ! empty( $import['preview_url'] ) ? $import['preview_url'] : '';
					$has_zip = ! empty( $import['zip_url'] ) || ( ! empty( $import['zip_path'] ) && file_exists( $import['zip_path'] ) );

					// Default to theme screenshot if no preview image.
					if ( empty( $import_preview ) ) {
						$import_preview = $theme->get_screenshot();
					}

					$output .= '<div class="smartocs__gl-item js-smartocs-gl-item">';
					$output .= '<div class="smartocs__gl-item-image-container">';
					if ( ! empty( $import_preview ) ) {
						$output .= '<img class="smartocs__gl-item-image" src="' . esc_url( $import_preview ) . '" alt="' . esc_attr( $import_name ) . '" loading="lazy">';
					} else {
						$output .= '<div class="smartocs__gl-item-image smartocs__gl-item-image--no-image">' . esc_html__( 'No preview image.', 'smart-one-click-setup' ) . '</div>';
					}
					$output .= '</div>';
					$output .= '<div class="smartocs__gl-item-footer' . esc_attr( ! empty( $import_preview_url ) ? ' smartocs__gl-item-footer--with-preview' : '' ) . '">';
					$output .= '<h4 class="smartocs__gl-item-title" title="' . esc_attr( $import_name ) . '">' . esc_html( $import_name ) . '</h4>';
					if ( ! empty( $import_description ) ) {
						$output .= '<p class="smartocs-smart-import-description">' . esc_html( $import_description ) . '</p>';
					}
					$output .= '<span class="smartocs__gl-item-buttons">';
					if ( ! empty( $import_preview_url ) ) {
						$output .= '<a class="smartocs__gl-item-button button js-smartocs-preview-demo" href="' . esc_url( $import_preview_url ) . '" target="_blank" rel="noopener noreferrer">';
						$output .= esc_html__( 'Preview Demo', 'smart-one-click-setup' );
						$output .= '</a>';
					}
					if ( $has_zip ) {
						$output .= '<button class="smartocs__gl-item-button button button-primary js-smartocs-use-predefined-import" data-import-index="' . esc_attr( $index ) . '">';
						$output .= esc_html__( 'Import Demo', 'smart-one-click-setup' );
						$output .= '</button>';
					} else {
						$output .= '<span class="notice notice-error inline smartocs-smart-import-error-notice">';
						$output .= esc_html__( 'ZIP file URL or path is missing.', 'smart-one-click-setup' );
						$output .= '</span>';
					}
					$output .= '</span>';
					$output .= '</div>';
					$output .= '</div>';
				}
				$output .= '</div>';
				$output .= '</div>';
			}
			$output .= '</div>';
		}

		$output .= '<div class="smartocs-smart-import-tab-content' . esc_attr( $has_predefined_imports ? '' : ' active' ) . '" data-tab-content="upload">';
		$output .= '<form id="smartocs-smart-import-form" class="smartocs-smart-import-form">';
		$output .= '<div class="smartocs__file-upload-container-items">';
		$output .= '<div class="smartocs__file-upload smartocs__card smartocs__card--full">';
		$output .= '<div class="smartocs__card-content">';
		$output .= '<label for="smartocs__zip-file-upload">';
		$output .= '<div class="smartocs-icon-container">';
		$output .= '<img src="' . esc_url( SMARTOCS_URL . 'assets/images/icons/copy.svg' ) . '" class="smartocs-icon--copy" alt="' . esc_attr__( 'Upload icon', 'smart-one-click-setup' ) . '">';
		$output .= '</div>';
		$output .= '<h3>' . esc_html__( 'Upload Export ZIP File', 'smart-one-click-setup' ) . '</h3>';
		$output .= '<p>' . esc_html__( 'Select a ZIP file exported from Smart Export.', 'smart-one-click-setup' ) . '</p>';
		$output .= '<span class="smartocs-smart-import-file-name js-smartocs-smart-import-file-name"></span>';
		$output .= '</label>';
		$output .= '<a href="https://smartocs.buildbycj.com/#user-guide" target="_blank" rel="noopener noreferrer" class="smartocs__card-content-info">';
		$output .= '<img src="' . esc_url( SMARTOCS_URL . 'assets/images/icons/info-circle.svg' ) . '" alt="' . esc_attr__( 'Info icon', 'smart-one-click-setup' ) . '">';
		$output .= '</a>';
		$output .= '</div>';
		$output .= '<div class="smartocs__card-footer">';
		$output .= '<label for="smartocs__zip-file-upload" class="button button-primary custom-file-upload-button">';
		$output .= esc_html__( 'Select ZIP File', 'smart-one-click-setup' );
		$output .= '</label>';
		$output .= '<input id="smartocs__zip-file-upload" type="file" class="smartocs-hide-input" name="zip_file" accept=".zip">';
		$output .= '</div>';
		$output .= '</div>';

		$output .= '<div class="smartocs__file-upload smartocs__card smartocs__card--full smartocs-smart-import-hooks-card">';
		$output .= '<div class="smartocs__card-content">';
		$output .= '<h3>' . esc_html__( 'Import Hooks Configuration', 'smart-one-click-setup' ) . '</h3>';
		$output .= '<p class="description">';
		$output .= esc_html__( 'Configure custom actions to run before and after import. These can also be configured programmatically using filters.', 'smart-one-click-setup' );
		$output .= '</p>';

		$output .= '<div class="smartocs-smart-import-hook-section">';
		$output .= '<label>';
		$output .= '<strong>' . esc_html__( 'Before Import Hook', 'smart-one-click-setup' ) . '</strong>';
		$output .= '<textarea name="before_import_hook" rows="3" placeholder="' . esc_attr__( 'PHP code to execute before import (optional)', 'smart-one-click-setup' ) . '" class="smartocs-smart-import-textarea"></textarea>';
		$output .= '<small class="smartocs-smart-import-hook-help">' . esc_html__( 'Or use the filter: smartocs/before_content_import', 'smart-one-click-setup' ) . '</small>';
		$output .= '</label>';
		$output .= '</div>';

		$output .= '<div class="smartocs-smart-import-hook-section">';
		$output .= '<label>';
		$output .= '<strong>' . esc_html__( 'After Import Hook', 'smart-one-click-setup' ) . '</strong>';
		$output .= '<textarea name="after_import_hook" rows="3" placeholder="' . esc_attr__( 'PHP code to execute after import (optional)', 'smart-one-click-setup' ) . '" class="smartocs-smart-import-textarea"></textarea>';
		$output .= '<small class="smartocs-smart-import-hook-help">' . esc_html__( 'Or use the filter: smartocs/after_import', 'smart-one-click-setup' ) . '</small>';
		$output .= '</label>';
		$output .= '</div>';
		$output .= '</div>';
		$output .= '</div>';

		$output .= '</div>';
		$output .= '<div class="smartocs__file-upload-container--footer">';
		$output .= '<button type="submit" class="smartocs__button button button-hero button-primary js-smartocs-start-smart-import" disabled>';
		$output .= esc_html__( 'Start Import', 'smart-one-click-setup' );
		$output .= '</button>';
		$output .= '</div>';
		$output .= '</form>';
		$output .= '</div>';
		$output .= '</div>';
		$output .= '</div>';

		$output .= '<div class="smartocs-importing js-smartocs-importing">';
		$output .= '<div class="smartocs-importing-header">';
		$output .= '<h2>' . esc_html__( 'Importing Content', 'smart-one-click-setup' ) . '</h2>';
		$output .= '<p>' . esc_html__( 'Please sit tight while we import your content. Do not refresh the page or hit the back button.', 'smart-one-click-setup' ) . '</p>';
		$output .= '</div>';
		$output .= '<div class="smartocs-importing-content">';
		$output .= '<img class="smartocs-importing-content-importing" src="' . esc_url( SMARTOCS_URL . 'assets/images/importing.svg' ) . '" alt="' . esc_attr__( 'Importing animation', 'smart-one-click-setup' ) . '">';
		$output .= '</div>';
		$output .= '</div>';

		$output .= '<div class="smartocs-imported js-smartocs-imported">';
		$output .= '<div class="smartocs-imported-header">';
		$output .= '<h2 class="js-smartocs-ajax-response-title">' . esc_html__( 'Import Complete!', 'smart-one-click-setup' ) . '</h2>';
		$output .= '<div class="js-smartocs-ajax-response-subtitle">';
		$output .= '<p>' . esc_html__( 'Congrats, your demo was imported successfully. You can now begin editing your site.', 'smart-one-click-setup' ) . '</p>';
		$output .= '</div>';
		$output .= '</div>';
		$output .= '<div class="smartocs-imported-content">';
		$output .= '<div class="smartocs__response js-smartocs-ajax-response"></div>';
		$output .= '</div>';
		$output .= '<div class="smartocs-imported-footer">';
		$output .= wp_kses( $smartocs->get_import_successful_buttons_html(), array( 'a' => array( 'href' => array(), 'class' => array(), 'target' => array() ) ) );
		$output .= '</div>';
		$output .= '</div>';
		$output .= '</div>';

		if ( $args['show_sidebar'] ) {
			$output .= '<div class="smartocs__content-container-content--side">';
			$output .= wp_kses_post( ViewHelpers::small_theme_card() );
			$output .= '</div>';
		}

		$output .= '</div>';
		$output .= '</div>';
		$output .= '</div>';
		$output .= '</div>';

	} catch ( \Exception $e ) {
		// Log the exception if needed.
		$output = '<div class="notice notice-error"><p>' . esc_html__( 'An error occurred while generating the import interface.', 'smart-one-click-setup' ) . '</p></div>';
	}

	// Allow filtering of the output.
	$output = Helpers::apply_filters( 'smartocs/template_smart_import_output', $output, $args );

	if ( $args['echo'] ) {
		echo wp_kses_post( $output );
		return;
	}

	return $output;
}

