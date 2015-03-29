<?php namespace Braseidon\Mole\Api;

use DB;

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

    protected $table = [
        'table'         => 'mole_index',
        'column'        => 'url',
        'crawled'       => 'crawled',
        'increments'    => 'crawl_count',
    ];

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

        if ($this->checkDB($string)) {
            $this->incrementDB($string);

            return false;
        }

        $this->addToDB($string);

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

    /**
     * Stores the current batch of matches
     *
     * @return bool
     */
    public function checkDB($string)
    {
        if (! isset($this->table) || ! is_array($this->table) || ! isset($this->table['column'])) {
            throw new InvalidArgumentException('The variable `table` must be set to an array with parameters save to the database.');
        }

        $check = DB::table($this->table['table'])->where($this->table['column'], '=', $string)->count();

        if ($check > 0) {
            return true;
        }

        return false;
    }

    public function addToDB($string)
    {
        DB::table($this->table['table'])->insert([
                $this->table['column'] => $string,
            ]);
    }

    public function incrementDB($string)
    {
        if (isset($this->table['increments'] && isset($this->table['crawled'])) {
            DB::table($this->table['table'])
                ->where($this->table['column'], '=', $string)
                ->increment($this->table['increments'], 1, ['crawled' => 1]);
        }
    }
}
