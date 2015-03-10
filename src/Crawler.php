<?php namespace Braseidon\Scraper;

use Braseidon\Scraper\Http\ProxyInterface;
use Braseidon\Scraper\Parser\HtmlParser;
use Braseidon\Scraper\Parser\HtmlParserFactory;
use Braseidon\Scraper\Traits\CrawlerOptions;
use RollingCurl\Request;
use RollingCurl\RollingCurl;

use Exception;

class Crawler
{
    use CrawlerOptions;

    protected $numRequests = 0;

    /**
     * @var array $config Configuration parameters.
     */
    protected $config;

    /**
     * @var string $target The target URL/website to scrape
     */
    protected $target;

    /**
     * @var HtmlParser $htmlParser The HtmlParser object
     */
    protected $htmlParser;

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
    public function __construct(HtmlParser $htmlParser, Worker $worker, ProxyInterface $proxy)
    {
        $this->setHtmlParser($htmlParser);
        $this->setWorker($worker);
        $this->setProxy($proxy);

        // $this->getWorker()->setCallback([$this->htmlParser, 'callback']);
    }

    /*
    |--------------------------------------------------------------------------
    | Setters & Getters
    |--------------------------------------------------------------------------
    |
    |
    */

    /**
     * @param HtmlParser Instantiate the HtmlParser
     */
    public function setHtmlParser(HtmlParser $htmlParser)
    {
        $this->htmlParser = $htmlParser;
    }

    /**
     * @return HtmlParser Get the HtmlParser instance
     */
    public function getHtmlParser()
    {
        return $this->htmlParser;
    }

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
     * @param Proxy Set the Proxy
     */
    public function setProxy(ProxyInterface $proxy)
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
    public function importProxies($path = null)
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
        return $this->getWorker()->addRequest($url);
    }

    /**
     * Begins the crawling process.
     *
     * @param  string $targetUrl
     * @return void
     */
    public function crawl()
    {
        if (empty($this->target)) {
            throw new Exception('You need to set a target either before calling go() or as its paramter.');
        }

        $this->add($this->target);

        dd($this->getWorker());
    }

}
