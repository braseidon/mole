<?php namespace Braseidon\Mole\Parser;

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
            $this->getInternalLinks($this->getAllOptions()),
            $this->getEmails($this->getAllOptions())
        );

        return $parser;
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
     * Returns the Emails types
     *
     * @return Emails
     */
    public function getEmails()
    {
        return new Emails();
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
