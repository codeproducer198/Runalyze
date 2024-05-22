<?php
/**
 * This file contains class::HeartrateAverage
 * @package Runalyze
 */

namespace Runalyze\Dataset\Keys;

use Runalyze\Activity\HeartRate;
use Runalyze\Dataset\Context;
use Runalyze\Dataset\SummaryMode;

/**
 * Dataset key: HeartrateAverage
 * 
 * @author Hannes Christiansen
 * @package Runalyze\Dataset\Keys
 */
class HeartrateAverage extends AbstractKey
{
	// default bound for heart-rate if no user/Athlete configuration exists
	const HR_MIN = 40;
	const HR_MAX = 220;

	/**
	 * Enum id
	 * @return int
	 */
	public function id()
	{
		return \Runalyze\Dataset\Keys::HEARTRATE_AVG;
	}

	/**
	 * Database key
	 * @return string
	 */
	public function column()
	{
		return 'pulse_avg';
	}

	/**
	 * @return string
	 * @codeCoverageIgnore
	 */
	public function label()
	{
		return __('avg.').' '.__('Heart rate');
	}

	/**
	 * @return string
	 * @codeCoverageIgnore
	 */
	public function shortLabel()
	{
		return __('avg.').' '.__('HR');
	}

	/**
	 * Get string to display this dataset value
	 * @param \Runalyze\Dataset\Context $context
	 * @return string
	 */
	public function stringFor(Context $context)
	{
		if ($context->activity()->hrAvg() > 0) {
			return $context->dataview()->hrAvg()->string();
		}

		return '';
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
	 * @return string
	 * @codeCoverageIgnore
	 */
	public function defaultCssStyle()
	{
		return 'font-style:italic;';
	}

	/**
	 * returns a value dependend CSS class and style.
	 * #TSC
     * @param \Runalyze\Dataset\Context $context
	 * @return array with key "class" and "style"
	 */
	public function valueDependendCssStyle(Context $context)
	{
		return self::hrBarCssStyle($context->dataview()->hrAvg());
	}

	/**
	 * returns the css class and style to show the HR as a bar (depending of the rest- and max-HR).
	 * #TSC
     * @param null|Runalyze\Activity\HeartRate $hr
	 * @param null|string $class
	 * @return array with key "class" and "style"
	 */
	public static function hrBarCssStyle(?HeartRate $hr, ?string $class = "bar gray")
	{
		$hrBpm = $hr->inBPM();
		$hrMin = $hr->getHRrest() ? $hr->getHRrest() : self::HR_MIN;
		$hrMax = $hr->getHRmax() ? $hr->getHRmax() : self::HR_MAX;

		if (isset($hr) && $hrBpm > 0) {
			$v = round(100 * ($hrBpm - $hrMin) / ($hrMax - $hrMin));
			return array('class' => $class, 'style' => "--perc: " . $v . "%;");
		} else {
			return null;
		}
	}
}
