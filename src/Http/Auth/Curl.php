<?php

namespace Neutrino\Http\Auth;

use Neutrino\Http\Contract\Request\Component;
use Neutrino\Http\Request;

/**
 * Class Basic
 *
 * @package     Neutrino\Http\Auth
 */
class Curl implements Component
{
    /** @var string */
    protected $type;

    /** @var string */
    protected $user;

    /** @var string */
    protected $pass;

    /**
     * Basic constructor.
     *
     * @param $type
     * @param string $user
     * @param string $pass
     * @throws Exception
     */
    public function __construct($type, $user, $pass)
    {
        if ($type == CURLAUTH_ANY
            || $type == CURLAUTH_ANYSAFE
            || $type & CURLAUTH_BASIC
            || $type & CURLAUTH_DIGEST
            || $type & CURLAUTH_NEGOTIATE
            || $type & CURLAUTH_GSSNEGOTIATE
            || $type & CURLAUTH_NTLM
            || $type & CURLAUTH_NTLM_WB
        ) {
            $this->type = $type;
            $this->user = $user;
            $this->pass = $pass;

            return;
        }

        throw new Exception(self::class . ' : Doesn\'t support Auth type : ' . $type);
    }

    public function build(Request $request)
    {
        $request
            ->setOption(CURLOPT_HTTPAUTH, $this->type)
            ->setOption(CURLOPT_USERPWD, $this->user . ':' . $this->pass);
    }
}
