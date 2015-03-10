<?php namespace Braseidon\Scraper;

use RollingCurl\Request;
use RollingCurl\RollingCurl;
use Braseidon\Scraper\Cache\WebCacheInterface;
use Braseidon\Scraper\Http\ProxyInterface;

class Worker extends RollingCurl
{
    /**
     * The config array
     *
     * @var array
     */
    protected $config = [];

    protected $cache;

    /**
     * Instantiate the Object
     *
     * @param array $config
     */
    public function __construct(WebCacheInterface $cache)
    {
        $this->cache = $cache;

        // $this->setCallback([$this->htmlParser, 'callback']);

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
        if (! $this->checkRequest()) {
            return false;
        }

        $newRequest = new Request($url, $method);

        if ($this->proxies->hasProxies()) {
            $newRequest->setOptions($this->proxies->setProxy());
        }

        return $this->add($newRequest);

        return false;
    }

    public function buildOptions(array $options = [])
    {
        if ($this->proxies->hasProxies()) {
            $options = array_merge($options, $this->proxies->setProxy());
        }

        $newRequest->setOptions($options);
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
     * Check to see if we're breaking limits
     *
     * @return bool
     */
    public function checkRequest()
    {
        if(! isset($this->config['request_limit'])) {
            return false;
        }

        if ($this->config['request_limit'] > 0 and $this->numRequests >= $this->config['request_limit']) {
            return false;
        }

        if (! $this->cache->checkUrl($url)) {
            return false;
        }

        return true;
    }

    /**
     * Execute RollingCurl if this isn't running
     *
     * @return void
     */
    public function crawlUrls()
    {
        dd('called');
        $this->setCallback([$this, 'testCallback']);

        if(! $this->countPending()) {
            $this->execute();
        }
    }

    public function testCallback()
    {
        dd('callback works!');
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
