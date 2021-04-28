<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@201442 */

class HolidaysUtils extends SDKExtendableUniqueClass {

	/**
	 * Fixed day holidays for some european countries
	 * The key is the ISO3166-1 2 letters code
	 * Sources: https://www.timeanddate.com/ and Wikipedia
	 * Some of them might be wrong, since the sources were discordant
	 */
	protected static $fixedHolidays = [
		'AT' => [
			'01-01', //capodanno
			'06-01', //epifania
			'01-05', //festa del lavoro
			'15-08', //assunzione
			'26-10', //national day
			'01-11', //ognissanti
			'08-12', //immacolata concezione
			'25-12', //natale
			'26-12', //santo stefano
		],
		'AU' => [
			'01-01', //capodanno
			'26-01', //australia day
			'25-04', //Anzac day
			'25-12', //natale
			'26-12', //santo stefano
		],
		'BE' => [
			'01-01', //capodanno
			'01-05', //festa del lavoro
			'21-07', //national day
			'15-08', //assunzione
			'01-11', //ognissanti
			'11-11', //armistizio
			'25-12', //natale
		],
		'BR' => [
			'01-01', //capodanno
			'21-04',
			'01-05',
			'07-09', //indipendenza
			'12-10', //ognissanti
			'02-11', //morti
			'15-11', //republic day
			'25-12', //natale
		],
		'CA' => [
			'01-01', //capodanno
			'01-07', //canada day
			'25-12', //natale
		],
		'CH' => [
			'01-08', //national day
		],
		'CZ' => [
			'01-01', //capodanno/indipendenza
			'01-05', //festa del lavoro
			'08-05', //vittoria europa
			'05-07', //s cirillo e metodio
			'06-07', //jan hus
			'28-09', //s venceslao
			'28-10', //indipendenza
			'17-11', //freedom day
			'24-12', //vigilia
			'25-12', //natale
			'26-12', //s stefano
		],
		'DE' => [
			'01-01', //capodanno
			'01-05', //festa del lavoro
			'03-10', //german unity
			'25-12', //natale
			'26-12', //natale2
		],
		'DK' => [
			'01-01', //capodanno
			'01-05', //festa del lavoro
			'05-06', //costituzione
			'25-12', //natale
			'26-12', //natale2
		],
		'EE' => [
			'01-01', //capodanno
			'24-02', //indipendenza
			'01-05', //festa del lavoro
			'23-06', //victory day
			'24-06', //midsummer
			'20-08', //restoration indipendence
			'24-12', //vigilia
			'25-12', //natale
			'26-12', //santo stefano
		],
		'ES' => [
			'01-01', //capodanno
			'06-01', //epifania
			'01-05', //festa del lavoro
			'15-08', //assunzione
			'12-10', //hispanic day
			'08-12', //immacolata concezione
			'25-12', //natale
		],
		'FI' => [
			'01-01', //capodanno
			'06-01', //epifania
			'01-05', //festa del lavoro
			'06-12', //indipendenza
			'24-12', //vigilia
			'25-12', //natale
			'26-12', //santo stefano
		],
		'FR' => [
			'01-01', //capodanno
			'01-05', //festa del lavoro
			'08-05', //vittoria ww2
			'14-07', //presa bastiglia
			'15-08', //assunzione
			'01-11', //ognissanti
			'11-11', //armistizio
			'25-12', //natale
		],
		'GB' => [
			'01-01', //capodanno
			'25-12', //natale
			'26-12', //santo stefano
		],
		'GR' => [
			'01-01', //capodanno
			'06-01', //epifania
			'25-03', //indipendenza
			'01-05', //festa del lavoro
			'15-08', //assunzione
			'28-10', //ochi day
			'25-12', //natale
			'26-12', //santo stefano
		],
		'HU' => [
			'01-01', //capodanno
			'15-03', //national day
			'01-05', //festa del lavoro
			'20-08', //state foundation
			'23-10', //national day
			'01-11', //ognissanti
			'25-12', //natale
			'26-12', //santo stefano
		],
		'IE' => [
			'01-01', //capodanno
			'17-03', //st patrick
			'25-12', //natale
			'26-12', //santo stefano
		],
		'IN' => [
			// only national days, not regional ones
			'26-01', //Republic day
			'15-08', //indipendenza
			'02-10', //Gandhi Jayanti
		],
		'IT' => [
			'01-01', //capodanno
			'06-01', //epifania
			'25-04', //liberazione
			'01-05', //festa del lavoro
			'02-06', //repubblica
			'15-08', //assunzione
			'01-11', //ognissanti
			'08-12', //immacolata concezione
			'25-12', //natale
			'26-12', //santo stefano
		],
		'NL' => [
			'01-01', //capodanno
			'27-04', //king's birthday (does it change with a new king?)
			'05-05', //liberazione
			'25-12', //natale
			'26-12', //santo stefano
		],
		'LT' => [
			'01-01', //capodanno
			'16-02', //indipendenza
			'11-03', //indipendenza2
			'01-05', //festa del lavoro
			'24-06', //st john
			'06-07', //statehood day
			'15-08', //assunzione
			'01-11', //ognissanti
			'02-11', //all souls
			'24-12', //vigilia
			'25-12', //natale
			'26-12', //santo stefano
		],
		'LV' => [
			'01-01', //capodanno
			'01-05', //festa del lavoro
			'04-05', //indipendenza
			'23-06', //midsummer eve
			'24-06', //midsummer
			'18-11', //day of republic
			'24-12', //vigilia
			'25-12', //natale
			'26-12', //santo stefano
			'31-12', //vigilia capodanno
		],
		'NO' => [
			'01-01', //capodanno
			'01-05', //festa del lavoro
			'17-05', //costituzione
			'25-12', //natale
			'26-12', //santo stefano
		],
		'PL' => [
			'01-01', //capodanno
			'06-01', //epifania
			'01-05', //festa del lavoro
			'03-05', //festa costituzione
			'15-08', //assunzione
			'01-11', //ognissanti
			'11-11', //indipendenza
			'25-12', //natale
			'26-12', //santo stefano
		],
		'PT' => [
			'01-01', //capodanno
			'25-04', //liberty day
			'01-05', //festa del lavoro
			'10-06', //portugal day
			'15-08', //assunzione
			'05-10', //festa repubblica
			'01-11', //ognissanti
			'01-12', //indipendenza
			'08-12', //immacolata concezione
			'25-12', //natale
		],
		'RU' => [
			'01-01', //capodanno
			'02-01',
			'03-01',
			'04-01',
			'05-01',
			'06-01',
			'07-01', //Orthodox Christmas day
			'08-01',
			'23-02', //Defender of the Fatherland Day
			'08-03', //women's day
			'01-05', //Spring and Labor Day
			'09-05', //Victory Day
			'12-06', //Russia day
			'04-11', //Unity day
		],
		'SE' => [
			'01-01', //capodanno
			'06-01', //epifania
			'01-05', //festa del lavoro
			'06-06', //national day
			'31-10', //ognissanti
			'25-12', //natale
			'26-12', //santo stefano
		],
		'SK' => [
			'01-01', //capodanno/indipendenza
			'06-01', //epifania
			'01-05', //festa del lavoro
			'08-05', //vittoria europa
			'05-07', //s cirillo e metodio
			'29-08', //uprising
			'01-09', //costituzione
			'15-09', //day our lady
			'01-11', //ognissanti
			'17-11', //fight freedom
			'24-12', //vigilia
			'25-12', //natale
			'26-12', //s stefano
		],
		'US' => [
			'01-01', //capodanno
			'04-07', //4 luglio
			'11-11', //veteran's day
			'25-12', //natale
		],
	];
	
