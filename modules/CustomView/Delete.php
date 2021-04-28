<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@115445 crmv@150024 */

$cvid = intval($_REQUEST["record"]);
$module = vtlib_purify($_REQUEST["dmodule"]);
$smodule = vtlib_purify($_REQUEST["smodule"]);
$parenttab = getParentTab();
(!empty($_REQUEST['return_action'])) ? $return_action = vtlib_purify($_REQUEST['return_action']) : $return_action = 'ListView';

if ($cvid > 0) {
	$customview = CRMEntity::getInstance('CustomView');
	$customview->trash($module, $cvid);
}

if(isset($smodule) && $smodule != '') {
	$smodule_url = "&smodule=".$smodule;
}

header("Location: index.php?action=$return_action&parenttab=$parenttab&module=$module".$smodule_url);