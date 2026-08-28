<?php
/**
 * WP Rocket compatibility layer.
 *
 * Provides full compatibility between Serbian Transliteration and
 * WP Rocket page caching by creating separate cache variants for
 * each active script, preventing cache pollution between Cyrillic
 * and Latin (or any other supported transliteration scheme).
 *
 * This compatibility layer also contains additional client-side
 * synchronization logic that corrects cached navigation fragments
 * and browser history cache (bfcache) after page restoration,
 * ensuring that the visible script always matches the currently
 * selected transliteration.
 *
 * The implementation dynamically uses the active transliteration
 * map provided by the plugin itself, making it compatible with all
 * supported language mappings instead of relying on hardcoded
 * Serbian-specific character tables.
 *
 * Plugin:
 * Serbian Transliteration
 *
 * @package Serbian_Transliteration
 *
 * @author Ivijan-Stefan Stipić <https://infinitumform.com>
 * @copyright Copyright (c) Ivijan-Stefan Stipić
 * @license GPL-2.0-or-later
 *
 * Contributors:
 * - Boris Košpić (WordPress.org: Boka003)
 *   https://www.dizajnarena.com
 *   Contributed to the WP Rocket compatibility layer,
 *   including client-side cache synchronization logic
 *   and cache behavior improvements.
 *
 * @since 2.5.7
 */

if (!defined('WPINC')) {
    die();
}

final class Transliteration_Plugin_Wp_Rocket extends Transliteration
{
    /**
     * Cookie used by the plugin to store the selected script.
     */
    private const SCRIPT_COOKIE = 'rstr_script';

    /**
     * Register WP Rocket compatibility hooks.
     */
    public function __construct()
    {
        if (!$this->is_cache_support_enabled()) {
            return;
        }

        $this->add_filter(
            'rocket_cache_mandatory_cookies',
            'register_mandatory_cookie'
        );

        $this->add_filter(
            'rocket_cache_dynamic_cookies',
            'register_dynamic_cookie'
        );

        /*
         * Dynamic cookie detection requires WP Rocket's PHP cache
         * processing instead of direct .htaccess file delivery.
         *
         * This must use the native WordPress add_filter() function because
         * the plugin helper converts string callbacks into class methods.
         */
        add_filter(
            'rocket_htaccess_mod_rewrite',
            '__return_false'
        );

        /*
         * Correct any stale full-page or fragment cache that was generated
         * in the opposite script. Priority 1 keeps the correction synchronous
         * and early enough to avoid displaying the wrong script first.
         */
        $this->add_action(
            'wp_head',
            'print_cache_corrector',
            1
        );
    }

    /**
     * Register the transliteration cookie as mandatory.
     *
     * WP Rocket will not serve a generic cached page before the visitor's
     * selected script is known.
     *
     * @param array<int, string> $cookies Mandatory cookie names.
     *
     * @return array<int, string>
     */
    public function register_mandatory_cookie(array $cookies): array
    {
        return $this->register_cookie($cookies);
    }

    /**
     * Register the transliteration cookie as dynamic.
     *
     * WP Rocket will create a separate cached page variant for each
     * supported cookie value, currently "lat" and "cyr".
     *
     * @param array<int, string> $cookies Dynamic cookie names.
     *
     * @return array<int, string>
     */
    public function register_dynamic_cookie(array $cookies): array
    {
        return $this->register_cookie($cookies);
    }

    /**
     * Add the script cookie without creating duplicate entries.
     *
     * @param array<int, string> $cookies Cookie names.
     *
     * @return array<int, string>
     */
    private function register_cookie(array $cookies): array
    {
        $cookies[] = self::SCRIPT_COOKIE;

        return array_values(
            array_unique(
                array_filter($cookies, 'is_string')
            )
        );
    }
    /**
     * The site's configured fallback script for a visitor with no cookie
     * yet, mirroring Transliterator's own first-visit default.
     */
    private function default_script(): string {
        $default = function_exists( 'get_rstr_option' ) ? get_rstr_option( 'first-visit-mode', 'lat' ) : 'lat';
        return in_array( $default, array( 'cyr', 'lat' ), true ) ? $default : 'lat';
    }


