<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

require_once('include/database/PearDatabase.php');
require_once('include/ComboUtil.php'); //new
require_once('include/utils/CommonUtils.php'); //new


/** This function returns the detail view form vte_field and and its properties in array format.
  * Param $uitype - UI type of the vte_field
  * Param $fieldname - Form vte_field name
  * Param $fieldlabel - Form vte_field label name
  * Param $col_fields - array contains the vte_fieldname and values
  * Param $generatedtype - Field generated type (default is 1)
  * Param $tabid - vte_tab id to which the Field belongs to (default is "")
  * Return type is an array
  */

function getDetailViewOutputHtml($uitype, $fieldname, $fieldlabel, $col_fields,$generatedtype,$tabid='',$module='',$dynaform_info=array())	//crmv@96450
{
	global $log,$adb, $table_prefix;
	$log->debug("Entering getDetailViewOutputHtml(".$uitype.",". $fieldname.",". $fieldlabel.",". $col_fields.",".$generatedtype.",".$tabid.") method ...");
	global $mod_strings, $app_strings;
	global $current_user;
	global $theme;
	$theme_path="themes/".$theme."/";
	$image_path=$theme_path."images/";
	$fieldlabel = from_html($fieldlabel);
	$custfld = '';
	$value ='';
	$arr_data =Array();
	$label_fld = Array();
	$data_fld = Array();

	$InventoryUtils = InventoryUtils::getInstance(); // crmv@42024

	require('user_privileges/requireUserPrivileges.php'); // crmv@39110
	require('user_privileges/sharing_privileges_'.$current_user->id.'.php');
	//crmv@10666
	if($generatedtype == 2 && $mod_strings[$fieldlabel] == ''){
		$mod_strings[$fieldlabel] = $fieldlabel;
	}
	//crmv@10666e
	//crmv@sdk-18509
	if(SDK::isUitype($uitype))
	{
		$sdk_file = SDK::getUitypeFile('php','detail',$uitype);
		if ($sdk_file != '') {
			include($sdk_file);
		}
	}
	//crmv@sdk-18509 e
	// vtlib customization: New uitype to handle relation between modules
	elseif($uitype == '10'){
		//crmv@90385
		$fieldlabel = getTranslatedString($fieldlabel, $module);
		
		$fldmod_result = $adb->pquery('SELECT relmodule, status FROM '.$table_prefix.'_fieldmodulerel WHERE fieldid=
			(SELECT fieldid FROM '.$table_prefix.'_field, '.$table_prefix.'_tab WHERE '.$table_prefix.'_field.tabid='.$table_prefix.'_tab.tabid AND fieldname=? AND name=? and '.$table_prefix.'_field.presence in (0,2))',
			Array($fieldname, $module));
		$entityTypes = Array();
		$parent_id = $value;
		for($index = 0; $index < $adb->num_rows($fldmod_result); ++$index) {
			$entityTypes[] = $adb->query_result($fldmod_result, $index, 'relmodule');
		}
			
		$parent_id = $col_fields[$fieldname];
		if(!empty($parent_id)) {
			if (in_array('Users',$entityTypes)) {
				$resUserField = $adb->pquery("SELECT user_name, first_name, last_name FROM {$table_prefix}_users WHERE id = ?", array($parent_id));
				if ($resUserField && $adb->num_rows($resUserField) > 0) {
					$parent_module = 'Users';
					$displayValue = getUserFullName($userid, $resUserField);
				}
			}
			if (empty($displayValue)) {
				$parent_module = getSalesEntityType($parent_id);
				$displayValueArray = getEntityName($parent_module, $parent_id);
				if(!empty($displayValueArray)){
					foreach($displayValueArray as $key=>$value){
						$displayValue = $value;
					}
				}
			}
			$valueTitle=$parent_module;
			if($app_strings[$valueTitle]) $valueTitle = $app_strings[$valueTitle];
			$label_fld=array($fieldlabel,
				"<a href='index.php?module=$parent_module&action=DetailView&record=$parent_id' title='$valueTitle' data-panelview='true' data-panelview-mode='detail' data-entitypreview='true' data-module='$parent_module' data-record='$parent_id'>$displayValue</a>");	//crmv@152802 // crmv@157124
		} else {
			$moduleSpecificMessage = 'MODULE_NOT_SELECTED';
			if($mod_strings[$moduleSpecificMessage] != ""){
				$moduleSpecificMessage = $mod_strings[$moduleSpecificMessage];
			}
			$label_fld=array($fieldlabel, '');
		}
		//crmv@90385e
	} // END
	elseif($uitype == 99)
	{
		$label_fld[] = getTranslatedString($fieldlabel, $module);
		$label_fld[] = $col_fields[$fieldname];
		if($fieldname == 'confirm_password')
			return null;
	}elseif($uitype == 116 || $uitype == 117)
	{
		$label_fld[] = getTranslatedString($fieldlabel, $module);
                $label_fld[] = getCurrencyName($col_fields[$fieldname]);
		$pick_query="select * from ".$table_prefix."_currency_info where currency_status = 'Active' and deleted=0";
		$pickListResult = $adb->pquery($pick_query, array());
		$noofpickrows = $adb->num_rows($pickListResult);

		//Mikecrowe fix to correctly default for custom pick lists
		$options = array();
		$found = false;
		for($j = 0; $j < $noofpickrows; $j++)
		{
			$pickListValue=$adb->query_result($pickListResult,$j,'currency_name');
			$currency_id=$adb->query_result($pickListResult,$j,'id');
			if($col_fields[$fieldname] == $currency_id)
			{
				$chk_val = "selected";
				$found = true;
			}
			else
			{
				$chk_val = '';
			}
			$options[$currency_id] = array($pickListValue=>$chk_val );
		}
		$label_fld ["options"] = $options;
	}
	elseif($uitype == 13 || $uitype == 104)
	{
		$label_fld[] = getTranslatedString($fieldlabel, $module);
		$label_fld[] = $col_fields[$fieldname];
	}
	elseif($uitype == 15 || $uitype == 16)
	{
		$label_fld[] = getTranslatedString($fieldlabel, $module);
		$label_fld[] = $col_fields[$fieldname];
		//crmv@96450
		if (!empty($dynaform_info['picklistvalues'])) {
			$picklistvalues = explode("\n",$dynaform_info['picklistvalues']);
			if (!empty($picklistvalues)) {
				foreach($picklistvalues as $picklistvalue) {
					$picklistvalue = trim($picklistvalue);
					$values_arr[$picklistvalue] = $picklistvalue;
				}
			}
		} else {
			$roleid=$current_user->roleid;
			$values_arr = getAssignedPicklistValues($fieldname, $roleid, $adb,$module);
		}
		//crmv@96450e
		$value = $col_fields[$fieldname];
		$value_decoded = decode_html($value);
		$pickcount = count($values_arr);
		if ($pickcount > 0){
			$chk_selected = false; //crmv@65492 - 10
			foreach ($values_arr as $pickListValue=>$translated_value){
				//crmv@65492 - 10
				if($value_decoded == trim($pickListValue)){
					$chk_val = "selected";
					$chk_selected = true;
				}else {
					$chk_val = '';
				}
				//crmv@65492e - 10
				$pickListValue = to_html($pickListValue);
				if(isset($_REQUEST['file']) && $_REQUEST['file'] == 'QuickCreate')
					$options[] = array(htmlentities($translated_value,ENT_QUOTES,$default_charset),$pickListValue,$chk_val );
				else
					$options[] = array($translated_value,$pickListValue,$chk_val );
			}
			//crmv@65492 - 10
			if(!$chk_selected && !empty($value_decoded)){
				$options[] =  array($app_strings['LBL_NOT_ACCESSIBLE'],$value,'selected');
			}
			//crmv@65492e - 10
		}
		elseif($pickcount == 0 && !empty($value)) // crmv@167234
		{
			$options[] =  array("<font color='red'>".$app_strings['LBL_NOT_ACCESSIBLE']."</font>",$value,'selected');
		}
		$label_fld ["options"] = $options;
	}
	//crmv@8982
	elseif($uitype == 1015)
	{
		$label_fld[] = getTranslatedString($fieldlabel,$module); //crmv@481398
		if ($col_fields[$fieldname] != ""){
			$label_fld[] = Picklistmulti::getTranslatedPicklist($col_fields[$fieldname],$fieldname);
		}
		else
			$label_fld[] = '';
		$picklistvalues = Picklistmulti::getTranslatedPicklist(false,$fieldname);
		if (is_array($picklistvalues)){
			foreach ($picklistvalues as $picklistid=>$pickListValue){
				if ($col_fields[$fieldname] == trim($picklistid)){
					$chk_val = "selected";
					$pickcount++;
					$found = true;
				}
				else
					$chk_val = '';
					$pickListValue =to_html($pickListValue);
					$options[] = array($pickListValue,$picklistid,$chk_val );
				}
		}
		$label_fld ["options"] = $options;
	}
	//crmv@8982e
	elseif($uitype == 33)
	{
		$value = $col_fields[$fieldname];
		$label_fld[] = getTranslatedString($fieldlabel, $module);
		$label_fld[] = str_ireplace(' |##| ',', ',$value);
		//crmv@96450
		if (!empty($dynaform_info['picklistvalues'])) {
			$picklistvalues = explode("\n",$dynaform_info['picklistvalues']);
			if (!empty($picklistvalues)) {
				foreach($picklistvalues as $picklistvalue) {
					$picklistvalue = trim($picklistvalue);
					$values_arr[$picklistvalue] = $picklistvalue;
				}
			}
		} else {
			$roleid=$current_user->roleid;
			$values_arr = getAssignedPicklistValues($fieldname, $roleid, $adb,$module);
		}
		//crmv@96450e
		$value_decoded = decode_html($value);
		$valuearr = explode(' |##| ',$value);
		$valuearr_decoded = explode(' |##| ',$value_decoded);
		$pickcount = count($values_arr);
		if ($pickcount > 0){
			foreach ($values_arr as $pickListValue=>$translated_value){
				if(in_array(trim($pickListValue),$valuearr_decoded))
					$chk_val = "selected";
				else
					$chk_val = '';
				$pickListValue = to_html($pickListValue);
				if(isset($_REQUEST['file']) && $_REQUEST['file'] == 'QuickCreate')
					$options[] = array(htmlentities($translated_value,ENT_QUOTES,$default_charset),$pickListValue,$chk_val );
				else
					$options[] = array($translated_value,$pickListValue,$chk_val );
			}
			if ($value != ''){
				foreach (array_diff($valuearr_decoded,array_keys($values_arr)) as $value_not_accessible){
					 $options[] =  array("<font color='red'>".$app_strings['LBL_NOT_ACCESSIBLE']."</font>",$value_not_accessible,'selected');
				}
			}
		}
		elseif($value != '')
		{
			$options[] =  array("<font color='red'>".$app_strings['LBL_NOT_ACCESSIBLE']."</font>",$value,'selected');
		}
		$label_fld ["options"] = $options;
	}
	elseif($uitype == 17)
	{
		$label_fld[] = getTranslatedString($fieldlabel, $module);
		$label_fld[] = $col_fields[$fieldname];
	}
	elseif($uitype == 19)
	{
		//crmv@2963m crmv@56409
		if ($fieldname == 'description' && $module == 'Messages') {
			// translates only &amp; to preserve &lt; and &gt; in the raw html
			// blame the query_result, which by default converts everything from html :(
			// this field is already supposed to be pure html in the database
			$col_fields[$fieldname] = str_replace('&amp;', '&', $col_fields[$fieldname]);
		//crmv@2963me crmv@56409e
		} elseif($fieldname == 'notecontent' || ($fieldname == 'description' && $module == 'Timecards')){ //crmv@22860
			$col_fields[$fieldname]= decode_html($col_fields[$fieldname]);
			//crmv@361584
			if ($col_fields[$fieldname] != strip_tags($col_fields[$fieldname])){
				$col_fields[$fieldname] = str_replace("\r",'',$col_fields[$fieldname]);
				$col_fields[$fieldname] = str_replace("\n",'',$col_fields[$fieldname]);
			}
			//crmv@361584e
		}
		else
			$col_fields[$fieldname]= str_replace("&lt;br /&gt;","<br>",$col_fields[$fieldname]);
		$label_fld[] = getTranslatedString($fieldlabel, $module);
		$label_fld[] = $col_fields[$fieldname];
	}
	elseif($uitype == 21) // Armando LC<scher 11.08.2005 -> B'descriptionSpan -> Desc: removed $uitype == 19 and made an aditional elseif above
	{
		$col_fields[$fieldname]=nl2br($col_fields[$fieldname]);
		$label_fld[] = getTranslatedString($fieldlabel, $module);
		$label_fld[] = $col_fields[$fieldname];
	}
	//crmv@101683
	elseif($uitype == 52 || $uitype == 77 || $uitype == 54)
	{
		$label_fld[] = getTranslatedString($fieldlabel, $module);
		$id = $col_fields[$fieldname];
		if ($uitype == 54) {
			$name = getGroupName($id);
			$name = $name[0];
			$link = '<a href="index.php?module=Settings&action=GroupDetailView&groupId='.$id.'">'.$name.'</a>';
			$assigned_user_id = $id;
		} else {
			global $showfullusername;
			$name = getUserName($id,$showfullusername);
			$link = '<a href="index.php?module=Users&action=DetailView&record='.$id.'">'.$name.'</a>';
			$assigned_user_id = $current_user->id;
		}
		if(is_admin($current_user))
		{
			$label_fld[] = $link;
		}
		else
		{
			$label_fld[] = $name;
		}
		if($is_admin==false && $profileGlobalPermission[2] == 1 && ($defaultOrgSharingPermission[getTabid($module)] == 3 or $defaultOrgSharingPermission[getTabid($module)] == 0))
		{
			if ($uitype == 54)
				$combo = get_select_options_array(get_group_array(FALSE, "Active", $assigned_user_id,'private'), $assigned_user_id);
			else
				$combo = get_select_options_array(get_user_array(FALSE, "Active", $assigned_user_id,'private'), $assigned_user_id);
		}
		else
		{
			if ($uitype == 54)
				$combo = get_select_options_array(get_group_array(FALSE, "Active", $id), $assigned_user_id);
			else
				$combo = get_select_options_array(get_user_array(FALSE, "Active", $id), $assigned_user_id);
		}
		$label_fld ["options"] = $combo;
	}
	//crmv@101683e
	// crmv@112606
	elseif($uitype == 53)
	{
		$owner_id = $col_fields[$fieldname];

		$user = 'no';
		$result = $adb->pquery("SELECT count(*) as count from ".$table_prefix."_users where id = ?",array($owner_id));
		if($adb->query_result($result,0,'count') > 0) {
			$user = 'yes';
		}

		global $showfullusername;
		$owner_name = getUserName($owner_id,$showfullusername);
		//crmv@93486
		if(empty($owner_name)){
			$tmp = getGroupName($owner_id);
			$owner_name = $tmp[0];
		}
		//crmv@93486e
		$label_fld[] =getTranslatedString($fieldlabel, $module);
		$label_fld[] =$owner_name;

		if(is_admin($current_user))
		{
			$label_fld["secid"][] = $owner_id;
			if($user == 'no') {
				$label_fld["link"][] = "index.php?module=Settings&action=GroupDetailView&groupId=".$owner_id;
			}
			else {
				$label_fld["link"][] = "index.php?module=Users&action=DetailView&record=".$owner_id;
			}
			//$label_fld["secid"][] = $groupid;
			//$label_fld["link"][] = "index.php?module=Settings&action=GroupDetailView&groupId=".$groupid;
		}

		//$value = $user_id;
		if($owner_id != '') {
			if($user == 'yes') {
				$label_fld ["options"][] = 'User';
				$assigned_user_id = $owner_id;
				$user_checked = "checked";
				$team_checked = '';
				$user_style='display:block';
				$team_style='display:none';
			}
			else {
				//$record = $col_fields["record_id"];
				//$module = $col_fields["record_module"];
				$label_fld ["options"][] = 'Group';
				$assigned_group_id = $owner_id;
				$user_checked = '';
				$team_checked = 'checked';
				$user_style='display:none';
				$team_style='display:block';
			}
		}
		else {
			$label_fld ["options"][] = 'User';
			$assigned_user_id = $current_user->id;
			$user_checked = "checked";
			$team_checked = '';
			$user_style='display:block';
			$team_style='display:none';
		}


		if($fieldlabel == 'Assigned To' && $is_admin==false && $profileGlobalPermission[2] == 1 && ($defaultOrgSharingPermission[getTabid($module)] == 3 or $defaultOrgSharingPermission[getTabid($module)] == 0))
		{
			$users_combo = get_select_options_array(get_user_array(FALSE, "Active", $assigned_user_id,'private'), $assigned_user_id);
		}
		else
		{
			$users_combo = get_select_options_array(get_user_array(FALSE, "Active", $assigned_user_id), $assigned_user_id);
		}

		if($fieldlabel == 'Assigned To' && $is_admin==false && $profileGlobalPermission[2] == 1 && ($defaultOrgSharingPermission[getTabid($module)] == 3 or $defaultOrgSharingPermission[getTabid($module)] == 0))
		{
			$groups_combo = get_select_options_array(get_group_array(FALSE, "Active", $assigned_group_id,'private'), $assigned_group_id);	//crmv@31171
		}
		else
		{
			$groups_combo = get_select_options_array(get_group_array(FALSE, "Active", $assigned_group_id), $assigned_group_id);	//crmv@31171
		}

		$label_fld ["options"][] = $users_combo;
		$label_fld ["options"][] = $groups_combo;
	}
	// crmv@112606e
	elseif($uitype == 55 || $uitype == 255) {
		// crmv@127567
		$label_fld[] =getTranslatedString($fieldlabel, $module);
		$value = $col_fields[$fieldname];
		// crmv@127567e
		
		$value = $col_fields[$fieldname];
		if($uitype==255)
		{
			global $currentModule;
			$fieldpermission = getFieldVisibilityPermission($currentModule, $current_user->id,'firstname');
		}
		if($uitype == 255 && $fieldpermission == 0 && $fieldpermission != '')
		{
			$fieldvalue[] = '';
		}
		else
		{
			$roleid=$current_user->roleid;
			$subrole = getRoleSubordinates($roleid);
			if(count($subrole)> 0)
			{
				$roleids = implode("','",$subrole);
				$roleids = $roleids."','".$roleid;
			}
			else
			{
				$roleids = $roleid;
			}
			if($is_admin == true || $profileGlobalPermission[1] == 0 || $profileGlobalPermission[2] == 0)
			{
				$pick_query="select salutationtype from ".$table_prefix."_salutationtype order by salutationtype";
				$params = array();
			}
			else
			{
				$pick_query="select * from ".$table_prefix."_salutationtype left join ".$table_prefix."_role2picklist on ".$table_prefix."_role2picklist.picklistvalueid=".$table_prefix."_salutationtype.picklist_valueid where picklistid in (select picklistid from ".$table_prefix."_picklist where name='salutationtype') and roleid=? order by salutationtype";
				$params = array($current_user->roleid);
			}
			$pickListResult = $adb->pquery($pick_query, $params);
			$noofpickrows = $adb->num_rows($pickListResult);
			$sal_value = $col_fields["salutationtype"];
			$salcount =0;
			for($j = 0; $j < $noofpickrows; $j++)
			{
				$pickListValue=$adb->query_result($pickListResult,$j,"salutationtype");

				if($sal_value == $pickListValue)
				{
					$chk_val = "selected";
					$salcount++;
				}
				else
				{
					$chk_val = '';
				}
			}
			if($salcount == 0 && $sal_value != '')
			{
				$notacc =  $app_strings['LBL_NOT_ACCESSIBLE'];
			}
           		$sal_value = $col_fields["salutationtype"];
           		if($sal_value == '--None--')
           		{
                   		$sal_value='';
	   		}
	   		$label_fld["salut"] = getTranslatedString($sal_value);
           		$label_fld["notaccess"] = $notacc;
	   	}
		$label_fld[] = $value;
        }
	elseif($uitype == 56)
	{
		$label_fld[] =getTranslatedString($fieldlabel, $module);
		$value = $col_fields[$fieldname];
		if($value == 1)
		{
			//Since "yes" is not been translated it is given as app strings here..
			$display_val = $app_strings['yes'];
		}
		else
		{
			$display_val = $app_strings['no'];
		}
		$label_fld[] = $display_val;
	}
	elseif($uitype == 61)
	{
		global $adb;
		$label_fld[] =getTranslatedString($fieldlabel, $module);
		if($tabid ==10 || getTabname($tabid) == 'Fax') //Emails and Fax
		{
			$attach_result = $adb->pquery("select * from ".$table_prefix."_seattachmentsrel where crmid = ?", array($col_fields['record_id']));
			for($ii=0;$ii < $adb->num_rows($attach_result);$ii++)
			{
				$attachmentid = $adb->query_result($attach_result,$ii,'attachmentsid');
				if($attachmentid != '')
				{
					$attachquery = "select * from ".$table_prefix."_attachments where attachmentsid=?";
					$attachmentsname = $adb->query_result($adb->pquery($attachquery, array($attachmentid)),0,'name');
					//crmv@24153
					$res_attach = $adb->pquery($attachquery, array($attachmentid));
					$attachmentstype = $adb->query_result($res_attach,0,'type');
					$attachmentslink = $adb->query_result($res_attach,0,'path');
					if ($attachmentstype == 'link')  {
						$custfldval = '<a href = "'.$attachmentslink.'">'.$attachmentsname.'</a>';
					} else {
						if($attachmentsname != '')
							$custfldval = '<a href = "index.php?module=uploads&action=downloadfile&return_module='.$col_fields['record_module'].'&fileid='.$attachmentid.'&entityid='.$col_fields['record_id'].'">'.$attachmentsname.'</a>';
						else
							$custfldval = '';
					}
					//crmv@24153e
				}
				$label_fld['options'][] = $custfldval;
			}
		} else {
			$attachmentid=$adb->query_result($adb->pquery("select * from ".$table_prefix."_seattachmentsrel where crmid = ?", array($col_fields['record_id'])),0,'attachmentsid');
			if($col_fields[$fieldname] == '' && $attachmentid != '')
			{
				$attachquery = "select * from ".$table_prefix."_attachments where attachmentsid=?";
				$col_fields[$fieldname] = $adb->query_result($adb->pquery($attachquery, array($attachmentid)),0,'name');
			}

			//This is added to strip the crmid and _ from the file name and show the original filename
			//$org_filename = ltrim($col_fields[$fieldname],$col_fields['record_id'].'_');
			/*Above line is not required as the filename in the database is stored as it is and doesn't have crmid attached to it.
			This was the cause for the issue reported in ticket #4645 */
			$org_filename = $col_fields[$fieldname];
        	// For Backward Compatibility version < 5.0.4
        	$filename_pos = strpos($org_filename, $col_fields['record_id'].'_');
       		if ($filename_pos === 0) {
				$start_idx = $filename_pos+strlen($col_fields['record_id'].'_');
				$org_filename = substr($org_filename, $start_idx);
            }
			if($org_filename != '') {
				if($col_fields['filelocationtype'] == 'E' ){
					if($col_fields['filestatus'] == 1 ){
					$custfldval = '<a target="_blank" href ='.$col_fields['filename'].' onclick=\'javascript:dldCntIncrease('.$col_fields['record_id'].');\'>'.$col_fields[$fieldname].'</a>';
					}
					else{
						$custfldval = $col_fields[$fieldname];
					}
				}elseif($col_fields['filelocationtype'] == 'I') {
					if($col_fields['filestatus'] == 1){
					$custfldval = '<a href = "index.php?module=uploads&action=downloadfile&return_module='.$col_fields['record_module'].'&fileid='.$attachmentid.'&entityid='.$col_fields['record_id'].'" onclick=\'javascript:dldCntIncrease('.$col_fields['record_id'].');\'>'.$col_fields[$fieldname].'</a>';
					}
					else{
						$custfldval = $col_fields[$fieldname];
					}
				} else
					$custfldval = '';
			}
			$label_fld[] =$custfldval;
		}
	}
	elseif($uitype==28){
		$label_fld[] =getTranslatedString($fieldlabel, $module);
		$attachmentid=$adb->query_result($adb->pquery("select * from ".$table_prefix."_seattachmentsrel where crmid = ?", array($col_fields['record_id'])),0,'attachmentsid');
		if($col_fields[$fieldname] == '' && $attachmentid != '')
		{
			$attachquery = "select * from ".$table_prefix."_attachments where attachmentsid=?";
			$col_fields[$fieldname] = $adb->query_result($adb->pquery($attachquery, array($attachmentid)),0,'name');
		}
		$org_filename = $col_fields[$fieldname];
    	// For Backward Compatibility version < 5.0.4
    	$filename_pos = strpos($org_filename, $col_fields['record_id'].'_');
   		if ($filename_pos === 0) {
			$start_idx = $filename_pos+strlen($col_fields['record_id'].'_');
			$org_filename = substr($org_filename, $start_idx);
        }
        if($org_filename != '') {
        	if($col_fields['filelocationtype'] == 'E' ){
        		if($col_fields['filestatus'] == 1 ){//&& strlen($col_fields['filename']) > 7  ){
        			$custfldval = '<a target="_blank" href ='.$col_fields['filename'].' onclick=\'javascript:dldCntIncrease('.$col_fields['record_id'].');\'>'.$col_fields[$fieldname].'</a>';
        		}
        		else{
        			$custfldval = $col_fields[$fieldname];
        		}
        	}elseif($col_fields['filelocationtype'] == 'I' || $col_fields['filelocationtype'] == 'B') { // crmv@95157
				$col_fields[$fieldname] = html_entity_decode($col_fields[$fieldname],ENT_QUOTES,$default_charset); //crmv@131416
        		if($col_fields['filestatus'] == 1){
        			$custfldval = '<a href = "index.php?module=uploads&action=downloadfile&return_module='.$col_fields['record_module'].'&fileid='.$attachmentid.'&entityid='.$col_fields['record_id'].'" onclick=\'javascript:dldCntIncrease('.$col_fields['record_id'].');\'>'.$col_fields[$fieldname].'</a>';
        		}
        		else{
        			$custfldval = $col_fields[$fieldname];
        		}
        	} else
        	$custfldval = '';
        }
        $label_fld[] =$custfldval;
	}
	elseif($uitype == 69)
	{
		$label_fld[] =getTranslatedString($fieldlabel, $module);
		if($tabid==14)
		{
			$images=array();
			$query = 'select productname, '.$table_prefix.'_attachments.path, '.$table_prefix.'_attachments.attachmentsid, '.$table_prefix.'_attachments.name,'.$table_prefix.'_crmentity.setype from '.$table_prefix.'_products left join '.$table_prefix.'_seattachmentsrel on '.$table_prefix.'_seattachmentsrel.crmid='.$table_prefix.'_products.productid inner join '.$table_prefix.'_attachments on '.$table_prefix.'_attachments.attachmentsid='.$table_prefix.'_seattachmentsrel.attachmentsid inner join '.$table_prefix.'_crmentity on '.$table_prefix.'_crmentity.crmid = '.$table_prefix.'_attachments.attachmentsid where '.$table_prefix.'_crmentity.setype=? and productid=?';
			$result_image = $adb->pquery($query, array("Products Image",$col_fields['record_id']));
			$image_array = array(); // crmv@167234
			for($image_iter=0;$image_iter < $adb->num_rows($result_image);$image_iter++)
			{
				$image_id_array[] = $adb->query_result($result_image,$image_iter,'attachmentsid');

				//decode_html  - added to handle UTF-8   characters in file names
				//urlencode    - added to handle special characters like #, %, etc.,
				$image_array[] = urlencode(decode_html($adb->query_result($result_image,$image_iter,'name')));
				$image_orgname_array[] = decode_html($adb->query_result($result_image,$image_iter,'name'));

				$imagepath_array[] = $adb->query_result($result_image,$image_iter,'path');
			}
			if(count($image_array)>1)
			{
				if(count($image_array) < 4)
					$sides=count($image_array)*2;
				else
					$sides=8;

				$image_lists = '<div id="Carousel" style="position:relative;vertical-align: middle;">
					<img src="modules/Products/placeholder.gif" width="571" height="117" style="position:relative;">
					</div><script>var Car_NoOfSides='.$sides.'; Car_Image_Sources=new Array(';

				for($image_iter=0;$image_iter < count($image_array);$image_iter++)
				{
					$images[]='"'.$imagepath_array[$image_iter].$image_id_array[$image_iter]."_".$image_array[$image_iter].'","'.$imagepath_array[$image_iter].$image_id_array[$image_iter]."_".$image_array[$image_iter].'"';
				}
				$image_lists .=implode(',',$images).');</script><script language="JavaScript" type="text/javascript" src="modules/Products/Productsslide.js"></script><script language="JavaScript" type="text/javascript">Carousel();</script>';
				$label_fld[] =$image_lists;
			}elseif(count($image_array)==1)
			{
				list($pro_image_width, $pro_image_height) = getimagesize($imagepath_array[0].$image_id_array[0]."_".$image_orgname_array[0]);
				if($pro_image_width  > 450 ||  $pro_image_height > 300)
					$label_fld[] ='<img src="'.$imagepath_array[0].$image_id_array[0]."_".$image_array[0].'" border="0" width="450" height="300">';
				else
				$label_fld[] ='<img src="'.$imagepath_array[0].$image_id_array[0]."_".$image_array[0].'" border="0" width="'.$pro_image_width.'" height="'.$pro_image_height.'">';
			}else
			{
				$label_fld[] ='';
			}
		}
		if($tabid==4)
		{
			//$imgpath = getModuleFileStoragePath('Contacts').$col_fields[$fieldname];
			$sql = "select ".$table_prefix."_attachments.*,".$table_prefix."_crmentity.setype from ".$table_prefix."_attachments inner join ".$table_prefix."_seattachmentsrel on ".$table_prefix."_seattachmentsrel.attachmentsid = ".$table_prefix."_attachments.attachmentsid inner join ".$table_prefix."_crmentity on ".$table_prefix."_crmentity.crmid = ".$table_prefix."_attachments.attachmentsid where ".$table_prefix."_crmentity.setype='Contacts Image' and ".$table_prefix."_seattachmentsrel.crmid=?";
			$image_res = $adb->pquery($sql, array($col_fields['record_id']));
			$image_id = $adb->query_result($image_res,0,'attachmentsid');
			$image_path = $adb->query_result($image_res,0,'path');

			//decode_html  - added to handle UTF-8   characters in file names
			//urlencode    - added to handle special characters like #, %, etc.,
			$image_name = urlencode(decode_html($adb->query_result($image_res,0,'name')));

			$imgpath = $image_path.$image_id."_".$image_name;
			if($image_name != '')
				$label_fld[] ='<a href="'.$imgpath.'" target="_blank"><img src="'.$imgpath.'" style="max-height:200px" alt="'.$mod_strings['Contact Image'].'" title= "'.$mod_strings['Contact Image'].'" border="0"></a>';
			else
				$label_fld[] = '';
		}
	}
	elseif($uitype == 62)
	{
		$value = $col_fields[$fieldname];
		if($value != '')
		{
			$parent_module = getSalesEntityType($value);
			if($parent_module == "Leads")
			{
				$label_fld[] =$app_strings['LBL_LEAD_NAME'];
				$lead_name = getLeadName($value);

				$label_fld[] ='<a href="index.php?module='.$parent_module.'&action=DetailView&record='.$value.'">'.$lead_name.'</a>';
			}
			elseif($parent_module == "Accounts")
			{
				$label_fld[] = $app_strings['LBL_ACCOUNT_NAME'];
				$sql = "select * from  ".$table_prefix."_account where accountid=?";
				$result = $adb->pquery($sql, array($value));
				$account_name = $adb->query_result($result,0,"accountname");

				$label_fld[] ='<a href="index.php?module='.$parent_module.'&action=DetailView&record='.$value.'">'.$account_name.'</a>';
		}
			elseif($parent_module == "Potentials")
			{
				$label_fld[] =$app_strings['LBL_POTENTIAL_NAME'];
				$sql = "select * from  ".$table_prefix."_potential where potentialid=?";
				$result = $adb->pquery($sql, array($value));
				$potentialname = $adb->query_result($result,0,"potentialname");

				$label_fld[] ='<a href="index.php?module='.$parent_module.'&action=DetailView&record='.$value.'">'.$potentialname.'</a>';
			}
			elseif($parent_module == "Products")
			{
				$label_fld[] =$app_strings['LBL_PRODUCT_NAME'];
				$sql = "select * from  ".$table_prefix."_products where productid=?";
				$result = $adb->pquery($sql, array($value));
				$productname= $adb->query_result($result,0,"productname");

				$label_fld[] ='<a href="index.php?module='.$parent_module.'&action=DetailView&record='.$value.'">'.$productname.'</a>';
			}
			elseif($parent_module == "PurchaseOrder")
			{
				$label_fld[] =$app_strings['LBL_PORDER_NAME'];
				$sql = "select * from  ".$table_prefix."_purchaseorder where purchaseorderid=?";
				$result = $adb->pquery($sql, array($value));
				$pordername= $adb->query_result($result,0,"subject");

				$label_fld[] = '<a href="index.php?module='.$parent_module.'&action=DetailView&record='.$value.'">'.$pordername.'</a>';
			}
			elseif($parent_module == "SalesOrder")
			{
				$label_fld[] = $app_strings['LBL_SORDER_NAME'];
				$sql = "select * from  ".$table_prefix."_salesorder where salesorderid=?";
				$result = $adb->pquery($sql, array($value));
				$sordername= $adb->query_result($result,0,"subject");

				$label_fld[] = '<a href="index.php?module='.$parent_module.'&action=DetailView&record='.$value.'">'.$sordername.'</a>';
			}
			elseif($parent_module == "Invoice")
			{
				$label_fld[] = $app_strings['LBL_INVOICE_NAME'];
				$sql = "select * from  ".$table_prefix."_invoice where invoiceid=?";
				$result = $adb->pquery($sql, array($value));
				$invoicename= $adb->query_result($result,0,"subject");

				$label_fld[] ='<a href="index.php?module='.$parent_module.'&action=DetailView&record='.$value.'">'.$invoicename.'</a>';
			}
			elseif($parent_module == "Quotes")
			{
				$label_fld[] = $app_strings['LBL_QUOTES_NAME'];
				$sql = "select * from  ".$table_prefix."_quotes where quoteid=?";
				$result = $adb->pquery($sql, array($value));
				$quotename= $adb->query_result($result,0,"subject");

				$label_fld[] ='<a href="index.php?module='.$parent_module.'&action=DetailView&record='.$value.'">'.$quotename.'</a>';
			}
			elseif($parent_module == "HelpDesk")
			{
				$label_fld[] = $app_strings['LBL_HELPDESK_NAME'];
				$sql = "select * from  ".$table_prefix."_troubletickets where ticketid=?";
				$result = $adb->pquery($sql, array($value));
				$title= $adb->query_result($result,0,"title");
				$label_fld[] ='<a href="index.php?module='.$parent_module.'&action=DetailView&record='.$value.'">'.$title.'</a>';
			}
		}
		else
		{
			$label_fld[] = getTranslatedString($fieldlabel, $module);
			$label_fld[] = $value;
		}


	}
	elseif($uitype == 105)//Added for user image
	{
		$label_fld[] =getTranslatedString($fieldlabel, $module);
		//$imgpath = getModuleFileStoragePath('Contacts').$col_fields[$fieldname];
		$sql = "select ".$table_prefix."_attachments.* from ".$table_prefix."_attachments left join ".$table_prefix."_salesmanattachmentsrel on ".$table_prefix."_salesmanattachmentsrel.attachmentsid = ".$table_prefix."_attachments.attachmentsid where ".$table_prefix."_salesmanattachmentsrel.smid=?";
		$image_res = $adb->pquery($sql, array($col_fields['record_id']));
		$image_id = $adb->query_result($image_res,0,'attachmentsid');
		$image_path = $adb->query_result($image_res,0,'path');
		$image_name = $adb->query_result($image_res,0,'name');
		$imgpath = $image_path.$image_id."_".$image_name;
		if($image_name != '') {
			//Added the following check for the image to retain its in original size.
			//crmv@29079
			$user_name = getUserFullName($col_fields['record_id']);
			$label_fld[] ='<a href="'.$imgpath.'" target="_blank"><img src="'.$imgpath.'" style="max-height:200px" alt="'.$user_name.'" title="'.$user_name.'" border="0"></a>';
			//crmv@29079e
		} else {
			$label_fld[] = '';
		}
	}
	elseif($uitype == 67)
	{
		$value = $col_fields[$fieldname];
		if($value != '')
		{
			$parent_module = getSalesEntityType($value);
			if($parent_module == "Leads")
			{
				$label_fld[] = $app_strings['LBL_LEAD_NAME'];
				$lead_name = getLeadName($value);
				$label_fld[] = '<a href="index.php?module='.$parent_module.'&action=DetailView&record='.$value.'">'.$lead_name.'</a>';
			}
			elseif($parent_module == "Contacts")
			{
				$label_fld[] = $app_strings['LBL_CONTACT_NAME'];
				$contact_name = getContactName($value);
				$label_fld[] = '<a href="index.php?module='.$parent_module.'&action=DetailView&record='.$value.'">'.$contact_name.'</a>';
			}
		}
		else
		{
			$label_fld[] = getTranslatedString($fieldlabel, $module);
			$label_fld[] = $value;

		}
	}
	//added by raju/rdhital for better emails
	elseif($uitype == 357)
	{
		//crmv@fix crmid
		$value = intval($col_fields[$fieldname]);
		//crmv@fix crmid e
		if($value != '')
		{
			$parent_name='';
			$parent_id='';
			$myemailid= $_REQUEST['record'];
			$mysql = "select crmid from ".$table_prefix."_seactivityrel where activityid=?";
			$myresult = $adb->pquery($mysql, array($myemailid));
			$mycount=$adb->num_rows($myresult);
			if ($mycount>1){
				$label_fld[] = $app_strings['LBL_RELATED_TO'];
				$label_fld[] =$app_strings['LBL_MULTIPLE'];
			}
			else
			{
				$parent_module = getSalesEntityType($value);
				if($parent_module == "Leads")
				{
					$label_fld[] = $app_strings['LBL_LEAD_NAME'];
					$lead_name = getLeadName($value);
					$label_fld[] = '<a href="index.php?module='.$parent_module.'&action=DetailView&record='.$value.'">'.$lead_name.'</a>';
				}
				elseif($parent_module == "Contacts")
				{
					$label_fld[] = $app_strings['LBL_CONTACT_NAME'];
					$contact_name = getContactName($value);
					$label_fld[] = '<a href="index.php?module='.$parent_module.'&action=DetailView&record='.$value.'">'.$contact_name.'</a>';
				}
				elseif($parent_module == "Accounts")
				{
					$label_fld[] = $app_strings['LBL_ACCOUNT_NAME'];
					$sql = "select * from  ".$table_prefix."_account where accountid=?";
					$result = $adb->pquery($sql, array($value));
					$accountname = $adb->query_result($result,0,"accountname");
					$label_fld[] = '<a href="index.php?module='.$parent_module.'&action=DetailView&record='.$value.'">'.$accountname.'</a>';
				}
				elseif($parent_module == "Vendors")
				{
					$label_fld[] = $app_strings['LBL_VENDOR_NAME'];
					$sql = "select * from  ".$table_prefix."_vendor where vendorid=?";
					$result = $adb->pquery($sql, array($value));
					$vendorname = $adb->query_result($result,0,"vendorname");
					$label_fld[] = '<a href="index.php?module='.$parent_module.'&action=DetailView&record='.$value.'">'.$vendorname.'</a>';
				}
			}
		}
		else
		{
			$label_fld[] = getTranslatedString($fieldlabel, $module);
			$label_fld[] = $value;
		}
	}//Code added by raju for better email ends
	elseif($uitype==63)
        {
	   $label_fld[] =getTranslatedString($fieldlabel, $module);
	   $label_fld[] = $col_fields[$fieldname].'h&nbsp; '.$col_fields['duration_minutes'].'m';
        }
	elseif($uitype == 6)
        {
		$label_fld[] =getTranslatedString($fieldlabel, $module);
          	if($col_fields[$fieldname]=='0')
                $col_fields[$fieldname]='';
		if($col_fields['time_start']!='')
                {
                       $start_time = $col_fields['time_start'];
                }
		if($col_fields[$fieldname] == '0000-00-00' || $col_fields[$fieldname] === null)
		{
			$displ_date = '';
		}
		else
		{
			$displ_date = getDisplayDate(substr($col_fields[$fieldname],0,10));
		}
		$label_fld[] = $displ_date.'&nbsp;'.$start_time;
	}
	//crmv@18338
	elseif($uitype == 5 || $uitype == 23 || $uitype == 70 || $uitype == 1021)
	//crmv@18338 end
	{
		$label_fld[] =getTranslatedString($fieldlabel, $module);
		$cur_date_val = $col_fields[$fieldname];
		if($col_fields['time_end']!='' && ($tabid == 9 || $tabid == 16) && $uitype == 23)
		{
			$end_time = $col_fields['time_end'];
		}
		if($cur_date_val == '0000-00-00' || $cur_date_val === null)
		{
			$display_val = '';
		}
		else
		{
            //ds@30
            if($cur_date_val == '') {
				$display_val = '';
			//crmv@2963m
			} elseif ($module == 'Messages' && $fieldname == 'mdate') {
				$focus = CRMEntity::getInstance($module);
				$display_val = $focus->getFullDate($cur_date_val);
			//crmv@2963me
			} else {
            	if ($uitype == 5 || $uitype == 23 )
            		$cur_date_val = substr($cur_date_val,0,10);
    			$display_val = getDisplayDate($cur_date_val);
            }
            //ds@30e
		}
		//crmv@12035
		$field_val = array();
		if ($display_val != '')
			$field_val[] = $display_val;
		if ($end_time != '')
			$field_val[] = $end_time;
		$field_val = implode('&nbsp;',$field_val);
		//crmv@12035e
		//crmv@7214
	    if($fieldname == "expiry_date" && ($tabid == 13 || $tabid == 14)) // 13=HelpDesk, 14=Products
	    {
	        $expiry_date = $col_fields['expiry_date'];
	        if ($tabid == 13){ // HelpDesk
	            $product_id = $col_fields["product_id"];
	            if($product_id != '') {
	                $sql = "select expiry_date from ".$table_prefix."_products where productid=?";
	                $result = $adb->pquery($sql, array($product_id));
	                $expiry_date = $adb->query_result($result,0,"expiry_date");
	            }
	        }
	        $today = date("Y-m-d");
	        if (trim($expiry_date) == ''){

	        }
	        elseif ( $expiry_date >= $today){
	        	$secid = 'ok';
	        	$expiry_date = getDisplayDate(date("Y-m-d",strtotime($expiry_date)));	//crmv@35579
	            $field_val = $expiry_date;
	        }
	        elseif ( $expiry_date < $today){
	        	$expiry_date = getDisplayDate(date("Y-m-d",strtotime($expiry_date)));	//crmv@35579
	            $field_val = $expiry_date;
	            $secid = 'ko';
	        }
	    }
		//crmv@7214e
		$label_fld[] = $field_val;
		$label_fld['secid'] = $secid;
	}
	elseif($uitype == 71 || $uitype == 72)
	{
        $label_fld[] = getTranslatedString($fieldlabel, $module);
		// crmv@92112
        if($fieldname == 'unit_price') {
			$rate_symbol=getCurrencySymbolandCRate($InventoryUtils->getProductBaseCurrency($col_fields['record_id'], $module)); //crmv@42024
		} else {
			$rate_symbol=getCurrencySymbolandCRate($user_info['currency_id']);
		}
        if ($col_fields[$fieldname] !== '' && $col_fields[$fieldname] !== null) {
			if($fieldname == 'unit_price') {
				$label_fld[]  = formatUserNumber(floatval($col_fields[$fieldname])); //crmv@92824
			} else {
				$rate = $rate_symbol['rate'];
				$value = convertFromMasterCurrency($col_fields[$fieldname],$rate); //crmv@92519
				$label_fld[] = formatUserNumber(floatval($value)); // crmv@83877
			}
		} else {
			$label_fld[] = '';
		}
		// crmv@92112e
        $currency = $rate_symbol['symbol'];
        $label_fld["cursymb"] = $currency;
	// crmv@83877 crmv@92112
	} elseif($uitype == 7 || $uitype == 9) {
		$label_fld[] = getTranslatedString($fieldlabel, $module);
		if ($col_fields[$fieldname] !== '' && $col_fields[$fieldname] !== null) {
			$label_fld[] = formatUserNumber(floatval($col_fields[$fieldname]), true);
		} else {
			$label_fld[] = '';
		}
	// crmv@83877e crmv@92112e
	} elseif($uitype == 79)
	{
		$label_fld[] =getTranslatedString($fieldlabel, $module);
		$purchaseorder_id = $col_fields[$fieldname];
		if($purchaseorder_id != '')
		{
			$purchaseorder_name = getPoName($purchaseorder_id);
		}
		$label_fld[] = $purchaseorder_name;
		$label_fld["secid"] = $purchaseorder_id;
		$label_fld["link"] = "index.php?module=PurchaseOrder&action=DetailView&record=".$purchaseorder_id;
	}
	elseif($uitype == 30)
	{
		$rem_days = 0;
		$rem_hrs = 0;
		$rem_min = 0;
		$reminder_str ="";
		$rem_days = floor($col_fields[$fieldname]/(24*60));
		$rem_hrs = floor(($col_fields[$fieldname]-$rem_days*24*60)/60);
		$rem_min = ($col_fields[$fieldname]-$rem_days*24*60)%60;

		$label_fld[] =getTranslatedString($fieldlabel, $module);
		if($col_fields[$fieldname])
                {
                        $reminder_str= $rem_days.'&nbsp;'.$mod_strings['LBL_DAYS'].'&nbsp;'.$rem_hrs.'&nbsp;'.$mod_strings['LBL_HOURS'].'&nbsp;'.$rem_min.'&nbsp;'.$mod_strings['LBL_MINUTES'].'&nbsp;&nbsp;'.$mod_strings['LBL_BEFORE_EVENT'];
                }
		$label_fld[] = '&nbsp;'.$reminder_str;
	}elseif($uitype == 98)
	{
	 	$label_fld[] =getTranslatedString($fieldlabel, $module);
		if(is_admin($current_user))
			$label_fld[] = '<a href="index.php?module=Settings&action=RoleDetailView&roleid='.$col_fields[$fieldname].'">'.getRoleName($col_fields[$fieldname]).'</a>';
		else
			$label_fld[] = getRoleName($col_fields[$fieldname]);
	}elseif($uitype == 85) //Added for Skype by Minnie
	{
		$label_fld[] =getTranslatedString($fieldlabel, $module);
		$label_fld[]= $col_fields[$fieldname];
	}
	//vtc
	elseif($uitype == 26){
		 $label_fld[] =getTranslatedString($fieldlabel, $module);
		 // crmv@30967
		 $query = "select foldername from ".$table_prefix."_crmentityfolder where tabid = ? and folderid = ?";
		 $result = $adb->pquery($query, array(getTabId($module), $col_fields[$fieldname]));
		 // crmv@30967e
		 $folder_name = $adb->query_result($result,0,"foldername");
		 $label_fld[] = $folder_name;
	}
	elseif($uitype == 27){
		// crmv@95157
		if($col_fields[$fieldname] == 'I' || $col_fields[$fieldname] == 'B'){
			$label_fld[]=getTranslatedString($fieldlabel);
			$label_fld[]= $mod_strings['LBL_INTERNAL'];
		} elseif($col_fields[$fieldname] == 'E') {
			$label_fld[]=getTranslatedString($fieldlabel);
			$label_fld[]= $mod_strings['LBL_EXTERNAL'];
		}
		// crmv@95157e
	}
	//crmv@16265	crmv@43764
	elseif($uitype == 199){
		(!empty($col_fields[$fieldname])) ? $fakeValue = '********' : $fakeValue = '';
		$label_fld[] = getTranslatedString($fieldlabel, $module);
		$label_fld[] = $fakeValue;
		//$label_fld['options'] = $col_fields[$fieldname];	// real value
	}
	//crmv@16265e	crmv@43764e
	//crmv@18338
	elseif($uitype == 1020){
		$temp_val = $col_fields[$fieldname];
		$label_fld[] = getTranslatedString($fieldlabel, $module);
		$label_fld[] = $temp_val;
		//crmv@46872 @TODO:optimize sla config
		if ($fieldname == 'sla_time'){
			$sla_obj = CRMEntity::getInstance('SLA');
			$sla_config_global = $sla_obj->get_config();
			if ((count($sla_config_global)>0 && in_array($module,array_keys($sla_config_global)))){
				switch ($sla_config_global[$module]['time_measure']){
					case 'minutes':
						$temp_val = $temp_val*60;
						break;
					case 'hours':
						$temp_val = $temp_val*3600;
						break;
					case 'days':
						$temp_val = $temp_val*86400;
						break;
				}
			}
		}
		//crmv@46872 e		
		$value=time_duration(abs($temp_val));
		if (strpos($fieldname,"remaining")!==false || strpos($fieldname,"_out_")!==false){
			if (strpos($fieldname,"remaining")!==false){
				if ($temp_val<=0)
					$color = "red";
				else
					$color = "green";
			}
			if (strpos($fieldname,"_out_")!==false){
				if ($temp_val>0)
					$color = "red";
				else
					$color = "green";
			}
			$value = "<font color=$color>$value</font>";
		}
		$label_fld['options'] = $value;
	}
	//crmv@18338 end
	else{
		$label_fld[] =getTranslatedString($fieldlabel, $module);
		// crmv@59470 - show the zero value in generic fields
		/*if ($col_fields[$fieldname]=='0' && $fieldname != 'filedownloadcount' && $fieldname != 'filestatus' && $fieldname != 'filesize') {
			$col_fields[$fieldname]='';
		}*/
		// crmv@59470e
		if ($uitype == 1 && ($fieldname=='expectedrevenue' || $fieldname=='budgetcost' || $fieldname=='actualcost' || $fieldname=='expectedroi' || $fieldname=='actualroi' )) {
			$rate_symbol=getCurrencySymbolandCRate($user_info['currency_id']);
			$label_fld[] = convertFromDollar($col_fields[$fieldname],$rate_symbol['rate']);
		} else {
			//code for Documents module :start
			if($tabid == 8)
			{
				$downloadtype = $col_fields['filelocationtype'];
				if($fieldname == 'filename')
				{
					if($downloadtype == 'I' || $downloadtype == 'B') // crmv@95157
					{
						//$file_value = $mod_strings['LBL_INTERNAL'];
						$fld_value = $col_fields['filename'];
						$ext_pos = strrpos($fld_value, ".");
						$ext =substr($fld_value, $ext_pos + 1);
						$ext = strtolower($ext);
						if($ext == 'bin' || $ext == 'exe' || $ext == 'rpm')
							$fileicon="<img src='" . resourcever('fExeBin.gif') . "' hspace='3' align='absmiddle' border='0'>";
						elseif($ext == 'jpg' || $ext == 'gif' || $ext == 'bmp')
							$fileicon="<img src='" . resourcever('fbImageFile.gif') . "' hspace='3' align='absmiddle' border='0'>";
						elseif($ext == 'txt' || $ext == 'doc' || $ext == 'xls')
							$fileicon="<img src='" . resourcever('fbTextFile.gif') . "' hspace='3' align='absmiddle' border='0'>";
						elseif($ext == 'zip' || $ext == 'gz' || $ext == 'rar')
							$fileicon="<img src='" . resourcever('fbZipFile.gif') . "' hspace='3' align='absmiddle'	border='0'>";
						else
							$fileicon="<img src='" . resourcever('fbUnknownFile.gif') . "' hspace='3' align='absmiddle' border='0'>";
					}
					else
					{
						$fld_value = $col_fields['filename'];
						$fileicon = "<img src='" . resourcever('fbLink.gif') . "' alt='".$mod_strings['LBL_EXTERNAL_LNK']."' title='".$mod_strings['LBL_EXTERNAL_LNK']."' hspace='3' align='absmiddle' border='0'>";
					}
					$col_fields[$fieldname] = $fileicon.$fld_value;
				}
				if($fieldname == 'filesize')
				{
					if($col_fields['filelocationtype'] == 'I' || $col_fields['filelocationtype'] == 'B') // crmv@95157z
					{
						$filesize = $col_fields[$fieldname];
						if($filesize < 1024)
							$col_fields[$fieldname]=$filesize.' B';
						elseif($filesize > 1024 && $filesize < 1048576)
							$col_fields[$fieldname]=round($filesize/1024,2).' KB';
						else if($filesize > 1048576)
							$col_fields[$fieldname]=round($filesize/(1024*1024),2).' MB';
					}
					else
					{
						$col_fields[$fieldname]=' --';
					}
				}
				if($fieldname == 'filetype' && $col_fields['filelocationtype'] == 'E')
				{
					$col_fields[$fieldname]=' --';
				}
				/*if($fieldname == 'filestatus')
				{
					$filestatus = $col_fields[$fieldname];
					if($filestatus == 0)
						$col_fields[$fieldname]=$mod_strings['LBL_ACTIVE'];
					else
						$col_fields[$fieldname]=$mod_strings['LBL_INACTIVE'];
				}*/
			}
			//code for Documents module :end
			$label_fld[] = $col_fields[$fieldname];
		}
	}
	$label_fld[]=$uitype;

	//sets whether the currenct user is admin or not
	if(is_admin($current_user))
	{
	    $label_fld["isadmin"] = 1;
	}else
	{
	   $label_fld["isadmin"] = 0;
	}
	$log->debug("Exiting getDetailViewOutputHtml method ...");
	return $label_fld;
}

/** This function returns the related vte_tab details for a given entity or a module.
* Param $module - module name
* Param $focus - module object
* Return type is an array
*/

function getRelatedListsInformation($module,$focus)
{
	global $log;
	$log->debug("Entering getRelatedLists(".$module.",focus) method ...");
	global $adb,$table_prefix;
	global $current_user;
	require('user_privileges/requireUserPrivileges.php'); // crmv@39110

	$cur_tab_id = getTabid($module);

	//$sql1 = "select * from vte_relatedlists where tabid=? order by sequence";
	// vtlib customization: Do not picklist module which are set as in-active
	$sql1 = "select * from ".$table_prefix."_relatedlists where tabid=? and related_tabid not in (SELECT tabid FROM ".$table_prefix."_tab WHERE presence = 1) order by sequence";
	// END
	$result = $adb->pquery($sql1, array($cur_tab_id));
	$num_row = $adb->num_rows($result);
	for($i=0; $i<$num_row; $i++) {
		$rel_tab_id = $adb->query_result($result,$i,"related_tabid");
		$function_name = $adb->query_result($result,$i,"name");
		$label = $adb->query_result($result,$i,"label");
		$actions = $adb->query_result($result,$i,"actions");
		$relationId = $adb->query_result($result,$i,"relation_id");
		if($rel_tab_id != 0) {
			if($profileTabsPermission[$rel_tab_id] == 0) {
		        	if($profileActionPermission[$rel_tab_id][3] == 0) {
						// vtlib customization: Send more information (from module, related module)
						// to the callee
						$focus_list[$label] = $focus->$function_name($focus->id, $cur_tab_id,
								$rel_tab_id, $actions);
						// END
        			}
			}
		} else {
			// vtlib customization: Send more information (from module, related module)
			// to the callee
			$focus_list[$label] = $focus->$function_name($focus->id, $cur_tab_id, $rel_tab_id,
					$actions);
			// END
		}
	}
	$log->debug("Exiting getRelatedLists method ...");
	return $focus_list;
}

/** This function returns the related vte_tab details for a given entity or a module.
* Param $module - module name
* Param $focus - module object
* Return type is an array
*/

function getRelatedLists($module,$focus,$turbolift=false)	//crmv@26896
{
	global $log;
	$log->debug("Entering getRelatedLists(".$module.") method ...");
	global $adb,$table_prefix;
	global $current_user;
	require('user_privileges/requireUserPrivileges.php'); // crmv@39110

	$cur_tab_id = getTabid($module);

	//$sql1 = "select * from vte_relatedlists where tabid=? order by sequence";
	// vtlib customization: Do not picklist module which are set as in-active
	//crmv@26896 crmv@39110 crmv@150751
	$firstUserProfile = $current_user_profiles[0];
	
	$vh_info = array(
		'relatedlists' => array($table_prefix.'_relatedlists',"{$table_prefix}_relatedlists.tabid=?",$cur_tab_id),
		'tab' => array($table_prefix.'_tab',"{$table_prefix}_tab.tabid = {$table_prefix}_relatedlists.related_tabid"),
	);
	require_once('modules/Settings/ProcessMaker/ProcessMakerUtils.php');
	$PMUtils = ProcessMakerUtils::getInstance();
	$tvh_id = $PMUtils->getSystemVersion4Record($focus->id,array('tabs',$module,'id'));
	if (!empty($tvh_id)) {
		$vh_info['relatedlists'] = array($table_prefix.'_relatedlists_vh',"{$table_prefix}_relatedlists_vh.tabid=? and {$table_prefix}_relatedlists_vh.versionid=?",array($cur_tab_id,$tvh_id));
		$vh_info['tab'] = array($table_prefix.'_tab',"{$table_prefix}_tab.tabid = {$table_prefix}_relatedlists_vh.related_tabid"); //crmv@150751
	}
	$vh_info['profile2related'] = array($table_prefix.'_profile2related', "p2r.profileid = ? AND p2r.tabid = ? AND p2r.relationid = {$vh_info['relatedlists'][0]}.relation_id");
	
	$pvh_id = $PMUtils->getSystemVersion4Record($focus->id,array('profiles','id'));
	if (!empty($pvh_id)) {
		$vh_info['profile2related'] = array($table_prefix.'_profile2related_vh', $vh_info['profile2related'][1]." and p2r.versionid = {$pvh_id}");
	}
	
	if ($turbolift) {
		$condition1 = '';
		if ($cur_tab_id == 9) {
			$condition1 = "and {$vh_info['relatedlists'][0]}.related_tabid not in (0,4)";
		}
		// use first profile to get only one row, no sense to mix them all
		$sql1 = "select {$vh_info['relatedlists'][0]}.*, p2r.visible as profile_visible, p2r.actions as profile_actions, {$vh_info['tab'][0]}.name as related_tabname
			FROM {$vh_info['relatedlists'][0]}
			INNER JOIN {$vh_info['tab'][0]} ON {$vh_info['tab'][1]}
			LEFT JOIN vte_turbolift_count on (vte_turbolift_count.relation_id = {$vh_info['relatedlists'][0]}.relation_id and vte_turbolift_count.userid = $current_user->id)
			LEFT JOIN {$vh_info['profile2related'][0]} p2r ON {$vh_info['profile2related'][1]}
			WHERE {$vh_info['relatedlists'][1]} $condition1
			AND {$vh_info['tab'][0]}.presence != 1 AND {$vh_info['relatedlists'][0]}.presence != 1
			AND (p2r.visible IS NULL OR p2r.visible = 1)
			ORDER BY COALESCE(p2r.sequence, 0) ASC, coalesce(tb_count,0) DESC, {$vh_info['relatedlists'][0]}.sequence ASC";
	} else {
		$sql1 = "select {$vh_info['relatedlists'][0]}.*, p2r.visible as profile_visible, p2r.actions as profile_actions, {$vh_info['tab'][0]}.name as related_tabname
			FROM {$vh_info['relatedlists'][0]}
			LEFT JOIN {$vh_info['tab'][0]} ON {$vh_info['tab'][1]}
			LEFT JOIN {$vh_info['profile2related'][0]} p2r ON {$vh_info['profile2related'][1]}
			WHERE {$vh_info['relatedlists'][1]}
			AND ({$vh_info['tab'][0]}.tabid IS NULL OR {$vh_info['tab'][0]}.presence != 1) AND {$vh_info['relatedlists'][0]}.presence != 1
			AND (p2r.visible IS NULL OR p2r.visible = 1)
			ORDER BY COALESCE(p2r.sequence, {$vh_info['relatedlists'][0]}.sequence)";
	}
	$params = array($firstUserProfile, $cur_tab_id, $vh_info['relatedlists'][2]);
	//crmv@26896e crmv@39110e crmv@150751e
	$result = $adb->pquery($sql1, $params);

	$num_row = $adb->num_rows($result);
	for($i=0; $i<$num_row; $i++) {
		$rel_tab_id = $adb->query_result($result,$i,"related_tabid");
		$related_tabname = $adb->query_result($result,$i,"related_tabname");
		$function_name = $adb->query_result($result,$i,"name");
		$label = $adb->query_result($result,$i,"label");
		$actions = $adb->query_result($result,$i,"actions");
		$relationId = $adb->query_result($result,$i,"relation_id");
		$presence = $adb->query_result($result,$i,"presence"); //crmv@170167
		if($rel_tab_id != 0) {
			if($profileTabsPermission[$rel_tab_id] == 0) {
		        	if($profileActionPermission[$rel_tab_id][3] == 0) {
						// vtlib customization: Send more information (from module, related module)
						// to the callee
						$focus_list[$label] = array(
							'related_tabid' => $rel_tab_id,
							'relationId' => $relationId,
							'actions' => $actions,
							'name'=>$function_name,	// crmv@33097
							'related_tabname'=>$related_tabname,
							'presence'=>$presence, //crmv@170167
						);
						// END
        			}
			}
		} else {
			// vtlib customization: Send more information (from module, related module)
			// to the callee
			$focus_list[$label] = array(
				'related_tabid' => $rel_tab_id,
				'relationId' => $relationId,
				'actions' => $actions,
				'name'=>$function_name,	// crmv@33097
				'related_tabname'=>$related_tabname,
				'presence'=>$presence, //crmv@170167
			);
			// END
		}
	}
	$log->debug("Exiting getRelatedLists method ...");
	return $focus_list;
}

/** This function returns whether related lists is present for this particular module or not
* Param $module - module name
* Param $activity_mode - mode of activity
* Return type true or false
*/


function isPresentRelatedLists($module,$activity_mode='') {
	static $moduleRelatedListCache = array();

	global $adb,$current_user,$table_prefix;
	$retval=array();
	/* crmv@140903 removed tabdata include */
	require('user_privileges/requireUserPrivileges.php'); // crmv@39110
	$tab_id=getTabid($module);
	// We need to check if there is atleast 1 relation, no need to use count(*)
	$query= "select relation_id,related_tabid, label from ".$table_prefix."_relatedlists where tabid=? ".
	"order by sequence";
	$result=$adb->pquery($query, array($tab_id));
	$count=$adb->num_rows($result);
	if($count < 1 || ($module =='Calendar' && $activity_mode=='task')) {
		$retval='false';
	}else if(empty($moduleRelatedListCache[$module])){
		$tab_seq_array = TabdataCache::get('tab_seq_array'); //crmv@140903
		for($i=0; $i<$count; ++$i) {
			$relatedId = $adb->query_result($result, $i, 'relation_id');
			$relationLabel = $adb->query_result($result, $i, 'label');
			$relatedTabId = $adb->query_result($result, $i, 'related_tabid');
			//check for module disable.
			$permitted = $tab_seq_array[$relatedTabId];
			if($permitted === 0 || $permitted === '0' || empty($relatedTabId)){ // crmv@140903
				if($is_admin || $profileTabsPermission[$relatedTabId] === 0 || empty($relatedTabId)){
					$retval[$relatedId] = $relationLabel;
				}
			}
		}
		$moduleRelatedListCache[$module] = $retval;
	}
	return $moduleRelatedListCache[$module];
}

/** This function returns the detailed block information of a record in a module.
* Param $module - module name
* Param $block - block id
* Param $col_fields - column vte_fields array for the module
* Param $tabid - vte_tab id
* Return type is an array
*/
function getDetailBlockInformation($view,$module,$result,$col_fields,$tabid,$blockdata,&$aBlockStatus='')	//crmv@96450 crmv@104568
{
	global $log;
	$log->debug("Entering getDetailBlockInformation(".$module.",". $result.",".$col_fields.",".$tabid.",".$blockdata.") method ..."); // crmv@104568
	global $adb;
	global $current_user;
	global $mod_strings;
	$label_data = Array();

	$noofrows = $adb->num_rows($result);
	//crmv@9434+31357
	if (vtlib_isModuleActive('Transitions')){
		$transitions_obj = CRMEntity::getInstance('Transitions');
		$transitions_obj->Initialize($module,$current_user->roleid);
	}
	//crmv@9434+31357 end
	//crmv@9433
	if (vtlib_isModuleActive('Conditionals')){
		//crmv@36505
		$conditionals_obj = CRMEntity::getInstance('Conditionals');
		$conditionals_obj->Initialize($module,$tabid,$col_fields);
		// crmv@198388: removed crmv@142262
		//crmv@36505e
	}
	//crmv@9433 end
	for($i=0; $i<$noofrows; $i++)
	{
		$fieldtablename = $adb->query_result($result,$i,"tablename");
		$fieldcolname = $adb->query_result($result,$i,"columnname");
		$uitype = $adb->query_result($result,$i,"uitype");
		$fieldname = $adb->query_result($result,$i,"fieldname");
		$fieldid = $adb->query_result($result,$i,"fieldid");
		$fieldlabel = $adb->query_result($result,$i,"fieldlabel");
		$maxlength = $adb->query_result($result,$i,"maximumlength");
		$block = $adb->query_result($result,$i,"block");
		$generatedtype = $adb->query_result($result,$i,"generatedtype");
		$tabid = $adb->query_result($result,$i,"tabid");
		$displaytype = $adb->query_result($result,$i,'displaytype');
		$readonly = $adb->query_result($result,$i,"readonly");
		//crmv@3085m
		$quickcreate = $adb->query_result($result,$i,"quickcreate");
		$typeofdata = getFinalTypeOfData($adb->query_result($result,$i,"typeofdata"), $adb->query_result($result,$i,"mandatory"));	//crmv@49510
		$type_of_data  = explode('~',$typeofdata);
		if ($type_of_data[1] == 'M') $mandatory = true; else $mandatory = false;
		//crmv@3085me
		//crmv@9434
		if (vtlib_isModuleActive('Transitions'))
			$transitions_obj->handle_managed_fields($fieldname,$fieldcolname,$readonly,$col_fields,$mode,'DetailView');
		//crmv@9434 end
		//crmv@9433
		if (vtlib_isModuleActive('Conditionals')){
			$fieldid = $adb->query_result($result,$i,"fieldid");
			if (is_array($conditionals_obj->permissions[$fieldid])){
				if ($conditionals_obj->permissions[$fieldid]["f2fp_visible"] == 0)
					$readonly = 100;
				elseif ($conditionals_obj->permissions[$fieldid]["f2fp_editable"] == 0)
					$readonly = 99;
			}
			// crmv@198388: removed crmv@142262, crmv@181275
		}
		//crmv@9433 e
		//crmv@sdk-18508
		$sdk_files = SDK::getViews($module,'detail');
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
		//crmv@sdk-18508 e
		//crmv@3085m	crmv@45034
		if ($view == 'summary') {
			$wbsFldObj = WebserviceField::fromQueryResult($adb,$result,$i);
			if (!in_array($quickcreate,array(0,2))) {
				if (!$wbsFldObj->showInSummary()) {
//					if ($wbsFldObj->isExcludedBySummary() || $wbsFldObj->isEmpty($col_fields[$fieldname])) {
						$readonly = 100;
//					}
				}
			}
		}
		//crmv@3085me	crmv@45034e
		$custfld = getDetailViewOutputHtml($uitype, $fieldname, $fieldlabel, $col_fields,$generatedtype,$tabid,$module);
		if(is_array($custfld))
		{
			//crmv@3085m
			if ($view == 'summary') {
				$label_data[] = array($custfld[0]=>array("value"=>$custfld[1],"ui"=>$custfld[2],"options"=>$custfld["options"],"secid"=>$custfld["secid"],"link"=>$custfld["link"],"cursymb"=>$custfld["cursymb"],"salut"=>$custfld["salut"],"notaccess"=>$custfld["notaccess"],"isadmin"=>$custfld["isadmin"],"tablename"=>$fieldtablename,"fldname"=>$fieldname,"fldid"=>$fieldid,"displaytype"=>$displaytype,"readonly"=>$readonly,'mandatory'=>$mandatory));
			} else {
				$label_data[$block][] = array($custfld[0]=>array("value"=>$custfld[1],"ui"=>$custfld[2],"options"=>$custfld["options"],"secid"=>$custfld["secid"],"link"=>$custfld["link"],"cursymb"=>$custfld["cursymb"],"salut"=>$custfld["salut"],"notaccess"=>$custfld["notaccess"],"isadmin"=>$custfld["isadmin"],"tablename"=>$fieldtablename,"fldname"=>$fieldname,"fldid"=>$fieldid,"displaytype"=>$displaytype,"readonly"=>$readonly,'mandatory'=>$mandatory));
			}
			//crmv@3085me
		}
	}
	//crmv@96450
	if ($module == 'Processes' && $view != 'summary') {
		require_once('modules/Settings/ProcessMaker/ProcessDynaForm.php');
		$processDynaFormObj = ProcessDynaForm::getInstance();
		$processDynaFormObj->addBlockInformation($col_fields,'detail',$label_data,$blockdata,$aBlockStatus,array(),true); // crmv@104568 crmv@106857
	}
	//crmv@96450e
	
	// crmv@198024
	if ($module == 'Products' && $view != 'summary') {
		$prodFocus = CRMEntity::getInstance('Products');
		$prodFocus->addAttributesBlock($col_fields, 'detail', $label_data, $blockdata, $aBlockStatus);
	}
	// crmv@198024e
	
	//crmv@3085m
	if ($view == 'summary') {
		return $label_data;
	}
	//crmv@3085me
	//crmv@99316
	foreach($label_data as $headerid=>$value_array) {
		foreach($value_array as $i => $arr) {
			$keys = array_keys($arr);
			$key = $keys[0];
			if ($arr[$key]['readonly'] == 100) unset($value_array[$i]);	// skip field
		}
		if (empty($value_array)) unset($label_data[$headerid]);	// skip block if empty
	}
	//crmv@99316e
	foreach($label_data as $headerid=>$value_array)
	{
		$detailview_data = Array();
		for ($i=0,$j=0;$i<count($value_array);$j++)
		{
			$key2 = null;
			$keys=array_keys($value_array[$i]);
			$key1=$keys[0];
			if(is_array($value_array[$i+1]) && ($value_array[$i][$key1]['ui']!=19 && $value_array[$i][$key1]['ui']!=20)) //crmv@167234
			{
				$keys=array_keys($value_array[$i+1]);
				$key2=$keys[0];
			}
			// Added to avoid the unique keys
			$use_key1 = $key1;
			if($key1 == $key2) {
				$use_key1 = " " . $key1;
			}

			if($value_array[$i][$key1]['ui']!=19 && $value_array[$i][$key1]['ui']!=20){ // crmv@167234
				$detailview_data[$j]=array($use_key1 => $value_array[$i][$key1],$key2 => $value_array[$i+1][$key2]);
				$i+=2;
			}else{
				$detailview_data[$j]=array($use_key1 => $value_array[$i][$key1]);
				$i++;
			}
		}
		$label_data[$headerid] = $detailview_data;
	}
	// crmv@104568
	$returndata = array();
	foreach($blockdata as $blockid=>$blockinfo) {
		$label = $blockinfo['label'];
		if ($label != '') {
			$curBlock = $label;
		}
		$blocklabel = getTranslatedString($curBlock,$module);
		$key = $blocklabel;
		if(is_array($label_data[$blockid])) {
			if (!is_array($returndata[$key])) {
				$returndata[$key] = array(
					'blockid' => $blockid,
					'panelid' => $blockinfo['panelid'],
					'label' => $blocklabel,
					'fields' => array()
				);
			}
			$returndata[$key]['fields'] = array_merge((array)$returndata[$key]['fields'], (array)$label_data[$blockid]);
		}
	}
	// crmv@104568e
	$log->debug("Exiting getDetailBlockInformation method ...");
	return $returndata;
}

function VT_detailViewNavigation($smarty,$recordNavigationInfo,$currrentRecordId){
	$pageNumber =0;
	foreach ($recordNavigationInfo as $start=>$recordIdList){
		$pageNumber++;
		foreach ($recordIdList as $index=>$recordId) {
			if($recordId === $currrentRecordId){
				if($index ==0 ){
					$smarty->assign('privrecordstart',$start-1);
					$smarty->assign('privrecord',$recordNavigationInfo[$start-1][count($recordNavigationInfo[$start-1])-1]);
				}else{
					$smarty->assign('privrecordstart',$start);
					$smarty->assign('privrecord',$recordIdList[$index-1]);
				}
				if($index == count($recordIdList)-1){
					$smarty->assign('nextrecordstart',$start+1);
					$smarty->assign('nextrecord',$recordNavigationInfo[$start+1][0]);
				}else{
					$smarty->assign('nextrecordstart',$start);
					$smarty->assign('nextrecord',$recordIdList[$index+1]);
				}
			}
		}
	}
}

//crmv@150751
function getRelatedListInfoById($relationId, $recordid=''){
	global $table_prefix;
	static $relatedInfoCache = array();
	if(isset($relatedInfoCache[$relationId])){
		return $relatedInfoCache[$relationId];
	}
	$adb = PearDatabase::getInstance();
	require_once('modules/Settings/ProcessMaker/ProcessMakerUtils.php');
	$PMUtils = ProcessMakerUtils::getInstance();
	if (!empty($recordid)) $tvh_id = $PMUtils->getSystemVersion4Record($recordid,array('tabs',getSalesEntityType($recordid),'id'));
	if (!empty($tvh_id)) {
		$result = $adb->pquery("select * from {$table_prefix}_relatedlists_vh where relation_id=? and versionid=?", array($relationId, $tvh_id));
	} else {
		$result = $adb->pquery("select * from {$table_prefix}_relatedlists where relation_id=?", array($relationId));
	}
	$rowCount = $adb->num_rows($result);
	$relationInfo = array();
	if($rowCount > 0) {
		$relationInfo['tabid'] = $adb->query_result($result,0,"tabid"); // crmv@49398
		$relationInfo['relatedTabId'] = $adb->query_result($result,0,"related_tabid");
		$relationInfo['functionName'] = $adb->query_result($result,0,"name");
		$relationInfo['label'] = $adb->query_result($result,0,"label");
		$relationInfo['actions'] = $adb->query_result($result,0,"actions");
		$relationInfo['relationId'] = $adb->query_result($result,0,"relation_id");
	}
	$relatedInfoCache[$relationId] = $relationInfo;
	return $relatedInfoCache[$relationId];
}
//crmv@150751e