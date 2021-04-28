<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@43611 crmv@43864 crmv@44323 crmv@56603 */
/*
 * Class to render a customizable listview everywhere is needed. It does not depend on any specific module.
 *
 * TODO:
 *
 * 1. ordering by column
 */

require_once('include/BaseClasses.php');


class SimpleListView extends SDKExtendableClass {

	static $suggested_viewid = -1000;

	protected $user;					// use this user when retrieving data
	protected $module;					// the module to display
	protected $viewid;					// the customview id (-1000 is the suggested)
	protected $searchString = '';		// filter by this string
	protected $page = 1;				// start from this page
	protected $sortCol = '';			// the current sort column
	protected $sortDir = 'ASC';			// the sort order if sort column is used
	protected $parentid = 0;			// the crmid that originated this list
	protected $suggestedids = array();	// the crmid(s) to implement exludes from list
	protected $relatedid = array();		// crmid(s) of elements ised to populate the suggested filter

	// options
	public $listid = 0;						// id of the list. if multiple lists are on the same page, use different id, default = tabid
	public $useSession = true;				// store internal variables in session. if false, store them in the page and ajax requests will send them back
	public $showSearch = true;				// show search box
	public $showNavigation = true;			// show page box and buttons
	public $showCreate = false;				// show create button (forced to false if user has no write permission)
	public $showCancel = false;				// show a cancel button - crmv@126184
	public $showFilters = true;				// show filters selection box
	public $showSorting = true;				// enable the user to change the sort column and ordering direction
	public $showSuggested = true;			// show suggested filter (works only if showFilters = true)
	public $showCheckboxes = true;			// show checkbox for multiple select
	public $autoSwitchSuggested = true;		// if true, when suggested filter is empty, switch to default filter
	public $maxFields = 5;					// maximum number of columns to display
	public $showExtraFieldsRow = false;		// if more columns are available and this is true, they will be shown in an extra row
	public $entriesPerPage = 0;				// number of entries to show per page. if 0, use the default global var
	public $extraButtonsHTML = '';			// extra html code to be addedd after the search box
	public $template = 'SimpleListView.tpl';	// template to use when rendering cells
	public $selectFunction = 'SLV.select';				// javascript function to call when a row is pressed
	public $createFunction = 'SLV.create_new';			// javascript function to call when "Create" button is pressed
	public $cancelFunction = 'void';					// javascript function to call when "Cancel" button is pressed - crmv@126184
	public $addSelectedFunction = 'SLV.add_selected';	// javascript function to call when "Add Selected" is pressed

	public $emailTemplateType = 'Newsletter';

	public $hideLinkedRecords = false;		// hide records which are already linked
	public $relationId = null;				// relationId used to find linked record when the option $hideLinkedRecords is used
	
	// crmv@44323
	public $queryChangeFunction = null;		// a php function to call to alter the list query
	public $queryChangeFunctionParams = '';	// extra parameters to pass to previous function
	public $extraInputs = null;
	
	//crmv@48964
	public $sdkPopupQuery = false;
	public $sdkPopupShowAllButton = false;
	//crmv@48964e
	

	// mostly private data
	protected $renderMode = '';
	protected $moduleInstance;
	protected $customView;
	protected $queryGenerator;
	protected $listQuery;
	protected $lvController;
	protected $selectedIds = array();		// list of selected crmid

	protected $totalPages;
	protected $crmids = array();

	function __construct($module, $user = null) {
		global $current_user, $list_max_entries_per_page;

		$this->module = $module;
		$this->user = (empty($user) ? $current_user : $user);
		if ($this->entriesPerPage == 0) $this->entriesPerPage = $list_max_entries_per_page;

		if ($module != 'EmailTemplates') {
			$this->listid = getTabid($module);
			$this->moduleInstance = CRMEntity::getInstance($this->module);
			$this->moduleInstance->initSortByField($this->module);
			if (!method_exists($this->moduleInstance, 'getMessagePopupLinkQuery')) $this->showSuggested = false;
		}

		if ($this->module == 'EmailTemplates') {
			$this->showFilters = false;
			$this->showSuggested = false;
		}

	}

	public function reset() {
		//$this->moduleInstance
	}

	public function setParentId($parentid) {
		$this->parentid = $parentid;
	}
	