    /**
     * Get the currently active PHP transliteration maps.
     *
     * The source map is loaded from the locale selected by the plugin, so
     * this cache integration follows the same alphabet and custom map filter
     * as the main PHP transliteration process.
     *
     * @return array{source_to_target: array<string, string>, target_to_source: array<string, string>}
     */
    private function get_active_transliteration_maps(): array
    {
        $empty_maps = [
            'source_to_target' => [],
            'target_to_source' => [],
        ];

        if (!class_exists('Transliteration_Map')) {
            return $empty_maps;
        }

        $map_class = Transliteration_Map::get()->map();

        if (
            !$map_class
            || !class_exists($map_class)
            || !property_exists($map_class, 'map')
        ) {
            return $empty_maps;
        }

        $source_to_target = $map_class::$map ?? [];

        if (!is_array($source_to_target) || $source_to_target === []) {
            return $empty_maps;
        }

        $locale = preg_replace('/^Transliteration_Map_/', '', $map_class);

        if (is_string($locale) && $locale !== '') {
            $source_to_target = apply_filters(
                'transliteration_map_' . $locale,
                $source_to_target
            );
        }

        $source_to_target = array_filter(
            $source_to_target,
            static function ($value, $key): bool {
                return is_string($key)
                    && $key !== ''
                    && is_string($value)
                    && $value !== '';
            },
            ARRAY_FILTER_USE_BOTH
        );

        if ($source_to_target === []) {
            return $empty_maps;
        }

        $target_to_source = array_flip($source_to_target);

        uksort(
            $source_to_target,
            static fn ($first, $second): int => mb_strlen($second) <=> mb_strlen($first)
        );

        uksort(
            $target_to_source,
            static fn ($first, $second): int => mb_strlen($second) <=> mb_strlen($first)
        );

        return [
            'source_to_target' => $source_to_target,
            'target_to_source' => $target_to_source,
        ];
    }

