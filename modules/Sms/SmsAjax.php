<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com>
 * SPDX-License-Identifier: AGPL-3.0-only
 ************************************/

require_once('modules/Sms/Sms.php');
global $adb;

$local_log =& LoggerManager::getLogger('SmsAjax');

$ajaxaction = $_REQUEST["ajxaction"];
if($ajaxaction == "DETAILVIEW")
{
    $crmid = $_REQUEST["recordid"];
    $tablename = $_REQUEST["tableName"];
    $fieldname = $_REQUEST["fldName"];
    $fieldvalue = $_REQUEST["fieldValue"];
    if($crmid != "")
    {
        $modObj = CRMEntity::getInstance('Sms');
        $modObj->retrieve_entity_info($crmid,"Sms");
        $modObj->column_fields[$fieldname] = $fieldvalue;
        $modObj->id = $crmid;
        $modObj->mode = "edit";
        $modObj->save("Sms");
        if($modObj->id != "")
        {
            echo ":#:SUCCESS";
        }else
        {
            echo ":#:FAILURE";
        }
    }else
    {
        echo ":#:FAILURE";
    }
}
elseif($_REQUEST['ajaxmode'] == 'qcreate')
{
    require_once('quickcreate.php');
}
else
{
    require_once('include/Ajax/CommonAjax.php');
}