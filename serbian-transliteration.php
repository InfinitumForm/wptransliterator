<?php

/**
 * Plugin Name:       Transliterator – Multilingual and Multi-script Text Conversion
 * Plugin URI:        https://wordpress.org/plugins/serbian-transliteration/
 * Description:       All-in-one Cyrillic to Latin transliteration plugin for WordPress. Supports Slavic, Arabic, Greek, and Central Asian scripts.
 * Version:           2.4.1
 * Requires at least: 5.4
 * Tested up to:      6.9
 * Requires PHP:      7.4
 * Author:            Ivijan-Stefan Stipić
 * Author URI:        https://profiles.wordpress.org/ivijanstefan/
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       serbian-transliteration
 * Domain Path:       /languages
 * Network:           true
 *
 * @wordpress-plugin
 *
 * This plugin is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * (at your option) any later version.
 *
 * This plugin is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this plugin. If not, see <https://www.gnu.org/licenses/>.
 */


// If someone try to called this file directly via URL, abort.
if (! defined('WPINC')) {
    die("Don't mess with us.");
}

if (! defined('ABSPATH')) {
    exit;
}

// Database version
if (! defined('RSTR_DATABASE_VERSION')) {
    define('RSTR_DATABASE_VERSION', '1.0.1');
}


/*************************************************************************************************************************
 * Hey, champ!
 *
 * Yeah, you - hovering over this comment like it owes you money.
 * If you're reading this, you're either a coding genius or took a wrong turn on your way to cat memes.
 * Either way, welcome to the magical world of programming - where we turn chaos into... slightly more structured chaos.
 *
 * But wait—plot twist! We’re on a noble mission. A glorious crusade to make the web smoother, faster, and so clean
 * it could make Elon’s rockets blush. And guess what? We need YOU. Yes, you with the keyboard that sounds like
 * a tap-dancing velociraptor at 3AM.
 *
 * Imagine this: you, code sorcerer, wielding your IDE like a lightsaber,
 * slicing bugs faster than grandma demolishes cheesecake.
 * This isn’t just coding. It’s destiny. And it’s buzzing in your notifications.
 *
 * Ready to write scripts so elegant they could double as pickup lines?
 * Help us build the future (and survive the occasional existential crisis) here:
 * 👉 https://github.com/InfinitumForm/serbian-transliteration
 *
 * Together, we’ll squash bugs, silence warnings, and shame runtime errors into submission.
 * Think of us as the Justice League - but with more Git commits and less brooding in caves.
 *
 * So, what’s it gonna be? Keep sipping that coffee while questioning life,
 * or rise up and become the dev legends people whisper about on Slack?
 * Your move, superstar. Snacks not included. Probably.
 *************************************************************************************************************************/


/**
 * Main plugin constants
 * @since     1.1.0
 * @version   1.0.0
 */
// Main plugin file
if (! defined('RSTR_FILE')) {
    define('RSTR_FILE', __FILE__);
}

if (is_admin()) {
    // Needed for is_plugin_active_for_network
    include_once ABSPATH . 'wp-admin/includes/plugin.php';
}

/**
 * Check if current blog_id is allow-listed when plugin is network-activated.
 */
if(!function_exists('rstr_is_allowed_site')) : function rstr_is_allowed_site($blog_id) {
    $allowed = (array) get_site_option('rstr_allowed_sites', array());
    $allowed = array_map('intval', $allowed);
    return in_array((int) $blog_id, $allowed, true);
} endif;

/**
 * Decide whether to boot heavy plugin logic on this site.
 * Rules:
 * - Single-site WP: always boot.
 * - Multisite, plugin not network active: boot (site-level activation).
 * - Multisite, plugin network active: boot only if current site is in allow-list.
 */
