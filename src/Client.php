<?php namespace Braseidon\Scraper;

use Exception;

class Client
{

    /**
     * Crawler parameters.
     *
     * @var array
     */
    protected $crawler;

    /**
     *  Instantiate the Object
     */
    public function __construct(Crawler $crawler)
    {
        $this->setCrawler($crawler);
    }

    /**
     * Instantiate the Crawler
     *
     * @param Crawler
     */
    public function setCrawler(Crawler $crawler)
    {
        $this->crawler = $crawler;
    }

    /**
     * Get the Crawler
     *
     * @return Crawler
     */
    public function getCrawler()
    {
        return $this->crawler;
    }
}
