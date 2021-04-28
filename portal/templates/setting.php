<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
// crmv@168297
//HTML Option
$site_name = $browsername;
$site_title = getTranslatedString('customerportal');
//Display Option
$wrapper_align = "center";
//Set Banner
$banner_image = "banner.jpg";
$banner_code = "<a class='banner' href='{$enterprise_website[0]}' target='_blank'><img src='templates/images/$banner_image' /></a>";
//Set Subtitles
function getSubString($substr, $language = null) {
	return getTranslatedString('LBL_'.strtoupper($substr).'_DESC');
}
?>