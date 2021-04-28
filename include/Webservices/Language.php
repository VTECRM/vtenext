<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
//crmv@2390m
function vte_get_labels($username, $language, $module){
	
	global $default_language,$adb;
	$user = CRMEntity::getInstance('Users');
	$userId = $user->retrieve_user_id($username);
	$user = $user->retrieveCurrentUserInfoFromFile($userId);
	
	if($user->default_language != '' && $language == ''){
		$language = $user->default_language;
	}
	
	$query = "SELECT label,trans_label 
				FROM sdk_language 
				WHERE language = ? ";
	if($module != ''){
		$query .= " AND module = '$module' ";
	}
	$res = $adb->pquery($query, array($language));
	
	$labels = Array();
	
	while($row = $adb->fetchByAssoc($res)){
		$labels[$row["label"]] = $row["trans_label"];
	}
	VTWS_PreserveGlobal::flush();
	
	return $labels;
}

function vte_get_langs($language='it_it'){
	global $adb, $table_prefix;
	
	$query = "SELECT prefix,label 
				FROM {$table_prefix}_language ";
	
	$res = $adb->query($query);
	
	$langs = Array();
	while($row = $adb->fetchByAssoc($res)){
		$langs[$row["prefix"]] = $row["label"];
	}
	VTWS_PreserveGlobal::flush();
	
	return $langs;
}
//crmv@2390me
?>