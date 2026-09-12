<?php

if (!defined('WPINC')) {
    die();
}

/**
 * Private, resumable batches for the existing permalink tool.
 *
 * Files live outside the document root, expire after one day, and never contain
 * executable content. Keeping the journal on disk also makes previews DB read-only.
 */
class Transliteration_Permalink_Job
{
    private string $directory;
    private array $state;

    private static function fail(string $message): void
    {
        // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Caught at the entry points; JSON encoded or escaped for wp_die there.
        throw new RuntimeException($message);
    }

    public static function taxonomies(): array
    {
        return array_filter(get_taxonomies([], 'objects'), static function ($taxonomy) {
            return (!$taxonomy->_builtin || in_array($taxonomy->name, ['category', 'post_tag'], true)) && is_taxonomy_viewable($taxonomy) && false !== $taxonomy->rewrite;
        });
    }

    private static function input(string $key): string
    {
        // Authorization is performed at both entry points before reading job input.
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        return isset($_REQUEST[$key]) && is_scalar($_REQUEST[$key]) ? sanitize_text_field(wp_unslash((string) $_REQUEST[$key])) : '';
    }

    private static function authorize(string $action): void
    {
        if (!current_user_can('manage_options') || !wp_verify_nonce(self::input('nonce'), $action)) {
            self::fail(__('Permission denied or expired nonce. Refresh the page and try again.', 'serbian-transliteration'));
        }
    }

    private static function selection(string $key, array $allowed): array
    {
        $selected = array_values(array_unique(array_filter(explode(',', self::input($key)), 'strlen')));
        if (array_diff($selected, $allowed)) {
            self::fail(__('The selection contains an unavailable post type or taxonomy.', 'serbian-transliteration'));
        }
        return $selected;
    }

    private static function count_candidates(array $posts, array $taxonomies): int
    {
        global $wpdb;
        $total = 0;
        foreach ($posts as $post_type) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- One count per selected type, only when starting an operation.
            $total += (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(1) FROM {$wpdb->posts} WHERE post_type = %s AND post_status <> 'trash' AND TRIM(IFNULL(post_name, '')) <> ''", $post_type));
        }
        foreach ($taxonomies as $taxonomy) {
            $count = wp_count_terms(['taxonomy' => $taxonomy, 'hide_empty' => false, 'hierarchical' => false]);
            if (is_wp_error($count)) {
                self::fail($count->get_error_message());
            }
            $total += (int) $count;
        }
        return $total;
    }

    private static function root(): string
    {
        $root = realpath(sys_get_temp_dir());
        if (!$root || !wp_is_writable($root)) {
            self::fail(__('A writable private system temporary directory is required.', 'serbian-transliteration'));
        }
        foreach ([ABSPATH, isset($_SERVER['DOCUMENT_ROOT']) ? sanitize_text_field(wp_unslash($_SERVER['DOCUMENT_ROOT'])) : ABSPATH] as $public) {
            $public = realpath($public);
            if ($public && strpos(strtolower(wp_normalize_path($root) . '/'), strtolower(trailingslashit(wp_normalize_path($public)))) === 0) {
                self::fail(__('The system temporary directory must be outside the public web directory.', 'serbian-transliteration'));
            }
        }
        return $root . '/rstr-permalinks-' . substr(wp_hash(ABSPATH . get_current_blog_id()), 0, 20);
    }

    private static function read(string $path): array
    {
        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Private local journal, never a URL.
        $value = is_file($path) ? json_decode(file_get_contents($path), true) : null;
        if (!is_array($value)) {
            self::fail(__('The operation has expired or its journal is unavailable.', 'serbian-transliteration'));
        }
        return $value;
    }

    private static function write(string $path, array $value): void
    {
        $json = wp_json_encode($value);
        // Atomic replacement allows retry after a worker or network failure.
        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
        if (false === $json || strlen($json) !== file_put_contents($path . '.tmp', $json, LOCK_EX)) {
            self::fail(__('Cannot save the operation journal. Check temporary disk space and retry.', 'serbian-transliteration'));
        }
        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_chmod
        chmod($path . '.tmp', 0600);
        // phpcs:ignore WordPress.WP.AlternativeFunctions.rename_rename
        if (!rename($path . '.tmp', $path)) {
            self::fail(__('Cannot save the operation journal. Check temporary disk space and retry.', 'serbian-transliteration'));
        }
    }

