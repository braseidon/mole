<?php namespace Braseidon\Scraper\Parser;

use InvalidArgumentException;

class UrlBuilder {

	/**
	 * The seed URL to scrape from
	 *
	 * @var string
	 */
	protected $targetUrl;

	/**
	 * Target domain scheme
	 *
	 * @var string
	 */
	protected $targetScheme;

	/**
	 * The target domain, naked
	 *
	 * @var string
	 */
	protected $targetDomain;

	/**
	 * Instantiate the Object
	 */
	public function __construct($targetUrl = '')
	{
		$this->targetUrl	= $targetUrl;
		$this->targetScheme	= $targetScheme;
		$this->targetDomain	= $targetDomain;
	}

	/**
	 * Set the TargetURL that we're scraping
	 *
	 * @param string $path
	 * @param array $params
	 */
	public function setTargetUrl($url)
	{
		$parts = parse_url(trim($this->targetUrl, '/');

		if ($parts === false) {
			throw new InvalidArgumentException('Not a valid path.');
		}

		$this->targetUrl	= rtrim($url, '/');
		$this->targetScheme	= $parts['scheme'] . '://';
		$this->targetDomain	= $parts['host'];
	}
}