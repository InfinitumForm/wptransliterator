<?php if (!defined('WPINC')) {
    die();
}

final class Transliteration_Settings extends Transliteration
{
    public function __construct()
    {

        $this->add_action('plugin_action_links_' . RSTR_BASENAME, 'action_links');
        $this->add_action('plugin_row_meta', 'row_meta_links', 10, 2);

        $this->add_action('admin_menu', 'add_settings_page');
        $this->add_action('admin_init', 'register_settings');
        $this->add_action('wp_before_admin_bar_render', 'admin_bar_link');
        $this->add_action('admin_enqueue_scripts', 'enqueue_admin_scripts');

        if (get_rstr_option('allow-admin-tools', 'yes') == 'yes') {
            $this->add_action('enqueue_block_editor_assets', 'transliteration_tool_enqueue_assets');
        }

        if (isset($_GET['rstr-activation']) && $_GET['rstr-activation'] == 'true') {
            $this->add_action('admin_notices', 'admin_notice__activation', 10, 0);
            if (isset($_GET['settings-updated']) && $_GET['settings-updated'] == 'true') {
                Transliteration_Cache_DB::set(RSTR_BASENAME . '_nonce_save', 'true', 30);
                $this->add_action('admin_init', 'updated_option__redirection', 10, 0);
            }
        }

        if (Transliteration_Cache_DB::get(RSTR_BASENAME . '_nonce_save') == 'true') {
            $this->add_action('admin_notices', 'admin_notice__success', 10, 0);
        }

        $this->add_action('wp_dashboard_setup', 'transliterator_dashboard_widget', 1);
    }

    public function updated_option__redirection(): void
    {
        Transliteration_Utilities::clear_plugin_cache();
        if (!headers_sent() && wp_safe_redirect(admin_url('options-general.php?page=transliteration-settings'), 302)) {
            exit;
        }
    }

    public function admin_bar_link(): void
    {
        if (current_user_can('administrator')) {
            global $wp_admin_bar;
            $wp_admin_bar->add_menu([
                'parent' => 'site-name',
                'id'     => 'transliteration-settings',
                'title'  => __('Transliteration', 'serbian-transliteration'),
                'href'   => admin_url('/options-general.php?page=transliteration-settings'),
            ]);
        }
    }

    public function admin_notice__success(): void
    {
        global $pagenow;
        if ($pagenow == 'options-general.php') {
            printf(
                '<div class="notice notice-success is-dismissible">%s</div>',
                sprintf('<p>%s</p>', __('Settings saved.', 'serbian-transliteration'))
            );
            Transliteration_Cache_DB::delete(RSTR_BASENAME . '_nonce_save');
        }
    }

    /**
     * @return mixed[]
     */
    public function action_links($links): array
    {

        return array_merge([
            '<a href="' . esc_url(admin_url('/options-general.php?page=transliteration-settings')) . '">' . __('Settings', 'serbian-transliteration') . '</a>',
        ], $links);

    }

    public function row_meta_links($links, $file)
    {
        if (RSTR_BASENAME == $file) {
            return array_merge($links, [
                'rstr-shortcodes' => '<a href="' . esc_url(admin_url('/options-general.php?page=transliteration-settings&tab=documentation&action=shortcodes')) . '">' . __('Shortcodes', 'serbian-transliteration') . '</a>',
                'rstr-functions'  => '<a href="' . esc_url(admin_url('/options-general.php?page=transliteration-settings&tab=documentation&action=functions')) . '">' . __('PHP Functions', 'serbian-transliteration') . '</a>',
                'rstr-review'     => '<a href="https://wordpress.org/support/plugin/serbian-transliteration/reviews/?filter=5#new-post" target="_blank">' . __('5 stars?', 'serbian-transliteration') . '</a>',
            ]);
        }
        return $links;
    }

    public function add_settings_page(): void
    {
        add_options_page(
            __('Transliteration Settings', 'serbian-transliteration'),
            __('Transliteration', 'serbian-transliteration'),
            'manage_options',
            'transliteration-settings',
            [$this, 'create_admin_page']
        );
    }

