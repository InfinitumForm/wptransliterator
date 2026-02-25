<?php if (!defined('WPINC')) {
    die();
}

class Transliteration_Settings_Fields
{
    private $options;
    private $nonce;

    public function __construct()
    {
        $this->options = get_option(RSTR_NAME, []);

        if (empty($this->nonce)) {
            $this->nonce = esc_attr(wp_create_nonce('rstr-options'));
        }

        if (empty($this->options)) {
            $this->options = get_rstr_option();
        }

        add_action('wp_ajax_rstr_filter_mode_options', [$this, 'ajax__rstr_filter_mode_options']);
    }

    /**
     * Sanitize each setting field as needed
     *
     * @param  array   $input Contains all settings fields as array keys
     * @return mixed[]
     */
    public function sanitize_settings($input): array
    {
        $new_input = [];

        foreach ($input as $key => $value) {
            if (in_array($key, ['exclude-latin-words', 'exclude-cyrillic-words']) && preg_match('/\n/', $value)) {
                $value = Transliteration_Utilities::explode("\n", $value);
                $value = implode('|', $value);
            }

            $new_input[$key] = is_array($value) ? array_map('sanitize_text_field', $value) : sanitize_text_field($value);
        }

        if (isset($new_input[RSTR_NAME]['transliteration-mode'])) {
            switch ($new_input[RSTR_NAME]['transliteration-mode']) {
                case 'cyr_to_lat':
                    Transliteration_Utilities::setcookie('lat');
                    break;

                case 'lat_to_cyr':
                    Transliteration_Utilities::setcookie('cyr');
                    break;
            }
        }

        Transliteration_Utilities::clear_plugin_cache();

        return $new_input;
    }

