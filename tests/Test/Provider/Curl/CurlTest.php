<?php

namespace Test\Provider\Curl;

use Neutrino\Http\Provider\Curl;
use Neutrino\Http\Standard\Method;
use Test\Provider\TestCase;

class CurlTest extends TestCase
{
    public function dataCall()
    {
        return [
            "GET 200"    => self::makeDataCall(Method::GET, 200),
            "HEAD 200"   => self::makeDataCall(Method::HEAD, 200),
            "DELETE 200" => self::makeDataCall(Method::DELETE, 200),
            "POST 200"   => self::makeDataCall(Method::POST, 200),
            "PUT 200"    => self::makeDataCall(Method::PUT, 200),
            "PATCH 200"  => self::makeDataCall(Method::PATCH, 200),

            "GET 300" => self::makeDataCall(Method::GET, 300),
            "GET 400" => self::makeDataCall(Method::GET, 400),
            "GET 500" => self::makeDataCall(Method::GET, 500),
            "GET 600" => self::makeDataCall(Method::GET, 600),

            "GET 200'Success'" => self::makeDataCall(Method::GET, 200, 'Success'),

            "GET 200 query"    => self::makeDataCall(Method::GET, 200, null, ['query' => 'test']),
            "HEAD 200 query"   => self::makeDataCall(Method::HEAD, 200, null, ['query' => 'test']),
            "DELETE 200 query" => self::makeDataCall(Method::DELETE, 200, null, ['query' => 'test']),
            "POST 200 query"   => self::makeDataCall(Method::POST, 200, null, ['query' => 'test']),
            "PUT 200 query"    => self::makeDataCall(Method::PUT, 200, null, ['query' => 'test']),
            "PATCH 200 query"  => self::makeDataCall(Method::PATCH, 200, null, ['query' => 'test']),

            "GET 200 json"  => self::makeDataCall(Method::POST, 200, null, ['query' => 'test'], true),
            "POST 200 json" => self::makeDataCall(Method::POST, 200, null, ['query' => 'test'], true),
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
    public function testCall($expected, $method, $url, $params = [], $json = false)
    {
        if ($method !== Method::HEAD) {
            $jsonBody = json_decode($expected['body'], true);

            $jsonBody['header_send']['Accept'] = '*/*';
            ksort($jsonBody['header_send']);
            $expected['body'] = json_encode($jsonBody);
        }

        $curl = new Curl();

        $curl
            ->setMethod($method)
            ->setJsonRequest($json)
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

    /**
     * @expectedException \Neutrino\Http\Exception
     */
    public function testCallFailed()
    {
        try {
            $curl = new Curl();

            $curl
                ->setMethod('GET')
                ->setUri('http://invalid domain')
                ->setProxy('', null, null)// Force Remove proxy
                ->call();

        } catch (\Neutrino\Http\Provider\Exception $e) {
            $this->assertFalse($e);
        } catch (\Neutrino\Http\Exception $e) {
            $this->assertEquals(null, $curl->response->code);
            $this->assertEquals(null, $curl->response->body);
            $this->assertEquals(null, $curl->response->data);
            $this->assertEquals($e->getMessage(), $curl->response->error);
            $this->assertEquals($e->getCode(), $curl->response->errorCode);

            throw $e;
        }
    }

    public function testBuildProxy()
    {
        $reflectionClass  = new \ReflectionClass(Curl::class);
        $buildProxyMethod = $reflectionClass->getMethod('buildProxy');
        $buildProxyMethod->setAccessible(true);

        $streamCtx = new Curl;

        $streamCtx->setProxy('domain.com');

        $buildProxyMethod->invoke($streamCtx);

        $options = $streamCtx->getOptions();
        $this->assertArrayHasKey(CURLOPT_PROXY, $options);
        $this->assertArrayHasKey(CURLOPT_PROXYPORT, $options);
        $this->assertArrayNotHasKey(CURLOPT_PROXYUSERPWD, $options);
        $this->assertEquals('domain.com', $options[CURLOPT_PROXY]);
        $this->assertEquals(8080, $options[CURLOPT_PROXYPORT]);

        $streamCtx->setProxy('domain.com', 8888);

        $buildProxyMethod->invoke($streamCtx);

        $options = $streamCtx->getOptions();
        $this->assertArrayHasKey(CURLOPT_PROXY, $options);
        $this->assertArrayHasKey(CURLOPT_PROXYPORT, $options);
        $this->assertArrayNotHasKey(CURLOPT_PROXYUSERPWD, $options);
        $this->assertEquals('domain.com', $options[CURLOPT_PROXY]);
        $this->assertEquals(8888, $options[CURLOPT_PROXYPORT]);

        $streamCtx->setProxy('domain.com', 8888, 'user:pass');

        $buildProxyMethod->invoke($streamCtx);

        $options = $streamCtx->getOptions();
        $this->assertArrayHasKey(CURLOPT_PROXY, $options);
        $this->assertArrayHasKey(CURLOPT_PROXYPORT, $options);
        $this->assertArrayHasKey(CURLOPT_PROXYUSERPWD, $options);
        $this->assertEquals('domain.com', $options[CURLOPT_PROXY]);
        $this->assertEquals(8888, $options[CURLOPT_PROXYPORT]);
        $this->assertEquals('user:pass', $options[CURLOPT_PROXYUSERPWD]);
    }

    public function testBuildCookies()
    {
        $reflectionClass    = new \ReflectionClass(Curl::class);
        $buildCookiesMethod = $reflectionClass->getMethod('buildCookies');
        $buildCookiesMethod->setAccessible(true);

        $curl = new Curl;

        $curl->setCookie(null, 'biscuit');
        $curl->setCookie(null, 'muffin');

        $buildCookiesMethod->invoke($curl);

        $options = $curl->getOptions();
        $this->assertArrayHasKey(CURLOPT_COOKIE, $options);
        $this->assertEquals(implode(';', ['biscuit', 'muffin']), $options[CURLOPT_COOKIE]);
    }
}