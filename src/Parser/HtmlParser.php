<?php namespace Braseidon\Scraper\Parser;

use RollingCurl\Request;
use RollingCurl\RollingCurl;

class HtmlParser implements HtmlParserInterface
{

    /**
     * Config
     *
     * @var array
     */
    protected $config;

    /**
     * Visited URL index handler
     *
     * @var HtmlParserCache
     */
    protected $index;

    /**
     *  Instantiate the Object
     */
    public function __construct()
    {
    }

    /**
     * The callback for the requests
     *
     * @return function
     */
    public function callback(Request $request, RollingCurl $rollingCurl)
    {
        dd($request);
    }

    /**
     * Parse the HTML
     *
     * @return string $html
     */
    public function parseHtml($html)
    {
        //
    }
}
