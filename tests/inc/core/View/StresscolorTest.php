<?php

namespace Runalyze\View;

/**
 * Generated by PHPUnit_SkeletonGenerator 1.2.0 on 2015-01-15 at 11:27:05.
 */
class StresscolorTest extends \PHPUnit\Framework\TestCase {

	public function testSetting() {
		$ViaConstructor = new Stresscolor(17);
		$this->assertEquals(17, $ViaConstructor->value());

		$ViaSet = new Stresscolor();
		$this->assertEquals(23, $ViaSet->setValue(23)->value());
	}

	public function testScale() {
		$Stress = new Stresscolor();

		$this->assertEquals(32, $Stress->setValue(0.32)->scale(0, 1)->value());
		$this->assertEquals(70, $Stress->setValue(57)->scale(50, 60)->value());
	}

	public function testRgb() {
		$Stress = new Stresscolor();

		$this->assertEquals('c8c8c8', $Stress->setValue(-10)->rgb());
		$this->assertEquals('c8c8c8', $Stress->setValue(  0)->rgb());
		$this->assertEquals('c89696', $Stress->setValue( 25)->rgb());
		$this->assertEquals('c86464', $Stress->setValue( 50)->rgb());
		$this->assertEquals('c83232', $Stress->setValue( 75)->rgb());
		$this->assertEquals('c80000', $Stress->setValue(100)->rgb());
	}

}
