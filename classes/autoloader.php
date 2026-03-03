<?php

if (!defined('WPINC')) {
    die();
}

if (!class_exists('Transliterator_Autoloader', false)) : final class Transliterator_Autoloader
{
    /**
     * Prefix to directory mapping.
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
     * Static cache array to store resolved class paths.
     */
    private static array $class_map_cache = [];

    /**
     * Flag to check if APCu is available.
     */
    private static bool $apcu_exists;

    /**
     * Initialize the autoloader.
     */
    public static function init(): void
    {
        // Check if APCu is available
        self::$apcu_exists = function_exists('apcu_fetch');

        // Load cached class map if APCu is available
        if (self::$apcu_exists) {
            self::$class_map_cache = apcu_fetch('rstr_class_map_cache') ?: [];
        }

        // Register the autoloader
        spl_autoload_register([self::class, 'autoload']);
    }

    /**
     * Autoload function to resolve and load classes.
     *
     * @param string $class_name The name of the class to load.
     */
    public static function autoload(string $class_name): void
    {
        // Prevent autoloading this class
        if ($class_name === self::class) {
            return;
        }

        // Check if the class is already cached
        if (isset(self::$class_map_cache[$class_name])) {
            if (!class_exists($class_name, false)) {
                // Keep the code under the plugin to avoid conflicts
                if (strpos(self::$class_map_cache[$class_name], (string) RSTR_ROOT) === false) {
                    return;
                }

                // Load the file
                require_once self::$class_map_cache[$class_name];
            }

            // Stop on the loaded class
            return;
        }

        // Iterate over the prefix mappings
        foreach (self::$prefixes as $prefix => $directory) {
            // Check if the class name starts with the prefix
            if (strncmp($class_name, $prefix, strlen($prefix)) === 0 && !class_exists($class_name, false)) {
                // Resolve the class file path
                $file = self::resolveClassFile($prefix, $class_name, $directory);

                // Keep the code under the plugin to avoid conflicts
                if (strpos($file, (string) RSTR_ROOT) === false) {
                    continue;
                }

                // Check if the file exists and load it
                if (file_exists($file)) {
                    self::$class_map_cache[$class_name] = $file;

                    if (self::$apcu_exists) {
                        apcu_store('rstr_class_map_cache', self::$class_map_cache);
                    }

                    // Load the file
                    require_once $file;
                    return;
                }
            }
        }
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
        // Remove the prefix from the class name
        $class_file = str_replace($prefix, '', $class_name);

        // Handle different naming conventions
        if ($prefix === 'Transliteration_Map_') {
            // Retain underscores for Transliteration_Map_
            $class_file = str_replace('-', '_', $class_file);
        } else {
            // Convert underscores to hyphens and lowercase for other prefixes
            $class_file = strtolower(str_replace('_', '-', $class_file));
        }

        return $directory . $class_file . '.php';
    }
} endif;
