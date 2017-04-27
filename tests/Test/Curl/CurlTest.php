<?php

namespace Test\Curl;

use Neutrino\Http\Provider\Curl;
use Neutrino\Http\Standard\Method;
use Neutrino\Http\Standard\StatusCode;
use PHPUnit\Framework\TestCase;

class CurlTest extends TestCase
{
    public static function makeDataCall($method, $code, $body, $status = null, $params = [])
    {
        $statusMessage = is_null($status) ? StatusCode::message($code) : $status;
        $statusCode = $code . (empty($statusMessage) ? '' : ' ' . $statusMessage);

        $expected = [
            'code' => $code,
            'status' => $statusMessage,
            'body' => $body,
            'headers' => [
                'Status-Code' => $statusCode,
                'Request-Method' => $method
            ]
        ];

        if (!empty($params) && $method !== Method::HEAD) {
            $expected['body'] = json_encode($params);
        }

        return [$expected, $method, "/$code" . (!empty($status) ? "/" . trim($status) : ''), $params];
    }

    public function dataCall()
    {
        return [
            "GET 200" => self::makeDataCall(Method::GET, 200, ''),
            "HEAD 200" => self::makeDataCall(Method::HEAD, 200, ''),
            "DELETE 200" => self::makeDataCall(Method::DELETE, 200, ''),
            "POST 200" => self::makeDataCall(Method::POST, 200, ''),
            "PUT 200" => self::makeDataCall(Method::PUT, 200, ''),
            "PATCH 200" => self::makeDataCall(Method::PATCH, 200, ''),

            "GET 300" => self::makeDataCall(Method::GET, 300, ''),
            "GET 400" => self::makeDataCall(Method::GET, 400, ''),
            "GET 500" => self::makeDataCall(Method::GET, 500, ''),
            "GET 600" => self::makeDataCall(Method::GET, 600, ''),

            "GET 200'Success'" => self::makeDataCall(Method::GET, 200, '', 'Success'),

            "GET 200 query" => self::makeDataCall(Method::GET, 200, '', null, ['query' => 'test']),
            "HEAD 200 query" => self::makeDataCall(Method::HEAD, 200, '', null, ['query' => 'test']),
            "DELETE 200 query" => self::makeDataCall(Method::DELETE, 200, '', null, ['query' => 'test']),
            "POST 200 query" => self::makeDataCall(Method::POST, 200, '', null, ['query' => 'test']),
            "PUT 200 query" => self::makeDataCall(Method::PUT, 200, '', null, ['query' => 'test']),
            "PATCH 200 query" => self::makeDataCall(Method::PATCH, 200, '', null, ['query' => 'test']),
        ];
    }

    /**
     * @dataProvider dataCall
     *
     * @param $expected
     * @param $method
     * @param $url
     * @param $params
     */
    public function testCall($expected, $method, $url, $params = [])
    {
        $curl = new Curl();

        $curl
            ->setMethod($method)
            ->setUri('http://127.0.0.1:8000' . $url)
            ->setProxy('', null, null)// Force Remove proxy
            ->setParams($params)
            ->call();

        $response = $curl->response;

        $this->assertEquals($response->code, $response->header->code);
        $this->assertEquals($expected['code'], $response->code);
        $this->assertEquals($expected['body'], $response->body);
        $this->assertEquals($expected['status'], $response->header->status);

        $header = $response->header;
        foreach ($expected['headers'] as $name => $value) {
            $this->assertTrue($header->has($name));
            $this->assertEquals($value, $header->get($name));
        }
    }
}