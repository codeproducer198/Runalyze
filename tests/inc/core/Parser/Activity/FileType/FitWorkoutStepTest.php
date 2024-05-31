<?php

namespace Runalyze\Tests\Parser\Activity;

use Runalyze\Activity\LapIntensity;
use Runalyze\Parser\Activity\FileType\FitWorkoutStep;

// #TSC
class FitWorkoutStepTest extends \PHPUnit\Framework\TestCase
{
    /** @var FitWorkoutStep */
    protected $underTest;

    public function setUp() : void
    {
        $this->underTest = new FitWorkoutStep();

        $valLap0 = array(
            'intensity' => array("0", "active"),
            'custom_target_speed_low' => array("3175", "11.430 km/h"),
            'custom_target_speed_high' => array("3333", "11.999 km/h")
        );
        $valLap1 = array(
            'weight_display_unit' => array("1", "kilogram")
        );
        $valLap2 = array(
            'intensity' => array("1", "rest"),
            'custom_target_heart_rate_low' => array("128", "128 bpm"),
            'custom_target_heart_rate_high' => array("190", "190 bpm")
        );

        $this->underTest->collectWorkoutstep($valLap0);
        $this->underTest->collectWorkoutstep($valLap1);
        $this->underTest->collectWorkoutstep($valLap2);
    }

    public function testLap0Speed()
    {
        $lap = 0;
        $this->assertEquals(LapIntensity::getInstanceActive(), $this->underTest->getIntensity($lap));
        $this->assertEquals(array(
            FitWorkoutStep::ADD_TARGET_SPEED . ' low' => 3.175,
            FitWorkoutStep::ADD_TARGET_SPEED . ' high' => 3.333
        ), $this->underTest->getSplitAdditionals($lap));
    }

    public function testLap1Speed()
    {
        $lap = 1;
        $this->assertNull($this->underTest->getIntensity($lap));
        $this->assertNull($this->underTest->getSplitAdditionals($lap));
    }

    public function testLap2Hr()
    {
        $lap = 2;
        $this->assertEquals(LapIntensity::getInstanceRest(), $this->underTest->getIntensity($lap));
        $this->assertEquals(array(
            FitWorkoutStep::ADD_TARGET_HR . ' low' => 128,
            FitWorkoutStep::ADD_TARGET_HR . ' high' => 190
        ), $this->underTest->getSplitAdditionals($lap));
    }
}
