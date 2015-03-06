<?php namespace Braseidon\Scraper;

use Braseidon\Scraper\Parsers\HtmlParserFactory;

class CrawlerFactory {

	/**
	 * Configuration
	 *
	 * @var array $config
	 */
	protected $config;

	/**
	 * Create CrawlerFactory object
	 *
	 * @param array $config
	 */
	public function __construct(array $config = [])
	{
		$this->config = $config;
	}

	/**
	 * Create Crawler object
	 *
	 * @return Crawler
	 */
	public function getCrawler()
	{
		$crawler = new Crawler([
			$this->getHtmlParser()
			// $this->getRequest(),
			// $this->getHtmlParser()
		]);

		$crawler->setThreads($this->getThreads());
		$crawler->setOptions($this->getOptions());
		$crawler->setRequestLimit($this->getRequestLimit());

		return $crawler;
	}

	/**
	 * Instantiate HtmlParserFactory
	 *
	 * @return HtmlParserFactory
	 */
	public function getHtmlParser()
	{
		return HtmlParserFactory::create($this->config);
	}

	/**
	 * Get the request Callback
	 *
	 * @return string
	 */
	public function getThreads()
	{
		$threads = 2;

		if (isset($this->config['threads'])) {
			$threads = $this->config['threads'];
		}

		return $threads;
	}

	/**
	 * Default Curl options
	 *
	 * @return array
	 */
	public function getOptions()
	{
		return [
			CURLOPT_SSL_VERIFYHOST	=> 2,
			CURLOPT_SSL_VERIFYPEER	=> 1,
			CURLOPT_RETURNTRANSFER	=> true,
			CURLOPT_CONNECTTIMEOUT	=> 10,
			CURLOPT_TIMEOUT			=> 20,
			CURLOPT_FOLLOWLOCATION	=> true,
			CURLOPT_MAXREDIRS		=> 5,
			CURLOPT_HEADER			=> 0,
		];
	}

	/**
	 * Get the request limit or set default
	 *
	 * @return integer
	 */
	public function getRequestLimit()
	{
		$requestLimit = 2;

		if (isset($this->config['request_limit'])) {
			$requestLimit = $this->config['request_limit'];
		}

		return $requestLimit;
	}

	public function getIgnoredFiletypes()
	{
		$ignoredFiletypes = ['.css', '.doc', '.gif', '.jpeg', '.jpg', '.js', '.pdf', '.png'];

		if (isset($this->config['ignored_filetypes'])) {
			$scrapeLimit = $this->config['ignored_filetypes'];
		}

		return $ignoredFiletypes;
	}

	/**
	 * Create Crawler object
	 *
	 * @param  array   $config
	 * @return Crawler
	 */
	public static function create(array $config = [])
	{
		return (new self($config))->getCrawler();
	}
}