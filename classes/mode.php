<?php

if (!defined('WPINC')) {
    die();
}

final class Transliteration_Mode extends Transliteration
{
    use Transliteration__Cache;

    private $mode;

    /*
     * The main constructor
     */
    public function __construct($actions = true)
    {
        // Load transliteration
        $this->load_mode();

        if ($actions) {
            // Apply transliteration filters
            $this->apply_filters();
        }
    }

    /*
     * Get current instance
     */
    public static function get()
    {
        return self::cached_static('instance', fn (): \Transliteration_Mode => new self(false));
    }

    /*
     * The current mode
     */
    public function mode($mode = null)
    {
        return self::cached_static('mode', function () use ($mode) {

            $available_modes = Transliteration_Utilities::available_modes();

            $current_mode = $mode && in_array($mode, $available_modes) ? $mode : get_rstr_option('mode', 'light');

            if ($current_mode && class_exists($mode = 'Transliteration_Mode_' . ucfirst($current_mode))) {
                return $mode;
            }

            return [];
        }, ($mode ?? 0));
    }

    /*
     * Load the current mode
     * @contributor    Ivijan-Stefan Stipić
     * @version        2.0.0
     */
    public function filters()
    {
		if(empty($this->mode)) {
			return [];
		}
		
        return self::cached_static('filters', function () {
            if (get_rstr_option('transliteration-mode', 'light') == 'none') {
                $filters = [];
                $filters = apply_filters('transliteration_mode_filters', $filters);
                return apply_filters_deprecated('rstr/transliteration/exclude/filters', [$filters], '2.0.0', 'transliteration_mode_filters');
            }
            $filters = $this->mode->filters();
            $filters = apply_filters('transliteration_mode_filters', $filters);
            $filters = apply_filters('transliteration_mode_filters_' . $this->mode::MODE, $filters);
            $filters = apply_filters_deprecated('rstr/transliteration/exclude/filters', [$filters], '2.0.0', 'transliteration_mode_filters');
            return apply_filters_deprecated('rstr/transliteration/exclude/filters/' . $this->mode::MODE, [$filters], '2.0.0', 'transliteration_mode_filters_' . $this->mode::MODE);
        });
    }

    /*
     * Load the current mode
     * @contributor    Ivijan-Stefan Stipić
     * @version        2.0.0
     */
    private function load_mode(): void
    {
        if (empty($this->mode) && ($mode_class = $this->mode())) {
            $this->mode = $mode_class::get();
			
			// Include buffer
			Transliteration_Controller::get()->init_output_buffer();
        }
    }

    /*
     * Apply filters for current mode
     * @contributor    Ivijan-Stefan Stipić
     * @version        2.0.0
     */
    public function apply_filters(): void
    {
        global $pagenow;
        static $filters_loaded = false;

        if ($filters_loaded) {
            return;
        }

        if (get_rstr_option('transliteration-mode', 'cyr_to_lat') === 'none') {
            return;
        }

        $filters = null;

        // Is admin, a do special static filters
        if (is_admin() && !wp_doing_ajax()) {
            if (class_exists('Transliteration_Mode_Admin') && get_rstr_option('avoid-admin', 'no') == 'no') {
                $filters = Transliteration_Mode_Admin::get()->filters();
            }
        }
        // On the frontend enable modes
        else {
			if (in_array($this->mode::MODE, ['forced'])) {
				return;
			}
		
            $filters = $this->filters();
        }

        if (empty($filters)) {
            return;
        }

        if ($filters) {
            add_action('init', function () use ($filters, $pagenow): void {
                foreach ($filters as $key => $method) {
                    if ($pagenow && $pagenow === 'nav-menus.php' && $key === 'the_title') {
                        continue;
                    }

                    $args = $key === 'gettext' ? 3 : 1;

                    if (is_array($method)) {
                        add_filter($key, $method, (PHP_INT_MAX - 100), $args);
                    } elseif (method_exists($this, $method)) {
                        $this->add_filter($key, $method, (PHP_INT_MAX - 100), $args);
                    }
                }
            }, (PHP_INT_MAX - 100));

            $filters_loaded = true;
        }
    }

    /**
     * Transliterate any content
     * @contributor    Ivijan-Stefan Stipić
     * @version        1.0.0
     */
    public function content($content, $mode = 'auto', $sanitize_html = true)
    {
        return Transliteration_Controller::get()->transliterate($content, $mode, $sanitize_html);
    }

    /*
     * Force to Lat - Transliterate Content (HTML & Text)
     * @contributor    Ivijan-Stefan Stipić
     * @version        1.0.0
     **/
    public function content__force_lat($content = '')
    {
        if (empty($content)) {
            return $content;
        }
        if (is_array($content)) {
            return $this->objects($content);
        }

        if (is_string($content)) {
            return $this->content($content, 'cyr_to_lat');
        }

        return $content;
    }

    /**
     * Transliterate text
     * @contributor    Ivijan-Stefan Stipić
     * @version        1.0.0
     */
    public function no_html_content($content)
    {
        return Transliteration_Controller::get()->transliterate_no_html($content, (Transliteration_Utilities::is_admin() ? 'cyr_to_lat' : 'auto'));
    }

