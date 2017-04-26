<?php

namespace Neutrino\Http\Parser;

class Xml implements Parserize
{
    public function parse($raw)
    {
        return simplexml_load_string($raw);
    }
}