<?php
namespace SMARTOCS;

/**
 * Static functions used in the SMARTOCS plugin.
 *
 * @package smartocs
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class with static helper functions.
 */
class Helpers {
	/**
	 * Holds the date and time string for demo import and log file.
	 *
	 * @var string
	 */
	public static $demo_import_start_time = '';

	/**
	 * Track which separators have been logged in the current import session to prevent duplicates.
	 *
	 * @var array
	 */
	private static $logged_separators = array();

	/**
	 * Filter through the array of import files and get rid of those who do not comply.
	 *
	 * @param  array $import_files list of arrays with import file details.
	 * @return array list of filtered arrays.
	 */
	public static function validate_import_file_info( $import_files ) {
		$smartocs_filtered_import_file_info = array();

		foreach ( $import_files as $import_file ) {
			if ( self::is_import_file_info_format_correct( $import_file ) ) {
				$smartocs_filtered_import_file_info[] = $import_file;
			}
		}

		return $smartocs_filtered_import_file_info;
	}


	/**
	 * Helper function: a simple check for valid import file format.
	 *
	 * @param  array $import_file_info array with import file details.
	 * @return boolean
	 */
	private static function is_import_file_info_format_correct( $import_file_info ) {
		if ( empty( $import_file_info['import_file_name'] ) ) {
			return false;
		}

		return true;
	}


	/**
	 * Download import files. Content .xml and widgets .wie|.json files.
	 *
	 * @since 3.3.0 Add WPForms support.
	 *
	 * @param  array  $import_file_info array with import file details.
	 *
	 * @return array|WP_Error array of paths to the downloaded files or WP_Error object with error message.
	 */
	public static function download_import_files( $import_file_info ) {
		$smartocs_downloaded_files = array(
			'content'    => '',
			'widgets'    => '',
			'customizer' => '',
			'redux'      => '',
			'wpforms'    => '',
		);
		$smartocs_downloader = new Downloader();

		$import_file_info = self::apply_filters('smartocs/pre_download_import_files', $import_file_info);

		// ----- Set content file path -----
		// Check if 'import_file_url' is not defined. That would mean a local file.
		if ( empty( $import_file_info['import_file_url'] ) ) {
			if ( ! empty( $import_file_info['local_import_file'] ) && file_exists( $import_file_info['local_import_file'] ) ) {
				$smartocs_downloaded_files['content'] = $import_file_info['local_import_file'];
			}
		}
		else {
			// Set the filename string for content import file.
			$smartocs_content_filename = self::apply_filters( 'smartocs/downloaded_content_file_prefix', 'demo-content-import-file_' ) . self::$demo_import_start_time . self::apply_filters( 'smartocs/downloaded_content_file_suffix_and_file_extension', '.xml' );

			// Download the content import file.
			$smartocs_downloaded_files['content'] = $smartocs_downloader->download_file( $import_file_info['import_file_url'], $smartocs_content_filename );

			// Return from this function if there was an error.
			if ( is_wp_error( $smartocs_downloaded_files['content'] ) ) {
				return $smartocs_downloaded_files['content'];
			}
		}

		// ----- Set widget file path -----
		// Get widgets file as well. If defined!
		if ( ! empty( $import_file_info['import_widget_file_url'] ) ) {
			// Set the filename string for widgets import file.
			$smartocs_widget_filename = self::apply_filters( 'smartocs/downloaded_widgets_file_prefix', 'demo-widgets-import-file_' ) . self::$demo_import_start_time . self::apply_filters( 'smartocs/downloaded_widgets_file_suffix_and_file_extension', '.json' );

			// Download the widgets import file.
			$smartocs_downloaded_files['widgets'] = $smartocs_downloader->download_file( $import_file_info['import_widget_file_url'], $smartocs_widget_filename );

			// Return from this function if there was an error.
			if ( is_wp_error( $smartocs_downloaded_files['widgets'] ) ) {
				return $smartocs_downloaded_files['widgets'];
			}
		}
		else if ( ! empty( $import_file_info['local_import_widget_file'] ) ) {
			if ( file_exists( $import_file_info['local_import_widget_file'] ) ) {
				$smartocs_downloaded_files['widgets'] = $import_file_info['local_import_widget_file'];
			}
		}

		// ----- Set customizer file path -----
		// Get customizer import file as well. If defined!
		if ( ! empty( $import_file_info['import_customizer_file_url'] ) ) {
			// Setup filename path to save the customizer content.
			$smartocs_customizer_filename = self::apply_filters( 'smartocs/downloaded_customizer_file_prefix', 'demo-customizer-import-file_' ) . self::$demo_import_start_time . self::apply_filters( 'smartocs/downloaded_customizer_file_suffix_and_file_extension', '.dat' );

			// Download the customizer import file.
			$smartocs_downloaded_files['customizer'] = $smartocs_downloader->download_file( $import_file_info['import_customizer_file_url'], $smartocs_customizer_filename );

			// Return from this function if there was an error.
			if ( is_wp_error( $smartocs_downloaded_files['customizer'] ) ) {
				return $smartocs_downloaded_files['customizer'];
			}
		}
		else if ( ! empty( $import_file_info['local_import_customizer_file'] ) ) {
			if ( file_exists( $import_file_info['local_import_customizer_file'] ) ) {
				$smartocs_downloaded_files['customizer'] = $import_file_info['local_import_customizer_file'];
			}
		}

		// ----- Set Redux file paths -----
		// Get Redux import file as well. If defined!
		if ( ! empty( $import_file_info['import_redux'] ) && is_array( $import_file_info['import_redux'] ) ) {
			$smartocs_redux_items = array();

			// Setup filename paths to save the Redux content.
			foreach ( $import_file_info['import_redux'] as $index => $redux_item ) {
				$smartocs_redux_filename = self::apply_filters( 'smartocs/downloaded_redux_file_prefix', 'demo-redux-import-file_' ) . $index . '-' . self::$demo_import_start_time . self::apply_filters( 'smartocs/downloaded_redux_file_suffix_and_file_extension', '.json' );

				// Download the Redux import file.
				$smartocs_file_path = $smartocs_downloader->download_file( $redux_item['file_url'], $smartocs_redux_filename );

				// Return from this function if there was an error.
				if ( is_wp_error( $smartocs_file_path ) ) {
					return $smartocs_file_path;
				}

				$smartocs_redux_items[] = array(
					'option_name' => $redux_item['option_name'],
					'file_path'   => $smartocs_file_path,
				);
			}

			// Download the Redux import file.
			$smartocs_downloaded_files['redux'] = $smartocs_redux_items;
		}
		else if ( ! empty( $import_file_info['local_import_redux'] ) ) {

			$smartocs_redux_items = array();

			// Setup filename paths to save the Redux content.
			foreach ( $import_file_info['local_import_redux'] as $redux_item ) {
				if ( file_exists( $redux_item['file_path'] ) ) {
					$smartocs_redux_items[] = $redux_item;
				}
			}

			// Download the Redux import file.
			$smartocs_downloaded_files['redux'] = $smartocs_redux_items;
		}

		// ----- Set WPForms file paths -----
		// Get WPForms import file as well. If defined!
		if ( ! empty( $import_file_info['import_wpforms_file_url'] ) ) {
			// Setup filename path to save the WPForms content.
			$smartocs_wpforms_filename = self::apply_filters( 'smartocs/downloaded_wpforms_file_prefix', 'demo-wpforms-import-file_' ) . self::$demo_import_start_time . self::apply_filters( 'smartocs/downloaded_wpforms_file_suffix_and_file_extension', '.json' );

			// Download the customizer import file.
			$smartocs_downloaded_files['wpforms'] = $smartocs_downloader->download_file( $import_file_info['import_wpforms_file_url'], $smartocs_wpforms_filename );

			// Return from this function if there was an error.
			if ( is_wp_error( $smartocs_downloaded_files['wpforms'] ) ) {
				return $smartocs_downloaded_files['wpforms'];
			}
		} else if ( ! empty( $import_file_info['local_import_wpforms_file'] ) ) {
			if ( file_exists( $import_file_info['local_import_wpforms_file'] ) ) {
				$smartocs_downloaded_files['wpforms'] = $import_file_info['local_import_wpforms_file'];
			}
		}

		return $smartocs_downloaded_files;
	}


