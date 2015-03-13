<?php namespace Braseidon\Mole\Api;

interface IndexInterface
{
    public function add($url);

    public function check($url);

    public function clean($url);

    public function count();

    public function all();
}
