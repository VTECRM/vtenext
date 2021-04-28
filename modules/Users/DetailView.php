<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

require_once('modules/Users/Users.php');
require_once('modules/Settings/AuditTrail.php'); // crmv@202301

global $current_user;
global $theme;
global $default_language;
global $adb;
global $currentModule;
global $app_strings;
global $mod_strings;
global $table_prefix;

$focus = CRMEntity::getInstance('Users');

if(!empty($_REQUEST['record']))
{
	$focus->retrieve_entity_info($_REQUEST['record'],'Users');
	$focus->id = $_REQUEST['record'];
}
else
{

    echo "
        <script type='text/javascript'>
            window.location = 'index.php?module=Users&action=ListView';
        </script>
        ";
}

if( $focus->user_name == "" )
{

	if(is_admin($current_user))
	{
    echo "
            <table>
                <tr>
                    <td>
                        <b>User does not exist.</b>
                    </td>
		    </tr>";

    echo "
                <tr>
                    <td>
                        <a href='index.php?module=Users&action=ListView'>List Users</a>
                    </td>
                </tr>
            </table>
	    ";
    exit;
	}

}


if(isset($_REQUEST['isDuplicate']) && $_REQUEST['isDuplicate'] == 'true') {
	$focus->id = "";
}

global $theme;
$theme_path="themes/".$theme."/";
$image_path=$theme_path."images/";

//the user might belong to multiple groups
$log->info("User detail view");

$category = getParenttab();

$smarty = new VteSmarty();
if($_REQUEST['mail_error'] != '')
{
    require_once("modules/Emails/mail.php");
    $error_msg = strip_tags(parseEmailErrorString($_REQUEST['mail_error']));
	$error_msg = $app_strings['LBL_MAIL_NOT_SENT_TO_USER']. ' ' . vtlib_purify($_REQUEST['user']). '. ' .$app_strings['LBL_PLS_CHECK_EMAIL_N_SERVER'];
	$smarty->assign("ERROR_MSG",$mod_strings['LBL_MAIL_SEND_STATUS'].' <b><font class="warning">'.$error_msg.'</font></b>');
}
$smarty->assign("UMOD", $mod_strings);
global $current_language;
//crmv@7222
$smod_strings = return_module_language($current_language, 'Settings');
$smarty->assign("MOD", $smod_strings);
//crmv@7222e
$smarty->assign("APP", $app_strings);

$oGetUserGroups = new GetUserGroups();
$oGetUserGroups->getAllUserGroups($focus->id);
if(useInternalMailer() == 1)
        $smarty->assign("INT_MAILER","true");

$smarty->assign("GROUP_COUNT",count($oGetUserGroups->user_groups));
$smarty->assign("THEME", $theme);
$smarty->assign("IMAGE_PATH", $image_path);
$smarty->assign("ID", $focus->id);
$smarty->assign("CATEGORY", $category);

if(isset($_REQUEST['modechk']) && $_REQUEST['modechk'] != '' )
{
	$modepref = $_REQUEST['modechk'];
}
	if($_REQUEST['modechk'] == 'prefview')
		$parenttab = '';
	else
		$parenttab = 'Settings';

$smarty->assign("PARENTTAB", $parenttab);
//crmv@20054
if ((is_admin($current_user) || $_REQUEST['record'] == $current_user->id)
		&& isset($default_user_name)
		&& $default_user_name == $focus->user_name
		&& isset($lock_default_user_name)
		&& $lock_default_user_name == true	) {
	$buttons = "<input title='".$app_strings['LBL_EDIT_BUTTON_TITLE']."' accessKey='".$app_strings['LBL_EDIT_BUTTON_KEY']."' class='crmButton small edit' onclick=\"DetailView.return_module.value='Users'; DetailView.return_action.value='DetailView'; DetailView.return_id.value='$focus->id'; DetailView.action.value='EditView'; DetailView.submit();\" type='button' name='Edit' value='".$app_strings['LBL_EDIT_BUTTON_LABEL']."'>";
	$smarty->assign('EDIT_BUTTON',$buttons);
}
elseif (is_admin($current_user) || $_REQUEST['record'] == $current_user->id) {
	$buttons = "<input title='".$app_strings['LBL_EDIT_BUTTON_TITLE']."' accessKey='".$app_strings['LBL_EDIT_BUTTON_KEY']."' class='crmButton small edit' onclick=\"DetailView.return_module.value='Users'; DetailView.return_action.value='DetailView'; DetailView.return_id.value='$focus->id'; DetailView.action.value='EditView'; DetailView.submit();\" type='button' name='Edit' value='".$app_strings['LBL_EDIT_BUTTON_LABEL']."'>";
	$smarty->assign('EDIT_BUTTON',$buttons);

	//crmv@9010
	$AUTHCFG = get_config_ldap();
	// Allow the 'admin' always to log in independent from the LDAP server
	if ($AUTHCFG['active'] && $focus->user_name != 'admin' && $focus->column_fields['use_ldap'] == 1) // crmv@167234
		$buttons = $mod_strings['LBL_LDAP'];
	else
		$buttons = "<a href='javascript:;' onclick='openPopup(\"index.php?module=Users&action=ChangePassword&form=DetailView\",\"test\",\"\",\"\",320,200);'>{$mod_strings['LBL_CHANGE_PASSWORD_BUTTON_TITLE']}</a>";//crmv@21048m
	$smarty->assign('CHANGE_PW_BUTTON',$buttons);
	//crmv@9010e
}