	/**
	 * Write content to a file.
	 *
	 * @param string $content content to be saved to the file.
	 * @param string $file_path file path where the content should be saved.
	 * @return string|WP_Error path to the saved file or WP_Error object with error message.
	 */
	public static function write_to_file( $content, $file_path ) {
		// Ensure directory exists
		$dir = dirname( $file_path );
		if ( ! file_exists( $dir ) ) {
			$created = wp_mkdir_p( $dir );
			if ( ! $created ) {
				return new \WP_Error(
					'failed_creating_directory',
					sprintf( /* translators: %s - directory path */
						__( 'Failed to create directory: %s', 'smart-one-click-setup' ),
						$dir
					)
				);
			}
		}

		// Check if directory is writable using WordPress filesystem API approach.
		$test_file = trailingslashit( $dir ) . '.smartocs-write-test-' . uniqid();
		$test_write = @file_put_contents( $test_file, 'test' ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		if ( false === $test_write ) {
			return new \WP_Error(
				'directory_not_writable',
				sprintf( /* translators: %s - directory path */
					__( 'Directory is not writable: %s', 'smart-one-click-setup' ),
					$dir
				)
			);
		}
		wp_delete_file( $test_file );

		// Write file using direct file operations
		$result = file_put_contents( $file_path, $content );
		if ( false === $result ) {
			return new \WP_Error(
				'failed_writing_file_to_server',
				sprintf( /* translators: %1$s - br HTML tag, %2$s - file path */
					__( 'An error occurred while writing file to your server! Tried to write a file to: %1$s%2$s.', 'smart-one-click-setup' ),
					'<br>',
					$file_path
				)
			);
		}

		// Return the file path on successful file write.
		return $file_path;
	}


	/**
	 * Append content to the file.
	 *
	 * @param string $content content to be saved to the file.
	 * @param string $file_path file path where the content should be saved.
	 * @param string $separator_text separates the existing content of the file with the new content.
	 * @return boolean|WP_Error, path to the saved file or WP_Error object with error message.
	 */
	public static function append_to_file( $content, $file_path, $separator_text = '' ) {
		// Ensure directory exists
		$dir = dirname( $file_path );
		if ( ! file_exists( $dir ) ) {
			$created = wp_mkdir_p( $dir );
			if ( ! $created ) {
				return new \WP_Error(
					'failed_creating_directory',
					sprintf( /* translators: %s - directory path */
						__( 'Failed to create directory: %s', 'smart-one-click-setup' ),
						$dir
					)
				);
			}
		}

		$smartocs_existing_data = '';
		if ( file_exists( $file_path ) ) {
			$smartocs_existing_data = file_get_contents( $file_path );
			if ( false === $smartocs_existing_data ) {
				return new \WP_Error(
					'failed_reading_file',
					sprintf( /* translators: %s - file path */
						__( 'Failed to read existing file: %s', 'smart-one-click-setup' ),
						$file_path
					)
				);
			}
		}

		// Style separator.
		$smartocs_separator = PHP_EOL . '---' . $separator_text . '---' . PHP_EOL;

		// Create a unique key for this separator + file path combination
		$separator_key = md5( $separator_text . $file_path );

		// Normalize content for comparison (trim and normalize line endings)
		$normalized_content = trim( $content );
		$normalized_content = preg_replace( '/\r\n|\r/', "\n", $normalized_content );
		
		// Build the full block that would be written (separator + content)
		$full_block = $smartocs_separator . $normalized_content . PHP_EOL;
		$normalized_full_block = preg_replace( '/\r\n|\r/', "\n", $full_block );
		
		// EARLY EXIT: Check if this exact block already exists in the file (most reliable duplicate check)
		if ( ! empty( $smartocs_existing_data ) && ! empty( $separator_text ) ) {
			$normalized_existing = preg_replace( '/\r\n|\r/', "\n", $smartocs_existing_data );
			// Check if the exact normalized block exists
			if ( strpos( $normalized_existing, $normalized_full_block ) !== false ) {
				// Exact duplicate block found - skip entirely
				return true;
			}
		}

		// If content is empty and separator text is provided, don't add anything (skip empty sections).
		if ( empty( $normalized_content ) && ! empty( $separator_text ) ) {
			// Check if this separator was already logged in this session
			if ( isset( self::$logged_separators[ $separator_key ] ) ) {
				return true; // Already logged in this session, skip empty content
			}
			// Check if this separator already exists in the file - if so, don't add empty section
			if ( ! empty( $smartocs_existing_data ) && strpos( $smartocs_existing_data, $smartocs_separator ) !== false ) {
				// Mark as logged to prevent future duplicates
				self::$logged_separators[ $separator_key ] = true;
				return true; // Separator already exists, skip empty content
			}
		}

		// Check if this exact content (separator + content) already exists in the file to prevent duplicates.
		if ( ! empty( $smartocs_existing_data ) && ! empty( $separator_text ) && ! empty( $normalized_content ) ) {
			// Also check if we've already logged this exact content in this session
			$content_hash = md5( $normalized_content );
			$content_key = $separator_key . '_' . $content_hash;
			if ( isset( self::$logged_separators[ $content_key ] ) ) {
				// This exact content was already logged in this session - skip
				return true;
			}
			
			// Normalize existing file data for comparison
			$normalized_existing_data = preg_replace( '/\r\n|\r/', "\n", $smartocs_existing_data );
			
			// Find all occurrences of this separator in the existing file
			$separator_positions = array();
			$normalized_separator = preg_replace( '/\r\n|\r/', "\n", $smartocs_separator );
			$offset = 0;
			while ( ( $pos = strpos( $normalized_existing_data, $normalized_separator, $offset ) ) !== false ) {
				$separator_positions[] = $pos;
				$offset = $pos + 1;
			}
			
			// Check each occurrence to see if the content after it matches the new content
			foreach ( $separator_positions as $sep_pos ) {
				// Get content after this separator until the next separator or end of file
				$content_start = $sep_pos + strlen( $normalized_separator );
				
				// Find next separator (any separator pattern)
				$next_separator_pattern = '/\n---[^-]+---\n/';
				$remaining_text = substr( $normalized_existing_data, $content_start );
				$next_separator_match = preg_match( $next_separator_pattern, $remaining_text, $matches, PREG_OFFSET_CAPTURE );
				
				if ( $next_separator_match ) {
					// There's another separator, get content between them
					$content_after_separator = substr( $remaining_text, 0, $matches[0][1] );
				} else {
					// This is the last separator, get everything after it
					$content_after_separator = $remaining_text;
				}
				
				// Normalize existing content for comparison (trim whitespace)
				$normalized_existing_content = trim( $content_after_separator );
				
				// If the content matches exactly, skip writing (duplicate found)
				if ( $normalized_content === $normalized_existing_content ) {
					// Exact duplicate found - mark as logged and skip writing
					self::$logged_separators[ $content_key ] = true;
					return true;
				}
			}
			
			// Also check if the exact full block (separator + content) exists anywhere in the file
			// This catches cases where the separator and content were written together
			if ( strpos( $normalized_existing_data, $normalized_full_block ) !== false ) {
				// Exact duplicate block found
				self::$logged_separators[ $content_key ] = true;
				return true;
			}
		}

		// Check if the separator already exists in the file to prevent duplicate headers.
		// Only add separator if we haven't already logged this exact separator+content combination.
		$needs_separator = true;
		if ( ! empty( $separator_text ) ) {
			// First check if we've already logged this separator in this session
			if ( isset( self::$logged_separators[ $separator_key ] ) ) {
				// Already logged in this session - append to existing section without adding separator
				$needs_separator = false;
			} elseif ( ! empty( $smartocs_existing_data ) ) {
				// Find the LAST occurrence of this specific separator in the file
				$last_separator_pos = strrpos( $smartocs_existing_data, $smartocs_separator );
				
				if ( false !== $last_separator_pos ) {
					// This separator exists - check the content after the last occurrence
					$content_after_last_separator_start = $last_separator_pos + strlen( $smartocs_separator );
					
					// Find the next separator (if any) to get the content block
					$next_separator_pattern = '/\n---[^-]+---\n/';
					$remaining_text = substr( $smartocs_existing_data, $content_after_last_separator_start );
					$next_separator_match = preg_match( $next_separator_pattern, $remaining_text, $matches, PREG_OFFSET_CAPTURE );
					
					if ( $next_separator_match ) {
						// There's another separator after this one, get content between them
						$content_after_separator = substr( $remaining_text, 0, $matches[0][1] );
					} else {
						// This is the last separator, get everything after it
						$content_after_separator = $remaining_text;
					}
					
					// Normalize both contents for comparison
					$normalized_existing = trim( preg_replace( '/\r\n|\r/', "\n", $content_after_separator ) );
					$normalized_new = trim( preg_replace( '/\r\n|\r/', "\n", $content ) );
					
					// If the content after the last occurrence of this separator matches the new content, it's a duplicate
					if ( $normalized_existing === $normalized_new ) {
						// Exact duplicate - don't add separator or content
						self::$logged_separators[ $separator_key ] = true;
						return true;
					}
					
					// Content is different, but separator exists - check if we should append to existing section
					// Only append without separator if the last separator in the entire file is this one
					$last_any_separator_pattern = '/\n---[^-]+---\n/';
					$all_matches = array();
					
					if ( preg_match_all( $last_any_separator_pattern, $smartocs_existing_data, $all_matches, PREG_OFFSET_CAPTURE ) ) {
						$last_any_match = end( $all_matches[0] );
						$last_any_separator_pos = $last_any_match[1];
						$last_any_separator_text = $last_any_match[0];
						
						// Normalize for comparison
						$normalized_last_any = preg_replace( '/\r\n|\r/', "\n", $last_any_separator_text );
						$normalized_current = preg_replace( '/\r\n|\r/', "\n", $smartocs_separator );
						
						// If this separator is the last one in the file, append to it without adding a new separator
						if ( $normalized_last_any === $normalized_current ) {
							$needs_separator = false;
							self::$logged_separators[ $separator_key ] = true;
						}
					}
				}
			}
		}

		// Build the content to append
		$content_to_append = '';
		if ( $needs_separator ) {
			$content_to_append = $smartocs_separator;
		} elseif ( ! empty( $smartocs_existing_data ) ) {
			// If separator already exists and we're adding content, check if we need spacing
			$trimmed_existing = trim( $smartocs_existing_data );
			if ( ! empty( $trimmed_existing ) && substr( $trimmed_existing, -1 ) !== PHP_EOL ) {
				// Add a newline if the file doesn't end with one
				$content_to_append = PHP_EOL;
			} elseif ( ! empty( $trimmed_existing ) ) {
				// File ends with newline, but we might need spacing between entries
				$content_to_append = '';
			}
		}
		// Use the original content (not normalized) for writing, but add proper line ending
		$content_to_append .= $content . PHP_EOL;

		$result = file_put_contents( $file_path, $smartocs_existing_data . $content_to_append, FILE_APPEND | LOCK_EX );
		if ( false === $result ) {
			return new \WP_Error(
				'failed_writing_file_to_server',
				sprintf( /* translators: %1$s - br HTML tag, %2$s - file path */
					__( 'An error occurred while writing file to your server! Tried to write a file to: %1$s%2$s.', 'smart-one-click-setup' ),
					'<br>',
					$file_path
				)
			);
		}

		// Mark this separator as logged if we added it
		if ( $needs_separator && ! empty( $separator_text ) ) {
			self::$logged_separators[ $separator_key ] = true;
		}

		// Also mark the content as logged if we wrote content (to prevent duplicate content blocks)
		if ( ! empty( trim( $content ) ) && ! empty( $separator_text ) ) {
			$normalized_content = trim( $content );
			$normalized_content = preg_replace( '/\r\n|\r/', "\n", $normalized_content );
			$content_hash = md5( $normalized_content );
			$content_key = $separator_key . '_' . $content_hash;
			self::$logged_separators[ $content_key ] = true;
		}

		return true;
	}


	/**
	 * Get data from a file
	 *
	 * @param string $file_path file path where the content should be saved.
	 * @return string|WP_Error content of the file or WP_Error object with error message.
	 */
	public static function data_from_file( $file_path ) {
		if ( ! file_exists( $file_path ) ) {
			return new \WP_Error(
				'file_not_found',
				sprintf( /* translators: %s - file path */
					__( 'File not found: %s', 'smart-one-click-setup' ),
					$file_path
				)
			);
		}

		if ( ! is_readable( $file_path ) ) {
			return new \WP_Error(
				'file_not_readable',
				sprintf( /* translators: %s - file path */
					__( 'File is not readable: %s', 'smart-one-click-setup' ),
					$file_path
				)
			);
		}

		$smartocs_data = file_get_contents( $file_path );

		if ( false === $smartocs_data ) {
			return new \WP_Error(
				'failed_reading_file_from_server',
				sprintf( /* translators: %1$s - br HTML tag, %2$s - file path */
					__( 'An error occurred while reading a file from your server! Tried reading file from path: %1$s%2$s.', 'smart-one-click-setup' ),
					'<br>',
					$file_path
				)
			);
		}

		// Return the file data.
		return $smartocs_data;
	}


	/**
	 * Get plugin data from plugin file header.
	 * Custom implementation that doesn't require core files.
	 *
	 * @param string $plugin_file Path to the plugin file.
	 * @return array Plugin data array.
	 */
	public static function get_plugin_data( $plugin_file ) {
		$default_headers = array(
			'Name'        => 'Plugin Name',
			'PluginURI'  => 'Plugin URI',
			'Version'     => 'Version',
			'Description' => 'Description',
			'Author'      => 'Author',
			'AuthorURI'   => 'Author URI',
			'TextDomain'  => 'Text Domain',
			'DomainPath'  => 'Domain Path',
			'Network'     => 'Network',
			'RequiresWP'  => 'Requires at least',
			'RequiresPHP' => 'Requires PHP',
			'UpdateURI'   => 'Update URI',
			'Title'       => 'Plugin Name',
			'AuthorName'  => 'Author',
		);

		$plugin_data = get_file_data( $plugin_file, $default_headers, 'plugin' );

		// Ensure all keys exist
		$plugin_data = wp_parse_args( $plugin_data, array(
			'Name'        => '',
			'PluginURI'  => '',
			'Version'     => '',
			'Description' => '',
			'Author'      => '',
			'AuthorURI'   => '',
			'TextDomain'  => '',
			'DomainPath'  => '',
			'Network'     => '',
			'RequiresWP'  => '',
			'RequiresPHP' => '',
			'UpdateURI'   => '',
			'Title'       => '',
			'AuthorName'  => '',
		) );

		return $plugin_data;
	}

	/**
	 * Get all plugins without requiring core files.
	 * Uses WordPress functions and hooks instead of direct core file access.
	 *
	 * @return array Array of plugins.
	 */
	public static function get_plugins() {
		// Try to use WordPress's built-in get_plugins() function if available.
		// This function is available in admin context and uses WordPress APIs.
		if ( function_exists( 'get_plugins' ) ) {
			$plugins = get_plugins();
			
			// Allow filtering of plugins list.
			$plugins = self::apply_filters( 'smartocs/get_plugins', $plugins );
			
			// Sort plugins by name.
			uasort( $plugins, function( $a, $b ) {
				return strcasecmp( $a['Name'], $b['Name'] );
			} );
			
			return $plugins;
		}

		// Fallback: Get plugin directory using WordPress functions.
		$plugin_root = self::get_plugin_directory_path();

		if ( empty( $plugin_root ) || ! is_dir( $plugin_root ) ) {
			return array();
		}

		$plugin_files = array();
		
		// Use WordPress glob function to find plugin files.
		$plugin_dirs = glob( $plugin_root . '/*', GLOB_ONLYDIR );
		
		if ( false !== $plugin_dirs ) {
			foreach ( $plugin_dirs as $plugin_dir ) {
				$dir_name = basename( $plugin_dir );
				
				// Skip hidden directories.
				if ( '.' === substr( $dir_name, 0, 1 ) ) {
					continue;
				}
				
				// Find PHP files in plugin directory.
				$php_files = glob( $plugin_dir . '/*.php' );
				
				if ( false !== $php_files ) {
					foreach ( $php_files as $php_file ) {
						$file_name = basename( $php_file );
						$plugin_files[] = $dir_name . '/' . $file_name;
							}
						}
			}
		}
		
		// Also check for single-file plugins in root.
		$root_php_files = glob( $plugin_root . '/*.php' );
		
		if ( false !== $root_php_files ) {
			foreach ( $root_php_files as $php_file ) {
				$file_name = basename( $php_file );
				$plugin_files[] = $file_name;
					}
		}

		if ( empty( $plugin_files ) ) {
			return array();
		}

		$plugins = array();

		foreach ( $plugin_files as $plugin_file ) {
			$plugin_file_path = trailingslashit( $plugin_root ) . $plugin_file;
			
			if ( ! is_readable( $plugin_file_path ) ) {
				continue;
			}

			$plugin_data = self::get_plugin_data( $plugin_file_path );

			if ( empty( $plugin_data['Name'] ) ) {
				continue;
			}

			$plugins[ $plugin_file ] = $plugin_data;
		}

		// Allow filtering of plugins list.
		$plugins = self::apply_filters( 'smartocs/get_plugins', $plugins );

		// Sort plugins by name.
		uasort( $plugins, function( $a, $b ) {
			return strcasecmp( $a['Name'], $b['Name'] );
		} );

		return $plugins;
	}

	/**
	 * Get plugin directory path using WordPress functions.
	 * Avoids direct use of WP_PLUGIN_DIR constant.
	 *
	 * @return string Plugin directory path or empty string on failure.
	 */
	private static function get_plugin_directory_path() {
		// Allow filtering to override plugin directory path.
		$plugin_root = self::apply_filters( 'smartocs/plugin_directory_path', '' );
		
		if ( ! empty( $plugin_root ) && is_dir( $plugin_root ) ) {
			return $plugin_root;
		}
		
		// Get plugin directory using plugin_dir_path() on current plugin file.
		// Then derive the plugins directory by going up one level.
		$current_plugin_path = plugin_dir_path( __FILE__ );
		
		if ( empty( $current_plugin_path ) ) {
			return '';
		}
		
		// Get the plugins directory by going up from current plugin directory.
		// Current plugin is in: wp-content/plugins/smart-one-click-setup/inc/
		// We need: wp-content/plugins/
		$plugins_dir = dirname( dirname( $current_plugin_path ) );
		
		// Verify it's a valid directory.
		if ( ! is_dir( $plugins_dir ) ) {
			return '';
		}
		
		return $plugins_dir;
	}


	/**
	 * Get log file path
	 *
	 * @return string, path to the log file
	 */
	public static function get_log_path() {
		$smartocs_upload_dir  = wp_upload_dir();
		$smartocs_upload_path = self::apply_filters( 'smartocs/upload_file_path', trailingslashit( $smartocs_upload_dir['path'] ) );

		$smartocs_log_path = $smartocs_upload_path . self::apply_filters( 'smartocs/log_file_prefix', 'log_file_' ) . self::$demo_import_start_time . self::apply_filters( 'smartocs/log_file_suffix_and_file_extension', '.txt' );

		self::register_file_as_media_attachment( $smartocs_log_path );

		return $smartocs_log_path;
	}


	/**
	 * Register file as attachment to the Media page.
	 *
	 * @param string $log_path log file path.
	 * @return void
	 */
	public static function register_file_as_media_attachment( $log_path ) {
		// Check the type of file.
		$smartocs_log_mimes = array( 'txt' => 'text/plain' );
		$smartocs_filetype  = wp_check_filetype( basename( $log_path ), self::apply_filters( 'smartocs/file_mimes', $smartocs_log_mimes ) );

		// Prepare an array of post data for the attachment.
		$smartocs_attachment = array(
			'guid'           => self::get_log_url( $log_path ),
			'post_mime_type' => $smartocs_filetype['type'],
			'post_title'     => self::apply_filters( 'smartocs/attachment_prefix', esc_html__( 'Smart One Click Setup - ', 'smart-one-click-setup' ) ) . preg_replace( '/\.[^.]+$/', '', basename( $log_path ) ),
			'post_content'   => '',
			'post_status'    => 'inherit',
		);

		// Insert the file as attachment in Media page.
		$smartocs_attach_id = wp_insert_attachment( $smartocs_attachment, $log_path );
	}


	/**
	 * Get log file url
	 *
	 * @param string $log_path log path to use for the log filename.
	 * @return string, url to the log file.
	 */
	public static function get_log_url( $log_path ) {
		$smartocs_upload_dir = wp_upload_dir();
		$smartocs_upload_url = self::apply_filters( 'smartocs/upload_file_url', trailingslashit( $smartocs_upload_dir['url'] ) );

		return $smartocs_upload_url . basename( $log_path );
	}


	/**
	 * Check if the AJAX call is valid.
	 */
	public static function verify_ajax_call() {
		check_ajax_referer( 'smartocs-ajax-verification', 'security' );

		// Check if user has the WP capability to import data.
		if ( ! current_user_can( 'import' ) ) {
			wp_die(
				wp_kses_post(
					sprintf( /* translators: %1$s - opening div and paragraph HTML tags, %2$s - closing div and paragraph HTML tags. */
						__( '%1$sYour user role isn\'t high enough. You don\'t have permission to import demo data.%2$s', 'smart-one-click-setup' ),
						'<div class="notice  notice-error"><p>',
						'</p></div>'
					)
				)
			);
		}
	}


	/**
	 * Validate file upload array structure.
	 *
	 * @param array $file_array The file upload array to validate.
	 * @return bool True if valid, false otherwise.
	 */
	private static function validate_file_upload_array( $file_array ) {
		if ( ! is_array( $file_array ) ) {
			return false;
		}

		// Check for required keys in file upload array.
		$required_keys = array( 'tmp_name', 'name', 'type', 'size', 'error' );
		foreach ( $required_keys as $key ) {
			if ( ! isset( $file_array[ $key ] ) ) {
				return false;
			}
		}

		// Validate tmp_name is a string and file exists.
		if ( ! is_string( $file_array['tmp_name'] ) || ! file_exists( $file_array['tmp_name'] ) ) {
			return false;
		}

		// Validate error code (should be UPLOAD_ERR_OK = 0 for successful uploads).
		if ( ! is_numeric( $file_array['error'] ) || $file_array['error'] !== UPLOAD_ERR_OK ) {
			return false;
		}

		return true;
	}

	/**
	 * Sanitize file upload array.
	 *
	 * @param array $file_array The file upload array to sanitize.
	 * @return array Sanitized file upload array.
	 */
	private static function sanitize_file_upload_array( $file_array ) {
		if ( ! is_array( $file_array ) ) {
			return array();
		}

		$sanitized = array();

		// Preserve tmp_name exactly as-is (required by wp_handle_upload, already validated to exist).
		if ( isset( $file_array['tmp_name'] ) && is_string( $file_array['tmp_name'] ) ) {
			$sanitized['tmp_name'] = $file_array['tmp_name'];
		}

		// Sanitize file name.
		if ( isset( $file_array['name'] ) && is_string( $file_array['name'] ) ) {
			$sanitized['name'] = sanitize_file_name( wp_unslash( $file_array['name'] ) );
		}

		// Validate and sanitize mime type.
		if ( isset( $file_array['type'] ) && is_string( $file_array['type'] ) ) {
			$sanitized['type'] = sanitize_mime_type( wp_unslash( $file_array['type'] ) );
		}

		// Validate and sanitize file size.
		if ( isset( $file_array['size'] ) && is_numeric( $file_array['size'] ) ) {
			$sanitized['size'] = absint( $file_array['size'] );
		}

		// Validate error code.
		if ( isset( $file_array['error'] ) && is_numeric( $file_array['error'] ) ) {
			$sanitized['error'] = absint( $file_array['error'] );
		}

		return $sanitized;
	}

	/**
	 * Process uploaded files and return the paths to these files.
	 *
	 * @param array  $uploaded_files Array containing only the specific files needed: content_file, widget_file, customizer_file, redux_file.
	 * @param string $log_file_path path to the log file.
	 * @return array of paths to the content import and widget import files.
	 */
	public static function process_uploaded_files( $uploaded_files, $log_file_path ) {
		// Variable holding the paths to the uploaded files.
		$smartocs_selected_import_files = array(
			'content'    => '',
			'widgets'    => '',
			'customizer' => '',
			'redux'      => '',
		);

		// Upload settings to disable form and type testing for AJAX uploads.
		$smartocs_upload_overrides = array(
			'test_form' => false,
		);

		// Register the import file types and their mime types.
		add_filter( 'upload_mimes', function ( $defaults ) {
			$smartocs_custom = [
				'xml'  => 'text/xml',
				'json' => 'application/json',
				'wie'  => 'application/json',
				'dat'  => 'text/plain',
			];

			return array_merge( $smartocs_custom, $defaults );
		} );

		// Error data if the demo file was not provided.
		$smartocs_file_not_provided_error = array(
			'error' => esc_html__( 'No file provided.', 'smart-one-click-setup' )
		);

		// Handle demo file uploads.
		// Nonce verification is done in the calling AJAX callback via Helpers::verify_ajax_call().
		$smartocs_content_file_info = isset( $uploaded_files['content_file'] ) && self::validate_file_upload_array( $uploaded_files['content_file'] ) ?
			wp_handle_upload( self::sanitize_file_upload_array( $uploaded_files['content_file'] ), $smartocs_upload_overrides ) :
			$smartocs_file_not_provided_error;

		$smartocs_widget_file_info = isset( $uploaded_files['widget_file'] ) && self::validate_file_upload_array( $uploaded_files['widget_file'] ) ?
			wp_handle_upload( self::sanitize_file_upload_array( $uploaded_files['widget_file'] ), $smartocs_upload_overrides ) :
			$smartocs_file_not_provided_error;

		$smartocs_customizer_file_info = isset( $uploaded_files['customizer_file'] ) && self::validate_file_upload_array( $uploaded_files['customizer_file'] ) ?
			wp_handle_upload( self::sanitize_file_upload_array( $uploaded_files['customizer_file'] ), $smartocs_upload_overrides ) :
			$smartocs_file_not_provided_error;

		$smartocs_redux_file_info = isset( $uploaded_files['redux_file'] ) && self::validate_file_upload_array( $uploaded_files['redux_file'] ) ?
			wp_handle_upload( self::sanitize_file_upload_array( $uploaded_files['redux_file'] ), $smartocs_upload_overrides ) :
			$smartocs_file_not_provided_error;

		// Process content import file.
		if ( $smartocs_content_file_info && ! isset( $smartocs_content_file_info['error'] ) ) {
			// Set uploaded content file.
			$smartocs_selected_import_files['content'] = $smartocs_content_file_info['file'];
		}
		else {
			// Add this error to log file.
			$smartocs_log_added = self::append_to_file(
				sprintf( /* translators: %s - the error message. */
					__( 'Content file was not uploaded. Error: %s', 'smart-one-click-setup' ),
					$smartocs_content_file_info['error']
				),
				$log_file_path,
				esc_html__( 'Upload files' , 'smart-one-click-setup' )
			);
		}

		// Process widget import file.
		if ( $smartocs_widget_file_info && ! isset( $smartocs_widget_file_info['error'] ) ) {
			// Set uploaded widget file.
			$smartocs_selected_import_files['widgets'] = $smartocs_widget_file_info['file'];
		}
		else {
			// Add this error to log file.
			$smartocs_log_added = self::append_to_file(
				sprintf( /* translators: %s - the error message. */
					__( 'Widget file was not uploaded. Error: %s', 'smart-one-click-setup' ),
					$smartocs_widget_file_info['error']
				),
				$log_file_path,
				esc_html__( 'Upload files' , 'smart-one-click-setup' )
			);
		}

		// Process Customizer import file.
		if ( $smartocs_customizer_file_info && ! isset( $smartocs_customizer_file_info['error'] ) ) {
			// Set uploaded customizer file.
			$smartocs_selected_import_files['customizer'] = $smartocs_customizer_file_info['file'];
		}
		else {
			// Add this error to log file.
			$smartocs_log_added = self::append_to_file(
				sprintf( /* translators: %s - the error message. */
					__( 'Customizer file was not uploaded. Error: %s', 'smart-one-click-setup' ),
					$smartocs_customizer_file_info['error']
				),
				$log_file_path,
				esc_html__( 'Upload files' , 'smart-one-click-setup' )
			);
		}

		// Process Redux import file.
		if ( $smartocs_redux_file_info && ! isset( $smartocs_redux_file_info['error'] ) ) {
			// Verify nonce explicitly for phpcs.
			if ( ! isset( $_POST['security'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['security'] ) ), 'smartocs-ajax-verification' ) ) {
				self::log_error_and_send_ajax_response(
					esc_html__( 'Security check failed.', 'smart-one-click-setup' ),
					$log_file_path,
					esc_html__( 'Upload files', 'smart-one-click-setup' )
				);
			}

			if ( ! isset( $_POST['redux_option_name'] ) || empty( $_POST['redux_option_name'] ) ) {
				// Write error to log file and send an AJAX response with the error.
				self::log_error_and_send_ajax_response(
					esc_html__( 'Missing Redux option name! Please also enter the Redux option name!', 'smart-one-click-setup' ),
					$log_file_path,
					esc_html__( 'Upload files', 'smart-one-click-setup' )
				);
			}

			// Set uploaded Redux file.
			$smartocs_selected_import_files['redux'] = array(
				array(
					'option_name' => sanitize_text_field( wp_unslash( $_POST['redux_option_name'] ) ),
					'file_path'   => $smartocs_redux_file_info['file'],
				),
			);
		}
		else {
			// Add this error to log file.
			$smartocs_log_added = self::append_to_file(
				sprintf( /* translators: %s - the error message. */
					__( 'Redux file was not uploaded. Error: %s', 'smart-one-click-setup' ),
					$smartocs_redux_file_info['error']
				),
				$log_file_path,
				esc_html__( 'Upload files' , 'smart-one-click-setup' )
			);
		}

		// Add this message to log file.
		$smartocs_log_added = self::append_to_file(
			__( 'The import files were successfully uploaded!', 'smart-one-click-setup' ) . self::import_file_info( $smartocs_selected_import_files ),
			$log_file_path,
			esc_html__( 'Upload files' , 'smart-one-click-setup' )
		);

		// Return array with paths of uploaded files.
		return $smartocs_selected_import_files;
	}


