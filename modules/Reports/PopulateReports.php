<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

require 'modules/Reports/Reports.php';


/* crmv@97862 crmv@100399 crmv@205568 */

if (!function_exists('getFieldIdFromName')) {
	function getFieldIdFromName($module, $fieldname) {
		global $adb, $table_prefix;
		static $fieldCache = array();
		if (!isset($fieldCache[$module][$fieldname])) {
			$res = $adb->pquery("select fieldid from {$table_prefix}_field
				inner join {$table_prefix}_tab on {$table_prefix}_field.tabid = {$table_prefix}_tab.tabid
				where {$table_prefix}_tab.name = ? and {$table_prefix}_field.fieldname = ?",
				array($module, $fieldname));
			if ($res && $adb->num_rows($res) > 0) {
				$row = $adb->FetchByAssoc($res, -1, false);
				$fieldCache[$module][$fieldname] = $row['fieldid'];
			}
		}
		return $fieldCache[$module][$fieldname];
	}
}

global $adb,$table_prefix;

$rptfolder = Array(
	Array('Account and Contact Reports'=>'Account and Contact Reports'),
	Array('Lead Reports'=>'Lead Reports'),
	Array('Potential Reports'=>'Potential Reports'),
	Array('Activity Reports'=>'Activity Reports'),
	Array('HelpDesk Reports'=>'HelpDesk Reports'),
	Array('Product Reports'=>'Product Reports'),
	Array('Quote Reports' =>'Quote Reports'),
	Array('PurchaseOrder Reports'=>'PurchaseOrder Reports'),
	Array('Invoice Reports'=>'Invoice Reports'),
	Array('SalesOrder Reports'=>'SalesOrder Reports'),
	Array('Campaign Reports'=>'Campaign Reports'),
	//crmv@30976
	Array('LBL_REPORT_FOLDER_EXAMPLES'=>'LBL_REPORT_FOLDER_EXAMPLES_DESC'),
	//crmv@30976
	//crmv@30967
	Array('LBL_REPORT_FOLDER_PROJECTS'=>''),
	//crmv@30967e
);

