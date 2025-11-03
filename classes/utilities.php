<?php

if (!defined('WPINC')) {
    die();
}

class Transliteration_Utilities
{
    use Transliteration__Cache;
	
	/** @var array|null Request-scope language cache: ['slug'=>string,'locale'=>string,'ready'=>bool] */
	protected static $lang_cache = null;

    /*
     * Registered languages
     * @since     1.4.3
     * @version   1.0.0
     * @author    Ivijan-Stefan Stipic
     */
    public static function registered_languages()
    {
        return apply_filters('rstr_registered_languages', [
            'sr_RS' => __('Serbian', 'serbian-transliteration'),
            'bs_BA' => __('Bosnian', 'serbian-transliteration'),
            'cnr'   => __('Montenegrin', 'serbian-transliteration'),
			'hr' 	=> __('Croatian', 'serbian-transliteration'),
            'ru_RU' => __('Russian', 'serbian-transliteration'),
            'bel'   => __('Belarusian', 'serbian-transliteration'),
            'bg_BG' => __('Bulgarian', 'serbian-transliteration'),
            'mk_MK' => __('Macedoanian', 'serbian-transliteration'),
            'uk'    => __('Ukrainian', 'serbian-transliteration'),
            'kk'    => __('Kazakh', 'serbian-transliteration'),
            'tg'    => __('Tajik', 'serbian-transliteration'),
            'kir'   => __('Kyrgyz', 'serbian-transliteration'),
            'mn'    => __('Mongolian', 'serbian-transliteration'),
            'ba'    => __('Bashkir', 'serbian-transliteration'),
            'uz_UZ' => __('Uzbek', 'serbian-transliteration'),
            'ka_GE' => __('Georgian', 'serbian-transliteration'),
            'el'    => __('Greek', 'serbian-transliteration'),
            'hy'    => __('Armenian', 'serbian-transliteration'),
            'ar'    => __('Arabic', 'serbian-transliteration'),
        ]);
    }

    public static function plugin_default_options()
    {
        return apply_filters('rstr_plugin_default_options', [
            'site-script'               => 'cyr',
            'transliteration-mode'      => 'cyr_to_lat',
            'mode'                      => class_exists('DOMDocument', false) ? 'phantom' : 'advanced',
            'avoid-admin'               => 'no',
            'allow-admin-tools'         => 'yes',
            'allow-cyrillic-usernames'  => 'no',
            'media-transliteration'     => 'yes',
            'media-delimiter'           => '-',
            'permalink-transliteration' => 'yes',
            'cache-support'             => self::has_cache_plugin() ? 'yes' : 'no',
            'exclude-latin-words'       => 'WordPress',
            'exclude-cyrillic-words'    => 'ћирилица, кириллица, кірыліца, кирилица, кирилиця, კირილიცა, κυριλλικό, كيريلية, կիրիլիցա, кирилл, кириллӣ',
            'enable-search'             => 'yes',
            'search-mode'               => 'auto',
            'enable-alternate-links'    => 'no',
            'first-visit-mode'          => 'lat',
            'enable-rss'                => 'yes',
            'fix-diacritics'            => 'yes',
            'url-selector'              => 'rstr',
            'language-scheme'           => (
                in_array(
                    self::get_locale(),
                    array_keys(self::registered_languages())
                ) ? self::get_locale() : 'auto'
            ),
            'enable-body-class'           => 'yes',
            'force-widgets'               => 'no',
            'force-email-transliteration' => 'no',
            'force-ajax-calls'            => 'no',
            'force-rest-api'              => 'yes',
            'disable-by-language'         => ['en_US' => 'yes'],
            'disable-theme-support'       => 'no',
        ]);
    }
	
	/**
	 * Determine if current language is Cyrillic-script.
	 * Safe default: if language is not ready/resolved => return false (Latin).
	 *
	 * @param string|null $slug
	 * @param string|null $locale
	 * @return bool
	 */
	public static function is_cyrillic_locale(?string $slug = null, ?string $locale = null): bool
	{
		static $cache = [];

		// Prefer pre-resolved info
		$info = self::current_language_info();
		if ($slug === null)   { $slug = $info['slug'] ?: null; }
		if ($locale === null) { $locale = $info['locale'] ?: null; }

		$k = ($slug ?: '_') . '|' . ($locale ?: '_');
		if (array_key_exists($k, $cache)) {
			return $cache[$k];
		}

		// Tight Cyrillic lists
		$cyr_slugs_default = [
			'sr','sr_RS','sr_ME','sr_BA','sr-cyrl','sr_Cyrl',
			'ru','uk','bg','mk','kk','tg','ky','mn','ba','be','sah','tt','uz_Cyrl',
			// 'cnr' only if your site really uses Cyrillic for it
		];
		$cyr_locales_default = [
			'sr_RS','sr_ME','sr_BA',
			'ru_RU','uk_UA','bg_BG','mk_MK',
			'kk_KZ','tg_TJ','ky_KG','mn_MN','ba_RU','be_BY','sah_RU','tt_RU','uz_UZ_Cyrl'
		];

		$cyr_slugs   = apply_filters('rstr_cyrillic_slugs', $cyr_slugs_default, $slug, $locale);
		$cyr_locales = apply_filters('rstr_cyrillic_locales', $cyr_locales_default, $slug, $locale);

		// Optional narrowing to registered set
		$restrict_to_registered = apply_filters('rstr_cyrillic_restrict_to_registered', true, $slug, $locale);
		if ($restrict_to_registered && method_exists(__CLASS__, 'registered_languages')) {
			// Expect keys to include locales; if you also store slugs, adjust accordingly.
			$registered = array_map('strval', array_keys((array) self::registered_languages()));
			if (!empty($registered)) {
				$cyr_slugs   = array_values(array_intersect($cyr_slugs, $registered));
				$cyr_locales = array_values(array_intersect($cyr_locales, $registered));
			}
		}

		// SAFE DEFAULT: if not ready to decide, choose Latin (false).
		if (!$info['ready']) {
			$cache[$k] = false;
			return false;
		}

		$is_cyr = in_array((string)$slug, $cyr_slugs, true) || in_array((string)$locale, $cyr_locales, true);
		$cache[$k] = $is_cyr;
		return $is_cyr;
	}


    /*
     * Skip transliteration
     * @return        bool
     * @author        Ivijan-Stefan Stipic
     */
    public static function skip_transliteration()
    {
        return self::cached_static('skip_transliteration', fn (): bool => isset($_REQUEST['rstr_skip']) && in_array(is_string($_REQUEST['rstr_skip']) ? strtolower($_REQUEST['rstr_skip']) : $_REQUEST['rstr_skip'], ['true', true, 1, '1', 'yes'], true));
    }