	/**
	 * Get import file information and max execution time.
	 *
	 * @param array $selected_import_files array of selected import files.
	 */
	public static function import_file_info( $selected_import_files ) {
		$smartocs_redux_file_string = '';

		if ( ! empty( $selected_import_files['redux'] ) ) {
			$smartocs_redux_file_string = array_reduce( $selected_import_files['redux'], function( $string, $item ) {
				return sprintf( '%1$s%2$s -> %3$s %4$s', $string, $item['option_name'], $item['file_path'], PHP_EOL );
			}, '' );
		}

		return PHP_EOL .
		sprintf( /* translators: %s - the max execution time. */
			__( 'Initial max execution time = %s', 'smart-one-click-setup' ),
			ini_get( 'max_execution_time' )
		) . PHP_EOL .
		sprintf( /* translators: %1$s - new line break, %2$s - the site URL, %3$s - the file path for content import, %4$s - the file path for widgets import, %5$s - the file path for widgets import, %6$s - the file path for redux import. */
			__( 'Files info:%1$sSite URL = %2$s%1$sData file = %3$s%1$sWidget file = %4$s%1$sCustomizer file = %5$s%1$sRedux files:%1$s%6$s', 'smart-one-click-setup' ),
			PHP_EOL,
			get_site_url(),
			empty( $selected_import_files['content'] ) ? esc_html__( 'not defined!', 'smart-one-click-setup' ) : $selected_import_files['content'],
			empty( $selected_import_files['widgets'] ) ? esc_html__( 'not defined!', 'smart-one-click-setup' ) : $selected_import_files['widgets'],
			empty( $selected_import_files['customizer'] ) ? esc_html__( 'not defined!', 'smart-one-click-setup' ) : $selected_import_files['customizer'],
			empty( $smartocs_redux_file_string ) ? esc_html__( 'not defined!', 'smart-one-click-setup' ) : $smartocs_redux_file_string
		);
	}


