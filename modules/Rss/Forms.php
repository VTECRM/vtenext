<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

require_once("modules/Rss/Rss.php");

function get_rssfeeds_form() {

	$oRss = new VteRSS();
	$allrsshtml = $oRss->getRSSCategoryHTML();

	$the_form = '<table width="100%" border="0" cellspacing="2" cellpadding="0" style="margin-top:10px">';
	$the_form .= $allrsshtml;
	$the_form .= "</table>";
	
	return $the_form;
}
