<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

global $app_strings,$mod_strings, $adb, $db;
global $app_list_strings;
global $theme;
$theme_path="themes/".$theme."/";
$image_path=$theme_path."images/";
require_once('include/utils/utils.php');
require_once 'include/Webservices/Utils.php';
require_once('include/utils/ModuleHomeView.php');

class CustomView extends CRMEntity{

	var $module_list = Array();

	var $customviewmodule;

	var $list_fields;

	var $list_fields_name;

	var $setdefaultviewid;

	var $escapemodule;

	var $mandatoryvalues;

	var $showvalues;

	var $data_type;

	// Information as defined for this instance in the database table.
	protected $_status = false;
	protected $_userid = false;
	protected $meta;
	protected $moduleMetaInfo;

	/** This function sets the currentuser id to the class variable smownerid,
	  * modulename to the class variable customviewmodule
	  * @param $module -- The module Name:: Type String(optional)
	  * @returns  nothing
	 */
	function __construct($module="")
	{
		global $current_user,$adb;
		$this->customviewmodule = $module;
		$this->escapemodule[] =	$module."_";
		$this->escapemodule[] = "_";
		$this->smownerid = $current_user->id;
		$this->moduleMetaInfo = array();
		if($module != "") {
			$this->meta = $this->getMeta($module, $current_user);
		}
		$this->getAdvFilterOptions();	//crmv@26161
	}

	/**
	 *
	 * @param String:ModuleName $module
	 * @return EntityMeta
	 */
	public function getMeta($module, $user) {

		if (empty($this->moduleMetaInfo[$module])) {
			$handler = vtws_getModuleHandlerFromName($module, $user);
			$meta = $handler->getMeta();
			$this->moduleMetaInfo[$module] = $meta;
		}
		return $this->moduleMetaInfo[$module];
	}

	/** To get the customViewId of the specified module
	  * @param $module -- The module Name:: Type String
	  * @returns  customViewId :: Type Integer
	 */
	//crmv@102334
	function getViewId($module)
	{
		global $adb,$current_user,$table_prefix;
		$now_action = vtlib_purify($_REQUEST['action']);
		if(empty($_REQUEST['viewname'])) {
			$lvs_viewname = getLVS($module,'viewname');
			if (!empty($lvs_viewname)) {
				$viewid = $lvs_viewname;
			//} maybe it is deprecated!
			//elseif($this->setdefaultviewid != "") {
			//	$viewid = $this->setdefaultviewid;
			} else {
				$MHW = ModuleHomeView::getInstance($module, $current_user->id);
				$viewid = $MHW->getModHomeCvId();
				if (empty($viewid)) {
					$query="select cvid from ".$table_prefix."_customview where setdefault=1 and entitytype=? and coalesce(userid,0) = 0"; //crmv@36516
					$cvresult=$adb->pquery($query, array($module));
					if($adb->num_rows($cvresult) > 0) {
						$viewid = $adb->query_result($cvresult,0,'cvid');
					} else $viewid = '';
				}
			}
			//crmv@481398
			if ($now_action == 'Popup'){
				$now_action = 'index';
			}
			//crmv@481398e
			if($viewid == '' || $viewid == 0 || $this->isPermittedCustomView($viewid,$now_action,$module) != 'yes') {
				$query="select cvid from ".$table_prefix."_customview where viewname='All' and entitytype=?";
				$cvresult=$adb->pquery($query, array($module));
				$viewid = $adb->query_result($cvresult,0,'cvid');
			}
		}
		else {
			//crmv@183872
			$viewname = vtlib_purify($_REQUEST['viewname']);
			if ((is_string($viewname) && strtolower($viewname) == 'all') || empty($viewname)) {
				$viewid = $this->getViewIdByName('All', $module);
			} elseif (is_string($viewname) && !is_numeric($viewname)) {
				$viewid = $this->getViewIdByName($viewname, $module, $current_user->id);
			} else {
				$viewid = $viewname;
			}
			//crmv@183872e
			if($this->isPermittedCustomView($viewid,$now_action,$this->customviewmodule) != 'yes')
				$viewid = 0;
		}
		setLVS($module,$viewid,'viewname');
		return $viewid;
	}
	//crmv@102334e
	
	//Return id of a view : Added by Pavani
	//crmv@183872
	function getViewIdByName($viewname, $module, $userid='')
	{
		global $adb, $table_prefix;
		if(isset($viewname)) {
			$query = "select cvid from ".$table_prefix."_customview where viewname=? and entitytype=?";
			$params = array($viewname,$module);
			if (!empty($userid)) {
				$query .= " and userid=?";
				$params[] = $userid;
			}
			$cvresult=$adb->pquery($query, $params);
			$viewid = $adb->query_result($cvresult,0,'cvid');;
			return $viewid;
		} else {
			return 0;
		}
	}
	//crmv@183872e
	
	// crmv@49398
	// Returns true if the record is included in the customview $viewid, false otherwise
	static function isRecordInView($viewid, $module, $crmid) {
		global $adb, $current_user;

		$queryGenerator = QueryGenerator::getInstance($module, $current_user);
		$queryGenerator->initForCustomViewById($viewid);
		
		// crmv@173184
		$list_query = $queryGenerator->getQuery();
		$meta = $queryGenerator->getMeta($module);
		
		$baseTable = $meta->getEntityBaseTable();
		$idCol = $meta->getIdColumn();
		
		$list_query .= " AND $baseTable.$idCol = ?";
		// crmv@173184e
		
		$res = $adb->pquery($list_query, array($crmid));

		return ($res && $adb->num_rows($res) > 0);
	}

	// Returns a list of all the viewid that contains the record
	// TODO: viewid in related modules
	function getRecordViews($module, $crmid, $mobileOnly = false) {
		global $adb, $table_prefix;

		$query = $this->getCustomViewQuery($module, $params);
		if ($mobileOnly) {
			$query = preg_replace('/order by .*/i', '', $query);
			$query .= " AND {$table_prefix}_customview.setmobile = 1 AND ({$table_prefix}_customview.reportid IS NULL OR {$table_prefix}_customview.reportid = 0)";
		}

		$list = array();
		$res = $adb->pquery($query, $params);
		if ($res && $adb->num_rows($res) > 0) {
			while ($row = $adb->FetchByAssoc($res, -1, false)) {
				$viewid = intval($row['cvid']);
				if ($this->isRecordInView($viewid, $module, $crmid)) {
					$list[] = $viewid;
				}

			}
		}

		return $list;
	}
	// crmv@49398e

	// return type array
	/** to get the details of a customview
	  * @param $cvid :: Type Integer
	  * @returns  $customviewlist Array in the following format
	  * $customviewlist = Array('viewname'=>value,
	  *                         'setdefault'=>defaultchk,
	  *                         'setmetrics'=>setmetricschk)
	 */

	function getCustomViewByCvid($cvid)
	{
		global $adb,$current_user,$table_prefix;

		require('user_privileges/requireUserPrivileges.php'); // crmv@39110

		$ssql = "select ".$table_prefix."_customview.* from ".$table_prefix."_customview inner join ".$table_prefix."_tab on ".$table_prefix."_tab.name = ".$table_prefix."_customview.entitytype";
		$ssql .= " where ".$table_prefix."_customview.cvid=?";
		$sparams = array($cvid);

		if($is_admin == false) {
			$ssql .= " and (".$table_prefix."_customview.status=0 or ".$table_prefix."_customview.userid = ? or ".$table_prefix."_customview.status = 3 or ".$table_prefix."_customview.userid in(select ".$table_prefix."_user2role.userid from ".$table_prefix."_user2role inner join ".$table_prefix."_users on ".$table_prefix."_users.id=".$table_prefix."_user2role.userid inner join ".$table_prefix."_role on ".$table_prefix."_role.roleid=".$table_prefix."_user2role.roleid where ".$table_prefix."_role.parentrole like '".$current_user_parent_role_seq."::%'))";
			array_push($sparams, $current_user->id);
		}
		$result = $adb->pquery($ssql, $sparams);

		//crmv@102334
		$MHW = ModuleHomeView::getInstance($this->customviewmodule, $current_user->id);
		$viewid = $MHW->getModHomeCvId();
		if (!empty($viewid)) $def_cvid = $viewid;
		//crmv@102334e

		$customviewlist = array();

		while($cvrow=$adb->fetch_array($result))
		{
			$customviewlist["viewname"] = $cvrow["viewname"];
			if((isset($def_cvid) || $def_cvid != '') && $def_cvid == $cvid) {
				$customviewlist["setdefault"] = 1;
			} else {
				$customviewlist["setdefault"] = $cvrow["setdefault"];
			}
			$customviewlist["setmetrics"] = $cvrow["setmetrics"];
			$customviewlist["setmobile"] = $cvrow["setmobile"]; // crmv@49398
			$customviewlist["userid"] = $cvrow["userid"];
			$customviewlist["status"] = $cvrow["status"];
			$customviewlist["reportid"] = $cvrow["reportid"];	//crmv@31775
			$this->reportid = $customviewlist["reportid"];	//crmv@34627
		}

		return $customviewlist;
	}

