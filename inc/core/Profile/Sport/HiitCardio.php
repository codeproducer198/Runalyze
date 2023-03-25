<?php

namespace Runalyze\Profile\Sport;

use Runalyze\Metrics\Velocity\Unit\PaceEnum;

/**
 * #TSC add new sport "HITT" and "Cardio"
 * @codeCoverageIgnore
 */
class HiitCardio extends AbstractSport
{
    public function __construct()
    {
        parent::__construct(SportProfile::HIIT_CARDIO);
    }

    public function getIconClass()
    {
        return 'icons8-Pushups';
    }

    public function getName()
    {
        // #TSC: translation is available
        return __('HIIT Cardio');
    }

    public function getCaloriesPerHour()
    {
        return 700;
    }

    public function getAverageHeartRate()
    {
        return 135;
    }

    public function hasDistances()
    {
        return false;
    }

    public function hasPower()
    {
        return false;
    }

    public function isOutside()
    {
        return false;
    }

    public function getPaceUnitEnum()
    {
        return PaceEnum::KILOMETER_PER_HOUR;
    }

    public function usesShortDisplay()
    {
        return false;
    }
}
