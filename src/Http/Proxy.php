<?php namespace Braseidon\Mole\Http;

use Exception;
use InvalidArgumentException;

class Proxy
{
    /**
     * The config object
     *
     * @var array
     */
    protected $config = [];

    /**
     * Path to Proxy file
     *
     * @var string
     */
    protected $path = '';

    /**
     * Array of proxies for scraping
     *
     * @var array
     */
    protected $proxies = [];

    /**
     * Proxy count
     *
     * @var integer
     */
    protected $proxyCount = 0;

    /**
     * Instantiate the object
     *
     * @param string $path
     */
    public function __construct($path = null)
    {
        if ($path !== null) {
            $this->import($path);
        }
    }

    /**
     * Sets the Curl object's proxy options using a random proxy
     *
     * @param  array $options
     * @return array
     */
    public function getRandomProxy($options = [])
    {
        if (! $proxy = $this->random()) {
            return $options;
            throw new Exception('No proxy was returned!');
        }

        $options[CURLOPT_PROXY]     = $proxy['ip'];
        $options[CURLOPT_PROXYPORT] = $proxy['port'];

        if (! empty($proxy['user']) and ! empty($proxy['pass'])) {
            $options[CURLOPT_PROXYUSERPWD] = $proxy['user'] . ':' . $proxy['pass'];
        }

        return $options;
    }

    /**
     * Returns answer depending on if array is empty
     *
     * @return boolean
     */
    public function hasProxies()
    {
        if (! empty($this->proxies)) {
            return true;
        }

        return false;
    }

    /**
     * Returns a random proxy
     *
     * @return array|bool
     */
    public function random()
    {
        if (! $this->hasProxies()) {
            return false;
        }

        $rand = mt_rand(0, ($this->proxyCount - 1));

        return $this->proxies[$rand];
    }

    /**
     * Count the proxies
     *
     * @return integer
     */
    public function count()
    {
        return count($this->proxies);
    }

    /**
     * Import proxies for use by the system
     *
     * @param  string|array $proxies
     * @return array
     */
    public function import($path)
    {
        $proxies = $this->getFile($path);
        $this->proxies = $this->parseProxyFile($proxies);
        $this->proxyCount = count($this->proxies);
    }

    /**
     * Get the proxy list file
     *
     * @param  string $path
     * @return file|bool
     */
    protected function getFile($path)
    {
        if (empty($path) || $path === '' || ! is_string($path)) {
            throw new InvalidArgumentException('You need to include a proxy path.');
        }

        if (! file_exists($path)) {
            throw new InvalidArgumentException('Proxy list path is incorrect or the file does not exist.');
        }

        return file_get_contents($path);
    }

    /**
     * Break up that list of IP's into a usable array
     *
     * @return array
     */
    protected function parseProxyFile($file)
    {
        $proxies = explode(PHP_EOL, $file);

        if (count($proxies) > 0) {
            $newArr = [];

            foreach ($proxies as $proxy) {
                $proxyArr = explode(':', $proxy);

                if (is_array($proxyArr) and count($proxyArr) >= 2) {
                    $newArr[] = $this->mapProxyArgs($proxyArr);
                }
            }

            return $newArr;
        }

        throw new Exception('Proxies array either failed or is empty!');
    }

    /**
     * Maps a proxy's parts
     *
     * @param  array  $proxyArr
     * @return array
     */
    private function mapProxyArgs(array $proxyArr)
    {
        $proxyArr = array_map('trim', $proxyArr);
        $proxyArr = array_unique($proxyArr);

        return [
            'ip'    => $proxyArr[0],
            'port'  => $proxyArr[1],
            'user'  => (! isset($proxyArr[2]) ? false : $proxyArr[2]),
            'pass'  => (! isset($proxyArr[3]) ? false : $proxyArr[3]),
        ];
    }
}
