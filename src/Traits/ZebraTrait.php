<?php namespace Braseidon\Mole\Traits;

trait ZebraTrait
{

    /**
     * Setup the Cache
     *
     * @param  string  $path
     * @param  integer $lifetime
     * @param  boolean $compress
     * @param  integer $chmod
     * @return void
     */
    public function cache($path, $lifetime = 3600, $compress = true, $chmod = 0755)
    {
        // if caching is not explicitly disabled
        if ($path != false) {
            // save cache-related properties
            $this->cache = [
                'path'      =>  $path,
                'lifetime'  =>  $lifetime,
                'chmod'     =>  $chmod,
                'compress'  =>  $compress,
            ];
        } else {
            // if caching is explicitly disabled, set this property to FALSE
            $this->cache = false;
        }
    }

    /**
     * Generate a Cache file's filename
     *
     * @param  array $request
     * @return string
     */
    public function _get_cache_file_name($request)
    {
        // iterate through the options associated with the request
        foreach ($request['options'] as $key => $value) {// ...and remove null or empty values
            if (is_null($value) || $value == '') {
                unset($request['options'][$key]);
            }
            // remove some entries associated with the request
            // callback, arguments and the associated file handler (where it is the case) are not needed
            $request = array_diff_key($request, array('callback' => '', 'arguments' => '', 'file_handler' => ''));
        }
        // return the path and name of the file name associated with the request
        return rtrim($this->cache['path'], '/') . '/' . md5(serialize($request));
    }

    /**
     * Returns an array of curl_opts with a Proxy set to it
     *
     * @param  string  $proxy
     * @param  integer $port
     * @param  string  $username
     * @param  string  $password
     * @return array
     */
    public function proxy($proxy, $port = 80, $username = '', $password = '')
    {
        // if not disabled
        if ($proxy) {
            // set the required options
            $options = [
                CURLOPT_HTTPPROXYTUNNEL     =>  1,
                CURLOPT_PROXY               =>  $proxy,
                CURLOPT_PROXYPORT           =>  $port,
            ];
            // if a username is also specified
            if ($username != '') {
                // set authentication values
                $options[CURLOPT_PROXYUSERPWD] = $username . ':' . $password;
            }
        // if disabled
        } else {
            // unset proxy-related options
            $options[CURLOPT_HTTPPROXYTUNNEL]   = null;
            $options[CURLOPT_PROXY]             = null;
            $options[CURLOPT_PROXYPORT]         = null;
        }

        return $options;
    }
}
