<?php

namespace Test\Provider\Stream;

use Neutrino\Http\Event\Emitter;
use Neutrino\Http\Provider\StreamContext;
use Neutrino\Http\Standard\Method;
use Test\Provider\TestCase;

/**
 * Class StreamTest
 *
 * @package     Test\Provider\Stream
 */
class StreamContextTest extends TestCase
{

    public function dataCall()
    {
        return [
            "GET 200" => self::makeDataCall(Method::GET, 200),
            "HEAD 200" => self::makeDataCall(Method::HEAD, 200),
            "DELETE 200" => self::makeDataCall(Method::DELETE, 200),
            "POST 200" => self::makeDataCall(Method::POST, 200),
            "PUT 200" => self::makeDataCall(Method::PUT, 200),
            "PATCH 200" => self::makeDataCall(Method::PATCH, 200),

            "GET 300" => self::makeDataCall(Method::GET, 300),
            "GET 400" => self::makeDataCall(Method::GET, 400),
            "GET 500" => self::makeDataCall(Method::GET, 500),
            "GET 600" => self::makeDataCall(Method::GET, 600),

            "GET 200'Success'" => self::makeDataCall(Method::GET, 200, 'Success'),

            "GET 200 query" => self::makeDataCall(Method::GET, 200, null, ['query' => 'test']),
            "HEAD 200 query" => self::makeDataCall(Method::HEAD, 200, null, ['query' => 'test']),
            "DELETE 200 query" => self::makeDataCall(Method::DELETE, 200, null, ['query' => 'test']),
            "POST 200 query" => self::makeDataCall(Method::POST, 200, null, ['query' => 'test']),
            "PUT 200 query" => self::makeDataCall(Method::PUT, 200, null, ['query' => 'test']),
            "PATCH 200 query" => self::makeDataCall(Method::PATCH, 200, null, ['query' => 'test']),

            "GET 200 json" => self::makeDataCall(Method::POST, 200, null, ['query' => 'test'], true),
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

            $jsonBody['header_send']['Connection'] = 'close';

            if (isset($jsonBody['header_send']['Content-Length']) && $jsonBody['header_send']['Content-Length'] == '0') {
                unset($jsonBody['header_send']['Content-Length']);
            }

            ksort($jsonBody['header_send']);
            $expected['body'] = json_encode($jsonBody);
        }

        $streamCtx = new StreamContext();

        $streamCtx
            ->request($method, 'http://127.0.0.1:8000' . $url, $params)
            ->setJsonRequest($json)
            ->send();

        $response = $streamCtx->getResponse();

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
            $curl = new StreamContext();

            $curl
                ->setMethod('GET')
                ->setUri('http://invalid domain')
                ->setProxy('', null, null)// Force Remove proxy
                ->send();

        } catch (\Neutrino\Http\Provider\Exception $e) {
            $this->assertFalse($e);
        } catch (\Neutrino\Http\Exception $e) {
            $this->assertEquals(null, $curl->getResponse()->code);
            $this->assertEquals(null, $curl->getResponse()->body);
            $this->assertEquals(null, $curl->getResponse()->data);
            $this->assertEquals($e->getMessage(), $curl->getResponse()->error);
            $this->assertEquals($e->getCode(), $curl->getResponse()->errorCode);

            throw $e;
        }
    }

    public function testBuildProxy()
    {
        $reflectionClass = new \ReflectionClass(StreamContext::class);
        $buildProxyMethod = $reflectionClass->getMethod('buildProxy');
        $buildProxyMethod->setAccessible(true);

        $streamCtx = new StreamContext;

        $streamCtx->setProxy('domain.com');

        $buildProxyMethod->invoke($streamCtx);

        $this->assertEquals('tcp://domain.com:8080', $streamCtx->getOptions()['proxy']);

        $streamCtx->setProxy('domain.com', 8888);

        $buildProxyMethod->invoke($streamCtx);

        $this->assertEquals('tcp://domain.com:8888', $streamCtx->getOptions()['proxy']);

        $streamCtx->setProxy('domain.com', 8888, 'user:pass');

        $buildProxyMethod->invoke($streamCtx);

        $this->assertEquals('tcp://user:pass@domain.com:8888', $streamCtx->getOptions()['proxy']);
    }

    public function testBuildCookies()
    {
        $reflectionClass = new \ReflectionClass(StreamContext::class);
        $buildCookiesMethod = $reflectionClass->getMethod('buildCookies');
        $buildCookiesMethod->setAccessible(true);

        $streamCtx = new StreamContext;

        $streamCtx->setCookie(null, 'biscuit');
        $streamCtx->setCookie(null, 'muffin');

        $buildCookiesMethod->invoke($streamCtx);

        $headerProperty = $reflectionClass->getProperty('header');
        $headerProperty->setAccessible(true);
        $header = $headerProperty->getValue($streamCtx);

        $this->assertTrue($header->has('Cookie'));
        $this->assertEquals(implode(';', ['biscuit', 'muffin']), $header->get('Cookie'));
    }

    public function testSetTimeout()
    {
        $streamCtx = new StreamContext();

        $streamCtx->setTimeout(10);

        $options = $streamCtx->getOptions();

        $this->assertArrayHasKey('timeout', $options);
        $this->assertEquals(10, $options['timeout']);
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Neutrino\Http\Provider\StreamContext\Streaming only support start, progress, finish
     */
    public function testTryRegisterWrongEvent()
    {
        $streamCtx = new StreamContext\Streaming();

        $streamCtx->on('test', function () {
        });
    }

    public function testOnOff()
    {
        $streamingReflectionClass = new \ReflectionClass(StreamContext\Streaming::class);
        $emitterProperty = $streamingReflectionClass->getProperty('emitter');
        $emitterProperty->setAccessible(true);

        $streamCtx = new StreamContext\Streaming();

        $watcher = [];

        $closureStart = function () use (&$watcher) {
            $watcher[] = 'start';
        };
        $closureProgress = function () use (&$watcher) {
            $watcher[] = 'progress';
        };
        $closureFinish = function () use (&$watcher) {
            $watcher[] = 'finish';
        };

        $streamCtx->on($streamCtx::EVENT_START, $closureStart);
        $streamCtx->on($streamCtx::EVENT_PROGRESS, $closureProgress);
        $streamCtx->on($streamCtx::EVENT_FINISH, $closureFinish);

        $emitter = $emitterProperty->getValue($streamCtx);


        $emitterReflectionClass = new \ReflectionClass(Emitter::class);
        $listenerProperty = $emitterReflectionClass->getProperty('listeners');
        $listenerProperty->setAccessible(true);
        $listener = $listenerProperty->getValue($emitter);

        $this->assertArrayHasKey($streamCtx::EVENT_START, $listener);
        $this->assertArrayHasKey($streamCtx::EVENT_PROGRESS, $listener);
        $this->assertArrayHasKey($streamCtx::EVENT_FINISH, $listener);

        $this->assertEquals([$closureStart], $listener[$streamCtx::EVENT_START]);
        $this->assertEquals([$closureProgress], $listener[$streamCtx::EVENT_PROGRESS]);
        $this->assertEquals([$closureFinish], $listener[$streamCtx::EVENT_FINISH]);

        $streamCtx->off($streamCtx::EVENT_START, $closureStart);
        $listener = $listenerProperty->getValue($emitter);

        $this->assertEquals([], $listener[$streamCtx::EVENT_START]);

        $streamCtx->off($streamCtx::EVENT_PROGRESS, $closureProgress);
        $listener = $listenerProperty->getValue($emitter);

        $this->assertEquals([], $listener[$streamCtx::EVENT_PROGRESS]);

        $streamCtx->off($streamCtx::EVENT_FINISH, $closureFinish);
        $listener = $listenerProperty->getValue($emitter);

        $this->assertEquals([], $listener[$streamCtx::EVENT_FINISH]);
    }

    /**
     * @expectedException \Neutrino\Http\Provider\Exception
     * @expectedExceptionMessage Neutrino\Http\Provider\StreamContext HTTP or HTTPS stream wrappers not registered.
     */
    public function testAvailabilityFail()
    {
        $reflection = new \ReflectionClass(StreamContext::class);
        $isAvailableProperty = $reflection->getProperty('isAvailable');
        $isAvailableProperty->setAccessible(true);
        $isAvailableProperty->setValue(null, false);

        new StreamContext;
    }
}
