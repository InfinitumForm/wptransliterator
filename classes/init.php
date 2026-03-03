<?php

if (!defined('WPINC')) {
    die();
}

final class Transliteration_Init extends Transliteration
{
    public function __construct()
    {
        $this->set_admin_cookie_based_on_url();

        // Only register hooks here - do NOT instantiate heavy classes in constructor.
        $this->add_action('plugins_loaded', 'hook_init', 1);
        $this->add_action('template_redirect', 'set_transliteration');
    }

    public function hook_init(): void
    {
        // ----------------------------
        // Main classes
        // ----------------------------
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

        if (!is_array($main_classes)) {
            $main_classes = [];
        }

        foreach ($main_classes as $main_class_name) {
            if (!is_string($main_class_name) || $main_class_name === '') {
                continue;
            }

            // Try autoload; if it fails, skip without fatal.
            if (class_exists($main_class_name, true)) {
                new $main_class_name();
            }
        }

        // ----------------------------
        // Plugin classes
        // ----------------------------
        $classes = apply_filters('transliteration_plugin_classes_init', [
            'Transliteration_Rest',
            'Transliteration_Ajax',
            'Transliteration_Email',
            'Transliteration_Search',
        ]);

        if (!is_array($classes)) {
            $classes = [];
        }

        $classes = apply_filters('transliteration_init_classes', $classes);

        if (!is_array($classes)) {
            $classes = [];
        }

        foreach ($classes as $class_name) {
            if (!is_string($class_name) || $class_name === '') {
                continue;
            }

            if (class_exists($class_name, true)) {
                new $class_name();
            }
        }
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

        $request_uri = (string) ($_SERVER['REQUEST_URI'] ?? '');

        // Never touch cookies during AJAX.
        if (strpos($request_uri, '/admin-ajax.php') !== false || (function_exists('wp_doing_ajax') && wp_doing_ajax())) {
            return;
        }

        $is_admin_request = (strpos($request_uri, '/wp-admin/') !== false) || (function_exists('is_admin') && is_admin());

        // If WP cookie constants are not available yet, avoid emitting warnings/notices.
        if (!defined('COOKIEHASH') || !defined('COOKIEPATH') || !defined('COOKIE_DOMAIN')) {
            $rstr_is_admin = $is_admin_request;
            return;
        }

        if (!headers_sent()) {
            setcookie(
                'rstr_test_' . COOKIEHASH,
                $is_admin_request ? 'true' : 'false',
                [
                    'expires'  => 0,
                    'path'     => COOKIEPATH,
                    'domain'   => COOKIE_DOMAIN,
                    'secure'   => is_ssl(),
                    'httponly' => true,
                    'samesite' => 'Lax',
                ]
            );
        }

        $rstr_is_admin = $is_admin_request;
    }

    /**
     * Set transliteration & redirections.
     */
    public function set_transliteration(): void
    {
        if (function_exists('wp_doing_ajax') && wp_doing_ajax()) {
            return;
        }

        $url_selector = (string) get_rstr_option('url-selector', 'rstr');

        if ($url_selector === '') {
            $url_selector = 'rstr';
        }

        $request = $_REQUEST[$url_selector] ?? null;

        if (in_array($request, apply_filters('rstr/allowed_script', ['cyr', 'lat']), true)) {
            Transliteration_Utilities::setcookie((string) $request);

            $url = remove_query_arg($url_selector);

            if (get_rstr_option('cache-support', 'no') === 'yes') {
                $url = add_query_arg(
                    '_rstr_nocache',
                    uniqid($url_selector . random_int(1000, 9999), true),
                    $url
                );

                Transliteration_Utilities::cache_flush();
            } elseif (function_exists('nocache_headers')) {
                nocache_headers();
            }

            if (!headers_sent() && wp_safe_redirect($url, 302)) {
                exit;
            }
        }

        if (isset($_REQUEST['_rstr_nocache'])) {
            if (function_exists('nocache_headers')) {
                nocache_headers();
            }

            if (!headers_sent() && wp_safe_redirect(remove_query_arg('_rstr_nocache'), 302)) {
                exit;
            }
        }
    }

