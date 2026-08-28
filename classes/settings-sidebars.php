<?php if (!defined('WPINC')) {
    die();
}

class Transliteration_Settings_Sidebars
{
    private const USEFUL_PLUGIN_SLUGS = [
        'aiviso-ai-image-disclosure',
        'markuclean-markup-cleaner',
        'easy-auto-reload',
        'cf-geoplugin',
        'admin-category-filter',
        'cyr3lat',
        'onionify',
    ];

    private const USEFUL_PLUGINS_CACHE_KEY = 'rstr_useful_plugins';
    private const USEFUL_PLUGINS_CACHE_TTL = 43200;

    public function donations(): void
    {
        ?>
		<?php printf('<p>%s</p>', __('Transliterator is free to use and actively maintained. Ongoing updates, performance improvements, and new features require continuous time and resources.', 'serbian-transliteration')); ?>
		<?php printf('<p>%s</p>', __('If the plugin adds value to your work, you are welcome to support its further development with a voluntary contribution.', 'serbian-transliteration')); /*?>
		<p><a href="https://www.buymeacoffee.com/ivijanstefan" target="_blank"><img src="https://img.buymeacoffee.com/button-api/?text=<?php esc_attr_e('Buy me a coffee', 'serbian-transliteration'); ?>&emoji=&slug=ivijanstefan&button_colour=FFDD00&font_colour=000000&font_family=Bree&outline_colour=000000&coffee_colour=ffffff" /></a></p>
		*/ ?>
		<hr>
		<ul>
			<?php printf(
				'<li>%s: <br><b>%s</b><br>IBAN: <b>%s</b><br>Swift: <b>%s</b></li>',
				__('Banca Intesa a.d. Beograd', 'serbian-transliteration'),
				'160-6000002167503-32',
				'RS35160600000216750332',
				'DBDBRSBG'
			); ?>
			<?php /* printf('<li><b>%s</b>: %s</li>', esc_html__('PayPal', 'serbian-transliteration'), 'creativform@gmail.com');*/ ?>
		</ul>
		<hr>
		<?php printf('<p>%s</p>', __('Thank you for your support.', 'serbian-transliteration'));
    }

    public function contributors(): void
    {
        if ($plugin_info = Transliteration_Utilities::plugin_info(['contributors' => true, 'donate_link' => true])) : ?>
		<div class="rstr-inside-metabox flex">
			<?php foreach ($plugin_info->contributors as $username => $info) : $info = (object) $info; $avatar_url = add_query_arg('d', 'mp', $info->avatar); ?>
			<div class="contributor contributor-<?php echo esc_attr($username); ?>" id="contributor-<?php echo esc_attr($username); ?>">
				<a href="<?php echo esc_url($info->profile); ?>" target="_blank">
					<img src="<?php echo esc_url($avatar_url); ?>">
					<h3><?php echo esc_html($info->display_name); ?></h3>
				</a>
			</div>
			<?php endforeach; ?>
		</div>
		<div class="rstr-inside-metabox">
			<?php printf('<p>%s</p>', sprintf(__('If you want to support our work and effort, if you have new ideas or want to improve the existing code, %s.', 'serbian-transliteration'), '<a href="https://github.com/CreativForm/serbian-transliteration" target="_blank">' . __('join our team', 'serbian-transliteration') . '</a>')); ?>
			<?php /* printf('<p>%s</p>', sprintf(__('If you want to help further plugin development, you can also %s.', 'serbian-transliteration'), '<a href="' . esc_url($plugin_info->donate_link) . '" target="_blank">' . __('donate something for effort', 'serbian-transliteration') . '</a>')); */ ?>
		</div>
		<?php endif;
    }

