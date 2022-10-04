<?php

namespace Runalyze\Tests\Metrics\Velocity\Unit;

use Runalyze\Metrics\Velocity\Unit\MeterPerSecond;

class MeterPerSecondTest extends \PHPUnit\Framework\TestCase
{
    public function testSomeEasyValues()
    {
        $unit = new MeterPerSecond();

        $this->assertEqualsWithDelta(3.333, $unit->fromBaseUnit(300), 0.001);
        $this->assertEqualsWithDelta(300, $unit->toBaseUnit(3.333), 0.5);

        $this->assertEqualsWithDelta(2.777, $unit->fromBaseUnit(360), 0.001);
        $this->assertEqualsWithDelta(360, $unit->toBaseUnit(2.777), 0.5);
    }
}