    public function enqueue_admin_scripts($hook): void
    {

        $min = defined('RSTR_DEV_MODE') && RSTR_DEV_MODE ? '' : '.min';

        if (get_rstr_option('allow-admin-tools', 'yes') == 'yes') {
            wp_register_style('transliteration-tools', RSTR_ASSETS . '/css/tools' . $min . '.css');
            wp_register_script('transliteration-tools', RSTR_ASSETS . '/js/tools' . $min . '.js', ['jquery'], null, true);

            wp_enqueue_style('transliteration-tools');
            wp_enqueue_script('transliteration-tools');

            // Localize script
            wp_localize_script(
                'transliteration-tools',
                'RSTR_TOOL',
                [
                    'version' => RSTR_VERSION,
                    'home'    => get_bloginfo('wpurl'),
                    'ajax'    => admin_url('/admin-ajax.php'),
                    'nonce'   => wp_create_nonce('rstr-transliteration-letters'),
                    'prefix'  => RSTR_PREFIX,
                    'label'   => [
                        'Latin'         => __('Latin', 'serbian-transliteration'),
                        'Cyrillic'      => __('Cyrillic', 'serbian-transliteration'),
                        'toLatin'       => __('To Latin', 'serbian-transliteration'),
                        'toCyrillic'    => __('To Cyrillic', 'serbian-transliteration'),
                        'Transliterate' => __('Transliterate:', 'serbian-transliteration'),
                        'loading'       => __('Loading...', 'serbian-transliteration'),
                    ],
                ]
            );
        }

        // Enqueue scripts only on the transliteration settings page
        if ($hook !== 'settings_page_transliteration-settings') {
            return;
        }

        $tab = sanitize_text_field($_GET['tab'] ?? 'general');

        // Register the CSS and JS files
        wp_register_style('transliteration-admin', RSTR_ASSETS . '/css/admin' . $min . '.css');
        wp_register_script('transliteration-admin', RSTR_ASSETS . '/js/admin' . $min . '.js', ['jquery'], null, true);

        wp_register_style('transliteration-highlight', RSTR_ASSETS . '/css/highlight.min.css');
        wp_register_script('transliteration-highlight', RSTR_ASSETS . '/js/highlight.min.js', ['jquery'], null, true);

        // Enqueue the CSS and JS files
        wp_enqueue_style('transliteration-admin');
        wp_enqueue_script('transliteration-admin');

        // Hilights
        if ($tab == 'documentation') {
            wp_enqueue_style('transliteration-highlight');
            wp_enqueue_script('transliteration-highlight');
        }

        // Localize script
        wp_localize_script(
            'transliteration-admin',
            'RSTR',
            [
                'version' => RSTR_VERSION,
                'home'    => get_bloginfo('wpurl'),
                'ajax'    => admin_url('/admin-ajax.php'),
                'prefix'  => RSTR_PREFIX,
                'label'   => [
                    'progress_loading' => __('Please wait! Do not close the window or leave the page until this operation is completed!', 'serbian-transliteration'),
                    'done'             => __('DONE!!!', 'serbian-transliteration'),
                    'loading'          => __('Loading...', 'serbian-transliteration'),
                ],
            ]
        );
    }

    public function transliteration_tool_enqueue_assets(): void
    {
        $min = defined('RSTR_DEV_MODE') && RSTR_DEV_MODE ? '' : '.min';

        wp_enqueue_script(
            'transliteration-tools-block',
            RSTR_ASSETS . '/js/tools-block' . $min . '.js',
            ['wp-blocks', 'wp-element', 'wp-editor', 'wp-components', 'wp-data'],
            null,
            true
        );

        wp_localize_script(
            'transliteration-tools-block',
            'RSTR_TOOL',
            [
                'version' => RSTR_VERSION,
                'home'    => get_bloginfo('wpurl'),
                'ajax'    => admin_url('/admin-ajax.php'),
                'nonce'   => wp_create_nonce('rstr-transliteration-letters'),
                'prefix'  => RSTR_PREFIX,
                'label'   => [
                    'Latin'         => __('Latin', 'serbian-transliteration'),
                    'Cyrillic'      => __('Cyrillic', 'serbian-transliteration'),
                    'toLatin'       => __('To Latin', 'serbian-transliteration'),
                    'toCyrillic'    => __('To Cyrillic', 'serbian-transliteration'),
                    'Transliterate' => __('Transliterate:', 'serbian-transliteration'),
                    'loading'       => __('Loading...', 'serbian-transliteration'),
                ],
            ]
        );
    }

