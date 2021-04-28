<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@56023 */

require_once('include/ListView/ListView.php');

class LoginProtectionViewer extends SDKExtendableUniqueClass {

	var $track_login_table;
	var $enable_login_protection;
	var $userid;
	var $actiondate;
	
	var $sortby_fields = Array('module', 'action', 'actiondate');	 

	var $list_fields = Array();	
	var $list_fields_name = Array();	
		
	var $default_order_by = "last_attempt";
	var $default_sort_order = 'DESC';
	
	public static function getInstance() {
		$focus = parent::getInstance();
		
		$user = CRMEntity::getInstance('Users');
		$focus->track_login_table = $user->track_login_table;
		$focus->enable_login_protection = $user->enable_login_protection;
		
		$focus->list_fields = Array('User Name'=>Array($focus->track_login_table=>'userid'),
									'First Attempt'=>Array($focus->track_login_table=>'first_attempt'),
									'Last Attempt'=>Array($focus->track_login_table=>'last_attempt'),
									'IP'=>Array($focus->track_login_table=>'ip'),
									'Type'=>Array($focus->track_login_table=>'type'),
									'Attempts'=>Array($focus->track_login_table=>'attempts'),
									'Status'=>Array($focus->track_login_table=>'status'),
									'Whitelist Date'=>Array($focus->track_login_table=>'date_whitelist'),
								);
		
		$focus->list_fields_name = Array('User Name'=>'userid',
										'First Attempt'=>'first_attempt', 
										'Last Attempt'=>'last_attempt',
									    'IP'=>'ip',
										'Type'=>'type',
										'Attempts'=>'attempts',
										'Status'=>'status',
										'Whitelist Date'=>'date_whitelist',
									);
		
		return $focus;
	}
	
	function getLoginProtectionStatus(){
		return $this->enable_login_protection;
	}
	
	/**Function to get the table headers for a listview
	 *Param $navigation_arrray - navigation values in array
	*Param $url_qry - url string
	*Param $module - module name
	*Param $action- action file name
	*Param $viewid - view id
	*Returns an string value
	*/
	function getLoginProtectionNavigation($navigation_array, $url_qry,$module='',$action_val='index',$viewid='')
	{
		global $log,$app_strings;
		$log->debug("Entering getTableHeaderNavigation(".$navigation_array.",". $url_qry.",".$module.",".$action_val.",".$viewid.") method ...");
		global $theme,$current_user;
		$theme_path="themes/".$theme."/";
		$image_path=$theme_path."images/";
	
		$output = '<td align="right" style="padding="5px;">';
	
		$tabname = getParentTab();
	
		$url_string = '';
	
		if(($navigation_array['prev']) != 0)
		{
			$output .= '<a href="javascript:;" onClick="fetchloginprotection_js(getObj(\'user_list\'),\'&parenttab='.$tabname.'&start=1'.$url_string.'\');" alt="'.$app_strings['LBL_FIRST'].'" title="'.$app_strings['LBL_FIRST'].'"><img src="'.$image_path.'start.gif" border="0" align="absmiddle"></a>&nbsp;';
			$output .= '<a href="javascript:;" onClick="fetchloginprotection_js(getObj(\'user_list\'),\'&parenttab='.$tabname.'&start='.$navigation_array['prev'].$url_string.'\');" alt="'.$app_strings['LNK_LIST_PREVIOUS'].'"title="'.$app_strings['LNK_LIST_PREVIOUS'].'"><img src="'.$image_path.'previous.gif" border="0" align="absmiddle"></a>&nbsp;';
		}
		else
		{
			$output .= '<img src="'.$image_path.'start_disabled.gif" border="0" align="absmiddle">&nbsp;';
			$output .= '<img src="'.$image_path.'previous_disabled.gif" border="0" align="absmiddle">&nbsp;';
		}
		for ($i=$navigation_array['first'];$i<=$navigation_array['end'];$i++){
			if ($navigation_array['current']==$i){
				$output .='<b>'.$i.'</b>&nbsp;';
			}
			else{
				$output .= '<a href="javascript:;" onClick="fetchloginprotection_js(getObj(\'user_list\'),\'&start='.$i.$url_string.'\');" >'.$i.'</a>&nbsp;';
			}
		}
		if(($navigation_array['next']) !=0)
		{
			$output .= '<a href="javascript:;" onClick="fetchloginprotection_js(getObj(\'user_list\'),\'&parenttab='.$tabname.'&start='.$navigation_array['next'].$url_string.'\');" alt="'.$app_strings['LNK_LIST_NEXT'].'" title="'.$app_strings['LNK_LIST_NEXT'].'"><img src="'.$image_path.'next.gif" border="0" align="absmiddle"></a>&nbsp;';
			$output .= '<a href="javascript:;" onClick="fetchloginprotection_js(getObj(\'user_list\'),\'&parenttab='.$tabname.'&start='.$navigation_array['verylast'].$url_string.'\');" alt="'.$app_strings['LBL_LAST'].'" title="'.$app_strings['LBL_LAST'].'"><img src="'.$image_path.'end.gif" border="0" align="absmiddle"></a>&nbsp;';
		}
		else
		{
			$output .= '<img src="'.$image_path.'next_disabled.gif" border="0" align="absmiddle">&nbsp;';
			$output .= '<img src="'.$image_path.'end_disabled.gif" border="0" align="absmiddle">&nbsp;';
		}
		$output .= '</td>';
		
		$log->debug("Exiting getTableHeaderNavigation method ...");
		
		if($navigation_array['first']=='')
			return '';
		else
			return $output;
	}
	
