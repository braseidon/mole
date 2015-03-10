<?php namespace Braseidon\Scraper\Cache;

class WebCache implements WebCacheInterface
{

    /**
     * Handles checking for first page requests
     *
     * @var array
     */
    protected $cache = [];

    /**
     * The max items to have in a batch
     *
     * @var integer
     */
    protected $max = 1000;

    /**
     * Checks if a URL is cached
     *
     * @param string $url
     */
    public function check($url)
    {
        $url = $this->clean($url);

        if (isset($this->cacheHistory[$url])) {
            return true;
        }

        return false;
    }

    /**
     * Adds a URL if it isn't cached
     *
     * @param string $url
     */
    public function add($url)
    {

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
     * Returns the count of URLs
     *
     * @return integer
     */
    public function count()
    {
        return count($this->cacheHistory);
    }

    /**
     * Return the URL array
     *
     * @return arrays
     */
    public function all()
    {
        return $this->cacheHistory;
    }
}
