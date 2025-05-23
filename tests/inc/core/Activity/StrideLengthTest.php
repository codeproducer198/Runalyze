<?php

namespace Runalyze\Activity;

use Runalyze\Parameter\Application\DistanceUnitSystem;

/**
 * Generated by PHPUnit_SkeletonGenerator 1.2.0 on 2015-06-06 at 13:59:56.
 */
class StrideLengthTest extends \PHPUnit\Framework\TestCase {

	public function testStaticFunction() {
		$this->assertEquals('1.23&nbsp;'.DistanceUnitSystem::METER, StrideLength::format(123, true));
		$this->assertEquals('1.23', StrideLength::format(123, false));
	}

	public function testInMeter() {
		$StrideLength = new StrideLength();
		$StrideLength->setMeter(1.07);

		$this->assertEquals(1.07, $StrideLength->meter());
		$this->assertEquals('1.07&nbsp;'.DistanceUnitSystem::METER, $StrideLength->stringMeter());
	}

	public function testInCentimeter() {
		$StrideLength = new StrideLength();
		$StrideLength->set(87);

		$this->assertEquals(87, $StrideLength->cm());
		$this->assertEquals('87&nbsp;'.DistanceUnitSystem::CM, $StrideLength->stringCM());
	}

	public function testInFeet() {
		$StrideLength = new StrideLength();
		$StrideLength->setFeet(3.2);

		$this->assertEquals(3.2, $StrideLength->feet());
		$this->assertEquals('3.2&nbsp;'.DistanceUnitSystem::FEET, $StrideLength->stringFeet());
	}

	public function testSettingInPreferredUnit()
	{
		$this->assertEquals(1.23, (new StrideLength(0, new DistanceUnitSystem(DistanceUnitSystem::METRIC)))->setInPreferredUnit(1.23)->meter());
		$this->assertEquals(3.2, (new StrideLength(0, new DistanceUnitSystem(DistanceUnitSystem::IMPERIAL)))->setInPreferredUnit(3.2)->feet());
	}

}