    /**
     * Print a small, synchronous, inline script as early as possible. It
     * hides the page for at most a couple of seconds (usually milliseconds),
     * scans the rendered text for anything that doesn't match the visitor's
     * own script preference, corrects it in place, then reveals the page.
     * Runs every load; on an already-correct page it finds nothing to do.
     */
    public function print_cache_corrector(): void {

        if ( is_admin() ) {
            return;
        }
        $default = $this->default_script();
        $maps    = $this->get_active_transliteration_maps();
        ?>
<style>html.rstr-pending body{visibility:hidden}</style>
<script id="rstr-cache-bridge">
(function(d){
    "use strict";

    var DEFAULT_SCRIPT = <?php echo wp_json_encode( $default ); ?>;

    var html = d.documentElement;

    function currentWant(){
        var m = document.cookie.match(/(?:^|;\s*)rstr_script=(cyr|lat)/);
        return m ? m[1] : DEFAULT_SCRIPT;
    }

    // Hide immediately, synchronously, before first paint - this covers the
    // normal initial load. hide()/reveal() are called again around each
    // later run() too (e.g. a bfcache restore), since by then the browser
    // has already painted whatever it restored and may need re-hiding
    // only for the brief moment it takes to re-check and correct it.
    var revealed = false;
    var safetyTimer = null;
    function hide(){
        revealed = false;
        html.classList.add('rstr-pending');
        safetyTimer = setTimeout(reveal, 2000);
    }
    function reveal(){
        if (revealed) return;
        revealed = true;
        if (safetyTimer) clearTimeout(safetyTimer);
        html.classList.remove('rstr-pending');
    }
    hide();

    // Use the same active locale map as the PHP transliteration engine.
    var SOURCE_TO_TARGET = <?php echo wp_json_encode($maps['source_to_target']); ?>;
    var TARGET_TO_SOURCE = <?php echo wp_json_encode($maps['target_to_source']); ?>;

    function escapeRe(s){ return s.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'); }

    function buildRe(map, global){
        var keys = Object.keys(map).sort(function(a, b){ return b.length - a.length; });
        if (!keys.length) return null;
        return new RegExp(keys.map(escapeRe).join('|'), global ? 'g' : '');
    }

    var SOURCE_RE = buildRe(SOURCE_TO_TARGET, true);
    var TARGET_RE = buildRe(TARGET_TO_SOURCE, true);
    var HAS_SOURCE = buildRe(SOURCE_TO_TARGET, false);
    var HAS_TARGET = buildRe(TARGET_TO_SOURCE, false);

    function convert(text, dir){
        var map = dir === 'source_to_target' ? SOURCE_TO_TARGET : TARGET_TO_SOURCE;
        var re = dir === 'source_to_target' ? SOURCE_RE : TARGET_RE;

        if (!re) return text;

        return text.replace(re, function(ch){ return map[ch] || ch; });
    }

    // Per text node: detect its actual current script and fix ONLY if it
    // disagrees with what this visitor wants. This is what makes a single
    // stale fragment (e.g. a cached menu) correctable even when the rest
    // of the page is already right - each node is judged on its own.
    function fixNode(node, want){
        var text = node.nodeValue;
        if (HAS_SOURCE && HAS_SOURCE.test(text)) {
            if (want === 'lat') {
                node.nodeValue = convert(text, 'source_to_target');
            }
            return;
        }

        if (want === 'cyr' && HAS_TARGET && HAS_TARGET.test(text)) {
            node.nodeValue = convert(text, 'target_to_source');
        }
    }

    // Elements whose text should never be touched.
    var SKIP_TAGS = { SCRIPT:1, STYLE:1, NOSCRIPT:1, TEXTAREA:1, CODE:1, PRE:1 };
    var SWITCH_HREF_RE = /[?&](rstr|script|lang_script|letter|translt|skripta|pismo)=(lat|cyr)\b/i;

    function isSwitcherLink(p){
        var a = p.closest ? p.closest('a[href]') : null;
        if (!a) return false;
        if (a.classList && a.classList.contains('rstr-script-selector')) return true;
        var href = a.getAttribute('href') || '';
        return SWITCH_HREF_RE.test(href);
    }

    function run(){
        var want = currentWant();
        try {
            if (!document.body) return;

            var tw = document.createTreeWalker(document.body, NodeFilter.SHOW_TEXT, {
                acceptNode: function(node){
                    var p = node.parentNode;
                    if (!p || SKIP_TAGS[p.nodeName]) return NodeFilter.FILTER_REJECT;
                    if (p.closest && p.closest('[data-no-translit]')) return NodeFilter.FILTER_REJECT;
                    // Script-switcher labels (e.g. "Latinica"/"Ćirilica") name a
                    // script on purpose and must stay put, like "Français" would
                    // in a language picker - never "correct" them.
                    if (isSwitcherLink(p)) return NodeFilter.FILTER_REJECT;
                    return NodeFilter.FILTER_ACCEPT;
                }
            });
            var n, batch = [];
            while ((n = tw.nextNode())) batch.push(n);
            for (var i = 0; i < batch.length; i++) fixNode(batch[i], want);
        } finally {
            reveal();
        }
    }

    if (document.readyState === 'loading') {
        d.addEventListener('DOMContentLoaded', run);
    } else {
        run();
    }

    // Back/forward-cache restores (browser back/forward button) show the
    // page exactly as it was left, WITHOUT re-running any scripts - so a
    // cookie change made on another page would otherwise never be picked
    // up when the visitor navigates back here. pageshow with
    // event.persisted fires specifically for that case; re-hide briefly
    // and re-check against the live cookie.
    window.addEventListener('pageshow', function(event){
        if (!event.persisted) return;
        hide();
        run();
    });
})(document);
</script>
        <?php
    }


    /**
     * Determine whether the plugin's cache compatibility is enabled.
     */
    private function is_cache_support_enabled(): bool
    {
        return get_rstr_option('cache-support', 'no') === 'yes';
    }
}
