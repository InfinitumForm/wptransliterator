<?php

if (!defined('WPINC')) {
    die();
}

class Transliteration_Mode_Phantom extends Transliteration
{
    use Transliteration__Cache;

    public const MODE = 'phantom';

    /**
     * Stores the output buffer level created by this mode.
     *
     * @var int|null
     */
    private ?int $buffer_level = null;

    /**
     * The main constructor.
     */
    public function __construct()
    {
        $this->init_actions();
    }

    /**
     * Initialize actions.
     */
    public function init_actions(): void
    {
        $this->add_action('template_redirect', 'buffer_start', 0);
        $this->add_action('shutdown', 'buffer_end', 0);
		
		add_filter('gettext', [$this, 'gettext_content'], 9999, 3);
		add_filter('gettext_with_context', [$this, 'gettext_context_content'], 9999, 4);
		add_filter('ngettext', [$this, 'ngettext_content'], 9999, 5);
    }

    /**
     * Get current instance.
     */
    public static function get()
    {
        return self::cached_static('instance', fn (): \Transliteration_Mode_Phantom => new self());
    }

    /**
     * Get available filters for this mode.
     *
     * @return array<string, string>
     */
    public function filters(): array
	{
		return [
			'wp_title'                       => 'no_html_content',
			'pre_get_document_title'         => 'no_html_content',
			'document_title_parts'           => 'array_content',
			'bloginfo'                       => 'no_html_content',
			'get_bloginfo'                   => 'no_html_content',
			'language_attributes'            => 'html_content',
			'the_generator'                  => 'html_content',

			// WooCommerce titles and common labels.
			
			'document_title'                  => 'no_html_content',
			'wpseo_title'                     => 'no_html_content',
			'rank_math/frontend/title'        => 'no_html_content',
			'aioseo_title'                    => 'no_html_content',
		];
	}
	
	/**
	 * Transliterate frontend translated strings.
	 *
	 * @param mixed $translated Translated string.
	 *
	 * @return mixed
	 */
	public function gettext_content($translated, $text = '', $domain = '')
	{
		if (!is_string($translated) || $translated === '') {
			return $translated;
		}

		if ($this->should_skip_translation_filter()) {
			return $translated;
		}

		return Transliteration_Controller::get()->transliterate($translated, 'auto', false);
	}


	/**
	 * Transliterate frontend translated strings with context.
	 *
	 * @param mixed $translated Translated string.
	 *
	 * @return mixed
	 */
	public function gettext_context_content($translated, $text = '', $context = '', $domain = '')
	{
		return $this->gettext_content($translated, $text, $domain);
	}
	
	public function ngettext_content($translated, $single = '', $plural = '', $number = 1, $domain = '')
	{
		return $this->gettext_content($translated, '', $domain);
	}


	/**
	 * Avoid transliterating admin/editor/CLI strings.
	 *
	 * @return bool
	 */
	private function should_skip_translation_filter(): bool
	{
		if (is_admin()) {
			return true;
		}

		if (defined('WP_CLI') && WP_CLI) {
			return true;
		}

		if (function_exists('wp_doing_cron') && wp_doing_cron()) {
			return true;
		}

		if (function_exists('wp_is_serving_rest_request') && wp_is_serving_rest_request()) {
			return false;
		}

		if (function_exists('wp_doing_ajax') && wp_doing_ajax()) {
			return false;
		}

		return false;
	}

    /**
     * Transliterate array content recursively.
     *
     * @param mixed $content Content value.
     *
     * @return mixed
     */
    public function array_content($content)
    {
        if (!is_array($content)) {
            return $content;
        }

        foreach ($content as $key => $value) {
            if (is_string($value)) {
                $content[$key] = Transliteration_Controller::get()->transliterate($value, 'auto', false);
                continue;
            }

            if (is_array($value)) {
                $content[$key] = $this->array_content($value);
            }
        }

        return $content;
    }

    /**
     * Transliterate WooCommerce product tabs.
     *
     * @param mixed $tabs WooCommerce tabs.
     *
     * @return mixed
     */
    public function woocommerce_tabs_content($tabs)
    {
        if (!is_array($tabs)) {
            return $tabs;
        }

        foreach ($tabs as $key => $tab) {
            if (!is_array($tab)) {
                continue;
            }

            if (isset($tab['title']) && is_string($tab['title'])) {
                $tabs[$key]['title'] = Transliteration_Controller::get()->transliterate($tab['title'], 'auto', false);
            }
        }

        return $tabs;
    }