    /*
     * Retrieve available plugin modes.
     * Returns an array of mode keys with support for WooCommerce and developer-specific settings.
     *
     * @param  string|null $mode Optional specific mode key to check.
     * @return array|string      Array of mode keys or specific mode key if exists.
     */
    public static function available_modes($mode = null)
    {
        // Define available mode keys
        $modes = [];

        // Add special mode
        if (class_exists('DOMDocument', false)) {
            $modes = array_merge($modes, ['phantom']);
        }

        // Builtin modes
        $modes = array_merge($modes, [
            'light',
            'standard',
            'advanced',
            'forced',
        ]);

        // Add WooCommerce-specific mode key if WooCommerce is enabled
        if (RSTR_WOOCOMMERCE) {
            $modes[] = 'woocommerce';
        }

        // Add developer mode key if in development environment
        if (defined('RSTR_DEV_MODE') && RSTR_DEV_MODE) {
            $modes[] = 'dev';
        }

        // Allow filtering of mode keys
        $modes = apply_filters('rstr_plugin_mode_key', $modes);

        // Return specific mode key if $mode is provided
        if ($mode) {
            return in_array($mode, $modes, true) ? $mode : [];
        }

        return $modes;
    }

    /*
     * Retrieve plugin modes with descriptions.
     * Modes include predefined options with support for WooCommerce and developer-specific settings.
     *
     * @param  string|null $mode Optional specific mode key to retrieve.
     * @return array|string      Modes array with labels or specific mode description.
     */
    public static function plugin_mode($mode = null)
    {
        // Get available modes
        $available_modes = self::available_modes();

        // Map available modes to their descriptions
        $modes = [
            'phantom'     => __('Phantom Mode (ultra fast DOM-based transliteration)', 'serbian-transliteration'),
            'light'       => __('Light mode (basic parts of the site)', 'serbian-transliteration'),
            'standard'    => __('Standard mode (content, themes, plugins, translations, menu)', 'serbian-transliteration'),
            'advanced'    => __('Advanced mode (content, widgets, themes, plugins, translations, menu, permalinks, media)', 'serbian-transliteration'),
            'forced'      => __('Forced transliteration (everything)', 'serbian-transliteration'),
            'woocommerce' => __('Only WooCommerce (It bypasses all other transliterations and focuses only on WooCommerce)', 'serbian-transliteration'),
            'dev'         => __('Dev Mode (Only for developers and testers)', 'serbian-transliteration'),
        ];

        // Filter the modes to include only available modes
        $filtered_modes = array_intersect_key($modes, array_flip($available_modes));

        // Allow filtering of the labeled modes
        $filtered_modes = apply_filters('rstr_plugin_mode', $filtered_modes);

        // Return specific mode description if $mode is provided
        if ($mode) {
            return $filtered_modes[$mode] ?? [];
        }

        return $filtered_modes;
    }

    /*
     * Get locale
     * @return        string
     * @author        Ivijan-Stefan Stipic
     */
    public static function get_locale($locale = null)
    {
        return self::cached_static('get_locale', function () {
            if (!function_exists('is_user_logged_in')) {
                include_once ABSPATH . WPINC . '/pluggable.php';
            }

            $language_scheme = get_rstr_option('language-scheme', 'auto');
            if ($language_scheme !== 'auto') {
                return $language_scheme;
            }

            if (empty($get_locale)) {
                $get_locale = self::is_pll() ? pll_current_language('locale') : get_locale();

                if (is_user_logged_in() && empty($get_locale)) {
                    $get_locale = get_user_locale(wp_get_current_user());
                }

                if ($get_locale == 'sr') {
                    $get_locale = 'sr_RS';
                } elseif ($get_locale == 'mk') {
                    $get_locale = 'mk_MK';
                } elseif ($get_locale == 'bs') {
                    $get_locale = 'bs_BA';
                } elseif ($get_locale == 'bg') {
                    $get_locale = 'bg_BG';
                } elseif ($get_locale == 'uz') {
                    $get_locale = 'uz_UZ';
                } elseif ($get_locale == 'ru') {
                    $get_locale = 'ru_RU';
                }
            }

            return empty($locale) ? $get_locale : ($get_locale === $locale);
        });
    }

    /*
     * Get list of available locales
     * @return        bool false, array or string on needle
     * @author        Ivijan-Stefan Stipic
     */
    public static function get_locales($needle = null)
    {
        $cache = Transliteration_Cache_DB::get('get_locales');

        if (empty($cache)) {
            $file_name = apply_filters('rstr/init/libraries/file/locale', 'locale.lib');
            $cache     = self::parse_library($file_name);

            if (!empty($cache)) {
                Transliteration_Cache_DB::set('get_locales', $cache, apply_filters('rstr/init/libraries/file/locale/transient', YEAR_IN_SECONDS));
            }
        }

        if ($needle && is_array($cache)) {
            return (in_array($needle, $cache, true) ? $needle : false);
        }

        return $cache;
    }

    /*
    * Read file with chunks with memory free
    * @since     1.6.7
    */
    private static function read_file_chunks($path)
    {
        if ($handle = fopen($path, 'r')) {
            while (!feof($handle)) {
                yield fgets($handle);
            }

            fclose($handle);
        } else {
            return false;
        }
    }

    /*
     * Exclude transliteration
     * @return        bool
     * @author        Ivijan-Stefan Stipic
     */
    public static function exclude_transliteration(): bool
    {
        return self::cached_static('exclude_transliteration', function (): bool {
            $locale  = self::get_locale();
            $exclude = get_rstr_option('disable-by-language', []);

            return isset($exclude[$locale]) && $exclude[$locale] === 'yes';
        });
    }

    /*
     * Check if it can be transliterated
     * @param         string $content The content to check for transliteration
     * @return        bool
     * @author        Ivijan-Stefan Stipic
     */
    public static function can_transliterate($content)
    {
        // Quick exit for empty content
        if (empty($content)) {
            return apply_filters('rstr_can_transliterate', true, $content);
        }

        // Check if the content is of a type that does not support transliteration
        if (is_array($content) || is_object($content) || is_numeric($content) || is_bool($content)) {
            return apply_filters('rstr_can_transliterate', true, $content);
        }

        // Check for URL and Email
        if (self::is_url_or_email($content)) {
            return apply_filters('rstr_can_transliterate', true, $content);
        }

        // If none of the conditions are met, return true
        return apply_filters('rstr_can_transliterate', false, $content);
    }

