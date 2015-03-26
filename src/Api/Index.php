<?php namespace Braseidon\Mole\Api;

class Index implements CacheInterface
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
     * Adds an item if it isn't indexed
     *
     * @param string $item
     */
    public function add($item)
    {
        $item = $this->clean($item);

        if (! $this->check($item)) {
            $this->index[$item] = true;
        }
    }

    /**
     * Checks if an item is indexed
     *
     * @param string $item
     */
    public function check($item)
    {
        $item = $this->clean($item);

        if (isset($this->index[$item])) {
            return true;
        }

        return false;
    }

    /**
     * Clean the URL for consistent index checking
     *
     * @param  string $item
     * @return string
     */
    public function clean($item)
    {
        return trim(strtolower($item));
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
