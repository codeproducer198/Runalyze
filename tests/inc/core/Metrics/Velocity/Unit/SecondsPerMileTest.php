<?php

namespace Runalyze\Tests\Metrics\Velocity\Unit;

use Runalyze\Metrics\Velocity\Unit\SecondsPerMile;

class SecondsPerMileTest extends \PHPUnit\Framework\TestCase
{
    public function testSomeEasyValues()
    {
        $unit = new SecondsPerMile();

        $this->assertEqualsWithDelta(482.8, $unit->fromBaseUnit(300), 0.1);
        $this->assertEqualsWithDelta(300, $unit->toBaseUnit(482.8), 0.1);
    }
}
