<?php

if (!defined('WPINC')) {
    die();
}

/**
 * Active Plugin: WooCommerce
 *
 * @link              http://infinitumform.com/
 * @since             1.2.4
 * @package           Serbian_Transliteration
 * @author            Ivijan-Stefan Stipic
 */
class Transliteration_Plugin_Woocommerce extends Transliteration
{
    /**
     * WooCommerce text domains that should be transliterated on the frontend.
     *
     * @var string[]
     */
    private array $text_domains = [
        'woocommerce',
        'woo-gutenberg-products-block',
        'woocommerce-payments',
    ];

    /**
     * Main constructor.
     */
    public function __construct()
    {
        $this->add_filter('transliteration_mode_filters', 'filters');

        add_action('init', [$this, 'register_direct_filters'], PHP_INT_MAX - 150);
    }

    /**
     * Register direct WooCommerce filters.
     *
     * These filters are registered directly because the generic mode dispatcher
     * only calls handlers available on Transliteration_Mode, not methods from
     * this WooCommerce integration class.
     *
     * @return void
     */
    public function register_direct_filters(): void
    {
        if (!$this->can_transliterate_frontend()) {
            return;
        }

        add_filter('gettext', [$this, 'gettext'], PHP_INT_MAX - 100, 3);
        add_filter('gettext_with_context', [$this, 'gettext_with_context'], PHP_INT_MAX - 100, 4);
        add_filter('ngettext', [$this, 'ngettext'], PHP_INT_MAX - 100, 5);
        add_filter('ngettext_with_context', [$this, 'ngettext_with_context'], PHP_INT_MAX - 100, 6);

        add_filter('woocommerce_page_title', [$this, 'text'], PHP_INT_MAX - 100, 1);
        add_filter('woocommerce_product_title', [$this, 'text'], PHP_INT_MAX - 100, 1);
        add_filter('woocommerce_endpoint_order-received_title', [$this, 'text'], PHP_INT_MAX - 100, 1);

        add_filter('woocommerce_product_get_name', [$this, 'text'], PHP_INT_MAX - 100, 1);
        add_filter('woocommerce_product_variation_get_name', [$this, 'text'], PHP_INT_MAX - 100, 1);
        add_filter('woocommerce_product_get_title', [$this, 'text'], PHP_INT_MAX - 100, 1);
        add_filter('woocommerce_product_get_short_description', [$this, 'html'], PHP_INT_MAX - 100, 1);
        add_filter('woocommerce_product_get_description', [$this, 'html'], PHP_INT_MAX - 100, 1);

        add_filter('woocommerce_cart_item_name', [$this, 'html'], PHP_INT_MAX - 100, 3);
        add_filter('woocommerce_cart_item_price', [$this, 'html'], PHP_INT_MAX - 100, 3);
        add_filter('woocommerce_cart_item_subtotal', [$this, 'html'], PHP_INT_MAX - 100, 3);
        add_filter('woocommerce_widget_cart_item_quantity', [$this, 'html'], PHP_INT_MAX - 100, 3);
        add_filter('woocommerce_cart_item_backorder_notification', [$this, 'html'], PHP_INT_MAX - 100, 2);
        add_filter('woocommerce_cart_item_remove_link', [$this, 'html'], PHP_INT_MAX - 100, 2);

        add_filter('woocommerce_cart_subtotal', [$this, 'html'], PHP_INT_MAX - 100, 3);
        add_filter('woocommerce_cart_totals_order_total_html', [$this, 'html'], PHP_INT_MAX - 100, 1);
        add_filter('woocommerce_cart_totals_coupon_html', [$this, 'html'], PHP_INT_MAX - 100, 3);
        add_filter('woocommerce_cart_totals_fee_html', [$this, 'html'], PHP_INT_MAX - 100, 2);
        add_filter('woocommerce_cart_totals_taxes_total_html', [$this, 'html'], PHP_INT_MAX - 100, 1);
        add_filter('woocommerce_coupon_discount_amount_html', [$this, 'html'], PHP_INT_MAX - 100, 2);

        add_filter('woocommerce_shipping_not_enabled_on_cart_html', [$this, 'html'], PHP_INT_MAX - 100, 1);
        add_filter('woocommerce_shipping_may_be_available_html', [$this, 'html'], PHP_INT_MAX - 100, 1);
        add_filter('woocommerce_no_shipping_available_html', [$this, 'html'], PHP_INT_MAX - 100, 1);
        add_filter('woocommerce_cart_no_shipping_available_html', [$this, 'html'], PHP_INT_MAX - 100, 1);
        add_filter('woocommerce_cart_shipping_method_full_label', [$this, 'html'], PHP_INT_MAX - 100, 2);
        add_filter('woocommerce_shipping_rate_label', [$this, 'text'], PHP_INT_MAX - 100, 2);

        add_filter('woocommerce_order_item_name', [$this, 'html'], PHP_INT_MAX - 100, 3);
        add_filter('woocommerce_order_item_quantity_html', [$this, 'html'], PHP_INT_MAX - 100, 2);
        add_filter('woocommerce_order_button_text', [$this, 'text'], PHP_INT_MAX - 100, 1);
        add_filter('woocommerce_pay_order_button_text', [$this, 'text'], PHP_INT_MAX - 100, 1);
        add_filter('woocommerce_pay_order_button_html', [$this, 'html'], PHP_INT_MAX - 100, 1);
        add_filter('woocommerce_order_button_html', [$this, 'html'], PHP_INT_MAX - 100, 1);
        add_filter('woocommerce_thankyou_order_received_text', [$this, 'html'], PHP_INT_MAX - 100, 2);

        add_filter('woocommerce_get_availability_text', [$this, 'text'], PHP_INT_MAX - 100, 2);
        add_filter('woocommerce_get_stock_html', [$this, 'html'], PHP_INT_MAX - 100, 2);
        add_filter('woocommerce_get_price_html', [$this, 'html'], PHP_INT_MAX - 100, 2);
        add_filter('woocommerce_variable_price_html', [$this, 'html'], PHP_INT_MAX - 100, 2);
        add_filter('woocommerce_variable_empty_price_html', [$this, 'html'], PHP_INT_MAX - 100, 2);
        add_filter('woocommerce_empty_price_html', [$this, 'html'], PHP_INT_MAX - 100, 2);
        add_filter('woocommerce_grouped_price_html', [$this, 'html'], PHP_INT_MAX - 100, 2);
        add_filter('woocommerce_grouped_empty_price_html', [$this, 'html'], PHP_INT_MAX - 100, 2);

        add_filter('woocommerce_product_single_add_to_cart_text', [$this, 'text'], PHP_INT_MAX - 100, 2);
        add_filter('woocommerce_product_add_to_cart_text', [$this, 'text'], PHP_INT_MAX - 100, 2);
        add_filter('woocommerce_product_add_to_cart_description', [$this, 'text'], PHP_INT_MAX - 100, 2);
        add_filter('woocommerce_reset_variations_link', [$this, 'html'], PHP_INT_MAX - 100, 1);

        add_filter('woocommerce_sale_flash', [$this, 'html'], PHP_INT_MAX - 100, 3);
        add_filter('woocommerce_product_tabs', [$this, 'product_tabs'], PHP_INT_MAX - 100, 1);
        add_filter('woocommerce_product_description_heading', [$this, 'text'], PHP_INT_MAX - 100, 1);
        add_filter('woocommerce_product_additional_information_heading', [$this, 'text'], PHP_INT_MAX - 100, 1);
        add_filter('woocommerce_product_related_products_heading', [$this, 'text'], PHP_INT_MAX - 100, 1);
        add_filter('woocommerce_product_upsells_products_heading', [$this, 'text'], PHP_INT_MAX - 100, 1);
        add_filter('woocommerce_product_cross_sells_products_heading', [$this, 'text'], PHP_INT_MAX - 100, 1);

   //     add_filter('woocommerce_get_breadcrumb', [$this, 'breadcrumb'], PHP_INT_MAX - 100, 1);
    //    add_filter('woocommerce_breadcrumb_defaults', [$this, 'breadcrumb_defaults'], PHP_INT_MAX - 100, 1);

        add_filter('woocommerce_checkout_fields', [$this, 'checkout_fields'], PHP_INT_MAX - 100, 1);
        add_filter('woocommerce_default_address_fields', [$this, 'checkout_fields'], PHP_INT_MAX - 100, 1);
        add_filter('woocommerce_form_field', [$this, 'html'], PHP_INT_MAX - 100, 4);

        add_filter('woocommerce_countries', [$this, 'array_text'], PHP_INT_MAX - 100, 1);
        add_filter('woocommerce_get_country_name', [$this, 'text'], PHP_INT_MAX - 100, 1);
        add_filter('woocommerce_countries_allowed_countries', [$this, 'array_text'], PHP_INT_MAX - 100, 1);
        add_filter('woocommerce_countries_shipping_countries', [$this, 'array_text'], PHP_INT_MAX - 100, 1);
        add_filter('woocommerce_get_allowed_countries', [$this, 'array_text'], PHP_INT_MAX - 100, 1);
        add_filter('woocommerce_get_shipping_countries', [$this, 'array_text'], PHP_INT_MAX - 100, 1);

        add_filter('woocommerce_available_payment_gateways', [$this, 'payment_gateways'], PHP_INT_MAX - 100, 1);
        add_filter('woocommerce_gateway_title', [$this, 'text'], PHP_INT_MAX - 100, 2);
        add_filter('woocommerce_gateway_description', [$this, 'html'], PHP_INT_MAX - 100, 2);

        add_filter('wc_add_to_cart_message_html', [$this, 'html'], PHP_INT_MAX - 100, 3);
        add_filter('woocommerce_checkout_coupon_message', [$this, 'html'], PHP_INT_MAX - 100, 1);
        add_filter('woocommerce_checkout_login_message', [$this, 'html'], PHP_INT_MAX - 100, 1);
        add_filter('woocommerce_checkout_must_be_logged_in_message', [$this, 'html'], PHP_INT_MAX - 100, 1);
        add_filter('woocommerce_no_available_payment_methods_message', [$this, 'html'], PHP_INT_MAX - 100, 1);
    }

