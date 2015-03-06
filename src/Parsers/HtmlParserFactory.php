<?php namespace Braseidon\Scraper\Parsers;

class HtmlParserFactory {

	/**
	 * Configuration
	 *
	 * @var array $config
	 */
	protected $config;

	/**
	 * Create HtmlParserFactory object
	 *
	 * @param array $config
	 */
	public function __construct(array $config = [])
	{
		$this->config = $config;
	}

	/**
	 * Create HtmlParser object
	 *
	 * @return HtmlParser
	 */
	public function getHtmlParser()
	{
		return new HtmlParser($this->config);
	}

	/**
	 * Instantiate ParserFactory
	 *
	 * @return ParserFactory
	 */
	public function getParser($type = '')
	{
		return ParserFactory::create($this->config);
	}

	/**
	 * Create HtmlParserFactory object
	 *
	 * @param  array       $config
	 * @return HtmlParser
	 */
	public static function create($type, array $config = [])
	{
		return (new self($config));
	}
}