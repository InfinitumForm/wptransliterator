<?php

/**
 * Uninstall plugin and clean everything
 *
 * @link              http://infinitumform.com/
 * @package           Serbian_Transliteration
 */

// If uninstall not called from WordPress, then exit.
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

global $wpdb;

$RSTR_NAME = 'serbian-transliteration';

// Brisanje opcija u petlji
$options = [
    $RSTR_NAME,
    $RSTR_NAME . '-ID',
    $RSTR_NAME . '-activation',
    $RSTR_NAME . '-deactivation',
    $RSTR_NAME . '-term-script',
    $RSTR_NAME . '-html-tags',
    $RSTR_NAME . '-reviewed',
    $RSTR_NAME . '-version',
    $RSTR_NAME . '-db-version',
    $RSTR_NAME . '-activated',
    $RSTR_NAME . '-db-cache-table-exists',
];

foreach ($options as $option) {
    delete_option($option);
}

// Provera i brisanje termina u petlji
$terms = ['lat', 'cyr'];
foreach ($terms as $term_slug) {
    if (term_exists($term_slug, 'rstr-script')) {
        wp_delete_term(get_term_by('slug', $term_slug, 'rstr-script')->term_id, 'rstr-script');
    }
}

// Brisanje kolačića
if (!headers_sent()) {
    setcookie('rstr_script', '', time() - YEAR_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN);
}

// Brisanje transienta u petlji
$transients = [
    $RSTR_NAME . '-skip-words',
    $RSTR_NAME . '-diacritical-words',
    $RSTR_NAME . '-locales',
];

foreach ($transients as $transient) {
    delete_transient($transient);
}

// SQL upiti za brisanje opcija i tabele
if ($wpdb) {
    $wpdb->query("DELETE FROM `{$wpdb->options}` WHERE `{$wpdb->options}`.`option_name` REGEXP '^_transient_(.*)?{$RSTR_NAME}(.*|$)'");
    $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}rstr_cache");
}

// Brisanje jezičkih fajlova
$patterns = [
    path_join(WP_LANG_DIR, 'plugins') . '/' . $RSTR_NAME . '-*.{po,mo}',
    WP_LANG_DIR . '/' . $RSTR_NAME . '-*.{po,mo}',
];

foreach ($patterns as $pattern) {
    $files = glob($pattern, GLOB_BRACE);
    if ($files) {
        foreach ($files as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
    }
}
//-END
