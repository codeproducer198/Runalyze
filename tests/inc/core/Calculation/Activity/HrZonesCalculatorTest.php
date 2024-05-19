<?php

namespace Runalyze\Calculation\Activity;

class HrZonesCalculatorTest extends \PHPUnit\Framework\TestCase
{
	public function testValidateInput()
	{
		// ok
		$this->assertFalse(HrZonesCalculator::validateInputZone("t1:130:#12345f"));
		$this->assertFalse(HrZonesCalculator::validateInputZone("t1:130:#1A345c"));
		$this->assertFalse(HrZonesCalculator::validateInputZone("t1:130:#123456"));
		$this->assertFalse(HrZonesCalculator::validateInputZone("t2:13"));
		$this->assertFalse(HrZonesCalculator::validateInputZone("130"));

		// invalid - general format
		$this->assertTrue(HrZonesCalculator::validateInputZone("t1::#000000"));
		$this->assertTrue(HrZonesCalculator::validateInputZone(""));
		$this->assertTrue(HrZonesCalculator::validateInputZone("#000000"));
		$this->assertTrue(HrZonesCalculator::validateInputZone("t1"));
		$this->assertTrue(HrZonesCalculator::validateInputZone("t1:130|#000000"));
		$this->assertTrue(HrZonesCalculator::validateInputZone("t1:130#000000"));

		// invalid - not enough bpm / to much bpm
		$this->assertTrue(HrZonesCalculator::validateInputZone("t1:1:#000000"));
		$this->assertTrue(HrZonesCalculator::validateInputZone("1"));
		$this->assertTrue(HrZonesCalculator::validateInputZone("t1:1234:#000000"));
		$this->assertTrue(HrZonesCalculator::validateInputZone(""));
		$this->assertTrue(HrZonesCalculator::validateInputZone("t1::#000000"));

		// invalid - wrong color
		$this->assertTrue(HrZonesCalculator::validateInputZone("t1:80:#00000")); // less
		$this->assertTrue(HrZonesCalculator::validateInputZone("123:#G00000")); // no hex
		$this->assertTrue(HrZonesCalculator::validateInputZone("123:#000:00")); // no hex

		// invalid text
		$this->assertTrue(HrZonesCalculator::validateInputZone("12345678901234567890123456:80:#00000")); // to long
		$this->assertTrue(HrZonesCalculator::validateInputZone("12345678901234567890123456:80")); // to long
	}

	public function testSorting()
	{
		$expected = array(
			"1",
			"t2:13",
			"t1:130:#000000"
		);
		$this->assertEquals($expected, HrZonesCalculator::sortZones(array(
			"t1:130:#000000",
			"t2:13",
			"1"
		)));
	}

	public function testFitZones()
	{
		// we need 6 zones without text&color
		$underTest = new HrZonesCalculator(array("90", "100", "120", "130", "135", "180"), null);
		$result = $underTest->getZones(25, 130, 180);
		$this->assertEquals(array(
			array(                      'hr' => 25),
			array('t' => "B1 Warm up",  'hr' => 90 , 'c' => "#a6a6a6"),
			array('t' => "B2 Easy",     'hr' => 100, 'c' => "#3b97f3"),
			array('t' => "B3 Aerob",    'hr' => 120, 'c' => "#82c91e"),
			array('t' => "B4 Intensiv", 'hr' => 130, 'c' => "#f98925"),
			array('t' => "B5 Maximal",  'hr' => 135, 'c' => "#d32020"),
			array('t' => "B6 Override", 'hr' => 180, 'c' => "#b80000"),
		),$result);
		$this->assertEquals("activity stored zones", $underTest->getSourceHint());
		// sort of array
		$this->assertEquals(6, array_key_first($result));
		$this->assertEquals(0, array_key_last($result));
		$this->assertEquals(array('hr' => 25), end($result));

		// with min/max
		$result = $underTest->getZones(95, 190, 180);
		$this->assertEquals(array(
			array(                      'hr' => 0),
			array('t' => "B1 Warm up",  'hr' => 90 , 'c' => "#a6a6a6"),
			array('t' => "B2 Easy",     'hr' => 100, 'c' => "#3b97f3"),
			array('t' => "B3 Aerob",    'hr' => 120, 'c' => "#82c91e"),
			array('t' => "B4 Intensiv", 'hr' => 130, 'c' => "#f98925"),
			array('t' => "B5 Maximal",  'hr' => 135, 'c' => "#d32020"),
			array('t' => "B6 Override", 'hr' => 180, 'c' => "#b80000"),
			array(                      'hr' => 191),
		),$result);
		$this->assertEquals("activity stored zones", $underTest->getSourceHint());
	}

	public function testDefaultZones()
	{
		$underTest = new HrZonesCalculator(null, null);
		$result = $underTest->getZones(5, 80, 100);
		$this->assertEquals(array(
			array('hr' => 5),
			array('hr' => 31),
			array('hr' => 41),
			array('hr' => 51,  'c' => "#a6a6a6"),
			array('hr' => 61,  'c' => "#3b97f3"),
			array('hr' => 71,  'c' => "#82c91e"),
			array('hr' => 81,  'c' => "#f98925"),
			array('hr' => 91,  'c' => "#d32020"),
			array('hr' => 101, 'c' => "#b80000"),
		),$result);
		$this->assertEquals("default zones", $underTest->getSourceHint());
		// sort of array
		$this->assertEquals(8, array_key_first($result));
		$this->assertEquals(0, array_key_last($result));
		$this->assertEquals(array('hr' => 5), end($result));

		// with min/max
		$result = $underTest->getZones(55, 110, 100);
		$this->assertEquals(array(
			array('hr' => 0),
			array('hr' => 31),
			array('hr' => 41),
			array('hr' => 51,  'c' => "#a6a6a6"),
			array('hr' => 61,  'c' => "#3b97f3"),
			array('hr' => 71,  'c' => "#82c91e"),
			array('hr' => 81,  'c' => "#f98925"),
			array('hr' => 91,  'c' => "#d32020"),
			array('hr' => 101, 'c' => "#b80000"),
			array('hr' => 111),
		),$result);
		$this->assertEquals("default zones", $underTest->getSourceHint());
	}

	public function testSportZones()
	{
		$underTest = new HrZonesCalculator(null, array("t1:70:#000011", "100:#002200", "t3:120"));
		$result = $underTest->getZones(71, 110, 150);
		$this->assertEquals(array(
			array('hr' => 0),
			array('t' => "t1", 'hr' => 70,  'c' => "#000011"),
			array(             'hr' => 100, 'c' => "#002200"),
			array('t' => "t3", 'hr' => 120,),
		),$result);
		$this->assertEquals("sport stored zones", $underTest->getSourceHint());
		// sort of array
		$this->assertEquals(3, array_key_first($result));
		$this->assertEquals(0, array_key_last($result));
		$this->assertEquals(array('hr' => 0), end($result));

		// with min/max
		$result = $underTest->getZones(52, 160, 150);
		$this->assertEquals(array(
			array('hr' => 52),
			array('t' => "t1", 'hr' => 70,  'c' => "#000011"),
			array(             'hr' => 100, 'c' => "#002200"),
			array('t' => "t3", 'hr' => 120,),
			array(             'hr' => 161,),
		),$result);
		$this->assertEquals("sport stored zones", $underTest->getSourceHint());
		$this->assertEquals(array('hr' => 52), end($result));
	}
}
