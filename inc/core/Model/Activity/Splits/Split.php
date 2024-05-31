<?php
/**
 * This file contains class::Split
 * @package Runalyze\Model\Activity\Splits
 */

namespace Runalyze\Model\Activity\Splits;

use Runalyze\Model\StringObject;
use Runalyze\Activity\Duration;
use Runalyze\Activity\LapIntensity;
use Runalyze\Activity\Pace;
use Runalyze\Parameter\Application\PaceUnit;

/**
 * Single split
 *
 * @author Hannes Christiansen
 * @package Runalyze\Model\Activity\Splits
 */
class Split extends StringObject {
	/**
	 * Separator
	 * @var string
	 */
	const SEPARATOR = '|';

	/**
	 * Resting flag
	 * @var string
	 */
	const RESTING = 'R';

	/**
	 * Distance [km]
	 * @var float
	 */
	protected $Distance = 0;

	/**
	 * Time [s]
	 * @var int
	 */
	protected $Time = 0;

    /** @var LapIntensity */
    protected $Intensity;

	/**
	 * Construct
	 * @param string|float $dataOrDistance
	 * @param int $time [optional]
	 * @param boolean $active [optional]
	 */
	public function __construct($dataOrDistance = '', $time = null, $active = true) {
		if (is_null($time)) {
			parent::__construct($dataOrDistance);
		} else {
			$this->Distance = (float)$dataOrDistance;
			$this->Time = (int)$time;
			if ($active) {
				$this->Intensity = LapIntensity::getInstanceActive();
			} else {
				$this->Intensity = LapIntensity::getInstanceRest();
			}
		}
	}

	/**
	 * From string
	 * @param string $string
	 */
	public function fromString($string) {
		$intensity = LapIntensity::fromValue(substr($string, 0, 1));
		if (!is_null($intensity)) {
			$this->Intensity = $intensity;
			$string = substr($string, 1);
		} else {
			$this->Intensity = LapIntensity::getInstanceActive();
		}

		$Duration = new Duration(substr(strrchr($string, self::SEPARATOR), 1));

		$this->Distance = strstr($string, self::SEPARATOR, true);
		$this->Time = $Duration->seconds();
	}

	/**
	 * As string
	 * @return string
	 */
	public function asString() {
		$string  = $this->Intensity->getValue();
		$string .= $this->distanceAsString();
		$string .= self::SEPARATOR;
		$string .= $this->timeAsString();

		return $string;
	}

	/**
	 * Is empty
	 * @return boolean
	 */
	public function isEmpty() {
		return ($this->Distance <= 0 && $this->Time <= 0);
	}

	/**
	 * Set kilometer
	 * @param float $kilometer
	 */
	public function setDistance($kilometer) {
		$this->Distance = $kilometer;
	}

	/**
	 * Set time
	 * @param int $seconds
	 */
	public function setTime($seconds) {
		$this->Time = $seconds;
	}

	/**
	 * Set resting flag
	 * @param boolean $flag
	 */
	public function setResting($flag = true) {
		$this->Intensity = $flag ? LapIntensity::getInstanceRest() : LapIntensity::getInstanceActive();
	}

	/**
	 * Distance
	 * @return float
	 */
	public function distance() {
		return $this->Distance;
	}

	/**
	 * Time
	 * @return int
	 */
	public function time() {
		return $this->Time;
	}

	/**
	 * Pace
	 * @param int $legacyPaceUnitEnum [optional]
	 * @return \Runalyze\Activity\Pace
	 */
	public function pace($legacyPaceUnitEnum = PaceUnit::MIN_PER_KM) {
		return new Pace($this->Time, $this->Distance, $legacyPaceUnitEnum);
	}

	/**
	 * @return LapIntensity
	 */
	public function getIntensity() {
		return $this->Intensity;
	}

	/**
	 * Is active?
	 * @return boolean
	 */
	public function isActive() {
		return $this->Intensity->isNotRest();
	}

	/**
	 * Format distance
	 * @return string
	 */
	private function distanceAsString() {
		return number_format($this->Distance, 3, '.', '');
	}

	/**
	 * Format time
	 * @return string
	 */
	private function timeAsString() {
		return Duration::format($this->Time);
	}
}
