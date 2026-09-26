<?php if (!defined('WPINC')) {
    die();
} $nonce = esc_attr(wp_create_nonce('rstr-transliteration-letters')); ?>
<?php printf('<p>%s</p>', __('Copy the desired text into one field and press the desired key to convert the text.', 'serbian-transliteration')); ?>
<form class="rstr-row">
	<div class="rstr-col">
		<textarea name="rstr-transliteration-letters" id="rstr-transliteration-letters" class="form-control" rows="10"></textarea>
		<button type="button" class="button button-primary button-transliteration-letters" data-mode="cyr_to_lat" data-nonce="<?php echo esc_attr($nonce); ?>"><?php esc_html_e('Convert to Latin', 'serbian-transliteration'); ?></button>
		<button type="button" class="button button-primary button-transliteration-letters" data-mode="lat_to_cyr" data-nonce="<?php echo esc_attr($nonce); ?>"><?php esc_html_e('Convert to Cyrillic', 'serbian-transliteration'); ?></button>
	</div>
	<div class="rstr-col">
		<textarea name="rstr-transliteration-letters-result" id="rstr-transliteration-letters-result" class="form-control" rows="10" readonly></textarea>
		<button type="reset" class="button button-reset" data-mode="lat_to_cyr"><?php esc_html_e('Reset', 'serbian-transliteration'); ?></button>
	</div>
</form>