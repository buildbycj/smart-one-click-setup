<?php
/**
 * Class for the Elementor importer used in the Smart One Click Setup plugin.
 * Imports Elementor Site Kit settings (colors, typography, global styles).
 * Page and template data import is also supported for older exports.
 *
 * @package smartocs
 */

namespace SMARTOCS;

class ElementorImporter {
	/**
	 * Import Elementor Site Kit from JSON file.
	 * Imports Site Kit settings and makes it the active kit.
	 * Optionally imports page/template data if present (for older exports).
	 *
	 * @param string $elementor_import_file_path Path to the Elementor import file.
	 */
	public static function import( $elementor_import_file_path ) {
		$smartocs          = SmartOneClickSetup::get_instance();
		$log_file_path = $smartocs->get_log_file_path();

		// Check if Elementor is active.
		// Log section title for consistent logging.
		$log_section_title = esc_html__( 'Importing Elementor data', 'smart-one-click-setup' );

		if ( ! class_exists( '\Elementor\Plugin' ) ) {
			$error_message = esc_html__( 'Elementor plugin is not active, so the Elementor import was skipped!', 'smart-one-click-setup' );
			$smartocs->append_to_frontend_error_messages( $error_message );
			Helpers::append_to_file(
				$error_message,
				$log_file_path,
				$log_section_title
			);
			return;
		}

		// Import Elementor data and return result.
		if ( ! empty( $elementor_import_file_path ) ) {
			$results = self::import_elementor_data( $elementor_import_file_path );
		} else {
			return;
		}

		// Check for errors, else write the results to the log file.
		if ( is_wp_error( $results ) ) {
			$error_message = $results->get_error_message();

			// Add any error messages to the frontend_error_messages variable in SMARTOCS main class.
			$smartocs->append_to_frontend_error_messages( $error_message );

			$message = $error_message;
		} else {
			$message = self::format_results_for_log( $results );
		}

		// Write message to log file.
		if ( ! empty( $message ) ) {
			Helpers::append_to_file(
				$message,
				$log_file_path,
				$log_section_title
			);
		}
	}

	/**
	 * Process import file - this parses the Elementor data and returns it.
	 *
	 * @param string $file Path to JSON file.
	 * @return array|WP_Error Decoded JSON data or WP_Error.
	 */
	private static function process_import_file( $file ) {
		// File exists?
		if ( ! file_exists( $file ) ) {
			return new \WP_Error(
				'elementor_import_file_not_found',
				esc_html__( 'Error: Elementor import file could not be found.', 'smart-one-click-setup' )
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
				'elementor_import_json_error',
				sprintf( /* translators: %s: JSON error message */
					esc_html__( 'Error: Failed to decode Elementor import file. JSON error: %s', 'smart-one-click-setup' ),
					json_last_error_msg()
				)
			);
		}

		return $decoded;
	}

	/**
	 * Import Elementor Site Kit data.
	 * Primary focus: Import Site Kit settings and make it active.
	 * Secondary: Import page/template data if present (for older exports).
	 *
	 * @param string $data_file Path to JSON file with Elementor export data.
	 * @return array|WP_Error Results array or WP_Error.
	 */
	private static function import_elementor_data( $data_file ) {
		// Get Elementor data from file.
		$data = self::process_import_file( $data_file );

		// Return from this function if there was an error.
		if ( is_wp_error( $data ) ) {
			return $data;
		}

		// Have valid data?
		if ( empty( $data ) || ! is_array( $data ) ) {
			return new \WP_Error(
				'corrupted_elementor_import_data',
				esc_html__( 'Error: Elementor import data could not be read. Please try a different file.', 'smart-one-click-setup' )
			);
		}

		$results = array(
			'kit'   => false,
			'posts' => array(),
		);

		// Import Site Kit settings (primary purpose).
		if ( ! empty( $data['kit_settings'] ) ) {
			$kit_result = self::import_kit_settings( $data['kit_settings'] );
			$results['kit'] = $kit_result;
		}

		// Import Elementor posts/templates data (only if present in export).
		// Note: New exports only include Site Kit settings, not page data.
		if ( ! empty( $data['posts'] ) && is_array( $data['posts'] ) ) {
			$posts_result = self::import_elementor_posts( $data['posts'] );
			$results['posts'] = $posts_result;
		}

		return $results;
	}