$all_reports = array(
array(
	'folderid' => 'Account and Contact Reports',
	'reportname' => 'Contacts by Accounts',
	'description' => 'Contacts related to Accounts',
	'reporttype' => 'tabular',
	'module' => 'Contacts',
	'relations' => array(
	array(
		'name' => 'Contacts',
		'module' => 'Contacts',
	),
	array(
		'module' => 'Accounts',
		'name' => 'Contacts_Accounts_fld_'.getFieldIdFromName('Contacts','account_id'),
		'type' => 4,
		'fieldid' => 'Contacts::account_id',
		'parent' => 'Contacts',
	),
	),
	'fields' => array(
	array(
		'fieldid' => 'Contacts::firstname',
	),
	array(
		'fieldid' => 'Contacts::lastname',
	),
	array(
		'fieldid' => 'Contacts::leadsource',
	),
	array(
		'fieldid' => 'Contacts::account_id',
	),
	array(
		'fieldid' => 'Accounts::industry',
		'relation' => 'Contacts_Accounts_fld_'.getFieldIdFromName('Contacts','account_id'),
	),
	array(
		'fieldid' => 'Contacts::email',
	),
	),
),
array(
	'folderid' => 'Account and Contact Reports',
	'reportname' => 'Contacts without Accounts',
	'description' => 'Contacts not related to Accounts',
	'reporttype' => 'tabular',
	'module' => 'Contacts',
	'relations' => array(
	array(
		'name' => 'Contacts',
		'module' => 'Contacts',
	),
	array(
		'name' => 'Contacts_Accounts_fld_'.getFieldIdFromName('Contacts','account_id'),
		'module' => 'Accounts',
		'type' => 4,
		'parent' => 'Contacts',
		'fieldid' => 'Contacts::account_id',
	),
	),
	'fields' => array(
	array(
		'fieldid' => 'Contacts::firstname',
		'module' => 'Contacts',
		'fieldname' => 'firstname',
	),
	array(
		'fieldid' => 'Contacts::lastname',
		'module' => 'Contacts',
		'fieldname' => 'lastname',
	),
	array(
		'fieldid' => 'Contacts::leadsource',
		'module' => 'Contacts',
		'fieldname' => 'leadsource',
	),
	array(
		'fieldid' => 'Contacts::account_id',
		'module' => 'Contacts',
		'fieldname' => 'account_id',
	),
	array(
		'fieldid' => 'Accounts::industry',
		'module' => 'Accounts',
		'fieldname' => 'industry',
		'relation' => 'Contacts_Accounts_fld_'.getFieldIdFromName('Contacts','account_id'),
	),
	array(
		'fieldid' => 'Contacts::email',
		'module' => 'Contacts',
		'fieldname' => 'email',
	),
	),
	'advfilters' => array(
	array(
		'conditions' => array(
		array(
			'fieldid' => 'Contacts::account_id',
			'comparator' => 'e',
			'value' => '',
			'glue' => 'and',
		),
		),
	),
	),
),
array(
	'folderid' => 'Account and Contact Reports',
	'reportname' => 'Contacts by Potentials',
	'description' => 'Contacts related to Potentials',
	'reporttype' => 'tabular',
	'module' => 'Contacts',
	'relations' => array(
	array(
		'name' => 'Contacts',
		'module' => 'Contacts',
	),
	array(
		'name' => 'Contacts_Potentials_fld_'.getFieldIdFromName('Potentials','related_to'),
		'module' => 'Potentials',
		'type' => 2,
		'parent' => 'Contacts',
		'fieldid' => 'Potentials::related_to',
		'relationid' => '15',
	),
	),
	'fields' => array(
	array(
		'fieldid' => 'Contacts::firstname',
		'module' => 'Contacts',
		'fieldname' => 'firstname',
	),
	array(
		'fieldid' => 'Contacts::lastname',
		'module' => 'Contacts',
		'fieldname' => 'lastname',
	),
	array(
		'fieldid' => 'Contacts::account_id',
		'module' => 'Contacts',
		'fieldname' => 'account_id',
	),
	array(
		'fieldid' => 'Contacts::email',
		'module' => 'Contacts',
		'fieldname' => 'email',
	),
	array(
		'fieldid' => 'Potentials::potentialname',
		'module' => 'Potentials',
		'fieldname' => 'potentialname',
		'relation' => 'Contacts_Potentials_fld_'.getFieldIdFromName('Potentials','related_to'),
	),
	array(
		'fieldid' => 'Potentials::sales_stage',
		'module' => 'Potentials',
		'fieldname' => 'sales_stage',
		'relation' => 'Contacts_Potentials_fld_'.getFieldIdFromName('Potentials','related_to'),
	),
	),
	'advfilters' => array(
	array(
		'conditions' => array(
		array(
			'fieldid' => 'Potentials::potentialname',
			'comparator' => 'n',
			'relation' => 'Contacts_Potentials_fld_'.getFieldIdFromName('Potentials','related_to'),
			'value' => '',
			'glue' => 'and',
		),
		),
	),
	),
),
array(
	'folderid' => 'Lead Reports',
	'reportname' => 'Lead by Source',
	'description' => 'Lead by Source',
	'reporttype' => 'summary',
	'module' => 'Leads',
	'relations' => array(
	array(
		'name' => 'Leads',
		'module' => 'Leads',
	),
	),
	'fields' => array(
	array(
		'fieldid' => 'Leads::leadsource',
		'module' => 'Leads',
		'fieldname' => 'leadsource',
		'group' => true,
		'sortorder' => 'DESC',
	),
	array(
		'fieldid' => 'Leads::firstname',
		'module' => 'Leads',
		'fieldname' => 'firstname',
	),
	array(
		'fieldid' => 'Leads::lastname',
		'module' => 'Leads',
		'fieldname' => 'lastname',
	),
	array(
		'fieldid' => 'Leads::company',
		'module' => 'Leads',
		'fieldname' => 'company',
	),
	array(
		'fieldid' => 'Leads::email',
		'module' => 'Leads',
		'fieldname' => 'email',
	),
	),
),
array(
	'folderid' => 'Lead Reports',
	'reportname' => 'Lead Status Report',
	'description' => 'Lead Status Report',
	'reporttype' => 'summary',
	'module' => 'Leads',
	'relations' => array(
	array(
		'name' => 'Leads',
		'module' => 'Leads',
	),
	),
	'fields' => array(
	array(
		'fieldid' => 'Leads::leadstatus',
		'module' => 'Leads',
		'fieldname' => 'leadstatus',
		'group' => true,
		'sortorder' => 'DESC',
	),
	array(
		'fieldid' => 'Leads::firstname',
		'module' => 'Leads',
		'fieldname' => 'firstname',
	),
	array(
		'fieldid' => 'Leads::lastname',
		'module' => 'Leads',
		'fieldname' => 'lastname',
	),
	array(
		'fieldid' => 'Leads::company',
		'module' => 'Leads',
		'fieldname' => 'company',
	),
	array(
		'fieldid' => 'Leads::email',
		'module' => 'Leads',
		'fieldname' => 'email',
	),
	array(
		'fieldid' => 'Leads::leadsource',
		'module' => 'Leads',
		'fieldname' => 'leadsource',
	),
	),
),
array(
	'folderid' => 'Potential Reports',
	'reportname' => 'Potential Pipeline',
	'description' => 'Potential Pipeline',
	'reporttype' => 'summary',
	'module' => 'Potentials',
	'relations' => array(
	array(
		'name' => 'Potentials',
		'module' => 'Potentials',
	),
	),
	'fields' => array(
	array(
		'fieldid' => 'Potentials::sales_stage',
		'module' => 'Potentials',
		'fieldname' => 'sales_stage',
		'group' => true,
		'sortorder' => 'DESC',
	),
	array(
		'fieldid' => 'Potentials::potentialname',
		'module' => 'Potentials',
		'fieldname' => 'potentialname',
	),
	array(
		'fieldid' => 'Potentials::amount',
		'module' => 'Potentials',
		'fieldname' => 'amount',
	),
	array(
		'fieldid' => 'Potentials::opportunity_type',
		'module' => 'Potentials',
		'fieldname' => 'opportunity_type',
	),
	array(
		'fieldid' => 'Potentials::leadsource',
		'module' => 'Potentials',
		'fieldname' => 'leadsource',
	),
	),
),
array(
	'folderid' => 'Potential Reports',
	'reportname' => 'Closed Potentials',
	'description' => 'Potential that have Won',
	'reporttype' => 'tabular',
	'module' => 'Potentials',
	'relations' => array(
	array(
		'name' => 'Potentials',
		'module' => 'Potentials',
	),
	),
	'fields' => array(
	array(
		'fieldid' => 'Potentials::potentialname',
		'module' => 'Potentials',
		'fieldname' => 'potentialname',
	),
	array(
		'fieldid' => 'Potentials::amount',
		'module' => 'Potentials',
		'fieldname' => 'amount',
	),
	array(
		'fieldid' => 'Potentials::opportunity_type',
		'module' => 'Potentials',
		'fieldname' => 'opportunity_type',
	),
	array(
		'fieldid' => 'Potentials::leadsource',
		'module' => 'Potentials',
		'fieldname' => 'leadsource',
	),
	array(
		'fieldid' => 'Potentials::sales_stage',
		'module' => 'Potentials',
		'fieldname' => 'sales_stage',
	),
	),
	'advfilters' => array(
	array(
		'conditions' => array(
		array(
			'fieldid' => 'Potentials::sales_stage',
			'comparator' => 'e',
			'value' => 'Closed Won',
			'glue' => 'and',
		),
		),
	),
	),
),
array(
	'folderid' => 'Activity Reports',
	'reportname' => 'Last Month Activities',
	'description' => 'Last Month Activities',
	'reporttype' => 'tabular',
	'module' => 'Events',
	'relations' => array(
	array(
		'name' => 'Events',
		'module' => 'Events',
	),
	),
	'fields' => array(
	array(
		'fieldid' => 'Events::subject',
		'module' => 'Events',
		'fieldname' => 'subject',
	),
	array(
		'fieldid' => 'Events::contact_id',
		'module' => 'Events',
		'fieldname' => 'contact_id',
	),
	array(
		'fieldid' => 'Events::eventstatus',
		'module' => 'Events',
		'fieldname' => 'eventstatus',
	),
	array(
		'fieldid' => 'Events::taskpriority',
		'module' => 'Events',
		'fieldname' => 'taskpriority',
	),
	array(
		'fieldid' => 'Events::assigned_user_id',
		'module' => 'Events',
		'fieldname' => 'assigned_user_id',
	),
	),
	'stdfilters' => array(
	array(
		'fieldid' => 'Events::modifiedtime',
		'type' => 'datefilter',
		'value' => 'lastmonth',
		'startdate' => NULL,
		'enddate' => NULL,
	),
	),
),
array(
	'folderid' => 'Activity Reports',
	'reportname' => 'This Month Activities',
	'description' => 'This Month Activities',
	'reporttype' => 'tabular',
	'module' => 'Events',
	'relations' => array(
	array(
		'name' => 'Events',
		'module' => 'Events',
	),
	),
	'fields' => array(
	array(
		'fieldid' => 'Events::subject',
		'module' => 'Events',
		'fieldname' => 'subject',
	),
	array(
		'fieldid' => 'Events::contact_id',
		'module' => 'Events',
		'fieldname' => 'contact_id',
	),
	array(
		'fieldid' => 'Events::eventstatus',
		'module' => 'Events',
		'fieldname' => 'eventstatus',
	),
	array(
		'fieldid' => 'Events::taskpriority',
		'module' => 'Events',
		'fieldname' => 'taskpriority',
	),
	array(
		'fieldid' => 'Events::assigned_user_id',
		'module' => 'Events',
		'fieldname' => 'assigned_user_id',
	),
	),
	'stdfilters' => array(
	array(
		'fieldid' => 'Events::modifiedtime',
		'type' => 'datefilter',
		'value' => 'thismonth',
		'startdate' => NULL,
		'enddate' => NULL,
	),
	),
),
array(
	'folderid' => 'Activity Reports',
	'reportname' => 'This Month Tasks',
	'description' => 'This Month Tasks',
	'reporttype' => 'tabular',
	'module' => 'Calendar',
	'relations' => array(
	array(
		'name' => 'Calendar',
		'module' => 'Calendar',
	),
	),
	'fields' => array(
	array(
		'fieldid' => 'Calendar::subject',
		'module' => 'Calendar',
		'fieldname' => 'subject',
	),
	array(
		'fieldid' => 'Calendar::taskstatus',
		'module' => 'Calendar',
		'fieldname' => 'taskstatus',
	),
	array(
		'fieldid' => 'Calendar::taskpriority',
		'module' => 'Calendar',
		'fieldname' => 'taskpriority',
	),
	array(
		'fieldid' => 'Calendar::assigned_user_id',
		'module' => 'Calendar',
		'fieldname' => 'assigned_user_id',
	),
	),
	'stdfilters' => array(
	array(
		'fieldid' => 'Calendar::modifiedtime',
		'type' => 'datefilter',
		'value' => 'thismonth',
		'startdate' => NULL,
		'enddate' => NULL,
	),
	),
),
array(
	'folderid' => 'Activity Reports',
	'reportname' => 'Current Month Tracked Activities',
	'description' => 'Current Month Tracked Activities',
	'reporttype' => 'tabular',
	'module' => 'Events',
	'relations' => array(
	array(
		'name' => 'Events',
		'module' => 'Events',
	),
	),
	'fields' => array(
	array(
		'fieldid' => 'Events::subject',
		'module' => 'Events',
		'fieldname' => 'subject',
	),
	array(
		'fieldid' => 'Events::date_start',
		'module' => 'Events',
		'fieldname' => 'date_start',
	),
	array(
		'fieldid' => 'Events::due_date',
		'module' => 'Events',
		'fieldname' => 'due_date',
	),
	array(
		'fieldid' => 'Events::time_end',
		'module' => 'Events',
		'fieldname' => 'time_end',
	),
	array(
		'fieldid' => 'Events::assigned_user_id',
		'module' => 'Events',
		'fieldname' => 'assigned_user_id',
	),
	array(
		'fieldid' => 'Events::parent_id',
		'module' => 'Events',
		'fieldname' => 'parent_id',
	),
	),
	'stdfilters' => array(
	array(
		'fieldid' => 'Events::date_start',
		'type' => 'datefilter',
		'value' => 'thismonth',
		'startdate' => NULL,
		'enddate' => NULL,
	),
	),
	'advfilters' => array(
	array(
		'conditions' => array(
		array(
			'fieldid' => 'Events::activitytype',
			'comparator' => 'e',
			'value' => 'Tracked',
			'glue' => 'and',
		),
		),
	),
	),
),
array(
	'folderid' => 'HelpDesk Reports',
	'reportname' => 'Tickets by Products',
	'description' => 'Tickets related to Products',
	'reporttype' => 'tabular',
	'module' => 'HelpDesk',
	'relations' => array(
	array(
		'name' => 'HelpDesk',
		'module' => 'HelpDesk',
	),
	array(
		'name' => 'HelpDesk_Products_fld_'.getFieldIdFromName('HelpDesk','product_id'),
		'module' => 'Products',
		'type' => 4,
		'parent' => 'HelpDesk',
		'fieldid' => 'HelpDesk::product_id',
	),
	),
	'fields' => array(
	array(
		'fieldid' => 'HelpDesk::ticket_title',
		'module' => 'HelpDesk',
		'fieldname' => 'ticket_title',
	),
	array(
		'fieldid' => 'HelpDesk::ticketstatus',
		'module' => 'HelpDesk',
		'fieldname' => 'ticketstatus',
	),
	array(
		'fieldid' => 'Products::productname',
		'module' => 'Products',
		'fieldname' => 'productname',
		'relation' => 'HelpDesk_Products_fld_'.getFieldIdFromName('HelpDesk','product_id'),
	),
	array(
		'fieldid' => 'Products::discontinued',
		'module' => 'Products',
		'fieldname' => 'discontinued',
		'relation' => 'HelpDesk_Products_fld_'.getFieldIdFromName('HelpDesk','product_id'),
	),
	array(
		'fieldid' => 'Products::productcategory',
		'module' => 'Products',
		'fieldname' => 'productcategory',
		'relation' => 'HelpDesk_Products_fld_'.getFieldIdFromName('HelpDesk','product_id'),
	),
	array(
		'fieldid' => 'Products::manufacturer',
		'module' => 'Products',
		'fieldname' => 'manufacturer',
		'relation' => 'HelpDesk_Products_fld_'.getFieldIdFromName('HelpDesk','product_id'),
	),
	),
),
array(
	'folderid' => 'HelpDesk Reports',
	'reportname' => 'Tickets by Priority',
	'description' => 'Tickets by Priority',
	'reporttype' => 'summary',
	'module' => 'HelpDesk',
	'relations' => array(
	array(
		'name' => 'HelpDesk',
		'module' => 'HelpDesk',
	),
	),
	'fields' => array(
	array(
		'fieldid' => 'HelpDesk::ticketpriorities',
		'module' => 'HelpDesk',
		'fieldname' => 'ticketpriorities',
		'group' => true,
		'sortorder' => 'DESC',
	),
	array(
		'fieldid' => 'HelpDesk::ticket_title',
		'module' => 'HelpDesk',
		'fieldname' => 'ticket_title',
	),
	array(
		'fieldid' => 'HelpDesk::ticketseverities',
		'module' => 'HelpDesk',
		'fieldname' => 'ticketseverities',
	),
	array(
		'fieldid' => 'HelpDesk::ticketstatus',
		'module' => 'HelpDesk',
		'fieldname' => 'ticketstatus',
	),
	array(
		'fieldid' => 'HelpDesk::ticketcategories',
		'module' => 'HelpDesk',
		'fieldname' => 'ticketcategories',
	),
	array(
		'fieldid' => 'HelpDesk::assigned_user_id',
		'module' => 'HelpDesk',
		'fieldname' => 'assigned_user_id',
	),
	),
),
array(
	'folderid' => 'HelpDesk Reports',
	'reportname' => 'Open Tickets',
	'description' => 'Tickets that are Open',
	'reporttype' => 'tabular',
	'module' => 'HelpDesk',
	'relations' => array(
	array(
		'name' => 'HelpDesk',
		'module' => 'HelpDesk',
	),
	),
	'fields' => array(
	array(
		'fieldid' => 'HelpDesk::ticket_title',
		'module' => 'HelpDesk',
		'fieldname' => 'ticket_title',
	),
	array(
		'fieldid' => 'HelpDesk::ticketpriorities',
		'module' => 'HelpDesk',
		'fieldname' => 'ticketpriorities',
	),
	array(
		'fieldid' => 'HelpDesk::ticketseverities',
		'module' => 'HelpDesk',
		'fieldname' => 'ticketseverities',
	),
	array(
		'fieldid' => 'HelpDesk::ticketstatus',
		'module' => 'HelpDesk',
		'fieldname' => 'ticketstatus',
	),
	array(
		'fieldid' => 'HelpDesk::ticketcategories',
		'module' => 'HelpDesk',
		'fieldname' => 'ticketcategories',
	),
	array(
		'fieldid' => 'HelpDesk::assigned_user_id',
		'module' => 'HelpDesk',
		'fieldname' => 'assigned_user_id',
	),
	),
	'advfilters' => array(
	array(
		'conditions' => array(
		array(
			'fieldid' => 'HelpDesk::ticketstatus',
			'comparator' => 'n',
			'value' => 'Closed',
			'glue' => 'and',
		),
		),
	),
	),
),
array(
	'folderid' => 'Product Reports',
	'reportname' => 'Product Details',
	'description' => 'Product Detailed Report',
	'reporttype' => 'tabular',
	'module' => 'Products',
	'relations' => array(
	array(
		'name' => 'Products',
		'module' => 'Products',
	),
	),
	'fields' => array(
	array(
		'fieldid' => 'Products::productname',
		'module' => 'Products',
		'fieldname' => 'productname',
	),
	array(
		'fieldid' => 'Products::productcode',
		'module' => 'Products',
		'fieldname' => 'productcode',
	),
	array(
		'fieldid' => 'Products::discontinued',
		'module' => 'Products',
		'fieldname' => 'discontinued',
	),
	array(
		'fieldid' => 'Products::productcategory',
		'module' => 'Products',
		'fieldname' => 'productcategory',
	),
	array(
		'fieldid' => 'Products::website',
		'module' => 'Products',
		'fieldname' => 'website',
	),
	array(
		'fieldid' => 'Products::vendor_id',
		'module' => 'Products',
		'fieldname' => 'vendor_id',
	),
	array(
		'fieldid' => 'Products::mfr_part_no',
		'module' => 'Products',
		'fieldname' => 'mfr_part_no',
	),
	),
),
array(
	'folderid' => 'Product Reports',
	'reportname' => 'Products by Contacts',
	'description' => 'Products related to Contacts',
	'reporttype' => 'tabular',
	'module' => 'Products',
	'relations' => array(
	array(
		'name' => 'Products',
		'module' => 'Products',
	),
	array(
		'name' => 'Products_Contacts_rel_42',
		'module' => 'Contacts',
		'type' => 8,
		'parent' => 'Products',
		'fieldid' => NULL,
		'relationid' => '42',
	),
	),
	'fields' => array(
	array(
		'fieldid' => 'Products::productname',
		'module' => 'Products',
		'fieldname' => 'productname',
	),
	array(
		'fieldid' => 'Products::manufacturer',
		'module' => 'Products',
		'fieldname' => 'manufacturer',
	),
	array(
		'fieldid' => 'Products::productcategory',
		'module' => 'Products',
		'fieldname' => 'productcategory',
	),
	array(
		'fieldid' => 'Contacts::firstname',
		'module' => 'Contacts',
		'fieldname' => 'firstname',
		'relation' => 'Products_Contacts_rel_42',
	),
	array(
		'fieldid' => 'Contacts::lastname',
		'module' => 'Contacts',
		'fieldname' => 'lastname',
		'relation' => 'Products_Contacts_rel_42',
	),
	array(
		'fieldid' => 'Contacts::leadsource',
		'module' => 'Contacts',
		'fieldname' => 'leadsource',
		'relation' => 'Products_Contacts_rel_42',
	),
	),
),
array(
	'folderid' => 'Quote Reports',
	'reportname' => 'Open Quotes',
	'description' => 'Quotes that are Open',
	'reporttype' => 'tabular',
	'module' => 'Quotes',
	'relations' => array(
	array(
		'name' => 'Quotes',
		'module' => 'Quotes',
	),
	),
	'fields' => array(
	array(
		'fieldid' => 'Quotes::subject',
		'module' => 'Quotes',
		'fieldname' => 'subject',
	),
	array(
		'fieldid' => 'Quotes::potential_id',
		'module' => 'Quotes',
		'fieldname' => 'potential_id',
	),
	array(
		'fieldid' => 'Quotes::quotestage',
		'module' => 'Quotes',
		'fieldname' => 'quotestage',
	),
	array(
		'fieldid' => 'Quotes::contact_id',
		'module' => 'Quotes',
		'fieldname' => 'contact_id',
	),
	array(
		'fieldid' => 'Quotes::assigned_user_id1',
		'module' => 'Quotes',
		'fieldname' => 'assigned_user_id1',
	),
	array(
		'fieldid' => 'Quotes::account_id',
		'module' => 'Quotes',
		'fieldname' => 'account_id',
	),
	),
	'advfilters' => array(
	array(
		'conditions' => array(
		array(
			'fieldid' => 'Quotes::quotestage',
			'comparator' => 'n',
			'value' => 'Accepted',
			'glue' => 'and',
		),
		array(
			'fieldid' => 'Quotes::quotestage',
			'comparator' => 'n',
			'value' => 'Rejected',
			'glue' => 'and',
		),
		),
	),
	),
),
array(
	'folderid' => 'Quote Reports',
	'reportname' => 'Quotes Detailed Report',
	'description' => 'Quotes Detailed Report',
	'reporttype' => 'tabular',
	'module' => 'Quotes',
	'relations' => array(
	array(
		'name' => 'Quotes',
		'module' => 'Quotes',
	),
	),
	'fields' => array(
	array(
		'fieldid' => 'Quotes::subject',
		'module' => 'Quotes',
		'fieldname' => 'subject',
	),
	array(
		'fieldid' => 'Quotes::potential_id',
		'module' => 'Quotes',
		'fieldname' => 'potential_id',
	),
	array(
		'fieldid' => 'Quotes::quotestage',
		'module' => 'Quotes',
		'fieldname' => 'quotestage',
	),
	array(
		'fieldid' => 'Quotes::contact_id',
		'module' => 'Quotes',
		'fieldname' => 'contact_id',
	),
	array(
		'fieldid' => 'Quotes::assigned_user_id1',
		'module' => 'Quotes',
		'fieldname' => 'assigned_user_id1',
	),
	array(
		'fieldid' => 'Quotes::account_id',
		'module' => 'Quotes',
		'fieldname' => 'account_id',
	),
	array(
		'fieldid' => 'Quotes::carrier',
		'module' => 'Quotes',
		'fieldname' => 'carrier',
	),
	array(
		'fieldid' => 'Quotes::shipping',
		'module' => 'Quotes',
		'fieldname' => 'shipping',
	),
	),
),
array(
	'folderid' => 'PurchaseOrder Reports',
	'reportname' => 'PurchaseOrder by Contacts',
	'description' => 'PurchaseOrder related to Contacts',
	'reporttype' => 'tabular',
	'module' => 'PurchaseOrder',
	'relations' => array(
	array(
		'name' => 'PurchaseOrder',
		'module' => 'PurchaseOrder',
	),
	array(
		'name' => 'PurchaseOrder_Contacts_fld_'.getFieldIdFromName('PurchaseOrder','contact_id'),
		'module' => 'Contacts',
		'type' => 4,
		'parent' => 'PurchaseOrder',
		'fieldid' => 'PurchaseOrder::contact_id',
	),
	),
	'fields' => array(
	array(
		'fieldid' => 'PurchaseOrder::subject',
		'module' => 'PurchaseOrder',
		'fieldname' => 'subject',
	),
	array(
		'fieldid' => 'PurchaseOrder::vendor_id',
		'module' => 'PurchaseOrder',
		'fieldname' => 'vendor_id',
	),
	array(
		'fieldid' => 'PurchaseOrder::tracking_no',
		'module' => 'PurchaseOrder',
		'fieldname' => 'tracking_no',
	),
	array(
		'fieldid' => 'Contacts::firstname',
		'module' => 'Contacts',
		'fieldname' => 'firstname',
		'relation' => 'PurchaseOrder_Contacts_fld_'.getFieldIdFromName('PurchaseOrder','contact_id'),
	),
	array(
		'fieldid' => 'Contacts::lastname',
		'module' => 'Contacts',
		'fieldname' => 'lastname',
		'relation' => 'PurchaseOrder_Contacts_fld_'.getFieldIdFromName('PurchaseOrder','contact_id'),
	),
	array(
		'fieldid' => 'Contacts::leadsource',
		'module' => 'Contacts',
		'fieldname' => 'leadsource',
		'relation' => 'PurchaseOrder_Contacts_fld_'.getFieldIdFromName('PurchaseOrder','contact_id'),
	),
	array(
		'fieldid' => 'Contacts::email',
		'module' => 'Contacts',
		'fieldname' => 'email',
		'relation' => 'PurchaseOrder_Contacts_fld_'.getFieldIdFromName('PurchaseOrder','contact_id'),
	),
	),
),
array(
	'folderid' => 'PurchaseOrder Reports',
	'reportname' => 'PurchaseOrder Detailed Report',
	'description' => 'PurchaseOrder Detailed Report',
	'reporttype' => 'tabular',
	'module' => 'PurchaseOrder',
	'relations' => array(
	array(
		'name' => 'PurchaseOrder',
		'module' => 'PurchaseOrder',
	),
	),
	'fields' => array(
	array(
		'fieldid' => 'PurchaseOrder::subject',
		'module' => 'PurchaseOrder',
		'fieldname' => 'subject',
	),
	array(
		'fieldid' => 'PurchaseOrder::vendor_id',
		'module' => 'PurchaseOrder',
		'fieldname' => 'vendor_id',
	),
	array(
		'fieldid' => 'PurchaseOrder::requisition_no',
		'module' => 'PurchaseOrder',
		'fieldname' => 'requisition_no',
	),
	array(
		'fieldid' => 'PurchaseOrder::tracking_no',
		'module' => 'PurchaseOrder',
		'fieldname' => 'tracking_no',
	),
	array(
		'fieldid' => 'PurchaseOrder::contact_id',
		'module' => 'PurchaseOrder',
		'fieldname' => 'contact_id',
	),
	array(
		'fieldid' => 'PurchaseOrder::carrier',
		'module' => 'PurchaseOrder',
		'fieldname' => 'carrier',
	),
	array(
		'fieldid' => 'PurchaseOrder::salescommission',
		'module' => 'PurchaseOrder',
		'fieldname' => 'salescommission',
	),
	array(
		'fieldid' => 'PurchaseOrder::exciseduty',
		'module' => 'PurchaseOrder',
		'fieldname' => 'exciseduty',
	),
	array(
		'fieldid' => 'PurchaseOrder::assigned_user_id',
		'module' => 'PurchaseOrder',
		'fieldname' => 'assigned_user_id',
	),
	),
),
array(
	'folderid' => 'Invoice Reports',
	'reportname' => 'Invoice Detailed Report',
	'description' => 'Invoice Detailed Report',
	'reporttype' => 'tabular',
	'module' => 'Invoice',
	'relations' => array(
	array(
		'name' => 'Invoice',
		'module' => 'Invoice',
	),
	),
	'fields' => array(
	array(
		'fieldid' => 'Invoice::subject',
		'module' => 'Invoice',
		'fieldname' => 'subject',
	),
	array(
		'fieldid' => 'Invoice::salesorder_id',
		'module' => 'Invoice',
		'fieldname' => 'salesorder_id',
	),
	array(
		'fieldid' => 'Invoice::customerno',
		'module' => 'Invoice',
		'fieldname' => 'customerno',
	),
	array(
		'fieldid' => 'Invoice::exciseduty',
		'module' => 'Invoice',
		'fieldname' => 'exciseduty',
	),
	array(
		'fieldid' => 'Invoice::salescommission',
		'module' => 'Invoice',
		'fieldname' => 'salescommission',
	),
	array(
		'fieldid' => 'Invoice::account_id',
		'module' => 'Invoice',
		'fieldname' => 'account_id',
	),
	),
),
array(
	'folderid' => 'SalesOrder Reports',
	'reportname' => 'SalesOrder Detailed Report',
	'description' => 'SalesOrder Detailed Report',
	'reporttype' => 'tabular',
	'module' => 'SalesOrder',
	'relations' => array(
	array(
		'name' => 'SalesOrder',
		'module' => 'SalesOrder',
	),
	array(
		'name' => 'SalesOrder_Quotes_fld_'.getFieldIdFromName('SalesOrder','quote_id'),
		'module' => 'Quotes',
		'type' => 4,
		'parent' => 'SalesOrder',
		'fieldid' => 'SalesOrder::quote_id',
	),
	),
	'fields' => array(
	array(
		'fieldid' => 'SalesOrder::subject',
		'module' => 'SalesOrder',
		'fieldname' => 'subject',
	),
	array(
		'fieldid' => 'Quotes::subject',
		'module' => 'Quotes',
		'fieldname' => 'subject',
		'relation' => 'SalesOrder_Quotes_fld_'.getFieldIdFromName('SalesOrder','quote_id'),
	),
	array(
		'fieldid' => 'SalesOrder::contact_id',
		'module' => 'SalesOrder',
		'fieldname' => 'contact_id',
	),
	array(
		'fieldid' => 'SalesOrder::duedate',
		'module' => 'SalesOrder',
		'fieldname' => 'duedate',
	),
	array(
		'fieldid' => 'SalesOrder::carrier',
		'module' => 'SalesOrder',
		'fieldname' => 'carrier',
	),
	array(
		'fieldid' => 'SalesOrder::sostatus',
		'module' => 'SalesOrder',
		'fieldname' => 'sostatus',
	),
	array(
		'fieldid' => 'SalesOrder::account_id',
		'module' => 'SalesOrder',
		'fieldname' => 'account_id',
	),
	array(
		'fieldid' => 'SalesOrder::salescommission',
		'module' => 'SalesOrder',
		'fieldname' => 'salescommission',
	),
	array(
		'fieldid' => 'SalesOrder::exciseduty',
		'module' => 'SalesOrder',
		'fieldname' => 'exciseduty',
	),
	array(
		'fieldid' => 'SalesOrder::assigned_user_id',
		'module' => 'SalesOrder',
		'fieldname' => 'assigned_user_id',
	),
	),
),
array(
	'folderid' => 'Campaign Reports',
	'reportname' => 'Campaign Expectations and Actuals',
	'description' => 'Campaign Expectations and Actuals',
	'reporttype' => 'tabular',
	'module' => 'Campaigns',
	'relations' => array(
	array(
		'name' => 'Campaigns',
		'module' => 'Campaigns',
	),
	),
	'fields' => array(
	array(
		'fieldid' => 'Campaigns::campaignname',
		'module' => 'Campaigns',
		'fieldname' => 'campaignname',
	),
	array(
		'fieldid' => 'Campaigns::campaigntype',
		'module' => 'Campaigns',
		'fieldname' => 'campaigntype',
	),
	array(
		'fieldid' => 'Campaigns::targetaudience',
		'module' => 'Campaigns',
		'fieldname' => 'targetaudience',
	),
	array(
		'fieldid' => 'Campaigns::budgetcost',
		'module' => 'Campaigns',
		'fieldname' => 'budgetcost',
	),
	array(
		'fieldid' => 'Campaigns::actualcost',
		'module' => 'Campaigns',
		'fieldname' => 'actualcost',
	),
	array(
		'fieldid' => 'Campaigns::expectedrevenue',
		'module' => 'Campaigns',
		'fieldname' => 'expectedrevenue',
	),
	array(
		'fieldid' => 'Campaigns::expectedsalescount',
		'module' => 'Campaigns',
		'fieldname' => 'expectedsalescount',
	),
	array(
		'fieldid' => 'Campaigns::actualsalescount',
		'module' => 'Campaigns',
		'fieldname' => 'actualsalescount',
	),
	array(
		'fieldid' => 'Campaigns::assigned_user_id',
		'module' => 'Campaigns',
		'fieldname' => 'assigned_user_id',
	),
	),
),
array(
	'folderid' => 'LBL_REPORT_FOLDER_EXAMPLES',
	'reportname' => 'Activities by users',
	'description' => '',
	'reporttype' => 'summary',
	'module' => 'Events',
	'relations' => array(
	array(
		'name' => 'Events',
		'module' => 'Events',
	),
	),
	'fields' => array(
	array(
		'fieldid' => 'Events::assigned_user_id',
		'module' => 'Events',
		'fieldname' => 'assigned_user_id',
		'group' => true,
		'sortorder' => 'DESC',
		'summary' => true,
	),
	array(
		'fieldid' => 'Events::subject',
		'module' => 'Events',
		'fieldname' => 'subject',
	),
	array(
		'fieldid' => 'Events::activitytype',
		'module' => 'Events',
		'fieldname' => 'activitytype',
	),
	array(
		'fieldid' => 'Events::date_start',
		'module' => 'Events',
		'fieldname' => 'date_start',
	),
	array(
		'fieldid' => 'Events::parent_id',
		'module' => 'Events',
		'fieldname' => 'parent_id',
	),
	),
),
array(
	'folderid' => 'LBL_REPORT_FOLDER_EXAMPLES',
	'reportname' => 'Quotes by status',
	'description' => '',
	'reporttype' => 'summary',
	'module' => 'Quotes',
	'relations' => array(
	array(
		'name' => 'Quotes',
		'module' => 'Quotes',
	),
	),
	'fields' => array(
	array(
		'fieldid' => 'Quotes::quotestage',
		'group' => true,
		'sortorder' => 'ASC',
		'summary' => true,
	),
	array(
		'fieldid' => 'Quotes::subject',
	),
	array(
		'fieldid' => 'Quotes::potential_id',
	),
	array(
		'fieldid' => 'Quotes::contact_id',
	),
	array(
		'fieldid' => 'Quotes::hdnGrandTotal',
	),
	),
	'totals' => array(
	array(
		'fieldid' => 'Quotes::hdnGrandTotal',
		'aggregator' => 'SUM',
	),
	),
	'summary' => array(
	array(
		'fieldid' => 'Quotes::hdnGrandTotal',
		'aggregators' => array('SUM'),
	),
	),
),
array(
	'folderid' => 'LBL_REPORT_FOLDER_EXAMPLES',
	'reportname' => 'Created quotes by users',
	'description' => '',
	'reporttype' => 'summary',
	'module' => 'Quotes',
	'relations' => array(
	array(
		'name' => 'Quotes',
		'module' => 'Quotes',
	),
	),
	'fields' => array(
	array(
		'fieldid' => 'Quotes::assigned_user_id',
		'module' => 'Quotes',
		'fieldname' => 'assigned_user_id',
		'group' => true,
		'sortorder' => 'DESC',
		'summary' => true,
	),
	array(
		'fieldid' => 'Quotes::subject',
		'module' => 'Quotes',
		'fieldname' => 'subject',
	),
	array(
		'fieldid' => 'Quotes::potential_id',
		'module' => 'Quotes',
		'fieldname' => 'potential_id',
	),
	array(
		'fieldid' => 'Quotes::contact_id',
		'module' => 'Quotes',
		'fieldname' => 'contact_id',
	),
	array(
		'fieldid' => 'Quotes::account_id',
		'module' => 'Quotes',
		'fieldname' => 'account_id',
	),
	array(
		'fieldid' => 'Quotes::hdnGrandTotal',
		'module' => 'Quotes',
		'fieldname' => 'hdnGrandTotal',
	),
	),
	'advfilters' => array(
	array(
		'conditions' => array(
		array(
			'fieldid' => 'Quotes::quotestage',
			'comparator' => 'e',
			'value' => 'Created',
			'glue' => 'and',
		),
		),
	),
	),
	'totals' => array(
	array(
		'fieldid' => 'Quotes::hdnGrandTotal',
		'aggregator' => 'SUM',
	),
	),
	'summary' => array(
	array(
		'fieldid' => 'Quotes::hdnGrandTotal',
		'aggregators' => array('SUM'),
	),
	),
),
array(
	'folderid' => 'LBL_REPORT_FOLDER_EXAMPLES',
	'reportname' => 'Reviewed quotes by users',
	'description' => '',
	'reporttype' => 'summary',
	'module' => 'Quotes',
	'relations' => array(
	array(
		'name' => 'Quotes',
		'module' => 'Quotes',
	),
	),
	'fields' => array(
	array(
		'fieldid' => 'Quotes::assigned_user_id',
		'module' => 'Quotes',
		'fieldname' => 'assigned_user_id',
		'group' => true,
		'sortorder' => 'DESC',
		'summary' => true,
	),
	array(
		'fieldid' => 'Quotes::subject',
		'module' => 'Quotes',
		'fieldname' => 'subject',
	),
	array(
		'fieldid' => 'Quotes::potential_id',
		'module' => 'Quotes',
		'fieldname' => 'potential_id',
	),
	array(
		'fieldid' => 'Quotes::contact_id',
		'module' => 'Quotes',
		'fieldname' => 'contact_id',
	),
	array(
		'fieldid' => 'Quotes::account_id',
		'module' => 'Quotes',
		'fieldname' => 'account_id',
	),
	array(
		'fieldid' => 'Quotes::hdnGrandTotal',
		'module' => 'Quotes',
		'fieldname' => 'hdnGrandTotal',
	),
	),
	'advfilters' => array(
	array(
		'conditions' => array(
		array(
			'fieldid' => 'Quotes::quotestage',
			'comparator' => 'e',
			'value' => 'Reviewed',
			'glue' => 'and',
		),
		),
	),
	),
	'totals' => array(
	array(
		'fieldid' => 'Quotes::hdnGrandTotal',
		'aggregator' => 'SUM',
	),
	),
	'summary' => array(
	array(
		'fieldid' => 'Quotes::hdnGrandTotal',
		'aggregators' => array('SUM'),
	),
	),
),
array(
	'folderid' => 'LBL_REPORT_FOLDER_EXAMPLES',
	'reportname' => 'Accounts by users',
	'description' => '',
	'reporttype' => 'summary',
	'module' => 'Accounts',
	'relations' => array(
	array(
		'name' => 'Accounts',
		'module' => 'Accounts',
	),
	),
	'fields' => array(
	array(
		'fieldid' => 'Accounts::assigned_user_id',
		'module' => 'Accounts',
		'fieldname' => 'assigned_user_id',
		'group' => true,
		'sortorder' => 'DESC',
		'summary' => true,
	),
	array(
		'fieldid' => 'Accounts::accountname',
		'module' => 'Accounts',
		'fieldname' => 'accountname',
	),
	array(
		'fieldid' => 'Accounts::website',
		'module' => 'Accounts',
		'fieldname' => 'website',
	),
	array(
		'fieldid' => 'Accounts::industry',
		'module' => 'Accounts',
		'fieldname' => 'industry',
	),
	array(
		'fieldid' => 'Accounts::annual_revenue',
		'module' => 'Accounts',
		'fieldname' => 'annual_revenue',
	),
	array(
		'fieldid' => 'Accounts::crmv_vat_registration_number',
		'module' => 'Accounts',
		'fieldname' => 'crmv_vat_registration_number',
	),
	),
),
array(
	'folderid' => 'LBL_REPORT_FOLDER_EXAMPLES',
	'reportname' => 'Potentials by status',
	'description' => '',
	'reporttype' => 'summary',
	'module' => 'Potentials',
	'relations' => array(
	array(
		'name' => 'Potentials',
		'module' => 'Potentials',
	),
	),
	'fields' => array(
	array(
		'fieldid' => 'Potentials::sales_stage',
		'module' => 'Potentials',
		'fieldname' => 'sales_stage',
		'group' => true,
		'sortorder' => 'DESC',
		'summary' => true,
	),
	array(
		'fieldid' => 'Potentials::potentialname',
		'module' => 'Potentials',
		'fieldname' => 'potentialname',
	),
	array(
		'fieldid' => 'Potentials::related_to',
		'module' => 'Potentials',
		'fieldname' => 'related_to',
	),
	array(
		'fieldid' => 'Potentials::amount',
		'module' => 'Potentials',
		'fieldname' => 'amount',
	),
	array(
		'fieldid' => 'Potentials::assigned_user_id',
		'module' => 'Potentials',
		'fieldname' => 'assigned_user_id',
	),
	),
	'totals' => array(
	array(
		'fieldid' => 'Potentials::amount',
		'aggregator' => 'SUM',
	),
	),
	'summary' => array(
	array(
		'fieldid' => 'Potentials::amount',
		'aggregators' => array('SUM'),
	),
	),
),
array(
	'folderid' => 'LBL_REPORT_FOLDER_EXAMPLES',
	'reportname' => 'Leads count',
	'description' => '',
	'reporttype' => 'summary',
	'module' => 'Leads',
	'relations' => array(
	array(
		'name' => 'Leads',
		'module' => 'Leads',
	),
	),
	'fields' => array(
	array(
		'fieldid' => 'Leads::assigned_user_id',
		'module' => 'Leads',
		'fieldname' => 'assigned_user_id',
		'group' => true,
		'sortorder' => 'DESC',
		'summary' => true,
	),
	array(
		'fieldid' => 'Leads::firstname',
		'module' => 'Leads',
		'fieldname' => 'firstname',
	),
	array(
		'fieldid' => 'Leads::lastname',
		'module' => 'Leads',
		'fieldname' => 'lastname',
	),
	array(
		'fieldid' => 'Leads::company',
		'module' => 'Leads',
		'fieldname' => 'company',
	),
	array(
		'fieldid' => 'Leads::leadstatus',
		'module' => 'Leads',
		'fieldname' => 'leadstatus',
	),
	array(
		'fieldid' => 'Leads::leadsource',
		'module' => 'Leads',
		'fieldname' => 'leadsource',
	),
	),
),
array(
	'folderid' => 'LBL_REPORT_FOLDER_EXAMPLES',
	'reportname' => 'Contacts by users',
	'description' => '',
	'reporttype' => 'summary',
	'module' => 'Contacts',
	'relations' => array(
	array(
		'name' => 'Contacts',
		'module' => 'Contacts',
	),
	),
	'fields' => array(
	array(
		'fieldid' => 'Contacts::assigned_user_id',
		'module' => 'Contacts',
		'fieldname' => 'assigned_user_id',
		'group' => true,
		'sortorder' => 'DESC',
		'summary' => true,
	),
	array(
		'fieldid' => 'Contacts::firstname',
		'module' => 'Contacts',
		'fieldname' => 'firstname',
	),
	array(
		'fieldid' => 'Contacts::lastname',
		'module' => 'Contacts',
		'fieldname' => 'lastname',
	),
	array(
		'fieldid' => 'Contacts::email',
		'module' => 'Contacts',
		'fieldname' => 'email',
	),
	array(
		'fieldid' => 'Contacts::account_id',
		'module' => 'Contacts',
		'fieldname' => 'account_id',
	),
	),
),
array(
	'folderid' => 'LBL_REPORT_FOLDER_EXAMPLES',
	'reportname' => 'Invoice total',
	'description' => '',
	'reporttype' => 'summary',
	'module' => 'Invoice',
	'relations' => array(
	array(
		'name' => 'Invoice',
		'module' => 'Invoice',
	),
	),
	'fields' => array(
	array(
		'fieldid' => 'Invoice::assigned_user_id',
		'module' => 'Invoice',
		'fieldname' => 'assigned_user_id',
		'group' => true,
		'sortorder' => 'DESC',
		'summary' => true,
	),
	array(
		'fieldid' => 'Invoice::subject',
		'module' => 'Invoice',
		'fieldname' => 'subject',
	),
	array(
		'fieldid' => 'Invoice::account_id',
		'module' => 'Invoice',
		'fieldname' => 'account_id',
	),
	array(
		'fieldid' => 'Invoice::contact_id',
		'module' => 'Invoice',
		'fieldname' => 'contact_id',
	),
	array(
		'fieldid' => 'Invoice::invoicedate',
		'module' => 'Invoice',
		'fieldname' => 'invoicedate',
	),
	array(
		'fieldid' => 'Invoice::invoicestatus',
		'module' => 'Invoice',
		'fieldname' => 'invoicestatus',
	),
	array(
		'fieldid' => 'Invoice::hdnGrandTotal',
		'module' => 'Invoice',
		'fieldname' => 'hdnGrandTotal',
	),
	),
	'totals' => array(
	array(
		'fieldid' => 'Invoice::hdnGrandTotal',
		'aggregator' => 'SUM',
	),
	),
	'summary' => array(
	array(
		'fieldid' => 'Invoice::hdnGrandTotal',
		'aggregators' => array('SUM'),
	),
	),
),
array(
	'folderid' => 'LBL_REPORT_FOLDER_EXAMPLES',
	'reportname' => 'Invoices by status',
	'description' => '',
	'reporttype' => 'summary',
	'module' => 'Invoice',
	'relations' => array(
	array(
		'name' => 'Invoice',
		'module' => 'Invoice',
	),
	),
	'fields' => array(
	array(
		'fieldid' => 'Invoice::invoicestatus',
		'module' => 'Invoice',
		'fieldname' => 'invoicestatus',
		'group' => true,
		'sortorder' => 'DESC',
		'summary' => true,
	),
	array(
		'fieldid' => 'Invoice::subject',
		'module' => 'Invoice',
		'fieldname' => 'subject',
	),
	array(
		'fieldid' => 'Invoice::account_id',
		'module' => 'Invoice',
		'fieldname' => 'account_id',
	),
	array(
		'fieldid' => 'Invoice::invoicedate',
		'module' => 'Invoice',
		'fieldname' => 'invoicedate',
	),
	array(
		'fieldid' => 'Invoice::hdnGrandTotal',
		'module' => 'Invoice',
		'fieldname' => 'hdnGrandTotal',
	),
	),
	'totals' => array(
	array(
		'fieldid' => 'Invoice::hdnGrandTotal',
		'aggregator' => 'SUM',
	),
	),
	'summary' => array(
	array(
		'fieldid' => 'Invoice::hdnGrandTotal',
		'aggregators' => array('SUM'),
	),
	),
),
array(
	'folderid' => 'LBL_REPORT_FOLDER_EXAMPLES',
	'reportname' => 'SalesOrder by status',
	'description' => '',
	'reporttype' => 'summary',
	'module' => 'SalesOrder',
	'relations' => array(
	array(
		'name' => 'SalesOrder',
		'module' => 'SalesOrder',
	),
	),
	'fields' => array(
	array(
		'fieldid' => 'SalesOrder::sostatus',
		'module' => 'SalesOrder',
		'fieldname' => 'sostatus',
		'group' => true,
		'sortorder' => 'DESC',
		'summary' => true,
	),
	array(
		'fieldid' => 'SalesOrder::subject',
		'module' => 'SalesOrder',
		'fieldname' => 'subject',
	),
	array(
		'fieldid' => 'SalesOrder::potential_id',
		'module' => 'SalesOrder',
		'fieldname' => 'potential_id',
	),
	array(
		'fieldid' => 'SalesOrder::account_id',
		'module' => 'SalesOrder',
		'fieldname' => 'account_id',
	),
	array(
		'fieldid' => 'SalesOrder::hdnGrandTotal',
		'module' => 'SalesOrder',
		'fieldname' => 'hdnGrandTotal',
	),
	array(
		'fieldid' => 'SalesOrder::assigned_user_id',
		'module' => 'SalesOrder',
		'fieldname' => 'assigned_user_id',
	),
	),
	'totals' => array(
	array(
		'fieldid' => 'SalesOrder::hdnGrandTotal',
		'aggregator' => 'SUM',
	),
	),
	'summary' => array(
	array(
		'fieldid' => 'SalesOrder::hdnGrandTotal',
		'aggregators' => array('SUM'),
	),
	),
),
);

