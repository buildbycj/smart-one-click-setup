<?php
namespace SMARTOCS;
/**
 * The Smart Export page view.
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

<div class="smartocs smartocs--export">

	<?php echo wp_kses_post( ViewHelpers::plugin_header_output() ); ?>

	<div class="smartocs__content-container">

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

		<div class="smartocs__admin-notices js-smartocs-admin-notices-container"></div>

		<?php
		// Build plugin intro text.
		$smartocs_plugin_intro_text = '<div class="smartocs__intro-text">
				<p class="about-description">
				' . esc_html__( 'Export your WordPress site content, widgets, customizer settings, plugin settings, and Elementor data into a single ZIP file.', 'smart-one-click-setup' ) . '
				' . esc_html__( 'This exported package can then be imported on another WordPress site using Smart Import.', 'smart-one-click-setup' ) . '
				</p>
		</div>';

		// Display the plugin intro text (can be replaced with custom text through the filter below).
		echo wp_kses_post( Helpers::apply_filters( 'smartocs/plugin_intro_text', $smartocs_plugin_intro_text ) );
		?>

		<?php $smartocs_theme = wp_get_theme(); ?>

		<div class="smartocs__content-container-content">
			<div class="smartocs__content-container-content--main">
				<div class="smartocs-export-content js-smartocs-export-content">
					<div class="smartocs__file-upload-container">
						<div class="smartocs__file-upload-container--header">
							<h2><?php esc_html_e( 'Smart Export', 'smart-one-click-setup' ); ?></h2>
						</div>

						<form id="smartocs-export-form" class="smartocs-export-form">
							<div class="smartocs__file-upload-container-items">
								<?php $smartocs_first_row_class = class_exists( 'ReduxFramework' ) ? 'four' : 'three'; ?>
								<div class="smartocs__file-upload smartocs__card smartocs__card--<?php echo esc_attr( $smartocs_first_row_class ); ?>">
									<div class="smartocs__card-content">
										<label for="smartocs-export-content">
											<div class="smartocs-icon-container">
												<img src="<?php echo esc_url( SMARTOCS_URL . 'assets/images/icons/content.svg' ); ?>" class="smartocs-icon--content" alt="<?php esc_attr_e( 'Content export icon', 'smart-one-click-setup' ); ?>">
											</div>
											<h3><?php esc_html_e( 'Export Content', 'smart-one-click-setup' ); ?></h3>
											<p><?php esc_html_e( 'Export all posts, pages, comments, custom fields, categories, and tags.', 'smart-one-click-setup' ); ?></p>
										</label>
										<a href="https://smartocs.buildbycj.com/#user-guide" target="_blank" rel="noopener noreferrer" class="smartocs__card-content-info">
											<img src="<?php echo esc_url( SMARTOCS_URL . 'assets/images/icons/info-circle.svg' ); ?>" alt="<?php esc_attr_e( 'Info icon', 'smart-one-click-setup' ); ?>">
										</a>
									</div>
									<div class="smartocs__card-footer">
										<label for="smartocs-export-content" class="button button-primary">
											<input type="checkbox" id="smartocs-export-content" name="export_content" value="1" checked class="smartocs-hide-input">
											<span class="js-smartocs-export-checkbox-label"><?php esc_html_e( 'Selected', 'smart-one-click-setup' ); ?></span>
										</label>
									</div>
								</div>

								<div class="smartocs__file-upload smartocs__card smartocs__card--<?php echo esc_attr( $smartocs_first_row_class ); ?>">
									<div class="smartocs__card-content">
										<label for="smartocs-export-widgets">
											<div class="smartocs-icon-container">
												<img src="<?php echo esc_url( SMARTOCS_URL . 'assets/images/icons/widgets.svg' ); ?>" class="smartocs-icon--widgets" alt="<?php esc_attr_e( 'Widgets export icon', 'smart-one-click-setup' ); ?>">
											</div>
											<h3><?php esc_html_e( 'Export Widgets', 'smart-one-click-setup' ); ?></h3>
											<p><?php esc_html_e( 'Export all widget settings and sidebar configurations.', 'smart-one-click-setup' ); ?></p>
										</label>
										<a href="https://smartocs.buildbycj.com/#user-guide" target="_blank" rel="noopener noreferrer" class="smartocs__card-content-info">
											<img src="<?php echo esc_url( SMARTOCS_URL . 'assets/images/icons/info-circle.svg' ); ?>" alt="<?php esc_attr_e( 'Info icon', 'smart-one-click-setup' ); ?>">
										</a>
									</div>
									<div class="smartocs__card-footer">
										<label for="smartocs-export-widgets" class="button button-primary">
											<input type="checkbox" id="smartocs-export-widgets" name="export_widgets" value="1" checked class="smartocs-hide-input">
											<span class="js-smartocs-export-checkbox-label"><?php esc_html_e( 'Selected', 'smart-one-click-setup' ); ?></span>
										</label>
									</div>
								</div>

								<div class="smartocs__file-upload smartocs__card smartocs__card--<?php echo esc_attr( $smartocs_first_row_class ); ?>">
									<div class="smartocs__card-content">
										<label for="smartocs-export-customizer">
											<div class="smartocs-icon-container">
												<img src="<?php echo esc_url( SMARTOCS_URL . 'assets/images/icons/brush.svg' ); ?>" class="smartocs-icon--brush" alt="<?php esc_attr_e( 'Customizer export icon', 'smart-one-click-setup' ); ?>">
											</div>
											<h3><?php esc_html_e( 'Export Customizer', 'smart-one-click-setup' ); ?></h3>
											<p><?php esc_html_e( 'Export theme customizer settings.', 'smart-one-click-setup' ); ?></p>
										</label>
										<a href="https://smartocs.buildbycj.com/#user-guide" target="_blank" rel="noopener noreferrer" class="smartocs__card-content-info">
											<img src="<?php echo esc_url( SMARTOCS_URL . 'assets/images/icons/info-circle.svg' ); ?>" alt="<?php esc_attr_e( 'Info icon', 'smart-one-click-setup' ); ?>">
										</a>
									</div>
									<div class="smartocs__card-footer">
										<label for="smartocs-export-customizer" class="button button-primary">
											<input type="checkbox" id="smartocs-export-customizer" name="export_customizer" value="1" checked class="smartocs-hide-input">
											<span class="js-smartocs-export-checkbox-label"><?php esc_html_e( 'Selected', 'smart-one-click-setup' ); ?></span>
										</label>
									</div>
								</div>

								<?php if ( class_exists( '\Elementor\Plugin' ) ) : ?>
								<div class="smartocs__file-upload smartocs__card smartocs__card--<?php echo esc_attr( $smartocs_first_row_class ); ?>">
									<div class="smartocs__card-content">
										<label for="smartocs-export-elementor">
											<div class="smartocs-icon-container">
												<img src="<?php echo esc_url( SMARTOCS_URL . 'assets/images/icons/elementor.svg' ); ?>" class="smartocs-icon--layout" alt="<?php esc_attr_e( 'Elementor export icon', 'smart-one-click-setup' ); ?>">
											</div>
											<h3><?php esc_html_e( 'Export Elementor Style Kit', 'smart-one-click-setup' ); ?></h3>
											<p><?php esc_html_e( 'Export Elementor Style Kit style kit settings.', 'smart-one-click-setup' ); ?></p>
										</label>
										<a href="https://smartocs.buildbycj.com/#user-guide" target="_blank" rel="noopener noreferrer" class="smartocs__card-content-info">
											<img src="<?php echo esc_url( SMARTOCS_URL . 'assets/images/icons/info-circle.svg' ); ?>" alt="<?php esc_attr_e( 'Info icon', 'smart-one-click-setup' ); ?>">
										</a>
									</div>
									<div class="smartocs__card-footer">
										<label for="smartocs-export-elementor" class="button button-secondary">
											<input type="checkbox" id="smartocs-export-elementor" name="export_elementor" value="1" class="smartocs-hide-input">
											<span class="js-smartocs-export-checkbox-label"><?php esc_html_e( 'Select', 'smart-one-click-setup' ); ?></span>
										</label>
									</div>
								</div>
								<?php endif; ?>

								<div class="smartocs__file-upload smartocs__card smartocs__card--<?php echo esc_attr( $smartocs_first_row_class ); ?>">
									<div class="smartocs__card-content">
										<label>
											<div class="smartocs-icon-container">
												<img src="<?php echo esc_url( SMARTOCS_URL . 'assets/images/icons/plugin-settings.svg' ); ?>" class="smartocs-icon--plugins" alt="<?php esc_attr_e( 'Plugins export icon', 'smart-one-click-setup' ); ?>">
											</div>
											<h3><?php esc_html_e( 'Export Plugin Settings', 'smart-one-click-setup' ); ?></h3>
											<p><?php esc_html_e( 'Select which plugin settings to export.', 'smart-one-click-setup' ); ?></p>
										</label>
										<a href="https://smartocs.buildbycj.com/#user-guide" target="_blank" rel="noopener noreferrer" class="smartocs__card-content-info">
											<img src="<?php echo esc_url( SMARTOCS_URL . 'assets/images/icons/info-circle.svg' ); ?>" alt="<?php esc_attr_e( 'Info icon', 'smart-one-click-setup' ); ?>">
										</a>
									</div>
									<div class="smartocs__card-footer">
										<div class="smartocs-export-plugins-list">
											<?php
											// Use custom get_plugins implementation that doesn't require core files.
											$smartocs_plugin_list = array();

											// Get all installed plugins using custom helper function.
											$smartocs_all_plugins = Helpers::get_plugins();

												// Get the list of active plugins (returns array of paths like 'folder/file.php')
												$smartocs_active_plugins = get_option( 'active_plugins', array() );

												// Loop through active plugins and match them with the data from get_plugins()
												foreach ( $smartocs_active_plugins as $smartocs_plugin_file_path ) {

													// Check if the active plugin exists in the installed plugins list
													if ( isset( $smartocs_all_plugins[ $smartocs_plugin_file_path ] ) ) {
														$smartocs_plugin_data = $smartocs_all_plugins[ $smartocs_plugin_file_path ];

														if ( ! empty( $smartocs_plugin_data['Name'] ) ) {
															// Calculate slug similar to your previous logic
															$smartocs_plugin_slug = dirname( $smartocs_plugin_file_path );

															// Handle single-file plugins (where dirname is '.')
															if ( $smartocs_plugin_slug === '.' ) {
																$smartocs_plugin_slug = $smartocs_plugin_file_path;
															}

															$smartocs_plugin_list[ $smartocs_plugin_slug ] = $smartocs_plugin_data['Name'];
														}
													}
												}

											// Allow filtering of plugin list.
											$smartocs_plugin_list = Helpers::apply_filters( 'smartocs/export_plugin_list', $smartocs_plugin_list );

											if ( ! empty( $smartocs_plugin_list ) ) :
												foreach ( $smartocs_plugin_list as $smartocs_slug => $smartocs_name ) :
													?>
													<div class="smartocs-export-plugin-item-wrapper">
														<label class="smartocs-export-plugin-item">
															<input type="checkbox" name="export_plugins[]" value="<?php echo esc_attr( $smartocs_slug ); ?>" class="smartocs-export-plugin-checkbox" data-plugin-slug="<?php echo esc_attr( $smartocs_slug ); ?>">
															<span><?php echo esc_html( $smartocs_name ); ?></span>
														</label>
														<button type="button" class="smartocs-export-plugin-custom-options-btn" data-plugin-slug="<?php echo esc_attr( $smartocs_slug ); ?>" data-plugin-name="<?php echo esc_attr( $smartocs_name ); ?>" title="<?php esc_attr_e( 'Add Custom Options', 'smart-one-click-setup' ); ?>">
															<span class="dashicons dashicons-admin-generic"></span>
														</button>
													</div>
													<?php
												endforeach;
											else :
												?>
												<p class="smartocs-export-no-plugins"><?php esc_html_e( 'No plugins available for export.', 'smart-one-click-setup' ); ?></p>
												<?php
											endif;
											?>
										</div>
									</div>
								</div>
							</div>

							<div class="smartocs__file-upload-container--footer">
							<a href="<?php echo esc_url( $this->get_plugin_settings_url() ); ?>" class="smartocs__button button">
								
								<span><?php esc_html_e( 'Go Back', 'smart-one-click-setup' ); ?></span>
							</a>
							<button type="submit" class="smartocs__button button button-hero button-primary js-smartocs-start-export">
								<?php esc_html_e( 'Generate Export', 'smart-one-click-setup' ); ?>
							</button>
						</div>
					</form>
					</div>
				</div>

				<div class="smartocs-exporting js-smartocs-exporting">
					<div class="smartocs-exporting-header">
						<h2><?php esc_html_e( 'Exporting...', 'smart-one-click-setup' ); ?></h2>
						<p><?php esc_html_e( 'Please wait while we prepare your export package.', 'smart-one-click-setup' ); ?></p>
					</div>
					<div class="smartocs-exporting-content">
						<img class="smartocs-exporting-content-exporting" src="<?php echo esc_url( SMARTOCS_URL . 'assets/images/importing.svg' ); ?>" alt="<?php esc_attr_e( 'Exporting animation', 'smart-one-click-setup' ); ?>">
					</div>
				</div>

				<div class="smartocs-exported js-smartocs-exported">
					<div class="smartocs-exported-header">
						<h2 class="js-smartocs-export-response-title"><?php esc_html_e( 'Export Complete!', 'smart-one-click-setup' ); ?></h2>
						<div class="js-smartocs-export-response-subtitle">
							<p><?php esc_html_e( 'Your export package has been generated successfully.', 'smart-one-click-setup' ); ?></p>
						</div>
					</div>
					<div class="smartocs-exported-content">
						<div class="smartocs__response js-smartocs-export-response"></div>
					</div>
					<div class="smartocs-exported-footer">
						<a href="<?php echo esc_url( admin_url( 'themes.php?page=smartocs-smart-export' ) ); ?>" class="button button-primary button-hero">
							<?php esc_html_e( 'Go Back', 'smart-one-click-setup' ); ?>
						</a>
						<a href="#" class="button button-primary button-hero js-smartocs-download-export" target="_blank">
							<?php esc_html_e( 'Download Export', 'smart-one-click-setup' ); ?>
						</a>
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

<!-- Custom Plugin Options Modal -->
<div id="smartocs-custom-options-modal" class="smartocs-modal">
	<div class="smartocs-modal-overlay"></div>
	<div class="smartocs-modal-content">
		<div class="smartocs-modal-header">
			<h2 class="smartocs-modal-title"><?php esc_html_e( 'Add Custom Options', 'smart-one-click-setup' ); ?></h2>
			<button type="button" class="smartocs-modal-close" aria-label="<?php esc_attr_e( 'Close', 'smart-one-click-setup' ); ?>">
				<span class="dashicons dashicons-no-alt"></span>
			</button>
		</div>
		<div class="smartocs-modal-body">
			<p class="smartocs-modal-plugin-name"></p>
			<p class="smartocs-modal-description">
				<?php esc_html_e( 'Add custom option names as a JSON array, or provide a complete JSON object with option names and values. If you provide option names only, they will be fetched from the database. If you provide values, those will be used directly.', 'smart-one-click-setup' ); ?>
			</p>
			<div class="smartocs-modal-examples">
				<p class="smartocs-modal-example">
					<strong><?php esc_html_e( 'Option Names Only (Array):', 'smart-one-click-setup' ); ?></strong><br>
					<code>["option_name_1", "option_name_2", "another_option"]</code>
				</p>
				<p class="smartocs-modal-example">
					<strong><?php esc_html_e( 'Option Names with Values (Object):', 'smart-one-click-setup' ); ?></strong><br>
					<code>{"option_name_1": "value1", "option_name_2": {"nested": "data"}}</code>
				</p>
			</div>
			<textarea id="smartocs-custom-options-textarea" class="smartocs-custom-options-textarea" rows="10" placeholder='["option_name_1", "option_name_2"]&#10;&#10;OR&#10;&#10;{"option_name_1": "value1", "option_name_2": "value2"}'></textarea>
			<div class="smartocs-modal-error"></div>
		</div>
		<div class="smartocs-modal-footer">
			<button type="button" class="button button-secondary smartocs-modal-cancel"><?php esc_html_e( 'Cancel', 'smart-one-click-setup' ); ?></button>
			<button type="button" class="button button-primary smartocs-modal-save"><?php esc_html_e( 'Save Options', 'smart-one-click-setup' ); ?></button>
		</div>
	</div>
</div>

<?php
/**
 * Hook for adding the custom admin page footer
 */
Helpers::do_action( 'smartocs/plugin_page_footer' );
?>

