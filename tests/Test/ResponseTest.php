<?php

namespace Test;

use Neutrino\Http\Parser\Json;
use Neutrino\Http\Parser\Parserize;
use Neutrino\Http\Response;
use PHPUnit\Framework\TestCase;

/**
 * Class ResponseTest
 *
 * @package     Test
 */
class ResponseTest extends TestCase
{
    public function testIsser()
    {
        $response = new Response();

        $response->code = 200;
        $this->assertTrue($response->isOk());
        $this->assertFalse($response->isFail());
        $this->assertFalse($response->isError());

        $response->code = 300;
        $this->assertFalse($response->isOk());
        $this->assertTrue($response->isFail());
        $this->assertFalse($response->isError());

        $response->code = 400;
        $this->assertFalse($response->isOk());
        $this->assertTrue($response->isFail());
        $this->assertFalse($response->isError());

        $response->code = 500;
        $this->assertFalse($response->isOk());
        $this->assertTrue($response->isFail());
        $this->assertFalse($response->isError());

        $response->code = 600;
        $this->assertFalse($response->isOk());
        $this->assertTrue($response->isFail());
        $this->assertFalse($response->isError());

        $response->errorCode = 1;
        $this->assertFalse($response->isOk());
        $this->assertTrue($response->isFail());
        $this->assertTrue($response->isError());
    }

    public function testParse()
    {
        $data = ['int' => 123, 'str' => 'abc', 'null' => null];

        $response = new Response();

        $response->body = json_encode($data);

        $this->assertEquals(null, $response->data);

        $response->parse(Json::class);

        $this->assertEquals((object)$data, $response->data);

        $response->parse(JsonArray::class);

        $this->assertEquals($data, $response->data);
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Neutrino\Http\Response::parse: $parserize must implement Neutrino\Http\Parser\Parserize
     */
    public function testParseException()
    {
        $response = new Response();

        $response->parse([]);
    }
}

class JsonArray implements Parserize
{

    public function parse($raw)
    {
        return json_decode($raw, true);
    }
}