<?php

if (!defined('WPINC')) {
    die();
}

class Transliteration_Tools extends Transliteration
{
    public function __construct()
    {
        if (!is_admin()) {
            return;
        }

        $this->add_action('wp_ajax_rstr_transliteration_letters', 'transliteration_letters');
        $this->add_action('wp_ajax_rstr_run_permalink_transliteration', 'permalink_transliteration');
    }

    /*
     * AJAX Transliterator
     */
    public function transliteration_letters(): void
	{
		$nonce = isset($_REQUEST['nonce']) ? sanitize_text_field(wp_unslash($_REQUEST['nonce'])) : '';
		if ($nonce === '' || wp_verify_nonce($nonce, 'rstr-transliteration-letters') === false) {
			wp_send_json_error([
				'message' => __('An error occurred while converting. Please refresh the page and try again.', 'serbian-transliteration'),
			], 403);
		}

		$raw_value = isset($_REQUEST['value']) ? (string) wp_unslash($_REQUEST['value']) : '';
		if ($raw_value === '') {
			wp_send_json_error([
				'message' => __('The field is empty.', 'serbian-transliteration'),
			], 400);
		}

		// Keep safe HTML only (post-like content).
		$value = wp_kses_post($raw_value);

		$mode = isset($_REQUEST['mode']) ? sanitize_text_field(wp_unslash($_REQUEST['mode'])) : 'cyr_to_lat';
		if (!in_array($mode, ['cyr_to_lat', 'lat_to_cyr'], true)) {
			$mode = 'cyr_to_lat';
		}

		$controller = Transliteration_Controller::get();

		$result = ($mode === 'lat_to_cyr')
			? $controller->lat_to_cyr($value, true, true)
			: $controller->cyr_to_lat($value, true);

		// Return HTML (safe). Do NOT esc_html().
		echo wp_kses_post($result);
		exit;
	}

