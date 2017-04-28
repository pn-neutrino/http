<?php

namespace Neutrino\Http\Parser;

use Neutrino\Http\Contract\Parser\Parserize;

class Json implements Parserize
{
    public function parse($raw)
    {
        return json_decode($raw);
    }
}
