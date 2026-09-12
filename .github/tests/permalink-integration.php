<?php
/**
 * Run against a disposable, installed WordPress database ONLY:
 * RSTR_TEST_WP_ROOT=/private/wordpress php .github/tests/permalink-integration.php
 * The fixture creates posts, terms and users. Never run on a real site.
 */
error_reporting(E_ALL & ~E_DEPRECATED);
register_shutdown_function(static function () {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        fwrite(STDERR, $error['message'] . ' in ' . $error['file'] . ':' . $error['line'] . "\n");
    }
});
if (!getenv('RSTR_TEST_WP_ROOT') || !getenv('RSTR_TEST_DISPOSABLE')) {
    exit("Set RSTR_TEST_WP_ROOT and RSTR_TEST_DISPOSABLE=1 for an isolated test site.\n");
}
define('WP_ADMIN', true);
define('DOING_AJAX', true);
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['REQUEST_URI'] = '/wp-admin/admin-ajax.php';
$_REQUEST['action'] = 'rstr_run_permalink_transliteration';
require rtrim(getenv('RSTR_TEST_WP_ROOT'), '/\\') . '/wp-load.php';
wp_set_current_user(1);
update_option('WPLANG', 'sr_RS');
update_option('serbian-transliteration', ['site-script' => 'cyr', 'mode' => 'standard', 'language-scheme' => 'sr_RS', 'permalink-transliteration' => 'yes']);
update_option('permalink_structure', '/%category%/%postname%/');
update_option('category_base', 'news');
update_option('tag_base', 'topics');
$wp_rewrite->init();
register_post_type('product', ['public' => true, 'rewrite' => ['slug' => 'shop'], 'supports' => ['title']]);
register_taxonomy('product_cat', 'product', ['public' => true, 'hierarchical' => true, 'rewrite' => ['slug' => 'product-category', 'hierarchical' => true]]);
register_taxonomy('rstr_tree', 'post', ['public' => true, 'hierarchical' => true, 'rewrite' => ['slug' => 'tree', 'hierarchical' => true]]);
register_taxonomy('rstr_flat', 'post', ['public' => true, 'rewrite' => ['slug' => 'flat']]);
register_taxonomy('rstr_internal', 'post', ['public' => false, 'rewrite' => false]);
require dirname(__DIR__, 2) . '/serbian-transliteration.php';
new Transliteration_Tools();
$settings = new Transliteration_Settings();
$settings->enqueue_admin_scripts('settings_page_transliteration-settings');
check((string) filemtime(RSTR_ROOT . '/assets/js/admin.min.js') === (string) wp_scripts()->registered['transliteration-admin']->ver, 'production admin script uses file modification cache busting');
if (isset($argv[1]) && 'download' === $argv[1]) {
    $saved = json_decode(file_get_contents(getenv('RSTR_TEST_WP_ROOT') . '/test-result.json'), true);
    $_REQUEST = ['job' => $saved['token'], 'nonce' => wp_create_nonce('rstr-permalink-csv')];
    if (isset($argv[2]) && 'report' === $argv[2]) {
        $_REQUEST['report'] = '1';
    }
    Transliteration_Permalink_Job::download();
}

