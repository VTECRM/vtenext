<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
// Turn on debugging level

function setTicketStatusPicklistValues($values) {
	//crmv@64964
	$fieldInstance = Vtecrm_Field::getInstance('ticketstatus',Vtecrm_Module::getInstance('HelpDesk'));
	$fieldInstance->setPicklistValues($values);
	//crmv@64964e
}
setTicketStatusPicklistValues( Array ('Maintain') );

$moduleHD = Vtecrm_Module::getInstance('HelpDesk');
$moduleTC = Vtecrm_Module::getInstance('Timecards');
$moduleHD->setRelatedList($moduleTC, 'Timecards', Array('ADD'),'get_timecards');

//$moduleAccounts = Vtecrm_Module::getInstance('Accounts');
//$moduleAccounts->setRelatedList($module, 'Timecards', Array('ADD','SELECT'));

//crmv fix ticketstatus
$adb->pquery("update ".$table_prefix."_field set uitype = ? where tablename = ? and fieldname = ?",
				Array(15, $table_prefix.'_timecards', 'ticketstatus'));