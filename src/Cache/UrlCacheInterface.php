<?php namespace Braseidon\Scraper\Cache;

interface CacheUrl {

	public function check($url);

	public function add($url);

	public function count();
}