    public function admin_notice__activation(): void
    {
        global $pagenow;
        if ($pagenow == 'options-general.php') {
            printf(
                '<div class="notice notice-warning is-dismissible">%s%s</div>',
                sprintf('<h3>%s</h3>', __('PLEASE UPDATE PLUGIN SETTINGS', 'serbian-transliteration')),
                sprintf('<p>%s</p>', __('Carefully review the transliteration plugin settings and adjust how it fits your WordPress installation. It is important that every time you change the settings, you test the parts of the site that are affected by this plugin.', 'serbian-transliteration'))
            );
        }
    }

    public function create_admin_page(): void
    {
        $tab = sanitize_text_field($_GET['tab'] ?? '');
        switch ($tab) {
            default:
            case 'general':
                $this->settings_page__general();
                break;

            case 'documentation':
                $this->settings_page__documentation([
                    'shortcodes' => __('Shortcodes', 'serbian-transliteration'),
                    'functions'  => __('PHP Functions', 'serbian-transliteration'),
                    'tags'       => __('Tags', 'serbian-transliteration'),
                ]);
                break;

            case 'tools':
                $this->settings_page__tools([
                    'transliteration' => __('Transliteration Tool', 'serbian-transliteration'),
                    'permalinks'      => __('Permalink Tool', 'serbian-transliteration'),
                ]);
                break;

            case 'debug':
                $this->settings_page__debug();
                break;

            case 'credits':
                $this->settings_page__credits();
                break;
        }
    }

    public function settings_tabs(): void
    {
        $main_url = admin_url('/options-general.php?page=transliteration-settings');
        $tab      = sanitize_text_field($_GET['tab'] ?? 'general');
        $tabs     = [
            'general' => [
                'icon'  => 'admin-settings',
                'label' => __('General Settings', 'serbian-transliteration'),
            ],
            'documentation' => [
                'icon'  => 'media-spreadsheet',
                'label' => __('Documentation', 'serbian-transliteration'),
            ],
            'tools' => [
                'icon'  => 'admin-generic',
                'label' => __('Tools', 'serbian-transliteration'),
            ],
            'debug' => [
                'icon'  => 'sos',
                'label' => __('Debug', 'serbian-transliteration'),
            ],
            'credits' => [
                'icon'  => 'info-outline',
                'label' => __('Credits', 'serbian-transliteration'),
            ],
        ];
        ?>
		<ul class="transliteration-settings-tabs">
			<?php foreach ($tabs as $key => $item): ?>
			<li<?php echo(($tab == $key) ? ' class="current"' : ''); ?> title="<?php echo esc_attr($item['label'] ?? ''); ?>">
				<a href="<?php echo esc_url(add_query_arg('tab', $key, $main_url)); ?>"><?php if (($item['icon'] ?? null) !== '' && ($item['icon'] ?? null) !== '0') : ?><i class="dashicons dashicons-<?php echo esc_attr($item['icon']); ?>"></i> <?php endif; ?><span class="lbl"><?php echo esc_html($item['label'] ?? ''); ?></span></a>
			</li>
			<?php endforeach; ?>
		</ul>
		<?php
    }

    public function settings_page_actions($actions = []): void
    {
        $tab      = sanitize_text_field($_GET['tab'] ?? 'general');
        $main_url = admin_url('/options-general.php?page=transliteration-settings&tab=' . $tab);
        $action   = sanitize_text_field($_GET['action'] ?? '');
        ?>
		<ul class="transliteration-action-links">
			<?php $i = 0;
        foreach ($actions as $key => $label): ?>
			<li<?php echo(($action == $key || ($i === 0 && empty($action))) ? ' class="active"' : ''); ?>>
				<a href="<?php echo esc_url(add_query_arg('action', $key, $main_url)); ?>"><?php echo esc_html($label); ?></a>
			</li>
			<?php ++$i; endforeach; ?>
		</ul>
		<?php
    }

