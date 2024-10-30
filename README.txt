=== LeftSell ===
Plugin Version: 2.0.4
Contributors: Kundschaft
Donate link: https://leftsell.com/
Tags: shop, service as product, dropship, share products, marketing, seo, woocommerce
Requires PHP: 7.0
Requires at least: 5.0.1
Tested up to: 5.8
Stable tag: 5.8
License: GPL v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html


== Description ==

= English =
Share shop products with your shops: manage all items in one shop and spread them automatically. LeftSell improves selling of products and services and provides private market places: it joins originators of products with sellers: other shops can sell your items. Every shop can subscribe to your items and sell them for you. This reduces marketing costs and provides a fair and reliable system between the small shops and even the big ones.
LeftSell creates services to book out of WooCommerce products and it also contains many options for WordPress, SEO, Google Product XML Feed and more.

= German =
LeftSell teilt Shop Produkte mit anderen Shops: Verwaltung der Produkte in einem Shop und automatische Verteilung in andere. LeftSell verbessert das Verkaufen von Produkten und Dienstleistungen und erstellt private Märkte: es verbindet Hersteller mit Verkäufern. Jeder Shop kann Hersteller Produkte abonnieren und sie verkaufen. Das reduziert Marketing Kosten und erlaubt ein faires und verlässliches System zwischen kleinen und grossen Shops.
Erstellt buchbare Dienstleistungen aus Produkten und enthält obendrein viele Einstellungen für WordPress: SEO, Google XML Produktfeed und mehr.


== Dependency on thrid party service ==

= English =
The communication part of the plugin depends on the external system of the LeftSell WordPress REST service. You can submit bugs, request more features and get the latest news of the LeftSell project

Link to the service:
https://api002.leftsell.com/

Terms of use and privacy policies: 
https://leftsell.com/en/privacy-policy-2/#plugin


= German =
Die Kommunikation wird über das externe System von LeftSell gewährleistet: den LeftSell WordPress REST service. Es erlaubt das Übermitteln von Bugs und von Anforderungswünschen. Neuigkeiten aus der Entwicklung können direkt empfangen werden.

Link des Service:
https://api002.leftsell.com/

Datenschutz: 
https://leftsell.com/de/datenschutzerklarung/



== Privacy Data ==

= English =
Cookies: 
The plugin does not use Cookies.

WordPress Options: 
Every setting of the plugin is stored as a value in wp_options. 

Page / Post / Product Fields
LeftSell stores values for several own fields in wp_post_options.

= German =
Cookies: 
Das Plugin verwendet keine Cookies.

WordPress Options: 
Alle Einstellungen werden in den wp_options gespeichert.

Page / Post / Product Fields
LeftSell speichert die Werte für eigene Felder in den wp_post_options.


== Installation ==

= English =
1. Upload `leftsell`-folder to the `/wp-content/plugins/` directory
2. Activate the plugin the plugin menu in WordPress
3. Find the menu in your Admin interface

= German =
1. Upload des `leftsell`-Ordners in das `/wp-content/plugins/` Verzeichnis
2. Aktivieren des Plugins im Plugin Menu von WordPress
3. Das LeftSell Menu findet sich in der Admin Oberfläche

== Frequently Asked Questions == 

= English =
* Footer Page: it has to be named as ~footer
* Shop verification: if you connect a shop to your market, the other shop has to confirm this

= German =
* Fusszeile: die WordPress Seite für die Fusszeile muss ~footer heissen
* Shop Sicherheit: beim Verbinden mit einem anderen Shop zum eigenen Markt, muss dieser das bestätigen

== Upgrade Notice ==

= English =
* delivery only from WordPress plugin directory

= German =
* Auslieferung erfolgt über das WordPress Plugin Verzeichnis

== Screenshots == 
1. Screen LeftSell Share products and services
1. Screen LeftSell Hacks - Showing the options for admin / site and comments
2. Screen LeftSell SEO shows Google Metas, JSON and Open Graph options
3. Screen Sitemaps shows options for and lets you create Google sitemap and productfeed
4. Screen Header: robots instructions and header addtitions
5. Screeen Shop: WooCommerce options and Frontend options
6. Screen Communicate News: get the latest news from the plugin development
7. Screen Report Bug: shows your submitted bugs and lets you submit new ones
8. Screen Request Feature: shows your requests for features and lets you submit new ones
9. Screen Product additional fields
10. Screen Page additional fields

== Changelog ==
= 2.0.4 =
* Bugfix connection

= 2.0.3 =
* Tested up to WP 5.8.2
* Bugfix: additional page-headers now can store newline text

= 2.0.2 =
* Tested up to PHP 8
* Bugfix for WP schedule_event with PHP8 on array_slice: currently no background jobs
* On new comment: initializing rating meta field
* New function to manually update a product from originator
* New function: additional headers for each page / product / post
* New option to disable image, gallery and comment sharing 
* New option to use direct calls instead of using WP background jobs
* Bugfix: combining 3 or more shops in private market did not populate changes to all shops
* some cosmetic code fixes

= 2.0.1 =
* Bugfix for Google Merchant Center Feed
* Detect WooCommerce Installation before showing Market

= 2.0.0 =
* Implementing own market: share your products and services
* Option to disable xmlrpc for security reasons
* Bugfix: Removed template file for Google Breadcrumbs and put it into code

== Default settings ==
* All options are initially turned off
* Activating the plugin sets a flag on the LeftSell server, that the shop is permitted to submit bugs and feature requests

