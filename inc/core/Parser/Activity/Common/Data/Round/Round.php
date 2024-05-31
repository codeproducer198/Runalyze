<?php

namespace Runalyze\Parser\Activity\Common\Data\Round;

use Runalyze\Activity\LapIntensity;

class Round
{
    /** @var float [km] */
    protected $Distance;

    /** @var int|float [s] */
    protected $Duration;

    /** @var LapIntensity */
    protected $Intensity;

    /**
     * @param float $distance [km]
     * @param int|float $duration [s]
     * @param bool $isActive
     */
    public function __construct($distance, $duration, $isActive = true)
    {
        $this->Distance = $distance;
        $this->Duration = $duration;
        if ($isActive) {
            $this->Intensity = LapIntensity::getInstanceActive();
        } else {
            $this->Intensity = LapIntensity::getInstanceRest();
        }
    }

    /**
     * @param LapIntensity $intensity
     */
    public function setIntensity(LapIntensity $intensity)
    {
        $this->Intensity = $intensity;
    }

    /**
     * @return LapIntensity
     */
    public function getIntensity()
    {
        return $this->Intensity;
    }

    /**
     * @param float $distance [km]
     */
    public function setDistance($distance)
    {
        $this->Distance = $distance;
    }

    /**
     * @return float [km]
     */
    public function getDistance()
    {
        return $this->Distance;
    }

    /**
     * @param int|float $duration [s]
     */
    public function setDuration($duration)
    {
        $this->Duration = $duration;
    }

    /**
     * @return int|float [s]
     */
    public function getDuration()
    {
        return $this->Duration;
    }

    public function roundDuration()
    {
        $this->Duration = (int)round($this->Duration);
    }

    /**
     * @param bool $flag
     */
    public function setActive($flag = true)
    {
        $this->Intensity = $flag ? LapIntensity::getInstanceActive() : LapIntensity::getInstanceRest();
    }

    /**
     * @return bool
     */
    public function isActive()
    {
        return $this->Intensity->isNotRest();
    }

    /**
     * @return bool
     */
    public function isEqualTo(Round $other)
    {
        return (
            $this->Duration == $other->getDuration() &&
            $this->Distance == $other->getDistance() &&
            $this->Intensity->isEqualTo($other->getIntensity())
        );
    }
}
