<?php

namespace Test\Provider\Curl;

use Neutrino\Http\Provider\Curl;
use Neutrino\Http\Provider\StreamContext;
use Neutrino\Http\Standard\Method;
use Test\Provider\TestCase;

/**
 * Class CurlStreamTest
 *
 * @package     Test\Provider\Curl
 */
class StreamContextStreamingTest extends TestCase
{
    public function testCall()
    {
        $streamCtxStreaming = new StreamContext\Streaming();

        $whatcher = [];

        $streamCtxStreaming
            ->get('http://127.0.0.1:8000/', ['stream' => true])
            ->setBufferSize(2048)
            ->on(Curl\Streaming::EVENT_START, function (StreamContext\Streaming $streamCtxStreaming) use (&$whatcher) {
                if (isset($whatcher[StreamContext\Streaming::EVENT_START])) {
                    throw new \Exception('EVENT_START already raised');
                }

                $whatcher[StreamContext\Streaming::EVENT_START] = [
                    'code'    => $streamCtxStreaming->response->code,
                    'status'  => $streamCtxStreaming->response->header->status,
                    'headers' => $streamCtxStreaming->response->header->getHeaders(),
                ];

                $whatcher['memory_start'] = memory_get_peak_usage();
            })
            ->on(StreamContext\Streaming::EVENT_PROGRESS,
                function (StreamContext\Streaming $streamCtxStreaming, $content) use (&$whatcher) {
                    if (!isset($whatcher[StreamContext\Streaming::EVENT_PROGRESS])) {
                        $whatcher[StreamContext\Streaming::EVENT_PROGRESS] = [
                            'count'  => 1,
                            'length' => strlen($content)
                        ];
                    } else {
                        $whatcher[StreamContext\Streaming::EVENT_PROGRESS]['count']++;
                        $whatcher[StreamContext\Streaming::EVENT_PROGRESS]['length'] += strlen($content);
                    }

                    $whatcher['memory_progress'] = memory_get_peak_usage();

                    if ($whatcher['memory_progress'] > $whatcher['memory_start']) {
                        $delta = $whatcher['memory_progress'] - $whatcher['memory_start'];
                        if ($delta / $whatcher['memory_start'] > 0.05) {
                            throw new \Exception("Memory Leak in progress");
                        }
                    }
                })
            ->on(StreamContext\Streaming::EVENT_FINISH, function (StreamContext\Streaming $curlStream) use (&$whatcher) {
                if (isset($whatcher[StreamContext\Streaming::EVENT_FINISH])) {
                    throw new \Exception('EVENT_FINISH already raised');
                }

                $whatcher[StreamContext\Streaming::EVENT_FINISH] = true;
                $whatcher['memory_finish']                       = memory_get_usage();
            })
            ->send();

        $response = $streamCtxStreaming->response;

        $this->assertArrayHasKey(StreamContext\Streaming::EVENT_START, $whatcher);
        $this->assertArrayHasKey(StreamContext\Streaming::EVENT_PROGRESS, $whatcher);
        $this->assertArrayHasKey(StreamContext\Streaming::EVENT_FINISH, $whatcher);

        $this->assertEquals($whatcher[StreamContext\Streaming::EVENT_START]['code'], $response->code);
        $this->assertEquals($whatcher[StreamContext\Streaming::EVENT_START]['status'], $response->header->status);
        $this->assertEquals($whatcher[StreamContext\Streaming::EVENT_START]['headers'], $response->header->getHeaders());

        $this->assertGreaterThanOrEqual(1, $whatcher[StreamContext\Streaming::EVENT_PROGRESS]['count']);
        $this->assertGreaterThanOrEqual(1, $whatcher[StreamContext\Streaming::EVENT_PROGRESS]['length']);

        $this->assertGreaterThanOrEqual($response->header->get('Content-Length'),
            $whatcher[StreamContext\Streaming::EVENT_PROGRESS]['length']);

        if ($whatcher['memory_finish'] > $whatcher['memory_start']) {
            $delta = $whatcher['memory_finish'] - $whatcher['memory_start'];
            if ($delta / $whatcher['memory_start'] > 0.05) {
                throw new \Exception("Memory Leak in progress");
            }
        }
    }

    public function testSetBufferSize()
    {
        $streamingReflectionClass = new \ReflectionClass(StreamContext\Streaming::class);
        $bufferSizeProperty = $streamingReflectionClass->getProperty('bufferSize');
        $bufferSizeProperty->setAccessible(true);

        $streamCtxStreaming = new StreamContext\Streaming();

        $streamCtxStreaming->setBufferSize(2048);

        $bufferSize = $bufferSizeProperty->getValue($streamCtxStreaming);

        $this->assertEquals(2048, $bufferSize);
    }
}