    /** Remove expired journals incrementally, without loading a directory into memory. */
    private static function cleanup(string $root): void
    {
        if (!is_dir($root)) {
            return;
        }
        $budget = 200;
        foreach (new DirectoryIterator($root) as $entry) {
            if ($entry->isDot() || $entry->isLink() || !$entry->isDir() || !preg_match('/^[a-f0-9]{64}$/D', $entry->getFilename())) {
                continue;
            }
            $directory = $entry->getPathname();
            $state_file = $directory . '/state.json';
            if (is_file($state_file) && filemtime($state_file) > time() - DAY_IN_SECONDS) {
                continue;
            }
            foreach (new DirectoryIterator($directory) as $file) {
                if ($file->isFile() && !$file->isLink()) {
                    wp_delete_file($file->getPathname());
                    if (--$budget <= 0) {
                        return;
                    }
                }
            }
            // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir
            rmdir($directory);
        }
    }

    /** Both downloads and mutations share a site-specific lock; no database locks/options. */
    private static function lock(string $root)
    {
        if (!is_dir($root)) {
            // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_mkdir
            if (!mkdir($root, 0700) && !is_dir($root)) {
                self::fail(__('Cannot create the private operation directory.', 'serbian-transliteration'));
            }
        }
        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
        $lock = fopen($root . '/lock', 'c');
        if (!$lock || !flock($lock, LOCK_EX | LOCK_NB)) {
            if ($lock) {
                // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
                fclose($lock);
            }
            self::fail(__('Another permalink request is running. Retry in a moment.', 'serbian-transliteration'));
        }
        return $lock;
    }

    private function __construct(string $root, string $token, bool $create, ?array $options = null)
    {
        if (!preg_match('/^[a-f0-9]{32}$/D', $token)) {
            self::fail(__('Invalid operation identifier.', 'serbian-transliteration'));
        }
        $this->directory = $root . '/' . hash_hmac('sha256', $token . ':' . get_current_user_id(), wp_salt());
        if (!is_dir($this->directory) && $create) {
            $allowed_posts = array_keys(get_post_types(['public' => true], 'objects'));
            $allowed_taxonomies = array_keys(self::taxonomies());
            $posts = null === $options ? self::selection('post_type', $allowed_posts) : array_values(array_unique($options['posts'] ?? []));
            $taxonomies = null === $options ? self::selection('taxonomy', $allowed_taxonomies) : array_values(array_unique($options['taxonomies'] ?? []));
            $mode = null === $options ? self::input('mode') : ($options['mode'] ?? '');
            if (array_diff($posts, $allowed_posts) || array_diff($taxonomies, $allowed_taxonomies)) {
                self::fail(__('The selection contains an unavailable post type or taxonomy.', 'serbian-transliteration'));
            }
            if ((!$posts && !$taxonomies) || !in_array($mode, ['dry_run', 'apply'], true) || (null === $options && 'apply' === $mode && '1' !== self::input('confirmed'))) {
                self::fail(__('Select at least one post type or taxonomy and confirm URL changes before applying.', 'serbian-transliteration'));
            }
            $size = apply_filters('transliteration_permalink_transliteration_batch_size', 50);
            $size = apply_filters_deprecated('rstr/permalink-tool/transliteration/offset', [$size], '2.0.0', 'transliteration_permalink_transliteration_batch_size');
            if (null !== $options && isset($options['size'])) {
                $size = $options['size'];
            }
            $this->state = [
                'token' => $token, 'mode' => $mode, 'expires' => time() + DAY_IN_SECONDS,
                'posts' => $posts, 'taxonomies' => $taxonomies, 'size' => max(1, min(1000, absint($size))),
                'phase' => 'scan', 'group' => 0, 'cursor' => PHP_INT_MAX, 'step' => 0,
                'batches' => 0, 'batch' => 0, 'total' => 0, 'updated' => 0, 'redirects' => 0, 'issues' => 0,
                'expected' => self::count_candidates($posts, $taxonomies),
            ];
            // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_mkdir
            if (!mkdir($this->directory, 0700)) {
                self::fail(__('Cannot create the private operation directory.', 'serbian-transliteration'));
            }
            self::write($this->directory . '/state.json', $this->state);
        } else {
            $this->state = self::read($this->directory . '/state.json');
        }
        if ($this->state['expires'] < time()) {
            self::fail(__('The operation has expired. Download reports within 24 hours and start a new operation if needed.', 'serbian-transliteration'));
        }
        // Revalidate against the current site's registry on every continuation/download.
        if (array_diff($this->state['posts'], get_post_types(['public' => true])) || array_diff($this->state['taxonomies'], array_keys(self::taxonomies()))) {
            self::fail(__('A selected post type or taxonomy is no longer available.', 'serbian-transliteration'));
        }
    }

