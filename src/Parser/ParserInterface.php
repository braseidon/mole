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


    public function pregMatch($pattern, $html);

    public function filterMatches($html);
}
