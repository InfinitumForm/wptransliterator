<?php
/** Loopback-only browser fixture. Requires a disposable WordPress test database. */
if ('cli-server' !== PHP_SAPI || '1' !== getenv('RSTR_TEST_DISPOSABLE') || !getenv('RSTR_TEST_WP_ROOT')) {
    exit;
}
define('WP_ADMIN', true);
$route = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if ('/wp-admin/admin-ajax.php' === $route) {
    define('DOING_AJAX', true);
}
require rtrim(getenv('RSTR_TEST_WP_ROOT'), '/\\') . '/wp-load.php';
wp_set_current_user(1);
register_post_type('product', ['public' => true, 'rewrite' => ['slug' => 'shop']]);
register_taxonomy('product_cat', 'product', ['public' => true, 'hierarchical' => true, 'rewrite' => ['slug' => 'product-category', 'hierarchical' => true]]);
register_taxonomy('rstr_tree', 'post', ['public' => true, 'hierarchical' => true, 'rewrite' => ['slug' => 'tree', 'hierarchical' => true]]);
register_taxonomy('rstr_flat', 'post', ['public' => true, 'rewrite' => ['slug' => 'flat']]);
add_filter('transliteration_permalink_transliteration_batch_size', static function () { return 2; });
if ('/wp-admin/admin-ajax.php' === $route) {
    do_action('wp_ajax_rstr_run_permalink_transliteration');
}
if ('/wp-admin/admin-post.php' === $route) {
    do_action('admin_post_rstr_permalink_csv');
}
if (in_array($route, ['/admin.js', '/admin.min.js'], true)) {
    header('Content-Type: application/javascript');
    readfile(WP_PLUGIN_DIR . '/wptransliterator/assets/js' . $route);
    exit;
}
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><title>Permalink integration fixture</title>
<style>[hidden]{display:none!important}body{font:14px sans-serif;margin:24px}td,th{padding:6px;vertical-align:top;max-width:260px;overflow-wrap:anywhere}.notice{padding:8px;background:#eef}.button{padding:6px}#rstr-progress-bar{max-width:600px}progress{width:100%}</style>
</head><body>
<?php
require WP_PLUGIN_DIR . '/wptransliterator/classes/settings/page-permalinks.php';
do_action('admin_enqueue_scripts', 'settings_page_transliteration-settings');
$data = wp_scripts()->get_data('transliteration-admin', 'data');
echo '<script>' . $data . '</script>';
?>
<script src="<?php echo isset($_GET['production']) ? '/admin.min.js' : '/admin.js'; ?>"></script>
</body></html>
