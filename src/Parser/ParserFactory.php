<?php namespace Braseidon\Mole\Parser;

use Braseidon\Mole\Cache\WebCache;
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
            $this->getParserTypes()
        );

        return $parser;
    }

    /**
     * Get the Cache object
     *
     * @return Cache
     */
    protected function getCache()
    {
        return new Cache();
    }

    public function getParserTypes()
    {
        return [
            new InternalLinks(),
            new ExternalLinks(),
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
