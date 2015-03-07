<?php Braseidon\Scraper\Parser;

class UrlBuilderFactory
{

    /**
     * Create UrlBuilderFactory
     *
     * @param  string     $targetUrl The seed URL to be scraped
     * @return UrlBuilder The UrlBuilder object
     */
    public static function create($targetUrl)
    {
        return new UrlBuilder($targetUrl);
    }
}
