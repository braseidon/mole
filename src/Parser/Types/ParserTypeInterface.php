<?php namespace Braseidon\Scraper\Parser\Types;

interface ParserTypeInterface
{

    /**
     * Finds matches in the HTML
     *
     * @param  string $html
     * @return bool
     */
    public function findMatches($html);
}
