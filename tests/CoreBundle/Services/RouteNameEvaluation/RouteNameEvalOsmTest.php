<?php

namespace Runalyze\Bundle\CoreBundle\Tests\Services;

use Runalyze\Service\RouteNameEvaluation\RouteNameEvalOsm;
use Runalyze\Service\RouteNameEvaluation\OsmCsvDataCollection;
use Runalyze\Service\RouteNameEvaluation\OsmCsvData;
use Runalyze\Bundle\CoreBundle\Entity\Sport;
use Runalyze\Profile\Sport\SportProfile;

use GuzzleHttp\Client;

/**
 * Test for RouteNameEvalOsm.
 */
class RouteNameEvalOsmTest extends \PHPUnit\Framework\TestCase {
    private $underTest;
    private $coll;

    public function setUp() : void {
        $this->underTest = new RouteNameEvalOsm("", "", new Client());
        $this->coll = $this->getMockBuilder(OsmCsvDataCollection::class)->disableOriginalConstructor()->getMock();
    }

    private function getSportRunning(): Sport {
        $s = new Sport();
        $s->setInternalSportId(SportProfile::RUNNING);
        return $s;
    }

    private function getSportMountain(): Sport {
        $s = new Sport();
        $s->setInternalSportId(SportProfile::MOUNTAINEERING);
        return $s;
    }

    public function testCreateResultAllExists() {
        $this->coll->method('getNames')->will($this->returnValueMap(
            [
                [OsmCsvData::$CAT_PEAK, ["apeak1", "apeak2"] ],
                [OsmCsvData::$CAT_PLACE, ["aplace"] ],
                [OsmCsvData::$CAT_HUT, ["ahut"] ],
                [OsmCsvData::$CAT_WAY, ["away"] ],
                [OsmCsvData::$CAT_INFO, ["ainfo"] ]
            ]));

        $result = $this->underTest->createResult($this->getSportRunning(), $this->coll);

        $this->assertEquals("apeak1 - apeak2 - aplace", $result->getNames());
        $this->assertEquals("Verpflegung: ahut\nWege: away\nInfo: ainfo\n", $result->getNotes());
    }

    public function testCreateResultOnlyPeak() {
        $this->coll->method('getNames')->will($this->returnValueMap(
            [
                [OsmCsvData::$CAT_PEAK, ["apeak1"] ],
                [OsmCsvData::$CAT_PLACE, [] ],
                [OsmCsvData::$CAT_HUT, [] ],
                [OsmCsvData::$CAT_WAY, [] ],
                [OsmCsvData::$CAT_INFO, [] ]
            ]));

        $result = $this->underTest->createResult($this->getSportRunning(), $this->coll);

        $this->assertEquals("apeak1", $result->getNames());
        $this->assertEquals("", $result->getNotes());
    }

    public function testCreateResultPeakHutMissing() {
        $this->coll->method('getNames')->will($this->returnValueMap(
            [
                [OsmCsvData::$CAT_PEAK, [] ],
                [OsmCsvData::$CAT_PLACE, ["aplace1", "aplace2"] ],
                [OsmCsvData::$CAT_HUT, [] ],
                [OsmCsvData::$CAT_WAY, ["away"] ],
                [OsmCsvData::$CAT_INFO, ["ainfo"] ]
            ]));

        $result = $this->underTest->createResult($this->getSportRunning(), $this->coll);

        $this->assertEquals("aplace1 - aplace2", $result->getNames());
        $this->assertEquals("Wege: away\nInfo: ainfo\n", $result->getNotes());
    }

    public function testCreateResultOnlyPlace() {
        $this->coll->method('getNames')->will($this->returnValueMap(
            [
                [OsmCsvData::$CAT_PEAK, [] ],
                [OsmCsvData::$CAT_PLACE, ["aplace1", "aplace2"] ],
                [OsmCsvData::$CAT_HUT, [] ],
                [OsmCsvData::$CAT_WAY, [] ],
                [OsmCsvData::$CAT_INFO, [] ]
            ]));

        $result = $this->underTest->createResult($this->getSportRunning(), $this->coll);

        $this->assertEquals("aplace1 - aplace2", $result->getNames());
        $this->assertEquals("", $result->getNotes());
    }

