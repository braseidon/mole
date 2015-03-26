<?php namespace Braseidon\Mole\Parser\Types;

use Exception;

abstract class AbstractParser
{
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
     * Array of blocked strings
     *
     * @var array
     */
    protected $blockedArr = [];

    /**
     * The target domain
     *
     * @var string
     */
    protected $targetDomain = null;

    /**
     * The regex pattern
     *
     * @var string
     */
    protected $pattern;

    /**
     * Sets the The regex pattern.
     *
     * @param string $pattern The pattern
     * @return self
     */
    protected function setPattern($pattern)
    {
        $this->pattern = $pattern;

        return $this;
    }

    /**
     * Gets the The regex pattern.
     *
     * @return string
     */
    public function getPattern()
    {
        return $this->pattern;
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
     * Check the item against blocked strings
     *
     * @param  string $item
     * @return bool
     */
    protected function checkBlockedStrings($item)
    {
        if (empty($this->blockedArr)) {
            return true;
        }

        foreach ($this->blockedArr as $blocked) {
            if (strpos($item, $blocked) !== false) {
                return false;
            }
        }

        return true;
    }

    /**
     * Finds matches in the HTML
     *
     * @param  string $html
     * @return bool
     */
    public function pregMatch($html)
    {
        if (! isset($this->pattern)) {
            throw new Exception('You must have a pattern set in your parser!');
        }

        $rawMatches = [];

        if (preg_match_all($this->pattern, $html, $rawMatches, PREG_PATTERN_ORDER)) {
            $rawMatches = array_unique($rawMatches[0]);

            foreach ($rawMatches as $match) {
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
        if (! $this->checkMatch($string)) {
            $this->matches[$string] = true;
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
        if (isset($this->matches[$string])) {
            return true;
        }

        return false;
    }

    /**
     * Returns the count of matches
     *
     * @return integer
     */
    public function count()
    {
        return count($this->getMatches());
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
    }
}