    public function settings_page__general(): void
    {
        ?>
<div class="wrap" id="wp-transliteration-settings">
	<h1><?php esc_html_e('Transliteration', 'serbian-transliteration'); ?></h1>
	<?php $this->settings_tabs(); ?>
	<div id="poststuff" class="metabox-holder has-right-sidebar">
		<div class="inner-sidebar">
			<div id="side-sortables" class="meta-box-sortables">
			<?php
				// Hook triggered before settings sidebar
				do_action('transliteration-settings-before-sidebar', 'general', $this);
				do_meta_boxes('transliteration-settings', 'side', null);
				// Hook triggered after settings sidebar
				do_action('transliteration-settings-after-sidebar', 'general', $this);
			?>
			</div>
		</div>
		<div id="post-body">
			<div id="post-body-content">
				<form method="post" action="options.php">
					<?php
						settings_fields('transliteration-group');
						do_settings_sections('serbian-transliteration');
						submit_button(
							__('Save Changes'),
							'primary',
							'trasnliteration_settings_save_changes_1',
							false,
							['id' => 'trasnliteration_settings_submit_button_1']
						);
					?>
					<div id="trasnliteration_settings_submit_button_float">
						<?php submit_button(
						    __('Save Changes'),
						    'primary',
						    'trasnliteration_settings_save_changes_2',
						    false,
						    ['id' => 'trasnliteration_settings_submit_button_2']
						); ?>
					</div>
				</form>
			</div>
		</div>
	</div>
</div>
		<?php
    }

    public function settings_page__documentation($actions): void
    {
        $action = sanitize_text_field($_GET['action'] ?? 'shortcodes');

        switch ($action) {
            default:
            case 'shortcodes':
                $this->settings_action_page__shortcodes($actions);
                break;

            case 'functions':
                $this->settings_action_page__functions($actions);
                break;

            case 'tags':
                $this->settings_action_page__tags($actions);
                break;
        }
    }

    public function settings_action_page__shortcodes($actions): void
    {
        ?>
<script>
document.addEventListener('DOMContentLoaded', (event) => {
	document.querySelectorAll('.lang-txt').forEach((block) => {
		hljs.highlightBlock(block);
	});
});
</script>
<div class="wrap" id="wp-transliteration-settings">
	<h1><?php esc_html_e('Transliteration', 'serbian-transliteration'); ?> - <?php esc_html_e('Available shortcodes', 'serbian-transliteration'); ?></h1>
	<?php $this->settings_tabs(); ?>
	<div id="poststuff" class="metabox-holder has-right-sidebar">
		<div class="inner-sidebar">
			<div id="side-sortables" class="meta-box-sortables">
				<?php
// Hook triggered before settings sidebar
                    do_action('transliteration-settings-before-sidebar', 'shortcodes', $this);
        do_meta_boxes('transliteration-settings', 'side', null);
// Hook triggered after settings sidebar
        do_action('transliteration-settings-after-sidebar', 'shortcodes', $this);
        ?>
			</div>
		</div>
		<div id="post-body">
			<div id="post-body-content">
				<div class="transliteration-page" id="documentation-page"><?php
            $this->settings_page_actions($actions);
        include_once RSTR_CLASSES . '/settings/page-shortcodes.php';
        ?></div>
			</div>
		</div>
	</div>
</div>
		<?php
    }

