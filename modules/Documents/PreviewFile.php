<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@62414 */

global $adb,$currentModule,$current_user,$table_prefix,$mod_strings,$app_strings,$default_charset;

$mode = vtlib_purify($_REQUEST['mode']);
$record = vtlib_purify($_REQUEST['record']);

$focus = CRMEntity::getInstance($currentModule);

if($mode == 'button'){
	//going to check if document is available
	$sql = "SELECT filename,folderid,filestatus FROM {$table_prefix}_notes WHERE notesid=?";
	$res = $adb->pquery($sql,array($record));
	if($res){
		$filename = $adb->query_result($res,0,'filename');
		$folderid = $adb->query_result($res,0,'folderid');
		$filestatus = $adb->query_result($res,0,'filestatus');
		$extension = substr(strrchr($filename, "."), 1);
		$supported = false;
		$js_action = false;
		if(in_array(strtolower($extension),$focus->view_image_supported_extensions) || in_array(strtolower($extension),$focus->viewerJS_supported_extensions)){
			$supported = true;
			$js_action = $focus->action_view_JSfunction_array[strtolower($extension)];
		}
		$smarty = new VteSmarty();
		$smarty->assign('APP', $app_strings);
		$smarty->assign('MOD', $mod_strings);
		$smarty->assign('MODULE', $currentModule);
		$smarty->assign('FILE_NAME', $filename);
		$smarty->assign('FILE_STATUS', $filestatus);
		$smarty->assign('FOLDERID', $folderid);
		$smarty->assign('FILE_SUPPORTED', $supported);
		$smarty->assign('JS_ACTION', $js_action);
		$smarty->assign('RECORD', $record);
		$smarty->display('modules/Documents/PreviewFile.tpl');
	}
	exit;
}
elseif($mode == 'fetchcontent'){
	$return_array = array('success'=>false,'savepath'=>'','height'=>0,'width'=>0);
	
	$dbQuery = "SELECT 
				{$table_prefix}_notes.notesid,
				{$table_prefix}_attachments.* 
				FROM
				  {$table_prefix}_notes 
				  INNER JOIN {$table_prefix}_seattachmentsrel 
					ON {$table_prefix}_seattachmentsrel.crmid = {$table_prefix}_notes.notesid 
				  INNER JOIN {$table_prefix}_attachments 
					ON {$table_prefix}_seattachmentsrel.attachmentsid = {$table_prefix}_attachments.attachmentsid 
				WHERE notesid = ?"; // crmv@198701
	$result = $adb->pquery($dbQuery,array($record));
	if($adb->num_rows($result) == 1)
	{
		$notesid = intval($adb->query_result($result, 0, "notesid")); // crmv@198701
		$name = $adb->query_result($result, 0, "name");
		$extension = substr(strrchr($name, "."), 1);
		$fileid = $adb->query_result($result, 0, "attachmentsid");
		$name = html_entity_decode($name, ENT_QUOTES, $default_charset);
		$filepath = $adb->query_result($result,0,'path');
			
		$saved_filename = $filepath.$fileid."_".$name;
		
		if(in_array(strtolower($extension),$focus->view_image_supported_extensions)){
			$image_info = getimagesize($root_directory.$saved_filename);
			$return_array['width'] = $image_info[0];
			$return_array['height'] = $image_info[1];
		}
		
		// crmv@198701
		if (in_array(strtolower($extension), $focus->view_image_supported_extensions) || in_array(strtolower($extension), $focus->viewerJS_supported_extensions)) {
			$return_array['savepath'] = $notesid;
		} else {
			$return_array['savepath'] = $saved_filename;
		}
		// crmv@198701e

		$return_array['success'] = true;
		
		echo Zend_Json::encode($return_array);
		exit;
	}
}
?>