    /*
     * Check if string is URL or email
     * @param         string $content The content to check if it's a URL or email
     * @return        bool
     * @uthor        Ivijan-Stefan Stipic
     */
    public static function is_url_or_email($content)
    {

        if (!is_string($content) && is_numeric($content)) {
            return false;
        }

        $content = self::decode($content);

        $urlRegex = '/^((https?|s?ftp):)?\/\/([a-zA-Z0-9\-\._\+]+(:[a-zA-Z0-9\-\._]+)?@)?([a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,})(:[0-9]+)?(\/[a-zA-Z0-9\-\._\%\&=\?\+\$\[\]\(\)\*\'\,\.\,\:]+)*\/?#?$/i';

        $emailRegex = '/^[a-zA-Z0-9\-\._\p{L}]+@[a-zA-Z0-9\-\._\p{L}]+\.[a-zA-Z]{2,}$/i';

        return !empty($content) && is_string($content) && strlen($content) > 10 && (preg_match($urlRegex, $content) || preg_match($emailRegex, $content));
    }

    /*
     * Has cache plugin active
     * @version    1.0.0
     */
    public static function has_cache_plugin()
    {
        return self::cached_static('has_cache_plugin', function (): bool {
            global $w3_plugin_totalcache;

            $cache_checks = [
                ['class_exists', '\LiteSpeed\Purge'],
                ['has_action', 'litespeed_purge_all'],
                ['function_exists', 'liteSpeed_purge_all'],
                ['function_exists', 'w3tc_flush_all'],
                ['function_exists', 'wpfc_clear_all_cache'],
                [$w3_plugin_totalcache],
                ['function_exists', 'rocket_clean_domain'],
                ['function_exists', 'prune_super_cache', 'get_supercache_dir'],
                ['function_exists', 'clear_site_cache'],
                ['class_exists', 'comet_cache', 'method_exists', 'comet_cache', 'clear'],
                ['class_exists', 'PagelyCachePurge'],
                ['function_exists', 'hyper_cache_clear'],
                ['function_exists', 'simple_cache_flush'],
                ['class_exists', 'autoptimizeCache', 'method_exists', 'autoptimizeCache', 'clearall'],
                ['class_exists', 'WP_Optimize_Cache_Commands'],
            ];

            foreach ($cache_checks as $check) {

                $count = count($check);
                if ($count === 1 && $check[0]) {
                    return true;
                }
                if ($count === 2 && in_array($check[0], ['function_exists', 'has_action']) && $check[0]($check[1])) {
                    return true;
                }
                if ($count === 2 && 'class_exists' === $check[0] && $check[0]($check[1], false)) {
                    return true;
                }
                if ($count === 3 && $check[0]($check[1]) && $check[0]($check[2])) {
                    return true;
                }

                if ($count === 4 && $check[0]($check[1], false) && $check[2]($check[3])) {
                    return true;
                }
            }

            return false;
        });
    }

    /*
     * Return plugin informations
     * @return        array/object
     * @author        Ivijan-Stefan Stipic
     */
    public static function plugin_info($fields = [])
    {
        return self::cached_static('plugin_info', function () use ($fields) {
            if (is_admin()) {

                if (! function_exists('plugins_api')) {
                    include_once WP_ADMIN_DIR . '/includes/plugin-install.php';
                }

                /** Prepare our query */
                //donate_link
                //versions
                return plugins_api('plugin_information', [
                    'slug'   => RSTR_NAME,
                    'fields' => array_merge([
                        'active_installs'     => false,       // rounded int
                        'added'               => false,       // date
                        'author'              => false,       // a href html
                        'author_block_count'  => false,       // int
                        'author_block_rating' => false,       // int
                        'author_profile'      => false,       // url
                        'banners'             => false,       // array( [low], [high] )
                        'compatibility'       => false,       // empty array?
                        'contributors'        => false,       // array( array( [profile], [avatar], [display_name] )
                        'description'         => false,       // string
                        'donate_link'         => false,       // url
                        'download_link'       => false,       // url
                        'downloaded'          => false,       // int
                        // 'group' => false,                  // n/a
                        'homepage'     => false,              // url
                        'icons'        => false,              // array( [1x] url, [2x] url )
                        'last_updated' => false,              // datetime
                        'name'         => false,              // string
                        'num_ratings'  => false,              // int
                        'rating'       => false,              // int
                        'ratings'      => false,              // array( [5..0] )
                        'requires'     => false,              // version string
                        'requires_php' => false,              // version string
                        // 'reviews' => false,                // n/a, part of 'sections'
                        'screenshots'              => false,  // array( array( [src],  ) )
                        'sections'                 => false,  // array( [description], [installation], [changelog], [reviews], ...)
                        'short_description'        => false,  // string
                        'slug'                     => false,  // string
                        'support_threads'          => false,  // int
                        'support_threads_resolved' => false,  // int
                        'tags'                     => false,  // array( )
                        'tested'                   => false,  // version string
                        'version'                  => false,  // version string
                        'versions'                 => false,  // array( [version] url )
                    ], $fields),
                ]);
            }

            return false;
        }, $fields);
    }

    /*
     * Delete all plugin transients and cached options
     * @return        array
     * @author        Ivijan-Stefan Stipic
    */
    public static function clear_plugin_cache(): void
    {
        global $wpdb;

        $locale = self::get_locale();

        if (Transliteration_Cache_DB::get(RSTR_NAME . ('-skip-words-' . $locale))) {
            Transliteration_Cache_DB::delete(RSTR_NAME . ('-skip-words-' . $locale));
        }

        if (Transliteration_Cache_DB::get(RSTR_NAME . ('-diacritical-words-' . $locale))) {
            Transliteration_Cache_DB::delete(RSTR_NAME . ('-diacritical-words-' . $locale));
        }

        if (Transliteration_Cache_DB::get(RSTR_NAME . '-locales')) {
            Transliteration_Cache_DB::delete(RSTR_NAME . '-locales');
        }

        if (get_option(RSTR_NAME . '-html-tags')) {
            delete_option(RSTR_NAME . '-html-tags');
        }

        if (get_option(RSTR_NAME . '-version')) {
            delete_option(RSTR_NAME . '-version');
        }

        if ($wpdb) {
            $RSTR_NAME = RSTR_NAME;
            $wpdb->query(sprintf("DELETE FROM `%s` WHERE `%s`.`option_name` REGEXP '^_transient_(.*)?%s(.*|\$)'", $wpdb->options, $wpdb->options, $RSTR_NAME));
        }

        if (class_exists('Transliteration_Plugins')) {
            Transliteration_Plugins::clear_cache();
        }
    }

