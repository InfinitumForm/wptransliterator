<?php if (!defined('WPINC')) {
    die();
}
/**
 * Notices
 *
 * @link              http://infinitumform.com/
 * @since             1.4.4
 * @package           Serbian_Transliteration
 * @author            Ivijan-Stefan Stipic
 */

class Transliteration_Notifications extends Transliteration
{
    public function __construct()
    {
        if (!is_admin()) {
            return;
        }

        $this->add_action('admin_init', 'check_installation_time');
        $this->add_action('admin_init', 'rstr_dimiss_review', 5);
        $this->add_action('admin_init', 'rstr_dimiss_donation', 5);
        $this->add_action('admin_init', 'rstr_dimiss_ads', 5);
    }

    // remove the notice for the user if review already done or if the user does not want to
    public function rstr_dimiss_review(): void
    {
        if ($this->can_dismiss_notice('rstr_dimiss_review')) {
            add_option('serbian-transliteration-reviewed', time());

            $parse_url = Transliteration_Utilities::parse_url();
            if (!headers_sent() && wp_safe_redirect(remove_query_arg(['rstr_dimiss_review', 'rstr_dismiss_nonce'], $parse_url['url']))) {
                exit;
            }
        }
    }

    // remove the notice for the user if donation already done or if the user does not want to
    public function rstr_dimiss_donation(): void
    {
        if ($this->can_dismiss_notice('rstr_dimiss_donation')) {
            add_option('serbian-transliteration-donated', time());

            $parse_url = Transliteration_Utilities::parse_url();
            if (!headers_sent() && wp_safe_redirect(remove_query_arg(['rstr_dimiss_donation', 'rstr_dismiss_nonce'], $parse_url['url']), 302)) {
                exit;
            }
        }
    }

    // remove ads notice
    public function rstr_dimiss_ads(): void
    {
        if ($this->can_dismiss_notice('rstr_dimiss_adds')) {
            set_transient('serbian-transliteration-ads', time(), MONTH_IN_SECONDS);

            $parse_url = Transliteration_Utilities::parse_url();
            if (!headers_sent() && wp_safe_redirect(remove_query_arg(['rstr_dimiss_adds', 'rstr_dismiss_nonce'], $parse_url['url']), 302)) {
                exit;
            }
        }
    }

    private function can_dismiss_notice(string $action): bool
    {
        if (!current_user_can('manage_options') || !isset($_GET[$action], $_GET['rstr_dismiss_nonce'])) {
            return false;
        }

        if (!is_scalar($_GET[$action]) || !is_scalar($_GET['rstr_dismiss_nonce'])) {
            return false;
        }

        $value = absint(wp_unslash((string) $_GET[$action]));
        $nonce = sanitize_text_field(wp_unslash((string) $_GET['rstr_dismiss_nonce']));

        return $value === 1 && $nonce !== '' && wp_verify_nonce($nonce, 'rstr-dismiss-' . $action) !== false;
    }

    private function dismissal_url(string $action, string $url): string
    {
        return esc_url(wp_nonce_url(add_query_arg($action, '1', $url), 'rstr-dismiss-' . $action, 'rstr_dismiss_nonce'));
    }

    // check if review notice should be shown or not
    public function check_installation_time(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        $this->display_vote();
        $this->display_donation();
        //	$this->display_ads();
    }

    // dimiss vote notices
    public function display_vote(): void
    {
        if (get_option('serbian-transliteration-reviewed')) {
            return;
        }

        $get_dates = get_option('serbian-transliteration-activation');

        $install_date = is_array($get_dates) ? strtotime(reset($get_dates)) : strtotime($get_dates);

        $past_date = strtotime('-1 week');

        if ($past_date >= $install_date) {
            $this->add_action('admin_notices', 'notice__give_us_vote', 1);
        }
    }

    // dimiss vote notices
    public function display_donation(): void
    {
        if (!get_option('serbian-transliteration-reviewed')) {
            return;
        }

        if (get_option('serbian-transliteration-donated')) {
            return;
        }

        $get_dates = get_option('serbian-transliteration-activation');

        $install_date = is_array($get_dates) ? strtotime(reset($get_dates)) : strtotime($get_dates);

        $past_date = strtotime('-3 weeks');

        if ($past_date >= $install_date) {
           // $this->add_action('admin_notices', 'notice__buy_me_a_coffee', 1);
		   $this->add_action('admin_notices', 'notice__donation_bank_account', 1);
        }
    }

