<?php
/**
 * Class for the Elementor importer used in the Smart One Click Setup plugin.
 *
 * @package socs
 */

namespace SOCS;

class ElementorImporter {
	/**
	 * Import Elementor data from JSON file.
	 *
	 * IMPORTANT: This import runs AFTER the XML content import (priority 50 in ImportActions).
	 * This is intentional because:
	 * 1. XML import includes Elementor post meta (_elementor_data, etc.) but may be incomplete
	 * 2. elementor.json contains the authoritative, complete Elementor data export
	 * 3. We update/replace Elementor data from XML with the complete data from elementor.json
	 * 4. This ensures Elementor pages/templates have the correct, complete data
	 *
	 * The import does NOT conflict with:
	 * - Elementor itself: We use Elementor's hooks and cache clearing mechanisms
	 * - XML import: We intentionally run after XML to update with complete Elementor data
	 *
	 * @param string $elementor_import_file_path Path to the Elementor import file.
	 */
	public static function import( $elementor_import_file_path ) {
		$socs          = SmartOneClickSetup::get_instance();
		$log_file_path = $socs->get_log_file_path();

		// Check if Elementor import is disabled via filter.
		if ( ! Helpers::apply_filters( 'socs/enable_elementor_import', true ) ) {
			$message = esc_html__( 'Elementor import is disabled via filter.', 'smart-one-click-setup' );
			Helpers::append_to_file(
				$message,
				$log_file_path,
				esc_html__( 'Importing Elementor data', 'smart-one-click-setup' )
			);
			return;
		}

		// Check if Elementor is active.
		if ( ! class_exists( '\Elementor\Plugin' ) ) {
			$error_message = esc_html__( 'Elementor plugin is not active, so the Elementor import was skipped!', 'smart-one-click-setup' );
			$socs->append_to_frontend_error_messages( $error_message );
			Helpers::append_to_file(
				$error_message,
				$log_file_path,
				esc_html__( 'Importing Elementor data', 'smart-one-click-setup' )
			);
			return;
		}
		
		// Verify Elementor is properly loaded.
		if ( ! method_exists( '\Elementor\Plugin', 'get_instance' ) ) {
			$error_message = esc_html__( 'Elementor plugin is not properly loaded.', 'smart-one-click-setup' );
			$socs->append_to_frontend_error_messages( $error_message );
			Helpers::append_to_file(
				$error_message,
				$log_file_path,
				esc_html__( 'Importing Elementor data', 'smart-one-click-setup' )
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

			// Add any error messages to the frontend_error_messages variable in SOCS main class.
			$socs->append_to_frontend_error_messages( $error_message );

			// Write error to log file.
			Helpers::append_to_file(
				$error_message,
				$log_file_path,
				esc_html__( 'Importing Elementor data', 'smart-one-click-setup' )
			);
		} else {
			ob_start();
				self::format_results_for_log( $results );
			$message = ob_get_clean();

			// Add this message to log file.
			Helpers::append_to_file(
				$message,
				$log_file_path,
				esc_html__( 'Importing Elementor data', 'smart-one-click-setup' )
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
	 * Import Elementor data.
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

		// Import kit settings.
		if ( ! empty( $data['kit_settings'] ) ) {
			$kit_result = self::import_kit_settings( $data['kit_settings'] );
			$results['kit'] = $kit_result;
		}

		// Import Elementor posts/templates data.
		if ( ! empty( $data['posts'] ) && is_array( $data['posts'] ) ) {
			$posts_result = self::import_elementor_posts( $data['posts'] );
			$results['posts'] = $posts_result;
		}

		return $results;
	}

	/**
	 * Import Elementor kit settings and make it active.
	 *
	 * @param array $kit_settings Kit settings array.
	 * @return array|WP_Error Results array or WP_Error.
	 */
	private static function import_kit_settings( $kit_settings ) {
		if ( ! class_exists( '\Elementor\Plugin' ) ) {
			return new \WP_Error( 'elementor_not_active', esc_html__( 'Elementor is not active.', 'smart-one-click-setup' ) );
		}

		$elementor = \Elementor\Plugin::$instance;

		// Verify kits_manager is available.
		if ( ! isset( $elementor->kits_manager ) || ! method_exists( $elementor->kits_manager, 'get_active_id' ) ) {
			return new \WP_Error(
				'elementor_kits_manager_unavailable',
				esc_html__( 'Elementor kits manager is not available. Please ensure Elementor is up to date.', 'smart-one-click-setup' )
			);
		}

		// Get existing active kit, or create a new one.
		$kit_id = $elementor->kits_manager->get_active_id();

		// If no active kit exists, create a new one.
		if ( ! $kit_id ) {
			// Create a new kit post.
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

			// Set the kit type taxonomy.
			wp_set_object_terms( $kit_id, 'kit', 'elementor_library_type' );
		}

		// Allow filtering of kit settings before import.
		$kit_settings = Helpers::apply_filters( 'socs/elementor_import_kit_settings', $kit_settings, $kit_id );
		
		// Update kit settings.
		update_post_meta( $kit_id, '_elementor_page_settings', $kit_settings );

		// Make this kit the active kit.
		// The active kit option name in Elementor.
		$previous_active_kit = get_option( 'elementor_active_kit' );
		update_option( 'elementor_active_kit', $kit_id );
		
		// Trigger Elementor's kit update hooks if available.
		if ( has_action( 'elementor/kit/update_settings' ) ) {
			do_action( 'elementor/kit/update_settings', $kit_id, $kit_settings );
		}
		
		// Clear Elementor cache to ensure kit settings are properly loaded.
		if ( method_exists( \Elementor\Plugin::$instance, 'files_manager' ) ) {
			\Elementor\Plugin::$instance->files_manager->clear_cache();
		}
		
		// Clear Elementor's kit cache specifically.
		if ( method_exists( $elementor->kits_manager, 'clear_cache' ) ) {
			$elementor->kits_manager->clear_cache();
		}

		return array(
			'success' => true,
			'kit_id'  => $kit_id,
			'message' => esc_html__( 'Elementor Site Kit imported and activated successfully.', 'smart-one-click-setup' ),
		);
	}

	/**
	 * Import Elementor posts/templates data.
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
		$socs                = SmartOneClickSetup::get_instance();
		$content_import_data  = $socs->importer->get_importer_data();
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

			// Check if Elementor data already exists from XML import.
			// We still import from elementor.json as it may have more complete/updated data.
			$existing_elementor_data = get_post_meta( $new_post_id, '_elementor_data', true );
			$has_existing_data = ! empty( $existing_elementor_data );
			
			// Import Elementor data for this post.
			// Note: This runs AFTER XML import, so it will update/replace any Elementor data
			// that was imported via XML. This is intentional as elementor.json contains
			// the authoritative Elementor data export.
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
		// Check if Elementor data already exists from XML import.
		// If it exists and matches, we can skip to avoid unnecessary updates.
		$existing_elementor_data = get_post_meta( $post_id, '_elementor_data', true );
		
		// Import _elementor_data.
		// Note: We update this even if it exists because elementor.json might have more complete/updated data
		// than what was imported via XML. The elementor.json export is specifically for Elementor data.
		if ( isset( $post_data['elementor_data'] ) ) {
			// Allow filtering before import.
			$elementor_data = Helpers::apply_filters( 'socs/elementor_import_post_data', $post_data['elementor_data'], $post_id, $post_data );
			
			// Use Elementor's update_post_meta if available, otherwise use WordPress function.
			// This ensures Elementor's internal hooks are triggered if they exist.
			update_post_meta( $post_id, '_elementor_data', $elementor_data );
			
			// Trigger Elementor's save_post action if available to ensure proper processing.
			if ( has_action( 'elementor/document/before_save' ) ) {
				// Allow Elementor to process the data update.
				do_action( 'elementor/document/before_save', $post_id );
			}
		}

		// Import _elementor_edit_mode.
		if ( isset( $post_data['elementor_edit_mode'] ) ) {
			update_post_meta( $post_id, '_elementor_edit_mode', $post_data['elementor_edit_mode'] );
		} else {
			// Default to builder mode if not set and Elementor data exists.
			if ( isset( $post_data['elementor_data'] ) ) {
				update_post_meta( $post_id, '_elementor_edit_mode', 'builder' );
			}
		}

		// Import _elementor_css (if exists).
		// Note: Elementor regenerates CSS, but we import it for consistency.
		if ( isset( $post_data['elementor_css'] ) ) {
			update_post_meta( $post_id, '_elementor_css', $post_data['elementor_css'] );
		}

		// Clear Elementor cache for this post to ensure fresh data.
		if ( method_exists( \Elementor\Plugin::$instance, 'posts_css_manager' ) ) {
			\Elementor\Plugin::$instance->posts_css_manager->clear_cache();
		}
		
		// Trigger Elementor's after_save action if available.
		if ( has_action( 'elementor/document/after_save' ) ) {
			do_action( 'elementor/document/after_save', $post_id );
		}

		return true;
	}

	/**
	 * Format results for log file.
	 *
	 * @param array $results Elementor import results.
	 */
	private static function format_results_for_log( $results ) {
		if ( empty( $results ) ) {
			esc_html_e( 'No results for Elementor import!', 'smart-one-click-setup' );
			return;
		}

		// Kit import results.
		if ( isset( $results['kit'] ) ) {
			if ( is_array( $results['kit'] ) && isset( $results['kit']['success'] ) && $results['kit']['success'] ) {
				echo esc_html( $results['kit']['message'] ) . PHP_EOL;
				if ( isset( $results['kit']['kit_id'] ) ) {
					/* translators: %d: Kit ID */
					echo sprintf( esc_html__( 'Kit ID: %d', 'smart-one-click-setup' ), $results['kit']['kit_id'] ) . PHP_EOL;
				}
			} elseif ( is_wp_error( $results['kit'] ) ) {
				echo esc_html__( 'Kit import failed: ', 'smart-one-click-setup' ) . esc_html( $results['kit']->get_error_message() ) . PHP_EOL;
			}
			echo PHP_EOL;
		}

		// Posts import results.
		if ( isset( $results['posts'] ) && is_array( $results['posts'] ) ) {
			$posts_results = $results['posts'];
			if ( isset( $posts_results['success'] ) || isset( $posts_results['failed'] ) || isset( $posts_results['skipped'] ) ) {
				/* translators: %d: Number of posts */
				echo sprintf( esc_html__( 'Posts imported: %d', 'smart-one-click-setup' ), $posts_results['success'] ) . PHP_EOL;
				/* translators: %d: Number of posts */
				echo sprintf( esc_html__( 'Posts failed: %d', 'smart-one-click-setup' ), $posts_results['failed'] ) . PHP_EOL;
				/* translators: %d: Number of posts */
				echo sprintf( esc_html__( 'Posts skipped: %d', 'smart-one-click-setup' ), $posts_results['skipped'] ) . PHP_EOL;
				echo PHP_EOL;

				// Show details if available.
				if ( ! empty( $posts_results['details'] ) ) {
					foreach ( $posts_results['details'] as $detail ) {
						$status_icon = 'success' === $detail['status'] ? '✓' : ( 'failed' === $detail['status'] ? '✗' : '⊘' );
						echo $status_icon . ' ' . esc_html( $detail['title'] ) . ' - ' . esc_html( $detail['message'] ) . PHP_EOL;
					}
				}
			}
		}
	}
}