    public static function request(): void
    {
        $lock = null;
        $resume = null;
        try {
            self::authorize('rstr-run-permalink-transliteration');
            if ('POST' !== (isset($_SERVER['REQUEST_METHOD']) ? sanitize_text_field(wp_unslash($_SERVER['REQUEST_METHOD'])) : '')) {
                self::fail(__('Permalink operations require a POST request.', 'serbian-transliteration'));
            }
            $root = self::root();
            $lock = self::lock($root);
            self::cleanup($root);
            $step = self::input('step');
            if (!ctype_digit($step)) {
                self::fail(__('Invalid batch sequence.', 'serbian-transliteration'));
            }
            $job = new self($root, self::input('job'), '0' === $step);
            // Prevent two apply jobs from interleaving between AJAX requests.
            if ('apply' === $job->state['mode']) {
                $lease_file = $root . '/active.json';
                $lease = is_file($lease_file) ? self::read($lease_file) : [];
                if ($lease && $lease['expires'] > time() && $lease['job'] !== $job->directory) {
                    if (isset($lease['owner'], $lease['token']) && get_current_user_id() === $lease['owner']) {
                        $resume = ['job' => $lease['token'], 'step' => 0, 'mode' => 'apply', 'expires' => $lease['expires']];
                    }
                    self::fail(__('Another migration is active. Resume it or wait for its 24-hour expiry.', 'serbian-transliteration'));
                }
                self::write($lease_file, ['job' => $job->directory, 'expires' => $job->state['expires'], 'owner' => get_current_user_id(), 'token' => $job->state['token']]);
            }
            if ((int) $step > $job->state['step']) {
                self::fail(__('Invalid batch sequence. Resume the last acknowledged batch.', 'serbian-transliteration'));
            }
            if ((int) $step === $job->state['step'] && 'done' !== $job->state['phase']) {
                $job->advance();
                ++$job->state['step'];
                self::write($job->directory . '/state.json', $job->state);
            }
            if ('done' === $job->state['phase'] && 'apply' === $job->state['mode']) {
                wp_delete_file($lease_file);
            }
            $response = $job->response();
        } catch (Throwable $error) {
            $response = ['error' => true, 'message' => $error->getMessage(), 'resume' => $resume];
        } finally {
            if ($lock) {
                flock($lock, LOCK_UN);
                // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
                fclose($lock);
            }
        }
        wp_send_json($response);
    }

    /** Run the same journalled operation synchronously for WP-CLI. */
    public static function run_cli(array $options, ?callable $progress = null): array
    {
        $lock = null;
        $lease_file = '';
        $job = null;
        try {
            $root = self::root();
            $lock = self::lock($root);
            self::cleanup($root);
            $token = isset($options['token']) ? (string) $options['token'] : bin2hex(random_bytes(16));
            $job = new self($root, $token, !isset($options['token']), $options);
            $lease_file = $root . '/active.json';
            if ('apply' === $job->state['mode']) {
                $lease = is_file($lease_file) ? self::read($lease_file) : [];
                if ($lease && $lease['expires'] > time() && $lease['job'] !== $job->directory) {
                    self::fail(__('Another migration is active. Resume it or wait for its 24-hour expiry.', 'serbian-transliteration'));
                }
                self::write($lease_file, ['job' => $job->directory, 'expires' => $job->state['expires'], 'owner' => get_current_user_id(), 'token' => $token]);
            }
            while ('done' !== $job->state['phase']) {
                $job->advance();
                ++$job->state['step'];
                self::write($job->directory . '/state.json', $job->state);
                if ($progress) {
                    $progress($job->response());
                }
            }
            if ('apply' === $job->state['mode']) {
                wp_delete_file($lease_file);
                $lease_file = '';
            }
            if (!empty($options['report'])) {
                $job->write_csv_file((string) $options['report'], true);
            }
            if (!empty($options['redirects']) && $job->state['redirects'] > 0) {
                $job->write_csv_file((string) $options['redirects'], false);
            }
            return $job->response();
        } finally {
            if ($lease_file && $job && 'done' === $job->state['phase']) {
                wp_delete_file($lease_file);
            }
            if ($lock) {
                flock($lock, LOCK_UN);
                // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
                fclose($lock);
            }
        }
    }

