<?php
/**
 * This file contains class::DataBrowserLinker
 * @package Runalyze\DataBrowser
 */

use Runalyze\Util\LocalTime;

/**
 * Linker for DataBrowser
 *
 * @author Hannes Christiansen
 * @package Runalyze\DataBrowser
 */
class DataBrowserLinker {
	/**
	 * Get a ajax-link to a specified DataBrowser
	 * @param string $name Name to be displayed as link
	 * @param int $startTimestampInNoTimezone Timestamp for first date in browser
	 * @param int $endTimestampInNoTimezone Timestamp for last date in browser
	 * @param int $sportid sport-id to filter one sport
	 * @param string $title title for the link
	 * @param string $rel
	 * @return string HTML-link
	 */
	public static function link($name, $startTimestampInNoTimezone, $endTimestampInNoTimezone, $title = '', $rel = '', ?int $sportid = null) {
		if (FrontendShared::$IS_SHOWN)
			return DataBrowserShared::getLink($name, $startTimestampInNoTimezone, $endTimestampInNoTimezone, $title = '');

		$href = 'call/call.DataBrowser.display.php?start='.$startTimestampInNoTimezone.'&end='.$endTimestampInNoTimezone;
		if (!is_null($sportid)) {
			$href = $href . '&sport=' . $sportid; // #TSC add sport selection criteria
		}

		return Ajax::link($name, 'data-browser-inner', $href, $rel, $title, 'Pace.restart()');
	}

	/**
	 * Get a ajax-link to a specified DataBrowser
	 * @param string $name Name to be displayed as link
	 * @param int $time Timestamp of the week
	 * @param bool $transformToLocalTime
	 * @param int $sportid sport-id to filter one sport
	 * @return string HTML-link
	 */
	public static function weekLink($name, $time, $transformToLocalTime = true, ?int $sportid = null) {
		$localTime = $transformToLocalTime ? LocalTime::fromServerTime($time) : new LocalTime($time);

		return self::link($name, $localTime->weekstart(), $localTime->weekend(), '', 'week-link', $sportid);
	}

	/**
	 * Get a ajax-link to a specified DataBrowser
	 * @param string $name Name to be displayed as link
	 * @param int $timestampInNoTimezone Timestamp of the month
	 * @param int $sportid sport-id to filter one sport
	 * @return string HTML-link
	 */
	public static function monthLink($name, $timestampInNoTimezone, ?int $sportid = null) {
		$localTime = new LocalTime($timestampInNoTimezone);

		return self::link($name, $localTime->monthStart(), $localTime->monthEnd(), '', 'month-link', $sportid);
	}

	/**
	 * Get a ajax-link to a specified DataBrowser
	 * @param string $name Name to be displayed as link
	 * @param int $timestampInNoTimezone Timestamp of the year
	 * @param int $sportid sport-id to filter one sport
	 * @return string HTML-link
	 */
	public static function yearLink($name, $timestampInNoTimezone, ?int $sportid = null) {
		$localTime = new LocalTime($timestampInNoTimezone);

		return self::link($name, $localTime->yearStart(), $localTime->yearEnd(), '', 'year-link', $sportid);
	}

	/**
	 * Get previous timestamps depending on current time-interval (just an alias for getNextTimestamps($start, $end, true);)
	 * @param int $startTimestampInNoTimezone Timestamp for first date in browser
	 * @param int $endTimestampInNoTimezone Timestamp for last date in browser
	 * @return array Returns an array {'start', 'end'}
	 */
	public static function prevTimestamps($startTimestampInNoTimezone, $endTimestampInNoTimezone) {
		return self::nextTimestamps($startTimestampInNoTimezone, $endTimestampInNoTimezone, true);
	}

	/**
	 * Get next timestamps depending on current time-interval
	 * @param int $startTimestampInNoTimezone Timestamp for first date in browser
	 * @param int $endTimestampInNoTimezone Timestamp for last date in browser
	 * @param bool $getPrev optional to get previous timestamps
	 * @return array Returns an array {'start', 'end'}
	 */
	public static function nextTimestamps($startTimestampInNoTimezone, $endTimestampInNoTimezone, $getPrev = false) {
		$start = new LocalTime(is_numeric($startTimestampInNoTimezone) ? $startTimestampInNoTimezone : null);
		$end = new LocalTime(is_numeric($endTimestampInNoTimezone) ? $endTimestampInNoTimezone : null);

		$date = array();
		$factor = $getPrev ? -1 : 1;
		$diff_in_days = round(($end->getTimestamp() - $start->getTimestamp()) / 86400);
		$start_month = $start->format('m');
		$start_day   = $start->format('d');
		$start_year  = $start->format('Y');
		$end_month   = $end->format('m');
		$end_day     = $end->format('d');
		$end_year    = $end->format('Y');

		if (360 < $diff_in_days && $diff_in_days < 370) {
			$start_year  += 1*$factor;
			$end_year    += 1*$factor;
		} elseif (28 <= $diff_in_days && $diff_in_days <= 31) {
			$start_month += 1*$factor;
			$end_month   += 1*$factor;

			if ($start_day == 1 && $end_day != 0) {
				$end_month = $start_month + 1;
				$end_day = 0;
			}
		} else {
			$start_day   += $diff_in_days*$factor;
			$end_day     += $diff_in_days*$factor;
		}

		$date['start'] = $start->setDate($start_year, $start_month, $start_day)->setTime(0, 0, 0)->getTimestamp();
		$date['end'] = $end->setDate($end_year, $end_month, $end_day)->setTime(23, 59, 59)->getTimestamp();

		return $date;
	}
}
