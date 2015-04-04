<?php namespace Braseidon\Mole\Index;

use Braseidon\Mole\Traits\UsesConfig;

class UrlCache implements CacheInterface
{
    use UsesConfig;

    /**
     * Uses Laravel's Cache manager
     *
     * @var CacheManager
     */
    protected $cache;

    /**
     * The max items to have in a batch
     *
     * @var integer
     */
    protected $batchMax = 1000;

    /**
     * Cache Tag - The domain we're scraping
     *
     * @var string
     */
    protected $domain = 'freeforeclosuredatabase.com';

    /**
     * Cache Tag - The data we're scraping
     *
     * @var string
     */
    protected $cachetag = 'crawlerlinks';

    /**
     * Instantiate the Object
     *
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        $this->mergeOptions($config);

        $this->cache = \App::make('cache');
    }

    /**
     * Checks if a URL is cached
     *
     * @param string $url
     * @return bool Returns true if URL exists in cache
     */
    public function has($url)
    {
        $url = $this->clean($url);

        return $this->cache->tags($this->domain)->has($url);
    }

    /**
     * Adds a URL if it isn't cached
     *
     * @param string $url
     */
    public function add($url)
    {
        $url = $this->clean($url);

        return $this->cache->tags($this->domain)->put($url, true, $this->getOption('cache_time'));
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
        return ($this->cache->command('LLEN', ['queues:' . Config::get('queue.connections.redis.queue')]));
    }
}