    public function settings_action_page__functions($actions): void
    {
        ?>
<script>
document.addEventListener('DOMContentLoaded', (event) => {
	document.querySelectorAll('code.lang-php').forEach((block) => {
		hljs.highlightBlock(block);
	});
});
</script>
<div class="wrap" id="wp-transliteration-settings">
	<h1><?php esc_html_e('Transliteration', 'serbian-transliteration'); ?> - <?php esc_html_e('Available PHP Functions', 'serbian-transliteration'); ?></h1>
	<?php $this->settings_tabs(); ?>
	<div id="poststuff" class="metabox-holder has-right-sidebar">
		<div class="inner-sidebar">
			<div id="side-sortables" class="meta-box-sortables">
				<?php
// Hook triggered before settings sidebar
                    do_action('transliteration-settings-before-sidebar', 'functions', $this);
        do_meta_boxes('transliteration-settings', 'side', null);
// Hook triggered after settings sidebar
        do_action('transliteration-settings-after-sidebar', 'functions', $this);
        ?>
			</div>
		</div>
		<div id="post-body">
			<div id="post-body-content">
				<div class="transliteration-page"><?php
            $this->settings_page_actions($actions);
        include_once RSTR_CLASSES . '/settings/page-functions.php';
        ?></div>
			</div>
		</div>
	</div>
</div>
		<?php
    }

    public function settings_action_page__tags($actions): void
    {
        ?>
<script>
document.addEventListener('DOMContentLoaded', (event) => {
	document.querySelectorAll('.lang-txt').forEach((block) => {
		hljs.highlightBlock(block);
	});
});
</script>
<div class="wrap" id="wp-transliteration-settings">
	<h1><?php esc_html_e('Transliteration', 'serbian-transliteration'); ?> - <?php esc_html_e('Available Tags', 'serbian-transliteration'); ?></h1>
	<?php $this->settings_tabs(); ?>
	<div id="poststuff" class="metabox-holder has-right-sidebar">
		<div class="inner-sidebar">
			<div id="side-sortables" class="meta-box-sortables">
				<?php
// Hook triggered before settings sidebar
                    do_action('transliteration-settings-before-sidebar', 'tags', $this);
        do_meta_boxes('transliteration-settings', 'side', null);
// Hook triggered after settings sidebar
        do_action('transliteration-settings-after-sidebar', 'tags', $this);
        ?>
			</div>
		</div>
		<div id="post-body">
			<div id="post-body-content">
				<div class="transliteration-page"><?php
            $this->settings_page_actions($actions);
        include_once RSTR_CLASSES . '/settings/page-tags.php';
        ?></div>
			</div>
		</div>
	</div>
</div>
		<?php
    }

    public function settings_page__tools($actions): void
    {
        $action = sanitize_text_field($_GET['action'] ?? 'transliteration');

        switch ($action) {
            default:
            case 'transliteration':
                $this->settings_action_page__transliteration($actions);
                break;

            case 'permalinks':
                $this->settings_action_page__permalinks($actions);
                break;
        }
    }

    public function settings_action_page__transliteration($actions): void
    {
        ?>
<div class="wrap" id="wp-transliteration-settings">
	<h1><?php esc_html_e('Transliteration', 'serbian-transliteration'); ?> - <?php esc_html_e('Converter for transliterating Cyrillic into Latin and vice versa', 'serbian-transliteration'); ?></h1>
	<?php $this->settings_tabs(); ?>
	<div id="poststuff" class="metabox-holder has-right-sidebar">
		<div class="inner-sidebar">
			<div id="side-sortables" class="meta-box-sortables">
				<?php
// Hook triggered before settings sidebar
                    do_action('transliteration-settings-before-sidebar', 'transliteration', $this);
        do_meta_boxes('transliteration-tools', 'side', null);
// Hook triggered after settings sidebar
        do_action('transliteration-settings-after-sidebar', 'transliteration', $this);
        ?>
			</div>
		</div>
		<div id="post-body">
			<div id="post-body-content">
				<div class="transliteration-page"><?php
            $this->settings_page_actions($actions);
        include_once RSTR_CLASSES . '/settings/page-transliteration.php';
        ?></div>
			</div>
		</div>
	</div>
</div>
		<?php
    }

