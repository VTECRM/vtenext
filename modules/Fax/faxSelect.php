<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

global $app_strings,$mod_strings,$current_user,$theme,$adb;
global $table_prefix;
$image_path = 'themes/'.$theme.'/images/';
$idlist = $_REQUEST['idlist'];
$pmodule=$_REQUEST['return_module'];
$ids=explode(';',$idlist);
$single_record = false;
if(!strpos($idlist,','))
{
	$single_record = true;
}
$smarty = new VteSmarty();

$userid =  $current_user->id;

//crmv@18926 - aggiunto and presence...
$querystr = "select fieldid, fieldname, fieldlabel, columnname from ".$table_prefix."_field where tabid=? and uitype=1013 and presence in (0,2)";
//crmv@18926e
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

if($single_record && count($columnlists) > 0)
{
	$count = 0;
	$val_cnt = 0;	
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
			$query = 'select '.$adb->sql_concat(Array('firstname',"' '",'lastname')).' as leadname,'.implode(",",$columnlists).' from '.$table_prefix.'_leaddetails left join '.$table_prefix.'_leadscf on '.$table_prefix.'_leadscf.leadid = '.$table_prefix.'_leaddetails.leadid inner join '.$table_prefix.'_leadaddress on '.$table_prefix.'_leadaddress.leadaddressid = '.$table_prefix.'_leaddetails.leadid where '.$table_prefix.'_leaddetails.leadid = ?';
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
			$query = 'select '.$adb->sql_concat(Array('firstname',"' '",'lastname')).' as contactname,'.implode(",",$columnlists).' from '.$table_prefix.'_contactdetails left join '.$table_prefix.'_contactscf on '.$table_prefix.'_contactscf.contactid = '.$table_prefix.'_contactdetails.contactid where '.$table_prefix.'_contactdetails.contactid = ?';
			$result=$adb->pquery($query, array($idlist));
		        foreach($columnlists as $columnname)	
			{
				$con_eval = $adb->query_result($result,0,$columnname);
				$field_value[$count++] = $con_eval;
				if($con_eval != "") $val_cnt++;
			}	
			$entity_name = $adb->query_result($result,0,'contactname');
			break;	
		case 'Vendors':
			$query = 'select vendorname ,'.implode(",",$columnlists).' from '.$table_prefix.'_vendor left join '.$table_prefix.'_vendorcf on '.$table_prefix.'_vendorcf.vendorid = '.$table_prefix.'_vendor.vendorid where '.$table_prefix.'_vendor.vendorid = ?';
//			echo $adb->convert2Sql($query,$adb->flatten_array(array($idlist)));die;
			$result=$adb->pquery($query, array($idlist));
		        foreach($columnlists as $columnname)	
			{
				$con_eval = $adb->query_result($result,0,$columnname);
				$field_value[$count++] = $con_eval;
				if($con_eval != "") $val_cnt++;
			}	
			$entity_name = $adb->query_result($result,0,'vendorname');
			break;			
	}	
}
$smarty->assign('PERMIT',$permit);
$smarty->assign('ENTITY_NAME',$entity_name);
$smarty->assign('ONE_RECORD',$single_record);
$smarty->assign('FAXDATA',$field_value);
$smarty->assign('FAXINFO',$returnvalue);
$smarty->assign("MOD", $mod_strings);
$smarty->assign("IDLIST", $idlist);
$smarty->assign("APP", $app_strings);
$smarty->assign("FROM_MODULE", $pmodule);
$smarty->assign("IMAGE_PATH",$image_path);
if($single_record && count($columnlists) > 0 && $val_cnt > 0)
	$smarty->display("SelectFax.tpl");
else if(!$single_record && count($columnlists) > 0)
	$smarty->display("SelectFax.tpl");
else if($single_record && $val_cnt == 0)
        echo "No Fax Ids";	
else
	echo "Fax Ids not permitted";	
?>