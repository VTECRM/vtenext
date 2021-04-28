<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@sdk-25183	crmv@25671	crmv@37463	crmv@40799 */

$tmp_action = '';
$tmp_module = $_REQUEST['module'];
if (isModuleInstalled('SDK')) {
	$tmp_action = SDK::getFile($tmp_module,strip_tags($_REQUEST['file']));//crmv@208173
}
if ($tmp_action == '') {
	$tmp_action = strip_tags($_REQUEST['file']);//crmv@208173
}
$tmp_action = str_replace('..', '', $tmp_action);

$is_action = false;
$in_dir = @scandir($root_directory.'modules/'.$tmp_module);
$in_dir = is_array($in_dir) ? $in_dir : array();
$temp_arr = Array("CVS","Attic");
$res_arr = @array_intersect($in_dir,$temp_arr);
if(count($res_arr) == 0 && !preg_match("/[\/.]/",$tmp_module)) {
	if(@in_array($tmp_action.".php",$in_dir))
		$is_action = true;
}
if(!$is_action) {
	$in_dir = @scandir($root_directory.'modules/VteCore');
	$in_dir = is_array($in_dir) ? $in_dir : array();
	$res_arr = @array_intersect($in_dir,$temp_arr);
	if(count($res_arr) == 0 && !preg_match("/[\/.]/",'VteCore')) {
		if(@in_array($tmp_action.".php",$in_dir)) {
			$tmp_module = 'VteCore';
		}
	}
}

checkFileAccess('modules/'.$tmp_module.'/'.$tmp_action.'.php');
require_once('modules/'.$tmp_module.'/'.$tmp_action.'.php');
?>