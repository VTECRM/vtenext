<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
require_once('include/CustomFieldUtil.php');
global $mod_strings;
global $app_strings;

$smarty=new VteSmarty();
global $theme;
$theme_path="themes/".$theme."/";
$image_path=$theme_path."images/";
$smarty->assign("IMAGE_PATH", $image_path);

/**
 * Function to get Account custom fields
 * @param integer $leadid      - lead customfield id
 * @param integer $accountid   - account customfield id
 * return array   $accountcf   - account customfield
 */
function getAccountCustomValues($leadid,$accountid)
{
	global $adb,$table_prefix;
	$accountcf=Array();
	$sql="select fieldid,fieldlabel,uitype,typeofdata from ".$table_prefix."_field,".$table_prefix."_tab where ".$table_prefix."_field.tabid=".$table_prefix."_tab.tabid and generatedtype=2 and ".$table_prefix."_tab.name='Accounts'";
	$result = $adb->pquery($sql, array());
	$noofrows = $adb->num_rows($result);
	
	for($i=0;$i<$noofrows;$i++)
	{
		$account_field['fieldid']=$adb->query_result($result,$i,"fieldid");
		$account_field['fieldlabel']=$adb->query_result($result,$i,"fieldlabel");
		$account_field['typeofdata']=$adb->query_result($result,$i,"typeofdata");
		$account_field['fieldtype']=getCustomFieldTypeName($adb->query_result($result,$i,"uitype"));
		if($account_field['fieldid']==$accountid)
			$account_field['selected'] = "selected";
		else
			$account_field['selected'] = "";
		$account_cfelement[]=$account_field;
	}
	$accountcf[$leadid.'_account']=$account_cfelement;
	return $accountcf;
}

/**
 * Function to get contact custom fields
 * @param integer $leadid      - lead customfield id
 * @param integer $contactid   - contact customfield id
 * return array   $contactcf   - contact customfield
 */
function getContactCustomValues($leadid,$contactid)
{	
	global $adb,$table_prefix;	
	$contactcf=Array();
	$sql="select fieldid,fieldlabel,uitype,typeofdata from ".$table_prefix."_field,".$table_prefix."_tab where ".$table_prefix."_field.tabid=".$table_prefix."_tab.tabid and generatedtype=2 and ".$table_prefix."_tab.name='Contacts'";
	$result = $adb->pquery($sql, array());
	$noofrows = $adb->num_rows($result);
	for($i=0; $i<$noofrows; $i++)
	{
		$contact_field['fieldid']=$adb->query_result($result,$i,"fieldid");
		$contact_field['fieldlabel']=$adb->query_result($result,$i,"fieldlabel");
		$contact_field['typeofdata']=$adb->query_result($result,$i,"typeofdata");
		$contact_field['fieldtype']=getCustomFieldTypeName($adb->query_result($result,$i,"uitype"));
	
                if($contact_field['fieldid']==$contactid)
                        $contact_field['selected']="selected";
		else
                        $contact_field['selected'] = "";
		$contact_cfelement[]=$contact_field;
	}
	$contactcf[$leadid.'_contact'] = $contact_cfelement;
        return $contactcf;
}	

/**
 * Function to get potential custom fields
 * @param integer $leadid      - lead customfield id
 * @param integer $potentialid - potential customfield id
 * return array   $potentialcf - potential customfield
 */
function getPotentialCustomValues($leadid,$potentialid)
{
	global $adb,$table_prefix;	
	$potentialcf=Array();
	$sql="select fieldid,fieldlabel,uitype,typeofdata from ".$table_prefix."_field,".$table_prefix."_tab where ".$table_prefix."_field.tabid=".$table_prefix."_tab.tabid and generatedtype=2 and ".$table_prefix."_tab.name='Potentials'";
	$result = $adb->pquery($sql, array());
	$noofrows = $adb->num_rows($result);
	for($i=0; $i<$noofrows; $i++)
	{
		$potential_field['fieldid']=$adb->query_result($result,$i,"fieldid");
		$potential_field['fieldlabel']=$adb->query_result($result,$i,"fieldlabel");
		$potential_field['typeofdata']=$adb->query_result($result,$i,"typeofdata");
		$potential_field['fieldtype']=getCustomFieldTypeName($adb->query_result($result,$i,"uitype"));

		if($potential_field['fieldid']==$potentialid)
			 $potential_field['selected']="selected";
		else
                         $potential_field['selected'] = "";
		$potential_cfelement[]=$potential_field;
	}
	$potentialcf[$leadid.'_potential']=$potential_cfelement;
        return $potentialcf;
}