	// crmv@33545
	// ritorna la query per avere la lista dei filtri per un modulo
	function getCustomViewQuery($module, &$sparams) {
		global $adb, $table_prefix, $current_user;

		$tabid = getTabid($module);
		require('user_privileges/requireUserPrivileges.php'); // crmv@39110

		$ssql = "
		select
		{$table_prefix}_customview.*, {$table_prefix}_users.user_name
		from {$table_prefix}_customview
		inner join {$table_prefix}_tab on {$table_prefix}_tab.name = {$table_prefix}_customview.entitytype
		left join {$table_prefix}_users on {$table_prefix}_customview.userid = {$table_prefix}_users.id
		where {$table_prefix}_tab.tabid = ?";
		$sparams = array($tabid);

		if ($is_admin == false){
			$ssql .= " and ({$table_prefix}_customview.status=0 or {$table_prefix}_customview.userid = ? or {$table_prefix}_customview.status = 3 or {$table_prefix}_customview.userid in (
			select {$table_prefix}_user2role.userid
			from {$table_prefix}_user2role
			inner join {$table_prefix}_users on {$table_prefix}_users.id={$table_prefix}_user2role.userid
			inner join {$table_prefix}_role on {$table_prefix}_role.roleid={$table_prefix}_user2role.roleid
			where {$table_prefix}_role.parentrole like '".$current_user_parent_role_seq."::%'))";
			array_push($sparams, $current_user->id);
		}
		//crmv@78053
		if (!$adb->isMssql()) {
			$ssql .= " ORDER BY viewname";
		}
		//crmv@78053e
		return $ssql;
	}
	// crmv@33545e

	// crmv@39110
	function getPublicFilters() {
		global $adb;

		global $table_prefix;

		$tabid = getTabid($this->customviewmodule);

		$ssql = "
			select
				{$table_prefix}_customview.*, {$table_prefix}_users.user_name
			from {$table_prefix}_customview
				inner join {$table_prefix}_tab on {$table_prefix}_tab.name = {$table_prefix}_customview.entitytype
				left join {$table_prefix}_users on {$table_prefix}_customview.userid = {$table_prefix}_users.id
			where {$table_prefix}_tab.tabid = ? and {$table_prefix}_customview.status in (0,3)
			order by viewname";
		$sparams = array($tabid);

		$ret = array();
		$res = $adb->pquery($ssql, $sparams);
		if ($res) {
			while ($row = $adb->FetchByAssoc($res, -1, false)) {
				$ret[] = $row;
			}
		}
		return $ret;
	}
	// crmv@39110e


	/** to get the customviewCombo for the class variable customviewmodule
	  * @param $viewid :: Type Integer
	  * $viewid will make the corresponding selected
	  * @returns  $customviewCombo :: Type String
	 */

	function getCustomViewCombo($viewid='', $markselected=true)
	{
		global $adb,$current_user;
		global $app_strings;
		$tabid = getTabid($this->customviewmodule);

		require('user_privileges/requireUserPrivileges.php'); // crmv@39110

		$shtml_user = '';
		$shtml_pending = '';
		$shtml_public = '';
		$shtml_others = '';

		$selected = 'selected';
		if ($markselected == false) $selected = '';

		$ssql = $this->getCustomViewQuery($this->customviewmodule, $sparams); // crmv@33545
		$result = $adb->pquery($ssql, $sparams);
		while($cvrow=$adb->fetch_array($result))
		{
			if($cvrow['viewname'] == 'All')
			{
				$cvrow['viewname'] = $app_strings['COMBO_ALL'];
			}
			//crmv@17001
			if($tabid == 9 && in_array($cvrow['viewname'],array('Events','Tasks')))
			{
				$cvrow['viewname'] = $app_strings[$cvrow['viewname']];
			}
			//crmv@17001e

			$option = '';
			$viewname = $cvrow['viewname'];
			if ($cvrow['status'] == CV_STATUS_DEFAULT || $cvrow['userid'] == $current_user->id) {
				$disp_viewname = $viewname;
			} else {
				$disp_viewname = $viewname . " [" . $cvrow['user_name'] . "] ";
			}


			if($cvrow['setdefault'] == 1 && $viewid =='')
			{
				$disp_viewname = $viewname; // crmv@152712
				$option = "<option $selected value=\"".$cvrow['cvid']."\">".$disp_viewname."</option>";
				$this->setdefaultviewid = $cvrow['cvid'];
			}
			elseif($cvrow['cvid'] == $viewid)
			{
				$disp_viewname = $viewname; // crmv@152712
				$option = "<option $selected value=\"".$cvrow['cvid']."\">".$disp_viewname."</option>";
				$this->setdefaultviewid = $cvrow['cvid'];
			}
			else
			{
				$option = "<option value=\"".$cvrow['cvid']."\">".$disp_viewname."</option>";
			}

			// Add the option to combo box at appropriate section
			if($option != '') {
				if ($cvrow['status'] == CV_STATUS_DEFAULT || $cvrow['userid'] == $current_user->id) {
					$shtml_user .= $option;
				} elseif ($cvrow['status'] == CV_STATUS_PUBLIC) {
					if ($shtml_public == '')
						$shtml_public = "<option disabled>--- " .$app_strings['LBL_PUBLIC']. " ---</option>";
					$shtml_public .= $option;
				} elseif ($cvrow['status'] == CV_STATUS_PENDING) {
					if ($shtml_pending == '')
						$shtml_pending = "<option disabled>--- " .$app_strings['LBL_PENDING']. " ---</option>";
					$shtml_pending .= $option;
				} else {
					if ($shtml_others == '')
						$shtml_others = "<option disabled>--- " .$app_strings['LBL_OTHERS']. " ---</option>";
					$shtml_others .= $option;
				}
			}
		}
		$shtml = $shtml_user;
		if ($is_admin == true) $shtml .= $shtml_pending;
		$shtml = $shtml . $shtml_public . $shtml_others;
		if (!$shtml)
			$shtml = "<option value=\"0\">".$app_strings['LBL_NONE']."</option>";
		return $shtml;
	}

	/** to get the getColumnsListbyBlock for the given module and Block
	  * @param $module :: Type String
	  * @param $block :: Type Integer
	  * @returns  $columnlist Array in the format
	  * $columnlist = Array ($fieldlabel =>'$fieldtablename:$fieldcolname:$fieldname:$module_$fieldlabel1:$fieldtypeofdata',
	                         $fieldlabel1 =>'$fieldtablename1:$fieldcolname1:$fieldname1:$module_$fieldlabel11:$fieldtypeofdata1',
					|
			         $fieldlabeln =>'$fieldtablenamen:$fieldcolnamen:$fieldnamen:$module_$fieldlabel1n:$fieldtypeofdatan')
	 */

	function getColumnsListbyBlock($module,$block,$mode)	//crmv@103450
	{
		global $adb,$table_prefix;
		$block_ids = explode(",", $block);
		$tabid = getTabid($module);
		global $current_user;
	    require('user_privileges/requireUserPrivileges.php'); // crmv@39110
		if (empty($this->meta)) {
			$this->meta = $this->getMeta($module, $current_user);
		}
		if($tabid == 9)
			$tabid ="9,16";
		$display_type = " ".$table_prefix."_field.displaytype in (1,2,3)";

		if($is_admin == true || $profileGlobalPermission[1] == 0 || $profileGlobalPermission[2] == 0)
		{
			$tab_ids = explode(",", $tabid);
			$sql = "select * from ".$table_prefix."_field ";
			$sql.= " where ".$table_prefix."_field.tabid in (". generateQuestionMarks($tab_ids) .") and ".$table_prefix."_field.block in (". generateQuestionMarks($block_ids) .") and ".$table_prefix."_field.presence in (0,2) and";
			$sql.= $display_type;

			// crmv@89417 crmv@94838
			if($tabid == 9 || $tabid==16) {
				$sql.= " and ".$table_prefix."_field.fieldname not in ('notime','duration_minutes','duration_hours')";
			}
			// crmv@89417e crmv@94838

			$sql.= " order by sequence";
			$params = array($tab_ids, $block_ids);
		}
		else
		{
			$tab_ids = explode(",", $tabid);
			$profileList = getCurrentUserProfileList();
			$sql = "select * from ".$table_prefix."_field inner join ".$table_prefix."_def_org_field on ".$table_prefix."_def_org_field.fieldid=".$table_prefix."_field.fieldid ";
			$sql.= " where ".$table_prefix."_field.tabid in (". generateQuestionMarks($tab_ids) .") and ".$table_prefix."_field.block in (". generateQuestionMarks($block_ids) .") and";
			$sql.= "$display_type and ".$table_prefix."_def_org_field.visible=0 and ".$table_prefix."_field.presence in (0,2)";

			$params = array($tab_ids, $block_ids);

		    $sql.=" AND EXISTS(SELECT * FROM ".$table_prefix."_profile2field WHERE ".$table_prefix."_profile2field.fieldid = ".$table_prefix."_field.fieldid ";
		        if (count($profileList) > 0) {
			  	 	$sql.=" AND ".$table_prefix."_profile2field.profileid IN (". generateQuestionMarks($profileList) .") ";
			  	 	array_push($params, $profileList);
			}
		    $sql.=" AND ".$table_prefix."_profile2field.visible = 0) ";

		    // crmv@89417 crmv@181281
		    $focusNewsletter = CRMEntity::getInstance('Newsletter');
		    if($tabid == 9 || $tabid==16) {
		    	$sql.= " and ".$table_prefix."_field.fieldname not in ('notime','duration_minutes','duration_hours')";
		    } elseif (array_key_exists($module,$focusNewsletter->email_fields)) {
		    	$sql.= " and ".$table_prefix."_field.fieldname not in ('newsletter_unsubscrpt')";
		    }
		    // crmv@89417e crmv@181281e

			$sql.= " order by sequence";
		}
		if($tabid == '9,16')
             $tabid = "9";
		$result = $adb->pquery($sql, $params);
		$noofrows = $adb->num_rows($result);
		/* crmv@130625 DEPRECATED
		//Added on 14-10-2005 -- added ticket id in list
		if($module == 'HelpDesk' && $block == 25)
		{
			$module_columnlist[$table_prefix.'_crmentity:crmid::HelpDesk_Ticket_ID:I'] = 'Ticket ID';
		}*/
		//Added to include vte_activity type in vte_activity vte_customview list
		if($module == 'Calendar' && $block == 19)
		{
			$module_columnlist[$table_prefix.'_activity:activitytype:activitytype:Calendar_Activity_Type:V'] = 'Activity Type';
		}
	//crmv@8056
//		if($module == 'SalesOrder' && $block == 63)
//			$module_columnlist['vte_crmentity:crmid::SalesOrder_Order_No:I'] = $app_strings['Order No'];
//
//		if($module == 'PurchaseOrder' && $block == 57)
//			$module_columnlist['vte_crmentity:crmid::PurchaseOrder_Order_No:I'] = $app_strings['Order No'];
//
//		if($module == 'Quotes' && $block == 51)
//                        $module_columnlist['vte_crmentity:crmid::Quotes_Quote_No:I'] = $app_strings['Quote No'];
//crmv@8056
		$moduleFieldList = $this->meta->getModuleFields();
		$distinct_fields = array();
		for($i=0; $i<$noofrows; $i++)
		{
			$fieldtablename = $adb->query_result($result,$i,"tablename");
			$fieldcolname = $adb->query_result($result,$i,"columnname");
			if (in_array($fieldtablename.$fieldcolname,$distinct_fields))
				continue;
			$distinct_fields[] = $fieldtablename.$fieldcolname;
			$fieldname = $adb->query_result($result,$i,"fieldname");
			$fieldtype = $adb->query_result($result,$i,"typeofdata");
			$fieldtype = explode("~",$fieldtype);
			$fieldtypeofdata = $fieldtype[0];
			$fieldlabel = $adb->query_result($result,$i,"fieldlabel");
			$field = $moduleFieldList[$fieldname];
			if ($fieldname == 'hdnTaxType') continue; // crmv@88444 - ignore taxtype, it's problematic
			//crmv@12035
			if(!empty($field) && in_array($field->getFieldDataType(),Array('reference','owner'))) {
				$fieldtypeofdata = 'V';
			} else {
			//crmv@12035 end
				//Here we Changing the displaytype of the field. So that its criteria will be
				//displayed Correctly in Custom view Advance Filter.
				$fieldtypeofdata=ChangeTypeOfData_Filter($fieldtablename,$fieldcolname,
						$fieldtypeofdata);
			}
			//crmv@103450
			$readonly = $adb->query_result($result,$i,"readonly");
			$sdk_files = SDK::getViews($module,$mode);
			if (!empty($sdk_files)) {
				foreach($sdk_files as $sdk_file) {
					$success = false;
					$readonly_old = $readonly;
					include($sdk_file['src']);
					SDK::checkReadonly($readonly_old,$readonly,$sdk_file['mode']);
					if ($success && $sdk_file['on_success'] == 'stop') {
						break;
					}
				}
			}
			if ($readonly == 100) continue;
			//crmv@103450e
			if($fieldlabel == "Related To")
			{
				$fieldlabel = "Related to";
			}
			if($fieldlabel == "Start Date & Time")
			{
				$fieldlabel = "Start Date";
				if($module == 'Calendar' && $block == 19)
					$module_columnlist[$table_prefix.'_activity:time_start::Calendar_Start_Time:I'] = 'Start Time';

			}
			$fieldlabel1 = str_replace(" ","_",$fieldlabel);
			$optionvalue = $fieldtablename.":".$fieldcolname.":".$fieldname.":".$module."_".
				$fieldlabel1.":".$fieldtypeofdata;
			//added to escape attachments fields in customview as we have multiple attachments
			$fieldlabel = getTranslatedString($fieldlabel,$module); // crmv@98977 added to support i18n issue
			if($module != 'HelpDesk' || $fieldname !='filename')
				$module_columnlist[$optionvalue] = $fieldlabel;
			if($fieldtype[1] == "M")
			{
				$this->mandatoryvalues[] = "'".$optionvalue."'";
				$this->showvalues[] = $fieldlabel;
				$this->data_type[getTranslatedString($fieldlabel,$module)] = $fieldtype[1];
			}
//crmv@8590e
		}
		return $module_columnlist;
	}

	/** to get the getModuleColumnsList for the given module
	  * @param $module :: Type String
	  * @returns  $ret_module_list Array in the following format
	  * $ret_module_list =
		Array ('module' =>
				Array('BlockLabel1' =>
						Array('$fieldtablename:$fieldcolname:$fieldname:$module_$fieldlabel1:$fieldtypeofdata'=>$fieldlabel,
	                                        Array('$fieldtablename1:$fieldcolname1:$fieldname1:$module_$fieldlabel11:$fieldtypeofdata1'=>$fieldlabel1,
				Array('BlockLabel2' =>
						Array('$fieldtablename:$fieldcolname:$fieldname:$module_$fieldlabel1:$fieldtypeofdata'=>$fieldlabel,
	                                        Array('$fieldtablename1:$fieldcolname1:$fieldname1:$module_$fieldlabel11:$fieldtypeofdata1'=>$fieldlabel1,
					 |
				Array('BlockLabeln' =>
						Array('$fieldtablename:$fieldcolname:$fieldname:$module_$fieldlabel1:$fieldtypeofdata'=>$fieldlabel,
	                                        Array('$fieldtablename1:$fieldcolname1:$fieldname1:$module_$fieldlabel11:$fieldtypeofdata1'=>$fieldlabel1,


	 */

	function getModuleColumnsList($module,$reportsubmodules=true,$mode='cv_columns')	//crmv@34627 crmv@103450
	{
	
		$ret_module_list = array(); // crmv@197210
		$module_info = $this->getCustomViewModuleInfo($module,$reportsubmodules);	//crmv@34627
		foreach($this->module_list[$module] as $key=>$value)
		{
			$columnlist = $this->getColumnsListbyBlock($module,$value,$mode);	//crmv@103450

			if(isset($columnlist))
			{
				$ret_module_list[$module][$key] = $columnlist;
			}
		}
		//crmv@34627
		if ($reportsubmodules) {
			$reportmodules = $this->getReportModules();
			if ($reportmodules) {
				foreach ($reportmodules as $reportmodule) {
					if ($reportmodule != $module) {
						$ret_module_list = array_merge($ret_module_list,$this->getModuleColumnsList($reportmodule,false));
					}
				}
			}
		}
		//crmv@34627e
		return $ret_module_list;
	}

	// crmv@129135
	/** to get the getModuleColumnsList for the given customview
	  * @param $cvid :: Type Integer
	  * @returns  $columnlist Array in the following format
	  * $columnlist = Array( $columnindex => $columnname,
	  *			 $columnindex1 => $columnname1,
	  *					|
	  *			 $columnindexn => $columnnamen)
	  */
	function getColumnsListByCvid($cvid)
	{
		global $adb,$table_prefix;
		
		$cvinfo = $this->getCustomViewByCvid($cvid);
		$inPopup = ($_REQUEST['action'] == 'Popup');

		$sSQL = "select ".$table_prefix."_cvcolumnlist.* from ".$table_prefix."_cvcolumnlist";
		$sSQL .= " inner join ".$table_prefix."_customview on ".$table_prefix."_customview.cvid = ".$table_prefix."_cvcolumnlist.cvid";
		$sSQL .= " where ".$table_prefix."_customview.cvid =? order by ".$table_prefix."_cvcolumnlist.columnindex";
		$result = $adb->pquery($sSQL, array($cvid));

		$columnlist = array();

		while($columnrow = $adb->fetch_array($result))
		{
			if (!empty($columnrow['columnname'])) {
				if ($inPopup && $cvinfo['reportid'] > 0) {
					list($table, $column, $field, $label) = explode(':', $columnrow['columnname']);
					list($module, $xx) = explode('_', $label, 2);
					if ($module && $module != $this->customviewmodule) {
						// skip columns of other modules in popups
						continue;
					}
				}
				$columnlist[$columnrow['columnindex']] = $columnrow['columnname'];
			}
		}

		return $columnlist;
	}
	// crmv@129135e

	/** to get the standard filter fields or the given module
	  * @param $module :: Type String
	  * @returns  $stdcriteria_list Array in the following format
	  * $stdcriteria_list = Array( $tablename:$columnname:$fieldname:$module_$fieldlabel => $fieldlabel,
	  *			 $tablename1:$columnname1:$fieldname1:$module_$fieldlabel1 => $fieldlabel1,
	  *					|
	  *			 $tablenamen:$columnnamen:$fieldnamen:$module_$fieldlabeln => $fieldlabeln)
	  */
	function getStdCriteriaByModule($module)
	{
		global $adb,$table_prefix;
		$tabid = getTabid($module);

		global $current_user;
       	require('user_privileges/requireUserPrivileges.php'); // crmv@39110

		$module_info = $this->getCustomViewModuleInfo($module);
		foreach($this->module_list[$module] as $key=>$blockid)
		{
			if (strpos($blockid,",") !==false){
				foreach (explode(",",$blockid) as $block){
					$blockids[] = $block;
				}
			}
			else
				$blockids[] = $blockid;
		}

		if($is_admin == true || $profileGlobalPermission[1] == 0 || $profileGlobalPermission[2] == 0)
		{
			$sql = "select * from ".$table_prefix."_field inner join ".$table_prefix."_tab on ".$table_prefix."_tab.tabid = ".$table_prefix."_field.tabid ";
			$sql.= " where ".$table_prefix."_field.tabid=? and ".$table_prefix."_field.block in (". generateQuestionMarks($blockids) .")
                        and ".$table_prefix."_field.uitype in (5,6,23,70)";
			$sql.= " and ".$table_prefix."_field.presence in (0,2) order by ".$table_prefix."_field.sequence";
			$params = array($tabid, $blockids);
		}
		else
		{
			$profileList = getCurrentUserProfileList();
			$sql = "select * from ".$table_prefix."_field inner join ".$table_prefix."_tab on ".$table_prefix."_tab.tabid = ".$table_prefix."_field.tabid inner join ".$table_prefix."_def_org_field on ".$table_prefix."_def_org_field.fieldid=".$table_prefix."_field.fieldid ";
			$sql.= " where ".$table_prefix."_field.tabid=? and ".$table_prefix."_field.block in (". generateQuestionMarks($blockids) .") and (".$table_prefix."_field.uitype in (5,6,23) or ".$table_prefix."_field.displaytype=2)";
			$sql.= " and ".$table_prefix."_def_org_field.visible=0 and ".$table_prefix."_field.presence in (0,2)";

			$params = array($tabid, $blockids);

		    $sql.=" AND EXISTS(SELECT * FROM ".$table_prefix."_profile2field WHERE ".$table_prefix."_profile2field.fieldid = ".$table_prefix."_field.fieldid ";
		        if (count($profileList) > 0) {
			  	 	$sql.=" AND ".$table_prefix."_profile2field.profileid IN (". generateQuestionMarks($profileList) .") ";
			  	 	array_push($params, $profileList);
			}
		    $sql.=" AND ".$table_prefix."_profile2field.visible = 0) ";

			$sql.= " order by ".$table_prefix."_field.sequence";
		}

		$result = $adb->pquery($sql, $params);

		while($criteriatyperow = $adb->fetch_array($result))
		{
			$fieldtablename = $criteriatyperow["tablename"];
			$fieldcolname = $criteriatyperow["columnname"];
			$fieldlabel = $criteriatyperow["fieldlabel"];
			$fieldname = $criteriatyperow["fieldname"];
			$fieldlabel1 = str_replace(" ","_",$fieldlabel);
			$optionvalue = $fieldtablename.":".$fieldcolname.":".$fieldname.":".$module."_".$fieldlabel1;
			$stdcriteria_list[$optionvalue] = $fieldlabel;
		}

		return $stdcriteria_list;

	}

	/** to get the standard filter criteria
	  * @param $selcriteria :: Type String (optional)
	  * @returns  $filter Array in the following format
	  * $filter = Array( 0 => array('value'=>$filterkey,'text'=>$mod_strings[$filterkey],'selected'=>$selected)
	  * 		     1 => array('value'=>$filterkey1,'text'=>$mod_strings[$filterkey1],'selected'=>$selected)
	  *		                             		|
	  * 		     n => array('value'=>$filterkeyn,'text'=>$mod_strings[$filterkeyn],'selected'=>$selected)
	  */
	function getStdFilterCriteria($selcriteria = "")
	{
		global $mod_strings;
		$filter = array();

		$stdfilter = Array("custom"=>"".$mod_strings['Custom']."",
				"prevfy"=>"".$mod_strings['Previous FY']."",
				"thisfy"=>"".$mod_strings['Current FY']."",
				"nextfy"=>"".$mod_strings['Next FY']."",
				"prevfq"=>"".$mod_strings['Previous FQ']."",
				"thisfq"=>"".$mod_strings['Current FQ']."",
				"nextfq"=>"".$mod_strings['Next FQ']."",
				"yesterday"=>"".$mod_strings['Yesterday']."",
				"today"=>"".$mod_strings['Today']."",
				"tomorrow"=>"".$mod_strings['Tomorrow']."",
				"lastweek"=>"".$mod_strings['Last Week']."",
				"thisweek"=>"".$mod_strings['Current Week']."",
				"nextweek"=>"".$mod_strings['Next Week']."",
				"lastmonth"=>"".$mod_strings['Last Month']."",
				"thismonth"=>"".$mod_strings['Current Month']."",
				"nextmonth"=>"".$mod_strings['Next Month']."",
				"last7days"=>"".$mod_strings['Last 7 Days']."",
				"last30days"=>"".$mod_strings['Last 30 Days']."",
				"last60days"=>"".$mod_strings['Last 60 Days']."",
				"last90days"=>"".$mod_strings['Last 90 Days']."",
				"last120days"=>"".$mod_strings['Last 120 Days']."",
				"next7days"=>"".$mod_strings['Next 7 Days']."", // crmv@60091
				"next30days"=>"".$mod_strings['Next 30 Days']."",
				"next60days"=>"".$mod_strings['Next 60 Days']."",
				"next90days"=>"".$mod_strings['Next 90 Days']."",
				"next120days"=>"".$mod_strings['Next 120 Days']."",
					);

				foreach($stdfilter as $FilterKey=>$FilterValue)
				{
					if($FilterKey == $selcriteria)
					{
						$shtml['value'] = $FilterKey;
						$shtml['text'] = $FilterValue;
						$shtml['selected'] = "selected";
					}else
					{
						$shtml['value'] = $FilterKey;
						$shtml['text'] = $FilterValue;
						$shtml['selected'] = "";
					}
					$filter[] = $shtml;
				}
				return $filter;

	}

	/** to get the standard filter criteria scripts
	  * @returns  $jsStr : Type String
	  * This function will return the script to set the start data and end date
	  * for the standard selection criteria
	  */
	function getCriteriaJS() {
		global $current_user; // crmv@150808
		
		static $dayNames = array('Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday');
	
		// get first and last day of week
		$weekstart = $current_user->weekstart;
		if ($weekstart === null || $weekstart === '') $weekstart = 1;
		$weekstart = intval($weekstart);
		$weekend = ($weekstart + 6) % 7;
		// crmv@150808e

		$today = date("Y-m-d",mktime(0, 0, 0, date("m")  , date("d"), date("Y")));
		$tomorrow  = date("Y-m-d",mktime(0, 0, 0, date("m")  , date("d")+1, date("Y")));
		$yesterday  = date("Y-m-d",mktime(0, 0, 0, date("m")  , date("d")-1, date("Y")));

		$currentmonth0 = date("Y-m-d",mktime(0, 0, 0, date("m"), "01",   date("Y")));
		$currentmonth1 = date("Y-m-t");
		$lastmonth0 = date("Y-m-d",mktime(0, 0, 0, date("m")-1, "01",   date("Y")));
		$lastmonth1 = date("Y-m-d", strtotime("last day of previous month")); // crmv@184741
		$nextmonth0 = date("Y-m-d",mktime(0, 0, 0, date("m")+1, "01",   date("Y")));		
		$nextmonth1 = date("Y-m-d", strtotime("last day of next month")); // crmv@184741
		
		// crmv@150808
		$todayNum = date('w');

		$prevstart = ($todayNum == $weekstart ? time() : strtotime("last {$dayNames[$weekstart]}"));
		$nextend = ($todayNum == $weekend ? time() : strtotime("next {$dayNames[$weekend]}"));
	
		$lastweek0 = date('Y-m-d', strtotime('-1 week', $prevstart));
		$lastweek1 = date('Y-m-d', strtotime('-1 week', $nextend));
		$thisweek0 = date('Y-m-d', $prevstart);
		$thisweek1 = date('Y-m-d', $nextend);
		$nextweek0 = date('Y-m-d', strtotime('+1 week', $prevstart));
		$nextweek1 = date('Y-m-d', strtotime('+1 week', $nextend));
		// crmv@150808e

		$next7days = date("Y-m-d",mktime(0, 0, 0, date("m")  , date("d")+6, date("Y")));
		$next30days = date("Y-m-d",mktime(0, 0, 0, date("m")  , date("d")+29, date("Y")));
		$next60days = date("Y-m-d",mktime(0, 0, 0, date("m")  , date("d")+59, date("Y")));
		$next90days = date("Y-m-d",mktime(0, 0, 0, date("m")  , date("d")+89, date("Y")));
		$next120days = date("Y-m-d",mktime(0, 0, 0, date("m")  , date("d")+119, date("Y")));

		$last7days = date("Y-m-d",mktime(0, 0, 0, date("m")  , date("d")-6, date("Y")));
		$last30days = date("Y-m-d",mktime(0, 0, 0, date("m")  , date("d")-29, date("Y")));
		$last60days = date("Y-m-d",mktime(0, 0, 0, date("m")  , date("d")-59, date("Y")));
		$last90days = date("Y-m-d",mktime(0, 0, 0, date("m")  , date("d")-89, date("Y")));
		$last120days = date("Y-m-d",mktime(0, 0, 0, date("m")  , date("d")-119, date("Y")));

		$currentFY0 = date("Y-m-d",mktime(0, 0, 0, "01", "01",   date("Y")));
		$currentFY1 = date("Y-m-t",mktime(0, 0, 0, "12", date("d"),   date("Y")));
		$lastFY0 = date("Y-m-d",mktime(0, 0, 0, "01", "01",   date("Y")-1));
		$lastFY1 = date("Y-m-t", mktime(0, 0, 0, "12", date("d"), date("Y")-1));

		$nextFY0 = date("Y-m-d",mktime(0, 0, 0, "01", "01",   date("Y")+1));
		$nextFY1 = date("Y-m-t", mktime(0, 0, 0, "12", date("d"), date("Y")+1));

		if(date("m") <= 3)
		{
			$cFq = date("Y-m-d",mktime(0, 0, 0, "01","01",date("Y")));
			$cFq1 = date("Y-m-d",mktime(0, 0, 0, "03","31",date("Y")));
			$nFq = date("Y-m-d",mktime(0, 0, 0, "04","01",date("Y")));
			$nFq1 = date("Y-m-d",mktime(0, 0, 0, "06","30",date("Y")));
			$pFq = date("Y-m-d",mktime(0, 0, 0, "10","01",date("Y")-1));
			$pFq1 = date("Y-m-d",mktime(0, 0, 0, "12","31",date("Y")-1));
		}else if(date("m") > 3 and date("m") <= 6)
		{
			$pFq = date("Y-m-d",mktime(0, 0, 0, "01","01",date("Y")));
			$pFq1 = date("Y-m-d",mktime(0, 0, 0, "03","31",date("Y")));
			$cFq = date("Y-m-d",mktime(0, 0, 0, "04","01",date("Y")));
			$cFq1 = date("Y-m-d",mktime(0, 0, 0, "06","30",date("Y")));
			$nFq = date("Y-m-d",mktime(0, 0, 0, "07","01",date("Y")));
			$nFq1 = date("Y-m-d",mktime(0, 0, 0, "09","30",date("Y")));

		}else if(date("m") > 6 and date("m") <= 9)
		{
			$nFq = date("Y-m-d",mktime(0, 0, 0, "10","01",date("Y")));
			$nFq1 = date("Y-m-d",mktime(0, 0, 0, "12","31",date("Y")));
			$pFq = date("Y-m-d",mktime(0, 0, 0, "04","01",date("Y")));
			$pFq1 = date("Y-m-d",mktime(0, 0, 0, "06","30",date("Y")));
			$cFq = date("Y-m-d",mktime(0, 0, 0, "07","01",date("Y")));
			$cFq1 = date("Y-m-d",mktime(0, 0, 0, "09","30",date("Y")));
		}
		else if(date("m") > 9 and date("m") <= 12)
		{
			$nFq = date("Y-m-d",mktime(0, 0, 0, "01","01",date("Y")+1));
			$nFq1 = date("Y-m-d",mktime(0, 0, 0, "03","31",date("Y")+1));
			$pFq = date("Y-m-d",mktime(0, 0, 0, "07","01",date("Y")));
			$pFq1 = date("Y-m-d",mktime(0, 0, 0, "09","30",date("Y")));
			$cFq = date("Y-m-d",mktime(0, 0, 0, "10","01",date("Y")));
			$cFq1 = date("Y-m-d",mktime(0, 0, 0, "12","31",date("Y")));

		}
		//crmv@32334
		$sjsStr = '<script language="JavaScript" type="text/javaScript">
			function showDateRange( type )
			{
				if (type!="custom")
				{
					document.EditView.startdate.readOnly=true
					document.EditView.enddate.readOnly=true
					getObj("jscal_trigger_date_start").style.visibility="hidden"
					getObj("jscal_trigger_date_end").style.visibility="hidden"
				}
				else
				{
					document.EditView.startdate.readOnly=false
					document.EditView.enddate.readOnly=false
					getObj("jscal_trigger_date_start").style.visibility="visible"
					getObj("jscal_trigger_date_end").style.visibility="visible"
				}
				if( type == "today" )
				{
					document.EditView.startdate.value = "'.getDisplayDate($today).'";
					document.EditView.enddate.value = "'.getDisplayDate($today).'";
				}
				else if( type == "yesterday" )
				{
					document.EditView.startdate.value = "'.getDisplayDate($yesterday).'";
					document.EditView.enddate.value = "'.getDisplayDate($yesterday).'";
				}
				else if( type == "tomorrow" )
				{

					document.EditView.startdate.value = "'.getDisplayDate($tomorrow).'";
					document.EditView.enddate.value = "'.getDisplayDate($tomorrow).'";
				}
				else if( type == "thisweek" )
				{
					document.EditView.startdate.value = "'.getDisplayDate($thisweek0).'";
					document.EditView.enddate.value = "'.getDisplayDate($thisweek1).'";
				}
				else if( type == "lastweek" )
				{
					document.EditView.startdate.value = "'.getDisplayDate($lastweek0).'";
					document.EditView.enddate.value = "'.getDisplayDate($lastweek1).'";
				}
				else if( type == "nextweek" )
				{
					document.EditView.startdate.value = "'.getDisplayDate($nextweek0).'";
					document.EditView.enddate.value = "'.getDisplayDate($nextweek1).'";
				}
				else if( type == "thismonth" )
				{
					document.EditView.startdate.value = "'.getDisplayDate($currentmonth0).'";
					document.EditView.enddate.value = "'.getDisplayDate($currentmonth1).'";
				}
				else if( type == "lastmonth" )
				{
					document.EditView.startdate.value = "'.getDisplayDate($lastmonth0).'";
					document.EditView.enddate.value = "'.getDisplayDate($lastmonth1).'";
				}
				else if( type == "nextmonth" )
				{
					document.EditView.startdate.value = "'.getDisplayDate($nextmonth0).'";
					document.EditView.enddate.value = "'.getDisplayDate($nextmonth1).'";
				}
				else if( type == "next7days" )
				{
					document.EditView.startdate.value = "'.getDisplayDate($today).'";
					document.EditView.enddate.value = "'.getDisplayDate($next7days).'";
				}
				else if( type == "next30days" )
				{
					document.EditView.startdate.value = "'.getDisplayDate($today).'";
					document.EditView.enddate.value = "'.getDisplayDate($next30days).'";
				}
				else if( type == "next60days" )
				{
					document.EditView.startdate.value = "'.getDisplayDate($today).'";
					document.EditView.enddate.value = "'.getDisplayDate($next60days).'";
				}
				else if( type == "next90days" )
				{
					document.EditView.startdate.value = "'.getDisplayDate($today).'";
					document.EditView.enddate.value = "'.getDisplayDate($next90days).'";
				}
				else if( type == "next120days" )
				{
					document.EditView.startdate.value = "'.getDisplayDate($today).'";
					document.EditView.enddate.value = "'.getDisplayDate($next120days).'";
				}
				else if( type == "last7days" )
				{
					document.EditView.startdate.value = "'.getDisplayDate($last7days).'";
					document.EditView.enddate.value =  "'.getDisplayDate($today).'";
				}
				else if( type == "last30days" )
				{
					document.EditView.startdate.value = "'.getDisplayDate($last30days).'";
					document.EditView.enddate.value = "'.getDisplayDate($today).'";
				}
				else if( type == "last60days" )
				{
					document.EditView.startdate.value = "'.getDisplayDate($last60days).'";
					document.EditView.enddate.value = "'.getDisplayDate($today).'";
				}
				else if( type == "last90days" )
				{
					document.EditView.startdate.value = "'.getDisplayDate($last90days).'";
					document.EditView.enddate.value = "'.getDisplayDate($today).'";
				}
				else if( type == "last120days" )
				{
					document.EditView.startdate.value = "'.getDisplayDate($last120days).'";
					document.EditView.enddate.value = "'.getDisplayDate($today).'";
				}
				else if( type == "thisfy" )
				{
					document.EditView.startdate.value = "'.getDisplayDate($currentFY0).'";
					document.EditView.enddate.value = "'.getDisplayDate($currentFY1).'";
				}
				else if( type == "prevfy" )
				{
					document.EditView.startdate.value = "'.getDisplayDate($lastFY0).'";
					document.EditView.enddate.value = "'.getDisplayDate($lastFY1).'";
				}
				else if( type == "nextfy" )
				{
					document.EditView.startdate.value = "'.getDisplayDate($nextFY0).'";
					document.EditView.enddate.value = "'.getDisplayDate($nextFY1).'";
				}
				else if( type == "nextfq" )
				{
					document.EditView.startdate.value = "'.getDisplayDate($nFq).'";
					document.EditView.enddate.value = "'.getDisplayDate($nFq1).'";
				}
				else if( type == "prevfq" )
				{
					document.EditView.startdate.value = "'.getDisplayDate($pFq).'";
					document.EditView.enddate.value = "'.getDisplayDate($pFq1).'";
				}
				else if( type == "thisfq" )
				{
					document.EditView.startdate.value = "'.getDisplayDate($cFq).'";
					document.EditView.enddate.value = "'.getDisplayDate($cFq1).'";
				}
				else
				{
					document.EditView.startdate.value = "";
					document.EditView.enddate.value = "";
				}
			}
		</script>';
		//crmv@32334 e
		return $sjsStr;
	}

	/** to get the standard filter for the given customview Id
	  * @param $cvid :: Type Integer
	  * @returns  $stdfilterlist Array in the following format
	  * $stdfilterlist = Array( 'columnname' =>  $tablename:$columnname:$fieldname:$module_$fieldlabel,'stdfilter'=>$stdfilter,'startdate'=>$startdate,'enddate'=>$enddate)
	  */

	function getStdFilterByCvid($cvid)
	{
		global $adb,$table_prefix;

		$sSQL = "select ".$table_prefix."_cvstdfilter.* from ".$table_prefix."_cvstdfilter inner join ".$table_prefix."_customview on ".$table_prefix."_customview.cvid = ".$table_prefix."_cvstdfilter.cvid";
		$sSQL .= " where ".$table_prefix."_cvstdfilter.cvid=?";

		$result = $adb->pquery($sSQL, array($cvid));
		$stdfilterrow = $adb->fetch_array($result);
		//crmv@15893
		if (is_array($stdfilterrow)){
			$stdfilterlist["columnname"] = $stdfilterrow["columnname"];
			if (trim($stdfilterrow["startdate"]) != '')
				$stdfilterlist["startdate"] = substr($stdfilterlist["startdate"],0,10);
			if (trim($stdfilterrow["enddate"]) != '')
				$stdfilterlist["enddate"] = substr($stdfilterlist["enddate"],0,10);
			$stdfilterlist["stdfilter"] = $stdfilterrow["stdfilter"];
		}
		//crmv@15893 end

		if($stdfilterrow["stdfilter"] == "custom" || $stdfilterrow["stdfilter"] == "")
		{
			if($stdfilterrow["startdate"] != "0000-00-00" && $stdfilterrow["startdate"] != "")
			{
				$stdfilterlist["startdate"] = $stdfilterrow["startdate"];
			}
			if($stdfilterrow["enddate"] != "0000-00-00" && $stdfilterrow["enddate"] != "")
			{
				$stdfilterlist["enddate"] = $stdfilterrow["enddate"];
			}
		}else  //if it is not custom get the date according to the selected duration
		{
			$datefilter = $this->getDateforStdFilterBytype($stdfilterrow["stdfilter"]);
			$stdfilterlist["startdate"] = $datefilter[0];
			$stdfilterlist["enddate"] = $datefilter[1];
		}

		//crmv@10468
		$stdfilterlist["only_month_and_day"] = $stdfilterrow["only_month_and_day"];
		//crmv@10468e

		return $stdfilterlist;
	}

	/** to get the Advanced filter for the given customview Id
	  * @param $cvid :: Type Integer
	  * @returns  $stdfilterlist Array in the following format
	  * $stdfilterlist = Array( 0=>Array('columnname' =>  $tablename:$columnname:$fieldname:$module_$fieldlabel,'comparator'=>$comparator,'value'=>$value),
	  *			    1=>Array('columnname' =>  $tablename1:$columnname1:$fieldname1:$module_$fieldlabel1,'comparator'=>$comparator1,'value'=>$value1),
	  *		   			|
	  *			    4=>Array('columnname' =>  $tablename4:$columnname4:$fieldname4:$module_$fieldlabel4,'comparator'=>$comparatorn,'value'=>$valuen),
	  */
	function getAdvFilterByCvid($cvid)
	{
		global $adb, $table_prefix, $modules, $current_user, $showfullusername; // crmv@190595

		$sSQL = "select ".$table_prefix."_cvadvfilter.* from ".$table_prefix."_cvadvfilter inner join ".$table_prefix."_customview on ".$table_prefix."_cvadvfilter.cvid = ".$table_prefix."_customview.cvid";
		$sSQL .= " where ".$table_prefix."_cvadvfilter.cvid=?";
		$result = $adb->pquery($sSQL, array($cvid));

		$advfilterlist = array();

		while($advfilterrow = $adb->fetch_array($result))
		{
			// crmv@190595
			if ($this->customviewmodule == 'Processes' && is_numeric($advfilterrow["value"])) {
				$pendingCvId = $this->getViewIdByName('Pending', 'Processes', $current_user->id);
				if ($cvid == $pendingCvId && stripos($advfilterrow["columnname"],'assigned_user_id') !== false) {
					require('user_privileges/requireUserPrivileges.php');
					if (!isset($current_user_groups)) {
						require_once('include/utils/GetUserGroups.php');
						$userGroupFocus=new GetUserGroups();
						$userGroupFocus->getAllUserGroups($advfilterrow["value"]);
						$current_user_groups = $userGroupFocus->user_groups;
					}
					if (!empty($current_user_groups)) {
						$advfilterrow["value"] = getUserName($advfilterrow["value"], $showfullusername);
						foreach($current_user_groups as $current_user_group) {
							$advfilterrow["value"] .= ','.getGroupName($current_user_group)[0];
						}
					}
				}
			}
			// crmv@190595e
			
			//crmv@fix empty columnname
			if ($advfilterrow["columnname"]){
				$advft["columnname"] = $advfilterrow["columnname"];
				$advft["comparator"] = $advfilterrow["comparator"];
				$advft["value"] = $advfilterrow["value"];
				$advfilterlist[] = $advft;
			}
			//crmv@fix empty columnname end
		}

		return $advfilterlist;
	}

	//crmv@31775
    function getReportId($cvid) {
    	global $adb,$table_prefix;
    	$return = false;
		$sSQL = "SELECT reportid FROM {$table_prefix}_customview WHERE cvid = ?";
		$result = $adb->pquery($sSQL, array($cvid));
		if ($result) {
			$reportid = $adb->query_result($result,0,'reportid');
			if ($reportid != '' && $reportid > 0) {
				$return = $reportid;
			}
		}
		return $return;
    }

	// crmv@98500
    function getReportFilter($cvid) {
		$reportid = $this->getReportId($cvid);
		if ($reportid !== false) {
			global $table_prefix;
			$folderid = getSingleFieldValue($table_prefix.'_report', 'folderid', 'reportid', $reportid);
			$sdkrep = SDK::getReport($reportid, $folderid);
			if (!is_null($sdkrep)) {
				require_once($sdkrep['reportrun']);
				$oReportRun = new $sdkrep['runclass']($reportid);
			} else {
				require_once('modules/Reports/ReportRun.php');
				$oReportRun = ReportRun::getInstance($reportid);
			}
			$oReportRun->setCVInfo(array('cvid'=>$cvid,'module'=>$this->customviewmodule));
			$oReportRun->GenerateReport("CV_RPRT");
		}
		return $reportid;
	}
	// crmv@98500e
	
	// crmv@63349 - get rid of the temporary table
	function createReportFilterTable($reportid,$userid,$sSQL,$prefix=0) {
		if (PerformancePrefs::getBoolean('USE_TEMP_TABLES', true)) {
			return $this->createReportFilterTable_tmp($reportid, $userid, $sSQL, $prefix);
		} else {
			return $this->createReportFilterTable_notmp($reportid, $userid, $sSQL, $prefix);
		}
	}

	//crmv@91667
	function createReportFilterTable_notmp($reportid,$userid,$sSQL,$prefix=0) {
		global $adb, $table_prefix;

		$userid = intval($userid);
		$reportid = intval($reportid);
		$prefix = intval($prefix);

		$tableName = $this->getReportFilterTableName($reportid,$userid,$prefix);
		$this->cleanAllReportFilterTable($reportid,$userid,$prefix);
		$res = $adb->query($sSQL);
		if ($res){
			$val_done = Array();
			$inserts = Array();
			while($row=$adb->fetchByAssoc($res,-1,false)){
				foreach ($row as $field=>$val){
					if (empty($val)) continue;
					$field = explode("_",$field);
					$prefix = intval($field[1]);
					if (!isset($val_done[$val])){
						$inserts[] = Array($userid,$reportid,$prefix,intval($val));
						$val_done[$val] = $val;
					}
				}
			}
			if (!empty($inserts)){
				$adb->bulkInsert($tableName, Array('userid','reportid','prefix','id'), $inserts);
			}
			unset($val_done);
		}		
	}

	function cleanAllReportFilterTable($reportid,$userid,$prefix=0) {
		global $adb;
		$tableName = $this->getReportFilterTableName($reportid,$userid,$prefix);
		
		$sql = "DELETE FROM $tableName WHERE userid = ? AND reportid = ?";
		$adb->pquery($sql, array(intval($userid), intval($reportid)));
	}

	function cleanReportFilterTable($reportid,$userid,$prefix=0) {
		global $adb;
		$tableName = $this->getReportFilterTableName($reportid,$userid,$prefix);
		
		$sql = "DELETE FROM $tableName WHERE userid = ? AND reportid = ? AND prefix = ?";
		$adb->pquery($sql, array(intval($userid), intval($reportid), intval($prefix)));
	}
	// crmv@63349e

	// crmv@122906
	function getReportFilterJoin($idColumn, $reportid,$userid,$prefix=0) {
		global $table_prefix;
		
		$table = $this->getReportFilterTableName($reportid,$userid, $prefix);
		
		if (PerformancePrefs::getBoolean('USE_TEMP_TABLES', true)) {
			$join = " INNER JOIN $table ON $table.id = $idColumn";
		} else {
			$reportid = intval($reportid);
			$userid = intval($userid);
			$prefix = intval($prefix); // crmv@150024
			$join = " INNER JOIN $table ON $table.id = $idColumn AND $table.reportid = $reportid AND $table.userid = $userid AND $table.prefix = $prefix"; // crmv@150024
		}
		return $join;
	}
	// crmv@122906e

	function createReportFilterTable_tmp($reportid,$userid,$sSQL,$prefix='') { // crmv@63349
		$db = PearDatabase::getInstance();
		//crmv@181281
		$tableName = $this->getReportFilterTableName($reportid,$userid,$prefix);
		if ($db->table_exist($tableName)) {
			$db->query("DELETE FROM $tableName");
		}
		//crmv@181281e
		$res = $db->query($sSQL);
		if ($res){
			$val_done = Array();
			$inserts = Array();
			$first = true;
			$cached_tablenames = Array();
			while($row=$db->fetchByAssoc($res,-1,false)){
				foreach ($row as $field=>$val){
					$field = explode("_",$field);
					$prefix = $field[1];	// this is the tabid of the module
					if ($first){
						$this->dropReportFilterTable($reportid,$userid,$prefix);
					}
					if (!isset($cached_tablenames[$reportid."|".$userid."|".$prefix])){
						$cached_tablenames[$reportid."|".$userid."|".$prefix] = $this->getReportFilterTableName($reportid,$userid,$prefix);
					}
					$tableName = $cached_tablenames[$reportid."|".$userid."|".$prefix];
					if (!isset($val_done[$val]) && $val !== null){ // crmv@129135
						$inserts[$tableName][] = Array($val);
						$val_done[$val] = $val;
					}
				}
				$first = false;
			}
			if (!empty($inserts)){
				foreach ($inserts as $tableName=>$arr_vals){
					Vtecrm_Utils::CreateTable($tableName,"id I(19) NOTNULL PRIMARY");
					$db->bulkInsert($tableName,null, $arr_vals);
				}
			}
			unset($val_done);
		}
	}
	//crmv@91667e

	function dropReportFilterTable($reportid,$userid,$prefix='') {
		global $adb;
		$tableName = $this->getReportFilterTableName($reportid,$userid,$prefix);
		if ($adb->table_exist($tableName)){
			$sqlarray = $adb->datadict->DropTableSQL($tableName);
			$adb->datadict->ExecuteSQLArray($sqlarray);
		}
	}

	// crmv@63349
	function getReportFilterTableName($reportid,$userid,$prefix=0) {
	
		if (PerformancePrefs::getBoolean('USE_TEMP_TABLES', true)) {
			return self::getReportFilterTableName_tmp($reportid, $userid, $prefix);
		} else {
			return self::getReportFilterTableName_notmp($reportid, $userid, $prefix);
		}
		
	}

	function getReportFilterTableName_notmp($reportid,$userid,$prefix=0) {
		global $table_prefix;
		return $table_prefix.'_customview_rpt';
	}
	// crmv@63349e

	function getReportFilterTableName_tmp($reportid,$userid,$prefix='') { // crmv@63349
		if (!empty($prefix)) {
			return "tmp_rpt_{$prefix}_{$reportid}_u_{$userid}";
		} else {
			return "tmp_rpt_{$reportid}_u_{$userid}";
		}
	}
	
	// crmv@98500
	function getReportModules() {
		if (isset($_REQUEST['reportid'])) {
    		$reportid = $_REQUEST['reportid'];
    	} elseif (!empty($this->reportid)) {
    		$reportid = $this->reportid;
    	}
    	if ($reportid > 0) {
			require_once('modules/Reports/Reports.php');
			$reports = Reports::getInstance();
			$modules = $reports->getAllModules($reportid);
			if (($k = array_search('ProductsBlock', $modules)) !== false) {
				unset($modules[$k]);
				$modules = array_values($modules);
			}
			return $modules;
		}
		return false;
	}
	//crmv@31775e crmv@98500e

	/**
	 * Cache information to perform re-lookups
	 *
	 * @var String
	 */
	protected $_fieldby_tblcol_cache = array();

	/**
	 * Function to check if field is present based on
	 *
	 * @param String $columnname
	 * @param String $tablename
	 */
	function isFieldPresent_ByColumnTable($columnname, $tablename) {
		global $adb,$table_prefix;

		if(!isset($this->_fieldby_tblcol_cache[$tablename])) {
			$query   = 'SELECT columnname FROM '.$table_prefix.'_field WHERE tablename = ? and presence in (0,2)';

			$result  = $adb->pquery($query,array($tablename));
			$numrows = $adb->num_rows($result);

			if($numrows) {
				$this->_fieldby_tblcol_cache[$tablename] = array();
				for($index = 0; $index < $numrows; ++$index) {
					$this->_fieldby_tblcol_cache[$tablename][] = $adb->query_result($result, $index, 'columnname');
				}
			}
		}
		// If still the field was not found (might be disabled or deleted?)
		if(!isset($this->_fieldby_tblcol_cache[$tablename])) {
			return false;
		}
		return in_array($columnname, $this->_fieldby_tblcol_cache[$tablename]);
	}


	/** to get the customview Columnlist Query for the given customview Id
	  * @param $cvid :: Type Integer
	  * @returns  $getCvColumnList as a string
	  * This function will return the columns for the given customfield in comma seperated values in the format
	  *                     $tablename.$columnname,$tablename1.$columnname1, ------ $tablenamen.$columnnamen
	  *
	  */
	function getCvColumnListSQL($cvid,$popuptype)
	{
		global $adb,$table_prefix;
		$columnslist = $this->getColumnsListByCvid($cvid);
		if(isset($columnslist))
		{
			foreach($columnslist as $columnname=>$value)
			{
				$tablefield = array(); // crmv@167234
				if($value != "")
				{
					$list = explode(":",$value);

					//Added For getting status for Activities -Jaguar
					$sqllist_column = $list[0].".".$list[1];
					if($this->customviewmodule == "Calendar")
					{
						if($list[1] == "status" || $list[1] == "eventstatus")
						{
							//crmv@10764
							$sqllist_column = "CASE WHEN (".$table_prefix."_activity.activitytype NOT LIKE 'Task')
								THEN ".$table_prefix."_activity.eventstatus
								ELSE ".$table_prefix."_activity.status END AS activitystatus";
							//crmv@10764 e
						}
					}
					//Added for assigned to sorting
					if($list[1] == "smownerid")
					{
						$sqllist_column = "case when (".$table_prefix."_users.user_name is not null) then ".$table_prefix."_users.user_name else ".$table_prefix."_groups.groupname end as user_name";
					}
//					if($list[0] == "vte_contactdetails" && $list[1] == "lastname")
//                    	$sqllist_column = "vte_contactdetails.lastname,vte_contactdetails.firstname";
					$sqllist[] = $sqllist_column;
					//Ends

					$tablefield[$list[0]] = $list[1];

					//Changed as the replace of module name may replace the string if the fieldname has module name in it -- Jeri
					$fieldinfo = explode('_',$list[3],2);
					$fieldlabel = $fieldinfo[1];
					$fieldlabel = str_replace("_"," ",$fieldlabel);

					if($this->isFieldPresent_ByColumnTable($list[1], $list[0])){

						$this->list_fields[$fieldlabel] = $tablefield;
						$this->list_fields_name[$fieldlabel] = $list[2];
					}
				}
			}
			//crmv@fix key column
			$obj = CRMEntity::getInstance($this->customviewmodule);
			$colname = $obj->table_name.".".$obj->tab_name_index[$obj->table_name];
			if ($colname && !in_array($colname,$sqllist))
				$sqllist[] = $colname;
			//crmv@fix key column end
			//crmv@add email,fax & sms fields to popup
			if (in_array($popuptype,array('set_return_emails','set_return_emails_cc','set_return_emails_bcc'))) {	//crmv@25356
				$uitype_search = 13;
			}
			elseif ($popuptype == 'set_return_fax'){
				$uitype_search = 1013;
			}
			elseif ($popuptype == 'set_return_sms'){
				$uitype_search = 1014;
			}
			if ($uitype_search){
				$sql = "select tablename,columnname,fieldname,fieldlabel from ".$table_prefix."_field where tabid = ? and uitype = ?";
				$params = array(getTabid($this->customviewmodule),$uitype_search);
				$res = $adb->pquery($sql,$params);
				if ($res && $adb->num_rows($res) > 0){
					while ($row = $adb->fetchByAssoc($res)){
						$field_add = $row['tablename'].".".$row['columnname'];
						if (!in_array($field_add,$sqllist)){
							$this->list_fields[$row['fieldlabel']] = Array($row['tablename']=>$row['columnname']);
							$this->list_fields_name[$row['fieldlabel']] = $row['fieldname'];
							$sqllist[] = $field_add;
						}
					}
				}
			}
			//crmv@add email,fax & sms fields to popup e
			//crmv@9433
			if (vtlib_isModuleActive('Conditionals')){
				//crmv@36505
			    $conditionals_obj = CRMEntity::getInstance('Conditionals');
			    $conditional_fields = $conditionals_obj->getConditionalFields($this->customviewmodule);
			    //crmv@36505 e
				if (!empty($conditional_fields)){
					foreach ($conditional_fields as $row){
						$field_add = $row['tablename'].".".$row['columnname'];
						if (!in_array($field_add,$sqllist)){
//							$this->list_fields[$row['fieldlabel']] = Array($row['tablename']=>$row['columnname']);
//							$this->list_fields_name[$row['fieldlabel']] = $row['fieldname'];
							$sqllist[] = $field_add;
						}
					}
				}
			}
			//crmv@9433 end
			//crmv@15951
			if ($popuptype == 'inventory_prod' || $popuptype == 'inventory_prod_po' || $popuptype == 'inventory_service'){
				$field_add = $obj->table_name.'.description'; // crmv@150773
				if (!in_array($field_add,$sqllist))
					$sqllist[] = $field_add;
			}
			//crmv@15951 end
			//crmv@16238
			if (in_array($table_prefix.'_account.accountname',$sqllist) && !in_array($table_prefix.'_account.accountid',$sqllist)){
				$sqllist[] = $table_prefix.'_account.accountid';
			}
			//crmv@16238 end
			//crmv@17001 : Private Permissions
			if ($this->customviewmodule == 'Calendar' && !in_array($table_prefix.'_activity.visibility',$sqllist))
				$sqllist[] = $table_prefix.'_activity.visibility';
			//crmv@17001e
			//crmv@55228
			if ($this->customviewmodule == 'Products' && !in_array($table_prefix.'_products.unit_cost',$sqllist))
				$sqllist[] = $table_prefix.'_products.unit_cost';
			//crmv@55228e
			//crmv@66603
			if ($this->customviewmodule == 'Products' && !in_array($table_prefix.'_products.unit_price',$sqllist))
				$sqllist[] = $table_prefix.'_products.unit_price';
			//crmv@66603e
			$returnsql = implode(",",$sqllist);
		}
		return $returnsql;
	}

	/** to get the customview stdFilter Query for the given customview Id
	  * @param $cvid :: Type Integer
	  * @returns  $stdfiltersql as a string
	  * This function will return the standard filter criteria for the given customfield
	  *
	  */
	function getCVStdFilterSQL($cvid)
	{
		global $adb;
		$stdfilterlist = $this->getStdFilterByCvid($cvid);
		if(isset($stdfilterlist))
		{
			foreach($stdfilterlist as $columnname=>$value)
			{
				if($columnname == "columnname")
				{
					$filtercolumn = $value;
				}elseif($columnname == "stdfilter")
				{
					$filtertype = $value;
				}elseif($columnname == "startdate")
				{
					$startdate = $value;
				}elseif($columnname == "enddate")
				{
					$enddate = $value;
				}
			}
			if($filtertype != "custom")
			{
				$datearray = $this->getDateforStdFilterBytype($filtertype);
				$startdate = $datearray[0];
				$enddate = $datearray[1];
			}
			//crmv@10468
			if($startdate != "" && $enddate != "" && $stdfilterlist["only_month_and_day"] == 1)
			{
				$columns = explode(":",$filtercolumn);
				$stdfiltersql = "(
					".$adb->database->SQLDate("m",$columns[0].".".$columns[1])." >= ".date('m',strtotime($startdate))."
					and ".$adb->database->SQLDate("d",$columns[0].".".$columns[1])." >= ".date('d',strtotime($startdate))."
					and ".$adb->database->SQLDate("m",$columns[0].".".$columns[1])." <= ".date('m',strtotime($enddate))."
					and ".$adb->database->SQLDate("d",$columns[0].".".$columns[1])." <= ".date('d',strtotime($enddate))."
					)";
			}
			elseif($startdate != "" && $enddate != "")
			//crmv@10468e
			{
				$columns = explode(":",$filtercolumn);
				$stdfiltersql = $columns[0].".".$columns[1]." between '".$startdate." 00:00:00' and '".$enddate." 23:59:00'";
			}
		}
		return $stdfiltersql;
	}
	/** to get the customview AdvancedFilter Query for the given customview Id
	  * @param $cvid :: Type Integer
	  * @returns  $advfiltersql as a string
	  * This function will return the advanced filter criteria for the given customfield
	  *
	  */
	function getCVAdvFilterSQL($cvid)
	{
		global $current_user,$table_prefix;
		$advfilter = $this->getAdvFilterByCvid($cvid);
		if(isset($advfilter))
		{
			foreach($advfilter as $key=>$advfltrow)
			{
				if(isset($advfltrow))
				{
					$columns = explode(":",$advfltrow["columnname"]);
					$datatype = (isset($columns[4])) ? $columns[4] : "";
					if(trim($advfltrow["columnname"]) != "" && trim($advfltrow["comparator"]) != "")
					{

						$valuearray = explode(",",trim($advfltrow["value"]));
						if(isset($valuearray) && count($valuearray) > 1)
						{
							$advorsql = array(); // crmv@177592
							for($n=0;$n<count($valuearray);$n++)
							{
								$advorsql[] = $this->getRealValues($columns[0],$columns[1],$advfltrow["comparator"],trim($valuearray[$n]),$datatype);
							}
							//If negative logic filter ('not equal to', 'does not contain') is used, 'and' condition should be applied instead of 'or'
							if($advfltrow["comparator"] == 'n' || $advfltrow["comparator"] == 'k')
								$advorsqls = implode(" and ",$advorsql);
							else
								$advorsqls = implode(" or ",$advorsql);
							$advfiltersql[] = " (".$advorsqls.") ";
						}else
						{
							//Added for getting vte_activity Status -Jaguar
							if($this->customviewmodule == "Calendar" && ($columns[1] == "status" || $columns[1] == "eventstatus"))
							{
								if(getFieldVisibilityPermission("Calendar", $current_user->id,'taskstatus') == '0')
								{
									$advfiltersql[] = "case when (".$table_prefix."_activity.status not like '') then ".$table_prefix."_activity.status else ".$table_prefix."_activity.eventstatus end".$this->getAdvComparator($advfltrow["comparator"],trim($advfltrow["value"]),$datatype);
								}
								else
									$advfiltersql[] = $table_prefix."_activity.eventstatus".$this->getAdvComparator($advfltrow["comparator"],trim($advfltrow["value"]),$datatype);
							}
							elseif($this->customviewmodule == "Documents" && $columns[1]=='folderid'){
								$advfiltersql[] = $table_prefix."_crmentityfolder.foldername".$this->getAdvComparator($advfltrow["comparator"],trim($advfltrow["value"]),$datatype); // crmv@30967
							}
							//crmv@102635
							elseif($this->customviewmodule == "Products" && $columns[1]=='productlineid'){
								$advfiltersql[] = $table_prefix."_productlines.productlinename".$this->getAdvComparator($advfltrow["comparator"],trim($advfltrow["value"]),$datatype);
							}
							//crmv@102635e
							elseif($this->customviewmodule == "Assets"){
								if($columns[1]=='account' ){
									$advfiltersql[] = $table_prefix."_account.accountname".$this->getAdvComparator($advfltrow["comparator"],trim($advfltrow["value"]),$datatype);
								}
								if($columns[1]=='product'){
									$advfiltersql[] = $table_prefix."_products.productname".$this->getAdvComparator($advfltrow["comparator"],trim($advfltrow["value"]),$datatype);
								}
								if($columns[1]=='invoiceid'){
									$advfiltersql[] = $table_prefix."_invoice.subject".$this->getAdvComparator($advfltrow["comparator"],trim($advfltrow["value"]),$datatype);
								}
							}
							else
							{
								$advfiltersql[] = $this->getRealValues($columns[0],$columns[1],$advfltrow["comparator"],trim($advfltrow["value"]),$datatype);
							}
						}
					}
				}
			}
		}
		if(isset($advfiltersql))
		{
			$advfsql = implode(" and ",$advfiltersql);
		}
		return $advfsql;
	}

	/** to get the realvalues for the given value
	  * @param $tablename :: type string
	  * @param $fieldname :: type string
	  * @param $comparator :: type string
	  * @param $value :: type string
	  * @returns  $value as a string in the following format
	  *	  $tablename.$fieldname comparator
	  */
	function getRealValues($tablename,$fieldname,$comparator,$value,$datatype,$userid=null) //crmv@63872
	{
		//we have to add the fieldname/tablename.fieldname and the corresponding value (which we want) we can add here. So that when these LHS field comes then RHS value will be replaced for LHS in the where condition of the query
		global $adb, $mod_strings, $currentModule, $current_user,$table_prefix;
		//Added for proper check of contact name in advance filter
		if($tablename == $table_prefix."_contactdetails" && $fieldname == "lastname")
			$fieldname = "contactid";

		$contactid = $table_prefix."_contactdetails.lastname";
		if ($currentModule != "Contacts" && $currentModule != "Leads" && getFieldVisibilityPermission("Contacts", $userid, 'firstname') == '0' && $currentModule != 'Campaigns') {	//crmv@63872
			$contactid = $adb->sql_concat(Array($table_prefix.'_contactdetails.lastname',"' '",$table_prefix.'_contactdetails.firstname'));
		}
		$change_table_field = Array(

			"product_id"=>$table_prefix."_products.productname",
			"contactid"=>$contactid,
			"contact_id"=>$contactid,
			"accountid"=>"",//in cvadvfilter accountname is stored for Contact, Potential, Quotes, SO, Invoice
			"account_id"=>"",//Same like accountid. No need to change
			"vendorid"=>$table_prefix."_vendor.vendorname",
			"vendor_id"=>$table_prefix."_vendor.vendorname",
			"potentialid"=>$table_prefix."_potential.potentialname",

			$table_prefix."_account.parentid"=>$table_prefix."_account2.accountname",
			"quoteid"=>$table_prefix."_quotes.subject",
			"salesorderid"=>$table_prefix."_salesorder.subject",
			"campaignid"=>$table_prefix."_campaign.campaignname",
			$table_prefix."_contactdetails.reportsto"=>$adb->sql_concat(Array($table_prefix.'_contactdetails2.lastname',"' '",$table_prefix.'_contactdetails2.firstname')),
			$table_prefix."_pricebook.currency_id"=>$table_prefix."_currency_info.currency_name",
			);

		//sk@2
	    if($tablename== $table_prefix.'_projects' && $fieldname=='reports_to_id'){
	      $tablename = 'users_projectleader';
	      $fieldname = 'user_name';
	    }
	    //sk@2e

	    if($fieldname == "smownerid")
	    {
	    	//crmv@38505
	    	$username = getUserName($value);
	    	if ($username == ''){
	    		$username = $value;
	    	}
	    	$temp_value = "( ".$table_prefix."_users.user_name".$this->getAdvComparator($comparator,$username,$datatype);
	    	$temp_value.= " OR  ".$table_prefix."_groups.groupname".$this->getAdvComparator($comparator,$username,$datatype);
	    	$value=$temp_value.")";
	    	//crmv@38505
	    }
	    elseif( $fieldname == "inventorymanager")
	    {
	    	$value = $tablename.".".$fieldname.$this->getAdvComparator($comparator,getUserId_Ol($value),$datatype);
	    }
		elseif($change_table_field[$fieldname] != '')//Added to handle special cases
		{
			$value = $change_table_field[$fieldname].$this->getAdvComparator($comparator,$value,$datatype);
		}
		elseif($change_table_field[$tablename.".".$fieldname] != '')//Added to handle special cases
		{
			$tmp_value = '';
			if((($comparator == 'e' || $comparator == 's' || $comparator == 'c') && trim($value) == '') || (($comparator == 'n' || $comparator == 'k') && trim($value) != ''))
			{
				$tmp_value = $change_table_field[$tablename.".".$fieldname].' IS NULL or ';
			}
			$value = $tmp_value.$change_table_field[$tablename.".".$fieldname].$this->getAdvComparator($comparator,$value,$datatype);
		}
		elseif($fieldname == "handler")
		{
			$value = $table_prefix."_users.user_name".$this->getAdvComparator($comparator,$value,$datatype);
		}
		elseif(($fieldname == "crmid" && $tablename != $table_prefix.'_crmentity') || $fieldname == "parent_id" || $fieldname == 'parentid')
		{
			//For crmentity.crmid the control should not come here. This is only to get the related to modules
			$value = $this->getSalesRelatedName($comparator,$value,$datatype,$tablename,$fieldname);
		}
		else
		{

			// crmv@116519
			$fieldModule = $this->customviewmodule;
			if (empty($fieldModule)) {
				// find the module from the field (there might be multiple matches, take the first)
				$res = $adb->pquery("SELECT t.name FROM {$table_prefix}_tab t INNER JOIN {$table_prefix}_field f ON f.tabid = t.tabid WHERE f.tablename = ? AND f.fieldname = ?", array($tablename, $fieldname));
				if ($res && $adb->num_rows($res) > 0) {
					$fieldModule = $adb->query_result_no_html($res, 0, 'name');
				}
			}
			// crmv@116519e
			
			// crmv@184929
			// get field info
			$res = $adb->pquery("SELECT fieldid, uitype FROM {$table_prefix}_field WHERE tabid = ? AND fieldname = ?", array(getTabid($fieldModule), $fieldname));
			$fieldid = intval($adb->query_result_no_html($res, 0, 'fieldid'));
			$field_uitype = intval($adb->query_result_no_html($res, 0, 'uitype'));
			// crmv@184929e

			//crmv@128159
			if (SDK::isUitype($field_uitype)) {
				$sdk_file = SDK::getUitypeFile('php','customviewsearch',$field_uitype);
				if ($sdk_file != '') {
					include($sdk_file);
				}
			//crmv@128159e
			} elseif($field_uitype == 56) {
				if(strtolower($value) == 'yes')         $value = 1;
				elseif(strtolower($value) ==  'no')     $value = 0;
			// crmv@116519 - other owner fields
			} elseif ($field_uitype == 52) {
				// crmv@184929 - moved up
				if ($fieldid > 0) {
					$username = getUserName($value) ?: $value;
					$alias = $table_prefix."_users_fld_".$fieldid;
					$value = "{$alias}.user_name".$this->getAdvComparator($comparator,$username,$datatype);
					return $value;
				}
			// crmv@116519e
			} elseif(is_uitype($field_uitype, '_picklist_')) { /* Fix for tickets 4465 and 4629 */
				// Get all the keys for the for the Picklist value
				if (is_array($mod_strings)) {
					$mod_keys = array_keys($mod_strings, $value);

					// Iterate on the keys, to get the first key which doesn't start with LBL_      (assuming it is not used in PickList)
					foreach($mod_keys as $mod_idx=>$mod_key) {
						$stridx = strpos($mod_key, 'LBL_');
						// Use strict type comparision, refer strpos for more details
						if ($stridx !== 0) {
							$value = $mod_key;
							break;
						}
					}
				}
			//crmv@45758	crmv@81559
			} elseif ($field_uitype == 1015) {
				$src_val = Picklistmulti::get_search_values($fieldname,Array($value),$comparator);
				$search_values = $src_val[0];
				if($comparator == 'k') $comparator = 'n';
				if($comparator == 'c') $comparator = 'e';
				$return_value = ' ( ';
				for ($ind = 0; $ind < count($search_values); $ind++) {
					if ($ind != 0) {
						if($comparator == 'e'){
							$return_value .= " OR ";
						}
						else{
							$return_value .= " AND ";
						}
					}
					$return_value .= $tablename.".".$fieldname.$this->getAdvComparator($comparator,$search_values[$ind],$datatype);
				}
				$return_value .= ' ) ';
				return $return_value;

			// crmv@184929
			} elseif ($field_uitype == 10) {
				$value = "entityname_fld_{$fieldid}.displayname".$this->getAdvComparator($comparator,$value,$datatype);
				return $value;
			// crmv@184929e

			}
			//crmv@45758e	crmv@81559e
			//added to fix the ticket
			if($fieldModule == "Calendar" && ($fieldname=="status" || $fieldname=="taskstatus" || $fieldname=="eventstatus")) // crmv@116519
			{
				if(getFieldVisibilityPermission("Calendar", $userid,'taskstatus') == '0')	//crmv@63872
				{
					$value = " (case when (".$table_prefix."_activity.status not like '') then ".$table_prefix."_activity.status else ".$table_prefix."_activity.eventstatus end)".$this->getAdvComparator($comparator,$value,$datatype);
				}
				else
					$value = $table_prefix."_activity.eventstatus ".$this->getAdvComparator($comparator,$value,$datatype);
			} elseif ($comparator == 'e' && (trim($value) == "NULL" || trim($value) == '')) {
				$value = '('.$tablename.".".$fieldname.' IS NULL OR '.$tablename.".".$fieldname.' = \'\')';
			} else {
				$value = $tablename.".".$fieldname.$this->getAdvComparator($comparator,$value,$datatype);
			}
			//end
		}
		return $value;
	}

	/** to get the related name for the given module
	  * @param $comparator :: type string,
	  * @param $value :: type string,
	  * @param $datatype :: type string,
	  * @returns  $value :: string
	  */

	function getSalesRelatedName($comparator,$value,$datatype,$tablename,$fieldname)
	{
	        global $log,$table_prefix;
                $log->info("in getSalesRelatedName ".$comparator."==".$value."==".$datatype."==".$tablename."==".$fieldname);
		global $adb;

		$adv_chk_value = $value;
		$value = '(';
		//$sql = "select distinct(setype) from ".$table_prefix."_crmentity c INNER JOIN ".$adb->sql_escape_string($tablename)." t ON t.".$adb->sql_escape_string($fieldname)." = c.crmid";
		$sql = "SELECT relmodule FROM {$table_prefix}_fieldmodulerel
				INNER JOIN {$table_prefix}_field ON {$table_prefix}_field.fieldid = {$table_prefix}_fieldmodulerel.fieldid
				WHERE {$table_prefix}_field.tablename = ? AND {$table_prefix}_field.fieldname = ?";
		$res=$adb->pquery($sql, array($tablename,$fieldname));
		for($s=0;$s<$adb->num_rows($res);$s++)
		{
			$modulename=$adb->query_result($res,$s,"relmodule");
			if($modulename == 'Vendors')
			{
				continue;
			}
			if($s != 0)
                 $value .= ' or ';
			if($modulename == 'Accounts')
			{
				//By Pavani : Related to problem in calender, Ticket: 4284 and 4675
				if(($comparator == 'e' || $comparator == 's' || $comparator == 'c') && trim($adv_chk_value) == '')
				{
					if($tablename == $table_prefix.'_seactivityrel' && $fieldname == 'crmid')
					{
						$value .= $table_prefix.'_account2.accountname IS NULL or ';
					}
					else{
						$value .= $table_prefix.'_account.accountname IS NULL or ';
					}
				}
				if($tablename == $table_prefix.'_seactivityrel' && $fieldname == 'crmid')
					{
						$value .= $table_prefix.'_account2.accountname';
					}
					else{
						$value .= $table_prefix.'_account.accountname';
					}
			}
			if($modulename == 'Leads')
			{
				if(($comparator == 'e' || $comparator == 's' || $comparator == 'c') && trim($adv_chk_value) == '')
				{
					$value .= " ".$adb->sql_concat(Array($table_prefix.'_leaddetails.lastname',"' '",$table_prefix.'_leaddetails.firstname'))." IS NULL or ";
				}
				$value .= " ".$adb->sql_concat(Array($table_prefix.'_leaddetails.lastname',"' '",$table_prefix.'_leaddetails.firstname'));
			}
			if($modulename == 'Potentials')
			{
				if(($comparator == 'e' || $comparator == 's' || $comparator == 'c') && trim($adv_chk_value) == '')
				{

				$value .= $table_prefix.'_potential.potentialname IS NULL or ';
				}
				$value .= $table_prefix.'_potential.potentialname';
			}
			if($modulename == 'Products')
			{
				if(($comparator == 'e' || $comparator == 's' || $comparator == 'c') && trim($adv_chk_value) == '')
				{
					$value .= $table_prefix.'_products.productname IS NULL or ';
				}
				$value .= $table_prefix.'_products.productname';
			}
			if($modulename == 'Invoice')
			{
				if(($comparator == 'e' || $comparator == 's' || $comparator == 'c') && trim($adv_chk_value) == '')
				{
					$value .= $table_prefix.'_invoice.subject IS NULL or ';
				}
				$value .= $table_prefix.'_invoice.subject';
			}
			if($modulename == 'PurchaseOrder')
			{
				if(($comparator == 'e' || $comparator == 's' || $comparator == 'c') && trim($adv_chk_value) == '')
				{
					$value .= $table_prefix.'_purchaseorder.subject IS NULL or ';
				}
				$value .= $table_prefix.'_purchaseorder.subject';
			}
			if($modulename == 'SalesOrder')
			{
				if(($comparator == 'e' || $comparator == 's' || $comparator == 'c') && trim($adv_chk_value) == '')
				{
					$value .= $table_prefix.'_salesorder.subject IS NULL or ';
				}
				$value .= $table_prefix.'_salesorder.subject';
			}
			if($modulename == 'Quotes')
			{

				if(($comparator == 'e' || $comparator == 's' || $comparator == 'c') && trim($adv_chk_value) == '')
				{
					$value .= $table_prefix.'_quotes.subject IS NULL or ';
				}
				$value .= $table_prefix.'_quotes.subject';
			}
			if($modulename == 'Contacts')
			{
				if(($comparator == 'e' || $comparator == 's' || $comparator == 'c') && trim($adv_chk_value) == '')
				{
					$value .= " ".$adb->sql_concat(Array($table_prefix.'_contactdetails.lastname',"' '",$table_prefix.'_contactdetails.firstname'))." IS NULL or ";

				}
				$value .= " ".$adb->sql_concat(Array($table_prefix.'_contactdetails.lastname',"' '",$table_prefix.'_contactdetails.firstname'));
			}
			if($modulename == 'HelpDesk')
			{
				if(($comparator == 'e' || $comparator == 's' || $comparator == 'c') && trim($adv_chk_value) == '')
				{
					$value .= $table_prefix.'_troubletickets.title IS NULL or ';
				}
				$value .= $table_prefix.'_troubletickets.title';

			}
			if($modulename == 'Campaigns')
			{
				if(($comparator == 'e' || $comparator == 's' || $comparator == 'c') && trim($adv_chk_value) == '')
				{
					$value .= $table_prefix.'_campaign.campaignname IS NULL or ';
				}
				$value .= $table_prefix.'_campaign.campaignname';
			}

			$value .= $this->getAdvComparator($comparator,$adv_chk_value,$datatype);
		}
		$value .= ")";
                $log->info("in getSalesRelatedName ".$comparator."==".$value."==".$datatype."==".$tablename."==".$fieldname);
		return $value;
	}

	/** to get the comparator value for the given comparator and value
	  * @param $comparator :: type string
	  * @param $value :: type string
	  * @returns  $rtvalue in the format $comparator $value
	  */

	function getAdvComparator($comparator,$value,$datatype = '')
	{

		global $adb, $default_charset;
		$value=html_entity_decode(trim($value),ENT_QUOTES,$default_charset);
		$value = $adb->sql_escape_string($value);
		if($comparator == "e")
		{
			if(trim($value) == "NULL")
			{
				$rtvalue = " is NULL";
			}elseif(trim($value) != "")
			{
				$rtvalue = " = ".$adb->quote($value);
			}elseif(trim($value) == "" && ($datatype == "V" || $datatype == "E"))
			{
				$rtvalue = " = ".$adb->quote($value);
			}else
			{
				$rtvalue = " is NULL";
			}
		}
		if($comparator == "n")
		{
			if(trim($value) == "NULL")
			{
				$rtvalue = " is NOT NULL";
			}elseif(trim($value) != "")
			{
				$rtvalue = " <> ".$adb->quote($value);
			}elseif(trim($value) == "" && $datatype == "V")
			{
				$rtvalue = " <> ".$adb->quote($value);
			}elseif(trim($value) == "" && $datatype == "E")
			{
				$rtvalue = " <> ".$adb->quote($value);
			}else
			{
				$rtvalue = " is NOT NULL";
			}
		}
		if($comparator == "s")
		{
			if(trim($value) == "" && ($datatype == "V" || $datatype == "E"))
			{
				$rtvalue = " like '". formatForSqlLike($value, 3) ."'";
			}else
			{
				$rtvalue = " like '". formatForSqlLike($value, 2) ."'";
			}
		}
		if($comparator == "ew")
		{
			if(trim($value) == "" && ($datatype == "V" || $datatype == "E"))
			{
				$rtvalue = " like '". formatForSqlLike($value, 3) ."'";
			}else
			{
				$rtvalue = " like '". formatForSqlLike($value, 1) ."'";
			}
		}
		if($comparator == "c")
		{
			if(trim($value) == "" && ($datatype == "V" || $datatype == "E"))
			{
				$rtvalue = " like '". formatForSqlLike($value, 3) ."'";
			}else
			{
				$rtvalue = " like '". formatForSqlLike($value) ."'";
			}
		}
		if($comparator == "k")
		{
			if(trim($value) == "" && ($datatype == "V" || $datatype == "E"))
			{
				$rtvalue = " not like ''";
			}else
			{
				$rtvalue = " not like '". formatForSqlLike($value) ."'";
			}
		}
		if($comparator == "l")
		{
			$rtvalue = " < ".$adb->quote($value);
		}
		if($comparator == "g")
		{
			$rtvalue = " > ".$adb->quote($value);
		}
		if($comparator == "m")
		{
			$rtvalue = " <= ".$adb->quote($value);
		}
		if($comparator == "h")
		{
			$rtvalue = " >= ".$adb->quote($value);
		}

		return $rtvalue;
	}

	/** to get the date value for the given type
	  * @param $type :: type string
	  * @returns  $datevalue array in the following format
	  *             $datevalue = Array(0=>$startdate,1=>$enddate)
	  */

	function getDateforStdFilterBytype($type) {
		global $current_user; // crmv@150808
		
		static $dayNames = array('Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday');
	
		// get first and last day of week
		$weekstart = $current_user->weekstart;
		if ($weekstart === null || $weekstart === '') $weekstart = 1;
		$weekstart = intval($weekstart);
		$weekend = ($weekstart + 6) % 7;
		// crmv@150808e
		
		$thisyear = date("Y");
		$today = date("Y-m-d",mktime(0, 0, 0, date("m")  , date("d"), date("Y")));
		$tomorrow  = date("Y-m-d",mktime(0, 0, 0, date("m")  , date("d")+1, date("Y")));
		$yesterday  = date("Y-m-d",mktime(0, 0, 0, date("m")  , date("d")-1, date("Y")));

		$currentmonth0 = date("Y-m-d",mktime(0, 0, 0, date("m"), "01",   date("Y")));
		$currentmonth1 = date("Y-m-t");
		$lastmonth0 = date("Y-m-d",mktime(0, 0, 0, date("m")-1, "01",   date("Y")));
		$lastmonth1 = date("Y-m-d", strtotime("last day of previous month")); // crmv@184741
		$nextmonth0 = date("Y-m-d",mktime(0, 0, 0, date("m")+1, "01",   date("Y")));
		$nextmonth1 = date("Y-m-d", strtotime("last day of next month")); // crmv@184741
		
		// crmv@150808
		$todayNum = date('w');

		$prevstart = ($todayNum == $weekstart ? time() : strtotime("last {$dayNames[$weekstart]}"));
		$nextend = ($todayNum == $weekend ? time() : strtotime("next {$dayNames[$weekend]}"));
	
		$lastweek0 = date('Y-m-d', strtotime('-1 week', $prevstart));
		$lastweek1 = date('Y-m-d', strtotime('-1 week', $nextend));
		$thisweek0 = date('Y-m-d', $prevstart);
		$thisweek1 = date('Y-m-d', $nextend);
		$nextweek0 = date('Y-m-d', strtotime('+1 week', $prevstart));
		$nextweek1 = date('Y-m-d', strtotime('+1 week', $nextend));
		// crmv@150808e

		$next7days = date("Y-m-d",mktime(0, 0, 0, date("m")  , date("d")+6, date("Y")));
		$next30days = date("Y-m-d",mktime(0, 0, 0, date("m")  , date("d")+29, date("Y")));
		$next60days = date("Y-m-d",mktime(0, 0, 0, date("m")  , date("d")+59, date("Y")));
		$next90days = date("Y-m-d",mktime(0, 0, 0, date("m")  , date("d")+89, date("Y")));
		$next120days = date("Y-m-d",mktime(0, 0, 0, date("m")  , date("d")+119, date("Y")));

		$last7days = date("Y-m-d",mktime(0, 0, 0, date("m")  , date("d")-6, date("Y")));
		$last30days = date("Y-m-d",mktime(0, 0, 0, date("m")  , date("d")-29, date("Y")));
		$last60days = date("Y-m-d",mktime(0, 0, 0, date("m")  , date("d")-59, date("Y")));
		$last90days = date("Y-m-d",mktime(0, 0, 0, date("m")  , date("d")-89, date("Y")));
		$last120days = date("Y-m-d",mktime(0, 0, 0, date("m")  , date("d")-119, date("Y")));

		$currentFY0 = date("Y-m-d",mktime(0, 0, 0, "01", "01",   date("Y")));
		$currentFY1 = date("Y-m-t",mktime(0, 0, 0, "12", date("d"),   date("Y")));
		$lastFY0 = date("Y-m-d",mktime(0, 0, 0, "01", "01",   date("Y")-1));
		$lastFY1 = date("Y-m-t", mktime(0, 0, 0, "12", date("d"), date("Y")-1));
		$nextFY0 = date("Y-m-d",mktime(0, 0, 0, "01", "01",   date("Y")+1));
		$nextFY1 = date("Y-m-t", mktime(0, 0, 0, "12", date("d"), date("Y")+1));

		// crmv@101474
		if(date("m") <= 3)
		{
			$cFq = date("Y-m-d",mktime(0, 0, 0, "01","01",date("Y")));
			$cFq1 = date("Y-m-d",mktime(0, 0, 0, "03","31",date("Y")));
			$nFq = date("Y-m-d",mktime(0, 0, 0, "04","01",date("Y")));
			$nFq1 = date("Y-m-d",mktime(0, 0, 0, "06","30",date("Y")));
			$pFq = date("Y-m-d",mktime(0, 0, 0, "10","01",date("Y")-1));
			$pFq1 = date("Y-m-d",mktime(0, 0, 0, "12","31",date("Y")-1));
		}else if(date("m") > 3 and date("m") <= 6)
		{
			$pFq = date("Y-m-d",mktime(0, 0, 0, "01","01",date("Y")));
			$pFq1 = date("Y-m-d",mktime(0, 0, 0, "03","31",date("Y")));
			$cFq = date("Y-m-d",mktime(0, 0, 0, "04","01",date("Y")));
			$cFq1 = date("Y-m-d",mktime(0, 0, 0, "06","30",date("Y")));
			$nFq = date("Y-m-d",mktime(0, 0, 0, "07","01",date("Y")));
			$nFq1 = date("Y-m-d",mktime(0, 0, 0, "09","30",date("Y")));

		}else if(date("m") > 6 and date("m") <= 9)
		{
			$nFq = date("Y-m-d",mktime(0, 0, 0, "10","01",date("Y")));
			$nFq1 = date("Y-m-d",mktime(0, 0, 0, "12","31",date("Y")));
			$pFq = date("Y-m-d",mktime(0, 0, 0, "04","01",date("Y")));
			$pFq1 = date("Y-m-d",mktime(0, 0, 0, "06","30",date("Y")));
			$cFq = date("Y-m-d",mktime(0, 0, 0, "07","01",date("Y")));
			$cFq1 = date("Y-m-d",mktime(0, 0, 0, "09","30",date("Y")));
		}
		else if(date("m") > 9 and date("m") <= 12)
		{
			$nFq = date("Y-m-d",mktime(0, 0, 0, "01","01",date("Y")+1));
			$nFq1 = date("Y-m-d",mktime(0, 0, 0, "03","31",date("Y")+1));
			$pFq = date("Y-m-d",mktime(0, 0, 0, "07","01",date("Y")));
			$pFq1 = date("Y-m-d",mktime(0, 0, 0, "09","30",date("Y")));
			$cFq = date("Y-m-d",mktime(0, 0, 0, "10","01",date("Y")));
			$cFq1 = date("Y-m-d",mktime(0, 0, 0, "12","31",date("Y")));
		}
		// crmv@101474e

		if($type == "today" )
		{

			$datevalue[0] = $today;
			$datevalue[1] = $today;
		}
		elseif($type == "yesterday" )
		{

			$datevalue[0] = $yesterday;
			$datevalue[1] = $yesterday;
		}
		elseif($type == "tomorrow" )
		{

			$datevalue[0] = $tomorrow;
			$datevalue[1] = $tomorrow;
		}
		elseif($type == "thisweek" )
		{

			$datevalue[0] = $thisweek0;
			$datevalue[1] = $thisweek1;
		}
		elseif($type == "lastweek" )
		{

			$datevalue[0] = $lastweek0;
			$datevalue[1] = $lastweek1;
		}
		elseif($type == "nextweek" )
		{

			$datevalue[0] = $nextweek0;
			$datevalue[1] = $nextweek1;
		}
		elseif($type == "thismonth" )
		{

			$datevalue[0] =$currentmonth0;
			$datevalue[1] = $currentmonth1;
		}

		elseif($type == "lastmonth" )
		{

			$datevalue[0] = $lastmonth0;
			$datevalue[1] = $lastmonth1;
		}
		elseif($type == "nextmonth" )
		{

			$datevalue[0] = $nextmonth0;
			$datevalue[1] = $nextmonth1;
		}
		elseif($type == "next7days" )
		{

			$datevalue[0] = $today;
			$datevalue[1] = $next7days;
		}
		elseif($type == "next30days" )
		{

			$datevalue[0] =$today;
			$datevalue[1] =$next30days;
		}
		elseif($type == "next60days" )
		{

			$datevalue[0] = $today;
			$datevalue[1] = $next60days;
		}
		elseif($type == "next90days" )
		{

			$datevalue[0] = $today;
			$datevalue[1] = $next90days;
		}
		elseif($type == "next120days" )
		{

			$datevalue[0] = $today;
			$datevalue[1] = $next120days;
		}
		elseif($type == "last7days" )
		{

			$datevalue[0] = $last7days;
			$datevalue[1] = $today;
		}
		elseif($type == "last30days" )
		{

			$datevalue[0] = $last30days;
			$datevalue[1] =  $today;
		}
		elseif($type == "last60days" )
		{

			$datevalue[0] = $last60days;
			$datevalue[1] = $today;
		}
		else if($type == "last90days" )
		{

			$datevalue[0] = $last90days;
			$datevalue[1] = $today;
		}
		elseif($type == "last120days" )
		{

			$datevalue[0] = $last120days;
			$datevalue[1] = $today;
		}
		elseif($type == "thisfy" )
		{

			$datevalue[0] = $currentFY0;
			$datevalue[1] = $currentFY1;
		}
		elseif($type == "prevfy" )
		{

			$datevalue[0] = $lastFY0;
			$datevalue[1] = $lastFY1;
		}
		elseif($type == "nextfy" )
		{

			$datevalue[0] = $nextFY0;
			$datevalue[1] = $nextFY1;
		}
		elseif($type == "nextfq" )
		{

			$datevalue[0] = $nFq;
			$datevalue[1] = $nFq1;
		}
		elseif($type == "prevfq" )
		{

			$datevalue[0] = $pFq;
			$datevalue[1] = $pFq1;
		}
		elseif($type == "thisfq")
		{
			$datevalue[0] = $cFq;
			$datevalue[1] = $cFq1;
		}
		else
		{
			$datevalue[0] = "";
			$datevalue[1] = "";
		}

		return $datevalue;
	}

	/** to get the customview query for the given customview
	  * @param $viewid (custom view id):: type Integer
	  * @param $listquery (List View Query):: type string
	  * @param $module (Module Name):: type string
	  * @returns  $query
	  */

	//CHANGE : TO IMPROVE PERFORMANCE
	function getModifiedCvListQuery($viewid,$listquery,$module,$popuptype=false)
	{
		global $table_prefix;
		if($viewid != "" && $listquery != "")
		{
			//crmv@15845 fix listprice
			$listquery = preg_replace("/[\n\r\t]+/"," ",$listquery); //crmv@20049
			$listviewquery = substr($listquery, stripos($listquery,' FROM '),strlen($listquery));
			//crmv@15845 end
			if($module == "Calendar" || $module == "Emails")
			{
				$query = "select ".$this->getCvColumnListSQL($viewid,$popuptype);
				$field_add = array(
					'activityid'=>$table_prefix.'_activity.activityid',
					'activitytype'=>$table_prefix.'_activity.activitytype as type',
					'eventstatus'=>'case when ('.$table_prefix.'_activity.status is not null) then '.$table_prefix.'_activity.status else '.$table_prefix.'_activity.eventstatus end as status',
					'status'=>$table_prefix.'_activity.activityid',
					'crmid'=>$table_prefix.'_crmentity.crmid',
					'contactid'=>$table_prefix.'_contactdetails.contactid',
				);
				foreach ($field_add as $search=>$replace){
					if (strpos($query,$search)===false){
						$query.=",$replace";
					}
				}
				$query .= " ".$listviewquery;
				if($module == "Calendar")
					$query = str_replace($table_prefix.'_seactivityrel.crmid,','',$query);
			}else if($module == "Documents")
			{
				$query = "select ".$this->getCvColumnListSQL($viewid,$popuptype)." ,".$table_prefix."_crmentity.crmid ".$listviewquery;
			}
			else if($module == "Products")
			{
				$query = "select ".$this->getCvColumnListSQL($viewid,$popuptype)." ,".$table_prefix."_crmentity.crmid ".$listviewquery;
				//crmv@102635
				if(strpos($query,$table_prefix.'_productlines') === false){
					$q = substr($query,0,stripos($query,' WHERE '));
					$q .= "LEFT JOIN {$table_prefix}_productlines ON {$table_prefix}_products.productlineid = {$table_prefix}_productlines.productlineid ";
					$where = substr($query, stripos($query,' WHERE '),strlen($listquery));
					$query = $q.$where;
				}
				//crmv@102635e
			}
			else if($module == "Vendors")
			{
				$query = "select ".$this->getCvColumnListSQL($viewid,$popuptype)." ,".$table_prefix."_crmentity.crmid ".$listviewquery;
			}
			else if($module == "PriceBooks")
			{
				$query = "select ".$this->getCvColumnListSQL($viewid,$popuptype)." ,".$table_prefix."_crmentity.crmid ".$listviewquery;
			}
			else if($module == "Faq")
		       	{
				$query = "select ".$this->getCvColumnListSQL($viewid,$popuptype)." ,".$table_prefix."_crmentity.crmid ".$listviewquery;
			}
			else if($module == "Potentials" || $module == "Contacts" || $module == "Projects") //sk@2s
            {
            	$query = "select ".$this->getCvColumnListSQL($viewid,$popuptype)." ,".$table_prefix."_crmentity.crmid ".$listviewquery;
            }
            else if($module == "Invoice" || $module == "SalesOrder" || $module == "Quotes")
            {
            	$query = "select ".$this->getCvColumnListSQL($viewid,$popuptype)." ,".$table_prefix."_crmentity.crmid ".$listviewquery;
            }
            else if($module == "PurchaseOrder")
            {
            	$query = "select ".$this->getCvColumnListSQL($viewid,$popuptype)." ,".$table_prefix."_crmentity.crmid ".$listviewquery;
            }
			else
			{
				$query = "select ".$this->getCvColumnListSQL($viewid,$popuptype)." ,".$table_prefix."_crmentity.crmid ".$listviewquery;
			}
			$stdfiltersql = $this->getCVStdFilterSQL($viewid);
			$advfiltersql = $this->getCVAdvFilterSQL($viewid);
			if(isset($stdfiltersql) && $stdfiltersql != '')
			{
				$query .= ' and '.$stdfiltersql;
			}
			if(isset($advfiltersql) && $advfiltersql != '')
			{
				$query .= ' and '.$advfiltersql;
			}
		}
		return $query;
	}

	/** to get the Key Metrics for the home page query for the given customview  to find the no of records
	  * @param $viewid (custom view id):: type Integer
	  * @param $listquery (List View Query):: type string
	  * @param $module (Module Name):: type string
	  * @returns  $query
	  */
	function getMetricsCvListQuery($viewid,$listquery,$module)
	{
		if($viewid != "" && $listquery != "")
                {
                        $listviewquery = substr($listquery, strpos($listquery,'FROM'),strlen($listquery));

                        $query = "select count(*) AS count ".$listviewquery;

			$stdfiltersql = $this->getCVStdFilterSQL($viewid);
                        $advfiltersql = $this->getCVAdvFilterSQL($viewid);
                        if(isset($stdfiltersql) && $stdfiltersql != '')
                        {
                                $query .= ' and '.$stdfiltersql;
                        }
                        if(isset($advfiltersql) && $advfiltersql != '')
                        {
                                $query .= ' and '.$advfiltersql;
                        }

                }

                return $query;
	}

	/** to get the custom action details for the given customview
	  * @param $viewid (custom view id):: type Integer
	  * @returns  $calist array in the following format
	  * $calist = Array ('subject'=>$subject,
  			     'module'=>$module,
	     		     'content'=>$content,
			     'cvid'=>$custom view id)
	  */
	function getCustomActionDetails($cvid)
	{
		global $adb,$table_prefix;

		$sSQL = "select ".$table_prefix."_customaction.* from ".$table_prefix."_customaction inner join ".$table_prefix."_customview on ".$table_prefix."_customaction.cvid = ".$table_prefix."_customview.cvid";
		$sSQL .= " where ".$table_prefix."_customaction.cvid=?";
		$result = $adb->pquery($sSQL, array($cvid));

		while($carow = $adb->fetch_array($result))
		{
			$calist["subject"] = $carow["subject"];
			$calist["module"] = $carow["module"];
			$calist["content"] = $carow["content"];
			$calist["cvid"] = $carow["cvid"];
		}
		return $calist;
	}


	/* This function sets the block information for the given module to the class variable module_list
	* and return the array
	*/

	function getCustomViewModuleInfo($module,$reportsubmodules=true)	//crmv@34627
	{
		global $adb,$table_prefix;
		global $current_language;
		$current_mod_strings = return_specified_module_language($current_language, $module);
		$block_info = Array();
		$modules_list = explode(",", $module);
		if($module == "Calendar") {
			$module = "Calendar','Events";
			$modules_list = array('Calendar', 'Events');
		}

		// Tabid mapped to the list of block labels to be skipped for that tab.
		$skipBlocksList = array(
			getTabid('Contacts') => array('LBL_IMAGE_INFORMATION'),
			getTabid('HelpDesk') => array('LBL_COMMENTS'),
			getTabid('Products') => array('LBL_IMAGE_INFORMATION'),
			getTabid('Faq') => array('LBL_COMMENT_INFORMATION'),
			getTabid('Quotes') => array('LBL_RELATED_PRODUCTS'),
			getTabid('PurchaseOrder') => array('LBL_RELATED_PRODUCTS'),
			getTabid('SalesOrder') => array('LBL_RELATED_PRODUCTS'),
			getTabid('Invoice') => array('LBL_RELATED_PRODUCTS'),
			getTabid('Ddt') => array('LBL_RELATED_PRODUCTS')
		);

		$Sql = "select distinct block,".$table_prefix."_field.tabid,name,blocklabel from ".$table_prefix."_field inner join ".$table_prefix."_blocks on ".$table_prefix."_blocks.blockid=".$table_prefix."_field.block inner join ".$table_prefix."_tab on ".$table_prefix."_tab.tabid=".$table_prefix."_field.tabid where displaytype != 3 and ".$table_prefix."_tab.name in (". generateQuestionMarks($modules_list) .") and ".$table_prefix."_field.presence in (0,2) order by block";
		$result = $adb->pquery($Sql, array($modules_list));
		if($module == "Calendar','Events")
			$module = "Calendar";

		$pre_block_label = '';
		while($block_result = $adb->fetch_array($result))
		{
			$block_label = $block_result['blocklabel'];
			$tabid = $block_result['tabid'];
			// Skip certain blocks of certain modules
			if(array_key_exists($tabid, $skipBlocksList) && in_array($block_label, $skipBlocksList[$tabid])) continue;

			if (trim($block_label) == '')
			{
				$block_info[$pre_block_label] = $block_info[$pre_block_label].",".$block_result['block'];
			}else
			{
				//crmv@blocchi custom
				$lan_block_label = getTranslatedString($block_label,$module);
				//crmv@blocchi custom end
				//crmv@34627
				if (!empty($_REQUEST['reportid']) || (!isset($_REQUEST['reportid']) && !empty($this->reportid))) {
					$lan_block_label = getSingleModuleName($module).': '.$lan_block_label;
				}
				//crmv@34627e
				if (isset($block_info[$lan_block_label]) && $block_info[$lan_block_label] != '') {
					$block_info[$lan_block_label] = $block_info[$lan_block_label].",".$block_result['block'];
				} else {
					$block_info[$lan_block_label] = $block_result['block'];
				}
			}
			$pre_block_label = $lan_block_label;
		}
		$this->module_list[$module] = $block_info;
		return $this->module_list;
	}

	//crmv@7635
	function getOrderByFilterByCvid($cvid)
	{
		global $adb,$table_prefix;
		global $modules;

		// crmv@26907
		$sSQL = "select tbl_s_cvorderby.* from tbl_s_cvorderby inner join ".$table_prefix."_customview on tbl_s_cvorderby.cvid = ".$table_prefix."_customview.cvid";
		$sSQL .= " where tbl_s_cvorderby.cvid=?";
		$result = $adb->pquery($sSQL, array($cvid));
		// crmv@26907e

		$noofrows = $adb->num_rows($result);

		if($noofrows <= 0) {
			$advft["columnname"] = "";
			$advft["ordertype"]  = "";
			$advfilterlist[] = $advft;
			return $advfilterlist;
		}

		while($advfilterrow = $adb->fetch_array($result))
		{
			$advft["columnname"] = $advfilterrow["columnname"];
			$advft["ordertype"] = $advfilterrow["ordertype"];
			$advfilterlist[] = $advft;
		}

		return $advfilterlist;
	}

	function getOrderByFilterSQL($cvid)
	{
		$columnslist = $this->getOrderByFilterByCvid($cvid);
		$sql_column = '';

		if(isset($columnslist) && $columnslist != '' && $columnslist != null) {

			$sql_column = "";
			if(isset($columnslist[0]["columnname"]) && $columnslist[0]["columnname"] != "")
			{
				$list = explode(":",$columnslist[0]["columnname"]);
				$sql_column = $list[1];
				$ordertype = $columnslist[0]["ordertype"];

			}
		}
		return array($sql_column,$ordertype);
	}
	//crmv@7635e

	//vtc
	function getCrmvVisibilita($cvid)
	{
		global $adb,$table_prefix;
		$tabid = getTabid($this->customviewmodule);
		$ssql = "select ".$table_prefix."_customview.crmv_user_id from ".$table_prefix."_customview ";
		$ssql .= " where ".$table_prefix."_customview.cvid= ?";//crmv@208173

		$result = $adb->pquery($ssql, array($cvid));//crmv@208173
		$noofrows = $adb->num_rows($result);
		if($noofrows != 1) return 0;
		$fieldtablename = $adb->query_result($result,0,"crmv_user_id");
		if($fieldtablename == "") return 1;
		else return 0;

	}
	//vtc e
	/**
	 * Get the userid, status information of this custom view.
	 *
	 * @param Integer $viewid
	 * @return Array
	 */

	function getStatusAndUserid($viewid) {
		global $adb,$table_prefix;

		if($this->_status === false || $this->_userid === false) {
			$query = "SELECT status, userid FROM ".$table_prefix."_customview WHERE cvid=?";
			$result = $adb->pquery($query, array($viewid));
			if($result && $adb->num_rows($result)) {
				$this->_status = $adb->query_result($result, 0, 'status');
				$this->_userid = $adb->query_result($result, 0, 'userid');
			} else {
				return false;
			}
		}
		return array('status' => $this->_status, 'userid' => $this->_userid);

	}

	//Function to check if the current user is able to see the customView
	function isPermittedCustomView($record_id, $action, $module)
	{
		global $log, $adb,$table_prefix;
		global $current_user;
		$log->debug("Entering isPermittedCustomView($record_id,$action,$module) method....");

		require('user_privileges/requireUserPrivileges.php'); // crmv@39110
		$permission = "yes";

		if($record_id != '')
		{
			$status_userid_info = $this->getStatusAndUserid($record_id);

			if($status_userid_info)
			{
				$status = $status_userid_info['status'];
				$userid = $status_userid_info['userid'];

				if($status == CV_STATUS_DEFAULT)
				{
					$log->debug("Entering when status=0");
					//crmv@26265
					if ($module == 'Calendar' && $action == 'Popup') {
						$permission = "yes";
//					} elseif($action == 'ListView' || $action == $module."Ajax" || $action == 'index' || $action == 'DetailView' || $is_admin) { //crmv@33466
					} elseif($action == 'ListView' || $action == $module."Ajax" || $action == 'index' || $action == 'DetailView') {
						$permission = "yes";
					} else {
						$permission = "no";
					}
					//crmv@26265e
				}
				elseif($is_admin)
				{
					$permission = 'yes';
				}
				elseif ($action != 'ChangeStatus')
				{
					if($userid == $current_user->id)
					{
						$log->debug("Entering when $userid=$current_user->id");
						$permission = "yes";
					}
					elseif($status == CV_STATUS_PUBLIC)
					{
						$log->debug("Entering when status=3");
						if($action == 'ListView' || $action == $module."Ajax" || $action == 'index' || $action == 'DetailView' || $action == 'Popup' || $action == 'HomeView')	// crmv@33814 crmv@141557
						{
							$permission = "yes";
						}
						else
							$permission = "no";
					}
					elseif($status == CV_STATUS_PRIVATE || $status == CV_STATUS_PENDING)
					{
						$log->debug("Entering when status=1 or 2");
						if($userid == $current_user->id)
							$permission = "yes";
						else
						{
							/*if($action == 'ListView' || $action == $module."Ajax" || $action == 'index')
							{*/
								//crmv@22209
								$log->debug("Entering when status=1 or status=2 & action = ListView or $module.Ajax or index");
								$sql="select ".$table_prefix."_users.id,cvid from ".$table_prefix."_customview left join ".$table_prefix."_users on ".$table_prefix."_users.id = ".$table_prefix."_customview.userid where ".$table_prefix."_customview.cvid = ? and ".$table_prefix."_customview.userid in (select ".$table_prefix."_user2role.userid from ".$table_prefix."_user2role inner join ".$table_prefix."_users on ".$table_prefix."_users.id=".$table_prefix."_user2role.userid inner join ".$table_prefix."_role on ".$table_prefix."_role.roleid=".$table_prefix."_user2role.roleid where ".$table_prefix."_role.parentrole like '%".$current_user_parent_role_seq."::%')";
								$result = $adb->pquery($sql, array($record_id));

								while($row = $adb->fetchByAssoc($result))
								{
									$temp_result[] = $row['cvid'];
								}
								$cv_array = $temp_result;
								if(sizeof($cv_array) > 0)
								{
									if(!in_array($record_id,$cv_array))
										$permission = "no";
									else
										$permission = "yes";
								}
								else
									$permission = "no";
								//crmv@22209e
							/*}
							else
							{
								$log->debug("Entering when status=1 or 2 & action = Editview or Customview");
								$permission = "no";
							}*/
						}
					}
					else
						$permission = "yes";
				}
				else
				{
					$log->debug("Entering else condition............");
					$permission = "no";
				}
			}
			else
			{
				$log->debug("Enters when count =0");
				$permission = 'no';
			}
		}
		$log->debug("Permission @@@@@@@@@@@@@@@@@@@@@@@@@@@ : $permission");
		$log->debug("Exiting isPermittedCustomView($record_id,$action,$module) method....");
		return $permission;
	}

	function isPermittedChangeStatus($status)
	{
		global $current_user,$log;
		global $current_language;
		$custom_strings = return_module_language($current_language, "CustomView");

		$log->debug("Entering isPermittedChangeStatus($status) method..............");
		require('user_privileges/requireUserPrivileges.php'); // crmv@39110
		$status_details = Array();
		if($is_admin)
		{
			if($status == CV_STATUS_PENDING)
			{
				$changed_status = CV_STATUS_PUBLIC;
				$status_label = $custom_strings['LBL_STATUS_PUBLIC_APPROVE'];
			}
			elseif($status == CV_STATUS_PUBLIC)
			{
				$changed_status = CV_STATUS_PENDING;
				$status_label = $custom_strings['LBL_STATUS_PUBLIC_DENY'];
			}
			$status_details = Array('Status'=>$status,'ChangedStatus'=>$changed_status,'Label'=>$status_label);
		}
		$log->debug("Exiting isPermittedChangeStatus($status) method..............");
		return $status_details;
	}

	//crmv@26161
	function getAdvFilterOptions() {
		$adv_filter_options = array("e"=>getTranslatedString('equals', 'CustomView'),
		                            "n"=>getTranslatedString('not equal to', 'CustomView'),
		                            "s"=>getTranslatedString('starts with', 'CustomView'),
		                            "ew"=>getTranslatedString('ends with', 'CustomView'),
		                            "c"=>getTranslatedString('contains', 'CustomView'),
		                            "k"=>getTranslatedString('does not contain', 'CustomView'),
		                            "l"=>getTranslatedString('less than', 'CustomView'),
		                            "g"=>getTranslatedString('greater than', 'CustomView'),
		                            "m"=>getTranslatedString('less or equal', 'CustomView'),
		                            "h"=>getTranslatedString('greater or equal', 'CustomView'),
		                            );
		return $adv_filter_options;
	}
	//crmv@26161e

	function getByModule_ColumnsHTML($module,$columnslist,$selected="",$reportsubmodules=true)	//crmv@34627
	{
		global $current_language,$theme;
		global $app_list_strings;
		$advfilter = array();
		$advfilter_out = array(); // crmv@197210
		$mod_strings = return_specified_module_language($current_language,$module);

		// crmv@80759
		$check_dup = Array();
		$matchSelected = $selected;
		//crmv@119815 - removed code
		foreach ($this->module_list[$module] as $key=>$value) {
			$advfilter = array();
			$label = $key;
			
			if (isset($columnslist[$module][$key])) {
				foreach($columnslist[$module][$key] as $field=>$fieldlabel) {
					$matchField = $field; // crmv@119815
					if (!in_array($fieldlabel,$check_dup)) {
						$advfilter_option['value'] = $field;
						$advfilter_option['text'] = (empty($mod_strings[$fieldlabel]) ? $fieldlabel : $mod_strings[$fieldlabel]); // crmv@81050
						$advfilter_option['selected'] = ($matchSelected == $matchField ? "selected" : "");

						$advfilter[] = $advfilter_option;
						$check_dup [] = $fieldlabel;
					}
				}
				$advfilter_out[$label]= $advfilter;
			}
		}
		// crmv@80759e

		// Special case handling only for Calendar moudle - Not required for other modules.
		if($module == 'Calendar') {
			$finalfield = Array();
			$finalfield1 = Array();
			$finalfield2 = Array();
			$newLabel = $mod_strings['LBL_CALENDAR_INFORMATION'];
			//crmv@26376
			$advfilter_out[$newLabel] = array();
			if(isset($advfilter_out[$mod_strings['LBL_TASK_INFORMATION']])) {
				$advfilter_out[$newLabel] = $advfilter_out[$mod_strings['LBL_TASK_INFORMATION']];
				unset($advfilter_out[$mod_strings['LBL_TASK_INFORMATION']]);
			}
			if(isset($advfilter_out[$mod_strings['LBL_EVENT_INFORMATION']])) {
				$advfilter_out[$newLabel] = array_merge($advfilter_out[$mod_strings['LBL_EVENT_INFORMATION']], $advfilter_out[$newLabel]);
				unset($advfilter_out[$mod_strings['LBL_EVENT_INFORMATION']]);
			}
			if (empty($advfilter_out[$newLabel])) unset($advfilter_out[$newLabel]);
			ksort($advfilter_out);
			//crmv@26376e
		}
		//crmv@34627
		if ($reportsubmodules) {
			$reportmodules = $this->getReportModules();
			if ($reportmodules) {
				foreach ($reportmodules as $reportmodule) {
					if ($reportmodule != $module) {
						$advfilter_out = array_merge($advfilter_out,$this->getByModule_ColumnsHTML($reportmodule,$columnslist,$selected,false));
					}
				}
			}
		}
		//crmv@34627e
		return $advfilter_out;
	}
	
	// crmv@150024
	public function trash($module, $cvid) {
		global $adb, $table_prefix;
		
		require_once('include/utils/ModuleHomeView.php');
		
		$params = array($cvid);
		$adb->pquery("DELETE FROM {$table_prefix}_customview WHERE cvid = ?", $params);
		$adb->pquery("DELETE FROM {$table_prefix}_cvstdfilter WHERE cvid = ?", $params);
		$adb->pquery("DELETE FROM {$table_prefix}_cvadvfilter WHERE cvid = ?", $params);
		$adb->pquery("DELETE FROM {$table_prefix}_cvcolumnlist WHERE cvid = ?", $params);
		$adb->pquery("DELETE FROM tbl_s_cvorderby WHERE cvid = ?", $params);

		// remove filter notifications
		$adb->pquery("DELETE FROM vte_modnot_follow_cv WHERE cvid = ?", $params);
		
		unsetLVS($module,"viewname");

		// update the modhome blocks
		$MHW = ModuleHomeView::getInstance($module);
		$MHW->handleRemoveFilter($cvid);
		
		// remove the filter from dynamic targets
		$focusTargets = CRMEntity::getInstance('Targets');
		$focusTargets->deleteDynamicCV($cvid);

		// crmv@49398
		global $metaLogs;
		if ($metaLogs) $metaLogs->log($metaLogs::OPERATION_DELFILTER, $cvid, array('module'=>$module));
		// crmv@49398e
	}
	// crmv@150024e
	
}

