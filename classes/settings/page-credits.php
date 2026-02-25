<?php if (!defined('WPINC')) {
    die();
}

$special_thanks_sponsors = [
    'INFINITUM FORM'       => 'https://infinitumform.com/',
    'FreelancePoslovi.com' => 'https://freelanceposlovi.com/',
    'Contra Team d.o.o.'   => 'https://korisnickicentar.contrateam.com/aff.php?aff=385',
    'UG Preobraženje'      => 'https://preobrazenje.rs/',
    'CNZD'                 => 'https://cnzd.rs/',
];

$special_thanks = [
    'tihi'                => 'https://profiles.wordpress.org/tihi',
    'dizajn24'            => 'https://profiles.wordpress.org/dizajn24',
    'Yaroslav Ingulskyi'  => 'https://profiles.wordpress.org/ingyaroslav',
	'BlackWiCKED'         => 'https://profiles.wordpress.org/blackwicked',
	'Nemanja Novaković'   => 'https://profiles.wordpress.org/lupus1204',
	'ivantre'             => 'https://profiles.wordpress.org/ivantre',
	'daliborm74'          => 'https://profiles.wordpress.org/daliborm74',
	'Željko Galetić'      => 'https://profiles.wordpress.org/zeljkogaletic',
	'Toni'                => 'https://profiles.wordpress.org/tonistankovic',
    'Slobodan Mirić'      => 'https://www.facebook.com/websitesworkshop',
    'Branislav Mitić'     => 'https://profiles.wordpress.org/xlr84xs/',
    'Dušan Filiferović'   => 'https://korisnickicentar.contrateam.com/aff.php?aff=385',
    'Boris Kuljaj'        => 'https://www.linkedin.com/in/boris-kuljaj-2944b4192/',
    'Branko Mrki Vidović' => 'https://www.facebook.com/branko.m.vidovic',
    'Slobodan Pantović'   => '',
    'Ivan Stanojević'     => '',
    'Ognjen Odobašić'     => '',
    'Miloš Maksimović'    => ''
]; ?>
<script>function rstr_popup(url, title, w, h) {
	// Fixes dual-screen position Most browsers Firefox
	var dualScreenLeft = (window.screenLeft != undefined ? window.screenLeft : screen.left),
		dualScreenTop = (window.screenTop != undefined ? window.screenTop : screen.top);
	
	width = (window.innerWidth ? window.innerWidth : (document.documentElement.clientWidth ? document.documentElement.clientWidth : screen.width));
	height = (window.innerHeight ? window.innerHeight : (document.documentElement.clientHeight ? document.documentElement.clientHeight : screen.height));
	
	var left = ((width / 2) - (w / 2)) + dualScreenLeft,
		top = ((height / 2) - (h / 2)) + dualScreenTop,
		newWindow = window.open(url, title, 'scrollbars=yes, width=' + w + ', height=' + h + ', top=' + top + ', left=' + left);
	
	// Puts focus on the newWindow
	if (window.focus) {
		newWindow.focus();
	}
};</script>
<h3><span class="dashicons dashicons-heart"></span> <?php esc_html_e('Support', 'serbian-transliteration'); ?></h3>
<?php printf('<p>%s</p>', esc_html__('This plugin is 100% free. If you want to buy us one coffee, beer or in general help the development of this plugin through a monetary donation, you can do it in the following ways:', 'serbian-transliteration')); ?>
<ul>
	<?php /*printf('<li><b>%s</b>: %s</li>', esc_html__('PayPal', 'serbian-transliteration'), 'creativform@gmail.com');*/ ?>
	<?php printf('<li><b>%s</b>: %s (%s)</li>', esc_html__('From Serbia', 'serbian-transliteration'), '160-6000002167503-32', esc_html__('Banca Intesa a.d. Beograd', 'serbian-transliteration')); ?>
</ul>