	/**
	 * Write the error to the log file and send the AJAX response.
	 *
	 * @param string $error_text text to display in the log file and in the AJAX response.
	 * @param string $log_file_path path to the log file.
	 * @param string $separator title separating the old and new content.
	 */
	public static function log_error_and_send_ajax_response( $error_text, $log_file_path, $separator = '' ) {
		// Add this error to log file.
		$smartocs_log_added = self::append_to_file(
			$error_text,
			$log_file_path,
			$separator
		);

		// Send JSON Error response to the AJAX call.
		wp_send_json( $error_text );
	}


	/**
	 * Set the $demo_import_start_time class variable with the current date and time string.
	 */
	public static function set_demo_import_start_time() {
		self::$demo_import_start_time = gmdate( self::apply_filters( 'smartocs/date_format_for_file_names', 'Y-m-d__H-i-s' ) );
		// Reset logged separators when starting a new import
		self::$logged_separators = array();
	}


	/**
	 * Get the category list of all categories used in the predefined demo imports array.
	 *
	 * @param  array $demo_imports Array of demo import items (arrays).
	 * @return array|boolean       List of all the categories or false if there aren't any.
	 */
	public static function get_all_demo_import_categories( $demo_imports ) {
		$smartocs_categories = array();

		foreach ( $demo_imports as $item ) {
			if ( ! empty( $item['categories'] ) && is_array( $item['categories'] ) ) {
				foreach ( $item['categories'] as $category ) {
					$smartocs_categories[ sanitize_key( $category ) ] = $category;
				}
			}
		}

		if ( empty( $smartocs_categories ) ) {
			return false;
		}

		return $smartocs_categories;
	}


