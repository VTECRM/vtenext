<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@128159 */
class UitypeTimeUtils extends SDKExtendableUniqueClass {
	
	var $format = 'H:i';
	
	function time2Seconds($value) {
		if (empty($value))
			return 0;
		elseif (is_numeric($value))
			return $value;
		//crmv@206919
		else{
			$time = strtotime("1970-01-01 $value UTC");
			if ($time === false) {
				return $value;
			} else {
				return $time;
			}
		}
		//crmv@206919e
	}
	
	function seconds2Time($value) {
		if (!empty($value) && is_numeric($value))
			return gmdate($this->format, $value);
		else
			return '';
	}
}