	/**
	 * Import Elementor kit settings and make it active.
	 * Looks for existing "Imported Site Kit" and updates it, or creates a new one if it doesn't exist.
	 * This ensures we always use the same imported kit and preserve other kits like "Default Kit".
	 *
	 * @param array $kit_settings Kit settings array from elementor.json.
	 * @return array|WP_Error Results array or WP_Error.
	 */
	private static function import_kit_settings( $kit_settings ) {
		if ( ! class_exists( '\Elementor\Plugin' ) ) {
			return new \WP_Error( 'elementor_not_active', esc_html__( 'Elementor is not active.', 'smart-one-click-setup' ) );
		}

		// Validate kit settings.
		if ( empty( $kit_settings ) || ! is_array( $kit_settings ) ) {
			return new \WP_Error(
				'invalid_kit_settings',
				esc_html__( 'Invalid kit settings provided. Kit settings must be a non-empty array.', 'smart-one-click-setup' )
			);
		}

		$elementor = \Elementor\Plugin::$instance;
		$kit_id = null;
		$is_new_kit = false;

		// Look for existing "Imported Site Kit" specifically to avoid updating other kits.
		// This ensures we always use/update the same imported kit and preserve other kits like "Default Kit".
		$imported_kit_title = esc_html__( 'Imported Site Kit', 'smart-one-click-setup' );
		
		// Query for existing "Imported Site Kit" by title and taxonomy.
		$existing_imported_kits = get_posts( array(
			'post_type'      => 'elementor_library',
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
			'tax_query'      => array(
				array(
					'taxonomy' => 'elementor_library_type',
					'field'    => 'slug',
					'terms'    => 'kit',
				),
			),
		) );

		// Find the kit with matching title.
		$existing_kit_id = null;
		if ( ! empty( $existing_imported_kits ) && is_array( $existing_imported_kits ) ) {
			foreach ( $existing_imported_kits as $kit_post_id ) {
				$kit_post = get_post( $kit_post_id );
				if ( $kit_post && $imported_kit_title === $kit_post->post_title ) {
					$existing_kit_id = $kit_post_id;
					break;
				}
			}
		}

		// If "Imported Site Kit" exists, use it.
		if ( ! empty( $existing_kit_id ) ) {
			$existing_kit = get_post( $existing_kit_id );
			
			// Verify it's a valid kit.
			if ( $existing_kit && 'elementor_library' === $existing_kit->post_type ) {
				$kit_type = wp_get_object_terms( $existing_kit_id, 'elementor_library_type', array( 'fields' => 'slugs' ) );
				if ( ! is_wp_error( $kit_type ) && in_array( 'kit', $kit_type, true ) ) {
					$kit_id = $existing_kit_id;
				}
			}
		}

		// Create new "Imported Site Kit" if it doesn't exist.
		if ( empty( $kit_id ) ) {
			$kit_post = array(
				'post_title'  => esc_html__( 'Imported Site Kit', 'smart-one-click-setup' ),
				'post_status' => 'publish',
				'post_type'   => 'elementor_library',
				'meta_input'  => array(
					'_elementor_template_type' => 'kit',
					'_elementor_edit_mode'     => 'builder',
				),
			);

			$kit_id = wp_insert_post( $kit_post );

			if ( is_wp_error( $kit_id ) ) {
				return new \WP_Error(
					'kit_creation_failed',
					sprintf( /* translators: %s: Error message */
						esc_html__( 'Failed to create Elementor kit: %s', 'smart-one-click-setup' ),
						$kit_id->get_error_message()
					)
				);
			}

			// Set the kit type taxonomy for new kit.
			wp_set_object_terms( $kit_id, 'kit', 'elementor_library_type' );
			$is_new_kit = true;
		}

		// For new kits, ensure we apply settings exactly as they are in elementor.json.
		// Do not merge with any defaults - use the exact settings from the export.
		// Make a deep copy to avoid any reference issues.
		$settings_to_apply = $kit_settings;
		if ( is_array( $kit_settings ) ) {
			// Ensure we're working with a clean copy of the settings.
			$settings_to_apply = json_decode( wp_json_encode( $kit_settings ), true );
		}

		// Update kit settings with exact settings from elementor.json.
		// Use update_post_meta to ensure settings are saved exactly as provided.
		$updated = update_post_meta( $kit_id, '_elementor_page_settings', $settings_to_apply );

		// Also save using Elementor's method if available (for proper internal processing).
		// But ensure it uses our exact settings, not merged defaults.
		if ( method_exists( $elementor->kits_manager, 'save_kit_settings' ) ) {
			try {
				// Use Elementor's method to ensure proper saving and internal processing.
				$elementor->kits_manager->save_kit_settings( $kit_id, $settings_to_apply );
			} catch ( \Exception $e ) {
				// If Elementor's method fails, we still have the post meta saved above.
				// Log but don't fail the import.
			}
		}

		// For new kits, also ensure _elementor_data meta is set (some Elementor versions require this).
		if ( $is_new_kit ) {
			// Set empty Elementor data structure for new kit.
			update_post_meta( $kit_id, '_elementor_data', '[]' );
		}

		// Activate the imported Elementor Site Kit using Elementor's API.
		// 
		// IMPORTANT: 'elementor_active_kit' is Elementor's CORE option name, not a plugin option.
		// Elementor plugin only reads the active kit ID from the option named 'elementor_active_kit'.
		// Using a custom prefix here would break Elementor functionality entirely - Elementor would not recognize the active kit.
		// 
		// We use Elementor's documented API methods which handle option updates internally when available.
		// This avoids direct manipulation of Elementor's core options when possible.
		// 
		// Plugin-specific Elementor data (if any) uses a unique plugin prefix (e.g., 'smartocs_elementor_data').
		// But the active kit option MUST use Elementor's core option name for Elementor to recognize it.
		// 
		// Reference: Elementor Core - Kits Manager API
		if ( did_action( 'elementor/loaded' ) ) {
			// Use Elementor's kits_manager API if available (Elementor 3.0+).
			// This is Elementor's documented method that properly handles kit activation.
			// The API method handles the 'elementor_active_kit' option internally.
			if ( method_exists( $elementor->kits_manager, 'set_active_kit' ) ) {
				try {
					// Elementor's API method handles the option update internally.
					// No direct option manipulation needed when using the API.
					$elementor->kits_manager->set_active_kit( $kit_id );
				} catch ( \Exception $e ) {
					// API method failed - fallback to direct option update (last resort).
					// This is Elementor's own core option, not a plugin option.
					// Elementor only reads from 'elementor_active_kit' - using a custom prefix would break functionality.
					// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedOptionName -- Elementor core required option name 'elementor_active_kit', not a plugin option. Elementor only reads from this option name.
					update_option( 'elementor_active_kit', absint( $kit_id ) );
				}
			} else {
				// Older Elementor versions - direct option update required.
				// This is Elementor's own core option, required for kit activation.
				// Elementor only reads from 'elementor_active_kit' - using a custom prefix would break functionality.
				// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedOptionName -- Elementor core required option name 'elementor_active_kit', not a plugin option. Elementor only reads from this option name.
				update_option( 'elementor_active_kit', absint( $kit_id ) );
			}
		} else {
			// Elementor not fully loaded - fallback to direct option update.
			// This is Elementor's own core option, not a plugin option.
			// Elementor only reads from 'elementor_active_kit' - using a custom prefix would break functionality.
			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedOptionName -- Elementor core required option name 'elementor_active_kit', not a plugin option. Elementor only reads from this option name.
			update_option( 'elementor_active_kit', absint( $kit_id ) );
		}

		// Clear Elementor cache to ensure changes take effect.
		if ( method_exists( $elementor->files_manager, 'clear_cache' ) ) {
			$elementor->files_manager->clear_cache();
		}

		// Force Elementor to regenerate CSS for the kit.
		if ( method_exists( $elementor->posts_css_manager, 'clear_cache' ) ) {
			$elementor->posts_css_manager->clear_cache();
		}

		// Clear kit-specific cache.
		if ( method_exists( $elementor->kits_manager, 'clear_cache' ) ) {
			$elementor->kits_manager->clear_cache();
		}

		// Clean up any duplicate kits that might have been created from content.xml import.
		// This ensures we only have "Imported Site Kit" and original kits, no duplicates.
		self::cleanup_duplicate_kits( $kit_id );

		// Verify settings were updated.
		$saved_settings = get_post_meta( $kit_id, '_elementor_page_settings', true );
		if ( empty( $saved_settings ) ) {
			return new \WP_Error(
				'kit_settings_not_saved',
				esc_html__( 'Kit settings were not saved properly. Please try again.', 'smart-one-click-setup' )
			);
		}

		// Ensure settings from elementor.json are applied exactly (for both new and existing "Imported Site Kit").
		// Re-apply settings if they don't match to ensure exact match.
		// Normalize both arrays for comparison (handle potential serialization/formatting differences).
		$imported_normalized = self::normalize_settings_for_comparison( $kit_settings );
		$saved_normalized = self::normalize_settings_for_comparison( $saved_settings );
		
		// If key settings don't match, force re-save with exact settings from elementor.json.
		if ( $imported_normalized !== $saved_normalized ) {
			// Delete and re-save to ensure exact match.
			delete_post_meta( $kit_id, '_elementor_page_settings' );
			update_post_meta( $kit_id, '_elementor_page_settings', $settings_to_apply );
			
			// If Elementor's save method exists, use it again with exact settings.
			if ( method_exists( $elementor->kits_manager, 'save_kit_settings' ) ) {
				try {
					$elementor->kits_manager->save_kit_settings( $kit_id, $settings_to_apply );
				} catch ( \Exception $e ) {
					// Continue even if Elementor's method fails - post meta is saved.
				}
			}
		}

		$message = $is_new_kit
			? esc_html__( 'Elementor Site Kit created and activated successfully.', 'smart-one-click-setup' )
			: esc_html__( 'Elementor Site Kit settings updated and activated successfully.', 'smart-one-click-setup' );

		return array(
			'success'     => true,
			'kit_id'      => $kit_id,
			'is_new_kit'  => $is_new_kit,
			'message'     => $message,
		);
	}

