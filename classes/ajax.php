<?php

if (!defined('WPINC')) {
    die();
}

class Transliteration_Ajax extends Transliteration
{
    public function __construct()
    {
        $this->add_action('wp_loaded', 'ajax_transliteration_start', PHP_INT_MAX - 99);
    }

    public function ajax_transliteration_start(): void
    {
        $this->ob_start('ajax_transliteration_callback');
        $this->add_action('shutdown', 'ajax_transliteration_end', PHP_INT_MAX - 99);
    }

    public function ajax_transliteration_callback($buffer)
    {
        if (Transliteration_Utilities::is_editor()) {
            return $buffer;
        }

        if (!isset($_REQUEST['action']) || (!in_array($_REQUEST['action'], ['find_posts', 'heartbeat', 'query-attachments', 'wp_block']) && !preg_match('/^((ct_|oxy_)(.*?))$/i', $_REQUEST['action']) && !preg_match('/^(rstr_(.*?))$/i', $_REQUEST['action']) && !preg_match('/^(divi_(.*?))$/i', $_REQUEST['action']) && !preg_match('/^(elementor_(.*?))$/i', $_REQUEST['action']))) {
            $json = json_decode($buffer, true);
            if ($json !== null && is_array($json)) {
                $buffer = json_encode(Transliteration_Mode::get()->transliterate_objects($json));
            } else {
                $buffer = Transliteration_Controller::get()->transliterate($buffer);
            }
        }

        return $buffer;
    }

    public function ajax_transliteration_end(): void
    {
        if (ob_get_level() > 0) {
            ob_end_flush();
        }
    }
}