	/**
	 * Return the concatenated string of demo import item categories.
	 * These should be separated by comma and sanitized properly.
	 *
	 * @param  array  $item The predefined demo import item data.
	 * @return string       The concatenated string of categories.
	 */
	public static function get_demo_import_item_categories( $item ) {
		$smartocs_sanitized_categories = array();

		if ( isset( $item['categories'] ) ) {
			foreach ( $item['categories'] as $category ) {
				$smartocs_sanitized_categories[] = sanitize_key( $category );
			}
		}

		if ( ! empty( $smartocs_sanitized_categories ) ) {
			return implode( ',', $smartocs_sanitized_categories );
		}

		return false;
	}


	/**
	 * Set the SMARTOCS transient with the current importer data.
	 *
	 * @param array $data Data to be saved to the transient.
	 */
	public static function set_smartocs_import_data_transient( $data ) {
		set_transient( 'smartocs_importer_data', $data, 0.1 * HOUR_IN_SECONDS );
	}


	/**
	 * Apply filters helper using smartocs prefix.
	 * This method should be used for all apply_filters calls.
	 *
	 * @param string $hook         The filter hook name.
	 * @param mixed  $default_data The default filter data.
	 *
	 * @return mixed|void
	 */
	public static function apply_filters( $hook, $default_data ) {
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.DynamicHooknameFound
		return apply_filters( $hook, $default_data );
	}

