=== Advanced Product Manager with AI Categorization ===
Contributors: yourname
Tags: products, api, google-vision, ai, categorization, woocommerce, import, rest-api
Requires at least: 5.6
Tested up to: 6.4
Stable tag: 2.1.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Requires PHP: 7.4

Complete product management system with REST API fetching and Google Vision AI categorization.

== Description ==

This plugin combines powerful REST API management with intelligent AI-powered product categorization:

**✨ Version 2.1.0 Highlights:**
- Complete code refactoring with improved architecture
- Enhanced error handling and logging system
- Real-time dashboard with live statistics
- Advanced pagination support for various API types
- Bulk operations with progress tracking
- WooCommerce integration improvements
- REST API endpoints for external integration
- Comprehensive settings and tools page

**🔄 REST API Integration:**
- Connect to unlimited external APIs
- Support for multiple authentication methods
- Advanced pagination handling (page, offset, cursor-based)
- Field mapping and transformation
- Scheduled automatic fetching

**🤖 Google Vision AI:**
- Automatic image analysis and categorization
- Confidence-based category assignment
- Custom mapping rules with priority system
- Bulk processing capabilities
- Uncategorized labels management

**🚀 Smart Publishing:**
- Automatic WordPress post creation
- WooCommerce product integration
- Custom post type support
- Bulk publishing operations

**📊 Advanced Features:**
- Comprehensive logging system
- Real-time dashboard statistics
- Bulk operations with progress tracking
- Search and filtering capabilities
- Export functionality (JSON/CSV)
- System tools and optimization
- Sample data import

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/advanced-product-manager/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Configure your Google Vision API key in Settings
4. Add your first REST API and start importing products!
5. (Optional) Import sample data to test the functionality

== Frequently Asked Questions ==

= Do I need a Google Cloud account? =
Yes, you'll need a Google Cloud Platform account with Vision API enabled.

= Does this work with WooCommerce? =
Yes! The plugin automatically detects WooCommerce and creates proper products. You can enable/disable WooCommerce integration in settings.

= What pagination types are supported? =
The plugin supports: page parameters, offset parameters, cursor-based pagination, and Link headers.

= Can I import sample data to test? =
Yes, there's a "Import Sample Data" button in the Tools section that will set up a sample API and fetch demo products.

= How often does the plugin fetch data? =
By default, it fetches daily, but you can also trigger manual fetches. You can configure the schedule in the settings.

= Is there a way to export products? =
Yes, you can export products in JSON or CSV format from the Products page.

== Changelog ==

= 2.1.0 =
* Complete code refactoring with improved architecture
* Enhanced error handling and logging system
* Real-time dashboard with live statistics
* Advanced pagination support
* Bulk operations with progress tracking
* WooCommerce integration improvements
* REST API endpoints
* Comprehensive settings and tools page
* Sample data import feature
* Export functionality (JSON/CSV)
* Mobile-responsive design improvements

= 2.0.0 =
* Complete rewrite combining REST API and Google Vision features
* Added unified dashboard
* Improved bulk processing
* Enhanced error handling and logging

= 1.0.0 =
* Initial release with basic API integration
* Google Vision categorization
* Product management features

== Upgrade Notice ==

= 2.1.0 =
This is a major update with significant improvements. It's recommended to:
1. Backup your database before updating
2. Test the update on a staging site first
3. Check your API configurations after update
4. Review the new settings options

= 2.0.0 =
This version includes a complete rewrite. Please test thoroughly before deploying to production.