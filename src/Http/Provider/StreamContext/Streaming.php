<?php

namespace Neutrino\Http\Provider\StreamContext;

use Neutrino\Http\Client\Provider\StreamContext;
use Neutrino\Http\Streaming\Streamable;
use Neutrino\Http\Streaming\Streamize;

class Streaming extends StreamContext implements Streamable
{
    use Streamize;

    protected function streamContextExec($context)
    {
        $emitter = $this->getEmitter();

        try {
            $handler = fopen($this->uri->build(), 'r', null, $context);

            $this->streamContextParseHeader();

            $emitter->fire(self::EVENT_START, [$this]);

            $buffer = $this->bufferSize ? $this->bufferSize : 4096;

            while (feof($handler)) {
                $emitter->fire(self::EVENT_PROGRESS, [$this, stream_get_contents($handler, $buffer)]);
            }

            $emitter->fire(self::EVENT_FAILURE, [$this]);

            return true;
        } catch (\Exception $e) {
            $emitter->fire(self::EVENT_FAILURE, [$this, $e]);

            throw $e;
        } finally {
            if (isset($handler) && is_resource($handler)) {
                fclose($handler);
            }
        }
    }

}