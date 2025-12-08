<?php
namespace SMARTOCS;

/**
 * Class for exporting demo content, widgets, customizer, and plugin settings.
 *
 * @package smartocs
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use ZipArchive;

/**
 * Exporter class.
 */
class Exporter {
	/**
	 * Export options.
	 *
	 * @var array
	 */
	private $export_options = array();

	/**
	 * Export directory path.
	 *
	 * @var string
	 */
	private $export_dir;

	/**
	 * Export directory URL.
	 *
	 * @var string
	 */
	private $export_url;

	/**
	 * Constructor.
	 *
	 * @param array $export_options Export options.
	 */
	public function __construct( $export_options = array() ) {
		$this->export_options = wp_parse_args( $export_options, array(
			'content'              => true,
			'widgets'              => true,
			'customizer'            => true,
			'plugins'               => array(),
			'elementor'             => false,
			'custom_plugin_options' => array(),
		) );

		$upload_dir = wp_upload_dir();
		
		// Check if upload directory is writable.
		if ( isset( $upload_dir['error'] ) && $upload_dir['error'] !== false ) {
			return;
		}

		$this->export_dir = trailingslashit( $upload_dir['basedir'] ) . 'smartocs-exports/';
		$this->export_url = trailingslashit( $upload_dir['baseurl'] ) . 'smartocs-exports/';

		// Create export directory if it doesn't exist.
		if ( ! file_exists( $this->export_dir ) ) {
			$created = wp_mkdir_p( $this->export_dir );
			if ( ! $created ) {
				// Directory creation failed, but we'll handle this in generate_export.
			}
		}
	}

