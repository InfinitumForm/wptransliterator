<?php
/**
 * Critical Site Health checks for Transliterator.
 *
 * Only register a test if a verified, actionable critical condition exists.
 * No success notices, promotional tests, remote requests, or stored results.
 *
 * @package Serbian_Transliteration
 */

if (!defined('WPINC')) {
    die();
}

final class Transliteration_Site_Health
{
    /**
     * Register the Site Health integration.
     */
    public function __construct()
    {
        add_filter('site_status_tests', [$this, 'register_tests']);
    }

    /**
     * Register only currently failing direct tests.
     *
     * @param array $tests Existing WordPress Site Health tests.
     * @return array
     */
    public function register_tests(array $tests): array
    {
        if (!isset($tests['direct']) || !is_array($tests['direct'])) {
            $tests['direct'] = [];
        }

        if (
            !function_exists('mb_strlen')
            || !function_exists('mb_substr')
            || !function_exists('mb_strtoupper')
        ) {
            $tests['direct']['transliterator_mbstring'] = [
                'label' => __('Transliterator requires PHP mbstring', 'serbian-transliteration'),
                'test'  => [$this, 'test_mbstring'],
            ];
        }

        $mode = (string) get_rstr_option('mode', 'light');
        $available_modes = Transliteration_Utilities::available_modes();

        if (!is_array($available_modes) || !in_array($mode, $available_modes, true)) {
            $tests['direct']['transliterator_mode'] = [
                'label' => __('Transliterator cannot start the selected mode', 'serbian-transliteration'),
                'test'  => [$this, 'test_mode'],
            ];
        }

        $scheme = (string) get_rstr_option('language-scheme', 'auto');
        $disabled = get_rstr_option('disable-by-language', []);
        $is_disabled = is_array($disabled) && ($disabled[$scheme] ?? 'no') === 'yes';

        if (
            $scheme !== ''
            && $scheme !== 'auto'
            && !$is_disabled
            && (
                preg_match('/^[a-zA-Z0-9_]+$/D', $scheme) !== 1
                || !class_exists('Transliteration_Map_' . $scheme)
            )
        ) {
            $tests['direct']['transliterator_language_map'] = [
                'label' => __('Transliterator cannot load the selected language map', 'serbian-transliteration'),
                'test'  => [$this, 'test_language_map'],
            ];
        }

        return $tests;
    }

    /**
     * Report an unavailable PHP dependency.
     *
     * @return array Site Health result.
     */
    public function test_mbstring(): array
    {
        return $this->critical_result(
            'transliterator_mbstring',
            __('Transliterator requires PHP mbstring', 'serbian-transliteration'),
            __('Required multibyte string functions are unavailable. Transliteration can fail when processing Unicode text.', 'serbian-transliteration'),
            '<p>' . esc_html__('Ask your hosting provider to enable the PHP mbstring extension for this website.', 'serbian-transliteration') . '</p>'
        );
    }

    /**
     * Report a selected mode that is unavailable in the current environment.
     *
     * @return array Site Health result.
     */
    public function test_mode(): array
    {
        $mode = (string) get_rstr_option('mode', 'light');
        $settings_url = admin_url('options-general.php?page=serbian-transliteration&tab=settings');

        if ($mode === 'phantom' && !class_exists('DOMDocument')) {
            $reason = __('Phantom Mode is selected, but the PHP DOM extension is unavailable. This mode cannot initialize correctly.', 'serbian-transliteration');
            $help = $this->specialist_assistance();
        } elseif ($mode === 'woocommerce' && defined('RSTR_WOOCOMMERCE') && !RSTR_WOOCOMMERCE) {
            $reason = __('WooCommerce-only Mode is selected, but Transliterator does not detect WooCommerce as active. This mode is unavailable.', 'serbian-transliteration');
            $help = $this->specialist_assistance();
        } else {
            $reason = sprintf(
                /* translators: %s: transliteration mode identifier. */
                __('The selected transliteration mode "%s" is not available. Transliteration may not initialize correctly.', 'serbian-transliteration'),
                esc_html($mode)
            );
            $help = '';
        }

        $actions = sprintf(
            '<p><a href="%1$s">%2$s</a></p>',
            esc_url($settings_url),
            esc_html__('Review Transliterator settings and choose an available mode', 'serbian-transliteration')
        );

        return $this->critical_result(
            'transliterator_mode',
            __('Transliterator cannot start the selected mode', 'serbian-transliteration'),
            $reason,
            $actions . $help
        );
    }

    /**
     * Report an unavailable explicitly configured language map.
     *
     * @return array Site Health result.
     */
    public function test_language_map(): array
    {
        $scheme = (string) get_rstr_option('language-scheme', 'auto');
        $settings_url = admin_url('options-general.php?page=serbian-transliteration&tab=settings');

        $description = sprintf(
            /* translators: %s: language scheme identifier. */
            __('The configured language map "%s" cannot be loaded. Transliteration for this language will not work correctly.', 'serbian-transliteration'),
            esc_html($scheme)
        );

        $actions = sprintf(
            '<p><a href="%1$s">%2$s</a></p>',
            esc_url($settings_url),
            esc_html__('Select an available language map or restore the missing map file', 'serbian-transliteration')
        );

        return $this->critical_result(
            'transliterator_language_map',
            __('Transliterator cannot load the selected language map', 'serbian-transliteration'),
            $description,
            $actions . $this->specialist_assistance()
        );
    }

    /**
     * Build a genuine critical Site Health result.
     *
     * @param string $test        Unique test identifier.
     * @param string $label       Result title.
     * @param string $description Problem description (plain text).
     * @param string $actions     Trusted, escaped HTML with resolution steps.
     * @return array
     */
    private function critical_result(string $test, string $label, string $description, string $actions): array
    {
        return [
            'label'       => $label,
            'status'      => 'critical',
            'badge'       => [
                'label' => __('Transliterator', 'serbian-transliteration'),
                'color' => 'red',
            ],
            'description' => '<p>' . $description . '</p>',
            'actions'     => $actions,
            'test'        => $test,
        ];
    }

    /**
     * Offer optional, clearly identified professional help only for complex issues.
     *
     * @return string Escaped HTML.
     */
    private function specialist_assistance(): string
    {
        return sprintf(
            '<p>%1$s <a href="%2$s" target="_blank" rel="noopener noreferrer">%3$s</a>.</p>',
            esc_html__('For complex configuration or compatibility problems, professional assistance is available from', 'serbian-transliteration'),
            esc_url('https://infinitumform.com/'),
            esc_html__('INFINITUM FORM', 'serbian-transliteration')
        );
    }
}
