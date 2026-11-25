<?php

if (!defined('WPINC')) {
    die();
}

final class Transliteration_Controller extends Transliteration
{
    use Transliteration__Cache;
	
	/**
	 * Controller output buffer level.
	 *
	 * @var int|null
	 */
	private ?int $controller_buffer_level = null;
	private bool $restoring_keep_blocks = false;

    private const HTML_ATTRIBUTE = '([ \t\n\f\r]++[a-z_:][a-z0-9_.:-]*+)(?:([ \t\n\f\r]*+=[ \t\n\f\r]*+)("[^"]*+"|\'[^\']*+\'|[^ \t\n\f\r"\'=<>\x60]+))?';
    private const HTML_UNQUOTED_ESCAPE = [
        ' ' => '&#32;', "\t" => '&#9;', "\n" => '&#10;', "\f" => '&#12;', "\r" => '&#13;',
        '"' => '&quot;', "'" => '&#39;', '<' => '&lt;', '>' => '&gt;', '=' => '&#61;', "\x60" => '&#96;',
    ];

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
	
		if (is_admin() || Transliteration_Utilities::is_elementor_editor()) {
			return;
		}

		if ($this->controller_buffer_level !== null) {
			return;
		}

		add_action('shutdown', [$this, 'end_output_buffer'], PHP_INT_MAX);
		$neutralize_comment = function ($content) {
			return is_string($content) ? $this->neutralize_comment_markers($content) : $content;
		};
		add_filter('get_comment_text', $neutralize_comment, PHP_INT_MAX);
		add_filter('comment_text', $neutralize_comment, PHP_INT_MAX);

		$this->ob_start([$this, 'controller_output_buffer']);