    public function register_settings(): void
    {
        register_setting(
            'transliteration-group',
            RSTR_NAME,
            [
                'type'              => 'array',
                'sanitize_callback' => [$this, 'sanitize_settings'],
            ]
        );

        /*
         * Global Settings
         */
        add_settings_section(
            'transliteration_global',
            __('Global Settings', 'serbian-transliteration'),
            [$this, 'print_global_settings_callback'],
            RSTR_NAME
        );

        add_settings_field(
            'site-script', // ID
            __('My site is', 'serbian-transliteration'), // Title
            [$this, 'site_script_callback'], // Callback,
            RSTR_NAME,
            'transliteration_global'
        );

        add_settings_field(
            'transliteration-mode', // ID
            __('Transliteration Mode', 'serbian-transliteration'), // Title
            [$this, 'transliteration_mode_callback'], // Callback
            RSTR_NAME,
            'transliteration_global'
        );

        add_settings_field(
            'first-visit-mode', // ID
            __('First visit mode', 'serbian-transliteration'), // Title
            [$this, 'first_visit_mode_callback'], // Callback
            RSTR_NAME,
            'transliteration_global'
        );

        add_settings_field(
            'language-scheme', // ID
            __('Language scheme', 'serbian-transliteration'), // Title
            [$this, 'language_callback'], // Callback
            RSTR_NAME,
            'transliteration_global'
        );

        add_settings_field(
            'mode', // ID
            __('Plugin Mode', 'serbian-transliteration'), // Title
            [$this, 'mode_callback'], // Callback
            RSTR_NAME,
            'transliteration_global'
        );

        add_settings_field(
            'permalink-transliteration', // ID
            __('Force transliteration permalinks to latin', 'serbian-transliteration'), // Title
            [$this, 'permalink_transliteration_callback'], // Callback
            RSTR_NAME,
            'transliteration_global'
        );

        /*
         * Filters Settings
         */
        add_settings_section(
            'transliteration_filters',
            __('Filters', 'serbian-transliteration'),
            [$this, 'print_filters_settings_callback'],
            RSTR_NAME
        );

        add_settings_field(
            'transliteration-filter', // ID
            __('Transliteration Filters', 'serbian-transliteration'), // Title
            [$this, 'transliteration_filter_callback'], // Callback
            RSTR_NAME,
            'transliteration_filters'
        );

        add_settings_field(
            'exclude-latin-words', // ID
            __('Exclude Latin words that you do not want to be transliterated to the Cyrillic.', 'serbian-transliteration'), // Title
            [$this, 'exclude_latin_words_callback'], // Callback
            RSTR_NAME,
            'transliteration_filters'
        );

        add_settings_field(
            'exclude-cyrillic-words', // ID
            __('Exclude Cyrillic words that you do not want to be transliterated to the Latin.', 'serbian-transliteration'), // Title
            [$this, 'exclude_cyrillic_words_callback'], // Callback
            RSTR_NAME,
            'transliteration_filters'
        );



        /*
         * Special Settings
         */
        add_settings_section(
            'transliteration_special_settings',
            __('Special Settings', 'serbian-transliteration'),
            [$this, 'print_special_settings_callback'],
            RSTR_NAME
        );

        add_settings_field(
            'cache-support', // ID
            __('Enable cache support', 'serbian-transliteration'), // Title
            [$this, 'cache_support_callback'], // Callback
            RSTR_NAME,
            'transliteration_special_settings'
        );

        add_settings_field(
            'force-widgets', // ID
            __('Force widget transliteration', 'serbian-transliteration'), // Title
            [$this, 'force_widgets_callback'], // Callback
            RSTR_NAME,
            'transliteration_special_settings'
        );

        add_settings_field(
            'force-email-transliteration', // ID
            __('Force e-mail transliteration', 'serbian-transliteration'), // Title
            [$this, 'force_email_transliteration_callback'], // Callback
            RSTR_NAME,
            'transliteration_special_settings'
        );

        add_settings_field(
            'force-rest-api', // ID
            __('Force transliteration for WordPress REST API', 'serbian-transliteration'), // Title
            [$this, 'force_rest_api_callback'], // Callback
            RSTR_NAME,
            'transliteration_special_settings'
        );

        add_settings_field(
            'force-ajax-calls', // ID
            __('Force transliteration for AJAX calls', 'serbian-transliteration') . ' (' . __('EXPERIMENTAL', 'serbian-transliteration') . ')',
            [$this, 'force_ajax_calls_callback'], // Callback
            RSTR_NAME,
            'transliteration_special_settings'
        );



        /*
         * WP Admin Settings
         */
        add_settings_section(
            'transliteration_wp_admin_settings',
            __('WP Admin', 'serbian-transliteration'),
            [$this, 'print_wp_admin_callback'],
            RSTR_NAME
        );

        add_settings_field(
            'avoid-admin', // ID
            __('WP-Admin transliteration', 'serbian-transliteration'), // Title
            [$this, 'avoid_admin_callback'], // Callback
            RSTR_NAME,
            'transliteration_wp_admin_settings'
        );

        add_settings_field(
            'allow-cyrillic-usernames', // ID
            __('Allow Cyrillic Usernames', 'serbian-transliteration'), // Title
            [$this, 'allow_cyrillic_usernames_callback'], // Callback
            RSTR_NAME,
            'transliteration_wp_admin_settings'
        );

        add_settings_field(
            'allow-admin-tools', // ID
            __('Allow Admin Tools', 'serbian-transliteration'), // Title
            [$this, 'allow_admin_tools_callback'], // Callback
            RSTR_NAME,
            'transliteration_wp_admin_settings'
        );



        /*
         * Media Settings
         */
        add_settings_section(
            'transliteration_media_settings',
            __('Media Settings', 'serbian-transliteration'),
            [$this, 'print_media_callback'],
            RSTR_NAME
        );

        add_settings_field(
            'media-transliteration', // ID
            __('Transliterate filenames to latin', 'serbian-transliteration'), // Title
            [$this, 'media_transliteration_callback'], // Callback
            RSTR_NAME,
            'transliteration_media_settings'
        );

        add_settings_field(
            'media-delimiter', // ID
            __('Filename delimiter', 'serbian-transliteration'), // Title
            [$this, 'media_delimiter_callback'], // Callback
            RSTR_NAME,
            'transliteration_media_settings'
        );



        /*
         * Media Settings
         */
        add_settings_section(
            'transliteration_search_settings',
            __('WordPress Search', 'serbian-transliteration'),
            [$this, 'print_search_settings_callback'],
            RSTR_NAME
        );

        add_settings_field(
            'enable-search', // ID
            __('Enable search transliteration', 'serbian-transliteration'), // Title
            [$this, 'enable_search_callback'], // Callback
            RSTR_NAME,
            'transliteration_search_settings'
        );

        if (Transliteration_Utilities::get_locale('sr_RS')) {
            add_settings_field(
                'fix-diacritics', // ID
                __('Fix Diacritics', 'serbian-transliteration'), // Title
                [$this, 'fix_diacritics_callback'], // Callback
                RSTR_NAME,
                'transliteration_search_settings'
            );
        }

        add_settings_field(
            'search-mode', // ID
            __('Search Mode', 'serbian-transliteration'), // Title
            [$this, 'search_mode_callback'], // Callback
            RSTR_NAME,
            'transliteration_search_settings'
        );



        /*
         * SEO
         */
        add_settings_section(
            'transliteration_seo_settings',
            __('SEO Settings', 'serbian-transliteration'),
            [$this, 'print_seo_settings_callback'],
            RSTR_NAME
        );

        add_settings_field(
            'url-selector', // ID
            __('Parameter URL selector', 'serbian-transliteration'), // Title
            [$this, 'parameter_url_selector_callback'], // Callback
            RSTR_NAME,
            'transliteration_seo_settings'
        );

        add_settings_field(
            'enable-rss', // ID
            __('RSS transliteration', 'serbian-transliteration'), // Title
            [$this, 'enable_rss_callback'], // Callback
            RSTR_NAME,
            'transliteration_seo_settings'
        );



        /*
         * Exclusions
         */
        add_settings_section(
            'transliteration_exclusion_settings',
            __('Exclusion', 'serbian-transliteration'),
            [$this, 'print_exclusion_settings_callback'],
            RSTR_NAME
        );

        $installed_languages = get_available_languages();

        foreach ($installed_languages as $locale) {

            $language_name = sprintf(
                __('Language: %s', 'serbian-transliteration'),
                $locale
            );

            add_settings_field(
                'disable-by-language-' . $locale, // ID
                $language_name, // Title
                [$this, 'exclude_language_callback'], // Callback
                RSTR_NAME,
                'transliteration_exclusion_settings',
                [
                    'locale' => $locale,
                    'name'   => $language_name,
                ]
            );
        }


        /*
         * Misc
         */
        add_settings_section(
            'transliteration_misc_settings',
            __('Misc.', 'serbian-transliteration'),
            [$this, 'print_misc_settings_callback'],
            RSTR_NAME
        );

        add_settings_field(
            'enable-body-class', // ID
            __('Enable body class', 'serbian-transliteration'), // Title
            [$this, 'enable_body_class_callback'], // Callback
            RSTR_NAME,
            'transliteration_misc_settings'
        );

        add_settings_field(
            'disable-theme-support', // ID
            __('Theme Support', 'serbian-transliteration'), // Title
            [$this, 'disable_theme_support_callback'], // Callback
            RSTR_NAME,
            'transliteration_misc_settings'
        );



        /*
         * Settings Sidebars
         */
        $sidebars = new Transliteration_Settings_Sidebars();
        $tab      = sanitize_text_field($_GET['tab'] ?? '');

        if ($tab != 'debug') {
            add_meta_box(
                'donations',
                '<span class="dashicons dashicons-heart"></span> <span> ' . __('Support the Project', 'serbian-transliteration'),
                [$sidebars, 'donations'],
                ['transliteration-settings', 'transliteration-tools'],
                'side',
                'default'
            );
        }

        if ($tab != 'documentation') {
            add_meta_box(
                'contributors',
                '<span class="dashicons dashicons-superhero-alt"></span> <span> ' . __('Contributors & Developers', 'serbian-transliteration') . '</span>',
                [$sidebars, 'contributors'],
                ['transliteration-settings', 'transliteration-credits'],
                'side',
                'default'
            );
        }
    }

