<?php

namespace Neutrino\Http\Event;

class Emitter
{
    /** @var array */
    protected $listeners = [];

    /**
     *
     *
     * @param string            $event
     * @param callable|\Closure $closure
     * @param null|string       $name
     *
     * @return $this
     */
    public function attach($event, $closure, $name = null)
    {
        if (is_null($name)) {
            $this->listeners[$event][] = $closure;
        } else {
            $this->listeners[$event][$name] = $closure;
        }

        return $this;
    }

    /**
     * @param string      $event
     * @param null        $closure
     * @param null|string $name
     *
     * @return $this
     */
    public function detach($event, $closure = null, $name = null)
    {
        if (isset($this->listeners[$event])) {
            if(!is_null($closure)){
                $index = array_search($closure, $this->listeners[$event], true);

                if (false !== $index) {
                    unset($this->listeners[$event][$index]);

                    return $this;
                }
            }

            if (is_null($name)) {
                unset($this->listeners[$event]);
            } elseif (isset($this->listeners[$event][$name])) {
                unset($this->listeners[$event][$name]);
            }
        }

        return $this;
    }

    /**
     * @param string $event
     * @param array  $arguments
     *
     * @return $this
     */
    public function fire($event, array $arguments = [])
    {
        if (isset($this->listeners[$event])) {
            return $this->emit($this->listeners[$event], $arguments);
        }

        return $this;
    }

    /**
     * @param array $arguments
     *
     * @return $this
     */
    public function fireAll(array $arguments = [])
    {
        foreach ($this->listeners as $events) {
            $this->emit($events, $arguments);
        }

        return $this;
    }

    /**
     * @param array $events
     * @param array $arguments
     *
     * @return $this
     */
    protected function emit(array $events, array $arguments = [])
    {
        foreach ($events as $event) {
            if (call_user_func_array($event, $arguments) === false) {
                return $this;
            }
        }

        return $this;
    }
}
