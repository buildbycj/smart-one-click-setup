=== Smart One Click Setup ===
Contributors: Chiranjit Hazarika
Tags: import, export, theme options, elementor, one click demo import
Requires at least: 5.5
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv3 or later

One-click import/export for demo content, widgets, customizer, plugin settings, and Elementor data. Perfect for theme authors and site migration.

== Description ==

The best feature of this plugin is, that theme authors can define import files in their themes and so all you (the user of the theme) have to do is click on the "Import Demo Data" button.

**🎯 Key Features & Unique Selling Points:**

**✨ One-Click ZIP Import/Export**
* Export everything to a single ZIP file - content, widgets, customizer, plugins, and Elementor data
* Import from a single ZIP file - automatically detects and imports all available data
* Easy site migration and backup - transfer your entire site configuration in one file

**🎨 Elementor Compatible**
* Full Elementor support - export and import Elementor templates, page data, and kit settings
* Preserve all Elementor designs and configurations across sites
* Seamless integration with Elementor Pro features

**🔌 Any Plugin Settings Import/Export**
* Export settings from ANY plugin - not just predefined ones
* Custom plugin support - developers can add their own export hooks
* Selective export - choose which plugins to include in your export
* Automatic detection of active plugins with customizable export list

**🌐 Remote File Support**
* Import from remote URLs - no need to download files manually
* Support for presigned URLs (Amazon S3, etc.) via filters
* Local file support - use files from your theme directory
* Flexible file sources - mix and match remote and local files

**🚀 Smart Import Interface**
* Predefined demo imports - theme authors can pre-configure imports
* Manual ZIP upload - import your own exported files
* Tabbed interface - easy switching between predefined and manual imports
* Visual preview - see demo imports before importing

**⚙️ Developer-Friendly**
* Comprehensive hooks and filters for customization
* Before/after import actions for custom setup
* WP-CLI commands for automated imports
* Template function for theme integration
* Full WordPress coding standards compliance

