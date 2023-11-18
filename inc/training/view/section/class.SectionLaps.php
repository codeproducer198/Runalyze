<?php
/**
 * This file contains class::SectionLaps
 * @package Runalyze\DataObjects\Training\View\Section
 */

use Runalyze\Model\Trackdata;

/**
 * Section: Laps
 * 
 * @author Hannes Christiansen
 * @package Runalyze\DataObjects\Training\View\Section
 */
class SectionLaps extends TrainingViewSectionTabbed {
	/**
	 * Set header and rows
	 */
	protected function setHeaderAndRows() {
		$this->Header = __('Laps');

		if (!$this->Context->activity()->splits()->isEmpty()) {
			$this->appendRowTabbed( new SectionLapsRowManual($this->Context), __('Manual Laps') );
		}

		if ($this->Context->trackdata()->has(Trackdata\Entity::DISTANCE) && $this->Context->trackdata()->has(Trackdata\Entity::TIME)) {
			$this->appendRowTabbed( new SectionLapsRowComputed($this->Context), __('Computed Laps') );
		}

		if ($this->Context->activity()->paceGoal()) {
			$this->appendRowTabbed( new SectionLapsRowPaceGoal($this->Context), __('Pace Goal') );
		}
	}

	/**
	 * Has the training all required data?
	 * @return bool
	 */
	protected function hasRequiredData() {
		// #TSC only splits are needed as required-data, if the splits()->totalDistance() is 0, anyway show laps
		return (!$this->Context->activity()->splits()->isEmpty())
			|| ($this->Context->trackdata()->has(Trackdata\Entity::DISTANCE) && $this->Context->trackdata()->has(Trackdata\Entity::TIME));
	}

	/**
	 * CSS-ID
	 * @return string
	 */
	protected function cssId() {
		return 'laps';
	}
}
