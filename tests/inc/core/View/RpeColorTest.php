<?php

namespace Runalyze\Tests\View;

use Runalyze\View\RpeColor;

class RpeColorTest extends \PHPUnit\Framework\TestCase
{
    public function testEmptyObject()
    {
        $object = new RpeColor();

        $this->assertNull($object->value());
        $this->assertEquals('transparent', $object->borderColor());
    }

    public function testSetting()
    {
        $viaConstructor = new RpeColor(1);
        $this->assertEquals(1, $viaConstructor->value());

        $viaConstructor = new RpeColor(10);
        $this->assertEquals(10, $viaConstructor->value());

        $viaSet = new RpeColor();
        $this->assertEquals(null, $viaSet->setValue(19)->value());
    }

	public function testBackgroundColor()
    {
        $rpe = new RpeColor();

        $this->assertEquals('transparent', $rpe->setValue(0)->borderColor());
        $this->assertEquals('#225ea8', $rpe->setValue(1)->borderColor());
        $this->assertEquals('#fecc5c', $rpe->setValue(6)->borderColor());
        $this->assertEquals('#fd8d3c', $rpe->setValue(9)->borderColor());
        $this->assertEquals('#fd8d3c', $rpe->setValue(9)->borderColor());
        $this->assertEquals('#e31a1c', $rpe->setValue(10)->borderColor());
        $this->assertEquals('transparent', $rpe->setValue(20)->borderColor());
    }
}
