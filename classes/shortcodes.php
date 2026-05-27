<?php

if (!defined('WPINC')) {
    die();
}

class Transliteration_Shortcodes extends Transliteration
{
    public function __construct()
    {
        // Default shortcodes
        $this->add_shortcode('rstr_selector', 'script_selector');
        $this->add_shortcode('rstr_img', 'img');
        $this->add_shortcode('transliteration', 'transliteration');

        // New shortcodes
        $this->add_shortcode('cyr_to_lat', 'cyr_to_lat');
        $this->add_shortcode('lat_to_cyr', 'lat_to_cyr');
        $this->add_shortcode('skip_translit', 'skip');
		$this->add_shortcode('keep_translit', 'keep');

        // Deprecated shortcodes
        $this->add_shortcode('rstr_cyr_to_lat', 'cyr_to_lat');
        $this->add_shortcode('rstr_lat_to_cyr', 'lat_to_cyr');
        $this->add_shortcode('rstr_skip', 'skip');
		$this->add_shortcode('rstr_keep', 'keep');
    }

    /*
     * Script selector
     */
    public function script_selector($attr = [])
    {
        $args = (object) shortcode_atts([
            'type'        => 'inline',
            'separator'   => ' | ',
            'cyr_caption' => __('Cyrillic', 'serbian-transliteration'),
            'lat_caption' => __('Latin', 'serbian-transliteration'),
        ], $attr);

        return script_selector([
            'echo'         => false,
            'display_type' => $args->type,
            'separator'    => $args->separator,
            'cyr_caption'  => $args->cyr_caption,
            'lat_caption'  => $args->lat_caption,
        ]);
    }

    /*
     * Cyrillic to Latin
     */
    public function cyr_to_lat($attr = [], $content = '', string $shortcode_tag = '')
    {
        if (is_admin() && $shortcode_tag === 'rstr_cyr_to_lat') {
            $content = sprintf(
                '<div class="notice notice-warning"><p class="deprecated">%s</p></div>',
                sprintf(
                    __('The %1$s shortcode has been deprecated as of version %2$s. Please update your content and use the new %3$s shortcode.', 'serbian-transliteration'),
                    '<code>[' . $shortcode_tag . ']</code>',
                    '2.0.0',
                    '<code>[cyr_to_lat]</code>',
                )
            ) . $content;
        }

        $attr = (object) shortcode_atts([
            'output'   => 'shortcode',
            'fix_html' => true,
        ], $attr);

        $attr->fix_html = in_array($attr->fix_html, ['yes', 'true', true, 1, '1']);

        if ($attr->output == 'php') {
            return cyr_to_lat(do_shortcode($content), $attr->fix_html);
        }
        return '{cyr_to_lat}' . do_shortcode($content) . '{/cyr_to_lat}';
    }

    /*
     * Latin to Cyrillic
     */
    public function lat_to_cyr($attr = [], $content = '', string $shortcode_tag = '')
    {
        if (is_admin() && $shortcode_tag === 'rstr_lat_to_cyr') {
            $content = sprintf(
                '<div class="notice notice-warning"><p class="deprecated">%s</p></div>',
                sprintf(
                    __('The %1$s shortcode has been deprecated as of version %2$s. Please update your content and use the new %3$s shortcode.', 'serbian-transliteration'),
                    '<code>[' . $shortcode_tag . ']</code>',
                    '2.0.0',
                    '<code>[lat_to_cyr]</code>',
                )
            ) . $content;
        }

        $attr = (object) shortcode_atts([
            'output'         => 'shortcode',
            'fix_html'       => true,
            'fix_diacritics' => true,
        ], $attr);

        $attr->fix_html       = in_array($attr->fix_html, ['yes', 'true', true, 1, '1']);
        $attr->fix_diacritics = in_array($attr->fix_diacritics, ['yes', 'true', true, 1, '1']);

        if ($attr->output == 'php') {
            return lat_to_cyr(do_shortcode($content), $attr->fix_html, $attr->fix_diacritics);
        }
        return '{lat_to_cyr}' . do_shortcode($content) . '{/lat_to_cyr}';
    }

