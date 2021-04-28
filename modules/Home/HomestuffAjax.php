<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
	
global $adb,$current_user, $mod_strings,$table_prefix;
require('user_privileges/user_privileges_'.$current_user->id.'.php');

$modval=trim($_REQUEST['modname']);
//crmv@208472
if(!empty($modval)){
	$tabid = getTabId($modval);
	$ssql = "select ".$table_prefix."_customview.*, ".$table_prefix."_users.user_name from ".$table_prefix."_customview inner join ".$table_prefix."_tab on ".$table_prefix."_tab.name = ".$table_prefix."_customview.entitytype 
				left join ".$table_prefix."_users on ".$table_prefix."_customview.userid = ".$table_prefix."_users.id ";
	$ssql .= " where ".$table_prefix."_tab.tabid=?";
	$sparams = array($tabid);
	
	if($is_admin == false){
		$ssql .= " and (".$table_prefix."_customview.status=0 or ".$table_prefix."_customview.userid = ? or ".$table_prefix."_customview.status = 3 or ".$table_prefix."_customview.userid in(select ".$table_prefix."_user2role.userid from ".$table_prefix."_user2role inner join ".$table_prefix."_users on ".$table_prefix."_users.id=".$table_prefix."_user2role.userid inner join ".$table_prefix."_role on ".$table_prefix."_role.roleid=".$table_prefix."_user2role.roleid where ".$table_prefix."_role.parentrole like '".$current_user_parent_role_seq."::%'))";
		array_push($sparams, $current_user->id);
	}
	$ssql .= " ORDER BY viewname";
	$result = $adb->pquery($ssql, $sparams);
	
	if($adb->num_rows($result)==0){
	  echo $mod_strings['MSG_NO_FILTERS'];
	  die;
	}else{
		$html = '<select id=selFilterid name=selFiltername onchange="VTE.Homestuff.setPrimaryFld(this)" class="detailedViewTextBox" onfocus="this.className=\'detailedViewTextBoxOn\'" onblur="this.className=\'detailedViewTextBox\'">';
		for($i=0;$i<$adb->num_rows($result);$i++){
			if($adb->query_result($result,$i,'userid')==$current_user->id || $adb->query_result($result,$i,"viewname")=='All'){
				$html .= "<option value='".$adb->query_result($result,$i,'cvid')."'>".$adb->query_result($result,$i,"viewname")."</option>";
			}else{
				$html .= "<option value='".$adb->query_result($result,$i,'cvid')."'>".$adb->query_result($result,$i,"viewname")."[".$adb->query_result($result,$i,'user_name')."]</option>";
			}
		}
		$html .= '</select>';
	}
	echo $html;
}

/* crmv@208472 */

if(!empty($_REQUEST['primecvid'])){
	$cvid=$_REQUEST['primecvid'];
	$fieldmodule = vtlib_purify($_REQUEST['fieldmodname']);
	$queryprime="select cvid,columnname from ".$table_prefix."_cvcolumnlist where columnname not like '%::%' and cvid=?";
	$result=$adb->pquery($queryprime,array($cvid));
	global $current_language,$app_strings;
	$fieldmod_strings = return_module_language($current_language, $fieldmodule);
	if($adb->num_rows($result)==0){
	  echo $mod_strings['MSG_NO_FIELDS'];
	  die;
	}else{
		$html = '<select id=selPrimeFldid name=PrimeFld multiple class="detailedViewTextBox" onfocus="this.className=\'detailedViewTextBoxOn\'" onblur="this.className=\'detailedViewTextBox\'">';
		for($i=0;$i<$adb->num_rows($result);$i++){
			$columnname=$adb->query_result($result,$i,"columnname");
			if($columnname != ''){
				$prifldarr=explode(":",$columnname);
				$fieldname = $prifldarr[2];
				$priarr=explode("_",$prifldarr[3],2); //getting field label
				//crmv@89011
				if($fieldmodule == 'Documents' && $priarr[0] == 'Notes'){
					$priarr[0] = $fieldmodule;
				}
				//crmv@89011e
				//crmv@34627
				if ($fieldmodule != $priarr[0]) {
					continue;
				}
				//crmv@34627e
				$prifld = str_replace("_"," ",$priarr[1]);
				if($is_admin == false){
					$fld_permission = getFieldVisibilityPermission($fieldmodule,$current_user->id,$fieldname);
				}
				if($fld_permission == 0){
					$field_query = $adb->pquery("SELECT fieldlabel FROM ".$table_prefix."_field WHERE fieldname = ? AND tablename = ? and ".$table_prefix."_field.presence in (0,2)", array($fieldname,$prifldarr[0]));
					$field_label = $adb->query_result($field_query,0,'fieldlabel');
					if(trim($field_label) != '')
						$html .= "<option value='".$columnname."'>".getTranslatedString($field_label, $fieldmodule)."</option>";
				}
			}
		}
		$html .= '</select>';
	}
	echo $html;	
}

