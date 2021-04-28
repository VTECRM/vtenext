<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
$piva = $_REQUEST['piva'];

if(ControlloPIVA($piva))
	echo 'true';
else
	echo 'false';

function ControlloPIVA($pi) {
	if( $pi == '' )
		return false;
	if( strlen($pi) != 11 )
		return false;
	if( ! preg_match('/^[0-9]+$/', $pi) ) // crmv@146653
		return false;
	$s = 0;
	for( $i = 0; $i <= 9; $i += 2 )
		$s += ord($pi[$i]) - ord('0');
	for( $i = 1; $i <= 9; $i += 2 ){
		$c = 2*( ord($pi[$i]) - ord('0') );
		if( $c > 9 )
			$c = $c - 9;
		$s += $c;
	}
	if( ( 10 - $s%10 )%10 != ord($pi[10]) - ord('0') )
		return false;
	return true;
}
?>