<?php
/**
 * The Smart Export page view.
 *
 * @package socs
 */

namespace SOCS;

/**
 * Hook for adding the custom plugin page header
 */
Helpers::do_action( 'socs/plugin_page_header' );
?>

<div class="socs socs--export">

	<?php echo wp_kses_post( ViewHelpers::plugin_header_output() ); ?>

	<div class="socs__content-container">

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

		<div class="socs__admin-notices js-socs-admin-notices-container"></div>

		<?php
		// Start output buffer for displaying the plugin intro text.
		ob_start();
		?>

		<div class="socs__intro-text">
			<p class="about-description">
				<?php esc_html_e( 'Export your WordPress site content, widgets, customizer settings, plugin settings, and Elementor data into a single ZIP file.', 'smart-one-click-setup' ); ?>
				<?php esc_html_e( 'This exported package can then be imported on another WordPress site using Smart Import.', 'smart-one-click-setup' ); ?>
			</p>
		</div>

		<?php
		$socs_plugin_intro_text = ob_get_clean();

		// Display the plugin intro text (can be replaced with custom text through the filter below).
		echo wp_kses_post( Helpers::apply_filters( 'socs/plugin_intro_text', $socs_plugin_intro_text ) );
		?>

		<?php $socs_theme = wp_get_theme(); ?>

		<div class="socs__content-container-content">
			<div class="socs__content-container-content--main">
				<div class="socs-export-content js-socs-export-content">
					<div class="socs__file-upload-container">
						<div class="socs__file-upload-container--header">
							<h2><?php esc_html_e( 'Smart Export', 'smart-one-click-setup' ); ?></h2>
						</div>

						<form id="socs-export-form" class="socs-export-form">
							<div class="socs__file-upload-container-items">
								<?php $socs_first_row_class = class_exists( 'ReduxFramework' ) ? 'four' : 'three'; ?>
								<div class="socs__file-upload socs__card socs__card--<?php echo esc_attr( $socs_first_row_class ); ?>">
									<div class="socs__card-content">
										<label for="socs-export-content">
											<div class="socs-icon-container">
												<img src="<?php echo esc_url( SOCS_URL . 'assets/images/icons/content.svg' ); ?>" class="socs-icon--content" alt="<?php esc_attr_e( 'Content export icon', 'smart-one-click-setup' ); ?>">
											</div>
											<h3><?php esc_html_e( 'Export Content', 'smart-one-click-setup' ); ?></h3>
											<p><?php esc_html_e( 'Export all posts, pages, comments, custom fields, categories, and tags.', 'smart-one-click-setup' ); ?></p>
										</label>
										<a href="https://socs.buildbycj.com/#user-guide" target="_blank" rel="noopener noreferrer" class="socs__card-content-info">
											<img src="<?php echo esc_url( SOCS_URL . 'assets/images/icons/info-circle.svg' ); ?>" alt="<?php esc_attr_e( 'Info icon', 'smart-one-click-setup' ); ?>">
										</a>
									</div>
									<div class="socs__card-footer">
										<label for="socs-export-content" class="button button-primary">
											<input type="checkbox" id="socs-export-content" name="export_content" value="1" checked class="socs-hide-input">
											<span class="js-socs-export-checkbox-label"><?php esc_html_e( 'Selected', 'smart-one-click-setup' ); ?></span>
										</label>
									</div>
								</div>

								<div class="socs__file-upload socs__card socs__card--<?php echo esc_attr( $socs_first_row_class ); ?>">
									<div class="socs__card-content">
										<label for="socs-export-widgets">
											<div class="socs-icon-container">
												<img src="<?php echo esc_url( SOCS_URL . 'assets/images/icons/widgets.svg' ); ?>" class="socs-icon--widgets" alt="<?php esc_attr_e( 'Widgets export icon', 'smart-one-click-setup' ); ?>">
											</div>
											<h3><?php esc_html_e( 'Export Widgets', 'smart-one-click-setup' ); ?></h3>
											<p><?php esc_html_e( 'Export all widget settings and sidebar configurations.', 'smart-one-click-setup' ); ?></p>
										</label>
										<a href="https://socs.buildbycj.com/#user-guide" target="_blank" rel="noopener noreferrer" class="socs__card-content-info">
											<img src="<?php echo esc_url( SOCS_URL . 'assets/images/icons/info-circle.svg' ); ?>" alt="<?php esc_attr_e( 'Info icon', 'smart-one-click-setup' ); ?>">
										</a>
									</div>
									<div class="socs__card-footer">
										<label for="socs-export-widgets" class="button button-primary">
											<input type="checkbox" id="socs-export-widgets" name="export_widgets" value="1" checked class="socs-hide-input">
											<span class="js-socs-export-checkbox-label"><?php esc_html_e( 'Selected', 'smart-one-click-setup' ); ?></span>
										</label>
									</div>
								</div>

								<div class="socs__file-upload socs__card socs__card--<?php echo esc_attr( $socs_first_row_class ); ?>">
									<div class="socs__card-content">
										<label for="socs-export-customizer">
											<div class="socs-icon-container">
												<img src="<?php echo esc_url( SOCS_URL . 'assets/images/icons/brush.svg' ); ?>" class="socs-icon--brush" alt="<?php esc_attr_e( 'Customizer export icon', 'smart-one-click-setup' ); ?>">
											</div>
											<h3><?php esc_html_e( 'Export Customizer', 'smart-one-click-setup' ); ?></h3>
											<p><?php esc_html_e( 'Export theme customizer settings.', 'smart-one-click-setup' ); ?></p>
										</label>
										<a href="https://socs.buildbycj.com/#user-guide" target="_blank" rel="noopener noreferrer" class="socs__card-content-info">
											<img src="<?php echo esc_url( SOCS_URL . 'assets/images/icons/info-circle.svg' ); ?>" alt="<?php esc_attr_e( 'Info icon', 'smart-one-click-setup' ); ?>">
										</a>
									</div>
									<div class="socs__card-footer">
										<label for="socs-export-customizer" class="button button-primary">
											<input type="checkbox" id="socs-export-customizer" name="export_customizer" value="1" checked class="socs-hide-input">
											<span class="js-socs-export-checkbox-label"><?php esc_html_e( 'Selected', 'smart-one-click-setup' ); ?></span>
										</label>
									</div>
								</div>

								<?php if ( class_exists( '\Elementor\Plugin' ) ) : ?>
								<div class="socs__file-upload socs__card socs__card--<?php echo esc_attr( $socs_first_row_class ); ?>">
									<div class="socs__card-content">
										<label for="socs-export-elementor">
											<div class="socs-icon-container">
												<img src="<?php echo esc_url( SOCS_URL . 'assets/images/icons/elementor.svg' ); ?>" class="socs-icon--layout" alt="<?php esc_attr_e( 'Elementor export icon', 'smart-one-click-setup' ); ?>">
											</div>
											<h3><?php esc_html_e( 'Export Elementor Style Kit', 'smart-one-click-setup' ); ?></h3>
											<p><?php esc_html_e( 'Export Elementor Style Kit style kit settings.', 'smart-one-click-setup' ); ?></p>
										</label>
										<a href="https://socs.buildbycj.com/#user-guide" target="_blank" rel="noopener noreferrer" class="socs__card-content-info">
											<img src="<?php echo esc_url( SOCS_URL . 'assets/images/icons/info-circle.svg' ); ?>" alt="<?php esc_attr_e( 'Info icon', 'smart-one-click-setup' ); ?>">
										</a>
									</div>
									<div class="socs__card-footer">
										<label for="socs-export-elementor" class="button button-secondary">
											<input type="checkbox" id="socs-export-elementor" name="export_elementor" value="1" class="socs-hide-input">
											<span class="js-socs-export-checkbox-label"><?php esc_html_e( 'Select', 'smart-one-click-setup' ); ?></span>
										</label>
									</div>
								</div>
								<?php endif; ?>

								<div class="socs__file-upload socs__card socs__card--<?php echo esc_attr( $socs_first_row_class ); ?>">
									<div class="socs__card-content">
										<label>
											<div class="socs-icon-container">
												<img src="<?php echo esc_url( SOCS_URL . 'assets/images/icons/plugin-settings.svg' ); ?>" class="socs-icon--plugins" alt="<?php esc_attr_e( 'Plugins export icon', 'smart-one-click-setup' ); ?>">
											</div>
											<h3><?php esc_html_e( 'Export Plugin Settings', 'smart-one-click-setup' ); ?></h3>
											<p><?php esc_html_e( 'Select which plugin settings to export.', 'smart-one-click-setup' ); ?></p>
										</label>
										<a href="https://socs.buildbycj.com/#user-guide" target="_blank" rel="noopener noreferrer" class="socs__card-content-info">
											<img src="<?php echo esc_url( SOCS_URL . 'assets/images/icons/info-circle.svg' ); ?>" alt="<?php esc_attr_e( 'Info icon', 'smart-one-click-setup' ); ?>">
										</a>
									</div>
									<div class="socs__card-footer">
										<div class="socs-export-plugins-list">
											<?php
											$socs_active_plugins = get_option( 'active_plugins', array() );
											$socs_plugin_list = array();
											foreach ( $socs_active_plugins as $plugin ) {
												$socs_plugin_data = get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin );
												if ( ! empty( $socs_plugin_data['Name'] ) ) {
													$socs_plugin_slug = dirname( $plugin );
													$socs_plugin_list[ $socs_plugin_slug ] = $socs_plugin_data['Name'];
												}
											}

											// Allow filtering of plugin list.
											$socs_plugin_list = Helpers::apply_filters( 'socs/export_plugin_list', $socs_plugin_list );

											if ( ! empty( $socs_plugin_list ) ) :
												foreach ( $socs_plugin_list as $socs_slug => $socs_name ) :
													?>
													<div class="socs-export-plugin-item-wrapper">
														<label class="socs-export-plugin-item">
															<input type="checkbox" name="export_plugins[]" value="<?php echo esc_attr( $socs_slug ); ?>" class="socs-export-plugin-checkbox" data-plugin-slug="<?php echo esc_attr( $socs_slug ); ?>">
															<span><?php echo esc_html( $socs_name ); ?></span>
														</label>
														<button type="button" class="socs-export-plugin-custom-options-btn" data-plugin-slug="<?php echo esc_attr( $socs_slug ); ?>" data-plugin-name="<?php echo esc_attr( $socs_name ); ?>" title="<?php esc_attr_e( 'Add Custom Options', 'smart-one-click-setup' ); ?>">
															<span class="dashicons dashicons-admin-generic"></span>
														</button>
													</div>
													<?php
												endforeach;
											else :
												?>
												<p class="socs-export-no-plugins"><?php esc_html_e( 'No plugins available for export.', 'smart-one-click-setup' ); ?></p>
												<?php
											endif;
											?>
										</div>
									</div>
								</div>
							</div>

							<div class="socs__file-upload-container--footer">
							<a href="<?php echo esc_url( $this->get_plugin_settings_url() ); ?>" class="socs__button button">
								
								<span><?php esc_html_e( 'Go Back', 'smart-one-click-setup' ); ?></span>
							</a>
							<button type="submit" class="socs__button button button-hero button-primary js-socs-start-export">
								<?php esc_html_e( 'Generate Export', 'smart-one-click-setup' ); ?>
							</button>
						</div>
					</form>
					</div>
				</div>

				<div class="socs-exporting js-socs-exporting" style="display: none;">
					<div class="socs-exporting-header">
						<h2><?php esc_html_e( 'Exporting...', 'smart-one-click-setup' ); ?></h2>
						<p><?php esc_html_e( 'Please wait while we prepare your export package.', 'smart-one-click-setup' ); ?></p>
					</div>
					<div class="socs-exporting-content">
						<img class="socs-exporting-content-exporting" src="<?php echo esc_url( SOCS_URL . 'assets/images/importing.svg' ); ?>" alt="<?php esc_attr_e( 'Exporting animation', 'smart-one-click-setup' ); ?>">
					</div>
				</div>

				<div class="socs-exported js-socs-exported" style="display: none;">
					<div class="socs-exported-header">
						<h2 class="js-socs-export-response-title"><?php esc_html_e( 'Export Complete!', 'smart-one-click-setup' ); ?></h2>
						<div class="js-socs-export-response-subtitle">
							<p><?php esc_html_e( 'Your export package has been generated successfully.', 'smart-one-click-setup' ); ?></p>
						</div>
					</div>
					<div class="socs-exported-content">
						<div class="socs__response js-socs-export-response"></div>
					</div>
					<div class="socs-exported-footer">
						<a href="<?php echo esc_url( admin_url( 'themes.php?page=socs-smart-export' ) ); ?>" class="button button-primary button-hero">
							<?php esc_html_e( 'Go Back', 'smart-one-click-setup' ); ?>
						</a>
						<a href="#" class="button button-primary button-hero js-socs-download-export" target="_blank">
							<?php esc_html_e( 'Download Export', 'smart-one-click-setup' ); ?>
						</a>
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