	/**
	 * Clean up duplicate kits that may have been imported from content.xml.
	 * Keeps only "Imported Site Kit" and original kits, removes duplicates.
	 *
	 * @param int $keep_kit_id The kit ID to keep (our imported kit).
	 */
	private static function cleanup_duplicate_kits( $keep_kit_id ) {
		if ( empty( $keep_kit_id ) ) {
			return;
		}

		// Get all Elementor kits.
		$all_kits = get_posts( array(
			'post_type'      => 'elementor_library',
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
			'tax_query'      => array(
				array(
					'taxonomy' => 'elementor_library_type',
					'field'    => 'slug',
					'terms'    => 'kit',
				),
			),
		) );

		if ( empty( $all_kits ) || ! is_array( $all_kits ) ) {
			return;
		}

		// Track kit titles to find duplicates.
		$kit_titles = array();
		$kits_to_delete = array();

		foreach ( $all_kits as $kit_id ) {
			// Always keep our imported kit.
			if ( (int) $kit_id === (int) $keep_kit_id ) {
				continue;
			}

			$kit = get_post( $kit_id );
			if ( ! $kit ) {
				continue;
			}

			$kit_title = strtolower( trim( $kit->post_title ) );

			// Check if this is a duplicate title.
			if ( isset( $kit_titles[ $kit_title ] ) ) {
				// Found a duplicate - mark the newer one for deletion.
				// Keep the older kit (original), delete the newer one (imported from content.xml).
				$existing_kit_id = $kit_titles[ $kit_title ];
				$existing_kit = get_post( $existing_kit_id );
				
				if ( $existing_kit && $kit->post_date > $existing_kit->post_date ) {
					// Current kit is newer - delete it.
					$kits_to_delete[] = $kit_id;
				} else {
					// Existing kit is newer or same - delete existing and keep current.
					$kits_to_delete[] = $existing_kit_id;
					$kit_titles[ $kit_title ] = $kit_id;
				}
			} else {
				// First occurrence of this title - keep it.
				$kit_titles[ $kit_title ] = $kit_id;
			}
		}

		// Delete duplicate kits.
		foreach ( $kits_to_delete as $kit_id_to_delete ) {
			// Don't delete if it's our imported kit.
			if ( (int) $kit_id_to_delete === (int) $keep_kit_id ) {
				continue;
			}
			
			// Delete the duplicate kit.
			wp_delete_post( $kit_id_to_delete, true );
		}
	}

