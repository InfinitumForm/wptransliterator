<?php if (!defined('WPINC')) {
    die();
} ?>
<p class="description"><?php esc_html_e('These are available PHP functions that you can use in your themes and plugins.', 'serbian-transliteration'); ?></p>
<h2 style="margin:0;">get_latin_url</h2>
<p><code class="lang-php">function get_latin_url(string $url = NULL) : string</code></p>
<br>
<h2 style="margin:0;">get_cyrillic_url</h2>
<p><code class="lang-php">function get_cyrillic_url(string $url = NULL) : string</code></p>
<br>
<h2 style="margin:0;">get_active_transliteration</h2>
<p><code class="lang-php">function get_active_transliteration() : string</code></p>
<br>
<h2 style="margin:0;">is_cyrillic_text</h2>
<p><code class="lang-php">function is_cyrillic_text(string $content) : bool</code></p>
<br>
<h2 style="margin:0;">is_latin_text</h2>
<p><code class="lang-php">function is_latin_text(string $content) : bool</code></p>
<br>
<h2 style="margin:0;">is_already_cyrillic</h2>
<?php printf('<p>%s</p>', __('Determines whether the site is already in Cyrillic.', 'serbian-transliteration')); ?>
<p><code class="lang-php">function is_already_cyrillic() : bool</code></p>
<br>
<h2 style="margin:0;">is_cyrillic</h2>
<p><code class="lang-php">function is_cyrillic() : bool</code></p>
<br>
<h2 style="margin:0;">is_latin</h2>
<p><code class="lang-php">function is_latin() : bool</code></p>
<br>
<h2 style="margin:0;">is_serbian</h2>
<p><code class="lang-php">function is_serbian() : bool</code></p>
<br>

<h2 style="margin:0;">is_bosnian</h2>
<p><code class="lang-php">function is_bosnian() : bool</code></p>
<br>

<h2 style="margin:0;">is_montenegrin</h2>
<p><code class="lang-php">function is_montenegrin() : bool</code></p>
<br>

<h2 style="margin:0;">is_russian</h2>
<p><code class="lang-php">function is_russian() : bool</code></p>
<br>

<h2 style="margin:0;">is_belarusian</h2>
<p><code class="lang-php">function is_belarusian() : bool</code></p>
<br>

<h2 style="margin:0;">is_bulgarian</h2>
<p><code class="lang-php">function is_bulgarian() : bool</code></p>
<br>

<h2 style="margin:0;">is_macedonian</h2>
<p><code class="lang-php">function is_macedonian() : bool</code></p>
<br>

<h2 style="margin:0;">is_ukrainian</h2>
<p><code class="lang-php">function is_ukrainian() : bool</code></p>
<br>

<h2 style="margin:0;">is_kazakh</h2>
<p><code class="lang-php">function is_kazakh() : bool</code></p>
<br>

<h2 style="margin:0;">is_tajik</h2>
<p><code class="lang-php">function is_tajik() : bool</code></p>
<br>

<h2 style="margin:0;">is_kyrgyz</h2>
<p><code class="lang-php">function is_kyrgyz() : bool</code></p>
<br>

<h2 style="margin:0;">is_mongolian</h2>
<p><code class="lang-php">function is_mongolian() : bool</code></p>
<br>

<h2 style="margin:0;">is_bashkir</h2>
<p><code class="lang-php">function is_bashkir() : bool</code></p>
<br>

<h2 style="margin:0;">is_uzbek</h2>
<p><code class="lang-php">function is_uzbek() : bool</code></p>
<br>

<h2 style="margin:0;">is_georgian</h2>
<p><code class="lang-php">function is_georgian() : bool</code></p>
<br>

<h2 style="margin:0;">is_greek</h2>
<p><code class="lang-php">function is_greek() : bool</code></p>
<br>

<h2 style="margin:0;">is_armenian</h2>
<p><code class="lang-php">function is_armenian() : bool</code></p>
<br>

<h2 style="margin:0;">is_arabic</h2>
<p><code class="lang-php">function is_arabic() : bool</code></p>
<br>

