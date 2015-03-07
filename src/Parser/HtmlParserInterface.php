<?php namespace Braseidon\Scraper\Parser;

use RollingCurl\Request;
use RollingCurl\RollingCurl;

interface HtmlParserInterface
{

    public function callback(Request $request, RollingCurl $rollingCurl);

    public function parseHtml($html);
}
