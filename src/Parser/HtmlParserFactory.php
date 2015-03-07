<?php namespace Braseidon\Scraper\Parser;

class HtmlParserFactory
{

    /**
     * Configuration
     *
     * @var array $config
     */
    protected $config;

    /**
     * Create HtmlParserFactory object
     *
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        $this->config = $config;
    }

    /**
     * Create HtmlParser object
     *
     * @return HtmlParser
     */
    public function getHtmlParser()
    {
        return new HtmlParser();
    }

    /**
     * Instantiate ParserFactory
     *
     * @return ParserFactory
     */
    public function getParser($type = '')
    {
        return ParserFactory::create($this->config);
    }

    /**
     * Create HtmlParserFactory object
     *
     * @param  array       $config
     * @return HtmlParser
     */
    public static function create(array $config = [])
    {
        return (new self($config));
    }
}
