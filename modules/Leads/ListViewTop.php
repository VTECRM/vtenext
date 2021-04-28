<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com>
 * SPDX-License-Identifier: AGPL-3.0-only
 ************************************/


/** Function to get the 5 New Leads
 *return array $values - array with the title, header and entries like  Array('Title'=>$title,'Header'=>$listview_header,'Entries'=>$listview_entries) where as listview_header and listview_entries are arrays of header and entity values which are returned from function getListViewHeader and getListViewEntries
 */
function getNewLeads($maxval, $calCnt)
{
	global $log;
	$log->debug("Entering getNewLeads() method ...");
	require_once("data/Tracker.php");
	require_once("include/utils/utils.php");

	global $table_prefix;
	global $adb;
	global $current_language;
	global $current_user;
	$currentModuleStrings = return_module_language($current_language, 'Leads');


	if ($_REQUEST['lead_view'] == '') {
		$query = "select lead_view from " . $table_prefix . "_users where id =?";
		$result = $adb->pquery($query, array($current_user->id));
		$leadView = $adb->query_result($result, 0, 'lead_view');
	} else
		$leadView = $_REQUEST['lead_view'];

	$today = date("Y-m-d", time());

	if ($leadView == 'Today') {
		$startDate = date("Y-m-d", strtotime("$today"));
	} else if ($leadView == 'Last 2 Days') {
		$startDate = date("Y-m-d", strtotime("-2  days"));
	} else if ($leadView == 'Last Week') {
		$startDate = date("Y-m-d", strtotime("-1 week"));
	}

	$listQuery = 'select ' . $table_prefix . '_leaddetails.firstname, ' . $table_prefix . '_leaddetails.lastname, ' . $table_prefix . '_leaddetails.leadid, ' . $table_prefix . '_leaddetails.company 
		from ' . $table_prefix . '_leaddetails inner join ' . $table_prefix . '_crmentity on ' . $table_prefix . '_leaddetails.leadid = ' . $table_prefix . '_crmentity.crmid 
		where ' . $table_prefix . '_crmentity.deleted =0 AND ' . $table_prefix . '_leaddetails.converted =0 AND 
		' . $table_prefix . '_leaddetails.leadstatus not in (?,?,?,?) 
		AND ' . $table_prefix . '_crmentity.createdtime >=? AND ' . $table_prefix . '_crmentity.smownerid = ?';
	$params = array("Lost Lead", "Junk Lead", $currentModuleStrings['Lost Lead'], $currentModuleStrings['Junk Lead']);
	$params[] = $startDate;
	$params[] = $current_user->id;
	if ($calCnt == 'calculateCnt') {
		$listResultRows = $adb->pquery(mkCountQuery($listQuery), $params);
		return $adb->query_result($listResultRows, 0, 'count');
	}
	$listResult = $adb->limitpQuery($listQuery, 0, $adb->sql_escape_string($maxval), $params);
	$numberOfRows = $adb->num_rows($listResult);

	$openLeadList = array();
	if ($numberOfRows > 0) {
		for ($i = 0; $i < $numberOfRows && $i < $maxval; $i++) {
			$openLeadList[] = array('leadname' => $adb->query_result($listResult, $i, 'firstname') . ' ' . $adb->query_result($listResult, $i, 'lastname'),
				'company' => $adb->query_result($listResult, $i, 'company'),
				'id' => $adb->query_result($listResult, $i, 'leadid'),
			);
		}
	}

	$title = array();
	$title[] = 'Leads.gif';
	$title[] = $currentModuleStrings["LBL_NEW_LEADS"];
	$title[] = 'home_mynewlead';
	$title[] = getLeadView($leadView);
	$title[] = 'showLeadView';
	$title[] = 'MyNewLeadFrm';
	$title[] = 'lead_view';

	$header = array();
	$header[] = $currentModuleStrings['LBL_LIST_LEAD_NAME'];
	$header[] = $currentModuleStrings['Company'];

	$entries = array();
	foreach ($openLeadList as $lead) {
		$value = array();
		$leadfields = array(
			'LEAD_NAME' => $lead['leadname'],
			'COMPANY' => $lead['company'],
			'LEAD_ID' => $lead['id'],
		);

		$topLeads = (strlen($lead['leadname']) > 20) ? (substr($lead['leadname'], 0, 20) . '...') : $lead['leadname'];
		$value[] = '<a href="index.php?action=DetailView&module=Leads&record=' . $leadfields['LEAD_ID'] . '">' . $topLeads . '</a>';
		$value[] = $leadfields['COMPANY'];

		$entries[$leadfields['LEAD_ID']] = $value;
	}

	$search_qry = "&query=true&Fields0=" . $table_prefix . "_leaddetails.leadstatus&Condition0=n&Srch_value0=Lost+Lead&Fields1=" . $table_prefix . "_leaddetails.leadstatus&Condition1=n&Srch_value1=Junk+Lead&Fields2=" . $table_prefix . "_crmentity.smownerid&Condition2=e&Srch_value2=" . $current_user->column_fields['user_name'] . "&Fields3=" . $table_prefix . "_crmentity.createdtime&Condition3=h&Srch_value3=" . $startDate . "&searchtype=advance&search_cnt=4&matchtype=all"; // crmv@157122

	$values = array('ModuleName' => 'Leads', 'Title' => $title, 'Header' => $header, 'Entries' => $entries, 'search_qry' => $search_qry);
	$log->debug("Exiting getNewLeads method ...");
	if ((count($entries) == 0) || (count($entries) > 0))
		return $values;
}

/** Function to get the Lead View from the Combo List
 * @param string $leadView - (eg today, last 2 days)
 *  Returns the Lead view select option
 */
function getLeadView($leadView)
{
	global $log;
	$log->debug("Entering getLeadView(" . $leadView . ") method ...");

	if ($leadView == 'Today') {
		$selected1 = 'selected';
	} else if ($leadView == 'Last 2 Days') {
		$selected2 = 'selected';
	} else if ($leadView == 'Last Week') {
		$selected3 = 'selected';
	}

	$leadViewSelectOption = '<select class=small name="lead_view" onchange="showLeadView(this)">';
	$leadViewSelectOption .= '<option value="Today" ' . $selected1 . '>';
	$leadViewSelectOption .= 'Today';
	$leadViewSelectOption .= '</option>';
	$leadViewSelectOption .= '<option value="Last 2 Days" ' . $selected2 . '>';
	$leadViewSelectOption .= 'Last 2 Days';
	$leadViewSelectOption .= '</option>';
	$leadViewSelectOption .= '<option value="Last Week" ' . $selected3 . '>';
	$leadViewSelectOption .= 'Last Week';
	$leadViewSelectOption .= '</option>';
	$leadViewSelectOption .= '</select>';

	$log->debug("Exiting getLeadView method ...");
	return $leadViewSelectOption;
}