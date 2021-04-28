<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
require_once('include/home.php');
require_once('modules/Home/HomeBlock.php');
require_once('include/database/PearDatabase.php');
require_once('include/utils/UserInfoUtil.php');
require_once('include/utils/CommonUtils.php');
require_once 'modules/Home/HomeUtils.php';

global $app_strings, $app_list_strings, $mod_strings;
global $adb, $current_user;
global $theme;
global $current_language;

$theme_path="themes/".$theme."/";
$image_path=$theme_path."images/";

$smarty = new VteSmarty();
$homeObj=new Homestuff;

// Performance Optimization
$tabrows = vtlib_prefetchModuleActiveInfo();
// END

$modulenamearr = Array();
$excludeMods = array('Events', 'Emails', 'Fax', 'Sms', 'ModComments','Messages', 'PBXManager'); // crmv@132733 crmv@164120 crmv@164122
foreach($tabrows as $resultrow) {
	if($resultrow['isentitytype'] != '0') {
		if (in_array($resultrow['name'], $excludeMods)) continue; // crmv@132733
		$modName=$resultrow['name'];
		if(isPermitted($modName,'DetailView') == 'yes' && vtlib_isModuleActive($modName)){
			$modulenamearr[$modName]=array($resultrow['tabid'],$modName);
		}
	}
}
ksort($modulenamearr); // We avoided ORDER BY in Query (vtlib_prefetchModuleActiveInfo)!
// END


//Security Check done for RSS
$allow_rss='no';
if(isPermitted('Rss','DetailView') == 'yes' && vtlib_isModuleActive('Rss')){
	$allow_rss='yes';
}
//crmv@208472
/* crmv@30014 */
$allow_charts = 'no';
if(vtlib_isModuleActive('Charts') && isPermitted('Charts','DetailView') == 'yes'){
	$allow_charts='yes';
}
/* crmv@30014e */


$homedetails = $homeObj->getHomePageFrame();
$maxdiv = sizeof($homedetails ?: array())-1;
$user_name = $current_user->column_fields['user_name'];
$buttoncheck['Calendar'] = isPermitted('Calendar','index');
$numberofcols = getNumberOfColumns();

$smarty->assign("CHECK",$buttoncheck);
if(vtlib_isModuleActive('Calendar')){
	$smarty->assign("CALENDAR_ACTIVE","yes");
}
$smarty->assign("IMAGE_PATH",$image_path);
$smarty->assign("MODULE",'Home');
$smarty->assign("CATEGORY",getParenttab('Home'));
$smarty->assign("CURRENTUSER",$user_name);
$smarty->assign("MAXLEN",$maxdiv);
$smarty->assign("ALLOW_RSS",$allow_rss);
//crmv@208472
$smarty->assign("ALLOW_CHARTS",$allow_charts); // crmv@30014
$smarty->assign("HOMEFRAME",$homedetails);
//crmv@fix homegate IE
foreach ((array)$homedetails as $key=>$arr){
	if (!($arr['Stufftype'] != 'Default' || $arr['Stufftitle'] != getTranslatedString('Home Page Dashboard','Home') && $arr['Stufftype'] != 'DashBoard')){
		unset($homedetails[$key]);
	}
}
$smarty->assign("HOMEFRAME_RESTRICTED",array_values((array)$homedetails));
//crmv@fix homegate IE end
$smarty->assign("MODULE_NAME",$modulenamearr);
$smarty->assign("MOD",$mod_strings);
$smarty->assign("APP",$app_strings);
$smarty->assign("THEME", $theme);
$smarty->assign("LAYOUT", $numberofcols);
$widgetBlockSize = PerformancePrefs::getBoolean('HOME_PAGE_WIDGET_GROUP_SIZE', 12);
$smarty->assign('widgetBlockSize', $widgetBlockSize);
$smarty->assign("CURRENT_USER_ID",$current_user->id);	//crmv@20054
$smarty->assign('OPEN_MYNOTES_POPUP', intval($_REQUEST['openNote'])); // crmv@146652

$smarty->display("Home/Homestuff.tpl");

?>