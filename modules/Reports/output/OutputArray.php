<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@96742 */

require_once('modules/Reports/output/OutputBase.php');

/**
 *
 */
class ReportOutputArray extends ReportOutputBase {

	public function getSimpleHeaderArray() {
		$out = array();
		foreach ($this->header as $cell) {
			if ($cell['column']) {
				$out[$cell['column']] = $cell['label'];
			} else {
				$out[] = $cell['label'];
			}
		}
		return $out;
	}
	
	public function getSimpleDataArray() {
		$out = array();
		foreach ($this->data as $row) {
			$orow = array();
			foreach ($row as $cell) {
				if ($cell['column']) {
					$orow[$cell['column']] = $cell['value'];
				} else {
					$orow[] = $cell['value'];
				}
			}
			$out[] = $orow;
		}
		return $out;
	}
	
}