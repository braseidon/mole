<?php namespace Braseidon\ShutterScraper;

use Braseidon\ShutterScraper\Parsers\EmailParser;
use RollingCurl\Request;
use RollingCurl\RollingCurl;

use Exception;

class Crawler extends RollingCurl {

	private $threads = 2;			// Number of simultaneous connections
	protected $targetUrl;			// The starting URL of the crawler
	protected $targetScheme;		// The domain scheme
	protected $targetDomain;		// The target domain
	protected $maxDepth = 8;
	protected $maxRequests = 5;		// Set a limit on requests, or 0 for unlimited [caution widdat]

	/**
	 * If the link matches anything in this array, it gets ignored
	 *
	 * @var array $blockedArr
	 */
	private $blockedArr = [];

	/**
	 * Time started
	 *
	 * @var timestamp
	 */
	private $startTime;

	/**
	 * Number of requests made
	 *
	 * @var integer
	 */
	private $numRequests = 0;

	/**
	 * If RollingCurl is running, this is set to 1
	 *
	 * @var integer
	 */
	protected $running = 0;

	/**
	 * The parser to be used
	 *
	 * @var AbstractParser $parser
	 */
	public $parser;

	/**
	 * Visited URL cache handler
	 *
	 * @var CrawlerCache $cache
	 */
	protected $cache;

	/**
	 * Proxy handler
	 *
	 * @var ProxyBag $proxies
	 */
	protected $proxies;

	/**
	 * Default options for every Curl request
	 *
	 * @var array
	 */
	public $options = [
		CURLOPT_SSL_VERIFYHOST => false,
		CURLOPT_SSL_VERIFYPEER => false,
		CURLOPT_RETURNTRANSFER	=> 1,
		CURLOPT_CONNECTTIMEOUT	=> 10,
		CURLOPT_TIMEOUT			=> 20,
		CURLOPT_FOLLOWLOCATION	=> true,
		CURLOPT_MAXREDIRS		=> 5,
		CURLOPT_HEADER			=> 0,
	];

	/**
	 *  Default constructor.
	 *
	 *  @access public
	 *
	 *  @param  string  $targetUrl   The starting url for crawling
	 *  @param  string  $dbUser     The database username
	 *  @param  string  $dbPass     The database password
	 *  @param  string  $dbName     The database name
	 *  @param  string  $dbHost     The database host
	 */
	public function __construct($targetUrl = null)
	{
		// Memory limit
		ini_set('memory_limit', '64M');

		$this->setSimultaneousLimit($this->threads)		// Set thread count
			->setCallback([$this, 'parseHtml']);		// RollingCurl callback

		// Link cache handler, email parser, and proxy handler
		$this->cache = new UrlCache();
		$this->parser = new EmailParser();
		$this->proxies = new ProxyBag();
	}

	/**
	 * Begins the crawling process.
	 *
	 * @return void
	 */
	public function crawl($targetUrl = null)
	{
		if($targetUrl !== null)
			$this->setTargetUrls($targetUrl);

		$this->crawlUrls();						// Begin crawling the url
		$this->finalizeCrawl();					// Update url counts and remove the temp table
	}

	/**
	 * Process the variables for the target domain
	 *
	 * @param string $targetUrl
	 */
	protected function setTargetUrls($targetUrl)
	{
		if(strpos($targetUrl, 'http://') === false &&
			strpos($targetUrl, 'https://') === false) {
			throw new Exception('The starting URL must begin with "http" or "https".');
		}

		$this->startTime = date('Y-m-d H:i:s');
		// Store the base url
		$this->targetUrl = rtrim($targetUrl, '/') . '/';
		$parseTarget = parse_url($this->targetUrl);
		$this->targetScheme = $parseTarget['scheme'] . '://';
		$this->targetDomain = $this->targetScheme . $parseTarget['host'];

		$this->addRequest($this->targetUrl);
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
		if(! $this->cache->checkUrl($url))
		{
			// Check max
			if($this->maxRequests > 0 && $this->numRequests >= $this->maxRequests)
				return false;

			$newRequest = new Request($url, $method);

			if($this->proxies->hasProxies())
				$newRequest->setOptions($this->proxies->setProxy());

			$this->cache->addUrl($url);
			$this->numRequests++;

			return $this->add($newRequest);
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
		// dd($request->getResponseInfo());
		$url = $request->getUrl();
		$html = $request->getResponseText();
		$http_code = array_get($request->getResponseInfo(), 'http_code');

		// Add URL to index (or update count)
		$this->cache->addUrl($url);

		if($http_code >= 200 && $http_code < 400 && ! empty($html))
		{
			// Start arrays
			$urlMatches = [];
			$emailMatches = [];

			// Parse - URL's
			$pattern = '/href="([^#"]*)"/i';

			if(preg_match_all($pattern, $html, $urlMatches, PREG_PATTERN_ORDER))
			{
				$urlMatches = array_unique($urlMatches[1]);
				// dd($urlMatches);
				foreach ($urlMatches as $k => $link)
				{
					if(! $link = $this->parseLink($link))
						continue;
				}

				// Garbage collect
				unset($urlMatches);
			}

			// Parse - Emails
			$this->parser->findMatches($html);

			// Garbage collect
			unset($html);

			// Crawl any newly found URLs
			$this->crawlUrls();
		}
	}

	/**
	 * Sends a link through various checks to add it to the request queue
	 *
	 * @param  string $link
	 * @return string|bool
	 */
	protected function parseLink($link)
	{
		$link = trim($link);

		if(strlen($link) === 0)
			$link = '/';

		if(! $this->checkBlockedStrings($link))
			return false;

		// Don't allow more than maxDepth forward slashes in the URL
		if($this->maxDepth > 0 && strpos($link, 'http') === false && substr_count($link, '/') > $this->maxDepth)
			return false;

		// Check for a relative path starting with a forward slash
		if(strpos($link, 'http') === false && strpos($link, '/') === 0)
		{
			// Prefix the full domain
			$link = $this->targetDomain . $link;
		}
		// Check if HTTP and WWW are in the link
		elseif(strpos($link, 'http') === false && strpos($link, '/') === false)
		{
			if(strpos($link, 'www.') !== false)
				return false;

			$link = $this->targetDomain . '/' . $link;
		}
		// Dont index email addresses
		elseif(strpos($link, 'mailto:') !== false)
		{
			// Add email to parser's matches array
			$this->parser->addMatch(str_replace('mailto:', '', $link));

			return false;
		}
		// Skip link if it isnt on the same domain
		elseif(strpos($link, $this->targetDomain) === false)
			return false;

		// Add URL as request
		$this->addRequest($link);

		return $link;
	}

	/**
	 * Check the link against blocked strings
	 *
	 * @param  string $link
	 * @return bool
	 */
	protected function checkBlockedStrings($link)
	{
		if(count($this->blockedArr) == 0)
			return true;

		foreach($this->blockedArr as $blocked)
		{
			if(strpos($link, $blocked) !== false)
				return false;
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
			$this->execute();
	}

	/**
	 * Finalize the scrape
	 *
	 * @return array
	 */
	private function finalizeCrawl()
	{
		echo 'URLs pending: ' . $this->countPending() . '<br />';
		echo 'URLs completed: ' . $this->countCompleted() . '<br />';
		echo 'URLs active: ' . $this->countActive() . '<br />';
		echo 'Total Emails grabbed: ' . $this->parser->count() . '<br />';
		echo 'Total URLs grabbed: ' . $this->cache->count() . '<br />';
	}
}
