<?php namespace Braseidon\Mole;

use Braseidon\Mole\Traits\UsesConfig;

class ClientFactory
{
    use UsesConfig;

    /**
     * Create ClientFactory object.
     *
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        $this->mergeOptions($config);
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

        // $client->setCrawler($this->getCrawler());

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
