<?php namespace Braseidon\Mole\Http;

interface ProxyInterface {

	public function setProxyPath($path);

	public function getRandomProxy();

}