if(!function_exists('rstr_should_boot')) : function rstr_should_boot() {
    if (!is_multisite()) {
        return true;
    }

    $basename = plugin_basename(__FILE__);
    $is_network_active = function_exists('is_plugin_active_for_network') && is_plugin_active_for_network($basename);

    if (!$is_network_active) {
        // Site-only activation path
        return true;
    }

    // Network active path -> allow-list check
    return rstr_is_allowed_site(get_current_blog_id());
} endif;

/**
 * Ensure network allow-list option exists on network activation.
 */
register_activation_hook(__FILE__, function ($network_wide) {
    if (is_multisite() && $network_wide) {
        if (false === get_site_option('rstr_allowed_sites', false)) {
            add_site_option('rstr_allowed_sites', array());
        }
    }
});

/**
 * Network settings menu and screen for selecting allowed sites.
 * Text Domain: serbian-transliteration
 */

// Register Network Admin submenu
if (!function_exists('rstr_register_network_settings_menu')) :
/**
 * Register network submenu under Settings.
 */
function rstr_register_network_settings_menu() {
    if (!is_multisite()) {
        return;
    }

    add_submenu_page(
        'settings.php',
        __('Transliterator', 'serbian-transliteration'),
        __('Transliterator', 'serbian-transliteration'),
        'manage_network_options',
        'rstr-network',
        'rstr_render_network_settings'
    );
}
add_action('network_admin_menu', 'rstr_register_network_settings_menu');
endif;

if (!function_exists('rstr_render_network_settings')) :
/**
 * Render the network settings screen with allow-list of sites.
 */
function rstr_render_network_settings() {
    if (!current_user_can('manage_network_options')) {
        wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'serbian-transliteration'));
    }

    // Handle POST
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- We verify nonce explicitly below.
        if (isset($_POST['rstr_network_nonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['rstr_network_nonce'])), 'rstr_network_save')) {
            // phpcs:ignore WordPress.Security.NonceVerification.Missing
            $raw_sites = isset($_POST['rstr_sites']) ? (array) wp_unslash($_POST['rstr_sites']) : array();
            $site_ids  = array_map('absint', $raw_sites);

            update_site_option('rstr_allowed_sites', $site_ids);

            add_settings_error(
                'rstr_messages',
                'rstr_saved',
                esc_html__('Settings saved.', 'serbian-transliteration'),
                'updated'
            );
        } else {
            add_settings_error(
                'rstr_messages',
                'rstr_nonce',
                esc_html__('Security check failed. Please try again.', 'serbian-transliteration'),
                'error'
            );
        }
    }

    // Read current value
    $enabled_sites = array_map('intval', (array) get_site_option('rstr_allowed_sites', array()));

    // Fetch sites with pagination for large networks
    $sites  = array();
    $paged  = 1;
    $number = 100;

    do {
        $batch = get_sites(array(
            'number' => $number,
            'paged'  => $paged,
            'fields' => 'all',
        ));
        if (empty($batch)) {
            break;
        }
        $sites = array_merge($sites, $batch);
        $paged++;
    } while (count($batch) === $number);

    ?>
    <div class="wrap">
        <h1><?php echo esc_html__('Transliterator - Network Settings', 'serbian-transliteration'); ?></h1>

        <?php settings_errors('rstr_messages'); ?>

        <form method="post" action="<?php echo esc_url(network_admin_url('settings.php?page=rstr-network')); ?>">
            <?php wp_nonce_field('rstr_network_save', 'rstr_network_nonce'); ?>

            <p><?php echo esc_html__('Select sites where the plugin should run when it is network-activated.', 'serbian-transliteration'); ?></p>

            <table class="widefat fixed striped">
                <thead>
                    <tr>
                        <th style="width:60px;"><?php echo esc_html__('Enable', 'serbian-transliteration'); ?></th>
                        <th><?php echo esc_html__('Site', 'serbian-transliteration'); ?></th>
                        <th><?php echo esc_html__('Domain', 'serbian-transliteration'); ?></th>
                        <th><?php echo esc_html__('Path', 'serbian-transliteration'); ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php
                if (!empty($sites)) :
                    foreach ($sites as $site) :
                        $blog_id = isset($site->blog_id) ? (int) $site->blog_id : 0;
                        if ($blog_id <= 0) {
                            continue;
                        }
                        $checked = in_array($blog_id, $enabled_sites, true);

                        // get_blog_details can return false on rare cases; guard it.
                        $details = get_blog_details($blog_id);
                        $blogname = $details && !empty($details->blogname) ? $details->blogname : sprintf(__('Site #%d', 'serbian-transliteration'), $blog_id);
                        $domain   = $details && !empty($details->domain) ? $details->domain : '';
                        $path     = $details && !empty($details->path) ? $details->path : '';
                        ?>
                        <tr>
                            <td>
                                <label class="screen-reader-text" for="rstr_site_<?php echo esc_attr($blog_id); ?>">
                                    <?php echo esc_html(sprintf(__('Enable Transliterator on site ID %d', 'serbian-transliteration'), $blog_id)); ?>
                                </label>
                                <input
                                    id="rstr_site_<?php echo esc_attr($blog_id); ?>"
                                    type="checkbox"
                                    name="rstr_sites[]"
                                    value="<?php echo esc_attr($blog_id); ?>"
                                    <?php checked($checked); ?>
                                />
                            </td>
                            <td><?php echo esc_html($blogname) . ' ' . esc_html(sprintf('(ID: %d)', $blog_id)); ?></td>
                            <td><?php echo esc_html($domain); ?></td>
                            <td><?php echo esc_html($path); ?></td>
                        </tr>
                        <?php
                    endforeach;
                else :
                    ?>
                    <tr>
                        <td colspan="4"><?php echo esc_html__('No sites found.', 'serbian-transliteration'); ?></td>
                    </tr>
                    <?php
                endif;
                ?>
                </tbody>
            </table>

            <p>
                <input type="submit" class="button button-primary" value="<?php echo esc_attr__('Save changes', 'serbian-transliteration'); ?>" />
            </p>
        </form>
    </div>
    <?php
}
endif;