/**
 * Function to get leads mapping custom fields
 * return array   $leadcf - mapping custom fields
 */

function customFieldMappings() {
	global $adb,$table_prefix;
	$tabid = getTabid('Leads');
	$convert_sql = "SELECT ".$table_prefix."_convertleadmapping.*,uitype,fieldlabel,typeofdata,fieldid FROM ".$table_prefix."_field  LEFT JOIN ".$table_prefix."_convertleadmapping
				ON ".$table_prefix."_field.fieldid = ".$table_prefix."_convertleadmapping.leadfid
				WHERE tabid=?
				AND ".$table_prefix."_field.presence IN (0,2)
				AND ".$table_prefix."_field.fieldname NOT IN('assigned_user_id','createdtime','modifiedtime','lead_no','modifiedby','campaignrelstatus')
				ORDER BY ".$table_prefix."_field.fieldlabel";
	$convert_result = $adb->pquery($convert_sql, array($tabid));

	$no_rows = $adb->num_rows($convert_result);
	for ($j = 0; $j < $no_rows; $j++) {
		$lead_field_id = $adb->query_result($convert_result, $j, "fieldlabel");
		if (!empty($lead_field_id)) {

			$cfmid = $adb->query_result($convert_result, $j, "cfmid");
			$accountid = $adb->query_result($convert_result, $j, "accountfid");
			$contactid = $adb->query_result($convert_result, $j, "contactfid");
			$potentialid = $adb->query_result($convert_result, $j, "potentialfid");
			if ((empty($accountid) && empty($contactid) && empty($potentialid))) {
				$lead_field['display'] = 'false';
			} else {
				$lead_field['display'] = 'true';
			}
			$lead_field['editable'] = $adb->query_result($convert_result, $j, "editable");
			$lead_field['cfmid'] = $cfmid;
			$lead_field['cfmname'] = $cfmid . '_cfmid';
			$lead_field['fieldid'] = $adb->query_result($convert_result, $j, "fieldid");
			$lead_field['leadid'] = getTranslatedString($adb->query_result($convert_result, $j, "fieldlabel"), 'Leads');
			$lead_field['typeofdata'] = $adb->query_result($convert_result, $j, "typeofdata");
			$lead_field['fieldtype'] = getCustomFieldTypeName($adb->query_result($convert_result, $j, "uitype"));
			$lead_field['account'] = getModuleValues( $accountid, 'Accounts');
			$lead_field['contact'] = getModuleValues( $contactid, 'Contacts');
			$lead_field['potential'] = getModuleValues( $potentialid, 'Potentials');
			$lead_field['lead'] = getModuleValues( null, 'Leads');

			if (empty($accountid) && empty($contactid) && empty($potentialid)) {
				$lead_field['display'] = 'false';
			} else {
				$lead_field['display'] = 'true';
			}
			$leadcf[] = $lead_field;
		}
	}
	return $leadcf;
}

