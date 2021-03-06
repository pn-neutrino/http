<?php

namespace Neutrino\Http\Contract\Streaming;

use Neutrino\Http\Event\Emitter;

trait Streamize
{
    /** @var Emitter */
    protected $emitter;

    /** @var int|null */
    protected $bufferSize;

    protected function getEmitter()
    {
        if (!isset($this->emitter)) {
            $this->emitter = new Emitter();
        }

        return $this->emitter;
    }

    public function on($event, $callback)
    {
        $this->checkEvent($event);

        $this->getEmitter()->attach($event, $callback);

        return $this;
    }

    public function off($event, $callback)
    {
        $this->checkEvent($event);

        $this->getEmitter()->detach($event, $callback);

        return $this;
    }

    public function setBufferSize($size)
    {
        $this->bufferSize = $size;

        return $this;
    }

    private function checkEvent($event)
    {
        if ($event == Streamable::EVENT_START
            || $event == Streamable::EVENT_PROGRESS
            || $event == Streamable::EVENT_FINISH

        ) {
            return;
        }

        throw new \RuntimeException(static::class . ' only support ' . implode(', ',
                [
                    Streamable::EVENT_START,
                    Streamable::EVENT_PROGRESS,
                    Streamable::EVENT_FINISH,
                ]));
    }
}
