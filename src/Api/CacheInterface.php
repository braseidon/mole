<?php namespace Braseidon\Mole\Api;

interface CacheInterface
{
    public function add($item);

    public function check($item);

    public function clean($item);

    public function count();

    public function all();
}
