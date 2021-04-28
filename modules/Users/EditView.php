<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

require_once('modules/Users/Users.php');
require_once('modules/Users/Forms.php');
//crmv@208173
require_once('modules/Leads/ListViewTop.php');
global $table_prefix;
global $app_strings;
global $app_list_strings;
global $mod_strings;
global $currentModule,$default_charset;


$smarty=new VteSmarty();
$focus = CRMEntity::getInstance('Users');

if(isset($_REQUEST['record']) && isset($_REQUEST['record'])) {
	$smarty->assign("ID",vtlib_purify($_REQUEST['record']));
	$mode='edit';
	if (isPermitted($currentModule, 'EditView', intval($_REQUEST['record'])) != 'yes') { // crmv@37004
		die ("Unauthorized access to user administration.");
	}
    $focus->retrieve_entity_info($_REQUEST['record'],'Users');
	$smarty->assign("USERNAME",$focus->last_name.' '.$focus->first_name);
}else
{
	$mode='create';
	// crmv@184240
	if (isPermitted($currentModule, 'EditView') != 'yes') {
		die ("Unauthorized access to user administration.");
	}
	// crmv@184240e
}

if(isset($_REQUEST['isDuplicate']) && $_REQUEST['isDuplicate'] == 'true') {
	$focus->id = "";
	$focus->user_name = "";
	$mode='create';

	//When duplicating the user the password fields should be empty
	$focus->column_fields['user_password']='';
	$focus->column_fields['confirm_password']='';
}

//crmv@25390
if ($mode == 'create') {
	$focus->column_fields['menu_view']='Large Menu';
	$focus->column_fields['start_hour']='08:00';
	$focus->column_fields['no_week_sunday']=1;
	$focus->column_fields['activity_view']='This Week';
}
//crmv@25390e


global $theme;
$theme_path="themes/".$theme."/";
$image_path=$theme_path."images/";

$log->info("User edit view");


$smarty->assign("JAVASCRIPT", get_validate_record_js());
$smarty->assign("UMOD", $mod_strings);
global $current_language;
$smod_strings = return_module_language($current_language,'Settings');
$smarty->assign("MOD", $smod_strings);
$smarty->assign("CURRENT_USERID", $current_user->id);
$smarty->assign("APP", $app_strings);

if (isset($_REQUEST['error_string'])) $smarty->assign("ERROR_STRING", "<font class='error'>Error: ".vtlib_purify($_REQUEST['error_string'])."</font>");
if (isset($_REQUEST['return_module']))
{
        $smarty->assign("RETURN_MODULE", vtlib_purify($_REQUEST['return_module']));
        $RETURN_MODULE=vtlib_purify($_REQUEST['return_module']);
}
if (isset($_REQUEST['return_action']))
{
        $smarty->assign("RETURN_ACTION", vtlib_purify($_REQUEST['return_action']));
        $RETURN_ACTION = vtlib_purify($_REQUEST['return_action']);
}
if ($_REQUEST['isDuplicate'] != 'true' && isset($_REQUEST['return_id']))
{
        $smarty->assign("RETURN_ID", vtlib_purify($_REQUEST['return_id']));
        $RETURN_ID = vtlib_purify($_REQUEST['return_id']);
}
$smarty->assign("THEME", $theme);
$smarty->assign("IMAGE_PATH", $image_path);
$focus->mode = $mode;
$disp_view = getView($focus->mode);
$smarty->assign("IMAGENAME",$focus->imagename);
$smarty->assign("BLOCKS",getBlocks($currentModule,$disp_view,$mode,$focus->column_fields));
$smarty->assign("MODULE", 'Users');	//crmv@42247
$smarty->assign("MODE",$focus->mode);
$smarty->assign("HOUR_FORMAT",$focus->hour_format);
$smarty->assign("START_HOUR",$focus->start_hour);
if ($_REQUEST['Edit'] == ' Edit ')
{
	$smarty->assign("READONLY", "readonly");
	$smarty->assign("USERNAME_READONLY", "readonly");

}
if(isset($_REQUEST['record']) && $_REQUEST['isDuplicate'] != 'true')
{
	$smarty->assign("USERNAME_READONLY", "readonly");
}

$smarty->assign("HOMEORDER",$focus->getHomeStuffOrder($focus->id));
$smarty->assign("DUPLICATE",vtlib_purify($_REQUEST['isDuplicate']));
$smarty->assign("USER_MODE",$mode);
$smarty->assign('PARENTTAB', getParentTab());

//crmv@9010
// When creating a new user with LDAP or AD access, add a button in the template which allows to load
// full name, email address, telephone, etc from the LDAP server, so the admin doesn't have to enter this information manually
$LdapBtnText = "";
$AUTHCFG = get_config_ldap();
if ($mode == "create")
{
	$LdapBtnText = "LDAP";
}
$smarty->assign("LDAP_BUTTON", $LdapBtnText);

	$sql="SELECT rolename FROM ".$table_prefix."_role WHERE roleid = ?";
	$params = array($AUTHCFG['role']);
	$resid= $adb->pquery($sql,$params);
	$id= $adb->query_result($resid,0,'rolename');
	$smarty->assign("secondvalue",$id);
	$smarty->assign("roleid",$AUTHCFG['role']);

//crmv@9010e
//crmv@20054
$smarty->assign("OP_MODE",$focus->mode.'_view');
$smarty->assign("SINGLE_MOD",'LBL_USER');
$smarty->assign("NAME", $focus->last_name.' '.$focus->first_name);
if ($_REQUEST['parenttab'] == 'Settings') {
	$check_button = Button_Check($currentModule);
	$smarty->assign("CHECK", $check_button);
}
//crmv@20054e

// Gather the help information associated with fields
$smarty->assign('FIELDHELPINFO', vtlib_getFieldHelpInfo($currentModule));
// END

//crmv@57221
$CU = CRMVUtils::getInstance();
$smarty->assign("OLD_STYLE", $CU->getConfigurationLayout('old_style'));
//crmv@57221e

$smarty->assign('HIDE_BUTTON_CREATE', true); // crmv@140887

// crmv@181170
$calendarFocus = CRMEntity::getInstance('Calendar');
$smarty->assign('CALENDAR_SHARE_CONTENT', $calendarFocus->getCalendarShareContent($_REQUEST['record'], $mode));
// crmv@181170e

$smarty->display('UserEditView.tpl');
?>