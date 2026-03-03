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

        if (isset($_REQUEST['nonce']) && wp_verify_nonce(sanitize_text_field($_REQUEST['nonce']), 'rstr-run-permalink-transliteration') !== false) {
            // Posts per page
            $posts_per_page = apply_filters('transliteration_permalink_transliteration_batch_size', 50);
            $posts_per_page = apply_filters_deprecated('rstr/permalink-tool/transliteration/offset', [$posts_per_page], '2.0.0', 'transliteration_permalink_transliteration_batch_size');

            // Set post type
            $post_type       = isset($_REQUEST['post_type']) ? (is_array($_REQUEST['post_type']) ? implode(',', array_map('sanitize_text_field', $_REQUEST['post_type'])) : sanitize_text_field($_REQUEST['post_type'])) : null;
            $post_type_query = $post_type ? sprintf("FIND_IN_SET(`post_type`, '%s')", $post_type) : '1=1'; // Use "1=1" as a fallback to avoid SQL syntax errors

            // Get maximum number of the posts
            $total = isset($_POST['total']) ? absint($_POST['total']) : absint($wpdb->get_var(sprintf("SELECT COUNT(1) FROM `%s` WHERE %s AND `post_type` NOT LIKE 'revision' AND TRIM(IFNULL(`post_name`,'')) <> '' AND `post_status` NOT LIKE 'trash'", $wpdb->posts, $post_type_query)));

            // Get updated and current page
            $updated = isset($_POST['updated']) ? absint($_POST['updated']) : 0;
            $paged   = isset($_POST['paged']) ? absint($_POST['paged']) + 1 : 1;

            // Calculate pagination values
            $pages      = max(ceil($total / $posts_per_page), 1);
            $percentage = min(max(round(($paged / $pages) * 100, 2), 0), 100);

            // Perform transliteration
            $return = [];
            if ($total) {
                $offset      = ($paged - 1) * $posts_per_page;
                $get_results = $wpdb->get_results($wpdb->prepare(sprintf("SELECT `ID`, `post_name` FROM `%s` WHERE %s AND TRIM(IFNULL(`post_name`,'')) <> '' AND `post_type` NOT LIKE 'revision' AND `post_status` NOT LIKE 'trash' ORDER BY `ID` DESC LIMIT %%d, %%d", $wpdb->posts, $post_type_query), $offset, $posts_per_page));

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
                    'nonce'          => sanitize_text_field($_REQUEST['nonce']),
                    'action'         => sanitize_text_field($_REQUEST['action']),
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
                    'nonce'      => sanitize_text_field($_REQUEST['nonce']),
                    'action'     => sanitize_text_field($_REQUEST['action']),
                    'post_type'  => $post_type,
                ];
            }
        }

        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}
