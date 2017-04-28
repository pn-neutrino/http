<?php

namespace Neutrino\Http\Contract\Streaming;

interface Streamable
{
    const EVENT_START    = 'start';
    const EVENT_PROGRESS = 'progress';
    const EVENT_FINISH   = 'finish';

    public function on($event, $callback);

    public function off($event, $callback);

    public function setBufferSize($size);
}
