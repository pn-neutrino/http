<?php

namespace Neutrino\Http\Event;

class Emitter
{
    /** @var array */
    protected $listeners = [];

    /**
     * @param string $event
     * @param callable $closure
     *
     * @return bool
     */
    public function attach($event, $closure)
    {
        $this->listeners[$event][] = $closure;

        return true;
    }

    /**
     * @param string $event
     * @param callable $closure
     *
     * @return bool
     */
    public function detach($event, $closure)
    {
        if (isset($this->listeners[$event])) {
            if (!is_null($closure)) {
                $index = array_search($closure, $this->listeners[$event], true);

                if (false !== $index) {
                    unset($this->listeners[$event][$index]);

                    return true;
                }
            }
        }

        return false;
    }

    public function clear($event)
    {
        if (isset($this->listeners[$event])) {
            unset($this->listeners[$event]);

            return true;
        }
        return false;
    }

    /**
     * @param string $event
     * @param array $arguments
     *
     * @return $this
     */
    public function fire($event, array $arguments = [])
    {
        if (isset($this->listeners[$event])) {
            foreach ($this->listeners[$event] as $event) {
                call_user_func_array($event, $arguments);
            }
        }

        return $this;
    }
}