    /*
     * Section Callbacks
     **********************/
    public function print_global_settings_callback(): void
    {
        printf('<p>%s</p>', __('This setting determines the mode of operation for the Transliteration plugin.', 'serbian-transliteration'));
        printf('<p>%s</p>', __('Carefully choose the option that is best for your site and the plugin will automatically set everything you need for optimal performance.', 'serbian-transliteration'));
    }

    public static function print_filters_settings_callback(): void
    {
        printf('<p>%s</p>', __('This section contains filters for exclusions, allowing you to specify content that should be excluded from transliteration, and also includes a filter to disable certain WordPress filters related to transliteration.', 'serbian-transliteration'));
    }

    public function print_special_settings_callback(): void
    {
        printf('<p>%s</p>', __('These are special settings that can enhance transliteration and are used only if you need them.', 'serbian-transliteration'));
    }

    public function print_wp_admin_callback(): void
    {
        printf('<p>%s</p>', __('These settings apply to the administrative part.', 'serbian-transliteration'));
    }

    public function print_media_callback(): void
    {
        printf('<p>%s</p>', __('Upload, view and control media and files.', 'serbian-transliteration'));
    }

    public function print_seo_settings_callback(): void
    {
        printf('<p>%s</p>', __('Our plugin also has special SEO options that are very important for your project.', 'serbian-transliteration'));
    }

    public function print_exclusion_settings_callback(): void
    {
        printf('<p>%s</p>', __('Within this configuration section, you have the opportunity to customize your experience by selecting the particular languages for which you wish to turn off the transliteration function. This feature is designed to give you greater control and adaptability over how the system handles transliteration, allowing you to disable it for specific languages based on your individual needs or preferences.', 'serbian-transliteration'));
    }

    public function print_misc_settings_callback(): void
    {
        printf('<p>%s</p>', __('Various interesting settings that can be used in the development of your project.', 'serbian-transliteration'));
    }

    /*
     * Settings Callbacks
     **********************/
    public function print_search_settings_callback(): void
    {
        printf('<p>%s</p>', __('This setting determines the search mode within the WordPress core depending on the type of language located in the database.', 'serbian-transliteration'));
        printf('<p>%s</p>', __('The search type setting is mostly experimental and you need to test each variant so you can get the best result you need.', 'serbian-transliteration'));
    }

    public function site_script_callback(): void
    {
        $inputs = [];
        $locale = Transliteration_Utilities::get_locale();

        $checkbox = [
            'cyr' => __('Cyrillic', 'serbian-transliteration'),
            'lat' => __('Latin', 'serbian-transliteration'),
        ];

        if ($locale == 'ar') {
            $checkbox['cyr'] = __('Arabic', 'serbian-transliteration');
        } elseif ($locale == 'hy') {
            $checkbox['cyr'] = __('Armenian', 'serbian-transliteration');
        }

        foreach ($checkbox as $key => $label) {
            $inputs[] = sprintf(
                '<label for="site-script-%1$s" class="label-block"><input type="radio" id="site-script-%1$s" name="%3$s[site-script]" value="%1$s"%4$s> <span>%2$s</span></label>',
                esc_attr($key),
                esc_html($label),
                RSTR_NAME,
                (isset($this->options['site-script']) ? ($this->options['site-script'] == $key ? ' checked' : '') : ($key === 'cyr' ? ' checked' : ''))
            );
        }

        echo implode(' ', $inputs);

        if (count($checkbox) > 1) {
            printf('<p class="description">%1$s</p>', __('Define whether your primary alphabet on the site is Latin or Cyrillic. If the primary alphabet is Cyrillic then choose Cyrillic. If it is Latin, then choose Latin. This option is crucial for the plugin to work properly.', 'serbian-transliteration'));
        } else {
            __('Define whether your primary alphabet on the site.', 'serbian-transliteration');
        }
    }

    /**
     * Transliteration mode
     */
    public function transliteration_mode_callback(): void
    {
        $inputs = [];

        $transliteration_mode = function () {
            $modes = [
                'none'       => __('Transliteration disabled', 'serbian-transliteration'),
                'cyr_to_lat' => __('Cyrillic to Latin', 'serbian-transliteration'),
                'lat_to_cyr' => __('Latin to Cyrillic', 'serbian-transliteration'),
            ];

            $locale = Transliteration_Utilities::get_locale();

            if ($locale == 'ar') {
                $modes['cyr_to_lat'] = __('Arabic to Latin', 'serbian-transliteration');
                $modes['lat_to_cyr'] = __('Latin to Arabic', 'serbian-transliteration');
            } elseif ($locale == 'hy') {
                $modes['cyr_to_lat'] = __('Armenian to Latin', 'serbian-transliteration');
                $modes['lat_to_cyr'] = __('Latin to Armenian', 'serbian-transliteration');
            }

            return apply_filters('rstr_transliteration_mode', $modes);
        };

        foreach ($transliteration_mode() as $key => $label) {
            $inputs[] = sprintf(
                '<label for="transliteration-mode-%1$s" class="label-block"><input type="radio" id="transliteration-mode-%1$s" name="%3$s[transliteration-mode]" value="%1$s" data-nonce="%4$s"%5$s> <span>%2$s</span></label>',
                esc_attr($key),
                esc_html($label),
                RSTR_NAME,
                $this->nonce,
                (isset($this->options['transliteration-mode']) ? ($this->options['transliteration-mode'] == $key ? ' checked' : '') : ($key == 'none' ? ' checked' : ''))
            );
        }

        echo implode(' ', $inputs);
        printf('<p class="description">%1$s</p>', __('This option determines the global transliteration of your web site. If you do not want to transliterate the entire website and use this plugin for other purposes, disable this option. This option does not affect to the functionality of short codes and tags.', 'serbian-transliteration'));
    }

