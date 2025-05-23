<?php

namespace Runalyze\Activity\PaceUnit;

class MinPer100yTest extends \PHPUnit\Framework\TestCase
{
	public function testSomePaces()
	{
		$Pace = new MinPer100y();

		$this->assertEqualsWithDelta(33, $Pace->rawValue(360), 0.5);
		$this->assertEqualsWithDelta(27, $Pace->rawValue(300), 0.5);
	}

	public function testComparison()
	{
		$Pace = new MinPer100y();

		$this->assertEqualsWithDelta(+5.5, $Pace->rawValue($Pace->compare(300, 360)), 0.1);
		$this->assertEqualsWithDelta(-5.5, $Pace->rawValue($Pace->compare(360, 300)), 0.1);
		$this->assertEqualsWithDelta(  0, $Pace->rawValue($Pace->compare(300, 300)), 0.1);
	}

	public function testFormat()
	{
		$Pace = new MinPer100y();

		$this->assertEquals('0:27', $Pace->format(300));
		$this->assertEquals('0:20', $Pace->format(224));
		$this->assertEquals('-:--', $Pace->format(0));
	}
}
