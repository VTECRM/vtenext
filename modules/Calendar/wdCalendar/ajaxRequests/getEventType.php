<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
//crmv@26807	crmv@OPER6317
//echo getActFieldCombo('activitytype','vte_activitytype');

echo '			<input id="activitytype" type="hidden">';
echo '			<div class="textbox-fill-mid">';
   	    	      		getEnetTypeTr('activitytype',$table_prefix.'_activitytype');
echo '			</div>';

function getEnetTypeTr($fieldname,$tablename)
{
	global $adb, $mod_strings,$current_user,$table_prefix;
	require('user_privileges/user_privileges_'.$current_user->id.'.php');
	$roleid=$current_user->roleid;
	$subrole = getRoleSubordinates($roleid);
	if(count($subrole)> 0)
	{
		$roleids = $subrole;
		array_push($roleids, $roleid);
	}
	else
	{	
		$roleids = $roleid;
	}
	$pick_query="select $fieldname from ".$table_prefix."_$fieldname inner join ".$table_prefix."_role2picklist on ".$table_prefix."_role2picklist.picklistvalueid = ".$table_prefix."_$fieldname.picklist_valueid and roleid = ? ";
	$params = array($roleid);
	$pick_query.=" order by sortid asc "; //crmv@32334
	$Res = $adb->pquery($pick_query,$params);
	$noofrows = $adb->num_rows($Res);
	$first_value = $adb->query_result($Res,0,$fieldname);
	
	echo '<input type="button" id="activitytype_show_button" name="'.$first_value.'" class="picklistButton crmbutton small edit" style="white-space:nowrap;overflow:hidden;width:200px;text-transform:none;" value="'.getTranslatedString($first_value).'">';
	echo '<table id="activitytype_tab" class="picklistTab" style="cursor:hand;cursor:pointer;display:none;top:120px;position:absolute;overflow:hidden;background-color:white;border:1px solid #999999;width:200px;z-index:101">';	//crmv@26921	//crmv@26935
	
	for($i = 0; $i < $noofrows; $i++) {
		$value = $adb->query_result($Res,$i,$fieldname);
		echo "<tr id=\"".$i."_activitytype\" onmouseover=\"onMouseOverButton(this.id)\" onmouseout=\"onMouseOutButton(this.id)\"  onclick=\"javascript:activitytypeTabClick('".$value."','".getTranslatedString($value)."');\"><td style=\"padding:10px 20px;width:200px\" nowrap>".getTranslatedString($value)."</td></tr>"; //crmv@26921     
	}
	echo '</table>';
}
//crmv@26807e	crmv@OPER6317e
?>