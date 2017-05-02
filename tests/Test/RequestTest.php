<?php

namespace Test;

use Neutrino\Http\Auth;
use Neutrino\Http\Standard\Method;
use Neutrino\Http\Uri;
use PHPUnit\Framework\TestCase;

/**
 * Class RequestTest
 *
 * @package Test
 */
class RequestTest extends TestCase
{
    public function testUri()
    {
        $request = new _Fake\FakeRequest();

        $request->setUri('http://www.google.com/');

        $this->assertEquals(new Uri('http://www.google.com/'), $request->getUri());

        $request
            ->setUri('http://www.google.com/')
            ->setParams(['test' => 'test']);

        $this->assertEquals(new Uri('http://www.google.com/?test=test'), $request->getUri());

        $request
            ->setMethod(Method::POST)
            ->setUri('http://www.google.com/')
            ->setParams(['test' => 'test']);

        $this->assertEquals(new Uri('http://www.google.com/'), $request->getUri());
    }

    public function testParams()
    {
        $request = new _Fake\FakeRequest();

        $request->setParams([
            'test' => 'value',
            'test1' => 'value1',
        ]);

        $this->assertEquals([
            'test' => 'value',
            'test1' => 'value1',
        ], $request->getParams());

        $request->setParam('test', 'test');

        $this->assertEquals([
            'test' => 'test',
            'test1' => 'value1',
        ], $request->getParams());

        $request->setParams([
            'test' => 'value',
            'test2' => 'value2',
            'test3' => 'value3',
        ], true);

        $this->assertEquals([
            'test' => 'value',
            'test1' => 'value1',
            'test2' => 'value2',
            'test3' => 'value3'
        ], $request->getParams());


        $request->setParams([
            'test1' => 'value1',
            'test2' => 'value2',
            'test3' => 'value3',
        ]);

        $this->assertEquals([
            'test1' => 'value1',
            'test2' => 'value2',
            'test3' => 'value3'
        ], $request->getParams());
    }

    public function testAuth()
    {
        $request = new _Fake\FakeRequest();

        $request->setAuth(new Auth\Basic('user', 'pass'));

        $this->assertEquals(new Auth\Basic('user', 'pass'), $request->getAuth());

        $reflectionClass = new \ReflectionClass(_Fake\FakeRequest::class);

        $buildAuthMethod = $reflectionClass->getMethod('buildAuth');
        $buildAuthMethod->setAccessible(true);
        $buildAuthMethod->invoke($request);

        $headerProperty = $reflectionClass->getProperty('header');
        $headerProperty->setAccessible(true);
        $header = $headerProperty->getValue($request);

        $this->assertTrue($header->has('Authorization'));
        $this->assertEquals($header->get('Authorization'), 'Basic ' . base64_encode('user:pass'));
    }

    public function testProxy()
    {
        $request = new _Fake\FakeRequest();

        $request->setProxy('domain.com');

        $this->assertEquals([
            'host' => 'domain.com',
            'port' => 8080,
            'access' => null
        ], $request->getProxy());

        $request->setProxy('domain.com', 8888);

        $this->assertEquals([
            'host' => 'domain.com',
            'port' => 8888,
            'access' => null
        ], $request->getProxy());

        $request->setProxy('domain.com', 8888, 'user:pass');

        $this->assertEquals([
            'host' => 'domain.com',
            'port' => 8888,
            'access' => 'user:pass'
        ], $request->getProxy());
    }

    public function testOptions()
    {
        $request = new _Fake\FakeRequest();

        $request->setOptions([
            'test' => 'value',
            'test1' => 'value1',
        ]);

        $this->assertEquals([
            'test' => 'value',
            'test1' => 'value1',
        ], $request->getOptions());

        $request->setOption('test', 'test');

        $this->assertEquals([
            'test' => 'test',
            'test1' => 'value1',
        ], $request->getOptions());

        $request->setOptions([
            'test' => 'value',
            'test2' => 'value2',
            'test3' => 'value3',
        ], true);

        $this->assertEquals([
            'test' => 'value',
            'test1' => 'value1',
            'test2' => 'value2',
            'test3' => 'value3'
        ], $request->getOptions());


        $request->setOptions([
            'test1' => 'value1',
            'test2' => 'value2',
            'test3' => 'value3',
        ]);

        $this->assertEquals([
            'test1' => 'value1',
            'test2' => 'value2',
            'test3' => 'value3'
        ], $request->getOptions());
    }

