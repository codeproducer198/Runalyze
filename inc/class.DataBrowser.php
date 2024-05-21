<?php
/**
 * This file contains class::DataBrowser
 * @package Runalyze\DataBrowser
 */

use Runalyze\Configuration;
use Runalyze\Dataset;
use Runalyze\Model\Factory;
use Runalyze\Util\Time;
use Runalyze\Util\LocalTime;
use Runalyze\View;

/**
 * DataBrowser
 * @author Hannes Christiansen
 * @package Runalyze\DataBrowser
 */
class DataBrowser {
	/**
	 * CSS-ID for calendar-widget
	 * @var string
	 */
	const CALENDAR_ID = 'data-browser-calendar';

	/**
	 * CSS-ID for refresh button
	 * @var string
	 */
	const REFRESH_BUTTON_ID = 'refreshDataBrowser';

	/**
	 * Timestamp for first day to be displayed
	 * @var int [timestamp without any timezone]
	 */
	protected $TimestampStart;

	/**
	 * Timestamp for last day to be displayed
	 * @var int [timestamp without any timezone]
	 */
	protected $TimestampEnd;

	/**
	 * sportid to be displayed
	 * @var null|int
	 */
	protected $SportId;

	/**
	 * Number of days to be displayed
	 * @var int
	 */
	protected $DayCount;

	/**
	 * Days to be displayed
	 * @var array
	 */
	protected $Days;

	/** @var array sport ids that are explicitly set as 'only icon' */
	protected $SportsShort = [];

	/** @var array type ids that are explicitly set as 'only icon' */
	protected $TypesShort = [];

	/** @var array type ids that are explicitly set as 'complete row' */
	protected $TypesNotShort = [];

	/** @var array distinct sports of the activities */
	protected $Sports = [];

	/** @var int */
	protected $AccountID;

	/** @var \PDOforRunalyze */
	protected $DB;

	/** @var \Runalyze\Dataset\Configuration */
	protected $DatasetConfig;

	/** @var \Runalyze\Dataset\Query */
	protected $DatasetQuery;

	/** @var \Runalyze\Model\Factory */
	protected $Factory;

	/**
	 * Number of additional columns
	 * @var int
	 */
	protected $AdditionalColumns = 2;

	/**
	 * Boolean flag: has current view no activities
	 * @var boolean
	 */
	protected $AllDaysEmpty = true;

	/**
	 * Default constructor
	 */
	public function __construct() {
		$this->initInternalObjects();
		$this->initTimestamps();
		$this->initSports(); // #TSC
		$this->initDays();
	}

	/**
	 * Init pointer to DB/Error-object
	 */
	protected function initInternalObjects() {
		$this->AccountID = SessionAccountHandler::getId();
		$this->DB = DB::getInstance();
		$this->DatasetConfig = new Dataset\Configuration($this->DB, $this->AccountID);
		$this->DatasetQuery = new Dataset\Query($this->DatasetConfig, $this->DB, $this->AccountID);
		$this->DatasetQuery->setAdditionalColumns(array('is_public'));
		$this->Factory = new Factory($this->AccountID);
	}

	/**
	 * Init private timestamps from request
	 */
	protected function initTimestamps() {
		if (!isset($_GET['start']) || !isset($_GET['end']) || !is_numeric($_GET['start']) || !is_numeric($_GET['end'])) {
			$Mode = Configuration::DataBrowser()->mode();

			if ($Mode->showMonth()) {
				$this->TimestampStart = LocalTime::fromString('first day of this month 00:00:00')->getTimestamp();
				$this->TimestampEnd   = LocalTime::fromString('last day of this month 23:59:59')->getTimestamp();
			} else {
				$this->TimestampStart = (new LocalTime)->weekstart();
				$this->TimestampEnd   = (new LocalTime)->weekend();
			}
		} else {
			$this->TimestampStart = $_GET['start'];
			$this->TimestampEnd   = $_GET['end'];
		}

		$this->DayCount = round(($this->TimestampEnd - $this->TimestampStart) / 86400);
	}

	/**
	 * Init sports where activity exists.
	 * #TSC
	 */
	protected function initSports() {
		// set the sport from the request to this model
		if (!isset($_GET['sport']) || !is_numeric($_GET['sport'])) {
			$this->SportId = null;
		} else {
			$this->SportId = $_GET['sport'];
		}

		// get sports where activities exists
		$Statement = $this->DatasetQuery->statementToFetchActivitySports($this->TimestampStart, $this->TimestampEnd, $this->SportId);

		while ($sport = $Statement->fetch()) {
			$this->Sports[$sport['id']] = array('name' => $sport['name'], 'icon' => $sport['img']);
		}
	}

