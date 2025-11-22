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
			'options' => array(), // Track imported options per plugin.
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
			$import_result = self::import_plugin_settings_data( $plugin_slug, $plugin_settings );

			if ( is_array( $import_result ) && isset( $import_result['success'] ) && $import_result['success'] ) {
				$results['success']++;
				$imported_options_count = isset( $import_result['options_count'] ) ? $import_result['options_count'] : 0;
				$results['options'][ $plugin_slug ] = isset( $import_result['options'] ) ? $import_result['options'] : array();
				
				$message = esc_html__( 'Plugin settings imported successfully.', 'smart-one-click-setup' );
				if ( $imported_options_count > 0 ) {
					/* translators: %d: Number of options imported */
					$message .= ' ' . sprintf( esc_html__( '(%d options imported)', 'smart-one-click-setup' ), $imported_options_count );
				}
				
				$results['details'][] = array(
					'plugin'  => $plugin_slug,
					'status'  => 'success',
					'message' => $message,
				);
			} elseif ( $import_result === true ) {
				// Backward compatibility: if method returns true without details.
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
	 * @return bool|array True on success, false on failure, or array with success status and details.
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
			// Trigger action after plugin settings are imported via filter.
			Helpers::do_action( 'socs/after_plugin_' . $plugin_slug . '_settings_imported', $settings );
			// Return success with option count if available.
			$options_count = count( $settings );
			return array(
				'success'      => true,
				'options_count' => $options_count,
				'options'      => array_keys( $settings ),
			);
		}

		// Check if this is a single-option plugin
		// Single-option plugins store all settings in one option, not as individual options.
		$single_option_name = self::detect_single_option_plugin( $plugin_slug, $settings );
		if ( $single_option_name ) {
			// Special handling: If settings contains the option name as a key (e.g., { "keystone_options": {...} }),
			// extract the nested value as the actual option value.
			$option_value = $settings;
			
			// If settings has exactly one key and that key matches the detected option name, extract the nested value.
			if ( count( $settings ) === 1 ) {
				$single_key = key( $settings );
				$single_value = reset( $settings );
				
				// If the key matches the detected option name, use the nested value.
				if ( $single_key === $single_option_name && is_array( $single_value ) ) {
					$option_value = $single_value;
				}
			}
			
			// This is a single-option plugin - import the entire structure as one option.
			$old_value = get_option( $single_option_name );
			
			// Allow filtering of the option value before import.
			$option_value = Helpers::apply_filters( 'socs/import_plugin_option_value', $option_value, $single_option_name, $plugin_slug );
			$option_value = Helpers::apply_filters( 'socs/import_plugin_' . $plugin_slug . '_option_value', $option_value, $single_option_name );
			
			// Force update by deleting and re-adding if value appears unchanged.
			// This ensures the option is always updated, even if WordPress thinks it hasn't changed.
			$updated = update_option( $single_option_name, $option_value );
			
			// If update_option returned false (value unchanged), force update by deleting and re-adding.
			if ( false === $updated && $old_value !== false ) {
				delete_option( $single_option_name );
				$updated = update_option( $single_option_name, $option_value );
			}
			
			// Always trigger hooks to ensure plugins process the option update.
			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
			do_action( 'update_option', $single_option_name, $old_value, $option_value );
			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
			do_action( 'update_option_' . $single_option_name, $old_value, $option_value, $single_option_name );
			
			// Trigger plugin-specific actions.
			Helpers::do_action( 'socs/after_plugin_option_imported', $single_option_name, $option_value, $old_value, $plugin_slug );
			Helpers::do_action( 'socs/after_plugin_' . $plugin_slug . '_option_imported', $single_option_name, $option_value, $old_value );
			
			// Trigger action after plugin settings are imported.
			Helpers::do_action( 'socs/after_plugin_' . $plugin_slug . '_settings_imported', $settings );
			
			// Clear cache.
			wp_cache_flush();
			
			return array(
				'success'       => true,
				'options_count' => 1,
				'options'       => array( $single_option_name ),
			);
		}

		// Default: import settings directly as WordPress options.
		// This works for plugins that store settings in wp_options table.
		$imported_options = array();
		$imported_count = 0;
		
		foreach ( $settings as $option_name => $option_value ) {
			// Sanitize option name.
			$option_name = sanitize_key( $option_name );

			// Skip if option name is empty after sanitization.
			if ( empty( $option_name ) ) {
				continue;
			}

			// Handle serialized data - ensure it's properly unserialized if needed.
			// WordPress options are automatically serialized/unserialized, but we need to handle
			// cases where the exported data might already be serialized strings.
			// maybe_unserialize() safely handles both serialized and non-serialized data.
			// Note: For custom options exported as JSON object, values are already unserialized.
			$original_value = $option_value;
			$option_value = maybe_unserialize( $option_value );
			
			// If maybe_unserialize failed (returned false for a non-false value), use original value.
			if ( false === $option_value && $original_value !== false && $original_value !== 'b:0;' ) {
				$option_value = $original_value;
			}

			// Allow filtering of option value before import.
			$option_value = Helpers::apply_filters( 'socs/import_plugin_option_value', $option_value, $option_name, $plugin_slug );
			$option_value = Helpers::apply_filters( 'socs/import_plugin_' . $plugin_slug . '_option_value', $option_value, $option_name );

			// Get old value for comparison and hooks.
			$old_value = get_option( $option_name );

			// Update the option.
			// Note: update_option() returns false if value hasn't changed, but we still want to track it as imported.
			$updated = update_option( $option_name, $option_value );
			
			// Track all imported options, even if value didn't change (update_option returned false).
			// This ensures custom options are always processed and tracked.
			$imported_options[] = $option_name;
			if ( $updated ) {
				$imported_count++;
			}

			// Trigger WordPress option update hooks if option was updated or if it's a new option.
			// For custom options, we want to ensure hooks are triggered even if value appears unchanged.
			if ( $updated || false === $old_value ) {
				// Trigger generic option update hook.
				// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
				do_action( 'update_option', $option_name, $old_value, $option_value );

				// Trigger specific option update hook (e.g., 'update_option_my_plugin_option').
				// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
				do_action( 'update_option_' . $option_name, $old_value, $option_value, $option_name );

				// Trigger plugin-specific action.
				Helpers::do_action( 'socs/after_plugin_option_imported', $option_name, $option_value, $old_value, $plugin_slug );
				Helpers::do_action( 'socs/after_plugin_' . $plugin_slug . '_option_imported', $option_name, $option_value, $old_value );
			}
		}

		// Trigger action after all plugin settings are imported.
		Helpers::do_action( 'socs/after_plugin_' . $plugin_slug . '_settings_imported', $settings );

		// Clear any relevant caches that might be affected by option updates.
		wp_cache_flush();

		return array(
			'success'       => true,
			'options_count' => $imported_count,
			'options'       => $imported_options,
		);
	}

	/**
	 * Detect if a plugin uses a single option to store all settings.
	 * 
	 * This detects plugins that store all settings
	 * in a nested structure within a single option, rather than individual options.
	 *
	 * @param string $plugin_slug Plugin slug.
	 * @param array  $settings    Plugin settings array.
	 * @return string|false The single option name if detected, false otherwise.
	 */
	private static function detect_single_option_plugin( $plugin_slug, $settings ) {
		// Special case: If settings has exactly one key and that key matches common single-option patterns,
		// treat the value as the entire option data and the key as the option name.
		// This handles cases like: { "keystone_options": {...} }
		if ( count( $settings ) === 1 ) {
			$single_key = key( $settings );
			$single_value = reset( $settings );
			
			// Check if the key matches common single-option patterns.
			if ( preg_match( '/_(options|settings|config)$/i', $single_key ) && is_array( $single_value ) && ! empty( $single_value ) ) {
				// Check if the value has a nested structure (confirming it's a single-option plugin).
				$has_nested_structure = false;
				$has_fields_structure = false;
				$has_name_icon_structure = false;
				
				foreach ( $single_value as $sub_key => $sub_value ) {
					if ( is_array( $sub_value ) && ! empty( $sub_value ) ) {
						$has_nested_structure = true;
						// Check for common patterns.
						if ( isset( $sub_value['fields'] ) && is_array( $sub_value['fields'] ) ) {
							$has_fields_structure = true;
						}
						if ( isset( $sub_value['name'] ) && isset( $sub_value['icon'] ) ) {
							$has_name_icon_structure = true;
						}
					}
				}
				
				// If it has nested structure with fields/name-icon pattern, or has nested arrays,
				// this is definitely a single-option plugin. Always return it for import.
				if ( $has_nested_structure || $has_fields_structure || $has_name_icon_structure ) {
					// Allow filter to override, but default to using the key as option name.
					$detected_option = Helpers::apply_filters( 'socs/detect_single_option_plugin', $single_key, $plugin_slug, $settings );
					if ( is_string( $detected_option ) && ! empty( $detected_option ) ) {
						return $detected_option;
					}
					// Return the key as the option name (even if option doesn't exist yet).
					return $single_key;
				}
			}
		}
		
		// Check if settings have a nested structure (indicating single-option plugin).
		// Single-option plugins typically have sections/fields structure.
		$has_nested_structure = false;
		$has_fields_structure = false;
		$has_name_icon_structure = false; // Check for 'name' and 'icon' keys.
		
		foreach ( $settings as $key => $value ) {
			// Check if value is an array with 'fields' key (common pattern).
			if ( is_array( $value ) && isset( $value['fields'] ) && is_array( $value['fields'] ) ) {
				$has_fields_structure = true;
				$has_nested_structure = true;
			}
			// Check for pattern: sections with 'name' and 'icon' keys.
			if ( is_array( $value ) && isset( $value['name'] ) && isset( $value['icon'] ) ) {
				$has_name_icon_structure = true;
				$has_nested_structure = true;
			}
			// Check if value is an array with nested arrays (sections pattern).
			if ( is_array( $value ) && ! empty( $value ) ) {
				$first_value = reset( $value );
				if ( is_array( $first_value ) ) {
					$has_nested_structure = true;
				}
			}
		}
		
		// If we have a nested structure with fields or name/icon pattern, this is likely a single-option plugin.
		// Also check if we have multiple top-level keys that are all arrays (sections pattern).
		if ( $has_fields_structure || $has_name_icon_structure || ( $has_nested_structure && count( $settings ) > 2 ) ) {
			// Allow developers to hook into this to specify custom option name first.
			$detected_option = Helpers::apply_filters( 'socs/detect_single_option_plugin', false, $plugin_slug, $settings );
			if ( is_string( $detected_option ) && ! empty( $detected_option ) ) {
				return $detected_option;
			}
			
			// Extract base name from plugin slug (e.g., "keystone-framework" -> "keystone").
			$slug_parts = explode( '-', $plugin_slug );
			$base_name = ! empty( $slug_parts[0] ) ? $slug_parts[0] : $plugin_slug;
			
			// Common single-option patterns.
			$possible_option_names = array(
				$plugin_slug,
				$plugin_slug . '_options',
				$plugin_slug . '_settings',
				$plugin_slug . '_config',
				str_replace( '-', '_', $plugin_slug ),
				str_replace( '-', '_', $plugin_slug ) . '_options',
				str_replace( '-', '_', $plugin_slug ) . '_settings',
				$base_name . '_options', // e.g., "keystone_options" from "keystone-framework"
				$base_name . '_settings',
				$base_name . '_config',
			);
			
			// Also check if any key in settings matches a single-option pattern.
			foreach ( $settings as $key => $value ) {
				if ( preg_match( '/_(options|settings|config)$/i', $key ) ) {
					$possible_option_names[] = $key;
				}
			}
			
			// Remove duplicates and re-index.
			$possible_option_names = array_values( array_unique( $possible_option_names ) );
			
			// Check which option name exists in the database (prefer existing option).
			foreach ( $possible_option_names as $option_name ) {
				if ( get_option( $option_name ) !== false ) {
					// Option exists, this is likely the correct one.
					return $option_name;
				}
			}
			
			// Check via filter if any option name should be used.
			foreach ( $possible_option_names as $option_name ) {
				$is_correct_option = Helpers::apply_filters( 'socs/is_single_option_plugin', false, $plugin_slug, $option_name, $settings );
				if ( $is_correct_option ) {
					return $option_name;
				}
			}
			
			// If settings contains a key that matches single-option pattern, use that.
			foreach ( $settings as $key => $value ) {
				if ( preg_match( '/_(options|settings|config)$/i', $key ) && is_array( $value ) && ! empty( $value ) ) {
					return $key;
				}
			}
			
			// Default: use plugin slug as option name (most common pattern).
			return $plugin_slug;
		}
		
		return false;
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
			echo esc_html( sprintf( __( 'Plugins imported: %d', 'smart-one-click-setup' ), absint( $results['success'] ) ) ) . PHP_EOL;
			/* translators: %d: Number of plugins */
			echo esc_html( sprintf( __( 'Plugins failed: %d', 'smart-one-click-setup' ), absint( $results['failed'] ) ) ) . PHP_EOL;
			/* translators: %d: Number of plugins */
			echo esc_html( sprintf( __( 'Plugins skipped: %d', 'smart-one-click-setup' ), absint( $results['skipped'] ) ) ) . PHP_EOL;
			echo PHP_EOL;

			// Show details if available.
			if ( ! empty( $results['details'] ) ) {
				foreach ( $results['details'] as $detail ) {
					$status_icon = 'success' === $detail['status'] ? '✓' : ( 'failed' === $detail['status'] ? '✗' : '⊘' );
					echo esc_html( $status_icon ) . ' ' . esc_html( $detail['plugin'] ) . ' - ' . esc_html( $detail['message'] ) . PHP_EOL;
					
					// Show imported options if available and in debug mode.
					if ( 'success' === $detail['status'] && ! empty( $results['options'][ $detail['plugin'] ] ) ) {
						$options = $results['options'][ $detail['plugin'] ];
						if ( defined( 'WP_DEBUG' ) && WP_DEBUG && ! empty( $options ) ) {
							echo '  ' . esc_html__( 'Imported options:', 'smart-one-click-setup' ) . ' ' . esc_html( implode( ', ', array_slice( $options, 0, 10 ) ) );
							if ( count( $options ) > 10 ) {
								/* translators: %d: Number of additional options */
								echo ' ' . sprintf( esc_html__( 'and %d more...', 'smart-one-click-setup' ), count( $options ) - 10 );
							}
							echo PHP_EOL;
						}
					}
				}
			}
		}
	}
}