		$this->controller_buffer_level = ob_get_level();
	}

	/**
	 * Output buffer callback that processes {cyr_to_lat}, {lat_to_cyr}, and {rstr_skip} tags.
	 *
	 * @param string $buffer The full HTML content of the page
	 * @return string Transliterated content with tag logic applied
	 */
	public function controller_output_buffer($buffer)
	{
		return is_admin() ? $buffer : $this->transliteration_tags_callback($buffer);
	}

	private function neutralize_comment_markers(string $content): string
	{
		// Phantom can decode references before the global marker pass. Match their
		// rendered spelling and insert a word joiner that survives serialization.
		$opening = '(?:\{|&#0*123(?:;|(?![0-9]))|&#x0*7b(?:;|(?![0-9a-f]))|&(?:lbrace|lcub);)';
		$characters = '(?:[a-z_/]|&#(?:x[0-9a-f]++|[0-9]++);?|&[a-z]+;)';
		$closing = '(?:\}|&#0*125(?:;|(?![0-9]))|&#x0*7d(?:;|(?![0-9a-f]))|&(?:rbrace|rcub);)';

		return preg_replace_callback(
			'~(' . $opening . ')(' . $characters . '{1,11})' . $closing . '~i',
			static function (array $matches): string {
				$name = preg_replace('/&#(x[0-9a-f]++|[0-9]++);?/i', '&#$1;', $matches[2]);
				$name = strtolower(html_entity_decode($name, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
				if (in_array(ltrim($name, '/'), ['cyr_to_lat', 'lat_to_cyr', 'rstr_skip', 'rstr_keep'], true)) {
					return $matches[1] . "\u{2060}" . substr($matches[0], strlen($matches[1]));
				}
				return $matches[0];
			},
			$content
		) ?? $content;
	}

	/**
	 * Ensures that output buffer is flushed and sent to the browser at the very end.
	 */
	public function end_output_buffer(): void
	{
		if ($this->controller_buffer_level === null) {
			return;
		}

		if (ob_get_level() < $this->controller_buffer_level) {
			$this->controller_buffer_level = null;
			return;
		}

		while (ob_get_level() >= $this->controller_buffer_level) {
			ob_end_flush();
		}

		$this->controller_buffer_level = null;
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
			
			if (!Transliteration_Utilities::is_admin() && !Transliteration_Utilities::is_transliteration_language()) {
				return null;
			}

            if (Transliteration_Utilities::is_admin()) {
                return 'cyr_to_lat';
            }

            // Settings mode
            $mode = get_rstr_option('transliteration-mode', 'cyr_to_lat');
			
			/**
			 * Known crawlers must not receive cookie-based redirects.
			 * They need stable HTML output for Open Graph, Twitter Cards, schema, canonical tags, etc.
			 */
			if (Transliteration_Utilities::is_known_crawler()) {
				return $mode;
			}

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
		if (Transliteration_Utilities::is_elementor_editor()) {
			return $content;
		}

		if ($this->disable_transliteration() && !Transliteration_Utilities::is_admin()) {
			return $content;
		}

		if (null === $mode || false === $mode) {
			return $content;
		}

		if ($mode === 'auto') {
			$mode = $this->mode();
		}

		if (!$mode) {
			return $content;
		}

		if (method_exists($this, $mode)) {
			return $this->{$mode}($content, (bool) $sanitize_html);
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

		if (!$force) {
			if (!$class_map || Transliteration_Utilities::can_transliterate($content) || Transliteration_Utilities::is_editor()) {
				return $content;
			}
		}

		$content = (string) $content;
		$html_placeholders = [];
		$url_attributes = [];
		if ($sanitize_html && $class_map && class_exists($class_map)) {
			$url_attributes = $this->protected_url_attribute_names();
			$protected = $this->protect_html_markup($content, $html_placeholders, $class_map, $url_attributes);
			if ($protected === null) {
				return $content;
			}
			$content = $protected;
		}
		
		$exclude_placeholders = [];
		$content = $this->protect_cyr_excluded_words($content, $exclude_placeholders);

		$head_placeholders = [];
		$content = preg_replace_callback('/<head\b[^>]*>.*?<\/head>/is', function ($matches) use (&$head_placeholders): string {
			$placeholder = self::make_placeholder(3, count($head_placeholders));
			$head_placeholders[$placeholder] = $matches[0];

			return $placeholder;
		}, $content);

		$script_placeholders = [];
		$content = preg_replace_callback('/<script\b[^>]*>.*?<\/script>/is', function ($matches) use (&$script_placeholders): string {
			$placeholder = self::make_placeholder(1, count($script_placeholders));
			$script_placeholders[$placeholder] = $matches[0];

			return $placeholder;
		}, $content);

		$style_placeholders = [];
		$content = preg_replace_callback('/<style\b[^>]*>.*?<\/style>/is', function ($matches) use (&$style_placeholders): string {
			$placeholder = self::make_placeholder(2, count($style_placeholders));
			$style_placeholders[$placeholder] = $matches[0];

			return $placeholder;
		}, $content);

		$url_attribute_placeholders = [];
		if ($sanitize_html) {
			$content = $this->protect_url_attributes($content, $url_attribute_placeholders, $url_attributes);
		}

		$format_specifiers = [];
		$content = preg_replace_callback('/(\b\d+(?:\.\d+)?&#37;)/', function ($matches) use (&$format_specifiers): string {
			$placeholder = self::make_placeholder(4, count($format_specifiers));
			$format_specifiers[$placeholder] = $matches[0];

			return $placeholder;
		}, $content);

		if ($class_map && class_exists($class_map)) {
			$content = $class_map::transliterate($content, 'cyr_to_lat');
			$content = Transliteration_Sanitization::get()->lat($content, $sanitize_html);
		}

		if ($format_specifiers !== []) {
			$content = strtr($content, $format_specifiers);
		}

		if ($exclude_placeholders !== []) {
			$content = strtr($content, $exclude_placeholders);
		}

		if ($url_attribute_placeholders !== []) {
			$content = strtr($content, $url_attribute_placeholders);
		}

		if ($script_placeholders !== []) {
			$content = strtr($content, $script_placeholders);
		}

		if ($style_placeholders !== []) {
			$content = strtr($content, $style_placeholders);
		}

		if ($head_placeholders !== []) {
			$content = strtr($content, $head_placeholders);
		}
		
		$content = $this->cleanup_placeholders($content);
		$content = strtr($content, $html_placeholders);

		return $sanitize_html && $class_map && class_exists($class_map)
			? $this->transliterate_html_attributes($content, 'cyr_to_lat', $class_map, $url_attributes, $exclude_placeholders, $html_placeholders)
			: $content;
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

        $html_placeholders = [];
        $url_attributes = [];
        if ($sanitize_html) {
            $url_attributes = $this->protected_url_attribute_names();
            $protected = $this->protect_html_markup($content, $html_placeholders);
            if ($protected === null) {
                return $content;
            }
            $content = $protected;
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
		
		// Extract <head> contents and replace them with placeholders
        $head_placeholders = [];
        $content           = preg_replace_callback('/<head\b[^>]*>(.*?)<\/head>/is', function ($matches) use (&$head_placeholders): string {
            $placeholder                     = self::make_placeholder(5, count($head_placeholders));
            $head_placeholders[$placeholder] = $matches[0];
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

        $url_attribute_placeholders = [];
        if ($sanitize_html) {
            $content = $this->protect_url_attributes($content, $url_attribute_placeholders, $url_attributes);
        }

        // Handle various format specifiers and other patterns by replacing them with placeholders
        $formatSpecifiers = [];
        $regex            = '/(\b\d+(?:\.\d+)?&#37;|%\d*\$?[ds]|&[^;]+;|https?:\/\/[^\s]+|[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}|rstr_skip|cyr_to_lat|lat_to_cyr)/';
        $content = preg_replace_callback($regex, function ($matches) use (&$formatSpecifiers): string {
            $placeholder                    = self::make_placeholder(8, count($formatSpecifiers));
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

        // Restore excluded words back to their original form
        if ($exclude_placeholders !== []) {
            $content = strtr($content, $exclude_placeholders);
        }

        if ($url_attribute_placeholders !== []) {
            $content = strtr($content, $url_attribute_placeholders);
            unset($url_attribute_placeholders);
        }
		
		// Restore <head> contents back to their original form
        if ($head_placeholders !== []) {
            $content = strtr($content, $head_placeholders);
            unset($head_placeholders);
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
		
		$content = $this->cleanup_placeholders($content);
		$content = strtr($content, $html_placeholders);

        return $sanitize_html
            ? $this->transliterate_html_attributes($content, 'lat_to_cyr', $class_map, $url_attributes, $exclude_placeholders, $html_placeholders, $fix_diacritics)
            : $content;
    }

    /*
     * Transliteration tags buffer start
     */
    public function transliteration_tags_start(): void
    {
        $this->ob_start('transliteration_tags_callback');
    }

    /**
	 * Output buffer callback for inline transliteration tags.
	 *
	 * @param mixed $buffer Buffered HTML output.
	 *
	 * @return mixed
	 */
	public function transliteration_tags_callback($buffer)
	{
		if (!is_string($buffer) || $buffer === '') {
			return $buffer;
		}

		if (!Transliteration_Utilities::is_transliteration_language()) {
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

		$callback = function (array $matches): string {
			$tag = $matches[1] ?? '';
			$content = $matches[2] ?? '';

			switch ($tag) {
				case 'cyr_to_lat':
					return $this->cyr_to_lat($content, true, true);

				case 'lat_to_cyr':
					return $this->lat_to_cyr($content, true);

				case 'rstr_skip':
					$keep_blocks = [];

					$content = preg_replace_callback(
						'~\{rstr_keep\}(.*?)\{/rstr_keep\}~is',
						static function (array $keep_matches) use (&$keep_blocks): string {
							// The token must be unguessable because it is restored into the full buffer, and letter-free so transliteration cannot modify it.
							$token = sprintf(
								'%%::%u::%u::%u::11::%d::%%',
								wp_rand(),
								wp_rand(),
								wp_rand(),
								count($keep_blocks)
							);

							$keep_blocks[$token] = $keep_matches[1] ?? '';

							return $token;
						},
						$content
					);

					$restoring_keep_blocks = $this->restoring_keep_blocks;
					$this->restoring_keep_blocks = $restoring_keep_blocks || $keep_blocks !== [];
					try {
						switch (get_rstr_option('transliteration-mode', 'cyr_to_lat')) {
							case 'cyr_to_lat':
								$content = $this->lat_to_cyr($content, true);
								break;

							case 'lat_to_cyr':
								$content = $this->cyr_to_lat($content, true, true);
								break;
						}
					} finally {
						$this->restoring_keep_blocks = $restoring_keep_blocks;
					}

					if ($keep_blocks !== []) {
						$content = strtr($content, $keep_blocks);
					}

					return $content;

				default:
					return $matches[0];
			}
		};

		return preg_replace_callback(
			'~\{(cyr_to_lat|lat_to_cyr|rstr_skip|rstr_keep)\}(.*?)\{/\1\}~is',
			$callback,
			$buffer
		);
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

    /**
	 * Transliterate full HTML output using DOM, while protecting fragile blocks
	 * and inline transliteration tags from DOMDocument rewriting.
	 *
	 * @param mixed $html Input HTML.
	 *
	 * @return mixed
	 */
	public function transliterate_html($html)
	{
		if (empty($html) || !is_string($html) || !class_exists('DOMDocument', false)) {
			return $html;
		}

		if (strlen($html) < 10) {
			return $html;
		}

		$placeholders = [];

		$tokenPrefix = '<!--~' . (string) time() . (string) random_int(100000, 999999) . '~';
		$tokenSuffix = '~-->';

		$protectPatterns = apply_filters('transliteration_dom_protect_blocks', [
			// Protect inline transliteration tags before global DOM transliteration.
			'~\{cyr_to_lat\}.*?\{/cyr_to_lat\}~is',
			'~\{lat_to_cyr\}.*?\{/lat_to_cyr\}~is',
			'~\{rstr_skip\}.*?\{/rstr_skip\}~is',

			// Protect fragile HTML blocks.
			'~<script\b[^>]*>.*?</script>~is',
			'~<style\b[^>]*>.*?</style>~is',
			'~<noscript\b[^>]*>.*?</noscript>~is',
			'~<svg\b[^>]*>.*?</svg>~is',
			'~<template\b[^>]*>.*?</template>~is',

			// Protect head-only tags.
			'~<link\b[^>]*>~is',
			'~<meta\b[^>]*>~is',
		]);

		foreach ($protectPatterns as $pattern) {
			if (!is_string($pattern) || $pattern === '') {
				continue;
			}

			$html = preg_replace_callback($pattern, static function ($matches) use (&$placeholders, $tokenPrefix, $tokenSuffix): string {
				$key = $tokenPrefix . (string) count($placeholders) . $tokenSuffix;
				$placeholders[$key] = $matches[0];

				return $key;
			}, $html);
		}

		$dom = new DOMDocument('1.0', 'UTF-8');

		$previous = libxml_use_internal_errors(true);

		$wrapped = '<?xml encoding="UTF-8">' . $html;

		if (!$dom->loadHTML($wrapped, LIBXML_HTML_NODEFDTD)) {
			libxml_clear_errors();
			libxml_use_internal_errors($previous);

			return strtr($html, $placeholders);
		}

		$dom->encoding = 'UTF-8';

		libxml_clear_errors();
		libxml_use_internal_errors($previous);

		$xpath = new DOMXPath($dom);

		$skipTags = apply_filters('transliteration_html_avoid_tags', [
			'script',
			'style',
			'noscript',
			'textarea',
			'input',
			'select',
			'option',
			'code',
			'pre',
			'svg',
			'template',
			'head',
			'meta',
			'link',
		], 'dom');

		$ancestorGuards = [];

		foreach ($skipTags as $tag) {
			if (is_string($tag) && $tag !== '') {
				$ancestorGuards[] = 'not(ancestor::' . strtolower($tag) . ')';
			}
		}

		$ancestorGuards[] = 'not(ancestor::*[@data-rstr-skip])';
		$ancestorGuards[] = 'not(ancestor::*[contains(concat(" ", normalize-space(@class), " "), " rstr-skip ")])';
		$ancestorGuards[] = 'not(ancestor::*[@contenteditable="true"])';

		$textQuery = '//text()';

		if ($ancestorGuards !== []) {
			$textQuery = '//text()[' . implode(' and ', $ancestorGuards) . ']';
		}

		$nodes = $xpath->query($textQuery);

		if ($nodes instanceof DOMNodeList && $nodes->length > 0) {
			foreach ($nodes as $textNode) {
				$value = (string) $textNode->nodeValue;

				if (trim($value) === '') {
					continue;
				}

				$textNode->nodeValue = $this->transliterate($value, 'auto', false);
			}
		}

		$output = (string) $dom->saveHTML();

		if ($placeholders !== []) {
			$output = strtr($output, $placeholders);
		}
		
		$output = $this->cleanup_placeholders($output);

		return $output;
	}

    /**
     * Keep HTML syntax out of the primary language-map pass. Unsupported or
     * incomplete tag syntax is left alone through the end of the fragment.
     */
    private function protect_html_markup(
        string $content,
        array &$placeholders,
        ?string $class_map = null,
        array $url_attributes = []
    ): ?string {
        if (strpos($content, '<') === false) {
            return $content;
        }

        $space = '[ \t\n\f\r]';
        $attribute = self::HTML_ATTRIBUTE;
        $end = '(?:' . $attribute . ')*+' . $space . '*+/?>';
        $tag_name = '[a-z][a-z0-9_.:-]*+';
        $closer = $space . '*+>';
        $pattern = '~<!--(?:>|->|[\s\S]*?(?:--!?>|\z))'
            . '|<!\[CDATA\[[\s\S]*?(?:\]\]>|\z)|<[!?][^>]*+(?:>|\z)'
            . '|<(?<raw>script|style|xmp|iframe|noembed|noframes|noscript)' . $end . '[\s\S]*?(?:</\k<raw>' . $closer . '|\z)'
            . '|<plaintext' . $end . '[\s\S]*\z'
            . '|(?<start><' . $tag_name . ')(?<tail>' . $end . ')'
            . '|(?<head_end></head' . $closer . ')'
            . '|</' . $tag_name . $closer
            . '|</?[a-z][\s\S]*\z|</[^>]*+(?:>|\z)'
            . '|(?<text>[^<]++|<)~i';
        $prefix = '%::' . wp_rand() . '::' . wp_rand() . '::12::';
        $in_head = false;

        return preg_replace_callback($pattern, function (array $matches) use (
            &$placeholders,
            &$in_head,
            $attribute,
            $class_map,
            $content,
            $prefix,
            $url_attributes
        ): string {
            $markup = $matches[0];
            $start = strtolower($matches['start'] ?? '');
            if ($start === '<head') {
                $in_head = true;
            } elseif ($start === '<body' || !empty($matches['head_end'])) {
                $in_head = false;
            }

            if (!$in_head && !empty($matches['text'])) {
                // A text "<" must not become a tag opener after transliteration.
                return $markup === '<' ? '&lt;' : $markup;
            }

            if (!$in_head && $class_map !== null && $start !== '') {
                $tail = preg_replace_callback('~' . $attribute . '~i', function (array $parts) use ($class_map, $url_attributes): string {
                    if (!isset($parts[3]) || in_array(strtolower(ltrim($parts[1])), $url_attributes, true)) {
                        return $parts[0];
                    }

                    $value = $parts[3];
                    $quote = $value[0] === '"' || $value[0] === "'" ? $value[0] : '';
                    $value = $quote === '' ? $value : substr($value, 1, -1);
                    $excluded = [];
                    $converted = $this->protect_cyr_excluded_words($value, $excluded);
                    $converted = $class_map::transliterate($converted, 'cyr_to_lat');
                    $converted = Transliteration_Sanitization::get()->lat($converted, true);
                    $converted = strtr($converted, $excluded);

                    if ($quote !== '') {
                        $converted = str_replace($quote, $quote === '"' ? '&quot;' : '&#39;', $converted);
                    } elseif ($converted !== $value) {
                        $converted = strtr($converted, self::HTML_UNQUOTED_ESCAPE);
                        if ($converted === '') {
                            $quote = '"';
                        }
                    }

                    return $parts[1] . $parts[2] . $quote . $converted . $quote;
                }, $matches['tail']);
                $markup = $tail === null ? $markup : $matches['start'] . $tail;
            }

            $index = count($placeholders);
            do {
                $placeholder = $prefix . ($index++) . '::%';
            } while (strpos($content, $placeholder) !== false || isset($placeholders[$placeholder]));
            $placeholders[$placeholder] = $markup;
            return $placeholder;
        }, $content);
    }

    private function transliterate_html_attributes(
        string $content,
        string $direction,
        string $class_map,
        array $url_attributes,
        array $exclude_placeholders,
        array $html_placeholders,
        bool $fix_diacritics = false
    ): string {
        // Run only after raw placeholders have been restored. A keep block can span HTML syntax.
        if ($this->restoring_keep_blocks || !class_exists('WP_HTML_Tag_Processor') || strpos($content, '<') === false) {
            return $content;
        }

        $attributes = array_diff(
            array_unique(array_map('strtolower', $this->private__html_atributes($direction, true))),
            $url_attributes
        );
        if ($attributes === []) {
            return $content;
        }

        $processor = new WP_HTML_Tag_Processor($content);
        $skip_tag = null;
        while (true) {
            try {
                if (!$processor->next_tag(['tag_closers' => 'visit'])) {
                    break;
                }
            } catch (ValueError $error) {
                // WordPress 6.2 can read past EOF on an unterminated attribute.
                return $content;
            }
            $tag = $processor->get_tag();
            if ($skip_tag !== null) {
                if ($processor->is_tag_closer() && $tag === $skip_tag) {
                    $skip_tag = null;
                }
                continue;
            }
            if ($processor->is_tag_closer()) {
                continue;
            }
            if ($tag === 'PLAINTEXT') {
                break;
            }
            // Early versions of the HTML API do not tokenize all raw-text elements.
            if (in_array($tag, ['HEAD', 'SCRIPT', 'STYLE', 'XMP', 'IFRAME', 'NOEMBED', 'NOFRAMES', 'NOSCRIPT'], true)) {
                $skip_tag = $tag;
                continue;
            }

            foreach ($attributes as $attribute) {
                $value = $processor->get_attribute($attribute);
                if (!is_string($value)) {
                    continue;
                }

                $protected_words = $exclude_placeholders;
                $converted = $value;
                if ($direction === 'cyr_to_lat') {
                    $protected_words = [];
                    $converted = $this->protect_cyr_excluded_words($value, $protected_words);
                } elseif ($protected_words !== []) {
                    $converted = strtr($value, array_flip($protected_words));
                }

                $converted = $class_map::transliterate($converted, $direction);
                if ($direction === 'cyr_to_lat') {
                    $converted = Transliteration_Sanitization::get()->lat($converted, true);
                } else {
                    $converted = Transliteration_Sanitization::get()->cyr($converted, true);
                    if ($fix_diacritics) {
                        $converted = self::fix_diacritics($converted);
                    }
                }
                $converted = strtr($converted, $protected_words);

                if ($converted !== $value) {
                    $processor->set_attribute($attribute, $converted);
                }
            }
        }

        $updated = $processor->get_updated_html();
        return $updated === $content ? $content : $this->preserve_attribute_syntax($updated, $html_placeholders);
    }

    /**
     * Keep original delimiters and attribute spelling when WordPress rewrites a
     * value. Only carry encoded values between matching, complete start tags.
     */
    private function preserve_attribute_syntax(string $updated, array $original_markup): string
    {
        $updated_markup = [];
        $masked = $this->protect_html_markup($updated, $updated_markup);
        if ($masked === null || count($original_markup) !== count($updated_markup)) {
            return $updated;
        }

        $original_markup = array_values($original_markup);
        $tag = '~\A(?<open><[a-z][a-z0-9_.:-]*+)(?<attributes>(?:' . self::HTML_ATTRIBUTE . ')*+)(?<end>[ \t\n\f\r]*+/?>)\z~i';
        $attribute = '~' . self::HTML_ATTRIBUTE . '~i';
        $index = 0;

        foreach ($updated_markup as &$markup) {
            $original = $original_markup[$index++];
            if ($original === $markup
                || !preg_match($tag, $original, $before)
                || !preg_match($tag, $markup, $after)
                || $before['open'] !== $after['open']
                || preg_match_all($attribute, $before['attributes'], $old, PREG_SET_ORDER)
                    !== preg_match_all($attribute, $after['attributes'], $new, PREG_SET_ORDER)
            ) {
                continue;
            }

            $result = $before['open'];
            foreach ($old as $position => $source) {
                $target = $new[$position];
                if (strcasecmp(ltrim($source[1]), ltrim($target[1])) !== 0) {
                    continue 2;
                }
                if ($source[0] === $target[0]) {
                    $result .= $source[0];
                    continue;
                }
                if (!isset($source[3], $target[3]) || $target[3][0] !== '"') {
                    continue 2;
                }

                $quote = $source[3][0];
                $encoded = substr($target[3], 1, -1);
                if ($quote === "'") {
                    $result .= $source[1] . $source[2] . "'" . str_replace("'", '&#39;', $encoded) . "'";
                } elseif ($quote === '"') {
                    $result .= $source[1] . $source[2] . '"' . $encoded . '"';
                } else {
                    $result .= $source[1] . $source[2] . ($encoded === '' ? '""' : strtr($encoded, self::HTML_UNQUOTED_ESCAPE));
                }
            }
            $markup = $result . $before['end'];
        }
        unset($markup);

        return strtr($masked, $updated_markup);
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
		static $seed = null;

		if ($seed === null) {
            $seed = (string) wp_rand(100000, 999999);
		}

		return '%%::' . $seed . '::' . $group . '::' . $index . '::%%';
	}
	
	/**
	 * Remove any leftover transliteration placeholders.
	 *
	 * @param string $content
	 * @return string
	 */
	private function cleanup_placeholders(string $content): string
	{
		// Match %%::seed::group::index::%%.
		// Do not use the /u modifier here because content may contain malformed UTF-8.
		$cleaned = preg_replace('/%%::\d+::\d+::\d+::%%/', '', $content);

		return is_string($cleaned) ? $cleaned : $content;
	}

	private function protected_url_attribute_names(): array
	{
		$filtered_attributes = apply_filters('rstr/protected_url_attributes', [
			'href',
			'src',
			'srcset',
			'action',
			'formaction',
			'poster',
			'cite',
		]);

		if (!is_array($filtered_attributes)) {
			return [];
		}

		return array_values(array_unique(array_filter(array_map(static function ($attribute): string {
			return is_string($attribute) ? sanitize_key($attribute) : '';
		}, $filtered_attributes))));
	}

	/**
	 * Preserve URL values in fragments left outside the protected HTML tokens.
	 */
	private function protect_url_attributes(string $content, array &$placeholders, array $attributes): string
	{
		if ($attributes === []) {
			return $content;
		}

		$pattern = '/(?<![\w-])(' . implode('|', array_map(static function (string $attribute): string {
			return preg_quote($attribute, '/');
		}, $attributes)) . ')(\s*=\s*)(?:(["\'])(.*?)\3|([^\s"\'=<>`]+))/is';

		$protected = preg_replace_callback($pattern, static function ($matches) use (&$placeholders, $content): string {
			$index = count($placeholders);
			do {
				$placeholder = self::make_placeholder(10, $index++);
			} while (strpos($content, $placeholder) !== false || isset($placeholders[$placeholder]));

			$value = isset($matches[3]) && $matches[3] !== ''
				? $matches[3] . $placeholder . $matches[3]
				: $placeholder;

			$original_value = isset($matches[3]) && $matches[3] !== '' ? $matches[4] : $matches[5];
			$placeholders[$placeholder] = $original_value;

			return $matches[1] . $matches[2] . $value;
		}, $content);

		return is_string($protected) ? $protected : $content;
	}
	
	/**
	 * Protect excluded Cyrillic words before transliteration.
	 *
	 * This method uses Unicode-aware boundaries instead of simple str_replace(),
	 * because Phantom mode works with DOM text nodes and simple replacement can
	 * produce partially transliterated words.
	 *
	 * @param string $content Input content.
	 * @param array<string, string> $placeholders Placeholder storage.
	 *
	 * @return string
	 */
	private function protect_cyr_excluded_words(string $content, array &$placeholders): string
	{
		$exclude_list = $this->cyr_exclude_list();

		if (empty($exclude_list)) {
			return $content;
		}

		$exclude_list = array_values(array_unique(array_filter(array_map(static function ($word): string {
			return is_string($word) ? trim($word) : '';
		}, $exclude_list))));

		if ($exclude_list === []) {
			return $content;
		}

		usort($exclude_list, static function (string $a, string $b): int {
			return mb_strlen($b, 'UTF-8') <=> mb_strlen($a, 'UTF-8');
		});

		foreach ($exclude_list as $index => $word) {
			$placeholder = self::make_placeholder(9, $index);

			$pattern = '/(?<![\p{L}\p{N}_])' . preg_quote($word, '/') . '(?![\p{L}\p{N}_])/u';

			$replaced = preg_replace_callback($pattern, static function () use ($placeholder): string {
				return $placeholder;
			}, $content);

			if (! is_string($replaced)) {
				continue;
			}

			$content = $replaced;
			$placeholders[$placeholder] = $word;
		}

		return $content;
	}

}
