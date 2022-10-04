<?php

namespace Runalyze\Tests\Metrics\HeartRate\Unit;

use Runalyze\Metrics\HeartRate\Unit\HeartRateEnum;

class HeartRateEnumTest extends \PHPUnit\Framework\TestCase
{
    public function testThatAllUnitsCanBeConstructed()
    {
        foreach (HeartRateEnum::getEnum() as $unit) {
            HeartRateEnum::get($unit, 200, 60);
        }
    }

    public function testThatUnknownUnitCantBeConstructed()
    {
        $this->expectException(\InvalidArgumentException::class);
        HeartRateEnum::get(42, 200, 60);
    }
}
