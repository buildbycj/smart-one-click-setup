<?php
/**
 * Helper class for easily adding predefined import files.
 *
 * @package socs
 */

namespace SOCS;

/**
 * ImportHelper class for simplified import file registration.
 *
 * This class provides an easy way for theme developers to add multiple
 * remote or local import files without manually constructing arrays.
 *
 * @since 1.0.0
 */
class ImportHelper {

	/**
	 * Storage for import files added via helper methods.
	 *
	 * @var array
	 */
	private static $imports = array();

	/**
	 * Add a single import file with all details.
	 *
	 * @param string      $name         Import name (required).
	 * @param string      $zip_url      Remote ZIP file URL (optional if zip_path provided).
	 * @param string      $zip_path     Local ZIP file path (optional if zip_url provided).
	 * @param string      $description  Import description (optional).
	 * @param string      $preview_image Preview image URL (optional).
	 * @param string      $preview_url  Preview site URL (optional).
	 * @param callable    $before_import Callback function before import (optional).
	 * @param callable    $after_import  Callback function after import (optional).
	 * @return void
	 */
	public static function add( $name, $zip_url = '', $zip_path = '', $description = '', $preview_image = '', $preview_url = '', $before_import = null, $after_import = null ) {
		$import = array(
			'name' => sanitize_text_field( $name ),
		);

		if ( ! empty( $zip_url ) ) {
			$import['zip_url'] = esc_url_raw( $zip_url );
		}

		if ( ! empty( $zip_path ) ) {
			$import['zip_path'] = sanitize_text_field( $zip_path );
		}

		if ( ! empty( $description ) ) {
			$import['description'] = sanitize_text_field( $description );
		}

		if ( ! empty( $preview_image ) ) {
			$import['preview_image'] = esc_url_raw( $preview_image );
		}

		if ( ! empty( $preview_url ) ) {
			$import['preview_url'] = esc_url_raw( $preview_url );
		}

		if ( is_callable( $before_import ) ) {
			$import['before_import'] = $before_import;
		}

		if ( is_callable( $after_import ) ) {
			$import['after_import'] = $after_import;
		}

		self::$imports[] = $import;
	}

	/**
	 * Add multiple imports from an array of URLs or simple configurations.
	 *
	 * This method accepts a simple array format for quick bulk addition:
	 * - Array of strings (URLs) - will auto-generate names
	 * - Array of arrays with 'url' or 'zip_url' key
	 * - Array of full import configurations
	 *
	 * @param array $imports Array of import configurations or URLs.
	 * @return void
	 */
	public static function add_multiple( $imports ) {
		if ( ! is_array( $imports ) ) {
			return;
		}

		foreach ( $imports as $index => $import ) {
			// If it's a simple string URL, convert to full format.
			if ( is_string( $import ) ) {
				$name = self::generate_name_from_url( $import );
				self::add( $name, $import );
			}
			// If it's an array with 'url' or 'zip_url' key, use simplified format.
			elseif ( is_array( $import ) && ( isset( $import['url'] ) || isset( $import['zip_url'] ) ) ) {
				$zip_url = isset( $import['zip_url'] ) ? $import['zip_url'] : $import['url'];
				$name    = isset( $import['name'] ) ? $import['name'] : self::generate_name_from_url( $zip_url );
				$description = isset( $import['description'] ) ? $import['description'] : '';
				$preview_image = isset( $import['preview_image'] ) ? $import['preview_image'] : '';
				$preview_url = isset( $import['preview_url'] ) ? $import['preview_url'] : '';
				$zip_path = isset( $import['zip_path'] ) ? $import['zip_path'] : '';
				$before_import = isset( $import['before_import'] ) ? $import['before_import'] : null;
				$after_import = isset( $import['after_import'] ) ? $import['after_import'] : null;

				self::add( $name, $zip_url, $zip_path, $description, $preview_image, $preview_url, $before_import, $after_import );
			}
			// If it's already a full import configuration, add it directly.
			elseif ( is_array( $import ) && isset( $import['name'] ) ) {
				self::$imports[] = $import;
			}
		}
	}

	/**
	 * Generate a readable name from a URL.
	 *
	 * @param string $url The URL to generate a name from.
	 * @return string Generated name.
	 */
	private static function generate_name_from_url( $url ) {
		$name = basename( $url, '.zip' );
		$name = str_replace( array( '-', '_' ), ' ', $name );
		$name = ucwords( $name );
		return $name;
	}

	/**
	 * Get all imports added via helper methods.
	 *
	 * @return array Array of import configurations.
	 */
	public static function get_imports() {
		return self::$imports;
	}

	/**
	 * Clear all stored imports (useful for testing or resetting).
	 *
	 * @return void
	 */
	public static function clear() {
		self::$imports = array();
	}

