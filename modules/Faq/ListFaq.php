<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/**    function used to get the top 5 recent FAQs from Listview query
 * @return array $values - array with the title, header and entries like  Array('Title'=>$title,'Header'=>$listview_header,'Entries'=>$listview_entries) where as listview_header and listview_entries are arrays of header and entity values which are returned from function getListViewHeader and getListViewEntries
 */
function getMyFaq($maxval, $calCnt)
{
    require_once("data/Tracker.php");
    require_once('modules/Faq/Faq.php');
    require_once('include/logging.php');
    require_once('include/ListView/ListView.php');
    require_once('include/utils/utils.php');
    require_once('modules/CustomView/CustomView.php');

	global $current_language,$current_user,$list_max_entries_per_page,$theme,$adb;
	$current_module_strings = return_module_language($current_language, 'Faq');
	
    $LVU = ListViewUtils::getInstance();
    $urlString = '';
    $sorder = '';
    $CustomView = CRMEntity::getInstance('CustomView', "Faq"); // crmv@115329
	if (isset($_REQUEST['viewname']) == false || $_REQUEST['viewname'] == '') {
		if ($CustomView->setdefaultviewid != "") {
		} else {
		}
	}
    $focus = CRMEntity::getInstance('Faq');


    //Retreive the list from Database
    //<<<<<<<<<customview>>>>>>>>>
    $currentModule = 'Faq';
    $viewId = getCvIdOfAll($currentModule);
    $queryGenerator = QueryGenerator::getInstance($currentModule, $current_user);
    $queryGenerator->initForCustomViewById($viewId);
    $meta = $queryGenerator->getMeta($currentModule);
    $accessibleFieldNameList = array_keys($meta->getModuleFields());
    $customViewFields = $queryGenerator->getCustomViewFields();
    $fields = $queryGenerator->getFields();
    $newFields = array_diff($fields, $customViewFields);
    $widgetFieldsList = array('question', 'product_id');
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
    $_REQUEST = getMyFaqSearch($_REQUEST);
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


    //Retreive the List View Table Header
    $title = array('myFaqs.gif', $current_module_strings['LBL_MY_FAQ'], 'home_myfaq');
    $controller = ListViewController::getInstance($adb, $current_user, $queryGenerator);
    $controller->setHeaderSorting(false);
    $header = $controller->getListViewHeader($focus, $currentModule, $urlString, $sorder, true);

    $entries = $controller->getListViewEntries($focus, $currentModule, $listResult,
        $navigationArray, true);

    $values = array('ModuleName' => 'Faq', 'Title' => $title, 'Header' => $header, 'Entries' => $entries, 'search_qry' => $searchQry);
    if (($numberOfRows == 0) || ($numberOfRows > 0)) {
        return $values;
    }
}

function getMyFaqSearch($output)
{
    $output['&query'] = 'true';
    $output['&Fields0'] = 'faqstatus';
    $output['&Condition0'] = 'n';
    $output['&Srch_value0'] = 'Obsolete';
    $output['&searchtype'] = 'advance';
    $output['&search_cnt'] = '1';
    $output['matchtype'] = 'any';
    return $output;
}

?>
