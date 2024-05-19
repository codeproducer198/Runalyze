<?php
/**
 * This file contains class::TableZonesHeartrate
 * @package Runalyze\DataObjects\Training\View\Section
 */

use Runalyze\Activity\Pace;
use Runalyze\Calculation\Activity\HrZonesCalculator;
use Runalyze\Calculation\Distribution\TimeSeriesForTrackdata;
use Runalyze\Model\Trackdata;
use Runalyze\View\Activity\Context;

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
	 * Calculator
	 * @var Runalyze\Calculation\Activity\HrZonesCalculator
	 */
	protected $Calculator;

	/**
	 * Constructor
	 * @param \Runalyze\View\Activity\Context $context
	 */
	public function __construct(Context $context) {
		$this->Calculator = new HrZonesCalculator($context->activity()->hrZoneBounds(), $context->sport()->hrZoneBounds());
		
		parent::__construct($context);
	}

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

	private function getZones($minHr, $maxHr, $hrMaxUser, $footerHintMaxHr) {
		$z = $this->Calculator->getZones($minHr, $maxHr, $hrMaxUser);
		$this->footerHint = 'Shows the ' . $this->Calculator->getSourceHint() . ' ' . $footerHintMaxHr;
		return $z;
	}
}
