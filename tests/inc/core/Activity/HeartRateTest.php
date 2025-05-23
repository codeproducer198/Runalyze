<?php

namespace Runalyze\Activity;

use Runalyze\Athlete;

/**
 * Generated by PHPUnit_SkeletonGenerator 1.2.0 on 2014-10-10 at 21:44:46.
 */
class HeartRateTest extends \PHPUnit\Framework\TestCase {
	public function testWithoutAthlete() {
		$HR = new HeartRate(150);

		$this->assertFalse( $HR->canShowInHRmax() );
		$this->assertFalse( $HR->canShowInHRrest() );
		$this->assertEquals( 150, $HR->inBPM() );
	}

	public function testDynamicSetting() {
		$HR = new HeartRate(150);
		$this->assertEquals(150, $HR->inBPM());

		$HR->setBPM(120);
		$this->assertEquals(120, $HR->inBPM());
	}

	public function testWithAthlete() {
		$HR = new HeartRate(160, new Athlete(null, 200, 40));

		$this->assertTrue( $HR->canShowInHRmax() );
		$this->assertTrue( $HR->canShowInHRrest() );
		$this->assertEquals( 80, $HR->inHRmax() );
		$this->assertEquals( 75, $HR->inHRrest() );
	}

	public function testWithOnlyHRmax() {
		$HR = new HeartRate(150, new Athlete(null, 200));

		$this->assertTrue( $HR->canShowInHRmax() );
		$this->assertFalse( $HR->canShowInHRrest() );
		$this->assertEquals( 75, $HR->inHRmax() );
	}
}
