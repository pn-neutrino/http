<?php

namespace Neutrino\Psr7;

/**
 * Class Headers
 *
 * @package     Neutrino\Psr7
 */
class Headers
{
    /** @var string[][] */
    protected $fields;

    /**
     * @return \string[][]
     */
    public function all()
    {
        return $this->fields;
    }

    /**
     * @param string $name
     * @param string $value
     *
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function add($name, $value)
    {
        $this->validString($name, '$name');
        $this->validString($value, '$value');

        if (($key = $this->realKey($name)) === false) {
            $key = $name;
        }

        $this->fields[$key][] = $value;

        return $this;
    }

    /**
     * @param string   $name
     * @param string[] $values
     *
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function set($name, array $values)
    {
        try {
            foreach ($values as $value) {
                $this->validString($value, '');
            }
        } catch (\InvalidArgumentException $e) {
            throw new \InvalidArgumentException(static::class . 'At least one of the parameters is not a valid string.');
        }

        if (($key = $this->realKey($name)) === false) {
            $key = $name;
        }

        $this->fields[$key] = $values;

        return $this;
    }

    /**
     * @param string     $name
     * @param null|mixed $default
     *
     * @return null|\string[]|mixed
     *
     * @throws \InvalidArgumentException
     */
    public function get($name, $default = null)
    {
        if ($key = $this->realKey($name)) {
            return $this->fields[$key];
        }

        return $default;
    }

    /**
     * @param string     $name
     * @param null|mixed $default
     *
     * @return null|string|mixed
     *
     * @throws \InvalidArgumentException
     */
    public function line($name, $default = null)
    {
        if ($key = $this->realKey($name)) {
            return implode(', ', $this->fields[$key]);
        }

        return $default;
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function has($name)
    {
        return $this->realKey($name) !== false;
    }

    /**
     * @param string $name
     *
     * @return $this
     */
    public function remove($name)
    {
        if ($key = $this->realKey($name)) {
            unset($this->fields[$key]);
        }

        return $this;
    }

    /**
     * Return the header builded for the HTTP Request
     *
     * @return array
     */
    public function build()
    {
        $headers = [];

        foreach ($this->fields as $name => $value) {
            $headers[] = $name . ': ' . implode(', ', $value);
        }

        return $headers;
    }

    /**
     * @param string $name
     *
     * @return bool|string
     */
    protected function realKey($name)
    {
        foreach ($this->fields as $k => $v) {
            if (strcasecmp($k, $name) == 0) {
                return $k;
            }
        }

        return false;
    }

    /**
     * @param string $value
     *
     * @param string $paramName
     *
     * @throws \InvalidArgumentException
     */
    protected function validString($value, $paramName)
    {
        if (empty($value) || !is_string($value)) {
            throw new \InvalidArgumentException(static::class . ' Parameter ' . $paramName . ' must by an not empty and a string.');
        }
    }
}
