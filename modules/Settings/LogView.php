<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@173186 */
global $app_strings, $mod_strings;

$smarty = new VteSmarty();
$smarty->assign("MOD",$mod_strings);
$smarty->assign("APP",$app_strings);
$smarty->assign("THEME", $theme);
$smarty->assign("IMAGE_PATH", "themes/$theme/images/");
 
$logConfId = $_REQUEST['log'];

$logUtils = LogUtils::getInstance();
if ($_REQUEST['log'] !== '') {
	$conf = $logUtils->getLogConfig($logConfId);
}
if (empty($conf)) exit;

$buttons = '
	<div class="morphsuitlink" style="float:left; height:34px; font-size:14px; padding-top:7px; padding-left:10px">
		'.$mod_strings['LBL_SETTINGS'].'</a> &gt; '.$mod_strings['LBL_LOG_CONFIG'].' &gt; '.$mod_strings[$conf['label']].'
	</div>
	<div style="float:right; padding-right:5px">
		<div id="status" style="display:none;"><i class="dataloader" data-loader="circle"></i></div>
		<input type="button" style="background-color:white" onclick="window.close()" class="crmbutton small edit" value="'.$app_strings['LBL_CLOSE'].'" title="'.$app_strings['LBL_CLOSE'].'">
		<img id="logo" src="'.get_logo('header').'" alt="'.$app_strings['LBL_BROWSER_TITLE'].'" title="'.$app_strings['LBL_BROWSER_TITLE'].'" border=0 style="padding:1px 0px 3px 0px; max-height:34px">
	</div>';
$smarty->assign("BUTTON_LIST", $buttons);
$smarty->assign("HEADER_Z_INDEX", 1);
$smarty->assign("PAGE_TITLE", 'SKIP_TITLE');
$smarty->assign("HEAD_INCLUDE", 'all');
$smarty->assign("BUTTON_LIST_CLASS", 'navbar navbar-default');
$smarty->display('SmallHeader.tpl');

include_once('include/VTEBaseLogger.php');
$logger = VTESystemLogger::getLogger($logConfId);
$logger->display();
exit;