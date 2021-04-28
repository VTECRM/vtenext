<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
require_once('include/Zend/Json.php');
global $adb, $current_user,$table_prefix;
$mode = vtlib_purify($_REQUEST['mode']);
$search = vtlib_purify($_REQUEST['term']);
$idlist = vtlib_purify($_REQUEST['idlist']);
if ($idlist != '') {
	$idlist = array_filter(explode('|',$idlist));
}

$return = array();
if ($mode == 'Users') {
	$query = 'select id, user_name, first_name, last_name, avatar from '.$table_prefix.'_users where status = ? and (user_name like ? or first_name like ? or last_name like ?)';
	$params = array('Active',"%$search%","%$search%","%$search%");
	$query .= ' and id <> ?';
	$params[] = $current_user->id;
	if (!empty($idlist)) {
		$query .= ' and id not in ('.generateQuestionMarks($idlist).')';
		$params[] = $idlist;
	}
	$result = $adb->pquery($query,$params);
	if ($result && $adb->num_rows($result) > 0) {
		while($row=$adb->fetchByAssoc($result)) {
			$avatar = $row['avatar'];
			if ($avatar == '') {
				$avatar = getDefaultUserAvatar();
			}
			$full_name = trim($row['first_name'].' '.$row['last_name']);
			$return[] = array('value'=>$row['id'],'label'=>$row['user_name'].' ('.$full_name.')','user_name'=>$row['user_name'],'full_name'=>$full_name,'img'=>$avatar);
		}
	}
}

echo Zend_Json::encode($return);
exit;
?>