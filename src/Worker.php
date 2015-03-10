<?php namespace Braseidon\Mole;

use Braseidon\Mole\Http\Proxy;
use Braseidon\Mole\Http\UserAgent;
use Braseidon\Mole\Parser\ParserInterface;
use RollingCurl\Request;
use RollingCurl\RollingCurl;

use InvalidArgumentException;
use Exception;

class Worker extends RollingCurl
{
    /**
     * @var array $config The config array
     */
    protected $config = [];

    /**
     * @var Proxy $proxy The proxy handler
     */
    protected $proxy;

    /**
     * @var Parser $parser The HTML parser
     */
    protected $parser;

    /**
     * @var integer $numRequests The number of requests performed
     */
    protected $numRequests = 0;

    /**
     * @var array $options The base options set for every request
     */
    protected $options = [
        CURLOPT_CONNECTTIMEOUT      => 10,
        CURLOPT_FOLLOWLOCATION      => true,
        CURLOPT_HEADER              => 0,
        CURLOPT_MAXREDIRS           => 5,
        CURLOPT_RETURNTRANSFER      => true,
        CURLOPT_SSL_VERIFYHOST      => 2,
        CURLOPT_SSL_VERIFYPEER      => 1,
        CURLOPT_TIMEOUT             => 20,
    ];

    /**
     * Instantiate the Object
     *
     * @param WebCacheInterface $cache
     */
    public function __construct(ParserInterface $parser, array $config = [])
    {
        $this->setParser($parser);
        $this->mergeConfig($config);
        $this->proxy = $this->getProxy();

        $this->setup();
    }

    /**
     * @param Parser Instantiate the Parser
     */
    public function setParser(ParserInterface $parser)
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
     * Create a new request and add it to the queue
     *
     * @param string $url
     * @param string $method
     * @param array  $options
     */
    public function addRequest($url, $options = [])
    {
        if (! $this->checkRequest($url)) {
            return false;
        }

        $newRequest = new Request($url);

        $newRequest->setOptions($this->buildOptions());

        $this->add($newRequest);
    }

    /**
     * Check to see if we're breaking limits
     *
     * @return bool
     */
    public function checkRequest($url)
    {
        if (! $this->hasConfig('request_limit')) {
            throw new InvalidArgumentException('"request_limit" must be set.');
        }

        if ($this->getConfig('request_limit') > 0 and $this->numRequests >= $this->getConfig('request_limit')) {
            return false;
        }

        // if ($this->cache->check($url)) {
        //     return false;
        // }

        return true;
    }

    /**
     * Add multiple requests
     *
     * @param array  $urls
     * @param string $method
     */
    public function addRequests(array $urls)
    {
        foreach ($urls as $url) {
            $this->addRequest($url);
        }
    }

    /**
     * Execute RollingCurl if this isn't running
     *
     * @return void
     */
    public function crawlUrls()
    {
        if (! $this->countCompleted()) {
            $this->execute();
        }
    }

    /**
     * The RollingCurl callback function
     *
     * @param  Request     $request      The request object
     * @param  RollingCurl $rolling_curl The current RollingCurl object
     * @return void
     */
    public function theCallback(Request $request, RollingCurl $rollingCurl)
    {
        $this->getParser()->callback($request, $rollingCurl);
    }

    /*
    |--------------------------------------------------------------------------
    | Options & Proxy Setup
    |--------------------------------------------------------------------------
    |
    |
    */

    /**
     * Setup the class options
     *
     * @return void
     */
    private function setup()
    {
        $this->setCallback([$this, 'theCallback']);

        if ($this->hasConfig('threads')) {
            $this->setSimultaneousLimit($this->getConfig('threads', 5));
        }
    }
    /**
     * Get the Proxy object
     *
     * @return Proxy
     */
    public function getProxy()
    {
        if (! $this->proxy) {
            return new Proxy();
        }

        return $this->proxy;
    }

    /**
     * Set the proxy file path and grab them
     *
     * @param  string $path
     * @return void
     */
    public function importProxies($path)
    {
        $this->proxy->setProxyPath($path);
    }

    /**
     * Builds the curl options array
     *
     * @return array
     */
    public function buildOptions($curlOptions = [])
    {
        if ($this->getConfig('use_proxies')) {
            if ($this->proxy->hasProxies()) {
                $curlOptions = $this->proxy->getRandomProxy($curlOptions);
            } else {
                throw new Exception('You have use_proxies set to true, but no proxies were imported.');
            }
        }

        if ($this->getConfig('use_user_agent')) {
            $curlOptions = UserAgent::generate($curlOptions);
        }

        return $curlOptions;
    }

    /*
    |--------------------------------------------------------------------------
    | Config
    |--------------------------------------------------------------------------
    |
    |
    */

    /**
     * Get a config option
     *
     * @param  string $key
     * @param  mixed $default
     * @return mixed
     */
    public function getConfig($key, $default = null)
    {
        if (isset($this->config[$key])) {
            return $this->config[$key];
        }

        return $default;
    }

    /**
     * Set a config option
     *
     * @param string $key
     * @param mixed  $value
     */
    public function setConfig($key, $value)
    {
        $this->config[$key] = $value;
    }

    /**
     * Merge config options
     *
     * @param  array $options
     * @return array
     */
    public function mergeConfig($options = [])
    {
        $this->config = array_merge($this->config, $options);
    }

    /**
     * Tell if a config option exists
     *
     * @param  string  $key
     * @return boolean
     */
    public function hasConfig($key)
    {
        return isset($this->config[$key]);
    }

    // protected function finalizeCrawl()
    // {
    //     echo 'Requests pending: ' . $this->countPending() . '<br />';
    //     echo 'Requests completed: ' . $this->countCompleted() . '<br />';
    //     echo 'Requests active: ' . $this->countActive() . '<br />';
    //     echo 'Total Emails grabbed: ' . $this->emailParser->count() . '<br />';
    //     echo 'Total URLs grabbed: ' . $this->cache->count() . '<br />';
    // }
}
