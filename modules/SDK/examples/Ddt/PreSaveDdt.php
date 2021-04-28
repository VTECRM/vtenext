<?php 
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
if ($type == 'MassEditSave') {
	$status = true;
	$message = '';
} else {
	if (in_array($values['salesorderid'],array('',0))) {
		$confirm = true;
		$message = 'Ordine di Vendita è vuoto! Vuoi procedere comunque?';
	}
} 
?>