<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
global $current_user;
require_once('include/utils/ListViewUtils.php');
require_once('modules/CustomView/CustomView.php');
require_once('include/utils/CommonUtils.php');
//crmv@208173

class Homestuff{
	var $userid;
	var $dashdetails=array();

	/**
	 * this is the constructor for the class
	 */
	function __construct(){

	}

	/**
	 * this function adds a new widget information to the database
	 */
	function addStuff(){
		global $adb,$table_prefix;
		global $current_user;
		global $current_language;
		//crmv@208472
		$stuffid=$adb->getUniqueId($table_prefix.'_homestuff');
		$queryseq="select max(stuffsequence)+1 as seq from ".$table_prefix."_homestuff";
		$sequence=$adb->query_result($adb->pquery($queryseq, array()),0,'seq');
		//crmv@fix sequence
		if (!$sequence)
			$sequence = 0;
		//crmv@fix sequence end
		if($this->defaulttitle != ""){
			$this->stufftitle = $this->defaulttitle;
		}
		// crmv@30014 crmv@43676
		$columns = array('stuffid','stuffsequence','stufftype','userid','visible','stufftitle','size');
		$adb->format_columns($columns);
		$query="insert into {$table_prefix}_homestuff (".implode(",",$columns).") values (".generateQuestionMarks($columns).")";
		$params= array($stuffid,$sequence,$this->stufftype,$current_user->id,0,$this->stufftitle,1);
		// crmv@30014e crmv@43676e
		$result=$adb->pquery($query, $params);
		if(!$result){
			return false;
		}

		if($this->stufftype=="Module"){
			$fieldarray=explode(",",$this->fieldvalue);
			$querymod="insert into ".$table_prefix."_homemodule(stuffid, modulename, maxentries, customviewid, setype) values(?, ?, ?, ?, ?)";
			$params = array($stuffid,$this->selmodule,$this->maxentries,$this->selFiltername,$this->selmodule);
			$result=$adb->pquery($querymod, $params);
			if(!$result){
				return false;
			}

			for($q=0;$q<sizeof($fieldarray);$q++){
				$queryfld="insert into ".$table_prefix."_homemoduleflds values(? ,?)";
				$params = array($stuffid,$fieldarray[$q]);
				$result=$adb->pquery($queryfld, $params);
			}

			if(!$result){
				return false;
			}
		}else if($this->stufftype=="RSS"){
			$queryrss="insert into ".$table_prefix."_homerss values(?,?,?)";
			$params = array($stuffid,$this->txtRss,$this->maxentries);
			$resultrss=$adb->pquery($queryrss, $params);
			if(!$resultrss){
				return false;
			}
		}else if($this->stufftype=="Default"){//crmv@208472
            $querydef="insert into ".$table_prefix."_homedefault values(?, ?)";
			$params = array($stuffid,$this->defaultvalue);
	       	$resultdef=$adb->pquery($querydef, $params);
			if(!$resultdef){
				return false;
			}
		}else if($this->stufftype=='URL'){
			$userid = $current_user->id;
			$query="insert into ".$table_prefix."_homewidget_url values(?, ?)";
	       	$result=$adb->pquery($query, array($stuffid, $this->txtURL));
			if(!$result){
				return false;
			}
		// crmv@30014
		}else if($this->stufftype=='Charts'){
			$userid = $current_user->id;
			$query="insert into {$table_prefix}_homecharts values(?, ?)";
			$result=$adb->pquery($query, array($stuffid, $this->selchart));
			if(!$result){
				return false;
			}
		}
		// crmv@30014e

	 	return "VTE.Homestuff.loadAddedDiv($stuffid,'".$this->stufftype."', 1)"; // crmv@30014
	}

