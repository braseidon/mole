<?php namespace Braseidon\Mole\Driver;

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
