<?php namespace Braseidon\Mole\Parser\Types;

use Braseidon\Mole\Traits\UsesConfig;
use DB;
use Exception;
use InvalidArgumentException;

abstract class AbstractParser
{
    use UsesConfig;

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

    public function __construct(array $config = [])
    {
        $this->mergeOptions($config);
    }

    /**
     * Check the item against blocked strings
     *
     * @param  string $item
     * @return bool
     */
    protected function hasBlockedString($item)
    {
        if (empty($this->blockedArr)) {
            return true;
        }

        foreach ($this->blockedArr as $blocked) {
            if (strpos($item, $blocked) !== false) {
                return true;
            }
        }

        return false;
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
                if ($this->parse($match) !== false) {
                    $this->addMatch($match);
                }
            }
        }

        return $this->getMatches();
    }

    /**
     * Add a new match to the array
     *
     * @param string $string
     */
    public function addMatch($string)
    {
        if ($this->checkMatch($string)) {
            return false;
        }

        $this->matches[] = $string;
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
     * Clean the match for the database
     *
     * @param  string $string
     * @return string
     */
    public function clean($string)
    {
        return trim(strtolower($string));
    }
}
