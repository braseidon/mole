<?php namespace Braseidon\Mole;

use Braseidon\Mole\Traits\CrawlerOptions;
use Braseidon\Mole\Traits\UsesConfig;

use Exception;

class Crawler
{
    use CrawlerOptions, UsesConfig;

    protected $numRequests = 0;

    /**
     * @var string $target The target URL/website to scrape
     */
    protected $target;

    /**
     * @var Worker $worker The RollingCurl extension
     */
    protected $worker;

    /**
     * @var array $curlOptions Default curlOptions for every Curl request
     */
    protected $curlOptions;

    /**
     *  Instantiate the Object
     */
    public function __construct(Worker $worker)
    {
        $this->setWorker($worker);
    }

    /*
    |--------------------------------------------------------------------------
    | Setters & Getters
    |--------------------------------------------------------------------------
    |
    |
    */

    /**
     * @param Worker Set the Worker
     */
    public function setWorker(Worker $worker)
    {
        $this->worker = $worker;
    }

    /**
     * @return Worker Get the Worker instance
     */
    public function getWorker()
    {
        return $this->worker;
    }

    /**
     * @return array Set the ignoredFileTypes array
     */
    public function setIgnoredFileTypes(array $array = [])
    {
        return $this->ignoredFileTypes = $array;
    }

    public function getIgnoredFileTypes()
    {
        return $this->ignoredFileTypes;
    }

    /*
    |--------------------------------------------------------------------------
    | RollingCurl Section
    |--------------------------------------------------------------------------
    |
    |
    */

    /**
     * Set the proxy file path and grab them
     *
     * @param  string $path
     * @return void
     */
    public function importProxies($path)
    {
        $this->getProxy()->setProxyPath($path);
    }

    /**
     * Add a single URL or an array of URLs
     *
     * @param mixed $url
     */
    public function add($url)
    {
        $this->getWorker()->addRequest($url);
    }

    /**
     * Begins the crawling process.
     *
     * @param  string $targetUrl
     * @return void
     */
    public function crawl($target = null)
    {
        if ($target !== null) {
            $this->add($target);
        } else {
            $this->add($this->getTarget());
        }

        if (! $this->getWorker()->countPending()) {
            throw new Exception('You need to set a target for crawling.');
        }

        $this->getWorker()->crawlUrls();
    }
}