    /*
     * Set cookie
     * @since     1.0.10
     * @version   1.0.0
    */
    public static function setcookie($val, $expire = null): bool
    {
        // Check if headers are already sent
        if (headers_sent()) {
            return false;
        }

        // Set default expiration if not provided
        if ($expire === null) {
            $expire = time() + YEAR_IN_SECONDS;
        }

        // Set the cookie
        setcookie('rstr_script', $val, ['expires' => $expire, 'path' => COOKIEPATH, 'domain' => COOKIE_DOMAIN]);

        // Send no-cache headers if available
        if (function_exists('nocache_headers')) {
            nocache_headers();
        }

        return true;
    }

    /*
     * Get current script
     * @since     1.0.10
     * @version   1.0.0
     */
    public static function get_current_script()
    {
        return self::cached_static('get_current_script', function () {
            // Cookie mode
            if (!empty($_COOKIE['rstr_script'] ?? null)) {
                switch (sanitize_text_field($_COOKIE['rstr_script'])) {
                    case 'lat':
                        return 'lat';

                    case 'cyr':
                        return 'cyr';
                }
            }

            // Set new script
            return get_rstr_option('first-visit-mode', 'lat');
        });
    }

    /*
     * Flush Cache
     * @version   1.0.1
     */
    protected static $cache_flush = false;

    public static function cache_flush(): bool
    {

        if (headers_sent()) {
            return false;
        }

        // Flush must be fired only once
        if (self::$cache_flush) {
            return true;
        }

        self::$cache_flush = true;

        // Let's enable all caches
        global $post, $user, $w3_plugin_totalcache;

        // Standard cache
        header('Expires: Tue, 01 Jan 2000 00:00:00 GMT');
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
        header('Cache-Control: post-check=0, pre-check=0', false);
        header('Cache-Control: no-store, no-cache, max-age=0, must-revalidate, proxy-revalidate');
        header('Clear-Site-Data: "cache", "storage", "executionContexts"');
        header('Pragma: no-cache');

        // Set nocache headers
        if (function_exists('nocache_headers')) {
            nocache_headers();
        }

        // Flush WP cache
        if (function_exists('wp_cache_flush')) {
            wp_cache_flush();
        }
        /*
        // Clean user cache
        if($user && function_exists('clean_user_cache')) {
            clean_user_cache( $user );
        }
        */
        /*
        // Clean stanrad WP cache
        if($post && function_exists('clean_post_cache')) {
            clean_post_cache( $post );
        }
        */
        // Flush LS Cache
        if (class_exists('\LiteSpeed\Purge', false)) {
            \LiteSpeed\Purge::purge_all();
            return true;
        }
        if (has_action('litespeed_purge_all')) {
            do_action('litespeed_purge_all');
            return true;
        }

        if (function_exists('liteSpeed_purge_all')) {
            litespeed_purge_all();
            return true;
        }
        // W3 Total Cache
        if (function_exists('w3tc_flush_all')) {
            w3tc_flush_all();
            return true;
        }

        // W3 Total Cache
        if ($w3_plugin_totalcache) {
            $w3_plugin_totalcache->flush_all();
            return true;
        }

        // WP Fastest Cache
        if (function_exists('wpfc_clear_all_cache')) {
            wpfc_clear_all_cache(true);
            return true;
        }

        // WP Rocket
        if (function_exists('rocket_clean_domain')) {
            rocket_clean_domain();
            return true;
        }

        // WP Super Cache
        if (function_exists('prune_super_cache') && function_exists('get_supercache_dir')) {
            prune_super_cache(get_supercache_dir(), true);
            return true;
        }

        // Cache Enabler
        if (function_exists('clear_site_cache')) {
            clear_site_cache();
            return true;
        }

        // Comet Cache
        if (class_exists('comet_cache', false) && method_exists('comet_cache', 'clear')) {
            comet_cache::clear();
            return true;
        }

        // Clean Pagely cache
        if (class_exists('PagelyCachePurge', false)) {
            (new PagelyCachePurge())->purgeAll();
            return true;
        }

        // Clean Hyper Cache
        if (function_exists('hyper_cache_clear')) {
            hyper_cache_clear();
            return true;
        }

        // Clean Simple Cache
        if (function_exists('simple_cache_flush')) {
            simple_cache_flush();
            return true;
        }

        // Clean Autoptimize
        if (class_exists('autoptimizeCache') && method_exists('autoptimizeCache', 'clearall')) {
            autoptimizeCache::clearall();
            return true;
        }

        // Clean WP-Optimize
        if (class_exists('WP_Optimize_Cache_Commands', false)) {
            (new WP_Optimize_Cache_Commands())->purge_page_cache();
            return true;
        }

        return false;
    }

    /*
     * Decode content
     * @return        string
     * @author        Ivijan-Stefan Stipic
    */
    public static function decode($content, $flag = ENT_NOQUOTES)
    {
        if (!empty($content) && is_string($content) && !is_numeric($content) && !is_array($content) && !is_object($content)) {
            if (filter_var($content, FILTER_VALIDATE_URL) && strpos($content, '%') !== false) {
                // If content is a valid URL and contains encoded characters
                $content = rawurldecode($content);
            } else {
                // Decode HTML entities
                $content = html_entity_decode($content, $flag);
            }
        }

        return $content;
    }

    /*
     * Check is already cyrillic
     * @return        string
     * @author        Ivijan-Stefan Stipic
    */
    public static function already_cyrillic()
    {
        return self::cached_static('already_cyrillic', fn (): bool => in_array(self::get_locale(), apply_filters('rstr_already_cyrillic', ['sr_RS', 'mk_MK', 'bg_BG', 'ru_RU', 'uk', 'kk', 'el', 'ar', 'hy', 'sr', 'mk', 'bg', 'ru', 'bel', 'sah', 'bs', 'kir', 'mn', 'ba', 'uz', 'ka', 'tg', 'cnr', 'bs_BA'])) !== (1 === 2));
    }
	
	/*
     * Check is Polylang
     * @return        boolean
     * @author        Ivijan-Stefan Stipic
     */
    public static function is_pll()
    {
		static $is_pll = NULL;
		
		if($is_pll === NULL) {
			$is_pll = function_exists('pll_current_language');
		}
		
		return $is_pll;
	}

