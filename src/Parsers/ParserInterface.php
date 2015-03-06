<?php namespace Braseidon\Scraper\Parsers;

interface ParserInterface {

	/**
	 * Set the regex pattern for filtering content
	 *
	 * @param string
	 */
	public function setPattern($pattern);

	/**
	 * Finds matches in the HTML
	 *
	 * @param  string $html
	 * @return bool
	 */
	public function findMatches($html);
}