// create folders
$folderids = array();
foreach($rptfolder as $rptarray) {
	foreach($rptarray as $foldername=>$folderdescription) {
		$folderid = addEntityFolder('Reports', $foldername, $folderdescription, 1, 'SAVED'); // crmv@30967
		if ($folderid !== false) $folderids[$foldername] = $folderid;
	}
}

// create reports
foreach ($all_reports as $config) {
	$reports = Reports::getInstance();
	
	// convert the folderid and fieldid values
	array_walk_recursive($config, function(&$value, $key) use ($reports, $folderids) {
		if ($key == 'folderid') {
			$value = $folderids[$value];
		} elseif ($key == 'fieldid' && $value) {
			list ($module, $fieldname) = explode('::', $value);
			$finfo = $reports->getFieldInfoByName($module, $fieldname);
			$value = $finfo['fieldid'];
		}
	});
	
	// no folder found
	if (!$config['folderid']) continue;
	
	// fix for stupid bug of array_Walk
	if (isset($config['summary'][0]['aggregators']) && empty($config['summary'][0]['aggregators'][0])) {
		$config['summary'][0]['aggregators'][0] = 'SUM';
	}
	
	$config['owner'] = 1;
	$config['state'] = 'SAVED';
	$config['customizable'] = 1;
	$config['sharingtype'] = 'Public';

	$reports->insertReport($config);
}