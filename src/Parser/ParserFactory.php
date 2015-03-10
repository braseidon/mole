<?php namespace Braseidon\Mole\Parser;

use Braseidon\Mole\Cache\WebCache;
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
        return new Parser($this->getCache());
    }

    /**
     * Get the Cache object
     *
     * @return Cache
     */
    protected function getCache()
    {
        return new WebCache();
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
