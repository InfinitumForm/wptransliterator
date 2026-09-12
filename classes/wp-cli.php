<?php

if (!defined('WPINC')) {
    die();
}

/*
 * WP-CLI Helpers
 * @since     1.4.3
 * @version   1.0.1
 * @author    Ivijan-Stefan Stipic
 */

if (class_exists('WP_CLI_Command')):
    class Transliteration_Wp_Cli extends WP_CLI_Command
    {
        /**
         * Preview or transliterate existing post and taxonomy slugs to Latin.
         *
         * ## OPTIONS
         *
         * [--dry-run]
         * : Inspect every selected object without changing the database.
         *
         * [--post-types=<post-types>]
         * : Comma-separated public post types. Defaults to all public post types.
         *
         * [--taxonomies=<taxonomies>]
         * : Comma-separated viewable taxonomies. Defaults to all supported taxonomies.
         *
         * [--batch-size=<number>]
         * : Objects per batch. Defaults to 500 and is limited to 1-1000.
         *
         * [--report=<path>]
         * : Write the complete CSV report to this path.
         *
         * [--redirects=<path>]
         * : Write changed old/new URLs to this CSV path after applying changes.
         *
         * [--resume=<operation-id>]
         * : Resume an interrupted operation using its printed operation ID.
         *
         * [--yes]
         * : Apply changes without an interactive confirmation.
         *
         * ## EXAMPLES
         *
         *     wp transliterate permalinks --dry-run --report=permalink-report.csv
         *     wp transliterate permalinks --post-types=post,page --taxonomies=category --yes
         *     wp transliterate permalinks --resume=<operation-id> --yes
         *
         * @when after_wp_load
         */
        public function permalinks($args, $assoc_args): void
        {
            if (isset($assoc_args['script']) && 'lat' !== $assoc_args['script']) {
                WP_CLI::error(__('The shared permalink migration supports Cyrillic-to-Latin slugs only.', 'serbian-transliteration'));
            }
            $resume = isset($assoc_args['resume']) ? sanitize_text_field((string) $assoc_args['resume']) : '';
            $mode = isset($assoc_args['dry-run']) ? 'dry_run' : 'apply';
            if ('apply' === $mode) {
                WP_CLI::confirm(__('This will change existing URLs. Confirm that you have a backup and want to continue.', 'serbian-transliteration'), $assoc_args);
            }
            $split = static function ($value): array {
                return array_values(array_filter(array_map('sanitize_key', explode(',', (string) $value)), 'strlen'));
            };
            $batch_size = absint($assoc_args['batch-size'] ?? ($assoc_args['batch_size'] ?? apply_filters('transliteration_cli_permalink_transliteration_batch_size', 500)));
            $options = [
                'mode' => $mode,
                'posts' => isset($assoc_args['post-types']) ? $split($assoc_args['post-types']) : array_keys(get_post_types(['public' => true], 'objects')),
                'taxonomies' => isset($assoc_args['taxonomies']) ? $split($assoc_args['taxonomies']) : array_keys(Transliteration_Permalink_Job::taxonomies()),
                'size' => max(1, min(1000, $batch_size)),
            ];
            foreach (['report', 'redirects'] as $output) {
                if (!empty($assoc_args[$output])) {
                    $options[$output] = (string) $assoc_args[$output];
                }
            }
            if ('' !== $resume) {
                $options['token'] = $resume;
            }
            $last_percentage = -1;
            $operation_id = '';
            try {
                $result = Transliteration_Permalink_Job::run_cli($options, static function (array $state) use (&$last_percentage, &$operation_id): void {
                    if ('' === $operation_id) {
                        $operation_id = $state['token'];
                        WP_CLI::log(sprintf(__('Operation ID: %s', 'serbian-transliteration'), $operation_id));
                    }
                    $percentage = (int) floor($state['percentage'] / 10) * 10;
                    if ($percentage !== $last_percentage) {
                        $last_percentage = $percentage;
                        WP_CLI::log(sprintf('%d%% — %s', min(100, $percentage), $state['message']));
                    }
                });
            } catch (Throwable $error) {
                WP_CLI::error($error->getMessage());
                return;
            }
            WP_CLI::log(sprintf(__('Objects: %1$s. Slugs changed: %2$s. Redirects: %3$s. Issues requiring review: %4$s.', 'serbian-transliteration'), $result['total'], $result['updated'], $result['redirects'], $result['issues']));
            if (!empty($options['report'])) {
                WP_CLI::log(sprintf(__('Full report: %s', 'serbian-transliteration'), $options['report']));
            }
            if (!empty($options['redirects']) && $result['redirects'] > 0) {
                WP_CLI::log(sprintf(__('Redirect report: %s', 'serbian-transliteration'), $options['redirects']));
            }
            WP_CLI::success($result['message']);
        }
    }
endif;

// Add comands
if (defined('WP_CLI') && WP_CLI) {
    WP_CLI::add_command('transliterate', 'Transliteration_Wp_Cli');
}
