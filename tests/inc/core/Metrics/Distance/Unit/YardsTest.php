<?php

namespace Runalyze\Tests\Metrics\Distance\Unit;

use Runalyze\Metrics\Distance\Unit\Yards;

class YardsTest extends \PHPUnit\Framework\TestCase
{
    public function testSomeEasyValues()
    {
        $unit = new Yards();

        $this->assertEqualsWithDelta(1093.6, $unit->fromBaseUnit(1.0), 0.1);
        $this->assertEqualsWithDelta(1.0, $unit->toBaseUnit(1093.6), 0.1);

        $this->assertEqualsWithDelta(54.7, $unit->fromBaseUnit(0.05), 0.1);
        $this->assertEqualsWithDelta(0.05, $unit->toBaseUnit(54.7), 0.01);
    }
}
