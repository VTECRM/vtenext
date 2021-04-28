<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@49432 */
function format_flags($v) {
	if (substr($v,0,1) == '\\')
		$v = '\\'.ucfirst(strtolower(substr($v,1)));
	elseif (substr($v,0,1) == '$')
		$v = '$'.ucfirst(strtolower(substr($v,1)));
	else
		$v = ucfirst(strtolower($v));
	return $v;
}
?>