	/**
	 * this function returns the information about a widget in an array
	 * @return array(stuffid=>"id", stufftype=>"type", stufftitle=>"title")
	 */
	function getHomePageFrame(){
		global $adb,$table_prefix;
		global $current_user;
		// crmv@30014 crmv@33981 crmv@43676
		$column = 'size';
		$adb->format_columns($column);
		$querystuff ="select ".$table_prefix."_homestuff.stuffid,stufftype,stufftitle,setype,".$column." from ".$table_prefix."_homestuff
						left join ".$table_prefix."_homedefault on ".$table_prefix."_homedefault.stuffid=".$table_prefix."_homestuff.stuffid
						where visible=0 and userid=? order by stuffsequence desc";
		// crmv@30014e crmv@33981e crmv@43676e
		$resultstuff=$adb->pquery($querystuff, array($current_user->id));
		for($i=0;$i<$adb->num_rows($resultstuff);$i++){
			$modulename = $adb->query_result($resultstuff,$i,'setype');
			$stuffid = $adb->query_result($resultstuff,$i,'stuffid');
			$stufftype=$adb->query_result($resultstuff,$i,'stufftype');
			$stuffsize=$adb->query_result($resultstuff,$i,'size'); // crmv@30014
			if(!empty($modulename) && $modulename!='NULL'){
				if(!vtlib_isModuleActive($modulename)){
					continue;
				}
			}elseif($stufftype == 'Module'){
				//check for setype in vte_homemodule table and hide if module is de-activated
				$sql = "select setype from ".$table_prefix."_homemodule where stuffid=?";
				$result_setype = $adb->pquery($sql, array($stuffid));
				// crmv@174898
				if($adb->num_rows($result_setype)>0){
					$modulename = $adb->query_result($result_setype, 0, "setype");
				}
				if(!empty($modulename) && $modulename!='NULL'){
					if(!vtlib_isModuleActive($modulename)){
						continue;
					}
				}
				// crmv@174898e
			}elseif($stufftype == 'Charts'){//crmv@208472
                if(!vtlib_isModuleActive('Charts')){
					continue;
				}
			// crmv@30014e
			}elseif(!empty($stufftype) && $stufftype=='RSS'){
				//crmv@22924
				$module_real = "Rss";
				if(!vtlib_isModuleActive($module_real)){
					continue;
				}
				//crmv@22924 end
			}

			$nontrans_stufftitle = $adb->query_result($resultstuff,$i,'stufftitle');
			$trans_stufftitle = getTranslatedString($nontrans_stufftitle);
			$stufftitle=decode_html($trans_stufftitle);
			if(strlen($stufftitle)>100){
				$stuff_title=substr($stufftitle,0,97)."...";
			}else{
				$stuff_title = $stufftitle;
			}

			if($stufftype == 'Default' && $nontrans_stufftitle != 'Home Page Dashboard' && $nontrans_stufftitle != 'Tag Cloud'){
				if($modulename != 'NULL'){
					if(isPermitted($modulename,'index') == "yes"){
						$homeval[]=Array('Stuffid'=>$stuffid,'Stufftype'=>$stufftype,'Stufftitle'=>$stuff_title, 'Stuffsize'=>$stuffsize); // crmv@30014
					}
				}else{
					$homeval[]=Array('Stuffid'=>$stuffid,'Stufftype'=>$stufftype,'Stufftitle'=>$stuff_title, 'Stuffsize'=>$stuffsize); // crmv@30014
				}
			}else if($stufftype == 'Tag Cloud'){
				$homeval[]=Array('Stuffid'=>$stuffid,'Stufftype'=>$stufftype,'Stufftitle'=>$stuff_title, 'Stuffsize'=>$stuffsize); // crmv@30014
			}else if($modulename && $modulename != 'NULL'){ // crmv@174898
				if(isPermitted($modulename,'index') == "yes"){
					$homeval[]=Array('Stuffid'=>$stuffid,'Stufftype'=>$stufftype,'Stufftitle'=>$stuff_title, 'Stuffsize'=>$stuffsize); // crmv@30014
				}
			}else{
				$homeval[]=Array('Stuffid'=>$stuffid,'Stufftype'=>$stufftype,'Stufftitle'=>$stuff_title, 'Stuffsize'=>$stuffsize); // crmv@30014
			}
		}
		$homeframe=$homeval;
		return $homeframe;
	}

	/**
	 * this function returns information about the given widget in an array format
	 * @return array(stuffid=>"id", stufftype=>"type", stufftitle=>"title")
	 */
	function getSelectedStuff($sid,$stuffType){
		global $adb,$table_prefix;
		global $current_user;
		//crmv@43676
		$column = 'size';
		$adb->format_columns($column);
		//crmv@43676e
		$querystuff="select stufftitle,{$column} from {$table_prefix}_homestuff where visible=0 and stuffid=?"; // crmv@30014
		$resultstuff=$adb->pquery($querystuff, array($sid));
		$homeval=Array('Stuffid'=>$sid,'Stufftype'=>$stuffType,'Stufftitle'=>$adb->query_result($resultstuff,0,'stufftitle'), 'Stuffsize'=>$adb->query_result($resultstuff,0,'size')); // crmv@30014
		return $homeval;
	}

