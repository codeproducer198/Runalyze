<?php

namespace Runalyze\Tests\Metrics\Energy\Unit;

use Runalyze\Metrics\Energy\Unit\Kilojoules;

class KiloJoulesTest extends \PHPUnit\Framework\TestCase
{
    public function testSomeEasyValues()
    {
        $unit = new Kilojoules();

        $this->assertEqualsWithDelta(419, $unit->fromBaseUnit(100), 0.5);
        $this->assertEqualsWithDelta(100, $unit->toBaseUnit(419), 0.5);

        $this->assertEqualsWithDelta(100, $unit->fromBaseUnit(24), 0.5);
        $this->assertEqualsWithDelta(24, $unit->toBaseUnit(100), 0.5);
    }
}
