<?php

namespace Runalyze\Tests\Metrics\Velocity\Unit;

use Runalyze\Metrics\Velocity\Unit\MilesPerHour;

class MilesPerHourTest extends \PHPUnit\Framework\TestCase
{
    public function testSomeEasyValues()
    {
        $unit = new MilesPerHour();

        $this->assertEqualsWithDelta(7.46, $unit->fromBaseUnit(300), 0.01);
        $this->assertEqualsWithDelta(300, $unit->toBaseUnit(7.46), 0.5);

        $this->assertEqualsWithDelta(6.21, $unit->fromBaseUnit(360), 0.01);
        $this->assertEqualsWithDelta(360, $unit->toBaseUnit(6.21), 0.5);
    }
}
