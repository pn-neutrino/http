<?php

namespace Neutrino\Http\Parser;

use Neutrino\Http\Contract\Parser\Parserize;

class Xml implements Parserize
{
    public function parse($raw)
    {
        return simplexml_load_string($raw);
    }
}
