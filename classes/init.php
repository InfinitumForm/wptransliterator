<?php

if (!defined('WPINC')) {
    die();
}

final class Transliteration_Init extends Transliteration
{
    public function __construct()
    {
        // Register the textdomain for the plugin
        $this->set_admin_cookie_based_on_url();
        // Load translations after plugins are fully loaded
		
        $this->add_action('plugins_loaded', 'hook_init');
        $this->add_action('template_redirect', 'set_transliteration');

        // Register main classes
        $main_classes = apply_filters('transliteration_classes_init', [
            'Transliteration_Themes',
            'Transliteration_Plugins',
            'Transliteration_Filters',
            'Transliteration_Settings',
            'Transliteration_Mode',
            'Transliteration_Controller',
            'Transliteration_Wordpress',
            'Transliteration_Menus',
            'Transliteration_Notifications',
            'Transliteration_Tools',
            'Transliteration_Shortcodes',
			'Transliteration_Blocks',
        ]);

        foreach ($main_classes as $main_class_name) {
            new $main_class_name();
        }

        // Register plugin classes
        $classes = apply_filters('transliteration_classes_init', [
            'Transliteration_Rest',
            'Transliteration_Ajax',
            'Transliteration_Email',
            'Transliteration_Search',
        ]);

        $classes = apply_filters('transliteration_init_classes', $classes);

        foreach ($classes as $class_name) {
            new $class_name();
        }
    }

	public function hook_init(): void
	{
		// Void for now
	}

    /**
     * Set an admin detection cookie based on the requested URL.
     *
     * This allows other plugin components to know whether the current
     * request is in the admin area without relying solely on is_admin().
     */
    public function set_admin_cookie_based_on_url(): void
    {
        global $rstr_is_admin;

        $request_uri = $_SERVER['REQUEST_URI'] ?? '';

        if (strpos($request_uri, '/admin-ajax.php') !== false || (function_exists('wp_doing_ajax') && wp_doing_ajax())) {
            return;
        }

        if (strpos($request_uri, '/wp-admin/') !== false || (function_exists('is_admin') && is_admin())) {
            if (!headers_sent()) {
                setcookie('rstr_test_' . COOKIEHASH, 'true', ['expires' => 0, 'path' => COOKIEPATH, 'domain' => COOKIE_DOMAIN]);
            }

            $rstr_is_admin = true;
        } else {
            if (!headers_sent()) {
                setcookie('rstr_test_' . COOKIEHASH, 'false', ['expires' => 0, 'path' => COOKIEPATH, 'domain' => COOKIE_DOMAIN]);
            }

            $rstr_is_admin = false;
        }
    }

    /*
     * Set transliteration & redirections
     */
    public function set_transliteration(): void
    {
        if (function_exists('wp_doing_ajax') && wp_doing_ajax()) {
            return;
        }

        // URL Selector
        $url_selector = get_rstr_option('url-selector', 'rstr');

        // Set REQUEST param
        $request = $_REQUEST[$url_selector] ?? null;
        if (in_array($request, apply_filters('rstr/allowed_script', ['cyr', 'lat']), true)) {
            // Set cookie
            Transliteration_Utilities::setcookie($request);
            // Get current URL
            $url = remove_query_arg($url_selector);
            // Set no-cache headers
            if (get_rstr_option('cache-support', 'no') == 'yes') {
                $url = add_query_arg('_rstr_nocache', uniqid($url_selector . random_int(1000, 9999)), $url);
                Transliteration_Utilities::cache_flush();
            } elseif (function_exists('nocache_headers')) {
                nocache_headers();
            }

            if (!headers_sent() && wp_safe_redirect($url, 302)) {
                exit;
            }
        }

        // Cache control
        if (isset($_REQUEST['_rstr_nocache'])) {
            // Clear cache
            if (function_exists('nocache_headers')) {
                nocache_headers();
            }

            // Remove cache param
            if (!headers_sent() && wp_safe_redirect(remove_query_arg('_rstr_nocache'), 302)) {
                exit;
            }
        }
    }