    public function settings_action_page__permalinks($actions): void
    {
        ?>
<div class="wrap" id="wp-transliteration-settings">
	<h1><?php esc_html_e('Transliteration', 'serbian-transliteration'); ?> - <?php esc_html_e('Permalink Transliteration Tool', 'serbian-transliteration'); ?></h1>
	<?php $this->settings_tabs(); ?>
	<div id="poststuff" class="metabox-holder has-right-sidebar">
		<div class="inner-sidebar">
			<div id="side-sortables" class="meta-box-sortables">
				<?php
// Hook triggered before settings sidebar
                    do_action('transliteration-settings-before-sidebar', 'permalinks', $this);
        do_meta_boxes('transliteration-tools', 'side', null);
// Hook triggered after settings sidebar
        do_action('transliteration-settings-after-sidebar', 'permalinks', $this);
        ?>
			</div>
		</div>
		<div id="post-body">
			<div id="post-body-content">
				<div class="transliteration-page"><?php
            $this->settings_page_actions($actions);
        include_once RSTR_CLASSES . '/settings/page-permalinks.php';
        ?></div>
			</div>
		</div>
	</div>
</div>
		<?php
    }

    public function settings_page__debug(): void
    {
        ?>
<script>
document.addEventListener('DOMContentLoaded', (event) => {
	document.querySelectorAll('.lang-php').forEach((block) => {
		hljs.highlightBlock(block);
	});
});
</script>
<div class="wrap" id="wp-transliteration-settings">
	<h1><?php esc_html_e('Transliteration', 'serbian-transliteration'); ?> - <?php esc_html_e('Debug information', 'serbian-transliteration'); ?></h1>
	<?php $this->settings_tabs(); ?>
	<div id="poststuff" class="metabox-holder has-right-sidebar">
		<div class="inner-sidebar">
			<div id="side-sortables" class="meta-box-sortables">
				<?php
// Hook triggered before settings sidebar
                    do_action('transliteration-settings-before-sidebar', 'debug', $this);
        do_meta_boxes('transliteration-settings', 'side', null);
// Hook triggered after settings sidebar
        do_action('transliteration-settings-after-sidebar', 'debug', $this);
        ?>
			</div>
		</div>
		<div id="post-body">
			<div id="post-body-content">
				<div class="transliteration-page"><?php
            include_once RSTR_CLASSES . '/settings/page-debug.php';
        ?></div>
			</div>
		</div>
	</div>
</div>
		<?php
    }

    public function settings_page__credits(): void
    {
        ?>
<div class="wrap" id="wp-transliteration-settings">
	<h1><?php esc_html_e('Transliteration', 'serbian-transliteration'); ?> - <?php esc_html_e('Credits', 'serbian-transliteration'); ?></h1>
	<?php $this->settings_tabs(); ?>
	<div id="poststuff" class="metabox-holder has-right-sidebar">
		<div class="inner-sidebar">
			<div id="side-sortables" class="meta-box-sortables">
				<?php
// Hook triggered before settings sidebar
                    do_action('transliteration-settings-before-sidebar', 'credits', $this);
        do_meta_boxes('transliteration-credits', 'side', null);
// Hook triggered after settings sidebar
        do_action('transliteration-settings-after-sidebar', 'credits', $this);
        ?>
			</div>
		</div>
		<div id="post-body">
			<div id="post-body-content">
				<div class="transliteration-page"><?php
            include_once RSTR_CLASSES . '/settings/page-credits.php';
        ?></div>
			</div>
		</div>
	</div>
</div>
		<?php
    }

    public function register_settings(): void
    {
        $settings_fields = new Transliteration_Settings_Fields();
        $settings_fields->register_settings();

        if (get_rstr_option('allow-admin-tools', 'yes') == 'yes') {
            $this->add_filter('mce_external_plugins', 'tools_tinymce');
            $this->add_filter('mce_buttons', 'tools_tinymce_buttons');
        }
    }

    public function tools_tinymce_buttons($buttons)
    {
        $buttons[] = 'transliterate_to_latin';
        $buttons[] = 'transliterate_to_cyrillic';
        return $buttons;
    }

    public function tools_tinymce(array $plugin_array): array
    {
        $min                                    = defined('RSTR_DEV_MODE') && RSTR_DEV_MODE ? '' : '.min';
        $plugin_array['transliteration_plugin'] = RSTR_ASSETS . '/js/tools-tinymce' . $min . '.js';
        return $plugin_array;
    }

    public function transliterator_dashboard_widget(): void
    {
        wp_add_dashboard_widget(
            'transliterator_dashboard_widget',
            __('WordPress Transliterator', 'serbian-transliteration'),
            [&$this, 'transliterator_dashboard_widget_display'],
            null,
            null,
            'side',
            'core'
        );
    }

