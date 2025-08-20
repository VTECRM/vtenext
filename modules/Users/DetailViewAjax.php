<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
// crmv@67410

global $currentModule, $current_user;
$modObj = CRMEntity::getInstance($currentModule);

$ajaxaction = $_REQUEST["ajxaction"];
if($ajaxaction == "DETAILVIEW")
{
	$crmid = $_REQUEST["recordid"];
	$tablename = $_REQUEST["tableName"];
	$fieldname = $_REQUEST["fldName"];
	
	// crmv@341228: moved to Users::filterOrDenySave()
	
	$fieldvalue = utf8RawUrlDecode($_REQUEST["fieldValue"]); 
	if($crmid != "")
	{
	    //crmv@69568
        $modObj->retrieve_entity_info($crmid,$currentModule);

        // crmv@42024 - translate separators
        if ($fieldname == 'decimal_separator' || $fieldname == 'thousands_separator')
            $fieldvalue = $modObj->convertToSeparatorValue($fieldvalue);
        // crmv@42024e

        $modObj->column_fields[$fieldname] = $fieldvalue;

        if($fieldname == 'internal_mailer') {
            if(VteSession::hasKey('internal_mailer') && VteSession::get('internal_mailer') != $modObj->column_fields['internal_mailer'])
                VteSession::set('internal_mailer', $modObj->column_fields['internal_mailer']);
        }

        $modObj->id = $crmid;
        $modObj->mode = "edit";
	
		// crmv@341228
		if (!$modObj->filterOrDenySave()) {
			RequestHandler::outputRedirect("index.php?module=Users&action=Logout"); // crmv@150748
			exit;
		}
		$modObj->save($currentModule);
		// crmv@341228e
        if($modObj->id != "") {
			echo ":#:SUCCESS";
        } else {
            echo ":#:FAILURE";
        }
        //crmv@69568e
	} else {
		echo ":#:FAILURE";
	}
} elseif($ajaxaction == "LOADRELATEDLIST" || $ajaxaction == "DISABLEMODULE"){
	require_once 'include/ListView/RelatedListViewContents.php';
}
?>
