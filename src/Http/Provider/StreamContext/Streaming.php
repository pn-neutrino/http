<?php

namespace Neutrino\Http\Provider\StreamContext;

use Neutrino\Http\Contract\Streaming\Streamable;
use Neutrino\Http\Contract\Streaming\Streamize;
use Neutrino\Http\Provider\StreamContext;

class Streaming extends StreamContext implements Streamable
{
    use Streamize;

    protected function streamContextExec($context)
    {
        $emitter = $this->getEmitter();

        try {
            $handler = fopen($this->uri->build(), 'r', null, $context);

            $this->streamContextParseHeader($http_response_header);

            $emitter->fire(self::EVENT_START, [$this]);

            $buffer = $this->bufferSize ? $this->bufferSize : 4096;

            while (!feof($handler)) {
                $emitter->fire(self::EVENT_PROGRESS, [$this, stream_get_contents($handler, $buffer)]);
            }

            $emitter->fire(self::EVENT_FINISH, [$this]);

            return true;
        } finally {
            if (isset($handler) && is_resource($handler)) {
                fclose($handler);
            }
        }
    }

}
