<?php

if (!defined('WPINC')) {
    die();
}

final class Transliteration_Controller extends Transliteration
{
    use Transliteration__Cache;

    /*
     * The main constructor
     */
    public function __construct($actions = true)
    {
        if ($actions) {
        //    $this->add_action('init', 'transliteration_tags_start', 1);
        //    $this->add_action('shutdown', 'transliteration_tags_end', PHP_INT_MAX - 100);
        }
    }
	
	/**
	 * Initialize a late-stage output buffer to apply inline tag-based transliteration
	 * after the main content has been rendered.
	 *
	 * This allows local tags like {cyr_to_lat} or {lat_to_cyr} to override global settings.
	 */
	public function init_output_buffer(): void
	{
		// Register a shutdown action to flush the buffer at the end of the request
		add_action('shutdown', [$this, 'end_output_buffer'], PHP_INT_MAX);

		// Start output buffering with a custom callback that handles tag-based transliteration
		$this->ob_start([$this, 'controller_output_buffer']);
	}

	/**
	 * Output buffer callback that processes {cyr_to_lat}, {lat_to_cyr}, and {rstr_skip} tags.
	 *
	 * @param string $buffer The full HTML content of the page
	 * @return string Transliterated content with tag logic applied
	 */
	public function controller_output_buffer($buffer)
	{
		return $this->transliteration_tags_callback($buffer);
	}

	/**
	 * Ensures that output buffer is flushed and sent to the browser at the very end.
	 */
	public function end_output_buffer(): void
	{
		if (ob_get_level() > 0) {
			@ob_end_flush();
		}
	}

    /*
     * Get current instance
     */
    public static function get()
    {
        return self::cached_static('instance', fn (): \Transliteration_Controller => new self(false));
    }

    /*
     * Transliteration mode
     */
    public function mode($no_redirect = true)
    {
        return self::cached_static('mode', function () use ($no_redirect) {

            $mode = null;

            if (Transliteration_Utilities::is_sitemap()) {
                return $mode;
            }
			
			if (!Transliteration_Utilities::is_admin() && !Transliteration_Utilities::is_cyrillic_locale()) {
				return null;
			}

            if (Transliteration_Utilities::is_admin()) {
                return 'cyr_to_lat';
            }

            // Settings mode
            $mode = get_rstr_option('transliteration-mode', 'cyr_to_lat');

            // Cookie mode
            if (!empty($_COOKIE['rstr_script'] ?? null)) {
                switch (sanitize_text_field($_COOKIE['rstr_script'])) {
                    case 'lat':
                        $mode = 'cyr_to_lat';
                        break;

                    case 'cyr':
                        $mode = 'lat_to_cyr';
                        break;
                }
            }

            // First visit mode
            else {
                $url      = (Transliteration_Utilities::parse_url())['url'] ?? null;
                $redirect = false;

                $has_redirected = get_transient('transliteration_redirect');

                switch (get_rstr_option('first-visit-mode', 'lat')) {
                    case 'lat':
                        $mode = 'cyr_to_lat';
                        Transliteration_Utilities::setcookie('lat');
                        $redirect = true;
                        break;

                    case 'cyr':
                        $mode = 'lat_to_cyr';
                        Transliteration_Utilities::setcookie('cyr');
                        $redirect = true;
                        break;
                }

                if (!$has_redirected && !$no_redirect && $redirect && $url && !is_admin() && !headers_sent() && function_exists('wp_safe_redirect')) {
                    set_transient('transliteration_redirect', true, 30);
                    if ($url && wp_safe_redirect($url, 302)) {
                        exit;
                    }
                }
            }

            return $mode;

        }, $no_redirect);
    }

    /**
     * Exclude words or sentences for Latin
     * @return array
     * @autor        Ivijan-Stefan Stipic
     */
    public function lat_exclude_list()
    {
        return self::cached_static('lat_exclude_list', function () {
            if (Transliteration_Utilities::is_admin()) {
                return [];
            }

            return apply_filters('rstr/init/exclude/lat', []);
        });
    }

