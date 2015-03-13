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
     * Use regex to find matches
     *
     * @param  string $html
     * @return mixed
     */
    public function pregMatch($pattern, $html)
    {
        // Parse - URL's
        $savedMatches = [];

        if (preg_match_all($pattern, $html, $matches, PREG_PATTERN_ORDER)) {
            $matches = array_unique($matches[1]);

            foreach ($matches as $k => $link) {
                if (! $link = $this->filterMatches($link)) {
                    continue;
                }
                $savedMatches[] = $link;
            }

            unset($matches, $html);
        }
        // dd($savedMatches);
        return $savedMatches;
    }

    /**
     * Check each link to see if its fit for crawling
     *
     * @param  string $link
     * @return string|bool
     */
    public function filterMatches($link)
    {
        $link = trim(urldecode($link));

        if (strlen($link) === 0) {
            $link = '/';
        }

        // Check blocked strings
        if ($this->hasIgnoredStrings($link)) {
            return false;
        }

        // Don't allow more than maxDepth forward slashes in the URL
        if ($this->getConfig('max_depth', 0) > 0 && strpos($link, 'http') === false && substr_count($link, '/') > $this->getConfig('max_depth', 0)) {
            return false;
        }
        if (strpos($link, 'http') === false && strpos($link, '/') === 0) {              // Check for a relative path starting with a forward slash
            $link = $this->domain['domain_full'] . $link;                               // Prefix the full domain
        } elseif (strpos($link, 'http') === false && strpos($link, '/') === false) {    // Check for a same directory reference
            if (strpos($link, 'www.') !== false) {
                continue;
            }
            $link = $this->domain['domain_full'] . '/' . $link;
        } elseif (strpos($link, 'mailto:') !== false) {                                 // Dont index email addresses
            // $this->parser->addMatch(str_replace('mailto:', '', $link));
            return false;
        } elseif (strpos($link, $this->domain['domain_plain']) === false) {               // Skip link if it isnt on the same domain
            return false;
        }

        return $link;
    }
}
