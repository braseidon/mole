<?php namespace Braseidon\Mole\Drivers;

use RollingCurl\Request;
use RollingCurl\RollingCurl;

class RollingCurlDriver implements CurlInterface
{

    /**
     * The RollingCurl instance
     *
     * @var RollingCurl
     */
    protected $rollingCurl;
}
