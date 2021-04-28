<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

global $adb;
global $default_charset;
global $table_prefix;
$local_log =& LoggerManager::getLogger('index');
$focus = new Reports();

$rfid = vtlib_purify($_REQUEST['record']);
$mode = vtlib_purify($_REQUEST['savemode']);
$foldername = vtlib_purify($_REQUEST["foldername"]);
$foldername = function_exists('iconv') ? @iconv("UTF-8",$default_charset, $foldername) : $foldername; // crmv@167702
$folderdesc = vtlib_purify($_REQUEST["folderdesc"]);
$foldername = str_replace('*amp*','&',$foldername);
$folderdesc = str_replace('*amp*','&',$folderdesc);

if($mode=="Save")
{
	if($rfid=="")
	{
		// crmv@30967
		$result = addEntityFolder('Reports', trim($foldername), $fldrdescription, $current_user->id, 'CUSTOMIZED');
		// crmv@30967e
		if($result!=false)
		{
			header("Location: index.php?action=ReportsAjax&file=ListView&mode=ajax&module=Reports");
		}else
		{
			include('modules/VteCore/header.php');	//crmv@30447

			$errormessage = "<font color='red'><B>Error Message<ul>
			<li><font color='red'>Error while inserting the record</font>
			</ul></B></font> <br>" ;
			echo $errormessage;
		}
	}
}elseif($mode=="Edit")
{
	if($rfid != "")
	{
		// crmv@30967
		$result = editEntityFolder($rfid, trim($foldername), $folderdesc);
		// crmv@30967e
		if($result!=false)
		{
			header("Location: index.php?action=ReportsAjax&file=ListView&mode=ajax&module=Reports");
		}else
		{
			include('modules/VteCore/header.php');	//crmv@30447
			$errormessage = "<font color='red'><B>Error Message<ul>
			<li><font color='red'>Error while updating the record</font>
			</ul></B></font> <br>" ;
			echo $errormessage;
		}
	}
}

?>