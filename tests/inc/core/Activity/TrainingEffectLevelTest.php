<?php

namespace Runalyze\Activity;

class TrainingEffectLevelTest extends \PHPUnit\Framework\TestCase
{
	public function testInvalidLevelTooSmall()
	{
        $this->expectException(\InvalidArgumentException::class);
        TrainingEffectLevel::levelFor(0.9);
	}

    public function testInvalidLevelTooBig()
    {
        $this->expectException(\InvalidArgumentException::class);
        TrainingEffectLevel::levelFor(5.1);
    }

    public function testInvalidLevelNonNumeric()
    {
        $this->expectException(\InvalidArgumentException::class);
        TrainingEffectLevel::levelFor(false);
    }

    public function testInvalidLevelString()
    {
        $this->expectException(\InvalidArgumentException::class);
        TrainingEffectLevel::levelFor('foobar');
    }

    public function testValidLevels()
    {
        $this->assertEquals(TrainingEffectLevel::EASY, TrainingEffectLevel::levelFor(1.0));
        $this->assertEquals(TrainingEffectLevel::EASY, TrainingEffectLevel::levelFor(1.9));
        $this->assertEquals(TrainingEffectLevel::MAINTAINING, TrainingEffectLevel::levelFor(2.0));
        $this->assertEquals(TrainingEffectLevel::MAINTAINING, TrainingEffectLevel::levelFor(2.9));
        $this->assertEquals(TrainingEffectLevel::IMPROVING, TrainingEffectLevel::levelFor(3.0));
        $this->assertEquals(TrainingEffectLevel::IMPROVING, TrainingEffectLevel::levelFor(3.9));
        $this->assertEquals(TrainingEffectLevel::HIGHLY_IMPROVING, TrainingEffectLevel::levelFor(4.0));
        $this->assertEquals(TrainingEffectLevel::HIGHLY_IMPROVING, TrainingEffectLevel::levelFor(4.9));
        $this->assertEquals(TrainingEffectLevel::OVERREACHING, TrainingEffectLevel::levelFor(5.0));
    }

    public function testLabelForInvalidLevel()
    {
        $this->expectException(\InvalidArgumentException::class);
        TrainingEffectLevel::label(0);
    }

    public function testDescriptionForInvalidLevel()
    {
        $this->expectException(\InvalidArgumentException::class);
        TrainingEffectLevel::description(TrainingEffectLevel::OVERREACHING + 1);
    }

    public function testThatLabelAndDescriptionAreDefinedForAllLevels()
    {
        foreach (TrainingEffectLevel::getEnum() as $level) {
            $this->assertNotEmpty(TrainingEffectLevel::label($level));
            $this->assertNotEmpty(TrainingEffectLevel::description($level));
        }
    }
}
