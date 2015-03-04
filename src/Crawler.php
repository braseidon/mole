<?php namespace Braseidon\ShutterScraper;

use Braseidon\ShutterScraper\Http\Proxy;
use Braseidon\ShutterScraper\Http\UserAgent;
use Braseidon\ShutterScraper\Parsers\EmailParser;
use RollingCurl\Request;
use RollingCurl\RollingCurl;

use Exception;

class Crawler extends RollingCurl {

	protected $targetUrl;				// The starting URL of the crawler
	protected $targetScheme;			// The domain scheme
	protected $targetDomain;			// The target domain

	protected $threads = 2;				// Number of simultaneous connections
	protected $maxDepth = 8;
	protected $maxRequests = 3;			// Set a limit on requests, or 0 for unlimited [caution widdat]
	protected $numRequests = 0;			// Number of requests added

	/**
     * Configuration parameters.
     *
     * @var array
     */
    public $config;

	/**
	 * @var timestamp
	 */
	public $startTime;

	/**
	 * If RollingCurl is running, this is set to 1
	 *
	 * @var integer
	 */
	protected $running = 0;

	/**
	 * The parser for links
	 *
	 * @var LinkParser
	 */
	public $parserLinks;

	/**
	 * The parser for emails
	 *
	 * @var EmailParser
	 */
	public $parserEmails;

	/**
	 * Visited URL index handler
	 *
	 * @var CrawlerCache $index
	 */
	public $index;

	/**
	 * Proxy handler
	 *
	 * @var ProxyBag $proxies
	 */
	public $proxies;

	/**
	 * Default options for every Curl request
	 *
	 * @var array
	 */
	public $options = [
		CURLOPT_SSL_VERIFYHOST	=> false,
		CURLOPT_SSL_VERIFYPEER	=> false,
		CURLOPT_RETURNTRANSFER	=> true,
		CURLOPT_CONNECTTIMEOUT	=> 10,
		CURLOPT_TIMEOUT			=> 20,
		CURLOPT_FOLLOWLOCATION	=> true,
		CURLOPT_MAXREDIRS		=> 5,
		CURLOPT_HEADER			=> 0,
	];

	/**
	 *  Instantiate the Object
	 */
	public function __construct($config = [])
	{
		// Memory limit
		ini_set('memory_limit', '64M');

		$this->config = $config;
		$this->setSimultaneousLimit($this->threads);
		$this->setCallback([$this, 'parseHtml']);

		// Link index handler, email parser, and proxy handler
		$this->index		= new Index();
		$this->parserEmails	= new EmailParser();
		$this->proxies		= new Proxy();
	}

	/**
	 * Begins the crawling process.
	 *
	 * @return void
	 */
	public function crawl($targetUrl = null)
	{
		if($targetUrl !== null)
		{
			$this->setTargetUrls($targetUrl);
		}

		$this->crawlUrls();
		$this->finalizeCrawl();
	}

	/**
	 * Process the variables for the target domain
	 *
	 * @param string $targetUrl
	 */
	protected function setTargetUrls($targetUrl)
	{
		if(strpos($targetUrl, 'http://') !== false && strpos($targetUrl, 'https://') !== false)
		{
			throw new Exception('The starting URL must begin with "http" or "https".');
		}

		$this->startTime = date('Y-m-d H:i:s');

		$targetUrl = rtrim($targetUrl, '/');
		$parseTarget = parse_url($targetUrl);

		$this->targetScheme = $parseTarget['scheme'] . '://';
		$this->targetDomain = $this->targetScheme . $parseTarget['host'];

		$this->addRequest($targetUrl);
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
		if($this->maxRequests > 0 && $this->numRequests >= $this->maxRequests)
		{
			return false;
		}

		if(! $this->index->checkUrl($url))
		{
			$request = new Request($url, $method);

			$request->addOptions([
					UserAgent::generate(),
					$this->proxies->setProxy()
				]);

			$this->index->addUrl($url);
			$this->numRequests++;

			return $this->add($request);
		}

		return false;
	}

	/**
	 * Process the returned HTML with our parsers
	 *
	 * @param string  $url       The url requested
	 * @param string  $html      The body content
	 * @param int     $http_code The returned HTTP code
	 */
	protected function parseHtml(Request $request, RollingCurl $rolling_curl)
	{
		$response	= $request->getResponseInfo();
		$url		= array_get($response, 'url');
		$http_code	= array_get($response, 'http_code');
		$html = $request->getResponseText();

		// Add URL to index (or update count)
		$this->index->addUrl($url);

		if($http_code >= 200 && $http_code < 400 && ! empty($html))
		{
			$this->parserLinks->findMatches($html);

			// Parse - Emails
			$this->parserEmails->findMatches($html);

			// Garbage collect
			unset($html);

			// Crawl any newly found URLs
			$this->crawlUrls();
		}
	}

	/**
	 * Check the link against blocked strings
	 *
	 * @param  string $link
	 * @return bool
	 */
	protected function checkBlockedStrings($link)
	{
		if(empty($this->blockedArr))
		{
			return true;
		}

		foreach($this->blockedArr as $blocked)
		{
			if(strpos($link, $blocked) !== false)
			{
				return false;
			}
		}

		return true;
	}

	/**
	 * Execute RollingCurl if this isn't running
	 *
	 * @return void
	 */
	protected function crawlUrls()
	{
		if(empty($this->pendingRequests))
		{
			$this->execute();
		}
	}

	protected function finalizeCrawl()
	{
		//
	}

}