    /**
     * Register Plugin Activation.
     */
    public static function register_activation(): ?bool
    {
        if (function_exists('current_user_can') && !current_user_can('activate_plugins')) {
            return null;
        }

        if (function_exists('is_textdomain_loaded') && is_textdomain_loaded('serbian-transliteration')) {
            unload_textdomain('serbian-transliteration');
        }

        add_option('serbian-transliteration-version', RSTR_VERSION, '', 'no');

        $activation   = (array) get_option('serbian-transliteration-activation', []);
        $activation[] = gmdate('Y-m-d H:i:s');
        update_option('serbian-transliteration-activation', $activation);

        if (!get_option('serbian-transliteration-ID')) {
            add_option('serbian-transliteration-ID', Transliteration_Utilities::generate_token(64));
        }

        $options = get_option('serbian-transliteration', Transliteration_Utilities::plugin_default_options());
        $options = array_merge(Transliteration_Utilities::plugin_default_options(), (array) $options);
        add_option('serbian-transliteration', $options);

        $firstVisitMode      = get_rstr_option('first-visit-mode');
        $transliterationMode = get_rstr_option('transliteration-mode');

        if (!isset($_COOKIE['rstr_script'])) {
            if (in_array($firstVisitMode, ['lat', 'cyr'], true)) {
                Transliteration_Utilities::setcookie($firstVisitMode);
            } else {
                $mode = ($transliterationMode === 'cyr_to_lat') ? 'lat' : 'cyr';
                Transliteration_Utilities::setcookie($mode);
            }
        }

        if (RSTR_DATABASE_VERSION !== get_option('serbian-transliteration-db-version')) {
            Transliteration_Cache_DB::table_install();
            update_option('serbian-transliteration-db-version', RSTR_DATABASE_VERSION, false);
        }

        if (function_exists('flush_rewrite_rules')) {
            flush_rewrite_rules();
        }

        // Purge class map after activation to avoid stale APCu maps.
        if (class_exists('Transliterator_Autoloader', false)) {
            Transliterator_Autoloader::purgeCache(RSTR_VERSION);
        }

        return true;
    }

    /**
     * Register Plugin Deactivation.
     */
    public static function register_deactivation(): void
    {
        if (function_exists('current_user_can') && !current_user_can('activate_plugins')) {
            return;
        }

        if (function_exists('is_textdomain_loaded') && is_textdomain_loaded('serbian-transliteration')) {
            unload_textdomain('serbian-transliteration');
        }

        delete_option('serbian-transliteration-db-cache-table-exists');

        Transliteration_Utilities::clear_plugin_translations();

        $deactivation   = (array) get_option('serbian-transliteration-deactivation', []);
        $deactivation[] = gmdate('Y-m-d H:i:s');
        update_option('serbian-transliteration-deactivation', $deactivation);

        Transliteration_Utilities::clear_plugin_cache();

        if (function_exists('flush_rewrite_rules')) {
            flush_rewrite_rules();
        }
    }

    /**
     * Register Plugin Updater.
     *
     * @param mixed $upgrader_object Unused.
     * @param array $options Update options.
     */
    public static function register_updater($upgrader_object, $options): void
    {
        if (function_exists('current_user_can') && !current_user_can('activate_plugins')) {
            return;
        }

        if (
            isset($options['action'], $options['type'], $options['plugins']) &&
            $options['action'] === 'update' &&
            $options['type'] === 'plugin' &&
            in_array(plugin_basename(RSTR_FILE), (array) $options['plugins'], true)
        ) {
            delete_option('serbian-transliteration-db-cache-table-exists');

            Transliteration_Utilities::clear_plugin_translations();

            if (RSTR_DATABASE_VERSION !== get_option('serbian-transliteration-db-version')) {
                Transliteration_Cache_DB::table_install();
                update_option('serbian-transliteration-db-version', RSTR_DATABASE_VERSION, false);
            }

            Transliteration_Utilities::clear_plugin_cache();

            if (function_exists('flush_rewrite_rules')) {
                flush_rewrite_rules();
            }

            $current_version = get_option('serbian-transliteration-version');
            if ($current_version !== RSTR_VERSION) {
                update_option('serbian-transliteration-version', RSTR_VERSION, true);
            }

            // Purge APCu map after update.
            if (class_exists('Transliterator_Autoloader', false)) {
                Transliterator_Autoloader::purgeCache(RSTR_VERSION);
            }
        }
    }

    /**
     * Check Plugin Update (manual update detection).
     */
    public static function check_plugin_update(): void
    {
        $current_version = get_option('serbian-transliteration-version');

        if ($current_version !== RSTR_VERSION) {
            delete_option('serbian-transliteration-db-cache-table-exists');

            Transliteration_Utilities::clear_plugin_translations();

            // Purge APCu map before doing anything else.
            if (class_exists('Transliterator_Autoloader', false)) {
                Transliterator_Autoloader::purgeCache(RSTR_VERSION);
            }

            if (RSTR_DATABASE_VERSION !== get_option('serbian-transliteration-db-version')) {
                Transliteration_Cache_DB::table_install();
                update_option('serbian-transliteration-db-version', RSTR_DATABASE_VERSION, false);
            }

            Transliteration_Utilities::clear_plugin_cache();

            if (function_exists('flush_rewrite_rules')) {
                flush_rewrite_rules();
            }

            update_option('serbian-transliteration-version', RSTR_VERSION, true);
        }
    }

    /**
     * Redirect after activation.
     */
    public static function register_redirection(): void
    {
        add_action('activated_plugin', function ($plugin): void {
            if ($plugin === RSTR_BASENAME && !get_option('serbian-transliteration-activated')) {
                update_option('serbian-transliteration-activated', true);

                if (!headers_sent() && wp_safe_redirect(admin_url('options-general.php?page=transliteration-settings&rstr-activation=true'), 302)) {
                    exit;
                }
            }
        }, 10, 1);
    }
}