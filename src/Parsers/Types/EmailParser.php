<?php namespace Braseidon\ShutterScraper\Parsers\Types;

class EmailParser extends AbstractParser implements ParserTypeInterface {

	/**
	 * Regex pattern
	 *
	 * @var string
	 */
	protected $pattern = '/[a-z0-9_\-\+]+@[a-z0-9\-]+\.([a-z]{2,3})(?:\.[a-z]{2})?/i';
	// protected $pattern = '/^.+@.+\..+$/';

}