    // dimiss vote notices
    public function display_ads(): void
    {

        if (strpos(home_url('/'), 'freelanceposlovi.com') !== false) {
            return;
        }

        if (get_transient('serbian-transliteration-ads')) {
            return;
        }

        $get_dates = get_option('serbian-transliteration-activation');

        $install_date = is_array($get_dates) ? strtotime(reset($get_dates)) : strtotime($get_dates);

        $past_date = strtotime('-4 weeks');

        if ($past_date >= $install_date) {
            $this->add_action('admin_notices', 'notice__ads', 1);
        }
    }

    /**
     * Display Admin Notice, asking for a review
     **/
    public function notice__give_us_vote(): void
    {
        $parse_url    = Transliteration_Utilities::parse_url();
        $dont_disturb = $this->dismissal_url('rstr_dimiss_review', $parse_url['url']);
        $plugin_info  = get_plugin_data(RSTR_FILE, true, true);
		$reviewurl    = 'https://wordpress.org/support/plugin/serbian-transliteration/reviews/#new-post';

		printf(
			'<div class="notice notice-info"><h3>%1$s</h3><p>%2$s</p><p class="void-review-btn"><a href="%3$s" class="button button-primary" target="_blank">%4$s</a><a href="%5$s" class="void-grid-review-done" style="margin-left: 10px;">%6$s</a></p></div>',
			wp_kses_post(sprintf(
				/* translators: %1$s: Plugin name. */
				__('You have been using <b> %1$s </b> plugin for a while. We hope you liked it!', 'serbian-transliteration'),
				esc_html($plugin_info['Name'])
			)),
			esc_html__('Please give us a quick rating, it works as a boost for us to keep working on the plugin!', 'serbian-transliteration'),
			esc_url($reviewurl),
			esc_html__('Rate Now!', 'serbian-transliteration'),
			esc_url($dont_disturb),
			esc_html__("I've already done that!", 'serbian-transliteration')
		);
    }

    /**
     * Display Admin Notice, asking for a review
     **/
    public function notice__buy_me_a_coffee(): void
    {
        $parse_url    = Transliteration_Utilities::parse_url();
        $dont_disturb = $this->dismissal_url('rstr_dimiss_donation', $parse_url['url']);
        $plugin_info  = get_plugin_data(RSTR_FILE, true, true);
        $donationurl  = 'https://www.buymeacoffee.com/ivijanstefan';

		printf(
			'<div class="notice notice-info">
			<h3>%1$s</h3>
			
			<p>%2$s</p>
			<p>%3$s</p>
		</div>',
			wp_kses_post(sprintf(
				/* translators: %1$s: Plugin name. */
				__('Hey there! It\'s been a while since you\'ve been using the <b> %1$s </b> plugin', 'serbian-transliteration'),
				esc_html($plugin_info['Name'])
			)),
			wp_kses_post(sprintf(
				/* translators: %s: Link to the donation page. */
				__('I\'m glad to hear you\'re enjoying the plugin. I\'ve put a lot of time and effort into ensuring that your website runs smoothly. If you\'re feeling generous, how about %s for my hard work? 😊', 'serbian-transliteration'),
				'<big><strong><a href="' . esc_url($donationurl) . '" target="_blank">' . esc_html__('treating me to a coffee', 'serbian-transliteration') . '</a></strong></big>'
			)),
			wp_kses_post(sprintf(
				/* translators: %s: Link that permanently dismisses the notice. */
				__('Or simply %s forever.', 'serbian-transliteration'),
				'<a href="' . esc_url($dont_disturb) . '">' . esc_html__('hide this message', 'serbian-transliteration') . '</a>'
			))
		);
    }
	
