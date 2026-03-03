<?php

if (!defined('WPINC')) {
    die();
}

class Transliteration
{
    // Current instance
    /**
     * @var $this
     */
    public static $self;

    /**
     * Constructor
     *
     * @param mixed $data Object or array of data to initialize properties.
     */
    public function __construct($data = null)
    {
        self::$self = $this;

        if ($data === null) {
            return;
        }

        if (is_object($data)) {
            $this->initialize_properties($data);
        } elseif (is_array($data)) {
            $this->initialize_properties((object) $data);
        }
    }

    /**
     * Initialize properties from given data
     *
     * @type private
     *
     * @param object $data Data to initialize properties.
     */
    private function initialize_properties($data): void
    {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $this->sanitize($value);
            }
        }
    }

    /**
     * Run the plugin
     */
    public static function run_the_plugin(): void
    {
	    // PHP8.4 autoloader fix
		if (!class_exists('Transliteration_Init', false)) {
			require_once plugin_dir_path(__FILE__) . 'init.php';
		}
		
		// Run the plugin
        new Transliteration_Init();
    }

    /**
     * Magic method to handle dynamic calls to properties
     *
     * @param string $function  Method name.
     * @param array  $arguments Method arguments.
     *
     * @return mixed
     * @throws Exception if the method does not exist.
     */
    public function __call(string $function, array $arguments)
    {
        if (property_exists($this, $function)) {
            return $this->$function;
        }

        // Handle dynamic setters
        if (substr($function ?? '', 0, 3) === 'set') {
            $property = lcfirst(substr($function ?? '', 3));
            if (property_exists($this, $property)) {
                $this->$property = $this->sanitize($arguments[0]);
                return $this;
            }
        }

        throw new Exception('No such method: ' . get_class($this) . '->' . $function . '()');
    }

    /**
     * Get method for properties
     *
     * @param string $property Property name.
     *
     * @return mixed
     * @throws Exception if the property does not exist.
     */
    public function get_property(string $property)
    {
        if (property_exists($this, $property)) {
            return $this->$property;
        }

        throw new Exception('No such property: ' . get_class($this) . '->' . $property);
    }

    /**
     * Set method for properties
     *
     * @param string $property Property name.
     * @param mixed  $value    Property value.
     *
     * @return $this
     * @throws Exception if the property does not exist.
     */
    public function set_property(string $property, $value): self
    {
        if (property_exists($this, $property)) {
            $this->$property = $this->sanitize($value);
            return $this;
        }

        throw new Exception('No such property: ' . get_class($this) . '->' . $property);
    }

    /*
     * Helper for add_action()
     * @author        Ivijan-Stefan Stipic
     */
    public function ob_start($callback = null, int $chunk_size = 0, int $flags = PHP_OUTPUT_HANDLER_REMOVABLE): void
    {
        ob_start($this->ensure_callable($callback), $chunk_size, $flags);
    }

    /**
     * Helper for add_action()
     * @author        Ivijan-Stefan Stipic
     */
    public function add_action(string $tag, $function_to_add, int $priority = 10, int $accepted_args = 1)
    {
        return add_action($tag, $this->ensure_callable($function_to_add), $priority, $accepted_args);
    }

    /**
     * Helper for remove_action()
     * @author        Ivijan-Stefan Stipic
     */
    public function remove_action(string $tag, $function_to_remove, int $priority = 10)
    {
        return remove_action($tag, $this->ensure_callable($function_to_remove), $priority);
    }

    /**
     * Helper for add_filter()
     * @author        Ivijan-Stefan Stipic
     */
    public function add_filter(string $tag, $function_to_add, int $priority = 10, int $accepted_args = 1)
    {
        return add_filter($tag, $this->ensure_callable($function_to_add), $priority, $accepted_args);
    }

    /**
     * Helper for remove_filter()
     * @author        Ivijan-Stefan Stipic
     */
    public function remove_filter(string $tag, $function_to_remove, int $priority = 10)
    {
        return remove_filter($tag, $this->ensure_callable($function_to_remove), $priority);
    }

    /**
     * Helper for add_shortcode()
     * @author        Ivijan-Stefan Stipic
     */
    public function add_shortcode(string $tag, $function_to_add, bool $overwrite = false)
    {
        if (shortcode_exists($tag) && !$overwrite) {
            return false;
        }

        return add_shortcode($tag, $this->ensure_callable($function_to_add));
    }

    /**
     * Sanitize values
     *
     * @type protected
     *
     * @param mixed $object Any object type that need to be sanitized.
     *
     * @return mixed
     */
    public function sanitize($object)
    {
        if (is_array($object)) {
            return array_map([&$this, 'tp_sanitize'], $object);
        }

        if (is_numeric($object)) {
            if ($object == ($intval = intval($object))) {
                return $intval;
            }
            if ($object == ($floatval = floatval($object))) {
                return $floatval;
            }
        } elseif (is_string($object)) {
            if (filter_var($object, FILTER_VALIDATE_EMAIL)) {
                return sanitize_email($object);
            }
            if (filter_var($object, FILTER_VALIDATE_URL)) {
                return esc_url($object);
            }
            if ($object !== strip_tags($object)) {
                return wp_kses_post(sanitize_textarea_field($object));
            } else {
                return sanitize_text_field($object);
            }
        }

        return apply_filters('transliterator_sanitize', $object, $this);
    }

    /**
     * Ensure callable is in array format for class methods
     * @param           $function
     * @return callable
     */
    private function ensure_callable($function): array
    {
        return is_array($function) ? $function : [&$this, $function];
    }
}
