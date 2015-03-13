<?php namespace Braseidon\Mole\Api;

class Index implements IndexInterface
{

    /**
     * @var string The type of index this instance is
     */
    public $indexType = '';

    /**
     * @var array Handles checking for first page requests
     */
    protected $index = [];

    /**
     * Adds a URL if it isn't indexd
     *
     * @param string $url
     */
    public function add($url)
    {
        $url = $this->clean($url);

        if (! $this->check($url)) {
            $this->index[$url] = true;
        }
    }

    /**
     * Checks if a URL is indexd
     *
     * @param string $url
     */
    public function check($url)
    {
        if (empty($url)) {
            return false;
        }

        $url = $this->clean($url);

        if (isset($this->index[$url])) {
            return true;
        }

        return false;
    }

    /**
     * Clean the URL for consistent index checking
     *
     * @param  string $url
     * @return string
     */
    public function clean($url)
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
        return count($this->index);
    }

    /**
     * Return the URL array
     *
     * @return arrays
     */
    public function all()
    {
        return $this->index;
    }
}
