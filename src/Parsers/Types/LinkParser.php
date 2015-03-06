<?php namespace Braseidon\Scraper\Parsers\Types;

use RollingCurl\Request;

class LinkParser extends AbstractParser implements ParserTypeInterface {

	/**
	 * Regex pattern
	 *
	 * @var string
	 */
	protected $pattern = '/href="([^#"]*)"/i';

	/**
	 * Folder depth limit for crawling
	 *
	 * @var integer
	 */
	protected $maxDepth = 8;

	/**
	 * The target domain
	 *
	 * @var string
	 */
	protected $targetDomain = null;

	/**
	 * Manually set the target domain
	 *
	 * @param string $targetDomain
	 */
	public function setTargetDomain($targetDomain)
	{
		$this->targetDomain = $targetDomain;
	}

	/**
	 * Get the target domain
	 *
	 * @param string $targetDomain
	 */
	public function getTargetDomain()
	{
		return $this->targetDomain;
	}

	public function findMatches($html)
	{
		// Parse - URL's
		if(preg_match_all($this->pattern, $html, $urlMatches, PREG_PATTERN_ORDER))
		{
			$urlMatches = array_unique($urlMatches[1]);

			foreach ($urlMatches as $k => $link)
			{
				if(! $link = $this->parseLink($link))
				{
					continue;
				}

				// Add URL as request
				$this->addMatch($link);
			}

			// Garbage collect
			unset($urlMatches, $html);
		}

		return $this->getMatches();
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
		// Check blocked strings
		if(! $this->checkBlockedStrings($link))
		{
			return false;
		}
		// Check depth vs our specified max depth
		if($this->maxDepth > 0 && strpos($link, 'http') === false && substr_count($link, '/') > $this->maxDepth)
		{
			return false;
		}
		// Links without the domain, begins with slash
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
		// Email links
		elseif(strpos($link, 'mailto:') !== false)
		{
			return false;
		}
		// Links that aren't on the target domain
		elseif(strpos($link, $this->targetDomain) === false)
		{
			return false;
		}

		return $link;
	}

	/**
	 * Check the link against blocked strings
	 *
	 * @param  string $link
	 * @return bool
	 */
	protected function checkBlockedStrings($link)
	{
		if(empty($this->blockedArr))
		{
			return true;
		}

		foreach($this->blockedArr as $blocked)
		{
			if(strpos($link, $blocked) !== false)
			{
				return false;
			}
		}

		return true;
	}
}