    /**
     * Render WordPress.org plugin recommendations in the settings sidebar.
     */
    public function more_useful_plugins(): void
    {
        $plugins = $this->get_useful_plugins();
        ?>
        <p><?php esc_html_e('Discover a few other free WordPress plugins you may find useful.', 'serbian-transliteration'); ?></p>
        <div class="rstr-useful-plugins">
            <?php foreach ($plugins as $plugin) : ?>
                <div class="rstr-useful-plugin">
                    <div class="rstr-useful-plugin__icon-wrap">
                        <?php if ($plugin['icon_url'] !== '') : ?>
                            <img
                                class="rstr-useful-plugin__icon"
                                src="<?php echo esc_url($plugin['icon_url']); ?>"
                                alt=""
                                loading="lazy"
                                decoding="async"
                            >
                        <?php else : ?>
                            <span class="dashicons dashicons-admin-plugins rstr-useful-plugin__placeholder" aria-hidden="true"></span>
                        <?php endif; ?>
                    </div>
                    <div class="rstr-useful-plugin__content">
                        <h3><?php echo esc_html($plugin['name']); ?></h3>
                        <?php if ($plugin['short_description'] !== '') : ?>
                            <p class="rstr-useful-plugin__description"><?php echo esc_html($plugin['short_description']); ?></p>
                        <?php endif; ?>
                        <?php if ($plugin['rating'] > 0 || $plugin['active_installs'] > 0) : ?>
                            <p class="rstr-useful-plugin__meta">
                                <?php if ($plugin['rating'] > 0) : ?>
                                    <span>
                                        <span class="screen-reader-text"><?php esc_html_e('Rating', 'serbian-transliteration'); ?>:</span>
                                        <?php echo esc_html(number_format_i18n($plugin['rating'] / 20, 1) . '/5'); ?>
                                    </span>
                                <?php endif; ?>
                                <?php if ($plugin['active_installs'] > 0) : ?>
                                    <span>
                                        <span class="screen-reader-text"><?php esc_html_e('Active installs', 'serbian-transliteration'); ?>:</span>
                                        <?php echo esc_html(number_format_i18n($plugin['active_installs']) . '+'); ?>
                                    </span>
                                <?php endif; ?>
                            </p>
                        <?php endif; ?>
                        <p class="rstr-useful-plugin__action">
                            <a href="<?php echo esc_url($plugin['plugin_url']); ?>" target="_blank" rel="noopener noreferrer">
                                <?php esc_html_e('View plugin', 'serbian-transliteration'); ?>
                            </a>
                        </p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
    }

    /**
     * Retrieve, validate, and cache the fixed WordPress.org recommendations.
     *
     * @return array<int, array{slug:string,name:string,short_description:string,plugin_url:string,icon_url:string,rating:int,active_installs:int}>
     */
    private function get_useful_plugins(): array
    {
        $cached = get_transient(self::USEFUL_PLUGINS_CACHE_KEY);
        if (is_array($cached)) {
            $plugins = $this->normalize_useful_plugins($cached);
            if (count($plugins) === count(self::USEFUL_PLUGIN_SLUGS)) {
                return $plugins;
            }
        }

        $plugins = $this->fetch_useful_plugins();
        $plugins = $this->complete_useful_plugins($plugins);

        set_transient(self::USEFUL_PLUGINS_CACHE_KEY, $plugins, self::USEFUL_PLUGINS_CACHE_TTL);

        return $plugins;
    }

    /**
     * Fetch the requested plugin details from the WordPress.org API.
     *
     * @return array<int, array{slug:string,name:string,short_description:string,plugin_url:string,icon_url:string,rating:int,active_installs:int}>
     */
    private function fetch_useful_plugins(): array
    {
        if (!function_exists('plugins_api')) {
            $plugin_install_file = ABSPATH . 'wp-admin/includes/plugin-install.php';
            if (is_readable($plugin_install_file)) {
                require_once $plugin_install_file;
            }
        }

        if (!function_exists('plugins_api')) {
            return [];
        }

        $plugins = [];
        foreach (self::USEFUL_PLUGIN_SLUGS as $slug) {
            $response = plugins_api('plugin_information', [
                'slug'   => $slug,
                'locale' => get_user_locale(),
                'is_ssl' => is_ssl(),
                'fields' => [
                    'short_description' => true,
                    'rating'            => true,
                    'icons'             => true,
                    'active_installs'   => true,
                    'sections'          => false,
                    'description'       => false,
                    'banners'           => false,
                    'contributors'      => false,
                    'versions'          => false,
                ],
            ]);

            if (is_wp_error($response) || (!is_array($response) && !is_object($response))) {
                continue;
            }

            $plugin = $this->normalize_useful_plugin($slug, (array) $response);
            if ($plugin !== null) {
                $plugins[] = $plugin;
            }
        }

        return $plugins;
    }

