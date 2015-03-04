<?php namespace Braseidon\ShutterScraper\Parsers;

class UrlParser extends AbstractParser implements ParserInterface {

	/**
	 * Regex pattern
	 *
	 * @var string
	 */
	protected $pattern = '/href="([^#"]*)"/i';
}