<?php namespace Braseidon\Mole\Parser;

use RollingCurl\Request;
use RollingCurl\RollingCurl;

interface ParserInterface
{

    /**
     * Set the regex pattern for filtering content
     *
     * @param string
     */
    public function setPattern($pattern);

    /**
     * Uses PregMatch to filter the HTML
     *
     * @param  string $pattern
     * @param  string $html
     * @return array
     */
    public function matches($pattern, $html);

    /**
     * Filter the matches for specific results
     *
     * @param  string $html
     * @return string
     */
    public function filter($html);
}
