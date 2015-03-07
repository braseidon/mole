<?php namespace Braseidon\Scraper\Parser;

use RollingCurl\Request;
use RollingCurl\RollingCurl;

class Parser {

	protected $linkParser;

	protected $

	/**
	 * Instantiate the Parser object
	 *
	 * @param LinkParser  $linkParser
	 * @param EmailParser $emailParser
	 */
	public function __construct(LinkParser $linkParser, EmailParser $emailParser)
	{
		$this->setLinkParser($linkParser);
		$this->setEmailParser($emailParser);
	}

	/**
	 * Process the returned HTML with our parsers
	 *
	 * @param  Request     $request
	 * @param  RollingCurl $rolling_curl
	 * @return [type]
	 */
	public function parseHtml(Request $request, RollingCurl $rolling_curl)
	{
		$response	= $request->getResponseInfo();
		$url		= $request->getUrl();
		$http_code	= array_get($request->getResponseInfo(), 'http_code', false);
		$html		= $request->getResponseText();

		// Add URL to index (or update count)
		$this->index->addUrl($url);

		if($http_code >= 200 and $http_code < 400 and ! empty($html))
		{
			// Parse - Links
			$this->linkParser->findMatches($html);

			// Parse - Emails
			$this->emailParser->findMatches($html);

			// Garbage collect
			unset($html);

			// Crawl any newly found URLs
			$this->crawlUrls();
		}
	}
}