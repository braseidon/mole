<?php namespace Braseidon\Scraper\Traits;

trait CrawlerOptions
{

    /**
     * Set the target
     *
     * @param string $target
     */
    public function setTarget($target = '')
    {
        $this->target = $target;
    }

    /**
     * Get the target
     *
     * @param string $target
     */
    public function getTarget()
    {
        return $this->target;
    }

    /**
     * Set the threads
     *
     * @param integer $threads
     */
    public function setThreads($threads)
    {
        $this->config['threads'] = $threads;
    }

    /**
     * Get the threads
     *
     * @return integer
     */
    public function getThreads()
    {
        if ($this->config['threads']) {
            return $this->config['threads'];
        }
    }

    /**
     * Set the options
     *
     * @param integer $options
     */
    public function setOptions($options)
    {
        $this->config['options'] = $options;
    }

    /**
     * Get the options
     *
     * @return integer
     */
    public function getOptions()
    {
        if ($this->config['options']) {
            return $this->config['options'];
        }
    }

    /**
     * Set the request limit
     *
     * @param integer $requestLimit
     */
    public function setRequestLimit($requestLimit)
    {
        $this->config['request_limit'] = $requestLimit;
    }

    /**
     * Get the request limit
     *
     * @return integer
     */
    public function getRequestLimit()
    {
        if ($this->config['request_limit']) {
            return $this->config['request_limit'];
        }
    }

    /**
     * Set the max depth
     *
     * @param integer $maxDepth
     */
    public function setMaxDepth($maxDepth)
    {
        $this->config['max_depth'] = $maxDepth;
    }

    /**
     * Get the max depth
     *
     * @return integer
     */
    public function getMaxDepth()
    {
        if ($this->config['max_depth']) {
            return $this->config['max_depth'];
        }
    }
}
