<?php namespace Braseidon\Scraper;

use Braseidon\Scraper\Parsers\HtmlParserFactory;
use Braseidon\Scraper\Parsers\HtmlParserInterface;
use Braseidon\Scraper\Traits\CrawlerOptionsTrait;
use RollingCurl\Request;
use RollingCurl\RollingCurl;

use Exception;

class Crawler { // extends RollingCurl

	use CrawlerOptionsTrait;

	protected $numRequests = 0;

	/**
	 * Configuration parameters.
	 *
	 * @var array
	 */
	protected $config;

	/**
	 * The HTMLParser
	 *
	 * @var HtmlParser $proxies
	 */
	protected $htmlParser;

	/**
	 * Default curlOptions for every Curl request
	 *
	 * @var array
	 */
	protected $curlOptions;

	/**
	 *  Instantiate the Object
	 */
	public function __construct(array $config = [])
	{
		$this->config = $config;

		$this->setHtmlParser($this->getHtmlParser());

		// $this->setCallback([$this->htmlParser, 'parseHtml']);
	}

	/**
	 * Instantiate the HtmlParser
	 *
	 * @param HtmlParser
	 */
	public function setHtmlParser(HtmlParserInterface $htmlParser)
	{
		$this->htmlParser = $htmlParser;
	}

	/**
	 * Get the HtmlParser
	 *
	 * @return HtmlParser
	 */
	public function getHtmlParser()
	{
		return HtmlParserFactory::create($this->config)->getHtmlParser();
	}





	/**
	 * Add multiple requests
	 *
	 * @param array  $urls
	 * @param string $method
	 */
	public function addRequests(array $urls, $method = "GET")
	{
		foreach($urls as $url)
		{
			$this->addRequest($url, $method);
		}
	}

	/**
	 * Create a new request and add it to the queue
	 *
	 * @param string $url
	 * @param string $method
	 * @param array  $options
	 */
	public function addRequest($url, $method = "GET", $options = [])
	{
		if($this->maxRequests > 0 and $this->numRequests >= $this->maxRequests)
		{
			return false;
		}

		if(! $this->index->checkUrl($url))
		{
			$request = new Request($url, $method);

			$request->addOptions($this->getRequestOptions());

			$this->index->addUrl($url);
			$this->numRequests++;

			return $this->add($request);
		}

		return false;
	}

	/**
	 * Get a new Request's curl options
	 *
	 * @return array
	 */
	public function getRequestOptions()
	{
		return [
			UserAgent::generate(),
			$this->proxies->setProxy(),
		];
	}

	/**
	 * Execute RollingCurl if this isn't running
	 *
	 * @return void
	 */
	public function crawlUrls()
	{
		if(empty($this->pendingRequests))
		{
			$this->execute();
		}
	}

	protected function finalizeCrawl()
	{
		echo 'Requests pending: ' . $this->countPending() . '<br />';
		echo 'Requests completed: ' . $this->countCompleted() . '<br />';
		echo 'Requests active: ' . $this->countActive() . '<br />';
		echo 'Total Emails grabbed: ' . $this->emailParser->count() . '<br />';
		echo 'Total URLs grabbed: ' . $this->index->count() . '<br />';
	}
}