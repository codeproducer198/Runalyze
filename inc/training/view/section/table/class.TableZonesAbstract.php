<?php
/**
 * This file contains class::TableZonesAbstract
 * @package Runalyze\DataObjects\Training\View\Section
 */

use Runalyze\Activity\Distance;
use Runalyze\Activity\Duration;
use Runalyze\View\Activity\Context;

/**
 * Display zones
 * 
 * @author Hannes Christiansen
 * @package Runalyze\DataObjects\Training\View\Section
 */
abstract class TableZonesAbstract {
	/**
	 * @var int [s]
	 */
	const MINIMUM_TIME_IN_ZONE = 10;

	/**
	 * Context
	 * @var \Runalyze\View\Activity\Context
	 */
	protected $Context;

	/**
	 * Data
	 * @var array
	 */
	protected $Data = array();

	private $TitlePercent = ''; // #TSC

	/**
	 * Constructor
	 * @param \Runalyze\View\Activity\Context $context
	 */
	public function __construct(Context $context) {
		$this->Context = $context;

		if ($this->Context->trackdata()->has(Runalyze\Model\Trackdata\Entity::TIME)) {
			$this->initData();
			$this->convertData();
		}
	}

	/**
	 * Get title for average
	 * @return string
	 */
	public function titleForAverage() { return ''; }

	/**
	 * Show average?
	 * @return bool
	 */
	private function showAverage() { return $this->titleForAverage() != ''; }

	/**
	 * Init data
	 */
	abstract protected function initData();

	/**
	 * Get hint for footer #TSC
	 * @return string
	 */
	public function footerHint() { return null; }

	/**
	 * Convert data
	 */
	private function convertData() {
		$totalTime = 0;
		$totalDist = 0;

		foreach ($this->Data as $Info) {
 			$totalTime += $Info['time'];
			$totalDist += $Info['distance'];
		}

		foreach ($this->Data as $i => $Info) {
			if ($totalTime > 0) {
				$percentage = round(100 * $Info['time'] / $totalTime, 1);
				$this->TitlePercent = 'Time';
			} elseif ($totalDist > 0) {
				$percentage = round(100 * $Info['distance'] / $totalDist, 1);
				$this->TitlePercent = 'Distance';
			} else {
				$percentage = '-';
			}

			$this->Data[$i]['percentage'] = $percentage;
			$this->Data[$i]['time']       = $totalTime > 0 ? Duration::format($Info['time']) : '-';
			$this->Data[$i]['distance']   = $totalDist > 0 ? Distance::format($Info['distance']) : '-';
		}
	}

	/**
	 * Display
	 * @return string
	 */
	final public function getCode() {
		if (empty($this->Data))
			return '';

		$Code = '<table class="fullwidth bar-chart-table">';
		$Code .= '<thead><tr>';
		$Code .= '<th>'.__('Zone').'</th>';
		$Code .= '<th style="text-align: left;">% '.__($this->TitlePercent).'</th>'; // #TSC set the table column title
		$Code .= '<th>'.__('Time').'</th>';
		$Code .= '<th>'.__('Distance').'</th>';
		if ($this->showAverage()) $Code .= '<th>'.$this->titleForAverage().'</th>';
		$Code .= '</tr></thead>';

		$Code .= '<tbody>';
		$Code .= $this->getDataCode();
		$Code .= '</tbody>';
		$Code .= '</table>';

		if (!empty($this->footerHint())) {
			$Code .= HTML::info($this->footerHint());
		}

		return $Code;
	}

	/**
	* Display data
	 * @return string
	*/
	private function getDataCode() {
		$Code = '';

		foreach ($this->Data as $Info) {
			$Code .= '<tr>';
			$Code .= '<td class="bar-chart-label">'.$Info['zone'].'</td>';

			// #TSC support of colors for the percent bar
			$stylecolor = array_key_exists('color', $Info) ? 'background:'.$Info['color'].';' : '';
			$Code .= '<td class="bar-chart-value-cell"><span class="bar-chart-value" style="width:'.$Info['percentage'].'%;'.$stylecolor.'"></span> <span class="bar-chart-text">'.$Info['percentage'].' &#37;</span></td>';

			$Code .= '<td>'.$Info['time'].'</td>';
			$Code .= '<td>'.$Info['distance'].'</td>';
			if ($this->showAverage()) $Code .= '<td>'.$Info['average'].'</td>';
			$Code .= '</tr>';
		}

		return $Code;
	}
}
