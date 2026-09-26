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
         * This tool can rename all existing Cyrillic permalinks to Latin inside database
         *
         * ## OPTIONS
         *
         *     --script=<lat|cyr>    (optional) Change transliteration type (Latin or Cyrillic)
         *                                      If it is set to "cyr", then it will translate permalinks
         *                                      from Latin to Cyrillic
         *     --batch_size=500      (optional) Number of records to process in each batch.
         *                                              This helps in managing memory usage and server load.
         *
         * ## EXAMPLES
         *
         *     wp transliterate permalinks                  Translate permalinks from Cyrillic to Latin
         *     wp transliterate permalinks --script=cyr     Translate permalinks from Latin to Cyrillic
         *
         * @when after_wp_load
         */
        public function permalinks($args, $assoc_args): void
        {
            global $wpdb;

            $batch_size = apply_filters('transliteration_cli_permalink_transliteration_batch_size', 500);

            $updated    = 0;
            $batch_size = absint($assoc_args['batch_size'] ?? $batch_size);

            $type = $assoc_args['script'] ?? 'lat';
            $type = 'cyr' === $type ? 'lat_to_cyr' : 'cyr_to_lat';

            $get_post_types = get_post_types([
                'public' => true,
            ], 'names', 'and');

            $post_type       = implode(', ', $get_post_types);
            $post_type_query = sprintf("FIND_IN_SET(`post_type`, '%s')", $post_type);


            $total = $wpdb->get_var(sprintf("SELECT COUNT(`ID`) FROM `%s` WHERE %s AND TRIM(IFNULL(`post_name`,'')) <> '' AND `post_type` NOT LIKE 'revision' AND `post_status` NOT LIKE 'trash'", $wpdb->posts, $post_type_query));

            if ($total > 0) {
                $inst = Transliteration_Controller::get();
                WP_CLI::log(PHP_EOL . PHP_EOL);
                WP_CLI::log(__('Please wait! Do not close the terminal or terminate the script until this operation is completed!', 'serbian-transliteration'));

                $progress = \WP_CLI\Utils\make_progress_bar(__('Progress:', 'serbian-transliteration'), $total);

                for ($offset = 0; $offset < $total; $offset += $batch_size) {
                    $get_results = $wpdb->get_results($wpdb->prepare(
                        sprintf("SELECT `ID`, `post_name`, `post_title` FROM `%s` WHERE %s AND TRIM(IFNULL(`post_name`,'')) <> '' AND `post_type` NOT LIKE 'revision' AND `post_status` NOT LIKE 'trash' ORDER BY `ID` DESC LIMIT %%d OFFSET %%d", $wpdb->posts, $post_type_query),
                        $batch_size,
                        $offset
                    ));

                    if ($get_results) {
                        $get_results = array_map(function ($match) use (&$wpdb, &$inst, &$updated, &$type, &$progress): void {
                            $progress->tick();

                            $old_post_name = $match->post_name;

                            $match->post_name = Transliteration_Utilities::decode($match->post_name);
                            if ('lat_to_cyr' === $type) {
                                $match->post_name = $inst->lat_to_cyr($match->post_name, false, true);
                            } else {
                                $match->post_name = $inst->cyr_to_lat_sanitize($match->post_name);
                            }

                            if ($wpdb->update(
                                $wpdb->posts,
                                [
                                    'post_name' => $match->post_name,
                                ],
                                [
                                    'ID' => $match->ID,
                                ],
                                [
                                    '%s',
                                ],
                                [
                                    '%d',
                                ]
                            )) {
                                delete_post_meta($match->ID, '_wp_old_slug');

                                if ('lat_to_cyr' === $type) {
                                    update_post_meta($match->ID, '_wp_cyr_slug', $inst->lat_to_cyr($match->post_name));
                                    update_post_meta($match->ID, '_wp_lat_slug', $inst->cyr_to_lat_sanitize($old_post_name));
                                } else {
                                    update_post_meta($match->ID, '_wp_cyr_slug', $inst->lat_to_cyr($old_post_name));
                                    update_post_meta($match->ID, '_wp_lat_slug', $inst->cyr_to_lat_sanitize($match->post_name));
                                }

                                ++$updated;
                                WP_CLI::success(sprintf(
                                    __('Updated page ID %1$d, (%2$s) at URL: %3$s', 'serbian-transliteration'),
                                    $match->ID,
                                    $match->post_title,
                                    get_the_permalink($match->ID)
                                ));
                            }
                        }, $get_results);
                    }
                }

                $progress->finish();
                WP_CLI::log(PHP_EOL . PHP_EOL);
            }

            if ($updated > 0) {
                WP_CLI::success(sprintf(_n('%d permalink was successfully transliterated.', '%d permalinks were successfully transliterated.', $updated, 'serbian-transliteration'), $updated));
            } else {
                WP_CLI::error(__('No changes to the permalink have been made.', 'serbian-transliteration'), false);
            }
        }
    }
endif;

// Add comands
if (defined('WP_CLI') && WP_CLI) {
    WP_CLI::add_command('transliterate', 'Transliteration_Wp_Cli');
}
