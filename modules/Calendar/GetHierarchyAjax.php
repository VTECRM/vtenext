<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

require_once('modules/Calendar/Calendar.php');
require_once('modules/Calendar/Activity.php');

function GetCalControllante($id) {
	global $adb;
	$activity = CRMEntity::getInstance('Activity');
	if($id != "") {
		$result = $adb->limitQuery($activity->get_fathers($id,9,9,false,true),0,1);
		if($result) {
			if($row = $adb->fetchByAssoc($result)) {
				return $row;
			} else return null;
		} else return null;
	} else return null;
}

function GetCalControllati($id) {
	$activity = CRMEntity::getInstance('Activity');
	if($id != "") {
		global $adb;
		$result = $adb->query($activity->get_children($id,9,9,false,true));
		if($result) {
			$retval = Array();
			while($row = $adb->fetchByAssoc($result)) {
				$retval[] = $row;
			} 
			return $retval;
		} else return null;
	} else return null;							
}

function GetCalHierarchy($id) {
	global $app_strings,$mod_strings,$adb,$current_user,$table_prefix;
	
	$controllante = GetCalControllante($id);
	if($controllante) {
		if (!is_admin($current_user) && getUserId($controllante['crmid']) != $current_user->id && $controllante['visibility'] == 'Private')	//crmv@17001 : Private Permissions
			$detail_url = getTranslatedString('Private Event','Calendar');
		else
			$detail_url = "<a href=\"index.php?module=Calendar&action=DetailView&record=".$controllante['crmid']."&parenttab=Sales\"> ".$controllante['subject']."</a>";
		$html  = "<ul class=\"uil\"><li>$detail_url</li>" ; 
	} else {
		$html  = "<ul class=\"uil\"><li>".$app_strings['LBL_NO_M']." ".$mod_strings['Fathers']."</li>" ;
	}
	
	$res = $adb->query("select subject from ".$table_prefix."_activity where activityid = $id");
	$html .= "<ul class=\"uil\"><li>".$adb->query_result($res,0,'subject')."<ul>";
	
	$controllati = GetCalControllati($id);
	for($i=0;$i<count($controllati);$i++) {
		if (!is_admin($current_user) && getUserId($controllati[$i]['crmid']) != $current_user->id && $controllati[$i]['visibility'] == 'Private') //crmv@17001 : Private Permissions
			$detail_url = getTranslatedString('Private Event','Calendar');
		else
			$detail_url = "<a href=\"index.php?module=Calendar&action=DetailView&record=".$controllati[$i]['crmid']."&parenttab=Sales\"> ".$controllati[$i]['subject']."</a>"; 
		$html .= "<li>$detail_url</li>";
	}
	
	$html .= "</ul></ul></ul>";
	return $html;
}

global $adb;
if(isset($_REQUEST['id']) && $_REQUEST['id'] != "" ) {
	echo GetCalHierarchy($_REQUEST['id']);
} else {
	echo "<font color=red><b>Errore inaspettato nella creazione del grafo!</b></font>";
}
die();
//crmv@17001e
?>