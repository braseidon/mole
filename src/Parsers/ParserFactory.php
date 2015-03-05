<?php namespace Braseidon\ShutterScraper\Parsers;

class ParserFactory {

	/**
	 * Configuration parameters.
	 * @var array
	 */
	protected $config;
	/**
	 * Create ParserFactory
	 *
	 * @param array $config Configuration parameters.
	 */
	public function __construct(array $config = [])
	{
		$this->config = $config;
	}

	/**
	 * Create ParserFactory instance
	 *
	 * @param  array $config Configuration parameters.
	 * @return Parser   The configured Parser.
	 */
	public static function create(array $config = [])
	{
		return (new self($config))->getParser();
	}
}