	/**
	 * Fetch and register demos from a remote API based on theme name.
	 *
	 * This method:
	 * 1. Gets the base URL from filter 'socs/demo_api_base_url'
	 * 2. Gets the current theme name
	 * 3. Constructs API URL: {base_url}/{theme_name}/demos.json
	 * 4. Fetches and parses the JSON response
	 * 5. Automatically registers all demos found in the API
	 *
	 * @param string $base_url Optional. Override base URL from filter.
	 * @param string $theme_name Optional. Override theme name detection.
	 * @param string $api_endpoint Optional. API endpoint path. Default: 'demos.json'.
	 * @return bool|WP_Error True on success, WP_Error on failure.
	 */
	public static function fetch_from_api( $base_url = '', $theme_name = '', $api_endpoint = 'demos.json' ) {
		// Get base URL from filter if not provided.
		if ( empty( $base_url ) ) {
			$base_url = Helpers::apply_filters( 'socs/demo_api_base_url', '' );
		}

		// If no base URL is set, return early.
		if ( empty( $base_url ) ) {
			return new \WP_Error(
				'no_base_url',
				__( 'No API base URL provided. Use the socs/demo_api_base_url filter or pass it as a parameter.', 'smart-one-click-setup' )
			);
		}

		// Sanitize base URL.
		$base_url = esc_url_raw( rtrim( $base_url, '/' ) );

		// Get theme name if not provided.
		if ( empty( $theme_name ) ) {
			$theme = wp_get_theme();
			$theme_name = $theme->get( 'TextDomain' );
			
			// Fallback to theme directory name if TextDomain is empty.
			if ( empty( $theme_name ) ) {
				$theme_name = $theme->get_stylesheet();
			}
		}

		// Sanitize theme name for URL.
		$theme_name = sanitize_file_name( strtolower( $theme_name ) );

		// Construct API URL.
		$api_url = $base_url . '/' . $theme_name . '/' . $api_endpoint;

		// Check cache first.
		$cache_key = 'socs_api_demos_' . md5( $api_url );
		$cached_demos = get_transient( $cache_key );

		if ( false !== $cached_demos ) {
			// Register cached demos.
			self::register_demos_from_api_response( $cached_demos );
			return true;
		}

		// Fetch from API.
		$timeout = Helpers::apply_filters( 'socs/demo_api_timeout', 15 );
		$response = wp_remote_get(
			$api_url,
			array(
				'timeout' => $timeout,
				'sslverify' => Helpers::apply_filters( 'socs/demo_api_sslverify', true ),
			)
		);

		// Check for errors.
		if ( is_wp_error( $response ) ) {
			return new \WP_Error(
				'api_fetch_error',
				sprintf(
					/* translators: %1$s - API URL, %2$s - error message */
					__( 'Failed to fetch demos from API: %1$s. Error: %2$s', 'smart-one-click-setup' ),
					$api_url,
					$response->get_error_message()
				)
			);
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		if ( 200 !== $response_code ) {
			return new \WP_Error(
				'api_response_error',
				sprintf(
					/* translators: %1$s - API URL, %2$s - response code */
					__( 'API returned error code %2$s for URL: %1$s', 'smart-one-click-setup' ),
					$api_url,
					$response_code
				)
			);
		}

		// Get response body.
		$body = wp_remote_retrieve_body( $response );
		if ( empty( $body ) ) {
			return new \WP_Error(
				'empty_response',
				sprintf(
					/* translators: %s - API URL */
					__( 'Empty response from API: %s', 'smart-one-click-setup' ),
					$api_url
				)
			);
		}

		// Parse JSON.
		$demos_data = json_decode( $body, true );
		if ( json_last_error() !== JSON_ERROR_NONE ) {
			return new \WP_Error(
				'json_parse_error',
				sprintf(
					/* translators: %1$s - API URL, %2$s - JSON error message */
					__( 'Failed to parse JSON from API: %1$s. Error: %2$s', 'smart-one-click-setup' ),
					$api_url,
					json_last_error_msg()
				)
			);
		}

		// Cache the response (1 hour default).
		$cache_duration = Helpers::apply_filters( 'socs/demo_api_cache_duration', HOUR_IN_SECONDS );
		set_transient( $cache_key, $demos_data, $cache_duration );

		// Register demos from API response.
		self::register_demos_from_api_response( $demos_data );

		return true;
	}

	/**
	 * Register demos from API response data.
	 *
	 * @param array $demos_data API response data containing demo configurations.
	 * @return void
	 */
	private static function register_demos_from_api_response( $demos_data ) {
		if ( ! is_array( $demos_data ) ) {
			return;
		}

		// Support different API response formats.
		// Format 1: Direct array of demos.
		if ( isset( $demos_data[0] ) && is_array( $demos_data[0] ) ) {
			$demos = $demos_data;
		}
		// Format 2: Wrapped in 'demos' key.
		elseif ( isset( $demos_data['demos'] ) && is_array( $demos_data['demos'] ) ) {
			$demos = $demos_data['demos'];
		}
		// Format 3: Wrapped in 'data' key.
		elseif ( isset( $demos_data['data'] ) && is_array( $demos_data['data'] ) ) {
			$demos = $demos_data['data'];
		}
		else {
			// Single demo object.
			$demos = array( $demos_data );
		}

		// Register each demo.
		foreach ( $demos as $demo ) {
			if ( ! is_array( $demo ) ) {
				continue;
			}

			// Extract demo data.
			$name = isset( $demo['name'] ) ? $demo['name'] : '';
			$zip_url = isset( $demo['zip_url'] ) ? $demo['zip_url'] : ( isset( $demo['url'] ) ? $demo['url'] : '' );
			$zip_path = isset( $demo['zip_path'] ) ? $demo['zip_path'] : '';
			$description = isset( $demo['description'] ) ? $demo['description'] : '';
			$preview_image = isset( $demo['preview_image'] ) ? $demo['preview_image'] : ( isset( $demo['preview'] ) ? $demo['preview'] : '' );
			$preview_url = isset( $demo['preview_url'] ) ? $demo['preview_url'] : ( isset( $demo['demo_url'] ) ? $demo['demo_url'] : '' );
			$before_import = isset( $demo['before_import'] ) && is_callable( $demo['before_import'] ) ? $demo['before_import'] : null;
			$after_import = isset( $demo['after_import'] ) && is_callable( $demo['after_import'] ) ? $demo['after_import'] : null;

			// Skip if no name or zip_url/zip_path.
			if ( empty( $name ) || ( empty( $zip_url ) && empty( $zip_path ) ) ) {
				continue;
			}

			// Register the demo.
			self::add( $name, $zip_url, $zip_path, $description, $preview_image, $preview_url, $before_import, $after_import );
		}
	}
}

