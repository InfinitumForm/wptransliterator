<?php

if (!defined('WPINC')) {
    die();
}

/**
 * Active Plugin: Polylang
 *
 * @link              http://infinitumform.com/
 * @since             1.2.4
 * @package           Serbian_Transliteration
 * @author            Ivijan-Stefan Stipic
 */

class Transliteration_Plugin_Polylang extends Transliteration
{
    public function __construct()
    {
        $this->add_filter('pll_the_language_link', 'get_the_languages', 10, 2);
    }

    // So... we need to keep it under control
    public function get_the_languages($url, $slug)
    {
        static $languages, $current_language;

        if (!$current_language) {
            $current_language = pll_current_language();
        }

        if ($current_language === $slug) {
            return $url;
        }

        if (!$languages) {
            $languages = apply_filters('rstr_plugin_polylang_languages', [
                'uk', 'kk', 'el', 'ar', 'hy', 'sr', 'mk',
                'bg', 'ru', 'bel', 'sah', 'bs', 'kir',
                'mn', 'ba', 'uz', 'ka', 'tg', 'cnr',
            ], $url, $slug);
        }

        if (in_array($slug, $languages)) {
            return add_query_arg(get_rstr_option('url-selector', 'rstr'), get_rstr_option('first-visit-mode', 'lat'), $url);
        }

        return add_query_arg(get_rstr_option('url-selector', 'rstr'), 'lat', $url);
    }
}
