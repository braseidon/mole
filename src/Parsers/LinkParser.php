<?php namespace Braseidon\ShutterScraper\Parsers;

class LinkParser extends AbstractParser implements ParserInterface {

	/**
	 * Regex pattern
	 *
	 * @var string
	 */
	protected $pattern = '/href="([^#"]*)"/i';

	protected function findMatches($html)
	{
		// Parse - URL's
		if(preg_match_all($pattern, $html, $urlMatches, PREG_PATTERN_ORDER))
		{
			$urlMatches = array_unique($urlMatches[1]);

			foreach ($urlMatches as $k => $link)
			{
				if(! $link = $this->parseLink($link))
				{
					continue;
				}
			}

			// Garbage collect
			unset($urlMatches);
		}
	}

	/**
	 * Sends a link through various checks to add it to the request queue
	 *
	 * @param  string $link
	 * @return string|bool
	 */
	protected function parseLink($link)
	{
		$link = trim($link);

		if(strlen($link) === 0)
		{
			$link = '/';
		}

		if(! $this->checkBlockedStrings($link))
		{
			return false;
		}

		if($this->maxDepth > 0 && strpos($link, 'http') === false && substr_count($link, '/') > $this->maxDepth)
		{
			return false;
		}

		if(strpos($link, 'http') === false && strpos($link, '/') === 0)
		{
			$link = $this->targetDomain . $link;
		}
		elseif(strpos($link, 'http') === false && strpos($link, '/') === false)
		{
			if(strpos($link, 'www.') !== false)
			{
				return false;
			}

			$link = $this->targetDomain . '/' . $link;
		}
		elseif(strpos($link, 'mailto:') !== false)
		{
			return false;
		}
		elseif(strpos($link, $this->targetDomain) === false)
		{
			return false;
		}

		// Add URL as request
		$this->addMatch($link);

		return $link;
	}
}