    /** ID cursor prevents deletions or slug changes from shifting subsequent pages. */
    private function ids(string $kind, string $name): array
    {
        global $wpdb;
        if ('post' === $kind) {
            // Preserve the existing post eligibility and descending ID order.
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Bounded migration scan must read current database values.
            return $wpdb->get_col($wpdb->prepare("SELECT ID FROM {$wpdb->posts} WHERE post_type = %s AND post_status <> 'trash' AND TRIM(IFNULL(post_name, '')) <> '' AND ID < %d ORDER BY ID DESC LIMIT %d", $name, $this->state['cursor'], $this->state['size']));
        }
        $cursor = $this->state['cursor'];
        $filter = static function ($clauses, $taxonomies, $args) use ($cursor, $wpdb) {
            if (!empty($args['rstr_permalink_scan'])) {
                $clauses['where'] .= $wpdb->prepare(' AND t.term_id < %d', $cursor);
            }
            return $clauses;
        };
        add_filter('terms_clauses', $filter, 10, 3);
        try {
            $ids = get_terms(['taxonomy' => $name, 'hide_empty' => false, 'hierarchical' => false, 'fields' => 'ids', 'orderby' => 'term_id', 'order' => 'DESC', 'number' => $this->state['size'], 'update_term_meta_cache' => false, 'rstr_permalink_scan' => true, 'cache_domain' => 'rstr-' . $cursor]);
        } finally {
            remove_filter('terms_clauses', $filter, 10);
        }
        if (is_wp_error($ids)) {
            self::fail($ids->get_error_message());
        }
        return $ids;
    }

    private static function object(array $row)
    {
        $item = 'post' === $row['object_type'] ? get_post($row['object_id']) : get_term($row['object_id'], $row['post_type_or_taxonomy']);
        if (!$item || is_wp_error($item)) {
            return null;
        }
        if ('post' === $row['object_type'] && ($item->post_type !== $row['post_type_or_taxonomy'] || 'trash' === $item->post_status)) {
            return null;
        }
        return $item;
    }

    private static function slug($item): string
    {
        return $item instanceof WP_Post ? $item->post_name : $item->slug;
    }

    private static function url($item): string
    {
        $url = $item instanceof WP_Post ? get_permalink($item) : get_term_link($item);
        return is_wp_error($url) || !is_string($url) ? '' : $url;
    }

    /** The legacy transliteration pipeline is shared by preview and apply. */
    private static function proposal(string $slug): string
    {
        // WordPress stores non-ASCII slugs percent-encoded; decode() only URL-decodes full URLs.
        return Transliteration_Controller::get()->cyr_to_lat_sanitize(Transliteration_Utilities::decode(rawurldecode($slug)));
    }

    private static function unique(string $slug, $item): string
    {
        $slug = sanitize_title($slug);
        return $item instanceof WP_Post
            ? wp_unique_post_slug($slug, $item->ID, $item->post_status, $item->post_type, $item->post_parent)
            : wp_unique_term_slug($slug, $item);
    }

