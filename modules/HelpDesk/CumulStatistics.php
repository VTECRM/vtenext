<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
require_once('include/database/PearDatabase.php');
require_once('include/utils/utils.php');
require_once('TicketStatisticsUtil.php');

global $app_strings;
global $app_list_strings;
global $mod_strings;

global $theme;
$theme_path="themes/".$theme."/";
$image_path=$theme_path."images/";

echo get_module_title("HelpDesk", $mod_strings['LBL_TICKETS'].": ".$mod_strings['LBL_STATISTICS'] , true);
echo '<br>';

$totTickets = getTotalNoofTickets();
if($totTickets == 0)
{
	$singleUnit = 0;
}
else
{
	$singleUnit = 80/$totTickets;
}
$totOpenTickets = getTotalNoofOpenTickets();
$totClosedTickets = getTotalNoofClosedTickets();

$smarty=new VteSmarty();
$smarty->assign("MOD", $mod_strings);
$smarty->assign("APP", $app_strings);

if(isset($_REQUEST['return_module'])) $smarty->assign("RETURN_MODULE", $_REQUEST['return_module']);
if(isset($_REQUEST['return_action'])) $smarty->assign("RETURN_ACTION", $_REQUEST['return_action']);
if(isset($_REQUEST['return_id'])) $smarty->assign("RETURN_ID", $_REQUEST['return_id']);
$smarty->assign("THEME", $theme);
$smarty->assign("IMAGE_PATH", $image_path);

$smarty->assign("ALLOPEN", outBar($totOpenTickets, $image_path, $singleUnit));
$smarty->assign("ALLCLOSED", outBar($totClosedTickets, $image_path, $singleUnit));
$smarty->assign("ALLTOTAL", outBar($totTickets, $image_path, $singleUnit));
$smarty->assign("PRIORITIES", showPriorities($image_path, $singleUnit)); 
$smarty->assign("CATEGORIES", showCategories($image_path, $singleUnit)); 
$smarty->assign("USERS", showUserBased($image_path, $singleUnit)); 

$smarty->display('CumulStatistics.tpl');;


?>