	/**
	 * this function only returns the widget contents for a given widget
	 */
	function getHomePageStuff($sid,$stuffType){
		global $adb;
		global $current_user;
		$header=Array();
		if($stuffType=="Module"){
			$details=$this->getModuleFilters($sid);
		}else if($stuffType=="RSS"){
			$details=$this->getRssDetails($sid);//crmv@208472
		}else if($stuffType=="Charts" && vtlib_isModuleActive("Charts")){
			$details=$this->getChartDetails($sid);
		// crmv@30014e
		}else if($stuffType=="Default"){
			$details=$this->getDefaultDetails($sid,'');
		}
		return $details;
	}

	/**
	 * this function returns the widget information for an module type widget
	 */
	private function getModuleFilters($sid){
		global $adb,$current_user,$table_prefix;
		
		$LVU = ListViewUtils::getInstance();
		
		$querycvid="select ".$table_prefix."_homemoduleflds.fieldname,".$table_prefix."_homemodule.* from ".$table_prefix."_homemoduleflds
					left join ".$table_prefix."_homemodule on ".$table_prefix."_homemodule.stuffid=".$table_prefix."_homemoduleflds.stuffid
					where ".$table_prefix."_homemoduleflds.stuffid=?";
		$resultcvid=$adb->pquery($querycvid, array($sid));
		$modname=$adb->query_result($resultcvid,0,"modulename");
		$cvid=$adb->query_result($resultcvid,0,"customviewid");
		$maxval=$adb->query_result($resultcvid,0,"maxentries");
		$column_count = $adb->num_rows($resultcvid);
		$cvid_check_query = $adb->pquery("SELECT * FROM ".$table_prefix."_customview WHERE cvid = ?",array($cvid));
		if(isPermitted($modname,'index') == "yes"){
			if($adb->num_rows($cvid_check_query)>0){
				$focus = CRMEntity::getInstance($modname);

				$oCustomView = CRMEntity::getInstance('CustomView', $modname); // crmv@115329
				$queryGenerator = QueryGenerator::getInstance($modname, $current_user);
				$queryGenerator->initForCustomViewById($cvid);
				$customViewFields = $queryGenerator->getCustomViewFields();
				$fields = $queryGenerator->getFields();
				$newFields = array_diff($fields, $customViewFields);

				for($l=0;$l < $column_count;$l++){
					$customViewColumnInfo = $adb->query_result($resultcvid,$l,"fieldname");
					$details = explode(':', $customViewColumnInfo);
					$newFields[] = $details[2];
				}
				//crmv@19800 crmv@26345 crmv@110153
				
				// crmv@169620
				list($focus->customview_order_by, $focus->customview_sort_order) = $oCustomView->getOrderByFilterSQL($cvid);
				$sort_order = $focus->getSortOrder();
				$order_by = $focus->getOrderBy();
				VteSession::set($modname.'_ORDER_BY', $order_by);
				VteSession::set($modname.'_SORT_ORDER', $sort_order);
				// crmv@169620e
				
				$query = $queryGenerator->getQuery();
				if(!empty($order_by) && $order_by != '' && $order_by != null) {
					$query .= $focus->getFixedOrderBy($modname,$order_by,$sort_order); //crmv@25403 crmv@127820
					// crmv@156758 - removed code
				}
				$queryGenerator->setFields($newFields);
				//crmv@19800e crmv@26345e crmv@110153e
				$count_result = $adb->query(mkCountQuery($query));
				$noofrows = $adb->query_result($count_result,0,"count");
				$navigation_array = $LVU->getNavigationValues(1, $noofrows, $maxval);

				//To get the current language file
				global $current_language,$app_strings;
				$fieldmod_strings = return_module_language($current_language, $modname);
				//crmv@limit query
				$list_result = $adb->limitQuery($query,0,$maxval);
				//crmv@limit query end
				$controller = ListViewController::getInstance($adb, $current_user, $queryGenerator);
				$controller->setHeaderSorting(false);
				$header = $controller->getListViewHeader($focus,$modname,'','','', true);

				$listview_entries = $controller->getListViewEntries($focus,$modname,$list_result,
						$navigation_array, true);
				$return_value =Array('ModuleName'=>$modname,'cvid'=>$cvid,'Maxentries'=>$maxval,'Header'=>$header,'Entries'=>$listview_entries);
				if(sizeof($header)!=0){
		       		return $return_value;
				}else{
		       		return array('Entries'=>"Fields not found in Selected Filter");
				}
			}
			else{
				return array('Entries'=>"<font color='red'>Filter You have Selected is Not Found</font>");
			}
 		}
		else{
			return array('Entries'=>"<font color='red'>Permission Denied</font>");
		}
	}