    /**
     * Validate an API response record before it reaches the admin screen.
     *
     * @param array<string, mixed> $data API response data.
     * @return array{slug:string,name:string,short_description:string,plugin_url:string,icon_url:string,rating:int,active_installs:int}|null
     */
    private function normalize_useful_plugin(string $slug, array $data): ?array
    {
        if (isset($data['slug']) && (!is_scalar($data['slug']) || sanitize_key((string) $data['slug']) !== $slug)) {
            return null;
        }

        if (!isset($data['name']) || !is_scalar($data['name'])) {
            return null;
        }

        $name = sanitize_text_field(wp_strip_all_tags((string) $data['name']));
        if ($name === '') {
            return null;
        }

        $description = isset($data['short_description']) && is_scalar($data['short_description'])
            ? sanitize_text_field(wp_strip_all_tags((string) $data['short_description']))
            : '';
        $icons = isset($data['icons']) && (is_array($data['icons']) || is_object($data['icons']))
            ? (array) $data['icons']
            : [];

        return [
            'slug'              => $slug,
            'name'              => wp_html_excerpt($name, 80, '…'),
            'short_description' => wp_html_excerpt($description, 160, '…'),
            'plugin_url'        => $this->useful_plugin_url($slug),
            'icon_url'          => $this->useful_plugin_icon($icons),
            'rating'            => isset($data['rating']) && is_numeric($data['rating']) ? max(0, min(100, (int) $data['rating'])) : 0,
            'active_installs'   => isset($data['active_installs']) && is_numeric($data['active_installs']) ? max(0, (int) $data['active_installs']) : 0,
        ];
    }

    /**
     * Normalize cached records and restore their required order.
     *
     * @param array<int, mixed> $cached Cached plugin records.
     * @return array<int, array{slug:string,name:string,short_description:string,plugin_url:string,icon_url:string,rating:int,active_installs:int}>
     */
    private function normalize_useful_plugins(array $cached): array
    {
        $records = [];
        foreach ($cached as $record) {
            if (!is_array($record) || !isset($record['slug']) || !is_scalar($record['slug'])) {
                continue;
            }

            $slug = sanitize_key((string) $record['slug']);
            if (!in_array($slug, self::USEFUL_PLUGIN_SLUGS, true)) {
                continue;
            }

            $plugin = $this->normalize_cached_useful_plugin($slug, $record);
            if ($plugin !== null) {
                $records[$slug] = $plugin;
            }
        }

        $ordered = [];
        foreach (self::USEFUL_PLUGIN_SLUGS as $slug) {
            if (isset($records[$slug])) {
                $ordered[] = $records[$slug];
            }
        }

        return $ordered;
    }

    /**
     * Validate one cached record without discarding its previously trusted icon.
     *
     * @param array<string, mixed> $data Cached plugin data.
     * @return array{slug:string,name:string,short_description:string,plugin_url:string,icon_url:string,rating:int,active_installs:int}|null
     */
    private function normalize_cached_useful_plugin(string $slug, array $data): ?array
    {
        if (!isset($data['name']) || !is_scalar($data['name'])) {
            return null;
        }

        $name = sanitize_text_field(wp_strip_all_tags((string) $data['name']));
        if ($name === '') {
            return null;
        }

        $description = isset($data['short_description']) && is_scalar($data['short_description'])
            ? sanitize_text_field(wp_strip_all_tags((string) $data['short_description']))
            : '';
        $icon_url = isset($data['icon_url']) && is_scalar($data['icon_url'])
            ? $this->trusted_useful_plugin_icon_url((string) $data['icon_url'])
            : '';

        return [
            'slug'              => $slug,
            'name'              => wp_html_excerpt($name, 80, '…'),
            'short_description' => wp_html_excerpt($description, 160, '…'),
            'plugin_url'        => $this->useful_plugin_url($slug),
            'icon_url'          => $icon_url,
            'rating'            => isset($data['rating']) && is_numeric($data['rating']) ? max(0, min(100, (int) $data['rating'])) : 0,
            'active_installs'   => isset($data['active_installs']) && is_numeric($data['active_installs']) ? max(0, (int) $data['active_installs']) : 0,
        ];
    }

