<?php namespace Braseidon\Mole\Parser\Types;

class InternalLinks extends AbstractParser implements ParserTypeInterface
{

    /**
     * The table that keeps the data this parser scrapes
     *
     * @var string
     */
    protected $table = 'mole_index';

    /**
     * The table column that keeps the data this parser scrapes
     *
     * @var string
     */
    protected $tableColumn = 'url';

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
     * @var array $blockArr Array of blocked strings
     */
    protected $blockedArr = ['.css', '.doc', '.gif', '.jpeg', '.jpg', '.js', '.pdf', '.png'];

    /**
     * The target domain
     *
     * @var string
     */
    protected $domain;

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
            // dd($matches);

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
     * @param  string $item
     * @return string|bool
     */
    public function parse($url)
    {
        $url = trim(urldecode($url));

        if (strlen($url) === 0) {
            $url = '/';
        }

        // Check blocked strings
        if ($this->hasBlockedString($url)) {
            return false;
        }

        // Don't allow more than maxDepth forward slashes in the URL
        if ($this->getOption('max_depth', 0) > 0 && strpos($url, 'http') === false && substr_count($url, '/') > $this->getOption('max_depth', 0)) {
            return false;
        }
        if (strpos($url, 'http') === false && strpos($url, '/') === 0) {              // Check for a relative path starting with a forward slash
            $url = $this->domain['domain_full'] . $url;                               // Prefix the full domain
        } elseif (strpos($url, 'http') === false && strpos($url, '/') === false) {    // Check for a same directory reference
            if (strpos($url, 'www.') !== false) {
                continue;
            }
            $url = $this->domain['domain_full'] . '/' . $url;
        } elseif (strpos($url, 'mailto:') !== false) {                                 // Dont index email addresses
            // $this->parser->addMatch(str_replace('mailto:', '', $url));
            return false;
        } elseif (strpos($url, $this->domain['domain_plain']) === false) {             // Skip url if it isnt on the same domain
            return false;
        }

        return $url;
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

    /*
    |--------------------------------------------------------------------------
    | Interal Links Specific Stuff
    |--------------------------------------------------------------------------
    |
    |
    */

    /**
     * Set the domain we're crawling
     *
     * @param string $url
     */
    public function setDomain($url)
    {
        if ($parts = parse_url($url)) {
            $this->domain = $parts;
            $this->domain['scheme']         = $this->domain['scheme'] . '://';
            $this->domain['domain_plain']   = str_ireplace('www.', '', $parts['host']);
            $this->domain['domain_full']    = $this->domain['scheme'] . $parts['host'];
        }

        return $this;
    }

    public function addToDB($string)
    {
        DB::table($this->table)
            ->insert([
                'target'    => $this->domain['domain_plain'],
                'url'       => $string,
            ]);
    }

    public function incrementDB($string)
    {
        DB::table($this->table)
            ->where($this->tableColumn, '=', $string)
            ->increment('crawl_count', 1, ['crawled' => 1]);
    }
}
