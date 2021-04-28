<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@158543 */
/* This file is obsolete and now used only for the calendar module. It will be removed in the future */

require_once('modules/Settings/LayoutBlockListUtils.php');

$fldmodule=vtlib_purify($_REQUEST['fld_module']);
$blockid = vtlib_purify($_REQUEST['blockid']);
$fldlabel=vtlib_purify(trim($_REQUEST['fldLabel_'.$blockid]));
$fldType= vtlib_purify($_REQUEST['fieldType_'.$blockid]);

$parenttab=getParentTab();

// alter some values to conform the expected input
$_REQUEST['fldLabel'] = $fldlabel;
$_REQUEST['fieldType'] = $fldType;
if(isset($_REQUEST['fldLength_'.$blockid])) $_REQUEST['fldLength'] = $_REQUEST['fldLength_'.$blockid];
if(isset($_REQUEST['fldDecimal_'.$blockid])) $_REQUEST['fldDecimal'] = $_REQUEST['fldDecimal_'.$blockid];
if(isset($_REQUEST['fldPickList_'.$blockid])) $_REQUEST['fldPickList'] = $_REQUEST['fldPickList_'.$blockid];

$error = addCustomField();

if ($error) {
	header("Location:index.php?module=Settings&action=CustomFieldList&fld_module=".$fldmodule."&fldType=".$fldType."&fldlabel=".$fldlabel."&parenttab=".$parenttab."&duplicate=yes");
} else {
	header("Location:index.php?module=Settings&action=CustomFieldList&fld_module=".$fldmodule."&parenttab=".$parenttab);
}