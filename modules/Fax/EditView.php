<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
global $table_prefix;
require_once 'modules/VteCore/EditView.php';	//crmv@30447

if($_REQUEST['upload_error'] == true)
{
        echo '<br><b><font color="red"> The selected file has no data or a invalid file.</font></b><br>';
}

//Email Error handling
if($_REQUEST['fax_error'] != '') 
{
	require_once("modules/Fax/fax_.php");
	echo parseEmailErrorString($_REQUEST['mail_error']);
}
//added to select the module in combobox of compose-popup
if(isset($_REQUEST['par_module']) && $_REQUEST['par_module']!=''){
	$smarty->assign('select_module',$_REQUEST['par_module']);
}
elseif(isset($_REQUEST['pmodule']) && $_REQUEST['pmodule']!='') {
	$smarty->assign('select_module',$_REQUEST['pmodule']);	
}

if(isset($_REQUEST['record']) && $_REQUEST['record'] !='') 
{
	$focus->id = $_REQUEST['record'];
	$focus->mode = 'edit';
	$focus->retrieve_entity_info($_REQUEST['record'],"Fax");
	if(isset($_REQUEST['forward']) && $_REQUEST['forward'] != '')
	{
		$att_id_list = '';//used in getBlockInformation funtion as global variable to get the attachment id list for forwarding fax with attachment 
		$focus->mode = '';
	}
	else
	{
		$query = 'select idlists,from_number,to_number from '.$table_prefix.'_faxdetails where faxid =?';
		$result = $adb->pquery($query, array($focus->id));
		$smarty->assign('FROM_FAX',$adb->query_result($result,0,'from_number'));	
		$to_fax = str_replace('###',',',$adb->query_result($result,0,'to_number'));
		$smarty->assign('TO_FAX',trim($to_fax,",").",");	
		$smarty->assign('IDLISTS',str_replace('###',',',$adb->query_result($result,0,'idlists')));	
	}
    $log->info("Entity info successfully retrieved for EditView.");
	$focus->name=$focus->column_fields['name'];		
}
elseif(isset($_REQUEST['sendfax']) && $_REQUEST['sendfax'] !='')
{
	saveListViewCheck($_REQUEST['pmodule'],$_REQUEST['idlist']);	//crmv@55198
	$faxids = get_to_faxids($_REQUEST['pmodule']);
	if($faxids['faxids'] != '')
		$to_add = trim($faxids['faxids'],",").",";
	$smarty->assign('TO_FAX',$to_add);
	$smarty->assign('IDLISTS',$faxids['idlists']);
	$focus->mode = '';
}
if($_REQUEST["internal_mailer"] == "true") {
	$smarty->assign('INT_MAILER',"true");
	$rec_type = $_REQUEST["type"];
	$rec_id = $_REQUEST["rec_id"];
	$fieldname = $_REQUEST["fieldname"];
	
	//added for getting list-ids to compose email popup from list view(Accounts,Contacts,Leads)
	if(isset($_REQUEST['field_id']) && strlen($_REQUEST['field_id']) != 0) {
	     if($_REQUEST['par_module'] == "Users")
		$id_list = $_REQUEST['rec_id'].'@'.'-1|';
	     else
                $id_list = $_REQUEST['rec_id'].'@'.$_REQUEST['field_id'].'|';
             $smarty->assign("IDLISTS", $id_list);
        }
	
	if($rec_type == "record_id") {
		$type = $_REQUEST['par_module'];
		//check added for email link in user detail view
		// crmv@64542
		$modInstance = CRMEntity::getInstance($type);
		if(substr($fieldname,0,2)=="cf")
			$tablename = $modInstance->customFieldTable[0];
		else
			$tablename = $modInstance->table_name;
		// crmv@64542e
		if($type == "Users")
			$q = "select $fieldname from $tablename where id=?";	
		elseif($type == "Leads") 
			$q = "select $fieldname from $tablename where leadaddressid=?";
		elseif ($type == "Contacts")
			$q = "select $fieldname from $tablename where contactid=?";
		elseif ($type == "Accounts")
			$q = "select $fieldname from $tablename where accountid=?";
		elseif ($type == "Vendors")
			$q = "select $fieldname from $tablename where vendorid=?";
		$to_fax = $adb->query_result($adb->pquery($q, array($rec_id)),0,$fieldname);
	} elseif ($rec_type == "email_addy") {
		$to_fax = $_REQUEST["email_addy"];
	}
	$smarty->assign('TO_FAX',trim($to_fax,",").",");
}	

//handled for replying fax
if($_REQUEST['reply'] == "true")
{
		$fromadd = $_REQUEST['record'];	
		$query = "select from_fax,idlists from ".$table_prefix."_faxdetails where faxid =?";
		$result = $adb->pquery($query, array($fromadd));
		$from_fax = $adb->query_result($result,0,'from_fax');	
		$smarty->assign('TO_FAX',trim($from_fax,",").',');
		$smarty->assign('IDLISTS',str_replace('###',',',$adb->query_result($result,0,'idlists')));	
}

$details = getBlocks($currentModule,$disp_view,$mode,$focus->column_fields);
//changed this below line to view description in all language - bharath
$smarty->assign("BLOCKS",$details['LBL_FAX_INFORMATION']); 

//id list of attachments while forwarding
$smarty->assign("ATT_ID_LIST",$att_id_list);

//needed when creating a new fax with default values passed in
if (isset($_REQUEST['contact_name']) && is_null($focus->contact_name)) 
{
	$focus->contact_name = $_REQUEST['contact_name'];
}
if (isset($_REQUEST['contact_id']) && is_null($focus->contact_id)) 
{
	$focus->contact_id = $_REQUEST['contact_id'];
}
if (isset($_REQUEST['parent_name']) && is_null($focus->parent_name)) 
{
	$focus->parent_name = $_REQUEST['parent_name'];
}
if (isset($_REQUEST['parent_id']) && is_null($focus->parent_id)) 
{
	$focus->parent_id = $_REQUEST['parent_id'];
}
if (isset($_REQUEST['parent_type'])) 
{
	$focus->parent_type = $_REQUEST['parent_type'];
}
if (isset($_REQUEST['filename']) && $_REQUEST['isDuplicate'] != 'true') 
{
        $focus->filename = $_REQUEST['filename'];
}
elseif (is_null($focus->parent_type)) 
{
	$focus->parent_type = $app_list_strings['record_type_default_key'];
}

$smarty->assign("ENTITY_ID", $_REQUEST["record"]);
$smarty->assign("ENTITY_TYPE",$_REQUEST["fax_directing_module"]);
$smarty->assign("OLD_ID", $old_id );
//Display the FCKEditor or not? -- configure $FCKEDITOR_DISPLAY in config.php 
$smarty->assign("FCKEDITOR_DISPLAY",$FCKEDITOR_DISPLAY);

if(empty($focus->filename))
{
	$smarty->assign("FILENAME_TEXT", "");
	$smarty->assign("FILENAME", "");
}
else
{
	$smarty->assign("FILENAME_TEXT", "(".$focus->filename.")");
	$smarty->assign("FILENAME", $focus->filename);
}

$smarty->display("ComposeFax.tpl");
?>