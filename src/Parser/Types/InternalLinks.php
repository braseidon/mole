<?php namespace Braseidon\Mole\Parser\Types;

use Braseidon\RollingCurl\RollingCurl;

class InternalLinks extends AbstractParser implements ParserTypeInterface
{

    /**
     * The table that keeps the data this parser scrapes
     *
     * @var string
     */
    protected $table = 'mole_index';

    /**
     * The table column that keeps the data this parser scrapes
     *
     * @var string
     */
    protected $tableColumn = 'url';

    /**
     * Regex pattern
     *
     * @var string
     */
    protected $pattern = '/href="([^#"]*)"/i';

    /**
     * Folder depth limit for crawling
     *
     * @var integer
     */
    protected $maxDepth = 0;

    /**
     * @var array $blockArr Array of blocked strings
     */
    protected $blockedArr = ['.css', '.doc', '.gif', '.jpeg', '.jpg', '.js', '.pdf', '.png'];

    /**
     * The domain being scraped
     *
     * @var string
     */
    protected $domain;

    /**
     * Instantiate the Object
     *
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        $this->mergeOptions($config);

        $this->cache = \App::make('cache');
    }

    /**
     * Set the domain being scraped
     */
    public function setDomain($domain)
    {
        $this->domain = $domain;

        return $this;
    }

    /**
     * Runs the parser
     *
     * @param  string $html
     * @return array
     */
    public function run($html, RollingCurl $rollingCurl)
    {
        // Parse - URL's
        if (preg_match_all($this->pattern, $html, $matches, PREG_PATTERN_ORDER)) {
            $matches = array_unique($matches[1]);
            // dd($matches);

            foreach ($matches as $k => $url) {
                if (! $url = $this->parse($url)) {
                    continue;
                }

                // Add URL as request
                // $rollingCurl->addRequest($url);
                // $this->addMatch($link);
            }
        }

        // Garbage collect
        unset($matches, $html);
    }

    /**
     * Sends a link through various checks to add it to the request queue
     *
     * @param  string $url
     * @return string|bool
     */
    public function parse($url)
    {
        $url = trim(urldecode($url));

        if (strlen($url) === 0) {
            $url = '/';
        }

        // Check blocked strings
        if ($this->hasBlockedString($url)) {
            return false;
        }

        // Don't allow more than maxDepth forward slashes in the URL
        if ($this->getOption('max_depth', 0) > 0 && strpos($url, 'http') === false && substr_count($url, '/') > $this->getOption('max_depth', 0)) {
            return false;
        }
        if (strpos($url, 'http') === false && strpos($url, '/') === 0) {
            // Check for a relative path starting with a forward slash
            // Prefix the full domain
            $url = $this->domain['domain_full'] . $url;
        } elseif (strpos($url, 'http') === false && strpos($url, '/') === false) {
            // Check for a same directory reference
            if (strpos($url, 'www.') !== false) {
                continue;
            }
            $url = $this->domain['domain_full'] . '/' . $url;
        } elseif (strpos($url, 'mailto:') !== false) {
            // Dont index email addresses
            dd($url);
            return false;
        } elseif (strpos($url, $this->domain['domain_plain']) === false) {
            // Skip url if it isnt on the same domain
            return false;
        }

        return $url;
    }

    /*
    |--------------------------------------------------------------------------
    | Cache
    |--------------------------------------------------------------------------
    |
    |
    */
}
