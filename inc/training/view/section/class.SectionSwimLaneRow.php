<?php
/**
 * This file contains class::SectionLapsRowManual
 * @package Runalyze\DataObjects\Training\View\Section
 */

use Runalyze\View\Activity;

/**
 * Row: Laps (manual)
 *
 * @author Hannes Christiansen & Michael Pohl
 * @package Runalyze\DataObjects\Training\View\Section
 */
class SectionSwimLaneRow extends TrainingViewSectionRowTabbedPlot {
	/**
	 * Constructor
	 */
	public function __construct(Activity\Context $Context = null) {
		parent::__construct($Context);

	}

	/**
	 * Set content right
	 */
	protected function setRightContent() {
            $Plot = new Activity\Plot\Stroke($this->Context);
            $this->addRightContent('stroke', __('Stroke'), $Plot);
            $Plot = new Activity\Plot\Swolf($this->Context);
            $this->addRightContent('swolf', __('SWOLF'), $Plot);
            $Plot = new Activity\Plot\Swolfcycles($this->Context);
            $this->addRightContent('swolfcycles', __('SWOLFcycles'), $Plot);

			// #TSC only show tab, if pool-lengh is a "part" of 100 meter
			$poollength = $this->Context->swimdata()->poollength(); // in cm
			if(!empty($poollength) && (100 % ($poollength / 100)) == 0) {
				$Table = new TableSwimLaneAggregateInterval($this->Context);
				$this->addRightContent('lanes_aggregate', __('Lanes') . ' 100m', $Table->getCode());
			}
        }

	/**
	 * Set content
	 */
	protected function setContent() {
		$this->withShadow = true;
		$this->addTable();

	}

	/**
	 * Add: table
	 */
	protected function addTable() {
		$Table = new TableSwimLane($this->Context);
		$this->Code = $Table->getCode();
	}
}
