<?php namespace Braseidon\Scraper\Parser;

class ParserFactory
{

    /**
     * Configuration
     *
     * @var array
     */
    protected $config;

    /**
     * The parser for links
     *
     * @var LinkParser
     */
    protected $linkParser;

    /**
     * The parser for emails
     *
     * @var EmailParser
     */
    protected $emailParser;

    /**
     * Create ParserFactory
     *
     * @param array $config Configuration parameters.
     */
    public function __construct(array $config = [])
    {
        $this->config = $config;
    }

    /**
     * Create ParserFactory instance
     *
     * @param  array $config Configuration parameters.
     * @return Parser   The configured Parser.
     */
    public static function create(array $config = [])
    {
        return (new self($config))->getParser();
    }
}
