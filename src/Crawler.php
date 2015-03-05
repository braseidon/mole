<?php namespace Braseidon\ShutterScraper;

use Braseidon\ShutterScraper\Http\Proxy;
use Braseidon\ShutterScraper\Http\UserAgent;
use Braseidon\ShutterScraper\Parsers\EmailParser;
use Braseidon\ShutterScraper\Parsers\LinkParser;
use RollingCurl\Request;
use RollingCurl\RollingCurl;

use Exception;

class Crawler extends RollingCurl {

	protected $threads = 2;				// Number of simultaneous connections
	protected $maxRequests = 2;			// Set a limit on requests, or 0 for unlimited [caution widdat]
	protected $numRequests = 0;			// Number of requests added

	/**
     * Configuration parameters.
     *
     * @var array
     */
    protected $config;

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
	protected $linkParser;

	/**
	 * The parser for emails
	 *
	 * @var EmailParser
	 */
	protected $emailParser;

	/**
	 * Visited URL index handler
	 *
	 * @var CrawlerCache $index
	 */
	protected $index;

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
	protected $options = [
		CURLOPT_SSL_VERIFYHOST	=> 2,
		CURLOPT_SSL_VERIFYPEER	=> 1,
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

		$this->startTime = date('Y-m-d H:i:s');

		$this->config = $config;
		$this->setSimultaneousLimit($this->threads);
		$this->setCallback([$this, 'parseHtml']);

		// Link index handler, email parser, and proxy handler
		$this->index		= new Index();
		$this->proxies		= new Proxy();
		$this->linkParser	= new LinkParser();
		$this->emailParser	= new EmailParser();
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
			$this->addRequest($targetUrl);
		}

		$this->crawlUrls();
		$this->finalizeCrawl();
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

			$request->addOptions([UserAgent::generate(), $this->proxies->setProxy()]);

			$this->index->addUrl($url);
			$this->numRequests++;

			return $this->add($request);
		}

		return false;
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
		echo 'Requests pending: ' . $this->countPending() . '<br />';
		echo 'Requests completed: ' . $this->countCompleted() . '<br />';
		echo 'Requests active: ' . $this->countActive() . '<br />';
		echo 'Total Emails grabbed: ' . $this->emailParser->count() . '<br />';
		echo 'Total URLs grabbed: ' . $this->index->count() . '<br />';
	}

	/**
	 * Import proxies for use with scraping
	 *
	 * @param  string|array $proxies
	 * @return array
	 */
	public function importProxies($proxies)
	{
		return $this->proxies->import($proxies);
	}

}