	/**
	 * Init all days for being displayed
	 */
	protected function initDays() {
		$this->initShortModes();
		$this->initEmptyDays();

		$Statement = $this->DatasetQuery->statementToFetchActivities($this->TimestampStart, $this->TimestampEnd, $this->SportId);

		while ($Training = $Statement->fetch()) {
			$w = Time::diffInDays($Training['time'], $this->TimestampStart);

			if (
				in_array($Training['typeid'], $this->TypesShort) ||
				(!in_array($Training['typeid'], $this->TypesNotShort) && in_array($Training['sportid'], $this->SportsShort))
			) {
				$this->Days[$w]['shorts'][]    = $Training;
			} else {
				$this->Days[$w]['trainings'][] = $Training;
			}
			$this->AllDaysEmpty = false;
		}
		if (\Runalyze\Configuration::DataBrowser()->reverseMode()) {
		    $this->Days = array_reverse($this->Days);
        }
	}

	/**
	 * Init array with empty days
	 */
	protected function initEmptyDays() {
		$this->Days = array();
		$date = new LocalTime($this->TimestampStart);
		$date->setTime(0, 0, 0);

		for ($w = 0; $w <= ($this->DayCount-1); $w++) {
			$this->Days[] = array(
				'date' => $date->getTimestamp(),
				'shorts' => array(),
				'trainings' => array()
			);

			$date->add(new \DateInterval('P1D'));
		}
	}

	/**
	 * Init $this->sports_short
	 */
	protected function initShortModes() {
		$this->SportsShort = $this->DB->query('SELECT `id` FROM `'.PREFIX.'sport` WHERE `short`=1 AND accountid = '.$this->AccountID)->fetchAll(PDO::FETCH_COLUMN);
		$this->TypesShort = $this->DB->query('SELECT `id` FROM `'.PREFIX.'type` WHERE `short`=1 AND accountid = '.$this->AccountID)->fetchAll(PDO::FETCH_COLUMN);
		$this->TypesNotShort = $this->DB->query('SELECT `id` FROM `'.PREFIX.'type` WHERE `short`=0 AND accountid = '.$this->AccountID)->fetchAll(PDO::FETCH_COLUMN);
	}

	/**
	 * Display the DataBrowser
	 */
	public function display() {
		include 'tpl/tpl.DataBrowser.php';
	}

	/**
	 * Display links to navigate in calendar
	 */
	protected function displayNavigationLinks() {
		// #TSC more space between for better usablility on small devices
		// #TSC use fa-lg for larger icons in the navigation (better for tablets)
		echo '<span class="fa-lg" style="width: 25px; text-align: center; display: inline-block;">' . $this->getCalenderLink() . '</span>';
		echo '<span class="fa-lg" style="width: 25px; text-align: center; display: inline-block;">' . $this->getPrevLink() . '</span>';
		echo '<span class="fa-lg" style="width: 25px; text-align: center; display: inline-block;">' . $this->getNextLink() . '</span>';
		echo '<span class="fa-lg" style="width: 25px; text-align: center; display: inline-block;">' . $this->getCurrentLink() . '</span>';
	}

	/**
	 * Display title
	 */
	protected function displayTitle() {
		$now = (new LocalTime)->getTimestamp();
		$timestampForLinks = ($this->TimestampStart < $now && $now < $this->TimestampEnd) ? $now : $this->TimestampStart;
		$timeForLinks = new LocalTime($timestampForLinks);

		echo DataBrowserLinker::monthLink(Time::month($timeForLinks->format('m')), $timestampForLinks, $this->SportId).', ';
		echo DataBrowserLinker::yearLink($timeForLinks->format('Y'), $timestampForLinks, $this->SportId).', ';
		echo DataBrowserLinker::weekLink(Configuration::General()->weekStart()->phpWeek($timestampForLinks, true).'. '.__('week'), $timestampForLinks, null, $this->SportId);
	}

	/**
	 * Display specific icon-links
	 */
	protected function displayIconLinks() {
		echo '<ul>';
		echo '<li>'.$this->getSharedListLink().'</li>';
		echo '<li>'.$this->getMonthKmLink().'</li>';
		echo '<li>'.$this->getWeekKmLink().'</li>';
		echo '<li>'.$this->getAddLink().'</li>';
		echo '</ul>';
	}

	/**
	 * Display hover links
	 */
	protected function displayHoverLinks() {
		echo $this->getConfigLink();
		echo $this->getRefreshLink();
	}

	/**
	 * Display config link
	 */
	protected function getConfigLink() {
		echo Ajax::window('<a class="tab" href="settings/dataset">'.Icon::$CONF.'</a>');
	}

	/**
	 * Get link to navigation back
	 * @return string
	 */
	protected function getPrevLink() {
		$timestamp_array = DataBrowserLinker::prevTimestamps($this->TimestampStart, $this->TimestampEnd);

		return DataBrowserLinker::link(Icon::$BACK, $timestamp_array['start'], $timestamp_array['end'], __('back'), null, $this->SportId);
	}

	/**
	 * Get link to navigation forward
	 * @return string
	 */
	protected function getNextLink() {
		$timestamp_array = DataBrowserLinker::nextTimestamps($this->TimestampStart, $this->TimestampEnd);

		return DataBrowserLinker::link(Icon::$NEXT, $timestamp_array['start'], $timestamp_array['end'], __('next'), null, $this->SportId);
	}

