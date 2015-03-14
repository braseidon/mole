<?php namespace Braseidon\Mole\Parser\Types;

interface ParserTypeInterface
{

    /**
     * Finds matches in the HTML
     *
     * @param  string $html
     * @return bool
     */
    public function run($html);

    /**
     * Parse an item, deciding whether to keep it
     *
     * @param  string $item
     * @return string $item
     */
    public function parse($item);
}
