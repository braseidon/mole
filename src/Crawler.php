<?php namespace Braseidon\Mole;

use Braseidon\Mole\Api\Index;
use Braseidon\Mole\Http\Proxy;
use Braseidon\Mole\Http\UserAgent;
use Braseidon\Mole\Parser\ParserInterface;
use RollingCurl\Request;
use RollingCurl\RollingCurl;
use Braseidon\Mole\Traits\ZebraTrait;
use Braseidon\Mole\Traits\UsesConfig;

use Exception;

class Crawler extends RollingCurl
{
    use UsesConfig, ZebraTrait;

    /**
     * @var string $target The target website being scraped
     */
    protected $target;

    /**
     * @var array $domain The target website in parts
     */
    protected $domain;

    /**
     * @var integer $numRequests The number of requests added
     */
    protected $numRequests = 0;

    /**
     * @var Index $index The requested URL index
     */
    protected $index;

    /**
     * @var Parser $parser The Parser object that parses HTML
     */
    protected $parser;

    /**
     * @var Proxy $proxy The Proxy object that handles proxies
     */
    protected $proxy;

    protected $options = [
        CURLINFO_HEADER_OUT         =>  1,
        CURLOPT_AUTOREFERER         =>  1,
        CURLOPT_COOKIEFILE          =>  '',
        CURLOPT_CONNECTTIMEOUT      =>  10,
        CURLOPT_ENCODING            =>  'gzip,deflate',
        CURLOPT_FOLLOWLOCATION      =>  1,
        CURLOPT_HEADER              =>  1,
        CURLOPT_MAXREDIRS           =>  50,
        CURLOPT_TIMEOUT             =>  30,
        CURLOPT_RETURNTRANSFER      =>  1,
    ];

    /**
     * Instantiate the Object
     *
     * @param array $config
     */
    public function __construct(array $config = [])   //Index $index, ParserInterface $parser, Proxy $proxy
    {
        if (!extension_loaded('curl')) {
            throw new Exception('php_curl extension is not loaded.');
        }

        $this->mergeOptions($config);
        $this->setCallback([$this, 'callback']);
    }

    /*
    |--------------------------------------------------------------------------
    | Setters & Getters
    |--------------------------------------------------------------------------
    |
    |
    */

    /**
     * @param array $config Set the target
     */
    public function setTarget($target)
    {
        if (! parse_url($target)) {
            throw new InvalidArgumentException('Option `target` must be a valid URL.');
        }

        $this->target = $target;
    }

    /**
     * @return array $config Get the target
     */
    public function getTarget()
    {
        return $this->target;
    }

    /**
     * @param Index Set the Index
     */
    public function setIndex(Index $index)
    {
        $this->index = $index;
    }

    /**
     * @return Index Get the Index instance
     */
    public function getIndex()
    {
        return $this->index;
    }

    /**
     * @param Parser Set the Parser
     */
    public function setParser(ParserInterface $parser)
    {
        $this->parser = $parser;
    }

    /**
     * @return Parser Get the Parser instance
     */
    public function getParser()
    {
        return $this->parser;
    }

    /**
     * @return Proxy Set the Proxy instance
     */
    public function setProxy(Proxy $proxy)
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

    /*
    |--------------------------------------------------------------------------
    | RollingCurl - Adding Requests
    |--------------------------------------------------------------------------
    |
    |
    */

    /**
     * Add multiple requests
     *
     * @param array  $urls
     * @param string $method
     */
    public function addRequests(array $urls, $method = "GET")
    {
        foreach ($urls as $url) {
            $this->addRequest($url, $method);
        }
    }

    /**
     * Create a new request and add it to the queue
     *
     * @param string $url
     * @param string $method
     * @param array  $options
     */
    public function addRequest($url)
    {
        if ($this->getOption('request_limit') > 0 and $this->numRequests >= $this->getOption('request_limit')) {
            return false;
        }

        if ($this->getIndex()->check($url)) {
            return false;
        }

        $request = new Request($url);
        $request = $this->setRequestOptions($request);

        $this->getIndex()->add($url);
        $this->numRequests++;

        return $this->add($request);
    }

