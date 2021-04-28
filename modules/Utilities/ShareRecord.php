<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@43147 */

include('config.inc.php');

// webservices
require_once 'include/Webservices/Utils.php';
require_once("include/Webservices/VtenextCRMObject.php");//crmv@207871
require_once("include/Webservices/VtenextCRMObjectMeta.php");//crmv@207871
require_once("include/Webservices/DataTransform.php");
require_once("include/Webservices/WebServiceError.php");
require_once('include/Webservices/ModuleTypes.php');
require_once("include/Webservices/Retrieve.php");
require_once("include/Webservices/DescribeObject.php");


global $current_user, $theme, $current_language;
global $adb, $table_prefix;

// initialize some variables

$theme = $default_theme;
$current_language = $default_language;

$token = substr(trim($_REQUEST['sharetoken']), 0, 82);

$CU = CRMVUtils::getInstance();
$valid = $CU->validateShareToken($token);

$smarty = new VteSmarty();
$smarty->assign("SHARETOKEN", $token);
$smarty->assign("THEME", $theme);
$smarty->assign('CURRENT_LANGUAGE',$current_language);


if (empty($valid) || empty($valid['userid'])) {
	$smarty->assign('BODY',"<p>".getTranslatedString('LBL_INVALID_URL')."</p>");
	$smarty->display('NoLoginMsg.tpl');
	die();
}

// we have a valid token

// impersonate the user who sent the email
$current_user = CRMEntity::getInstance('Users');
$current_user->retrieveCurrentUserInfoFromFile($valid['userid']);

// theme and language
$theme = $current_user->column_fields['default_theme'];
$current_language = $current_user->column_fields['default_language'];


$showModule = $valid['module'];
$showCrmid = $valid['crmid'];
$canEdit = $valid['edit'];

$focus = CRMEntity::getInstance($showModule);
$focus->retrieve_entity_info($showCrmid, $showModule);
$entityName = getEntityName($showModule, $showCrmid);
$entityName = $entityName[$showCrmid];

//crmv@59514
$enable_revision = true;
if($focus->column_fields['filelocationtype'] == 'E'){
	$enable_revision = false;
}
//crmv@59514e

