<?php

namespace Neutrino\Http\Provider\Curl;

use Neutrino\Http\Contract\Streaming\Streamable;
use Neutrino\Http\Contract\Streaming\Streamize;
use Neutrino\Http\Provider\Curl;

class Streaming extends Curl implements Streamable
{
    use Streamize;

    /** @var bool */
    protected $hasStarted = false;

    /**
     * Curl WRITEFUNCTION handler
     *
     * @param resource $ch
     * @param string   $content
     *
     * @return int
     */
    protected function curlWriteFunction($ch, $content)
    {
        if (!$this->hasStarted) {
            $this->hasStarted = true;

            $this->emitter->fire(self::EVENT_START, [$this]);
        }

        $length = strlen($content);

        $this->emitter->fire(self::EVENT_PROGRESS, [$this, $content]);

        return $length;
    }

    public function call()
    {
        parent::call();

        $this->emitter->fire(self::EVENT_FINISH, [$this]);
    }

    protected function curlOptions($ch)
    {
        parent::curlOptions($ch);

        curl_setopt_array($ch,
            [
                CURLOPT_RETURNTRANSFER => false,
                CURLOPT_WRITEFUNCTION  => [$this, 'curlWriteFunction'],
            ]);

        if (isset($this->bufferSize)) {
            curl_setopt($ch, CURLOPT_BUFFERSIZE, $this->bufferSize);
        }
    }
}