	// crmv@123658
	/**
	 * Return the easter date for the specified year in the timestamp format (hour is midnight)
	 */
	public static function getEasterDate($Year) {
		$G = $Year % 19;
		$C = (int)($Year / 100);
		$H = (int)($C - (int)($C / 4) - (int)((8*$C+13) / 25) + 19*$G +  15) % 30;
		$I = (int)$H - (int)($H / 28)*(1 - (int)($H / 28)*(int)(29 / ($H +1))*((int)(21 - $G) / 11));
		$J = ($Year + (int)($Year/4) + $I + 2 - $C + (int)($C/4)) % 7;
		$L = $I - $J;
		$m = 3 + (int)(($L + 40) / 44);
		$d = $L + 28 - 31 * ((int)($m / 4));
		$y = $Year;
		$E = mktime(0,0,0, $m, $d, $y);
		return $E;
	}
	
	public static function getSupportedCountries() {
		return array_keys(self::$fixedHolidays);
	}
	
	/**
	 * Return a list of dynamic holidays for the specified year and country (only Italy is supported now)
	 */
	public static function getDynamicHolidaysForYear($year, $country = 'IT') {
		$easter = self::getEasterDate($year);
		if ($country == 'AT') {
			$holidays = Array(
				date("d-m",$easter), //pasqua
				date("d-m",strtotime("+ 1 day",$easter)), //pasquetta
				date("d-m",strtotime("+ 39 day",$easter)), // Ascensione
				date("d-m",strtotime("+ 50 day",$easter)), // whit monday
				date("d-m",strtotime("+ 60 day",$easter)), // Corpus Christi
			);
		} elseif ($country == 'AU') {
			$holidays = Array(
				date("d-m",strtotime("- 2 day",$easter)), //venerdì
				date("d-m",strtotime("- 1 day",$easter)), //sabato
				date("d-m",$easter), //pasqua
				date("d-m",strtotime("+ 1 day",$easter)), //pasquetta
			);
			// subsitute weekend holidays for mondays
			$dow = date('w', strtotime($year.'-01-01'));
			if ($dow == 0 | $dow == 6) {
				$holidays[] = date("d-m",strtotime('next monday', strtotime($year.'-01-01 12:00:00')));
			}
			$dow = date('w', strtotime($year.'-01-26'));
			if ($dow == 0 | $dow == 6) {
				$holidays[] = date("d-m",strtotime('next monday', strtotime($year.'-01-26 12:00:00')));
			}
			$dow = date('w', strtotime($year.'-12-25'));
			if ($dow == 0 | $dow == 6) {
				$holidays[] = date("d-m",strtotime('next monday', strtotime($year.'-12-25 12:00:00')));
			}
		} elseif ($country == 'BE') {
			$holidays = Array(
				date("d-m",$easter), //pasqua
				date("d-m",strtotime("+ 1 day",$easter)), //pasquetta
				date("d-m",strtotime("+ 39 day",$easter)), // Ascensione
				date("d-m",strtotime("+ 49 day",$easter)), // Pentecoste
				date("d-m",strtotime("+ 50 day",$easter)), // whit monday
			);
		} elseif ($country == 'CA') {
			$holidays = Array(
				date("d-m",strtotime("- 2 day",$easter)), //venerdì
				date("d-m",strtotime('next monday', strtotime($year.'-08-31 12:00:00'))), //Labour Day
			);
		} elseif ($country == 'CZ') {
			$holidays = Array(
				date("d-m",strtotime("- 2 day",$easter)), //venerdì
				date("d-m",$easter), //pasqua
				date("d-m",strtotime("+ 1 day",$easter)), //pasquetta 
			);
		} elseif ($country == 'DE') {
			$holidays = Array(
				date("d-m",strtotime("- 2 day",$easter)), //venerdì
				date("d-m",$easter), //pasqua
				date("d-m",strtotime("+ 1 day",$easter)), //pasquetta
				date("d-m",strtotime("+ 39 day",$easter)), // Ascensione
				date("d-m",strtotime("+ 50 day",$easter)), // whit monday
			);
		} elseif ($country == 'DK') {
			$holidays = Array(
				date("d-m",strtotime("- 3 day",$easter)), //giovedi
				date("d-m",strtotime("- 2 day",$easter)), //venerdì
				date("d-m",$easter), //pasqua
				date("d-m",strtotime("+ 1 day",$easter)), //pasquetta 
				date("d-m",strtotime("+ 26 day",$easter)), // great prayer day
				date("d-m",strtotime("+ 39 day",$easter)), // Ascensione
				date("d-m",strtotime("+ 49 day",$easter)), // Pentecoste
				date("d-m",strtotime("+ 50 day",$easter)), // whit monday
			);
		} elseif ($country == 'EE') {
			$holidays = Array(
				date("d-m",strtotime("- 2 day",$easter)), //venerdì
				date("d-m",$easter), //pasqua
				date("d-m",strtotime("+ 49 day",$easter)), // Pentecoste
			);
		} elseif ($country == 'ES') {
			$holidays = Array(
				date("d-m",strtotime("- 2 day",$easter)), //venerdì
				date("d-m",$easter), //pasqua
			);
		} elseif ($country == 'FI') {
			// calc midsommer, saturday between 20 and 26 june
			$midsummer = strtotime('next saturday', strtotime($year.'-06-19 12:00:00'));
			// calc all saints day, saturday after 31/10
			$allSaints = strtotime('next saturday', strtotime($year.'-10-30 12:00:00'));
			$holidays = Array(
				date("d-m",strtotime("- 2 day",$easter)), //venerdì
				date("d-m",$easter), //pasqua
				date("d-m",strtotime("+ 1 day",$easter)), //pasquetta 
				date("d-m",strtotime("+ 39 day",$easter)), // Ascensione
				date("d-m",strtotime("- 1 day",$midsummer)), //vigilia midsummer
				date("d-m",$midsummer), //midsummer
				date("d-m",$allSaints), //all saints
			);
		} elseif ($country == 'GB') {
			if ($year == 2020) {
				$mayday = strtotime('2020-05-08 12:00:00');
			} else {
				$mayday = strtotime('next monday', strtotime($year.'-04-30 12:00:00'));
			}
			$lastMonDay = strtotime('previous monday', strtotime($year.'-06-01 12:00:00'));
			$lastAugDay = strtotime('previous monday', strtotime($year.'-09-01 12:00:00'));
			$holidays = Array(
				date("d-m",strtotime("- 2 day",$easter)), //venerdì
				date("d-m",$easter), //pasqua
				date("d-m",strtotime("+ 1 day",$easter)), //pasquetta 
				date("d-m",$mayday), //may day
				date("d-m",$lastMonDay), //spring bank holiday
				date("d-m",$lastAugDay), //late summer
			);
			$dow1 = date('w', strtotime($year.'-01-01'));
			if ($dow1 == 0) {
				$holidays[] = '02-01';
			} elseif ($dow1 == 6) {
				$holidays[] = '03-01';
			}
			// christmas madness!
			$dow2 = date('w', strtotime($year.'-12-25'));
			if ($dow2 == 0 || $dow2 == 6) {
				$holidays[] = '27-12';
			}
			if ($dow2 == 5 || $dow2 == 6) {
				$holidays[] = '28-12';
			}
		} elseif ($country == 'GR') {
			$holidays = Array(
				date("d-m",strtotime("- 48 day",$easter)), //clean monday
				date("d-m",strtotime("- 2 day",$easter)), //venerdì
				date("d-m",$easter), //pasqua
				date("d-m",strtotime("+ 50 day",$easter)), // whit monday
			);
		} elseif ($country == 'HU') {
			$holidays = Array(
				date("d-m",$easter), //pasqua
				date("d-m",strtotime("+ 1 day",$easter)), //pasquetta
				date("d-m",strtotime("+ 50 day",$easter)), // whit monday
			);
		} elseif ($country == 'IE') {
			$mayDay = strtotime('next monday', strtotime($year.'-04-30 12:00:00'));
			$juneDay = strtotime('next monday', strtotime($year.'-05-31 12:00:00'));
			$augustDay = strtotime('next monday', strtotime($year.'-07-31 12:00:00'));
			$octoberDay = strtotime('previous monday', strtotime($year.'-11-01 12:00:00'));
			$holidays = Array(
				date("d-m",$easter), //pasqua
				date("d-m",strtotime("+ 1 day",$easter)), //pasquetta 
				date("d-m",$mayDay), //may day
				date("d-m",$juneDay), //june holiday
				date("d-m",$augustDay), //august holiday
				date("d-m",$octoberDay), //october holiday
			);
		} elseif ($country == 'IT') {
			$holidays = Array(
				date("d-m",$easter), //pasqua
				date("d-m",strtotime("+ 1 day",$easter)), //pasquetta 
			);
		} elseif ($country == 'LT') {
			$mothersDay = strtotime('next sunday', strtotime($year.'-04-30 12:00:00'));
			$fathersDay = strtotime('next sunday', strtotime($year.'-05-31 12:00:00'));
			$holidays = Array(
				date("d-m",$easter), //pasqua
				date("d-m",strtotime("+ 1 day",$easter)), //pasquetta 
				date("d-m",$mothersDay), //mother's day
				date("d-m",$fathersDay), //father's day
			);
		} elseif ($country == 'LV') {
			$holidays = Array(
				date("d-m",strtotime("- 2 day",$easter)), //venerdì
				date("d-m",$easter), //pasqua
				date("d-m",strtotime("+ 1 day",$easter)), //pasquetta 
			);
		} elseif ($country == 'NL') {
			$holidays = Array(
				date("d-m",strtotime("- 2 day",$easter)), //venerdì
				date("d-m",$easter), //pasqua
				date("d-m",strtotime("+ 1 day",$easter)), //pasquetta 
				date("d-m",strtotime("+ 39 day",$easter)), // Ascensione
				date("d-m",strtotime("+ 49 day",$easter)), // Pentecoste
				date("d-m",strtotime("+ 50 day",$easter)), // whit monday
			);
		} elseif ($country == 'NO') {
			$holidays = Array(
				date("d-m",strtotime("- 3 day",$easter)), //giovedi
				date("d-m",strtotime("- 2 day",$easter)), //venerdì
				date("d-m",$easter), //pasqua
				date("d-m",strtotime("+ 1 day",$easter)), //pasquetta 
				date("d-m",strtotime("+ 39 day",$easter)), // Ascensione
				date("d-m",strtotime("+ 49 day",$easter)), // Pentecoste
				date("d-m",strtotime("+ 50 day",$easter)), // whit monday
			);
		} elseif ($country == 'FR') {
			$holidays = Array(
				date("d-m",$easter), //pasqua
				date("d-m",strtotime("+ 1 day",$easter)), //pasquetta
				date("d-m",strtotime("+ 39 day",$easter)), // Ascensione
				date("d-m",strtotime("+ 50 day",$easter)), // whit monday
			);
		} elseif ($country == 'PL') {
			$holidays = Array(
				date("d-m",$easter), //pasqua
				date("d-m",strtotime("+ 1 day",$easter)), //pasquetta
				date("d-m",strtotime("+ 49 day",$easter)), // Pentecoste / Zielone Świątki
				date("d-m",strtotime("+ 60 day",$easter)), // Corpus Christi / Boże ciało
			);
		} elseif ($country == 'PT') {
			$holidays = Array(
				date("d-m",strtotime("- 2 day",$easter)), //venerdì
				date("d-m",$easter), //pasqua
				date("d-m",strtotime("+ 60 day",$easter)), // Corpus Christi / Boże ciało
			);
		} elseif ($country == 'SE') {
			// calc midsommer, saturday between 20 and 26 june
			$midsummer = strtotime('next saturday', strtotime($year.'-06-19 12:00:00'));
			$holidays = Array(
				date("d-m",strtotime("- 2 day",$easter)), //venerdì
				date("d-m",$easter), //pasqua
				date("d-m",strtotime("+ 1 day",$easter)), //pasquetta 
				date("d-m",strtotime("+ 39 day",$easter)), // Ascensione
				date("d-m",strtotime("+ 49 day",$easter)), // Pentecoste
				date("d-m",$midsummer), //midsummer
			);
		} elseif ($country == 'SK') {
			$holidays = Array(
				date("d-m",strtotime("- 2 day",$easter)), //venerdì
				date("d-m",$easter), //pasqua
				date("d-m",strtotime("+ 1 day",$easter)), //pasquetta 
			);
		} elseif ($country == 'US') {
			$holidays = Array(
				date('d-m', strtotime('next monday', strtotime($year.'-01-14 12:00:00'))), // Martin Luther King Jr day
				date('d-m', strtotime('next monday', strtotime($year.'-02-14 12:00:00'))), // Washington's Birthday
				date('d-m', strtotime('last monday', strtotime($year.'-06-01 12:00:00'))), // Memorial Day
				date('d-m', strtotime('next monday', strtotime($year.'-08-31 12:00:00'))), // Labor day
				date('d-m', strtotime('next monday', strtotime($year.'-10-07 12:00:00'))), // Columbus Day
				date('d-m', strtotime('next thursday', strtotime($year.'-11-21 12:00:00'))), // Thanksgiving Day
			);
		} else {
			$holidays = [];
		}
		return $holidays;
	}
	