    /**
     * Fill any failed API lookups with safe minimal records in the required order.
     *
     * @param array<int, array{slug:string,name:string,short_description:string,plugin_url:string,icon_url:string,rating:int,active_installs:int}> $plugins
     * @return array<int, array{slug:string,name:string,short_description:string,plugin_url:string,icon_url:string,rating:int,active_installs:int}>
     */
    private function complete_useful_plugins(array $plugins): array
    {
        $by_slug = [];
        foreach ($plugins as $plugin) {
            if (isset($plugin['slug']) && in_array($plugin['slug'], self::USEFUL_PLUGIN_SLUGS, true)) {
                $by_slug[$plugin['slug']] = $plugin;
            }
        }

        $complete = [];
        foreach ($this->fallback_useful_plugins() as $fallback) {
            $complete[] = $by_slug[$fallback['slug']] ?? $fallback;
        }

        return $complete;
    }

    /**
     * Create safe minimal records if the remote API is unavailable.
     *
     * @return array<int, array{slug:string,name:string,short_description:string,plugin_url:string,icon_url:string,rating:int,active_installs:int}>
     */
    private function fallback_useful_plugins(): array
    {
        $plugins = [];
        foreach (self::USEFUL_PLUGIN_SLUGS as $slug) {
            $plugins[] = [
                'slug'              => $slug,
                'name'              => ucwords(str_replace('-', ' ', $slug)),
                'short_description' => '',
                'plugin_url'        => $this->useful_plugin_url($slug),
                'icon_url'          => '',
                'rating'            => 0,
                'active_installs'   => 0,
            ];
        }

        return $plugins;
    }

    /**
     * Return the trusted WordPress.org URL for a recommendation.
     */
    private function useful_plugin_url(string $slug): string
    {
        return 'https://wordpress.org/plugins/' . rawurlencode($slug) . '/';
    }

    /**
     * Return the best available trusted plugin icon URL.
     *
     * @param array<string, mixed> $icons Icon URLs from WordPress.org.
     */
    private function useful_plugin_icon(array $icons): string
    {
        foreach (['svg', '2x', '1x', 'default'] as $size) {
            if (!isset($icons[$size]) || !is_scalar($icons[$size])) {
                continue;
            }

            $icon_url = $this->trusted_useful_plugin_icon_url((string) $icons[$size]);
            if ($icon_url !== '') {
                return $icon_url;
            }
        }

        return '';
    }

    /**
     * Permit only HTTPS WordPress.org-hosted icon URLs.
     */
    private function trusted_useful_plugin_icon_url(string $url): string
    {
        $parts = wp_parse_url(trim($url));
        if (!is_array($parts) || ($parts['scheme'] ?? '') !== 'https' || empty($parts['host'])) {
            return '';
        }

        if (isset($parts['user']) || isset($parts['pass']) || (isset($parts['port']) && (int) $parts['port'] !== 443)) {
            return '';
        }

        $host = strtolower((string) $parts['host']);
        $wordpress_org_subdomain = substr($host, -14) === '.wordpress.org';
        if ($host !== 'ps.w.org' && $host !== 'wordpress.org' && !$wordpress_org_subdomain) {
            return '';
        }

        return esc_url_raw($url, ['https']);
    }
}
