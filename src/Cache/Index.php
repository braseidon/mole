<?php namespace Braseidon\Mole;

class Index
{

    /**
     * Handles checking for first page requests
     *
     * @var array
     */
    protected $cacheHistory = [];

    /**
     * The max items to have in a batch
     *
     * @var integer
     */
    protected $batchMax = 1000;

    /**
     * The number of items in this batch
     *
     * @var integer
     */
    protected $batchCount = 0;

    /**
     * Adds a URL if it isn't cached
     *
     * @param string $url
     */
    public function addUrl($url)
    {
        $url = $this->clean($url);

        if (! $this->checkUrl($url)) {
            $this->cacheHistory[$url] = true;
            $this->batchCount++;
        }

        return false;
    }

    /**
     * Checks if a URL is cached
     *
     * @param string $url
     */
    public function checkUrl($url)
    {
        $url = $this->clean($url);

        if (isset($this->cacheHistory[$url])) {
            return true;
        }

        return false;
    }

    /**
     * Clean the URL for consistent cache checking
     *
     * @param  string $url
     * @return string
     */
    private function clean($url)
    {
        return str_ireplace(['http://', 'https://', 'www.'], '', rtrim($url, '/'));
    }

    /**
     * Return the URL array
     *
     * @return array
     */
    public function getUrls()
    {
        return $this->cacheHistory;
    }

    /**
     * Returns the count of URLs
     *
     * @return integer
     */
    public function count()
    {
        return count($this->cacheHistory);
    }
}
