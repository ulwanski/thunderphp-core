<?php

/** $Id$
 * Client.php
 * @version 1.0.0, $Revision$
 * @package TestApp
 * @author Marek Ulwański <marek@ulwanski.pl>
 * @copyright Copyright (c) 2015, Marek Ulwański
 * @link $HeadURL$ Subversion
 */

namespace Core\Http;

use Core\Exceptions\UrlParseException;

class Client extends AbstractRequest {

    /** @var array */
    protected $options = [];

    /**
     * Client constructor.
     * @param $host
     */
    public function __construct($host)
    {
        $this->setOption(CURLOPT_RETURNTRANSFER, true);
        parent::__construct($host);
    }

    /**
     * @param int $option
     * @param mixed $value
     * @return Client
     */
    public function setOption(int $option, $value) : Client
    {
        $this->options[$option] = $value;
        return $this;
    }

    /**
     * @param int $option
     * @return mixed
     */
    public function getOption(int $option)
    {
        return isset($this->options[$option])?$this->options[$option]:null;
    }

    /**
     * @return array
     */
    public function getOptions() : array
    {
        return $this->options;
    }

    /**
     * @param string $path Path to get
     * @param string|null $content Returned data
     * @param array $info Request info array
     * @return int Request HTTP code
     * @throws UrlParseException
     */
    public function execute(string $path, string &$content = null, array &$info = null) : int
    {
        # Extract components from path string
        $pathComponents = parse_url($path);

        # Throw an exception when extracting components fails
        if($pathComponents === false){
            throw new UrlParseException($path);
        }

        # Merge host and path components
        $urlComponents = array_merge($this->hostComponents, $pathComponents);

        # Initialize a cURL session
        $ch = curl_init($this->buildUrl($urlComponents));

        # Set multiple options for a cURL transfer
        curl_setopt_array($ch, $this->options);

        # Execute the given cURL session.
        $content = curl_exec($ch);

        # Get information regarding a transfer
        $info = curl_getinfo($ch);

        # Closes a cURL session and frees all resources.
        curl_close($ch);

        # Return request status code
        return $info['http_code'];
    }

    /**
     * @param array $parsedUrl
     * @return string
     */
    protected function buildUrl(array $parsedUrl) : string {
        $scheme   = isset($parsedUrl['scheme']) ? $parsedUrl['scheme'] . '://' : '';
        $host     = isset($parsedUrl['host']) ? $parsedUrl['host'] : '';
        $port     = isset($parsedUrl['port']) ? ':' . $parsedUrl['port'] : '';
        $user     = isset($parsedUrl['user']) ? $parsedUrl['user'] : '';
        $pass     = isset($parsedUrl['pass']) ? ':' . $parsedUrl['pass']  : '';
        $path     = isset($parsedUrl['path']) ? $parsedUrl['path'] : '';
        $query    = isset($parsedUrl['query']) ? '?' . $parsedUrl['query'] : '';
        $fragment = isset($parsedUrl['fragment']) ? '#' . $parsedUrl['fragment'] : '';
        $pass     = ($user || $pass) ? "$pass@" : '';

        return "$scheme$user$pass$host$port$path$query$fragment";
    }

    /**
     * @param array $headers
     * @return Client
     */
    public function setHeaders(array $headers) : Client
    {
        # Prepare array for combine headers
        $combineHeaders = [];

        # Rewrite headers array to combine array
        foreach($headers as $name => $value){
            $combineHeaders[] = "$name:$value";
        }

        # Set combine headers to options array
        $this->setOption(CURLOPT_HTTPHEADER, $combineHeaders);

        return $this;
    }

    /**
     * @param $name
     * @param $value
     * @return Client
     */
    public function addHeader($name, $value) : Client
    {
        # Get combined headers
        $combineHeaders = $this->getOption(CURLOPT_HTTPHEADER);

        # Prepare array for extracted array
        $separatedHeaders = [];

        # Extract headers to separated array
        if(is_array($combineHeaders)) foreach($combineHeaders as $header){
            $parts = explode(':', $header, 2);
            $key   = $parts[0];
            $header = isset($parts[1])?$parts[1]:null;
            $separatedHeaders[$key] = $header;
        }

        # Add new header to array
        $separatedHeaders[$name] = $value;

        # Set new headers to options array
        $this->setHeaders($separatedHeaders);

        return $this;
    }

    /**
     * @param bool $follow Set true to follow location
     * @param int|null $redirects Max amount of redirects
     * @return Client
     */
    public function followRedirect(bool $follow = true, int $redirects = null) : Client
    {
        $this->setOption(CURLOPT_FOLLOWLOCATION, (bool)$follow);
        if($redirects !== null) $this->setOption(CURLOPT_MAXREDIRS, $redirects);

        return $this;
    }

    /**
     * @param bool $set
     * @return Client
     */
    public function setAutoReferer(bool $set = true) : Client
    {
        $this->setOption(CURLOPT_AUTOREFERER, $set);
    }

}