<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
global $adb,$table_prefix;

$cvid = vtlib_purify($_REQUEST["cvid"]);
$cvmodule = vtlib_purify($_REQUEST["cvmodule"]);
$mode = $_REQUEST["mode"];
$subject = $_REQUEST["subject"];
$body = $_REQUEST["body"];

if($cvid != "")
{
	if($mode == "new")
	{
		$customactionsql = "insert into ".$table_prefix."_customaction(cvid,subject,module,content) values (?,?,?,?)";
		$customactionparams = array($cvid, $subject, $cvmodule, $body);
		$customactionresult = $adb->pquery($customactionsql, $customactionparams);
		if($customactionresult == false)
		{
			include('modules/VteCore/header.php');	//crmv@30447
			$errormessage = "<font color='red'><B>Error Message<ul>
				<li><font color='red'>Error while inserting the record</font>
				</ul></B></font> <br>" ;
			echo $errormessage;

		}

	}elseif($mode == "edit")
	{
		$updatecasql = "update ".$table_prefix."_customaction set subject=?, content=? where cvid=?";
		$updatecaresult = $adb->pquery($updatecasql, array($subject, $body, $cvid));
		if($updatecaresult == false)
		{
			include('modules/VteCore/header.php');	//crmv@30447
			$errormessage = "<font color='red'><B>Error Message<ul>
				<li><font color='red'>Error while inserting the record</font>
				</ul></B></font> <br>" ;
			echo $errormessage;
		}
	}
}
header("Location: index.php?action=index&module=$cvmodule&viewname=$cvid");
?>