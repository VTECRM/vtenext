<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
//crmv@18199

function vtOwnerFieldJson($adb){
	global $current_user,$table_prefix;
	$picklistValues = array();
	
	$res = $adb->query('SELECT id,user_name,first_name,last_name FROM '.$table_prefix.'_users ORDER BY user_name');
	while($row = $adb->fetchByAssoc($res)) {
		$picklistValues[] = array('label'=>'('.getTranslatedString('LBL_USER').') '.$row['user_name'].' ('.$row['first_name'].' '.$row['last_name'].')','value'=>$row['id']);
	}
	$res = $adb->query('SELECT groupid,groupname FROM '.$table_prefix.'_groups ORDER BY groupname');
	while($row = $adb->fetchByAssoc($res)) {
		$picklistValues[] = array('label'=>'('.getTranslatedString('LBL_GROUP').') '.$row['groupname'],'value'=>$row['groupid']);
	}
	echo Zend_Json::encode(array('picklistValues'=>$picklistValues));
}

vtOwnerFieldJson($adb);
//crmv@18199e
?>