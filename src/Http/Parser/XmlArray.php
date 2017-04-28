<?php

namespace Neutrino\Http\Parser;

class XmlArray extends Xml
{
    public function parse($raw)
    {
        return json_decode(json_encode(parent::parse($raw)), true);
    }
}
