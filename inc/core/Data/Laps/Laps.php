<?php
/**
 * This file contains class::Laps
 * @package Runalyze\Data\Laps
 */

namespace Runalyze\Data\Laps;

use Runalyze\Model\Trackdata;
use Runalyze\Model\Route;
use Runalyze\Model\Swimdata;
use Runalyze\Model\Activity\Splits;

/**
 * Object of laps
 * 
 * @author Hannes Christiansen
 * @package Runalyze\Data\Laps
 */
class Laps {
	/**
	 * @var \Runalyze\Data\Laps\Lap[]
	 */
	protected $Objects;

	/**
	 * @var bool
	 */
	protected $CalculateAdditionalValues = false;

	/**
	 * @param bool $flag
	 */
	public function enableCalculationOfAdditionalValues($flag = true) {
		$this->CalculateAdditionalValues = $flag;
	}

	/**
	 * @param array $distances
	 * @param \Runalyze\Model\Trackdata\Entity $trackdata
	 * @param \Runalyze\Model\Route\Entity $route
	 * @param \Runalyze\Model\Swimdata\Entity $swimdata
	 */
	public function calculateFrom(array $distances, Trackdata\Entity $trackdata, Route\Entity $route = null) {
		$Calculator = new Calculator($this);
		$Calculator->calculateAdditionalValues($this->CalculateAdditionalValues);
		$Calculator->setDistances($distances);
		$Calculator->calculateFrom($trackdata, $route);
	}

	/**
	 * @param array $times
	 * @param \Runalyze\Model\Trackdata\Entity $trackdata
	 * @param \Runalyze\Model\Route\Entity $route
	 * @param \Runalyze\Model\Swimdata\Entity $swimdata
	 */
	public function calculateFromTimes(array $times, Trackdata\Entity $trackdata, Route\Entity $route = null, Swimdata\Entity $swimdata = null) {
		$Calculator = new Calculator($this);
		$Calculator->calculateAdditionalValues($this->CalculateAdditionalValues);
		$Calculator->setTimes($times);
		$Calculator->calculateFrom($trackdata, $route, ($swimdata != null && $swimdata->num() > 0 ? $swimdata : null));
	}

	/**
	 * @param \Runalyze\Model\Activity\Splits\Entity $splits
	 */
	public function readFrom(Splits\Entity $splits) {
		$SplitsReader = new SplitsReader($this);
		$SplitsReader->readFrom($splits);
	}

	/**
	 * @param \Runalyze\Data\Laps\Lap $object
	 */
	public function add(Lap $object) {
		$this->Objects[] = $object;
	}

	/**
	 * @param int $index
	 * @return \Runalyze\Data\Laps\Lap
	 * @throws \InvalidArgumentException
	 */
	public function at($index) {
		if (!is_numeric($index) || $index < 0 || $index > $this->num()) {
			throw new \InvalidArgumentException('Index out of bounds.');
		}

		return $this->Objects[$index];
	}

	/**
	 * @return \Runalyze\Data\Laps\Lap[]
	 */
	public function objects() {
		return $this->Objects;
	}

	/**
	 * @return int
	 */
	public function num() {
		return count($this->Objects);
	}

	/**
	 * true if there other laps with mode not ACTIVE and not RESTING.
	 * #TSC
	 * @return bool
	 */
	public function hasMoreThanActiveOrRestLaps() {
		foreach ($this->Objects as $lap) {
			if (!$lap->getMode()->isActive() && !$lap->getMode()->isRest()) {
				return true;
			}
		}
		return false;
	}
}
