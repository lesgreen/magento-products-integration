=== Mag products integration for WordPress ===
Contributors: santerref
Tags: magento, product, listing, wordpress, rest, api, e-commerce, webshop, shortcode, integration, post, posts, admin, page, commerce, products, free
Requires at least: 4.0
Tested up to: 4.6
Stable tag: 1.3.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Magento products integration for WordPress use the Magento REST API to list products on your WordPress.

== Description ==

This plugin use the Magento REST API to list products on your page or blog post.

Use the configuration page to link your Magento store to your WordPress and the shortcode to display the products.

The plugin works out of the box, but I also provide a free Magento extension to give you more functionalities. Find more details on the [plugin's website page](http://magentowp.santerref.com "Magento products integration for WordPress").

= Plugin features =

* Show product title, short description, price and buy now button
* Cache to reduce page load time
* Shortcode to list products on your page or blog post

= Magento extension features =

* Reduced page load time: only 1 request to fetch all data
* Thumbnails generation (by default images are natural size and resized using img width/height attributes)

= Actions and filters =

For developers: [actions and filters documentation](http://magentowp.santerref.com/documentation.html "Actions and filters documentation").

= Coming soon =

* OAuth authentication
* Possibility to set custom thumbnail for products without images
* Magento 2 compatibility

== Installation ==

1. Extract `magentowp.zip` to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Create REST API user and role in your magento store (see our [documentation](http://magentowp.santerref.com/documentation.html "Magento documentation"))
1. Configure the plugin through the 'Magento' menu in WordPress
1. Place `[magento]` shortcode in one of your page or blog posts

== Screenshots ==

1. Products listing
2. Plugin's configuration page
3. Page shortcode example

== Changelog ==

= 1.2.5 =
* NEW Possibility to set custom thumbnail for products without images
* Update plugin files name and structure to respect WordPress's coding standards
* Prepare plugin for Magento 2
* Test plugin with WordPress 4.6
* Developer: Add PSR-4 compliant autoloader and remove all require calls
* Developer: Add namespace mag_products_integration. You have to replace mag_products_integration() with \mag_products_integration\plugin_instance() and mag_products_integration_admin() with \mag_products_integration\admin_instance().

= 1.2.4 =
* Fix Magento module requests when Magento is in a subdirectory.

= 1.2.3 =
* Fix cache to work with multiple shortcodes. Currently, the cache was only working with one shortcode which prevents users to show different categories of products on different pages.
* Test plugin with WordPress 4.5

= 1.2.2 =
* Fix missing product image (If you are using the Magento module, you must update it to 1.0.1)
* NEW Hide products image via shortcode (use hide_image="true", default is false)
* NEW Add flush cache button
* Update cache to use WordPress Transients API
* Replace CURL functions with WordPress HTTP API
* Update POT file and French translation

= 1.2.1 =
* Fix undismissable notice on other admin pages
* Update POT file and French translation

= 1.2.0 =
* NEW Cache for better performance (reduced page load time)
* NEW Possibility to disable the provided jQuery script
* Default CSS style improvements
* Clearer error messages and notices

= 1.1.1 =
* Fix missing product URL and buy it now button for those who are not using the Magento module
* Add french (fr_FR) translation
* Add PHPDoc on methods and properties
* Update POT file

= 1.1.0 =
* Add new hooks
* Add 13 new actions
* Add 7 new filters

= 1.0.0 =
First stable version.