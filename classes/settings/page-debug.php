<?php if (!defined('WPINC')) {
    die();
}
$activations = get_option(RSTR_NAME . '-activation');
?><br>
<table class="table table-sm table-striped w-100">
<?php // Hook to extend debug table header ?>
	<thead><?php do_action('rstr/settings/debug/table/thead'); ?></thead>
<?php // Hook before first debug table row ?>
	<tbody>
		<?php do_action('rstr/settings/debug/table/tbody/start'); ?>
		<tr>
			<td width="30%" style="width:30%;"><strong><?php esc_html_e('Plugin ID', 'serbian-transliteration'); ?></strong></td>
			<td><?php echo get_option(RSTR_NAME . '-ID'); ?></td>
		</tr>
		<tr>
			<td width="30%" style="width:30%;"><strong><?php esc_html_e('Plugin version', 'serbian-transliteration'); ?></strong></td>
			<td><?php echo RSTR_VERSION; ?></td>
		</tr>
		<tr>
			<td><strong><?php esc_html_e('WordPress version', 'serbian-transliteration'); ?></strong></td>
			<td><?php echo get_bloginfo('version'); ?></td>
		</tr>
		<tr>
			<td><strong><?php esc_html_e('Last plugin update', 'serbian-transliteration'); ?></strong></td>
			<td><?php echo(empty($activations) ? '-' : end($activations)); ?></td>
		</tr>
		<tr>
			<td><strong><?php esc_html_e('PHP version', 'serbian-transliteration'); ?></strong></td>
			<td>PHP <?php echo PHP_VERSION; ?></td>
		</tr>
		<tr>
			<td><strong><?php esc_html_e('PHP version ID', 'serbian-transliteration'); ?></strong></td>
			<td><?php echo PHP_VERSION_ID; ?></td>
		</tr>
		<tr>
			<td><strong><?php esc_html_e('PHP architecture', 'serbian-transliteration'); ?></strong></td>
			<td><?php printf(__('%d bit', 'serbian-transliteration'), (Transliteration_Debug::is_php64() ? 64 : 32)); ?></td>
		</tr>
		<tr>
			<td><strong><?php esc_html_e('WordPress debug', 'serbian-transliteration'); ?></strong></td>
			<td><?php echo(WP_DEBUG ? '<strong><span style="color:#007d1b">' . esc_html__('On', 'serbian-transliteration') . '</span></strong>' : __('Off', 'serbian-transliteration')); ?></td>
		</tr>
		<tr>
			<td><strong><?php esc_html_e('WordPress multisite', 'serbian-transliteration'); ?></strong></td>
			<td><?php echo(RSTR_MULTISITE ? '<strong><span style="color:#007d1b">' . esc_html__('On', 'serbian-transliteration') . '</span></strong>' : __('Off', 'serbian-transliteration')); ?></td>
		</tr>
	<?php if (RSTR_WOOCOMMERCE) : ?>
		<tr>
			<td><strong><?php esc_html_e('WooCommerce active', 'serbian-transliteration'); ?></strong></td>
			<td><?php echo(RSTR_WOOCOMMERCE ? '<strong><span style="color:#007d1b">' . esc_html__('On', 'serbian-transliteration') . '</span></strong>' : __('Off', 'serbian-transliteration')); ?></td>
		</tr>
		<?php if (defined('WC_VERSION')) : ?>
		<tr>
			<td><strong><?php esc_html_e('WooCommerce version', 'serbian-transliteration'); ?></strong></td>
			<td><?php echo WC_VERSION; ?></td>
		</tr>
		<?php endif; ?>
	<?php endif; ?>
		<tr>
			<td><strong><?php esc_html_e('Site title', 'serbian-transliteration'); ?></strong></td>
			<td><?php if ($name = get_bloginfo('name')) {
			    echo $name;
			} else {
			    echo '-';
			} ?></td>
		</tr>
		<tr>
			<td><strong><?php esc_html_e('Tagline', 'serbian-transliteration'); ?></strong></td>
			<td><?php if ($description = get_bloginfo('description')) {
			    echo $description;
			} else {
			    echo '-';
			} ?></td>
		</tr>
		<tr>
			<td><strong><?php esc_html_e('WordPress address (URL)', 'serbian-transliteration'); ?></strong></td>
			<td><?php echo get_bloginfo('wpurl'); ?></td>
		</tr>
		<tr>
			<td><strong><?php esc_html_e('Admin email', 'serbian-transliteration'); ?></strong></td>
			<td><?php echo get_bloginfo('admin_email'); ?></td>
		</tr>
		<tr>
			<td><strong><?php esc_html_e('Encoding for pages and feeds', 'serbian-transliteration'); ?></strong></td>
			<td><?php echo get_bloginfo('charset'); ?></td>
		</tr>
		<tr>
			<td><strong><?php esc_html_e('Content-Type', 'serbian-transliteration'); ?></strong></td>
			<td><?php echo get_bloginfo('html_type'); ?></td>
		</tr>
		<tr>
			<td><strong><?php esc_html_e('Site Language', 'serbian-transliteration'); ?></strong></td>
			<td><?php echo get_locale(); ?></td>
		</tr>
		<tr>
			<td><strong><?php esc_html_e('Server time', 'serbian-transliteration'); ?></strong></td>
			<td><?php echo date('r'); ?></td>
		</tr>
		<tr>
			<td><strong><?php esc_html_e('WordPress directory path', 'serbian-transliteration'); ?></strong></td>
			<td><?php echo ABSPATH; ?></td>
		</tr>
		<tr>
			<td><strong><?php esc_html_e('Operting system', 'serbian-transliteration'); ?></strong></td>
			<td><?php echo Transliteration_Debug::getOS(); ?> <?php printf(__('%d bit', 'serbian-transliteration'), Transliteration_Debug::architecture()); ?></td>
		</tr>
		<tr>
			<td><strong><?php esc_html_e('User agent', 'serbian-transliteration'); ?></strong></td>
			<td><?php echo Transliteration_Debug::user_agent(); ?></td>
		</tr>
		<tr>
			<td><strong><?php esc_html_e('Plugin directory path', 'serbian-transliteration'); ?></strong></td>
<?php // Hook after last debug table row ?>
			<td><?php echo RSTR_ROOT; ?></td>
<?php // Hook to extend debug table footer ?>
		</tr>
		<?php do_action('rstr/settings/debug/table/tbody/end'); ?>
	</tbody>
	<tfoot><?php do_action('rstr/settings/debug/table/tfoot'); ?></tfoot>
</table>
<br><br>
<?php /* div class="accordion-container">
	<button class="accordion-link" type="button"><?php esc_html_e('Plugin settings', 'serbian-transliteration'); ?></button>
	<div class="accordion-panel" style="padding:0;">
		<?php Transliteration_Utilities::debug_render_all_settings_fields('html'); ?>
	</div>
</div */ ?>
<h3><?php esc_html_e('Plugin settings', 'serbian-transliteration'); ?></h3>
<?php Transliteration_Utilities::debug_render_all_settings_fields('html');