function check($condition, $message) {
    if (!$condition) {
        throw new RuntimeException('FAIL: ' . $message);
    }
    $GLOBALS['assertions'] = ($GLOBALS['assertions'] ?? 0) + 1;
}
class Rstr_Test_Ajax_Exit extends RuntimeException {}
add_filter('wp_die_ajax_handler', static function () {
    return static function ($message = '') { throw new Rstr_Test_Ajax_Exit((string) $message); };
});
function download_rejected(array $input): bool {
    $_REQUEST = array_merge(['nonce' => wp_create_nonce('rstr-permalink-csv')], $input);
    try {
        Transliteration_Permalink_Job::download();
    } catch (Rstr_Test_Ajax_Exit $exit) {
        return '' !== $exit->getMessage();
    }
    return false;
}
function request(array $input): array {
    $_REQUEST = array_merge(['action' => 'rstr_run_permalink_transliteration', 'nonce' => wp_create_nonce('rstr-run-permalink-transliteration')], $input);
    $_POST = $_REQUEST;
    ob_start();
    try {
        do_action('wp_ajax_rstr_run_permalink_transliteration');
    } catch (Rstr_Test_Ajax_Exit $exit) {
        // WordPress JSON endpoint deliberately terminates the request.
    }
    $output = ob_get_clean();
    $response = json_decode($output, true);
    if (!is_array($response)) {
        throw new RuntimeException('Non-JSON AJAX output: ' . $output);
    }
    return $response;
}
function selection($mode): array {
    return ['job' => bin2hex(random_bytes(16)), 'mode' => $mode, 'confirmed' => '1', 'step' => '0', 'post_type' => 'post,page,product', 'taxonomy' => 'category,post_tag,product_cat,rstr_tree,rstr_flat'];
}
function finish(array $input): array {
    for ($attempt = 0; $attempt < 1000; ++$attempt) {
        $response = request($input);
        check(empty($response['error']), 'batch succeeds: ' . ($response['message'] ?? ''));
        $replayed = request($input);
        check($response === $replayed, 'duplicate request returns the saved state');
        if ($response['done']) {
            return $response;
        }
        $input['step'] = (string) $response['step'];
    }
    throw new RuntimeException('Batch did not finish');
}
function directory(array $response): string {
    $root = new ReflectionMethod(Transliteration_Permalink_Job::class, 'root');
    $root->setAccessible(true);
    return $root->invoke(null) . '/' . hash_hmac('sha256', $response['token'] . ':' . get_current_user_id(), wp_salt());
}
function rows(array $response): array {
    $directory = directory($response);
    $rows = [];
    for ($batch = 0; $batch < $response['batches']; ++$batch) {
        $path = $directory . '/' . ('dry_run' === $response['mode'] ? 'batch-' : 'final-') . $batch . '.json';
        foreach (json_decode(file_get_contents($path), true) as $row) {
            $rows[$row['object_type'] . ':' . $row['object_id']] = $row;
        }
    }
    return $rows;
}
function term_fixture($name, $taxonomy, $slug, $parent = 0): int {
    $result = wp_insert_term($name, $taxonomy, ['slug' => $slug, 'parent' => $parent]);
    check(!is_wp_error($result), 'term fixture is valid');
    return (int) $result['term_id'];
}
$suffix = '-' . substr(bin2hex(random_bytes(5)), 0, 8);
$parent = term_fixture('Родитељ' . $suffix, 'rstr_tree', 'родитељ' . $suffix);
$child = term_fixture('Дете' . $suffix, 'rstr_tree', 'дете' . $suffix, $parent);
$unchanged_child = term_fixture('Latin child' . $suffix, 'rstr_tree', 'latin-child' . $suffix, $parent);
$category = term_fixture('Вести из Србије' . $suffix, 'category', 'вести-из-србије' . $suffix);
$tag = term_fixture('Ознака' . $suffix, 'post_tag', 'ознака' . $suffix);
$product_cat = term_fixture('Одећа' . $suffix, 'product_cat', 'одећа' . $suffix);
$latin = term_fixture('Existing latin' . $suffix, 'rstr_flat', 'vesti' . $suffix);
$collision = term_fixture('Existing Cyrillic' . $suffix, 'rstr_flat', 'вести' . $suffix);
$plain = term_fixture('Unchanged' . $suffix, 'rstr_flat', 'unchanged' . $suffix);
$potential_one = term_fixture('First future collision' . $suffix, 'rstr_flat', 'љубав' . $suffix);
$potential_two = term_fixture('Second future collision' . $suffix, 'rstr_flat', 'лјубав' . $suffix);
$empty = term_fixture('Invalid slug' . $suffix, 'rstr_flat', 'invalid' . $suffix);
$wpdb->update($wpdb->terms, ['slug' => '---'], ['term_id' => $empty]);
clean_term_cache($empty, 'rstr_flat');
$post = wp_insert_post(['post_title' => '=Formula, "Unicode Ћ"' . $suffix, 'post_name' => 'чланак' . $suffix, 'post_type' => 'post', 'post_status' => 'publish', 'post_category' => [$category]], true);
$page_parent = wp_insert_post(['post_title' => 'Страна' . $suffix, 'post_name' => 'страна' . $suffix, 'post_type' => 'page', 'post_status' => 'publish'], true);
$page_child = wp_insert_post(['post_title' => 'Друга' . $suffix, 'post_name' => 'друга' . $suffix, 'post_parent' => $page_parent, 'post_type' => 'page', 'post_status' => 'publish'], true);
$unchanged_post = wp_insert_post(['post_title' => 'Latin' . $suffix, 'post_name' => 'latin' . $suffix, 'post_type' => 'post', 'post_status' => 'publish'], true);
$product = wp_insert_post(['post_title' => 'Производ' . $suffix, 'post_name' => 'производ' . $suffix, 'post_type' => 'product', 'post_status' => 'publish'], true);
// Register the same save/slug hooks used by the live plugin after seeding old slugs.
new Transliteration_Wordpress();
add_filter('transliteration_permalink_transliteration_batch_size', static function () { return 2; });
check(isset(Transliteration_Permalink_Job::taxonomies()['product_cat']), 'WooCommerce-style taxonomy discovered through API');
check(!isset(Transliteration_Permalink_Job::taxonomies()['rstr_internal']), 'internal taxonomy excluded');
check(!isset(Transliteration_Permalink_Job::taxonomies()['nav_menu']), 'system taxonomy excluded');
check(!isset(Transliteration_Permalink_Job::taxonomies()['post_format']), 'fixed system post formats excluded');

