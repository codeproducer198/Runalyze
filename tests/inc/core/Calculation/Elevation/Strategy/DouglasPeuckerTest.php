<?php

namespace Runalyze\Calculation\Elevation\Strategy;

/**
 * Generated by hand
 */
class DouglasPeuckerTest extends \PHPUnit\Framework\TestCase {

	public function testSimpleArray() {
		$DP = new DouglasPeucker(array(0, 2, 4, 6, 5, 7, 4, 8, 10, 0), 0);
		$DP->runSmoothing();

		$this->assertEquals( array(0, 6, 5, 7, 4, 8, 10, 0), $DP->smoothedData());
		$this->assertEquals( array(0, 3, 4, 5, 6, 7, 8, 9), $DP->smoothingIndices());

		$DP->setEpsilon(1);
		$DP->runSmoothing();

		$this->assertEquals( array(0, 6, 5, 7, 4, 10, 0), $DP->smoothedData());
		$this->assertEquals( array(0, 3, 4, 5, 6, 8, 9), $DP->smoothingIndices());

		$DP->setEpsilon(2);
		$DP->runSmoothing();

		$this->assertEquals( array(0, 6, 7, 4, 10, 0), $DP->smoothedData());
		$this->assertEquals( array(0, 3, 5, 6, 8, 9), $DP->smoothingIndices());
	}

	public function testComplicatedIndices() {
		$DP = new DouglasPeucker(array(0, 2, 4, 6, 15, 27, 25, 18, 13, 58, 95, 94, 91, 100, 105, 127, 15, 125, 67, 65, 0), 10);
		$DP->runSmoothing();

		$this->assertEquals( array(0, 6, 27, 13, 95, 91, 127, 15, 125, 67, 65, 0), $DP->smoothedData());
		$this->assertEquals( array(0, 3, 5, 8, 10, 12, 15, 16, 17, 18, 19, 20), $DP->smoothingIndices());
	}

}
