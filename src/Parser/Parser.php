<?php namespace Braseidon\Mole\Parser;

use Braseidon\Mole\Api\CacheInterface;
use Braseidon\Mole\Parser\Types\InternalLinks;
use RollingCurl\Request;
use RollingCurl\RollingCurl;
use Braseidon\Mole\Traits\UsesConfig;

class Parser
{
    use UsesConfig;

    /**
     * @var CacheInterface $urlCache The CacheInterface object
     */
    protected $urlCache;

    /**
     * @var string $pattern The regex pattern to search for
     */
    protected $pattern;

    /**
     * @var array $blockArr Array of blocked strings
     */
    protected $blockedFileTypes = ['.css', '.doc', '.gif', '.jpeg', '.jpg', '.js', '.pdf', '.png'];

    /**
     * @var string $domain The domain we're crawling
     */
    protected $domain = [];

    /**
     * The InternalLinks This is needed for crawling
     *
     * @var InternalLinks $internalLinks
     */
    protected $internalLinks;

    /**
     * The various parsers
     *
     * @var array
     */
    protected $parsers;

    /**
     * Instantiate the Parser object
     *
     * @param CacheInterface $urlCache
     */
    public function __construct(array $config, CacheInterface $urlCache, InternalLinks $internalLinks, array $parsers)
    {
        $this->mergeOptions($config);
        $this->setUrlCache($urlCache);
        $this->setInternalLinks($internalLinks);
        $this->parsers = $parsers;
    }

    /**
     * @param CacheInterface Instantiate the UrlCache
     */
    public function setUrlCache(CacheInterface $urlCache)
    {
        $this->urlCache = $urlCache;
    }

    /**
     * @return UrlCache Get the UrlCache instance
     */
    public function getUrlCache()
    {
        return $this->urlCache;
    }

    /**
     * @param InternalLinks Instantiate the InternalLinks
     */
    public function setInternalLinks(InternalLinks $internalLinks)
    {
        $this->internalLinks = $internalLinks;
    }

    /**
     * @return InternalLinks Get the InternalLinks instance
     */
    public function getInternalLinks()
    {
        return $this->internalLinks;
    }

    /**
     * @param array $config Set the config
     */
    public function setConfig($config = [])
    {
        $this->config = $config;
    }

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
     * Set the domain we're crawling
     *
     * @param string $url
     */
    public function setDomain($url)
    {
        if ($parts = parse_url($url)) {
            $this->domain = $parts;
            $this->domain['scheme']         = $this->domain['scheme'] . '://';
            $this->domain['domain_plain']   = str_ireplace('www.', '', $parts['host']);
            $this->domain['domain_full']    = $this->domain['scheme'] . $parts['host'];

            return $this->domain;
        }

        return false;
    }

    /*
    |--------------------------------------------------------------------------
    | Parser
    |--------------------------------------------------------------------------
    |
    |
    */

    /**
     * Process the returned HTML with our parsers
     *
     * @param  Request     $request
     * @param  RollingCurl $rolling_curl
     * @return void
     */
    public function parseHtml(Request $request, RollingCurl $crawler)
    {
        $url        = $request->getUrl();
        $httpCode   = array_get($request->getResponseInfo(), 'http_code', false);
        $html       = $request->getResponseText();

        // Add URL to index (or update count)
        $this->getUrlCache()->add($url);

        if ($httpCode >= 200 and $httpCode < 400 and ! empty($html)) {
            // Parse - Links
            $newLinks = $this->getInternalLinks()->run($html);
            dd($newLinks);

            // Parse - Emails
            $this->emailParser->run($html);

            // Garbage collect
            unset($html, $url, $httpCode);

            // Crawl any newly found URLs
            $crawler->crawlUrls();
        }
    }

    // public function runParsers($html)
    // {
    //     foreach ($this->manipulators as $manipulator) {
    //         $image = $manipulator->run($request, $image);
    //     }
    // }

    /**
     * The RollingCurl callback function
     *
     * @param  Request     $request      The request object
     * @param  RollingCurl $rolling_curl The current RollingCurl object
     * @return void
     */
    public function parseLinks(Request $request, RollingCurl $rollingCurl)
    {
        $url = $request->getUrl();
        $html = $request->getResponseText();

        // Set the domain we're crawling, if it doesn't then work return false
        if (! $this->setDomain($request->getUrl())) {
            return false;
        }

        echo 'Crawled: ' . $url . '<br />';

        $pattern = '/href="([^#"]*)"/i';
        $newLinks = $this->pregMatch($pattern, $html);

        $rollingCurl->addRequests($newLinks);
    }
}
