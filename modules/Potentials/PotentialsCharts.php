<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
// crmv@53923

require_once('modules/Charts/Charts.php');

class PotentialsCharts extends Charts {
	
	protected $potential = null;
	protected $potentialid = 0;	
	
	function __construct($potentialFocus) {
		parent::__construct();
		
		$this->potential = $potentialFocus;
		$this->potentialid = $potentialFocus->id;
	}
	
	function getChartData($level = 1, $levelIds = array()) { // crmv@146653
		global $adb, $table_prefix, $default_timezone, $default_decimals_num;
		
		$data = array();
		if (!$this->potentialid) return $data;
		
		if ($this->column_fields['chartname'] == 'AmountHistory') {
		
			$res = $adb->pquery("select amountdate, amount from {$table_prefix}_potential_amounts where potentialid = ? order by amountdate", array($this->potentialid));
			if ($res && $adb->num_rows($res) > 0) {
				while ($row = $adb->fetchByAssoc($res, -1, false)) {
					$dt = DateTime::createFromFormat('Y-m-d H:i:s', $row['amountdate'], new DateTimeZone($default_timezone));
					if ($dt) {
						$data['labels'][] = $row['amountdate']; //$dt->getTimestamp();
						$data['values'][] = $row['amount'];
					}
				}
				$data['limited'] = false;
				$data['timestamp'] = true;
			}
		
		} elseif ($this->column_fields['chartname'] == 'ProductLines') {
			$lines = $this->potential->prodLineInfo;
			if ($lines && is_array($lines['list'])) {
				foreach ($lines['list'] as $lineinfo) {
					$data['labels'][] = $lineinfo['linename'];
					$data['values'][] = round($lineinfo['total'], $default_decimals_num);
				}
			}
			$data['limited'] = false;
			$data['timestamp'] = false;
		}
		
		return $data;
	}
	
	function generateFileName($format = 'png') {

		if (!is_dir($this->cachedir)) {
			@mkdir($this->cachedir, 0755, true);
		}

		if (!is_writable($this->cachedir)) {
			//throw new Exception("Directory $basedir is not writable.");
			return null;
		}
		
		$chartid = 0;
		if ($this->column_fields['chartname'] == 'AmountHistory') {
			$chartid = 1;
		} elseif ($this->column_fields['chartname'] == 'ProductLines') {
			$chartid = 2;
		}

		return $this->cachedir."chart_pot_{$this->potentialid}_$chartid.$format";
	}
}