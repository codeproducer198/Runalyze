<?php
/**
 * This file contains class::SectionHeartrateRow
 * @package Runalyze\DataObjects\Training\View\Section
 */

use Runalyze\View\Activity;
use Runalyze\View\Activity\Box;
/**
 * Row: Heartrate
 *
 * @author Hannes Christiansen
 * @package Runalyze\DataObjects\Training\View\Section
 */
class SectionHeartrateRow extends TrainingViewSectionRowTabbedPlot {
	/**
	 * Set plot
	 */
	protected function setRightContent() {
		$this->addRightContent('plot', __('Heartrate plot'), new Activity\Plot\Heartrate($this->Context));

		if (
			$this->Context->trackdata()->has(\Runalyze\Model\Trackdata\Entity::HEARTRATE) &&
			$this->Context->trackdata()->has(\Runalyze\Model\Trackdata\Entity::TIME)
		) {
			$Table = new TableZonesHeartrate($this->Context);
			$Code = $Table->getCode();

			$this->addRightContent('zones', __('Heartrate zones'), $Code);
		}
	}

	/**
	 * Set content
	 */
	protected function setContent() {
		$this->addAverageHeartrate();
		$this->addMaximalHeartrate();
		$this->addAverageHeartrateActive();
		$this->addCaloriesAndTrimp();
		$this->addFitTrainingEffect();
		$this->addFitAnaerobicTrainingEffect(); // #TSC: add Anaerobic to statistic-page to the heart-rate table

		foreach ($this->BoxedValues as &$Value)
			$Value->defineAsFloatingBlock('w50');
	}

	/**
	 * Add: average heartrate
	 */
	protected function addAverageHeartrate() {
		if ($this->Context->activity()->hrAvg() > 0) {
			$this->BoxedValues[] = new BoxedValue($this->Context->dataview()->hrAvg()->inBPM(), 'bpm', __('avg.').' '.__('Heart rate'));

			if ($this->Context->dataview()->hrMax()->canShowInHRmax()) {
				$this->BoxedValues[] = new BoxedValue($this->Context->dataview()->hrAvg()->inPercent(), '&#37;', __('avg.').' '.__('Heart rate'));
			}
		}
	}

	/**
	 * Add: average heartrate
	 */
	protected function addMaximalHeartrate() {
		if ($this->Context->activity()->hrMax() > 0) {
			$this->BoxedValues[] = new Box\MaximalHeartRateInBPM($this->Context->dataview()->hrMax());

			if ($this->Context->dataview()->hrMax()->canShowInHRmax()) {
				$this->BoxedValues[] = new Box\MaximalHeartRateInPercent($this->Context->dataview()->hrMax(), !Request::isOnSharedPage());
			}
		}
	}

	/**
	 * #TSC Add: average heartrate of active rounds
	 */
	protected function addAverageHeartrateActive() {
		$hr = $this->Context->activity()->hrAvgActive();
		if ($hr > 0 && $hr != $this->Context->activity()->hrAvg()) {
			$this->BoxedValues[] = new BoxedValue($this->Context->dataview()->hrAvgActive()->inBPM(), 'bpm', '&#216; '.__('Heart rate').' active');
		}
	}

	/**
	 * Add: calories/trimp
	 */
	protected function addCaloriesAndTrimp() {
		if ($this->Context->activity()->energy() > 0 || $this->Context->activity()->trimp() > 0) {
			$this->BoxedValues[] = new Box\Energy($this->Context);
			$this->BoxedValues[] = new Box\Trimp($this->Context);
		}
	}

	/**
	 * Add: FitTrainingEffect
	 */
	protected function addFitTrainingEffect() {
	    if ($this->Context->activity()->fitTrainingEffect() > 0) {
		$this->BoxedValues[] = new Box\FitTrainingEffect($this->Context);
	    }
	}

	/**
	 * Add: FitAnaerobicTrainingEffect
     * #TSC: new Anaerobic
	 */
	protected function addFitAnaerobicTrainingEffect() {
	    if ($this->Context->activity()->fitAnaerobicTrainingEffect() > 0) {
            $this->BoxedValues[] = new Box\FitAnaerobicTrainingEffect($this->Context);
	    }
	}
}