    /*
     * Register Plugin Activation
     */
    public static function register_activation(): ?bool
    {
        if (function_exists('current_user_can') && !current_user_can('activate_plugins')) {
            return null;
        }

        // Unload textdomain before is loaded
        if (is_textdomain_loaded('serbian-transliteration')) {
            unload_textdomain('serbian-transliteration');
        }

        // Save version and set activation date
        add_option('serbian-transliteration-version', RSTR_VERSION, '', 'no');

        $activation   = get_option('serbian-transliteration-activation', []);
        $activation[] = date('Y-m-d H:i:s');
        update_option('serbian-transliteration-activation', $activation);

        // Generate unique ID
        if (!get_option('serbian-transliteration-ID')) {
            add_option('serbian-transliteration-ID', Transliteration_Utilities::generate_token(64));
        }

        // Set default options if not set
        $options = get_option('serbian-transliteration', Transliteration_Utilities::plugin_default_options());
        $options = array_merge(Transliteration_Utilities::plugin_default_options(), $options);
        add_option('serbian-transliteration', $options);

        // Set important cookie
        $firstVisitMode      = get_rstr_option('first-visit-mode');
        $transliterationMode = get_rstr_option('transliteration-mode');

        if (!isset($_COOKIE['rstr_script'])) {
            if (in_array($firstVisitMode, ['lat', 'cyr'])) {
                Transliteration_Utilities::setcookie($firstVisitMode);
            } else {
                $mode = $transliterationMode === 'cyr_to_lat' ? 'lat' : 'cyr';
                Transliteration_Utilities::setcookie($mode);
            }
        }

        // Install database tables
        if (RSTR_DATABASE_VERSION !== get_option('serbian-transliteration-db-version')) {
            Transliteration_Cache_DB::table_install();
            update_option('serbian-transliteration-db-version', RSTR_DATABASE_VERSION, false);
        }

        if (function_exists('flush_rewrite_rules')) {
            flush_rewrite_rules();
        }

        return true;
    }

    /*
     * Register Plugin Deactivation
     */
    public static function register_deactivation(): void
    {
        if (function_exists('current_user_can') && !current_user_can('activate_plugins')) {
            return;
        }

        // Unload textdomain
        if (function_exists('is_textdomain_loaded') && is_textdomain_loaded('serbian-transliteration')) {
            unload_textdomain('serbian-transliteration');
        }

        // Reset table check
        delete_option('serbian-transliteration-db-cache-table-exists');

        // Delete old translations
        Transliteration_Utilities::clear_plugin_translations();

        // Add deactivation date
        $deactivation   = get_option('serbian-transliteration-deactivation', []);
        $deactivation[] = date('Y-m-d H:i:s');
        update_option('serbian-transliteration-deactivation', $deactivation);

        // Clear plugin cache
        Transliteration_Utilities::clear_plugin_cache();

        // Reset permalinks
        if (function_exists('flush_rewrite_rules')) {
            flush_rewrite_rules();
        }
    }

    /*
     * Register Plugin Updater
     */
    public static function register_updater($upgrader_object, $options): void
    {
        if (function_exists('current_user_can') && !current_user_can('activate_plugins')) {
            return;
        }

        if (isset($options['action'], $options['type'], $options['plugins']) && $options['action'] == 'update' && $options['type'] == 'plugin' && in_array(plugin_basename(RSTR_FILE), $options['plugins'])) {
            // Reset table check
            delete_option('serbian-transliteration-db-cache-table-exists');
            // Delete old translations
            Transliteration_Utilities::clear_plugin_translations();
            // Install database tables
            if (RSTR_DATABASE_VERSION !== get_option('serbian-transliteration-db-version')) {
                Transliteration_Cache_DB::table_install();
                update_option('serbian-transliteration-db-version', RSTR_DATABASE_VERSION, false);
            }
            // Clear plugin cache
            Transliteration_Utilities::clear_plugin_cache();
            // Reset permalinks
            if (function_exists('flush_rewrite_rules')) {
                flush_rewrite_rules();
            }
            // Save version
            $current_version = get_option('serbian-transliteration-version');
            if ($current_version !== RSTR_VERSION) {
                update_option('serbian-transliteration-version', RSTR_VERSION, true);
            }
        }
    }

    /*
     * Cheeck Plugin Update
     */
    public static function check_plugin_update(): void
    {
        $current_version = get_option('serbian-transliteration-version');

        // Proveri ako se verzija promenila
        if ($current_version !== RSTR_VERSION) {
            // Reset table check
            delete_option('serbian-transliteration-db-cache-table-exists');

            // Delete old translations
            Transliteration_Utilities::clear_plugin_translations();

            // Install database tables
            if (RSTR_DATABASE_VERSION !== get_option('serbian-transliteration-db-version')) {
                Transliteration_Cache_DB::table_install();
                update_option('serbian-transliteration-db-version', RSTR_DATABASE_VERSION, false);
            }

            // Clear plugin cache
            Transliteration_Utilities::clear_plugin_cache();

            // Reset permalinks
            if (function_exists('flush_rewrite_rules')) {
                flush_rewrite_rules();
            }

            // Save new version
            update_option('serbian-transliteration-version', RSTR_VERSION, true);
        }
    }

    /*
     * Redirect after activation
     */
    public static function register_redirection(): void
    {
        add_action('activated_plugin', function ($plugin): void {
            if ($plugin == RSTR_BASENAME && !get_option('serbian-transliteration-activated')) {
                update_option('serbian-transliteration-activated', true);
                if (!headers_sent() && wp_safe_redirect(admin_url('options-general.php?page=transliteration-settings&rstr-activation=true'), 302)) {
                    exit;
                }
            }
        }, 10, 1);
    }
}