$invalid = selection('apply');
$invalid['nonce'] = 'invalid';
check(request($invalid)['error'], 'invalid nonce rejected');
$invalid = selection('apply');
$invalid['taxonomy'] = 'rstr_internal';
check(request($invalid)['error'], 'non-public taxonomy rejected');
$invalid = selection('apply');
$invalid['post_type'] = 'revision';
check(request($invalid)['error'], 'non-public post type rejected');
$invalid = selection('apply');
$invalid['confirmed'] = '0';
check(request($invalid)['error'], 'apply requires confirmation');
$invalid = selection('dry_run');
$invalid['post_type'] = '';
$invalid['taxonomy'] = '';
check(request($invalid)['error'], 'empty selection rejected');
wp_set_current_user(0);
check(request(selection('dry_run'))['error'], 'anonymous request rejected');
wp_set_current_user(1);
$_SERVER['REQUEST_METHOD'] = 'GET';
check(request(selection('apply'))['error'], 'GET mutation rejected');
$_SERVER['REQUEST_METHOD'] = 'POST';

$old_child_url = get_term_link($child, 'rstr_tree');
$old_post_url = get_permalink($post);
$old_page_url = get_permalink($page_child);
Transliteration_Controller::get()->cyr_to_lat_sanitize('тест');
$writes = [];
$capture_writes = static function ($sql) use (&$writes) {
    if (preg_match('/^\s*(INSERT|UPDATE|DELETE|REPLACE|CREATE|ALTER|DROP|TRUNCATE)\b/i', $sql)) {
        $writes[] = $sql;
    }
    return $sql;
};
add_filter('query', $capture_writes);
$dry = finish(selection('dry_run'));
remove_filter('query', $capture_writes);
check([] === $writes, 'dry run performs zero database writes: ' . implode('; ', $writes));
check('' === $dry['csv'], 'dry run has no redirect map');
$preview = rows($dry);
check('vesti-iz-srbije' . $suffix === $preview['term:' . $category]['new_slug'], 'Serbian category proposal uses plugin transliteration: ' . wp_json_encode($preview['term:' . $category]));
check('collision_adjusted' === $preview['term:' . $collision]['status'], 'WordPress term uniqueness is previewed');
check('unchanged' === $preview['term:' . $plain]['status'], 'unchanged term is identified');
check('unchanged' === $preview['post:' . $unchanged_post]['status'], 'unchanged post is identified');
check(in_array('potential_collision', [$preview['term:' . $potential_one]['status'], $preview['term:' . $potential_two]['status']], true), 'competing proposals are marked as uncertain');
check('invalid' === $preview['term:' . $empty]['status'], 'empty sanitized proposal is rejected');
check($old_child_url === get_term_link($child, 'rstr_tree'), 'dry run leaves hierarchy unchanged');
$other_admin = wp_create_user('rstr-admin-' . substr($suffix, 1), wp_generate_password(), 'other' . $suffix . '@example.test');
(new WP_User($other_admin))->set_role('administrator');
wp_set_current_user($other_admin);
check(request(['job' => $dry['token'], 'step' => (string) $dry['step']])['error'], 'another administrator cannot access the journal');
wp_set_current_user(1);
$original_blog_id = $blog_id;
$blog_id = $original_blog_id + 100;
check(request(['job' => $dry['token'], 'step' => (string) $dry['step']])['error'], 'job identifiers are scoped to the current blog');
$blog_id = $original_blog_id;

