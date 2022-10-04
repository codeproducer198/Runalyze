<?php

namespace Runalyze\Activity;

use PDO;

/**
 * @group dependsOn
 * @group dependsOnOldDatabase
 */
class DuplicateFinderTest extends \PHPUnit\Framework\TestCase
{
	/** @var \PDO */
	protected $PDO;

	/** @var \Runalyze\Activity\DuplicateFinder */
	protected $Finder;

	public function setUp() : void
	{
		$this->PDO = \DB::getInstance();
		$this->PDO->exec('DELETE FROM `'.PREFIX.'training`');
		$this->PDO->exec('INSERT INTO `'.PREFIX.'training` (`activity_id`, `accountid`, `sportid`, `time`, `s`) VALUES (1448797800, 0, 0, 1477839906, 2)');
		$this->PDO->exec('INSERT INTO `'.PREFIX.'training` (`activity_id`, `accountid`, `sportid`, `time`, `s`) VALUES (1450823800, 0, 0, 1477839906, 2)');

		$this->Finder = new DuplicateFinder($this->PDO, 0);
	}

	public function tearDown() : void
	{
		$this->PDO->exec('DELETE FROM `'.PREFIX.'training`');
	}

	public function testDuplicate()
	{
	    $this->assertTrue($this->Finder->checkForDuplicate(1448797800));
	}

	public function testNewActivity()
	{
	    $this->assertFalse($this->Finder->checkForDuplicate(1234567890));
	}

	public function testInvalidArgument()
	{
		$this->expectException(\InvalidArgumentException::class);
		$this->Finder->checkForDuplicate('foobar');
	}

	public function testSingleNull()
	{
		$this->assertFalse($this->Finder->checkForDuplicate(null));
	}

	public function testDuplicates()
	{
	    $this->assertEquals([
	    		'1234567890' => false,
	    		'1448797800' => true,
	    		'1448797980' => false,
	    		'1450823800' => true,
	    		'9876543210' => false
	    	], $this->Finder->checkForDuplicates([
	    		1234567890,
	    		1448797800,
	    		1448797980,
	    		1450823800,
	    		9876543210
	    	])
	    );
	}

	public function testDuplicatesWithNull()
	{
		$this->assertEquals([
				null => false,
				'1234567890' => false,
				'1448797800' => true
			], $this->Finder->checkForDuplicates([
				1234567890,
				null,
				1448797800,
				null
			])
		);
	}

	public function testDuplicatesWithOnlyNull()
	{
		$this->assertEquals([
				null => false
			], $this->Finder->checkForDuplicates([
				null,
				null
			])
		);
	}

	public function testInvalidArgumentForDuplicates()
	{
		$this->expectException(\InvalidArgumentException::class);
		$this->Finder->checkForDuplicates([
	    	1448797800,
	    	'foobar',
	    	1234567890
	    ]);
	}
}