	/**
	 * Return a list of fixed holidays for the specified year and country (only Italy is supported now)
	 */
	public static function getFixedHolidaysForYear($year, $country = 'IT') {
		return self::$fixedHolidays[$country];
	}
	
	/**
	 * Return a list of holidays for the specified year and country (only Italy is supported now)
	 */
	public static function getHolidaysForYear($year, $country = 'IT') {
		$fixed = self::getFixedHolidaysForYear($year, $country);
		$dyna = self::getDynamicHolidaysForYear($year, $country);
		$holidays = array_merge($fixed, $dyna);
		usort($holidays, function($a, $b) {
			$d1 = intval(substr($a, 0, 2));
			$d2 = intval(substr($b, 0, 2));
			$m1 = intval(substr($a, 3, 2));
			$m2 = intval(substr($b, 3, 2));
			return ($m1 < $m2 ? -1 : ($m1 > $m2 ? +1 : ($d1 < $d2 ? -1 : ($d1 > $d2 ? +1 : 0))));
		});
		return $holidays;
	}

	
	//crmv@104562 source: http://stackoverflow.com/questions/336127/calculate-business-days
	/**
	 * Get the list of holidays in the specified interval. If no interval is provided, use the current year
	 * $from and $to are in Y-m-d format
	 * $return_mode can be: 'jQueryGantt', 'date' or anything else
	 */
	public function getHolidays($from = '', $to = '', $return_mode=null, $country = 'IT') {

		$thisYear = date('Y');
		$holidayDays = $holidays = array();
		
		$fixed = self::getFixedHolidaysForYear($thisYear, $country);
		
		if (empty($from) || empty($to)) {	//crmv@106740
			// current year
			$all = self::getHolidaysForYear($thisYear, $country);
			foreach ($all as $date) {
				if (in_array($date, $fixed)) {
					$holidays[] = array('date' => $date, 'type' => 'fixed', 'year' => $thisYear);
				} else {
					$holidays[] = array('date' => $date, 'type' => 'dynamic','year' => $thisYear);
				}
			}
			
		} elseif ($from < $to) {
		
			$startYear = intval(substr($from,0,4));
			$endYear = intval(substr($to,0,4));
			
			if ($return_mode == 'date') {
				for ($year = $startYear; $year <= $endYear; ++$year) {
					$all = self::getHolidaysForYear($thisYear, $country);
					foreach ($all as $date) {
						list($d, $m) = explode('-', $date);
						$complete = $year.'-'.$m.'-'.$d;
						if ($complete < $from || $complete > $to) continue;
						if (in_array($date, $fixed)) {
							$holidays[] = array('date' => $date, 'type' => 'fixed', 'year' => $year);
						} else {
							$holidays[] = array('date' => $date, 'type' => 'dynamic','year' => $year);
						}
					}
				}
			} else {
				foreach ($fixed as $date) {
					// here ranges are not checked since they don't have the year
					$holidays[] = array('date' => $date, 'type' => 'fixed','year' => $thisYear);
				}
				for ($year = $startYear; $year <= $endYear; ++$year) {
					$dyna = self::getDynamicHolidaysForYear($year, $country);
					foreach ($dyna as $date) {
						list($d, $m) = explode('-', $date);
						$complete = $year.'-'.$m.'-'.$d;
						if ($complete < $from || $complete > $to) continue;
						$holidays[] = array('date' => $date, 'type' => 'dynamic','year' => $year);
					}
				}
				
			}
		}
		
		// now format the output
		foreach ($holidays as $day) {
			list($d, $m) = explode('-', $day['date']);
			switch ($return_mode) {
				case 'jQueryGantt':
					$holidayDays[] = $day['type'] == 'dynamic' ? $day['year']."_{$m}_{$d}" : "{$m}_{$d}";
					break;
				case 'date':
					$holidayDays[] = $day['year']."-{$m}-{$d}";
					break;
				default:
					$holidayDays[] = $day['type'] == 'dynamic' ? $day['year']."-{$m}-{$d}" : "*-{$m}-{$d}";
					break;
			}
		}
		
		// now format them
		return $holidayDays;
	}
	// crmv@123658e

	public function number_of_working_days($from, $to) {
	    $workingDays = array(1, 2, 3, 4, 5); // date format = N (1 = Monday, ...)
	    $holidayDays = $this->getHolidays($from, $to); // ex. array('*-12-25', '*-01-01', '2013-12-23'); // variable and fixed holidays
		
	    $from = new DateTime($from);
	    $to = new DateTime($to);
	    $to->modify('+1 day');
	    $interval = new DateInterval('P1D');
	    $periods = new DatePeriod($from, $interval, $to);
	
	    $days = 0;
	    foreach ($periods as $period) {
	        if (!in_array($period->format('N'), $workingDays)) continue;
	        if (in_array($period->format('Y-m-d'), $holidayDays)) continue;
	        if (in_array($period->format('*-m-d'), $holidayDays)) continue;
	        $days++;
	    }
	    return $days;
	}
	//crmv@104562e
	
}