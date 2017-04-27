<?php

namespace Test;

use Neutrino\Http\Request;
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
        $request = new FakeRequest();

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
        $request = new FakeRequest();

        $request->setParams([
            'test'  => 'value',
            'test1' => 'value1',
        ]);

        $this->assertEquals([
            'test'  => 'value',
            'test1' => 'value1',
        ], $request->getParams());

        $request->addParam('test', 'test');

        $this->assertEquals([
            'test'  => 'test',
            'test1' => 'value1',
        ], $request->getParams());

        $request->setParams([
            'test'  => 'value',
            'test2' => 'value2',
            'test3' => 'value3',
        ], true);

        $this->assertEquals([
            'test'  => 'value',
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
        $request = new FakeRequest();

        $request->setAuth('basic', 'user', 'pass');

        $this->assertEquals([
            'type' => 'basic',
            'user' => 'user',
            'pass' => 'pass'
        ], $request->getAuth());

        $reflectionClass = new \ReflectionClass(FakeRequest::class);

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
        $request = new FakeRequest();

        $request->setProxy('domain.com');

        $this->assertEquals([
            'host'   => 'domain.com',
            'port'   => 8080,
            'access' => null
        ], $request->getProxy());

        $request->setProxy('domain.com', 8888);

        $this->assertEquals([
            'host'   => 'domain.com',
            'port'   => 8888,
            'access' => null
        ], $request->getProxy());

        $request->setProxy('domain.com', 8888, 'user:pass');

        $this->assertEquals([
            'host'   => 'domain.com',
            'port'   => 8888,
            'access' => 'user:pass'
        ], $request->getProxy());
    }

    public function testOptions()
    {
        $request = new FakeRequest();

        $request->setOptions([
            'test'  => 'value',
            'test1' => 'value1',
        ]);

        $this->assertEquals([
            'test'  => 'value',
            'test1' => 'value1',
        ], $request->getOptions());

        $request->setOption('test', 'test');

        $this->assertEquals([
            'test'  => 'test',
            'test1' => 'value1',
        ], $request->getOptions());

        $request->setOptions([
            'test'  => 'value',
            'test2' => 'value2',
            'test3' => 'value3',
        ], true);

        $this->assertEquals([
            'test'  => 'value',
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
        $request = new FakeRequest();

        $reflectionClass = new \ReflectionClass(FakeRequest::class);

        $headerProperty = $reflectionClass->getProperty('header');
        $headerProperty->setAccessible(true);
        $header = $headerProperty->getValue($request);

        $request->setHeaders([
            'test'  => 'value',
            'test1' => 'value1',
        ]);

        $this->assertEquals([
            'test'  => 'value',
            'test1' => 'value1',
        ], $header->getHeaders());

        $request->addHeader('test', 'test');

        $this->assertEquals([
            'test'  => 'test',
            'test1' => 'value1',
        ], $header->getHeaders());

        $request->setHeaders([
            'test'  => 'value',
            'test2' => 'value2',
            'test3' => 'value3',
        ], true);

        $this->assertEquals([
            'test'  => 'value',
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
        $request = new FakeRequest();

        $request->setCookies([
            'test'  => 'value',
            'test1' => 'value1',
        ]);

        $this->assertEquals([
            'test'  => 'value',
            'test1' => 'value1',
        ], $request->getCookies());

        $request->addCookie('test', 'test');
        $request->addCookie(null, 'test');

        $this->assertEquals([
            0       => 'test',
            'test'  => 'test',
            'test1' => 'value1',
        ], $request->getCookies());

        $request->setCookies([
            'test'  => 'value',
            'test2' => 'value2',
            'test3' => 'value3',
        ], true);

        $this->assertEquals([
            0       => 'test',
            'test'  => 'value',
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
        $request = new FakeRequest();

        $request->setJsonRequest(true);

        $this->assertTrue($request->isJsonRequest());

        $request->setJsonRequest(false);

        $this->assertFalse($request->isJsonRequest());
    }

    public function testBuildUrl()
    {
        $reflectionClass = new \ReflectionClass(FakeRequest::class);
        $buildUrlMethod = $reflectionClass->getMethod('buildUrl');
        $buildUrlMethod->setAccessible(true);

        $request = new FakeRequest();

        $request->setUri('http://www.google.com/');

        $request->setMethod(Method::POST);

        $buildUrlMethod->invoke($request);

        $this->assertEquals([], $request->called);

        $request->setMethod(Method::GET);

        $buildUrlMethod->invoke($request);

        $this->assertEquals(['extendUrl'], $request->called);
    }

    public function testCall()
    {
        $request = new FakeRequest();

        $request->call();

        $this->assertEquals([
            'buildParams',
            'buildAuth',
            'buildProxy',
            'buildCookies',
            'buildHeaders',
            'makeCall'
        ], $request->called);
    }
}

class FakeRequest extends Request
{
    public $called = [];

    /**
     * Definie le timeout de la requete
     *
     * @param int $timeout
     *
     * @return $this
     */
    public function setTimeout($timeout)
    {
        return $this->setOption('timeout', $timeout);
    }

    /**
     * @return \Neutrino\Http\Response
     */
    protected function makeCall()
    {
        $this->called[] = __FUNCTION__;

        $this->response->body = json_encode($this->options);

        return $this->response;
    }

    /**
     * Construit les parametres de la requete.
     *
     * @return $this
     */
    protected function buildParams()
    {
        $this->called[] = __FUNCTION__;

        if (!empty($this->params)) {

            if ($this->isPostMethod()) {
                if ($this->isJsonRequest()) {
                    return $this->setOption('params', json_encode($this->params));
                }

                return $this->setOption('params', $this->params);
            }

            $this->uri->extendQuery($this->params);
        }

        return $this;
    }

    /**
     * Construit les headers de la requete.
     *
     * @return $this
     */
    protected function buildHeaders()
    {
        $this->called[] = __FUNCTION__;

        return $this->setOption('headers', $this->header->build());
    }

    /**
     * Construit le proxy de la requete
     *
     * @return $this
     */
    protected function buildProxy()
    {
        $this->called[] = __FUNCTION__;

        return $this->setOption('proxy', $this->proxy);
    }

    /**
     * Construit les cookies de la requete
     *
     * @return $this
     */
    protected function buildCookies()
    {
        $this->called[] = __FUNCTION__;

        return $this->setOption('cookies', $this->getCookies(true));
    }

    protected function buildAuth()
    {
        $this->called[] = __FUNCTION__;

        return parent::buildAuth();
    }

    public function extendUrl(array $parameters = [])
    {
        $this->called[] = __FUNCTION__;

        return parent::extendUrl($parameters);
    }

}