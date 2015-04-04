<?php namespace Braseidon\Mole;

use Braseidon\Mole\Api\Index;
use Braseidon\Mole\Http\Proxy;
use Braseidon\Mole\Http\UserAgent;
use Braseidon\Mole\Parser\Parser;
use Braseidon\Mole\Traits\UsesConfig;
use Braseidon\Mole\Traits\ZebraTrait;
use Braseidon\RollingCurl\Request;
use Braseidon\RollingCurl\RollingCurl;

use Exception;
use InvalidArgumentException;

class Crawler extends RollingCurl
{
    use UsesConfig;

    /**
     * @var array $domain The target website in parts
     */
    protected $domain = '';

    /**
     * @var integer $numRequests The number of requests added
     */
    protected $numRequests = 0;

    /**
     * @var Index $index The requested URL index
     */
    protected $index;

    /**
     * @var Parser $parser The Parser object that parses HTML
     */
    protected $parser;

    /**
     * @var Proxy $proxy The Proxy object that handles proxies
     */
    protected $proxy;

    protected $options = [
        CURLINFO_HEADER_OUT         => 1,
        CURLOPT_AUTOREFERER         => 1,
        CURLOPT_CONNECTTIMEOUT      => 20,
        CURLOPT_COOKIEFILE          => '',
        CURLOPT_ENCODING            => 'gzip,deflate',
        CURLOPT_FOLLOWLOCATION      => 1,
        CURLOPT_HEADER              => 1,
        CURLOPT_MAXREDIRS           => 5,
        CURLOPT_RETURNTRANSFER      => 1,
        CURLOPT_SSL_VERIFYPEER      => 0,
        CURLOPT_TIMEOUT             => 30,
    ];

    /**
     * So the crawler knows if it started
     *
     * @var integer
     */
    private $started = false;

    /**
     * Base memory usage on load
     *
     * @var integer
     */
    private $baseMemory = 0;

    /**
     * Instantiate the Object
     *
     * @param array $config
     */
    public function __construct(array $config = [])   //Index $index, Parser $parser, Proxy $proxy
    {
        if (!extension_loaded('curl')) {
            throw new Exception('php_curl extension is not loaded.');
        }

        $this->mergeOptions($config);
        $this->setCallback([$this, 'callback']);
    }

    /*
    |--------------------------------------------------------------------------
    | Setters & Getters
    |--------------------------------------------------------------------------
    |
    |
    */

    /**
     * @param array $config Set the target
     */
    public function setTarget($target)
    {
        if (! parse_url($target)) {
            throw new InvalidArgumentException('Option `target` must be a valid URL.');
        }

        $this->setOption('target', $target);
        $this->setDomain($target);
    }

    /**
     * @return array $config Get the target
     */
    public function getTarget()
    {
        return $this->getOption('target');
    }

    /**
     * @param Index Set the Index
     */
    public function setIndex(Index $index)
    {
        $this->index = $index;
    }

    /**
     * @return Index Get the Index instance
     */
    public function getIndex()
    {
        return $this->index;
    }

    /**
     * @param Parser Set the Parser
     */
    public function setParser(Parser $parser)
    {
        $this->parser = $parser;
    }

    /**
     * @return Parser Get the Parser instance
     */
    public function getParser()
    {
        return $this->parser;
    }

    /**
     * @return Proxy Set the Proxy instance
     */
    public function setProxy(Proxy $proxy)
    {
        $this->proxy = $proxy;
    }

    /**
     * @return Proxy Get the Proxy instance
     */
    public function getProxy()
    {
        return $this->proxy;
    }

    /*
    |--------------------------------------------------------------------------
    | RollingCurl - Adding Requests
    |--------------------------------------------------------------------------
    |
    |
    */