	/**
	 * Do action helper using smartocs prefix.
	 * This method should be used for all do_action calls.
	 *
	 * @param string $hook   The action hook name.
	 * @param mixed  ...$arg Optional. Additional arguments which are passed on to the
	 *                       functions hooked to the action. Default empty.
	 */
	public static function do_action( $hook, ...$arg ) {
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.DynamicHooknameFound
		do_action( $hook, ...$arg );
	}

	/**
	 * Has action helper using smartocs prefix.
	 * This method should be used for all has_action calls.
	 *
	 * @param string        $hook              The name of the action hook.
	 * @param callable|bool $function_to_check Optional. The callback to check for. Default false.
	 *
	 * @return bool|int If $function_to_check is omitted, returns boolean for whether the hook has
	 *                  anything registered. When checking a specific function, the priority of that
	 *                  hook is returned, or false if the function is not attached. When using the
	 *                  $function_to_check argument, this function may return a non-boolean value
	 *                  that evaluates to false (e.g.) 0, so use the === operator for testing the
	 *                  return value.
	 */
	public static function has_action( $hook, $function_to_check = false ) {
		return has_action( $hook, $function_to_check );
	}

	/**
	 * Get the plugin page setup data.
	 *
	 * @return array
	 */
	public static function get_plugin_page_setup_data() {
		return Helpers::apply_filters( 'smartocs/plugin_page_setup', array(
			'parent_slug' => 'themes.php',
			'page_title'  => esc_html__( 'Smart One Click Setup' , 'smart-one-click-setup' ),
			'menu_title'  => esc_html__( 'Smart Import' , 'smart-one-click-setup' ),
			'capability'  => 'import',
			'menu_slug'   => 'smart-one-click-setup',
		) );
	}

