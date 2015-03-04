<?php namespace Braseidon\ShutterScraper\Parsers;

class EmailParser extends AbstractParser implements ParserInterface {

	/**
	 * Regex pattern
	 *
	 * @var string
	 */
	protected $pattern = '/[a-z0-9_\-\+]+@[a-z0-9\-]+\.([a-z]{2,3})(?:\.[a-z]{2})?/i';

}