    public function first_visit_mode_callback(): void
    {
        $inputs = [];

        foreach ([
        //	'auto' => __('Auto (Based on Transliteration Mode)', 'serbian-transliteration'),
            'lat' => __('Latin', 'serbian-transliteration'),
            'cyr' => __('Cyrillic', 'serbian-transliteration'),
        ] as $key => $label) {
            $inputs[] = sprintf(
                '<label for="first-visit-mode-%1$s" class="label-block"><input type="radio" id="first-visit-mode-%1$s" name="%3$s[first-visit-mode]" value="%1$s"%4$s> <span>%2$s</span></label>',
                esc_attr($key),
                esc_html($label),
                RSTR_NAME,
                (isset($this->options['first-visit-mode']) ? ($this->options['first-visit-mode'] == $key ? ' checked' : '') : ($key === 'lat' ? ' checked' : ''))
            );
        }
        printf('%1$s<p class="description">%2$s</p>', implode(' ', $inputs), __('This option determines the type of language script that the visitors sees when they first time come to your site.', 'serbian-transliteration'));
    }

    /**
     * Transliteration language
     */
    public function language_callback(): void
    {
        $inputs    = [];
        $languages = array_merge(['auto' => __('Automatical', 'serbian-transliteration')], Transliteration_Utilities::registered_languages());

        if (!isset($this->options['language-scheme'])) {
            $this->options['language-scheme'] = (
                in_array(
                    Transliteration_Utilities::get_locale(),
                    array_keys(Transliteration_Utilities::registered_languages())
                ) ? Transliteration_Utilities::get_locale() : 'auto'
            );
        }

        foreach ($languages as $locale => $label) {
            $inputs[] = sprintf(
                '<option value="%1$s"%3$s>%2$s</option>',
                $locale,
                esc_html($label) . ($locale != 'auto' ? sprintf(' (%s)', $locale) : ''),
                (isset($this->options['language-scheme']) ? ($this->options['language-scheme'] == $locale ? ' selected' : '') : ($locale == 'auto' ? ' selected' : ''))
            );
        }
        echo '<select name="serbian-transliteration[language-scheme]" id="serbian-transliteration-language-scheme" data-nonce="' . esc_attr($this->nonce) . '" style="margin-bottom:5px;">' . implode(' ', $inputs) . '</select>';

        printf('<p class="description">%1$s</p>', __('This option defines the language script. Automatic script detection is the best way but if you are using a WordPress installation in a language that does not match the scripts supported by this plugin, then choose on which script you want the transliteration to be performed.', 'serbian-transliteration'));
    }

    /**
     * Plugin mode
     */
    public function mode_callback(): void
    {
        $inputs = [];

        foreach (Transliteration_Utilities::plugin_mode() as $key => $label) {
            $inputs[] = sprintf(
                '<label for="mode-%1$s" class="label-block"><input type="radio" id="mode-%1$s" name="%3$s[mode]" value="%1$s" data-nonce="%4$s"%5$s> <span>%2$s</span></label>',
                esc_attr($key),
                esc_html($label),
                RSTR_NAME,
                $this->nonce,
                (isset($this->options['mode']) ? ($this->options['mode'] == $key ? ' checked' : '') : ($key == 'light' ? ' checked' : ''))
            );
        }

        printf(
            '<div%3$s id="rstr-mode-list">%1$s%2$s<p class="description info" id="forced-transliteration" style="display:none; ">%3$s</p></div>',
            implode(' ', $inputs),
            sprintf(
                '<p class="description">%1$s</p>',
                __("This option configures the operating mode of the entire plugin and affects all aspects of the site related to transliteration. Each mode has its own set of filters, which are activated based on your specific needs. It's important to take the time to review and customize these settings according to your preferences.", 'serbian-transliteration')
            ),
            __('Forced transliteration can sometimes cause problems if Latin is translated into Cyrillic in pages and posts. To this combination must be approached experimentally.', 'serbian-transliteration'),
            (get_rstr_option('mode') === 'woocommerce' && RSTR_WOOCOMMERCE === false ? ' class="required-box"' : '')
        );
    }

    /**
     * Transliteration filter
     */
    public function transliteration_filter_callback(): void
    {
        ?>
<div class="accordion-container">
	<button class="accordion-link" type="button"><?php esc_html_e("Exclude filters you don't need (optional)", 'serbian-transliteration'); ?></button>
	<div class="accordion-panel">
		<?php

                printf(
                    '<p>%s<br><b>%s</b></p><br>',
                    __('Select the transliteration filters you want to exclude.', 'serbian-transliteration'),
                    __('The filters you select here will not be transliterated (these filters do not work on forced transliteration).', 'serbian-transliteration')
                );

        $list = array_keys(Transliteration_Mode::get()->filters());
        sort($list);

        ?>
        <div class="row" id="rstr-filter-mode-options">
			<?php $this->private___list_filter_mode_options($list); ?>
        </div>
		<?php
        printf(
            '<br><p><b>%s</b><br>%s %s</p>',
            __('TIPS & TRICKS:', 'serbian-transliteration'),
            __('You can find details about some of the listed filters in this article:', 'serbian-transliteration'),
            '<a href="https://codex.wordpress.org/Plugin_API/Filter_Reference" target="_blank">Plugin_API/Filter_Reference</a>'
        );

        if (RSTR_WOOCOMMERCE) {
            printf(
                '<p>%s</p>',
                sprintf(
                    __('Since you are already a WooCommerce user, you also can see the following documentation: %s', 'serbian-transliteration'),
                    '<a href="https://docs.woocommerce.com/documentation/plugins/woocommerce/woocommerce-codex/snippets/" target="_blank">WooCommerce/Codex Snippets</a>'
                )
            );
        }

        ?>
	</div>
</div>
<?php
    }

