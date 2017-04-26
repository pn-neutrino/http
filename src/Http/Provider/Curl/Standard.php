<?php

namespace Neutrino\Http\Provider\Curl;

use Neutrino\Http\Provider\Curl;

class Standard extends Curl
{
    /**
     * @param resource $ch
     *
     * @return void
     */
    protected function exec($ch)
    {
        $result = curl_exec($ch);

        $this->response->body = $result;
    }
}