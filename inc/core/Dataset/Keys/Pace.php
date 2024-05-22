<?php
/**
 * This file contains class::Pace
 * @package Runalyze
 */

namespace Runalyze\Dataset\Keys;

use Runalyze\Activity;
use Runalyze\Dataset\Context;
use Runalyze\Dataset\SummaryMode;
use Runalyze\Profile\Sport\SportProfile;

/**
 * Dataset key: Pace
 *
 * @author Hannes Christiansen
 * @package Runalyze\Dataset\Keys
 */
class Pace extends AbstractKey
{
	// #TSC minimum- and maximum-values in "km/h" to bound the pace bar
	const P_MIN_MAX = array(
		// km/h values means: pace between 12min/km - 3:20min/km
		SportProfile::RUNNING => array('min' => 5, 'max' => 18),
		// km/h values means: pace between 3min/100m - 1:20min/100
		SportProfile::SWIMMING => array('min' => 2, 'max' => 4.5),
		// 10 - 32 km/h
		SportProfile::CYCLING => array('min' => 10, 'max' => 32),
	);

	/** @var string */
	const DURATION_SUM_WITH_DISTANCE_KEY = 's_sum_with_distance';

	/**
	 * Enum id
	 * @return int
	 */
	public function id()
	{
		return \Runalyze\Dataset\Keys::PACE;
	}

	/**
	 * Database key
	 * @return string
	 */
	public function column()
	{
		return '';
	}

	/**
	 * @return bool
	 */
	public function isInDatabase()
	{
		return false;
	}

	/**
	 * @return string
	 * @codeCoverageIgnore
	 */
	public function label()
	{
		return __('Pace');
	}

	/**
	 * Get string to display this dataset value
	 * @param \Runalyze\Dataset\Context $context
	 * @return string
	 */
	public function stringFor(Context $context)
	{
		$pace = $this->getPace($context);
		if (is_null($pace)) {
			return '';
		} else {
			return $pace->valueWithAppendix();
		}
	}

	/**
	 * @return int see \Runalyze\Dataset\SummaryMode for enum
	 */
	public function summaryMode()
	{
		return SummaryMode::AVG;
	}

	/**
	 * @return string
	 * @codeCoverageIgnore
	 */
	public function cssClass()
	{
		return 'small';
	}

	/**
	 * Get pace
	 * @param \Runalyze\Dataset\Context $context
	 * @return \Runalyze\Activity\Pace
	 */
	private function getPace(Context $context)
	{
		if ($context->activity()->distance() > 0) {
			if ($context->hasData(self::DURATION_SUM_WITH_DISTANCE_KEY))  {
				if ($context->data(self::DURATION_SUM_WITH_DISTANCE_KEY) > 0) {
					$Pace = new Activity\Pace(
						$context->data(self::DURATION_SUM_WITH_DISTANCE_KEY),
						$context->activity()->distance(),
						$context->hasSport() ? $context->sport()->getLegacyPaceUnitEnum() : Activity\Pace::STANDARD
					);

					return $Pace;
				}

				return null;
			}

			return $context->dataview()->pace();
		}

		return null;
	}

	/**
	 * returns a value dependend CSS class and style.
	 * #TSC
     * @param \Runalyze\Dataset\Context $context
	 * @return array with key "class" and "style"
	 */
	public function valueDependendCssStyle(Context $context)
	{
		if (!array_key_exists($context->Sport()->getInternalProfileEnum(), self::P_MIN_MAX)) {
			return null;
		}
		
		$pace = $this->getPace($context);
		if (!is_null($pace)) {
			$minMax = self::P_MIN_MAX[$context->Sport()->getInternalProfileEnum()];

			$kmh = 1 / ($pace->secondsPerKm() / 60 / 60); // kmPerHour
			$v = round(100 * ($kmh - $minMax['min']) / ($minMax['max'] - $minMax['min']));

			// we must differ between time-base (lower is better) and decimal-based (higher is faster) representaion
			if ($pace->unit()->isTimeFormat()) {
				// time-base (lower is better): example: running pace like 5 minutes for 1 kilometer (=5:00min/km)
				$class = "bar gray";
				$v = 100 - $v;
			} else {
				// decimal-base (more km is better): example: cycling 20 km in one hour (=20km/h)
				$class = "bar blue";
			}

			return array('class' => $class, 'style' => "--perc: " . $v . "%;");
		} else {
			return null;
		}
	}
}
