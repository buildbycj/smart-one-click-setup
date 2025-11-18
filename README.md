# Smart One Click Setup

[![License: GPL v3](https://img.shields.io/badge/License-GPLv3-blue.svg)](https://www.gnu.org/licenses/gpl-3.0)
[![WordPress](https://img.shields.io/badge/WordPress-5.5%2B-blue.svg)](https://wordpress.org/)
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-purple.svg)](https://php.net/)

One-click import/export for demo content, widgets, customizer, plugin settings, and Elementor data. Perfect for theme authors and site migration.

## 🎯 Key Features

- **✨ One-Click ZIP Import/Export** - Export everything to a single ZIP file and import from a single ZIP file
- **🎨 Elementor Compatible** - Full Elementor support for templates, page data, and kit settings
- **🔌 Any Plugin Settings Import/Export** - Export settings from ANY plugin, not just predefined ones
- **🌐 Remote File Support** - Import from remote URLs with support for presigned URLs (Amazon S3, etc.)
- **🚀 Smart Import Interface** - Predefined demo imports and manual ZIP upload with tabbed interface
- **⚙️ Developer-Friendly** - Comprehensive hooks and filters, WP-CLI commands, and template functions

## 📋 Requirements

- WordPress 5.5 or higher
- PHP 7.4 or higher (PHP 8.0+ recommended)

## 🚀 Installation

### From WordPress Dashboard

1. Visit 'Plugins > Add New'
2. Search for 'Smart One Click Setup' and install the plugin
3. Activate 'Smart One Click Setup' from your Plugins page

### Manual Installation

1. Download the latest release
2. Upload the 'smart-one-click-setup' directory to your '/wp-content/plugins/' directory
3. Activate 'Smart One Click Setup' from your Plugins page

### Once Activated

- **Import page:** `Appearance -> Import Demo Data`
- **Export page:** `Appearance -> Smart Export`

## 📖 Documentation

For detailed documentation, please visit: [https://socs.buildbycj.com](https://socs.buildbycj.com)

## 🛠️ For Theme Authors

Setup Smart One Click Setup for your theme and your users will thank you for it!

[Follow this easy guide on how to setup this plugin for your themes!](https://socs.buildbycj.com/#developer-guide)

### Quick Start

```php
use SOCS\ImportHelper;

// Add multiple imports from URLs
ImportHelper::add_multiple( array(
    'https://example.com/demos/demo1.zip',
    'https://example.com/demos/demo2.zip',
) );

// Or add with full details
ImportHelper::add(
    'Demo Import 1',
    'https://example.com/demos/demo1.zip',
    '',  // zip_path (optional)
    'First demo import description',
    'https://example.com/preview1.jpg',
    'https://example.com/demo1'
);
```

## 🤝 Contributing

Contributions are welcome! Please refer to our [contributing guidelines](https://github.com/buildbycj/smart-one-click-setup/blob/main/CONTRIBUTING.md) for more information.

## 📝 Changelog

See [readme.txt](readme.txt) for the full changelog.

## 📄 License

This plugin is licensed under the GPL v3 or later.

```
Copyright (C) 2025 Chiranjit Hazarika

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.
```

## 👤 Author

**Chiranjit Hazarika**

- Website: [https://buildbycj.com](https://buildbycj.com)
- Plugin Site: [https://socs.buildbycj.com](https://socs.buildbycj.com)

## 🙏 Credits

- Built with WordPress coding standards
- Uses [buildbycj/wp-content-importer-v2](https://github.com/buildbycj/wp-content-importer-v2) for content import

## 📞 Support

For support, feature requests, or bug reports, please [open an issue](https://github.com/buildbycj/smart-one-click-setup/issues) on GitHub