    public function exclude_latin_words_callback(): void
    {
        printf(
            '<textarea name="%1$s[exclude-latin-words]" id="%1$s-exclude-latin-words" rows="5" style="width:100%%">%2$s</textarea>',
            RSTR_NAME,
            (isset($this->options['exclude-latin-words']) ? esc_html($this->options['exclude-latin-words']) : '')
        );
        printf('<p class="description">%1$s</p>', sprintf(__('Separate words, phrases, names and expressions with the sign %s or put it in a new row. HTML is not allowed.', 'serbian-transliteration'), '<code>|</code>'));
    }

    public function exclude_cyrillic_words_callback(): void
    {
        printf(
            '<textarea name="%1$s[exclude-cyrillic-words]" id="%1$s-exclude-cyrillic-words" rows="5" style="width:100%%">%2$s</textarea>',
            RSTR_NAME,
            (isset($this->options['exclude-cyrillic-words']) ? esc_html($this->options['exclude-cyrillic-words']) : '')
        );
        printf('<p class="description">%1$s</p>', sprintf(__('Separate words, phrases, names and expressions with the sign %s or put it in a new row. HTML is not allowed.', 'serbian-transliteration'), '<code>|</code>'));
    }

    public function cache_support_callback(): void
    {
        $inputs = [];

        foreach ([
            'yes' => __('Yes', 'serbian-transliteration'),
            'no'  => __('No', 'serbian-transliteration'),
        ] as $key => $label) {
            $inputs[] = sprintf(
                '<label for="cache-support-%1$s"><input type="radio" id="cache-support-%1$s" name="%3$s[cache-support]" value="%1$s"%4$s%5$s> <span>%2$s</span></label>',
                esc_attr($key),
                esc_html($label),
                RSTR_NAME,
                (isset($this->options['cache-support']) && $this->options['cache-support'] == $key ? ' checked' : (!isset($this->options['cache-support']) && $key === 'yes' ? ' checked' : '')),
                ''
            );
        }
        printf('%1$s<p class="description">%2$s</p>', implode(' ', $inputs), __('If you have a problem caching your pages, our plugin solves this problem by clearing the cache when changing the language script.', 'serbian-transliteration'));
    }

    public function force_widgets_callback(): void
    {
        $inputs = [];

        foreach ([
            'yes' => __('Yes', 'serbian-transliteration'),
            'no'  => __('No', 'serbian-transliteration'),
        ] as $key => $label) {
            $inputs[] = sprintf(
                '<label for="force-widgets-%1$s"><input type="radio" id="force-widgets-%1$s" name="%3$s[force-widgets]" value="%1$s"%4$s%5$s> <span>%2$s</span></label>',
                esc_attr($key),
                esc_html($label),
                RSTR_NAME,
                (isset($this->options['force-widgets']) && $this->options['force-widgets'] == $key ? ' checked' : (!isset($this->options['force-widgets']) && $key === 'no' ? ' checked' : '')),
                ''
            );
        }
        printf('%1$s<p class="description">%2$s</p>', implode(' ', $inputs), __('This option forces the widget to transliterate. There may be some unusual behaviour in the rare cases.', 'serbian-transliteration'));
    }

    public function force_email_transliteration_callback(): void
    {
        $inputs = [];

        foreach ([
            'yes' => __('Yes', 'serbian-transliteration'),
            'no'  => __('No', 'serbian-transliteration'),
        ] as $key => $label) {
            $inputs[] = sprintf(
                '<label for="force-email-transliteration-%1$s"><input type="radio" id="force-email-transliteration-%1$s" name="%3$s[force-email-transliteration]" value="%1$s"%4$s> <span>%2$s</span></label>',
                esc_attr($key),
                esc_html($label),
                RSTR_NAME,
                (isset($this->options['force-email-transliteration']) ? ($this->options['force-email-transliteration'] == $key ? ' checked' : '') : ($key === 'no' ? ' checked' : ''))
            );
        }
        printf('%1$s<p class="description">%2$s</p>', implode(' ', $inputs), __('Enable this feature if you want to force transliteration of email content.', 'serbian-transliteration'));
    }

    public function force_rest_api_callback(): void
    {
        $inputs = [];

        foreach ([
            'yes' => __('Yes', 'serbian-transliteration'),
            'no'  => __('No', 'serbian-transliteration'),
        ] as $key => $label) {
            $inputs[] = sprintf(
                '<label for="force-rest-api-%1$s"><input type="radio" id="force-rest-api-%1$s" name="%3$s[force-rest-api]" value="%1$s"%4$s> <span>%2$s</span></label>',
                esc_attr($key),
                esc_html($label),
                RSTR_NAME,
                (isset($this->options['force-rest-api']) ? ($this->options['force-rest-api'] == $key ? ' checked' : '') : ($key === 'yes' ? ' checked' : ''))
            );
        }
        printf('%1$s<p class="description">%2$s</p>', implode(' ', $inputs), __('Enable this feature if you want to force transliteration of WordPress REST API calls. The WordPress REST API is also used in many AJAX calls, WooCommerce, and page builders. It is recommended to be enabled by default.', 'serbian-transliteration'));
    }

    public function force_ajax_calls_callback(): void
    {
        $inputs = [];

        foreach ([
            'yes' => __('Yes', 'serbian-transliteration'),
            'no'  => __('No', 'serbian-transliteration'),
        ] as $key => $label) {
            $inputs[] = sprintf(
                '<label for="force-ajax-calls-%1$s"><input type="radio" id="force-ajax-calls-%1$s" name="%3$s[force-ajax-calls]" value="%1$s"%4$s> <span>%2$s</span></label>',
                esc_attr($key),
                esc_html($label),
                RSTR_NAME,
                (isset($this->options['force-ajax-calls']) ? ($this->options['force-ajax-calls'] == $key ? ' checked' : '') : ($key === 'no' ? ' checked' : ''))
            );
        }
        printf('%1$s<p class="description">%2$s</p>', implode(' ', $inputs), sprintf(__('Enable this feature if you want to force transliteration of AJAX calls. If you want to avoid transliteration of specific individual AJAX calls, you must add a new POST or GET parameter to your AJAX call: %s', 'serbian-transliteration'), '<code>rstr_skip=true</code>'));
    }

