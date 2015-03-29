<?php namespace Braseidon\Mole;

use Braseidon\Mole\Api\Index;
use Braseidon\Mole\Http\Proxy;
use Braseidon\Mole\Http\UserAgent;
use Braseidon\Mole\Parser\Parser;
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
    public function __construct(array $config = [])   //Index $index, Parser $parser, Proxy $proxy
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
    public function setParser(Parser $parser)
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

        $this->request($url, 'GET', null, null, $this->getRequestOptions());
        $this->numRequests++;

        return $this;
    }

    /**
     * Set the Curl options for a Request
     *
     * @param  Request $request
     * @return Request
     */
    protected function getRequestOptions($options = [])
    {
        if ($this->getOption('use_user_agent') === true) {
            $options = $options + UserAgent::generate();
        }

        if ($this->getOption('use_proxies') === true) {
            $options = $options + $this->getProxy()->getRandomProxy();
        }

        return $options;
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
            $this->getParser()->setDomain($target);
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
        echo '#' . $this->countCompleted() . ' - ' . $request->getUrl() . '<br />';

        $this->getIndex()->add($request->getUrl());

        $newLinks = $this->getParser()->parseHtml($request, $rollingCurl);

        $this->crawl();
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

        // Check for a relative path starting with a forward slash
        if (strpos($link, 'http') === false && strpos($link, '/') === 0) {
            // Prefix the full domain
            $link = $this->domain['domain_full'] . $link;
        // Check for a same directory reference
        } elseif (strpos($link, 'http') === false && strpos($link, '/') === false) {
            if (strpos($link, 'www.') !== false) {
                continue;
            }
            $link = $this->domain['domain_full'] . '/' . $link;
            // Dont index email addresses
        } elseif (strpos($link, 'mailto:') !== false) {
            // $this->parser->addMatch(str_replace('mailto:', '', $link));
            return false;
            // Skip link if it isnt on the same domain
        } elseif (strpos($link, $this->domain['domain_plain']) === false) {
            return false;
        }

        return $link;
    }

    /**
     * Filter emails for saving
     *
     * @param  string $email
     * @return string|bool
     */
    public function filterEmails($email)
    {
        $email = trim(urldecode($email));
    }
}