<!-- Custom Plugin Options Modal -->
<div id="socs-custom-options-modal" class="socs-modal" style="display: none;">
	<div class="socs-modal-overlay"></div>
	<div class="socs-modal-content">
		<div class="socs-modal-header">
			<h2 class="socs-modal-title"><?php esc_html_e( 'Add Custom Options', 'smart-one-click-setup' ); ?></h2>
			<button type="button" class="socs-modal-close" aria-label="<?php esc_attr_e( 'Close', 'smart-one-click-setup' ); ?>">
				<span class="dashicons dashicons-no-alt"></span>
			</button>
		</div>
		<div class="socs-modal-body">
			<p class="socs-modal-plugin-name"></p>
			<p class="socs-modal-description">
				<?php esc_html_e( 'Add custom option names as a JSON array, or provide a complete JSON object with option names and values. If you provide option names only, they will be fetched from the database. If you provide values, those will be used directly.', 'smart-one-click-setup' ); ?>
			</p>
			<div class="socs-modal-examples">
				<p class="socs-modal-example">
					<strong><?php esc_html_e( 'Option Names Only (Array):', 'smart-one-click-setup' ); ?></strong><br>
					<code>["option_name_1", "option_name_2", "another_option"]</code>
				</p>
				<p class="socs-modal-example">
					<strong><?php esc_html_e( 'Option Names with Values (Object):', 'smart-one-click-setup' ); ?></strong><br>
					<code>{"option_name_1": "value1", "option_name_2": {"nested": "data"}}</code>
				</p>
			</div>
			<textarea id="socs-custom-options-textarea" class="socs-custom-options-textarea" rows="10" placeholder='["option_name_1", "option_name_2"]&#10;&#10;OR&#10;&#10;{"option_name_1": "value1", "option_name_2": "value2"}'></textarea>
			<div class="socs-modal-error" style="display: none;"></div>
		</div>
		<div class="socs-modal-footer">
			<button type="button" class="button button-secondary socs-modal-cancel"><?php esc_html_e( 'Cancel', 'smart-one-click-setup' ); ?></button>
			<button type="button" class="button button-primary socs-modal-save"><?php esc_html_e( 'Save Options', 'smart-one-click-setup' ); ?></button>
		</div>
	</div>
</div>

<?php
/**
 * Hook for adding the custom admin page footer
 */
Helpers::do_action( 'socs/plugin_page_footer' );
?>