	/**
	 * this function gets the detailed information about a rss widget
	 */
	private function getRssDetails($rid){
		global $mod_strings,$table_prefix;
		if(isPermitted('Rss','index') == "yes"){
			require_once('modules/Rss/Rss.php');
			global $adb;
			$qry="select * from ".$table_prefix."_homerss where stuffid=?";
			$res=$adb->pquery($qry, array($rid));
			$url=$adb->query_result($res,0,"url");
			$maxval=$adb->query_result($res,0,"maxentries");
			$oRss = new VteRss();
			if($oRss->setRSSUrl($url)){
				$rss_html = $oRss->getListViewHomeRSSHtml($maxval);
			}else{
				$rss_html = "<strong>".$mod_strings['LBL_ERROR_MSG']."</strong>";
			}
			$return_value=Array('Maxentries'=>$maxval,'Entries'=>$rss_html);
		}else{
			return array('Entries'=>"<font color='red'>Not Accessible</font>");
		}
		return $return_value;
	}

	//crmv@208472

	// crmv@30014 crmv@165801
	function getChartDetails($did){
		global $adb, $table_prefix;
		$qry="select hc.chartid,hs.".$adb->format_column('size')." as chart_size from {$table_prefix}_homestuff hs inner join {$table_prefix}_homecharts hc on hc.stuffid = hs.stuffid where hc.stuffid=?";
		$result = $adb->pquery($qry, array($did));
		$row = $adb->fetchByAssoc($result, -1, false);
		return array('chartid'=>$row['chartid'], 'size'=>$row['chart_size']);
	}
	// crmv@30014e crmv@165801e

	/**
	 *
	 */
	private function getDefaultDetails($dfid,$calCnt){
		global $adb,$table_prefix;
		$qry="select * from ".$table_prefix."_homedefault where stuffid=?";
		$result=$adb->pquery($qry, array($dfid));
		$maxval=$adb->query_result($result,0,"maxentries");
		$hometype=$adb->query_result($result,0,"hometype");

		if($hometype=="ALVT" && vtlib_isModuleActive("Accounts")){
			include_once("modules/Accounts/ListViewTop.php");
			$home_values = getTopAccounts($maxval,$calCnt);
		}elseif($hometype=="PLVT" && vtlib_isModuleActive("Potentials")){
			if(isPermitted('Potentials','index') == "yes"){
				 include_once("modules/Potentials/ListViewTop.php");
				 $home_values=getTopPotentials($maxval,$calCnt);
			}
		}elseif($hometype=="QLTQ" && vtlib_isModuleActive("Quotes")){
			if(isPermitted('Quotes','index') == "yes"){
				require_once('modules/Quotes/ListTopQuotes.php');
				$home_values=getTopQuotes($maxval,$calCnt);
			}
		}elseif($hometype=="HLT" && vtlib_isModuleActive("HelpDesk")){
			if(isPermitted('HelpDesk','index') == "yes"){
				require_once('modules/HelpDesk/ListTickets.php');
				$home_values=getMyTickets($maxval,$calCnt);
			}
		}elseif($hometype=="GRT"){
			$home_values = getGroupTaskLists($maxval,$calCnt);
		}elseif($hometype=="OLTSO" && vtlib_isModuleActive("SalesOrder")){
			if(isPermitted('SalesOrder','index') == "yes"){
				require_once('modules/SalesOrder/ListTopSalesOrder.php');
				$home_values=getTopSalesOrder($maxval,$calCnt);
			}
		}elseif($hometype=="ILTI" && vtlib_isModuleActive("Invoice")){
			if(isPermitted('Invoice','index') == "yes"){
				require_once('modules/Invoice/ListTopInvoice.php');
				$home_values=getTopInvoice($maxval,$calCnt);
			}
		}elseif($hometype=="MNL" && vtlib_isModuleActive("Leads")){
			if(isPermitted('Leads','index') == "yes"){
				 include_once("modules/Leads/ListViewTop.php");
				 $home_values=getNewLeads($maxval,$calCnt);
			}
		}elseif($hometype=="OLTPO" && vtlib_isModuleActive("PurchaseOrder")){
			if(isPermitted('PurchaseOrder','index') == "yes"){
				require_once('modules/PurchaseOrder/ListTopPurchaseOrder.php');
				$home_values=getTopPurchaseOrder($maxval,$calCnt);
			}
		}elseif($hometype=="LTFAQ" && vtlib_isModuleActive("Faq")){
			if(isPermitted('Faq','index') == "yes"){
				require_once('modules/Faq/ListFaq.php');
				$home_values=getMyFaq($maxval,$calCnt);
			}
		}elseif($hometype=="CVLVT"){
			include_once("modules/CustomView/ListViewTop.php");
			$home_values = getKeyMetrics($calCnt);//crmv@208173
		}elseif($hometype == 'UA' && vtlib_isModuleActive("Calendar")){
			require_once "modules/Home/HomeUtils.php";
			$home_values = homepage_getUpcomingActivities($maxval, $calCnt);
		}elseif($hometype == 'PA' && vtlib_isModuleActive("Calendar")){
			require_once "modules/Home/HomeUtils.php";
			$home_values = homepage_getPendingActivities($maxval, $calCnt);
		}

		if($calCnt == 'calculateCnt'){
			return $home_values;
		}
		$return_value = Array();
		if(is_array($home_values) && count($home_values) > 0){ // crmv@181191
			$return_value=Array('Maxentries'=>$maxval,'Details'=>$home_values);
		}
		return $return_value;
	}

