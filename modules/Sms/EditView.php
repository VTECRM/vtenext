<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

require_once('include/FormValidationUtil.php');

global $log;
global $app_strings;
global $app_list_strings;
global $mod_strings;
global $current_user;
global $currentModule;
global $table_prefix;
$focus = CRMEntity::getInstance($currentModule);
$smarty = new VteSmarty();

if($_REQUEST['upload_error'] == true)
{
        echo '<br><b><font color="red"> The selected file has no data or a invalid file.</font></b><br>';
}

//Email Error handling
if($_REQUEST['sms_error'] != '') 
{
	require_once("modules/Sms/sms_.php");
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
	$focus->retrieve_entity_info($_REQUEST['record'],"Sms");
	if(isset($_REQUEST['forward']) && $_REQUEST['forward'] != '')
	{
		$att_id_list = '';//used in getBlockInformation funtion as global variable to get the attachment id list for forwarding sms with attachment 
		$focus->mode = '';
	}
	else
	{
		$query = 'select idlists,from_number,to_number from '.$table_prefix.'_smsdetails where smsid =?';
		$result = $adb->pquery($query, array($focus->id));
		$smarty->assign('FROM_SMS',$adb->query_result($result,0,'from_number'));	
		$to_sms = str_replace('###',',',$adb->query_result($result,0,'to_number'));
		$smarty->assign('TO_SMS',trim($to_sms,",").",");	
		$smarty->assign('IDLISTS',str_replace('###',',',$adb->query_result($result,0,'idlists')));	
	}
    $log->info("Entity info successfully retrieved for EditView.");
	$focus->name=$focus->column_fields['name'];		
}
elseif(isset($_REQUEST['sendsms']) && $_REQUEST['sendsms'] !='')
{
	$smsids = get_to_smsids($_REQUEST['pmodule']);
	if($smsids['smsids'] != '')
		$to_add = trim($smsids['smsids'],",").",";
	$smarty->assign('TO_SMS',$to_add);
	$smarty->assign('IDLISTS',$smsids['idlists']);
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
		$to_sms = $adb->query_result($adb->pquery($q, array($rec_id)),0,$fieldname);
	} elseif ($rec_type == "email_addy") {
		$to_sms = $_REQUEST["email_addy"];
	}
	$smarty->assign('TO_SMS',trim($to_sms,",").",");
}	


//handled for replying sms
if($_REQUEST['reply'] == "true")
{
		$fromadd = $_REQUEST['record'];	
		$query = "select from_sms,idlists from ".$table_prefix."_smsdetails where smsid =?";
		$result = $adb->pquery($query, array($fromadd));
		$from_sms = $adb->query_result($result,0,'from_sms');	
		$smarty->assign('TO_SMS',trim($from_sms,",").',');
		$smarty->assign('IDLISTS',str_replace('###',',',$adb->query_result($result,0,'idlists')));	
}

global $theme;
$theme_path="themes/".$theme."/";
$image_path=$theme_path."images/";

$disp_view = getView($focus->mode);
//crmv@9434
$mode = $focus->mode;
//crmv@9434 end
$details = getBlocks($currentModule,$disp_view,$mode,$focus->column_fields);
//changed this below line to view description in all language - bharath
$smarty->assign("BLOCKS",$details[$mod_strings['LBL_SMS_INFORMATION']]); 
$smarty->assign("MODULE",$currentModule);
$smarty->assign("SINGLE_MOD",$app_strings['Sms']);
//id list of attachments while forwarding
$smarty->assign("ATT_ID_LIST",$att_id_list);

//needed when creating a new sms with default values passed in
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

$log->info("Sms detail view");

$smarty->assign("MOD", $mod_strings);
$smarty->assign("APP", $app_strings);
if (isset($focus->name)) $smarty->assign("NAME", $focus->name);
else $smarty->assign("NAME", "");


if($focus->mode == 'edit')
{
	$smarty->assign("UPDATEINFO",updateInfo($focus->id));
        $smarty->assign("MODE", $focus->mode);
}

// Unimplemented until jscalendar language vte_files are fixed

$smarty->assign("CALENDAR_LANG", $app_strings['LBL_JSCALENDAR_LANG']);
$smarty->assign("CALENDAR_DATEFORMAT", parse_calendardate($app_strings['NTC_DATE_FORMAT']));

if(isset($_REQUEST['return_module'])) $smarty->assign("RETURN_MODULE", $_REQUEST['return_module']);
else $smarty->assign("RETURN_MODULE",'Emails');
if(isset($_REQUEST['return_action'])) $smarty->assign("RETURN_ACTION", $_REQUEST['return_action']);
else $smarty->assign("RETURN_ACTION",'index');
if(isset($_REQUEST['return_id'])) $smarty->assign("RETURN_ID", $_REQUEST['return_id']);
if (isset($_REQUEST['return_viewname'])) $smarty->assign("RETURN_VIEWNAME", $_REQUEST['return_viewname']);


$smarty->assign("THEME", $theme);
$smarty->assign("IMAGE_PATH", $image_path);
$smarty->assign("ID", $focus->id);
$smarty->assign("ENTITY_ID", $_REQUEST["record"]);
$smarty->assign("ENTITY_TYPE",$_REQUEST["sms_directing_module"]);
$smarty->assign("OLD_ID", $old_id );
$smarty->assign("DESCRIPTION", $_REQUEST["description"] );
//Display the FCKEditor or not? -- configure $FCKEDITOR_DISPLAY in config.php 
//$smarty->assign("FCKEDITOR_DISPLAY",$FCKEDITOR_DISPLAY);

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

$check_button = Button_Check($module);
$smarty->assign("CHECK", $check_button);

$smarty->display(vtlib_getModuleTemplate($currentModule, 'ComposeSms.tpl'));	//crmv@16703
?>