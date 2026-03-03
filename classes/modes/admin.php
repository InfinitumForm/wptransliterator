<?php

if (!defined('WPINC')) {
    die();
}

class Transliteration_Mode_Admin
{
    use Transliteration__Cache;

    // Mode ID
    public const MODE = 'admin';

    /*
     * Get current instance
     */
    public static function get()
    {
        return self::cached_static('instance', fn (): \Transliteration_Mode_Admin => new self());
    }

    /*
     * Get available filters for this mode
     */
    /**
     * @return array{'Transliteration_Mode_Admin', ('transliterate_admin_menu' | 'transliterate_pages_columns')}[]|'transliteration_json_content'[]
     */
    public function filters(): array
    {

        global $pagenow;

        if (!is_admin()) {
            return [];
        }

        $filters = [
            'ngettext'              => 'content__force_lat',
            'ngettext_with_context' => 'content__force_lat',
        //	'gettext_with_context'  => 'content__force_lat',
            'gettext'   => 'content__force_lat',
            'date_i18n' => 'content__force_lat',
        //	'the_title'             => 'content__force_lat',
            'wp_title'                     => 'content__force_lat',
            'option_blogname'              => 'content__force_lat',
            'option_blogdescription'       => 'content__force_lat',
            'document_title_parts'         => 'title_parts',
            'wp_get_object_terms'          => 'transliteration_wp_terms',
            'load_script_translations'     => 'transliteration_json_content',
            'pre_load_script_translations' => 'transliteration_json_content',
			'plugin_action_links' => [self::class, 'admin_plugin_action_links'],
			'network_admin_plugin_action_links' => [self::class, 'admin_plugin_action_links'],
            'admin_menu'                   => [self::class, 'transliterate_admin_menu'],
            'manage_pages_columns'         => [self::class, 'transliterate_pages_columns'],
            'display_post_states'          => [self::class, 'transliterate_pages_columns'],
        ];

        // WooCommerce fix
        if (RSTR_WOOCOMMERCE) {
            $filters['woocommerce_currency_symbol'] = 'content__force_lat';
            $filters['woocommerce_currencies']      = 'content__force_lat';
        }

        // Bug fix on the settings page
        if (in_array($pagenow, ['options-general.php', 'options.php'], true) && empty($_GET['page'])) {
            unset($filters['option_blogname'], $filters['option_blogdescription']);
        }
		
		// Performance + JS stability: do not process script translations on Menus screen.
		if (isset($pagenow) && $pagenow === 'nav-menus.php') {
			unset($filters['load_script_translations'], $filters['pre_load_script_translations']);
		}

        return $filters;
    }
	
	public static function admin_plugin_action_links($actions) {
	
		foreach($actions as &$action) {
			$action = Transliteration_Mode::get()->content__force_lat($action);
		}
		
		return $actions;
	}

    public static function transliterate_admin_menu(): void
    {
        global $menu, $submenu;
        if ($menu) {
            foreach ($menu as $key => $menu_item) {
                foreach ($menu_item as $key2 => $menu_item_item) {
                    $menu[$key][$key2] = Transliteration_Mode::get()->content__force_lat($menu_item_item);
                }
            }
        }

        if ($submenu) {
            foreach ($submenu as $key => $menu_item) {
                foreach ($menu_item as $key2 => $menu_item_item) {
                    if (isset($submenu[$key][$key2][0])) {
                        $submenu[$key][$key2][0] = Transliteration_Mode::get()->content__force_lat($submenu[$key][$key2][0]);
                    }
                }
            }
        }
    }

    public static function transliterate_pages_columns(array $columns): array
    {
        foreach ($columns as $key => $col) {
            $columns[$key] = Transliteration_Mode::get()->content__force_lat($col);
        }

        return $columns;
    }
}
