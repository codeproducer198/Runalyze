<?php

namespace Runalyze\Bundle\CoreBundle\Tests\Services;

use Runalyze\Service\RouteNameEvaluation\OsmCsvData;
use Runalyze\Service\RouteNameEvaluation\OsmCsvDataCollection;

class OsmCsvDataCollectionTest extends \PHPUnit\Framework\TestCase {
    public function setUp() : void {
    }

    public function testCsvCollectionEmpty() {
        $in = "@type|name|name:de|place|traffic_sign|tourism|landuse|natural|ele|hiking|sac_scale|amenity" . PHP_EOL;
        $eval = new OsmCsvDataCollection($in);
        $this->assertFalse($eval->hasNames());

        $in = "" . PHP_EOL;
        $eval = new OsmCsvDataCollection($in);
        $this->assertFalse($eval->hasNames());
    }

    public function testRunningCsvCollection() {
        $in = "@type|name|name:de|place|traffic_sign|tourism|landuse|natural|ele|hiking|sac_scale|amenity" . PHP_EOL .
              "node|Heimathatten|||city_limit|||||||" . PHP_EOL .
              "node|Shoe|Schuh||city_limit|||||||" . PHP_EOL .
              "node|Rind Routengabelung||||information||||||" . PHP_EOL .
              "node|Wallfahrtsstätten und Pilgerwege||||information||||||" . PHP_EOL .
              "node|Rattenhof||hamlet||||||||" . PHP_EOL .
              "node|Rattenhof|||city_limit|||||||" . PHP_EOL .
              "node|Freidenried||town||||||||" . PHP_EOL .
              "way|Wäldchen||||||wood||||" . PHP_EOL .
              "way|Pfingstenholz|||||forest|||||" . PHP_EOL .
              "node|Aachen|AachenDe|town||||||||" . PHP_EOL .
              "relation|See||||||water||||" . PHP_EOL .
              "way|Schuher Holz|||||forest|||||" . PHP_EOL;
        $eval = new OsmCsvDataCollection($in);
        $this->assertTrue($eval->hasNames());

        $this->assertEquals([], $eval->getNames(OsmCsvData::$CAT_PEAK));

        // first places, water, forest/wood
        $expectedNames = ["AachenDe", "Freidenried", "Rattenhof", "Heimathatten", "Schuh", "See", "Pfingstenholz", "Schuher Holz", "Wäldchen"];
        $this->assertEquals($expectedNames, $eval->getNames(OsmCsvData::$CAT_PLACE));

        $this->assertEquals([], $eval->getNames(OsmCsvData::$CAT_HUT));
        $this->assertEquals([], $eval->getNames(OsmCsvData::$CAT_WAY));

        $expectedInfoNames = ["Rind Routengabelung", "Wallfahrtsstätten und Pilgerwege"];
        $this->assertEquals($expectedInfoNames, $eval->getNames(OsmCsvData::$CAT_INFO));
    }

    public function testHikingCsvCollectionSpieser() {
        $in =   "@type|name|name:de|place|traffic_sign|tourism|landuse|natural|ele|hiking|sac_scale|amenity" . PHP_EOL .
                "node|Spieser||||||peak|1651|||" . PHP_EOL .
                "node|Spieser||||information|||1645|yes||" . PHP_EOL .
                "node|Später Gund||||information|||1503|yes||" . PHP_EOL .
                "node|Steinpaßsattl||||information|||1556|yes||" . PHP_EOL .
                "node|Steinpaßsattl||locality||||||||" . PHP_EOL .
                "node|Im Steinbest||||information|||1505|yes||" . PHP_EOL .
                "node|Buchel-Alpe||||information|||1276|yes||" . PHP_EOL .
                "node|Obergschwend||hamlet||||||||" . PHP_EOL .
                "node|Obergschwend||||information|||1053|yes||" . PHP_EOL .
                "node|Wertacher Hörnle||||||peak|1695|||" . PHP_EOL .
                "node|Wertacher Hörnle||||information|||1692|yes||" . PHP_EOL .
                "node|Wertacher Hörnle||||information|||1680|yes||" . PHP_EOL .
                "way|Wanderweg 43|||||||||mountain_hiking|" . PHP_EOL .
                "way|Wanderweg 43|||||||||demanding_mountain_hiking|" . PHP_EOL;

        $eval = new OsmCsvDataCollection($in);
        $this->assertTrue($eval->hasNames());

        $this->assertEquals(["Wertacher Hörnle", "Spieser"], $eval->getNames(OsmCsvData::$CAT_PEAK));
        $this->assertEquals(["Obergschwend"], $eval->getNames(OsmCsvData::$CAT_PLACE));
        $this->assertEquals([], $eval->getNames(OsmCsvData::$CAT_HUT));
        $this->assertEquals(["Wanderweg 43"], $eval->getNames(OsmCsvData::$CAT_WAY));
        $this->assertEquals(["Buchel Alpe", "Im Steinbest", "Später Gund", "Steinpaßsattl"], $eval->getNames(OsmCsvData::$CAT_INFO));
    }

