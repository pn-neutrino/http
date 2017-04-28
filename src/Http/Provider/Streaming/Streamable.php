<?php
/**
 * Created by PhpStorm.
 * User: xlzi590
 * Date: 27/04/2017
 * Time: 11:46
 */

namespace Neutrino\Http\Provider\Streaming;


interface Streamable
{
    const EVENT_START    = 'start';
    const EVENT_PROGRESS = 'progress';
    const EVENT_FINISH   = 'finish';

    public function on($event, $callback);

    public function off($event, $callback);

    public function setBufferSize($size);
}