    public function testHeader()
    {
        $request = new _Fake\FakeRequest();

        $reflectionClass = new \ReflectionClass(_Fake\FakeRequest::class);

        $headerProperty = $reflectionClass->getProperty('header');
        $headerProperty->setAccessible(true);
        $header = $headerProperty->getValue($request);

        $request->setHeaders([
            'test' => 'value',
            'test1' => 'value1',
        ]);

        $this->assertEquals([
            'test' => 'value',
            'test1' => 'value1',
        ], $header->getHeaders());

        $request->setHeader('test', 'test');

        $this->assertEquals([
            'test' => 'test',
            'test1' => 'value1',
        ], $header->getHeaders());

        $request->setHeaders([
            'test' => 'value',
            'test2' => 'value2',
            'test3' => 'value3',
        ], true);

        $this->assertEquals([
            'test' => 'value',
            'test1' => 'value1',
            'test2' => 'value2',
            'test3' => 'value3'
        ], $header->getHeaders());


        $request->setHeaders([
            'test1' => 'value1',
            'test2' => 'value2',
            'test3' => 'value3',
        ]);

        $this->assertEquals([
            'test1' => 'value1',
            'test2' => 'value2',
            'test3' => 'value3'
        ], $header->getHeaders());
    }

    public function testCookies()
    {
        $request = new _Fake\FakeRequest();

        $request->setCookies([
            'test' => 'value',
            'test1' => 'value1',
        ]);

        $this->assertEquals([
            'test' => 'value',
            'test1' => 'value1',
        ], $request->getCookies());

        $request->setCookie('test', 'test');
        $request->setCookie(null, 'test');

        $this->assertEquals([
            0 => 'test',
            'test' => 'test',
            'test1' => 'value1',
        ], $request->getCookies());

        $request->setCookies([
            'test' => 'value',
            'test2' => 'value2',
            'test3' => 'value3',
        ], true);

        $this->assertEquals([
            0 => 'test',
            'test' => 'value',
            'test1' => 'value1',
            'test2' => 'value2',
            'test3' => 'value3'
        ], $request->getCookies());

        $request->setCookies([
            'test1' => 'value1',
            'test2' => 'value2',
            'test3' => 'value3',
        ]);

        $this->assertEquals([
            'test1' => 'value1',
            'test2' => 'value2',
            'test3' => 'value3'
        ], $request->getCookies());

        $this->assertEquals(implode(';', [
            'test1' => 'value1',
            'test2' => 'value2',
            'test3' => 'value3'
        ]), $request->getCookies(true));
    }

    public function testJson()
    {
        $request = new _Fake\FakeRequest();

        $request->setJsonRequest(true);

        $this->assertTrue($request->isJsonRequest());

        $request->setJsonRequest(false);

        $this->assertFalse($request->isJsonRequest());
    }

    public function testBuildUrl()
    {
        $reflectionClass = new \ReflectionClass(_Fake\FakeRequest::class);
        $buildUrlMethod = $reflectionClass->getMethod('buildUrl');
        $buildUrlMethod->setAccessible(true);

        $request = new _Fake\FakeRequest();

        $request->setUri('http://www.google.com/');

        $request->setMethod(Method::POST);

        $buildUrlMethod->invoke($request);

        $this->assertEquals([], $request->called);

        $request->setMethod(Method::GET);

        $buildUrlMethod->invoke($request);

        $this->assertEquals(['extendUrl'], $request->called);
    }

    public function testSend()
    {
        $request = new _Fake\FakeRequest();

        $request->send();

        $this->assertEquals([
            'buildParams',
            'buildAuth',
            'buildProxy',
            'buildCookies',
            'buildHeaders',
            'makeCall'
        ], $request->called);
    }

    public function dataRequest()
    {
        return [
            [Method::GET, '/', ['q' => 'q'], ['Accept' => '*/*'], '/?q=q'],
            [Method::HEAD, '/', ['q' => 'q'], ['Accept' => '*/*'], '/?q=q'],
            [Method::DELETE, '/', ['q' => 'q'], ['Accept' => '*/*'], '/?q=q'],
            [Method::POST, '/', ['q' => 'q'], ['Accept' => '*/*'], '/'],
            [Method::PUT, '/', ['q' => 'q'], ['Accept' => '*/*'], '/'],
            [Method::PATCH, '/', ['q' => 'q'], ['Accept' => '*/*'], '/'],
        ];
    }

    /**
     * @dataProvider dataRequest
     */
    public function testRequest($method, $url, $params, $headers, $expectedUri)
    {
        $request = new _Fake\FakeRequest();

        $this->assertEquals($request, $request->{strtolower($method)}($url, $params, ['headers' => $headers]));

        $this->assertEquals($method, $request->getMethod());
        $this->assertEquals($expectedUri, $request->getUri()->build());
        $this->assertEquals($params, $request->getParams());
        $this->assertEquals($headers, $request->getHeaders());

        $this->assertEquals($request, $request->request($method, $url, $params, $headers));

        $this->assertEquals($method, $request->getMethod());
        $this->assertEquals($expectedUri, $request->getUri()->build());
        $this->assertEquals($params, $request->getParams());
        $this->assertEquals($headers, $request->getHeaders());
    }
}
