<?php

namespace Neutrino\Http\Parser;

class Json implements Parserize
{
    public function parse($raw)
    {
        return json_decode($raw);
    }
}