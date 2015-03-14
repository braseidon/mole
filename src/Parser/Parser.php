<?php namespace Braseidon\Mole\Parser;

use Braseidon\Mole\Cache\Http\WebCacheInterface;
use RollingCurl\Request;
use RollingCurl\RollingCurl;
use Braseidon\Mole\Traits\UsesConfig;

class Parser implements ParserInterface
{
    use UsesConfig;

    /**
     * @var WebCache $cache The WebCache object
     */
    protected $cache;

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
     * Instantiate the Parser object
     *
     * @param WebCacheInterface $cache
     */
    public function __construct(array $config = [])
    {
        $this->mergeOptions($config);
    }

    /**
     * @param WebCache Instantiate the WebCache
     */
    public function setWebCache(WebCacheInterface $cache)
    {
        $this->cache = $cache;
    }

    /**
     * @return WebCache Get the WebCache instance
     */
    public function getCache()
    {
        return $this->cache;
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
     * Set the regex pattern to match on
     *
     * @param string $pattern
     */
    public function setPattern($pattern)
    {
        $this->pattern = $pattern;
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
    | Parsing Functions
    |--------------------------------------------------------------------------
    |
    |
    */

    /**
     * Check the link against blocked strings
     *
     * @param  string $link
     * @return bool
     */
    protected function hasIgnoredStrings($link)
    {
        if (! $this->getConfig('ignore_file_types', false)) {
            return false;
        }

        foreach ($this->getConfig('ignore_file_types') as $blocked) {
            if (strpos($link, $blocked) !== false) {
                return true;
            }
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

    /**
     * Process the returned HTML with our parsers
     *
     * @param  Request     $request
     * @param  RollingCurl $rolling_curl
     * @return void
     */
    public function parseHtml(Request $request, RollingCurl $rolling_curl)
    {
        $url        = $request->getUrl();
        $httpCode   = array_get($request->getResponseInfo(), 'http_code', false);
        $html       = $request->getResponseText();
        // Add URL to index (or update count)
        $this->index->addUrl($url);
        if ($httpCode >= 200 and $httpCode < 400 and ! empty($html)) {
            // Parse - Links
            $this->linkParser->findMatches($html);
            // Parse - Emails
            $this->emailParser->findMatches($html);
            // Garbage collect
            unset($html, $url, $httpCode);
            // Crawl any newly found URLs
            $this->crawlUrls();
        }
    }
}