	/**
	 * Generate export package.
	 *
	 * @return array|WP_Error Export file info or WP_Error on failure.
	 */
	public function generate_export() {
		// Increase memory limit and execution time only for this export operation.
		// Use WordPress functions only (WordPress coding standards compliant).
		if ( function_exists( 'wp_raise_memory_limit' ) ) {
			wp_raise_memory_limit( 'export' );
		}
		
		// Increase execution time limit using WordPress function (WordPress 5.5+).
		if ( function_exists( 'wp_max_execution_time' ) ) {
			$time_limit = Helpers::apply_filters( 'smartocs/set_time_limit_for_export', 300 );
			wp_max_execution_time( $time_limit );
		}

		// Check if export directory exists and is writable.
		if ( ! file_exists( $this->export_dir ) ) {
			$created = wp_mkdir_p( $this->export_dir );
			if ( ! $created ) {
				return new \WP_Error( 'export_dir_not_created', esc_html__( 'Failed to create export directory. Please check file permissions.', 'smart-one-click-setup' ) );
			}
		}

		// Verify directory is writable using WordPress filesystem API approach.
		$test_file = trailingslashit( $this->export_dir ) . '.smartocs-write-test-' . uniqid();
		$test_write = @file_put_contents( $test_file, 'test' ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		if ( false === $test_write ) {
			return new \WP_Error( 'export_dir_not_writable', esc_html__( 'Export directory is not writable. Please check file permissions.', 'smart-one-click-setup' ) );
		}
		wp_delete_file( $test_file );

		$export_files = array();

		// Export content (XML).
		if ( ! empty( $this->export_options['content'] ) ) {
			$content_file = $this->export_content();
			if ( is_wp_error( $content_file ) ) {
				return $content_file;
			}
			if ( ! empty( $content_file ) ) {
				$export_files['content'] = $content_file;
			}
		}

		// Export widgets.
		if ( ! empty( $this->export_options['widgets'] ) ) {
			$widget_file = $this->export_widgets();
			if ( is_wp_error( $widget_file ) ) {
				return $widget_file;
			}
			if ( ! empty( $widget_file ) ) {
				$export_files['widgets'] = $widget_file;
			}
		}

		// Export customizer.
		if ( ! empty( $this->export_options['customizer'] ) ) {
			$customizer_file = $this->export_customizer();
			if ( is_wp_error( $customizer_file ) ) {
				return $customizer_file;
			}
			if ( ! empty( $customizer_file ) ) {
				$export_files['customizer'] = $customizer_file;
			}
		}

		// Export plugin settings.
		if ( ! empty( $this->export_options['plugins'] ) && is_array( $this->export_options['plugins'] ) && ! empty( $this->export_options['plugins'] ) ) {
			$plugins_file = $this->export_plugin_settings();
			if ( is_wp_error( $plugins_file ) ) {
				// Don't fail if no plugin settings found, just skip it.
				if ( 'no_plugin_settings' !== $plugins_file->get_error_code() ) {
					return $plugins_file;
				}
			} elseif ( ! empty( $plugins_file ) ) {
				$export_files['plugins'] = $plugins_file;
			}
		}

		// Export Elementor Style Kit data.
		if ( ! empty( $this->export_options['elementor'] ) && class_exists( '\Elementor\Plugin' ) ) {
			$elementor_file = $this->export_elementor();
			if ( is_wp_error( $elementor_file ) ) {
				// Don't fail if no Elementor data found, just skip it.
				if ( 'no_elementor_data' !== $elementor_file->get_error_code() ) {
					return $elementor_file;
				}
			} elseif ( ! empty( $elementor_file ) ) {
				$export_files['elementor'] = $elementor_file;
			}
		}

		// Create ZIP archive.
		$zip_file = $this->create_zip_archive( $export_files );
		if ( is_wp_error( $zip_file ) ) {
			return $zip_file;
		}

		// Clean up individual files.
		foreach ( $export_files as $file ) {
			if ( ! empty( $file ) && file_exists( $file ) ) {
				wp_delete_file( $file );
			}
		}

		return array(
			'file_path' => $zip_file,
			'file_url'  => $this->export_url . basename( $zip_file ),
			'file_name' => basename( $zip_file ),
		);
	}


	/**
	 * Export WordPress content to XML.
	 *
	 * @return string|WP_Error File path or WP_Error.
	 */
	private function export_content() {
		// Use WP Content Exporter v2 library.
		if ( ! class_exists( '\BuildByCj\WPContentExporter2\Exporter' ) ) {
			return new \WP_Error(
				'export_library_not_available',
				esc_html__( 'WP Content Exporter v2 library is not available.', 'smart-one-click-setup' )
			);
		}

		$filename = 'content.xml';
		$filepath = $this->export_dir . $filename;

		// Prepare export options.
		$export_options = array(
			'content'            => 'all',
			'include_attachments' => true,
			'include_comments'    => true,
			'include_terms'       => true,
			'include_users'       => true,
			'output_file'         => $filepath,
		);

		// Allow filtering of export options.
		$export_options = Helpers::apply_filters( 'smartocs/export_content_options', $export_options );

		// Create exporter instance.
		$exporter = new \BuildByCj\WPContentExporter2\Exporter( $export_options );

		// Perform export.
		$export_data = $exporter->export();

		if ( is_wp_error( $export_data ) ) {
			return $export_data;
		}

		// Verify file was created.
		if ( ! file_exists( $filepath ) ) {
			return new \WP_Error( 'export_file_not_created', esc_html__( 'Content export file was not created.', 'smart-one-click-setup' ) );
		}

		return $filepath;
	}

	/**
	 * Export widgets.
	 *
	 * @return string|WP_Error File path or WP_Error.
	 */
	private function export_widgets() {
		global $wp_registered_widgets;

		$widget_data = array();
		$sidebars_widgets = get_option( 'sidebars_widgets' );

		if ( empty( $sidebars_widgets ) || ! is_array( $sidebars_widgets ) ) {
			$sidebars_widgets = array();
		}

		foreach ( $sidebars_widgets as $sidebar_id => $widgets ) {
			if ( 'wp_inactive_widgets' === $sidebar_id || empty( $widgets ) || ! is_array( $widgets ) ) {
				continue;
			}

			foreach ( $widgets as $widget_id ) {
				if ( ! isset( $wp_registered_widgets[ $widget_id ] ) ) {
					continue;
				}

				$widget = $wp_registered_widgets[ $widget_id ];
				
				// Check if widget callback is valid.
				if ( ! isset( $widget['callback'] ) || ! is_array( $widget['callback'] ) || empty( $widget['callback'][0] ) ) {
					continue;
				}

				if ( ! isset( $widget_data[ $sidebar_id ] ) ) {
					$widget_data[ $sidebar_id ] = array();
				}

				// Get widget settings.
				$id_base = isset( $widget['callback'][0]->id_base ) ? $widget['callback'][0]->id_base : '';
				if ( empty( $id_base ) ) {
					continue;
				}

				// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WordPress core option format.
				$widget_settings = get_option( 'widget_' . $id_base );
				$widget_number = isset( $widget['params'][0]['number'] ) ? $widget['params'][0]['number'] : '';

				// Export format: widget_instance_id as key, widget settings as value.
				// This matches the format expected by WidgetImporter.
				if ( ! empty( $widget_number ) && isset( $widget_settings[ $widget_number ] ) ) {
					$widget_data[ $sidebar_id ][ $widget_id ] = $widget_settings[ $widget_number ];
				}
			}
		}

		// Allow filtering of widget data.
		$widget_data = Helpers::apply_filters( 'smartocs/export_widget_data', $widget_data );

		$filename = 'widgets.json';
		$filepath = $this->export_dir . $filename;

		// Always create the file, even if empty (for consistency).
		$json_data = wp_json_encode( $widget_data, JSON_PRETTY_PRINT );
		if ( false === $json_data ) {
			$json_data = wp_json_encode( array() );
		}

		$result = Helpers::write_to_file( $json_data, $filepath );
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return $filepath;
	}

	/**
	 * Export customizer settings.
	 *
	 * @return string|WP_Error File path or WP_Error.
	 */
	private function export_customizer() {
		$theme = get_stylesheet();
		$mods = get_theme_mods();

		$data = array(
			'theme' => $theme,
			'mods'  => $mods ? $mods : array(),
		);

		// Export WordPress reading options (home page settings).
		// These are stored as options, not theme mods, but are important for customizer configuration.
		$options = array();
		
		// Home page settings.
		$show_on_front = get_option( 'show_on_front' );
		if ( false !== $show_on_front ) {
			$options['show_on_front'] = $show_on_front;
			
			// If front page is set to a static page, also export the page ID.
			if ( 'page' === $show_on_front ) {
				$page_on_front = get_option( 'page_on_front' );
				if ( false !== $page_on_front && ! empty( $page_on_front ) ) {
					$options['page_on_front'] = $page_on_front;
				}
			}
		}
		
		// Blog page setting (posts page).
		$page_for_posts = get_option( 'page_for_posts' );
		if ( false !== $page_for_posts && ! empty( $page_for_posts ) ) {
			$options['page_for_posts'] = $page_for_posts;
		}

		// Add options to data if we have any.
		if ( ! empty( $options ) ) {
			$data['options'] = $options;
		}

		// Allow filtering of customizer data.
		$data = Helpers::apply_filters( 'smartocs/export_customizer_data', $data );

		$filename = 'customizer.dat';
		$filepath = $this->export_dir . $filename;

		$result = Helpers::write_to_file( serialize( $data ), $filepath );
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return $filepath;
	}

	/**
	 * Export plugin settings.
	 * Exports plugin settings in the format: { "plugin_slug": { "option_name": "value", ... } }
	 *
	 * @return string|WP_Error File path or WP_Error.
	 */
	private function export_plugin_settings() {

		$plugin_settings = array();

		foreach ( $this->export_options['plugins'] as $plugin_slug ) {
			$settings = $this->get_plugin_settings( $plugin_slug );
			if ( ! empty( $settings ) && is_array( $settings ) ) {
				$plugin_settings[ $plugin_slug ] = $settings;
			}
		}

		if ( empty( $plugin_settings ) ) {
			return new \WP_Error( 'no_plugin_settings', esc_html__( 'No plugin settings found to export.', 'smart-one-click-setup' ) );
		}

		// Allow filtering of plugin settings.
		$plugin_settings = Helpers::apply_filters( 'smartocs/export_plugin_settings_data', $plugin_settings );

		$filename = 'plugin-settings.json';
		$filepath = $this->export_dir . $filename;

		// Encode with proper flags to handle Unicode and ensure proper formatting.
		$json_flags = JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;
		$json_data = wp_json_encode( $plugin_settings, $json_flags );

		// Check for JSON encoding errors.
		if ( false === $json_data ) {
			return new \WP_Error(
				'plugin_settings_json_encode_failed',
				sprintf( /* translators: %s: JSON error message */
					esc_html__( 'Failed to encode plugin settings to JSON. Error: %s', 'smart-one-click-setup' ),
					json_last_error_msg()
				)
			);
		}

		$result = Helpers::write_to_file( $json_data, $filepath );
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return $filepath;
	}

	/**
	 * Get plugin settings.
	 * Returns an array in the format: { "option_name": "option_value", ... }
	 * This format matches what the importer expects - option_name as keys, option_value as values.
	 *
	 * @param string $plugin_slug Plugin slug.
	 * @return array Plugin settings array with option_name as keys and unserialized option_value as values.
	 */
	private function get_plugin_settings( $plugin_slug ) {
		$settings = array();

		// Allow developers to hook into this to export their plugin settings.
		$settings = Helpers::apply_filters( 'smartocs/export_plugin_' . $plugin_slug . '_settings', $settings );

		// Get custom plugin options if provided.
		$custom_options_data = null;
		if ( ! empty( $this->export_options['custom_plugin_options'][ $plugin_slug ] ) ) {
			$custom_options_data = $this->export_options['custom_plugin_options'][ $plugin_slug ];
		}

		// Check if custom options are in object format (with values) or array format (names only).
		$custom_options_is_object = false;
		$custom_options = array();
		if ( is_array( $custom_options_data ) && isset( $custom_options_data['options'] ) ) {
			$custom_options_is_object = ! empty( $custom_options_data['is_object'] );
			$custom_options = $custom_options_data['options'];
		} elseif ( is_array( $custom_options_data ) ) {
			$custom_options = $custom_options_data;
		}

		// If custom options are provided in object format (with values), use them directly.
		if ( $custom_options_is_object && is_array( $custom_options ) && ! empty( $custom_options ) ) {
			// Use the provided values directly.
			foreach ( $custom_options as $option_name => $option_value ) {
				// Sanitize option name.
				$option_name = sanitize_key( $option_name );
				if ( empty( $option_name ) ) {
					continue;
				}
				// Store the value as-is (it's already provided by the user).
				$settings[ $option_name ] = $option_value;
			}
		}

		// Default: try to get all options with plugin prefix.
		if ( empty( $settings ) || ! is_array( $settings ) ) {
			global $wpdb;
			
			// If custom options are provided as array (names only), fetch from database.
			if ( ! $custom_options_is_object && ! empty( $custom_options ) && is_array( $custom_options ) ) {
				// Fetch custom options from database.
				$placeholders = implode( ',', array_fill( 0, count( $custom_options ), '%s' ) );
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$options = $wpdb->get_results(
					$wpdb->prepare( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
						"SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name IN ($placeholders)", // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
						$custom_options
					)
				);

				// Also get options with plugin prefix (default behavior).
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$prefix_options = $wpdb->get_results(
					$wpdb->prepare(
						"SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE %s",
						$wpdb->esc_like( $plugin_slug ) . '%'
					)
				);

				// Merge both results, prioritizing custom options.
				$all_options = array();
				foreach ( $prefix_options as $option ) {
					$all_options[ $option->option_name ] = $option;
				}
				foreach ( $options as $option ) {
					$all_options[ $option->option_name ] = $option;
				}
				$options = array_values( $all_options );
			} else {
				// Default: get all options with plugin prefix.
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$options = $wpdb->get_results(
					$wpdb->prepare(
						"SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE %s",
						$wpdb->esc_like( $plugin_slug ) . '%'
					)
				);
			}

			foreach ( $options as $option ) {
				// Skip if already set from object format custom options.
				if ( isset( $settings[ $option->option_name ] ) ) {
					continue;
				}

				// Unserialize the option value if it's serialized.
				// This ensures the exported JSON contains the actual data structure, not serialized strings.
				$option_value = maybe_unserialize( $option->option_value );
				
				// Store as option_name => option_value for proper import format.
				// The importer expects: foreach ( $settings as $option_name => $option_value )
				$settings[ $option->option_name ] = $option_value;
			}
		} elseif ( ! $custom_options_is_object && ! empty( $custom_options ) && is_array( $custom_options ) ) {
			// If settings were provided via filter, still add custom options (array format - names only).
			global $wpdb;
			$placeholders = implode( ',', array_fill( 0, count( $custom_options ), '%s' ) );
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$custom_option_results = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name IN ($placeholders)", //phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					$custom_options
				)
			);

			foreach ( $custom_option_results as $option ) {
				if ( ! isset( $settings[ $option->option_name ] ) ) {
					$option_value = maybe_unserialize( $option->option_value );
					$settings[ $option->option_name ] = $option_value;
				}
			}
		}

