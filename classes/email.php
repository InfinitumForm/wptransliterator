<?php

if (!defined('WPINC')) {
	die();
}

class Transliteration_Email extends Transliteration
{
	public function __construct()
	{
		$this->add_action('phpmailer_init', 'transliterate_phpmailer', PHP_INT_MAX - 99, 1);
	}

	public function transliterate_phpmailer($phpmailer): void
	{
		if (get_rstr_option('force-email-transliteration', 'no') !== 'yes') {
			return;
		}

		if (!empty($phpmailer->Subject) && is_string($phpmailer->Subject)) {
			$phpmailer->Subject = $this->transliterate_email_part($phpmailer->Subject, false);
		}

		if (!empty($phpmailer->Body) && is_string($phpmailer->Body)) {
			$phpmailer->Body = $this->transliterate_email_part($phpmailer->Body, true);
		}

		if (!empty($phpmailer->AltBody) && is_string($phpmailer->AltBody)) {
			$phpmailer->AltBody = $this->transliterate_email_part($phpmailer->AltBody, false);
		}
	}

	private function transliterate_email_part(string $content, bool $html = false): string
	{
		if ($content === '') {
			return $content;
		}

		$tokens = [];

		$content = $this->protect_email_tokens($content, $tokens);

		if ($html) {
			$content = Transliteration_Controller::get()->transliterate($content);
		} else {
			$content = Transliteration_Controller::get()->transliterate_no_html($content);
		}

		if ($tokens !== []) {
			$content = strtr($content, $tokens);
		}

		return $content;
	}

	private function protect_email_tokens(string $content, array &$tokens): string
	{
		$patterns = [
			'/https?:\/\/[^\s<>"\']+/i',
			'/[a-z0-9._%+\-]+@[a-z0-9.\-]+\.[a-z]{2,}/i',
			'/\b(wordfence|woocommerce|wordpress|wpforms|elementor|yoast|rank math|aioseo|backup|security|firewall|activity|alert|login|password|update|plugin|theme|admin|administrator|user|username|email|server|domain|host|hosting|database|mysql|php|cron|debug|log|error|warning|notice)\b/i',
		];

		foreach ($patterns as $pattern) {
			$replaced = preg_replace_callback($pattern, static function (array $matches) use (&$tokens): string {
				$placeholder = '%%RSTR_EMAIL_' . count($tokens) . '%%';

				$tokens[$placeholder] = $matches[0];

				return $placeholder;
			}, $content);

			if (is_string($replaced)) {
				$content = $replaced;
			}
		}

		return $content;
	}
}