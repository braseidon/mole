<?php namespace Braseidon\Mole\Parser;

use Braseidon\Mole\Api\CacheInterface;
use Braseidon\Mole\Parser\Types\InternalLinks;
use Braseidon\Mole\Parser\Types\Emails;
use Braseidon\Mole\Traits\UsesConfig;

use RollingCurl\RollingCurl;
use RollingCurl\Request;

class Parser
{
    use UsesConfig;

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
     * The Emails This is needed for scraping emails
     *
     * @var Emails $emails
     */
    protected $emails;

    /**
     * Instantiate the Parser object
     *
     * @param CacheInterface $urlCache
     */
    public function __construct(array $config, InternalLinks $internalLinks, Emails $emails)
    {
        $this->mergeOptions($config);
        $this->setInternalLinks($internalLinks);
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
        $this->getInternalLinks()->setDomain($domain);
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
    public function parseHtml(Request $request, RollingCurl $crawler)
    {
        $url        = $request->getUrl();
        $httpCode   = array_get($request->getResponseInfo(), 'http_code', false);
        $html       = $request->getResponseText();

        $newLinks = [];

        if ($httpCode >= 200 and $httpCode < 400 and ! empty($html)) {
            // Parse - Links
            $newLinks = $this->getInternalLinks()->run($html);

            // Parse - Emails
            $this->getEmails()->run($html);
            // $this->runParsers($html);

            // Garbage collect
            unset($html, $url, $httpCode);

            if (! empty($newLinks) && is_array($newLinks)) {
                $crawler->addRequests($newLinks);
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
