<?php namespace Braseidon\Mole\Parser;

use Braseidon\Mole\Api\UrlCache;
use Braseidon\Mole\Parser\Types\Emails;
use Braseidon\Mole\Parser\Types\ExternalLinks;
use Braseidon\Mole\Parser\Types\InternalLinks;
use Braseidon\Mole\Traits\UsesConfig;

class ParserFactory
{
    use UsesConfig;

    /**
     * Create ParserFactory
     *
     * @param array $config Configuration parameters.
     */
    public function __construct(array $config = [])
    {
        $this->mergeOptions($config);
    }

    /**
     * Get the Parser object
     *
     * @return Parser
     */
    protected function getParser()
    {
        $parser = new Parser(
            $this->getAllOptions(),
            $this->getUrlCache(),
            $this->getInternalLinks($this->getAllOptions()),
            $this->getParserTypes()
        );

        return $parser;
    }

    /**
     * Get the UrlCache object
     *
     * @return UrlCache
     */
    protected function getUrlCache()
    {
        return new UrlCache();
    }

    /**
     * Get the InternalLinks object
     *
     * @return InternalLinks
     */
    protected function getInternalLinks()
    {
        return new InternalLinks();
    }

    /**
     * Returns the Parser types
     *
     * @return array
     */
    public function getParserTypes()
    {
        return [
            // new ExternalLinks(),
            new Emails()
        ];
    }

    /**
     * Create a Parser instance
     *
     * @param  array $config
     * @return Parser
     */
    public static function create($config = [])
    {
        return (new self($config))->getParser();
    }
}
