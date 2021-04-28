<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@103534 */
$gateway_list = array();
foreach($structure['shapes'] as $elid => $info) {
	if ($elid != $elementid && strpos($info['type'],'Gateway') !== false) {
		if (empty($info['text'])) $info['text'] = $elid;
		$gateway_list[$elid] = $PMUtils->getElementTitle($info);
	}
}
$smarty->assign("GATEWAY_LIST", $gateway_list);