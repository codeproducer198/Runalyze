<?php

namespace Runalyze\Parser\Activity\FileType;

use Runalyze\Activity\LapIntensity;

/**
 * Parser for workout_step's of a FIT file.
 * "workout_step" holds values to a detailed type of a lap (like warmup, recovery ...) and "custom target zones" for preconfigured trainings.
 * laps in the FIT file has a reference to a "workout_step".
 * #TSC
 */
class FitWorkoutStep {
    const ADD_TARGET_SPEED = 'FIT target speed';
    const ADD_TARGET_HR = 'FIT target HR';

    /** @var array array of LapIntensity|null can contain null values */
    protected $intensitys = [];
    /** @var array low/high of a target */
    protected $targets = [];

    /**
     * has intensity data.
     * @param int $index
     * @return LapIntensity|null
     */
    public function getIntensity($index): ?LapIntensity {
        return $this->intensitys[$index];
    }

    /**
     * get array for split-additionals.
     * @param int $index
     * @return array|null
     */
    public function getSplitAdditionals($index): ?array {
        if (!empty($this->targets) && count($this->targets) > $index && !empty($this->targets[$index])) {
            return $this->targets[$index];
        } else {
            return null;
        }
    }

    /**
     * #TSC collect informations.
     */
    public function collectWorkoutstep(&$values) {
        // intensity (like warmup, cooldown ...)
        if (isset($values['intensity'])) {
            $this->intensitys[] = LapIntensity::fromFitToEnum($values['intensity'][0]);
        } else {
            // the workout steps of type "repeat_steps" has no intensity, but are needed in the array for correct indexing
            $this->intensitys[] = null;
        }

        // custom targets (pace or heart-rate) with low and high bounds
        $target = array();
        if (isset($values['custom_target_speed_low'])) {
            $target[self::ADD_TARGET_SPEED . ' low'] = round($values['custom_target_speed_low'][0] / 1000, 3); // unit m/s
        } elseif (isset($values['custom_target_heart_rate_low'])) {
            $target[self::ADD_TARGET_HR . ' low'] = (int)$values['custom_target_heart_rate_low'][0];
        }

        if (isset($values['custom_target_speed_high'])) {
            $target[self::ADD_TARGET_SPEED . ' high'] = round($values['custom_target_speed_high'][0] / 1000, 3); // unit m/s
        } elseif (isset($values['custom_target_heart_rate_high'])) {
            $target[self::ADD_TARGET_HR . ' high'] = (int)$values['custom_target_heart_rate_high'][0];
        }
        $this->targets[] = $target;
    }
}