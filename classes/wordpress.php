<?php

if (!defined('WPINC')) {
    die();
}

class Transliteration_Wordpress extends Transliteration
{
    public function __construct()
    {
        $this->add_filter('sanitize_user', 'allow_cyrillic_usernames', 10, 3);
        $this->add_filter('body_class', 'add_body_class', 10, 2);

        $this->transliterate_rss_atom();
        $this->transliterate_widgets();
        $this->transliterate_permalinks();

        if (get_rstr_option('media-transliteration', 'yes') == 'yes') {
            $this->add_filter('wp_handle_upload_prefilter', 'upload_prefilter', (PHP_INT_MAX - 1), 1);
            $this->add_filter('sanitize_file_name', 'sanitize_file_name', (PHP_INT_MAX - 1));
            $this->add_filter('wp_unique_filename', 'sanitize_file_name', (PHP_INT_MAX - 1));
        }
    }

    public function allow_cyrillic_usernames($username, $raw_username, $strict)
    {
        if (get_rstr_option('allow-cyrillic-usernames', 'no') === 'no') {
            return $username;
        }

        // Osiguravamo da je $raw_username string, čak i ako je NULL
        $username = wp_strip_all_tags($raw_username ?? '');
        $username = remove_accents($username);

        // Uklanjamo oktete
        $username = preg_replace('|%([a-fA-F0-9][a-fA-F0-9])|', '', $username);
        $username = preg_replace('/&.+?;/', '', $username); // Uklanjamo entitete

        // Ako je strogi mod, smanjujemo na ASCII i ćirilične karaktere radi maksimalne prenosivosti
        if ($strict) {
            $username = preg_replace('|[^a-zа-я0-9 _.\-@]|iu', '', $username);
        }

        $username = trim($username);

        // Konsolidujemo uzastopne razmake
        $username = preg_replace('|\s+|', ' ', $username);

        return $username;
    }

    public function add_body_class($classes, $css_class)
    {
        if (get_rstr_option('enable-body-class', 'no') == 'no') {
            return $classes;
        }

        $script = Transliteration_Utilities::get_current_script();

        //body class based on the current script - cyr, lat
        $classes[] = 'rstr-' . $script;
        $classes[] = 'transliteration-' . $script;
        $classes[] = $script;

        return $classes;
    }

    private function transliterate_rss_atom(): void
    {
        if (get_rstr_option('enable-rss', 'no') === 'no' || get_rstr_option('transliteration-mode', 'cyr_to_lat') === 'none') {
            return;
        }

        $priority = PHP_INT_MAX - 100;

        $actions = [
            'rss_head', 'rss_footer',
            'rss2_head', 'rss2_footer',
            'rdf_head', 'rdf_footer',
            'atom_head', 'atom_footer',
        ];

        foreach ($actions as $action) {
            $this->add_action($action, 'rss_output_buffer_' . (strpos($action ?? '', '_head') ? 'start' : 'end'), $priority);
        }
    }

    private function transliterate_widgets(): void
    {
        if (get_rstr_option('force-widgets', 'no') === 'no' || get_rstr_option('transliteration-mode', 'cyr_to_lat') === 'none') {
            return;
        }

        $priority = PHP_INT_MAX - 100;
        $this->add_action('dynamic_sidebar_before', 'rss_output_buffer_start', $priority);
        $this->add_action('dynamic_sidebar_after', 'rss_output_buffer_end', $priority);
    }

    public function rss_output_buffer_start(): void
    {
        $this->ob_start('rss_output_buffer_callback');
    }

    public function rss_output_buffer_callback($buffer)
    {
        return Transliteration_Controller::get()->transliterate($buffer);
    }

    public function rss_output_buffer_end(): void
    {
        if (ob_get_level() > 0) {
            ob_end_flush();
        }
    }

    public function transliterate_permalinks(): void
    {
        $priority = PHP_INT_MAX - 100;

        if (get_rstr_option('permalink-transliteration', 'yes') == 'yes') {
            $this->add_filter('sanitize_title', 'force_permalink_to_latin', $priority, 1);
            $this->add_filter('the_permalink', 'force_permalink_to_latin', $priority, 1);
            $this->add_filter('wp_unique_post_slug', 'force_permalink_to_latin', $priority, 1);
            $this->add_filter('permalink_manager_filter_default_post_uri', 'force_permalink_to_latin', $priority, 1);
            $this->add_filter('permalink_manager_filter_default_term_uri', 'force_permalink_to_latin', $priority, 1);
            $this->add_filter('wp_insert_post_data', 'force_permalink_to_latin_on_save', $priority, 2);
            //	$this->add_action('wp_after_insert_post', 'after_insert_post_force_permalink_to_latin_on_save', $priority, 4);
        }
    }

    public function force_permalink_to_latin($permalink)
    {
        return Transliteration_Mode::get()->force_permalink_to_latin($permalink, true);
    }

        /**
		 * Sanitize transliterated post slugs before database persistence.
		 *
		 * @param array $data    Processed post data.
		 * @param array $postarr Original post data.
		 * @return array
		 */
		public function force_permalink_to_latin_on_save($data, $postarr)
		{
			if (isset($data['post_name'])) {
				$data['post_name'] = sanitize_title_with_dashes(
					$this->force_permalink_to_latin($data['post_name']),
					'',
					'save'
				);
			} elseif (isset($data['post_title'])) {
				$data['post_name'] = sanitize_title_with_dashes(
					sanitize_title($this->force_permalink_to_latin($data['post_title'])),
					'',
					'save'
				);
			}

			return $data;
		}

    public function after_insert_post_force_permalink_to_latin_on_save($post_id, $post, $update, $post_before): void
    {
        $new_slug = sanitize_title($this->force_permalink_to_latin($post->post_title));
        if ($new_slug !== $post->post_name) {
            wp_update_post([
                'ID'        => $post_id,
                'post_name' => $new_slug,
            ]);
        }
    }

    /*
     * Prefiler for the upload
    */
    public function upload_prefilter($file)
    {
        if (isset($file['name'])) {
            $file['name'] = $this->sanitize_file_name($file['name']);
        }

        return $file;
    }

    /*
     * Sanitize file name
    */
    public function sanitize_file_name($filename)
    {
        $delimiter = get_rstr_option('media-delimiter', 'no');

        if ($delimiter != 'no') {
            $name = $this->force_permalink_to_latin($filename);
            $name = preg_split("/[\-_~\s]+/", $name);
            $name = array_filter($name);

            if ($name !== []) {
                return implode($delimiter, $name);
            }
            return $filename;
        }

        return $filename;
    }
}
