<?php

if (!defined('WPINC')) {
    die();
}

/**
 * Set of important functions
 *
 * @link              http://infinitumform.com/
 * @since             1.0.7
 * @package           Serbian_Transliteration
 ***********************************************/

// Indicate functions file for the extended plugins
if (!defined('RSTR_FUNCTIONS')) {
    define('RSTR_FUNCTIONS', true);
}


/*
 * Is transliteration excluded
 * @return        bool
 * @author        Ivijan-Stefan Stipic
*/
if (!function_exists('transliteration_excluded')) :
    function transliteration_excluded(): bool
    {
        return Transliteration_Utilities::exclude_transliteration();
    }
endif;

/*
 * Get the latin URL
 * @param         $url (optional)
 * @return        string
 * @author        Ivijan-Stefan Stipic
*/
if (!function_exists('get_latin_url')) :
    function get_latin_url(string $url = null)
    {

        if ($url) {
            return add_query_arg(get_rstr_option('url-selector', 'rstr'), 'lat', $url);
        }

        return add_query_arg(get_rstr_option('url-selector', 'rstr'), 'lat');
    }
endif;


/*
 * Get the cyrillic URL
 * @param         $url (optional)
 * @return        string
 * @author        Ivijan-Stefan Stipic
*/
if (!function_exists('get_cyrillic_url')) :
    function get_cyrillic_url(string $url = null)
    {
        if ($url) {
            return add_query_arg(get_rstr_option('url-selector', 'rstr'), 'cyr', $url);
        }

        return add_query_arg(get_rstr_option('url-selector', 'rstr'), 'cyr');
    }
endif;

/*
 * Get current URL
 * @return        string
 * @author        Ivijan-Stefan Stipic
*/
if (!function_exists('get_current_url')) :
    function get_current_url()
    {
        $parse_url = Transliteration_Utilities::parse_url();
        return $parse_url['url'];
    }
endif;


/*
 * Get current script
 * @return        string
 * @author        Ivijan-Stefan Stipic
*/
if (!function_exists('get_script')) :
    function get_script()
    {
        return Transliteration_Utilities::get_current_script();
    } elseif (!function_exists('rstr_get_script')):
        function rstr_get_script()
        {
            _doing_it_wrong('rstr_get_script', __('This function is deprecated and will be removed. Replace it with the `get_script()` function', 'serbian-transliteration'), '1.10.5');
            return Transliteration_Utilities::get_current_script();
        }
    endif;


/*
 * Get the active transliteration
 * @return        string
 * @author        Ivijan-Stefan Stipic
*/
if (!function_exists('get_active_transliteration')) :
    function get_active_transliteration()
    {
        return (function_exists('rstr_get_script') ? rstr_get_script() : get_script());
    }
endif;


/*
 * Check is latin text
 * @return        boolean
 * @author        Ivijan-Stefan Stipic
*/
if (!function_exists('is_latin_text')) :
    function is_latin_text($content)
    {
        return Transliteration_Utilities::is_lat($content);
    }
endif;

/*
 * Check is cyrillic text
 * @return        boolean
 * @author        Ivijan-Stefan Stipic
*/
if (!function_exists('is_cyrillic_text')) :
    function is_cyrillic_text($content)
    {
        return Transliteration_Utilities::is_cyr($content);
    }
endif;

/*
 * Check is site on latin
 * @return        boolean
 * @author        Ivijan-Stefan Stipic
*/
if (!function_exists('is_latin')) :
    function is_latin(): bool
    {
        $script = function_exists('get_script') ? get_script() : rstr_get_script();

        return in_array($script, ['cyr_to_lat', 'lat']);
    }
endif;

/*
 * Check is site on cyrillic
 * @return        boolean
 * @author        Ivijan-Stefan Stipic
*/
if (!function_exists('is_cyrillic')) :
    function is_cyrillic(): bool
    {
        $script = function_exists('get_script') ? get_script() : rstr_get_script();

        return in_array($script, ['lat_to_cyr', 'cyr']);
    }
endif;

/*
 * Check is already cyrillic
 * @return        boolean
 * @author        Ivijan-Stefan Stipic
*/
if (!function_exists('is_already_cyrillic')) :
    function is_already_cyrillic()
    {
        return Transliteration_Utilities::already_cyrillic();
    }
endif;

/*
 * Check is Serbian language
 * @return        string
 * @author        Ivijan-Stefan Stipic
*/
if (!function_exists('is_serbian')) :
    function is_serbian()
    {
        return Transliteration_Utilities::get_locale('sr_RS');
    }
endif;

/*
 * Check is Russian language
 * @return        boolean
 * @author        Ivijan-Stefan Stipic
*/
if (!function_exists('is_russian')) :
    function is_russian()
    {
        return Transliteration_Utilities::get_locale('ru_RU');
    }
endif;

/*
 * Check is Georgian language
 * @return        boolean
 * @author        Ivijan-Stefan Stipic
*/
if (!function_exists('is_georgian')) :
    function is_georgian()
    {
        return Transliteration_Utilities::get_locale('ka_GE');
    }
endif;

