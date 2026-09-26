<?php
printf('<p>%s</p>', __('This tool enables you to convert all existing Cyrillic permalinks in your database to Latin characters.', 'serbian-transliteration'));
printf('<p><strong>%s</strong> %s</p>', __(
    'Warning:',
    'serbian-transliteration'
), __(
    'This option is dangerous and can create unexpected problems. Once you run this script, all permalinks in your database will be modified and this can affect on the SEO causing a 404 error.',
    'serbian-transliteration'
));
printf('<p>%s</p>', __('Before proceeding, consult with your SEO specialist, as you will likely need to resubmit your sitemap and adjust other settings to update the permalinks in search engines.', 'serbian-transliteration'));

printf('<p>%s: <code style="white-space: nowrap;word-break: keep-all;word-wrap: normal;">wp transliterate permalinks</code></p>', __('For advanced users, this function can also be executed via WP-CLI using the command', 'serbian-transliteration'));

printf('<p><strong class="text-danger">%s</strong></p>', sprintf(__('Important: Make sure to %s before running this script.', 'serbian-transliteration'), '<a href="https://wordpress.org/support/article/wordpress-backups/" target="_blank">' . __('back up your database', 'serbian-transliteration') . '</a>'));

$get_post_types = get_post_types([
    'public' => true,
], 'names', 'and');
/*
$post_types = array_map(function($match){
    return '<code><b>' . $match . '</b></code>';
}, $get_post_types);
*/
$post_types_selector = array_map(fn ($match): string => sprintf('<label for="tools-transliterate-post-type-%1$s"><input type="checkbox" id="tools-transliterate-post-type-%1$s" value="%1$s" class="tools-transliterate-permalinks-post-types" name="tools-transliterate-permalinks-post-types[]" checked><span>%2$s</span></label>', $match, strtr($match, ['_' => ' ',  '-' => ' '])), $get_post_types);

printf('<p>%s</p>', sprintf(__('This tool will affect on the following post types: %s', 'serbian-transliteration'), '<br><br>' . implode('&nbsp;&nbsp;&nbsp;&nbsp; ', $post_types_selector)));
?>
<br>
<div id="rstr-progress-bar" style="display:none;">
	<p class="progress-value" style="width:33%" data-value="33"></p>
	<progress max="100" value="33" class="php">
		<!-- Browsers that support HTML5 progress element will ignore the html inside `progress` element. Whereas older browsers will ignore the `progress` element and instead render the html inside it. -->
		<div class="progress-bar">
			<span style="width: 33%">33%</span>
		</div>
	</progress>
	<p class="progress-message"><?php esc_html_e('Please wait! Do not close the window or leave the page until this operation is completed!', 'serbian-transliteration'); ?></p>
</div>
<p>
	<input type="button" id="<?php echo 'serbian-transliteration' ?>-tools-transliterate-permalinks" class="button button-primary" data-nonce="<?php echo esc_attr(wp_create_nonce('rstr-run-permalink-transliteration')); ?>" value="<?php esc_attr_e("Let's do magic", 'serbian-transliteration'); ?>" disabled>
	&nbsp;&nbsp;&nbsp;
	<label for="<?php echo 'serbian-transliteration' ?>-tools-check">
		<input type="checkbox" id="<?php echo 'serbian-transliteration' ?>-tools-check" value="1"> <?php esc_html_e('Are you sure you want this?', 'serbian-transliteration'); ?>
	</label>
</p>
<blockquote id="rstr-disclaimer" style="display:none;">
	<h3><?php esc_html_e('Disclaimer', 'serbian-transliteration'); ?></h3>
	<?php printf('<p>%s</p>', __('While this tool is designed to operate safely, there is always a small risk of unpredictable issues.', 'serbian-transliteration')); ?>
	<?php printf('<p><b style="text-transform: uppercase;">%s</b></p>', __('Note: We do not guarantee that this tool will function correctly on your server. By using it, you assume all risks and responsibilities for any potential issues.', 'serbian-transliteration')); ?>
	<?php printf('<p><b style="text-transform: uppercase;">%s</b></p>', __('Backup your database before using this tool.', 'serbian-transliteration')); ?>
</blockquote>