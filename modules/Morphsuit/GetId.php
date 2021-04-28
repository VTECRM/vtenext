<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@54179 */
include_once('../../config.inc.php');
chdir($root_directory);
require_once('include/utils/utils.php');
$saved_morphsuit = getSavedMorphsuit();
if (!empty($saved_morphsuit)) {
	$saved_morphsuit = urldecode(trim($saved_morphsuit));
	$private_key = substr($saved_morphsuit,0,strpos($saved_morphsuit,'-----'));
	$enc_text = substr($saved_morphsuit,strpos($saved_morphsuit,'-----')+5);
	$saved_morphsuit = @decrypt_morphsuit($private_key,$enc_text);
	$saved_morphsuit = Zend_Json::decode($saved_morphsuit);
	//crmv@182677
	setCacheMorphsuitInfo($saved_morphsuit);
	echo $saved_morphsuit['id'];
	//crmv@182677e
}
if ($_REQUEST['resetTime2check'] == 'yes') {
	global $adb, $table_prefix;
	$adb->pquery("delete from {$table_prefix}_time2check where cwhat = ?",array('trackVTEInfo'));
	$adb->pquery("insert into {$table_prefix}_time2check (cwhat,cwhen) values (?,?)",array('trackVTEInfo',time()+864000));	//10 days
}
exit;