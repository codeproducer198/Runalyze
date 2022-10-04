<?php

namespace Runalyze\Tests\Metrics\Weight\Unit;

use Runalyze\Metrics\Weight\Unit\Stones;

class StonesTest extends \PHPUnit\Framework\TestCase
{
    public function testSomeEasyValues()
    {
        $unit = new Stones();

        $this->assertEqualsWithDelta(11.8, $unit->fromBaseUnit(75), 0.1);
        $this->assertEqualsWithDelta(75, $unit->toBaseUnit(11.8), 0.1);

        $this->assertEqualsWithDelta(10, $unit->fromBaseUnit(63.5), 0.1);
        $this->assertEqualsWithDelta(63.5, $unit->toBaseUnit(10), 0.1);

        $this->assertEqualsWithDelta(1.57, $unit->fromBaseUnit(10), 0.1);
        $this->assertEqualsWithDelta(10, $unit->toBaseUnit(1.57), 0.1);
    }
}
