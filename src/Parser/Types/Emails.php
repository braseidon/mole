<?php namespace Braseidon\Mole\Parser\Types;

use DB;

class Emails extends AbstractParser implements ParserTypeInterface
{

    /**
     * The table that keeps the data this parser scrapes
     *
     * @var string
     */
    protected $table = 'mole_emails';

    /**
     * Regex pattern
     *
     * @var string
     */
    protected $pattern = '/[a-z0-9_\-\+]+@[a-z0-9\-]+\.([a-z]{2,3})(?:\.[a-z]{2})?/i';
    // protected $pattern = '/^.+@.+\..+$/';

    /**
     * Cache Tag - The domain we're scraping
     *
     * @var string
     */
    protected $domain;

    /**
     * Cache Tag - The data we're scraping
     *
     * @var string
     */
    protected $cachetag = 'emails';

    /**
     * Instantiate the Object
     *
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        $this->mergeOptions($config);

        $this->cache = \App::make('cache');
    }

    /**
     * Finds matches in the HTML
     *
     * @param  string $html
     * @return bool
     */
    public function run($html)
    {
        $this->pregMatch($html);

        $this->processEmails();
    }

    /**
     * Parse an item, deciding whether to keep it
     *
     * @param  string $item
     * @return string $item
     */
    public function parse($item)
    {
        return true;
    }

    /**
     * Process all the found emails, adding them to the database
     *
     * @return void
     */
    protected function processEmails()
    {
        $emails = $this->getMatches();

        if (! count($emails)) {
            return false;
        }

        $emails = array_unique($emails);

        foreach ($emails as $email) {
            if (! $this->checkIndex($email)) {
                $this->addToIndex($email);
            }
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Database Stuff
    |--------------------------------------------------------------------------
    |
    |
    */

    /**
     * Add the item to the database if it doens't exist
     *
     * @param  string $item
     * @return bool
     */
    public function checkIndex($item)
    {
        $check = DB::table($this->table)->where('email', '=', $item)->count();

        if ($check == 0) {
            return false;
        }

        return true;
    }

    /**
     * Add an item to the database
     *
     * @param string $item
     */
    public function addToIndex($item)
    {
        DB::table($this->table)->insert(['email' => $item]);
    }
}