    /**
     * Avoid admin
     */
    public function avoid_admin_callback(): void
    {
        $inputs = [];

        foreach ([
            'no'  => __('Yes', 'serbian-transliteration'),
            'yes' => __('No', 'serbian-transliteration'),
        ] as $key => $label) {
            $inputs[] = sprintf(
                '<label for="avoid-admin-%1$s"><input type="radio" id="avoid-admin-%1$s" name="%3$s[avoid-admin]" value="%1$s"%4$s> <span>%2$s</span></label>',
                esc_attr($key),
                esc_html($label),
                RSTR_NAME,
                (isset($this->options['avoid-admin']) ? ($this->options['avoid-admin'] == $key ? ' checked' : '') : ($key === 'yes' ? ' checked' : ''))
            );
        }

        printf('%1$s<p class="description">%2$s</p>', implode(' ', $inputs), __('Enable if you want the WP-Admin area to be transliterated.', 'serbian-transliteration'));
    }

    public function allow_cyrillic_usernames_callback(): void
    {
        $inputs = [];

        foreach ([
            'yes' => __('Yes', 'serbian-transliteration'),
            'no'  => __('No', 'serbian-transliteration'),
        ] as $key => $label) {
            $inputs[] = sprintf(
                '<label for="enable-search-%1$s"><input type="radio" id="enable-search-%1$s" name="%3$s[allow-cyrillic-usernames]" value="%1$s"%4$s> <span>%2$s</span></label>',
                esc_attr($key),
                esc_html($label),
                RSTR_NAME,
                (isset($this->options['allow-cyrillic-usernames']) ? ($this->options['allow-cyrillic-usernames'] == $key ? ' checked' : '') : ($key === 'no' ? ' checked' : ''))
            );
        }
        printf('%1$s<p class="description">%2$s</p>', implode(' ', $inputs), __('Allows to create users with usernames containing Cyrillic characters.', 'serbian-transliteration'));
    }

    public function allow_admin_tools_callback(): void
    {
        $inputs = [];

        foreach ([
            'yes' => __('Yes', 'serbian-transliteration'),
            'no'  => __('No', 'serbian-transliteration'),
        ] as $key => $label) {
            $inputs[] = sprintf(
                '<label for="enable-search-%1$s"><input type="radio" id="enable-search-%1$s" name="%3$s[allow-admin-tools]" value="%1$s"%4$s> <span>%2$s</span></label>',
                esc_attr($key),
                esc_html($label),
                RSTR_NAME,
                (isset($this->options['allow-admin-tools']) ? ($this->options['allow-admin-tools'] == $key ? ' checked' : '') : ($key === 'yes' ? ' checked' : ''))
            );
        }
        printf('%1$s<p class="description">%2$s</p>', implode(' ', $inputs), __('This feature enables you to easily transliterate titles and content directly within the WordPress editor. This functionality is available for various content types, including categories, pages, posts, and custom post types, ensuring a seamless experience when managing multilingual content on your site. With just a few clicks, you can switch between scripts, making your content accessible to a broader audience.', 'serbian-transliteration'));
    }

    /**
     * Force transliteration permalinks to latin
     */
    public function permalink_transliteration_callback(): void
    {
        $inputs = [];

        foreach ([
            'yes' => __('Yes', 'serbian-transliteration'),
            'no'  => __('No', 'serbian-transliteration'),
        ] as $key => $label) {
            $inputs[] = sprintf(
                '<label for="permalink-transliteration-%1$s"><input type="radio" id="permalink-transliteration-%1$s" name="%3$s[permalink-transliteration]" value="%1$s"%4$s> <span>%2$s</span></label>',
                esc_attr($key),
                esc_html($label),
                RSTR_NAME,
                (isset($this->options['permalink-transliteration']) && $this->options['permalink-transliteration'] == $key ? ' checked' : ($key === 'yes' ? ' checked' : ''))
            );
        }
        printf('%1$s<p class="description">%2$s</p>', implode(' ', $inputs), __('Enable if you want to force cyrillic permalinks to latin.', 'serbian-transliteration'));
    }

    /**
     * Transliterate filenames to latin
     */
    public function media_transliteration_callback(): void
    {
        $inputs = [];

        foreach ([
            'yes' => __('Yes', 'serbian-transliteration'),
            'no'  => __('No', 'serbian-transliteration'),
        ] as $key => $label) {
            $inputs[] = sprintf(
                '<label for="media-transliteration-%1$s"><input type="radio" id="media-transliteration-%1$s" name="%3$s[media-transliteration]" value="%1$s"%4$s> <span>%2$s</span></label>',
                esc_attr($key),
                esc_html($label),
                RSTR_NAME,
                (isset($this->options['media-transliteration']) ? ($this->options['media-transliteration'] == $key ? ' checked' : '') : ($key === 'yes' ? ' checked' : ''))
            );
        }
        printf('%1$s<p class="description">%2$s</p>', implode(' ', $inputs), __('Enable if you want to convert cyrillic filenames to latin.', 'serbian-transliteration'));
    }

