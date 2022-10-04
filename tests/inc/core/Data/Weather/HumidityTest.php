<?php

namespace Runalyze\Data\Weather;

class HumidityTest extends \PHPUnit\Framework\TestCase
{
    public function testNonNumericValue()
    {
        $this->expectException(\InvalidArgumentException::class);
        new Humidity('foobar');
    }

    public function testNegativeValue()
    {
        $this->expectException(\InvalidArgumentException::class);
        new Humidity(-13);
    }

    public function testTooLargeValue()
    {
        $this->expectException(\InvalidArgumentException::class);
        new Humidity(123);
    }

    public function testNull()
    {
        $Humidity = new Humidity();

        $this->assertEquals(null, $Humidity->value());
        $this->assertEquals('', $Humidity->string());
        $this->assertTrue($Humidity->isUnknown());
    }

    public function testValue()
    {
        $Humidity = new Humidity(73);

        $this->assertEquals(73, $Humidity->value());
        $this->assertEquals(69, $Humidity->set(69)->value());
        $this->assertEquals('69', $Humidity->string(false));
    }
}
