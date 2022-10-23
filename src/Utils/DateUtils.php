<?php
/**
 * @noinspection RegExpUnnecessaryNonCapturingGroup
 * @noinspection RegExpRedundantEscape
 * @noinspection RegExpSingleCharAlternation
 * @copyright    2007-2017 by Nurlan Mukhanov <nurike@gmail.com>
 * @license      MIT
 */

declare(strict_types=1);

namespace Falseclock\AdvancedPSR7\Utils;

use DateInterval;
use DateTime;
use DateTimeZone;
use Exception;

final class DateUtils
{
    const VALID_BROWSER_DATE_REGEXP = '/([0-9]{4}-(0[1-9]|1[0-2])-([0-2][0-9]|3[0-1])|([0-2][0-9]|3[0-1])-(0[1-9]|1[0-2])-[0-9]{4})/';
    const VALID_DATE_REGEXP = '/^(?:(?:31(\/|-|\.)(?:0?[13578]|1[02]|(?:Jan|Mar|May|Jul|Aug|Oct|Dec)))\1|(?:(?:29|30)(\/|-|\.)(?:0?[1,3-9]|1[0-2]|(?:Jan|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec))\2))(?:(?:1[6-9]|[2-9]\d)?\d{2})$|^(?:29(\/|-|\.)(?:0?2|(?:Feb))\3(?:(?:(?:1[6-9]|[2-9]\d)?(?:0[48]|[2468][048]|[13579][26])|(?:(?:16|[2468][048]|[3579][26])00))))$|^(?:0?[1-9]|1\d|2[0-8])(\/|-|\.)(?:(?:0?[1-9]|(?:Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep))|(?:1[0-2]|(?:Oct|Nov|Dec)))\4(?:(?:1[6-9]|[2-9]\d)?\d{2})$/mi';

	/**
	 * @return false|string
	 */
	public static function getTimeStampGMT() {
		return gmdate('Y-m-d H:i:sO');
	}

	/**
	 * @param $userTimeZone
	 *
	 * @return int
	 * @throws Exception
	 */
	public static function getTimeZoneOffset($userTimeZone) {

		if(!$userTimeZone) {
			$userTimeZone = date_default_timezone_get();
		}

		$TimeZoneSite = new DateTimeZone('GMT');
		$TimeZoneUser = new DateTimeZone($userTimeZone);

		$dateTimeSite = new DateTime("now", $TimeZoneSite);

		//$dateTimeUser = new DateTime("now", $TimeZoneUser);

		return $TimeZoneUser->getOffset($dateTimeSite);
	}

	/**
	 * @param DateInterval $interval
	 * @param string       $default
	 *
	 * @return string
	 */
	public static function intervalToIso(DateInterval $interval, string $default = 'PT0F'): string {
		static $f = [ 'S0F', 'M0S', 'H0M', 'DT0H', 'M0D', 'P0Y', 'Y0M', 'P0M' ];
		static $r = [ 'S', 'M', 'H', 'DT', 'M', 'P', 'Y', 'P' ];

		return rtrim(str_replace($f, $r, $interval->format('P%yY%mM%dDT%hH%iM%sS%fF')), 'PT') ? : $default;
	}

	public static function isDateStringValid($dateString) {

		if(preg_match_all(self::VALID_DATE_REGEXP, $dateString, $matches, PREG_SET_ORDER, 0) || preg_match(self::VALID_BROWSER_DATE_REGEXP, $dateString)) {
			return true;
		}

		return false;
	}

	/**
	 * @param $timestamp
	 *
	 * @return false|string
	 */
	public static function pgTimestampToISO($timestamp) {
		return date("r", strtotime($timestamp));
	}

	/**
	 * У текущего времени отнимает сравниваемое время и отдает время в секундах
	 *
	 * @param      $compareTime
	 * @param null $currentTime
	 *
	 * @return false|int
	 */
	public static function timestampDiff($compareTime, $currentTime = null): int {
		if($currentTime === null) {
			$currentTime = strtotime(date("Y-m-d H:i:sO"));
		}
		else {
			if(!is_int($currentTime))
				$currentTime = strtotime($currentTime);
		}

		if(!is_int($compareTime))
			$compareTime = strtotime($compareTime);

		return abs($currentTime - $compareTime);
	}
}