    private function preview(array $row, $item): array
    {
        if (!$item || '' === $row['old_slug'] || '' === $row['old_url']) {
            $row['status'] = 'invalid';
            return $row;
        }
        if (!(defined('WP_CLI') && WP_CLI) && !current_user_can('post' === $row['object_type'] ? 'edit_post' : 'edit_term', $row['object_id'])) {
            $row['status'] = 'skipped';
            return $row;
        }
        $proposal = self::proposal($row['old_slug']);
        if ('' === $proposal || '' === sanitize_title($proposal)) {
            $row['status'] = 'invalid';
            $row['new_slug'] = '';
            $row['new_url'] = '';
            $row['url_status'] = 'unavailable_until_apply';
        } elseif ($proposal !== $row['old_slug']) {
            $row['new_slug'] = self::unique($proposal, $item);
            if ('' === $row['new_slug']) {
                $row['status'] = 'invalid';
                return $row;
            }
            $row['status'] = $row['new_slug'] === $row['old_slug'] ? 'unchanged' : ($row['new_slug'] === sanitize_title($proposal) ? 'will_change' : 'collision_adjusted');
            // Read-only uniqueness APIs cannot reserve slugs for later batches.
            $reservation = $this->directory . '/slug-' . hash('sha256', $row['object_type'] . ':' . $row['post_type_or_taxonomy'] . ':' . $row['new_slug']) . '.json';
            if (is_file($reservation) && self::read($reservation)['id'] !== $row['object_id']) {
                $row['status'] = 'potential_collision';
                $row['new_url'] = '';
                $row['url_status'] = 'unavailable_until_apply';
                return $row;
            }
            self::write($reservation, ['id' => $row['object_id']]);
            $copy = clone $item;
            if ($copy instanceof WP_Post) {
                $copy->post_name = $row['new_slug'];
            } else {
                $copy->slug = $row['new_slug'];
            }
            $row['new_url'] = self::url($copy);
            // Hierarchical custom post links resolve the URI using the stored ID.
            if ($copy instanceof WP_Post && 'page' !== $copy->post_type && is_post_type_hierarchical($copy->post_type)) {
                $row['new_url'] = '';
            }
            $row['url_status'] = '' === $row['new_url'] ? 'unavailable_until_apply' : 'estimate_current_database';
        }
        return $row;
    }

    private function advance(): void
    {
        $this->state['rows'] = [];
        if ('scan' === $this->state['phase']) {
            $groups = array_merge(array_map(static function ($name) { return ['post', $name]; }, $this->state['posts']), array_map(static function ($name) { return ['term', $name]; }, $this->state['taxonomies']));
            if ($this->state['group'] >= count($groups)) {
                $this->state['phase'] = 'dry_run' === $this->state['mode'] ? 'done' : 'apply';
                return;
            }
            list($kind, $name) = $groups[$this->state['group']];
            $ids = $this->ids($kind, $name);
            $rows = [];
            foreach ($ids as $id) {
                $row = ['object_type' => $kind, 'object_id' => (int) $id, 'post_type_or_taxonomy' => $name, 'name' => '', 'old_slug' => '', 'new_slug' => '', 'old_url' => '', 'new_url' => '', 'status' => 'unchanged', 'url_status' => 'actual'];
                $item = self::object($row);
                if ($item) {
                    $row['name'] = $item instanceof WP_Post ? $item->post_title : $item->name;
                    $row['old_slug'] = self::slug($item);
                    $row['new_slug'] = $row['old_slug'];
                    $row['old_url'] = self::url($item);
                    $row['new_url'] = $row['old_url'];
                }
                $row = 'dry_run' === $this->state['mode'] ? $this->preview($row, $item) : $row;
                if ('dry_run' === $this->state['mode'] && in_array($row['status'], ['invalid', 'skipped', 'potential_collision'], true)) {
                    ++$this->state['issues'];
                }
                $rows[] = $row;
                $this->state['cursor'] = (int) $id;
            }
            if ($rows) {
                self::write($this->directory . '/batch-' . $this->state['batches'] . '.json', $rows);
                ++$this->state['batches'];
                $this->state['total'] += count($rows);
                $this->state['rows'] = $rows;
            }
            if (count($ids) < $this->state['size']) {
                ++$this->state['group'];
                $this->state['cursor'] = PHP_INT_MAX;
            }
            return;
        }
        if ($this->state['batch'] >= $this->state['batches']) {
            $this->state['phase'] = 'apply' === $this->state['phase'] ? 'finalize' : 'done';
            $this->state['batch'] = 0;
            // Slug edits do not change rewrite structures, so no rewrite flush is needed.
            return;
        }
        $rows = self::read($this->directory . '/batch-' . $this->state['batch'] . '.json');
        foreach ($rows as &$row) {
            $file = $this->directory . '/object-' . $row['object_type'] . '-' . $row['object_id'] . '.json';
            if ('apply' === $this->state['phase']) {
                $row = $this->apply($row, $file);
                if ('changed' === $row['status']) {
                    ++$this->state['updated'];
                } elseif ('unchanged' !== $row['status']) {
                    ++$this->state['issues'];
                }
            } else {
                $row = self::read($file);
                $item = self::object($row);
                if ('changed' === $row['status'] && $item && self::slug($item) === $row['new_slug']) {
                    $row['new_url'] = self::url($item);
                    if ('' === $row['new_url'] || '' === $row['old_url']) {
                        ++$this->state['issues'];
                    }
                } elseif ('changed' === $row['status']) {
                    $row['status'] = 'conflict';
                    $row['new_url'] = '';
                    ++$this->state['issues'];
                } elseif ('unchanged' === $row['status'] && $item) {
                    $row['new_url'] = self::url($item);
                    if ($row['new_url'] !== $row['old_url']) {
                        $row['status'] = 'url_only_change';
                        ++$this->state['issues'];
                    }
                }
                if (self::redirect($row)) {
                    ++$this->state['redirects'];
                }
            }
        }
        unset($row);
        if ('finalize' === $this->state['phase']) {
            self::write($this->directory . '/final-' . $this->state['batch'] . '.json', $rows);
        }
        $this->state['rows'] = $rows;
        ++$this->state['batch'];
    }

