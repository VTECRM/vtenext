<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com>
 * SPDX-License-Identifier: AGPL-3.0-only
 ************************************/
function getTopInvoice($maxval, $calCnt)
{
    require_once("data/Tracker.php");
    require_once('modules/Invoice/Invoice.php');
    require_once('include/logging.php');
    require_once('include/ListView/ListView.php');
    require_once('include/utils/utils.php');
    require_once('modules/CustomView/CustomView.php');

    global $current_language, $current_user, $adb, $list_max_entries_per_page, $theme;
    $current_module_strings = return_module_language($current_language, 'Invoice');

    $LVU = ListViewUtils::getInstance();

    $urlString = '';
    $sorder = '';
    $CustomView = CRMEntity::getInstance('CustomView', "Invoice"); // crmv@115329
    if (isset($_REQUEST['viewname']) == false || $_REQUEST['viewname'] == '') {
        if ($CustomView->setdefaultviewid != "") {

        } else {

        }
    }

    $theme_path = "themes/" . $theme . "/";

    //Retreive the list from Database
    //<<<<<<<<<customview>>>>>>>>>
    $currentModule = 'Invoice';
    $viewId = getCvIdOfAll($currentModule);
    $queryGenerator = QueryGenerator::getInstance($currentModule, $current_user);
    $queryGenerator->initForCustomViewById($viewId);
    $meta = $queryGenerator->getMeta($currentModule);
    $accessibleFieldNameList = array_keys($meta->getModuleFields());
    $customViewFields = $queryGenerator->getCustomViewFields();
    $fields = $queryGenerator->getFields();
    $newFields = array_diff($fields, $customViewFields);
    $widgetFieldsList = array('subject', 'salesorder_id', 'account_id', 'contact_id', 'invoicestatus',
        'total');
    $widgetFieldsList = array_intersect($accessibleFieldNameList, $widgetFieldsList);
    $widgetSelectedFields = array_chunk(array_intersect($customViewFields, $widgetFieldsList), 2);
    //select the first chunk of two fields
    $widgetSelectedFields = $widgetSelectedFields[0];
    if (count($widgetSelectedFields) < 2) {
        $widgetSelectedFields = array_chunk(array_merge($widgetSelectedFields, $accessibleFieldNameList), 2);
        //select the first chunk of two fields
        $widgetSelectedFields = $widgetSelectedFields[0];
    }
    $newFields = array_merge($newFields, $widgetSelectedFields);
    $queryGenerator->setFields($newFields);
    $_REQUEST = getTopInvoiceSearch($_REQUEST, array(
        'assigned_user_id' => $current_user->column_fields['user_name']));
    $queryGenerator->addUserSearchConditions($_REQUEST);
    $searchQry = '&query=true' . getSearchURL($_REQUEST);
    $query = $queryGenerator->getQuery();

    //<<<<<<<<customview>>>>>>>>>

    if ($calCnt == 'calculateCnt') {
        $listResultRows = $adb->query(mkCountQuery($query));
        return $adb->query_result($listResultRows, 0, 'count');
    }

    $listResult = $adb->limitQuery($query, 0, $maxval);

    //Retreiving the no of rows
    $numberOfRows = $adb->num_rows($listResult);

    //Retreiving the start value from request
    if (isset($_REQUEST['start']) && $_REQUEST['start'] != '') {
        $start = vtlib_purify($_REQUEST['start']);
    } else {
        $start = 1;
    }

    //Retreive the Navigation array
    $navigationArray = $LVU->getNavigationValues($start, $numberOfRows, $list_max_entries_per_page);

    if ($navigationArray['start'] == 1) {
        if ($numberOfRows != 0)
        {}
        else

            if ($numberOfRows > $list_max_entries_per_page) {

            } else {

            }

    } else {
        if ($navigationArray['next'] > $list_max_entries_per_page) {

        } else {

        }
    }

    $focus = CRMEntity::getInstance('Invoice');

    $title = array('myTopInvoices.gif', $current_module_strings['LBL_MY_TOP_INVOICE'], 'home_mytopinv');
    //Retreive the List View Table Header
    $controller = ListViewController::getInstance($adb, $current_user, $queryGenerator);
    $controller->setHeaderSorting(false);
    $header = $controller->getListViewHeader($focus, $currentModule, $urlString, $sorder, true);

    $entries = $controller->getListViewEntries($focus, $currentModule, $listResult,
        $navigationArray, true);

    $values = array('ModuleName' => 'Invoice', 'Title' => $title, 'Header' => $header, 'Entries' => $entries, 'search_qry' => $searchQry);

    if (($numberOfRows == 0) || ($numberOfRows > 0))
        return $values;
}

function getTopInvoiceSearch($output, $input)
{
    $output['query'] = 'true';
    $output['Fields0'] = 'invoicestatus';
    $output['Condition0'] = 'n';
    $output['Srch_value0'] = 'Paid';
    $output['Fields1'] = 'assigned_user_id';
    $output['Condition1'] = 'e';
    $output['Srch_value1'] = $input['assigned_user_id'];
    $output['searchtype'] = 'advance';
    $output['search_cnt'] = '2';
    $output['matchtype'] = 'all';
    return $output;
}

