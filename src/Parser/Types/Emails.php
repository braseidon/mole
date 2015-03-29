<?php namespace Braseidon\Mole\Parser\Types;

class Emails extends AbstractParser implements ParserTypeInterface
{

    /**
     * The table that keeps the data this parser scrapes
     *
     * @var string
     */
    protected $table = 'mole_emails';

    /**
     * The table column that keeps the data this parser scrapes
     *
     * @var string
     */
    protected $tableColumn = 'email';

    /**
     * Regex pattern
     *
     * @var string
     */
    protected $pattern = '/[a-z0-9_\-\+]+@[a-z0-9\-]+\.([a-z]{2,3})(?:\.[a-z]{2})?/i';
    // protected $pattern = '/^.+@.+\..+$/';

    /**
     * Finds matches in the HTML
     *
     * @param  string $html
     * @return bool
     */
    public function run($html)
    {
        $this->pregMatch($html);
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
