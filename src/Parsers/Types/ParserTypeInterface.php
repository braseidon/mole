<?php namespace Braseidon\ShutterScraper\Parsers\Types;

interface ParserTypeInterface {

	/**
	 * Finds matches in the HTML
	 *
	 * @param  string $html
	 * @return bool
	 */
	public function findMatches($html);
}