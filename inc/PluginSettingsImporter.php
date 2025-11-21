<?php
/**
 * Class for the plugin settings importer used in the Smart One Click Setup plugin.
 *
 * @package socs
 */

namespace SOCS;

class PluginSettingsImporter {
	/**
	 * Import plugin settings from JSON file.
	 *
	 * @param string $plugin_settings_import_file_path Path to the plugin settings import file.
	 */
	public static function import( $plugin_settings_import_file_path ) {
		$socs          = SmartOneClickSetup::get_instance();
		$log_file_path = $socs->get_log_file_path();

		// Import plugin settings and return result.
		if ( ! empty( $plugin_settings_import_file_path ) ) {
			$results = self::import_plugin_settings( $plugin_settings_import_file_path );
		} else {
			return;
		}

		// Check for errors, else write the results to the log file.
		if ( is_wp_error( $results ) ) {
			$error_message = $results->get_error_message();

			// Add any error messages to the frontend_error_messages variable in SOCS main class.
			$socs->append_to_frontend_error_messages( $error_message );

			// Write error to log file.
			Helpers::append_to_file(
				$error_message,
				$log_file_path,
				esc_html__( 'Importing plugin settings', 'smart-one-click-setup' )
			);
		} else {
			ob_start();
				self::format_results_for_log( $results );
			$message = ob_get_clean();

			// Add this message to log file.
			Helpers::append_to_file(
				$message,
				$log_file_path,
				esc_html__( 'Importing plugin settings', 'smart-one-click-setup' )
			);
		}
	}

	/**
	 * Process import file - this parses the plugin settings data and returns it.
	 *
	 * @param string $file Path to JSON file.
	 * @return array|WP_Error Decoded JSON data or WP_Error.
	 */
	private static function process_import_file( $file ) {
		// File exists?
		if ( ! file_exists( $file ) ) {
			return new \WP_Error(
				'plugin_settings_import_file_not_found',
				esc_html__( 'Error: Plugin settings import file could not be found.', 'smart-one-click-setup' )
			);
		}

		// Get file contents and decode.
		$data = Helpers::data_from_file( $file );

		// Return from this function if there was an error.
		if ( is_wp_error( $data ) ) {
			return $data;
		}

		$decoded = json_decode( $data, true );

		if ( json_last_error() !== JSON_ERROR_NONE ) {
			return new \WP_Error(
				'plugin_settings_import_json_error',
				sprintf( /* translators: %s: JSON error message */
					esc_html__( 'Error: Failed to decode plugin settings import file. JSON error: %s', 'smart-one-click-setup' ),
					json_last_error_msg()
				)
			);
		}

		return $decoded;
	}

	/**
	 * Import plugin settings.
	 *
	 * @param string $data_file Path to JSON file with plugin settings export data.
	 * @return array|WP_Error Results array or WP_Error.
	 */
	private static function import_plugin_settings( $data_file ) {
		// Get plugin settings data from file.
		$data = self::process_import_file( $data_file );

		// Return from this function if there was an error.
		if ( is_wp_error( $data ) ) {
			return $data;
		}

		// Have valid data?
		if ( empty( $data ) || ! is_array( $data ) ) {
			return new \WP_Error(
				'corrupted_plugin_settings_import_data',
				esc_html__( 'Error: Plugin settings import data could not be read. Please try a different file.', 'smart-one-click-setup' )
			);
		}

		$results = array(
			'success' => 0,
			'failed'  => 0,
			'skipped' => 0,
			'details' => array(),
		);

		// Hook before import.
		Helpers::do_action( 'socs/plugin_settings_importer_before_import' );
		$data = Helpers::apply_filters( 'socs/before_plugin_settings_import_data', $data );

		// Loop through each plugin's settings.
		foreach ( $data as $plugin_slug => $plugin_settings ) {
			// Check if plugin is active.
			if ( ! self::is_plugin_active( $plugin_slug ) ) {
				$results['skipped']++;
				$results['details'][] = array(
					'plugin'  => $plugin_slug,
					'status'  => 'skipped',
					'message' => esc_html__( 'Plugin is not active.', 'smart-one-click-setup' ),
				);
				continue;
			}

			// Import settings for this plugin.
			$imported = self::import_plugin_settings_data( $plugin_slug, $plugin_settings );

			if ( $imported ) {
				$results['success']++;
				$results['details'][] = array(
					'plugin'  => $plugin_slug,
					'status'  => 'success',
					'message' => esc_html__( 'Plugin settings imported successfully.', 'smart-one-click-setup' ),
				);
			} else {
				$results['failed']++;
				$results['details'][] = array(
					'plugin'  => $plugin_slug,
					'status'  => 'failed',
					'message' => esc_html__( 'Failed to import plugin settings.', 'smart-one-click-setup' ),
				);
			}
		}

		// Hook after import.
		Helpers::do_action( 'socs/plugin_settings_importer_after_import' );

		return $results;
	}

