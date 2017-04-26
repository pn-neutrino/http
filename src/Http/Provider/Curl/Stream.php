<?php

namespace Neutrino\Http\Provider\Curl;

use Neutrino\Http\Event\Emitter;
use Neutrino\Http\Provider\Curl;
use Neutrino\Http\Response;

class Stream extends Curl
{
    const EVENT_START    = 'start';
    const EVENT_PROGRESS = 'progress';
    const EVENT_FINISH   = 'finish';
    const EVENT_FAILURE  = 'failure';

    /** @var \Neutrino\Http\Event\Emitter|null */
    protected $emitter;

    /** @var bool */
    protected $hasStarted = false;

    /** @var bool */
    protected $processEvenFails = false;

    /**
     * Stream constructor.
     *
     * @param \Neutrino\Http\Response|null      $response
     * @param \Neutrino\Http\Event\Emitter|null $emitter
     */
    public function __construct(Response $response = null, $emitter = null)
    {
        parent::__construct($response);

        $this->emitter = $emitter === null ? new Emitter() : $emitter;
    }

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
     * @return Stream
     */
    public function setProcessEvenFails($processEvenFails)
    {
        $this->processEvenFails = $processEvenFails;

        return $this;
    }

    public function on($event, $callback)
    {
        $this->checkEvent($event);

        $this->emitter->attach($event, $callback);

        return $this;
    }

    public function off($event, $callback)
    {
        $this->checkEvent($event);

        $this->emitter->detach($event, $callback);

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

    /**
     * @param resource $ch
     *
     * @return void
     * @throws \Exception
     */
    protected function exec($ch)
    {
        curl_setopt_array($ch,
            [
                CURLOPT_RETURNTRANSFER => false,
                CURLOPT_WRITEFUNCTION  => [$this, 'curlWriteFunction'],
            ]);

        curl_exec($ch);
    }

    private function checkEvent($event)
    {
        if ($event == self::EVENT_START || $event == self::EVENT_PROGRESS || $event == self::EVENT_FINISH || $event == self::EVENT_FAILURE) {
            return;
        }

        throw new \RuntimeException(__METHOD__ . ' only support ' . implode(', ',
                [
                    self::EVENT_START,
                    self::EVENT_PROGRESS,
                    self::EVENT_FINISH,
                    self::EVENT_FAILURE,
                ]));
    }
}