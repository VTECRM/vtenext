<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

global $table_prefix, $app_strings, $mod_strings, $current_user, $theme, $adb;
$image_path = 'themes/'.$theme.'/images/';
$idlist = vtlib_purify($_REQUEST['idlist']);
$pmodule = vtlib_purify($_REQUEST['return_module']);

$ids = explode(',',$idlist);	//crmv@14454
if (is_array($ids)) $ids = array_filter($ids);
(count($ids) == 1) ? $single_record = true : $single_record = false;

$smarty = new VteSmarty();

$userid =  $current_user->id;

$columnlists = array();
$tablelist = array();

//crmv@18926 - aggiunto and presence...
$querystr = "select fieldid, fieldname, fieldlabel, tablename, columnname from {$table_prefix}_field where tabid=? and uitype=13 and presence in (0,2)";
//crmv@18926e
$res = $adb->pquery($querystr, array(getTabid($pmodule)));
$numrows = $adb->num_rows($res);
$returnvalue = Array();
for($i = 0; $i < $numrows; $i++)
{
	$value = Array();
	$fieldname = $adb->query_result($res,$i,"fieldname");
	$permit = getFieldVisibilityPermission($pmodule, $userid, $fieldname);
	if($permit == '0')
	{
		$columnlists[] = $adb->query_result_no_html($res,$i,'columnname');
		$tablelist[] = $adb->query_result_no_html($res,$i,'tablename');
		$fieldid = $adb->query_result($res,$i,'fieldid');
		$fieldlabel = $adb->query_result($res,$i,'fieldlabel');
		$value[] = getTranslatedString($fieldlabel,$pmodule);
		$returnvalue[$fieldid]= $value;
	}
}
$tablelist = array_unique($tablelist);
if(count($columnlists) > 0)
{
	$count = 0;
	$val_cnt = 0;	
	switch($pmodule)
	{
		case 'Accounts':
			$query = 'select accountname,'.implode(",",$columnlists).' from '.$table_prefix.'_account left join '.$table_prefix.'_accountscf on '.$table_prefix.'_accountscf.accountid = '.$table_prefix.'_account.accountid where '.$table_prefix.'_account.accountid in ('.generateQuestionMarks($ids).')';
			$result=$adb->pquery($query, array($ids));
			foreach($columnlists as $columnname)
			{
				$acc_eval = $adb->query_result($result,0,$columnname);
				$field_value[$count++] = $acc_eval;
				if($acc_eval != "") $val_cnt++;
			}
			$entity_name = $adb->query_result($result,0,'accountname');
			break;
		case 'Leads':
			$query = 'select '.$adb->sql_concat(Array('firstname',"' '",'lastname')).' as leadname,'.implode(",",$columnlists).' from '.$table_prefix.'_leaddetails left join '.$table_prefix.'_leadscf on '.$table_prefix.'_leadscf.leadid = '.$table_prefix.'_leaddetails.leadid where '.$table_prefix.'_leaddetails.leadid in ('.generateQuestionMarks($ids).')';
			$result=$adb->pquery($query, array($ids));
			foreach($columnlists as $columnname)
			{
				$lead_eval = $adb->query_result($result,0,$columnname);
				$field_value[$count++] = $lead_eval;
				if($lead_eval != "") $val_cnt++;
			}
			$entity_name = $adb->query_result($result,0,'leadname');
			break;
		case 'Contacts':
			$query = 'select '.$adb->sql_concat(Array('firstname',"' '",'lastname')).' as contactname,'.implode(",",$columnlists).' from '.$table_prefix.'_contactdetails left join '.$table_prefix.'_contactscf on '.$table_prefix.'_contactscf.contactid = '.$table_prefix.'_contactdetails.contactid where '.$table_prefix.'_contactdetails.contactid in ('.generateQuestionMarks($ids).')';
			$result=$adb->pquery($query, array($ids));
			foreach($columnlists as $columnname)
			{
				$con_eval = $adb->query_result($result,0,$columnname);
				$field_value[$count++] = $con_eval;
				if($con_eval != "") $val_cnt++;
			}	
			$entity_name = $adb->query_result($result,0,'contactname');
			break;
		case 'Vendors':
			$query = 'select vendorname ,'.implode(",",$columnlists).' from '.$table_prefix.'_vendor left join '.$table_prefix.'_vendorcf on '.$table_prefix.'_vendorcf.vendorid = '.$table_prefix.'_vendor.vendorid where '.$table_prefix.'_vendor.vendorid in ('.generateQuestionMarks($ids).')';
			$result=$adb->pquery($query, array($ids));
			foreach($columnlists as $columnname)
			{
				$con_eval = $adb->query_result($result,0,$columnname);
				$field_value[$count++] = $con_eval;
				if($con_eval != "") $val_cnt++;
			}	
			$entity_name = $adb->query_result($result,0,'vendorname');
			break;
		//crmv@48167
		default:
			$focus = CRMEntity::getInstance($pmodule);
			$joins = "";
			foreach ($tablelist as $jointable) {
				if ($jointable != $focus->table_name) {
					$joinidx = $focus->tab_name_index[$jointable];
					$joins .= " LEFT JOIN $jointable ON $jointable.$joinidx = {$focus->table_name}.{$focus->table_index}";
				}
			}
			$query = "select ".implode(",",$columnlists)."
				from {$focus->table_name} 
				$joins
				where {$focus->table_name}.{$focus->table_index} in (".generateQuestionMarks($ids).")";
			$result=$adb->pquery($query, array($ids));
	        foreach($columnlists as $columnname)	
			{
				$con_eval = $adb->query_result($result,0,$columnname);
				$field_value[$count++] = $con_eval;
				if($con_eval != "") $val_cnt++;
			}	
			$tmp = getEntityName($pmodule, $ids[0]);
			$entity_name = $tmp[$ids[0]];
			break;
		//crmv@48167e
	}	
}

saveListViewCheck($pmodule,$idlist);	//crmv@27096

$smarty->assign('PERMIT',$permit);
$smarty->assign('ENTITY_NAME',$entity_name);
$smarty->assign('ONE_RECORD',$single_record);
$smarty->assign('MAILDATA',$field_value);
$smarty->assign('MAILINFO',$returnvalue);
$smarty->assign("MOD", $mod_strings);
$smarty->assign("IDLIST", $idlist);
$smarty->assign("APP", $app_strings);
$smarty->assign("FROM_MODULE", $pmodule);
$smarty->assign("THEME", $theme);
$smarty->assign("IMAGE_PATH",$image_path);

if ($single_record && $val_cnt == 0)	// I don't show the list of fields if I haven't any value
	echo "No Mail Ids";
elseif (count($columnlists) > 0 && $val_cnt > 0)
	$smarty->display("SelectEmail.tpl");
elseif (count($columnlists) > 0)
	$smarty->display("SelectEmail.tpl");
elseif ($val_cnt == 0)
	echo "No Mail Ids";
else
	echo "Mail Ids not permitted";
?>