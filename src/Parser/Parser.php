<?php namespace Braseidon\Mole\Parser;

use Braseidon\Mole\Index\CacheInterface;
use Braseidon\Mole\Parser\Types\AbstractParser;
use Braseidon\Mole\Parser\Types\InternalLinks;
use Braseidon\Mole\Parser\Types\Emails;
use Braseidon\RollingCurl\RollingCurl;
use Braseidon\RollingCurl\Request;

class Parser extends AbstractParser
{

    /**
     * Regex pattern for links
     *
     * @var string
     */
    protected $pattern = '/href="([^#"]*)"/i';

    /**
     * @var string $domain The domain we're crawling
     */
    protected $domain;

    /**
     * The Emails This is needed for scraping emails
     *
     * @var Emails $emails
     */
    protected $emails;

    /**
     * @var array $blockArr Array of blocked strings
     */
    protected $blockedArr = ['.css', '.doc', '.gif', '.jpeg', '.jpg', '.js', '.pdf', '.png'];

    /**
     * Instantiate the Parser object
     *
     * @param CacheInterface $urlCache
     */
    public function __construct(array $config, Emails $emails)
    {
        $this->mergeOptions($config);
        $this->setEmails($emails);
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
    public function setEmails(Emails $emails)
    {
        $this->emails = $emails;

        return $this->emails;
    }

    /**
     * @return InternalLinks Get the InternalLinks instance
     */
    public function getEmails()
    {
        return $this->emails;
    }

    /**
     * @param string $domain The URL we're crawling
     */
    public function setDomain($domain)
    {
        $this->domain = $domain;
    }

    /**
     * @return string
     */
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
    public function parseHtml(Request $request)
    {
        $html       = $request->getResponseText();
        $url        = $request->getUrl();
        $httpCode   = array_get($request->getResponseInfo(), 'http_code', false);

        // For checking if $rollingCurl is keeping the same instance
        // $rollingCurl->log('<span style="color:#ccc;"><strong>Code:</strong> ' . $httpCode . ' <strong>URL:</strong> #' . $rollingCurl->countCompleted() . ' - ' . $request->getUrl() . '</span><br />');
        $newLinks = [];

        if ($httpCode >= 200 and $httpCode < 400 and ! empty($html)) {
            // Parse - Links
            $newLinks = $this->parseNewLinks($html);
            // $this->parseNewLinks($html);

            // Parse - Emails
            $this->getEmails()->run($html);

            // Garbage collect
            unset($html, $url, $httpCode);

            // if (is_array($newLinks) && count($newLinks) > 0) {
            //     // dd($newLinks);
            //     $rollingCurl->addRequests($newLinks);
            // }
        }

        return $newLinks;
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

    /*
    |--------------------------------------------------------------------------
    | Internal Link Parser
    |--------------------------------------------------------------------------
    |
    |
    */

    /**
     * Runs the parser
     *
     * @param  string $html
     * @return array
     */
    public function parseNewLinks($html)
    {
        $newLinks = [];

        // Parse new internal links to crawl
        if (preg_match_all($this->pattern, $html, $matches, PREG_PATTERN_ORDER)) {
            // Garbage collect
            unset($html, $matches[0]);

            $matches = array_unique($matches[1]);

            foreach ($matches as $k => $url) {
                if (! $url = $this->filterLink($url)) {
                    continue;
                }

                $newLinks[] = $url;
            }
        }

        // Garbage collect
        unset($matches);

        return $newLinks;
    }

    /**
     * Sends a link through various checks to add it to the request queue
     *
     * @param  string $url
     * @return string|bool
     */
    public function filterLink($url)
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
            return false;
        } elseif (strpos($url, $this->domain['domain_plain']) === false) {
            // Skip url if it isnt on the same domain
            return false;
        }

        return $url;
    }
}