    /*
     * Check is cyrillic letters
     * @return        boolean
     * @author        Ivijan-Stefan Stipic
     */
    public static function is_cyr($string)
    {
        if (!is_string($string) || is_numeric($string)) {
            return false;
        }

        $string = strip_tags($string, '');

        if (strlen($string) > 10) {
            $string = substr($string, 0, 10);
        }

        $pattern = '/[\x{0400}-\x{04FF}\x{0500}-\x{052F}\x{2DE0}-\x{2DFF}\x{A640}-\x{A69F}\x{1C80}-\x{1C8F}\x{0370}-\x{03FF}\x{1F00}-\x{1FFF}\x{0600}-\x{06FF}\x{0750}-\x{077F}\x{08A0}-\x{08FF}\x{FB50}-\x{FDFF}\x{FE70}-\x{FEFF}\x{0530}-\x{058F}]+/u';

        return preg_match($pattern, $string) === 1;
    }

    /*
     * Check is latin letters
     * @return        boolean
     * @author        Ivijan-Stefan Stipic
    */
    public static function is_lat($string): bool
    {
        return !self::is_cyr($string);
    }

    /*
     * Check is plugin active
     */
    public static function is_plugin_active($plugin): bool
    {
        static $active_plugins = null;

        if ($active_plugins === null) {
            if (!function_exists('is_plugin_active')) {
                include_once WP_ADMIN_DIR . '/includes/plugin.php';
            }

            $active_plugins = get_option('active_plugins', []);
        }

        return in_array($plugin, $active_plugins);
    }

    /*
     * Check if it is a block editor screen
     * @since     1.0.9
     * @version   1.0.0
     */
    public static function is_editor()
    {
        return self::cached_static('is_editor', function () {
            $is_editor = false;

            // Check for specific editors and set the cache if true
            if (
                self::is_elementor_editor() ||
                self::is_oxygen_editor() ||
                self::is_wpbakery_editor()
            ) {
                return true;
            }

            // Determine if the current screen is the block editor
            if (version_compare(get_bloginfo('version'), '5.0', '>=')) {
                if (!function_exists('get_current_screen')) {
                    include_once WP_ADMIN_DIR . '/includes/screen.php';
                }

                $current_screen = get_current_screen();
                if (is_callable([$current_screen, 'is_block_editor']) && method_exists($current_screen, 'is_block_editor')) {
                    $is_editor = $current_screen->is_block_editor();
                }
            }

            if (!$is_editor) {
                return isset($_GET['action'], $_GET['post']) && $_GET['action'] === 'edit' && is_numeric($_GET['post']);
            }

            // Return the cached result
            return $is_editor;
        });
    }

    /*
     * Check is in the Elementor editor mode
     * @version   1.0.0
     */
    public static function is_elementor_editor()
    {
        return self::cached_static('is_elementor_editor', function (): bool {
            if (
                (
                    self::is_plugin_active('elementor/elementor.php')
                    && ($_REQUEST['action'] ?? null) === 'elementor'
                    && is_numeric($_REQUEST['post'] ?? null)
                )
                || preg_match('/^(elementor_(.*?))$/i', ($_REQUEST['action'] ?? ''))
            ) {
                return true;

                // Deprecated
                //	return \Elementor\Plugin::$instance->editor->is_edit_mode();
            }

            return false;
        });
    }

    /*
     * Check is in the Elementor preview mode
     * @version   1.0.0
     */
    public static function is_elementor_preview()
    {
        return self::cached_static('is_elementor_preview', function (): bool {
            if (
                !is_admin()
                && (
                    self::is_plugin_active('elementor/elementor.php')
                    && ($_REQUEST['preview'] ?? null) == 'true'
                    && is_numeric($_REQUEST['page_id'] ?? null)
                    && is_numeric($_REQUEST['preview_id'] ?? null)
                    && !empty($_REQUEST['preview_nonce'] ?? null)
                ) || preg_match('/^(elementor_(.*?))$/i', ($_REQUEST['action'] ?? ''))
            ) {
                return true;

                // Deprecated
                //	return \Elementor\Plugin::$instance->preview->is_preview_mode();
            }

            return false;
        });
    }

    /*
     * Check is in the Oxygen editor mode
     * @version   1.0.0
     */
    public static function is_oxygen_editor()
    {
        return self::cached_static('is_oxygen_editor', fn (): bool => self::is_plugin_active('oxygen/functions.php') && (($_REQUEST['ct_builder'] ?? null) == 'true' || ($_REQUEST['ct_inner'] ?? null) == 'true' || preg_match('/^((ct_|oxy_)(.*?))$/i', ($_REQUEST['action'] ?? ''))));
    }

    /*
     * Check is in the WPBakery editor mode
     * @version   1.0.0
     */
    public static function is_wpbakery_editor()
    {
        return self::cached_static('is_wpbakery_editor', function (): bool {
            if (
                self::is_plugin_active('js_composer/js_composer.php') && (
                    (!empty($_GET['vc_editable']) && $_GET['vc_editable'] === 'true') ||
                    (!empty($_GET['vc_action']) && $_GET['vc_action'] === 'vc_inline') ||
                    (function_exists('vc_is_inline') && vc_is_inline())
                )
            ) {
                return true;
            }

            return false;
        });
    }

    /*
     * Generate unique token
     * @author    Ivijan-Stefan Stipic
     */
    public static function generate_token($length = 16): string
    {
        // Use random_bytes for cryptographically secure token generation
        try {
            $bytes = random_bytes($length);
        } catch (Exception $exception) {
            // Fallback to openssl_random_pseudo_bytes if random_bytes is unavailable
            if (function_exists('openssl_random_pseudo_bytes')) {
                $bytes = openssl_random_pseudo_bytes($length);
            }
            // Fallback to uniqid with additional entropy as a last resort
            else {
                $bytes = bin2hex(uniqid('t' . microtime(), true));
            }
        }

        // Return the token, ensuring it matches the desired length
        return substr(bin2hex($bytes), 0, $length);
    }

    /*
     * Delete all plugin translations
     * @return        bool
     * @author        Ivijan-Stefan Stipic
     */
    public static function clear_plugin_translations(): bool
    {
        $domain_paths = [
            path_join(WP_LANG_DIR, 'plugins') . '/serbian-transliteration-*.{po,mo,l10n.php}',
            path_join(dirname(RSTR_ROOT), '') . '/serbian-transliteration-*.{po,mo,l10n.php}',
            path_join(WP_LANG_DIR, '') . '/serbian-transliteration-*.{po,mo,l10n.php}',
        ];

        $deleted_files = 0;

        foreach ($domain_paths as $pattern) {
            foreach (glob($pattern, GLOB_BRACE) as $file) {
                if (@unlink($file)) {
                    $deleted_files++;
                } else {
                    error_log(sprintf(__('Failed to delete plugin translation: %s', 'serbian-transliteration'), $file));
                }
            }
        }

        return $deleted_files > 0;
    }

