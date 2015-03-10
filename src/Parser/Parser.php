<?php namespace Braseidon\Mole\Parser;

use Braseidon\Mole\Cache\WebCacheInterface;
use RollingCurl\Request;
use RollingCurl\RollingCurl;

class Parser implements ParserInterface
{
    protected $config;

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
    protected $blockedArr = [];

    /**
     * @var string $domain The domain we're crawling
     */
    protected $domain;

    /**
     * Instantiate the Parser object
     *
     * @param WebCacheInterface $cache
     */
    public function __construct(WebCacheInterface $cache)
    {
        $this->cache = $cache;
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
            $this->domain = $parts['host'];
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
    protected function checkBlockedStrings($link)
    {
        if(count($this->blockedArr) == 0)
            return true;

        foreach($this->blockedArr as $blocked)
        {
            if(strpos($link, $blocked) !== false)
                return false;
        }

        return true;
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
    public function callback(Request $request, RollingCurl $rollingCurl)
    {
        // dd($request->getResponseInfo());
        $url = $request->getUrl();
        $html = $request->getResponseText();
        $httpCode = array_get($request->getResponseInfo(), 'http_code');

        // Set the domain we're crawling
        $this->setDomain($url);

        // Add URL to index (or update count)
        $this->cache->add($url);

        if ($httpCode >= 200 && $httpCode < 400 && ! empty($html)) {
            // Start arrays
            $emailMatches = [];

            // Parse - Links
            $this->parseMatches($html);

            // Parse - Emails
            // $this->parseEmails($html);

            // Garbage collect
            unset($html);

            // Crawl any newly found URLs
            $this->crawlUrls();
        }
    }

    public function parseMatches($html)
    {
        // Parse - URL's
        $savedMatches = [];
        $pattern = '/href="([^#"]*)"/i';

        if (preg_match_all($pattern, $html, $matches, PREG_PATTERN_ORDER)) {

            $matches = array_unique($matches[1]);

            dd($matches);

            foreach ($matches as $k => $link) {
                if (! $link = $this->parseMatch($link)) {
                    continue;
                }
                $savedMatches[] = $link;
            }

            dd($savedMatches);

            unset($matches, $html);
        }
    }

    /**
     * Sends a link through various checks to add it to the request queue
     *
     * @param  string $link
     * @return string|bool
     */
    public function parseMatch($link)
    {
        $link = trim($link);

        if (strlen($link) === 0) {
            $link = '/';
        }

        // Check blocked strings
        if ($this->checkBlockedStrings($link)) {
            return false;
        }

        // Don't allow more than maxDepth forward slashes in the URL
        if ($this->maxDepth > 0 && strpos($link, 'http') === false && substr_count($link, '/') > $this->maxDepth) {
            return false;
        }

        // Check for a relative path starting with a forward slash
        if (strpos($link, 'http') === false && strpos($link, '/') === 0) {
            // Prefix the full domain
            $link = $this->domain . $link;
        }
        // Check if HTTP and WWW are in the link
        elseif (strpos($link, 'http') === false && strpos($link, '/') === false) {
            if (strpos($link, 'www.') !== false) {
                return false;
            }

            $link = $this->domain . '/' . $link;
        }
        // Dont index email addresses
        elseif (strpos($link, 'mailto:') !== false) {
            // Add email to parser's matches array
            // $this->parser->addMatch(str_replace('mailto:', '', $link));

            return false;
        }
        // Skip link if it isnt on the same domain
        elseif (strpos($link, $this->domain) === false) {
            return false;
        }

        return $link;
    }
}