	public function setSuggestedIds($ids) {
		if (is_array($ids)) {
			$this->suggestedids = $ids;
		} else {
			$this->suggestedids = array($ids);
		}
	}

	public function setRelatedId($relatedid) {
		if (!is_array($relatedid)) $relatedid = array($relatedid);
		$this->relatedid = $relatedid;
	}

	public function setViewId($viewid) {
		$this->viewid = $viewid;
	}
	
	public function setRelationId($relationId) {
		$this->relationId = $relationId;
	}
	
	// try to guess the relationid from the parentid and the module
	public function calculateRelaionId() {
		$relationId = 0;
		if ($this->parentid && $this->module) {
			$fromModule = getSalesEntityType($this->parentid);
			if ($fromModule) {
				$RM = RelationManager::getInstance();
				$rels = $RM->getRelations($fromModule, null, $this->module);
				if (is_array($rels) && count($rels) == 1 && $rels[0]->relationid > 0) {
					$relationId = $rels[0]->relationid;
				}
			}
		}
		$this->setRelationId($relationId);
		return $relationId;
	}

	public function getCrmids() {
		return $this->crmids;
	}

	public function getQueryEmailTemplates() {
		global $adb, $table_prefix;

		$params = array($this->emailTemplateType);
		if (!empty($this->searchString)) {
			$searchQuery = "AND templatename LIKE '%{$this->searchString}%'";
		}
		$sql = "
			SELECT templateid, templatename, description
			FROM {$table_prefix}_emailtemplates
			WHERE deleted = 0 AND parentid = 0 AND templatetype = ? $searchQuery
			ORDER BY templatename ASC";
		return $adb->convert2Sql($sql, $params);
	}

	public function getExcludedIds() {
		$excludeIds = array();
		if ($this->parentid > 0) {
			$rm = RelationManager::getInstance();
			$excludeIds = $rm->getRelatedIds(getSalesEntityType($this->parentid), $this->parentid, $this->module);
		}
		// TODO: implement the case with relationid
		return $excludeIds;
	}
	
