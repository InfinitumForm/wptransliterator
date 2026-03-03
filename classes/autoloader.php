<?php

if (!defined('WPINC')) {
    die();
}

if (!class_exists('Transliterator_Autoloader', false)) :

final class Transliterator_Autoloader
{
    /**
     * Prefix to directory mapping.
     *
     * @var array<string, string>
     */
    private static array $prefixes = [
        'Transliteration__'       => RSTR_CLASSES . '/traits/',
        'Transliteration_Map_'    => RSTR_CLASSES . '/maps/',
        'Transliteration_Mode_'   => RSTR_CLASSES . '/modes/',
        'Transliteration_Plugin_' => RSTR_CLASSES . '/plugins/',
        'Transliteration_Theme_'  => RSTR_CLASSES . '/themes/',
        'Transliteration_'        => RSTR_CLASSES . '/',
    ];

    /**
     * Local in-memory class map cache.
     *
     * @var array<string, string>
     */
    private static array $class_map_cache = [];

    /**
     * APCu availability flag.
     */
    private static bool $apcu_exists = false;

    /**
     * APCu cache key (namespaced by plugin path + version + PHP).
     */
    private static string $apcu_key = '';

    /**
     * Plugin root realpath (for secure path validation).
     */
    private static string $root_realpath = '';

    /**
     * Whether autoloader has already been registered.
     */
    private static bool $registered = false;

    /**
     * Initialize the autoloader.
     *
     * @param string $plugin_version Plugin version used to namespace APCu cache.
     */
    public static function init(string $plugin_version): void
    {
        if (self::$registered) {
            return;
        }

        self::bootstrapPaths($plugin_version);

        // Load APCu map if available.
        if (self::$apcu_exists && self::$apcu_key !== '') {
            $cached = apcu_fetch(self::$apcu_key, $success);
            if ($success && is_array($cached)) {
                self::$class_map_cache = $cached;
            }
        }

        // Register and prepend to ensure we get first chance to load our classes.
        spl_autoload_register([self::class, 'autoload'], true, true);

        self::$registered = true;
    }

    /**
     * Autoload function to resolve and load classes.
     *
     * @param string $class_name The name of the class to load.
     */
    public static function autoload(string $class_name): void
    {
        if ($class_name === self::class) {
            return;
        }

        // If already loaded, stop.
        if (
            class_exists($class_name, false) ||
            interface_exists($class_name, false) ||
            trait_exists($class_name, false)
        ) {
            return;
        }

        // Fast path: cached class map.
        if (isset(self::$class_map_cache[$class_name])) {
            self::requireIfSafe(self::$class_map_cache[$class_name]);
            return;
        }

        foreach (self::$prefixes as $prefix => $directory) {
            if (strncmp($class_name, $prefix, strlen($prefix)) !== 0) {
                continue;
            }

            $file = self::resolveClassFile($prefix, $class_name, $directory);
            if ($file === '' || !is_file($file)) {
                continue;
            }

            // Cache it for future.
            self::$class_map_cache[$class_name] = $file;

            if (self::$apcu_exists && self::$apcu_key !== '') {
                apcu_store(self::$apcu_key, self::$class_map_cache);
            }

            self::requireIfSafe($file);
            return;
        }
    }

    /**
     * Purge APCu cache for current namespace (safe to call on update).
     *
     * Note: This requires $plugin_version to ensure the correct key is targeted.
     * If not provided, it will fall back to RSTR_VERSION (if defined).
     *
     * @param string|null $plugin_version Plugin version used to namespace APCu cache.
     */
    public static function purgeCache(?string $plugin_version = null): void
    {
        $version = $plugin_version;
        if ($version === null || $version === '') {
            $version = defined('RSTR_VERSION') ? (string) RSTR_VERSION : '0';
        }

        self::bootstrapPaths($version);

        if (self::$apcu_exists && self::$apcu_key !== '') {
            apcu_delete(self::$apcu_key);
        }

        self::$class_map_cache = [];
    }

    /**
     * Resolve the class file path based on prefix, class name, and directory.
     *
     * @param  string $prefix     The prefix for the class.
     * @param  string $class_name The full class name.
     * @param  string $directory  The directory associated with the prefix.
     * @return string The resolved file path.
     */
    private static function resolveClassFile(string $prefix, string $class_name, string $directory): string
    {
        $class_file = substr($class_name, strlen($prefix));
        if ($class_file === '' || $class_file === false) {
            return '';
        }

        if ($prefix === 'Transliteration_Map_') {
            // Keep underscores for Transliteration_Map_.
            $class_file = str_replace('-', '_', $class_file);
        } else {
            // Convert underscores to hyphens and lowercase for other prefixes.
            $class_file = strtolower(str_replace('_', '-', $class_file));
        }

        return rtrim($directory, '/\\') . '/' . $class_file . '.php';
    }

    /**
     * Require file only if it's inside plugin root (realpath guard).
     *
     * @param string $file Absolute or relative file path.
     */
    private static function requireIfSafe(string $file): void
    {
        $real = realpath($file);
        if ($real === false) {
            return;
        }

        if (self::$root_realpath === '') {
            self::$root_realpath = (string) realpath((string) RSTR_ROOT);
        }

        // Ensure file belongs to this plugin (avoid conflicts/injections).
        if (self::$root_realpath === '' || strpos($real, self::$root_realpath) !== 0) {
            return;
        }

        require_once $real;
    }

    /**
     * Bootstrap computed paths and APCu key for the given plugin version.
     *
     * @param string $plugin_version Plugin version used to namespace APCu cache.
     */
    private static function bootstrapPaths(string $plugin_version): void
    {
        $root = (string) realpath((string) RSTR_ROOT);

        self::$root_realpath = $root;

        self::$apcu_exists =
            function_exists('apcu_fetch') &&
            function_exists('apcu_store') &&
            function_exists('apcu_delete') &&
            function_exists('apcu_enabled') &&
            apcu_enabled();

        if ($root === '') {
            // If root cannot be resolved, disable APCu caching to avoid unsafe paths.
            self::$apcu_exists = false;
            self::$apcu_key = '';
            return;
        }

        $salt = $root . '|' . $plugin_version . '|' . PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION;
        self::$apcu_key = 'rstr_classmap_' . hash('sha256', $salt);
    }
}

endif;