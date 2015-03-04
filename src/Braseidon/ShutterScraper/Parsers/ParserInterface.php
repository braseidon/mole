<?php namespace Braseidon\ShutterScraper\Parsers;

interface ParserInterface {

	/**
	 * Finds matches in the HTML
	 *
	 * @param  string $html
	 * @return bool
	 */
	public function findMatches($html);
}