<?php namespace Braseidon\Mole\Drivers;

interface CurlDriver
{

    /**
     * Get the current driver
     *
     * @return mixed
     */
    public function getDriver();

    /**
     * Set the callback action for all requests
     *
     * @param callback $callback
     */
    public function setCallback($callback);

    /**
     * Add a request to the current request queue
     *
     * @param string $url
     * @param string $method
     * @param array  $options
     * @param array  $headers
     */
    public function addRequest($url, $method = "GET", $options = null, $headers = null);

    /**
     * Get the current request count
     *
     * @return integer
     */
    public function requestCount();

    /**
     * Execute the crawler
     *
     * @return void
     */
    public function execute();
}
