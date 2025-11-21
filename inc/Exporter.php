<?php
/**
 * Class for exporting demo content, widgets, customizer, and plugin settings.
 *
 * @package socs
 */

namespace SOCS;

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
			'content'     => true,
			'widgets'     => true,
			'customizer'  => true,
			'plugins'     => array(),
			'elementor'   => false,
		) );

		$upload_dir = wp_upload_dir();
		
		// Check if upload directory is writable.
		if ( isset( $upload_dir['error'] ) && $upload_dir['error'] !== false ) {
			return;
		}

		$this->export_dir = trailingslashit( $upload_dir['basedir'] ) . 'socs-exports/';
		$this->export_url = trailingslashit( $upload_dir['baseurl'] ) . 'socs-exports/';

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
		// Check if export directory exists and is writable.
		if ( ! file_exists( $this->export_dir ) ) {
			$created = wp_mkdir_p( $this->export_dir );
			if ( ! $created ) {
				return new \WP_Error( 'export_dir_not_created', esc_html__( 'Failed to create export directory. Please check file permissions.', 'smart-one-click-setup' ) );
			}
		}

		// Verify directory is accessible using WP_Filesystem if available.
		global $wp_filesystem;
		if ( ! $wp_filesystem && ! function_exists( 'WP_Filesystem' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}
		if ( ! $wp_filesystem ) {
			WP_Filesystem();
		}
		if ( $wp_filesystem && ! $wp_filesystem->is_writable( $this->export_dir ) ) {
			return new \WP_Error( 'export_dir_not_writable', esc_html__( 'Export directory is not writable. Please check file permissions.', 'smart-one-click-setup' ) );
		}

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
		require_once ABSPATH . 'wp-admin/includes/export.php';

		$args = array(
			'content' => 'all',
		);

		// Allow filtering of export arguments.
		$args = Helpers::apply_filters( 'socs/export_content_args', $args );

		// Capture output from export_wp function.
		ob_start();
		export_wp( $args );
		$export_data = ob_get_clean();

		if ( empty( $export_data ) ) {
			return new \WP_Error( 'export_failed', esc_html__( 'Content export failed. No data was exported.', 'smart-one-click-setup' ) );
		}

		$filename = 'content.xml';
		$filepath = $this->export_dir . $filename;

		$result = Helpers::write_to_file( $export_data, $filepath );
		if ( is_wp_error( $result ) ) {
			return $result;
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

				$widget_settings = get_option( 'widget_' . $id_base );
				$widget_number = isset( $widget['params'][0]['number'] ) ? $widget['params'][0]['number'] : '';

				if ( ! empty( $widget_number ) && isset( $widget_settings[ $widget_number ] ) ) {
					$widget_data[ $sidebar_id ][] = array(
						'id_base'   => $id_base,
						'widget_id' => $widget_id,
						'settings'  => $widget_settings[ $widget_number ],
					);
				}
			}
		}

		// Allow filtering of widget data.
		$widget_data = Helpers::apply_filters( 'socs/export_widget_data', $widget_data );

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

		// Allow filtering of customizer data.
		$data = Helpers::apply_filters( 'socs/export_customizer_data', $data );

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
	 *
	 * @return string|WP_Error File path or WP_Error.
	 */
	private function export_plugin_settings() {
		$plugin_settings = array();

		foreach ( $this->export_options['plugins'] as $plugin_slug ) {
			$settings = $this->get_plugin_settings( $plugin_slug );
			if ( ! empty( $settings ) ) {
				$plugin_settings[ $plugin_slug ] = $settings;
			}
		}

		if ( empty( $plugin_settings ) ) {
			return new \WP_Error( 'no_plugin_settings', esc_html__( 'No plugin settings found to export.', 'smart-one-click-setup' ) );
		}

		// Allow filtering of plugin settings.
		$plugin_settings = Helpers::apply_filters( 'socs/export_plugin_settings_data', $plugin_settings );

		$filename = 'plugin-settings.json';
		$filepath = $this->export_dir . $filename;

		$result = Helpers::write_to_file( wp_json_encode( $plugin_settings, JSON_PRETTY_PRINT ), $filepath );
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return $filepath;
	}

	/**
	 * Get plugin settings.
	 *
	 * @param string $plugin_slug Plugin slug.
	 * @return array Plugin settings.
	 */
	private function get_plugin_settings( $plugin_slug ) {
		$settings = array();

		// Allow developers to hook into this to export their plugin settings.
		$settings = Helpers::apply_filters( 'socs/export_plugin_' . $plugin_slug . '_settings', $settings );

		// Default: try to get all options with plugin prefix.
		if ( empty( $settings ) ) {
			global $wpdb;
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$options = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE %s",
					$wpdb->esc_like( $plugin_slug ) . '%'
				)
			);

			foreach ( $options as $option ) {
				$settings[ $option->option_name ] = maybe_unserialize( $option->option_value );
			}
		}

		return $settings;
	}

	/**
	 * Export Elementor Style Kit data.
	 *
	 * @return string|WP_Error File path or WP_Error.
	 */
	private function export_elementor() {
		if ( ! class_exists( '\Elementor\Plugin' ) ) {
			return new \WP_Error( 'elementor_not_active', esc_html__( 'Elementor is not active.', 'smart-one-click-setup' ) );
		}

		$elementor_data = array();

		// Export Elementor Style Kit kit settings.
		$kit_id = \Elementor\Plugin::$instance->kits_manager->get_active_id();
		if ( $kit_id ) {
			$kit_settings = get_post_meta( $kit_id, '_elementor_page_settings', true );
			if ( $kit_settings ) {
				$elementor_data['kit_settings'] = $kit_settings;
			}
		}

		// Export Elementor Style Kit templates and page data.
		// This meta_query is necessary to find all posts with Elementor data for export.
		$posts = get_posts( array(
			'post_type'      => array( 'page', 'elementor_library' ),
			'posts_per_page' => -1,
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Necessary to find all posts with Elementor data.
			'meta_query'     => array(
				array(
					'key'     => '_elementor_data',
					'compare' => 'EXISTS',
				),
			),
		) );

		$elementor_posts = array();
		foreach ( $posts as $post ) {
			$elementor_data_post = get_post_meta( $post->ID, '_elementor_data', true );
			if ( $elementor_data_post ) {
				$elementor_posts[ $post->ID ] = array(
					'post_id'   => $post->ID,
					'post_type' => $post->post_type,
					'post_title' => $post->post_title,
					'elementor_data' => $elementor_data_post,
					'elementor_edit_mode' => get_post_meta( $post->ID, '_elementor_edit_mode', true ),
					'elementor_css' => get_post_meta( $post->ID, '_elementor_css', true ),
				);
			}
		}

		if ( ! empty( $elementor_posts ) ) {
			$elementor_data['posts'] = $elementor_posts;
		}

		// Allow filtering of Elementor data.
		$elementor_data = Helpers::apply_filters( 'socs/export_elementor_data', $elementor_data );

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
			'export_version' => defined( 'SOCS_VERSION' ) ? SOCS_VERSION : '1.0.0',
		);
		$info_file = $this->export_dir . 'export-info.json';
		$info_result = Helpers::write_to_file( wp_json_encode( $info, JSON_PRETTY_PRINT ), $info_file );
		if ( ! is_wp_error( $info_result ) && file_exists( $info_file ) ) {
			$zip->addFile( $info_file, 'export-info.json' );
		}

		if ( ! $zip->close() ) {
			wp_delete_file( $zip_filepath );
			return new \WP_Error( 'zip_close_failed', esc_html__( 'Failed to close ZIP archive.', 'smart-one-click-setup' ) );
		}

		// Clean up info file.
		if ( file_exists( $info_file ) ) {
			wp_delete_file( $info_file );
		}

		// Verify ZIP file was created.
		if ( ! file_exists( $zip_filepath ) ) {
			return new \WP_Error( 'zip_file_not_created', esc_html__( 'ZIP file was not created successfully.', 'smart-one-click-setup' ) );
		}

		return $zip_filepath;
	}
}