function getModuleValues( $moduleid, $module) {
	global $adb,$table_prefix;
	$potentialcf = Array();
	switch($module){
		case "Accounts":$sql="SELECT fieldid,fieldlabel,uitype,typeofdata,fieldname FROM ".$table_prefix."_field,".$table_prefix."_tab WHERE ".$table_prefix."_field.tabid=".$table_prefix."_tab.tabid
						AND generatedtype IN (1,2)
						AND ".$table_prefix."_tab.name=?
						AND ".$table_prefix."_field.fieldname NOT IN('assigned_user_id','createdtime','modifiedtime','lead_no','modifiedby','campaignrelstatus','account_no','account_id','contact_no','contact_id','imagename','potential_no','related_to','campaignid','accountname','email1')
						AND ".$table_prefix."_field.presence in (0,2)";
				break;
		case "Contacts":$sql="SELECT fieldid,fieldlabel,uitype,typeofdata,fieldname FROM ".$table_prefix."_field,".$table_prefix."_tab WHERE ".$table_prefix."_field.tabid=".$table_prefix."_tab.tabid
						AND generatedtype IN (1,2)
						AND ".$table_prefix."_tab.name=?
						AND ".$table_prefix."_field.fieldname NOT IN('assigned_user_id','createdtime','modifiedtime','lead_no','modifiedby','campaignrelstatus','account_no','account_id','contact_no','contact_id','imagename','potential_no','related_to','campaignid','firstname','email','lastname')
						AND ".$table_prefix."_field.presence in (0,2)";
				break;
		case "Potentials":$sql="SELECT fieldid,fieldlabel,uitype,typeofdata,fieldname FROM ".$table_prefix."_field,".$table_prefix."_tab WHERE ".$table_prefix."_field.tabid=".$table_prefix."_tab.tabid
						AND generatedtype IN (1,2)
						AND ".$table_prefix."_tab.name=?
						AND ".$table_prefix."_field.fieldname NOT IN('assigned_user_id','createdtime','modifiedtime','lead_no','modifiedby','campaignrelstatus','account_no','account_id','contact_no','contact_id','imagename','potential_no','related_to','campaignid','potentialname')
						AND ".$table_prefix."_field.presence in (0,2)";
				break;
		case 'Leads': $sql="SELECT fieldid,fieldlabel,uitype,typeofdata,fieldname FROM ".$table_prefix."_field,".$table_prefix."_tab WHERE ".$table_prefix."_field.tabid=".$table_prefix."_tab.tabid
						AND generatedtype IN (1,2)
						AND ".$table_prefix."_tab.name=?
						AND ".$table_prefix."_field.fieldname NOT IN('assigned_user_id','createdtime','modifiedtime','lead_no','modifiedby','campaignrelstatus')
						AND ".$table_prefix."_field.presence in (0,2)";
				break;
	}
	
	$result = $adb->pquery($sql, array($module));
	$noofrows = $adb->num_rows($result);
	for ($i = 0; $i < $noofrows; $i++) {
		$module_field['fieldid'] = $adb->query_result($result, $i, "fieldid");
		$module_field['fieldlabel'] = getTranslatedString($adb->query_result($result, $i, "fieldlabel"), $module);
		$module_field['typeofdata'] = $adb->query_result($result, $i, "typeofdata");
		$module_field['fieldtype'] = getCustomFieldTypeName($adb->query_result($result, $i, "uitype"));

		if ($module_field['fieldid'] == $moduleid)
			$module_field['selected'] = "selected";
		else
			$module_field['selected'] = "";

		$module_cfelement[] = $module_field;
	}

	return $module_cfelement;
}

function getCFListEntries($module)
{
	global $adb,$app_strings,$theme,$smarty,$log,$table_prefix;
	$tabid = getTabid($module);
	if ($module == 'Calendar') {
		$tabid = array(9, 16);
	}
	$theme_path="themes/".$theme."/";
	$image_path="themes/images/";
	$dbQuery = "select fieldid,columnname,fieldlabel,uitype,displaytype,block,".$table_prefix."_convertleadmapping.cfmid,tabid from ".$table_prefix."_field left join ".$table_prefix."_convertleadmapping on  ".$table_prefix."_convertleadmapping.leadfid = ".$table_prefix."_field.fieldid where tabid in (". generateQuestionMarks($tabid) .") and ".$table_prefix."_field.presence in (0,2) and generatedtype = 2 order by sequence";
	$result = $adb->pquery($dbQuery, array($tabid));
	$row = $adb->fetch_array($result);
	$count=1;
	$cflist=Array();
	if($row!='')
	{
		do
		{
			$cf_element=Array();
			$cf_element['no']=$count;
			$cf_element['label']=getTranslatedString($row["fieldlabel"],$module);
			$fld_type_name = getCustomFieldTypeName($row["uitype"]);
			$cf_element['type']=$fld_type_name;
			$cf_tab_id = $row["tabid"];
			if($module == 'Leads')
			{
				$mapping_details = getListLeadMapping($row["cfmid"]);
				$cf_element[]= $mapping_details['accountlabel'];
				$cf_element[]= $mapping_details['contactlabel'];
				$cf_element[]= $mapping_details['potentiallabel'];
			}
			if($module == 'Calendar')
			{
				if ($cf_tab_id == '9')
					$cf_element['activitytype'] = getTranslatedString('Task',$module);
				else
					$cf_element['activitytype'] = getTranslatedString('Event',$module);
			}
			$cf_element['tool']='&nbsp;<img style="cursor:pointer;" onClick="deleteCustomField('.$row["fieldid"].',\''.$module.'\', \''.$row["columnname"].'\', \''.$row["uitype"].'\')" src="'. resourcever('delete.gif') .'" border="0"  alt="'.$app_strings['LBL_DELETE_BUTTON_LABEL'].'" title="'.$app_strings['LBL_DELETE_BUTTON_LABEL'].'"/></a>';
			$cflist[] = $cf_element;
			$count++;
		}while($row = $adb->fetch_array($result));
	}
	return $cflist;
}

