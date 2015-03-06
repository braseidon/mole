<?php namespace Braseidon\Scraper\Http;

use File;

use Exception;
use InvalidArgumentException;

class Proxy {

	/**
	 * Array of proxies for scraping
	 *
	 * @var array
	 */
	protected $proxies = [];

	/**
	 * Proxy count
	 *
	 * @var integer
	 */
	protected $proxyCount = 0;

	/**
	 * Import proxies for use by the system
	 *
	 * @param  string|array $proxies
	 * @return array
	 */
	public function import($path)
	{
		$proxies = $this->getFile($path);
		$proxies = explode(PHP_EOL, $proxies);

		if(count($proxies) > 0)
		{
			$newArr = [];

			foreach($proxies as $proxy)
			{
				$proxyArr = explode(':', $proxy);

				if(is_array($proxyArr) and count($proxyArr) >= 2)
				{
					$proxyArr = array_map('trim', $proxyArr);
					$proxyArr = array_unique($proxyArr);

					$newArr[] = [
						'ip'	=> $proxyArr[0],
						'port'	=> $proxyArr[1],
						'user'	=> (! isset($proxyArr[2]) ? false : $proxyArr[2]),
						'pass'	=> (! isset($proxyArr[3]) ? false : $proxyArr[3]),
					];
				}
			}

			$this->proxies = $newArr;
			$this->proxyCount = count($this->proxies);

			return $this->proxies;
		}

		throw new Exception('Proxies array either failed or is empty!');
	}

	/**
	 * Get the proxy list file
	 *
	 * @param  string $path
	 * @return file|bool
	 */
	private function getFile($path)
	{
		if(File::exists($path))
		{
			return File::get($path);
		}

		throw new InvalidArgumentException('Path to proxy file is invalid.');
	}

	/**
	 * Sets the Curl object's proxy options using a random proxy
	 *
	 * @param array $options
	 */
	public function setProxy($options = [])
	{
		// Grab random proxy
		if(! $proxy = $this->random())
		{
			return $options;
			// throw new Exception('No proxy was returned!');
		}

		// Set the Curl object's options
		$options[CURLOPT_PROXY]		= $proxy['ip'];
		$options[CURLOPT_PROXYPORT]	= $proxy['port'];

		// Apply user and pass if not null
		if(! empty($proxy['user']) and ! empty($proxy['pass']))
			$options[CURLOPT_PROXYUSERPWD] = $proxy['user'] . ':' . $proxy['pass'];

		return $options;
	}

	/**
	 * Returns answer depending on if array is empty
	 *
	 * @return boolean
	 */
	public function hasProxies()
	{
		if(! empty($this->proxies))
			return true;

		return false;
	}

	/**
	 * Returns a random proxy
	 *
	 * @return array|bool
	 */
	public function random()
	{
		if(! $this->hasProxies())
			return false;

		$rand = mt_rand(0, $this->proxyCount);

		return $this->proxies[$rand];
	}

	/**
	 * Count the proxies
	 *
	 * @return integer
	 */
	public function count()
	{
		return count($this->proxies);
	}
}