	/**
	 * this function returns the URL for a given widget id from the database
	 * @param integer $widgetid - the widgetid
	 * @return $url - the url for the widget
	 */
	function getWidgetURL($widgetid){
		global $adb, $current_user,$table_prefix;
		//crmv@add widget url functionality
		$sql = "select * from ".$table_prefix."_homewidget_url where widget_id=?";
		$result = $adb->pquery($sql, array($widgetid));
		//crmv@add widget url functionality end
		$url = "";
		if($adb->num_rows($result)>0){
			$url = $adb->query_result($result,0,"url");
		}
		return $url;
	}

	//crmv@25314	//crmv@26309
	function getIframeURL($widgetid){
		global $adb, $current_user, $current_language,$table_prefix;
		$sql = "SELECT ".$table_prefix."_home_iframe.* FROM ".$table_prefix."_home_iframe
				INNER JOIN ".$table_prefix."_homedefault ON ".$table_prefix."_homedefault.hometype = ".$table_prefix."_home_iframe.hometype
				INNER JOIN  ".$table_prefix."_homestuff ON ".$table_prefix."_homedefault.stuffid = ".$table_prefix."_homestuff.stuffid
				WHERE ".$table_prefix."_homedefault.stuffid = ?";
		$result = $adb->pquery($sql, array($widgetid));
		$url = "";
		if($adb->num_rows($result)>0){
			$url = $adb->query_result($result,0,"url");
			$url = str_replace('$CURRENT_LANGUAGE$',$current_language,$url);
		}
		return $url;
	}
	//crmv@25314e	//crmv@26309e
}

/**
 * this function returns the tasks allocated to different groups
 */
