<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

global $theme;
$theme_path="themes/".$theme."/";
$image_path=$theme_path."images/";
$smarty = new VteSmarty();

$smarty->assign("THEME", $theme);
$smarty->assign("IMAGEPATH",$image_path);
$smarty->display("Clock.tpl");