	/**
	 * Check if a plugin is active.
	 *
	 * @param string $plugin_slug Plugin slug or identifier.
	 * @return bool True if plugin is active, false otherwise.
	 */
	private static function is_plugin_active( $plugin_slug ) {
		// Include WordPress plugin functions if not already loaded.
		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		// Check if it's a plugin file path (e.g., 'plugin-name/plugin-name.php').
		if ( strpos( $plugin_slug, '/' ) !== false ) {
			return is_plugin_active( $plugin_slug );
		}

		// Check active plugins by slug.
		$active_plugins = get_option( 'active_plugins', array() );
		foreach ( $active_plugins as $plugin_file ) {
			// Extract plugin directory/slug from file path.
			$plugin_dir = dirname( $plugin_file );
			if ( $plugin_dir === $plugin_slug || basename( $plugin_file, '.php' ) === $plugin_slug ) {
				return true;
			}
		}

		// Also check if plugin class/function exists (for plugins that don't use standard activation).
		// Allow filtering for custom plugin detection.
		$is_active = Helpers::apply_filters( 'socs/is_plugin_active_' . $plugin_slug, false, $plugin_slug );
		if ( $is_active ) {
			return true;
		}

		return false;
	}

	/**
	 * Import settings for a specific plugin.
	 *
	 * @param string $plugin_slug Plugin slug.
	 * @param array  $settings    Plugin settings array.
	 * @return bool True on success, false on failure.
	 */
	private static function import_plugin_settings_data( $plugin_slug, $settings ) {
		if ( empty( $settings ) || ! is_array( $settings ) ) {
			return false;
		}

		// Allow developers to hook into this to import their plugin settings.
		// This gives plugin authors full control over how their settings are imported.
		$imported = Helpers::apply_filters( 'socs/import_plugin_' . $plugin_slug . '_settings', false, $settings );

		// If filter returned true, settings were imported via the filter.
		if ( $imported === true ) {
			return true;
		}

		// Default: import settings directly as WordPress options.
		// This works for plugins that store settings in wp_options table.
		foreach ( $settings as $option_name => $option_value ) {
			// Sanitize option name.
			$option_name = sanitize_key( $option_name );

			// Allow filtering of option value before import.
			$option_value = Helpers::apply_filters( 'socs/import_plugin_option_value', $option_value, $option_name, $plugin_slug );
			$option_value = Helpers::apply_filters( 'socs/import_plugin_' . $plugin_slug . '_option_value', $option_value, $option_name );

			// Update the option.
			update_option( $option_name, $option_value );
		}

		return true;
	}

	/**
	 * Format results for log file.
	 *
	 * @param array $results Plugin settings import results.
	 */
	private static function format_results_for_log( $results ) {
		if ( empty( $results ) ) {
			esc_html_e( 'No results for plugin settings import!', 'smart-one-click-setup' );
			return;
		}

		if ( isset( $results['success'] ) || isset( $results['failed'] ) || isset( $results['skipped'] ) ) {
			/* translators: %d: Number of plugins */
			echo sprintf( esc_html__( 'Plugins imported: %d', 'smart-one-click-setup' ), $results['success'] ) . PHP_EOL;
			/* translators: %d: Number of plugins */
			echo sprintf( esc_html__( 'Plugins failed: %d', 'smart-one-click-setup' ), $results['failed'] ) . PHP_EOL;
			/* translators: %d: Number of plugins */
			echo sprintf( esc_html__( 'Plugins skipped: %d', 'smart-one-click-setup' ), $results['skipped'] ) . PHP_EOL;
			echo PHP_EOL;

			// Show details if available.
			if ( ! empty( $results['details'] ) ) {
				foreach ( $results['details'] as $detail ) {
					$status_icon = 'success' === $detail['status'] ? '✓' : ( 'failed' === $detail['status'] ? '✗' : '⊘' );
					echo $status_icon . ' ' . esc_html( $detail['plugin'] ) . ' - ' . esc_html( $detail['message'] ) . PHP_EOL;
				}
			}
		}
	}
}

