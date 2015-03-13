<?php namespace Braseidon\Mole;

use Braseidon\Mole\Api\Index;
use Braseidon\Mole\Http\Proxy;
use Braseidon\Mole\Parser\ParserInterface;
use Braseidon\Mole\Traits\UsesConfig;

class ClientFactory
{
    use UsesConfig;

    /**
     * @var string $target The target website being scraped
     */
    protected $target;

    /**
     * Create ClientFactory object.
     *
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        $this->mergeOptions($config);
    }

    /**
     * Create Client object.
     *
     * @return Client
     */
    public function getClient()
    {
        $client = new Client($this->getAllOptions());

        $client->setOption('threads', $this->getOption('threads', 5));
        $client->setOption('request_limit', $this->getOption('request_limit', 10));
        $client->setOption('max_depth', $this->getOption('max_depth', 0));
        $client->setOption('ignored_file_types', $this->getIgnoredFileTypes());
        $client->setOption('use_user_agent', $this->getOption('use_user_agent', true));
        $client->setOption('use_proxies', $this->getOption('use_proxies', false));

        return $client;
    }

    /**
     * @return array Array of ignored file types
     */
    public function getIgnoredFileTypes()
    {
        $ignoredFileTypes = ['.css', '.doc', '.gif', '.jpeg', '.jpg', '.js', '.pdf', '.png'];

        return $this->getOption('ignored_file_types', $ignoredFileTypes);
    }

    /**
     * Create the Client object
     *
     * @param array
     * @return Client
     */
    public static function create(array $config = [])
    {
        return (new self($config))->getClient();
    }
}
