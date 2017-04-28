<?php

namespace Test\Auth;

use Neutrino\Http\Auth;
use Neutrino\Http\Provider\Curl;
use PHPUnit\Framework\TestCase;
use Test\_Fake\FakeRequest;

class CurlTest extends TestCase
{
    public function dataProvider()
    {
        return [
            'ANY' => [CURLAUTH_ANY, 'user', 'pass'],
            'ANYSAFE' => [CURLAUTH_ANYSAFE, 'user', 'pass'],
            'BASIC' => [CURLAUTH_BASIC, 'user', 'pass'],
            'DIGEST' => [CURLAUTH_DIGEST, 'user', 'pass'],
            'NTLM' => [CURLAUTH_NTLM, 'user', 'pass'],
            //'NTLM_WB' => [CURLAUTH_NTLM_WB, 'user', 'pass'],
            //'NEGOTIATE' => [CURLAUTH_NEGOTIATE, 'user', 'pass'],
            'GSSNEGOTIATE' => [CURLAUTH_GSSNEGOTIATE, 'user', 'pass'],

            'BASIC|DIGEST' => [CURLAUTH_BASIC | CURLAUTH_DIGEST, 'user', 'pass'],
            'BASIC|DIGEST|GSSNEGOTIATE' => [CURLAUTH_BASIC | CURLAUTH_DIGEST | CURLAUTH_GSSNEGOTIATE, 'user', 'pass'],
        ];
    }

    /**
     * @dataProvider dataProvider
     *
     * @param $type
     */
    public function testType($type)
    {
        $this->assertInstanceOf(Auth\Curl::class, new Auth\Curl($type, '', ''));
    }

    /**
     * @dataProvider dataProvider
     *
     * @param $type
     */
    public function testBuild($type, $user, $pass)
    {
        $request = new Curl();

        $auth = new Auth\Curl($type, $user, $pass);

        $auth->build($request);

        $options = $request->getOptions();

        $this->assertArrayHasKey(CURLOPT_HTTPAUTH, $options);
        $this->assertArrayHasKey(CURLOPT_USERPWD, $options);

        $this->assertEquals($type, $options[CURLOPT_HTTPAUTH]);
        $this->assertEquals($user . ':' . $pass, $options[CURLOPT_USERPWD]);
    }

    /**
     * @expectedException \Neutrino\Http\Auth\Exception
     */
    public function testWrongType()
    {
        new Auth\Curl('type', '', '');
    }
}