    /*
     * Transliterate Blog informations
     * @contributor    Ivijan-Stefan Stipić
     * @version        1.0.0
     **/
    public function bloginfo($output, $show = '')
    {
        if (!empty($show) && in_array($show, ['name', 'description'])) {
            return $this->no_html_content($output);
        }

        return $output;
    }

    /**
     * Transliterate Objects
     * @contributor    Ivijan-Stefan Stipić
     * @version        1.0.0
     */
    public function objects($data, $mode = 'auto')
    {
        return $this->transliterate_objects($data, $mode);
    }

    public function transliterate_objects($data, $mode = 'auto')
    {
        if (is_array($data)) {
            foreach ($data as &$value) {
                if (is_array($value) || is_object($value)) {
                    $value = $this->transliterate_objects($value, $mode);
                } elseif (is_string($value)) {
                    $value = Transliteration_Controller::get()->transliterate($value, $mode);
                }
            }
        } elseif (is_object($data)) {
            foreach ($data as $key => $value) {
                if (is_array($value) || is_object($value)) {
                    $data->$key = $this->transliterate_objects($value, $mode);
                } elseif (is_string($value)) {
                    $data->$key = Transliteration_Controller::get()->transliterate($value, $mode);
                }
            }
        }

        return $data;
    }

    /**
     * Transliterate WP Terms
     * @contributor    Ivijan-Stefan Stipić
     * @version        1.0.0
     */
    public function transliteration_wp_terms($wp_terms)
    {
        if (empty($wp_terms) || !is_array($wp_terms)) {
            return $wp_terms;
        }

        foreach ($wp_terms as $i => $term) {
            if (is_object($term)) {
                if (isset($term->name) && !empty($term->name)) {
                    $wp_terms[$i]->name = $this->content($term->name, (Transliteration_Utilities::is_admin() ? 'cyr_to_lat' : 'auto'));
                }

                if (isset($term->description) && !empty($term->description)) {
                    $wp_terms[$i]->description = $this->content($term->description, (Transliteration_Utilities::is_admin() ? 'cyr_to_lat' : 'auto'));
                }
            }
        }

        return $wp_terms;
    }

    /**
     * Force all permalinks to latin
     * @contributor    Ivijan-Stefan Stipić
     * @version        1.0.0
     */
    public function force_permalink_to_latin($permalink, $force = false)
    {
        $permalink = rawurldecode($permalink);

        return Transliteration_Controller::get()->cyr_to_lat_sanitize($permalink, $force);
    }

    /**
     * Transliterate Image attributes
     * @contributor    Ivijan-Stefan Stipić
     * @version        1.0.0
     */
    public function image_attributes($attributes)
    {
        return Transliteration_Controller::get()->transliterate_attributes($attributes, ['alt', 'title']);
    }

    /**
     * Transliterate WP Mails
     * @contributor    Ivijan-Stefan Stipić
     * @version        1.0.0
     */
    public function wp_mail($args)
    {

        if ($args['message'] ?? false) {
            $args['message'] = $this->content($args['message']);
        }

        if ($args['subject'] ?? false) {
            $args['subject'] = $this->content($args['subject']);
        }

        return $args;
    }

    /*
     * Transliterate gettext (HTML & Text)
     * @contributor    Ivijan-Stefan Stipić
     * @version        1.0.0
     **/
    public function gettext_content($content, $text = '', $domain = '')
    {
        if (empty($content)) {
            return $content;
        }
        if (is_array($content)) {
            return $this->transliterate_objects($content);
        }

        if (is_string($content)) {
            return $this->content($content);
        }

        return $content;
    }

    /*
     * Fix title parts
     * @contributor    Ivijan-Stefan Stipić
     * @version        1.0.0
     **/
    public function title_parts(array $titles = []): array
    {
        foreach ($titles as $key => $val) {
            if (is_string($val) && ! is_numeric($val)) {
                $titles[ $key ] = $this->no_html_content($titles[ $key ]);
            }
        }

        return $titles;
    }

    /*
     * Transliterate JSON
     * @contributor    Ivijan-Stefan Stipić
     * @version        1.0.0
     **/
    public function transliteration_json_content($json_content)
    {
        if (empty($json_content)) {
            return $json_content;
        }

        $content = json_decode($json_content, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return $json_content;
        }

        $content = $this->processMessages($content);

        return wp_json_encode($content);
    }

    public function the_post_filter($post)
    {
        $post->post_title   = $this->no_html_content($post->post_title ?? '');
        $post->post_content = $this->content($post->post_content ?? '');
        $post->post_excerpt = $this->content($post->post_excerpt ?? '');

        return $post;
    }

    /*
     * Transliterate Posts results
     * @contributor    Ivijan-Stefan Stipić
     * @version        1.0.0
     **/
    public function the_posts_filter($posts)
    {

        foreach ($posts as &$post) {
            $post = $this->the_post_filter($post);
        }

        return $posts;
    }

    private function processMessages(array $content): array
    {
        if (isset($content['locale_data']['messages']) && is_array($content['locale_data']['messages'])) {
            foreach ($content['locale_data']['messages'] as $key => $messages) {
                $content['locale_data']['messages'][$key] = $this->transliterateMessages($messages);
            }
        }

        return $content;
    }

    private function transliterateMessages($messages)
    {
        if (!is_array($messages)) {
            return $messages;
        }

        foreach ($messages as $key => $message) {
            $messages[$key] = $this->no_html_content($message);
        }

        return $messages;
    }
}
