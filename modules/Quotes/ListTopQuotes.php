<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com>
 * SPDX-License-Identifier: AGPL-3.0-only
 ************************************/

/**    function used to get the top 5 quotes from the ListView query
 * @return array $values - array with the title, header and entries like  Array('Title'=>$title,'Header'=>$listview_header,'Entries'=>$listview_entries) where as listview_header and listview_entries are arrays of header and entity values which are returned from function getListViewHeader and getListViewEntries
 */
function getTopQuotes($maxval, $calCnt)
{
    require_once('include/ListView/ListView.php');
    require_once('modules/CustomView/CustomView.php');

    global $adb, $current_language, $current_user, $list_max_entries_per_page;

    $currentModuleStrings = return_module_language($current_language, 'Quotes');

    $LVU = ListViewUtils::getInstance();

    $urlString = '';
    $sOrder = '';

    //Retreive the list from Database
    //<<<<<<<<<customview>>>>>>>>>
    $dateVar = date('Y-m-d');
    $currentModule = 'Quotes';
    $viewId = getCvIdOfAll($currentModule);
    $queryGenerator = QueryGenerator::getInstance($currentModule, $current_user);
    $queryGenerator->initForCustomViewById($viewId);
    $meta = $queryGenerator->getMeta($currentModule);
    $accessibleFieldNameList = array_keys($meta->getModuleFields());
    $customViewFields = $queryGenerator->getCustomViewFields();
    $fields = $queryGenerator->getFields();
    $newFields = array_diff($fields, $customViewFields);
    $widgetFieldsList = array('subject', 'potential_id', 'account_id', 'total');
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
    $_REQUEST = getTopQuotesSearch($_REQUEST, array(
            'assigned_user_id' => $current_user->id, // crmv@161420
            'validtill' => $dateVar,
            'quotestage.Rejected' => $currentModuleStrings['Rejected'],
            'quotestage.Accepted' => $currentModuleStrings['Accepted'])
    );
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

    $focus = CRMEntity::getInstance('Quotes');


    $title = array('TopOpenQuotes.gif', $currentModuleStrings['LBL_MY_TOP_QUOTE'], 'home_mytopquote');
    //Retreive the List View Table Header
    $controller = ListViewController::getInstance($adb, $current_user, $queryGenerator);
    $controller->setHeaderSorting(false);
    $header = $controller->getListViewHeader($focus, $currentModule, $urlString, $sOrder,
        true);

    $entries = $controller->getListViewEntries($focus, $currentModule, $listResult,
        $navigationArray, true);

    $values = array('ModuleName' => 'Quotes', 'Title' => $title, 'Header' => $header, 'Entries' => $entries, 'search_qry' => $searchQry);

    if (($numberOfRows == 0) || ($numberOfRows > 0))
        return $values;
}

function getTopQuotesSearch($output, $input)
{
    $output['query'] = 'true';
    $output['Fields0'] = 'assigned_user_id';
    $output['Condition0'] = 'e';
    $output['Srch_value0'] = $input['assigned_user_id'];
    $output['Fields1'] = 'validtill';
    $output['Condition1'] = 'h';
    $output['Srch_value1'] = $input['validtill'];
    $output['Fields2'] = 'quotestage';
    $output['Condition2'] = 'n';
    $output['Srch_value2'] = 'Rejected';
    $output['Fields3'] = 'quotestage';
    $output['Condition3'] = 'n';
    $output['Srch_value3'] = 'Accepted';
    $output['Fields4'] = 'quotestage';
    $output['Condition4'] = 'n';
    $output['Srch_value4'] = $input['quotestage.Rejected'];
    $output['Fields5'] = 'quotestage';
    $output['Condition5'] = 'n';
    $output['Srch_value5'] = $input['quotestage.Accepted'];
    $output['searchtype'] = 'advance';
    $output['search_cnt'] = '6';
    $output['matchtype'] = 'all';
    return $output;
}