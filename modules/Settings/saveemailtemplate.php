<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@22700 crmv@55418 crmv@80155 */

global $log;
global $adb, $table_prefix, $current_user;
$folderName = vtlib_purify($_REQUEST["foldername"]);
$templateName = from_html($_REQUEST["templatename"]);
$templateid = vtlib_purify($_REQUEST["templateid"]);
$description = from_html($_REQUEST["description"]);
$subject = from_html($_REQUEST["subject"]);
$body = fck_from_html($_REQUEST["body"]);
$templatetype = vtlib_purify($_REQUEST["templatetype"]);
$use_signature = (isset($_REQUEST["use_signature"]) && $_REQUEST["use_signature"] == 'on') ? 1 : 0;
$overwrite_message = (isset($_REQUEST["overwrite_message"]) && $_REQUEST["overwrite_message"] == 'on') ? 1 : 0;
if ($templatetype != 'Email') {
	$use_signature = 0;
	$overwrite_message = 1;
}
$res = $adb->query("select * from ".$table_prefix."_field where fieldname = 'bu_mc'");
$bu_mc_enabled = ($res && $adb->num_rows($res) > 0);

if(isset($templateid) && $templateid !='')
{
	$log->info("the templateid is set");
	if ($bu_mc_enabled) {
		$res = $adb->pquery("select bu_mc from ".$table_prefix."_emailtemplates where templateid = ?",array($templateid));
		$bu_mc_old = $adb->query_result($res,0,'bu_mc');
		$bu_mc_old = explode(' |##| ',$bu_mc_old);
		$bu_mc_user = explode(' |##| ', $current_user->column_fields['bu_mc']);
		$bu_mc_selected = vtlib_purify($_REQUEST["bu_mc"]);
		$bu_mc = array_filter(array_merge(array_diff($bu_mc_old, $bu_mc_user), (array)$bu_mc_selected));
		$bu_mc = implode(' |##| ',$bu_mc);
		$sql = "update ".$table_prefix."_emailtemplates set foldername =?, templatename =?, subject =?, description =".$adb->getEmptyClob(true).", body =".$adb->getEmptyClob(true).", templatetype =?, use_signature=?, overwrite_message=?, bu_mc=? where templateid =?";
		$params = array($folderName, $templateName, $subject, $templatetype, $use_signature, $overwrite_message, $bu_mc, $templateid);
	} else {
		$sql = "update ".$table_prefix."_emailtemplates set foldername =?, templatename =?, subject =?, description =".$adb->getEmptyClob(true).", body =".$adb->getEmptyClob(true).", templatetype =?, use_signature=?, overwrite_message=? where templateid =?";
		$params = array($folderName, $templateName, $subject, $templatetype, $use_signature, $overwrite_message, $templateid);
	}
	$adb->pquery($sql, $params);
 	$result = $adb->updateClob($table_prefix.'_emailtemplates','description',"templateid=$templateid",$description);
	$result = $adb->updateClob($table_prefix.'_emailtemplates','body',"templateid=$templateid",$body);
	$log->info("about to invoke the detailviewemailtemplate file");
}
else
{
	$templateid = $adb->getUniqueID($table_prefix.'_emailtemplates');
	if ($bu_mc_enabled) {
		$bu_mc = vtlib_purify($_REQUEST["bu_mc"]);
		if (!empty($bu_mc)) $bu_mc = implode(' |##| ',$bu_mc);
		$sql = "insert into ".$table_prefix."_emailtemplates (foldername,templatename,subject,description,deleted,templateid,templatetype,use_signature,overwrite_message,bu_mc) values (?,?,?,".$adb->getEmptyClob(true).",?,?,?,?,?,?)";
		$params = array($folderName, $templateName, $subject, 0, $templateid, $templatetype, $use_signature, $overwrite_message, $bu_mc);
	} else {
		$sql = "insert into ".$table_prefix."_emailtemplates (foldername,templatename,subject,description,deleted,templateid,templatetype,use_signature,overwrite_message) values (?,?,?,".$adb->getEmptyClob(true).",?,?,?,?,?)";
		$params = array($folderName, $templateName, $subject, 0, $templateid, $templatetype, $use_signature, $overwrite_message);
	}
	$adb->pquery($sql, $params);
	$result = $adb->updateClob($table_prefix.'_emailtemplates','description',"templateid=$templateid",$description);
	$result = $adb->updateClob($table_prefix.'_emailtemplates','body',"templateid=$templateid",$body);
	$log->info("added to the db the emailtemplate");
}
if ($_REQUEST['quick_create'] == 'true') {
	echo Zend_Json::encode(array('templateid'=>$templateid,'templatename'=>$templateName));die;
} else {
	header("Location:index.php?module=Settings&action=detailviewemailtemplate&parenttab=Settings&templateid=".$templateid);
}
?>