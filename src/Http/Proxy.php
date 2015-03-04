<?php namespace Braseidon\ShutterScraper\Http;

use Exception;

class Proxy {

	/**
	 * Array of proxies for scraping
	 *
	 * @var array
	 */
	protected $proxies = [];

	public function __construct()
	{

	}

	/**
	 * Sets the Curl object's proxy options using a random proxy
	 *
	 * @param array $options
	 */
	public function setProxy($options = [])
	{
		// Grab random proxy
		if(! $proxy = $this->getProxy())
		{
			return $options;
			// throw new Exception('No proxy was returned!');
		}

		// Set the Curl object's options
		$options[CURLOPT_PROXY]		= $proxy['ip'];
		$options[CURLOPT_PROXYPORT]	= $proxy['port'];

		// Apply user and pass if not null
		if(! empty($proxy['username']) && ! empty($proxy['password']))
			$options[CURLOPT_PROXYUSERPWD] = $proxy['username'] . ':' . $proxy['password'];

		return $options;
	}

	/**
	 * Returns answer depending on if array is empty
	 *
	 * @return boolean
	 */
	public function hasProxies()
	{
		if(empty($this->proxies))
			return false;

		return true;
	}

	/**
	 * Returns a random proxy
	 *
	 * @return array|bool
	 */
	public function getProxy()
	{
		if(! $this->hasProxies())
			return false;

		$rand = mt_rand(0, count($this->proxies)-1);

		return $proxy[$rand];
	}
}