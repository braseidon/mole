<?php namespace Braseidon\Scraper\Traits;

trait WorkerLikesCrawling
{

    /**
     * Begins the crawling process.
     *
     * @return void
     */
    public function execute()
    {
        $this->crawlUrls();
    }

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
    public function addRequest($url, $method = "GET", $options = [])
    {
        if ($this->maxRequests > 0 and $this->numRequests >= $this->maxRequests) {
            return false;
        }

        if (! $this->index->checkUrl($url)) {
            $request = new Request($url, $method);

            $request->addOptions($this->getRequestOptions());

            $this->index->addUrl($url);
            $this->numRequests++;

            return $this->add($request);
        }

        return false;
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
