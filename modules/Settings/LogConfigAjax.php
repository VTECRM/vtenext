<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@173186 */
$ajaxaction = $_REQUEST["ajxaction"];
if($ajaxaction == "TOGGLELOGPROP")
{
	$logConfId = vtlib_purify($_REQUEST['log']);
	$logUtils = LogUtils::getInstance();
	$logUtils->toggleLogProp($logConfId);
	echo 'SUCCESS';
} elseif($ajaxaction == "SAVEGLOBALCONFIG")
{
	$logUtils = LogUtils::getInstance();
	$logUtils->setGlobalConfig(vtlib_purify($_REQUEST["prop"]), vtlib_purify($_REQUEST["value"]));
	echo 'SUCCESS';
}
exit;