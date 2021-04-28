<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

require_once('modules/Rss/Rss.php');

global $mod_strings;
global $adb, $table_prefix;

if(isset($_REQUEST["record"])) {
	$adb->query('update '.$table_prefix.'_rss set starred=0');
	$adb->pquery('update '.$table_prefix.'_rss set starred=1 where rssid =?', array($_REQUEST["record"]));
	echo vtlib_purify($_REQUEST["record"]);

} elseif(isset($_REQUEST["rssurl"])) {

	$newRssUrl = str_replace('##amp##','&',$_REQUEST["rssurl"]);
	$setstarred = 0;
	$oRss = new VteRSS();
	if($oRss->setRSSUrl($newRssUrl)) {
		$result = $oRss->saveRSSUrl($newRssUrl,$setstarred);
        if($result == false) {
			echo $mod_strings['UNABLE_TO_SAVE'] ;
		} else {
			echo $result;
		}
	} else {
		echo $mod_strings['NOT_A_VALID'];
	}
	
}