    /** Persist intent before updating; replay reads the actual slug instead of updating twice. */
    private function apply(array $row, string $file): array
    {
        $pending = is_file($file) ? self::read($file) : null;
        if ($pending && 'pending' !== $pending['status']) {
            return $pending;
        }
        $item = self::object($row);
        if (!$item || '' === $row['old_slug'] || '' === $row['old_url']) {
            $row['status'] = 'invalid';
        } elseif ($pending && self::slug($item) === $pending['new_slug']) {
            $row = $pending;
            $row['status'] = 'changed';
            $row['new_url'] = self::url($item);
        } elseif (self::slug($item) !== $row['old_slug']) {
            $row['status'] = 'conflict';
        } elseif (!(defined('WP_CLI') && WP_CLI) && !current_user_can('post' === $row['object_type'] ? 'edit_post' : 'edit_term', $row['object_id'])) {
            $row['status'] = 'skipped';
        } else {
            $proposal = self::proposal($row['old_slug']);
            if ('' === $proposal || '' === sanitize_title($proposal)) {
                $row['status'] = 'invalid';
            } elseif ($proposal !== $row['old_slug']) {
                $row['new_slug'] = self::unique($proposal, $item);
                if ('' === $row['new_slug']) {
                    $row['status'] = 'invalid';
                } elseif ($row['new_slug'] !== $row['old_slug']) {
                    $row['status'] = 'pending';
                    self::write($file, $row);
                    $result = 'post' === $row['object_type']
                        ? wp_update_post(['ID' => $row['object_id'], 'post_name' => $proposal], true)
                        : wp_update_term($row['object_id'], $row['post_type_or_taxonomy'], ['slug' => $row['new_slug']]);
                    $item = self::object($row);
                    $row['status'] = !$item ? 'failed' : (self::slug($item) !== $row['old_slug'] ? 'changed' : (is_wp_error($result) || !$result ? 'failed' : 'unchanged'));
                    if ($item) {
                        $row['new_slug'] = self::slug($item);
                        $row['new_url'] = self::url($item);
                    }
                    if (is_wp_error($result)) {
                        $row['message'] = $result->get_error_message();
                    }
                }
            }
        }
        self::write($file, $row);
        return $row;
    }

    private static function redirect(array $row): bool
    {
        return 'changed' === $row['status'] && $row['old_slug'] !== $row['new_slug'] && '' !== $row['old_url'] && '' !== $row['new_url'] && $row['old_url'] !== $row['new_url'];
    }