    /**
     * Exclude words or sentences for Cyrillic
     * @return array
     * @autor        Ivijan-Stefan Stipic
     */
    public function cyr_exclude_list()
    {
        return self::cached_static('cyr_exclude_list', function () {
            if (Transliteration_Utilities::is_admin()) {
                return [];
            }

            return apply_filters('rstr/init/exclude/cyr', []);
        });
    }

    /*
     * Disable Transliteration for the same script
     */
    public function disable_transliteration()
	{
		return self::cached_static('disable_transliteration', function () {
			$current_script       = get_rstr_option('site-script', 'cyr');
			$transliteration_mode = get_rstr_option('transliteration-mode', 'cyr_to_lat');
			$current_mode         = sanitize_text_field($_COOKIE['rstr_script'] ?? '');

			return (
				($current_script === 'cyr' && $transliteration_mode === 'cyr_to_lat' && $current_mode === 'cyr') ||
				($current_script === 'lat' && $transliteration_mode === 'lat_to_cyr' && $current_mode === 'lat')
			);
		});
	}

    /*
     * Transliteration
     */
    public function transliterate($content, $mode = 'auto', $sanitize_html = true)
    {

        if ($this->disable_transliteration() && !Transliteration_Utilities::is_admin()) {
            return $content;
        }

        if (null === $mode || false === $mode) {
            return $content;
        }

        if ($mode == 'auto') {
            $mode = $this->mode();
        }

        if (!$mode) {
            return $content;
        }

        if (method_exists($this, $mode)) {
            return $this->$mode($content);
        }

        return $content;
    }

    /*
     * Transliteration with no HTML
     */
    public function transliterate_no_html($content, $mode = 'auto')
    {
        if ($this->disable_transliteration() && Transliteration_Utilities::is_admin()) {
            return $content;
        }

        return $this->transliterate($content, $mode, false);
    }

    /*
     * Transliteration of attributes
     */
    public function transliterate_attributes(array $attr, array $keys, $mode = 'auto'): array
    {

        foreach ($keys as $key) {
            $key = sanitize_key($key);
            if (isset($attr[$key])) {
                $attr[$key] = esc_attr($this->transliterate($attr[$key], $mode));
            }
        }

        return $attr;
    }

