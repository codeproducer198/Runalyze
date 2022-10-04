<?php

namespace Runalyze\Data\Weather;

class SourcesTest extends \PHPUnit\Framework\TestCase
{

	public function testThatStringsAreDefined()
	{
		foreach (Sources::getEnum() as $id) {
			Sources::stringFor($id);
		}
	}

}