/** to get the standard filter criteria
	* @param $module(module name) :: Type String
	* @param $elected (selection status) :: Type String (optional)
	* @returns  $filter Array in the following format
	* $filter = Array( 0 => array('value'=>$tablename:$colname:$fieldname:$fieldlabel,'text'=>$mod_strings[$field label],'selected'=>$selected),
	* 		     1 => array('value'=>$$tablename1:$colname1:$fieldname1:$fieldlabel1,'text'=>$mod_strings[$field label1],'selected'=>$selected),
	*/
function getStdFilterHTML($module,$selected="")
{
	global $app_list_strings, $current_language,$app_strings,$current_user;
	require('user_privileges/requireUserPrivileges.php'); // crmv@39110
	global $oCustomView;
	$stdfilter = array();
	$result = $oCustomView->getStdCriteriaByModule($module);
	$mod_strings = return_module_language($current_language,$module);

	if(isset($result))
	{
		foreach($result as $key=>$value)
		{
			if($value == 'Start Date & Time')
			{
				$value = 'Start Date';
			}
			$use_module_label =  getTranslatedString($module, $module);
			if(isset($app_list_strings['moduleList'][$module])) {
				$use_module_label = $app_list_strings['moduleList'][$module];
			}
			if(isset($mod_strings[$value]))
			{
				if($key == $selected)
				{

					$filter['value'] = $key;
					$filter['text'] = $use_module_label." - ".getTranslatedString($value, $module); //crmv@42329 
					$filter['selected'] = "selected";
				}else
				{
						$filter['value'] = $key;
						$filter['text'] = $use_module_label." - ".getTranslatedString($value, $module); //crmv@42329 
						$filter['selected'] ="";
				}
			}
			else
			{
				if($key == $selected)
				{
					$filter['value'] = $key;

					$filter['text'] = $use_module_label." - ".$value;
					$filter['selected'] = 'selected';
				}else
				{
					$filter['value'] = $key;
					$filter['text'] = $use_module_label." - ".$value;
					$filter['selected'] ='';
				}
			}
			$stdfilter[]=$filter;
			//added to fix ticket #5117. If a user doesn't have permission for a field and it has been used to fileter a custom view, it should be get displayed to him as Not Accessible.
			if(!$is_admin && $selected != '' && $filter['selected'] == '')
			{
				$keys = explode(":",$selected);
				if(getFieldVisibilityPermission($module,$current_user->id,$keys[2]) != '0')
				{
					$filter['value'] = "not_accessible";
					$filter['text'] = $app_strings["LBL_NOT_ACCESSIBLE"];
					$filter['selected'] = "selected";
					$stdfilter[]=$filter;
				}
			}

		}

	}
	return $stdfilter;
}
?>