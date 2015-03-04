<?php namespace Braseidon\ShutterScraper\Parsers;

use Exception;

abstract class AbstractParser {

	/**
	 * Array of matches found
	 *
	 * @var array $matches
	 */
	protected $matches = [];

	/**
	 * The max items to have in a batch
	 *
	 * @var integer
	 */
	protected $batchMax = 1000;

	/**
	 * The number of items in this batch
	 *
	 * @var integer
	 */
	protected $batchCount = 0;

	/**
	 * Instantiate the Object
	 */
	public function __construct()
	{
		if(! isset($this->pattern))
			throw new Exception('You must have a pattern set in your parser!');
	}

	/**
	 * Finds matches in the HTML
	 *
	 * @param  string $html
	 * @return bool
	 */
	public function findMatches($html)
	{
		$rawMatches = [];

		if(preg_match_all($this->pattern, $html, $rawMatches, PREG_PATTERN_ORDER))
		{
			$rawMatches = array_unique($rawMatches[0]);

			foreach($rawMatches as $match)
			{
				$this->addMatch($match);
			}
		}
	}

	/**
	 * Add a new match to the array
	 *
	 * @param string $string
	 */
	public function addMatch($string)
	{
		if(! $this->checkMatch($string))
		{
			$this->matches[$string] = true;
			$this->batchCount++;
		}
	}

	/**
	 * Check if a match is already in the array
	 *
	 * @param  string $string
	 * @return bool
	 */
	public function checkMatch($string)
	{
		if(isset($this->matches[$string]))
			return true;

		return false;
	}

	/**
	 * Return the matches
	 *
	 * @return array
	 */
	public function getMatches()
	{
		return $this->matches;
	}

	/**
	 * Returns the count of matches
	 *
	 * @return integer
	 */
	public function count()
	{
		return count($this->matches);
	}

	/**
	 * Stores the current batch of matches
	 *
	 * @return [type]
	 */
	public function store()
	{
		// database/file/etc store

		$this->matches = [];
		$this->batchCount = 0;
	}
}