    /**
     * Keep only handlers that exist on the central Transliteration_Mode class.
     *
     * @param mixed[] $filters Existing mode filters.
     *
     * @return mixed[]
     */
    public function filters($filters = []): array
    {
        return array_merge($filters, [
            'woocommerce_currency_symbol' => [self::class, 'currency_symbol'],
            'woocommerce_cart_item_quantity' => [self::class, 'fix_quantity'],
        ]);
    }

    /**
     * Transliterate plain text.
     *
     * @param mixed $content Input content.
     *
     * @return mixed
     */
    public function text($content)
    {
        if (!is_string($content) || $content === '') {
            return $content;
        }

        return Transliteration_Controller::get()->transliterate_no_html($content);
    }

    /**
     * Transliterate HTML safely.
     *
     * @param mixed $content Input HTML.
     *
     * @return mixed
     */
    public function html($content)
    {
        if (!is_string($content) || $content === '') {
            return $content;
        }

        if (stripos($content, '<') === false) {
            return $this->text($content);
        }

        return Transliteration_Controller::get()->transliterate_html($content);
    }

    /**
     * Transliterate array values recursively.
     *
     * @param mixed $data Input data.
     *
     * @return mixed
     */
    public function array_text($data)
    {
        if (!is_array($data)) {
            return $data;
        }

        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $data[$key] = $this->text($value);
                continue;
            }

