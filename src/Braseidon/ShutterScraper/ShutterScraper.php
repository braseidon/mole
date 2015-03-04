<?php namespace Braseidon\ShutterScraper;

use RollingCurl\Request;
use RollingCurl\RollingCurl;

use Exception;

class ShutterScraper extends RollingCurl {

	protected $ch;						// Stores the curl handler
	protected $db;						// Stores the database connection
	protected $startTime;				// The timestamp that caterpillar started

	protected $startUrl;				// The starting URL of the crawler
	protected $domain;					// The domain path
	protected $crawled = 0;				// Number of crawled pages

	/**
	 * If the link matches anything in this array, it gets ignored
	 *
	 * @var array $blockedArr
	 */
	protected $blockedArr = [];

	/**
	 * The parser to be used
	 *
	 * @var AbstractParser $parser
	 */
	protected $parser;

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
	 *  @param  string  $startUrl   The starting url for crawling
	 *  @param  string  $dbUser     The database username
	 *  @param  string  $dbPass     The database password
	 *  @param  string  $dbName     The database name
	 *  @param  string  $dbHost     The database host
	 */
	public function __construct($startUrl = null)
	{
		parent::__construct();

		// Memory limit
		ini_set('memory_limit', '64M');

		// RollingCurl callback
		$this->setCallback([$this, 'parseHtml']);

		// Link cache handler, email parser, and proxy handler
		$this->cache = new CrawlerCache();
		$this->parser = new EmailParser();
		$this->proxies = new ProxyBag();

		if($startUrl)
		{
			// Validate url
			if(strpos($startUrl, 'http://') === false &&
				strpos($startUrl, 'https://') === false)
			{
				throw new Exception('The starting URL must begin with "http" or "https".');
			}

			// Store the base url
			$this->startUrl = rtrim($startUrl, '/').'/';
			$this->startTime = date('Y-m-d H:i:s');
			// Parse the starting URL
			$info = parse_url($startUrl);
			$this->domain = $info['scheme'].'://'.$info['host'];
		}
	}

	/**
	 * Begins the crawling process.
	 *
	 * @return void
	 */
	public function crawl()
	{
		$this->addRequest($this->startUrl);		// Add initial URL to crawl list
		$this->crawlUrls();						// Begin crawling the url
		$this->finalizeCrawl();					// Update url counts and remove the temp table
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
			$this->cache->addUrl($url);

			$newRequest = new Request($url, $method);
			$newRequest->setOptions($this->proxies->proxyCurlOpts($options));

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
	protected function parseHtml($url, $html, $http_code)
	{
		if($http_code >= 200 && $http_code < 400 && !empty($html)) {

			// Add URL to index (or update count)
			if(!$this->checkUrlExists($url, $html, $filesize))
			{
				$this->addUrlToIndex($url, $html, $filesize);
			}

			// Find all urls on the page
			$pattern = '/href="([^#"]*)"/i';
			$urlMatches = [];

			if(preg_match_all($pattern, $html, $urlMatches, PREG_PATTERN_ORDER))
			{
				// Garbage collect
				unset($html);

				$urlMatches = array_unique($urlMatches[1]);

				// iterate over each link found on the page
				foreach ($urlMatches as $k => $link)
				{
					// Parse URL
					if(! $link = $this->parseLink($link))
						continue;

					// Add to requests
					$this->addRequest($link);
				}

				// Garbage collect
				unset($urlMatches);

				// Crawl any newly found URLs
				$this->crawlUrls();
			}
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

		// Check blocked strings
		if($this->checkBlockedStrings($link))
			return false;

		// Don't allow more than maxDepth forward slashes in the URL
		if($this->maxDepth > 0 && strpos($link, 'http') === false && substr_count($link, '/') > $this->maxDepth)
			return false;

		// Check for a relative path starting with a forward slash
		if(strpos($link, 'http') === false && strpos($link, '/') === 0)
		{
			// Prefix the full domain
			$link = $this->domain . $link;
		}
		// Check if HTTP and WWW are in the link
		elseif(strpos($link, 'http') === false && strpos($link, '/') === false)
		{
			if(strpos($link, 'www.') !== false)
				return false;

			$link = $this->domain . '/' . $link;
		}
		// Dont index email addresses
		elseif(strpos($link, 'mailto:') !== false)
		{
			// Add email to parser's matches array
			$this->parser->addMatch(str_replace('mailto:', '', $link));

			return false;
		}
		// Skip link if it isnt on the same domain
		elseif(strpos($link, $this->domain) === false)
			return false;

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
		if(! $this->running)
			$this->execute();
	}

	/**
	 * Finalize the scrape
	 *
	 * @return array
	 */
	private function finalizeCrawl()
	{
		return $this->parser->getMatches();
	}
}
