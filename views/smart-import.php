<?php
namespace SMARTOCS;
/**
 * The Smart Import page view.
 *
 * @package smartocs
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}



/**
 * Hook for adding the custom plugin page header
 */
Helpers::do_action( 'smartocs/plugin_page_header' );
?>



<div class="smartocs smartocs--smart-import">

	<?php echo wp_kses_post( ViewHelpers::plugin_header_output() ); ?>

	<?php
	// Display warning if PHP safe mode is enabled.
	if ( ini_get( 'safe_mode' ) ) {
		printf( /* translators: %1$s - the opening div and paragraph HTML tags, %2$s and %3$s - strong HTML tags, %4$s - the closing div and paragraph HTML tags. */
			esc_html__( '%1$sWarning: your server is using %2$sPHP safe mode%3$s. This means that you might experience server timeout errors.%4$s', 'smart-one-click-setup' ),
			'<div class="notice  notice-warning  is-dismissible"><p>',
			'<strong>',
			'</strong>',
			'</p></div>'
		);
	}
	?>
	<div class="smartocs__content-container">

	<div class="smartocs__admin-notices js-smartocs-admin-notices-container"></div>



		<?php
		$smartocs_show_intro_text = Helpers::apply_filters( 'smartocs/show_intro_text', true );
		if ( $smartocs_show_intro_text ) :
			// Build plugin intro text.
						$smartocs_default_description = esc_html__( 'Import demo data from a ZIP file exported using Smart Export, or use predefined import configurations set up by your theme developer.', 'smart-one-click-setup' ) . ' ' . esc_html__( 'This will import your content, widgets, customizer settings, and more.', 'smart-one-click-setup' );
						$smartocs_intro_description = Helpers::apply_filters( 'smartocs/intro_description_text', $smartocs_default_description );
			$smartocs_plugin_intro_text = '<div class="smartocs__intro-text">
				<p class="about-description">' . wp_kses_post( $smartocs_intro_description ) . '</p>
			</div>';

			// Display the plugin intro text (can be replaced with custom text through the filter below).
			echo wp_kses_post( Helpers::apply_filters( 'smartocs/plugin_intro_text', $smartocs_plugin_intro_text ) );
		endif;
		?>

		<?php $smartocs_theme = wp_get_theme(); ?>

		<div class="smartocs__content-container-content">
			<div class="smartocs__content-container-content--main">
				<div class="smartocs-smart-import-content js-smartocs-smart-import-content">
					<div class="smartocs__file-upload-container">
						<?php
						$smartocs_show_file_upload_header = Helpers::apply_filters( 'smartocs/show_file_upload_header', true );
						if ( $smartocs_show_file_upload_header ) :
						?>
						<div class="smartocs__file-upload-container--header">
							<h2><?php esc_html_e( 'Smart Import', 'smart-one-click-setup' ); ?></h2>
						</div>
						<?php endif; ?>

						<?php
						$smartocs_predefined_imports = Helpers::apply_filters( 'smartocs/predefined_import_files', array() );
						$smartocs_has_predefined_imports = ! empty( $smartocs_predefined_imports );
						$smartocs_show_tabs = Helpers::apply_filters( 'smartocs/show_smart_import_tabs', $smartocs_has_predefined_imports );
						?>

						<?php if ( $smartocs_has_predefined_imports && $smartocs_show_tabs ) : ?>
						<div class="smartocs-smart-import-tabs">
							<button type="button" class="smartocs-smart-import-tab active button" data-tab="predefined">
								<?php esc_html_e( 'Predefined Import', 'smart-one-click-setup' ); ?>
							</button>
							<button type="button" class="smartocs-smart-import-tab button" data-tab="upload">
								<?php esc_html_e( 'Upload ZIP File', 'smart-one-click-setup' ); ?>
							</button>
						</div>
						<?php endif; ?>

						<?php if ( $smartocs_has_predefined_imports ) : ?>
						<div class="smartocs-smart-import-tab-content active" data-tab-content="predefined">
							<?php
							if ( ! empty( $smartocs_predefined_imports ) ) :
								?>
								<div class="smartocs__gl js-smartocs-gl">
									<div class="smartocs__gl-item-container js-smartocs-gl-item-container">
										<?php
										// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
										foreach ( $smartocs_predefined_imports as $index => $import ) :
										?>
											<?php
											/* translators: %d: The demo import number. */
											$smartocs_import_name = ! empty( $import['name'] ) ? $import['name'] : sprintf( esc_html__( 'Demo Import %d', 'smart-one-click-setup' ), $index + 1 );
											$smartocs_import_description = ! empty( $import['description'] ) ? $import['description'] : '';
											$smartocs_import_preview = ! empty( $import['preview_image'] ) ? $import['preview_image'] : '';
											$smartocs_import_preview_url = ! empty( $import['preview_url'] ) ? $import['preview_url'] : '';
											$smartocs_has_zip = ! empty( $import['zip_url'] ) || ( ! empty( $import['zip_path'] ) && file_exists( $import['zip_path'] ) );

											// Default to theme screenshot if no preview image.
											if ( empty( $smartocs_import_preview ) ) {
												$smartocs_import_preview = $smartocs_theme->get_screenshot();
											}
											?>
											<div class="smartocs__gl-item js-smartocs-gl-item">
												<div class="smartocs__gl-item-image-container">
													<?php if ( ! empty( $smartocs_import_preview ) ) : ?>
														<img class="smartocs__gl-item-image" src="<?php echo esc_url( $smartocs_import_preview ); ?>" alt="<?php echo esc_attr( $smartocs_import_name ); ?>" loading="lazy">
													<?php else : ?>
														<div class="smartocs__gl-item-image smartocs__gl-item-image--no-image"><?php esc_html_e( 'No preview image.', 'smart-one-click-setup' ); ?></div>
													<?php endif; ?>
												</div>
												<div class="smartocs__gl-item-footer<?php echo esc_attr( ! empty( $smartocs_import_preview_url ) ? ' smartocs__gl-item-footer--with-preview' : '' ); ?>">
													<h4 class="smartocs__gl-item-title" title="<?php echo esc_attr( $smartocs_import_name ); ?>"><?php echo esc_html( $smartocs_import_name ); ?></h4>
													<?php if ( ! empty( $smartocs_import_description ) ) : ?>
														<p class="smartocs-smart-import-description"><?php echo esc_html( $smartocs_import_description ); ?></p>
													<?php endif; ?>
													<span class="smartocs__gl-item-buttons">
														<?php if ( ! empty( $smartocs_import_preview_url ) ) : ?>
															<a class="smartocs__gl-item-button button js-smartocs-preview-demo" href="<?php echo esc_url( $smartocs_import_preview_url ); ?>" target="_blank" rel="noopener noreferrer">
																<?php esc_html_e( 'Preview Demo', 'smart-one-click-setup' ); ?>
															</a>
														<?php endif; ?>
														<?php if ( $smartocs_has_zip ) : ?>
															<button class="smartocs__gl-item-button button button-primary js-smartocs-use-predefined-import" data-import-index="<?php echo esc_attr( $index ); ?>">
																<?php esc_html_e( 'Import Demo', 'smart-one-click-setup' ); ?>
															</button>
														<?php else : ?>
															<span class="notice notice-error inline smartocs-smart-import-error-notice">
																<?php esc_html_e( 'ZIP file URL or path is missing.', 'smart-one-click-setup' ); ?>
															</span>
														<?php endif; ?>
													</span>
												</div>
											</div>
										<?php endforeach; ?>
									</div>
								</div>
							<?php endif; ?>
						</div>
						<?php endif; ?>

						<div class="smartocs-smart-import-tab-content<?php echo esc_attr( $smartocs_has_predefined_imports ? '' : ' active' ); ?>" data-tab-content="upload">
							<form id="smartocs-smart-import-form" class="smartocs-smart-import-form">
								<div class="smartocs__file-upload-container-items">
									<div class="smartocs__file-upload smartocs__card smartocs__card--full">
										<div class="smartocs__card-content">
											<label for="smartocs__zip-file-upload">
												<div class="smartocs-icon-container">
													<img src="<?php echo esc_url( SMARTOCS_URL . 'assets/images/icons/copy.svg' ); ?>" class="smartocs-icon--copy" alt="<?php esc_attr_e( 'Upload icon', 'smart-one-click-setup' ); ?>">
												</div>
												<h3><?php esc_html_e( 'Upload Export ZIP File', 'smart-one-click-setup' ); ?></h3>
												<p><?php esc_html_e( 'Select a ZIP file exported from Smart Export.', 'smart-one-click-setup' ); ?></p>
												<span class="smartocs-smart-import-file-name js-smartocs-smart-import-file-name"></span>
											</label>
											<a href="https://smartocs.buildbycj.com/#user-guide" target="_blank" rel="noopener noreferrer" class="smartocs__card-content-info">
												<img src="<?php echo esc_url( SMARTOCS_URL . 'assets/images/icons/info-circle.svg' ); ?>" alt="<?php esc_attr_e( 'Info icon', 'smart-one-click-setup' ); ?>">
											</a>
										</div>
										<div class="smartocs__card-footer">
											<label for="smartocs__zip-file-upload" class="button button-primary custom-file-upload-button">
												<?php esc_html_e( 'Select ZIP File', 'smart-one-click-setup' ); ?>
											</label>
											<input id="smartocs__zip-file-upload" type="file" class="smartocs-hide-input" name="zip_file" accept=".zip">
										</div>
									</div>

									<div class="smartocs__file-upload smartocs__card smartocs__card--full smartocs-smart-import-hooks-card">
										<div class="smartocs__card-content">
											<h3><?php esc_html_e( 'Import Hooks Configuration', 'smart-one-click-setup' ); ?></h3>
											<p class="description">
												<?php esc_html_e( 'Configure custom actions to run before and after import. These can also be configured programmatically using filters.', 'smart-one-click-setup' ); ?>
											</p>

											<div class="smartocs-smart-import-hook-section">
												<label>
													<strong><?php esc_html_e( 'Before Import Hook', 'smart-one-click-setup' ); ?></strong>
													<textarea name="before_import_hook" rows="3" placeholder="<?php esc_attr_e( 'PHP code to execute before import (optional)', 'smart-one-click-setup' ); ?>" class="smartocs-smart-import-textarea"></textarea>
													<small class="smartocs-smart-import-hook-help"><?php esc_html_e( 'Or use the filter: smartocs/before_content_import', 'smart-one-click-setup' ); ?></small>
												</label>
											</div>

											<div class="smartocs-smart-import-hook-section">
												<label>
													<strong><?php esc_html_e( 'After Import Hook', 'smart-one-click-setup' ); ?></strong>
													<textarea name="after_import_hook" rows="3" placeholder="<?php esc_attr_e( 'PHP code to execute after import (optional)', 'smart-one-click-setup' ); ?>" class="smartocs-smart-import-textarea"></textarea>
													<small class="smartocs-smart-import-hook-help"><?php esc_html_e( 'Or use the filter: smartocs/after_import', 'smart-one-click-setup' ); ?></small>
												</label>
											</div>
										</div>
									</div>
								</div>

								<div class="smartocs__file-upload-container--footer">
									<a href="<?php echo esc_url( $this->get_plugin_settings_url() ); ?>" class="smartocs__button button">
										
										<span><?php esc_html_e( 'Go Back', 'smart-one-click-setup' ); ?></span>
									</a>
									<button type="submit" class="smartocs__button button button-hero button-primary js-smartocs-start-smart-import" disabled>
										<?php esc_html_e( 'Start Import', 'smart-one-click-setup' ); ?>
									</button>
								</div>
							</form>
						</div>
					</div>
				</div>

				<div class="smartocs-importing js-smartocs-importing">
					<div class="smartocs-importing-header">
						<h2><?php esc_html_e( 'Importing Content', 'smart-one-click-setup' ); ?></h2>
						<p><?php esc_html_e( 'Please sit tight while we import your content. Do not refresh the page or hit the back button.', 'smart-one-click-setup' ); ?></p>
					</div>
					<div class="smartocs-importing-content">
						<img class="smartocs-importing-content-importing" src="<?php echo esc_url( SMARTOCS_URL . 'assets/images/importing.svg' ); ?>" alt="<?php esc_attr_e( 'Importing animation', 'smart-one-click-setup' ); ?>">
					</div>
				</div>

				<div class="smartocs-imported js-smartocs-imported">
					<div class="smartocs-imported-header">
						<h2 class="js-smartocs-ajax-response-title"><?php esc_html_e( 'Import Complete!', 'smart-one-click-setup' ); ?></h2>
						<div class="js-smartocs-ajax-response-subtitle">
							<p><?php esc_html_e( 'Congrats, your demo was imported successfully. You can now begin editing your site.', 'smart-one-click-setup' ); ?></p>
						</div>
					</div>
					<div class="smartocs-imported-content">
						<div class="smartocs__response js-smartocs-ajax-response"></div>
					</div>
					<div class="smartocs-imported-footer">
						<?php echo wp_kses( $this->get_import_successful_buttons_html(), array( 'a' => array( 'href' => array(), 'class' => array(), 'target' => array() ) ) ); ?>
					</div>
				</div>
			</div>
			<div class="smartocs__content-container-content--side">
				<?php
				echo wp_kses_post( ViewHelpers::small_theme_card() );
				?>
			</div>
		</div>

	</div>
</div>

<?php
/**
 * Hook for adding the custom admin page footer
 */
Helpers::do_action( 'smartocs/plugin_page_footer' );
?>

