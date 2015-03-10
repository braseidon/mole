<?php namespace Braseidon\Mole;

use Braseidon\Mole\Parser\ParserFactory;
use Braseidon\Mole\Traits\UsesConfig;

class CrawlerFactory
{
    use UsesConfig;

    /**
     * Create CrawlerFactory object.
     *
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        $this->mergeOptions($config);
    }

    /*
    |--------------------------------------------------------------------------
    | Getters - Objects
    |--------------------------------------------------------------------------
    |
    |
    */

    /**
     * Create Crawler object.
     *
     * @return Crawler
     */
    public function getCrawler()
    {
        $crawler = new Crawler(
            $this->getWorker()
        );

        $crawler->mergeOptions($this->getOptions());
        $crawler->setThreads($this->getThreads());
        $crawler->setRequestLimit($this->getRequestLimit());
        $crawler->setIgnoredFileTypes($this->getIgnoredFiletypes());

        return $crawler;
    }

    /**
     * Create Worker object.
     *
     * @return Worker
     */
    public function getWorker()
    {
        $worker = new Worker(
            $this->getParser(),
            $this->getOptions()
        );

        if ($this->hasOption('proxy_list_path')) {
            $worker->importProxies($this->getOption('proxy_list_path'));
        }

        return $worker;
    }

    /**
     * Create ParserFactory object.
     *
     * @return ParserFactory
     */
    public function getParser()
    {
        return ParserFactory::create($this->getOptions());
    }

    /**
     * Get the WebCache object
     *
     * @return WebCache
     */
    public function getWebCache()
    {
        return new WebCache();
    }

    /*
    |--------------------------------------------------------------------------
    | Getters - Options
    |--------------------------------------------------------------------------
    |
    |
    */

    /**
     * Get the request Callback.
     *
     * @return string
     */
    public function getThreads()
    {
        $threads = 2;

        if (! $this->hasOption('threads')) {
            $this->setOption('threads', $threads);
        }

        return $this->getOption('threads');
    }

    /**
     * Get the request limit or set default.
     *
     * @return integer
     */
    public function getRequestLimit()
    {
        $requestLimit = 2;

        if (! $this->hasOption('request_limit')) {
            $this->setOption('request_limit', $requestLimit);
        }

        return $this->getOption('request_limit');
    }

    /**
     * Return the file extensions to ignore when crawling
     *
     * @return array
     */
    public function getIgnoredFiletypes()
    {
        $ignoredFiletypes = ['.css', '.doc', '.gif', '.jpeg', '.jpg', '.js', '.pdf', '.png'];

        if (! $this->hasOption('ignored_filetypes')) {
            $this->setOption('ignored_filetypes', $ignoredFiletypes);
        }

        return $this->getOption('ignored_filetypes');
    }

    /**
     * Create Crawler object.
     *
     * @param array $config
     *
     * @return Crawler
     */
    public static function create(array $config = [])
    {
        return (new self($config))->getCrawler();
    }
}
