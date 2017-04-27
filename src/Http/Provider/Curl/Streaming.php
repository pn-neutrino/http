<?php

namespace Neutrino\Http\Provider\Curl;

use Neutrino\Http\Provider\Curl;
use Neutrino\Http\Streaming\Streamable;
use Neutrino\Http\Streaming\Streamize;

class Streaming extends Curl implements Streamable
{
    use Streamize;

    /** @var bool */
    protected $hasStarted = false;

    /** @var bool */
    protected $processEvenFails = false;

    /**
     * @return bool
     */
    public function isProcessEvenFails()
    {
        return $this->processEvenFails;
    }

    /**
     * @param bool $processEvenFails
     *
     * @return Streaming
     */
    public function setProcessEvenFails($processEvenFails)
    {
        $this->processEvenFails = $processEvenFails;

        return $this;
    }

    /**
     * Curl WRITEFUNCTION handler
     *
     * @param resource $ch
     * @param string   $content
     *
     * @return int
     */
    public function curlWriteFunction($ch, $content)
    {
        if (!$this->hasStarted) {
            $this->hasStarted = true;

            if ($this->response->isOk() || $this->processEvenFails) {
                $this->emitter->fire(self::EVENT_START, [$this]);
            }
        }

        $length = strlen($content);

        if ($this->response->isOk() || $this->processEvenFails) {
            $this->emitter->fire(self::EVENT_PROGRESS, [$this, $content]);
        } else {
            $this->response->body .= $content;
        }

        return $length;
    }

    public function call()
    {
        parent::call();

        if ($this->response->isOk()) {
            $this->emitter->fire(self::EVENT_FINISH, [$this]);
        } else {
            $this->emitter->fire(self::EVENT_FAILURE, [$this]);
        }
    }

    protected function curlOptions($ch)
    {
        parent::curlOptions($ch);

        curl_setopt_array($ch,
            [
                CURLOPT_RETURNTRANSFER => false,
                CURLOPT_WRITEFUNCTION  => [$this, 'curlWriteFunction'],
            ]);

        if(isset($this->bufferSize)){
            curl_setopt($ch, CURLOPT_BUFFERSIZE, $this->bufferSize);
        }
    }

}