function getGroupTaskLists($maxval,$calCnt){
	//get all the group relation tasks
	global $current_user;
	global $adb;
	global $log;
	global $app_strings;
	global $table_prefix;
	$userid= $current_user->id;
	$groupids = explode(",", fetchUserGroupids($userid));

	//Check for permission before constructing the query.
	if(vtlib_isModuleActive("Leads") && count($groupids) > 0 && (isPermitted('Leads','index') == "yes"  || isPermitted('Calendar','index') == "yes" || isPermitted('HelpDesk','index') == "yes" || isPermitted('Potentials','index') == "yes"  || isPermitted('Accounts','index') == "yes" || isPermitted('Contacts','index') =='yes' || isPermitted('Campaigns','index') =='yes'  || isPermitted('SalesOrder','index') =='yes' || isPermitted('Invoice','index') =='yes' || isPermitted('PurchaseOrder','index') == 'yes')){
		$query = '';
		$params = array();
		if(isPermitted('Leads','index') == "yes"){
			$query = "select ".$table_prefix."_leaddetails.leadid as id,".$table_prefix."_leaddetails.lastname as name,".$table_prefix."_groups.groupname as groupname, 'Leads     ' as Type from ".$table_prefix."_leaddetails inner join ".$table_prefix."_crmentity on ".$table_prefix."_crmentity.crmid = ".$table_prefix."_leaddetails.leadid inner join ".$table_prefix."_groups on ".$table_prefix."_crmentity.smownerid=".$table_prefix."_groups.groupid where ".$table_prefix."_crmentity.deleted=0 and ".$table_prefix."_leaddetails.leadid > 0";
			if (count($groupids) > 0){
				$query .= " and ".$table_prefix."_groups.groupid in (". generateQuestionMarks($groupids). ")";
				array_push($params, $groupids);
			}
		}

		if(vtlib_isModuleActive("Calendar") && isPermitted('Calendar','index') == "yes"){
			if($query !=''){
				$query .= " union all ";
			}
			//Get the activities assigned to group
			$query .= "select ".$table_prefix."_activity.activityid as id,".$table_prefix."_activity.subject as name,".$table_prefix."_groups.groupname as groupname,'Activities' as Type from ".$table_prefix."_activity inner join ".$table_prefix."_crmentity on ".$table_prefix."_crmentity.crmid = ".$table_prefix."_activity.activityid inner join ".$table_prefix."_groups on ".$table_prefix."_crmentity.smownerid=".$table_prefix."_groups.groupid where  ".$table_prefix."_crmentity.deleted=0 and ((".$table_prefix."_activity.eventstatus !='held'and (".$table_prefix."_activity.status is null or ".$table_prefix."_activity.status ='')) or (".$table_prefix."_activity.status !='completed' and (".$table_prefix."_activity.eventstatus is null or ".$table_prefix."_activity.eventstatus=''))) and ".$table_prefix."_activity.activityid > 0";
			if (count($groupids) > 0) {
				$query .= " and ".$table_prefix."_groups.groupid in (". generateQuestionMarks($groupids). ")";
				array_push($params, $groupids);
			}
		}

		if(vtlib_isModuleActive("HelpDesk") && isPermitted('HelpDesk','index') == "yes"){
			if($query !=''){
				$query .= " union all ";
			}
			//Get the tickets assigned to group (status not Closed -- hardcoded value)
			$query .= "select ".$table_prefix."_troubletickets.ticketid,".$table_prefix."_troubletickets.title as name,".$table_prefix."_groups.groupname,'Tickets   ' as Type from ".$table_prefix."_troubletickets inner join ".$table_prefix."_crmentity on ".$table_prefix."_crmentity.crmid = ".$table_prefix."_troubletickets.ticketid inner join ".$table_prefix."_groups on ".$table_prefix."_crmentity.smownerid=".$table_prefix."_groups.groupid where ".$table_prefix."_crmentity.deleted=0 and ".$table_prefix."_troubletickets.status != 'Closed' and ".$table_prefix."_troubletickets.ticketid > 0";
			if (count($groupids) > 0) {
				$query .= " and ".$table_prefix."_groups.groupid in (". generateQuestionMarks($groupids). ")";
				array_push($params, $groupids);
			}
		}

		if(vtlib_isModuleActive("Potentials") && isPermitted('Potentials','index') == "yes"){
			if($query != ''){
				$query .=" union all ";
			}
			//Get the potentials assigned to group(sales stage not Closed Lost or Closed Won-- hardcoded value)
			$query .= "select ".$table_prefix."_potential.potentialid,".$table_prefix."_potential.potentialname as name,".$table_prefix."_groups.groupname as groupname,'Potentials ' as Type from ".$table_prefix."_potential  inner join ".$table_prefix."_crmentity on ".$table_prefix."_crmentity.crmid = ".$table_prefix."_potential.potentialid inner join ".$table_prefix."_groups on ".$table_prefix."_crmentity.smownerid = ".$table_prefix."_groups.groupid where ".$table_prefix."_crmentity.deleted=0  and ((".$table_prefix."_potential.sales_stage !='Closed Lost') or (".$table_prefix."_potential.sales_stage != 'Closed Won')) and ".$table_prefix."_potential.potentialid > 0";
			if (count($groupids) > 0){
				$query .= " and ".$table_prefix."_groups.groupid in (". generateQuestionMarks($groupids). ")";
				array_push($params, $groupids);
			}
		}

		if(vtlib_isModuleActive("Accounts") && isPermitted('Accounts','index') == "yes"){
			if($query != ''){
				$query .=" union all ";
			}
			//Get the Accounts assigned to group
			$query .= "select ".$table_prefix."_account.accountid as id,".$table_prefix."_account.accountname as name,".$table_prefix."_groups.groupname as groupname, 'Accounts ' as Type from ".$table_prefix."_account inner join ".$table_prefix."_crmentity on ".$table_prefix."_crmentity.crmid=".$table_prefix."_account.accountid inner join ".$table_prefix."_groups on ".$table_prefix."_crmentity.smownerid=".$table_prefix."_groups.groupid where ".$table_prefix."_crmentity.deleted=0 and ".$table_prefix."_account.accountid > 0";
			if (count($groupids) > 0){
				$query .= " and ".$table_prefix."_groups.groupid in (". generateQuestionMarks($groupids). ")";
				array_push($params, $groupids);
			}
		}

		if(vtlib_isModuleActive("Contacts") && isPermitted('Contacts','index') =='yes'){
			if($query != ''){
            	$query .=" union all ";
			}
            //Get the Contacts assigned to group
			$query .= "select ".$table_prefix."_contactdetails.contactid as id, ".$table_prefix."_contactdetails.lastname as name ,".$table_prefix."_groups.groupname as groupname, 'Contacts ' as Type from ".$table_prefix."_contactdetails inner join ".$table_prefix."_crmentity on ".$table_prefix."_crmentity.crmid=".$table_prefix."_contactdetails.contactid inner join ".$table_prefix."_groups on ".$table_prefix."_crmentity.smownerid = ".$table_prefix."_groups.groupid where ".$table_prefix."_crmentity.deleted=0 and ".$table_prefix."_contactdetails.contactid > 0";
			if (count($groupids) > 0) {
				$query .= " and ".$table_prefix."_groups.groupid in (". generateQuestionMarks($groupids). ")";
				array_push($params, $groupids);
			}
		}

		if(vtlib_isModuleActive("Campaigns") && isPermitted('Campaigns','index') =='yes'){
			if($query != ''){
				$query .=" union all ";
			}
			//Get the Campaigns assigned to group(Campaign status not Complete -- hardcoded value)
			$query .= "select ".$table_prefix."_campaign.campaignid as id, ".$table_prefix."_campaign.campaignname as name, ".$table_prefix."_groups.groupname as groupname,'Campaigns ' as Type from ".$table_prefix."_campaign inner join  ".$table_prefix."_crmentity on ".$table_prefix."_crmentity.crmid = ".$table_prefix."_campaign.campaignid inner join ".$table_prefix."_groups on ".$table_prefix."_crmentity.smownerid = ".$table_prefix."_groups.groupid where ".$table_prefix."_crmentity.deleted=0  and (".$table_prefix."_campaign.campaignstatus != 'Complete') and ".$table_prefix."_campaign.campaignid > 0";
			if (count($groupids) > 0) {
				$query .= " and ".$table_prefix."_groups.groupid in (". generateQuestionMarks($groupids). ")";
				array_push($params, $groupids);
			}
		}

		if(vtlib_isModuleActive("Quotes") && isPermitted('Quotes','index') == 'yes'){
			if($query != ''){
				$query .=" union all ";
			}
			//Get the Quotes assigned to group(Quotes stage not Rejected -- hardcoded value)
			$query .="select ".$table_prefix."_quotes.quoteid as id,".$table_prefix."_quotes.subject as name, ".$table_prefix."_groups.groupname as groupname ,'Quotes 'as Type from ".$table_prefix."_quotes inner join ".$table_prefix."_crmentity on ".$table_prefix."_crmentity.crmid = ".$table_prefix."_quotes.quoteid inner join ".$table_prefix."_groups on ".$table_prefix."_crmentity.smownerid = ".$table_prefix."_groups.groupid where ".$table_prefix."_crmentity.deleted=0  and (".$table_prefix."_quotes.quotestage != 'Rejected') and ".$table_prefix."_quotes.quoteid > 0";
			if (count($groupids) > 0) {
				$query .= " and ".$table_prefix."_groups.groupid in (". generateQuestionMarks($groupids). ")";
				array_push($params, $groupids);
			}
		}

		if(vtlib_isModuleActive("SalesOrder") && isPermitted('SalesOrder','index') =='yes'){
			if($query != ''){
				$query .=" union all ";
			}
            //Get the Sales Order assigned to group
            $query .="select ".$table_prefix."_salesorder.salesorderid as id, ".$table_prefix."_salesorder.subject as name,".$table_prefix."_groups.groupname as groupname,'SalesOrder ' as Type from ".$table_prefix."_salesorder inner join ".$table_prefix."_crmentity on ".$table_prefix."_crmentity.crmid = ".$table_prefix."_salesorder.salesorderid inner join ".$table_prefix."_groups on ".$table_prefix."_crmentity.smownerid = ".$table_prefix."_groups.groupid where ".$table_prefix."_crmentity.deleted=0 and ".$table_prefix."_salesorder.salesorderid > 0";
			if (count($groupids) > 0){
				$query .= " and ".$table_prefix."_groups.groupid in (". generateQuestionMarks($groupids). ")";
				array_push($params, $groupids);
			}
		}

		if(vtlib_isModuleActive("Invoice") && isPermitted('Invoice','index') =='yes'){
			if($query != ''){
				$query .=" union all ";
			}
			//Get the Sales Order assigned to group(Invoice status not Paid -- hardcoded value)
			$query .="select ".$table_prefix."_invoice.invoiceid as Id , ".$table_prefix."_invoice.subject as Name, ".$table_prefix."_groups.groupname as groupname,'Invoice ' as Type from ".$table_prefix."_invoice inner join ".$table_prefix."_crmentity on ".$table_prefix."_crmentity.crmid = ".$table_prefix."_invoice.invoiceid inner join ".$table_prefix."_groups on ".$table_prefix."_crmentity.smownerid = ".$table_prefix."_groups.groupid where ".$table_prefix."_crmentity.deleted=0 and(".$table_prefix."_invoice.invoicestatus != 'Paid') and ".$table_prefix."_invoice.invoiceid > 0";
			if (count($groupids) > 0){
				$query .= " and ".$table_prefix."_groups.groupid in (". generateQuestionMarks($groupids). ")";
				array_push($params, $groupids);
			}
		}

		if(vtlib_isModuleActive("PurchaseOrder") && isPermitted('PurchaseOrder','index') == 'yes'){
			if($query != ''){
				$query .=" union all ";
			}
			//Get the Purchase Order assigned to group
			$query .="select ".$table_prefix."_purchaseorder.purchaseorderid as id,".$table_prefix."_purchaseorder.subject as name,".$table_prefix."_groups.groupname as groupname, 'PurchaseOrder ' as Type from ".$table_prefix."_purchaseorder inner join ".$table_prefix."_crmentity on ".$table_prefix."_crmentity.crmid = ".$table_prefix."_purchaseorder.purchaseorderid inner join  ".$table_prefix."_groups on ".$table_prefix."_crmentity.smownerid =".$table_prefix."_groups.groupid where ".$table_prefix."_crmentity.deleted=0 and ".$table_prefix."_purchaseorder.purchaseorderid >0";
			if (count($groupids) > 0) {
				$query .= " and ".$table_prefix."_groups.groupid in (". generateQuestionMarks($groupids). ")";
				array_push($params, $groupids);
			}
		}

		if(vtlib_isModuleActive("Documents") && isPermitted('Documents','index') == 'yes'){
			if($query != ''){
				$query .=" union all ";
			}
			//Get the Purchase Order assigned to group
			$query .="select ".$table_prefix."_notes.notesid as id,".$table_prefix."_notes.title as name,".$table_prefix."_groups.groupname as groupname, 'Documents' as Type from ".$table_prefix."_notes inner join ".$table_prefix."_crmentity on ".$table_prefix."_crmentity.crmid = ".$table_prefix."_notes.notesid inner join  ".$table_prefix."_groups on ".$table_prefix."_crmentity.smownerid =".$table_prefix."_groups.groupid where ".$table_prefix."_crmentity.deleted=0 and ".$table_prefix."_notes.notesid > 0";
			if (count($groupids) > 0) {
				$query .= " and ".$table_prefix."_groups.groupid in (". generateQuestionMarks($groupids). ")";
				array_push($params, $groupids);
			}
		}

		$log->info("Here is the where clause for the list view: $query");
		//crmv@limit query
		$result = $adb->limitpQuery($query,0,$maxval,$params);
		//crmv@limit query end
		$title=array();
		$title[]='myGroupAllocation.gif';
		$title[]=$app_strings['LBL_GROUP_ALLOCATION_TITLE'];
		$title[]='home_mygrp';
		$header=array();
		$header[]=$app_strings['LBL_ENTITY_NAME'];
		$header[]=$app_strings['LBL_GROUP_NAME'];
		$header[]=$app_strings['LBL_ENTITY_TYPE'];

		if(count($groupids) > 0){
			$i=1;
			while($row = $adb->fetch_array($result)){
				$value=array();
				$row["type"]=trim($row["type"]);
				if($row["type"] == "Tickets"){
					$list = '<a href=index.php?module=HelpDesk';
					$list .= '&action=DetailView&record='.$row["id"].'>'.$row["name"].'</a>';
				}elseif($row["type"] == "Activities"){
					$row["type"] = 'Calendar';
					$acti_type = getActivityType($row["id"]);
					$list = '<a href=index.php?module='.$row["type"];
					if($acti_type == 'Task'){
						$list .= '&activity_mode=Task';
					}elseif($acti_type == 'Call' || $acti_type == 'Meeting'){
						$list .= '&activity_mode=Events';
					}
					$list .= '&action=DetailView&record='.$row["id"].'>'.$row["name"].'</a>';
				}else{
					$list = '<a href=index.php?module='.$row["type"];
					$list .= '&action=DetailView&record='.$row["id"].'>'.$row["name"].'</a>';
				}

				$value[]=$list;
				$value[]= $row["groupname"];
				$value[]= $row["type"];
				$entries[$row["id"]]=$value;
				$i++;
			}
		}

		$values=Array('Title'=>$title,'Header'=>$header,'Entries'=>$entries);
		if(count($entries)>0){
			return $values;
		}
	}
}