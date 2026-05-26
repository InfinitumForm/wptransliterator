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
		
		$this->add_action('wp_footer', 'print_dynamic_dom_observer', 9999);

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
            if ($split = preg_split('/[\n|,;]/', $exclude_latin_words)) {
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
            if ($split = preg_split('/[\n|,;]/', $exclude_cyrillic_words)) {
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
	
	/**
	 * Print frontend observer for dynamic DOM content.
	 *
	 * This observer handles JavaScript-rendered frontend content from WooCommerce Blocks,
	 * Elementor, Visual Composer, Gutenberg blocks, AJAX, fetch, XHR and history navigation.
	 *
	 * It only transliterates visible text nodes and selected safe attributes.
	 * It does not transliterate HTML tags, URLs, href/src attributes, JSON, nonce values,
	 * scripts, styles, form fields or editor zones.
	 *
	 * @return void
	 */
	public function print_dynamic_dom_observer(): void
	{
		if (get_rstr_option('js-dynamic-transliteration', 'no') === 'no') {
			return;
		}
		
		if (is_admin()) {
			return;
		}

		if (apply_filters('transliteration_dynamic_dom_observer_disabled', false)) {
			return;
		}

		$direction = Transliteration_Controller::get()->mode();

		if (!in_array($direction, ['cyr_to_lat', 'lat_to_cyr'], true)) {
			return;
		}
		
		if(get_rstr_option('site-script', 'cyr') === 'cyr' && $direction === 'lat_to_cyr') {
			return;
		}
		
		if(get_rstr_option('site-script', 'cyr') === 'lat' && $direction === 'cyr_to_lat') {
			return;
		}

		$map = $this->get_js_transliteration_map($direction);

		if ($map === []) {
			return;
		}

		$exclude = $direction === 'cyr_to_lat'
			? Transliteration_Controller::get()->cyr_exclude_list()
			: Transliteration_Controller::get()->lat_exclude_list();

		$exclude = array_filter(array_map('trim', $exclude));

		$json_map       = wp_json_encode($map, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		$json_exclude   = wp_json_encode($exclude, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		$json_direction = wp_json_encode($direction, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

		if (!$json_map || false === $json_exclude || !$json_direction) {
			return;
		}
		?>
		<script>
		(function () {
			"use strict";
			
			var RSTR = window.RSTR || (window.RSTR = {});

			if (!RSTR.seed) {
				RSTR.seed = String(Date.now()) + String(Math.floor(Math.random() * 1000000));
			}

			if (window.RSTRDynamicObserver && window.RSTRDynamicObserver.initialized) {
				window.RSTRDynamicObserver.run(document.body);
				return;
			}

			var direction = <?php echo $json_direction; ?>;
			var map = <?php echo $json_map; ?>;
			var excludeWords = <?php echo $json_exclude; ?>;

			var keys = Object.keys(map).sort(function (a, b) {
				return b.length - a.length;
			});

			if (!keys.length) {
				return;
			}

			function escapeRegExp(v) {
				return String(v).replace(/[.*+?^${}()|[\]\\]/g, "\\$&");
			}

			var pattern = new RegExp(keys.map(escapeRegExp).join("|"), "gu");

			var scriptPattern = direction === "cyr_to_lat"
				? /[\u0400-\u04FF]/u
				: /[A-Za-zŠĐČĆŽšđčćžǴǵḰḱ]/u;

			var excludePattern = null;

			if (Array.isArray(excludeWords) && excludeWords.length) {
				excludeWords = excludeWords
					.filter(function (w) {
						return typeof w === "string" && w.trim() !== "";
					})
					.sort(function (a, b) {
						return b.length - a.length;
					});

				if (excludeWords.length) {
					excludePattern = new RegExp(
						"(^|[^\\p{L}\\p{N}_])(" + excludeWords.map(escapeRegExp).join("|") + ")(?=$|[^\\p{L}\\p{N}_])",
						"gu"
					);
				}
			}

			function hasTargetScript(value) {
				return typeof value === "string" && scriptPattern.test(value);
			}

			function protectExcludedWords(value, storage) {
				if (!excludePattern || !value) return value;

				excludePattern.lastIndex = 0;

				return value.replace(excludePattern, function (full, prefix, word) {
					var token = "%%::" + RSTR.seed + "::0::" + storage.length + "::%%";
					storage.push({ token: token, value: word });
					return prefix + token;
				});
			}

			function restoreExcludedWords(value, storage) {
				if (!storage.length) return value;

				storage.forEach(function (item) {
					value = value.split(item.token).join(item.value);
				});

				return value;
			}

			function transliterateText(value) {
				if (!value || typeof value !== "string" || !hasTargetScript(value)) {
					return value;
				}

				var storage = [];

				value = protectExcludedWords(value, storage);

				pattern.lastIndex = 0;

				value = value.replace(pattern, function (m) {
					return map[m] || m;
				});

				return restoreExcludedWords(value, storage);
			}

			function shouldSkipNode(node) {
				if (!node || !node.parentNode) return true;

				var tag = node.parentNode.nodeName;

				if ([
					"SCRIPT","STYLE","TEXTAREA","INPUT","SELECT","OPTION",
					"CODE","PRE","SVG","TEMPLATE","NOSCRIPT"
				].indexOf(tag) !== -1) {
					return true;
				}

				if (node.parentNode.closest &&
					node.parentNode.closest("[data-rstr-skip], .rstr-skip, [contenteditable='true']")) {
					return true;
				}

				return false;
			}

			function transliterateAttributes(el) {
				if (!el || el.nodeType !== 1) return;

				if (el.matches &&
					el.matches("[data-rstr-skip], .rstr-skip, script, style, textarea, input, select, option, code, pre, svg, template, noscript")) {
					return;
				}

				["title","alt","aria-label","placeholder"].forEach(function (attr) {
					if (!el.hasAttribute || !el.hasAttribute(attr)) return;

					var val = el.getAttribute(attr);

					if (!hasTargetScript(val)) return;

					var next = transliterateText(val);

					if (next !== val) {
						el.setAttribute(attr, next);
					}
				});
			}

			function walk(root) {
				root = root || document.body;
				if (!root) return;

				if (root.nodeType === 3 && root.parentNode) {
					root = root.parentNode;
				}

				if (root.nodeType !== 1 && root.nodeType !== 9) return;

				if (root.nodeType === 1) {
					transliterateAttributes(root);

					if (root.querySelectorAll) {
						root.querySelectorAll("[title],[alt],[aria-label],[placeholder]")
							.forEach(transliterateAttributes);
					}
				}

				var walker = document.createTreeWalker(
					root,
					NodeFilter.SHOW_TEXT,
					{
						acceptNode: function (node) {
							if (shouldSkipNode(node)) return NodeFilter.FILTER_REJECT;
							if (!node.nodeValue || !hasTargetScript(node.nodeValue)) return NodeFilter.FILTER_REJECT;

							pattern.lastIndex = 0;

							if (!pattern.test(node.nodeValue)) return NodeFilter.FILTER_REJECT;

							return NodeFilter.FILTER_ACCEPT;
						}
					}
				);

				var nodes = [];

				while (walker.nextNode()) {
					nodes.push(walker.currentNode);
				}

				nodes.forEach(function (n) {
					var next = transliterateText(n.nodeValue);
					if (next !== n.nodeValue) {
						n.nodeValue = next;
					}
				});
			}

			var scheduled = false;

			function run(root) {
				if (scheduled) return;

				scheduled = true;

				requestAnimationFrame(function () {
					scheduled = false;
					walk(root || document.body);

					if (document.title && hasTargetScript(document.title)) {
						document.title = transliterateText(document.title);
					}
				});
			}

			function scheduleAfterAjax() {
				setTimeout(function () { run(document.body); }, 30);
				setTimeout(function () { run(document.body); }, 250);
				setTimeout(function () { run(document.body); }, 800);
			}

			function patchFetch() {
				if (!window.fetch || window.fetch.__rstr) return;

				var f = window.fetch;

				window.fetch = function () {
					return f.apply(this, arguments).then(function (r) {
						scheduleAfterAjax();
						return r;
					});
				};

				window.fetch.__rstr = true;
			}

			function patchXHR() {
				if (!window.XMLHttpRequest || window.XMLHttpRequest.__rstr) return;

				var open = XMLHttpRequest.prototype.open;
				var send = XMLHttpRequest.prototype.send;

				XMLHttpRequest.prototype.open = function () {
					this.__rstr = true;
					return open.apply(this, arguments);
				};

				XMLHttpRequest.prototype.send = function () {
					if (this.__rstr) {
						this.addEventListener("loadend", scheduleAfterAjax);
					}
					return send.apply(this, arguments);
				};

				window.XMLHttpRequest.__rstr = true;
			}

			function patchJQuery() {
				if (!window.jQuery || window.jQuery.__rstr) return;

				jQuery(document).on("ajaxComplete", scheduleAfterAjax);
				window.jQuery.__rstr = true;
			}

			function patchHistory() {
				if (!window.history || window.history.__rstr) return;

				["pushState","replaceState"].forEach(function (m) {
					var orig = history[m];
					history[m] = function () {
						var r = orig.apply(this, arguments);
						scheduleAfterAjax();
						return r;
					};
				});

				window.addEventListener("popstate", scheduleAfterAjax);
				window.history.__rstr = true;
			}

			var observer = new MutationObserver(function (mutations) {
				mutations.forEach(function (m) {
					if (m.addedNodes) {
						m.addedNodes.forEach(function (n) {
							if (n.nodeType === 1 || n.nodeType === 3) {
								run(n);
							}
						});
					}
				});
			});

			observer.observe(document.body, {
				childList: true,
				subtree: true,
				characterData: true
			});

			window.RSTRDynamicObserver = {
				initialized: true,
				run: run
			};

			patchFetch();
			patchXHR();
			patchJQuery();
			patchHistory();

			run(document.body);
		}());
		</script>
		<?php
	}

	/**
	 * Get transliteration map prepared for JavaScript.
	 *
	 * @param string $direction Transliteration direction.
	 *
	 * @return array<string, string>
	 */
	private function get_js_transliteration_map(string $direction): array
	{
		$class_map = Transliteration_Map::get()->map();

		if (!$class_map || !class_exists($class_map) || !property_exists($class_map, 'map')) {
			return [];
		}

		$map = $class_map::$map ?? [];

		if (!is_array($map) || $map === []) {
			return [];
		}

		$locale = preg_replace('/^Transliteration_Map_/', '', $class_map);

		if ($locale) {
			$map = apply_filters('transliteration_map_' . $locale, $map);
		}

		if ($direction === 'cyr_to_lat') {
			return $this->sort_js_map($map);
		}

		$reverse = array_flip($map);

		// Fallback
		if(!class_exists('Transliteration_Map_sr_RS', false)) {
			include_once RSTR_CLASSES . '/maps/sr_RS.php';
		}
		$custom = Transliteration_Map_sr_RS::$map;
		
		if($direction === 'lat_to_cyr') {
			$custom = array_flip($custom);
		}

		return $this->sort_js_map(array_merge($custom, $reverse));
	}

	/**
	 * Sort transliteration map by key length.
	 *
	 * @param array<string, string> $map Transliteration map.
	 *
	 * @return array<string, string>
	 */
	private function sort_js_map(array $map): array
	{
		uksort($map, static function ($a, $b): int {
			return mb_strlen((string) $b, 'UTF-8') <=> mb_strlen((string) $a, 'UTF-8');
		});

		return $map;
	}
}
