<?php namespace Braseidon\Scraper\Cache;

interface WebCacheInterface {

	public function check($url);

	public function add($url);

	public function count();

	public function all();
}