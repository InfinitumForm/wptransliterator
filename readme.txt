=== Transliterator – Multilingual and Multi-script Text Conversion ===
Contributors: ivijanstefan, creativform, tihi
Tags: cyrillic, latin, transliteration, latinisation, cyr2lat
Requires at least: 5.4
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 2.4.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Universal transliteration for permalinks, posts, tags, categories, media, files, search and more, rendering them universally readable.

== Description ==

✅ **MULTILINGUAL SCRIPT SUPPORT**:  
_Serbian, Bosnian, Montenegrin, Russian, Belarusian, Bulgarian, Macedonian, Kazakh, Ukrainian, Georgian, Greek, Arabic, Armenian, Uzbek, Tajik, Kyrgyz, Mongolian, Bashkir_

This plugin provides a modern, modular, and extensible solution for **transliterating WordPress content** between **Cyrillic and Latin scripts** - with additional support for Arabic-based and other regional alphabets. Originally launched as "Serbian Transliteration," it has evolved into a **general-purpose transliteration tool** built to serve a wider multilingual audience.

📝 [Discover the evolution of this plugin](https://buymeacoffee.com/ivijanstefan/transliterator-wordpress-transforming-language-barriers-bridges)

You can transliterate entire posts, pages, permalinks, media filenames, usernames, and even selectively control output using built-in shortcodes. Whether you're managing a bilingual site or just want cleaner slugs, **you remain in control**.

🔁 Features include:

* Real-time conversion between **Cyrillic and Latin scripts**
* Transliteration of **titles, content, permalinks, filenames, usernames**
* Shortcodes for **partial or conditional transliteration**
* Bilingual search across both script types
* Developer API with hooks and filters
* No database changes – safe to enable or disable anytime
* Supports Arabic, Greek, Cyrillic, and regional language variants

All settings are available under `Settings → Transliteration`.

📦 In terms of functionality, this plugin covers and extends what several popular tools offer individually, such as: [SrbTransLatin](https://wordpress.org/plugins/srbtranslatin/), [Cyr-To-Lat](https://wordpress.org/plugins/cyr2lat/), [Allow Cyrillic Usernames](https://wordpress.org/plugins/allow-cyrillic-usernames/), [Filenames to Latin](https://wordpress.org/plugins/filenames-to-latin/), [Cyrillic Permalinks](https://wordpress.org/plugins/cyrillic-slugs/), [Latin Now!](https://wordpress.org/plugins/latin-now/), [Cyrillic 2 Latin](https://wordpress.org/plugins/cyrillic2latin/), [Ukr-To-Lat](https://wordpress.org/plugins/ukr-to-lat/), [Cyr to Lat Enhanced](https://wordpress.org/plugins/cyr3lat/), [HyToLat](https://wordpress.org/plugins/hytolat/), [Cyrlitera](https://wordpress.org/plugins/cyrlitera/), [Arabic to Latin](https://wordpress.org/plugins/arabic-to-lat/), [Geo to Lat](https://wordpress.org/plugins/geo-to-lat/), [RusToLat](https://wordpress.org/plugins/sp-rtl-rus-to-lat/), and [srlatin](https://sr.wordpress.org/files/2018/12/srlatin.zip).

Instead of relying on multiple separate tools, this all-in-one plugin brings together everything you need in a single, lightweight package - without compromising performance or flexibility.

Every feature in this plugin can be selectively enabled or disabled based on your needs. Additionally, developers can make use of available filters and hooks to further customize behavior. We've designed this plugin with flexibility and compatibility in mind.

🧩 Fully compatible with popular plugins and page builders, including:

* [WooCommerce](https://wordpress.org/plugins/woocommerce/)
* [Polylang](https://wordpress.org/plugins/polylang/)
* [Elementor](https://wordpress.org/plugins/elementor/)
* [CF Geo Plugin](https://wordpress.org/plugins/cf-geoplugin/)
* [Yoast SEO](https://wordpress.org/plugins/wordpress-seo/)
* [Data Tables Generator by Supsystic](https://wordpress.org/plugins/data-tables-generator-by-supsystic/)
* [Slider Revolution](https://www.sliderrevolution.com/)
* [Avada theme](https://avada.theme-fusion.com/)
* [Themify](https://themify.me/)
* [Divi](https://www.elegantthemes.com/gallery/divi/)

Make your multilingual content readable, searchable, and SEO-friendly - **Transliterate once. Control forever. Latin now!**

== Installation ==

1. **Install via WordPress Admin:**
   - Navigate to `WP-Admin -> Plugins -> Add New`.
   - In the search bar, type "WordPress Transliteration".
   - Once you find the plugin, click on the "Install Now" button.

2. **Install via Upload:**
   - Download the **serbian-transliteration.zip** file.
   - Go to `WP-Admin -> Plugins -> Add New -> Upload Plugin`.
   - Click on "Choose File", select the downloaded ZIP file, and then click "Install Now".
   - Alternatively, you can manually upload the unzipped plugin folder to the `/wp-content/plugins` directory via FTP.

3. **Activate the Plugin:**
   - Once the plugin is installed, go to `WP-Admin -> Plugins`.
   - Find "WordPress Transliteration" in the list and click "Activate".

4. **Configure the Plugin:**
   - After activation, go to `Settings -> Transliteration` in your WordPress admin panel.
   - Adjust the settings according to your needs and save the changes.

== Screenshots ==

1. Cyrillic page before serbian transliteration
2. Latin page after serbian transliteration
3. Transliteration settings
4. Converter for transliterating Cyrillic into Latin and vice versa
5. Permalink tools
6. Shortcodes
7. Available PHP Functions
8. Language script inside Menus
9. Automated test

== Changelog ==

= 2.4.4 =
* Fixed nested plugins_loaded issue
* Refactored REST init
* Versioned APCu class map
* Secured and sanitized AJAX HTML input/output
* Purge autoloader cache on update
* UX update

= 2.4.3 =
* Fixing issue with PHP 8.4 + FPM + OPcache

= 2.4.2 =
* Fixed PHP 8.4 compatibility issue in autoloader (private method visibility).

= 2.4.1 =
* Fixed problem with transition codes
* Fixed issue with shortcodes conflicts
* Fixed a problem with the transfer algorithm
* Improved UX
* Removed ads

= 2.4.0 =
* Fixed language detection with PLL plugin
* Fixed problem with stuck loops
* Fixed navigation issue
* Fixed gettext problems

== Upgrade Notice ==

= 2.4.4 =
* Fixed nested plugins_loaded issue
* Refactored REST init
* Versioned APCu class map
* Secured and sanitized AJAX HTML input/output
* Purge autoloader cache on update
* UX update

== Frequently Asked Questions ==

= What is Romanization or Latinisation? =
**Romanisation or Latinisation**, in linguistics, is the conversion of writing from a different writing system to the Roman (Latin) script, or a system for doing so. Methods of romanization include transliteration, for representing written text, and transcription, for representing the spoken word, and combinations of both.

= Which Romanization does this plugin support? =
This plugin supports several world letters written in Cyrillic and enables their Romanization

* Romanization of Serbian what include Bosnian and Montenegrin
* Romanization of Russian
* Romanization of Belarusian
* Romanization of Bulgarian
* Romanization of Macedonian
* Romanization of Kazakh
* Romanization of Ukrainian
* Romanization of Greek
* Romanization of Arabic (EXPERIMENTAL)
* Romanization of Armenian (EXPERIMENTAL)

Each of these transliterations is created separately and follows the rules of the active language.

= What is the best practice for transliteration? =
Through various experiences, we came to the conclusion that it is best to create the entire site in Cyrillic and enable transliteration for Latin.

The reason for this solution lies in the problem of transliteration of Latin into Cyrillic due to encoding and, depending on the server, can create certain problems, especially in communication with the database. Creating a site in Cyrillic bypasses all problems and is very easily translated into Latin.

= Is Latin better for SEO than Cyrillic? =
According to Google documentation and discussions on forums and blogs, it is concluded that Latin is much better for SEO and it is necessary to practice Latin at least when permalinks and file names are in Latin, while the text can be in both letters but Latin is always preferred.

= Can I translate Cyrillic letters into Latin with this plugin? =
YES! Without any problems or conflicts.

= Can I translate Latin into Cyrillic with this plugin? =
YES! This plugin can translate a Latin site into Cyrillic, but this is not recommended and often causes problems. It is suggested that this approach be approached experimentally.

The best practice is to create a Cyrillic site including all other content and in the end just add transliteration to navigation so that the visitor can choose the desired script.

= How to transliterate Cyrillic permalinks? =
This plugin has a tool that transliterates already recorded permalinks in your database. This option is safe but requires extra effort to satisfy SEO.

With this tool, you permanently change the permalinks in your WordPress installation and a 404 error can occur if you visit old Cyrillic paths.

Therefore, you must re-asign your sitemap or make additional efforts to redirect old permalinks to new ones, which our plugin does not do.

If you are using WP-CLI, this function can also be started with a simple shell command: `wp transliterate permalinks`

= How can I define my own substitutions? =

You can customize the transliteration process by defining your own substitutions directly in your theme's `functions.php` file. This is done by using filters specific to the language you want to modify.

To create custom substitutions, use the following filter:

`add_filter( 'transliteration_map_{$locale}', 'function_callback', 10, 1 );`

Here's an example for Serbian (`sr_RS`) that demonstrates how to modify both single letters and multiple-letter combinations.

`/*
  * Modify conversion table for Serbian language.
  *
  * @param array $map Conversion map.
  *
  * @return array
  */
function my_transliteration__sr_RS( $map ) {

	// Example for 2 or more letters
	$new_map = [
		'Ња' => 'nja',
		'Ње' => 'nje',
		'Обједињени' => 'Objedinjeni'
	];
	$map = array_merge($new_map, $map);
	
	// Example for one letter
	$new_map = [
		'А' => 'X',
		'Б' => 'Y',
		'В' => 'Z'
	];
	$map = array_merge($map, $new_map);
	
	return $map;
}
add_filter( 'transliteration_map_sr_RS', 'my_transliteration__sr_RS', 10, 1 );`

In this example:
- The first `$new_map` array defines substitutions for combinations of two or more letters.
- The second `$new_map` array defines substitutions for individual letters.

This custom mapping will override the default transliteration rules for the specified language (`sr_RS` in this case).

== Other Notes ==

= Plugin Updates =
We regularly update the Transliterator plugin to improve its functionality, enhance performance, and ensure compatibility with the latest versions of WordPress and PHP. Keep your plugin up to date to benefit from the latest features and fixes. To stay informed about updates, visit our plugin page on WordPress.org or follow us on social media.

= Support and Feedback =
If you encounter any issues or have suggestions for improving the plugin, please don't hesitate to reach out. You can contact us through the support forum on WordPress.org, or directly via [GitHub repository](https://github.com/InfinitumForm/serbian-transliteration). We value your feedback and are committed to providing prompt and effective support.

= Compatibility =
The Transliterator plugin is compatible with a wide range of WordPress versions and works seamlessly with many popular plugins. However, due to the vast number of available plugins, there's a small chance of encountering conflicts. If you experience any issues, please check for plugin conflicts and update your WordPress installation and all plugins.

= Contributing =
We welcome contributions from the community! If you're a developer or a user with ideas for improvement, visit our [GitHub repository](https://github.com/InfinitumForm/serbian-transliteration) to contribute. You can report issues, suggest new features, or submit pull requests.

= Credits =
Special thanks to all contributors and beta testers who helped in developing and refining this plugin. Your feedback and support are invaluable.