    /*
     * AJAX update permalinks cyr to lat
     */
    public function permalink_transliteration(): void
    {
        global $wpdb;

        $data = [
            'error'   => true,
            'done'    => false,
            'message' => __('There was a communication problem. Please refresh the page and try again. If this does not solve the problem, contact the author of the plugin.', 'serbian-transliteration'),
            'loading' => false,
        ];

        $nonce = isset($_REQUEST['nonce']) && is_scalar($_REQUEST['nonce'])
            ? sanitize_text_field(wp_unslash((string) $_REQUEST['nonce']))
            : '';

        if (!current_user_can('manage_options') || $nonce === '' || wp_verify_nonce($nonce, 'rstr-run-permalink-transliteration') === false) {
            wp_send_json($data);
        }

        // Posts per page.
        $posts_per_page = apply_filters('transliteration_permalink_transliteration_batch_size', 50);
        $posts_per_page = apply_filters_deprecated('rstr/permalink-tool/transliteration/offset', [$posts_per_page], '2.0.0', 'transliteration_permalink_transliteration_batch_size');
        $posts_per_page = max(1, absint($posts_per_page));

        $available_post_types = get_post_types([
            'public' => true,
        ], 'names', 'and');
        $requested_post_types = $_REQUEST['post_type'] ?? null;
        $post_types           = [];

        if (is_array($requested_post_types)) {
            $requested_post_types = $requested_post_types;
        } elseif (is_scalar($requested_post_types)) {
            // The batch response is sent back by the existing JavaScript as a comma-separated value.
            $requested_post_types = explode(',', wp_unslash((string) $requested_post_types));
        } else {
            $requested_post_types = [];
        }

        foreach ($requested_post_types as $requested_post_type) {
            if (!is_scalar($requested_post_type)) {
                continue;
            }

            $post_type = sanitize_text_field(wp_unslash((string) $requested_post_type));
            if (in_array($post_type, $available_post_types, true)) {
                $post_types[] = $post_type;
            }
        }

        $post_types = array_values(array_unique($post_types));

        // An omitted post_type retains the existing all-applicable-post-types behavior.
        if ($requested_post_types !== [] && $post_types === []) {
            wp_send_json($data);
        }

        $post_type       = $post_types === [] ? null : implode(',', $post_types);
        $post_type_query = '1=1';
        $post_type_args  = [];

        if ($post_types !== []) {
            $post_type_query = '`post_type` IN (' . implode(', ', array_fill(0, count($post_types), '%s')) . ')';
            $post_type_args  = $post_types;
        }

        // Get maximum number of posts.
        $total = isset($_POST['total']) && is_scalar($_POST['total'])
            ? absint(wp_unslash((string) $_POST['total']))
            : 0;

        if (!isset($_POST['total'])) {
            $count_query = "SELECT COUNT(1) FROM `{$wpdb->posts}` WHERE {$post_type_query} AND `post_type` NOT LIKE 'revision' AND TRIM(IFNULL(`post_name`,'')) <> '' AND `post_status` NOT LIKE 'trash'";
            $total       = absint($post_type_args === [] ? $wpdb->get_var($count_query) : $wpdb->get_var($wpdb->prepare($count_query, ...$post_type_args)));
        }

        // Get updated and current page.
        $updated = isset($_POST['updated']) && is_scalar($_POST['updated']) ? absint(wp_unslash((string) $_POST['updated'])) : 0;
        $paged   = isset($_POST['paged']) && is_scalar($_POST['paged']) ? absint(wp_unslash((string) $_POST['paged'])) + 1 : 1;

        // Calculate pagination values.
        $pages      = max(ceil($total / $posts_per_page), 1);
        $percentage = min(max(round(($paged / $pages) * 100, 2), 0), 100);

        // Perform transliteration.
        $return = [];
        if ($total) {
            $offset       = ($paged - 1) * $posts_per_page;
            $select_query = "SELECT `ID`, `post_name` FROM `{$wpdb->posts}` WHERE {$post_type_query} AND TRIM(IFNULL(`post_name`,'')) <> '' AND `post_type` NOT LIKE 'revision' AND `post_status` NOT LIKE 'trash' ORDER BY `ID` DESC LIMIT %d, %d";
            $select_args  = array_merge($post_type_args, [$offset, $posts_per_page]);
            $get_results  = $wpdb->get_results($wpdb->prepare($select_query, ...$select_args));

            if ($get_results) {
                foreach ($get_results as $match) {
                    $original_post_name = $match->post_name;
                    $match->post_name   = Transliteration_Utilities::decode($match->post_name);
                    $match->post_name   = Transliteration_Controller::get()->cyr_to_lat_sanitize($match->post_name);

                    if ($match->post_name !== $original_post_name && wp_update_post(['ID' => $match->ID, 'post_name' => $match->post_name])) {
                        $updated++;
                        $return[] = $match;
                    }
                }
            }
        }

        if ($percentage >= 100 && function_exists('flush_rewrite_rules')) {
            flush_rewrite_rules();
        }

        $action = isset($_REQUEST['action']) && is_scalar($_REQUEST['action'])
            ? sanitize_text_field(wp_unslash((string) $_REQUEST['action']))
            : '';

        if ($paged < $pages) {
            $data = [
                'error'          => false,
                'done'           => false,
                'message'        => null,
                'posts_per_page' => $posts_per_page,
                'paged'          => $paged,
                'total'          => $total,
                'pages'          => $pages,
                'loading'        => true,
                'percentage'     => $percentage,
                'updated'        => $updated,
                'nonce'          => $nonce,
                'action'         => $action,
                'post_type'      => $post_type,
            ];
        } else {
            $data = [
                'error'      => false,
                'done'       => true,
                'message'    => null,
                'loading'    => true,
                'percentage' => $percentage,
                'return'     => $return,
                'updated'    => $updated,
                'nonce'      => $nonce,
                'action'     => $action,
                'post_type'  => $post_type,
            ];
        }

        wp_send_json($data);
    }
}
