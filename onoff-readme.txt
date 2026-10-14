=== OnOff Redirecionar ===
Tags: redirect, shortlinks, iframe, url, links
Requires at least: 6.0
Requires PHP: 7.4
Tested up to: 7.1
Stable tag: 1.1.5
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Create friendly paths on your WordPress domain with HTTP redirects or iframe embedding when supported by the destination website.

== Description ==

OnOff Redirecionar is a free WordPress plugin created by Camila Lins (OnOff Criativos).

* Create short paths such as /cobranca using your own site domain.
* Choose between HTTP 301 (permanent) and 302 (temporary) redirects.
* Display an external page inside an iframe while keeping your site's URL in the browser address bar, if the destination allows embedding.
* Manage links in the WordPress admin menu called "Redirecionar".
* All links are stored locally in the WordPress database.

This plugin does not automatically send visitor information, statistics, or tracking data to the author or any external analytics service. When users open a redirect or iframe, their browser connects to the website selected by the site administrator; that external site may process visitor data under its own privacy policy.

**Important:** An iframe cannot override the destination's X-Frame-Options or Content-Security-Policy restrictions. The embedded website might not load. Only embed trusted websites, and understand the implications of displaying sensitive interfaces inside third-party frames.

Developer: Camila Lins. Website: https://onoffcriativos.com.br. Support: gmlins06@gmail.com.

== Installation ==

1. Install and activate the plugin from the WordPress Plugins screen.
2. Open the "Redirecionar" menu in your WordPress administration.
3. Choose a path and destination URL.
4. Select redirect or iframe mode and save.

== Frequently Asked Questions ==

= Does embedding work for every website? =

No. The destination must allow iframe embedding. Some third-party services block it by design.

= Does the plugin track visitors? =

No. This version does not transmit usage metrics or visitor details to the plugin author.

= Can I use my existing short paths after upgrading? =

Yes. Existing links are stored as WordPress options and are retained during upgrades.

= What happens with a 301 redirect? =

A 301 is intended to be permanent and may be cached by browsers. Use 302 for destinations that may change.

== Changelog ==

= 1.1.2 =
* Improved WordPress.org submission metadata and documentation.
* Increased tolerance for malformed saved entries in the admin list.

= 1.1.1 =
* Updated credits and administration menu naming.
* Improved path validation.