	public function getQuery() {
		global $adb, $table_prefix;

		if ($this->module == 'EmailTemplates') {
			$this->listQuery = $this->getQueryEmailTemplates();
			return $this->listQuery;
		}

		$this->customView = CRMEntity::getInstance('CustomView', $this->module); // crmv@115329
		$this->defaultViewId = $this->customView->getViewId($this->module);

		if (empty($this->viewid)) {
			if ($this->showSuggested) {
				$this->viewid = self::$suggested_viewid;
			} else {
				$this->viewid = $this->defaultViewId;
			}
		}
		$viewid = $this->viewid;

		$this->queryGenerator = QueryGenerator::getInstance($this->module, $this->user);
		$this->lvController = ListViewController::getInstance($adb, $this->user, $this->queryGenerator);
		
		// if there's a parent, exclude already linked entities
		if ($this->hideLinkedRecords) $excludeIds = $this->getExcludedIds();

		if ($this->showSuggested && $viewid == self::$suggested_viewid) {
			// suggested

			// TODO: generalize
			$list_query = $this->moduleInstance->getMessagePopupLinkQuery($this->queryGenerator, $this->module, $this->suggestedids, $this->searchString, $excludeIds);
		} else {
			// standard filter

			// filter
			$viewinfo = $this->customView->getCustomViewByCvid($viewid);

			if (!empty($viewid)) {
				$this->queryGenerator->initForCustomViewById($viewid);
			} else {
				$this->queryGenerator->initForDefaultCustomView();
			}

			// search
			if ($this->searchString) {
				$listview_header_search = $this->lvController->getBasicSearchFieldInfoList();
				$listview_header_search = array_slice($listview_header_search, 0, $this->maxFields);
				$searchReq = array(
					'search_fields' => $listview_header_search,
					'search_text' => $this->searchString,
					'searchtype' => 'BasicSearch',
				);
				$this->queryGenerator->addUserSearchConditions($searchReq);
			}
			
			// exclude already linked ids
			if (!empty($excludeIds)) {
				$this->queryGenerator->appendToWhereClause(" AND {$table_prefix}_crmentity.crmid NOT IN (".implode(',', $excludeIds).")");
			}
			
			// crmv@151233
			if (empty($this->sortCol)) {
				$this->sortDir = $this->moduleInstance->getSortOrder($this->module, false);
				$this->sortCol = $this->moduleInstance->getOrderBy($this->module, false);
			}
			if (!empty($this->sortCol) && !empty($this->sortDir)) {
				if($this->sortCol != 'smownerid') {
					$orderby = $this->customView->getOrderByFilterSQL($viewid);
					// passing stuff with the session... What a wonderful idea!
					if($orderby[0] != ''){
						VteSession::set($this->module.'_ORDER_BY', $orderby[0]);
						VteSession::set($this->module.'_SORT_ORDER', $orderby[1]);
					} else {
						VteSession::set($this->module.'_ORDER_BY', $this->sortCol);
						VteSession::set($this->module.'_SORT_ORDER', $this->sortDir);
					}
				}
			}
			// crmv@151233e

			//generate query
			$list_query = $this->queryGenerator->getQuery();
			
			//crmv@48964
			if ($_REQUEST['sdk_view_all'] == '1') $this->sdkPopupQuery = false;
			if (!empty($this->sdkPopupQuery)) {
				$sdk_show_all_button = false;
				if ($_REQUEST['file'] == 'SimpleListViewAjax' && !empty($_REQUEST['listid'])) {
					$backup_request = $_REQUEST;
					$_REQUEST = VteSession::get('slvr_'.$this->listid);
				}
				VteSession::set('slvr_'.$this->listid, $_REQUEST);
				$query = $list_query; // in order to maintain compatibility
				include($this->sdkPopupQuery);
				$list_query = $query;
				if ($_REQUEST['file'] == 'SimpleListViewAjax' && !empty($_REQUEST['listid'])) {
					$_REQUEST = $backup_request;
				}
				if ($_REQUEST['sdk_view_all'] == '1') $sdk_show_all_button = false;
				$this->sdkPopupShowAllButton = $sdk_show_all_button;
			}
			//crmv@48964e

			// order crmv@44323 crmv@102007
			if (!empty($this->sortCol) && !empty($this->sortDir)) {
				if($this->sortCol == 'smownerid') {
					$list_query .= ' ORDER BY user_name '.$this->sortDir;
				} else {
					//crmv@102158
					$orderby = $this->customView->getOrderByFilterSQL($viewid);
					if($orderby[0] != ''){
						$list_query .= " ORDER BY ".$orderby[0]." ".$orderby[1];
					}else{
						$list_query .= $this->moduleInstance->getFixedOrderBy($this->module, $this->sortCol, $this->sortDir);
					}
					//crmv@102158e
				}
			}
		}

		// crmv@44323 // crmv@45949
		if (is_array($this->queryChangeFunction)) {
			$callClass = (($this->queryChangeFunction[0] == $this->module) ? $this->moduleInstance : CRMEntity::getInstance($this->queryChangeFunction[0]));
			$callable = array($callClass, $this->queryChangeFunction[1]);
		} else {
			$callable = $this->queryChangeFunction;
		}
		if (is_callable($callable)) {
			$list_query = call_user_func($callable, $list_query, $this->queryChangeFunctionParams);
		}
		// crmv@44323e //crmv@45949e
		
		$this->listQuery = $list_query;
		return $list_query;
	}
	
	public function getData() {
		global $adb;

		$query = $this->getQuery();
		if (!empty($query)) {
			$res = $adb->query(replaceSelectQuery($query,'count(*) as cnt'));
			if ($res){
				$noofrows = $adb->query_result_no_html($res,0,'cnt');
				$this->totalPages = ceil($noofrows/$this->entriesPerPage);
			}
		}
		if ($this->autoSwitchSuggested && $this->viewid == self::$suggested_viewid && $noofrows == 0) {
			// auto switch to default filter, if suggested is empty...
			$this->viewid = $this->defaultViewId;
			return $this->getData();
		}

		// execute query
		$limit_start_rec = ($this->page-1 ) * $this->entriesPerPage;
		$list_result = $adb->limitQuery($query, $limit_start_rec, $this->entriesPerPage);
		return $list_result;
	}

