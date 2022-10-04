<?php

namespace Runalyze\Tests\Profile\Mapping;

use Runalyze\Profile\Mapping\FitSdkExercise; 

class FitSdkExcerciseTest extends \PHPUnit\Framework\TestCase {
    protected $underTest;

    public function setUp() : void {
        $this->underTest = new FitSdkExercise();
    }

    public function testFullMap_1() {
        $this->assertEquals("curl, bench_press", $this->underTest->getFullMapping("7,65534,0", null));
    }

    public function testFullMap_2() {
        $this->assertEquals("triceps_extension, ez_bar_pullover", $this->underTest->getFullMapping("30,21,65534", "65535,8,65535"));
    }

    public function testFullMapWrongSub() {
        $this->assertEquals("triceps_extension, pull_up", $this->underTest->getFullMapping("30,21,65534", "65535"));
    }

    public function testCategory() {
        $this->assertEquals("flye", $this->underTest->getMapping(9, null));
        $this->assertEquals("flye", $this->underTest->getMapping(9));
    }

    public function testFailedCategory() {
        $this->assertEquals(null, $this->underTest->getMapping(666, null));
    }

    public function testUnknownCategory() {
        $this->assertEquals(null, $this->underTest->getMapping(65534));
    }

    public function testCategoryWithSub_1() {
        $this->assertEquals("single_leg_decline_push_up", $this->underTest->getMapping(1, 11));
    }

    public function testCategoryWithSub_30() {
        $this->assertEquals("weighted_dip", $this->underTest->getMapping(30, 40));
    }

    public function testCategoryWithSubNotExists() {
        // use only exercise
        $this->assertEquals("calf_raise", $this->underTest->getMapping(1, 99));
    }

    public function testCategoryWithSubUnused() {
        // use only exercise
        $this->assertEquals("sit_up", $this->underTest->getMapping(27, 65535));
    }
}
