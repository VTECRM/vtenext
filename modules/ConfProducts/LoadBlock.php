<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
 
/* crmv@198024 */


$output = ['success' => false];

$forModule = $_REQUEST['formodule'];
$forField = $_REQUEST['forfield'];
$confid = intval($_REQUEST['confproductid']);

if ($forModule == 'Products' && $forField = 'confproductid' && isPermitted($forModule, 'EditView') == 'yes') {
	if ($confid > 0 && vtlib_isModuleActive('ConfProducts') && isPermitted('ConfProducts', 'DetailView', $confid) == 'yes') {
		$focus = CRMEntity::getInstance('ConfProducts');
		
		// TODO
		$html = $focus->getHtmlBlock($forModule, $forField, $confid);
				
		$output['success'] = true;
		$output['html'] = $html;
	} else {
		$output['error'] = 'Not Permitted';
	}
} else {
	$output['error'] = 'Not Permitted';
}

echo Zend_Json::encode($output);
die();