if ($showModule == 'Messages' && $_REQUEST['share_action'] == 'dl_attachment') {
	$contentid = $valid['otherinfo']['contentid'];

	$_REQUEST['record'] = $showCrmid;
	$_REQUEST['contentid'] = $contentid;
	$currentModule = $showModule;
	require('modules/Messages/Download.php');
	die();

} elseif ($showModule == 'Documents' && $_REQUEST['share_action'] == 'download') {
	//crmv@59514
	if($focus->column_fields['filelocationtype'] == 'I' || $focus->column_fields['filelocationtype'] == 'B'){ // crmv@95157
		$attachmentid = $adb->query_result($adb->pquery("select attachmentsid from {$table_prefix}_seattachmentsrel where crmid = ?", array($showCrmid)),0,'attachmentsid');
		if ($attachmentid > 0) {
			$_REQUEST['module'] = 'uploads';
			$_REQUEST['action'] = 'downloadfile';
			$_REQUEST['fileid'] = $attachmentid;
			$_REQUEST['entityid'] = $showCrmid;
			$_REQUEST['return_module'] = 'Documents'; //crmv@174939
			require('modules/uploads/downloadfile.php');
		}
	}
	elseif($focus->column_fields['filelocationtype'] == 'E'){
		if(!empty($focus->column_fields['filename']))
			header("Location: ".$focus->column_fields['filename']);
	}
	//crmv@59514e
	die();

} elseif ($_REQUEST['share_action'] == 'getedittoken') {
	require_once('modules/Emails/mail.php');

	$visitor_email = $_REQUEST['visitor_email'];
	if (preg_match('/^[0-9a-z._-]+@[0-9a-z._]+$/i', $visitor_email)) {
		$extraInfo = array('visitor_email'=>$visitor_email);

		$etoken = $CU->generateShareToken('Documents', $showCrmid, true, $extraInfo);

		$subjectTpl = getTranslatedString('LBL_SHARE_EMAIL_EDIT_SUBJECT', 'APP_STRINGS');
		$subject = replaceStringTemplate($subjectTpl, array('type'=>$singleLabel, 'entityname'=>$entityName));

		$bodyTpl = getTranslatedString('LBL_SHARE_EMAIL_EDIT_BODY');
		$body = replaceStringTemplate($bodyTpl, array(
			'entityname'=>$focusName,
			'site_url'=>$site_URL,
			'token'=>$etoken,
			'date'=>date('Y-m-d', time()+$CU->shareTokenDuration),
		));

		// crmv@109570
		global $HELPDESK_SUPPORT_EMAIL_ID,$HELPDESK_SUPPORT_NAME;
		$r = send_mail($showModule, $visitor_email, $HELPDESK_SUPPORT_NAME, $HELPDESK_SUPPORT_EMAIL_ID, $subject, $body);
		// crmv@109570e

		if ($r == 1) {
			$return = array('error'=> '', 'message'=>getTranslatedString('LBL_EMAIL_SENT'));
		} else {
			$return = array('error'=> getTranslatedString('LBL_EMAIL_SEND_FAIL'));
			// delete token
			$CU->deleteShareToken($etoken);
		}

	} else {
		$return = array('error'=> getTranslatedString('LBL_EMAIL_INVALID'));
	}
	echo Zend_Json::encode($return);
	die();

} elseif ($_REQUEST['share_action'] == 'upload') {
	$uploadOk = false;
	if ($canEdit && isPermitted('Documents', 'EditView', $showCrmid) == 'yes') {
		$_REQUEST['record'] = $showCrmid;
		$_REQUEST['user_email'] = $valid['otherinfo']['visitor_email'];
		$bkp_focus = $focus; //crmv@65492 - 19
		ob_start();
		include('modules/Documents/RevisionSave.php');
		$output = ob_get_clean();
		ob_end_clean();
		$focus = $bkp_focus; //crmv@65492 - 19
		if (strpos($output, 'parent.document.location.reload') !== false) {
			$uploadOk = true;
		}
	}
	$smarty->assign("UPLOAD_STATUS", $uploadOk);
}

//$smarty->assign("PAGE_TITLE", $entityName);
$smarty->assign("MODULE", $showModule);
$smarty->assign("ID", $showCrmid);
$smarty->assign("EDIT_PERM", $canEdit);
$smarty->assign("ENABLE_REVISION", $enable_revision);	//crmv@59514

//$smarty->display('SmallHeader.tpl');

// template name can be overridden in class
$shareTpl = 'SharedRecord.tpl';
if (!empty($focus->sharedTemplate)) $shareTpl = $focus->sharedTemplate;

// retrieve fields
$wsmodule = vtws_describe($showModule, $current_user);

$wsid = vtws_getWebserviceEntityId($showModule, $showCrmid);
$wsrecord = vtws_retrieve($wsid, $current_user);

if ($showModule == 'Documents') {
	// for som reasons, file field is stripped out from WS
	$wsrecord['filename'] = getSingleFieldValue($focus->table_name, 'filename', $focus->table_index, $showCrmid);
}

// now filter to use only specified fields
if (!empty($focus->sharedFields)) $wsrecord = array_intersect_key($wsrecord, array_flip($focus->sharedFields));

$fields = array();
foreach ($wsmodule['fields'] as $fldinfo) {
	if (array_key_exists($fldinfo['name'], $wsrecord)) {
		if ($fldinfo['name'] == 'filename') {
			$smarty->assign('DOWNLOAD_LINK', $wsrecord[$fldinfo['name']]);
			$smarty->assign('DOWNLOAD_NAME', $wsrecord[$fldinfo['name']]);
		}  else {
			$fldinfo['value'] = $wsrecord[$fldinfo['name']];
			$fields[] = $fldinfo;
		}
	}
}

$smarty->assign('FIELDS', $fields);

// Only Documents supported now
if ($showModule == 'Documents') {
	$body = $smarty->fetch($shareTpl);
	$smarty->assign('BODY',$body);
	$smarty->display('NoLoginMsg.tpl');
} else {
	// should never arrive here
	echo "<p>Record type not supported.</p>";
}
?>