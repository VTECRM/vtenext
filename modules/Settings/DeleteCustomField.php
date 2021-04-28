<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@158543 */
/* This file is obsolete and now it's used only for the calendar module. It will be removed in the future */


if ($_REQUEST["fld_module"] != 'Calendar') die('Module not permitted');

require_once('modules/Settings/LayoutBlockListUtils.php');
deleteCustomField(); // parameters are passed in the request

// redirect to the list
header("Location:index.php?module=Settings&action=CustomFieldList&fld_module=".$fld_module."&parenttab=Settings");