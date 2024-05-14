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
use Runalyze\Activity\Pace;
use Runalyze\Parameter\Application\PaceUnit;
use Runalyze\Model\Activity\Splits;

/**
 * Display swim lanes in details of 100m intervals.
 * #TSC
 *
 * @author Hannes Christiansen & Michael Pohl
 * @package Runalyze\DataObjects\Training\View\Section
 */
class TableSwimLaneAggregateInterval extends TableLapsAbstract {
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
		// table header
		$this->Code .= '<table class="fullwidth zebra-style">';
		$this->Code .= '<thead><tr>';
		$this->Code .= '<th>'.__('Laps').'</th>';
		$this->Code .= '<th>'.__('Distance').'</th>';
		$this->Code .= '<th></th>';
		$this->Code .= '<th>'.__('Pace').'</th>';
		$this->Code .= '<th></th>';
		$this->Code .= '<th>'.__('Pace') . ' ' . __('diff').'</th>';
		$this->Code .= '<th>'.__('Swolf').'</th>';
		$this->Code .= '<th>'.__('Swolf') . ' ' . __('diff').'</th>';
		$this->Code .= '<th>'.__('Strokes').'</th>';
		$this->Code .= '<th>'.__('Strokes') . ' ' . __('diff').'</th>';
		$this->Code .= '<th>'.__('Type').'</th>';
		$this->Code .= '</tr></thead>';

		$this->Code .= '<tbody>';

		// needed data
		$Loop = new Swimdata\Loop($this->Context->swimdata());
		$poollength = $this->Context->swimdata()->poollength() / 100000; // in KM
		$Stroketype = new Stroketype(\Runalyze\Profile\FitSdk\StrokeTypeProfile::FREESTYLE);
		$distance = new Distance(0);	// whole distance
		$distance100 = new Distance(0); // distance for every 100m interval
		$duration = new Duration(0);	// duration for every 100m interval

		$TrackLoop = new Trackdata\Loop($this->Context->trackdata());
		// creates an array with indexes of the TrackLoop lap-changes
		$lapTrackloopIdx = $this->getTrackloopLapIdx($this->Context->activity()->splits(), $TrackLoop);

		$max = $Loop->num();

		// aggregated values and the previous value for diff
		$swolfs = 0;
		$prevSwolfs = 0;

		$strokes = 0;
		$prevStrokes = 0;

		$pace = new Pace(0, 100 / 1000, PaceUnit::MIN_PER_100M);
		$prevPace = new Pace(0, 100 / 1000, PaceUnit::MIN_PER_100M);

		$stroketypePrimary = array();

