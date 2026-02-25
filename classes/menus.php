<?php if (!defined('WPINC')) {
    die();
}
/**
 * Transliteration tools
 *
 * @link              http://infinitumform.com/
 * @since             1.0.0
 * @package           Serbian_Transliteration
 * @autor             Ivijan-Stefan Stipic
 */
final class Transliteration_Menus extends Transliteration
{
    use Transliteration__Cache;

    public function __construct()
    {
        $this->add_action('admin_head-nav-menus.php', 'admin_nav_menu');
        $this->add_filter('wp_setup_nav_menu_item', 'menu_setup');
        $this->add_filter('wp_nav_menu_objects', 'menu_objects');
        $this->add_action('wp_nav_menu_item_custom_fields', 'menu_item_custom_fields', 10, 5);
    }

    /* Registers Login/Logout/Register Links Metabox */
    public function admin_nav_menu(): void
    {

        if (!current_theme_supports('menus')) {
            return;
        }

        add_meta_box(
            'transliteration_menu',
            __('Transliteration', 'serbian-transliteration'),
            [$this, 'admin_nav_menu_callback'],
            'nav-menus',
            'side',
            'default'
        );
    }

    /* Displays Login/Logout/Register Links Metabox */
    public function admin_nav_menu_callback(): void
    {

        global $nav_menu_selected_id;

        $elems = [
            '#transliteration-lat#'    => __('Latin', 'serbian-transliteration'),
            '#transliteration-cyr#'    => __('Cyrillic', 'serbian-transliteration'),
            '#transliteration-latcyr#' => __('Latin', 'serbian-transliteration') . ' | ' . __('Cyrillic', 'serbian-transliteration'),
        ];
        $logitems = [
            'db_id'  => 0,
            'object' => 'bawlog',
            'object_id',
            'menu_item_parent' => 0,
            'type'             => 'custom',
            'title',
            'url',
            'target'     => '',
            'attr_title' => '',
            'classes'    => [],
            'xfn'        => '',
        ];

        $elems_obj = [];
        foreach ($elems as $value => $title) {
            $elems_obj[ $title ]            = (object) $logitems;
            $elems_obj[ $title ]->object_id = esc_attr($value);
            $elems_obj[ $title ]->title     = esc_attr($title);
            $elems_obj[ $title ]->url       = esc_attr($value);
        }

        $walker = new Walker_Nav_Menu_Checklist([]);
        ?>
		<div id="transliteration-links" class="transliterationlinksdiv">

            <div id="tabs-panel-transliteration-links-all" class="tabs-panel tabs-panel-view-all tabs-panel-active">
              <ul id="transliteration-linkschecklist" class="list:transliteration-links categorychecklist form-no-clear">
                <?php echo walk_nav_menu_tree(array_map('wp_setup_nav_menu_item', $elems_obj), 0, (object) ['walker' => $walker]); ?>
              </ul>
            </div>

            <p class="button-controls">
              <span class="list-controls hide-if-no-js">
                <a href="javascript:void(0);" class="help" onclick="jQuery( '#transliteration-menu-help' ).toggle();"><?php esc_html_e('Help', 'serbian-transliteration'); ?></a>
                <span class="hide-if-js" id="transliteration-menu-help"><br /><a name="transliteration-menu-help"></a>
                  <?php
                  echo '•' . esc_html__("To insert language script selector just add a relative link after the link's keyword, example :", 'serbian-transliteration') . ' <br /><code>#transliteration-latcyr#</code>.';
        echo '<br /><br />•' . esc_html__('You can also use', 'serbian-transliteration') . ' <code>#transliteration-lat#</code> ' . esc_html__('for change to Latin or use', 'serbian-transliteration') . ' <code>#transliteration-cyr#</code>' . esc_html__('for change to Cyrillic', 'serbian-transliteration') . '.';
        ?>
                  </span>
                </span>

                <span class="add-to-menu">
                  <input type="submit"<?php disabled($nav_menu_selected_id, 0); ?> class="button-secondary submit-add-to-menu right" value="<?php esc_attr_e('Add to Menu', 'serbian-transliteration'); ?>" name="add-transliteration-links-menu-item" id="submit-transliteration-links" />
                  <span class="spinner"></span>
                </span>
              </p>

		</div>
		<?php

    }

