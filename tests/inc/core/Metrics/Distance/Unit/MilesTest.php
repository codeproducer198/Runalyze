<?php

namespace Runalyze\Tests\Metrics\Distance\Unit;

use Runalyze\Metrics\Distance\Unit\Miles;

class MilesTest extends \PHPUnit\Framework\TestCase
{
    public function testSomeEasyValues()
    {
        $unit = new Miles();

        $this->assertEqualsWithDelta(1.0, $unit->fromBaseUnit(1.609), 0.001);
        $this->assertEqualsWithDelta(1.609, $unit->toBaseUnit(1.0), 0.001);

        $this->assertEqualsWithDelta(10.0, $unit->fromBaseUnit(16.1), 0.01);
        $this->assertEqualsWithDelta(16.1, $unit->toBaseUnit(10), 0.01);

        $this->assertEqualsWithDelta(26.2, $unit->fromBaseUnit(42.2), 0.1);
        $this->assertEqualsWithDelta(42.2, $unit->toBaseUnit(26.2), 0.1);
    }
}
