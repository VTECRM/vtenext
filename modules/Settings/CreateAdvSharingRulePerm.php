<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

$output='';
$userid = $_REQUEST['userid'];
$sharing_module=$_REQUEST['sharing_module'];
$tabid=getTabid($sharing_module);
$combovalues='';

global $mod_strings;
global $app_strings;
global $theme;
$theme_path="themes/".$theme."/";
$image_path=$theme_path."images/";
global $adb;
$shareid=$_REQUEST['shareid'];
$mode = $_REQUEST['mode'];
$entity_id = $_REQUEST['userid'];
if (!isset($_REQUEST['entity'])) $entity='Users';
	else $entity = $_REQUEST['entity'];
if($mode == 'create')
{
	$shareInfo=getAllAdvSharingRulePerm($sharing_module,$entity_id);
	if (is_array($shareInfo)){
		foreach($shareInfo as $details)
		{
			$fromtype=$details[1];
			$fromid=$details[0];
			$fromComboValues .='<option value="'.$fromid.'">'.$fromtype.'</option>';
		}
	}
	else {
		echo "false";
		die;
	}
}
elseif($mode == 'edit')
{
	$shareInfo=getAdvSharingRulePerm($shareid,$entity,$entity_id);	
	//constructing the from combo values
	$fromtype=$shareInfo[2];
	$fromid=$shareInfo[0];
	$fromComboValues='<option value="'.$fromid.'">'.$fromtype.'</option>';
}



$relatedmodule='';	
$relatedlistscombo='';
$relatedModuleSharingArr=getRelatedAdvSharingModules($tabid);
$size=sizeof($relatedModuleSharingArr);
if($size > 0)
{
	if($mode=='edit')
	{
		$relatedModuleSharingPerrArr=getRelatedModuleAdvSharingPerm($shareid,$entity,$entity_id);
	}
	foreach($relatedModuleSharingArr as $reltabid=>$relmode_shareid)
	{
		$rel_module=getTabModuleName($reltabid);
		$relatedmodule .=$rel_module.'###'; 						
	}
	foreach($relatedModuleSharingArr as $reltabid=>$relmode_shareid)
	{
		$ro_selected='';
		$rw_selected='';
		$rel_module=getTabModuleName($reltabid);
		if($mode=='create')
		{
			$ro_selected='selected';
		}
		elseif($mode=='edit')
		{
			$perr=$relatedModuleSharingPerrArr[$reltabid];
			if($perr == 0)
			{
				$ro_selected='selected';
			}
			elseif($perr == 1)
			{
				$rw_selected='selected';
			}
		}	

		$relatedlistscombo.='<tr><td align="right" nowrap style="padding-right:10px;"><b>'.$app_strings[$rel_module].' :</b></td>
			<td width="70%">';
		$relatedlistscombo.='<select id="'.$rel_module.'_accessopt" name="'.$rel_module.'_accessopt" >
			<option value="0" '.$ro_selected.' >'.$mod_strings["Read Only "].'</option>
			<option value="1" '.$rw_selected.' >'.$mod_strings["Read/Write"].'</option>
			</select></td></tr>';


	}
}


if($mode == 'create')
{
	$sharPerCombo = '<option value="0" selected>'.$mod_strings["Read Only "].'</option>';
        $sharPerCombo .= '<option value="1">'.$mod_strings["Read/Write"].'</option>';
}
elseif($mode == 'edit')
{
	$selected1='';
	$selected2='';
	if($shareInfo[4] == 0)
	{
		$selected1='selected';
	}
	elseif($shareInfo[4] == 1)
	{
		$selected2='selected';
	}

	$sharPerCombo = '<option value="0" '.$selected1.'>'.$mod_strings["Read Only "].'</option>';
        $sharPerCombo .= '<option value="1" '.$selected2.'>'.$mod_strings["Read/Write"].'</option>';	
}