    /**
     * Filename delimiter. Example: `my-upload-file.jpg`
     */
    public function media_delimiter_callback(): void
    {
        $inputs = [];

        foreach ([
            '-' => '- (' . __('hyphen (default)', 'serbian-transliteration') . ')',
            '_' => '_  (' . __('underscore', 'serbian-transliteration') . ')',
            '.' => '. (' . __('dot', 'serbian-transliteration') . ')',
            '~' => '~ (' . __('tilde', 'serbian-transliteration') . ')',
            '|' => '| (' . __('vartical bar', 'serbian-transliteration') . ')',
            '*' => '* (' . __('asterisk', 'serbian-transliteration') . ')',
        ] as $label => $name) {
            $inputs[] = sprintf(
                '<option value="%1$s"%3$s>%2$s</option>',
                $label,
                $name,
                (isset($this->options['media-delimiter']) ? ($this->options['media-delimiter'] == $label ? ' selected' : '') : ($label === '-' ? ' selected' : ''))
            );
        }
        echo '<select name="serbian-transliteration[media-delimiter]" id="serbian-transliteration-media-delimiter" data-nonce="' . esc_attr($this->nonce) . '" style="margin-bottom:5px;">' . implode(' ', $inputs) . '</select>';

        printf('<p class="description">%1$s <code>%2$s</code></p>', __('Filename delimiter, example:', 'serbian-transliteration'), __('my-upload-file.jpg', 'serbian-transliteration'));
    }

    public function enable_search_callback(): void
    {
        $inputs = [];

        foreach ([
            'yes' => __('Yes', 'serbian-transliteration'),
            'no'  => __('No', 'serbian-transliteration'),
        ] as $key => $label) {
            $inputs[] = sprintf(
                '<label for="enable-search-%1$s"><input type="radio" id="enable-search-%1$s" name="%3$s[enable-search]" value="%1$s"%4$s> <span>%2$s</span></label>',
                esc_attr($key),
                esc_html($label),
                RSTR_NAME,
                (isset($this->options['enable-search']) ? ($this->options['enable-search'] == $key ? ' checked' : '') : ($key === 'no' ? ' checked' : ''))
            );
        }
        printf('%1$s<p class="description">%2$s</p>', implode(' ', $inputs), __('Approve if you want transliteration for the search field.', 'serbian-transliteration'));
    }

    public function fix_diacritics_callback(): void
    {
        $inputs = [];

        foreach ([
            'yes' => __('Yes', 'serbian-transliteration'),
            'no'  => __('No', 'serbian-transliteration'),
        ] as $key => $label) {
            $inputs[] = sprintf(
                '<label for="fix-diacritics-%1$s"><input type="radio" id="fix-diacritics-%1$s" name="%3$s[fix-diacritics]" value="%1$s"%4$s> <span>%2$s</span></label>',
                esc_attr($key),
                esc_html($label),
                RSTR_NAME,
                (isset($this->options['fix-diacritics']) ? ($this->options['fix-diacritics'] == $key ? ' checked' : '') : ($key === 'no' ? ' checked' : ''))
            );
        }
        printf('%1$s<p class="description">%2$s</p>', implode(' ', $inputs), __('Try to fix the diacritics in the search field.', 'serbian-transliteration'));
    }

    public function search_mode_callback(): void
    {
        $inputs = [];

        foreach ([
            'auto'        => __('Automatical (recommended)', 'serbian-transliteration'),
            'plugin-mode' => __('Based on the plugin mode', 'serbian-transliteration'),
        ] as $key => $label) {
            $inputs[] = sprintf(
                '<label for="search-mode-%1$s" class="label-block"><input type="radio" id="search-mode-%1$s" name="%3$s[search-mode]" value="%1$s"%4$s> <span>%2$s</span></label>',
                esc_attr($key),
                esc_html($label),
                RSTR_NAME,
                (isset($this->options['search-mode']) ? ($this->options['search-mode'] == $key ? ' checked' : '') : ($key === 'auto' ? ' checked' : ''))
            );
        }
        printf('%1$s<p class="description">%2$s</p>', implode(' ', $inputs), __('The search has two working modes. Choose the one that works best with your search.', 'serbian-transliteration'));
    }

    public function parameter_url_selector_callback(): void
    {
        $inputs = [];

        foreach ([
            'rstr'        => '?<b>rstr</b>=lat ' . __('(safe)', 'serbian-transliteration'),
            'script'      => '?<b>script</b>=lat ' . __('(standard)', 'serbian-transliteration'),
            'lang_script' => '?<b>lang_script</b>=lat ' . __('(optional)', 'serbian-transliteration'),
            'letter'      => '?<b>letter</b>=lat ' . __('(optional)', 'serbian-transliteration'),
            'translt'     => '?<b>translt</b>=lat ' . __('(optional)', 'serbian-transliteration'),
            'skripta'     => '?<b>skripta</b>=lat ' . __('(optional)', 'serbian-transliteration'),
            'pismo'       => '?<b>pismo</b>=lat ' . __('(optional)', 'serbian-transliteration'),
        ] as $key => $label) {
            $inputs[] = sprintf(
                '<label for="url-selector-%1$s" class="label-block"><input type="radio" id="url-selector-%1$s" name="%3$s[url-selector]" value="%1$s"%4$s> <span>%2$s</span></label>',
                esc_attr($key),
                $label,
                RSTR_NAME,
                (isset($this->options['url-selector']) ? ($this->options['url-selector'] == $key ? ' checked' : '') : ($key === 'rstr' ? ' checked' : ''))
            );
        }
        printf('%1$s<p class="description">%2$s</p>', implode(' ', $inputs), __('This option dictates which URL parameter will be used to change the language.', 'serbian-transliteration'));
    }

    public function enable_rss_callback(): void
    {
        $inputs = [];

        foreach ([
            'yes' => __('Yes', 'serbian-transliteration'),
            'no'  => __('No', 'serbian-transliteration'),
        ] as $key => $label) {
            $inputs[] = sprintf(
                '<label for="enable-rss-%1$s"><input type="radio" id="enable-rss-%1$s" name="%3$s[enable-rss]" value="%1$s"%4$s%5$s> <span>%2$s</span></label>',
                esc_attr($key),
                esc_html($label),
                RSTR_NAME,
                (isset($this->options['enable-rss']) && $this->options['enable-rss'] == $key ? ' checked' : (!isset($this->options['enable-rss']) && $key === 'no' ? ' checked' : '')),
                ''
            );
        }
        printf('%1$s<p class="description">%2$s</p>', implode(' ', $inputs), __('This option transliterate the RSS feed.', 'serbian-transliteration'));
    }

