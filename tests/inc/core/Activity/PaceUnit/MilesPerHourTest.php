<?php

namespace Runalyze\Activity\PaceUnit;

class MilesPerHourTest extends \PHPUnit\Framework\TestCase
{
	public function testSomePaces()
	{
		$Pace = new MilesPerHour();

		$this->assertEqualsWithDelta(6.21, $Pace->rawValue(360), 0.01);
		$this->assertEqualsWithDelta(7.46, $Pace->rawValue(300), 0.01);
	}

	public function testComparison()
	{
		$Pace = new MilesPerHour();

		$this->assertEqualsWithDelta(+1.25, $Pace->rawValue($Pace->compare(300, 360)), 0.01);
		$this->assertEqualsWithDelta(-1.25, $Pace->rawValue($Pace->compare(360, 300)), 0.01);
		$this->assertEqualsWithDelta( 0.00, $Pace->rawValue($Pace->compare(300, 300)), 0.01);
	}

	public function testFormat()
	{
		AbstractDecimalUnit::$DecimalSeparator = ',';
		AbstractDecimalUnit::$Decimals = 1;

		$Pace = new MilesPerHour();

		$this->assertEquals('7,5', $Pace->format(300));
		$this->assertEquals('10,0', $Pace->format(224));
	}
}
