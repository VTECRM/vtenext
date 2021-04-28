<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
$fieldname = $_REQUEST["field"];
$id = $_REQUEST["id"];
$session_name_global = "global_search_cache_$fieldname";
$value = str_replace("'","\'",$_REQUEST["value"]);
$found = false;
if (!VteSession::hasKey($session_name_global)){
	require_once('plugin_'.$_REQUEST["field"].'.php');
}
foreach (VteSession::get($session_name_global) as $key=>$arr){
	if ($arr[$fieldname] == $id){
		if ($arr['calltype'] == utf8_encode($value) || $arr['calltype'] == $value) {
			$found = true;
			break;
		}
	}
}
if ($found) echo ":#:SUCCESS";
else echo ":#:FAILURE";
?>