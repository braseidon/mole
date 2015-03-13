<?php namespace Braseidon\Mole;

use Braseidon\Mole\Api\Index;
use Braseidon\Mole\Http\Proxy;
use Braseidon\Mole\Parser\Parser;
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
            $this->getAllOptions()
        );

        $crawler->setIndex($this->getIndex());
        $crawler->setParser($this->getParser());
        $crawler->setProxy($this->getProxy());

        return $crawler;
    }

    /**
     * @return Index The Index object
     */
    public function getIndex()
    {
        return new Index();
    }

    /**
     * @return Parser The Parser object
     */
    public function getParser()
    {
        return new Parser($this->getAllOptions());
    }

    /**
     * @return Proxy The Proxy object
     */
    public function getProxy()
    {
        return new Proxy($this->getOption('proxy_list_path'));
    }

    /**
     * @return Cache The Cache object
     */
    public function getCache()
    {
        return new Cache();
    }

    /*
    |--------------------------------------------------------------------------
    | Getters - Options
    |--------------------------------------------------------------------------
    |
    |
    */



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
