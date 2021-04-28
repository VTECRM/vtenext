<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com>
 * SPDX-License-Identifier: AGPL-3.0-only
 ************************************/

/**    Function to get the list of tickets for the currently loggedin user
 **/

function getMyTickets($maxval, $calCnt)
{
    global $log;
    $log->debug("Entering getMyTickets() method ...");
    global $current_user;
    global $current_language;
    global $adb, $table_prefix;
    $currentModuleStrings = return_module_language($current_language, 'HelpDesk');

    $searchQuery = "SELECT " . $table_prefix . "_troubletickets.ticketid,
	" . $table_prefix . "_troubletickets.parent_id," . $table_prefix . "_troubletickets.title
		FROM " . $table_prefix . "_troubletickets 
		INNER JOIN " . $table_prefix . "_crmentity on " . $table_prefix . "_crmentity.crmid = " . $table_prefix . "_troubletickets.ticketid 
		INNER JOIN " . $table_prefix . "_users on " . $table_prefix . "_users.id = " . $table_prefix . "_crmentity.smownerid
		where " . $table_prefix . "_crmentity.smownerid = ? and " . $table_prefix . "_crmentity.deleted = 0 and " . $table_prefix . "_troubletickets.status <> 'Closed' ORDER BY createdtime DESC";

    if ($calCnt == 'calculateCnt') {
        $listResultRows = $adb->pquery(mkCountQuery($searchQuery), array($current_user->id));
        return $adb->query_result($listResultRows, 0, 'count');
    }
    $ticketResult = $adb->limitpQuery($searchQuery, 0, $maxval, array($current_user->id));
    if ($adb->num_rows($tktresult)) {
        $title = array();
        $title[] = 'myTickets.gif';
        $title[] = $currentModuleStrings['LBL_MY_TICKETS'];
        $title[] = 'home_mytkt';

        $header = array();
        $header[] = $currentModuleStrings['LBL_SUBJECT'];
        $header[] = $currentModuleStrings['Related To'];

        $numberOfRows = $adb->num_rows($ticketResult);
        for ($i = 0; $i < $adb->num_rows($ticketResult); $i++) {
            $value = array();
            $ticketId = $adb->query_result($ticketResult, $i, "ticketid");
            $viewStatus = $adb->query_result($ticketResult, $i, "viewstatus");
            if ($viewStatus == 'Unread') {
                $value[] = '<a style="color:red;" href="index.php?action=DetailView&module=HelpDesk&record=' . substr($adb->query_result($ticketResult, $i, "ticketid"), 0, 20) . '">' . $adb->query_result($ticketResult, $i, "title") . '</a>';
            } elseif ($viewStatus == 'Marked') {
                $value[] = '<a style="color:yellow;" href="index.php?action=DetailView&module=HelpDesk&record=' . substr($adb->query_result($ticketResult, $i, "ticketid"), 0, 20) . '">' . $adb->query_result($ticketResult, $i, "title") . '</a>';
            } else {
                $value[] = '<a href="index.php?action=DetailView&module=HelpDesk&record=' . substr($adb->query_result($ticketResult, $i, "ticketid"), 0, 20) . '">' . substr($adb->query_result($ticketResult, $i, "title"), 0, 20) . '</a>';
            }
            $parentId = $adb->query_result($ticketResult, $i, "parent_id");
            $parentName = '';
            if ($parentId != '' && $parentId != NULL) {
				$parentName = getParentLink($parentName);
            }

            $value[] = $parentName;
            $entries[$ticketId] = $value;
        }

        $searchQry = "&query=true&Fields0=" . $table_prefix . "_troubletickets.status&Condition0=n&Srch_value0=closed&Fields1=" . $table_prefix . "_crmentity.smownerid&Condition1=c&Srch_value1=" . $current_user->column_fields['user_name'] . "&searchtype=advance&search_cnt=2&matchtype=all"; // crmv@157122

        $values = array('ModuleName' => 'HelpDesk', 'Title' => $title, 'Header' => $header, 'Entries' => $entries, 'search_qry' => $searchQry);
        if (($numberOfRows == 0) || ($numberOfRows > 0)) {
            $log->debug("Exiting getMyTickets method ...");
            return $values;
        }
    }
    $log->debug("Exiting getMyTickets method ...");
}

/**    Function to get the parent (Account or Contact) link
 * @param int $parentId -- parent id of the ticket (accountid or contactid)
 *    return string $parent_name -- return the parent name as a link
 **/
function getParentLink($parentId)
{
    global $log;
    $log->debug("Entering getParentLink(" . $parentId . ") method ...");
    global $adb, $table_prefix;

    // Static caching
    static $__cache_listtickets_parentlink = array();
    if (isset($__cache_listtickets_parentlink[$parentId])) {
        return $__cache_listtickets_parentlink[$parentId];
    }

    $parentModule = getSalesEntityType($parentId); //crmv@171021

    if ($parentModule == 'Contacts') {
        $sql = "select firstname,lastname from " . $table_prefix . "_contactdetails where contactid=?";
        $res = $adb->pquery($sql, array($parentId));
        $parent = $adb->query_result($res, 0, 'firstname');
        $parent .= ' ' . $adb->query_result($res, 0, 'lastname');
        $parentName = '<a href="index.php?action=DetailView&module=' . $parentModule . '&record=' . $parentId . '">' . $parent . '</a>';
    }
    if ($parentModule == 'Accounts') {
        $sql = "select accountname from " . $table_prefix . "_account where accountid=?";
        $sqlResult = $adb->pquery($sql, array($parentId));
		$parent = $adb->query_result($sqlResult, 0, 'accountname');
        $parentName = '<a href="index.php?action=DetailView&module=' . $parentModule . '&record=' . $parentId . '">' . $parent . '</a>';
    }

    // Add to cache
    $__cache_listtickets_parentlink[$parentId] = $parentName;

    $log->debug("Exiting getParentLink method ...");
    return $parentName;
}