    public function testRunningCsvCollectionOnlyInfo() {
        $in = "@type|name|name:de|place|traffic_sign|tourism|landuse|natural|ele|hiking|sac_scale|amenity" . PHP_EOL .
              "node|Bbbb||||information||||||" . PHP_EOL .
              "node|Aaaa||||information||||||" . PHP_EOL;
        $eval = new OsmCsvDataCollection($in);

        $this->assertEquals([], $eval->getNames(OsmCsvData::$CAT_PEAK));
        $this->assertEquals([], $eval->getNames(OsmCsvData::$CAT_PLACE));
        $this->assertEquals([], $eval->getNames(OsmCsvData::$CAT_HUT));
        $this->assertEquals([], $eval->getNames(OsmCsvData::$CAT_WAY));

        $expectedInfoNames = ["Aaaa", "Bbbb"];
        $this->assertEquals($expectedInfoNames, $eval->getNames(OsmCsvData::$CAT_INFO));
    }

    public function testHikingCsvCollection() {
        $in = "@type|name|name:de|place|traffic_sign|tourism|landuse|natural|ele|hiking|sac_scale|amenity" . PHP_EOL .
              "node|Grubistein-Vor-gipfel||||||peak||||" . PHP_EOL .
              "node|Grubigstein||||||peak|2230|||" . PHP_EOL .
              "node|Stepbergalm||||||||||restaurant" . PHP_EOL .
              "node|Gartner Wand||locality||||peak|2377|||" . PHP_EOL .
              "node|Sommerbergjöchle||||information||saddle||||" . PHP_EOL .
              "way|Wolfratshauser Hütte||||alpine_hut|||1751|||" . PHP_EOL .
              "way|Wolfratshauser Trail|||||||||demanding_mountain_hiking|" . PHP_EOL .
              "way|Wolfratshauser Trail|||||||||mountain_hiking|" . PHP_EOL;
        $eval = new OsmCsvDataCollection($in);

        $this->assertEquals(["Gartner Wand", "Grubigstein", "Grubistein Vor gipfel", "Sommerbergjöchle"],
            $eval->getNames(OsmCsvData::$CAT_PEAK));
        $this->assertEquals([], $eval->getNames(OsmCsvData::$CAT_PLACE));
        $this->assertEquals(["Stepbergalm", "Wolfratshauser Hütte"], $eval->getNames(OsmCsvData::$CAT_HUT));
        $this->assertEquals(["Wolfratshauser Trail"], $eval->getNames(OsmCsvData::$CAT_WAY));
        $this->assertEquals([], $eval->getNames(OsmCsvData::$CAT_INFO));
    }

    public function testHikingCsvCollectionKramer() {
        $in = "@type|name|name:de|place|traffic_sign|tourism|landuse|natural|ele|hiking|sac_scale|amenity" . PHP_EOL .
              "node|Kramerspitz||||||peak|1985|||" . PHP_EOL .
              "node|Königsstand||||||peak|1453|||" . PHP_EOL .
              "node|Wandergebiet Kramer||||information||||||" . PHP_EOL .
              "way|Kramersteig||||||||yes|hiking|" . PHP_EOL .
              "relation|See||||||water||||" . PHP_EOL .
              "way|Kellerleitensteig||||||||yes|hiking|" . PHP_EOL .
              "node|Garmisch||town||||||||" . PHP_EOL .
              "way|Kramersteig||||||||yes|hiking|" . PHP_EOL .
              "way|Kramerplateauweg||||||||yes|hiking|";;
        $eval = new OsmCsvDataCollection($in);

        $this->assertEquals(["Kramerspitz", "Königsstand"], $eval->getNames(OsmCsvData::$CAT_PEAK));
        $this->assertEquals(["Garmisch", "See"], $eval->getNames(OsmCsvData::$CAT_PLACE));
        $this->assertEquals([], $eval->getNames(OsmCsvData::$CAT_HUT));
        $this->assertEquals(["Kellerleitensteig", "Kramerplateauweg", "Kramersteig"], $eval->getNames(OsmCsvData::$CAT_WAY));
        $this->assertEquals(["Wandergebiet Kramer"], $eval->getNames(OsmCsvData::$CAT_INFO));
    }
}