<?php namespace Braseidon\Scraper\Http\RequestFactory;

use RollingCurl\Request;
use RollingCurl\RollingCurl;

class RequestFactory
{

    /**
     * Configuration parameters
     *
     * @var array
     */
    protected $config;

    /**
     * Create RollingCurl\Request object
     *
     * @param array $config Configuration parameters.
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    protected $options;
}
