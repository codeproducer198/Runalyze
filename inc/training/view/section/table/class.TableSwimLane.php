<?php
/**
 * This file contains class::TableLapsComputed
 * @package Runalyze\DataObjects\Training\View\Section
 */

use Runalyze\Model\Trackdata;
use Runalyze\Model\Swimdata;
use Runalyze\Activity\Distance;
use Runalyze\Activity\Duration;
use Runalyze\Data\Stroketype;
use Runalyze\Model\Activity\Splits;

/**
 * Display swim lanes
 *
 * @author Hannes Christiansen & Michael Pohl
 * @package Runalyze\DataObjects\Training\View\Section
 */
class TableSwimLane extends TableLapsAbstract {
	/**
	 * Data
	 * @var array
	 */
	protected $Data = array();

	/**
	 * Set code
	 */
	protected function setCode() {
		$this->setDataToCode();
	}

	/**
	 * Display data
	 */
	protected function setDataToCode() {
		$this->Code .= '<table class="fullwidth zebra-style">';
		$this->Code .= '<thead><tr>';
		$this->Code .= '<th></th>';
		$this->Code .= '<th>'.__('Distance').'</th>';
		$this->Code .= '<th>'.__('Time').'</th>';
		$this->Code .= '<th>'.__('Swolf').'</th>';
		$this->Code .= '<th>'.__('Strokes').'</th>';
		$this->Code .= '<th>'.__('Type').'</th>';
		$this->Code .= '</tr></thead>';

		$this->Code .= '<tbody>';

		$Loop = new Swimdata\Loop($this->Context->swimdata());
		$Poollength = $this->Context->swimdata()->poollength() / 100000;
		$TrackLoop = new Trackdata\Loop($this->Context->trackdata());
		$Stroketype = new Stroketype(\Runalyze\Profile\FitSdk\StrokeTypeProfile::FREESTYLE);
		$Distance = new Distance(0);

		$pauseTrackloopIdx = $this->getTrackloopPauseIdx($this->Context->activity()->splits(), $TrackLoop);

		$max = $Loop->num();
		$hasBreak = false;
		$interval = 1;
		$intervalLanes = 0;
		$swolfs = 0;
		$strokes = 0;

		for ($i = 1, $lane = 0; $i <= $max; ++$i) {
			$Stroketype->set($Loop->stroketype());
			$Distance->set($TrackLoop->distance());

			$lapFinished = in_array($TrackLoop->index(), $pauseTrackloopIdx);

			// #TSC: add more info to lanes-table: pause-lanes, summary line
			if($lapFinished && $Stroketype->isBreak()) {
				if($swolfs > 0 || $strokes > 0) {
					// show the summary lane of this interval
					$this->writeSummary($Poollength, $TrackLoop, $interval, $intervalLanes, $swolfs, $strokes);
				}

				$interval++;

				$intervalLanes = 0;
				$swolfs = 0;
				$strokes = 0;

				// TSC: pause/rest handling: if pause, show the pause lane
				$this->Code .= '<tr class="r unimportant">';
				$this->Code .= '<td></td>';
				$this->Code .= '<td></td>';
				$this->Code .= '<td>'.Duration::format($TrackLoop->difference(Trackdata\Entity::TIME)).'</td>';
				$this->Code .= '<td></td>';
				$this->Code .= '<td></td>';

				$hasBreak = true;
			} else {
				$this->Code .= '<tr class="r">';

				if(!$Stroketype->isBreak()) {
					$lane++;
					$intervalLanes++;

					$this->Code .= '<td>'.$lane.'.</td>';
					$this->Code .= '<td>'.$Distance->stringMeter().'</td>';
					$this->Code .= '<td>'.Duration::format($TrackLoop->difference(Trackdata\Entity::TIME)).'</td>';
					$this->Code .= '<td>'.$Loop->swolf().'</td>';
					$this->Code .= '<td>'.$Loop->stroke().'</td>';
				} else {
					$this->Code .= '<td></td>';
					$this->Code .= '<td></td>';
					$this->Code .= '<td>'.Duration::format($TrackLoop->difference(Trackdata\Entity::TIME)).'</td>';
					$this->Code .= '<td></td>';
					$this->Code .= '<td></td>';
				}
				
				$swolfs += empty($Loop->swolf()) ? 0 : $Loop->swolf();
				$strokes += empty($Loop->stroke()) ? 0: $Loop->stroke();
			}

			$this->Code .= '<td>'.$Stroketype->shortString().'</td>';
			$this->Code .= '</tr>';

			$TrackLoop->nextStep();
			$Loop->nextStep();
		}

		// #TSC: in break/rest add the last interval "summary"; but only if the last is not a break interval
		if($hasBreak && !$Stroketype->isBreak()) {
			$this->writeSummary($Poollength,$TrackLoop, $interval, $intervalLanes, $swolfs, $strokes);
		}

		$this->Code .= '</tbody>';
		$this->Code .= '</table>';
	}

	// create a array with trackloop-indexes where the pause laps/rounds including #TSC
	protected function getTrackloopPauseIdx(Splits\Entity $splits, Trackdata\Loop $trackLoop) {
		$result = array();

		$time = 0;
		foreach ($splits->asArray() as $Split) {
			$time += $Split->time();
			if (!$Split->isActive()) {
				$trackLoop->moveToTime($time);
				$result[] = $trackLoop->index();
			}
		}

		// reset further use
		$trackLoop->reset();

		return $result;
	}

	/**
	 * writes the summary line with infos about the previous lanes.
	 * #TSC
	 */
	protected function writeSummary($Poollength, $TrackLoop, $interval, $intervalLanes, $swolfs, $strokes) {
		$distance = new Distance($Poollength * $intervalLanes);
		$this->Code .= '<tr class="r unimportant">';

		$this->Code .= '<td>'. $interval.'./'.$intervalLanes. '</td>';
		$this->Code .= '<td>'. $distance->stringMeter() .'</td>';
		$this->Code .= '<td></td>';
		$this->Code .= '<td>&#216; '. ($intervalLanes != 0 ? round($swolfs / $intervalLanes)  : '-') .'</td>';
		$this->Code .= '<td>&#216; '. ($intervalLanes != 0 ? round($strokes / $intervalLanes) : '-') .'</td>';

		$this->Code .= '<td>Summary</td>';
		$this->Code .= '</tr>';
	}
}
