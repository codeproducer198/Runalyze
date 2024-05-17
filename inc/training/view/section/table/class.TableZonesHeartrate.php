<?php
/**
 * This file contains class::TableZonesHeartrate
 * @package Runalyze\DataObjects\Training\View\Section
 */

use Runalyze\Activity\Pace;
use Runalyze\Calculation\Distribution\TimeSeriesForTrackdata;
use Runalyze\Model\Trackdata;

/**
 * Display heartrate zones
 *
 * @author Hannes Christiansen
 * @package Runalyze\DataObjects\Training\View\Section
 */
class TableZonesHeartrate extends TableZonesAbstract {
	/**
	 * @var int [bpm]
	 */
	const MINIMUM_TIME_IN_ZONE_MIN_HR = 60;

	const DEFAULT_COLOR = '#c6c5c5';

	private $footerHint = null;

	/**
	 * Get title for average
	 * @return string
	 */
	public function titleForAverage() { return __('avg.').'&nbsp;'.__('Pace'); }

	/**
	 * Get hint for footer #TSC
	 * @return string
	 */
	public function footerHint() { return $this->footerHint; }

	/**
	 * Init data
	 */
	protected function initData() {
		$Zones = $this->computeZones();
		$Pace = new Pace(0, 0);
		$Pace->setUnit($this->Context->sport()->legacyPaceUnit());

		foreach ($Zones as $z => $Info) {
			if ($Info['time'] > parent::MINIMUM_TIME_IN_ZONE || $Info['#start'] > TableZonesHeartrate::MINIMUM_TIME_IN_ZONE_MIN_HR) {
				$Pace->setTime($Info['time']);
				$Pace->setDistance($Info['distance']);

				// #TSC add more info in the zone column: the range of the % and numbers incl. a text if available
				$perRange = $this->threeNumbers($Info['%start']) . ' -' . $this->threeNumbers($Info['%end']) . ' &#37;';
				$bpmRange = $this->threeNumbers($Info['#start']) . ' -' . $this->threeNumbers($Info['#end']) . ' bpm';

				$this->Data[] = array(
					'zone'     => $Info['text'] . ' ' . $perRange . ' / ' . $bpmRange,
					'time'     => $Info['time'],
					'distance' => $Info['distance'],
					'average'  => $Pace->value() > 0 ? $Pace->valueWithAppendix() : '-',
					'color'    => $Info['color'] #TSC support for colors
				);
			}
		}
	}

	/**
	 * fill a 2 char number with blanks for a better "column" prepresentation.
	 */
	private function threeNumbers($num) {
		return ($num <= 99 ? '&nbsp;&nbsp;' : '') . $num;
	}

	/**
	 * @return array
	 */
	protected function computeZones() {
		if (!is_null($this->Context->activity()->maxHrUser())) {
			$hrMax = (int)$this->Context->activity()->maxHrUser();
			$footerHintMaxHr = "with a maximum users heart-rate of ".$hrMax." from the activity.";
		} else {
			$hrMax = Runalyze\Configuration::Data()->HRmax();
			$footerHintMaxHr = "with a maximum users heart-rate of ".$hrMax." from the users body data.";
		}

		$Zones = array();
		$hasDistance = $this->Context->trackdata()->has(Trackdata\Entity::DISTANCE);

		$Distribution = new TimeSeriesForTrackdata(
			$this->Context->trackdata(),
			Trackdata\Entity::HEARTRATE,
			$hasDistance ? array(Trackdata\Entity::DISTANCE) : array()
		);
		$Data = $Distribution->data();

		// the histogram of bpm is already "summarized", so a bpm is only one time in the histogram
		// sort it in key order: key=bpm value=sumarized seconds
		$sortHist = $Distribution->histogram();
		ksort($sortHist);

		$defaultZones = $this->getZones(array_key_first($sortHist), array_key_last($sortHist), $hrMax, $footerHintMaxHr);

		foreach ($sortHist as $bpm => $seconds) {
			// index is 1-n of the relevant key to use
			$z = $this->zoneFor($bpm, $defaultZones);

			if (!isset($Zones[$z])) {
				$startHr = $defaultZones[$z]['hr'];
				$endHr = $defaultZones[$z + 1]['hr'] - 1; // use the next zone hr as the high bound of this zone

				$Zones[$z] = array(
					'text' => array_key_exists('t', $defaultZones[$z]) ? $defaultZones[$z]['t'] : '',
					'#start' => $startHr,
					'#end' => $endHr,
					'%start' => round(100 / $hrMax * $startHr),
					'%end' => round(100 / $hrMax * $endHr),
					'time' => 0,
					'distance' => 0,
					'color' => array_key_exists('c', $defaultZones[$z]) ? $defaultZones[$z]['c'] : TableZonesHeartrate::DEFAULT_COLOR
				);
			}
			$Zones[$z]['time'] += $seconds;
			$Zones[$z]['distance'] += $hasDistance ? $Data[$bpm][Trackdata\Entity::DISTANCE] : 0;
		}

		foreach ($Zones as &$z) {
			$z['%start'] = round(100 / $hrMax * $z['#start']);
			$z['%end'] = round(100 / $hrMax * $z['#end']);
}
		ksort($Zones, SORT_NUMERIC);

		return $Zones;
	}

