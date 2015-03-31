<?php namespace Braseidon\Mole\Parser\Types;

interface ParserTypeInterface
{

    /**
     * Finds matches in the HTML
     *
     * @param  string $html
     * @return bool
     */
    // public function run($html);

    /**
     * Preg Match the HTML for a pattern
     *
     * @param  string $html
     * @return array|null
     */
    public function pregMatch($html);

    /**
     * Parse an item, deciding whether to keep it
     *
     * @param  string $item
     * @return string $item
     */
    public function parse($item);

    /**
     * Checks if the matched item is in the database
     *
     * @param  string $item
     * @return bool
     */
    // public function has($item);

    // public function add($item);
}
