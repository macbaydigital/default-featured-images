=== Default Featured Images & Fallbacks ===
Contributors: macbaydigital
Tags: featured image, fallback, taxonomy, default image, thumbnail
Requires at least: 5.8
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Assign fallback featured images based on taxonomy terms with flexible priority rules and global defaults.

== Description ==

Default Featured Images & Fallbacks allows you to automatically assign fallback featured images to posts, pages, and custom post types based on their taxonomy terms.

**Key Features:**

* **Taxonomy-based Fallbacks**: Assign specific fallback images to individual taxonomy terms (categories, tags, custom taxonomies)
* **Hierarchical Priority**: Child terms automatically take precedence over parent terms
* **Taxonomy Priority Management**: Define which taxonomies have priority when multiple terms match
* **Global Default**: Optional global fallback image for all content without specific term assignments
* **Smart Logic**: Only applies fallbacks when no featured image is already set
* **Performance Optimized**: Lightweight implementation with minimal database queries
* **User-Friendly Interface**: Clean accordion-based UI under Media menu
* **Full Control**: Admins and Editors can manage all settings

**Perfect for:**

* Blogs with multiple categories needing distinct visual identities
* Multi-author sites with consistent category branding
* E-commerce sites with product category defaults
* News sites with section-specific imagery
* Any WordPress site needing automated featured image management

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/default-featured-images/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to Media > Featured Images to configure your fallback images
4. Assign fallback images to taxonomy terms
5. Optionally enable and set a global default image

== Frequently Asked Questions ==

= Does this override existing featured images? =

No. The plugin only applies fallback images when no featured image is already set on a post.

= How does the priority system work? =

The plugin follows this priority order:
1. Existing featured image (never overridden)
2. Most specific (deepest) taxonomy term with a fallback
3. Taxonomy priority order (configurable)
4. Global default (if enabled)

= Can I use this with custom post types? =

Yes! The plugin works with all post types and their associated taxonomies.

= What happens if a post has multiple categories? =

The plugin will use the fallback from the most specific (child) category. If multiple categories are at the same level, it uses the first one found according to your taxonomy priority settings.

= Does this work with WooCommerce? =

Yes! You can assign fallback images to product categories, tags, and any custom WooCommerce taxonomies.

== Screenshots ==

1. Main settings page with accordion interface
2. Global fallback configuration
3. Taxonomy priority management
4. Term-specific fallback assignment

== Changelog ==

= 1.0.0 =
* Initial release
* Taxonomy-based fallback system
* Hierarchical priority logic
* Global default option
* Taxonomy priority management
* Accordion UI with expand/collapse all
* Media library integration

== Upgrade Notice ==

= 1.0.0 =
Initial release of Default Featured Images & Fallbacks plugin.

== Support ==

For support, please visit https://macbay.digital or open an issue on GitHub.

== Credits ==

Developed by Macbay Digital - https://macbay.digital
