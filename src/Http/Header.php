<?php

namespace Neutrino\Http;

/**
 * Phalcon\Http\Client\Header
 *
 * @package Phalcon\Http\Client
 */
class Header
{
    /** @var array */
    private $headers = [];

    /**
     * @param string $name
     * @param string $value
     *
     * @return $this
     */
    public function set($name, $value)
    {
        $this->headers[$name] = $value;

        return $this;
    }

    /**
     * @param string $name
     * @param mixed  $default
     *
     * @return mixed
     */
    public function get($name, $default = null)
    {
        if ($this->has($name)) {
            return $this->headers[$name];
        }

        return $default;
    }

    /**
     * Determine if a header exists with a specific key.
     *
     * @param string $name
     *
     * @return boolean
     */
    public function has($name)
    {
        return isset($this->headers[$name]) || array_key_exists($name, $this->headers);
    }

    /**
     * @param string $name
     *
     * @return $this
     */
    public function remove($name)
    {
        if ($this->has($name)) {
            unset($this->headers[$name]);
        }

        return $this;
    }

    /**
     * Set multiple headers.
     *
     * <code>
     * $headers = [
     *     'X-Foo' => 'bar',
     *     'Content-Type' => 'application/json',
     * ];
     *
     * $curl->addMultiple($headers);
     * </code>
     *
     * @param array $fields
     * @param bool  $merge
     *
     * @return $this
     */
    public function setHeaders(array $fields, $merge = false)
    {
        if ($merge) {
            $this->headers = array_merge($this->headers, $fields);
        } else {
            $this->headers = $fields;
        }

        return $this;
    }

    /**
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    public function build()
    {
        $headers = [];

        foreach ($this->headers as $name => $value) {
            $headers[] = $name . ': ' . $value;
        }

        return $headers;
    }
}