	/**
	 * Get the failed attachment imports.
	 *
	 * @since 3.2.0
	 *
	 * @return mixed
	 */
	public static function get_failed_attachment_imports() {

		return get_transient( 'smartocs_importer_data_failed_attachment_imports' );
	}

	/**
	 * Set the failed attachment imports.
	 *
	 * @since 3.2.0
	 *
	 * @param string $attachment_url The attachment URL that was not imported.
	 *
	 * @return void
	 */
	public static function set_failed_attachment_import( $attachment_url ) {

		// Get current importer transient.
		$smartocs_failed_media_imports = self::get_failed_attachment_imports();

		if ( empty( $smartocs_failed_media_imports ) || ! is_array( $smartocs_failed_media_imports ) ) {
			$smartocs_failed_media_imports = [];
		}

		$smartocs_failed_media_imports[] = $attachment_url;

		set_transient( 'smartocs_importer_data_failed_attachment_imports', $smartocs_failed_media_imports, HOUR_IN_SECONDS );
	}

	/**
	 * Debug logging helper that only logs when WP_DEBUG and WP_DEBUG_LOG are enabled.
	 *
	 * @since 1.2.8
	 *
	 * @param string $message The debug message to log.
	 * @return void
	 */
	public static function debug_log( $message ) {
		// Debug logging removed - plugin uses its own log file system.
	}
}