    /**
     * Set the Curl options for a Request
     *
     * @param  Request $request
     * @return Request
     */
    protected function setRequestOptions(Request $request)
    {
        if ($this->getOption('use_user_agent') === true) {
            $request->addOptions(UserAgent::generate());
        }

        if ($this->getOption('use_proxies') === true) {
            $request->addOptions($this->getProxy()->getRandomProxy());
        }

        return $request;
    }

    /**
     * @return array Set the ignoredFileTypes array
     */
    public function setIgnoredFileTypes(array $array = [])
    {
        $this->setOption('ignored_file_types', $array);
    }

    /**
     * Get the ignored file types
     *
     * @return array  v3e2
     *
     */
    public function getIgnoredFileTypes()
    {
        return $this->ignoredFileTypes;
    }

    /**
     * Set the proxy file path and grab them
     *
     * @param  string $path
     * @return void
     */
    public function importProxies($path)
    {
        $this->getProxy()->import($path);
    }

    /*
    |--------------------------------------------------------------------------
    | Parsing Functions
    |--------------------------------------------------------------------------
    |
    |
    */

    /**
     * Break the target into parts and set as domain
     *
     * @param string $url
     */
    public function setDomain($url)
    {
        if (! $parts = parse_url($url)) {
            return false;
        }

        $this->domain = $parts;
        $this->domain['scheme'] = $this->domain['scheme'] . '://';
        $this->domain['domain_plain'] = str_ireplace('www.', '', $parts['host']);
        $this->domain['domain_full'] = $this->domain['scheme'] . $parts['host'];

        return $this->domain;
    }

    /**
     * Check the link against blocked strings
     *
     * @param  string $link
     * @return bool
     */
    protected function hasIgnoredStrings($link)
    {
        if (! $this->getOption('ignored_file_types', false)) {
            return false;
        }

        foreach ($this->getOption('ignored_file_types') as $blocked) {
            if (strpos($link, $blocked) !== false) {
                return true;
            }
        }

        return false;
    }

    /*
    |--------------------------------------------------------------------------
    | RollingCurl - Crawling
    |--------------------------------------------------------------------------
    |
    |
    */

    /**
     * Begins the crawling process.
     *
     * @param  string $targetUrl
     * @return void
     */
    public function crawl($target = null)
    {
        if ($target !== null) {
            $this->setTarget($target);
            $this->addRequest($target);
        }

        if ($this->countCompleted() == 0 && ! $this->countPending()) {
            throw new Exception('You need to set a target for crawling. (Or crawling is finished)');
        }

        $this->execute();
    }

    /**
     * The RollingCurl callback function
     *
     * @param  Request     $request      The request object
     * @param  RollingCurl $rolling_curl The current RollingCurl object
     * @return void
     */
    public function callback(Request $request, RollingCurl $rollingCurl)
    {
        $httpCode = array_get($request->getResponseInfo(), 'http_code');
        $html = $request->getResponseText();

        echo 'Crawled: ' . $request->getUrl() . '<br />';

        if ($httpCode >= 200 && $httpCode < 400 && !empty($html)) {
            // Set the domain we're crawling, if it doesn't then work return false
            if (! $this->setDomain($request->getUrl())) {
                return false;
            }

            $pattern = '/href="([^#"]*)"/i';
            $newLinks = $this->pregMatch($pattern, $html);

            $rollingCurl->addRequests($newLinks);
        }

        $this->crawl();
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
                if (! $link = $this->filterLinks($link)) {
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
    public function filterLinks($link)
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
        if ($this->getOption('max_depth', 0) > 0 && strpos($link, 'http') === false && substr_count($link, '/') > $this->getOption('max_depth', 0)) {
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