    /**
     * Add multiple requests
     *
     * @param array  $urls
     * @param string $method
     */
    public function addRequests(array $urls, $method = "GET")
    {
        if (count($urls) == 0) {
            return false;
        }

        foreach ($urls as $url) {
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
    public function addRequest($url, $method = "GET", $postData = null, $headers = null, $options = null)
    {
        if (empty($url)) {
            throw new InvalidArgumentException('The parameter `url` cannot be empty or null.');
        }

        if (! parse_url($url)) {
            return false;
            // throw new InvalidArgumentException('The URL `' . $url . '` is invalid. Check your code.');
        }

        if ($this->getIndex()->cacheHasRequest($url) || $this->getIndex()->has($url)) {
            return false;
        }

        if ($this->getOption('request_limit') > 0 and $this->numRequests >= $this->getOption('request_limit')) {
            return false;
        }

        $this->request($url, $method, $postData, $headers, $this->getRequestOptions());
        $this->getIndex()->cacheRequest($url);
        $this->numRequests++;

        return $this;
    }

    /**
     * Set the Curl options for a Request
     *
     * @param  Request $request
     * @return Request
     */
    protected function getRequestOptions($options = [])
    {
        if ($this->getOption('use_user_agent') === true) {
            $options = $options + UserAgent::generate();
        }

        if ($this->getOption('use_proxies') === true) {
            $options = $options + $this->getProxy()->getRandomProxy();
        }

        return $options;
    }

    /*
    |--------------------------------------------------------------------------
    | RollingCurl - Crawling
    |--------------------------------------------------------------------------
    |
    |
    */

    /**
     * Begins the crawling process.
     *
     * @param  string $targetUrl
     * @return void
     */
    public function crawl($target = null)
    {
        $this->setSimultaneousLimit($this->getOption('threads'));

        // Stuff to do on first run
        if ($this->started === false) {
            if ($target !== null) {
                $this->setTarget($target);
                $this->setDomain($target);
                $this->request($target);
                $this->numRequests++;
            } else {
                $this->setDomain($this->getTarget());
                $this->request($this->getTarget());
            }

            // Save base memory usage at script runtime
            $this->setBaseMemory();

            $this->loadTargetsFromDB($this->getOption('request_limit', 0));
        }

        if ($this->countCompleted() == 0 && ! $this->countPending()) {
            throw new Exception('You need to set a target for crawling. (Or crawling is finished)');
        }

        $this->started = true;
        $this->log('Starting crawling with <strong>' . $this->countPending() . ' pending requests</strong>. The request limit is ' . $this->getOption('request_limit', 0) . '. Base memory usage: ' . formatSizeUnits($this->logMemoryUsage()), true);
        $this->crawlUrls();
    }

    /**
     * The RollingCurl callback function
     *
     * @param  Request     $request      The request object
     * @param  RollingCurl $rolling_curl The current RollingCurl object
     * @return void
     */
    public function callback(Request $request, RollingCurl $rollingCurl)
    {
        $this->getIndex()->cacheUnsetRequest($request->getUrl());
        $httpCode = array_get($request->getResponseInfo(), 'http_code', false);

        $this->getIndex()->add($request->getUrl(), ['last_http_code' => $httpCode]);

        if ($httpCode == 200) {
            $newLinks = $this->getParser()->parseHtml($request);

            if (is_array($newLinks) && count($newLinks) > 0) {
                $this->addRequests($newLinks);
            }
        }

        // Logging
        $this->logCallback($request, $httpCode);

        // Garbage collect
        unset($request, $httpCode, $newLinks);

        $this->clearCompleted();
        $this->prunePendingRequestQueue();
        $this->crawlUrls();
    }

    /**
     * Recursively parse any more URL's added to the queue
     *
     * @return void
     */
    public function crawlUrls()
    {
        if (! $this->running) {
            $this->execute();
        } elseif ($this->countPending() < ($this->getSimultaneousLimit() * 3) && $this->getOption('request_limit') == 0) {
            $this->loadTargetsFromDB($this->getOption('request_limit', 0));
            $this->log('- Pending requests low! Adding uncrawled pages from the database.', true);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Crawler Options
    |--------------------------------------------------------------------------
    |
    |
    */

    /**
     * Sets the domain with all parts, and shares with dependencies
     *
     * @param string $domain
     */
    public function setDomain($domain)
    {
        if (empty($domain)) {
            throw new InvalidArgumentException('You cannot set `domain` to a empty string.');
        }

        if ($parts = parse_url($domain)) {
            $this->domain = $parts;
            $this->domain['scheme']         = $parts['scheme'] . '://';
            $this->domain['domain_plain']   = str_ireplace('www.', '', $parts['host']);
            $this->domain['domain_full']    = $parts['scheme'] . '://' . $parts['host'];
        } else {
            throw new InvalidArgumentException('The domain specified was not a valid URL.');
        }

        $this->getParser()->setDomain($this->domain);
        $this->getIndex()->setDomain($this->domain);
    }

    /**
     * Set the proxy file path and grab them
     *
     * @param  string $path
     * @return void
     */
    public function importProxies($path)
    {
        $this->getProxy()->import($path);
    }

    /**
     * Load target URLs from the IndexDB
     *
     * @param  integer $limit 100 is the max
     * @return void
     */
    public function loadTargetsFromDB($limit)
    {
        $num = ($limit - $this->countPending());
        $urls = $this->getIndex()->getUncrawledUrls($num);

        // dd(json_encode($urls));
        $this->log('- Loaded ' . number_format(count($urls)) . ' URL\'s from the database into the request queue', true);
        $this->addRequests($urls);
    }

    /*
    |--------------------------------------------------------------------------
    | Debug Stuff
    |--------------------------------------------------------------------------
    |
    |
    */

    /**
     * Turn on debug mode
     *
     * @return void
     */
    public function debugOn()
    {
        $this->debug = true;

        $this->log('<h1>Mole Debug</h1>');
    }

    /**
    * Debug a message
    *
    * @param  string $message
    * @return void
    */
    public function log($message, $br = false)
    {
        if ($this->debug === true) {
            if (is_string($message)) {
                echo $message . ' ';
                if ($br === true) {
                    echo '<br />';
                }
            } elseif (is_array($message)) {
                echo '<ul>';
                foreach ($message as $m) {
                    echo '<li>' . $m . '</li>';
                }
                echo '</ul>';
            }
        }
    }

    /**
     * Logging stuff
     *
     * @param  Request $request
     * @param  integer  $httpCode
     * @return void
     */
    private function logCallback(Request $request, $httpCode)
    {
        $this->log('#' . $this->countCompleted());
        $this->log('<strong>Code:</strong> ' . $httpCode);
        $this->log('<strong>Pending:</strong> ' . $this->countPending());
        $this->log('<strong>Active:</strong> ' . $this->countActive());
        $this->log('<strong>Memory:</strong> ' . formatSizeUnits($this->logMemoryUsage()));
        $this->log('<strong>AVG:</strong> ' . $this->logAvgMemoryUsage());
        $this->log('<strong>URL:</strong> ' . $request->getUrl());
        $this->log('<br />');
    }

    /**
     * Set the base memory usage on runtime
     */
    private function setBaseMemory()
    {
        $this->baseMemory = memory_get_usage();
    }

    /**
     * Get the memory usage without the initial load memory
     *
     * @return integer
     */
    private function logMemoryUsage()
    {
        return (memory_get_usage() - $this->baseMemory);
    }

    /**
     * Log the current average memory usage per completed request
     *
     * @return string
     */
    private function logAvgMemoryUsage()
    {
        return formatSizeUnits($this->logMemoryUsage() / $this->countCompleted());
    }
}
