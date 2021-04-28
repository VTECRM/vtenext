<?php 
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
if ($type == 'MassEditSave') {
	$status = true;
	$message = '';
} else {
	if ($values['description'] == '') {
		$status = false;
		$message = 'Il campo Descrizione è vuoto!';
		$focus = 'description';
		$changes['description'] = 'Descrizione di default.';
	}
} 
?>