    private function response(): array
    {
        $done = 'done' === $this->state['phase'];
        $phase = $this->state['phase'];
        $scan_progress = min(1, $this->state['total'] / max(1, $this->state['expected'] ?? $this->state['total']));
        $percentage = $done ? 100 : ('scan' === $phase ? $scan_progress * ('dry_run' === $this->state['mode'] ? 99 : 10) : ('apply' === $phase ? 10 : 70) + ($this->state['batch'] / max(1, $this->state['batches'])) * ('apply' === $phase ? 60 : 29));
        return array_merge($this->state, [
            'error' => false, 'done' => $done, 'percentage' => $percentage,
            'message' => 'dry_run' === $this->state['mode']
                ? ($done ? __('Dry run complete. No database changes were made.', 'serbian-transliteration') : __('Dry run: inspecting existing slugs. No database changes.', 'serbian-transliteration'))
                : ($done ? __('Migration complete. Review the results and download the redirect CSV.', 'serbian-transliteration') : ('scan' === $phase ? __('Recording original URLs before making changes.', 'serbian-transliteration') : ('apply' === $phase ? __('Applying slug changes.', 'serbian-transliteration') : __('Resolving final URLs after all slug changes.', 'serbian-transliteration')))),
            'csv' => $done && $this->state['redirects'] > 0 ? add_query_arg(['action' => 'rstr_permalink_csv', 'job' => $this->state['token'], 'nonce' => wp_create_nonce('rstr-permalink-csv')], admin_url('admin-post.php')) : '',
            'report' => $done ? add_query_arg(['action' => 'rstr_permalink_csv', 'job' => $this->state['token'], 'report' => '1', 'nonce' => wp_create_nonce('rstr-permalink-csv')], admin_url('admin-post.php')) : '',
        ]);
    }

    private static function csv_cell($value): string
    {
        $value = (string) $value;
        // Also handle control characters, whitespace/BOM before a formula and full-width prefixes.
        return preg_match('/^(?:[\x00-\x20\x7f]|\x{FEFF}|\p{Z})*[=+@\-＝＋－＠]|^[\t\r\n]/u', $value) ? "'" . $value : $value;
    }

    private function write_csv_file(string $path, bool $report): void
    {
        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen -- Explicit WP-CLI output path.
        $stream = fopen($path, 'w');
        if (!$stream) {
            self::fail(sprintf(__('Cannot write the CSV report to %s.', 'serbian-transliteration'), $path));
        }
        $this->write_csv($stream, $report);
        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
        fclose($stream);
    }

    private function write_csv($stream, bool $report): void
    {
        $columns = ['object_type', 'object_id', 'post_type_or_taxonomy', 'old_slug', 'new_slug', 'old_url', 'new_url'];
        if ($report) {
            $columns = array_merge($columns, ['name', 'status', 'url_status', 'message']);
        }
        fputcsv($stream, $columns, ',', '"', '');
        for ($batch = 0; $batch < $this->state['batches']; ++$batch) {
            $rows = self::read($this->directory . '/' . ('dry_run' === $this->state['mode'] ? 'batch-' : 'final-') . $batch . '.json');
            foreach ($rows as $row) {
                if (!$report && !self::redirect($row)) {
                    continue;
                }
                $values = [];
                foreach ($columns as $column) {
                    $values[] = self::csv_cell($row[$column] ?? '');
                }
                fputcsv($stream, $values, ',', '"', '');
            }
        }
    }

    public static function download(): void
    {
        $lock = null;
        try {
            self::authorize('rstr-permalink-csv');
            $root = self::root();
            $lock = self::lock($root);
            $job = new self($root, self::input('job'), false);
            $report = '1' === self::input('report');
            if ('done' !== $job->state['phase'] || (!$report && !$job->state['redirects'])) {
                self::fail(__('Finish the operation before downloading its report. Redirect CSV is available only for changed URLs.', 'serbian-transliteration'));
            }
            nocache_headers();
            header('Content-Type: text/csv; charset=UTF-8');
            header('X-Content-Type-Options: nosniff');
            header('Content-Disposition: attachment; filename="' . ($report ? 'permalink-report.csv' : 'permalink-redirects.csv') . '"');
            // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
            $stream = fopen('php://output', 'w');
            if (!$stream) {
                self::fail(__('Cannot open the CSV download stream.', 'serbian-transliteration'));
            }
            $job->write_csv($stream, $report);
            // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
            fclose($stream);
        } catch (Throwable $error) {
            wp_die(esc_html($error->getMessage()), '', ['response' => 403]);
        } finally {
            if ($lock) {
                flock($lock, LOCK_UN);
                // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
                fclose($lock);
            }
        }
        exit;
    }
}