	// extra values to be passed to the tpl and then propagated every time by Ajax requests
	// use the name "slv_..." to automatically set class properties after ajax calls
	public function getExtraInputs() {
		$arr = array();
		if (!$this->useSession) {
			$props = $this->getPropertiesToSave();
			foreach ($props as $pname) {
				$savename = 'slv_'.$this->listid.'_'.$pname;
				$arr[$savename] = $this->$pname;
			}
		}
		// crmv@44323
		// merge with extra inputs
		if (is_array($this->extraInputs)) {
			$arr = array_merge($arr, $this->extraInputs);
		} elseif (is_string($this->extraInputs)) {
			// try a json array
			try {
				$json = json_decode($this->extraInputs, true);
				if (is_array($json)) $arr = array_merge($arr, $json);
			} catch (Exception $e) {

			}
		}
		// crmv@44323e
		return $arr;
	}

	/*
	 * a list of the properties of this class to save between ajax calls
	 */
	public function getPropertiesToSave() {
		$reflect = new ReflectionClass($this);
		$props = $reflect->getProperties(ReflectionProperty::IS_PUBLIC);
		
		$flds = array_map(function($fld) {
			return $fld->getName();
		}, $props);
		
		// add some other protected vars
		$flds[] = 'parentid';
		
		return $flds;
	}

	protected function saveProperties() {
		$props = $this->getPropertiesToSave();
		if ($this->useSession) {
			//crmv@170593
			$propstosave = Array();
			foreach ($props as $pname) {
				$propstosave['slv_'.$this->listid.'_'.$pname] = $this->$pname;
			}
			VteSession::setMulti($propstosave);
			unset($propstosave);
			//crmv@170593e
		} else {
			// done in get Extra Inputs
		}
	}

	protected function restoreProperties() {
		if ($this->useSession) {
			$storeVar = $_SESSION;
		} else {
			$storeVar = $_REQUEST;
		}

		$props = $this->getPropertiesToSave();
		foreach ($props as $pname) {
			$savename = 'slv_'.$this->listid.'_'.$pname;
			if (isset($storeVar[$savename])) {
				$this->$pname = $storeVar[$savename];
			}
		}
	}

	public function parseRawResultEmailTemplates(&$rawData) {
		global $adb;
		//crmv@48977
		$head = array();
		$head[] = array('fieldname' => getTranslatedString('LBL_EMAIL_TEMPLATE', 'Settings'), 'text' => getTranslatedString('LBL_EMAIL_TEMPLATE', 'Settings'));
		$head[] = array('fieldname' => getTranslatedString('LBL_DESCRIPTION'), 'text' => getTranslatedString('LBL_DESCRIPTION'));
		//crmv@48977e
		$rows = array();
		while ($row = $adb->fetchByAssoc($rawData)) {
			$id = $row['templateid'];
			$rows[$id] = array($row['templatename'], $row['description'], 'entityname'=>$row['templatename']);
		}
		return array($head, $rows);
	}

	public function parseRawResult(&$rawData) {

		if ($this->module == 'EmailTemplates') {
			return $this->parseRawResultEmailTemplates($rawData);
		}

		$skipActions = true;
		$listview_header = $this->lvController->getListViewHeader($this->moduleInstance,$this->module,$url_string,$sorder, $order_by, $skipActions);
		$listview_entries = $this->lvController->getListViewEntries($this->moduleInstance,$this->module,$rawData,$navigation_array,$skipActions,$listview_entries_other);

		// fix links
		if (!$this->showExtraFieldsRow) {
			$listview_header = array_slice($listview_header, 0, $this->maxFields);
		}

		foreach ($listview_header as &$lh) {
			// dirty trick to get the field name (for sorting)
			$fldname = '';
			if (preg_match('/order_by=([^&]+)&/i', $lh, $matches)) {
				$fldname = $matches[1];
			}
			$text = strip_tags($lh);
			$lh = array('text'=>$text, 'fieldname'=>$fldname);
		}

		$crmids = array();
		foreach ($listview_entries as $crmid=>&$lrow) {
			foreach ($lrow as &$le) {
				if (stripos($le,'vteicon') === false) $le = strip_tags($le);	//crmv@59091 crmv@120975
				//crmv@78549
				else {
					$pos = stripos($le, '<a href="javascript:;">');
					if ($pos !== false) $le = strip_tags(substr($le, 0, $pos)).substr($le, $pos);
				}
				//crmv@78549e
			}
			$rowname = getEntityName($this->module, $crmid);
			$rowname = $rowname[$crmid];
			$lrow['entityname'] = htmlspecialchars($rowname);
			$lrow['slv_selected'] = in_array($crmid, $this->selectedIds);
			$crmids[] = $crmid;
		}
		$this->crmids = $crmids;

		return array($listview_header, $listview_entries);
	}