    /*
     * Skip transliteration
     */
    public function skip($attr = [], $content = '', string $shortcode_tag = '')
    {
        if (is_admin() && $shortcode_tag === 'rstr_skip') {
            $content = sprintf(
                '<div class="notice notice-warning"><p class="deprecated">%s</p></div>',
                sprintf(
                    __('The %1$s shortcode has been deprecated as of version %2$s. Please update your content and use the new %3$s shortcode.', 'serbian-transliteration'),
                    '<code>[' . $shortcode_tag . ']</code>',
                    '2.0.0',
                    '<code>[skip_translit]</code>',
                )
            ) . $content;
        }

        shortcode_atts([], $attr);

        return '{rstr_skip}' . do_shortcode($content) . '{/rstr_skip}';
    }
	
	/*
     * Keep transliteration
     */
    public function keep($attr = [], $content = '', string $shortcode_tag = '')
    {
        if (is_admin() && $shortcode_tag === 'rstr_keep') {
            $content = sprintf(
                '<div class="notice notice-warning"><p class="deprecated">%s</p></div>',
                sprintf(
                    __('The %1$s shortcode has been deprecated as of version %2$s. Please update your content and use the new %3$s shortcode.', 'serbian-transliteration'),
                    '<code>[' . $shortcode_tag . ']</code>',
                    '2.0.0',
                    '<code>[keep_translit]</code>',
                )
            ) . $content;
        }

        shortcode_atts([], $attr);

        return '{rstr_keep}' . do_shortcode($content) . '{/rstr_keep}';
    }

    /*
     * Transliteration
     */
    public function transliteration($atts = [], $content = '', $shortcode_tag = '')
    {
        $atts = (object) shortcode_atts([
            'from' => 'cyr',
            'to'   => 'lat',
        ], $atts, 'transliteration');

        if (!in_array($atts->from, ['lat', 'cyr']) || !in_array($atts->to, ['lat', 'cyr'])) {
            return sprintf(
                '<pre>%s</pre>',
                __('Transliteration shortcode does not have adequate parameters and translation is not possible. Please check the documentation.', 'serbian-transliteration')
            );
        }

        $translation_key = strtolower(sprintf('%s_to_%s', $atts->from, $atts->to));

        switch ($translation_key) {
            case 'cyr_to_lat':
                return $this->cyr_to_lat([], do_shortcode($content));
            case 'lat_to_cyr':
                return $this->lat_to_cyr([], do_shortcode($content));
            default:
                return $content;
        }
    }

    /*
     * Image shortcode
     */
    public function img($attr = [], $content = '', $shortcode_tag = ''): string
    {
        $attr = (object) shortcode_atts([
            'cyr'             => '',
            'cyr_title'       => '',
            'cyr_caption'     => '',
            'lat'             => '',
            'lat_title'       => '',
            'lat_caption'     => '',
            'default'         => '',
            'default_title'   => '',
            'default_caption' => '',
            'img_attributes'  => '',
        ], $attr);

        switch (Transliteration_Utilities::get_current_script()) {
            case 'lat':
                if ($attr->lat_caption) {
                    return sprintf('<figure><img src="%1$s" alt="%2$s" %4$s/><figcaption>%3$s</figcaption></figure>', esc_attr($attr->lat), esc_attr($attr->lat_title), wp_kses_post($attr->lat_caption), wp_kses_post($attr->img_attributes));
                }
                return sprintf('<img src="%1$s" alt="%2$s" %3$s/>', esc_attr($attr->lat), esc_attr($attr->lat_title), wp_kses_post($attr->img_attributes));

                break;

            case 'cyr':
                if ($attr->cyr_caption) {
                    return sprintf('<figure><img src="%1$s" alt="%2$s" %4$s/><figcaption>%3$s</figcaption></figure>', esc_attr($attr->cyr), esc_attr($attr->cyr_title), wp_kses_post($attr->cyr_caption), wp_kses_post($attr->img_attributes));
                }
                return sprintf('<img src="%1$s" alt="%2$s" %3$s/>', esc_attr($attr->cyr), esc_attr($attr->cyr_title), wp_kses_post($attr->img_attributes));

                break;

            default:
                if ($attr->default_caption) {
                    return sprintf('<figure><img src="%1$s" alt="%2$s" %4$s/><figcaption>%3$s</figcaption></figure>', esc_attr($attr->default), esc_attr($attr->default_title), wp_kses_post($attr->default_caption), wp_kses_post($attr->img_attributes));
                }
                return sprintf('<img src="%1$s" alt="%2$s" %3$s/>', esc_attr($attr->default), esc_attr($attr->default_title), wp_kses_post($attr->img_attributes));

                break;
        }
    }
}
