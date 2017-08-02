<?php
namespace MaDnh\LaravelDevHelper\RequestProfile;

/**
 * Class Profile
 * Store data with 3 zones: override data, request data, default data
 */
class Profile
{
    /**
     * @var array
     */
    public $override = [];

    /**
     * @var array
     */
    public $default = [];

    /**
     * Use request() or not?
     * @var bool
     */
    public $use_request = true;

    /**
     * Profile constructor.
     * @param array $data
     * @param bool $is_default
     */
    public function __construct($data = [], $is_default = false)
    {
        if (!empty($data)) {
            if ($is_default) {
                $this->defaultData($data);
            } else {
                $this->overrideData($data);
            }
        }
    }

    public function __set($name, $value)
    {
        $this->overrideData([
            $name => $value
        ]);
    }

    /**
     * @param bool $use
     * @return $this
     */
    public function useRequest($use = true)
    {
        $this->use_request = $use;
        return $this;
    }

    /**
     * Check if profile has info field
     * @param string $name
     * @param bool $include_request
     * @return bool
     */
    public function has($name, $include_request = false)
    {
        return property_exists($this, $name)
            || array_key_exists($name, $this->override)
            || array_key_exists($name, $this->default)
            || ($this->use_request && $include_request && (array_key_exists($name, $_GET) || array_key_exists($name, $_POST) || request()->has($name)));
    }

    /**
     * @param string $name
     * @param null|mixed $default
     * @return mixed
     */
    public function get($name, $default = null)
    {
        if (property_exists($this, $name)) {
            return $this->$name;
        }
        if (array_has($this->override, $name)) {
            return array_get($this->override, $name, $default);
        }
        if ($this->use_request) {
            $request = request();
            if ($request->has($name)) {
                return $request->input($name, $default);
            }
        }
        if (array_has($this->default, $name)) {
            return array_get($this->default, $name, $default);
        }

        return $default;
    }

    /**
     * @param string $name
     * @param mixed $default
     * @return mixed|null
     */
    public function getOverride($name, $default = null)
    {
        if (array_has($this->override, $name)) {
            return array_get($this->override, $name, $default);
        }

        return $default;
    }

    /**
     * @param string $name
     * @param mixed $default
     * @return mixed|null
     */
    public function getDefault($name, $default = null)
    {
        if (array_has($this->default, $name)) {
            return array_get($this->default, $name, $default);
        }

        return $default;
    }

    /**
     * @param string $name
     * @return bool
     */
    public function isEmpty($name)
    {
        $value = $this->get($name);

        return empty($value);
    }

    /**
     * @param string $name
     * @return bool
     */
    public function isNotEmpty($name)
    {
        return !$this->isEmpty($name);
    }

    /**
     * Get data and cast to boolean
     * @param string $name
     * @param bool [$accept = true] If value is equal to this special value, it will return true
     * @return bool
     */
    public function is($name, $accept = true)
    {
        $value = $this->get($name);

        if ($value === $accept) {
            return true;
        }
        if (is_null($value)) {
            return false;
        }
        if (is_string($value)) {
            if (in_array(strtolower(trim($value)), ['yes', '1', 'true', 'on'])) {
                return true;
            } else if (in_array(strtolower(trim($value)), ['no', '0', 'false', 'off'])) {
                return false;
            }
        }
        if (in_array($value, [1, true])) {
            return true;
        }
        if (in_array($value, [0, false])) {
            return false;
        }

        return (bool)$value;
    }

    /**
     * Set override data
     * @param string|array $name Name or array of data
     * @param mixed $value
     * @return $this
     */
    public function set($name, $value = null)
    {
        if (is_string($name)) {
            $name = [$name => $value];
        }

        return $this->overrideData($name, false);
    }

    /**
     * @param array $data
     * @param bool $reset Reset data first
     * @return $this
     */
    public function overrideData($data, $reset = false)
    {
        if ($reset || empty($this->override)) {
            $this->override = $data;
        } else {
            foreach ($data as $name => $value) {
                array_set($this->override, $name, $value);
            }
        }

        return $this;
    }

    /**
     * Set default info
     * @param array $data
     * @param bool $reset
     * @return $this
     */
    public function defaultData($data, $reset = false)
    {
        if ($reset || empty($this->default)) {
            $this->default = $data;
        } else {
            foreach ($data as $name => $value) {
                array_set($this->default, $name, $value);
            }
        }

        return $this;
    }

    /**
     * @param static $other
     */
    public function merge($other)
    {
        $this->overrideData($other->override);
        $this->defaultData($other->default);
    }

    /**
     * Init new instance and set data
     * @param array $data
     * @param bool $is_default Is default data
     * @return static
     */
    public static function with($data, $is_default = false)
    {
        $instance = new static($data, $is_default);

        return $instance;
    }

    /**
     * Init new instance and set override data
     * @param array $data
     * @return self
     */
    public static function withOverride($data)
    {
        return static::with($data, false);
    }

    /**
     * Init new instance and set default data
     * @param array $data
     * @return self
     */
    public static function withDefault($data)
    {
        return static::with($data, true);
    }

}