<h3><span class="dashicons dashicons-info"></span> <?php esc_html_e('Credits', 'serbian-transliteration'); ?></h3>
<?php printf('<p>%s</p>', esc_html__('This is a light weight, simple and easy plugin with which you can transliterate your WordPress installation from Cyrillic to Latin and vice versa in a few clicks. This transliteration plugin also supports special shortcodes that you can use to partially transliterate parts of the content.', 'serbian-transliteration')); ?>
<p><?php

    $special_thanks_sponsors_render = [];

foreach ($special_thanks_sponsors as $name => $thanks_url) {

    if (in_array($name, ['INFINITUM FORM', 'ContraTeam'])) {
        $name = '<b>' . $name . '</b>';
    }

    if ($thanks_url !== '0') {
        $special_thanks_sponsors_render[sanitize_title($name)] = '<a href="' . esc_url($thanks_url) . '" target="_blank">' . $name . '</a>';
    } else {
        $special_thanks_sponsors_render[sanitize_title($name)] = $name;
    }
}

printf(
    '<strong>%s</strong> %s %s',
    __('Sponsors of this plugin:', 'serbian-transliteration'),
    implode(', ', $special_thanks_sponsors_render),
    sprintf(
        '(%s)',
        sprintf(__('If you want to help develop this plugin and be one of the sponsors, please contact us at: %s', 'serbian-transliteration'), '<a href="mailto:infinitumform@gmail.com">infinitumform@gmail.com</a>')
    )
);

?></p>
<?php if ($plugin_info = Transliteration_Utilities::plugin_info(['contributors' => true, 'donate_link' => false])) : ?>
<p><?php

    $special_thanks_render = [];

    foreach ($plugin_info->contributors as $username => $info) {
        if (in_array($username, ['ivijanstefan', 'creativform', 'infinitumform'])) {
            continue;
        }
        $info                                                       = (object) $info;
        $special_thanks_render[sanitize_title($info->display_name)] = '<a href="' . esc_url($info->profile) . '" target="_blank">' . $info->display_name . '</a>';
    }

    foreach ($special_thanks as $name => $thanks_url) {
        if ($thanks_url !== '' && $thanks_url !== '0') {
            $special_thanks_render[sanitize_title($name)] = '<a href="' . esc_url($thanks_url) . '" target="_blank">' . $name . '</a>';
        } else {
            $special_thanks_render[sanitize_title($name)] = $name;
        }
    }

printf('<strong>%s</strong> %s', esc_html__('Special thanks to the contributors in the development of this plugin:', 'serbian-transliteration'), implode(', ', $special_thanks_render));

?></p>

<?php endif; ?>

<h3>&copy; <?php esc_html_e('Copyright', 'serbian-transliteration'); ?></h3>
<?php printf(
    '<p>%s</p>',
    sprintf(
        __('Copyright &copy; 2020 - %1$d %2$s by %3$s. All Right Reserved.', 'serbian-transliteration'),
        date('Y'),
        '<a href="https://wordpress.org/plugins/serbian-transliteration/" target="_blank"><em><strong>' . __('Transliterator – WordPress Transliteration', 'serbian-transliteration') . '</strong></em></a>',
        '<a href="https://www.linkedin.com/in/ivijanstefanstipic/" target="_blank"><em><strong>Ivijan-Stefan Stipić</strong></em></a>'
    )
); ?>

<?php printf('<p>%s</p>', esc_html__('This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; either version 2 of the License, or (at your option) any later version.', 'serbian-transliteration')); ?>
<?php printf('<p>%s</p>', esc_html__('This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.', 'serbian-transliteration')); ?>
<p><a href="javascript:void(0);" onClick="rstr_popup('<?php echo RSTR_URL; ?>/LICENSE.txt','GNU GENERAL PUBLIC LICENSE','550','450');"><?php esc_html_e('See the GNU General Public License for more details.', 'serbian-transliteration'); ?></a></p>
<?php printf('<p>%s</p>', esc_html__('You should have received a copy of the GNU General Public License along with this program; if not, write to the Free Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.', 'serbian-transliteration')); ?>