		for ($i = 1, $lap = 1, $beginLap = true; $i <= $max; ++$i) {
			$Stroketype->set($Loop->stroketype());
			$distance->set($TrackLoop->distance());
			$distance100->add(new Distance($TrackLoop->difference(Trackdata\Entity::DISTANCE))); // in KM

			// if no break aggregate and write 100m interval row
			if(!$Stroketype->isBreak()) {
				$duration->add(new Duration($TrackLoop->difference(Trackdata\Entity::TIME)));
	
				// aggregate values (pace is diff by the TrackLoop)
				$swolfs += empty($Loop->swolf()) ? 0 : $Loop->swolf();
				$strokes += empty($Loop->stroke()) ? 0: $Loop->stroke();

				// calc a array where the type is the id; later the id with the highest value can be used for primary type
				$stroketypePrimary[$Stroketype->id()] = array_key_exists($Stroketype->id() , $stroketypePrimary) ? $stroketypePrimary[$Stroketype->id()] + 1 : 1;

				$lapFinished = in_array($TrackLoop->index(), $lapTrackloopIdx);

				// if 100m exceeded or the lap is finished write the table row
				if($distance100->stringMeter(false) == '100' || $lapFinished) {	
					$this->Code .= '<tr class="r"' . 
						($beginLap ? ' style="border-color: #666; border-style: dashed dashed dashed dashed; border-width: 1px 0px 1px 0px;"' : '')
						. '>';

					// round/lap
					$this->Code .= '<td>'.$lap.'</td>';

					// distance cumulated and per 100meter
					$this->Code .= '<td>'. $distance->stringMeter() .'</td>';
					$this->Code .= '<td>'. $distance100->stringMeter() .'</td>';

					// pace
					$pace->setTime($duration->seconds());
					$pace->setDistance($distance100->value());
					$this->Code .= '<td>'. $pace->valueWithAppendix() .'</td>';
					// make it very easy: the pace seconds define the bar-width
					$this->Code .= '<td style="text-align: left; padding-left: 2%;">'. 
						'<span class="bar-chart-value" style="width:'. ($pace->secondsPerKm() / 10) .'px; top: 0px;"></span></td>';
					if($beginLap && $lap == 1) {
						$this->Code .= '<td></td>';
					} else {
						$this->Code .= '<td><span style="opacity: ' . ($beginLap ? '0.5' : '1.0') . '">' . $pace->compareTo($prevPace) . '</span></td>';
					}

					// swolfs
					$sw = round($swolfs / ($distance100->value() / $poollength));
					if($beginLap && $lap == 1) {
						$this->Code .= '<td></td><td></td>';
					} else {
						$this->Code .= '<td>'. $sw .'</td>';
						$this->Code .= '<td>'.$this->difference($prevSwolfs, $sw, $beginLap).'</td>';
					}

					// strokes
					if($beginLap && $lap == 1) {
						$this->Code .= '<td></td><td></td>';
					} else {
						$this->Code .= '<td>'.$strokes.'</td>';
						$this->Code .= '<td>'.$this->difference($prevStrokes, $strokes, $beginLap).'</td>';
					}

					// get the primary stoke type of this 100m interval
					if(empty($stroketypePrimary)) {
						$this->Code .= '<td></td>';
					} else {
						$t = array_keys($stroketypePrimary,max($stroketypePrimary));
						$Stroketype->set($t);
						$this->Code .= '<td style="padding-right: 20px;">'.$Stroketype->shortString().'</td>';
					}

					$this->Code .= '</tr>';

					// store "previous" values for diff
					$prevPace->setTime($duration->seconds());
					$prevPace->setDistance($distance100->value());
					$prevSwolfs = $sw;
					$prevStrokes = $strokes;

					// reset values
					$swolfs = 0;
					$strokes = 0;
					$duration->fromSeconds(0);
					$distance100->set(0);
					$stroketypePrimary = array();

					// increase lap counter
					if($lapFinished) {
						$lap = $lap + 1;
						$beginLap = true;
					} else {
						$beginLap = false;
					}
				}
			}
			

			$TrackLoop->nextStep();
			$Loop->nextStep();
		}

		$this->Code .= '</tbody>';
		$this->Code .= '</table>';
	}

	// create a array with trackloop-indexes where the laps/rounds changing
	protected function getTrackloopLapIdx(Splits\Entity $splits, Trackdata\Loop $trackLoop) {
		$result = array();

		$time = 0;
		foreach ($splits->asArray() as $Split) {
			$time += $Split->time();
			if ($Split->isActive()) {
				$trackLoop->moveToTime($time);
				$result[] = $trackLoop->index();
			}
		}

		// reset further use
		$trackLoop->reset();

		return $result;
	}

	// calculates the difference between two values and return the diff with a pritty colored span
	protected function difference($previous, $new, $beginLap) {
		$diff = $previous - $new;
		if($diff > 0) {
			$class = 'plus';
			$sign = "+";
		} else {
			$class = 'minus';
			$sign = " ";
		}
		return '<span class="' . $class . '" style="opacity: '. ($beginLap ? '0.5' : '1.0') . '">' . $sign . $diff . '</span>';
	}
}
