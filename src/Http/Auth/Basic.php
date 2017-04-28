<?php

namespace Neutrino\Http\Auth;

use Neutrino\Http\Contract\Request\Component;
use Neutrino\Http\Request;

/**
 * Class Basic
 *
 * @package     Neutrino\Http\Auth
 */
class Basic implements Component
{
    /** @var string */
    protected $user;

    /** @var string */
    protected $pass;

    /**
     * Basic constructor.
     *
     * @param string $user
     * @param string $pass
     */
    public function __construct($user, $pass)
    {
        $this->user = $user;
        $this->pass = $pass;
    }

    public function build(Request $request)
    {
        $request->setHeader('Authorization', 'Basic ' . base64_encode($this->user . ':' . $this->pass));
    }
}
