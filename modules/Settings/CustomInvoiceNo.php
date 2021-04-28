<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

//crmv@8056
require_once('user_privileges/CustomQuoteNo.php');
require_once('user_privileges/CustomPorderNo.php');
require_once('user_privileges/CustomSorderNo.php');
require_once('user_privileges/CustomInvoiceNo.php');
require_once('user_privileges/CustomNoteNo.php'); //vtc

global $app_strings;
global $mod_strings;
global $currentModule;
global $theme;
$theme_path="themes/".$theme."/";
$image_path=$theme_path."images/";
global $current_language;

$smarty = new VteSmarty();

$smarty->assign("MOD", return_module_language($current_language,'Settings'));
$smarty->assign("CMOD", $mod_strings);
$smarty->assign("APP", $app_strings);
$smarty->assign("IMAGE_PATH",$image_path);

/*
if($singlepane_view == 'true')
	$viewstatus = 'enabled';
else
	$viewstatus = 'disabled';

$smarty->assign("ViewStatus", $viewstatus);
*/

$smarty->assign("inv_str", $inv_str);
$smarty->assign("inv_no", $inv_no);

$smarty->assign("quote_str", $quote_str);
$smarty->assign("quote_no", $quote_no);

$smarty->assign("porder_str", $porder_str);
$smarty->assign("porder_no", $porder_no);

$smarty->assign("sorder_str", $sorder_str);
$smarty->assign("sorder_no", $sorder_no);

//vtc
$smarty->assign("note_str", $note_str);
$smarty->assign("note_no", $note_no);
//vtc e

$smarty->display('Settings/CustomInvoiceNo.tpl');
//crmv@8056e