$apply_input = selection('apply');
$first = request($apply_input);
check($first === request($apply_input), 'initial batch replay is idempotent');
check(download_rejected(['job' => $first['token']]), 'partial migration does not expose an incomplete redirect CSV');
check(request(selection('apply'))['error'], 'second migration cannot interleave');
$apply_input['step'] = (string) $first['step'];
$apply = finish($apply_input);
$actual = rows($apply);
check($apply['updated'] >= 10, 'actual migration changes slugs');
check($apply['redirects'] >= 10, 'actual migration records redirects');
check('' !== $apply['csv'], 'completed migration exposes private download');
check(download_rejected(['job' => $apply['token'], 'nonce' => 'invalid']), 'download nonce is enforced');
check($old_child_url === $actual['term:' . $child]['old_url'], 'child original URL captured before parent update');
check(get_term_link($child, 'rstr_tree') === $actual['term:' . $child]['new_url'], 'child final URL includes final parent');
check($old_post_url === $actual['post:' . $post]['old_url'], 'post original URL captured before category update');
check(get_permalink($post) === $actual['post:' . $post]['new_url'], 'post final URL includes final category');
check($old_page_url === $actual['post:' . $page_child]['old_url'], 'child page original URL captured');
check(get_permalink($page_child) === $actual['post:' . $page_child]['new_url'], 'child page final parent URL resolved');
check('url_only_change' === $actual['term:' . $unchanged_child]['status'], 'URL-only descendant changes require review and stay outside redirect CSV');
check('vesti' . $suffix . '-2' === get_term($collision)->slug, 'term collision resolved by WordPress API');
check(get_term($potential_one)->slug !== get_term($potential_two)->slug, 'competing proposals receive unique actual slugs');
check('---' === get_term($empty)->slug, 'invalid proposal does not alter database slug');
check(!empty(get_post_meta($post, '_wp_old_slug')), 'WordPress old post slug mechanism preserved');
$again = finish(selection('apply'));
check(0 === $again['updated'] && '' === $again['csv'], 'second migration is a no-op without an empty CSV');

// Simulate a worker dying after WordPress commits but before the result is journaled.
register_taxonomy('rstr_recovery', 'post', ['public' => true, 'rewrite' => ['slug' => 'recovery']]);
$recovery_id = term_fixture('Recovery' . $suffix, 'rstr_recovery', 'recovery' . $suffix);
$wpdb->update($wpdb->terms, ['slug' => rawurlencode('опоравак') . $suffix], ['term_id' => $recovery_id]);
clean_term_cache($recovery_id, 'rstr_recovery');
$recovery_input = selection('apply');
$recovery_input['post_type'] = '';
$recovery_input['taxonomy'] = 'rstr_recovery';
do {
    $recovery = request($recovery_input);
    check(!$recovery['error'], 'recovery snapshot succeeds');
    $recovery_input['step'] = (string) $recovery['step'];
} while ('scan' === $recovery['phase']);
$recovery_directory = directory($recovery);
$pending_row = json_decode(file_get_contents($recovery_directory . '/batch-0.json'), true)[0];
$pending_row['new_slug'] = 'oporavak' . $suffix;
$pending_row['status'] = 'pending';
file_put_contents($recovery_directory . '/object-term-' . $recovery_id . '.json', wp_json_encode($pending_row));
wp_update_term($recovery_id, 'rstr_recovery', ['slug' => $pending_row['new_slug']]);
$edits = 0;
$count_edits = static function () use (&$edits) { ++$edits; };
add_action('edited_term', $count_edits);
$recovered = finish($recovery_input);
remove_action('edited_term', $count_edits);
check(0 === $edits && 1 === $recovered['updated'], 'retry recovers committed update without updating twice');
check(1 === $recovered['redirects'], 'recovered update creates exactly one redirect');

// The synchronous WP-CLI entry point uses the same scanner, preview, updater and CSV writer.
$cli_post = wp_insert_post(['post_title' => 'CLI Тест ' . $suffix, 'post_name' => 'командни-тест-' . $suffix, 'post_status' => 'publish', 'post_type' => 'post']);
$wpdb->update($wpdb->posts, ['post_name' => rawurlencode('командни-тест') . $suffix], ['ID' => $cli_post]);
clean_post_cache($cli_post);
$cli_original_slug = get_post($cli_post)->post_name;
$cli_report = getenv('RSTR_TEST_WP_ROOT') . '/cli-report.csv';
$cli_redirects = getenv('RSTR_TEST_WP_ROOT') . '/cli-redirects.csv';
$cli_options = ['mode' => 'dry_run', 'posts' => ['post'], 'taxonomies' => [], 'size' => 2, 'report' => $cli_report];
$cli_dry = Transliteration_Permalink_Job::run_cli($cli_options);
check('done' === $cli_dry['phase'] && 'dry_run' === $cli_dry['mode'], 'CLI dry run completes through shared job');
check($cli_original_slug === get_post($cli_post)->post_name, 'CLI dry run does not change database slugs');
check(is_file($cli_report) && false !== strpos(file_get_contents($cli_report), 'will_change'), 'CLI dry run writes complete status report');
$cli_options['mode'] = 'apply';
$cli_options['redirects'] = $cli_redirects;
$cli_apply = Transliteration_Permalink_Job::run_cli($cli_options);
check($cli_original_slug !== get_post($cli_post)->post_name, 'CLI apply transliterates through shared updater');
check($cli_apply['updated'] >= 1 && $cli_apply['redirects'] >= 1, 'CLI apply reports changes and redirects');
check(is_file($cli_redirects) && false !== strpos(file_get_contents($cli_redirects), (string) $cli_post), 'CLI redirect CSV contains changed object');

