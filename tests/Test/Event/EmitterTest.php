<?php

namespace Test\Event;

use Neutrino\Http\Event\Emitter;
use PHPUnit\Framework\TestCase;

class EmitterTest extends TestCase
{
    public function testEmitter()
    {
        $watcher = 0;

        $emitter = new Emitter();

        $closure = function () use (&$watcher) {
            $watcher++;
        };

        $this->assertTrue($emitter->attach('test', $closure));

        $emitter->fire('test');
        $this->assertEquals(1, $watcher);

        $this->assertTrue($emitter->attach('test', $closure));

        $emitter->fire('test');
        $this->assertEquals(3, $watcher);

        $this->assertTrue($emitter->detach('test', $closure));

        $emitter->fire('test');
        $this->assertEquals(4, $watcher);

        $this->assertTrue($emitter->detach('test', $closure));
        $this->assertFalse($emitter->detach('test', $closure));

        $emitter->fire('test');
        $this->assertEquals(4, $watcher);

        $this->assertTrue($emitter->clear('test'));
        $this->assertFalse($emitter->clear('test'));
        $this->assertFalse($emitter->detach('test', $closure));
    }
}