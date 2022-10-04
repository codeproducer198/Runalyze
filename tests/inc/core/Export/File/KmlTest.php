<?php

namespace Runalyze\Export\File;

use Runalyze\View\Activity\FakeContext;

/**
 * @group dependsOn
 * @group dependsOnOldFactory
 */
class KmlTest extends \PHPUnit\Framework\TestCase
{
	public function testFileCreationForOutdoorActivity()
	{
		$Exporter = new Kml(FakeContext::outdoorContext());
		$Exporter->createFileWithoutDirectDownload();
	}
}
