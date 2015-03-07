<?php namespace Braseidon\Scraper;

use Braseidon\Scraper\Http\Proxy;
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
    public function __construct(HtmlParser $htmlParser, Worker $worker, Proxy $proxy)
    {
        $this->setHtmlParser($htmlParser);
        $this->setWorker($worker);
        $this->setProxy($proxy);

        $crawler->getWorker()->setCallback([$this->htmlParser, 'callback']);

    }

    /**
     * Instantiate the HtmlParser
     *
     * @param HtmlParser
     */
    public function setHtmlParser(HtmlParser $htmlParser)
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
        return $this->htmlParser;
    }

    /**
     * Instantiate the Worker
     *
     * @param Worker
     */
    public function setWorker(Worker $worker)
    {
        $this->worker = $worker;
    }

    /**
     * Get the Worker
     *
     * @return Worker
     */
    public function getWorker()
    {
        return $this->worker;
    }

    /**
     * Begins the crawling process.
     *
     * @param  string $targetUrl
     * @return void
     */
    public function execute()
    {
        if (empty($this->target)) {
            throw new Exception('You need to set a target either before calling go() or as its paramter.');
        }

        $this->add($this->target);

        $this->getWorker()->crawlUrls();
    }

    /**
     * Add a single URL or an array of URLs
     *
     * @param string|array $url
     */
    public function add($url)
    {
        if (is_array($url)) {
            $this->getWorker()->addRequests($url);
            return true;
        }

        if (is_string($url)) {
            $this->getWorker()->addRequest($url);
            return true;
        }

        return false;
    }
}