if (is_admin($current_user))
{
	if ($theme === 'next') {
		$buttons = '<button type="button" class="crmbutton edit crmbutton-turbolift" onclick="DetailView.return_module.value=\'Users\'; DetailView.return_action.value=\'DetailView\'; DetailView.isDuplicate.value=true; DetailView.return_id.value=\''.$_REQUEST['record'].'\';DetailView.action.value=\'EditView\'; DetailView.submit();">';
		$buttons .= '<div>'.$app_strings['LBL_DUPLICATE_BUTTON_LABEL'].'</div>';
		$buttons .= '</button>';
	} else {
		$buttons = '<div class="turboliftEntry1 btn" title="'.$app_strings['LBL_DUPLICATE_BUTTON_TITLE'].'" accessKey="'.$app_strings['LBL_DUPLICATE_BUTTON_KEY'].'" onclick="DetailView.return_module.value=\'Users\'; DetailView.return_action.value=\'DetailView\'; DetailView.isDuplicate.value=true; DetailView.return_id.value=\''.$_REQUEST['record'].'\';DetailView.action.value=\'EditView\'; DetailView.submit();">';
		$buttons .= '<div>'.$app_strings['LBL_DUPLICATE_BUTTON_LABEL'].'</div>';
		$buttons .= '</div>';
	}
	$smarty->assign('DUPLICATE_BUTTON',$buttons);

	//done so that only the admin user can see the customize tab button
	if($_REQUEST['record'] != $current_user->id)
	{
		$buttons = "<input title='".$app_strings['LBL_DELETE_BUTTON_TITLE']."' accessKey='".$app_strings['LBL_DELETE_BUTTON_KEY']."' class='classBtn' onclick=\"deleteUser('$focus->id')\" type='button' name='Delete' value='  ".$app_strings['LBL_DELETE_BUTTON_LABEL']."  '>";
		$smarty->assign('DELETE_BUTTON',$buttons);
	}

	if(VteSession::get('authenticated_user_roleid') == 'administrator')
	{
		$buttons = "<input title='".$app_strings['LBL_LISTROLES_BUTTON_TITLE']."' accessKey='".$app_strings['LBL_LISTROLES_BUTTON_KEY']."' class='classBtn' onclick=\"DetailView.return_module.value='Users'; DetailView.return_action.value='TabCustomise'; DetailView.action.value='listroles'; DetailView.record.value= '". $current_user->id ."'; DetailView.submit();\" type='button' name='ListRoles' value=' ".$app_strings['LBL_LISTROLES_BUTTON_LABEL']." '>";
		$smarty->assign('LISTROLES_BUTTON',$buttons);
	}
}
$smarty->assign("SINGLE_MOD",'LBL_USER');
$smarty->assign("NAME", $focus->last_name.' '.$focus->first_name);
if ($_REQUEST['parenttab'] == 'Settings') {
	$check_button = Button_Check($module);
	$smarty->assign("CHECK", $check_button);
}
//crmv@20054e
//crmv@20209
global $mode;
$mode = 'detail';
$smarty->assign("MODE",$mode);
//crmv@20209e
if(is_admin($current_user))
	$smarty->assign("IS_ADMIN", true);
else
	$smarty->assign("IS_ADMIN", false);

//crmv@57221
$CU = CRMVUtils::getInstance();
$smarty->assign("OLD_STYLE", $CU->getConfigurationLayout('old_style'));
//crmv@57221e

$smarty->assign('HIDE_BUTTON_CREATE', true); // crmv@140887

