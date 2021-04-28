<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
	global $table_prefix;
	// crmv@30967
	$sql="select foldername,folderid from ".$table_prefix."_crmentityfolder where tabid = ? order by foldername";
	$res=$adb->pquery($sql,array(getTabId('Documents')));
	// crmv@30967e
	for($i=0;$i<$adb->num_rows($res);$i++) {
		$fid=$adb->query_result($res,$i,"folderid");
		$fname=$adb->query_result($res,$i,"foldername");
		$fieldvalue[]=$fid."@".$fname;
	}
    echo implode("###",$fieldvalue);
?>