    public function exclude_language_callback(array $attr=[]): void
    {
        foreach ([
            'no'  => __('Transliterate', 'serbian-transliteration'),
            'yes' => __('Omit the transliteration', 'serbian-transliteration'),
        ] as $key => $label) {
            $inputs[] = sprintf(
                '<label for="disable-by-language-%1$s-%5$s"><input type="radio" id="disable-by-language-%1$s-%5$s" name="%3$s[disable-by-language][%5$s]" value="%1$s"%4$s> <span>%2$s</span></label>',
                esc_attr($key),
                esc_html($label),
                RSTR_NAME,
                (isset($this->options['disable-by-language'][$attr['locale']]) ? ($this->options['disable-by-language'][$attr['locale']] == $key ? ' checked' : '') : ($key === 'no' ? ' checked' : '')),
                $attr['locale']
            );
        }

        echo implode(' ', $inputs);
    }

    public function enable_body_class_callback(): void
    {
        $inputs = [];

        foreach ([
            'yes' => __('Yes', 'serbian-transliteration'),
            'no'  => __('No', 'serbian-transliteration'),
        ] as $key => $label) {
            $inputs[] = sprintf(
                '<label for="enable-body-class-%1$s"><input type="radio" id="enable-body-class-%1$s" name="%3$s[enable-body-class]" value="%1$s"%4$s> <span>%2$s</span></label>',
                esc_attr($key),
                esc_html($label),
                RSTR_NAME,
                (isset($this->options['enable-body-class']) ? ($this->options['enable-body-class'] == $key ? ' checked' : '') : ($key === 'no' ? ' checked' : ''))
            );
        }
        printf('%1$s<p class="description">%2$s</p>', implode(' ', $inputs), __('This option adds CSS classes to your body HTML tag. These CSS classes vary depending on the language script.', 'serbian-transliteration'));
    }

    public function disable_theme_support_callback(): void
    {
        $inputs = [];

        foreach ([
            'no'  => __('Enable Theme Support', 'serbian-transliteration'),
            'yes' => __('Disable Theme Support', 'serbian-transliteration'),
        ] as $key => $label) {
            $inputs[] = sprintf(
                '<label for="disable-theme-support-%1$s"><input type="radio" id="disable-theme-support-%1$s" name="%3$s[disable-theme-support]" value="%1$s"%4$s> <span>%2$s</span></label>',
                esc_attr($key),
                esc_html($label),
                RSTR_NAME,
                (isset($this->options['disable-theme-support']) ? ($this->options['disable-theme-support'] == $key ? ' checked' : '') : ($key === 'no' ? ' checked' : ''))
            );
        }
        printf('%1$s<p class="description">%2$s</p>', implode(' ', $inputs), __("If you don't require transliteration support for your theme, you can disable it for your current theme here.", 'serbian-transliteration'));
    }

    public function ajax__rstr_filter_mode_options(): void
    {
        $mode = sanitize_text_field($_POST['mode']);

        if ($mode && isset($_REQUEST['nonce']) && wp_verify_nonce(sanitize_text_field($_REQUEST['nonce']), 'rstr-options') !== false) {
            $this->options['mode'] = $mode;

            $modeInstance = Transliteration_Mode::get()->mode($mode);
            $list         = $modeInstance::get()->filters();
            if (empty($list)) {
                $list = [];
            }

            $list = apply_filters('transliteration_mode_filters', $list);
            $list = apply_filters('transliteration_mode_filters_' . $modeInstance::MODE, $list);

            $list = apply_filters_deprecated('rstr/transliteration/exclude/filters', [$list], '2.0.0', 'transliteration_mode_filters');
            $list = apply_filters_deprecated('rstr/transliteration/exclude/filters/' . $modeInstance::MODE, [$list], '2.0.0', 'transliteration_mode_filters_' . $modeInstance::MODE);


            if (!empty($list)) {
                $list = array_keys($list);
                sort($list);
            } else {
                $list = [];
            }

            $this->private___list_filter_mode_options($list, $this->options);
        }
        exit;
    }

    /* PRIVATE - List filter mode options in the AJAX and admin */
    private function private___list_filter_mode_options($list, $options = []): void
    {
        $inputs = [];
        $i      = 0;

        if (empty($options)) {
            $options = $this->options;
        }

        $only_woo = false;
        if (RSTR_WOOCOMMERCE && $options['mode'] == 'woocommerce') {
            $only_woo = true;
        }

        if (empty($list)) {
            printf('<div class="col" style="color:#cc0000;">%s</div>', __('This mode has no filters.', 'serbian-transliteration'));
        }

        foreach ($list as $hook) {
            if ($only_woo && strpos($hook ?? '', 'woo') === false) {
                continue;
            }

            $inputs[$i][] = sprintf(
                '<p><label for="transliteration-filter-%1$s"><input type="checkbox" id="transliteration-filter-%1$s" name="%3$s[transliteration-filter][]" value="%1$s" data-nonce="%4$s"%5$s> <span>%2$s</span></label></p>',
                esc_attr($hook),
                esc_html($hook),
                RSTR_NAME,
                $this->nonce,
                (isset($options['transliteration-filter']) ? (is_array($options['transliteration-filter']) && in_array($hook, $options['transliteration-filter']) ? ' checked' : '') : '')
            );

            if ($i === 2) {
                $i = 0;
            } else {
                ++$i;
            }
        }

        foreach ($inputs as $options) {
            printf('<div class="col">%s</div>', implode(PHP_EOL, $options));
        }
    }
}
