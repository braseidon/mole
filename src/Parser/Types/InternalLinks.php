<?php namespace Braseidon\Mole\Parser\Types;

use RollingCurl\Request;

class InternalLinks extends AbstractParser implements ParserTypeInterface
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
     * Sends a link through various checks to add it to the request queue
     *
     * @param  string $link
     * @return string|bool
     */
    protected function parse($link)
    {
        $link = trim(urldecode($link));

        if (strlen($link) === 0) {
            $link = '/';
        }

        // Check blocked strings
        if ($this->hasIgnoredStrings($link)) {
            return false;
        }

        // Don't allow more than maxDepth forward slashes in the URL
        if ($this->getOption('max_depth', 0) > 0 && strpos($link, 'http') === false && substr_count($link, '/') > $this->getOption('max_depth', 0)) {
            return false;
        }
        if (strpos($link, 'http') === false && strpos($link, '/') === 0) {              // Check for a relative path starting with a forward slash
            $link = $this->domain['domain_full'] . $link;                               // Prefix the full domain
        } elseif (strpos($link, 'http') === false && strpos($link, '/') === false) {    // Check for a same directory reference
            if (strpos($link, 'www.') !== false) {
                continue;
            }
            $link = $this->domain['domain_full'] . '/' . $link;
        } elseif (strpos($link, 'mailto:') !== false) {                                 // Dont index email addresses
            // $this->parser->addMatch(str_replace('mailto:', '', $link));
            return false;
        } elseif (strpos($link, $this->domain['domain_plain']) === false) {             // Skip link if it isnt on the same domain
            return false;
        }

        return $link;
    }
}
