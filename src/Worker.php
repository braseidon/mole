<?php namespace Braseidon\Scraper;

use RollingCurl\Request;
use RollingCurl\RollingCurl;

class Worker extends RollingCurl
{

    /**
     * The config array
     *
     * @var array
     */
    protected $config = [];

    protected $cache;

    protected $proxies;

    public function __construct()

    /**
     * Add multiple requests
     *
     * @param array  $urls
     * @param string $method
     */
    public function addRequests(array $urls)
    {
        foreach ($urls as $url) {
            $this->addRequest($url);
        }
    }

    /**
     * Create a new request and add it to the queue
     *
     * @param string $url
     * @param string $method
     * @param array  $options
     */
    public function addRequest($url, $options = [])
    {
        if (! $this->checkRequest()) {
            return false;
        }

        $this->add($newRequest);

        return true;
    }

    /**
     * Check to see if we're breaking limits
     *
     * @return bool
     */
    public function checkRequest()
    {
        if ($this->config['max_requests'] > 0 and $this->numRequests >= $this->config['max_requests']) {
            return false;
        }

        if (! $this->index->checkUrl($url)) {
            return false;
        }

        return true;
    }

    /**
     * Execute RollingCurl if this isn't running
     *
     * @return void
     */
    protected function crawlUrls()
    {
        if(empty($this->pendingRequests)) {
            $this->execute();
        }
    }

    /**
     * Get a new Request's curl options
     *
     * @return array
     */
    public function getRequestOptions()
    {
        return [
            UserAgent::generate(),
            $this->proxies->setProxy(),
        ];
    }

    /**
     * Execute RollingCurl if this isn't running
     *
     * @return void
     */
    public function crawlUrls()
    {
        if (empty($this->pendingRequests)) {
            $this->execute();
        }
    }

    protected function finalizeCrawl()
    {
        echo 'Requests pending: ' . $this->countPending() . '<br />';
        echo 'Requests completed: ' . $this->countCompleted() . '<br />';
        echo 'Requests active: ' . $this->countActive() . '<br />';
        echo 'Total Emails grabbed: ' . $this->emailParser->count() . '<br />';
        echo 'Total URLs grabbed: ' . $this->index->count() . '<br />';
    }
}
