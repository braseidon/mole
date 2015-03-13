<?php namespace Braseidon\Mole\Traits;

trait UsesConfig
{
    /**
     * The config array
     *
     * @var array
     */
    protected $config = [];

    /**
     * Get an option
     *
     * @param  string $key
     * @param  mixed $default
     * @return mixed
     */
    public function getOption($key, $default = null)
    {
        if (! empty($this->config[$key])) {
            return $this->config[$key];
        }

        return $default;
    }

    /**
     * Get all options
     *
     * @return array
     */
    public function getAllOptions()
    {
        return $this->config;
    }

    /**
     * Set an option
     *
     * @param string $key
     * @param mixed  $value
     */
    public function setOption($key, $value)
    {
        $this->config[$key] = $value;

        return $this->config[$key];
    }

    /**
     * Merge options
     *
     * @param  array $config
     * @return array
     */
    public function mergeOptions($config = [])
    {
        $this->config = array_merge($this->config, $config);

        return $this->config;
    }

    /**
     * Tell if a option exists
     *
     * @param  string  $key
     * @return boolean
     */
    public function hasOption($key)
    {
        return isset($this->config[$key]);
    }
}
