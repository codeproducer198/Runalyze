<?php

namespace Runalyze\Calculation\Elevation\Strategy;

/**
 * Generated by hand
 */
class ThresholdTest extends \PHPUnit\Framework\TestCase {

	public function testSimpleArray() {
		$DP = new Threshold(array(0, 2, 4, 6, 5, 7, 4, 8, 10, 0), 0);
		$DP->runSmoothing();

		$this->assertEquals( array(0, 6, 5, 7, 4, 10, 0), $DP->smoothedData());
		$this->assertEquals( array(0, 3, 4, 5, 6, 8, 9), $DP->smoothingIndices());

		$DP->setEpsilon(1);
		$DP->runSmoothing();

		$this->assertEquals( array(0, 6, 4, 10, 0), $DP->smoothedData());
		$this->assertEquals( array(0, 3, 6, 8, 9), $DP->smoothingIndices());

		$DP->setEpsilon(2);
		$DP->runSmoothing();

		$this->assertEquals( array(0, 6, 10, 0), $DP->smoothedData());
		$this->assertEquals( array(0, 3, 8, 9), $DP->smoothingIndices());
	}

}