//crmv@167234	//crmv@171581 
$output.='<div id="sharingRule"><form name="newGroupForm" action="index.php" method="post">
<input type="hidden" name="__csrf_token" value="'.RequestHandler::getCSRFToken().'">
<input type="hidden" name="module" value="Settings">
<input type="hidden" name="parenttab" value="Settings">	
<input type="hidden" name="action" value="SaveAdvSharingRulePerm">
<input type="hidden" name="sharing_module" value="'.$sharing_module.'">
<input type="hidden" name="shareId" value="'.$shareid.'">
<input type="hidden" name="mode" value="'.$mode.'">
<input type="hidden" name="record" value="'.$userid.'">
<input type="hidden" id="rel_module_lists" name="rel_module_lists" value="'.$relatedmodule.'">

<table border=0 cellspacing=0 cellpadding=5 width=95% align=center> 
<tr>
	<td class="small">
	<table border=0 celspacing=0 cellpadding=5 width=100% align=center bgcolor=white>
	<tr>
		<td><b> '.$mod_strings['LBL_STEP'].' 1 : '.$mod_strings['LBL_SELECT_FILTER'].' </b></td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td>';
//combovalues
		
		$output .= '<div class="dvtCellInfo">';
		$output.='<select class="detailedViewTextBox" id="'.$app_strings[$sharing_module].'_share" name="'.$sharing_module.'_share">'.$fromComboValues.'</select>';	
		$output .= '</div>';
		$output.='</td>

		<td>&nbsp;</td>
	</tr>
	<tr>

		<td style="text-align:left;"><b>'.$mod_strings['LBL_STEP'].' 2 : '.$mod_strings['LBL_SELECT_PERM'].' </b></td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td>
				<div class="dvtCellInfo">
		<select	class="detailedViewTextBox" id="share_memberType" name="share_memberType">';
		$output .= $sharPerCombo;
		$output .= '</select></div>

		</td>
	</tr>
	<tr style="display:none;">
		<td style="text-align:left;"><b>'.$mod_strings['LBL_STEP'].' 3 : '.$mod_strings['LBL_ACCESS_RIGHTS_FOR_MODULES'].' </b></td>
		<td>&nbsp;</td>

	</tr>
	<tr style="display:none;">
		<td style="padding-left:20px;text-align:left;">
		<table width="75%"  border="0" cellspacing="0" cellpadding="0">';

		$output .=$relatedlistscombo.'</table>
		</td>
		<td>&nbsp;</td>
	</tr>
	<tr style="display:none;"><td colspan="2" align="left">&nbsp;</td></tr>
	<tr style="display:none;">
		<td colspan="2" class="dvInnerHeader"><b>'.$mod_strings['LBL_RULE_CONSTRUCTION'].'</b></td>

	</tr>
	<tr style="display:none;">
		<td  style="white-space:normal;padding:10px 0px" colspan="2" id="rules">&nbsp;
	</td>
	</tr>
	<tr style="display:none;">
		<td style="white-space:normal;" colspan="2" id="relrules">&nbsp;
		</td>
	</tr>
	</table>
	</td>
</tr>
</table>
<table border=0 cellspacing=0 cellpadding=5 width=100% class="layerPopupTransport">
	<tr>
		<td colspan="2" align="center">
		<input type="submit" class="crmButton small save" name="add" value="'.$mod_strings['LBL_ADD_RULE'].'">&nbsp;&nbsp;
	</td>
	</tr>
</table>';
//crmv@167234e

$output.='</form></div>';

// crmv@104853
if ($sharing_module == 'Accounts') {
	$display_module = $app_strings['Accounts'] . ' & ' . $app_strings['Contacts'];
} else {
	$display_module = $app_strings[$sharing_module];
}
$display_module .= ' - ';
if ($mode == 'edit') {
	$display_module .= $mod_strings['LBL_EDIT_CUSTOM_RULE'];
} else {
	$display_module .= $mod_strings['LBL_ADD_CUSTOM_RULE'];
}

$display_module = Zend_Json::encode($display_module);

$output .= '<script type="text/javascript">
	var titleObj = jQuery("#tempdiv3_Handle_Title");
	titleObj.html("<strong>"+'.$display_module.'+"</strong>");
	titleObj.addClass("layerPopupHeading");
</script>';
// crmv@104853e

echo $output;
?>