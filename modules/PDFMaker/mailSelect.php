<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
require_once('include/database/PearDatabase.php');


global $app_strings,$current_user,$theme,$adb,$table_prefix;
$image_path = 'themes/'.$theme.'/images/';
$idlist = vtlib_purify($_REQUEST['idlist']);
$pmodule=vtlib_purify($_REQUEST['return_module']);
$ids=explode(';',$idlist);
$single_record = false;
if(!strpos($idlist,':'))
{
	$single_record = true;
}
$language = VteSession::get('authenticated_user_language');
$mod_strings = return_module_language($language, "Emails");

$smarty = new VteSmarty();

$userid =  $current_user->id;

$tabid = getTabId($pmodule);
unset(VTCacheUtils::$_fieldinfo_cache[$tabid]);                   

if ($pmodule != "Accounts" && $pmodule != "Leads" && $pmodule != "Contacts")
{
  $focus2 = CRMEntity::getInstance($pmodule);
  $focus2->retrieve_entity_info($idlist,$pmodule);
  $focus2->id = $idlist;

  $accountid = $focus2->column_fields["account_id"];
  $contactid = $focus2->column_fields["contact_id"];
  $relatedto = $focus2->column_fields["related_to"];
  $parentid = $focus2->column_fields["parent_id"];
  
  if ($relatedto != "" && $relatedto != "0")
  {
      $type = getSalesEntityType($relatedto);
      if($type == "Accounts") $accountid = $relatedto; else $contactid = $relatedto;
  }
  if ($parentid != "" && $parentid != "0")
  {
      $type = getSalesEntityType($parentid);
      if($type == "Accounts") $accountid = $parentid; else $contactid = $parentid;
  }
  
  if ($accountid != "" && $accountid != "0")
  {
      $pmodule = "Accounts"; 
      $idlist = $accountid;
  }
  elseif ($contactid != "" && $contactid != "0")
  {
      $pmodule = "Contacts"; 
      $idlist = $contactid;
  }
  
  unset($focus2);
}
    
$querystr = "select fieldid, fieldname, fieldlabel, columnname from ".$table_prefix."_field where tabid=? and uitype=13 and ".$table_prefix."_field.presence in (0,2)";
$res=$adb->pquery($querystr, array(getTabid($pmodule)));
$numrows = $adb->num_rows($res);
$returnvalue = Array();
for($i = 0; $i < $numrows; $i++)
{
	$value = Array();
	$fieldname = $adb->query_result($res,$i,"fieldname");
	$permit = getFieldVisibilityPermission($pmodule, $userid, $fieldname);
	if($permit == '0')
	{
		$temp=$adb->query_result($res,$i,'columnname');
		$columnlists [] = $temp;
		$fieldid=$adb->query_result($res,$i,'fieldid');
		$fieldlabel =$adb->query_result($res,$i,'fieldlabel');
		$value[] = getTranslatedString($fieldlabel);
		$returnvalue [$fieldid]= $value;
	}
}

$count = 0;
$val_cnt = 0;	

if($single_record && count($columnlists) > 0)
{
	
	switch($pmodule)
	{
		case 'Accounts':
			$query = 'select accountname,'.implode(",",$columnlists).' from '.$table_prefix.'_account left join '.$table_prefix.'_accountscf on '.$table_prefix.'_accountscf.accountid = '.$table_prefix.'_account.accountid where '.$table_prefix.'_account.accountid = ?';
			$result=$adb->pquery($query, array($idlist));
		        foreach($columnlists as $columnname)	
			{
				$acc_eval = $adb->query_result($result,0,$columnname);
				$field_value[$count++] = $acc_eval;
				if($acc_eval != "") $val_cnt++;
				
			}
			$entity_name = $adb->query_result($result,0,'accountname');
			break;
		case 'Leads':
			//crmv@25962
			$col=array("firstname","' '","lastname");
			$query = 'select '.$adb->sql_concat($col).' as leadname,'.implode(",",$columnlists).' from '.$table_prefix.'_leaddetails left join '.$table_prefix.'_leadscf on '.$table_prefix.'_leadscf.leadid = '.$table_prefix.'_leaddetails.leadid where '.$table_prefix.'_leaddetails.leadid = ?';
			//crmv@25962e
			$result=$adb->pquery($query, array($idlist));
		        foreach($columnlists as $columnname)	
			{
				$lead_eval = $adb->query_result($result,0,$columnname);
				$field_value[$count++] = $lead_eval;
				if($lead_eval != "") $val_cnt++;
			}
			$entity_name = $adb->query_result($result,0,'leadname');
			break;
		case 'Contacts':
			//crmv@25962
			$col=array("firstname","' '","lastname");
			$query = 'select '.$adb->sql_concat($col).' as contactname,'.implode(",",$columnlists).' from '.$table_prefix.'_contactdetails left join '.$table_prefix.'_contactscf on '.$table_prefix.'_contactscf.contactid = '.$table_prefix.'_contactdetails.contactid where '.$table_prefix.'_contactdetails.contactid = ?';
			//crmv@25962e
			$result=$adb->pquery($query, array($idlist));
		        foreach($columnlists as $columnname)	
			{
				$con_eval = $adb->query_result($result,0,$columnname);
				$field_value[$count++] = $con_eval;
				if($con_eval != "") $val_cnt++;
			}	
			$entity_name = $adb->query_result($result,0,'contactname');
			break;	
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
if($single_record && count($columnlists) > 0 && $val_cnt > 0)
	$smarty->display("modules/PDFMaker/SelectEmail.tpl");
else if(!$single_record && count($columnlists) > 0)
	$smarty->display("modules/PDFMaker/SelectEmail.tpl");
else if($single_record && $val_cnt == 0)
  echo "No Mail Ids";	
else
	echo "Mail Ids not permitted";	
?>