	/**
	 * Get link to jump to today
	 * @return string
	 */
	protected function getCurrentLink() {
		return DataBrowserLinker::link('<i class="fa fa-lg fa-fw fa-circle"></i>', '', '', __('today'), null, $this->SportId);
	}

	/**
	 * Get ajax-link for reload this DataBrowser
	 * @return string
	 */
	protected function getRefreshLink() {
		$Link = DataBrowserLinker::link(Icon::$REFRESH, $this->TimestampStart, $this->TimestampEnd, null, null, $this->SportId);

		return str_replace('<a ', '<a id="'.self::REFRESH_BUTTON_ID.'" '.Ajax::tooltip('', __('Reload current datasheet'), false, true), $Link);
	}

	/**
	 * Get link including the selection of one sport.
	 * @param $sportId
	 * @param $name
	 * @param $icon
	 * @param $active the link was clicked and is active
	 * @return string
	 */
	protected function getSportLink(int $sportId, string $name, string $icon, bool $active) {
		$style = "padding-left: 2px; padding-right: 2px;";
		if ($active) {
			// if this sport is currently selected/active - the link is used for de-selection
			return DataBrowserLinker::link('<i class="fa fa-fw ' . $icon . '" style="'.$style.' color: black; font-weight:bolder; font-size: 16px; vertical-align: -2px;"></i>',
					$this->TimestampStart, $this->TimestampEnd, __($name), null, null);
		} else {
			return DataBrowserLinker::link('<i class="fa fa-lg fa-fw ' . $icon . '" style="'.$style.'"></i>',
					$this->TimestampStart, $this->TimestampEnd, __($name), null, $sportId);
		}
	}

	/**
	 * Get ajax-link for choosing timestamps from calendar
	 * @return string
	 */
	protected function getCalenderLink() {
		return '<span id="calendar-link" class="link" title="'.__('Calendar').'">'.Icon::$CALENDAR.'</span>';
	}

	/**
	 * Show year links for the previous 20 years
	 * #TSC
	 */
	protected function getYearLinks() {
		$t = new DateTime('now', new DateTimeZone('UTC'));
		$oneYear = new DateInterval('P1Y');
		$selectedYear = (new LocalTime($this->TimestampStart))->format('Y');

		echo '<table>';
		for($i = 0; $i < 20; $i++) {
			if($i % 4 == 0) {
				echo '<tr>';
			}
			$year = $t->format('Y');
			echo '<td' . ($year == $selectedYear ? ' style="background-color: #ff6"' : '') . '>' . 
				DataBrowserLinker::yearLink($year, $t->getTimestamp(), $this->SportId) . '</td>';
			if($i % 4 == 3) {
				echo '</tr>';
			}
			$t = $t->sub($oneYear);
		}
		echo '</table>';
	}

	/**
	 * Get ajax-link for showing month-kilometer
	 * @return string
	 */
	protected function getMonthKmLink() {
		return Ajax::window('<a href="'.PlotSumData::$URL.'?type=month" '.Ajax::tooltip('', __('Activity per month'), false, true).'>'.Icon::$BARS_BIG.'</a>');
	}

	/**
	 * Get ajax-link for showing week-kilometer
	 * @return string
	 */
	protected function getWeekKmLink() {
		return Ajax::window('<a href="'.PlotSumData::$URL.'?type=week" '.Ajax::tooltip('', __('Activity per week'), false, true).'>'.Icon::$BARS_SMALL.'</a>');
	}

	/**
	 * Get list to shared list
	 * @returns string
	 */
	protected function getSharedListLink() {
		return SharedLinker::getListLinkForCurrentUser();
	}

	/**
	 * Get ajax-link for searching
	 * @return string
	 */
	protected function getNaviSearchLink() {
		return Ajax::window('<a href="'.SearchLink::$WINDOW_URL.'" '.Ajax::tooltip('', __('Search for an activity'), false, true).'>'.Icon::$SEARCH.'</a>', 'big');
	}

	/**
	 * Get ajax-link for adding a training
	 * @return string
	 */
	protected function getAddLink() {
		return ImporterWindow::link();
	}

	/**
	 * Get date string for given timestamp
	 * @param int $timestampInNoTimezone
	 * @return string
	 */
	protected function dateString($timestampInNoTimezone) {
		$localTime = new LocalTime($timestampInNoTimezone);
		$addLink = '';
		$weekDay = Time::weekday($localTime->format('w'), true);

		if (Configuration::DataBrowser()->showCreateLink() && !FrontendShared::$IS_SHOWN) {
			$addLink = ImporterWindow::linkForDate($localTime->toServerTimestamp());
		}

		if ($localTime->isToday()) {
			$weekDay = '<strong>'.$weekDay.'</strong>';
		}

		return $localTime->format('d.m.').' '.$addLink.' '.$weekDay;
	}

	/**
	 * Additional columns that are shown next to date columns
	 * @param \Runalyze\View\Dataset\Table $table
	 * @param \Runalyze\Dataset\Context $context
	 * @return string html string that must contain `$this->AdditionalColumns - 2` columns
	 */
	protected function codeForAdditionalColumnsForActivity(View\Dataset\Table $table, Dataset\Context $context)
	{
		return '';
	}
}
