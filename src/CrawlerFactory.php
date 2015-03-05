<?php namespace Braseidon\ShutterScraper;

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
	public function __construct(array $config)
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
		$crawler = new Crawler(
			$this->getParser(),
		);

		return $crawler;
	}

	/**
	 * Instantiate ParserFactory
	 *
	 * @return ParserFactory
	 */
	public function getParser()
	{
		return ParserFactory::create($this->config);
	}

	/**
	 * Create CrawlerFactory object
	 * @param  array  $config Configuration parameters.
	 * @return Crawler The configured Crawler.
	 */
	public static function create(array $config = [])
	{
		return (new self($config))->getCrawler();
	}
}