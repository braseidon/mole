<?php namespace Braseidon\Mole\Api;

class UrlCache implements CacheInterface
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
    protected $batchMax = 1000;

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

            return true;
        }

        return false;
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
    public function clean($url)
    {
        return str_ireplace(['http://', 'https://', 'www.'], '', rtrim($url, '/'));
    }

    /**
     * Return the URL array
     *
     * @return array
     */
    public function all()
    {
        return $this->cache;
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
}
