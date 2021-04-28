<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

require_once('modules/HelpDesk/HelpDesk.php');
require_once('include/logging.php');
require_once('include/database/PearDatabase.php');
global $table_prefix, $currentModule;
$focus = CRMEntity::getInstance('HelpDesk');

//added to fix 4600
$search=vtlib_purify($_REQUEST['search_url']);
if(isset($_REQUEST['dup_check']) && $_REQUEST['dup_check'] != ''){
	
	check_duplicate(vtlib_purify($_REQUEST['module']),
	vtlib_purify($_REQUEST['colnames']),vtlib_purify($_REQUEST['fieldnames']),
	vtlib_purify($_REQUEST['fieldvalues']));
	die;
}

setObjectValuesFromRequest($focus);
global $adb,$mod_strings;
//Added to update the ticket history
//Before save we have to construct the update log. 
$mode = $_REQUEST['mode'];
if($mode == 'edit')
{
	$usr_qry = $adb->pquery("select * from ".$table_prefix."_crmentity where crmid=?", array($focus->id));
	$old_user_id = $adb->query_result($usr_qry,0,"smownerid");
}

//crmv@17952
if($_REQUEST['assigntype'] == 'U')  {
	$focus->column_fields['assigned_user_id'] = $_REQUEST['assigned_user_id'];
} elseif($_REQUEST['assigntype'] == 'T') {
	$focus->column_fields['assigned_user_id'] = $_REQUEST['assigned_group_id'];
}
//crmv@17952e

// crmv@160733
if (isset($_REQUEST['confinfo_check']) && $_REQUEST['confinfo_save_pwd'] != '') {
	$focus->setConfidentialRequest(array(
		'mode' => 'request',
		'pwd' => $_REQUEST['confinfo_save_pwd'],
		'data' => $_REQUEST['confinfo_save_more'],
	));
}
// crmv@160733e

$focus->save("HelpDesk");

//Added to retrieve the existing attachment of the ticket and save it for the new duplicated ticket
if($_FILES['filename']['name'] == '' && $_REQUEST['mode'] != 'edit' && $_REQUEST['old_id'] != '')
{
        $sql = "select ".$table_prefix."_attachments.* from ".$table_prefix."_attachments inner join ".$table_prefix."_seattachmentsrel on ".$table_prefix."_seattachmentsrel.attachmentsid=".$table_prefix."_attachments.attachmentsid where ".$table_prefix."_seattachmentsrel.crmid= ?";
        $result = $adb->pquery($sql, array($_REQUEST['old_id']));
        if($adb->num_rows($result) != 0)
	{
                $attachmentid = $adb->query_result($result,0,'attachmentsid');
		$filename = decode_html($adb->query_result($result,0,'name'));
		$filetype = $adb->query_result($result,0,'type');
		$filepath = $adb->query_result($result,0,'path');

		$new_attachmentid = $adb->getUniqueID($table_prefix."_crmentity");
		$date_var = date('Y-m-d H:i:s'); //crmv@69690

		$upload_filepath = decideFilePath();

		//Read the old file contents and write it as a new file with new attachment id
		$handle = @fopen($upload_filepath.$new_attachmentid."_".$filename,'w');
		fputs($handle, file_get_contents($filepath.$attachmentid."_".$filename));
		fclose($handle);	

		$adb->pquery("update ".$table_prefix."_troubletickets set filename=? where ticketid=?", array($filename, $focus->id));	
		$adb->pquery("insert into ".$table_prefix."_crmentity (crmid,setype,createdtime) values(?,?,?)", array($new_attachmentid, 'HelpDesk Attachment', $date_var));
		$adb->pquery("insert into ".$table_prefix."_attachments (attachmentsid,name,description,type,path) values(?,?,?,?,?)", array($new_attachmentid, $filename, '', $filetype, $upload_filepath));

		$adb->pquery("insert into ".$table_prefix."_seattachmentsrel values(?,?)", array($focus->id, $new_attachmentid));
	}
}


$return_id = $focus->id;

if(isset($_REQUEST['parenttab']) && $_REQUEST['parenttab'] != "") $parenttab = $_REQUEST['parenttab'];
if(isset($_REQUEST['return_module']) && $_REQUEST['return_module'] != "") $return_module = $_REQUEST['return_module'];
else $return_module = "HelpDesk";
if(isset($_REQUEST['return_action']) && $_REQUEST['return_action'] != "") $return_action = $_REQUEST['return_action'];
else $return_action = "DetailView";
if(isset($_REQUEST['return_id']) && $_REQUEST['return_id'] != "") $return_id = $_REQUEST['return_id'];

// crmv@137993 - email code moved to main class

$_REQUEST['return_id'] = $return_id;

if($_REQUEST['return_module'] == 'Products' && $_REQUEST['product_id'] != '' &&  $focus->id != '') {
	$return_id = $_REQUEST['product_id'];
}

// crmv@137993
$pms = $focus->getPortalEmailStatus();
if ($pms['status'] != '' && $pms['status'] != 1) { // crmv@104782
	$mail_error_status = $pms['error'];
}
// crmv@137993e

//code added for returning back to the current view after edit from list view
if($_REQUEST['return_viewname'] == '') $return_viewname='0';
if($_REQUEST['return_viewname'] != '')$return_viewname=$_REQUEST['return_viewname'];

//crmv@54375
if($_REQUEST['return2detail'] == 'yes') {
	$return_module = $currentModule;
	$return_action = 'DetailView';
	$return_id = $focus->id;
}
//crmv@54375e

$url = "index.php?action=$return_action&module=$return_module&parenttab=$parenttab&record=$return_id&$mail_error_status&viewname=$return_viewname&start=".$_REQUEST['pagenumber'].$search;

$from_module = vtlib_purify($_REQUEST['module']);
if (!empty($from_module)) $url .= "&from_module=$from_module";

header("Location: $url");
?>