    /*
     * PHP Wrapper for explode — Split a string by a string
     * @since     1.0.9
     * @version   1.0.0
     * @url       https://www.php.net/manual/en/function.explode.php
     */
    /**
     * @return mixed[]
     */
    public static function explode(string $separator, string $string, ?int $limit = PHP_INT_MAX, bool $keep_empty = false): array
    {
        if ($separator === '') {
            // Ako je separator prazan, vratite prazan niz ili prijavite grešku
            return [];
        }

        // Handle limit
        if ($limit === null) {
            $limit = PHP_INT_MAX;
        }

        // Explode string
        $string = explode($separator, ($string ?? ''), $limit);

        // Trim whitespace from each element
        $string = array_map('trim', $string);

        // Optionally filter out empty elements
        if (!$keep_empty) {
            return array_filter($string, fn ($value): bool => $value !== '');
        }

        return $string;
    }

    /**
     * Get current page ID
     * @autor    Ivijan-Stefan Stipic
     * @since    1.0.7
     * @version  2.0.1
     ******************************************************************/
    public static function get_page_ID()
    {
        return self::cached_static('get_page_ID', function () {
            global $post;
            // Check different methods to get the page ID and cache the result
            if ($id = self::get_page_ID__private__wp_query()) {
                return $id;
            }
            if ($id = self::get_page_ID__private__get_the_id()) {
                return $id;
            }
            if (!is_null($post) && isset($post->ID) && !empty($post->ID)) {
                return $post->ID;
            }
            if ($post = self::get_page_ID__private__GET_post()) {
                return $post;
            }
            if ($p = self::get_page_ID__private__GET_p()) {
                return $p;
            }
            if ($page_id = self::get_page_ID__private__GET_page_id()) {
                return $page_id;
            }
            if ($id = self::get_page_ID__private__query()) {
                return $id;
            }
            // Check different methods to get the page ID and cache the result
            if ($id = self::get_page_ID__private__page_for_posts()) {
                return get_option('page_for_posts');
            }

            return false;
        });
    }

    // Get page ID by using get_the_id() function
    protected static function get_page_ID__private__get_the_id()
    {
        if (function_exists('get_the_id') && $id = get_the_id()) {
            return $id;
        }

        return false;
    }

    // Get page ID by wp_query
    protected static function get_page_ID__private__wp_query()
    {
        global $wp_query;
        return ((!is_null($wp_query) && isset($wp_query->post) && isset($wp_query->post->ID) && !empty($wp_query->post->ID)) ? $wp_query->post->ID : false);
    }

    // Get page ID by GET[post] in edit mode
    protected static function get_page_ID__private__GET_post()
    {
        return ((isset($_GET['action']) && sanitize_text_field($_GET['action']) == 'edit') && (isset($_GET['post']) && is_numeric($_GET['post'])) ? absint($_GET['post']) : false);
    }

    // Get page ID by GET[page_id]
    protected static function get_page_ID__private__GET_page_id()
    {
        return ((isset($_GET['page_id']) && is_numeric($_GET['page_id'])) ? absint($_GET['page_id']) : false);
    }

    // Get page ID by GET[p]
    protected static function get_page_ID__private__GET_p()
    {
        return ((isset($_GET['p']) && is_numeric($_GET['p'])) ? absint($_GET['p']) : false);
    }

    // Get page ID by OPTION[page_for_posts]
    protected static function get_page_ID__private__page_for_posts()
    {
        $page_for_posts = get_option('page_for_posts');
        return (!is_admin() && 'page' == get_option('show_on_front') && $page_for_posts ? absint($page_for_posts) : false);
    }