    /*
     * Cyrillic to Latin
     */
    public function cyr_to_lat($content, bool $sanitize_html = true, bool $force = false)
    {
        $class_map = Transliteration_Map::get()->map();

        // If the content should not be transliterated or the user is an editor, return the original content
        if (!$force) {
			if (!$class_map || Transliteration_Utilities::can_transliterate($content) || Transliteration_Utilities::is_editor()) {
				return $content;
			}
		}

        /*// Don't transliterate if we already have transliteration
        if (!Transliteration_Utilities::is_lat($content)) {
            return $content;
        }*/

        // Retrieve the list of Cyrillic words to exclude from transliteration
        $exclude_list         = $this->cyr_exclude_list();
        $exclude_placeholders = [];

        // Check if the exclusion list is not empty
        if (!empty($exclude_list)) {
            foreach ($exclude_list as $key => $word) {
                $placeholder                        = self::make_placeholder(0, $key);
                $content                            = str_replace($word, $placeholder, $content);
                $exclude_placeholders[$placeholder] = $word;
            }
        }

        // Extract <script> contents and replace them with placeholders
        $script_placeholders = [];
        $content             = preg_replace_callback('/<script\b[^>]*>(.*?)<\/script>/is', function ($matches) use (&$script_placeholders): string {
            $placeholder                       = self::make_placeholder(1, count($script_placeholders));
            $script_placeholders[$placeholder] = $matches[0];
            return $placeholder;
        }, $content);

        // Extract <style> contents and replace them with placeholders
        $style_placeholders = [];
        $content            = preg_replace_callback('/<style\b[^>]*>(.*?)<\/style>/is', function ($matches) use (&$style_placeholders): string {
            $placeholder                      = self::make_placeholder(2, count($style_placeholders));
            $style_placeholders[$placeholder] = $matches[0];
            return $placeholder;
        }, $content);

        // Extract <head> contents and replace them with placeholders
        $head_placeholders = [];
        $content           = preg_replace_callback('/<head\b[^>]*>(.*?)<\/head>/is', function ($matches) use (&$head_placeholders): string {
            $placeholder                     = self::make_placeholder(3, count($head_placeholders));
            $head_placeholders[$placeholder] = $matches[0];
            return $placeholder;
        }, $content);

        // Handle percentage format specifiers by replacing them with placeholders
        $formatSpecifiers = [];
        $content          = preg_replace_callback('/(\b\d+(?:\.\d+)?&#37;)/', function ($matches) use (&$formatSpecifiers): string {
            $placeholder                    = self::make_placeholder(4, count($formatSpecifiers));
            $formatSpecifiers[$placeholder] = $matches[0];
            return $placeholder;
        }, $content);

        // Perform the transliteration using the class map
        if ($class_map && class_exists($class_map)) {
            $content = $class_map::transliterate($content, 'cyr_to_lat');
            $content = Transliteration_Sanitization::get()->lat($content, $sanitize_html);
        }

        // Restore <head> contents back to their original form
        if ($head_placeholders !== []) {
            $content = strtr($content, $head_placeholders);
            unset($head_placeholders);
        }

        // Restore percentage format specifiers back to their original form
        if ($formatSpecifiers !== []) {
            $content = strtr($content, $formatSpecifiers);
            unset($formatSpecifiers);
        }

        // Sanitize HTML attributes and transliterate their values
        if ($sanitize_html) {
            $html_attributes_match = $this->private__html_atributes('cyr_to_lat');
            if ($html_attributes_match) {
                $content = preg_replace_callback('/\b(' . $html_attributes_match . ')=("|\')(.*?)\2/i', function ($matches) use ($class_map, $sanitize_html): string {
                    $transliteratedValue = $class_map::transliterate($matches[3], 'cyr_to_lat');
                    $transliteratedValue = Transliteration_Sanitization::get()->lat($transliteratedValue, $sanitize_html);
                    return $matches[1] . '=' . $matches[2] . esc_attr($transliteratedValue) . $matches[2];
                }, $content);
            }
        }

        // Restore excluded words back to their original form
        if ($exclude_placeholders !== []) {
            $content = strtr($content, $exclude_placeholders);
            unset($exclude_placeholders);
        }

        // Restore <script> contents back to their original form
        if ($script_placeholders !== []) {
            $content = strtr($content, $script_placeholders);
            unset($script_placeholders);
        }

        // Restore <style> contents back to their original form
        if ($style_placeholders !== []) {
            $content = strtr($content, $style_placeholders);
            unset($style_placeholders);
        }

        return $content;
    }

    /*
     * Translate from cyr to lat
     * @return        string
     * @author        Ivijan-Stefan Stipic
     */
    public function cyr_to_lat_sanitize($content, $force = false)
    {

        if ($force === false && (Transliteration_Utilities::can_transliterate($content) || Transliteration_Utilities::is_editor())) {
            return $content;
        }

        // Decode content if necessary
        $content = Transliteration_Utilities::decode($content);

        // Transliterate from Cyrillic to Latin
        $content = $this->cyr_to_lat($content, false, $force);

        // Normalize the string
        $content = Transliteration_Utilities::normalize_latin_string($content);

        // Perform sanitization based on available functions
        if (function_exists('mb_convert_encoding')) {
            // Use mb_convert_encoding if available
            $content = mb_convert_encoding($content, 'ASCII', 'UTF-8');
        } elseif (function_exists('iconv')) {
            // Fallback to iconv if mb_convert_encoding is not available
            $locale = Transliteration_Utilities::get_locales(Transliteration_Utilities::get_locale());
            if ($locale && preg_match('/^[a-zA-Z]{2}(_[a-zA-Z]{2})?$/', $locale)) {
                // Save the current locale to restore it later
                $current_locale = setlocale(LC_CTYPE, 0);

                // Set the new locale
                setlocale(LC_CTYPE, $locale);

                // Perform the conversion
                $converted = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $content);

                // Restore the original locale
                setlocale(LC_CTYPE, $current_locale);

                if ($converted) {
                    $content = str_replace(['"', "'", '`', '^', '~'], '', $converted);
                }
            }
        }

