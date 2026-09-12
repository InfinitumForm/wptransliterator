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
        $this->add_action('admin_post_rstr_permalink_csv', 'permalink_csv');
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
        Transliteration_Permalink_Job::request();
    }

    public function permalink_csv(): void
    {
        Transliteration_Permalink_Job::download();
    }
}