    public function testCreateResultPeakPlaceMissing() {
        $this->coll->method('getNames')->will($this->returnValueMap(
            [
                [OsmCsvData::$CAT_PEAK, [] ],
                [OsmCsvData::$CAT_PLACE, [] ],
                [OsmCsvData::$CAT_HUT, ["ahut1", "ahut2"] ],
                [OsmCsvData::$CAT_WAY, ["away1", "away2"] ],
                [OsmCsvData::$CAT_INFO, ["ainfo"] ]
            ]));

        $result = $this->underTest->createResult($this->getSportRunning(), $this->coll);

        $this->assertEquals("ahut1 - ahut2 - away1 - away2", $result->getNames());
        // no huts & ways, because they already in "names"
        $this->assertEquals("Info: ainfo\n", $result->getNotes());
    }

    public function testCreateResultPeakPlaceHutMissing() {
        $this->coll->method('getNames')->will($this->returnValueMap(
            [
                [OsmCsvData::$CAT_PEAK, [] ],
                [OsmCsvData::$CAT_PLACE, [] ],
                [OsmCsvData::$CAT_HUT, [] ],
                [OsmCsvData::$CAT_WAY, ["away1", "away2"] ],
                [OsmCsvData::$CAT_INFO, ["ainfo"] ]
            ]));

        $result = $this->underTest->createResult($this->getSportRunning(), $this->coll);

        $this->assertEquals("away1 - away2", $result->getNames());
        // no huts & ways, because they already in "names"
        $this->assertEquals("Info: ainfo\n", $result->getNotes());
    }

    public function testCreateResultPeakPlaceHutWayMissing() {
        $this->coll->method('getNames')->will($this->returnValueMap(
            [
                [OsmCsvData::$CAT_PEAK, [] ],
                [OsmCsvData::$CAT_PLACE, [] ],
                [OsmCsvData::$CAT_HUT, [] ],
                [OsmCsvData::$CAT_WAY, [] ],
                [OsmCsvData::$CAT_INFO, ["ainfo1", "ainfo2"] ]
            ]));

        $result = $this->underTest->createResult($this->getSportRunning(), $this->coll);

        $this->assertEquals("ainfo1 - ainfo2", $result->getNames());
        // no info, because they already in "names"
        $this->assertEquals("", $result->getNotes());
    }

    public function testCreateResultAllMissing() {
        $this->coll->method('getNames')->will($this->returnValueMap(
            [
                [OsmCsvData::$CAT_PEAK, [] ],
                [OsmCsvData::$CAT_PLACE, [] ],
                [OsmCsvData::$CAT_HUT, [] ],
                [OsmCsvData::$CAT_WAY, [] ],
                [OsmCsvData::$CAT_INFO, [] ]
            ]));

        $result = $this->underTest->createResult($this->getSportRunning(), $this->coll);

        $this->assertNull($result);
    }

    public function testCreateResultHutWalking() {
        $this->coll->method('getNames')->will($this->returnValueMap(
            [
                [OsmCsvData::$CAT_PEAK, [] ],
                [OsmCsvData::$CAT_PLACE, ["aplace"] ],
                [OsmCsvData::$CAT_HUT, ["ahut"] ],
                [OsmCsvData::$CAT_WAY, [] ],
                [OsmCsvData::$CAT_INFO, [] ]
            ]));

        $result = $this->underTest->createResult($this->getSportRunning(), $this->coll);

        $this->assertEquals("aplace", $result->getNames());
        $this->assertEquals("Verpflegung: ahut\n", $result->getNotes());

        // special case "hüttenwandern; the hut is part of "names"
        $result = $this->underTest->createResult($this->getSportMountain(), $this->coll);

        $this->assertEquals("aplace - ahut", $result->getNames());
        $this->assertEquals("", $result->getNotes());
    }

    public function testCreateResultLimits() {
        $a = array();
        // create place array with over 255 length
        for($i = 0; $i < 8; $i++) {
            $a[] = str_repeat($i, 32);
        };
        
        $this->coll->method('getNames')->will($this->returnValueMap(
            [
                [OsmCsvData::$CAT_PEAK, [] ],
                [OsmCsvData::$CAT_PLACE, $a ],
                [OsmCsvData::$CAT_HUT, [] ],
                [OsmCsvData::$CAT_WAY, [] ],
                [OsmCsvData::$CAT_INFO, [] ]
            ]));

        $result = $this->underTest->createResult($this->getSportRunning(), $this->coll);

        // last index is ignored to avoid places with "anyna..."
        $this->assertEquals(implode(' - ', array_slice($a, 0 , 7)), $result->getNames());
    }

    protected function endsWith($haystack, $needle) {
        return $needle === "" || (substr($haystack, -strlen($needle)) === $needle);
    }
}