    // Get page ID by mySQL query
    protected static function get_page_ID__private__query()
    {
        if (is_admin()) {
            return false;
        }

        global $wpdb;
        $actual_link = rtrim($_SERVER['REQUEST_URI'] ?? '', '/');

        // Parse the URL to get the path
        $parsed_url = parse_url($actual_link);
        $path       = $parsed_url['path'];

        // Explode the path into parts and get the last part
        $parts = explode('/', trim($path, '/'));
        $slug  = end($parts);

        if (!empty($slug) && $post_id = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT `ID` 
					FROM `{$wpdb->posts}`
					WHERE `post_status` = 'publish'
					AND `post_name` = %s
					AND TRIM(`post_name`) <> ''
					LIMIT 1",
                sanitize_title($slug)
            )
        )) {
            return absint($post_id);
        }

        return false;
    }

    /**
     * END Get current page ID
     *****************************************************************/

    /*
    * Normalize latin string and remove special characters
    * @since     1.6.7
    */
    public static function normalize_latin_string($str)
	{
		// Step 1: Transliterate using WP native
		if (function_exists('remove_accents')) {
			$str = remove_accents($str);
		}

		// Step 2: Apply custom Unicode symbol map
		$map = apply_filters('rstr/utilities/normalize_latin_string', RSTR_NORMALIZE_LATIN_STRING_MAP, $str);

		// Step 3: Lazy translations
		$placeholders = [
			'%%degrees%%'         => __(' degrees ', 'serbian-transliteration'),
			'%%divided_by%%'      => __(' divided by ', 'serbian-transliteration'),
			'%%times%%'           => __(' times ', 'serbian-transliteration'),
			'%%plus_minus%%'      => __(' plus-minus ', 'serbian-transliteration'),
			'%%square_root%%'     => __(' square root ', 'serbian-transliteration'),
			'%%infinity%%'        => __(' infinity ', 'serbian-transliteration'),
			'%%almost_equal%%'    => __(' almost equal to ', 'serbian-transliteration'),
			'%%not_equal%%'       => __(' not equal to ', 'serbian-transliteration'),
			'%%identical%%'       => __(' identical to ', 'serbian-transliteration'),
			'%%less_equal%%'      => __(' less than or equal to ', 'serbian-transliteration'),
			'%%greater_equal%%'   => __(' greater than or equal to ', 'serbian-transliteration'),
			'%%left%%'            => __(' left ', 'serbian-transliteration'),
			'%%right%%'           => __(' right ', 'serbian-transliteration'),
			'%%up%%'              => __(' up ', 'serbian-transliteration'),
			'%%down%%'            => __(' down ', 'serbian-transliteration'),
			'%%left_right%%'      => __(' left and right ', 'serbian-transliteration'),
			'%%up_down%%'         => __(' up and down ', 'serbian-transliteration'),
			'%%care_of%%'         => __(' care of ', 'serbian-transliteration'),
			'%%estimated%%'       => __(' estimated ', 'serbian-transliteration'),
			'%%ohm%%'             => __(' ohm ', 'serbian-transliteration'),
			'%%female%%'          => __(' female ', 'serbian-transliteration'),
			'%%male%%'            => __(' male ', 'serbian-transliteration'),
			'%%copyright%%'       => __(' Copyright ', 'serbian-transliteration'),
			'%%registered%%'      => __(' Registered ', 'serbian-transliteration'),
			'%%trademark%%'       => __(' Trademark ', 'serbian-transliteration'),
			'%%latin%%'           => __('Latin', 'serbian-transliteration'),
			'%%cyrillic%%'        => __('Cyrillic', 'serbian-transliteration'),
		];

		// Replace placeholders with translated strings
		$map = array_map(function ($value) use ($placeholders) {
			return $placeholders[$value] ?? $value;
		}, $map);

		// Final string replacement
		return strtr($str, $map);
	}

    /*
     * Get skip words
     * @return        bool false, array or string on needle
     * @author        Ivijan-Stefan Stipic
    */
    public static function get_skip_words($needle = null)
    {
        $locale         = self::get_locale();
        $transient_name = RSTR_NAME . ('-skip-words-' . $locale);
        $cache          = Transliteration_Cache_DB::get($transient_name);
        if (empty($cache)) {
            $file_name = apply_filters(
                'rstr/init/libraries/file/skip-words',
                $locale . '.skip.words.lib',
                $locale,
                $transient_name
            );
            $cache = self::parse_library($file_name);
            if (!empty($cache)) {
                Transliteration_Cache_DB::set(
                    $transient_name,
                    $cache,
                    apply_filters('rstr/init/libraries/file/skip-words/transient', (DAY_IN_SECONDS * 7))
                );
            }
        }

        if ($needle && is_array($cache)) {
            return (in_array($needle, $cache, true) ? $needle : false);
        }

        return $cache;
    }

    /*
     * Get list of diacriticals
     * @return        bool false, array or string on needle
     * @author        Ivijan-Stefan Stipic
    */
    public static function get_diacritical($needle = null)
    {
        $locale         = self::get_locale();
        $transient_name = RSTR_NAME . ('-diacritical-words-' . $locale);
        $cache          = Transliteration_Cache_DB::get($transient_name);
        if (empty($cache)) {
            $file_name = apply_filters(
                'rstr/init/libraries/file/get_diacritical',
                $locale . '.diacritical.words.lib',
                $locale,
                $transient_name
            );
            $cache = self::parse_library($file_name);
            if (!empty($cache)) {
                Transliteration_Cache_DB::set(
                    $transient_name,
                    $cache,
                    apply_filters('rstr/init/libraries/file/get_diacritical/transient', (DAY_IN_SECONDS * 7))
                );
            }
        }

        if ($needle && is_array($cache)) {
            return (in_array($needle, $cache, true) ? $needle : false);
        }

        return $cache;
    }

    /*
     * Parse library
     * @return        bool false, array or string on needle
     * @author        Ivijan-Stefan Stipic
     */
    public static function parse_library(string $file_name, $needle = null)
    {

        $words      = [];
        $words_file = apply_filters('rstr/init/libraries/file', RSTR_ROOT . '/libraries/' . $file_name);

        if (file_exists($words_file)) {
            $contents = '';
            if ($read_file_chunks = self::read_file_chunks($words_file)) {
                foreach ($read_file_chunks as $chunk) {
                    $contents .= $chunk;
                }
            }

            if ($contents !== '' && $contents !== '0') {
                $words = self::explode("\n", $contents);
                $words = array_unique($words);
            } else {
                return false;
            }
        } else {
            return false;
        }

        if ($needle) {
            return (in_array($needle, $words, true) ? $needle : false);
        }
        return $words;
    }

    /**
     * Parse URL
     * @since     1.2.2
     * @version   1.0.0
     */
    public static function parse_url()
    {
        static $cachedUrl = null;

        if ($cachedUrl === null) {
            $http   = 'http' . (self::is_ssl() ? 's' : '');
            $domain = rtrim(preg_replace('%:/{3,}%i', '://', $http . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost')), '/');
            $url    = preg_replace('%:/{3,}%i', '://', $domain . '/' . (isset($_SERVER['REQUEST_URI']) ? ltrim($_SERVER['REQUEST_URI'], '/') : ''));

            $cachedUrl = [
                'method'    => $http,
                'home_fold' => str_replace($domain, '', home_url()),
                'url'       => $url,
                'domain'    => $domain,
            ];
        }

        return $cachedUrl;
    }

    /*
     * CHECK IS SSL
     * @return  true/false
     */
    public static function is_ssl($url = false)
    {
        return self::cached_static('is_ssl', function () use ($url): bool {
            if ($url !== false && preg_match('/^(https|ftps):/i', $url) === 1) {
                return true;
            }

            $conditions = [
                is_admin() && defined('FORCE_SSL_ADMIN') && FORCE_SSL_ADMIN,
                ($_SERVER['HTTPS'] ?? '')                  === 'on',
                ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https',
                ($_SERVER['HTTP_X_FORWARDED_SSL'] ?? '')   === 'on',
                ($_SERVER['SERVER_PORT'] ?? 0)           == 443,
                ($_SERVER['HTTP_X_FORWARDED_PORT'] ?? 0) == 443,
                ($_SERVER['REQUEST_SCHEME'] ?? '') === 'https',
            ];

            return in_array(true, $conditions, true);
        }, $url);
    }

	/*
     * Advanced array filter
     * @return  array
     */
    public static function array_filter($array, $remove, $reindex = false)
    {

        if (empty($remove)) {
            if ($reindex && array_values($array) === $array) {
                return array_values($array);
            }

            return $array;
        }

        if (!is_array($remove)) {
            $remove = self::explode(',', $remove);
        }

        $array = array_filter($array, function ($value) use ($remove): bool {
            if (is_array($value)) {
                $value = self::array_filter($value, $remove);
            }

            foreach ($remove as $item) {
                if (is_array($value) && is_array($item)) {
                    // Dubinsko poređenje nizova
                    if ($value === $item) {
                        return false;
                    }
                } elseif ($value === $item) {
                    return false;
                }
            }

            return true;
        });

        if ($reindex && array_values($array) === $array) {
            return array_values($array);
        }

        return $array;
    }

    /*
     * Check if it's wp-admin
     * @return  true/false
     */
    public static function is_admin()
    {
        return self::cached_static('is_admin', function (): bool {
            global $rstr_is_admin;
            return $rstr_is_admin || (is_admin() && !wp_doing_ajax());
        });
    }

    /*
     * Check if it's sitemap
     * @return  true/false
     */
    public static function is_sitemap()
    {
        return self::cached_static('is_sitemap', function (): bool {
            // Define the regex pattern for sitemap URLs
            $pattern = '/((wp-sitemap|sitemap(-[a-z0-9-]+)?)\.(xml|html))/i';
            // Check the current request URI against the pattern
            return isset($_SERVER['REQUEST_URI']) && preg_match($pattern, $_SERVER['REQUEST_URI']);
        });
    }

    /*
     * Check if it's Light Speed cache active
     * @return  true/false
     */
    public static function is_lscache_active()
    {
        return self::cached_static('is_lscache_active', function (): bool {
            // Check if LiteSpeed Cache plugin is enabled
            if (defined('LSCACHE_ENABLED') && LSCACHE_ENABLED) {
                return true;
            }

            // Check if the server software is LiteSpeed
            $software = $_SERVER['SERVER_SOFTWARE'] ?? null;
            if ($software && stripos($software, 'LiteSpeed') !== false) {
                return true;
            }
            // Check for LiteSpeed Cache specific HTTP header
            // Default: LiteSpeed Cache is not active
            return !empty($_SERVER['HTTP_X_LITESPEED_CACHE'] ?? null);
        });
    }

    /*
     * Check if it's Light Speed is cachable
     * @return  true/false
     */
    public static function litespeed_is_cachable()
    {
        return self::cached_static('litespeed_is_cachable', fn (): bool => defined('LSCACHE_ENABLED') && LSCACHE_ENABLED && function_exists('litespeed_is_cachable') && litespeed_is_cachable());
    }
	
	/*
	 * Return plugin settings in debug format
	 */
	public static function debug_render_all_settings_fields($format = 'html')
	{
		$page = 'serbian-transliteration';

		global $wp_settings_fields;

		if (!isset($wp_settings_fields[$page])) {
			if ($format === 'html') {
				echo '<p>No settings fields registered on this page.</p>';
			} else {
				return ($format === 'json') ? json_encode([]) : serialize([]);
			}
			return;
		}

		$disable_by_language = [];
		foreach ($wp_settings_fields[$page] as $section => $fields) {
			foreach ($fields as $id => $field) {
				if (strpos($id, 'disable-by-language-') !== false) {
					$disable_by_language = get_rstr_option('disable-by-language');
					break;
				}
			}
		}

		$data = [];
		$e = 0;

		foreach ($wp_settings_fields[$page] as $section => $fields) {
			foreach ($fields as $id => $field) {
				if (strpos($id, 'disable-by-language-') !== false) {
					if ($e === 1) {
						continue;
					}
					$label = __('Exclusion', 'serbian-transliteration');
					$val = $disable_by_language;
					++$e;
				} else {
					$label = $field['title'] ?? $id;
					$val = get_rstr_option($id);
				}
				$data[] = ['label' => $label, 'value' => $val];
			}
		}

		if ($format === 'json') {
			return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
		}

		if ($format === 'serialize') {
			return serialize($data);
		}

		// Default: HTML table output
		echo '<table class="rstr-debug-table" style="width:100%; max-width:100%; text-align:left; border-collapse: collapse">';
		echo '<tr>
			<th style="width:35%;min-width:165px;border:1px solid #efefef;padding:8px;">' . esc_html__('Option name', 'serbian-transliteration') . '</th>
			<th style="border:1px solid #efefef;padding:8px;">' . esc_html__('Value', 'serbian-transliteration') . '</th>
		</tr>';

		foreach ($data as $row) {
			$label = $row['label'];
			$val = $row['value'];

			echo '<tr>';
			echo '<td style="font-weight:600;border:1px solid #efefef;padding:8px;">' . esc_html($label) . '</td>';
			echo '<td style="border:1px solid #efefef;padding:' . esc_html(is_array($val) ? 0 : 8) . 'px;">';

			if (is_array($val)) {
				echo '<table class="rstr-debug-table-iner" style="width:100%;text-align:left;border-collapse:collapse;">';
				echo '<tr><th style="width:50%;border:1px solid #efefef;padding:8px;">' . esc_html__('Key', 'serbian-transliteration') . '</th>';
				echo '<th style="border:1px solid #efefef;padding:8px;">' . esc_html__('Value', 'serbian-transliteration') . '</th></tr>';
				foreach ($val as $i => $prop) {
					echo '<tr>';
					echo '<td style="border:1px solid #efefef;padding:8px;">' . esc_html($i) . '</td>';
					echo '<td style="border:1px solid #efefef;padding:8px;">' . esc_html($prop) . '</td>';
					echo '</tr>';
				}
				echo '</table>';
			} else {
				echo esc_html($val);
			}

			echo '</td>';
			echo '</tr>';
		}

		echo '</table>';
	}
	
	/**
	 * Hook to capture language when Polylang announces it. Runs very early.
	 * @param string $slug
	 */
	public static function on_pll_language_defined($slug) : void
	{
		// Try to read precise locale from Polylang if available
		$locale = self::is_pll() ? pll_current_language('locale') : null;
		if (!$locale) {
			$locale = self::get_locale();
		}
		self::$lang_cache = [
			'slug'   => (string) $slug,
			'locale' => (string) $locale,
			'ready'  => true,
		];
	}

	/**
	 * Fallback detector executed on 'wp' if language was not captured earlier.
	 * Guarantees we attempt a late resolve once the query is parsed.
	 */
	public static function late_language_resolve() : void
	{
		if (self::$lang_cache && !empty(self::$lang_cache['ready'])) {
			return; // already resolved
		}
		$slug = self::is_pll() ? pll_current_language('slug') : null;
		$locale = self::is_pll() ? pll_current_language('locale') : null;
		if (!$locale) {
			$locale = self::get_locale();
		}
		self::$lang_cache = [
			'slug'   => (string) ($slug ?: ''),
			'locale' => (string) ($locale ?: ''),
			'ready'  => (bool) $slug || (bool) $locale,
		];
	}

	/**
	 * Returns the best-known current language tuple.
	 * If not ready, returns ['slug'=>'','locale'=>'','ready'=>false].
	 *
	 * @return array{slug:string,locale:string,ready:bool}
	 */
	public static function current_language_info() : array
	{
		if (!self::$lang_cache) {
			// Very early call (before hooks). Populate with whatever we can guess.
			$slug = self::is_pll() ? pll_current_language('slug') : null;
			$locale = self::is_pll() ? pll_current_language('locale') : null;
			if (!$locale) {
				$locale = self::get_locale();
			}
			self::$lang_cache = [
				'slug'   => (string) ($slug ?: ''),
				'locale' => (string) ($locale ?: ''),
				'ready'  => (bool) $slug || (bool) $locale,
			];
		}
		return self::$lang_cache;
	}
}
