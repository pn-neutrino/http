<?php

namespace Neutrino\Http\Contract\Request;

use Neutrino\Http\Request;

/**
 * Class Component
 *
 * @package     Neutrino\Http\Contract\Request
 */
interface Component
{
    public function build(Request $request);
}
