<?php

namespace Runalyze\Profile\Sport;

use Runalyze\Metrics\Velocity\Unit\PaceEnum;

/**
 * E-MTB Mountain.
 * #TSC
 *
 * @codeCoverageIgnore
 */
class EMtbMountain extends AbstractSport
{
    public function __construct()
    {
        parent::__construct(SportProfile::E_MTB_MOUNTAIN);
    }

    public function getIconClass()
    {
        return 'icons8-Mountain-Biking';
    }

    public function getName()
    {
        return __('E-MTB Berg');
    }

    public function getCaloriesPerHour()
    {
        return 450;
    }

    public function getAverageHeartRate()
    {
        return 120;
    }

    public function hasDistances()
    {
        return true;
    }

    public function hasPower()
    {
        return true;
    }

    public function isOutside()
    {
        return true;
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
