<?php

namespace Runalyze\Tests\Metrics\Velocity\Unit;

use Runalyze\Metrics\Velocity\Unit\PaceEnum;

class PaceEnumTest extends \PHPUnit\Framework\TestCase
{
    public function testThatAllUnitsCanBeConstructed()
    {
        foreach (PaceEnum::getEnum() as $unit) {
            PaceEnum::get($unit);
        }
    }
}
