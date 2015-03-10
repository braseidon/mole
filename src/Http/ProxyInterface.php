<?php namespace Braseidon\Scraper\Http;

interface ProxyInterface {

	public function setProxyPath($path);

	public function getRandomProxy();

}