/**
 * Function to get customfield entries for leads
 * @param string $module - Module name
 * return array  $cflist - customfield entries
 */
function getCFLeadMapping($module) {
	global $adb, $app_strings, $theme, $smarty, $log,$table_prefix;
	$tabid = getTabid($module);
	$theme_path = "themes/" . $theme . "/";
	$image_path = "themes/images/";
	$dbQuery = "SELECT fieldid,columnname,fieldlabel,uitype,displaytype,block,".$table_prefix."_convertleadmapping.cfmid,".$table_prefix."_convertleadmapping.editable,tabid FROM ".$table_prefix."_convertleadmapping LEFT JOIN ".$table_prefix."_field
				ON  ".$table_prefix."_field.fieldid=".$table_prefix."_convertleadmapping.leadfid 
				WHERE tabid IN (" . generateQuestionMarks($tabid) . ")
				AND ".$table_prefix."_field.presence IN (0,2)
				AND generatedtype IN (1,2)
				AND ".$table_prefix."_field.fieldname NOT IN('assigned_user_id','createdtime','modifiedtime','lead_no','modifiedby','campaignrelstatus')
				ORDER BY ".$table_prefix."_field.fieldlabel";
	$result = $adb->pquery($dbQuery, array($tabid));
	$row = $adb->fetch_array($result);
	$count = 1;
	$cflist = Array();
	if ($row != '') {
		do {
			$cf_element = Array();
			$cf_element['map']['no'] = $count;
			$cf_element['map']['label'] = getTranslatedString($row["fieldlabel"], $module);
			$fld_type_name = getCustomFieldTypeName($row["uitype"]);
			$cf_element['map']['type'] = $fld_type_name;
			$cf_tab_id = $row["tabid"];
			$cf_element['cfmid'] = $row["cfmid"];
			$cf_element['editable']=$row["editable"];
			if ($module == 'Leads') {
				$mapping_details = getListLeadMapping($row["cfmid"]);
				$cf_element['map'][] = $mapping_details['accountlabel'];
				$cf_element['map'][] = $mapping_details['contactlabel'];
				$cf_element['map'][] = $mapping_details['potentiallabel'];
			}
			$cflist[] = $cf_element;
			$count++;
		}while ($row = $adb->fetch_array($result));
	}
	return $cflist;
}


/**
 * Function to Lead customfield Mapping entries
 * @param integer  $cfid   - Lead customfield id
 * return array    $label  - customfield mapping
 */
function getListLeadMapping($cfid) {
	global $adb,$table_prefix;
	$sql = "select * from ".$table_prefix."_convertleadmapping where cfmid =?";
	$result = $adb->pquery($sql, array($cfid));
	$noofrows = $adb->num_rows($result);
	for ($i = 0; $i < $noofrows; $i++) {
		$leadid = $adb->query_result($result, $i, 'leadfid');
		$accountid = $adb->query_result($result, $i, 'accountfid');
		$contactid = $adb->query_result($result, $i, 'contactfid');
		$potentialid = $adb->query_result($result, $i, 'potentialfid');
		$cfmid = $adb->query_result($result, $i, 'cfmid');

		$sql2 = "select fieldlabel from ".$table_prefix."_field where fieldid =?";
		$result2 = $adb->pquery($sql2, array($accountid));
		$accountfield = $adb->query_result($result2, 0, 'fieldlabel');
		$label['accountlabel'] = getTranslatedString($accountfield, 'Accounts');

		$sql3 = "select fieldlabel from ".$table_prefix."_field where fieldid =?";
		$result3 = $adb->pquery($sql3, array($contactid));
		$contactfield = $adb->query_result($result3, 0, 'fieldlabel');
		$label['contactlabel'] = getTranslatedString($contactfield, 'Contacts');
		$sql4 = "select fieldlabel from ".$table_prefix."_field where fieldid =?";
		$result4 = $adb->pquery($sql4, array($potentialid));
		$potentialfield = $adb->query_result($result4, 0, 'fieldlabel');
		$label['potentiallabel'] = getTranslatedString($potentialfield, 'Potentials');
	}
	return $label;
}

$smarty->assign("MOD",$mod_strings);
$smarty->assign("APP",$app_strings);
$smarty->assign("CUSTOMFIELDMAPPING",customFieldMappings());
$smarty->assign("MODULE",'Leads');
$smarty->assign("CFENTRIES",getCFLeadMapping('Leads'));
$smarty->display(vtlib_getModuleTemplate('Leads', 'LeadsCustomEntries.tpl'));	//crmv@29463

?>