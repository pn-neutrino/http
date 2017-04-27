<?php

namespace Test;

use Neutrino\Http\Header;

class HeaderTest extends \PHPUnit\Framework\TestCase
{
    public function dataParse()
    {
        return [
            [null, null, [], ""],
            [null, null, ['Date' => 'Thu, 27 Apr 2017 13:42:19 GMT'], PHP_EOL . "Date: Thu, 27 Apr 2017 13:42:19 GMT" . PHP_EOL],
            [200, 'OK', [], "HTTP/1.1 200 OK"],
            [200, 'Success', [], "HTTP/1.1 200 Success"],
            [302, 'Redirect', [], "HTTP/1.1 302 Redirect"],
            [418, 'I\'m a teapot', [], "HTTP/1.1 418 I'm a teapot"],
            [526, 'Whoops', [], "HTTP/1.1 526 Whoops"],
            [null, null, [
                'Date' => 'Thu, 27 Apr 2017 13:42:19 GMT',
                'X-Powered-By' => 'PHP/7.0.10',
                'Content-Length' => '5524',
                'Server' => 'Apache/2.4.23 (Win64) PHP/7.0.10',
            ], [
                "Date: Thu, 27 Apr 2017 13:42:19 GMT",
                "X-Powered-By: PHP/7.0.10\r\n Content-Length: 5524",
                "Server: Apache/2.4.23 (Win64) PHP/7.0.10",
            ]],
            [200, 'OK', [
                'Date' => 'Thu, 27 Apr 2017 13:42:19 GMT',
                'Server' => 'Apache/2.4.23 (Win64) PHP/7.0.10',
                'X-Powered-By' => 'PHP/7.0.10',
                'Content-Length' => '5524',
                'Content-Type' => 'text/html; charset=UTF-8',
            ], "HTTP/1.1 200 OK
Date: Thu, 27 Apr 2017 13:42:19 GMT
Server: Apache/2.4.23 (Win64) PHP/7.0.10
X-Powered-By: PHP/7.0.10
Content-Length: 5524
Content-Type: text/html; charset=UTF-8
"]
        ];
    }

    /**
     * @dataProvider dataParse
     *
     * @param $expectedCode
     * @param $expectedStatus
     * @param $expectedHeaders
     * @param $raw
     */
    public function testParse($expectedCode, $expectedStatus, $expectedHeaders, $raw)
    {
        $header = new Header();

        if (is_array($raw)) {
            foreach ($raw as $item) {
                $header->parse($item);
            }
        } else {
            $header->parse($raw);
        }

        $this->assertEquals($expectedCode, $header->code);
        $this->assertEquals($expectedStatus, $header->status);
        $this->assertEquals($expectedHeaders, $header->getHeaders());
    }
}