if(!empty($_REQUEST['showmaxval']) && !empty($_REQUEST['sid'])){
	$sid=$_REQUEST['sid'];
	$maxval=$_REQUEST['showmaxval'];
	global $adb;
	$query="select stufftype from ".$table_prefix."_homestuff where stuffid=?";
	$res=$adb->pquery($query, array($sid));
	$stufftypename=$adb->query_result($res,0,"stufftype");
	if($stufftypename=="Module"){
		$qry="update ".$table_prefix."_homemodule set maxentries=? where stuffid=?";
		$result=$adb->pquery($qry, array($maxval, $sid));
	}else if($stufftypename=="RSS"){
		$qry="update ".$table_prefix."_homerss set maxentries=? where stuffid=?";
		$result=$adb->pquery($qry, array($maxval, $sid));
	}else if($stufftypename=="Default"){
		$qry="update ".$table_prefix."_homedefault set maxentries=? where stuffid=?";
		$result=$adb->pquery($qry, array($maxval, $sid));
	}
	echo "VTE.Homestuff.loadStuff(".$sid.",'".$stufftypename."')";
}

if(!empty($_REQUEST['dashVal'])){
	$did=$_REQUEST['did'];
	global $adb;
	$qry="update ".$table_prefix."_homedashbd set dashbdtype=? where stuffid=?";	
	$res=$adb->pquery($qry, array($_REQUEST['dashVal'], $did));
	echo "VTE.Homestuff.loadStuff(".$did.",'DashBoard')";
}

if(!empty($_REQUEST['url']) && !empty($_REQUEST['sid'])){
	$sid=$_REQUEST['sid'];
	$url=$_REQUEST['url'];
	global $adb;
	$query="update ".$table_prefix."_homewidget_url set url = ? where widget_id=?";
	$result=$adb->pquery($query, array($url,$sid));
	echo "VTE.Homestuff.loadStuff(".$sid.",'URL')";
}

if(!empty($_REQUEST['homestuffid'])){
	$sid=$_REQUEST['homestuffid'];
	global $adb;
	//crmv@25466
	$sdkiframe = SDK::getHomeIframe($sid);
	if (empty($sdkiframe)) {
		$query="delete from ".$table_prefix."_homestuff where stuffid=?";
		$result=$adb->pquery($query, array($sid));
	} else {
		SDK::unsetHomeIframe($sid);
	}
	//crmv@25466e
	echo "SUCCESS";
}


//Sequencing of blocks starts
if(!empty($_REQUEST['matrixsequence'])){
	global $adb;
	$sequence = explode('_',$_REQUEST['matrixsequence']);
	for($i=count($sequence)-1, $seq=0;$i>=0;$i--, $seq++){
		$query = 'update '.$table_prefix.'_homestuff set stuffsequence=? where stuffid=?';
		$result = $adb->pquery($query, array($seq, $sequence[$i]));
	}
	echo "<table cellpadding='10' cellspacing='0' border='0' width='100%' class='vtResultPop small'><tr><td align='center'>Layout Saved</td></tr></table>";
}
//Sequencing of blocks ends

if(isset($_REQUEST['act']) && $_REQUEST['act'] =="hide"){
	$stuffid=$_REQUEST['stuffid'];
	global $adb,$current_user;
	$qry="update ".$table_prefix."_homestuff set visible=1 where stuffid=?";
	$res=$adb->pquery($qry, array($stuffid));
	echo "SUCCESS";
}

//saving layout here
if(!empty($_REQUEST['layout'])){
	global $adb, $current_user;
	
	$sql = "delete from ".$table_prefix."_home_layout where userid=?";
	$result = $adb->pquery($sql, array($current_user->id));
	
	$sql = "insert into ".$table_prefix."_home_layout values (?, ?)";
	$result = $adb->pquery($sql, array($current_user->id, $_REQUEST['layout']));
	if(!$result){
		echo "SUCCESS";
	}
}
//layout save ends here
?>