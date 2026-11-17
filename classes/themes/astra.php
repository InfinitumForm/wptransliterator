<?php

if (!defined('WPINC')) {
    die();
}

/**
 * Compatibility rules for the Astra theme and its child themes.
 *
 * Astra uses contextual gettext and the %astra% sentinel for retrieving
 * editable header/footer content from theme options. Avoid processing that
 * context through Transliterator's generic gettext_with_context handler.
 */
class Transliteration_Theme_Astra extends Transliteration
{
    /**
     * Register Astra-specific compatibility rules.
     */
    public function __construct()
    {
        $this->add_filter('transliteration_mode_filters', 'exclude_contextual_gettext', PHP_INT_MAX - 200);
        $this->add_action('init', 'disable_phantom_contextual_gettext', 20);
    }

    /**
     * Remove contextual gettext from filter-based transliteration modes.
     *
     * @param array<string, mixed> $filters Active translation hooks.
     * @return array<string, mixed>
     */
    public function exclude_contextual_gettext($filters): array
    {
        if (!is_array($filters)) {
            return [];
        }

        unset($filters['gettext_with_context']);

        return $filters;
    }

    /**
     * Phantom registers its contextual gettext callback directly, outside
     * transliteration_mode_filters. Remove only that specific callback.
     * Other WordPress and third-party gettext callbacks remain active.
     */
    public function disable_phantom_contextual_gettext(): void
    {
        if (get_rstr_option('mode', 'light') !== 'phantom') {
            return;
        }

        if (!class_exists('Transliteration_Mode_Phantom', false)) {
            return;
        }

        remove_filter(
            'gettext_with_context',
            [Transliteration_Mode_Phantom::get(), 'gettext_context_content'],
            9999
        );
    }
}