	/**
	 * #TSC
	 * array with the lower bound bpm of the zones (and some additional infos like text and color)
	 * index key is a sequence number and used for sorting and matching.
	 * starts with idx=0 which is the $minHr and will be inserted before the predefine zones will created.
	 * the predefine (generated or from FIT/device) will be start with idx=1.
	 * 
	 * @param int $minHr the minimal HR of this activity
	 * @param int $maxHr the max HR of this activity
	 * @param int $hrMaxUser the maximal users HR (not the max of this activity)
	 * @param string $footerHintMaxHr
	 * @return array
	 */
	private function getZones($minHr, $maxHr, $hrMaxUser, $footerHintMaxHr) {
		$majorZoneColors = array('#a6a6a6;', '#3b97f3', '#82c91e', '#f98925', '#d32020', '#b80000');
		$defaultZones = array();
		
		$k = 0;
		// add the low bound with min-HR (info: the high bound is the 6th element in the hrZoneBounds)
		$defaultZones[$k++] = array("hr" => 0);

		// are hrZoneBounds from a Garmin/FIT device available?
		if (!is_null($this->Context->activity()->hrZoneBounds()) && count($this->Context->activity()->hrZoneBounds()) == 6) {
			// format of the 91|109|127|146|164|182; details see FitActivity.readTimeInZone()
			$t = array('B1 Warm up', 'B2 Easy', 'B3 Aerob', 'B4 Intensiv', 'B5 Maximal', 'B6 Override');
			// now add the bounds with its colors
			foreach ($this->Context->activity()->hrZoneBounds() as $value) {
				$defaultZones[$k] = array("t" => $t[$k-1], "hr" => (int)$value, "c" => $majorZoneColors[$k-1]);
				$k++;
			}
			$this->footerHint = "Show the activity stored zones ".$footerHintMaxHr;
		} else {
			// default case: 10-percent steps to 100% (max-HR) with colors in the major range 50-100%
			$c = 0;
			for ($i = 3; $i <= 10; $i++, $k++) {
				$defaultZones[$k] = array("hr" => (int)($hrMaxUser * $i/10) + 1);
				// in zonee 50-100% use the same colors as upper
				if ($i >= 5 && $i <= 10) {
					$defaultZones[$k]['c'] = $majorZoneColors[$c++];
				}
			}
			$this->footerHint = "Show the default zones ".$footerHintMaxHr;
		}

		// if the min-HR lower than the first real/computed min HR (index=1), correct the 0 value
		if ($minHr < $defaultZones[1]['hr']) {
			$defaultZones[0]['hr'] = $minHr;
		}

		// this is a "dummy" zone and will never be filled with time/distance; but its used to limit the previous zone ;-)
		if ($maxHr >= end($defaultZones)['hr']) {
			$defaultZones[$k++] = array("hr" => $maxHr + 1);
		}

		// sort: highest idx/bpm must be the first entry
		krsort($defaultZones, SORT_NUMERIC);

		return $defaultZones;
	}

	/**
	 * @param int $bpm
	 * @param array $zones
	 * @return int
	 */
	private function zoneFor($bpm, $zones) {
		foreach ($zones as $key => $value) {
			if($bpm >= $value['hr']) {
				return $key;
			}
		}
	}
}
