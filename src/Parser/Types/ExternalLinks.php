<?php namespace Braseidon\Mole\Parser\Types;

class ExternalLinks extends AbstractParser implements ParserTypeInterface
{

    /**
     * Regex pattern
     *
     * @var string
     */
    protected $pattern = '/href="([^#"]*)"/i';

    /**
     * Folder depth limit for crawling
     *
     * @var integer
     */
    protected $maxDepth = 0;

    /**
     * Runs the parser
     *
     * @param  string $html
     * @return array
     */
    public function run($html)
    {
        // Parse - URL's
        if (preg_match_all($this->pattern, $html, $matches, PREG_PATTERN_ORDER)) {
            $matches = array_unique($matches[1]);

            foreach ($matches as $k => $link) {
                if (! $link = $this->parse($link)) {
                    continue;
                }

                // Add URL as request
                $this->addMatch($link);
            }

            // Garbage collect
            unset($matches, $html);
        }

        return $this->getMatches();
    }

    /**
     * Parse an item, deciding whether to keep it
     *
     * @param  string $item
     * @return string $item
     */
    public function parse($item)
    {
        $item = trim(urldecode($item));

        if (strlen($item) === 0) {
            $item = '/';
        }

        // Check blocked strings
        if ($this->checkBlockedStrings($item)) {
            return false;
        }

        // Don't allow more than maxDepth forward slashes in the URL
        if ($this->getOption('max_depth', 0) > 0 && strpos($item, 'http') === false && substr_count($item, '/') > $this->getOption('max_depth', 0)) {
            return false;
        }
        if (strpos($item, 'http') === false && strpos($item, '/') === 0) {              // Check for a relative path starting with a forward slash
            $item = $this->domain['domain_full'] . $item;                               // Prefix the full domain
        } elseif (strpos($item, 'http') === false && strpos($item, '/') === false) {    // Check for a same directory reference
            if (strpos($item, 'www.') !== false) {
                continue;
            }
            $item = $this->domain['domain_full'] . '/' . $item;
        } elseif (strpos($item, 'mailto:') !== false) {                                 // Dont index email addresses
            // $this->parser->addMatch(str_replace('mailto:', '', $item));
            return false;
        } elseif (strpos($item, $this->domain['domain_plain']) === false) {             // Skip item if it isnt on the same domain
            return false;
        }

        return $item;
    }

    /**
     * Add the item to the database if it doens't exist
     *
     * @param  string $item
     * @return bool
     */
    public function checkIndex($item)
    {
        //
    }
}