    public function transliterator_dashboard_widget_display(): void
    {
        $settings_url      = esc_url(admin_url('options-general.php?page=transliteration-settings'));
        $documentation_url = esc_url($settings_url . '&tab=documentation');
        $tools_url         = esc_url($settings_url . '&tab=tools');
        $debug_url         = esc_url($settings_url . '&tab=debug');
        $credits_url       = esc_url($settings_url . '&tab=credits');
        $rate_url          = esc_url('https://wordpress.org/support/plugin/serbian-transliteration/reviews/?filter=5#new-post');

        $options = get_rstr_option();

        $transliteration_mode = get_rstr_option('transliteration-mode', 'cyr_to_lat') === 'cyr_to_lat'
            ? __('Cyrillic to Latin', 'serbian-transliteration')
            : __('Latin to Cyrillic', 'serbian-transliteration');

        $cache_support = get_rstr_option('cache-support', 'no') === 'yes'
            ? '<span style="color:#007d1b">' . __('Yes', 'serbian-transliteration') . '</span>'
            : '<span>' . __('No', 'serbian-transliteration') . '</span>';

        $media_transliteration = get_rstr_option('media-transliteration', 'yes') === 'yes'
            ? '<span style="color:#007d1b">' . __('Yes', 'serbian-transliteration') . '</span>'
            : '<span>' . __('No', 'serbian-transliteration') . '</span>';

        $permalink_transliteration = get_rstr_option('permalink-transliteration', 'yes') === 'yes'
            ? '<span style="color:#007d1b">' . __('Yes', 'serbian-transliteration') . '</span>'
            : '<span>' . __('No', 'serbian-transliteration') . '</span>';

        defined('RSTR_MULTISITE') && RSTR_MULTISITE
            ? '<span style="color:#007d1b">' . __('Yes', 'serbian-transliteration') . '</span>'
            : '<span>' . __('No', 'serbian-transliteration') . '</span>';

        ?>
<h3><b><?php echo esc_html__('Quick Access:', 'serbian-transliteration'); ?></b></h3>
<ul>
	<li><a href="<?php echo $settings_url; ?>"><?php echo esc_html__('Settings', 'serbian-transliteration'); ?></a></li>
	<li><a href="<?php echo $documentation_url; ?>"><?php echo esc_html__('Documentation', 'serbian-transliteration'); ?></a></li>
	<li><a href="<?php echo $tools_url; ?>"><?php echo esc_html__('Tools', 'serbian-transliteration'); ?></a></li>
	<li><a href="<?php echo $debug_url; ?>"><?php echo esc_html__('Debug', 'serbian-transliteration'); ?></a></li>
	<li><a href="<?php echo $credits_url; ?>"><?php echo esc_html__('Credits', 'serbian-transliteration'); ?></a></li>
	<li><a href="<?php echo $rate_url; ?>" target="_blank"><?php echo esc_html__('Rate us', 'serbian-transliteration'); ?></a></li>
</ul>

<?php if ($options): ?>
	<h3><b><?php echo esc_html__('Current Settings:', 'serbian-transliteration'); ?></b></h3>
	<ul>
		<li><?php echo esc_html__('Transliteration Mode:', 'serbian-transliteration'); ?> <b><?php echo esc_html($transliteration_mode); ?></b></li>
		<li><?php echo esc_html__('Cache Support:', 'serbian-transliteration'); ?> <b><?php echo wp_kses_post($cache_support); ?></b></li>
		<li><?php echo esc_html__('Media Transliteration:', 'serbian-transliteration'); ?> <b><?php echo wp_kses_post($media_transliteration); ?></b></li>
		<li><?php echo esc_html__('Permalink Transliteration:', 'serbian-transliteration'); ?> <b><?php echo wp_kses_post($permalink_transliteration); ?></b></li>
	</ul>
<?php else: ?>
	<p style="color:#cc0000"><?php echo esc_html__('Transliterator plugin options are not yet available. Please update plugin settings!', 'serbian-transliteration'); ?></p>
<?php endif;
    }
}
