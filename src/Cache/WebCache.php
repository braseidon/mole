<?php namespace Braseidon\Mole\Cache;

class WebCache implements WebCacheInterface
{

    /**
     * @var string The type of cache this instance is
     */
    public $cacheType = '';

    /**
     * @var array Handles checking for first page requests
     */
    protected $cache = [];

    /**
     * @var integer The max items to have in a batch
     */
    protected $max = 1000;

    protected $batchCount = 0;

    /**
     * Adds a URL if it isn't cached
     *
     * @param string $url
     */
    public function add($url)
    {
        $url = $this->clean($url);

        if (! $this->check($url)) {
            $this->cache[$url] = true;
            $this->batchCount++;
        }
    }

    /**
     * Checks if a URL is cached
     *
     * @param string $url
     */
    public function check($url)
    {
        $url = $this->clean($url);

        if (isset($this->cache[$url])) {
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
     * Returns the count of URLs
     *
     * @return integer
     */
    public function count()
    {
        return count($this->cache);
    }

    /**
     * Return the URL array
     *
     * @return arrays
     */
    public function all()
    {
        return $this->cache;
    }
}