    /* Add custom fields to the menu item */
    public function menu_item_custom_fields($item_id, $item, $depth = 0, $args = null, $id = 0): void
    {

        if (!current_theme_supports('menus')) {
            return;
        }

        if ($item->url == '#transliteration-latcyr#') {
            printf(
                '<p style="padding:10px; background:cornsilk; float:left; margin-right: 10px; font-size:1.1em;"><strong>%s<br><br>%s<br><br>%s</strong></p>',
                sprintf(__('The name of this navigation is written by always putting the Latin name first, then the Cyrillic one second, separated by the sign %s', 'serbian-transliteration'), '<code>|</code>'),
                __('Example: Latinica | Ћирилица', 'serbian-transliteration'),
                __('Note that the white space around them will be cleared.', 'serbian-transliteration')
            );
        }

    }

    /**
     * Set title
     *
     * @since 1.0.0
     * @param object $title   The menu item object.
     * @param object $options The menu options.
     */
    public function transliteration_setup_title($title, $options)
    {
        return self::cached_static('transliteration_setup_title', function () use ($title, $options) {
            if (!current_theme_supports('menus')) {
                return $title;
            }

            $titles = explode('|', ($title ?? ''));

            if (is_array($titles)) {
                $titles = array_map('trim', $titles);
            }

            if ($options->active == 'cyr') {
                return '{cyr_to_lat}' . esc_html($titles[0] ?? $title) . '{/cyr_to_lat}';
            }
            return '{lat_to_cyr}' . esc_html($titles[1] ?? $title) . '{/lat_to_cyr}';
        }, [$title, $options->active]);
    }

    /**
     * Filters a navigation menu item object. Decorates a menu item object with the shared navigation menu item properties on front end.
     *
     * @since 1.2.0
     * @param object $menu_item The menu item object.
     */
    public function menu_setup($item)
	{

		if (!current_theme_supports('menus')) {
			return $item;
		}

		global $pagenow;

		if ($pagenow != 'nav-menus.php' && ! defined('DOING_AJAX') && isset($item->url) && strstr($item->url, '#transliteration') != '') {

			// Mark transliteration menu items so we can detect them later
			if (!isset($item->classes) || !is_array($item->classes)) {
				$item->classes = [];
			}

			if (!in_array('menu-item-transliteration', $item->classes, true)) {
				$item->classes[] = 'menu-item-transliteration';
			}

			$get_script = (isset($_COOKIE['rstr_script']) && in_array($_COOKIE['rstr_script'], apply_filters('rstr/allowed_script', ['cyr', 'lat']), true) ? $_COOKIE['rstr_script'] : 'none');

			$options = (object) [
				'active' => $get_script,
				'cyr'    => add_query_arg(get_rstr_option('url-selector', 'rstr'), 'cyr'),
				'lat'    => add_query_arg(get_rstr_option('url-selector', 'rstr'), 'lat'),
			];

			$item_url      = substr($item->url ?? '', 0, strpos($item->url ?? '', '#', 1)) . '#';
			$item_redirect = str_replace($item_url, '', $item->url);

			if ($item_redirect == '%current-page%') {
				$item_redirect = ($_SERVER['REQUEST_URI'] ?? '');
			}

			if ($item_url === '#transliteration-latcyr#') {
				$item_redirect = explode('|', ($item_redirect ?? ''));

				if (count($item_redirect) != 2) {
					$item_redirect    = array_map('trim', $item_redirect);
					$item_redirect[1] = $item_redirect[0];
				}

				$item->url   = $options->active == 'cyr' ? $options->lat : $options->cyr;
				$item->title = $this->transliteration_setup_title($item->title, $options);
			} elseif ($item_url === '#transliteration-lat#') {
				if ($options->active == 'cyr') {
					$item->url = $options->lat;
					return $item;
				}
			} elseif ($item_url === '#transliteration-cyr#') {
				if ($options->active == 'lat') {
					$item->url = $options->cyr;
					return $item;
				}
			}

			$item->url = esc_url($item->url);
		}

		return $item;
	}

    public function menu_objects(array $sorted_menu_items): array
	{
		$hide_trans_items = ! Transliteration_Utilities::is_cyrillic_locale();

		foreach ($sorted_menu_items as $index => $item) {
			$item_url = isset($item->url) ? (string) $item->url : '';

			$has_hash = ($item_url !== '' && strpos($item_url, '#transliteration') !== false);
			$has_class = (
				isset($item->classes)
				&& is_array($item->classes)
				&& in_array('menu-item-transliteration', $item->classes, true)
			);

			$is_trans_item = $has_hash || $has_class;

			if ($is_trans_item && $hide_trans_items) {
				unset($sorted_menu_items[$index]);
			}
		}

		return $sorted_menu_items;
	}


}
