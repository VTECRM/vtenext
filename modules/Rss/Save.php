<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
require_once('modules/Rss/Rss.php');

global $mod_strings;

if (isset($_REQUEST["rssurl"])) $newRssUrl = $_REQUEST["rssurl"];

$oRss = new VteRSS();
if($oRss->setRSSUrl($newRssUrl)) {
	if($oRss->saveRSSUrl($newRssUrl) == false) {
		echo $mod_strings['UNABLE_TO_SAVE'];
	}
} else {
	echo $mod_strings['INVALID_RSS_URL'];
}
