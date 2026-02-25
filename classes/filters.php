<?php if (!defined('WPINC')) {
    die();
}

class Transliteration_Filters extends Transliteration
{
    public function __construct()
    {
        $this->add_filter('transliteration_mode_filters', 'exclude_filters', PHP_INT_MAX - 100);
        $this->add_filter('transliteration_init_classes', 'disable_classes');

        $this->add_filter('rstr/init/exclude/lat', 'exclude_lat_words');
        $this->add_filter('rstr/init/exclude/cyr', 'exclude_cyr_words');

		// $this->add_action('transliteration-settings-after-sidebar', 'after_settings_sidebar', 5, 2);
    }

    /*
     * Exclude filters
     */
    public function exclude_filters($filters)
    {
        if ($remove_filters = get_rstr_option('transliteration-filter', [])) {
            return array_diff_key($filters, array_flip($remove_filters));
        }
        return $filters;
    }

    /*
     * Exclude filters
     */
    public function disable_classes($classes)
    {
        $remove = [];

        // Dodajte klase za uklanjanje na osnovu uslova
        if (get_rstr_option('transliteration-mode', 'cyr_to_lat') === 'none') {
            $remove = array_merge($remove, [
                'Transliteration_Email',
                'Transliteration_Ajax',
                'Transliteration_Rest',
                'Transliteration_Search',
            ]);
        }

        if (Transliteration_Controller::get()->disable_transliteration()
            || is_null(Transliteration_Map::get()->map())) {
            $remove = array_merge($remove, [
                'Transliteration_Email',
                'Transliteration_Ajax',
                'Transliteration_Rest',
            ]);
        }

        if (get_rstr_option('enable-search', 'no') == 'no') {
            $remove[] = 'Transliteration_Search';
        }

        if (get_rstr_option('force-rest-api', 'yes') == 'no') {
            $remove[] = 'Transliteration_Rest';
        }

        if (get_rstr_option('force-ajax-calls', 'no') == 'no' || !wp_doing_ajax()) {
            $remove[] = 'Transliteration_Ajax';
        }

        if (get_rstr_option('force-email-transliteration', 'no') == 'no') {
            $remove[] = 'Transliteration_Email';
        }

        $remove = array_unique($remove);

        return Transliteration_Utilities::array_filter($classes, $remove);
    }

    /*
     * Exclude latin words
     */
    public function exclude_lat_words($list)
    {
        $exclude_latin_words = get_rstr_option('exclude-latin-words', '');

        if (!empty($exclude_latin_words)) {
            $array = [];
            if ($split = preg_split('/[\n|]/', $exclude_latin_words)) {
                $split = array_map('trim', $split);
                $split = array_filter($split);
                if ($split !== [] && is_array($split)) {
                    $array = $split;
                }
            }
            return array_merge($list, $array);
        }

        return $list;
    }

    /*
     * Exclude cyrillic words
     */
    public function exclude_cyr_words($list)
    {
        $exclude_cyrillic_words = get_rstr_option('exclude-cyrillic-words', '');

        if (!empty($exclude_cyrillic_words)) {
            $array = [];
            if ($split = preg_split('/[\n|]/', $exclude_cyrillic_words)) {
                $split = array_map('trim', $split);
                $split = array_filter($split);
                if ($split !== [] && is_array($split)) {
                    $array = $split;
                }
            }
            return array_merge($list, $array);
        }

        return $list;
    }

    /*
     * After settings sidebar
     */
    public function after_settings_sidebar($page, $obj): void
    { ?>
<div class="postbox transliteration-affiliate">
	<a href="https://freelanceposlovi.com/poslovi" target="_blank">
		<img src="<?php echo esc_url(RSTR_ASSETS . '/img/' . (Transliteration_Utilities::get_locale('sr_RS') ? 'logo-freelance-poslovi-sr_RS.jpg' : 'logo-freelance-poslovi.jpg')); ?>" alt="<?php esc_attr_e('Freelance Jobs - Find or post freelance jobs', 'serbian-transliteration'); ?>">
	</a>
</div>
<div class="postbox transliteration-affiliate">
	<a href="https://korisnickicentar.contrateam.com/aff.php?aff=385" target="_blank">
		<img src="<?php echo esc_url(RSTR_ASSETS . '/img/logo-contra-team.jpg'); ?>" alt="<?php esc_attr_e('Contra Team - A complete hosting solution in one place', 'serbian-transliteration'); ?>">
	</a>
</div>
		<?php /* if (in_array($page, ['credits', 'permalinks', 'transliteration'])) : ?>
<script data-name="BMC-Widget" data-cfasync="false" src="https://cdnjs.buymeacoffee.com/1.0.0/widget.prod.min.js" data-id="ivijanstefan" data-description="<?php esc_attr_e('Support my work by buying me a coffee!', 'serbian-transliteration'); ?>" data-message="<?php esc_attr_e('Thank you for using Transliterator. Could you buy me a coffee?', 'serbian-transliteration'); ?>" data-color="#FF813F" data-position="Right" data-x_margin="18" data-y_margin="50"></script>
		<?php endif; */
    }
}