> **Are you a theme author?**
>
> Setup Smart One Click Setups for your theme and your users will thank you for it!
>
> [Follow this easy guide on how to setup this plugin for your themes!](https://socs.buildbycj.com/#developer-guide)

> **Are you a theme user?**
>
> Contact the author of your theme and [let them know about this plugin](https://socs.buildbycj.com/#features). Theme authors can make any theme compatible with this plugin in 15 minutes and make it much more user-friendly.
>

Please take a look at our [plugin documentation](https://socs.buildbycj.com/#user-guide) for more information on how to import your demo content.


NOTE: There is no setting to "connect" authors from the demo import file to the existing users in your WP site (like there is in the original WP Importer plugin). All demo content will be imported under the current user.

**Do you want to contribute?**

Please refer to our official [GitHub repository](https://github.com/buildbycj/smart-one-click-setup).

== Installation ==

**From your WordPress dashboard**

1. Visit 'Plugins > Add New',
2. Search for 'Smart One Click Setup' and install the plugin,
3. Activate 'Smart One Click Setup' from your Plugins page.

**From WordPress.org**

1. Download 'Smart One Click Setup'.
2. Upload the 'smart-one-click-setup' directory to your '/wp-content/plugins/' directory, using your favorite method (ftp, sftp, scp, etc...)
3. Activate 'Smart One Click Setup' from your Plugins page.

**Once the plugin is activated you will find:**
* Import page: *Appearance -> Import Demo Data*
* Export page: *Appearance -> Smart Export*

== Frequently Asked Questions ==

= I have activated the plugin. Where is the "Import Demo Data" page? =

You will find the import page in *wp-admin -> Appearance -> Import Demo Data*.

= Where are the demo import files and the log files saved? =

The files used in the demo import will be saved to the default WordPress uploads directory. An example of that directory would be: `../wp-content/uploads/2023/03/`.

The log file will also be registered in the *wp-admin -> Media* section, so you can access it easily.

= How to predefine demo imports? =

This question is for theme authors. To predefine demo imports, you can use the `socs/predefined_import_files` filter or the easier `ImportHelper` class.

**🌐 Remote File Support:** You can use remote URLs or local file paths:

**Method 1: Using the Filter (Traditional Method)**

```php
add_filter( 'socs/predefined_import_files', function( $predefined_imports ) {
    return array(
        array(
            'name'         => 'Demo Import 1',
            'description' => 'Demo import description',
            'preview_image' => 'http://example.com/preview.jpg',
            'preview_url'  => 'http://example.com/demo',
            'zip_url'      => 'http://example.com/demo.zip',  // Remote URL
            // or
            'zip_path'     => get_template_directory() . '/demos/demo.zip',  // Local path
        ),
    );
} );
```

**Method 2: Using ImportHelper (Easy Method for Multiple Imports)**

The `ImportHelper` class makes it super easy to add multiple remote zip files:

**Simple URL-based approach:**
```php
use SOCS\ImportHelper;

// Add multiple imports from URLs (auto-generates names)
ImportHelper::add_multiple( array(
    'https://example.com/demos/demo1.zip',
    'https://example.com/demos/demo2.zip',
    'https://example.com/demos/demo3.zip',
) );
```

**With full control:**
```php
use SOCS\ImportHelper;

// Add imports one by one with full details
ImportHelper::add(
    'Demo Import 1',
    'https://example.com/demos/demo1.zip',
    '',  // zip_path (optional)
    'First demo import description',
    'https://example.com/preview1.jpg',
    'https://example.com/demo1'
);

ImportHelper::add(
    'Demo Import 2',
    'https://example.com/demos/demo2.zip',
    '',  // zip_path (optional)
    'Second demo import description',
    'https://example.com/preview2.jpg',
    'https://example.com/demo2'
);
```

**Mixed format (simplified arrays):**
```php
use SOCS\ImportHelper;

ImportHelper::add_multiple( array(
    // Simple URL string
    'https://example.com/demos/demo1.zip',
    
    // Simplified array format
    array(
        'url' => 'https://example.com/demos/demo2.zip',
        'name' => 'Custom Demo Name',
        'description' => 'Custom description',
    ),
    
    // Full configuration
    array(
        'name' => 'Full Demo',
        'zip_url' => 'https://example.com/demos/demo3.zip',
        'preview_image' => 'https://example.com/preview3.jpg',
        'preview_url' => 'https://example.com/demo3',
    ),
) );
```

**Method 3: Using Remote API (Automatic Demo Discovery)**

The easiest way for theme authors! Just set a base URL and the plugin automatically fetches demos based on your theme name:

```php
// In your theme's functions.php
add_filter( 'socs/demo_api_base_url', function() {
    return 'https://yourplugin.com';
} );
```

**How it works:**
1. Plugin detects your theme name (e.g., "zenix")
2. Constructs API URL: `https://yourplugin.com/zenix/demos.json`
3. Fetches and parses the JSON file
4. Automatically registers all demos found

**API JSON Format:**

Your `demos.json` file should return one of these formats:

**Format 1: Direct array**
```json
[
    {
        "name": "Business Demo",
        "zip_url": "https://yourplugin.com/zenix/demos/business.zip",
        "description": "Perfect for business websites",
        "preview_image": "https://yourplugin.com/zenix/previews/business.jpg",
        "preview_url": "https://demo.yourplugin.com/zenix/business"
    },
    {
        "name": "Portfolio Demo",
        "zip_url": "https://yourplugin.com/zenix/demos/portfolio.zip",
        "description": "Great for showcasing work",
        "preview_image": "https://yourplugin.com/zenix/previews/portfolio.jpg",
        "preview_url": "https://demo.yourplugin.com/zenix/portfolio"
    }
]
```

**Format 2: Wrapped in 'demos' key**
```json
{
    "demos": [
        {
            "name": "Business Demo",
            "zip_url": "https://yourplugin.com/zenix/demos/business.zip"
        }
    ]
}
```

**Format 3: Wrapped in 'data' key**
```json
{
    "data": [
        {
            "name": "Business Demo",
            "zip_url": "https://yourplugin.com/zenix/demos/business.zip"
        }
    ]
}
```

**Custom API Endpoint:**

If your API uses a different endpoint (not `demos.json`):

```php
use SOCS\ImportHelper;

add_action( 'after_setup_theme', function() {
    // Set base URL via filter
    add_filter( 'socs/demo_api_base_url', function() {
        return 'https://yourplugin.com';
    } );
    
    // Fetch with custom endpoint
    ImportHelper::fetch_from_api( '', '', 'api/demos.php' );
} );
```

**Manual API Fetch:**

You can also manually trigger API fetch:

```php
use SOCS\ImportHelper;

add_action( 'after_setup_theme', function() {
    $result = ImportHelper::fetch_from_api(
        'https://yourplugin.com',  // Base URL
        'zenix',                    // Theme name (optional, auto-detected)
        'demos.json'                // API endpoint (optional, default: demos.json)
    );
    
    if ( is_wp_error( $result ) ) {
        // Handle error
        error_log( $result->get_error_message() );
    }
} );
```

**API Caching:**

Responses are cached for 1 hour by default. To customize:

```php
add_filter( 'socs/demo_api_cache_duration', function() {
    return 2 * HOUR_IN_SECONDS; // Cache for 2 hours
} );
```

**Additional API Filters:**

```php
// Custom timeout (default: 15 seconds)
add_filter( 'socs/demo_api_timeout', function() {
    return 30; // 30 seconds
} );

// Disable SSL verification (not recommended for production)
add_filter( 'socs/demo_api_sslverify', function() {
    return false;
} );
```

**Key Features:**
* Use remote URLs - no need to bundle files with your theme
* Use local files - include demos in your theme directory
* Support for presigned URLs (Amazon S3, etc.) via `socs/pre_download_import_files` filter
* Automatic download and extraction
* Easy bulk addition of multiple imports
* Auto-generated names from URLs if not provided
* **Automatic API-based demo discovery based on theme name**
* **Caching for better performance**
* **Flexible JSON response formats**

Please refer to the plugin documentation for more details.

= How to automatically assign "Front page", "Posts page" and menu locations after the importer is done? =

You can do that, with the `socs/after_import` action hook. The code would look something like this:

`
function socs_after_import_setup() {
	// Assign menus to their locations.
	$main_menu = get_term_by( 'name', 'Main Menu', 'nav_menu' );

	set_theme_mod( 'nav_menu_locations', array(
			'main-menu' => $main_menu->term_id, // replace 'main-menu' here with the menu location identifier from register_nav_menu() function
		)
	);

	// Assign front page and posts page (blog page).
	$front_page_id = get_page_by_title( 'Home' );
	$blog_page_id  = get_page_by_title( 'Blog' );

	update_option( 'show_on_front', 'page' );
	update_option( 'page_on_front', $front_page_id->ID );
	update_option( 'page_for_posts', $blog_page_id->ID );

}
add_action( 'socs/after_import', 'socs_after_import_setup' );
`

= What about using local import files (from theme folder)? =

You can use local import files by using the `socs/import_files` filter with `local_*` array keys. The values must be absolute paths (not URLs) to your import files. Note: make sure your import files are readable! Please refer to the plugin documentation for more details.

= How to handle different "after import setups" depending on which predefined import was selected? =

This question might be asked by a theme author wanting to implement different after import setups for multiple predefined demo imports. Lets say we have predefined two demo imports with the following names: 'Demo Import 1' and 'Demo Import 2', the code for after import setup would be (using the `socs/after_import` filter):

`
function socs_after_import( $selected_import ) {
	echo "This will be displayed on all after imports!";

	if ( 'Demo Import 1' === $selected_import['import_file_name'] ) {
		echo "This will be displayed only on after import if user selects Demo Import 1";

		// Set logo in customizer
		set_theme_mod( 'logo_img', get_template_directory_uri() . '/assets/images/logo1.png' );
	}
	elseif ( 'Demo Import 2' === $selected_import['import_file_name'] ) {
		echo "This will be displayed only on after import if user selects Demo Import 2";

		// Set logo in customizer
		set_theme_mod( 'logo_img', get_template_directory_uri() . '/assets/images/logo2.png' );
	}
}
add_action( 'socs/after_import', 'socs_after_import' );
`

= Can I add some code before the widgets get imported? =

Of course you can, use the `socs/before_widgets_import` action. You can also target different predefined demo imports like in the example above. Here is a simple example code of the `socs/before_widgets_import` action:

`
function socs_before_widgets_import( $selected_import ) {
	echo "Add your code here that will be executed before the widgets get imported!";
}
add_action( 'socs/before_widgets_import', 'socs_before_widgets_import' );
`

= How can I import via the WP-CLI? =

In the 2.4.0 version of this plugin we added two WP-CLI commands:

* `wp socs list` - Which will list any predefined demo imports currently active theme might have,
* `wp socs import` - which has a few options that you can use to import the things you want (content/widgets/customizer/predefined demos). Let's look at these options below.

`wp socs import` options:

`wp socs import [--content=<file>] [--widgets=<file>] [--customizer=<file>] [--predefined=<index>]`

* `--content=<file>` - will run the content import with the WP import file specified in the `<file>` parameter,
* `--widgets=<file>` - will run the widgets import with the widgets import file specified in the `<file>` parameter,
* `--customizer=<file>` - will run the customizer settings import with the customizer import file specified in the `<file>` parameter,
* `--predefined=<index>` - will run the theme predefined import with the index of the predefined import in the `<index>` parameter (you can use the `wp socs list` command to check which index is used for each predefined demo import)

The content, widgets and customizer options can be mixed and used at the same time. If the `predefined` option is set, then it will ignore all other options and import the predefined demo data.

= I'm a theme author and I want to change the plugin intro text, how can I do that? =

You can change the plugin intro text by using the `socs/plugin_intro_text` filter:

`
function socs_plugin_intro_text( $default_text ) {
	$default_text .= '<div class="socs__intro-text">This is a custom text added to this plugin intro text.</div>';

	return $default_text;
}
add_filter( 'socs/plugin_intro_text', 'socs_plugin_intro_text' );
`

To add some text in a separate "box", you should wrap your text in a div with a class of 'socs__intro-text', like in the code example above.

= How to disable generation of smaller images (thumbnails) during the content import =

This will greatly improve the time needed to import the content (images), but only the original sized images will be imported. You can disable it with a filter, so just add this code to your theme function.php file:

`add_filter( 'socs/regenerate_thumbnails_in_content_import', '__return_false' );`

= How to change the location, title and other parameters of the plugin page? =

As a theme author you do not like the location of the "Import Demo Data" plugin page in *Appearance -> Import Demo Data*? You can change that with the filter below. Apart from the location, you can also change the title or the page/menu and some other parameters as well.

`
function socs_plugin_page_setup( $default_settings ) {
	$default_settings['parent_slug'] = 'themes.php';
	$default_settings['page_title']  = esc_html__( 'Smart One Click Setup' , 'smart-one-click-setup' );
	$default_settings['menu_title']  = esc_html__( 'Import Demo Data' , 'smart-one-click-setup' );
	$default_settings['capability']  = 'import';
	$default_settings['menu_slug']   = 'smart-one-click-setup';

	return $default_settings;
}
add_filter( 'socs/plugin_page_setup', 'socs_plugin_page_setup' );
`

= How to do something before the content import executes? =

In version 2.0.0 there is a new action hook: `socs/before_content_import`, which will let you hook before the content import starts. An example of the code would look like this:

`
function socs_before_content_import( $selected_import ) {
	if ( 'Demo Import 1' === $selected_import['import_file_name'] ) {
		// Here you can do stuff for the "Demo Import 1" before the content import starts.
		echo "before import 1";
	}
	else {
		// Here you can do stuff for all other imports before the content import starts.
		echo "before import 2";
	}
}
add_action( 'socs/before_content_import', 'socs_before_content_import' );
`

= How can I enable the `customize_save*` wp action hooks in the customizer import? =

It's easy, just add this to your theme:

`add_action( 'socs/enable_wp_customize_save_hooks', '__return_true' );`

This will enable the following WP hooks when importing the customizer data: `customize_save`, `customize_save_*`, `customize_save_after`.

= How can I pass Amazon S3 presigned URL's (temporary links) as external files ? =

If you want to host your import content files on Amazon S3, but you want them to be publicly available, rather through an own API as presigned URL's (which expires) you can use the filter `socs/pre_download_import_files` in which you can pass your own URL's, for example:

`
add_filter( 'socs/pre_download_import_files', function( $import_file_info ){

	// In this example `get_my_custom_urls` is supposedly making a `wp_remote_get` request, getting the urls from an API server where you're creating the presigned urls, [example here](https://docs.aws.amazon.com/sdk-for-php/v3/developer-guide/s3-presigned-url.html).
	// This request should return an array containing all the 3 links - `import_file_url`, `import_widget_file_url`, `import_customizer_file_url`
	$request = get_my_custom_urls( $import_file_info );

	if ( !is_wp_error( $request ) )
	{
		if ( isset($request['data']) && is_array($request['data']) )
		{
			if( isset($request['data']['import_file_url']) && $import_file_url = $request['data']['import_file_url'] ){
				$import_file_info['import_file_url'] = $import_file_url;
			}
			if( isset($request['data']['import_widget_file_url']) && $import_widget_file_url = $request['data']['import_widget_file_url'] ){
				$import_file_info['import_widget_file_url'] = $import_widget_file_url;
			}
			if( isset($request['data']['import_customizer_file_url']) && $import_customizer_file_url = $request['data']['import_customizer_file_url'] ){
				$import_file_info['import_customizer_file_url'] = $import_customizer_file_url;
			}
		}
	}

	return $import_file_info;

} );
`

= How do I export my site's data? =

**✨ Export everything to a single ZIP file!**

You can export your site's content, widgets, customizer settings, plugin settings, and Elementor data all in one ZIP file:

1. Go to *Appearance -> Smart Export*
2. Select the items you want to export:
   * Content (posts, pages, media)
   * Widgets
   * Customizer settings
   * Elementor data (templates, pages, kit settings)
   * **Any plugin settings** - choose from all active plugins
3. Click the "Export" button
4. Download the generated ZIP file

**Key Advantage:** Everything is packaged in one ZIP file, making it super easy to transfer your entire site configuration to another WordPress installation. The exported ZIP file can then be imported on another site using the Smart Import feature.

= Can I import from a ZIP file? =

**✨ Yes! One ZIP file contains everything!**

You can import from a single ZIP file containing all your exported data. The plugin automatically detects and imports everything available:

1. Go to *Appearance -> Import Demo Data*
2. Click on the "Upload ZIP File" tab (if predefined imports are available) or use the manual import section
3. Upload your ZIP file
4. The plugin will automatically:
   * Extract the ZIP file
   * Detect available import files (content.xml, widgets.json, customizer.dat, plugin-settings.json, elementor.json)
   * Import all detected data in the correct order

**Key Advantage:** No need to upload multiple files separately - just one ZIP file and you're done! The plugin intelligently handles everything.

= Is this plugin compatible with Elementor? =

**🎨 Yes! Full Elementor compatibility!**

This plugin provides complete Elementor support:

* **Export Elementor Data:**
  * All Elementor templates and page data
  * Elementor kit settings (colors, typography, etc.)
  * Elementor CSS and edit mode settings
  * Everything packaged in one ZIP file

* **Import Elementor Data:**
  * Automatic detection of Elementor data in ZIP files
  * Preserves all Elementor designs and configurations
  * Works seamlessly with Elementor Pro features
  * Maintains template relationships and settings

* **Easy Migration:**
  * Export your Elementor designs from one site
  * Import them to another site with one click
  * Perfect for site cloning and template sharing

= Can I export settings from any plugin? =

**🔌 Yes! Export settings from ANY plugin!**

This plugin supports exporting settings from any WordPress plugin:

* **Automatic Detection:**
  * Automatically detects all active plugins
  * Shows them in a checklist for easy selection
  * No need to manually configure each plugin

* **Custom Plugin Support:**
  * Developers can add custom export hooks using `socs/export_plugin_{$plugin_slug}_settings`
  * Default fallback: exports all options with plugin prefix
  * Flexible and extensible architecture

* **Selective Export:**
  * Choose which plugins to include in your export
  * Mix and match - export only the plugins you need
  * All plugin settings packaged in one ZIP file

* **Example Usage:**
  * Export WooCommerce settings
  * Export Contact Form 7 forms
  * Export any custom plugin's settings
  * All in one convenient ZIP file

= I can't activate the plugin, because of a fatal error, what can I do? =

You want to activate the plugin, but this error shows up:

*Plugin could not be activated because it triggered a fatal error*

This happens, because your hosting server is using a very old version of PHP. This plugin requires PHP version of at least **7.4**, but we recommend version *8.0* or higher. Please contact your hosting company and ask them to update the PHP version for your site.

= Issues with the import, that we can't fix in the plugin =

Please visit this [docs page](https://github.com/buildbycj/smart-one-click-setup/blob/master/docs/import-problems.md), for more answers to issues with importing data.

== Screenshots ==

1. Example of multiple predefined demo imports, that a user can choose from.
2. How the import page looks like, when only one demo import is predefined.
3. Example of how the import page looks like, when no demo imports are predefined a.k.a manual import.
4. How the Recommended & Required theme plugins step looks like, just before the import step.

== Changelog ==

= 1.0.0 =

*Release Date - 17 Nov 2025*

* Initial release!
* Full import functionality: content, widgets, customizer, Redux, WPForms
* Smart Import interface with predefined demo imports and ZIP file upload
* Full export functionality: content, widgets, customizer, plugin settings, Elementor data
* Export to ZIP file for easy transfer between sites
* Before/after import hooks for custom setup
* WP-CLI commands for automated imports
* Template function `socs_display_smart_import()` for theme integration
* Full WordPress coding standards compliance
* Comprehensive developer hooks and filters
* Support for Elementor templates and kit settings export/import
