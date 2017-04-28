<?php

namespace Test\Provider\Curl;

use Neutrino\Http\Provider\Curl;
use Neutrino\Http\Standard\Method;
use Test\Provider\TestCase;

/**
 * Class CurlStreamTest
 *
 * @package     Test\Provider\Curl
 */
class CurlStreamingTest extends TestCase
{
    public function testCall()
    {
        $curlStream = new Curl\Streaming();

        $whatcher = [];

        $curlStream
            ->get('http://127.0.0.1:8000/', ['stream' => true])
            ->setProxy('', null, null)// Force Remove proxy
            ->setBufferSize(2048)
            ->on(Curl\Streaming::EVENT_START, function (Curl\Streaming $curlStream) use (&$whatcher) {
                if (isset($whatcher[Curl\Streaming::EVENT_START])) {
                    throw new \Exception('EVENT_START already raised');
                }

                $whatcher[Curl\Streaming::EVENT_START] = [
                    'code'    => $curlStream->response->code,
                    'status'  => $curlStream->response->header->status,
                    'headers' => $curlStream->response->header->getHeaders(),
                ];

                $whatcher['memory_start'] = memory_get_usage();
            })
            ->on(Curl\Streaming::EVENT_PROGRESS, function (Curl\Streaming $curlStream, $content) use (&$whatcher) {
                if (!isset($whatcher[Curl\Streaming::EVENT_PROGRESS])) {
                    $whatcher[Curl\Streaming::EVENT_PROGRESS] = [
                        'count'  => 1,
                        'length' => strlen($content)
                    ];
                } else {
                    $whatcher[Curl\Streaming::EVENT_PROGRESS]['count']++;
                    $whatcher[Curl\Streaming::EVENT_PROGRESS]['length'] += strlen($content);
                }

                $whatcher['memory_progress'] = memory_get_usage();

                if ($whatcher['memory_progress'] > $whatcher['memory_start']) {
                    $delta = $whatcher['memory_progress'] - $whatcher['memory_start'];
                    if ($delta / $whatcher['memory_start'] > 0.05) {
                        throw new \Exception("Memory Leak in progress");
                    }
                }
            })
            ->on(Curl\Streaming::EVENT_FINISH, function (Curl\Streaming $curlStream) use (&$whatcher) {
                if (isset($whatcher[Curl\Streaming::EVENT_FINISH])) {
                    throw new \Exception('EVENT_FINISH already raised');
                }

                $whatcher[Curl\Streaming::EVENT_FINISH] = true;
                $whatcher['memory_finish']              = memory_get_usage();
            })
            ->send();

        $response = $curlStream->response;

        $this->assertArrayHasKey(Curl\Streaming::EVENT_START, $whatcher);
        $this->assertArrayHasKey(Curl\Streaming::EVENT_PROGRESS, $whatcher);
        $this->assertArrayHasKey(Curl\Streaming::EVENT_FINISH, $whatcher);

        $this->assertEquals($whatcher[Curl\Streaming::EVENT_START]['code'], $response->code);
        $this->assertEquals($whatcher[Curl\Streaming::EVENT_START]['status'], $response->header->status);
        $this->assertEquals($whatcher[Curl\Streaming::EVENT_START]['headers'], $response->header->getHeaders());

        $this->assertGreaterThanOrEqual(1, $whatcher[Curl\Streaming::EVENT_PROGRESS]['count']);
        $this->assertGreaterThanOrEqual(1, $whatcher[Curl\Streaming::EVENT_PROGRESS]['length']);

        $this->assertGreaterThanOrEqual($response->header->get('Content-Length'), $whatcher[Curl\Streaming::EVENT_PROGRESS]['length']);

        if ($whatcher['memory_finish'] > $whatcher['memory_start']) {
            $delta = $whatcher['memory_finish'] - $whatcher['memory_start'];
            if ($delta / $whatcher['memory_start'] > 0.05) {
                throw new \Exception("Memory Leak in progress");
            }
        }
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Neutrino\Http\Provider\Curl\Streaming only support start, progress, finish
     */
    public function testTryRegisterWrongEvent()
    {
        $curlStream = new Curl\Streaming();

        $curlStream->on('test', function (){});
    }

    public function testSetBufferSize()
    {
        $curlReflectionClass = new \ReflectionClass(Curl\Streaming::class);
        $bufferSizeProperty = $curlReflectionClass->getProperty('bufferSize');
        $bufferSizeProperty->setAccessible(true);

        $curlStream = new Curl\Streaming();

        $curlStream->setBufferSize(2048);

        $bufferSize = $bufferSizeProperty->getValue($curlStream);

        $this->assertEquals(2048, $bufferSize);
    }
}
