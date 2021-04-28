<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

$viewid = vtlib_purify($_REQUEST['viewname']);
$returnmodule = vtlib_purify($_REQUEST['return_module']);
$return_action = vtlib_purify($_REQUEST['return_action']);
$idlist = implode(';',getListViewCheck($returnmodule));	//crmv@27096
$rstart='';
//Added to fix 4600
$url = getBasic_Advance_SearchURL();

//split the string and store in an array
$storearray = explode(";",$idlist);
array_filter($storearray);
$ids_list = array();
$errormsg = '';
foreach($storearray as $id) {
	//crmv@fix empty id
	if ($id) {
        if(isPermitted($returnmodule,'Delete',$id) == 'yes') {
			$focus = CRMEntity::getInstance($returnmodule);
			DeleteEntity($returnmodule,$returnmodule,$focus,$id,'');
        } else {
        	$ids_list[] = $id;
        }
	} 
	//crmv@fix empty id end  
}
if(count($ids_list) > 0) {
	$ret = getEntityName($returnmodule,$ids_list);
	if(count($ret) > 0) {
		$errormsg = implode(',',$ret);
	}
}

if(isset($_REQUEST['smodule']) && ($_REQUEST['smodule']!='')) {
	$smod = "&smodule=".vtlib_purify($_REQUEST['smodule']);
}
if(isset($_REQUEST['start']) && ($_REQUEST['start']!='')) {
	$rstart = "&start=".vtlib_purify($_REQUEST['start']);
}
//crmv@2963m
if ($returnmodule == 'Messages') {
	$url .= '&account='.$_REQUEST['account'].'&folder='.$_REQUEST['folder'].'&thread='.$_REQUEST['thread'];
	$rstart .= '&load_all=true';	//crmv@48307
	header("Location: index.php?module=".$returnmodule."&action=".$returnmodule."Ajax&ajax=true".$rstart."&file=ListView".$url);
} else
//crmv@2963me
if($return_action == 'ActivityAjax') {
	$subtab = vtlib_purify($_REQUEST['subtab']);
	header("Location: index.php?module=".$returnmodule."&action=".$return_action."".$rstart."&view=".vtlib_purify($_REQUEST['view'])."&hour=".vtlib_purify($_REQUEST['hour'])."&day=".vtlib_purify($_REQUEST['day'])."&month=".vtlib_purify($_REQUEST['month'])."&year=".vtlib_purify($_REQUEST['year'])."&type=".vtlib_purify($_REQUEST['type'])."&viewOption=".vtlib_purify($_REQUEST['viewOption'])."&view_filter=".vtlib_purify($_REQUEST['view_filter'])."&subtab=".$subtab.$url);
} elseif($returnmodule != 'Faq') {
	header("Location: index.php?module=".$returnmodule."&action=".$returnmodule."Ajax&ajax=delete".$rstart."&file=ListView&viewname=".$viewid."&errormsg=".$errormsg.$url);
} else {
	header("Location: index.php?module=".$returnmodule."&action=".$returnmodule."Ajax&ajax=delete".$rstart."&file=ListView&errormsg=".$errormsg.$url);
}
?>