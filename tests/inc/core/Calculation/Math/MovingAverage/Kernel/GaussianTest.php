<?php

namespace Runalyze\Calculation\Math\MovingAverage\Kernel;

class GaussianTest extends \PHPUnit\Framework\TestCase
{
    public function testSomeValuesForDefaultWidth()
    {
        $Kernel = new Gaussian(6.0);

        $this->assertEqualsWithDelta(0.011, $Kernel->at(-3.0), 0.001);
        $this->assertEqualsWithDelta(0.135, $Kernel->at(-2.0), 0.001);
        $this->assertEqualsWithDelta(0.607, $Kernel->at(-1.0), 0.001);
        $this->assertEqualsWithDelta(1.000, $Kernel->at(0.0), 0.001);
        $this->assertEqualsWithDelta(0.607, $Kernel->at(1.0), 0.001);
        $this->assertEqualsWithDelta(0.135, $Kernel->at(2.0), 0.001);
        $this->assertEqualsWithDelta(0.011, $Kernel->at(3.0), 0.001);
    }
}