// Required constants
if (!defined('COOKIEHASH') || !defined('COOKIEPATH') || !defined('COOKIE_DOMAIN')) {
    if (!function_exists('wp_cookie_constants')) {
        include_once ABSPATH . WPINC . '/default-constants.php';
    }

    if (function_exists('wp_cookie_constants')) {
        wp_cookie_constants();
    }
}

// Set of constants
include_once __DIR__ . '/constants.php';

// Set database tables
global $wpdb, $rstr_is_admin;
$wpdb->rstr_cache = $wpdb->get_blog_prefix() . 'rstr_cache';

// Check is in admin mode
$rstr_is_admin = ($_COOKIE['rstr_test_' . COOKIEHASH] ?? 'false' === 'true');

/*
 * Get plugin options
 * @since     1.1.3
 * @version   1.0.0
 */
if (!function_exists('get_rstr_option')) {
    function get_rstr_option($name = false, $default = null)
    {
        static $get_rstr_options = null;

        if ($get_rstr_options === null && !$get_rstr_options) {
            $get_rstr_options = get_option('serbian-transliteration');
        }

        if ($name === false) {
            return $get_rstr_options ?: $default;
        }

        return $get_rstr_options[$name] ?? $default;
    }
}

// Initialize the autoloader
include_once RSTR_CLASSES . '/autoloader.php';
Transliterator_Autoloader::init();


