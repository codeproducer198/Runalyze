<?php

namespace Runalyze\Model\Activity\Splits;

/**
 * Generated by hand
 */
class ObjectTest extends \PHPUnit\Framework\TestCase {

	public function testInvalidConstructor() {
		$this->expectException(\InvalidArgumentException::class);
		new Entity(array(new \stdClass(), new \stdClass()));
	}

	public function testStringConstructor() {
		$Split1 = new Split(1.00, 260, false);
		$Split2 = new Split(1.00, 200, true);
		$Splits = new Entity($Split1->asString().Entity::SEPARATOR.$Split2->asString());

		$this->assertFalse($Splits->isEmpty());
		$this->assertEquals(2, $Splits->num());
		$this->assertEquals($Split1, $Splits->at(0));
		$this->assertEquals($Split2, $Splits->at(1));
		$this->assertEquals(
			$Split1->asString().Entity::SEPARATOR.$Split2->asString(),
			$Splits->asString()
		);
	}

	public function testArrays() {
		$Split1 = new Split(1.00, 260, false);
		$Split2 = new Split(1.00, 200, true);
		$Splits1 = new Entity(array($Split1, $Split2));

		$Splits2 = new Entity();
		$Splits2->add($Split1);
		$Splits2->add($Split2);

		$this->assertEquals(2, count($Splits1->asArray()));
		$this->assertEquals($Splits1->asArray(), $Splits2->asArray());
	}

	public function testSummation() {
		$Splits = new Entity(array(
			new Split(1, 300),
			new Split(1.5, 450),
			new Split(1.5, 450),
			new Split(2, 600)
		));

		$this->assertEquals(6, $Splits->totalDistance());
		$this->assertEquals(1800, $Splits->totalTime());
	}

	public function testActiveAndInactiveFlags() {
		$Split1 = new Split(1.00, 300, false);
		$Split2 = new Split(1.00, 300, false);
		$Splits = new Entity(array($Split1, $Split2));

		$this->assertFalse($Splits->hasActiveAndInactiveLaps());
		$this->assertFalse($Splits->hasActiveLaps());

		$Split1->setResting(false);

		$this->assertTrue($Splits->hasActiveAndInactiveLaps());
		$this->assertTrue($Splits->hasActiveLaps());
		$this->assertFalse($Splits->hasActiveLaps(2));

		$Split2->setResting(false);

		$this->assertTrue($Splits->hasActiveLaps(2));
	}

}