		return $settings;
	}

	/**
	 * Export Elementor Site Kit data.
	 * Exports only the Site Kit settings (colors, typography, global styles).
	 * Page and template data are not exported as they are handled by the XML content export.
	 *
	 * @return string|WP_Error File path or WP_Error.
	 */
	private function export_elementor() {
		if ( ! class_exists( '\Elementor\Plugin' ) ) {
			return new \WP_Error( 'elementor_not_active', esc_html__( 'Elementor is not active.', 'smart-one-click-setup' ) );
		}

		$elementor_data = array();

		// Export Elementor Site Kit settings only.
		$kit_id = \Elementor\Plugin::$instance->kits_manager->get_active_id();
		if ( $kit_id ) {
			$kit_settings = get_post_meta( $kit_id, '_elementor_page_settings', true );
			if ( $kit_settings ) {
				$elementor_data['kit_settings'] = $kit_settings;
			}
		}

		// Allow filtering of Elementor data.
		$elementor_data = Helpers::apply_filters( 'smartocs/export_elementor_data', $elementor_data );

		if ( empty( $elementor_data ) ) {
			return new \WP_Error( 'no_elementor_data', esc_html__( 'No Elementor data found to export.', 'smart-one-click-setup' ) );
		}

		$filename = 'elementor.json';
		$filepath = $this->export_dir . $filename;

		$result = Helpers::write_to_file( wp_json_encode( $elementor_data, JSON_PRETTY_PRINT ), $filepath );
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return $filepath;
	}

	/**
	 * Create ZIP archive from export files.
	 *
	 * @param array $files Array of file paths.
	 * @return string|WP_Error ZIP file path or WP_Error.
	 */
	private function create_zip_archive( $files ) {
		if ( ! class_exists( 'ZipArchive' ) ) {
			return new \WP_Error( 'zip_not_supported', esc_html__( 'ZIP archive is not supported on this server.', 'smart-one-click-setup' ) );
		}

		// Filter out empty files.
		$valid_files = array();
		foreach ( $files as $type => $file ) {
			if ( ! empty( $file ) && file_exists( $file ) && is_readable( $file ) ) {
				$valid_files[ $type ] = $file;
			}
		}

		// If no valid files, return error.
		if ( empty( $valid_files ) ) {
			return new \WP_Error( 'no_files_to_export', esc_html__( 'No files to export. Please select at least one export option.', 'smart-one-click-setup' ) );
		}

		$zip_filename = 'demo-export.zip';
		$zip_filepath = $this->export_dir . $zip_filename;

		$zip = new ZipArchive();
		$zip_result = $zip->open( $zip_filepath, ZipArchive::CREATE | ZipArchive::OVERWRITE );
		if ( $zip_result !== true ) {
			/* translators: %d: The error code. */
			return new \WP_Error( 'zip_create_failed', sprintf( esc_html__( 'Failed to create ZIP archive. Error code: %d', 'smart-one-click-setup' ), $zip_result ) );
		}

		foreach ( $valid_files as $type => $file ) {
			if ( ! $zip->addFile( $file, basename( $file ) ) ) {
				$zip->close();
				wp_delete_file( $zip_filepath );
				/* translators: %s: The filename. */
				return new \WP_Error( 'zip_add_file_failed', sprintf( esc_html__( 'Failed to add file to ZIP: %s', 'smart-one-click-setup' ), basename( $file ) ) );
			}
		}

		// Add export info file.
		$info = array(
			'export_date'    => current_time( 'mysql' ),
			'site_url'       => get_site_url(),
			'wp_version'     => get_bloginfo( 'version' ),
			'export_version' => defined( 'SMARTOCS_VERSION' ) ? SMARTOCS_VERSION : '1.0.0',
		);
		$info_file = $this->export_dir . 'export-info.json';
		$info_result = Helpers::write_to_file( wp_json_encode( $info, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ), $info_file );
		if ( ! is_wp_error( $info_result ) && file_exists( $info_file ) ) {
			$zip->addFile( $info_file, 'export-info.json' );
		}

		// Add plugin info text file.
		$smartocs_plugin_data = Helpers::get_plugin_data( SMARTOCS_PATH . 'smart-one-click-setup.php' );
		
		// Strip HTML tags and decode HTML entities from plugin data.
		$plugin_name = ! empty( $smartocs_plugin_data['Name'] ) ? wp_strip_all_tags( $smartocs_plugin_data['Name'] ) : '';
		$plugin_version = defined( 'SMARTOCS_VERSION' ) ? SMARTOCS_VERSION : ( ! empty( $smartocs_plugin_data['Version'] ) ? wp_strip_all_tags( $smartocs_plugin_data['Version'] ) : '' );
		$plugin_uri = ! empty( $smartocs_plugin_data['PluginURI'] ) ? esc_url_raw( $smartocs_plugin_data['PluginURI'] ) : '';
		$author = ! empty( $smartocs_plugin_data['Author'] ) ? wp_strip_all_tags( html_entity_decode( $smartocs_plugin_data['Author'], ENT_QUOTES | ENT_HTML5, 'UTF-8' ) ) : '';
		$author_uri = ! empty( $smartocs_plugin_data['AuthorURI'] ) ? esc_url_raw( $smartocs_plugin_data['AuthorURI'] ) : '';
		$description = ! empty( $smartocs_plugin_data['Description'] ) ? wp_strip_all_tags( html_entity_decode( $smartocs_plugin_data['Description'], ENT_QUOTES | ENT_HTML5, 'UTF-8' ) ) : '';
		$license = ! empty( $smartocs_plugin_data['License'] ) ? wp_strip_all_tags( $smartocs_plugin_data['License'] ) : '';
		$license_uri = ! empty( $smartocs_plugin_data['LicenseURI'] ) ? esc_url_raw( $smartocs_plugin_data['LicenseURI'] ) : '';
		
		$plugin_info = sprintf(
			"This demo data zip is exported by Smart One Click Setup, Plugin details are below:\n" .
			"\n\n\n" .
			"SMART ONE CLICK SETUP - PLUGIN INFORMATION\n" .
			"==========================================\n" .
			"\n\n" .
			"Plugin Name: %s\n" .
			"Plugin Version: %s\n" .
			"Plugin URI: %s\n" .
			"Author: %s\n" .
			"Author URI: %s\n" .
			"\n\n" .
			"Description: %s\n" .
			"\n\n" .
			"By %s.\n" .
			"License: %s\n" .
			"License URI: %s\n" .
			"\n" .
			"Export Date: %s\n" .
			"Export Plugin Version: %s\n",
			$plugin_name,
			$plugin_version,
			$plugin_uri,
			$author,
			$author_uri,
			$description,
			$author,
			$license,
			$license_uri,
			current_time( 'mysql' ),
			defined( 'SMARTOCS_VERSION' ) ? SMARTOCS_VERSION : ( ! empty( $smartocs_plugin_data['Version'] ) ? wp_strip_all_tags( $smartocs_plugin_data['Version'] ) : '' )
		);
		$plugin_info_file = $this->export_dir . 'info.txt';
		$plugin_info_result = Helpers::write_to_file( $plugin_info, $plugin_info_file );
		if ( ! is_wp_error( $plugin_info_result ) && file_exists( $plugin_info_file ) ) {
			$zip->addFile( $plugin_info_file, 'info.txt' );
		}

		if ( ! $zip->close() ) {
			wp_delete_file( $zip_filepath );
			return new \WP_Error( 'zip_close_failed', esc_html__( 'Failed to close ZIP archive.', 'smart-one-click-setup' ) );
		}

		// Clean up info files.
		if ( file_exists( $info_file ) ) {
			wp_delete_file( $info_file );
		}
		if ( file_exists( $plugin_info_file ) ) {
			wp_delete_file( $plugin_info_file );
		}

		// Verify ZIP file was created.
		if ( ! file_exists( $zip_filepath ) ) {
			return new \WP_Error( 'zip_file_not_created', esc_html__( 'ZIP file was not created successfully.', 'smart-one-click-setup' ) );
		}

		return $zip_filepath;
	}
}

