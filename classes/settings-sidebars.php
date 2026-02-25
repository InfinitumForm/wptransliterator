<?php if (!defined('WPINC')) {
    die();
}

class Transliteration_Settings_Sidebars
{
    public function donations(): void
    {
        ?>
		<?php printf('<p>%s</p>', __('Transliterator is free to use and actively maintained. Ongoing updates, performance improvements, and new features require continuous time and resources.', 'serbian-transliteration')); ?>
		<?php printf('<p>%s</p>', __('If the plugin adds value to your work, you are welcome to support its further development with a voluntary contribution.', 'serbian-transliteration')); /*?>
		<p><a href="https://www.buymeacoffee.com/ivijanstefan" target="_blank"><img src="https://img.buymeacoffee.com/button-api/?text=<?php esc_attr_e('Buy me a coffee', 'serbian-transliteration'); ?>&emoji=&slug=ivijanstefan&button_colour=FFDD00&font_colour=000000&font_family=Bree&outline_colour=000000&coffee_colour=ffffff" /></a></p>
		*/ ?>
		<hr>
		<ul>
			<?php printf(
				'<li>%s: <br><b>%s</b><br>IBAN: <b>%s</b><br>Swift: <b>%s</b></li>',
				__('Banca Intesa a.d. Beograd', 'serbian-transliteration'),
				'160-6000002167503-32',
				'RS35160600000216750332',
				'DBDBRSBG'
			); ?>
			<?php /* printf('<li><b>%s</b>: %s</li>', esc_html__('PayPal', 'serbian-transliteration'), 'creativform@gmail.com');*/ ?>
		</ul>
		<hr>
		<?php printf('<p>%s</p>', __('Thank you for your support.', 'serbian-transliteration'));
    }

    public function contributors(): void
    {
        if ($plugin_info = Transliteration_Utilities::plugin_info(['contributors' => true, 'donate_link' => true])) : ?>
		<div class="rstr-inside-metabox flex">
			<?php foreach ($plugin_info->contributors as $username => $info) : $info = (object) $info; ?>
			<div class="contributor contributor-<?php echo esc_attr($username); ?>" id="contributor-<?php echo esc_attr($username); ?>">
				<a href="<?php echo esc_url($info->profile); ?>" target="_blank">
					<img src="<?php echo esc_url($info->avatar); ?>">
					<h3><?php echo esc_html($info->display_name); ?></h3>
				</a>
			</div>
			<?php endforeach; ?>
		</div>
		<div class="rstr-inside-metabox">
			<?php printf('<p>%s</p>', sprintf(__('If you want to support our work and effort, if you have new ideas or want to improve the existing code, %s.', 'serbian-transliteration'), '<a href="https://github.com/CreativForm/serbian-transliteration" target="_blank">' . __('join our team', 'serbian-transliteration') . '</a>')); ?>
			<?php /* printf('<p>%s</p>', sprintf(__('If you want to help further plugin development, you can also %s.', 'serbian-transliteration'), '<a href="' . esc_url($plugin_info->donate_link) . '" target="_blank">' . __('donate something for effort', 'serbian-transliteration') . '</a>')); */ ?>
		</div>
		<?php endif;
    }
}
