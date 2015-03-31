<?php namespace Braseidon\Mole\Api;

use Braseidon\Mole\Traits\UsesConfig;
use DB;

use InvalidArgumentException;

class Index implements IndexInterface
{

    use UsesConfig;

    /**
     * @var string The type of index this instance is
     */
    public $indexType = 'internal';

    protected $table = [
        'table'         => 'mole_index',
        'column'        => 'url',
        'crawled'       => 'crawled',
        'increments'    => 'crawl_count',
    ];

    /**
     * The parsed target domain
     *
     * @var string
     */
    protected $domain = [];

    /**
     * Instantiate the Object
     *
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        if (! isset($this->table) || ! is_array($this->table)) {
            throw new InvalidArgumentException('The variable `table` must be set to an array with parameters save to the database.');
        }

        $this->mergeOptions($config);

        $this->cache = \App::make('cache');
    }

    /*
    |--------------------------------------------------------------------------
    | Index - Main Actions
    |--------------------------------------------------------------------------
    |
    |
    */

    /**
     * Check the cache for the URL, then the database
     *
     * @param string $url
     * @return bool Returns true if URL exists in cache
     */
    public function has($url)
    {
        $url = $this->clean($url);

        // if ($this->cache->tags($this->domain['domain_plain'])->has($url)) {
        //     return true;
        // }

        if ($this->checkDB($url)) {
            return true;
        }

        return false;
    }

    /**
     * Adds a URL that has been crawled
     *
     * @param string $url
     */
    public function add($url)
    {
        if (empty($url)) {
            throw new InvalidArgumentException('You cannot index `url` when it is empty.');
        }

        $url = $this->clean($url);

        // $this->cache->tags($this->domain['domain_plain'])->put($url, true, $this->getOption('cache_time'));

        $check = $this->isPageIndexed($url);

        if ($check->count() == 0) {
            $this->addToDB($url);
        } else {
            $this->incrementDB($url);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Index - Cache
    |--------------------------------------------------------------------------
    |
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Index - Database
    |--------------------------------------------------------------------------
    |
    |
    */

    /**
     * See if a URL is indexed for the domain
     *
     * @param  string  $string
     * @return boolean
     */
    private function isPageIndexed($string)
    {
        return DB::table($this->table['table'])
            ->where('target', '=', $this->domain['domain_plain'])
            ->where('url', '=', $string);
    }

    /**
     * Check if a URL exists in the database
     *
     * @param  string $string
     * @return bool
     */
    private function checkDB($string)
    {
        $check = $this->isPageIndexed($string);
        $check = $check->where('crawled', '=', 1);
        // dd($string . ': ' . $check->count());

        if ($check->count() > 0) {
            return true;
        }

        $this->addToDB($string, true);

        return false;
    }

    /**
     * Add a scraped item to the database
     *
     * @param string $string
     * @return void
     */
    private function addToDB($string, $dontIncrement = false)
    {
        $check = $this->isPageIndexed($string)->count();

        if (empty($check)) {
            $this->addToDBInsert($string);
        } elseif (count($check) == 1 && $dontIncrement === false) {
            $this->incrementDB($string);
        } elseif (count($check) > 1) {
            throw new \Exception('Looks like there are duplicate URLs in the database.');
        }
    }

    /**
     * Insert a fresh row into the database
     *
     * @param string $string
     */
    private function addToDBInsert($string)
    {
        DB::table($this->table['table'])
            ->insert([
                'target'    => $this->domain['domain_plain'],
                'url'       => $string
            ]);
        // dd($string);
    }

    /**
     * Update the DB for a pageview
     *
     * @param  integer $id
     * @return void
     */
    private function incrementDB($string)
    {
        DB::table($this->table['table'])
            ->where('target', '=', $this->domain['domain_plain'])
            ->where('url', '=', $string)
            ->increment('crawl_count', 1, ['crawled' => 1]);
    }

    /**
     * Get un-crawled URL's when needed
     *
     * @param  integer $num
     * @return array
     */
    public function getUncrawledUrls($num = 100)
    {
        return DB::table($this->table['table'])
            ->where('target', '=', $this->domain['domain_plain'])
            ->where('crawled', '=', 0)
            ->where('crawl_count', '=', 0)
            ->limit($num)
            ->lists('url');
    }

    /**
     * Returns the count of URLs
     *
     * @return integer
     */
    public function count()
    {
        return DB::table($this->table['table'])->select('id')->count();
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

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    |
    |
    */

    /**
     * Clean the URL for consistent index checking
     *
     * @param  string $item
     * @return string
     */
    public function clean($item)
    {
        return trim($item); // strtolower() - can mess up urls that are case sensitive
    }

    /**
     * Set the domain we're crawling
     *
     * @param string $url
     */
    public function setDomain($domain)
    {
        $this->domain = $domain;
    }
}