/*
 * Check is Belarusian language
 * @return        boolean
 * @author        Ivijan-Stefan Stipic
*/
if (!function_exists('is_belarusian')) :
    function is_belarusian()
    {
        return Transliteration_Utilities::get_locale('bel');
    }
endif;

/*
 * Check is Bulgarian language
 * @return        boolean
 * @author        Ivijan-Stefan Stipic
*/
if (!function_exists('is_bulgarian')) :
    function is_bulgarian()
    {
        return Transliteration_Utilities::get_locale('bg_BG');
    }
endif;

/*
 * Check is Macedonian language
 * @return        boolean
 * @author        Ivijan-Stefan Stipic
*/
if (!function_exists('is_macedonian')) :
    function is_macedonian()
    {
        return Transliteration_Utilities::get_locale('mk_MK');
    }
endif;

/*
 * Check is Kazakh language
 * @return        boolean
 * @author        Ivijan-Stefan Stipic
*/
if (!function_exists('is_kazakh')) :
    function is_kazakh()
    {
        return Transliteration_Utilities::get_locale('kk');
    }
endif;

/*
 * Greece (Elini'ka) transliteration
 * @return        boolean
 * @author        Ivijan-Stefan Stipic
*/
if (!function_exists('is_greece')) :
    function is_greece()
    {
        return Transliteration_Utilities::get_locale('el');
    }
endif;

/*
 * Arabic transliteration
 * @return        boolean
 * @author        Ivijan-Stefan Stipic
*/
if (!function_exists('is_arabic')) :
    function is_arabic()
    {
        return Transliteration_Utilities::get_locale('ar');
    }
endif;

/*
 * Armenian transliteration
 * @return        boolean
 * @author        Ivijan-Stefan Stipic
*/
if (!function_exists('is_armenian')) :
    function is_armenian()
    {
        return Transliteration_Utilities::get_locale('hy');
    }
endif;

/*
 * Mongolian transliteration
 * @return        boolean
 * @author        Ivijan-Stefan Stipic
*/
if (!function_exists('is_mongolian')) :
    function is_mongolian()
    {
        return Transliteration_Utilities::get_locale('mn');
    }
endif;

/*
 * Bashkir transliteration
 * @return        boolean
 * @author        Ivijan-Stefan Stipic
*/
if (!function_exists('is_bashkir')) :
    function is_bashkir()
    {
        return Transliteration_Utilities::get_locale('ba');
    }
endif;

/*
 * Uzbek transliteration
 * @return        boolean
 * @author        Ivijan-Stefan Stipic
*/
if (!function_exists('is_uzbek')) :
    function is_uzbek()
    {
        return Transliteration_Utilities::get_locale('uz_UZ');
    }
endif;

/*
 * Kyrgyz transliteration
 * @return        boolean
 * @author        Ivijan-Stefan Stipic
*/
if (!function_exists('is_kyrgyz')) :
    function is_kyrgyz()
    {
        return Transliteration_Utilities::get_locale('kir');
    }
endif;

/*
 * Tajik transliteration
 * @return        boolean
 * @author        Ivijan-Stefan Stipic
*/
if (!function_exists('is_tajik')) :
    function is_tajik()
    {
        return Transliteration_Utilities::get_locale('tg');
    }
endif;

/*
 * Elini'ka is natural Greece language, alias of function is_greece()
 * @return        boolean
 * @author        Ivijan-Stefan Stipic
*/
if (!function_exists('is_elinika')) :
    function is_elinika()
    {
        return is_greece();
    }
endif;

/*
 * Transliterate content
 * @return        string
 * @author        Ivijan-Stefan Stipic
*/
if (!function_exists('transliterate')) :
    function transliterate($content, string $type = 'cyr_to_lat', bool $fix_html = true)
    {
        return Transliteration_Controller::get()->transliterate($content, $type, $fix_html);
    }
endif;

/*
 * Translate from Cyrillic to Latin
 * @return        string
 * @author        Ivijan-Stefan Stipic
*/
if (!function_exists('cyr_to_lat')) :
    function cyr_to_lat($content, bool $sanitize_html = true)
    {
        return Transliteration_Controller::get()->cyr_to_lat($content, $sanitize_html);
    }
endif;

/*
 * Translate from Latin to Cyrillic
 * @return        string
 * @author        Ivijan-Stefan Stipic
*/
if (!function_exists('lat_to_cyr')) :
    function lat_to_cyr($content, bool $sanitize_html = true, bool $fix_diacritics = true)
    {
        return Transliteration_Controller::get()->lat_to_cyr($content, $sanitize_html, $fix_diacritics);
    }
endif;

/*
 * Translate from Latin to Cyrillic in Sanitize way
 * @return        string
 * @author        Ivijan-Stefan Stipic
*/
if (!function_exists('cyr_to_ascii_lat')) :
    function cyr_to_ascii_lat($content)
    {
        return Transliteration_Controller::get()->cyr_to_lat_sanitize($content);
    }
endif;

if (!function_exists('cyr_to_simplified_lat')) : function cyr_to_simplified_lat($content)
{
    return cyr_to_ascii_lat($content);
} endif;

