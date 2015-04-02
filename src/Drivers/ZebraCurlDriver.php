<?php namespace Braseidon\Mole\Drivers;

use Zebra_cURL;

class ZebraCurlDriver implements CurlInterface
{

    /**
     * The Zebra_cURL instance
     *
     * @var Zebra_cURL
     */
    protected $zebra;

    public function __construct()
    {
        $this->zebra = new Zebra_cURL();
    }

    public function getDriver()
    {
        return $this->zebra;
    }

    public function setCallback($callback)
    {
        // $this->getDriver()->
    }

    public function execute()
    {
        return $this->getDriver()->start();
    }
}
