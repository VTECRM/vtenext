<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@42266 */

/*
 * Some utilities for currency conversions
 * TODO: move here stuff from utils.php
 */
require_once 'include/BaseClasses.php';
require_once 'modules/Utilities/Currencies.php';


class CurrencyUtils extends SDKExtendableUniqueClass {

	public $currencyFetchers = array('Yahoo', 'WSX', 'RE');

	protected $masterCurrencyInfo;
	protected $currencyCache;

	// from and to are the standard currency names (eg: EUR, USD...)
	function getExchangeRatio($from, $to) {
		if (empty($from) || empty($to)) return 0.0;

		// use all the available currency fetchers
		foreach ($this->currencyFetchers as $cf) {
			$className = 'CurrencyFetcher'.$cf;
			if (class_exists($className)) {
				$fetcherInst = new $className();
				$ratio = $fetcherInst->getSingleRatio($from, $to);
				if (!empty($ratio)) return $ratio;
			}
		}
		return 0.0;
	}

	public function populateExchangeTable() {
		global $adb, $table_prefix;
	}

	public function getMasterCurrencyCode() {
		if (empty($this->masterCurrencyInfo['currency_code'])) {
			$this->getMasterCurrencyInfo();
		}
		return $this->masterCurrencyInfo['currency_code'];
	}

	public function getMasterCurrencyId() {
		if (empty($this->masterCurrencyInfo['id'])) {
			$this->getMasterCurrencyInfo();
		}
		return $this->masterCurrencyInfo['id'];
	}

	protected function getMasterCurrencyInfo() {
		global $adb, $table_prefix;
		if (empty($this->masterCurrencyInfo)) {
			$res = $adb->pquery("select * from {$table_prefix}_currency_info where deleted = 0 AND (defaultid = -11 OR id = 1)");
			if ($adb->num_rows($res) > 0) {
				$this->masterCurrencyInfo = $adb->FetchByAssoc($res, -1, false);
			}
		}
		return $this->masterCurrencyInfo;
	}

	public function getCurrencyInfo($currencyId) {
		global $adb, $table_prefix;
		if (empty($this->currencyCache[$currencyId])) {
			$res = $adb->pquery("select * from {$table_prefix}_currencies where currencyid = ?", array($currencyId));
			$this->currencyCache[$currencyId] = $adb->FetchByAssoc($res, -1, false);
		}
		return $this->currencyCache[$currencyId];
	}

	public function getCurrencyInfoFromName($currencyName) {
		global $adb, $table_prefix;
		$res = $adb->pquery("select * from {$table_prefix}_currencies where currency_name = ?", array($currencyName));
		$row = $adb->FetchByAssoc($res, -1, false);
		return $row;
	}

}


// Classes to retrieve currencies ratios

abstract class CurrencyFetcher {

	public $timeout = 10;
	public $precision = 3;
	public $multiple = false;

	protected $baseUrl;

	abstract protected function buildUrl($from, $to);
	abstract protected function extractRatio($data);

	public function getSingleRatio($from, $to) {
		$url = $this->buildUrl($from, $to);
		$data = $this->fetchData($url);
		return $this->extractRatio($data);
	}

	protected function fetchData($url) {
		$ctx = stream_context_create(array(
			'http' => array(
				'timeout' => $this->timeout
			)
		));
		$data = @file_get_contents($url, false, $ctx);
		return $data;
	}
}

abstract class CurrencyFetcherMulti extends CurrencyFetcher {
	public $multiple = true;

	abstract public function getMultipleRatios($from, $to);
	abstract protected function extractRatios($data);

}

class CurrencyFetcherYahoo extends CurrencyFetcherMulti {

	protected $baseUrl = 'http://download.finance.yahoo.com/d/quotes.csv?f=l1&e=.csv';


	public function getMultipleRatios($from, $to) {
		if (!is_array($to)) $to = array($to);
		$url = $this->buildUrl($from, $to);
		$data = $this->fetchData($url);
		$ratios = $this->extractRatios($data);
		$ret = array();
		foreach ($to as $i=>$cto) {
			if ($ratios[$i] > 0) $ret[$cto] = $ratios[$i];
		}
		return $ret;
	}

	protected function extractRatio($data) {
		return round(floatval($data), $this->precision);
	}

	protected function extractRatios($data) {
		$ret = array();
		$data = preg_split('/\s/', $data, -1, PREG_SPLIT_NO_EMPTY);
		if (is_array($data)) {
			foreach ($data as $ratio) {
				$ret[] = round(floatval($ratio), $this->precision);
			}
		}
		return $ret;
	}

	protected function buildUrl($from, $to) {
		if (!is_array($to)) $to = array($to);
		$url = $this->baseUrl;
		foreach ($to as $cto) {
			$url .= '&s='.strtoupper($from.$cto)."=X";
		}
		return $url;
	}

}

class CurrencyFetcherWSX extends CurrencyFetcher {

	protected $baseUrl = 'http://www.webservicex.net/CurrencyConvertor.asmx/ConversionRate?';

	protected function extractRatio($data) {
		return round(floatval(strip_tags($data)), $this->precision);
	}

	protected function buildUrl($from, $to) {
		$url = $this->baseUrl;
		$url .= '&FromCurrency='.strtoupper($from).'&ToCurrency='.strtoupper($to);
		return $url;
	}

}

class CurrencyFetcherRE extends CurrencyFetcher {

	protected $baseUrl = 'http://rate-exchange.appspot.com/currency?';

	protected function extractRatio($data) {
		$data = zend_json::decode($data);
		if (is_array($data))
			return round(floatval($data['rate']), $this->precision);
		else
			return 0;
	}

	protected function buildUrl($from, $to) {
		$url = $this->baseUrl;
		$url .= '&from='.strtoupper($from).'&to='.strtoupper($to);
		return $url;
	}

}


//class CurrencyFetcherGoogle implements CurrencyFetcher {
// discontinued in november 2013 : not implemented
//http://www.google.com/ig/calculator?q=1EUR=?USD
//}


// not implemented: only from euro
/*class CurrencyFetcherBce implements CurrencyFetcher {
 }*/
