<?php namespace Braseidon\Mole\Index;

interface IndexInterface
{
    public function add($url);

    public function has($url);

    public function clean($url);

    public function count();

    public function all();
}