	/**
	 * Function to get the Headers of Audit Trail Information like Module, Action, RecordID, ActionDate.
	 * Returns Header Values like Module, Action etc in an array format.
	**/
	function getLoginProtectionHeader()
	{
		global $log;
		
		$log->debug("Entering getLoginProtectionHeader() method ...");
		
		$header_array = array();
		$header_array[] = getTranslatedString('Tools');
		
		foreach ($this->list_fields_name as $k => $v){
			$translated_string = getTranslatedString($k,'Settings');
			array_push($header_array,$translated_string);
		}
		

		$log->debug("Exiting getLoginProtectionHeader() method ...");
		return $header_array;
	}

	/**
	  * Function to get the Audit Trail Information values of the actions performed by a particular User.
	  * @param integer $userid - User's ID
	  * @param $navigation_array - Array values to navigate through the number of entries.
	  * @param $sortorder - DESC
	  * @param $orderby - actiondate
	  * Returns the audit trail entries in an array format.
	**/
	function getLoginProtectionEntries($userid, $navigation_array, $sorder='', $orderby='')
	{
		global $log,$table_prefix,$adb,$current_user;
		$log->debug("Entering getLoginProtectionEntries(".$userid.") method ...");
		
		$list_query = "select * from {$this->track_login_table}";
		$params = array();
		
		if($userid != ''){
			$list_query .= " where userid =? ";
			array_push($params,$userid);
		}
		
		if($sorder != '' && $orderby != '')
			$list_query .= " order by ".$orderby." ".$sorder; 
		else
			$list_query .= " order by ".$this->default_order_by." ".$this->default_sort_order;
	
		$result = $adb->pquery($list_query, $params);
		$entries_list = array();

		if($navigation_array['end_val'] != 0)
		{
			for($i = $navigation_array['start']; $i <= $navigation_array['end_val']; $i++)
			{
				$entries = array();
				
				$recordid = $adb->query_result($result, $i-1, 'id');
				$status = $adb->query_result($result, $i-1, 'status');
				//@TODO: make this dynamic 
				$entries[] = $this->getWhitelistAction($recordid,$status);
				$entries[] = getUserName($adb->query_result($result, $i-1, 'userid'));
				$entries[] = getDisplayDate($adb->query_result($result, $i-1, 'first_attempt'));
				$entries[] = getDisplayDate($adb->query_result($result, $i-1, 'last_attempt'));
				$entries[] = $adb->query_result($result, $i-1, 'ip');
				$entries[] = $this->translateValue('type', $adb->query_result($result, $i-1, 'type'));
				$entries[] = $adb->query_result($result, $i-1, 'attempts');
				$entries[] = $this->translateValue('status', $status);
				$entries[] = getDisplayDate($adb->query_result($result, $i-1, 'date_whitelist'));
				
				$entries_list[$recordid]=$entries;
			}
		}
		
		$log->debug("Exiting getLoginProtectionEntries() method ...");
		return $entries_list;
	}
	
	function getWhitelistAction($recordid,$status){
		global $log,$table_prefix,$adb,$current_user;
		$log->debug("Entering getWhitelistAction(".$recordid.")  method ...");
		
		$button = '';
		if($status != 'W'){
			$button .= '<a href="javascript:doNothing();" onclick="VTE.Settings.LoginProtectionPanel.whiteListRecord('.$recordid.')">'.getTranslatedString('LBL_ADD_TO_WHITELIST','Settings').'</a>';
		}
		
		$log->debug("Exiting getWhitelistAction() method ...");
		return $button;
	}
	
	function translateValue($field, $value) {
		if ($field == 'type') {
			if ($value == 'ws') {
				$value = 'webservice';
			} elseif ($value == 'as') {
				$value = 'active sync';
			}
		} elseif ($field == 'status') {
			if ($value == 'W') {
				$value = getTranslatedString('LBL_STATUS_WHITELIST','Settings');
			} elseif ($value == 'L') {
				$value = getTranslatedString('LBL_STATUS_LOCKED','Settings');
			} elseif ($value == 'B') {
				$value = getTranslatedString('LBL_STATUS_BANNED','Settings');
			}
		}
		return $value;
	}
}
?>