<?php namespace Braseidon\ShutterScraper;

use Exception;

class ProxyBag {

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
	 * Sets the Curl object's proxy curlOpts using a random proxy
	 *
	 * @param array $curlOpts
	 */
	public function setProxy($curlOpts = [])
	{
		// Grab random proxy
		if(! $proxy = $this->getProxy())
		{
			return false;
			// throw new Exception('No proxy was returned!');
		}

		// Set the Curl object's options
		$curlOpts[CURLOPT_PROXY]		= $proxy['ip'];
		$curlOpts[CURLOPT_PROXYPORT]	= $proxy['port'];

		// Apply user and pass if not null
		if(! empty($proxy['username']) && ! empty($proxy['password']))
			$curlOpts[CURLOPT_PROXYUSERPWD] = $proxy['username'] . ':' . $proxy['password'];

		return $curlOpts;
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