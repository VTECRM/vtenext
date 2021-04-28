<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@OPER6317 crmv@96233 */

global $current_user, $currentModule, $theme;
global $adb, $table_prefix;
global $upload_badext;

$outputMode = 'json';
$output = null;
$ajaxaction = $_REQUEST['ajaxaction'];

$wizardid = intval($_REQUEST['wizardid']);

$WU = WizardUtils::getInstance();
$WH = WizardHandler::getInstance($wizardid);

if ($ajaxaction == 'save') {

	$ret = array();
	
	$selectedRecords = Zend_Json::decode($_REQUEST['selectedRecords']);
	$forms = Zend_Json::decode($_REQUEST['forms']);
	
	try {
		$output = $WH->saveWizard($_REQUEST);
		if (!$output) {
			$output = array('success' => false, 'error' => 'Unable to complete the wizard');
		} else {
			$output['success'] = true;
		}
	} catch (Exception $e) {
		$output = array('success' => false, 'error' => $e->getMessage());
	}
		
} elseif ($ajaxaction ==  'uploadfile') {

	// DEPRECATED
	$output = array('success' => false, 'error' => 'This action is not supported');
	
	/*
	$return_arr = Array(
		'success'=>false,
		'message'=>getTranslatedString('LBL_UPLOAD_ERROR','Contacts'),
	);
	$uniqueid = vtlib_purify($_REQUEST['uniqueid']);
	$tmpdir = '/tmp/vte_wizard_upload_'.$uniqueid.'/';
	//check if a file is already existing
	if (!empty($_FILES)) {
		$realfiles = $_FILES;
		//check if a file is already existing
		$files_to_upload = Array();
		foreach ($realfiles as $index=>$file_arr){
			$filename = $file_arr['name'];
			$filename = from_html(preg_replace('/\s+/', '_', $filename));
			$binFile = sanitizeUploadFileName($filename, $upload_badext);
			$filename = ltrim(basename(" ".$binFile)); //allowed filename like UTF-8 characters
			$files_to_upload[$index] = strtolower($filename);
		}
		$files_to_upload_reverse = array_flip($files_to_upload);
		@mkdir($tmpdir);
		foreach($files_to_upload as $index => $filename) {
			$fext = pathinfo($filename, PATHINFO_EXTENSION);
			$fname = pathinfo($filename, PATHINFO_FILENAME);
			$result = move_uploaded_file($realfiles[$files_to_upload_reverse[strtolower($filename)]]['tmp_name'],$tmpdir.$filename);
		}
		if ($result) {
			$return_arr['success'] = true;
			$return_arr['message'] = '';
			$return_arr['uniqueid'] = $uniqueid;
		}
	}
	echo Zend_Json::encode($return_arr);
	exit;
	*/

} elseif ($ajaxaction ==  'removetmpfile') {
	// DEPRECATED
	$output = array('success' => false, 'error' => 'This action is not supported');
	
	/*$uniqueid = vtlib_purify($_REQUEST['uniqueid']);
	$filename = vtlib_purify($_REQUEST['filename']);
	$tmpdir = '/tmp/vte_wizard_upload_'.$uniqueid.'/';
	if ($handle = opendir($tmpdir)) {
		while (false !== ($entry = readdir($handle))) {
			if ($entry == $filename) {
				@unlink($tmpdir.$filename);
			}
		}
	}
	*/

} elseif ($ajaxaction ==  'documentlist') {
	// DEPRECATED
	$output = array('success' => false, 'error' => 'This action is not supported');
	/*
	$uniqueid = vtlib_purify($_REQUEST['uniqueid']);
	$tmpdir = '/tmp/vte_wizard_upload_'.$uniqueid.'/';
	$html = '<form name="documentlist">
		<input type="hidden" id="documentlist_uniqueid" name="documentlist_uniqueid" value="'.$uniqueid.'" />
	</form>';
	if ($handle = opendir($tmpdir)) {
		$html .= '<br><p><b>'.getTranslatedString('Documents').':</b></p>';
		$html .= '<table border="0" cellspacing="1" cellpadding="3" width="100%" class="lvt small">';
		while (false !== ($entry = readdir($handle))) {
			if ($entry != "." && $entry != "..") {
				$html .= '<tr bgcolor="white" onmouseover="this.className=\'lvtColDataHover\'" onmouseout="this.className=\'lvtColData\'" class="lvtColData">
					<td width="1%">
						<a href=\'javascript:removeTmpFile("'.$uniqueid.'","'.$entry.'");\'><img src="'.resourcever('small_delete.png').'" title="'.getTranslatedString('LBL_DELETE').'" border="0"></a>
					</td>
					<td width="99%">'.$entry.'</td>
				</tr>';
			}
		}
	}
	echo $html;
	exit;
	*/
}

if ($outputMode == 'json') {
	echo Zend_Json::encode($output);
	die();
} elseif ($outputMode == 'smarty' && isset($smarty) && !empty($template)) {
	$smarty->display($template);
} elseif ($outputMode == 'raw') {
	echo $output;
	die();
} elseif ($outputMode == 'include') {
	return $output;
}