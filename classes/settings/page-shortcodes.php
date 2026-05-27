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
	<?php printf('<li><code>%1$s</code> - %2$s</li>', 'fix_html', __('(optional) correct HTML code.', 'serbian-transliteration')); ?>
</ul>
<br>
<h2 style="margin:0;"><?php esc_html_e('Latin to Cyrillic', 'serbian-transliteration'); ?>:</h2>
<p><code class="lang-txt">[<span class="hljs-title">lat_to_cyr</span>]Latinica u ćirilicu[/<span class="hljs-title">lat_to_cyr</span>]</code></p>
<h3><?php esc_html_e('Optional shortcode parameters', 'serbian-transliteration'); ?>:</h3>
<ul>
	<?php printf('<li><code>%1$s</code> - %2$s</li>', 'fix_html', __('(optional) correct HTML code.', 'serbian-transliteration')); ?>
	<?php printf('<li><code>%1$s</code> - %2$s</li>', 'fix_diacritics', __('(optional) correct diacritics.', 'serbian-transliteration')); ?>
</ul>
<br>
<h2 style="margin:0;"><?php esc_html_e('Add an image depending on the language script', 'serbian-transliteration'); ?>:</h2>
<?php printf('<p>%s</p>', __('With this shortcode you can manipulate images and display images in Latin or Cyrillic depending on the setup.', 'serbian-transliteration')); ?>
<p><code class="lang-txt">[<span class="hljs-title">rstr_img</span>]</code></p>
<h4><?php esc_html_e('Example', 'serbian-transliteration'); ?>:</h4>
<p><code class="lang-txt">[<span class="hljs-title">rstr_img</span> <span class="hljs-params"><span class="hljs-keyword">lat</span>="<?php echo home_url('/logo_latin.jpg') ?>"</span> <span class="hljs-params"><span class="hljs-keyword">cyr</span>="<?php echo home_url('/logo_cyrillic.jpg') ?>"</span>]</code></p>
<h3><?php esc_html_e('Main shortcode parameters', 'serbian-transliteration'); ?>:</h3>
<ul>
	<?php printf('<li><code>%1$s</code> - %2$s</li>', 'lat', __('URL (src) as shown in the Latin language', 'serbian-transliteration')); ?>
	<?php printf('<li><code>%1$s</code> - %2$s</li>', 'cyr', __('URL (src) as shown in the Cyrillic language', 'serbian-transliteration')); ?>
	<?php printf('<li><code>%1$s</code> - %2$s</li>', 'default', __('(optional) URL (src) to the default image if Latin and Cyrillic are unavailable', 'serbian-transliteration')); ?>
</ul>
<h3><?php esc_html_e('Optional shortcode parameters', 'serbian-transliteration'); ?>:</h3>
<ul>
	<?php printf('<li><code>%1$s</code> - %2$s</li>', 'cyr_title', __('(optional) title (alt) description of the image for Cyrillic', 'serbian-transliteration')); ?>
	<?php printf('<li><code>%1$s</code> - %2$s</li>', 'cyr_caption', __('(optional) caption description of the image for Cyrillic', 'serbian-transliteration')); ?>
	<?php printf('<li><code>%1$s</code> - %2$s</li>', 'lat_title', __('(optional) title (alt) description of the image for Latin', 'serbian-transliteration')); ?>
	<?php printf('<li><code>%1$s</code> - %2$s</li>', 'lat_caption', __('(optional) caption description of the image for Latin', 'serbian-transliteration')); ?>
	<?php printf('<li><code>%1$s</code> - %2$s</li>', 'default_title', __('(optional) title (alt) description of the image if Latin and Cyrillic are unavailable', 'serbian-transliteration')); ?>
	<?php printf('<li><code>%1$s</code> - %2$s</li>', 'default_caption', __('(optional) caption description of the imag if Latin and Cyrillic are unavailable', 'serbian-transliteration')); ?>
</ul>
<h3><?php esc_html_e('Shortcode return', 'serbian-transliteration'); ?>:</h3>
<?php printf('<p>%s</p>', __('HTML image corresponding to the parameters set in this shortcode.', 'serbian-transliteration')); ?>

<br>
<h2 style="margin:0;"><?php esc_html_e('Language script menu', 'serbian-transliteration'); ?>:</h2>
<?php printf('<p>%s</p>', __('This shortcode displays a selector for the transliteration script.', 'serbian-transliteration')); ?>
<p><code class="lang-txt">[<span class="hljs-title">rstr_selector</span>]</code></p>
<h3><?php esc_html_e('Optional shortcode parameters', 'serbian-transliteration'); ?>:</h3>
<ul>
	<?php printf('<li><code>%1$s</code> - %2$s</li>', 'type', sprintf(__('(string) The type of selector that will be displayed on the site. It can be: "%1$s", "%2$s", "%3$s" or "%4$s"', 'serbian-transliteration'), 'inline', 'select', 'list', 'list_items')); ?>
	<?php printf('<li><code>%1$s</code> - %2$s</li>', 'separator', sprintf(__('(string) Separator to be used when the selector type is %s. Default: %s', 'serbian-transliteration'), 'inline', ' | ')); ?>
	<?php printf('<li><code>%1$s</code> - %2$s</li>', 'cyr_caption', __('(string) Text for Cyrillic link. Default: Cyrillic', 'serbian-transliteration')); ?>
	<?php printf('<li><code>%1$s</code> - %2$s</li>', 'lat_caption', __('(string) Text for Latin link. Default: Latin', 'serbian-transliteration')); ?>
</ul>
<br><br>
<?php printf('<p><b>%s</b></p>', __('This shortcodes work independently of the plugin settings and can be used anywhere within WordPress pages, posts, taxonomies and widgets (if they support it).', 'serbian-transliteration')); ?>