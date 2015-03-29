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
     * @var string $domain The domain we're crawling
     */
    protected $domain;

    /**
     * The InternalLinks This is needed for crawling
     *
     * @var InternalLinks $internalLinks
     */
    protected $internalLinks;

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

        return $this->internalLinks;
    }

    /**
     * @return InternalLinks Get the InternalLinks instance
     */
    public function getInternalLinks()
    {
        return $this->internalLinks;
    }

    public function setDomain($url)
    {
        $this->domain = $url;
    }

    public function getDomain()
    {
        return $this->domain;
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

        $newLinks = [];

        if ($httpCode >= 200 and $httpCode < 400 and ! empty($html)) {
            // Parse - Links
            $newLinks = $this->getInternalLinks()->setDomain($this->getDomain())->run($html);
            // dd($newLinks);
            // Parse - Emails
            $this->runParsers($html);

            // Garbage collect
            unset($html, $url, $httpCode);

            if (! empty($newLinks)) {
                $crawler->addRequests(array_keys($newLinks));
            }
        }
    }

    /**
     * Run all of the parsers
     *
     * @param  string $html
     * @return void
     */
    public function runParsers($html)
    {
        foreach ($this->parsers as $parser) {
            $parser->run($html);
        }
    }
}