	/**
	 * Normalize settings array for comparison.
	 * Handles potential differences in array ordering, null values, etc.
	 *
	 * @param array|mixed $settings Settings array to normalize.
	 * @return string Normalized JSON string for comparison.
	 */
	private static function normalize_settings_for_comparison( $settings ) {
		if ( ! is_array( $settings ) ) {
			return '';
		}

		// Sort array keys recursively for consistent comparison.
		$normalized = self::ksort_recursive( $settings );
		
		// Convert to JSON for comparison (handles nested arrays/objects).
		return wp_json_encode( $normalized, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
	}

	/**
	 * Recursively sort array by keys.
	 *
	 * @param array $array Array to sort.
	 * @return array Sorted array.
	 */
	private static function ksort_recursive( $array ) {
		if ( ! is_array( $array ) ) {
			return $array;
		}

		ksort( $array );
		foreach ( $array as $key => $value ) {
			if ( is_array( $value ) ) {
				$array[ $key ] = self::ksort_recursive( $value );
			}
		}

		return $array;
	}

	/**
	 * Import Elementor posts/templates data.
	 * This method is kept for older exports that included page data.
	 * New exports only include Site Kit settings, so this method may not be called.
	 *
	 * @param array $elementor_posts Array of Elementor post data.
	 * @return array Results array.
	 */
	private static function import_elementor_posts( $elementor_posts ) {
		$results = array(
			'success' => 0,
			'failed'  => 0,
			'skipped' => 0,
			'details' => array(),
		);

		// Get post ID mapping from the content import.
		$smartocs                = SmartOneClickSetup::get_instance();
		$content_import_data  = $smartocs->importer->get_importer_data();
		$post_id_mapping      = isset( $content_import_data['mapping']['post'] ) ? $content_import_data['mapping']['post'] : array();

		foreach ( $elementor_posts as $old_post_id => $post_data ) {
			// Get the new post ID from the mapping.
			if ( ! isset( $post_id_mapping[ $old_post_id ] ) ) {
				$results['skipped']++;
				$results['details'][] = array(
					'post_id'  => $old_post_id,
					'title'    => isset( $post_data['post_title'] ) ? $post_data['post_title'] : '',
					'status'   => 'skipped',
					'message'  => esc_html__( 'Post not found in import mapping.', 'smart-one-click-setup' ),
				);
				continue;
			}

			$new_post_id = $post_id_mapping[ $old_post_id ];

			// Verify the post exists and is the correct type.
			$post = get_post( $new_post_id );
			if ( ! $post ) {
				$results['failed']++;
				$results['details'][] = array(
					'post_id'  => $old_post_id,
					'new_id'   => $new_post_id,
					'title'    => isset( $post_data['post_title'] ) ? $post_data['post_title'] : '',
					'status'   => 'failed',
					'message'  => esc_html__( 'Post does not exist.', 'smart-one-click-setup' ),
				);
				continue;
			}

			// Verify post type matches.
			$expected_post_type = isset( $post_data['post_type'] ) ? $post_data['post_type'] : 'page';
			if ( $post->post_type !== $expected_post_type ) {
				$results['skipped']++;
				$results['details'][] = array(
					'post_id'  => $old_post_id,
					'new_id'   => $new_post_id,
					'title'    => isset( $post_data['post_title'] ) ? $post_data['post_title'] : '',
					'status'   => 'skipped',
					'message'  => sprintf( /* translators: %s: Post type */
						esc_html__( 'Post type mismatch. Expected: %s', 'smart-one-click-setup' ),
						$expected_post_type
					),
				);
				continue;
			}

			// Import Elementor data for this post.
			$imported = self::import_post_elementor_data( $new_post_id, $post_data );

			if ( $imported ) {
				$results['success']++;
				$results['details'][] = array(
					'post_id'  => $old_post_id,
					'new_id'   => $new_post_id,
					'title'    => isset( $post_data['post_title'] ) ? $post_data['post_title'] : $post->post_title,
					'status'   => 'success',
					'message'  => esc_html__( 'Elementor data imported successfully.', 'smart-one-click-setup' ),
				);
			} else {
				$results['failed']++;
				$results['details'][] = array(
					'post_id'  => $old_post_id,
					'new_id'   => $new_post_id,
					'title'    => isset( $post_data['post_title'] ) ? $post_data['post_title'] : $post->post_title,
					'status'   => 'failed',
					'message'  => esc_html__( 'Failed to import Elementor data.', 'smart-one-click-setup' ),
				);
			}
		}

		return $results;
	}

	/**
	 * Import Elementor data for a specific post.
	 *
	 * @param int   $post_id   The post ID.
	 * @param array $post_data Elementor post data from export.
	 * @return bool True on success, false on failure.
	 */
	private static function import_post_elementor_data( $post_id, $post_data ) {
		// Import _elementor_data.
		if ( isset( $post_data['elementor_data'] ) ) {
			update_post_meta( $post_id, '_elementor_data', $post_data['elementor_data'] );
		}

		// Import _elementor_edit_mode.
		if ( isset( $post_data['elementor_edit_mode'] ) ) {
			update_post_meta( $post_id, '_elementor_edit_mode', $post_data['elementor_edit_mode'] );
		} else {
			// Default to builder mode if not set.
			update_post_meta( $post_id, '_elementor_edit_mode', 'builder' );
		}

		// Import _elementor_css (if exists).
		if ( isset( $post_data['elementor_css'] ) ) {
			update_post_meta( $post_id, '_elementor_css', $post_data['elementor_css'] );
		}

		// Clear Elementor cache for this post.
		\Elementor\Plugin::$instance->posts_css_manager->clear_cache();

		return true;
	}

	/**
	 * Format results for log file.
	 *
	 * @param array $results Elementor import results.
	 */
	/**
	 * Format import results for log file.
	 *
	 * @param array $results Import results array.
	 * @return string Formatted results string.
	 */
	private static function format_results_for_log( $results ) {
		if ( empty( $results ) ) {
			return esc_html__( 'No results for Elementor import!', 'smart-one-click-setup' );
		}

		$output = '';

		// Site Kit import results (primary import).
		if ( isset( $results['kit'] ) ) {
			if ( is_array( $results['kit'] ) && isset( $results['kit']['success'] ) && $results['kit']['success'] ) {
				$output .= esc_html( $results['kit']['message'] ) . PHP_EOL;
				if ( isset( $results['kit']['kit_id'] ) ) {
					/* translators: %d: Kit ID */
					$output .= esc_html( sprintf( __( 'Site Kit ID: %d', 'smart-one-click-setup' ), absint( $results['kit']['kit_id'] ) ) ) . PHP_EOL;
					if ( isset( $results['kit']['is_new_kit'] ) ) {
						$kit_status = $results['kit']['is_new_kit']
							? esc_html__( 'New kit created', 'smart-one-click-setup' )
							: esc_html__( 'Existing kit updated', 'smart-one-click-setup' );
						$output .= esc_html( $kit_status ) . PHP_EOL;
					}
				}
			} elseif ( is_wp_error( $results['kit'] ) ) {
				$output .= esc_html__( 'Site Kit import failed: ', 'smart-one-click-setup' ) . esc_html( $results['kit']->get_error_message() ) . PHP_EOL;
			}
			$output .= PHP_EOL;
		}

		// Posts import results (only shown if page data was present in export).
		if ( isset( $results['posts'] ) && is_array( $results['posts'] ) && ! empty( $results['posts'] ) ) {
			$posts_results = $results['posts'];
			if ( isset( $posts_results['success'] ) || isset( $posts_results['failed'] ) || isset( $posts_results['skipped'] ) ) {
				/* translators: %d: Number of posts */
				$output .= esc_html( sprintf( __( 'Page/Template data imported: %d', 'smart-one-click-setup' ), absint( $posts_results['success'] ) ) ) . PHP_EOL;
				/* translators: %d: Number of posts */
				$output .= esc_html( sprintf( __( 'Page/Template data failed: %d', 'smart-one-click-setup' ), absint( $posts_results['failed'] ) ) ) . PHP_EOL;
				/* translators: %d: Number of posts */
				$output .= esc_html( sprintf( __( 'Page/Template data skipped: %d', 'smart-one-click-setup' ), absint( $posts_results['skipped'] ) ) ) . PHP_EOL;
				$output .= PHP_EOL;

				// Show details if available.
				if ( ! empty( $posts_results['details'] ) ) {
					foreach ( $posts_results['details'] as $detail ) {
						$status_icon = 'success' === $detail['status'] ? '✓' : ( 'failed' === $detail['status'] ? '✗' : '⊘' );
						$output .= esc_html( $status_icon ) . ' ' . esc_html( $detail['title'] ) . ' - ' . esc_html( $detail['message'] ) . PHP_EOL;
					}
				}
			}
		}

		return $output;
	}
}

