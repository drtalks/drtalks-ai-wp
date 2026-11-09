=== DrTalks AI ===
Contributors: [WPORG USERNAME]
Tags: chat, chatbot, ai, widget, live chat
Requires at least: 6.4
Tested up to: 7.1
Requires PHP: 7.0
Stable tag: 1.0.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Adds the DrTalks AI chat widget to your website.

== Description ==

DrTalks AI adds the DrTalks AI chat widget to the pages of your WordPress site. Enter the Widget ID and website domain from your DrTalks account and the plugin loads the widget script on the front end of your site.

Features:

* Simple setup: paste your Widget ID and website domain under Settings → DrTalks AI.
* Optional `defer` loading (on by default) so the widget does not hold up page rendering.
* Hide the widget on specific page types: homepage, search results, 404 pages, the login page, the blog index, archives and feeds.
* When WooCommerce is active, you can also hide it on the cart, checkout, My Account and order-confirmation pages.
* Hide the widget on specific URLs or paths, with `*` wildcards for sub-paths (for example `/my-account/*`).

A DrTalks account and Widget ID are required. The widget itself is provided by the DrTalks service; see the External services section below.

== Installation ==

1. Upload the `drtalks-ai` folder to `/wp-content/plugins/`, or install the plugin from the Plugins → Add New screen.
2. Activate the plugin through the Plugins screen in WordPress.
3. Go to Settings → DrTalks AI.
4. Log in to your DrTalks account at drtalks.ai, open drtalks.ai/api-keys, and copy your Widget ID and Website URL.
5. Paste them into the settings page and click Save Settings.

== Frequently Asked Questions ==

= Where do I find my Widget ID? =

Log in to your DrTalks account and open drtalks.ai/api-keys. The Widget ID is listed next to your website and looks like `widget_yoursite-com_xxxxxxxx`.

= Do I need to enter http:// or www. in the domain field? =

No. You can paste the full website URL; the plugin removes `http://`, `https://`, `www.` and trailing slashes when you save.

= The widget is not showing. What should I check? =

The widget only loads after both a Widget ID and a website domain have been saved. Also check that the page you are viewing is not excluded by a page-type or URL rule on the settings page.

= How do I hide the widget on certain pages? =

On the settings page, tick the page types to exclude, or list URLs or paths (one per line) in the Specific URLs box. Use `*` as a wildcard to match any sub-path, for example `/my-account/*`.

= What happens to my settings if I delete the plugin? =

Deleting the plugin from the Plugins screen removes its saved settings from your database. Deactivating it keeps them.

== External services ==

This plugin connects to the DrTalks chat widget service, provided by DrTalks, to display the chat widget on your site.

What is loaded, and when: the plugin loads the DrTalks chat widget script from `cdn.drtalks.ai` (https://cdn.drtalks.ai/v1_0_4/drtalks-doctors-widget.min.js) on front-end pages of your site that are not excluded on the settings page. Nothing is loaded until a site administrator has entered a Widget ID (and website domain) on the settings page. The script is not loaded in the WordPress admin.

What data is sent: the Widget ID and the site domain entered on the settings page are added to the script tag as the `data-widget-id` and `data-site-domain` attributes, so they are available to the DrTalks service. As with any script loaded from a third-party server, the visitor's browser requests the file from `cdn.drtalks.ai`, which exposes standard request information such as the visitor's IP address and browser user agent to that server.

DrTalks terms of service: [DRTALKS TERMS URL]
DrTalks privacy policy: [DRTALKS PRIVACY URL]

== Changelog ==

= 1.0.0 =
* Initial release.