	public function notice__donation_bank_account(): void
	{
		$parse_url    = Transliteration_Utilities::parse_url();
		$dont_disturb = $this->dismissal_url('rstr_dimiss_donation', $parse_url['url']);
		$plugin_info  = get_plugin_data(RSTR_FILE, true, true);

		printf(
			'<div class="notice notice-info">
			<h3>%1$s</h3>

			<p>%2$s</p>

			<p><strong>%3$s</strong><br>
			160-6000002167503-32<br>
			SWIFT: DBDBRSBG</p>

			<p>%4$s</p>

			<p>%5$s</p>
		</div>',
			wp_kses_post(sprintf(
				/* translators: %1$s: Plugin name. */
				__('Hey there! It\'s been a while since you\'ve been using the <b>%1$s</b> plugin', 'serbian-transliteration'),
				esc_html($plugin_info['Name'])
			)),
			esc_html__('I\'m really happy to see this plugin being useful for your website. If you ever feel like giving back, here\'s one way to do it.', 'serbian-transliteration'),
			esc_html__('Bank account (Banca Intesa a.d. Beograd):', 'serbian-transliteration'),
			esc_html__('Every little bit helps, and I truly appreciate it! ❤️', 'serbian-transliteration'),
			wp_kses_post(sprintf(
				/* translators: %s: Link that permanently dismisses the notice. */
				__('Or simply %s forever.', 'serbian-transliteration'),
				'<a href="' . esc_url($dont_disturb) . '">' . esc_html__('hide this message', 'serbian-transliteration') . '</a>'
			))
		);
	}

    /**
     * Display Admin Notice, asking for a review
     **/
    public function notice__ads(): void
    {
        $parse_url    = Transliteration_Utilities::parse_url();
        $dont_disturb = $this->dismissal_url('rstr_dimiss_adds', $parse_url['url']);

		printf(
			'<div class="notice notice-info is-dismissible" id="ads-freelance-poslovi"><img src="%1$s" alt="FreelancePoslovi.com"><h3>%2$s</h3><p>%3$s</p><p>%4$s</p><a href="%5$s" class="notice-dismiss" style="text-decoration:none;"></a></div>',
			esc_url(RSTR_ASSETS . '/img/fp-icon-80x80.png'),
			wp_kses_post(sprintf(
				/* translators: %1$s: Link to a freelance marketplace. */
				__('Find Work or %1$s in Serbia, Bosnia, Croatia, and Beyond!', 'serbian-transliteration'),
				'<a href="https://freelanceposlovi.com/" target="_blank" title="Freelance Poslovi">' . esc_html__('Hire Top Freelancers', 'serbian-transliteration') . '</a>'
			)),
			wp_kses_post(sprintf(
				/* translators: %1$s: Link to a freelance marketplace. */
				__('Visit %1$s to connect with skilled professionals across the region. Whether you need a project completed or are looking for work, our platform is your gateway to successful collaboration.', 'serbian-transliteration'),
				'<a href="https://freelanceposlovi.com/" target="_blank" title="Freelance Poslovi"><b>' . esc_html__('Freelance Jobs', 'serbian-transliteration') . '</b></a>'
			)),
			'<a href="https://freelanceposlovi.com/" target="_blank" class="button button-primary"><b>' . esc_html__('Join us today!', 'serbian-transliteration') . '</b></a>',
			esc_url($dont_disturb)
		);

        add_action('admin_footer', function (): void { ?>
<style>/* <![CDATA[ */#ads-freelance-poslovi{border-left-color:#07bab9}#ads-freelance-poslovi>img{width:80px;height:80px;float:left;margin:24px 10px 24px 0}#ads-freelance-poslovi a{color:#07bab9;text-decoration:none}#ads-freelance-poslovi a:hover{color:#203b4e}#ads-freelance-poslovi .button-primary{background-color:#07bab9;border-color:#07bab9;color:#fff}#ads-freelance-poslovi .button-primary:hover{background-color:#203b4e;border-color:#203b4e;color:#fff}@media all and (max-width:1440px){#ads-freelance-poslovi>img{margin:30px 10px 30px 0}}@media all and (max-width:768px){#ads-freelance-poslovi>img{width:64px;height:64px;margin:15px 0 8px}}/* ]]> */</style>
		<?php });
    }
}
