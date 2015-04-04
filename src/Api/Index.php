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
     * Newly added URLs go here
     *
     * @var array
     */
    protected $requestCache = [];

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

        // Turn off query logging
        DB::connection()->disableQueryLog();
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

        if ($this->checkIfFirstRequest($url)) {
            return true;
        }

        return false;
    }

    /**
     * Adds a URL that has been crawled
     *
     * @param string $url
     */
    public function add($url, $data = [])
    {
        if (empty($url)) {
            throw new InvalidArgumentException('You cannot index `url` when it is empty.');
        }

        $url = $this->clean($url);

        // $this->cache->tags($this->domain['domain_plain'])->put($url, true, $this->getOption('cache_time'));

        $check = $this->isPageIndexed($url);

        if ($check->count() == 0) {
            $this->addToDB($url, $data);
        } else {
            $this->incrementDB($url, $data);
        }
    }

    public function attempted($url, $httpCode)
    {
        $data = [
            'last_http_code' => $httpCode
        ];

        $this->attemptedUrl($url, $data);
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

    public function all()
    {
        return DB::table($this->table['table'])->all();
    }

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
    private function checkIfFirstRequest($string)
    {
        $check = $this->isPageIndexed($string);
        $check = $check->where('crawled', '=', 1);
        // dd($string . ': ' . $check->count());

        if ($check->count() > 0) {
            return true;
        }

        $this->addToDB($string, [], true);

        return false;
    }

    /**
     * Add a scraped item to the database
     *
     * @param string $string
     * @return void
     */
    private function addToDB($string, $data = [], $dontIncrement = false)
    {
        $check = $this->isPageIndexed($string)->count();

        if ($check == 0) {
            $this->addToDBInsert($string);
        } elseif (count($check) == 1 && $dontIncrement === false) {
            $this->incrementDB($string, $data);
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
     * @param  string $string
     * @return void
     */
    private function incrementDB($string, array $data)
    {
        $data['crawled'] = 1;

        DB::table($this->table['table'])
            ->where('target', '=', $this->domain['domain_plain'])
            ->where('url', '=', $string)
            ->increment('crawl_count', 1, $data);
    }

    /**
     * Update the HTTP attempts and HTTP code of a URL
     *
     * @param  string $url
     * @param  array $data
     * @return void
     */
    private function attemptedUrl($url, $data)
    {
        DB::table($this->table['table'])
            ->where('target', '=', $this->domain['domain_plain'])
            ->where('url', '=', $url);
    }

    /*
    |--------------------------------------------------------------------------
    | Request Cache
    |--------------------------------------------------------------------------
    |
    |
    */

    /**
     * Add a URL to the cache array
     *
     * @param  string $url
     * @return void
     */
    public function cacheRequest($url)
    {
        $this->requestCache[$url] = true;
    }

    /**
     * Check if the request cache has a URL
     *
     * @param  string $url
     * @return void
     */
    public function cacheHasRequest($url)
    {
        if (isset($this->requestCache[$url])) {
            return true;
        }

        return false;
    }

    /**
     * After incrementing row, unset the URL from the cache array
     *
     * @param  string $url
     * @return void
     */
    public function cacheUnsetRequest($url)
    {
        unset($this->requestCache[$url]);
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    |
    |
    */

    /**
     * Get un-crawled URL's when needed
     *
     * @param  integer $num
     * @return array
     */
    public function getUncrawledUrls($num = 100)
    {
        $num = ($num > 20 || $num < 1) ? 20 : $num;

        return DB::table($this->table['table'])
            ->where('target', '=', $this->domain['domain_plain'])
            ->where('crawled', '=', 0)
            ->where('crawl_count', '=', 0)
            ->orderByRaw("RAND()")
            ->limit($num)
            ->lists('url');
    }

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
