<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

require_once("data/Tracker.php");
require_once('modules/Rss/Rss.php');

global $app_strings, $mod_strings, $currentModule;
global $adb, $table_prefix;
global $theme, $image_path;

$oRss = new VteRSS();

if(isset($_REQUEST['folders']) && $_REQUEST['folders'] == 'true') {
	require_once("modules/".$currentModule."/Forms.php");
	echo get_rssfeeds_form();
	die;
}

if(isset($_REQUEST['record'])) {
	$recordid = vtlib_purify($_REQUEST['record']);
}

$rss_form = new VteSmarty();
$rss_form->assign("MOD", $mod_strings);
$rss_form->assign("APP", $app_strings);
$rss_form->assign("THEME",$theme);
$rss_form->assign("IMAGE_PATH",$image_path);
$rss_form->assign("MODULE", $currentModule);
$rss_form->assign("CATEGORY", getParenttab());

//$url = 'http://forums/rss.php?name=forums&file=rss';
//$url = 'http://forums/weblog_rss.php?w=202';
if(isset($_REQUEST['record'])) // crmv@167702
{
    $recordid = vtlib_purify($_REQUEST['record']);
	$url = $oRss->getRssUrlfromId($recordid);
	if($oRss->setRSSUrl($url)) {
		$rss_html = $oRss->getSelectedRssHTML($recordid);
	} else {
		$rss_html = "<strong>".$mod_strings['LBL_ERROR_MSG']."</strong>";
	}
	$rss_form->assign("TITLE",gerRssTitle($recordid));
	$rss_form->assign("ID",$recordid);
}else {
	$rss_form->assign("TITLE",gerRssTitle());
	$rss_html = $oRss->getStarredRssHTML();
	$query = "select rssid from ".$table_prefix."_rss where starred=1";
	$result = $adb->pquery($query, array());
	$recordid = $adb->query_result($result,0,'rssid');
	$rss_form->assign("ID",$recordid);
	$rss_form->assign("DEFAULT",'yes');
}

if($currentModule == "Rss") {
	require_once("modules/".$currentModule."/Forms.php");
	if (function_exists('get_rssfeeds_form'))
	{
		$rss_form->assign("RSSFEEDS", get_rssfeeds_form());
	}
}
$rss_form->assign("RSSDETAILS",$rss_html);

if(isset($_REQUEST['directmode']) && $_REQUEST['directmode'] == 'ajax')
	$rss_form->display("RssFeeds.tpl");
else
	$rss_form->display("Rss.tpl");
