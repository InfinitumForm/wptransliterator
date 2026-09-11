<?php if (!defined('WPINC')) {
    die();
} ?>
<p class="description"><?php esc_html_e('These are available shortcodes that you can use in your content, visual editors, themes and plugins.', 'serbian-transliteration'); ?></p>
<h2 style="margin:0;"><?php esc_html_e('Keep exact content', 'serbian-transliteration'); ?>:</h2>
<p><code class="lang-txt">[<span class="hljs-title">keep_translit</span>]<?php esc_html_e('Keep this text exactly as written', 'serbian-transliteration'); ?>[/<span class="hljs-title">keep_translit</span>]</code></p>
<br>
<h2 style="margin:0;"><?php esc_html_e('Skip active transliteration', 'serbian-transliteration'); ?>:</h2>
<p><code class="lang-txt">[<span class="hljs-title">skip_translit</span>]<?php esc_html_e('Display this text in the original site script', 'serbian-transliteration'); ?>[/<span class="hljs-title">skip_translit</span>]</code></p>
<br>
<h2 style="margin:0;"><?php esc_html_e('Cyrillic to Latin', 'serbian-transliteration'); ?>:</h2>
<p><code class="lang-txt">[<span class="hljs-title">cyr_to_lat</span>]Ћирилица у латиницу[/<span class="hljs-title">cyr_to_lat</span>]</code></p>
<ul>
	<?php printf('<li><code>%1$s</code> - %2$s</li>', 'fix_html', esc_html__('(optional) correct HTML code.', 'serbian-transliteration')); ?>
</ul>
<br>
<h2 style="margin:0;"><?php esc_html_e('Latin to Cyrillic', 'serbian-transliteration'); ?>:</h2>
<p><code class="lang-txt">[<span class="hljs-title">lat_to_cyr</span>]Latinica u ćirilicu[/<span class="hljs-title">lat_to_cyr</span>]</code></p>
<h3><?php esc_html_e('Optional shortcode parameters', 'serbian-transliteration'); ?>:</h3>
<ul>
	<?php printf('<li><code>%1$s</code> - %2$s</li>', 'fix_html', esc_html__('(optional) correct HTML code.', 'serbian-transliteration')); ?>
	<?php printf('<li><code>%1$s</code> - %2$s</li>', 'fix_diacritics', esc_html__('(optional) correct diacritics.', 'serbian-transliteration')); ?>
</ul>
<br>
<h2 style="margin:0;"><?php esc_html_e('Add an image depending on the language script', 'serbian-transliteration'); ?>:</h2>
<?php printf('<p>%s</p>', esc_html__('With this shortcode you can manipulate images and display images in Latin or Cyrillic depending on the setup.', 'serbian-transliteration')); ?>
<p><code class="lang-txt">[<span class="hljs-title">rstr_img</span>]</code></p>
<h4><?php esc_html_e('Example', 'serbian-transliteration'); ?>:</h4>
<p><code class="lang-txt">[<span class="hljs-title">rstr_img</span> <span class="hljs-params"><span class="hljs-keyword">lat</span>="<?php echo esc_url(home_url('/logo_latin.jpg')); ?>"</span> <span class="hljs-params"><span class="hljs-keyword">cyr</span>="<?php echo esc_url(home_url('/logo_cyrillic.jpg')); ?>"</span>]</code></p>
<h3><?php esc_html_e('Main shortcode parameters', 'serbian-transliteration'); ?>:</h3>
<ul>
	<?php printf('<li><code>%1$s</code> - %2$s</li>', 'lat', esc_html__('URL (src) as shown in the Latin language', 'serbian-transliteration')); ?>
	<?php printf('<li><code>%1$s</code> - %2$s</li>', 'cyr', esc_html__('URL (src) as shown in the Cyrillic language', 'serbian-transliteration')); ?>
	<?php printf('<li><code>%1$s</code> - %2$s</li>', 'default', esc_html__('(optional) URL (src) to the default image if Latin and Cyrillic are unavailable', 'serbian-transliteration')); ?>
</ul>
<h3><?php esc_html_e('Optional shortcode parameters', 'serbian-transliteration'); ?>:</h3>
<ul>
	<?php printf('<li><code>%1$s</code> - %2$s</li>', 'cyr_title', esc_html__('(optional) title (alt) description of the image for Cyrillic', 'serbian-transliteration')); ?>
	<?php printf('<li><code>%1$s</code> - %2$s</li>', 'cyr_caption', esc_html__('(optional) caption description of the image for Cyrillic', 'serbian-transliteration')); ?>
	<?php printf('<li><code>%1$s</code> - %2$s</li>', 'lat_title', esc_html__('(optional) title (alt) description of the image for Latin', 'serbian-transliteration')); ?>
	<?php printf('<li><code>%1$s</code> - %2$s</li>', 'lat_caption', esc_html__('(optional) caption description of the image for Latin', 'serbian-transliteration')); ?>
	<?php printf('<li><code>%1$s</code> - %2$s</li>', 'default_title', esc_html__('(optional) title (alt) description of the image if Latin and Cyrillic are unavailable', 'serbian-transliteration')); ?>
	<?php printf('<li><code>%1$s</code> - %2$s</li>', 'default_caption', esc_html__('(optional) caption description of the imag if Latin and Cyrillic are unavailable', 'serbian-transliteration')); ?>
</ul>
<h3><?php esc_html_e('Shortcode return', 'serbian-transliteration'); ?>:</h3>
<?php printf('<p>%s</p>', esc_html__('HTML image corresponding to the parameters set in this shortcode.', 'serbian-transliteration')); ?>

<br>
<h2 style="margin:0;"><?php esc_html_e('Language script menu', 'serbian-transliteration'); ?>:</h2>
<?php printf('<p>%s</p>', esc_html__('This shortcode displays a selector for the transliteration script.', 'serbian-transliteration')); ?>
<p><code class="lang-txt">[<span class="hljs-title">rstr_selector</span>]</code></p>
<h3><?php esc_html_e('Optional shortcode parameters', 'serbian-transliteration'); ?>:</h3>
<ul>
	<?php printf(
		'<li><code>%1$s</code> - %2$s</li>',
		'type',
		sprintf(
			/* translators: 1: inline. 2: select. 3: list. 4: list_items. */
			esc_html__('(string) The type of selector that will be displayed on the site. It can be: "%1$s", "%2$s", "%3$s" or "%4$s"', 'serbian-transliteration'),
			'inline',
			'select',
			'list',
			'list_items'
		)
	); ?>
	<?php printf(
		'<li><code>%1$s</code> - %2$s</li>',
		'separator',
		sprintf(
			/* translators: 1: Selector type. 2: Default separator. */
			esc_html__('(string) Separator to be used when the selector type is %1$s. Default: %2$s', 'serbian-transliteration'),
			'inline',
			' | '
		)
	); ?>
	<?php printf('<li><code>%1$s</code> - %2$s</li>', 'cyr_caption', esc_html__('(string) Text for Cyrillic link. Default: Cyrillic', 'serbian-transliteration')); ?>
	<?php printf('<li><code>%1$s</code> - %2$s</li>', 'lat_caption', esc_html__('(string) Text for Latin link. Default: Latin', 'serbian-transliteration')); ?>
</ul>
<br><br>
<?php printf('<p><b>%s</b></p>', esc_html__('This shortcodes work independently of the plugin settings and can be used anywhere within WordPress pages, posts, taxonomies and widgets (if they support it).', 'serbian-transliteration')); ?>
