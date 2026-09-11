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

    /** @var array<string, array{active:bool,installed:bool,plugin_file:string}> */
    private $useful_plugin_statuses = [];

    public function donations(): void
    {
        ?>
		<?php printf('<p>%s</p>', esc_html__('Transliterator is free to use and actively maintained. Ongoing updates, performance improvements, and new features require continuous time and resources.', 'serbian-transliteration')); ?>
		<?php printf('<p>%s</p>', esc_html__('If the plugin adds value to your work, you are welcome to support its further development with a voluntary contribution.', 'serbian-transliteration')); ?>
		<hr>
		<ul>
			<?php printf(
				'<li>%s: <br><b>%s</b><br>IBAN: <b>%s</b><br>Swift: <b>%s</b></li>',
				esc_html__('Banca Intesa a.d. Beograd', 'serbian-transliteration'),
				'160-6000002167503-32',
				'RS35160600000216750332',
				'DBDBRSBG'
			); ?>
			<?php /* printf('<li><b>%s</b>: %s</li>', esc_html__('PayPal', 'serbian-transliteration'), 'creativform@gmail.com');*/ ?>
		</ul>
		<hr>
		<?php printf('<p>%s</p>', esc_html__('Thank you for your support.', 'serbian-transliteration')); ?>
		<p><a class="button button-primary" href="<?php echo esc_url('https://ko-fi.com/ivijanstefanstipic'); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e('Support via Ko-fi', 'serbian-transliteration'); ?></a></p>
        <?php
    }

    public function contributors(): void
    {
		if ($plugin_info = Transliteration_Utilities::plugin_info(['contributors' => true, 'donate_link' => true])) :
			$developers = [];
			$collaborators = [];

			foreach ($plugin_info->contributors as $username => $info) {
				if (in_array($username, ['ivijanstefan', 'creativform', 'infinitumform'], true)) {
					$developers[$username] = $info;
				} else {
					$collaborators[$username] = $info;
				}
			}
		?>
		<h3 class="rstr-contributor-group-title developers"><?php esc_html_e('Developers', 'serbian-transliteration'); ?></h3>
		<div class="rstr-inside-metabox flex">
			<?php foreach ($developers as $username => $info) : $info = (object) $info; $avatar_url = add_query_arg('d', 'mp', $info->avatar); ?>
			<div class="contributor contributor-<?php echo esc_attr($username); ?>" id="contributor-<?php echo esc_attr($username); ?>">
				<a href="<?php echo esc_url($info->profile); ?>" target="_blank">
					<img src="<?php echo esc_url($avatar_url); ?>">
					<h3><?php echo esc_html($info->display_name); ?></h3>
				</a>
			</div>
			<?php endforeach; ?>
		</div>
		<?php if ($collaborators) : ?>
		<h3 class="rstr-contributor-group-title contributors"><?php esc_html_e('Contributors', 'serbian-transliteration'); ?></h3>
		<div class="rstr-inside-metabox flex">
			<?php foreach ($collaborators as $username => $info) : $info = (object) $info; $avatar_url = add_query_arg('d', 'mp', $info->avatar); ?>
			<div class="contributor contributor-<?php echo esc_attr($username); ?>" id="contributor-<?php echo esc_attr($username); ?>">
				<a href="<?php echo esc_url($info->profile); ?>" target="_blank">
					<img src="<?php echo esc_url($avatar_url); ?>">
					<h3><?php echo esc_html($info->display_name); ?></h3>
				</a>
			</div>
			<?php endforeach; ?>
		</div>
		<?php endif; ?>
		<div class="rstr-inside-metabox">
			<?php printf(
				'<p>%s</p>',
				wp_kses_post(sprintf(
					/* translators: %s: Link to the plugin's GitHub repository. */
					__('If you want to support our work and effort, if you have new ideas or want to improve the existing code, %s.', 'serbian-transliteration'),
					'<a href="https://github.com/CreativForm/serbian-transliteration" target="_blank">' . esc_html__('join our team', 'serbian-transliteration') . '</a>'
				))
			); ?>
			<?php /* printf('<p>%s</p>', sprintf(__('If you want to help further plugin development, you can also %s.', 'serbian-transliteration'), '<a href="' . esc_url($plugin_info->donate_link) . '" target="_blank">' . __('donate something for effort', 'serbian-transliteration') . '</a>')); */ ?>
		</div>
		<?php endif;
    }

    /**
     * Render WordPress.org plugin recommendations in the settings sidebar.
     */
    public function more_useful_plugins(): void
    {
        $plugins = array_filter($this->get_useful_plugins(), function (array $plugin): bool {
            return !$this->useful_plugin_status($plugin['slug'])['active'];
        });

        if ($plugins === []) {
            return;
        }
        ?>
        <p><?php esc_html_e('Discover a few other free WordPress plugins you may find useful.', 'serbian-transliteration'); ?></p>
        <div class="rstr-useful-plugins">
            <?php foreach ($plugins as $plugin) : ?>
                <?php $status = $this->useful_plugin_status($plugin['slug']); ?>
                <div class="rstr-useful-plugin">
                    <div class="rstr-useful-plugin__icon-wrap">
                        <img
                            class="rstr-useful-plugin__icon"
                            src="<?php echo esc_url($this->useful_plugin_icon_url($plugin['slug'])); ?>"
                            alt=""
                            loading="lazy"
                            decoding="async"
                        >
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
                            <?php if ($status['installed'] && $status['plugin_file'] !== '' && current_user_can('activate_plugin', $status['plugin_file'])) : ?>
                                <a href="<?php echo esc_url($this->useful_plugin_activation_url($status['plugin_file'])); ?>">
                                    <?php esc_html_e('Activate this plugin', 'serbian-transliteration'); ?>
                                </a>
                            <?php else : ?>
                                <a href="<?php echo esc_url($plugin['plugin_url']); ?>" target="_blank" rel="noopener noreferrer">
                                    <?php esc_html_e('View plugin', 'serbian-transliteration'); ?>
                                </a>
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
    }

    /**
     * Return whether at least one fixed recommendation is not active.
     */
    public function has_useful_plugins(): bool
    {
        foreach (self::USEFUL_PLUGIN_SLUGS as $slug) {
            if (!$this->useful_plugin_status($slug)['active']) {
                return true;
            }
        }

        return false;
    }

    /**
     * Retrieve, validate, and cache the fixed WordPress.org recommendations.
     *
     * @return array<int, array{slug:string,name:string,short_description:string,plugin_url:string,rating:int,active_installs:int}>
     */
    private function get_useful_plugins(): array
    {
        $cache_key = $this->useful_plugins_cache_key();
        $cached    = get_transient($cache_key);
        if (is_array($cached)) {
            $plugins = $this->normalize_useful_plugins($cached);
            if (count($plugins) === count(self::USEFUL_PLUGIN_SLUGS)) {
                return $plugins;
            }
        }

        $plugins = $this->fetch_useful_plugins();
        $plugins = $this->complete_useful_plugins($plugins);

        set_transient($cache_key, $plugins, self::USEFUL_PLUGINS_CACHE_TTL);

        return $plugins;
    }

    /**
     * Fetch the requested plugin details from the WordPress.org API.
     *
     * @return array<int, array{slug:string,name:string,short_description:string,plugin_url:string,rating:int,active_installs:int}>
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
     * @return array{slug:string,name:string,short_description:string,plugin_url:string,rating:int,active_installs:int}|null
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
        return [
            'slug'              => $slug,
            'name'              => wp_html_excerpt($name, 80, '…'),
            'short_description' => wp_html_excerpt($description, 160, '…'),
            'plugin_url'        => $this->useful_plugin_url($slug),
            'rating'            => isset($data['rating']) && is_numeric($data['rating']) ? max(0, min(100, (int) $data['rating'])) : 0,
            'active_installs'   => isset($data['active_installs']) && is_numeric($data['active_installs']) ? max(0, (int) $data['active_installs']) : 0,
        ];
    }

    /**
     * Normalize cached records and restore their required order.
     *
     * @param array<int, mixed> $cached Cached plugin records.
     * @return array<int, array{slug:string,name:string,short_description:string,plugin_url:string,rating:int,active_installs:int}>
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
     * Validate one cached recommendation record.
     *
     * @param array<string, mixed> $data Cached plugin data.
     * @return array{slug:string,name:string,short_description:string,plugin_url:string,rating:int,active_installs:int}|null
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
        return [
            'slug'              => $slug,
            'name'              => wp_html_excerpt($name, 80, '…'),
            'short_description' => wp_html_excerpt($description, 160, '…'),
            'plugin_url'        => $this->useful_plugin_url($slug),
            'rating'            => isset($data['rating']) && is_numeric($data['rating']) ? max(0, min(100, (int) $data['rating'])) : 0,
            'active_installs'   => isset($data['active_installs']) && is_numeric($data['active_installs']) ? max(0, (int) $data['active_installs']) : 0,
        ];
    }

    /**
     * Fill any failed API lookups with safe minimal records in the required order.
     *
     * @param array<int, array{slug:string,name:string,short_description:string,plugin_url:string,rating:int,active_installs:int}> $plugins
     * @return array<int, array{slug:string,name:string,short_description:string,plugin_url:string,rating:int,active_installs:int}>
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
     * @return array<int, array{slug:string,name:string,short_description:string,plugin_url:string,rating:int,active_installs:int}>
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
     * Find the installed plugin file and its activation state for a fixed slug.
     *
     * @return array{active:bool,installed:bool,plugin_file:string}
     */
    private function useful_plugin_status(string $slug): array
    {
        if (isset($this->useful_plugin_statuses[$slug])) {
            return $this->useful_plugin_statuses[$slug];
        }

        if (!function_exists('get_plugins') || !function_exists('is_plugin_active')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        $status = [
            'active'      => false,
            'installed'   => false,
            'plugin_file' => '',
        ];

        foreach (get_plugins($slug) as $file => $plugin_data) {
            $plugin_file = $slug . '/' . $file;
            $active      = is_plugin_active($plugin_file) || (is_multisite() && is_plugin_active_for_network($plugin_file));

            if ($status['plugin_file'] === '' || $active) {
                $status['plugin_file'] = $plugin_file;
            }

            $status['installed'] = true;
            $status['active']    = $status['active'] || $active;
        }

        $this->useful_plugin_statuses[$slug] = $status;

        return $status;
    }

    /**
     * Build the nonce-protected plugin activation URL.
     */
    private function useful_plugin_activation_url(string $plugin_file): string
    {
        return wp_nonce_url(
            self_admin_url('plugins.php?action=activate&plugin=' . rawurlencode($plugin_file)),
            'activate-plugin_' . $plugin_file
        );
    }

    /**
     * Return the bundled icon for a fixed recommendation.
     */
    private function useful_plugin_icon_url(string $slug): string
    {
        if (!in_array($slug, self::USEFUL_PLUGIN_SLUGS, true)) {
            return '';
        }

        return RSTR_ASSETS . '/img/recommended-plugins/' . rawurlencode($slug) . '.png';
    }

    /**
     * Keep recommendation metadata separate for each WordPress user locale.
     */
    private function useful_plugins_cache_key(): string
    {
        $locale = function_exists('get_user_locale') ? get_user_locale() : get_locale();

        return self::USEFUL_PLUGINS_CACHE_KEY . '_' . sanitize_key($locale);
    }

}