            if (is_array($value)) {
                $data[$key] = $this->array_text($value);
            }
        }

        return $data;
    }

    /**
     * Transliterate WooCommerce gettext strings.
     *
     * @param mixed  $translated Translated text.
     * @param string $text       Original text.
     * @param string $domain     Text domain.
     *
     * @return mixed
     */
    public function gettext($translated, string $text = '', string $domain = '')
    {
        if (!$this->should_translate_gettext($translated, $domain)) {
            return $translated;
        }

        return $this->text($translated);
    }

    /**
     * Transliterate WooCommerce gettext strings with context.
     *
     * @param mixed  $translated Translated text.
     * @param string $text       Original text.
     * @param string $context    Translation context.
     * @param string $domain     Text domain.
     *
     * @return mixed
     */
    public function gettext_with_context($translated, string $text = '', string $context = '', string $domain = '')
    {
        return $this->gettext($translated, $text, $domain);
    }

    /**
     * Transliterate WooCommerce plural gettext strings.
     *
     * @param mixed  $translated Translated text.
     * @param string $single     Singular text.
     * @param string $plural     Plural text.
     * @param int    $number     Number.
     * @param string $domain     Text domain.
     *
     * @return mixed
     */
    public function ngettext($translated, string $single = '', string $plural = '', int $number = 1, string $domain = '')
    {
        return $this->gettext($translated, $single, $domain);
    }

    /**
     * Transliterate WooCommerce plural gettext strings with context.
     *
     * @param mixed  $translated Translated text.
     * @param string $single     Singular text.
     * @param string $plural     Plural text.
     * @param int    $number     Number.
     * @param string $context    Translation context.
     * @param string $domain     Text domain.
     *
     * @return mixed
     */
    public function ngettext_with_context($translated, string $single = '', string $plural = '', int $number = 1, string $context = '', string $domain = '')
    {
        return $this->gettext($translated, $single, $domain);
    }

    /**
     * Check whether gettext string should be transliterated.
     *
     * @param mixed  $translated Translated text.
     * @param string $domain     Text domain.
     *
     * @return bool
     */
    private function should_translate_gettext($translated, string $domain = ''): bool
    {
        if (!$this->can_transliterate_frontend()) {
            return false;
        }

        if (!is_string($translated) || $translated === '') {
            return false;
        }

        if (!in_array($domain, $this->text_domains, true)) {
            return false;
        }

        return true;
    }

    /**
     * Check if current request can be transliterated.
     *
     * @return bool
     */
    private function can_transliterate_frontend(): bool
    {
        if (is_admin() && !(function_exists('wp_doing_ajax') && wp_doing_ajax())) {
            return false;
        }

        if (defined('WP_CLI') && WP_CLI) {
            return false;
        }

        if (function_exists('wp_doing_cron') && wp_doing_cron()) {
            return false;
        }

        if (get_rstr_option('transliteration-mode', 'cyr_to_lat') === 'none') {
            return false;
        }

        if (Transliteration_Controller::get()->disable_transliteration()) {
            return false;
        }

        return true;
    }

    /**
     * Transliterate WooCommerce product tabs.
     *
     * @param mixed $tabs Product tabs.
     *
     * @return mixed
     */
    public function product_tabs($tabs)
    {
        if (!is_array($tabs)) {
            return $tabs;
        }

        foreach ($tabs as $key => $tab) {
            if (!is_array($tab)) {
                continue;
            }

            if (isset($tab['title']) && is_string($tab['title'])) {
                $tabs[$key]['title'] = $this->text($tab['title']);
            }
        }

        return $tabs;
    }

    /**
     * Transliterate WooCommerce breadcrumb labels.
     *
     * @param mixed $crumbs Breadcrumb array.
     *
     * @return mixed
     */
    public function breadcrumb($crumbs)
    {
        if (!is_array($crumbs)) {
            return $crumbs;
        }

        foreach ($crumbs as $index => $crumb) {
            if (isset($crumb[0]) && is_string($crumb[0])) {
                $crumbs[$index][0] = $this->text($crumb[0]);
            }
        }

        return $crumbs;
    }

    /**
     * Transliterate WooCommerce breadcrumb defaults.
     *
     * @param mixed $defaults Breadcrumb defaults.
     *
     * @return mixed
     */
    public function breadcrumb_defaults($defaults)
    {
        if (!is_array($defaults)) {
            return $defaults;
        }

        foreach (['delimiter', 'wrap_before', 'wrap_after', 'before', 'after', 'home'] as $key) {
            if (isset($defaults[$key]) && is_string($defaults[$key])) {
                $defaults[$key] = $this->html($defaults[$key]);
            }
        }

        return $defaults;
    }

    /**
     * Transliterate checkout field labels and placeholders.
     *
     * @param mixed $fields Checkout fields.
     *
     * @return mixed
     */
    public function checkout_fields($fields)
    {
        if (!is_array($fields)) {
            return $fields;
        }

        foreach ($fields as $group_key => $group) {
            if (!is_array($group)) {
                continue;
            }

            foreach ($group as $field_key => $field) {
                if (!is_array($field)) {
                    continue;
                }

                foreach (['label', 'placeholder', 'description'] as $key) {
                    if (isset($field[$key]) && is_string($field[$key])) {
                        $fields[$group_key][$field_key][$key] = $this->text($field[$key]);
                    }
                }

                if (isset($field['options']) && is_array($field['options'])) {
                    $fields[$group_key][$field_key]['options'] = $this->array_text($field['options']);
                }
            }
        }

        return $fields;
    }

    /**
     * Transliterate payment gateway titles and descriptions.
     *
     * @param mixed $gateways Payment gateways.
     *
     * @return mixed
     */
    public function payment_gateways($gateways)
    {
        if (!is_array($gateways)) {
            return $gateways;
        }

        foreach ($gateways as $gateway) {
            if (!is_object($gateway)) {
                continue;
            }

            if (isset($gateway->title) && is_string($gateway->title)) {
                $gateway->title = $this->text($gateway->title);
            }

            if (isset($gateway->description) && is_string($gateway->description)) {
                $gateway->description = $this->html($gateway->description);
            }

            if (isset($gateway->method_title) && is_string($gateway->method_title)) {
                $gateway->method_title = $this->text($gateway->method_title);
            }

            if (isset($gateway->method_description) && is_string($gateway->method_description)) {
                $gateway->method_description = $this->html($gateway->method_description);
            }
        }

        return $gateways;
    }

    /**
     * Transliterate currency symbol.
     *
     * @param mixed  $currency_symbol Currency symbol.
     * @param string $currency        Currency code.
     *
     * @return mixed
     */
    public static function currency_symbol($currency_symbol, $currency = '')
    {
        if (!is_string($currency_symbol) || $currency_symbol === '') {
            return $currency_symbol;
        }

        return Transliteration_Controller::get()->transliterate(Transliteration_Utilities::decode($currency_symbol));
    }

    /**
     * Fix cart quantity data.
     *
     * @param mixed $data Quantity data.
     *
     * @return mixed
     */
    public static function fix_quantity($data)
    {
        if (is_array($data) && isset($data['product_name']) && is_string($data['product_name'])) {
            $data['product_name'] = Transliteration_Controller::get()->transliterate_no_html($data['product_name']);
        }

        return $data;
    }
}