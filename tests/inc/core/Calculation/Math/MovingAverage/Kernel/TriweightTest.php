<?php

namespace Runalyze\Calculation\Math\MovingAverage\Kernel;

class TriweightTest extends \PHPUnit\Framework\TestCase
{
    public function testSomeValuesForWidth2()
    {
        $Kernel = new Triweight(2);

        $this->assertEquals([
            0.0,
            0.421875,
            1.0,
            0.083740234375
        ], $Kernel->valuesAt([
            -1.0,
            -0.5,
            0.0,
            0.75
        ]));
    }

    public function testSomeValuesForWidth10()
    {
        $Kernel = new Triweight(10);

        $this->assertEqualsWithDelta([
            0.0,
            0.421875,
            1.0,
            0.262144
        ], $Kernel->valuesAt([
            -5.0,
            -2.5,
            0.0,
            3.0
        ]), 0.0001);
    }
}
