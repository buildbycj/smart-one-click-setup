<?php
namespace SMARTOCS;

/**
 * Class for declaring the content importer used in the Smart One Click Setup plugin
 *
 * @package smartocs
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Importer {
	/**
	 * The importer class object used for importing content.
	 *
	 * @var object
	 */
	private $importer;

	/**
	 * Time in milliseconds, marking the beginning of the import.
	 *
	 * @var float
	 */
	private $microtime;

	/**
	 * The instance of the SMARTOCS\Logger class.
	 *
	 * @var object
	 */
	public $logger;

	/**
	 * The instance of the Smart One Click Setup class.
	 *
	 * @var object
	 */
	private $smartocs;

	/**
	 * Constructor method.
	 *
	 * @param array  $importer_options Importer options.
	 * @param object $logger           Logger object used in the importer.
	 */
	public function __construct( $importer_options = array(), $logger = null ) {
		// Set logger to the importer.
		$this->logger = $logger;

		// Get the SMARTOCS (main plugin class) instance.
		$this->smartocs = SmartOneClickSetup::get_instance();

		// Load custom importer class (no longer requires WP_Importer).
		// The custom importer is self-contained and doesn't extend WP_Importer.
		if ( ! class_exists( __NAMESPACE__ . '\CustomWXRImporter' ) ) {
			require_once SMARTOCS_PATH . 'inc/CustomWXRImporter.php';
		}

		// Set the custom WXR importer (doesn't extend WP_Importer).
		$this->importer = new WXRImporter( $importer_options );

		if ( ! empty( $this->logger ) ) {
			$this->set_logger( $this->logger );
		}
	}

	/**
	 * Load custom importer class.
	 * No longer requires WP_Importer - the custom importer is self-contained.
	 *
	 * @return bool True if class is available, false otherwise.
	 */
	public static function load_wp_importer() {
		// Custom importer doesn't require WP_Importer anymore.
		// Just check if our custom class exists.
		if ( ! class_exists( __NAMESPACE__ . '\CustomWXRImporter' ) ) {
			require_once SMARTOCS_PATH . 'inc/CustomWXRImporter.php';
		}

		return class_exists( __NAMESPACE__ . '\CustomWXRImporter' );
	}


	/**
	 * Imports content from a WordPress export file.
	 *
	 * @param string $data_file path to xml file, file with WordPress export data.
	 */
	public function import( $data_file ) {
		$this->importer->import( $data_file );
	}


	/**
	 * Set the logger used in the import
	 *
	 * @param object $logger logger instance.
	 */
	public function set_logger( $logger ) {
		$this->importer->set_logger( $logger );
	}


	/**
	 * Get all protected variables from the WXR_Importer needed for continuing the import.
	 */
	public function get_importer_data() {
		return $this->importer->get_importer_data();
	}


	/**
	 * Sets all protected variables from the WXR_Importer needed for continuing the import.
	 *
	 * @param array $data with set variables.
	 */
	public function set_importer_data( $data ) {
		$this->importer->set_importer_data( $data );
	}


	/**
	 * Import content from an WP XML file.
	 *
	 * @param string $import_file_path Path to the import file.
	 */
	public function import_content( $import_file_path ) {
		$this->microtime = microtime( true );

		// Increase PHP memory limit only for this import operation.
		// Use WordPress function only (WordPress coding standards compliant).
		if ( function_exists( 'wp_raise_memory_limit' ) ) {
			wp_raise_memory_limit( 'admin' );
		}

		// Increase PHP max execution time. Just in case, even though the AJAX calls are only 25 sec long.
		// Use WordPress function only (WordPress 5.5+).
		if ( function_exists( 'wp_max_execution_time' ) ) {
			$time_limit = Helpers::apply_filters( 'smartocs/set_time_limit_for_demo_data_import', 300 );
			wp_max_execution_time( $time_limit );
		}

		// Disable import of authors.
		add_filter( 'wxr_importer.pre_process.user', '__return_false' );

		// Check, if we need to send another AJAX request and set the importing author to the current user.
		add_filter( 'wxr_importer.pre_process.post', array( $this, 'new_ajax_request_maybe' ) );

		// Skip invalid media file types (ZIP files, etc.) during attachment import.
		add_filter( 'wxr_importer.pre_process.post', array( $this, 'skip_invalid_media_attachments' ), 10, 1 );

		// Skip Elementor kits during content import - they are handled separately via elementor.json.
		// This prevents duplicate kits from being created when content.xml includes Elementor library posts.
		add_filter( 'wxr_importer.pre_process.post', array( $this, 'skip_elementor_kits' ), 10, 1 );

		// Disables generation of multiple image sizes (thumbnails) in the content import step.
		if ( ! Helpers::apply_filters( 'smartocs/regenerate_thumbnails_in_content_import', true ) ) {
			add_filter( 'intermediate_image_sizes_advanced', '__return_null' );
		}

		// Import content.
		if ( ! empty( $import_file_path ) ) {
			try {
				$this->import( $import_file_path );
			} catch ( \Exception $e ) {
				// Log the exception if needed.
				// The logger will handle error output.
			}

			// Get error output from logger.
			$error_output = $this->logger->error_output;

			// If error occurred, add to frontend error messages.
			if ( ! empty( $error_output ) ) {
				$this->smartocs->append_to_frontend_error_messages( $error_output );
			}

			// Clear logger's error_output after capturing it to prevent accumulation across imports.
			$this->logger->error_output = '';

			// Return any error messages for the front page output (errors, critical, alert and emergency level messages only).
			return $error_output;
		}

		// If no import file path provided, return empty string
		return '';
	}


	/**
	 * Check if we need to create a new AJAX request, so that server does not timeout.
	 *
	 * @param array $data current post data.
	 * @return array
	 */
	public function new_ajax_request_maybe( $data ) {

		if ( empty( $data ) ) {
			return $data;
		}

		$time = microtime( true ) - $this->microtime;

		// We should make a new ajax call, if the time is right.
		if ( $time > Helpers::apply_filters( 'smartocs/time_for_one_ajax_call', 25 ) ) {
			$response = array(
				'status'  => 'newAJAX',
				'message' => 'Time for new AJAX request!: ' . $time,
			);

			// Add message to log file.
			// Rely on the logger for capturing messages instead of output buffering.
			$log_added = Helpers::append_to_file(
				__( 'New AJAX call!' , 'smart-one-click-setup' ),
				$this->smartocs->get_log_file_path(),
				''
			);

			// Set the current importer stat, so it can be continued on the next AJAX call.
			$this->set_current_importer_data();

			// Send the request for a new AJAX call.
			wp_send_json( $response );
		}

		// Set importing author to the current user.
		// Fixes the [WARNING] Could not find the author for ... log warning messages.
		$current_user_obj    = wp_get_current_user();
		$data['post_author'] = $current_user_obj->user_login;

		return $data;
	}


	/**
	 * Set current state of the content importer, so we can continue the import with new AJAX request.
	 */
	private function set_current_importer_data() {
		$data = array_merge( $this->smartocs->get_current_importer_data(), $this->get_importer_data() );

		Helpers::set_smartocs_import_data_transient( $data );
	}

	/**
	 * Skip invalid media file types (ZIP files, etc.) during attachment import.
	 *
	 * This prevents errors when ZIP files or other non-media files are referenced
	 * as attachments in the WXR export file.
	 *
	 * @since 1.2.2
	 *
	 * @param array $data Post data to be imported.
	 * @return array Empty array to skip, or original data to continue.
	 */
	public function skip_invalid_media_attachments( $data ) {
		// Only process attachments.
		if ( empty( $data ) || empty( $data['post_type'] ) || $data['post_type'] !== 'attachment' ) {
			return $data;
		}

		// Get the attachment URL or GUID first to check filename.
		$attachment_url = '';
		if ( ! empty( $data['attachment_url'] ) ) {
			$attachment_url = $data['attachment_url'];
		} elseif ( ! empty( $data['guid'] ) ) {
			$attachment_url = $data['guid'];
		}

		// Check filename pattern for log files if URL is available.
		if ( ! empty( $attachment_url ) ) {
			$filename = basename( wp_parse_url( $attachment_url, PHP_URL_PATH ) );
			// Skip log files based on filename pattern: log_file_YYYY-MM-DD__HH-MM-SS.txt
			if ( preg_match( '/log_file_\d{4}-\d{2}-\d{2}__\d{2}-\d{2}-\d{2}\.txt$/i', $filename ) ) {
				// Return empty array to skip this log file attachment.
				return array();
			}
		}

		// Get the attachment title or post title to check for log files.
		$attachment_title = '';
		if ( ! empty( $data['post_title'] ) ) {
			$attachment_title = $data['post_title'];
		} elseif ( ! empty( $data['title'] ) ) {
			$attachment_title = $data['title'];
		}

		// Skip log file attachments based on title pattern.
		// Log files have patterns like: "log_file_YYYY-MM-DD__HH-MM-SS" or 
		// "Smart One Click Setup - log_file_YYYY-MM-DD__HH-MM-SS" or
		// "One Click Demo Import - log_file_YYYY-MM-DD__HH-MM-SS"
		if ( ! empty( $attachment_title ) && (
			preg_match( '/log_file_\d{4}-\d{2}-\d{2}__\d{2}-\d{2}-\d{2}/i', $attachment_title ) ||
			preg_match( '/^(Smart One Click Setup|One Click Demo Import)\s*-\s*log_file_/i', $attachment_title )
		) ) {
			// Return empty array to skip this log file attachment.
			return array();
		}

		// If no URL found, continue with import.
		if ( empty( $attachment_url ) ) {
			return $data;
		}

		// Get file extension from URL.
		$file_extension = strtolower( pathinfo( wp_parse_url( $attachment_url, PHP_URL_PATH ), PATHINFO_EXTENSION ) );

		// List of invalid file extensions that should not be imported as media.
		$invalid_extensions = Helpers::apply_filters( 'smartocs/invalid_media_extensions', array(
			'zip',
			'rar',
			'tar',
			'gz',
			'7z',
			'exe',
			'dmg',
			'pkg',
		) );

		// Skip if file extension is in the invalid list.
		if ( ! empty( $file_extension ) && in_array( $file_extension, $invalid_extensions, true ) ) {
			// Return empty array to skip this attachment.
			return array();
		}

		return $data;
	}

	/**
	 * Skip Elementor kits during content import.
	 * Elementor kits are handled separately via elementor.json import,
	 * so we should not import them from content.xml to avoid duplicates.
	 *
	 * @since 1.0.0
	 *
	 * @param array $data Post data to be imported.
	 * @return array Empty array to skip, or original data to continue.
	 */
	public function skip_elementor_kits( $data ) {
		// Only process elementor_library posts.
		if ( empty( $data ) || empty( $data['post_type'] ) || $data['post_type'] !== 'elementor_library' ) {
			return $data;
		}

		// Check post title - common kit names to skip.
		$post_title = '';
		if ( ! empty( $data['post_title'] ) ) {
			$post_title = strtolower( trim( $data['post_title'] ) );
		} elseif ( ! empty( $data['title'] ) ) {
			$post_title = strtolower( trim( $data['title'] ) );
		}

		// Skip if title suggests it's a default/active kit (these are handled via elementor.json).
		$kit_titles = array( 'default kit', 'default', 'active kit', 'site kit' );
		foreach ( $kit_titles as $kit_title ) {
			if ( $post_title === $kit_title || strpos( $post_title, $kit_title ) !== false ) {
				// Likely a kit - skip it to be safe.
				return array();
			}
		}

		// Check if this post has the 'kit' taxonomy term.
		// Elementor kits have taxonomy 'elementor_library_type' with term 'kit'.
		if ( ! empty( $data['terms'] ) && is_array( $data['terms'] ) ) {
			foreach ( $data['terms'] as $term ) {
				// Check different possible term structures.
				$taxonomy = '';
				$slug = '';
				$name = '';
				
				if ( isset( $term['domain'] ) ) {
					$taxonomy = $term['domain'];
				} elseif ( isset( $term['taxonomy'] ) ) {
					$taxonomy = $term['taxonomy'];
				}
				
				if ( isset( $term['slug'] ) ) {
					$slug = strtolower( trim( $term['slug'] ) );
				}
				
				if ( isset( $term['name'] ) ) {
					$name = strtolower( trim( $term['name'] ) );
				}
				
				if ( $taxonomy === 'elementor_library_type' && ( $slug === 'kit' || $name === 'kit' ) ) {
					// This is an Elementor kit - skip it.
					// Kit settings are imported via elementor.json, not content.xml.
					return array();
				}
			}
		}

		// Check meta data for kit type (meta value might be serialized).
		if ( ! empty( $data['meta'] ) && is_array( $data['meta'] ) ) {
			foreach ( $data['meta'] as $meta ) {
				if ( empty( $meta['key'] ) ) {
					continue;
				}
				
				// Check for kit-related meta keys.
				if ( $meta['key'] === '_elementor_template_type' || $meta['key'] === 'elementor_template_type' ) {
					$meta_value = '';
					if ( isset( $meta['value'] ) ) {
						$meta_value = $meta['value'];
						// Handle serialized values.
						if ( is_serialized( $meta_value ) ) {
							$meta_value = maybe_unserialize( $meta_value );
						}
					}
					
					// Check if value is 'kit' (could be string or in array).
					if ( $meta_value === 'kit' || ( is_array( $meta_value ) && in_array( 'kit', $meta_value, true ) ) ) {
						// This is an Elementor kit - skip it.
						return array();
					}
				}
				
				// Also check for _elementor_page_settings which indicates it's a kit.
				if ( $meta['key'] === '_elementor_page_settings' && ! empty( $meta['value'] ) ) {
					// If it has page settings, it's likely a kit (kits have page settings).
					// Skip it to be safe - kits are handled via elementor.json.
					return array();
				}
			}
		}

		// Not a kit, continue with import (could be a template or other Elementor library item).
		return $data;
	}
}
