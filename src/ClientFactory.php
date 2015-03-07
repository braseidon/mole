<?php namespace Braseidon\Scraper;

class ClientFactory
{
    /**
     * Configuration.
     *
     * @var array
     */
    protected $config;

    /**
     * Create ClientFactory object.
     *
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        $this->config = $config;
    }

    /**
     * Create Client object.
     *
     * @return Client
     */
    public function getClient()
    {
        $client = new Client(
            $this->getCrawler()
        );

        $client->setCrawler($this->getCrawler());

        return $client;
    }

    /**
     * Instantiate the CrawlerFactory object.
     *
     * @return Crawler
     */
    public function getCrawler()
    {
        return CrawlerFactory::create($this->config);
    }

    /**
     * Create Client object.
     *
     * @param array $config
     *
     * @return Client
     */
    public static function create(array $config = [])
    {
        return (new self($config))->getClient();
    }
}
