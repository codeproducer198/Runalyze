<?php
/**
 * This file contains class::TablePaceGoal
 * @package Runalyze\DataObjects\Training\View\Section
 */

use Runalyze\View\Activity\Context;
use Runalyze\Activity\Distance;
use Runalyze\Activity\Duration;
use Runalyze\Activity\Pace;
use Runalyze\Parameter\Application\PaceUnit;

/**
 * Display PaceGoal / PacePro.
 * #TSC
 *
 * @author codeproducer
 * @package Runalyze\DataObjects\Training\View\Section
 */
class TablePaceGoal {
	// Tolerance in % between stored running values and run-values from the plan; if the diff is higher than the tolerance, a warning is shown
	protected $TolerancePercent = 3.5;

	protected $GreenTooltip = '<i class="fa fa-fw fa-info-circle atRight" rel="tooltip" data-original-title="Plus/green means you are faster/better than<br/>your goal. Minus/red you are slower/worse."></i>';

	/**
	 * Code
	 * @var string
	 */
	protected $Code = '';

	/**
	 * @param \Runalyze\View\Activity\Context $context
	 */
	public function __construct(Context $context) {
		$this->distance = new Distance(0);

		// activity() = Runalyze\Model\Activity\Entity
		$this->setDataToCode($context->activity()->paceGoal(), $context->activity()->duration(), $context->activity()->distance() * 1000);
	}

	/**
	 * Get code
	 * @return string
	 */
	public function getCode() {
		return $this->Code;
	}

	/**
	 * generate data
	 */
	protected function setDataToCode(array $inPaceGoal, int $activityTimeS, float $activityDistanceM) {
		// set height, so that the layout not change why change the tab
		$this->Code .= '<div style="height: 200px; overflow: scroll;">';
		
		// header
		$this->Code .= '<table class="fullwidth zebra-style">';
		$this->Code .= '<thead><tr>';
		$this->Code .= '<th class="r">#</th>';
		$this->Code .= '<th class="r"><i>'. __('Distance') .' '. __('Goal') .'</i></th>';
		$this->Code .= '<th class="r">'. __('Distance') .'</th>';
		$this->Code .= '<th class="r">'. __('Distance') .' &sum;</th>';

		$this->Code .= '<th class="r"><i>'. __('Time') .' '. __('Goal') .'</i></th>';
		$this->Code .= '<th class="r">'. __('Time') . '</th>';
		$this->Code .= '<th class="r">'. __('Time') . ' &Delta; ' . $this->GreenTooltip . '</th>';

		$this->Code .= '<th class="r"><i>'. __('Pace') .' '. __('Goal') .'</i></th>';
		$this->Code .= '<th class="r">'. __('Pace').'</th>';
		$this->Code .= '<th class="r" style="padding-right: 20px;">'. __('Pace') . ' &Delta; ' . $this->GreenTooltip . '</th>';

		$this->Code .= '</tr></thead>';

		// splits/laps
		$this->Code .= '<tbody>';

		$count = 1;
		$paceGoal = new Pace(0, 100 / 1000, PaceUnit::MIN_PER_KM);
		$paceRun = new Pace(0, 100 / 1000, PaceUnit::MIN_PER_KM);
		$durationGoal = new Duration(0);
		$durationRun = new Duration(0);

		$sum_pp_dist_m = $dist_m = 0;
		$sum_pp_t_s = $sum_t_s = 0;

		foreach ($inPaceGoal as $split) {
			$sum_pp_dist_m += $split['pp_dist_m'];
			$sum_dist_m += $split['dist_m'];
			$sum_pp_t_s += $split['pp_t_s'];
			$sum_t_s += $split['t_s'];

			$this->Code .= '<tr class="r">';

			//var_dump($split);
			$this->Code .= '<td>'. $count++ .'</td>';
			$this->Code .= '<td><i>'. $this->formatDistance($split['pp_dist_m']) .'</i></td>'; // goal
			$this->Code .= '<td>'. $this->formatDistance($split['dist_m']) .'</td>';
			$this->Code .= '<td>'. $this->formatDistance($sum_dist_m) .'</td>';

			$durationGoal->fromSeconds($split['pp_t_s']);
			$this->Code .= '<td><i>'. $durationGoal->string() .'</i></td>'; // goal
			$durationRun->fromSeconds($split['t_s']);
			$this->Code .= '<td>'. $durationRun->string() .'</td>';
			$this->Code .= '<td>'. $durationGoal->compareTo($durationRun) .'</td>';

			$this->setPace($paceGoal, $split['pp_dist_m'], $split['pp_t_s']);
			$this->Code .= '<td><i>'. $paceGoal->valueWithAppendix() .'</i></td>'; // goal
			$this->setPace($paceRun, $split['dist_m'], $split['t_s']);
			$this->Code .= '<td>'. $paceRun->valueWithAppendix() .'</td>';
			$this->Code .= '<td style="padding-right: 20px;">'. $paceRun->compareTo($paceGoal) .'</td>';

			$this->Code .= '</tr>';
		}

		$this->Code .= '</tbody>';

		// summary
		$this->Code .= '<tbody>';
		$this->Code .= '<tr class="r" style="border-top: 1px solid #666;">';
		$this->Code .= '<td></td>';
		$this->Code .= '<td><i>'. $this->formatDistance($sum_pp_dist_m) .'</i></td>'; // goal
		$this->Code .= '<td>'. $this->formatDistance($sum_dist_m) .'</td>';
		$this->Code .= '<td>'. $this->formatDistance($sum_dist_m) .'</td>';

		$durationGoal->fromSeconds($sum_pp_t_s);
		$this->Code .= '<td><i>'. $durationGoal->string() .'</i></td>'; // goal
		$durationRun->fromSeconds($sum_t_s);
		$this->Code .= '<td>'. $durationRun->string() .'</td>';
		$this->Code .= '<td>'. $durationGoal->compareTo($durationRun) .'</td>';

		$this->setPace($paceGoal, $sum_pp_dist_m, $sum_pp_t_s);
		$this->Code .= '<td><i>'. $paceGoal->valueWithAppendix() .'</i></td>'; // goal
		$this->setPace($paceRun, $sum_dist_m, $sum_t_s);
		$this->Code .= '<td>'. $paceRun->valueWithAppendix() .'</td>';
		$this->Code .= '<td style="padding-right: 20px;">'. $paceRun->compareTo($paceGoal) .'</td>';

		$this->Code .= '</tr></tbody>';

		$this->Code .= '</table>';

		// check if the current activity and the IST values from the pace-goal are "valid"
		$diffTime = round(abs(100 / $sum_t_s * $activityTimeS - 100), 1);
		$diffDist = round(abs(100 / $sum_dist_m * $activityDistanceM - 100), 1);
		if ($diffTime >= $this->TolerancePercent || $diffDist >= $this->TolerancePercent) {
			$this->Code .= HTML::warning("Your activity distance (".$this->formatDistance($activityDistanceM).", ".$diffDist."%) and/or ".
				"duration (".Duration::format($activityTimeS).", ".$diffTime."%) differ more than ".$this->TolerancePercent."% from your goal values. " .
				"Maybe you have changed your activity data after the import and the Pace-Goal values are out-dated.");
		}

		$this->Code .= '</div>';
	}

	protected function formatDistance(int $meter): string {
		$this->distance->set($meter / 1000);
		return $this->distance->stringMeter();
	}

	protected function setPace(Pace $pace, int $meter, int $seconds) {
		$pace->setTime($seconds);
		$pace->setDistance($meter / 1000);
	}
}