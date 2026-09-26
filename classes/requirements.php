<?php if (!defined('WPINC')) {
    die();
}
/**
 * Transliteration Requirements
 *
 * @link              http://infinitumform.com/
 * @since             1.0.0
 * @package           Serbian_Transliteration
 */
final class Transliteration_Requirements
{
	private $title = 'WordPress Transliteration';
	private $php   = '7.0';
	private $wp    = '4.0';
	private $file;

	public function __construct($args)
	{
		foreach (['title', 'php', 'wp', 'file'] as $setting) {
			if (isset($args[$setting])) {
				$this->{$setting} = $args[$setting];
			}
		}

		add_action('in_plugin_update_message-' . RSTR_BASENAME, [$this, 'in_plugin_update_message'], 10, 2);

		if (get_rstr_option('mode') === 'woocommerce' && RSTR_WOOCOMMERCE === false) {
			add_action('admin_notices', [$this, 'woocommerce_disabled_notice'], 10, 2);
		}

		if (function_exists('mb_substr') === false) {
			add_action('admin_notices', [$this, 'mb_extension_notice'], 10, 2);
		}

		add_action('admin_init', [$this, 'privacy_policy']);
	}

	public function privacy_policy(): void
	{
		if (! function_exists('wp_add_privacy_policy_content')) {
			return;
		}
		$content = '';
		$content .= sprintf(
			'<p class="privacy-policy-tutorial">%s</p>',
			__('This plugin uses cookies and should be listed in your Privacy Policy page to prevent privacy issues.', 'serbian-transliteration')
		);
		$content .= sprintf(
			'<p><b>%s</b></p>',
			__('Suggested text:', 'serbian-transliteration')
		);
		$content .= sprintf('<p>%s</p>', sprintf(
			__('This website uses the %1$s plugin to transliterate content.', 'serbian-transliteration'),
			$this->title
		));
		$content .= sprintf(
			'<p>%s</p>',
			__('This is a simple and easy add-on with which this website translates content from Cyrillic to Latin and vice versa. This transliteration plugin also supports special shortcodes and functions that use cookies to specify the language script.', 'serbian-transliteration')
		);
		$content .= sprintf('<p>%s</p>', sprintf(
			__('These cookies do not affect your privacy because they are not intended for tracking and analytics. These cookies can have only two values: "%1$s" or "%2$s".', 'serbian-transliteration'),
			'lat',
			'cyr'
		));

		wp_add_privacy_policy_content(
			$this->title,
			wp_kses_post(wpautop($content, false))
		);
	}

	public function in_plugin_update_message($args, $response): void
	{
		$upgrade_notice = isset($response->upgrade_notice) && is_string($response->upgrade_notice) ? trim($response->upgrade_notice) : '';
		if ($upgrade_notice !== '') : ?>
<style>
.serbian-transliteration-upgrade-notice{
padding: 10px;
color: #000;
margin-top: 10px
}
.serbian-transliteration-upgrade-notice-list ol{
list-style-type: decimal;
padding-left:0;
margin-left: 15px;
}
.serbian-transliteration-upgrade-notice + p{
display:none;
}
.serbian-transliteration-upgrade-notice-info{
margin-top:32px;
font-weight:600;
}
</style>
<div class="serbian-transliteration-upgrade-notice">
<h3><?php
/* translators: %s: plugin version. */
printf(esc_html__('Important upgrade notice for the version %s:', 'serbian-transliteration'), esc_html((string) ($response->new_version ?? '')));
?></h3>
<div class="serbian-transliteration-upgrade-notice-list">
<?php
$allowed_upgrade_notice_html = [
	'a'      => [
		'href'   => true,
		'title'  => true,
		'target' => true,
		'rel'    => true,
	],
	'b'      => [],
	'br'     => [],
	'code'   => [],
	'em'     => [],
	'i'      => [],
	'li'     => [],
	'ol'     => [],
	'p'      => [],
	'strong' => [],
	'ul'     => [],
];

echo wp_kses(str_replace(
	[
	 '<ul>',
	 '</ul>',
	],
	[
	 '<ol>',
	 '</ol>',
	],
	$upgrade_notice
), $allowed_upgrade_notice_html); ?>
</div>
<div class="serbian-transliteration-upgrade-notice-info">
<?php esc_html_e('NOTE: Before doing the update, it would be a good idea to backup your WordPress installations and settings.', 'serbian-transliteration'); ?>
</div>
</div> 
	<?php endif;
	}

	public function passes()
	{
		$passes = $this->php_passes() && $this->wp_passes();
		if (! $passes) {
			add_action('admin_notices', [$this, 'deactivate']);
		}
		return $passes;
	}

	public function deactivate(): void
	{
		if ($this->file !== null) {
			deactivate_plugins(plugin_basename($this->file));
		}
	}

	private function php_passes(): bool
	{
		if ($this->__php_at_least($this->php)) {
			return true;
		}
		add_action('admin_notices', [$this, 'php_version_notice']);
		return false;
	}

	private function __php_at_least($min_version): bool
	{
		return version_compare(phpversion(), $min_version, '>=');
	}

	public function php_version_notice(): void
	{
		echo '<div class="notice notice-error">';
		echo '<p>' . sprintf(__('The %1$s cannot run on PHP versions older than PHP %2$s. Please contact your host and ask them to upgrade.', 'serbian-transliteration'), esc_html($this->title), $this->php) . '</p>';
		echo '</div>';
	}

	public function woocommerce_disabled_notice(): void
	{
		echo '<div class="notice notice-error">';
		echo '<p>' . sprintf(
			'<strong>%1$s</strong> %2$s',
			__('Transliteration plugin requires attention:', 'serbian-transliteration'),
			sprintf(
				__('Your plugin works under Only WooCoomerce mode and you need to %s because WooCommerce is no longer active.', 'serbian-transliteration'),
				'<a href="' . admin_url('/options-general.php?page=serbian-transliteration&tab=settings') . '">' . __('update your settings', 'serbian-transliteration') . '</a>'
			)
		) . '</p>';
		echo '</div>';
	}

	public function mb_extension_notice(): void
	{
		echo '<div class="notice notice-error">';
		echo '<p>' . sprintf(
			'<strong>%1$s</strong> %2$s',
			__('Transliteration plugin requires a Multibyte String PHP extension (mbstring).', 'serbian-transliteration'),
			sprintf(
				__('Without %s you will not be able to use this plugin.', 'serbian-transliteration'),
				'<a href="https://www.php.net/manual/en/mbstring.installation.php" target="_blank" title="' . __('Multibyte String Installation', 'serbian-transliteration') . '">' . __('this PHP extension', 'serbian-transliteration') . '</a>'
			)
		) . '</p>';
		echo '</div>';
	}

	private function wp_passes(): bool
	{
		if ($this->__wp_at_least($this->wp)) {
			return true;
		}
		add_action('admin_notices', [$this, 'wp_version_notice']);
		return false;
	}

	private function __wp_at_least($min_version): bool
	{
		return version_compare(get_bloginfo('version'), $min_version, '>=');
	}

	public function wp_version_notice(): void
	{
		echo '<div class="notice notice-error">';
		echo '<p>' . sprintf(__('The %1$s cannot run on WordPress versions older than %2$s. Please update your WordPress installation.', 'serbian-transliteration'), esc_html($this->title), $this->wp) . '</p>';
		echo '</div>';
	}
}
