<?php

namespace Runalyze\Calculation\Math\MovingAverage\Kernel;

class CosineTest extends \PHPUnit\Framework\TestCase
{
    public function testSomeValuesForDefaultWidth()
    {
        $Kernel = new Cosine(2.0);

        $this->assertEqualsWithDelta(0.000, $Kernel->at(-1.0), 0.001);
        $this->assertEqualsWithDelta(0.707, $Kernel->at(-0.5), 0.001);
        $this->assertEqualsWithDelta(1.000, $Kernel->at(0.0), 0.001);
        $this->assertEqualsWithDelta(0.707, $Kernel->at(0.5), 0.001);
        $this->assertEqualsWithDelta(0.000, $Kernel->at(1.0), 0.001);
    }
}
