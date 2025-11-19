<?php
/**
 * The Smart Import page view.
 *
 * @package socs
 */

namespace SOCS;


/**
 * Hook for adding the custom plugin page header
 */
Helpers::do_action( 'socs/plugin_page_header' );
?>



<div class="socs socs--smart-import">

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
	<div class="socs__content-container">

	<div class="socs__admin-notices js-socs-admin-notices-container"></div>



		<?php
		$socs_show_intro_text = Helpers::apply_filters( 'socs/show_intro_text', true );
		if ( $socs_show_intro_text ) :
			// Start output buffer for displaying the plugin intro text.
			ob_start();
			?>

			<div class="socs__intro-text">
				<p class="about-description">
					<?php
					$socs_default_description = esc_html__( 'Import demo data from a ZIP file exported using Smart Export, or use predefined import configurations set up by your theme developer.', 'smart-one-click-setup' ) . ' ' . esc_html__( 'This will import your content, widgets, customizer settings, and more.', 'smart-one-click-setup' );
					$socs_intro_description = Helpers::apply_filters( 'socs/intro_description_text', $socs_default_description );
					echo wp_kses_post( $socs_intro_description );
					?>
				</p>
			</div>

			<?php
			$socs_plugin_intro_text = ob_get_clean();

			// Display the plugin intro text (can be replaced with custom text through the filter below).
			echo wp_kses_post( Helpers::apply_filters( 'socs/plugin_intro_text', $socs_plugin_intro_text ) );
		endif;
		?>

		<?php $socs_theme = wp_get_theme(); ?>

		<div class="socs__content-container-content">
			<div class="socs__content-container-content--main">
				<div class="socs-smart-import-content js-socs-smart-import-content">
					<div class="socs__file-upload-container">
						<?php
						$socs_show_file_upload_header = Helpers::apply_filters( 'socs/show_file_upload_header', true );
						if ( $socs_show_file_upload_header ) :
						?>
						<div class="socs__file-upload-container--header">
							<h2><?php esc_html_e( 'Smart Import', 'smart-one-click-setup' ); ?></h2>
						</div>
						<?php endif; ?>

						<?php
						$socs_predefined_imports = Helpers::apply_filters( 'socs/predefined_import_files', array() );
						$socs_has_predefined_imports = ! empty( $socs_predefined_imports );
						$socs_show_tabs = Helpers::apply_filters( 'socs/show_smart_import_tabs', $socs_has_predefined_imports );
						?>

						<?php if ( $socs_has_predefined_imports && $socs_show_tabs ) : ?>
						<div class="socs-smart-import-tabs">
							<button type="button" class="socs-smart-import-tab active button" data-tab="predefined">
								<?php esc_html_e( 'Predefined Import', 'smart-one-click-setup' ); ?>
							</button>
							<button type="button" class="socs-smart-import-tab button" data-tab="upload">
								<?php esc_html_e( 'Upload ZIP File', 'smart-one-click-setup' ); ?>
							</button>
						</div>
						<?php endif; ?>

						<?php if ( $socs_has_predefined_imports ) : ?>
						<div class="socs-smart-import-tab-content active" data-tab-content="predefined">
							<?php
							if ( ! empty( $socs_predefined_imports ) ) :
								?>
								<div class="socs__gl js-socs-gl">
									<div class="socs__gl-item-container js-socs-gl-item-container">
										<?php
										// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
										foreach ( $socs_predefined_imports as $index => $import ) :
										?>
											<?php
											/* translators: %d: The demo import number. */
											$socs_import_name = ! empty( $import['name'] ) ? $import['name'] : sprintf( esc_html__( 'Demo Import %d', 'smart-one-click-setup' ), $index + 1 );
											$socs_import_description = ! empty( $import['description'] ) ? $import['description'] : '';
											$socs_import_preview = ! empty( $import['preview_image'] ) ? $import['preview_image'] : '';
											$socs_import_preview_url = ! empty( $import['preview_url'] ) ? $import['preview_url'] : '';
											$socs_has_zip = ! empty( $import['zip_url'] ) || ( ! empty( $import['zip_path'] ) && file_exists( $import['zip_path'] ) );

											// Default to theme screenshot if no preview image.
											if ( empty( $socs_import_preview ) ) {
												$socs_import_preview = $socs_theme->get_screenshot();
											}
											?>
											<div class="socs__gl-item js-socs-gl-item">
												<div class="socs__gl-item-image-container">
													<?php if ( ! empty( $socs_import_preview ) ) : ?>
														<img class="socs__gl-item-image" src="<?php echo esc_url( $socs_import_preview ); ?>" alt="<?php echo esc_attr( $socs_import_name ); ?>" loading="lazy">
													<?php else : ?>
														<div class="socs__gl-item-image socs__gl-item-image--no-image"><?php esc_html_e( 'No preview image.', 'smart-one-click-setup' ); ?></div>
													<?php endif; ?>
												</div>
												<div class="socs__gl-item-footer<?php echo ! empty( $socs_import_preview_url ) ? ' socs__gl-item-footer--with-preview' : ''; ?>">
													<h4 class="socs__gl-item-title" title="<?php echo esc_attr( $socs_import_name ); ?>"><?php echo esc_html( $socs_import_name ); ?></h4>
													<?php if ( ! empty( $socs_import_description ) ) : ?>
														<p class="socs-smart-import-description"><?php echo esc_html( $socs_import_description ); ?></p>
													<?php endif; ?>
													<span class="socs__gl-item-buttons">
														<?php if ( ! empty( $socs_import_preview_url ) ) : ?>
															<a class="socs__gl-item-button button js-socs-preview-demo" href="<?php echo esc_url( $socs_import_preview_url ); ?>" target="_blank" rel="noopener noreferrer">
																<?php esc_html_e( 'Preview Demo', 'smart-one-click-setup' ); ?>
															</a>
														<?php endif; ?>
														<?php if ( $socs_has_zip ) : ?>
															<button class="socs__gl-item-button button button-primary js-socs-use-predefined-import" data-import-index="<?php echo esc_attr( $index ); ?>">
																<?php esc_html_e( 'Import Demo', 'smart-one-click-setup' ); ?>
															</button>
														<?php else : ?>
															<span class="notice notice-error inline socs-smart-import-error-notice">
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

						<div class="socs-smart-import-tab-content<?php echo $socs_has_predefined_imports ? '' : ' active'; ?>" data-tab-content="upload">
							<form id="socs-smart-import-form" class="socs-smart-import-form">
								<div class="socs__file-upload-container-items">
									<div class="socs__file-upload socs__card socs__card--full">
										<div class="socs__card-content">
											<label for="socs__zip-file-upload">
												<div class="socs-icon-container">
													<img src="<?php echo esc_url( SOCS_URL . 'assets/images/icons/copy.svg' ); ?>" class="socs-icon--copy" alt="<?php esc_attr_e( 'Upload icon', 'smart-one-click-setup' ); ?>">
												</div>
												<h3><?php esc_html_e( 'Upload Export ZIP File', 'smart-one-click-setup' ); ?></h3>
												<p><?php esc_html_e( 'Select a ZIP file exported from Smart Export.', 'smart-one-click-setup' ); ?></p>
												<span class="socs-smart-import-file-name js-socs-smart-import-file-name"></span>
											</label>
											<a href="https://socs.buildbycj.com/#user-guide" target="_blank" rel="noopener noreferrer" class="socs__card-content-info">
												<img src="<?php echo esc_url( SOCS_URL . 'assets/images/icons/info-circle.svg' ); ?>" alt="<?php esc_attr_e( 'Info icon', 'smart-one-click-setup' ); ?>">
											</a>
										</div>
										<div class="socs__card-footer">
											<label for="socs__zip-file-upload" class="button button-primary custom-file-upload-button">
												<?php esc_html_e( 'Select ZIP File', 'smart-one-click-setup' ); ?>
											</label>
											<input id="socs__zip-file-upload" type="file" class="socs-hide-input" name="zip_file" accept=".zip">
										</div>
									</div>

									<div class="socs__file-upload socs__card socs__card--full socs-smart-import-hooks-card">
										<div class="socs__card-content">
											<h3><?php esc_html_e( 'Import Hooks Configuration', 'smart-one-click-setup' ); ?></h3>
											<p class="description">
												<?php esc_html_e( 'Configure custom actions to run before and after import. These can also be configured programmatically using filters.', 'smart-one-click-setup' ); ?>
											</p>

											<div class="socs-smart-import-hook-section">
												<label>
													<strong><?php esc_html_e( 'Before Import Hook', 'smart-one-click-setup' ); ?></strong>
													<textarea name="before_import_hook" rows="3" placeholder="<?php esc_attr_e( 'PHP code to execute before import (optional)', 'smart-one-click-setup' ); ?>" class="socs-smart-import-textarea"></textarea>
													<small class="socs-smart-import-hook-help"><?php esc_html_e( 'Or use the filter: socs/before_content_import', 'smart-one-click-setup' ); ?></small>
												</label>
											</div>

											<div class="socs-smart-import-hook-section">
												<label>
													<strong><?php esc_html_e( 'After Import Hook', 'smart-one-click-setup' ); ?></strong>
													<textarea name="after_import_hook" rows="3" placeholder="<?php esc_attr_e( 'PHP code to execute after import (optional)', 'smart-one-click-setup' ); ?>" class="socs-smart-import-textarea"></textarea>
													<small class="socs-smart-import-hook-help"><?php esc_html_e( 'Or use the filter: socs/after_import', 'smart-one-click-setup' ); ?></small>
												</label>
											</div>
										</div>
									</div>
								</div>

								<div class="socs__file-upload-container--footer">
									<a href="<?php echo esc_url( $this->get_plugin_settings_url() ); ?>" class="socs__button button">
										
										<span><?php esc_html_e( 'Go Back', 'smart-one-click-setup' ); ?></span>
									</a>
									<button type="submit" class="socs__button button button-hero button-primary js-socs-start-smart-import" disabled>
										<?php esc_html_e( 'Start Import', 'smart-one-click-setup' ); ?>
									</button>
								</div>
							</form>
						</div>
					</div>
				</div>

				<div class="socs-importing js-socs-importing">
					<div class="socs-importing-header">
						<h2><?php esc_html_e( 'Importing Content', 'smart-one-click-setup' ); ?></h2>
						<p><?php esc_html_e( 'Please sit tight while we import your content. Do not refresh the page or hit the back button.', 'smart-one-click-setup' ); ?></p>
					</div>
					<div class="socs-importing-content">
						<img class="socs-importing-content-importing" src="<?php echo esc_url( SOCS_URL . 'assets/images/importing.svg' ); ?>" alt="<?php esc_attr_e( 'Importing animation', 'smart-one-click-setup' ); ?>">
					</div>
				</div>

				<div class="socs-imported js-socs-imported">
					<div class="socs-imported-header">
						<h2 class="js-socs-ajax-response-title"><?php esc_html_e( 'Import Complete!', 'smart-one-click-setup' ); ?></h2>
						<div class="js-socs-ajax-response-subtitle">
							<p><?php esc_html_e( 'Congrats, your demo was imported successfully. You can now begin editing your site.', 'smart-one-click-setup' ); ?></p>
						</div>
					</div>
					<div class="socs-imported-content">
						<div class="socs__response js-socs-ajax-response"></div>
					</div>
					<div class="socs-imported-footer">
						<?php echo wp_kses( $this->get_import_successful_buttons_html(), array( 'a' => array( 'href' => array(), 'class' => array(), 'target' => array() ) ) ); ?>
					</div>
				</div>
			</div>
			<div class="socs__content-container-content--side">
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
Helpers::do_action( 'socs/plugin_page_footer' );
?>