// Exercise WP-CLI option parsing and output around the shared service without requiring the wp-cli binary.
if (!defined('WP_CLI')) {
    define('WP_CLI', true);
}
class WP_CLI_Command {}
class WP_CLI {
    public static array $messages = [];
    public static function add_command($name, $class): void {}
    public static function confirm($message, $args = []): void { self::$messages[] = $message; }
    public static function log($message): void { self::$messages[] = $message; }
    public static function success($message): void { self::$messages[] = $message; }
    public static function error($message, $exit = true): void { throw new RuntimeException($message); }
}
$wrapper_post = wp_insert_post(['post_title' => 'CLI wrapper ' . $suffix, 'post_status' => 'publish', 'post_type' => 'post']);
$wpdb->update($wpdb->posts, ['post_name' => rawurlencode('омотач') . $suffix], ['ID' => $wrapper_post]);
clean_post_cache($wrapper_post);
$wrapper_slug = get_post($wrapper_post)->post_name;
$wrapper_report = getenv('RSTR_TEST_WP_ROOT') . '/cli-wrapper-report.csv';
(new Transliteration_Wp_Cli())->permalinks([], ['dry-run' => true, 'post-types' => 'post', 'taxonomies' => '', 'batch-size' => 2, 'report' => $wrapper_report]);
check($wrapper_slug === get_post($wrapper_post)->post_name, 'WP-CLI --dry-run wrapper remains read-only');
check(is_file($wrapper_report) && false !== strpos(file_get_contents($wrapper_report), (string) $wrapper_post), 'WP-CLI wrapper forwards report and selection options');
check((bool) array_filter(WP_CLI::$messages, static function ($message) { return false !== strpos($message, 'Operation ID'); }), 'WP-CLI prints resumable operation ID');
$resume_token = '';
try {
    Transliteration_Permalink_Job::run_cli(['mode' => 'dry_run', 'posts' => ['post'], 'taxonomies' => [], 'size' => 2], static function (array $state) use (&$resume_token): void {
        $resume_token = $state['token'];
        throw new RuntimeException('simulated CLI interruption');
    });
} catch (RuntimeException $error) {
    check('simulated CLI interruption' === $error->getMessage(), 'CLI interruption is surfaced to the command');
}
check((bool) preg_match('/^[a-f0-9]{32}$/', $resume_token), 'interrupted CLI operation exposes a resumable ID');
$resumed_cli = Transliteration_Permalink_Job::run_cli(['token' => $resume_token]);
check('done' === $resumed_cli['phase'], 'CLI operation resumes from its saved journal');

// An expired job cannot be resumed even if its private files still exist.
$expired_state = json_decode(file_get_contents(directory($dry) . '/state.json'), true);
$expired_state['expires'] = time() - 1;
file_put_contents(directory($dry) . '/state.json', wp_json_encode($expired_state));
check(request(['job' => $dry['token'], 'step' => (string) $dry['step']])['error'], 'expired journal access is rejected');

$escape = new ReflectionMethod(Transliteration_Permalink_Job::class, 'csv_cell');
$escape->setAccessible(true);
foreach (['=1+1', '+SUM(A1)', '-1+1', '@SUM(A1)', "\t=1", "\r=1", " \n@SUM(A1)", "\xEF\xBB\xBF=1", '＝1+1'] as $formula) {
    check("'" === substr($escape->invoke(null, $formula), 0, 1), 'CSV formula prefix escaped');
}
$csv = fopen('php://temp', 'w+');
$values = ['Unicode Ђ, "quoted"', 'https://example.test/a,b/', $escape->invoke(null, '=1+1')];
fputcsv($csv, $values, ',', '"', '');
rewind($csv);
check($values === fgetcsv($csv, 0, ',', '"', ''), 'standard CSV round trip preserves Unicode, commas and quotes');
fclose($csv);
// Save only temporary test identifiers for the download smoke test.
file_put_contents(getenv('RSTR_TEST_WP_ROOT') . '/test-result.json', wp_json_encode($apply));
echo 'PASS: ' . $GLOBALS['assertions'] . " integration assertions; WordPress $wp_version; PHP " . PHP_VERSION . "\n";