<h2 style="margin:0;">transliteration_excluded</h2>
<?php printf('<p>%s</p>', __('This function provides information on whether the currently active language is excluded from transliteration.', 'serbian-transliteration')); ?>
<p><code class="lang-php">function transliteration_excluded() : bool</code></p>
<br>
<h2 style="margin:0;">transliterate</h2>
<?php printf('<p>%s</p>', __('Transliteration of some text or content into the desired script.', 'serbian-transliteration')); ?>
<p><code class="lang-php">function transliterate(string $content, string $type='cyr_to_lat', bool $fix_html = true) : string</code></p>
<?php printf('<p>%s</p>', __('The <b><i>$type</i></b> parameter has two values: <code>cyr_to_lat</code> (Cyrillic to Latin) and <code>lat_to_cyr</code> (Latin to Cyrillic)', 'serbian-transliteration')); ?>
<br>
<h2 style="margin:0;">cyr_to_lat</h2>
<?php printf('<p>%s</p>', __('Transliteration from Cyrillic to Latin.', 'serbian-transliteration')); ?>
<p><code class="lang-php">function cyr_to_lat(string $content, bool $sanitize_html = true) : string</code></p>
<br>
<h2 style="margin:0;">lat_to_cyr</h2>
<?php printf('<p>%s</p>', __('Transliteration from Latin to Cyrillic.', 'serbian-transliteration')); ?>
<p><code class="lang-php">function lat_to_cyr(string $content, bool $sanitize_html = true, bool $fix_diacritics = false) : string</code></p>
<br>
<h2 style="margin:0;">cyr_to_ascii_lat</h2>
<?php printf('<p>%s</p>', __('Transliterates Cyrillic characters to Latin, converting them to their basic ASCII equivalents by removing diacritics.', 'serbian-transliteration')); ?>
<p><code class="lang-php">function cyr_to_ascii_lat(string $content) : string</code></p>
<br>
<h2 style="margin:0;"><?php echo(function_exists('get_script') ? 'get_script' : 'rstr_get_script'); ?></h2>
<?php printf('<p>%s</p>', __('Get active script.', 'serbian-transliteration')); ?>
<p><code class="lang-php">function <?php echo(function_exists('get_script') ? 'get_script' : 'rstr_get_script'); ?>() : string</code></p>
<br>
<h2 style="margin:0;">script_selector</h2>
<?php printf('<p>%s</p>', __('This function displays a selector for the transliteration script.', 'serbian-transliteration')); ?>
<p><code class="lang-php">function script_selector(array $args) : string|echo|array|object</code></p>
<h3><?php esc_html_e('Parameters', 'serbian-transliteration'); ?></h3>
<?php printf('<p><b><code>$args</code></b> (array) - %1$s</p>', __('This attribute contains an associative set of parameters for this function:', 'serbian-transliteration')); ?>
<ul>
	<?php printf(
	    '<li><code>%1$s</code> - %2$s</li>',
	    'display_type',
	    sprintf(
	        __('(string) The type of selector that will be displayed on the site. It can be: %1$s, %2$s, %3$s, %4$s, %5$s or %6$s. Default: %1$s', 'serbian-transliteration'),
	        '<code>inline</code>',
	        '<code>select</code>',
	        '<code>list</code>',
	        '<code>list_items</code>',
	        '<code>array</code>',
	        '<code>object</code>'
	    )
	); ?>
	<?php printf(
	    '<li><code>%1$s</code> - %2$s</li>',
	    'echo',
	    sprintf(__('(bool) determines whether it will be displayed through an echo or as a string. Default: %s', 'serbian-transliteration'), '<code>false</code>')
	); ?>
	<?php printf(
	    '<li><code>%1$s</code> - %2$s</li>',
	    'separator',
	    sprintf(__('(string) Separator to be used when the selector type is %s. Default: %s', 'serbian-transliteration'), '<code>inline</code>', '<code> | </code>')
	); ?>
	<?php printf(
	    '<li><code>%1$s</code> - %2$s</li>',
	    'cyr_caption',
	    sprintf(__('(string) Text for Cyrillic link. Default: %s', 'serbian-transliteration'), '<code>' . __('Cyrillic', 'serbian-transliteration') . '</code>')
	); ?>
	<?php printf(
	    '<li><code>%1$s</code> - %2$s</li>',
	    'lat_caption',
	    sprintf(__('(string) Text for Latin link. Default: %s', 'serbian-transliteration'), '<code>' . __('Latin', 'serbian-transliteration') . '</code>')
	); ?>
</ul>