if (isPermitted($currentModule, 'DetailView', intval($_REQUEST['record'])) == 'yes') { // crmv@37004

	// crmv@83877 crmv@112297
	// Field Validation Information
	$tabid = getTabid($currentModule);
	$otherInfo = array();
	$validationData = getDBValidationData($focus->tab_name,$tabid,$otherInfo,$focus);	//crmv@96450
	$validationArray = split_validationdataArray($validationData, $otherInfo);
	$smarty->assign("VALIDATION_DATA_FIELDNAME",$validationArray['fieldname']);
	$smarty->assign("VALIDATION_DATA_FIELDDATATYPE",$validationArray['datatype']);
	$smarty->assign("VALIDATION_DATA_FIELDLABEL",$validationArray['fieldlabel']);
	$smarty->assign("VALIDATION_DATA_FIELDUITYPE",$validationArray['fielduitype']);
	$smarty->assign("VALIDATION_DATA_FIELDWSTYPE",$validationArray['fieldwstype']);
	// crmv@83877e crmv@112297e
	
	$smarty->assign("MODULE", 'Users');
	$smarty->assign("CURRENT_USERID", $current_user->id);
	$smarty->assign("HOMEORDER",$focus->getHomeStuffOrder($focus->id));
	$smarty->assign("BLOCKS", getBlocks($currentModule,"detail_view",'',$focus->column_fields));
	$smarty->assign("USERNAME",$focus->last_name.' '.$focus->first_name);
	$smarty->assign("HOUR_FORMAT",$focus->hour_format);
	$smarty->assign("START_HOUR",$focus->start_hour);
	
	// crmv@202301
	$AuditTrail = new AuditTrail();
	$smarty->assign("AUDITTRAIL",$AuditTrail->isEnabled() ? 'true' : 'false');
	// crmv@202301e
	
	//crmv@7222+7221
	if (is_admin($current_user) == true && $_REQUEST['record'] != 1) {
		$custom_access = array();
		//crmv@13979		//crmv@21280
		$othermodules = getSharingModuleList(Array('Contacts'));
		//crmv@13979 end	//crmv@21280e
		if(!empty($othermodules)) {
			foreach($othermodules as $moduleresname) {
				$custom_access[$moduleresname] = getSharingRuleListUser($moduleresname,$focus->id);
			}
		}
		$smarty->assign("MODSHARING", $custom_access);
		//advanced sharing rules
		//crmv@13979
		$othermodules = getSharingModuleList();
		//crmv@13979 end
		if(!empty($othermodules)) {
			foreach($othermodules as $moduleresname) {
				$adv_custom_access[$moduleresname] = getAdvSharingRule($moduleresname,$focus->id);
			}
		}
		$smarty->assign("ADVSHARING", $adv_custom_access);
	
		if (isset($_REQUEST['sharing']) && $_REQUEST['sharing'] = 'true') {
			$smarty->assign("SHOW_SHARING", 'block');
			$smarty->assign("SHOW_ADV_SHARING", 'none');
		}
		elseif (isset($_REQUEST['adv_sharing']) && $_REQUEST['adv_sharing'] = 'true') {
			$smarty->assign("SHOW_ADV_SHARING", 'block');
			$smarty->assign("SHOW_SHARING", 'none');
		}
		else {
			$smarty->assign("SHOW_SHARING", 'none');
			$smarty->assign("SHOW_ADV_SHARING", 'none');
		}
	}
	//crmv@7222+7221e
	
	$smarty->assign("EDIT_PERMISSION", 'yes');
	$smarty->assign("DETAILVIEW_AJAX_EDIT", true);
	
	// crmv@181170
	$calendarFocus = CRMEntity::getInstance('Calendar');
	$smarty->assign('CALENDAR_SHARE_CONTENT', $calendarFocus->getCalendarShareContent($_REQUEST['record'], $mode));
	// crmv@181170e

	$smarty->display("UserDetailView.tpl");
}
else
{
	$output = '<table border="0" cellpadding="5" cellspacing="0" height="450" width="100%">
		<tr><td align = "center">
		<div style="border: 3px solid rgb(153, 153, 153); background-color: rgb(255, 255, 255); width: 55%; position: relative; z-index: 10000000;">
			<table border="0" cellpadding="5" cellspacing="0" width="98%">
			<tr>
				<td rowspan="2" width="11%">
				  	<img src="'. resourcever('denied.gif').'">
				</td>
				<td style="border-bottom: 1px solid rgb(204, 204, 204);" nowrap="nowrap" width="70%">
					<span class="genHeaderSmall">'.$app_strings["LBL_PERMISSION"].'
					</span>
				</td>
			</tr>
			<tr>
				<td class="small" align="right" nowrap="nowrap">
					<a href="javascript:window.history.back();">'.$app_strings["LBL_GO_BACK"].'</a>
					<br>
				</td>
			</tr>
			</table>
		</div>
		</td></tr>
	</table>';
	echo $output;
}
?>