/*
 * Script selector
 * @return        HTML string/echo/object/array
 * @author        Ivijan-Stefan Stipic
*/
if (!function_exists('script_selector')) :
    function script_selector($args)
    {
        $ID   = uniqid('script_selector_');
        $args = (object) wp_parse_args($args, [
            'id'           => $ID,
            'display_type' => 'inline',
            'echo'         => false,
            'separator'    => ' | ',
            'cyr_caption'  => __('Cyrillic', 'serbian-transliteration'),
			'lat_caption'  => __('Latin', 'serbian-transliteration'),
        ]);
		
		/*
		 * Ensure captions are always rendered in correct scripts without using inline tags,
		 * to avoid conflicts with menu/title filters from other plugins.
		 */
		if (function_exists('lat_to_cyr')) {
			$args->cyr_caption = lat_to_cyr((string) $args->cyr_caption, false, false);
		}
		if (function_exists('cyr_to_lat')) {
			$args->lat_caption = cyr_to_lat((string) $args->lat_caption, false);
		}

        $options = (object) [
            'active' => function_exists('rstr_get_script') ? rstr_get_script() : get_script(),
            'cyr'    => add_query_arg(get_rstr_option('url-selector', 'rstr'), 'cyr'),
            'lat'    => add_query_arg(get_rstr_option('url-selector', 'rstr'), 'lat'),
        ];

        $activeClasses = [
            'cyr' => in_array($options->active, ['lat_to_cyr', 'cyr']) ? ' active' : ' inactive',
            'lat' => in_array($options->active, ['cyr_to_lat', 'lat']) ? ' active' : ' inactive',
        ];

        $return           = '';
        $templateHandlers = get_script_selector_template_handlers($options, $args, $ID, $activeClasses);

        if (isset($templateHandlers[$args->display_type])) {
            $return = call_user_func($templateHandlers[$args->display_type]);
        } else {
            $return = sprintf(__('Choose one of the display types: "%1$s", "%2$s", "%3$s", "%4$s", "%5$s" or "%6$s"', 'serbian-transliteration'), 'inline', 'select', 'list', 'list_items', 'array', 'object');
        }

        if ($args->echo) {
            echo $return;
        } else {
            return $return;
        }
        return;
    }

function get_script_selector_template_handlers($options, $args, $ID, $activeClasses): array
{
    return [
        'inline' => fn (): string => sprintf(
            '<a href="%1$s" class="rstr-script-selector%5$s">%2$s</a>%3$s<a href="%4$s" class="rstr-script-selector%6$s">%7$s</a>',
            esc_url($options->lat),
            esc_html($args->lat_caption),
            esc_html($args->separator),
            esc_url($options->cyr),
            $activeClasses['lat'],
            $activeClasses['cyr'],
            esc_html($args->cyr_caption)
        ),
        'select' => fn (): string => sprintf(
            '<script type="text/javascript">/*<![CDATA[*/function rstr_%1$s(redirect) {document.location.href = redirect.value;}/*]]>*/</script><select class="rstr-script-selector" onchange="rstr_%1$s(this);" id="rstr_%1$s"><option value="%2$s"%6$s>%3$s</option><option value="%4$s"%7$s>%5$s</option></select>',
            $ID,
            esc_url($options->lat),
            esc_html($args->lat_caption),
            esc_url($options->cyr),
            esc_html($args->cyr_caption),
            $activeClasses['lat'],
            $activeClasses['cyr']
        ),
        'list' => fn (): string => sprintf(
            '<ul class="rstr-script-selector" id="rstr_%1$s"><li class="rstr-script-selector-item%5$s"><a href="%2$s" class="rstr-script-selector-item-link%5$s">%3$s</a></li><li class="rstr-script-selector-item%6$s"><a href="%4$s" class="rstr-script-selector-item-link%6$s">%7$s</a></li></ul>',
            $ID,
            esc_url($options->lat),
            esc_html($args->lat_caption),
            esc_url($options->cyr),
            esc_html($args->cyr_caption),
            $activeClasses['lat'],
            $activeClasses['cyr']
        ),
        'list_items' => fn (): string => sprintf(
            '<li class="rstr-script-selector-item%5$s"><a href="%1$s" class="rstr-script-selector-item-link%5$s">%2$s</a></li><li class="rstr-script-selector-item%6$s"><a href="%3$s" class="rstr-script-selector-item-link%6$s">%4$s</a></li>',
            esc_url($options->lat),
            esc_html($args->lat_caption),
            esc_url($options->cyr),
            esc_html($args->cyr_caption),
            $activeClasses['lat'],
            $activeClasses['cyr']
        ),
        'array' => fn (): array => [
            'cyr' => [
                'caption' => $args->cyr_caption,
                'url'     => $options->cyr,
            ],
            'lat' => [
                'caption' => $args->lat_caption,
                'url'     => $options->lat,
            ],
        ],
        'object' => fn () => (object) [
            'cyr' => (object) [
                'caption' => $args->cyr_caption,
                'url'     => $options->cyr,
            ],
            'lat' => (object) [
                'caption' => $args->lat_caption,
                'url'     => $options->lat,
            ],
        ],
        // Add other display types here if needed.
    ];
}
endif;
