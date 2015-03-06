<?php namespace Braseidon\Scraper\Parsers;

class HtmlParser implements HtmlParserInterface {

	/**
	 * Config
	 *
	 * @var array
	 */
	protected $config;

	/**
	 * Visited URL index handler
	 *
	 * @var HtmlParserCache
	 */
	protected $index;

	/**
	 *  Instantiate the Object
	 */
	public function __construct(array $config = [])
	{
		$this->config = $config;
	}

	/**
	 * Begins the crawling process.
	 *
	 * @return void
	 */
	public function execute()
	{
		if($targetUrl !== null)
		{
			$this->setTargetUrls($targetUrl);
			$this->addRequest($targetUrl);
		}

		$this->crawlUrls();
		$this->finalizeCrawl();
	}

	// This is just for dev
	public function parseHtml()
	{
		dd('it worked!');
	}

}