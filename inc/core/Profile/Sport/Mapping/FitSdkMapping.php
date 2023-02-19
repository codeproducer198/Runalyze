<?php

namespace Runalyze\Profile\Sport\Mapping;

use Runalyze\Profile\FitSdk;
use Runalyze\Profile\Mapping\AbstractMapping;
use Runalyze\Profile\Sport\SportProfile;

class FitSdkMapping extends AbstractMapping
{
    /**
     * @return array [fitSdkId => runalyzeId, ...]
     */
    protected function getMapping()
    {
        return [
            FitSdk\SportProfile::GENERIC => SportProfile::GENERIC,
            FitSdk\SportProfile::RUNNING => SportProfile::RUNNING,
            FitSdk\SportProfile::E_BIKING => SportProfile::CYCLING,
            FitSdk\SportProfile::CYCLING => SportProfile::CYCLING,
            FitSdk\SportProfile::SWIMMING => SportProfile::SWIMMING,
            FitSdk\SportProfile::ROWING => SportProfile::ROWING,
            FitSdk\SportProfile::WALKING => SportProfile::HIKING,
            FitSdk\SportProfile::HIKING => SportProfile::HIKING,
            FitSdk\SportProfile::MOUNTAINEERING => SportProfile::MOUNTAINEERING,
            FitSdk\SportProfile::SNOWSHOEING => SportProfile::SNOW_SHOEING,
            FitSdk\SportProfile::CROSS_COUNTRY_SKIING => SportProfile::CROSS_COUNTRY_SKIING,
            FitSdk\SportProfile::CLIMBING_INDOOR => SportProfile::CLIMBING_INDOOR,
            FitSdk\SportProfile::BOULDERING => SportProfile::BOULDERING,
            FitSdk\SportProfile::STRENGTH_TRAINING => SportProfile::STRENGTH_TRAINING,
            FitSdk\SportProfile::E_MTB_MOUNTAIN => SportProfile::E_MTB_MOUNTAIN
        ];
    }

    /**
     * @return int
     */
    protected function internalDefault()
    {
        return SportProfile::GENERIC;
    }

    /**
     * @return int
     */
    protected function externalDefault()
    {
        return FitSdk\SportProfile::GENERIC;
    }

    /**
     * #TSC
     * Tries to find the internal sport with the sub-sport.
     * This is for cases like BOULDERING where sport=31=climbing and subsport=69=bouldern.
     */
    public function sportWithSubsportToInternal(string $sport, string $subsport)
    {
        $id = $sport * 1000 + $subsport;

        if (isset($this->getMapping()[$id])) {
            return $this->getMapping()[$id];
        } else {
            return;
        }
    }
}
