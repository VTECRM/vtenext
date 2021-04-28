<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

require_once('modules/Portal/Portal.php');

global $default_charset,$adb;
global $table_prefix;
$conv_pname = function_exists('iconv') ? @iconv("UTF-8",$default_charset, $_REQUEST['portalname']) : $_REQUEST['portalname']; // crmv@167702
$conv_purl = function_exists('iconv') ? @iconv("UTF-8",$default_charset, $_REQUEST['portalurl']) : $_REQUEST['portalurl']; // crmv@167702
$portlurl =str_replace(array("'",'"'),'',$conv_purl);
$portlname = from_html($conv_pname);
$portlurl = from_html($portlurl);

// crmv@173029
$portlurl=str_replace("#$#$#","&",$portlurl);
if(!empty($portlurl)){
	$scheme = parse_url($portlurl, PHP_URL_SCHEME);
	if(empty($scheme)) $portlurl = "http://".$portlurl;
}
// crmv@173029e

//added as an enhancement to set default value
if(isset($_REQUEST['check']) && $_REQUEST['check'] =='true')
{
	$updateDefalt ="UPDATE ".$table_prefix."_portal SET setdefault=1 WHERE portalid=?";
	$set_def = $adb->pquery($updateDefalt, array($_REQUEST['passing_var']));
	$updateZero = "UPDATE ".$table_prefix."_portal SET setdefault=0 WHERE portalid not in(?)";
	$set_default= $adb->pquery($updateZero, array($_REQUEST['passing_var']));
	exit();
}	
if($portlname != '' && $portlurl != '')
{
	if(isset($_REQUEST['record']) && $_REQUEST['record'] > 0) //crmv@121663
	{
		$result=UpdatePortal($portlname,$portlurl,$_REQUEST['record']); // crmv@173029
	}
	else
	{
		$result=SavePortal($portlname,$portlurl); // crmv@173029
	}
	header("Location: index.php?action=PortalAjax&module=Portal&file=ListView&mode=ajax&datamode=manage");
}else
{
	echo ":#:FAILURE";
}
?>