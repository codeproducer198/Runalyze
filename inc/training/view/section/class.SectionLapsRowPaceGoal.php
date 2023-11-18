<?php
/**
 * This file contains class::SectionLapsRowPaceGoal
 * @package Runalyze\DataObjects\Training\View\Section
 */

use Runalyze\View\Activity\Context;

/**
 * Row: Laps PaceGoal / PacePro #TSC
 * 
 * Here some trick: the SectionLapsRowComputed is left the laps-table and right normaly a plot.
 * But for this, the right content should be a table. So use no plot and override the displayPlot function.
 *
 * @author codeproducer
 * @package Runalyze\DataObjects\Training\View\Section
 */
class SectionLapsRowPaceGoal extends SectionLapsRowComputed {

	/**
	 * Display right content / PaceGoal table.
	 */
	protected function displayPlot() {
		$Table = new TablePaceGoal($this->Context);

		echo $Table->getCode();
	}
}