    /**
     * Transliterate WooCommerce breadcrumb labels.
     *
     * @param mixed $crumbs Breadcrumb items.
     *
     * @return mixed
     */
    public function woocommerce_breadcrumb_content($crumbs)
    {
        if (!is_array($crumbs)) {
            return $crumbs;
        }

        foreach ($crumbs as $index => $crumb) {
            if (isset($crumb[0]) && is_string($crumb[0])) {
                $crumbs[$index][0] = Transliteration_Controller::get()->transliterate($crumb[0], 'auto', false);
            }
        }

        return $crumbs;
    }

    /**
     * Start output buffering for frontend HTML output.
     */
    public function buffer_start(): void
	{
		if ($this->buffer_level !== null) {
			return;
		}

		if (is_admin()) {
			return;
		}

		if (function_exists('wp_doing_ajax') && wp_doing_ajax()) {
			return;
		}

		if (function_exists('wp_doing_cron') && wp_doing_cron()) {
			return;
		}

		if (defined('WP_CLI') && WP_CLI) {
			return;
		}

		if (function_exists('wp_is_json_request') && wp_is_json_request()) {
			return;
		}

		if (function_exists('wp_is_serving_rest_request') && wp_is_serving_rest_request()) {
			return;
		}

		ob_start([$this, 'buffer_callback']);

		$this->buffer_level = ob_get_level();
	}

	/**
	 * Detect WooCommerce dynamic frontend requests.
	 *
	 * @return bool
	 */
	private function is_woocommerce_dynamic_request(): bool
	{
		$request_uri = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '';

		if ($request_uri === '') {
			return false;
		}

		return (
			stripos($request_uri, 'wc-ajax=') !== false
			|| stripos($request_uri, '/wc/store/') !== false
			|| stripos($request_uri, '/wc/store/v1/') !== false
			|| stripos($request_uri, '/wc/store/v2/') !== false
		);
	}

    /**
     * Transliterate final HTML output.
     *
     * @param mixed $buffer Full buffered HTML content.
     *
     * @return string
     */
    public function buffer_callback($buffer): string
	{
		if (!is_string($buffer) || $buffer === '') {
			return (string) $buffer;
		}

		if (trim($buffer) === '') {
			return $buffer;
		}

		return Transliteration_Controller::get()->transliterate_html($buffer);
	}

    /**
     * Flush only the buffer created by this mode.
     */
    public function buffer_end(): void
    {
        if ($this->buffer_level === null) {
            return;
        }

        while (ob_get_level() >= $this->buffer_level) {
            ob_end_flush();
        }

        $this->buffer_level = null;
    }

    /**
     * Check whether current request is a frontend HTML request.
     *
     * @return bool
     */
    private function is_frontend_html_request(): bool
    {
        if (is_admin()) {
            return false;
        }

        if (function_exists('wp_doing_ajax') && wp_doing_ajax()) {
            return false;
        }

        if (function_exists('wp_doing_cron') && wp_doing_cron()) {
            return false;
        }

        if (defined('WP_CLI') && WP_CLI) {
            return false;
        }

        if (function_exists('wp_is_json_request') && wp_is_json_request()) {
            return false;
        }

        if (function_exists('wp_is_serving_rest_request') && wp_is_serving_rest_request()) {
            return false;
        }

        return true;
    }

    /**
     * Check whether buffer looks like HTML or visible text output.
     *
     * @param string $buffer Buffered output.
     *
     * @return bool
     */
    private function looks_like_html_output(string $buffer): bool
    {
        if (trim($buffer) === '') {
            return false;
        }

        if (
            stripos($buffer, '<html') !== false
            || stripos($buffer, '<!doctype') !== false
            || stripos($buffer, '<body') !== false
            || stripos($buffer, '</body>') !== false
            || stripos($buffer, '<div') !== false
            || stripos($buffer, '<main') !== false
            || stripos($buffer, '<section') !== false
            || stripos($buffer, '<article') !== false
            || stripos($buffer, '<span') !== false
            || stripos($buffer, '<h1') !== false
            || stripos($buffer, '<h2') !== false
            || stripos($buffer, '<p') !== false
            || stripos($buffer, 'woocommerce') !== false
        ) {
            return true;
        }

        return wp_strip_all_tags($buffer) !== trim($buffer) || preg_match('/[\p{Cyrillic}]/u', $buffer) === 1;
    }
}