        return $content;
    }

    /*
     * Latin to Cyrillic
     */
    public function lat_to_cyr($content, bool $sanitize_html = true, bool $fix_diacritics = false)
    {
        $class_map = Transliteration_Map::get()->map();

        // If the content should not be transliterated or the user is an editor, return the original content
        if (!$class_map || Transliteration_Utilities::can_transliterate($content) || Transliteration_Utilities::is_editor()) {
            return $content;
        }

        /*// Don't transliterate if we already have transliteration
        if (!Transliteration_Utilities::is_cyr($content)) {
            return $content;
        }*/

        // Retrieve the list of Latin words to exclude from transliteration
        $exclude_list         = $this->lat_exclude_list();
        $exclude_placeholders = [];
        if (!empty($exclude_list)) {
            foreach ($exclude_list as $key => $word) {
                $placeholder                        = self::make_placeholder(0, $key);
                $content                            = str_replace($word, $placeholder, $content);
                $exclude_placeholders[$placeholder] = $word;
            }
        }

        // Extract shortcode contents and replace them with placeholders
        $shortcode_placeholders = [];
        $content                = preg_replace_callback('/\[([\w+_-]+)\s?([^\]]*)\](.*?)\[\/\1\]/is', function ($matches) use (&$shortcode_placeholders): string {
            $placeholder                          = self::make_placeholder(1, count($shortcode_placeholders));
            $shortcode_placeholders[$placeholder] = $matches[0];
            return $placeholder;
        }, $content);

        // Extract self-closing shortcode contents and replace them with placeholders
        $self_closing_shortcode_placeholders = [];
        $content                             = preg_replace_callback('/\[(\w+)([^\]]*)\]/is', function ($matches) use (&$self_closing_shortcode_placeholders): string {
            $placeholder                                       = self::make_placeholder(2, count($self_closing_shortcode_placeholders));
            $self_closing_shortcode_placeholders[$placeholder] = $matches[0];
            return $placeholder;
        }, $content);

        // Extract <script> contents and replace them with placeholders
        $script_placeholders = [];
        $content             = preg_replace_callback('/<script\b[^>]*>(.*?)<\/script>/is', function ($matches) use (&$script_placeholders): string {
            $placeholder                       = self::make_placeholder(3, count($script_placeholders));
            $script_placeholders[$placeholder] = $matches[0];
            return $placeholder;
        }, $content);

        // Extract <style> contents and replace them with placeholders
        $style_placeholders = [];
        $content            = preg_replace_callback('/<style\b[^>]*>(.*?)<\/style>/is', function ($matches) use (&$style_placeholders): string {
            $placeholder                      = self::make_placeholder(4, count($style_placeholders));
            $style_placeholders[$placeholder] = $matches[0];
            return $placeholder;
        }, $content);

        // Extract <head> contents and replace them with placeholders
        $head_placeholders = [];
        $content           = preg_replace_callback('/<head\b[^>]*>(.*?)<\/head>/is', function ($matches) use (&$head_placeholders): string {
            $placeholder                     = self::make_placeholder(5, count($head_placeholders));
            $head_placeholders[$placeholder] = $matches[0];
            return $placeholder;
        }, $content);

        // Extract special shortcode contents and replace them with placeholders
        $special_shortcodes = [];
        $content            = preg_replace_callback('/\{\{([\w+_-]+)\s?([^\}]*)\}\}(.*?)\{\{\/\1\}\}/is', function ($matches) use (&$special_shortcodes): string {
            $placeholder                      = self::make_placeholder(6, count($special_shortcodes));
            $special_shortcodes[$placeholder] = $matches[0];
            return $placeholder;
        }, $content);

        $content = preg_replace_callback('/\{([\w+_-]+)\s?([^\}]*)\}(.*?)\{\/\1\}/is', function ($matches) use (&$special_shortcodes): string {
            $placeholder                      = self::make_placeholder(7, count($special_shortcodes));
            $special_shortcodes[$placeholder] = $matches[0];
            return $placeholder;
        }, $content);

        // Handle various format specifiers and other patterns by replacing them with placeholders
        $formatSpecifiers = [];
        $regex            = (
            $sanitize_html
            ? '/(\b\d+(?:\.\d+)?&#37;|%\d*\$?[ds]|<[^>]+>|&[^;]+;|https?:\/\/[^\s]+|[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}|rstr_skip|cyr_to_lat|lat_to_cyr)/'
            : '/(\b\d+(?:\.\d+)?&#37;|%\d*\$?[ds]|&[^;]+;|https?:\/\/[^\s]+|[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}|rstr_skip|cyr_to_lat|lat_to_cyr)/'
        );
        $content = preg_replace_callback($regex, function ($matches) use (&$formatSpecifiers): string {
            $placeholder                    = '@@::8-' . count($formatSpecifiers) . '@@';
            $formatSpecifiers[$placeholder] = $matches[0];
            return $placeholder;
        }, $content);

        // Perform the transliteration using the class map
        $content = $class_map::transliterate($content, 'lat_to_cyr');
        $content = Transliteration_Sanitization::get()->cyr($content, $sanitize_html);

        // Restore format specifiers back to their original form
        if ($formatSpecifiers !== []) {
            $content = strtr($content, $formatSpecifiers);
            unset($formatSpecifiers);
        }

        // Fix the diacritics
        if ($fix_diacritics) {
            $content = self::fix_diacritics($content);
        }

        // Restore self-closing shortcode contents back to their original form
        if ($self_closing_shortcode_placeholders !== []) {
            $content = strtr($content, $self_closing_shortcode_placeholders);
            unset($self_closing_shortcode_placeholders);
        }

        // Restore special shortcode contents back to their original form
        if ($special_shortcodes !== []) {
            $content = strtr($content, $special_shortcodes);
            unset($special_shortcodes);
        }

        // Restore shortcode contents back to their original form
        if ($shortcode_placeholders !== []) {
            $content = strtr($content, $shortcode_placeholders);
            unset($shortcode_placeholders);
        }

        // Sanitize HTML attributes and transliterate their values
        if ($sanitize_html) {
            $html_attributes_match = $this->private__html_atributes('lat_to_cyr');
            if ($html_attributes_match) {
                $content = preg_replace_callback('/\b(' . $html_attributes_match . ')=("|\')(.*?)\2/i', function ($matches) use ($class_map, $sanitize_html, $fix_diacritics): string {
                    $transliteratedValue = $class_map::transliterate($matches[3], 'lat_to_cyr');
                    $transliteratedValue = Transliteration_Sanitization::get()->cyr($transliteratedValue, $sanitize_html);
                    if ($fix_diacritics) {
                        $transliteratedValue = self::fix_diacritics($transliteratedValue);
                    }

                    return $matches[1] . '=' . $matches[2] . esc_attr($transliteratedValue) . $matches[2];
                }, $content);
            }
        }

        // Restore excluded words back to their original form
        if ($exclude_placeholders !== []) {
            $content = strtr($content, $exclude_placeholders);
            unset($exclude_placeholders);
        }

        // Restore <script> contents back to their original form
        if ($script_placeholders !== []) {
            $content = strtr($content, $script_placeholders);
            unset($script_placeholders);
        }

        // Restore <style> contents back to their original form
        if ($style_placeholders !== []) {
            $content = strtr($content, $style_placeholders);
            unset($style_placeholders);
        }

        // Restore <head> contents back to their original form
        if ($head_placeholders !== []) {
            $content = strtr($content, $head_placeholders);
            unset($head_placeholders);
        }

        return $content;
    }

    /*
     * Transliteration tags buffer start
     */
    public function transliteration_tags_start(): void
    {
        $this->ob_start('transliteration_tags_callback');
    }

    /*
     * Transliteration tags buffer callback
     */
    public function transliteration_tags_callback($buffer)
	{
		if (!Transliteration_Utilities::is_cyrillic_locale()) {
			return $buffer;
		}
	
		if (Transliteration_Utilities::can_transliterate($buffer) || Transliteration_Utilities::is_editor()) {
			return $buffer;
		}

		if (get_rstr_option('transliteration-mode', 'cyr_to_lat') === 'none') {
			return str_replace(
				['{cyr_to_lat}', '{lat_to_cyr}', '{rstr_skip}', '{/cyr_to_lat}', '{/lat_to_cyr}', '{/rstr_skip}'],
				'',
				$buffer
			);
		}

		$tags = ['cyr_to_lat', 'lat_to_cyr', 'rstr_skip'];
		foreach ($tags as $tag) {
			// Match only simple tag pairs, no recursion
			preg_match_all('/\{' . $tag . '\}(.*?)\{\/' . $tag . '\}/s', $buffer, $matches, PREG_SET_ORDER);

			if (!empty($matches)) {
				foreach ($matches as $entry) {
					$original_text = $entry[1];

					if ($tag === 'rstr_skip') {
						$mode = ($this->mode() === 'cyr_to_lat') ? 'lat_to_cyr' : 'cyr_to_lat';
						switch ($tag) {
							case 'cyr_to_lat':
								$transliterated_text = $this->cyr_to_lat($original_text, true, true);
								break;

							case 'lat_to_cyr':
								$transliterated_text = $this->lat_to_cyr($original_text, true);
								break;
						}
					} else {
						switch ($tag) {
							case 'cyr_to_lat':
								$transliterated_text = $this->cyr_to_lat($original_text, true, true);
								break;

							case 'lat_to_cyr':
								$transliterated_text = $this->lat_to_cyr($original_text, true);
								break;

							default:
								$transliterated_text = $original_text;
								break;
						}
					}

					$buffer = str_replace($entry[0], $transliterated_text, $buffer);
				}
			}
		}

		return $buffer;
	}

    /*
     * Transliteration tags buffer end
     */
    public function transliteration_tags_end(): void
    {
        if (ob_get_level() > 0) {
            ob_end_flush();
        }
    }

    /*
     * Fix the diactritics
     */
    public static function fix_diacritics($content)
    {
        if (Transliteration_Utilities::can_transliterate($content) || Transliteration_Utilities::is_editor() || !in_array(Transliteration_Utilities::get_locale(), ['sr_RS', 'bs_BA', 'cnr'])) {
            return $content;
        }

        $search = Transliteration_Utilities::get_diacritical();
        if (!$search) {
            return $content;
        }

        $new_string = strtr($content, [
            'dj' => 'đ', 'Dj' => 'Đ', 'DJ' => 'Đ',
            'sh' => 'š', 'Sh' => 'Š', 'SH' => 'Š',
            'ch' => 'č', 'Ch' => 'Č', 'CH' => 'Č',
            'cs' => 'ć', 'Cs' => 'Ć', 'CS' => 'Ć',
            'dz' => 'dž', 'Dz' => 'Dž', 'DZ' => 'DŽ',
        ]);

        $skip_words = array_map('strtolower', Transliteration_Utilities::get_skip_words());
        $search     = array_map('strtolower', $search);

        $arr        = Transliteration_Utilities::explode(' ', $new_string);
        $arr_origin = Transliteration_Utilities::explode(' ', $content);

        if (count($arr) !== count($arr_origin)) {
            return $content;
        }

        $result = '';
        foreach ($arr as $i => $word) {
            $word_origin        = $arr_origin[$i];
            $word_search        = strtolower(preg_replace('/[.,?!-*_#$]+/i', '', $word));
            $word_search_origin = strtolower(preg_replace('/[.,?!-*_#$]+/i', '', $word_origin));

            if (in_array($word_search_origin, $skip_words)) {
                $result .= $word_origin . ' ';
                continue;
            }

            if (in_array($word_search, $search)) {
                $result .= self::apply_case($word, $search, $word_search);
            } else {
                $result .= $word;
            }

            $result .= ' ';
        }

        $result = trim($result);

        return $result ?: $content;
    }

    /*
     * Transliterate HTML
     */
    public function transliterate_html($html)
    {		
        if (!class_exists('DOMDocument', false) || empty($html) || !is_string($html) || is_numeric($html)) {
            return $html;
        }

        $dom = new DOMDocument('1.0', 'UTF-8');

        libxml_use_internal_errors(true);
        $html = '<?xml encoding="UTF-8">' . $html; // UTF-8 deklaracija OBAVEZNA!
		
		// $html = htmlspecialchars($html, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
		
        $dom->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
		$dom->encoding = 'UTF-8';
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);

        // Izbegavamo tagove gde ne želimo transliteraciju
        $skipTags = apply_filters('transliteration_html_avoid_tags', [
            'script',
            'style',
            'textarea',
            'input',
            'select',
            'code',
            'pre',
            'img',
            'svg',
            'image',
            'head',
            'meta',
        ], 'dom');

        // Atributi na koje se primenjuje transliteracija
        $attributesToTransliterate = $this->private__html_atributes('dom', true);

        // Transliteracija teksta unutar tagova, osim onih koji su na listi za izbegavanje
        foreach ($xpath->query('//text()') as $textNode) {
            if (!in_array($textNode->parentNode->nodeName, $skipTags)) {
				// Normalize hidden characters
				$textNode->nodeValue = preg_replace('/^[\x{FEFF}\x{200B}\x{00A0}]+/u', '', $textNode->nodeValue);

				// Apply transliteration
				$textNode->nodeValue = $this->transliterate($textNode->nodeValue);
            }
        }

        // Transliteracija određenih atributa
        foreach ($xpath->query('//*[@' . implode(' or @', $attributesToTransliterate) . ']') as $node) {
            foreach ($attributesToTransliterate as $attr) {
                if ($node->hasAttribute($attr)) {
                    $node->setAttribute($attr, $this->transliterate($node->getAttribute($attr)));
                }
            }
        }

        // Vraćamo HTML sa pravilnim enkodingom
        return $dom->saveHTML();
    }

    /*
     * PRIVATE: Allowed HTML attributes for transliteration
     */
    private function private__html_atributes(string $type = 'inherit', bool $return_array = false)
    {
        return self::cached_static('private__html_atributes', function () use ($type, $return_array) {
            $html_attributes_match = [
                'title',
                'data-title',
                'alt',
                'placeholder',
                'data-placeholder',
                'aria-label',
                'data-label',
                'data-description',
                'data-text',
                'data-content',
                'data-tooltip',
                'data-success_message',
                'data-qm-component',
                'data-qm-subject',
            ];

            $html_attributes_match = apply_filters('transliteration_html_attributes', $html_attributes_match, $type);

            $html_attributes_match = is_array($html_attributes_match) ? array_map('trim', $html_attributes_match) : [];

            if ($return_array) {
                return $html_attributes_match;
            }

            return implode('|', $html_attributes_match);
        }, [$type, $return_array]);
    }

    /*
     * PRIVATE: Apply Case
     */
    private static function apply_case($word, array $search, $word_search)
    {
        if (ctype_upper($word) || preg_match('~^[A-ZŠĐČĆŽ]+$~u', $word)) {
            return strtoupper($search[array_search($word_search, $search, true)]);
        }
        if (preg_match('~^\p{Lu}~u', $word)) {
            $ucfirst = $search[array_search($word_search, $search, true)];
            return strtoupper(substr($ucfirst ?? '', 0, 1)) . substr($ucfirst ?? '', 1);
        }

        return $word;
    }
	
	/**
	 * Generate a placeholder token that:
	 * - does not contain letters (so it can't be transliterated)
	 * - does not contain [] or {} (so it can't be captured by shortcode/tag regex)
	 *
	 * @param int $group
	 * @param int $index
	 * @return string
	 */
	private static function make_placeholder(int $group, int $index): string
	{
		return '@@::' . $group . '-' . $index . '@@';
	}
}