	public function render($direct = false) {
		global $theme, $app_strings;

		// populate request with parameters
		$extraInputs = $this->getExtraInputs();
		if (is_array($extraInputs)) {
			foreach ($extraInputs as $k=>$v) {
				$_REQUEST[$k] = $v;
			}
		}

		$rawData = $this->getData();
		list($listview_header, $listview_entries) = $this->parseRawResult($rawData);

		$smarty = new VteSmarty();

		$smarty->assign('THEME', $theme);
		$smarty->assign('APP', $app_strings);
		$smarty->assign('MODULE', $this->module);

		$smarty->assign('RENDER_MODE', $this->renderMode);
		$smarty->assign('LISTID', $this->listid);
		$smarty->assign('LIST_PAGE', $this->page);
		$smarty->assign('LIST_TOT_PAGES', $this->totalPages);
		$smarty->assign('LIST_SEARCH', $this->searchString);
		$smarty->assign('LIST_MAXROWFIELDS', $this->maxFields);
		$smarty->assign('LIST_SORTCOL', $this->sortCol);
		$smarty->assign('LIST_SORTDIR', $this->sortDir);
		$smarty->assign('LIST_HEADER', $listview_header);
		$smarty->assign('LIST_ENTRIES', $listview_entries);

		$smarty->assign('SHOW_FILTERS', $this->showFilters);
		$smarty->assign('SHOW_SEARCH', $this->showSearch);
		$smarty->assign('SHOW_NAVIGATION', $this->showNavigation);
		$smarty->assign('SHOW_SORTING', $this->showSorting);
		$smarty->assign('SHOW_CREATE', $this->showCreate);
		$smarty->assign('SHOW_CHECKBOXES', $this->showCheckboxes);
		$smarty->assign('SHOW_CANCEL', $this->showCancel); // crmv@126184
		

		$smarty->assign('EXTRA_INPUTS', $extraInputs);
		$smarty->assign('SELECTED_IDS', $this->selectedIds);

		if ($this->showFilters) {
			$customview_html = $this->customView->getCustomViewCombo($this->viewid);
			if ($this->showSuggested) {
				// add magic filter
				$customview_html = '<option value="'.self::$suggested_viewid.'">'.getTranslatedString('Suggested').'</option>'.$customview_html;
			}
			$smarty->assign("CUSTOMVIEW_OPTION",$customview_html);
		}

		$smarty->assign('SELECT_FUNC', $this->selectFunction);
		$smarty->assign('CREATE_FUNC', $this->createFunction);
		$smarty->assign('CANCEL_FUNC', $this->cancelFunction); // crmv@126184
		$smarty->assign('ADDSELECTED_FUNC', $this->addSelectedFunction);
		$smarty->assign('EXTRA_BUTTONS_HTML', $this->extraButtonsHTML);
		
		$smarty->assign('SDK_SHOW_ALL_BUTTON', $this->sdkPopupShowAllButton);	//crmv@48964

		$this->saveProperties();

		if ($direct) {
			$smarty->display($this->template);
		} else {
			$html = $smarty->fetch($this->template);
			return $html;
		}
	}

	public function ajaxHandler() {
		$this->restoreProperties();

		$viewid = intval($_REQUEST['viewid']);
		if ($viewid > 0) $this->viewid = $viewid;

		$page = intval($_REQUEST['page']);
		if ($page > 0) $this->page = $page;

		$sortcol = vtlib_purify($_REQUEST['sortcol']);
		if (!empty($sortcol)) $this->sortCol = $sortcol;

		$sortdir = $_REQUEST['sortdir'];
		if (!empty($sortdir)) $this->sortDir = $sortdir;

		if ($_REQUEST['searchstr'] != '') $this->searchString = trim($_REQUEST['searchstr']);
		
		if (!empty($_REQUEST['selected_ids'])) {
			$this->selectedIds = array_filter(array_map('intval', explode(':', $_REQUEST['selected_ids'])));
		}

		if ($this->parentid > 0 || $this->relationId > 0) {
			$this->hideLinkedRecords = true;
		}

		$this->renderMode = 'ajax';
		$this->render(true);
	}
}

?>