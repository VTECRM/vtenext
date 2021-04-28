<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@95157 */

require_once('include/utils/utils.php');
global $adb,$table_prefix;

$record = vtlib_purify($_REQUEST['fileid']);
$folder = vtlib_purify($_REQUEST['folderid']);
$title = vtlib_purify($_REQUEST['title']);

$focus = CRMEntity::getInstance('Myfiles');
$focus->id = $record;

$ret_arr = Array(
	'success'=>false,
	'message'=>getTranslatedString('LBL_ERROR_CONVERT_DOCUMENT','Myfiles'),
);

$res = $focus->retrieve_entity_info_no_html($focus->id,'Myfiles',false);

if (empty($res)){
	$sql = "select ".$adb->sql_concat(Array('a.path','a.attachmentsid',"'_'",'a.name'))." as filepath from ".$table_prefix."_attachments a
	inner join ".$table_prefix."_seattachmentsrel s on s.attachmentsid = a.attachmentsid
	inner join ".$table_prefix."_myfiles m on m.myfilesid = s.crmid
	where s.crmid = ?";
	$res = $adb->pquery($sql,array($focus->id));
	if ($res && $adb->num_rows($res)>0){
		try{
			$filename = $adb->query_result_no_html($res,0,'filepath');
			$doc_obj = CRMEntity::getInstance('Documents');
			$filename_fieldname = $doc_obj->getFile_FieldName();
			
			// prepare the fake files array
			global $_FILES;
			$_FILES = Array();
			$_FILES[$filename_fieldname]['type'] = $focus->column_fields['filetype'];
			$_FILES[$filename_fieldname]['name'] = $focus->column_fields['filename'];
			$_FILES[$filename_fieldname]['tmp_name'] = $filename;
			$_FILES[$filename_fieldname]['error'] = 0;
			$_FILES[$filename_fieldname]['size'] = $focus->column_fields['filesize'];
			$_FILES[$filename_fieldname]['original_name'] = $focus->column_fields['filename'];
			$_POST['copy_not_move'] = true;
			
			// prepare the documents fields
			$doc_obj->column_fields = $focus->column_fields;
			$doc_obj->column_fields['notes_title'] = $title;
			$doc_obj->column_fields['folderid'] = $folder;
			
			// save the document
			$doc_obj->save('Documents');
			
			// delete the myfile
			$focus->trash('Myfiles',$focus->id);
			
			// prepare the output
			$ret_arr['success'] = true;
			$ret_arr['docid'] = $doc_obj->id;
			$ret_arr['content'] = "<p style=\"text-align:center\"><b>".getTranslatedString('LBL_CONVERT_OK_MSG1','Myfiles')."</b><br>".getTranslatedString('LBL_CONVERT_OK_MSG2','Myfiles')."<br>".getTranslatedString('LBL_CONVERT_OK_MSG3','Myfiles')."<br><span align=\"center\"><a href=\"index.php?module=Documents&action=DetailView&record={$doc_obj->id}\" target=\"_blank\">{$title}</a></span></p>";
			unset($ret_arr['message']);
		}
		catch(Exception $e){
			$ret_arr['message_event'] = $e->getMessage();
		}
	}
}

echo Zend_Json::encode($ret_arr);
exit;