/*************************************************************************************************************************
 * Oh, you’re still here? Respect.
 * That means you’ve made it this far, brave coder. Impressive.
 * Most devs would’ve bailed by now—either lost in a TikTok spiral or curled up in a ball after seeing `Undefined index`.
 * But not you. You’re different. You’re... *committed*.
 *
 * And since you’ve stuck around, let’s talk about what’s next.
 * By now, you’ve probably crushed a few bugs, high-fived yourself, and maybe even invented a new programming dialect made of curse words.
 * But the real challenge lies ahead: writing code so clean, Marie Kondo would weep with joy.
 *
 * This is where champions are forged. Where functions sing, variables moonwalk, and loops actually loop… responsibly.
 * So keep going. Keep coding. Every semicolon is a tiny victory. Unless you’re in Python. Then… condolences.
 *
 * Stuck? No worries. Even Tony Stark needed a few failed prototypes before nailing the flight test.
 * Breathe. Google like the rest of us mere mortals. And keep pushing forward.
 *
 * And when you finally ship this masterpiece and users marvel at how everything “just works,”
 * remember: that was YOU. Okay, maybe Stack Overflow helped a little. We won’t tell.
 *
 * If you're ready to level up and write code that makes keyboards weep with joy,
 * join us here: 👉 https://github.com/InfinitumForm/serbian-transliteration
 *
 * Together, we’ll turn bugs into dust, errors into folklore, and code into quiet art.
 * The internet deserves your genius. And yes—there might be snacks. Probably. 😉
 *************************************************************************************************************************/


// Transliteration requirements
if(!class_exists('Transliteration_Requirements')) {
	include_once RSTR_CLASSES . '/requirements.php';
}
$transliteration_requirements = new Transliteration_Requirements(['file' => RSTR_FILE]);

// Plugin is ready for the run
if ($transliteration_requirements->passes()) :
    // Ensure the main model class is loaded first
    require_once RSTR_CLASSES . '/model.php';

    // Ensure the WP_CLI class is loaaded second
    require_once RSTR_CLASSES . '/wp-cli.php';

    // On the plugin activation
    register_activation_hook(RSTR_FILE, ['Transliteration_Init', 'register_activation']);

    // On the deactivation
    register_deactivation_hook(RSTR_FILE, ['Transliteration_Init', 'register_deactivation']);

    // On the plugin update
    add_action('upgrader_process_complete', ['Transliteration_Init', 'register_updater'], 10, 2);

    // On the manual plugin update
    add_action('admin_init', ['Transliteration_Init', 'check_plugin_update']);

    // Redirect after activation
    add_action('admin_init', ['Transliteration_Init', 'register_redirection'], 10, 2);
	
	// Support for the Polylang
	add_action('pll_language_defined', ['Transliteration_Utilities','on_pll_language_defined'], 1);
	add_action('wp', ['Transliteration_Utilities','late_language_resolve'], 1);

    // Run the plugin
    if (rstr_should_boot()) {
		Transliteration::run_the_plugin();
	}

    // Plugin Functions
    include_once __DIR__ . '/functions.php';
endif;

// Clear memory
unset($transliteration_requirements);


/*************************************************************************************************************************
 * So here we are. The end of the code. The final frontier. The last semicolon standing.
 * If you’ve made it all the way here, you’re officially a legend. A champion of syntax.
 * The kind of coder they write songs about—or at least a few highly specific memes.
 *
 * But let’s not stop here. Oh no. The world still needs you.
 * Somewhere, a lonely script is sobbing. A bug is chewing through logic.
 * And a confused user just submitted a form 47 times and called it "lag."
 *
 * So, one last time: join us. Become part of something slightly bigger than your coffee mug.
 * Be the dev who makes the internet faster, cleaner, and just a bit less cursed.
 *
 * https://github.com/InfinitumForm/serbian-transliteration
 *
 * Fork it.  
 * Star it.  
 * Clone it.  
 * Pull it.  
 * Push it.  
 * Merge it.  
 * Fix it.  
 * Ship it.  
 *
 * (Harder. Better. Safer. Smoother.)
 *
 * And remember: the only thing standing between chaos and order... is YOU.
 * Now go. Code boldly, deploy confidently—and maybe, just maybe,
 * reward yourself with those cookies I promised. 🍪 You’ve earned them.
 *************************************************************************************************************************/
 