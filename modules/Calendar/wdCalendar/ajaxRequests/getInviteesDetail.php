<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@26807	crmv@29190	crmv@59091	crmv@OPER6317 */

$border = 'border:1px solid #999999;';
$button_width = '90%';
$lateral_td_width = '43%';
$center_td_width = '14%';

$inputSearch = '<input id="bbit-cal-txtSearch" name="bbit-cal-txtSearch" class="textbox-fill-input detailedViewTextBox" style="width:50%;padding-left:5px;" value="'.getTranslatedString('LBL_SEARCH_STRING').'">';
$imgSearch = '<img id="imgSearch" src="themes/softed/images/clear_field.gif" onclick="resetSearch(\'bbit-cal-txtSearch\');" alt="'.$mod_strings["LBL_CLEAR"].'" title="'.$mod_strings["LBL_CLEAR"].'" align="absmiddle" style="cursor:hand;cursor:pointer">';
$imgPopup = '<img onclick="javascript:inviteesPopup(\'selectedTable\',\'quick_parent_type\')" src="themes/'.$theme.'/images/select.gif" alt="'.$mod_strings['LBL_SELECT'].'" title="'.$mod_strings['LBL_SELECT'].'" align="absmiddle" style="cursor:hand;cursor:pointer;">';	//crmv@26961

$quick_parent_type = '<input onclick="showInv(\'quick_parent_type_tab\',this.id)" type="button" id="quick_parent_type" name="Users" class="picklistButton crmbutton small edit" style="white-space:nowrap;overflow:hidden;width:139px;text-transform:none;" value="'.getTranslatedString('Users','Users').'">';
$modulesSelectable = array('Users','Contacts');

$quick_parent_type .= '<table id="quick_parent_type_tab" class="picklistTab" style="cursor:hand;cursor:pointer;display:none;position:absolute;overflow:hidden;background-color:white;border:1px solid #999999;width:139px;z-index:101;">';	//crmv@26921	//crmv@26935
foreach ($modulesSelectable as $k => $value) {
	$quick_parent_type .= '<tr id="'.$value.'" onmouseover="onMouseOverButton(this.id)" onmouseout="onMouseOutButton(this.id)" onclick="searchTabClick(this.id,\''.getTranslatedString($value).'\')""><td style="width:139px;padding:10px 20px">'.getTranslatedString($value).'</td></tr>';	//crmv@26921
}
$quick_parent_type .= '</table>';
//
//$modulesSelectable = array('Users','Contacts');
//$quick_parent_type = '<select class="detailedViewTextBox">';
//foreach ($modulesSelectable as $k => $value) {
//	$quick_parent_type .= '<option value="'.$value.'" onclick="searchTabClick(this.id,\''.getTranslatedString($value).'\')"">'.getTranslatedString($value).'</option>';
//}
//$quick_parent_type .= '</select';

$userDetails = get_user_array(false);
$availableTable = '<table id="availableTable" class="small" border=0 cellspacing=1 cellpadding=0 style="width:100%;cursor:hand;cursor:pointer">';
// foreach($userDetails as $id=>$name){
// 	if($id != '') {
// 		$availableTable .= '<tr id="'.$id.'" onclick="checkTr(this.id)"><td align="center" style="display:none;"><input type="checkbox" value="'.$id.'"></td><td nowrap align="left" class="parent_name" style="width:100%">'.$name.'</td></tr>';
// 	}
// }
$availableTable .= '</table>';

$selectedTable = '<table id="selectedTable" class="small" border=0 cellspacing=1 cellpadding=0 style="width:100%;cursor:hand;cursor:pointer"></table>';

$html_list = '
<table style="width:100%;border-spacing:0px;">
	<tr>
		<td align="left" colspan="3" style="width:100%;" nowrap>
			'.$quick_parent_type.'
			'.$inputSearch.'
			'.$imgPopup.'
			'.$imgSearch.'
		</td>
	</tr>
</table>
-|@##@|-
<td align="center" style="width:'.$lateral_td_width.';padding-right:10px;box-sizing:border-box;" valign="middle">
	<div id="availableDestDiv" name="availableDest" style="overflow-y:auto;overflow-x:hidden;height:50px;width:100%;'.$border.';box-sizing:border-box;" class="small">
		'.$availableTable.'
	</div>
</td>
<td align="center" style="width:'.$center_td_width.'" valign="middle">
	<input type="button" onclick="incDest(\'availableDestDiv\',\'selectedTable\',this.value)" style="width:'.$button_width.'" class="crmbutton small edit" value="&gt;&gt;">
	<input type="button" onclick="rmvDest(\'selectedDestDiv\')" style="width:'.$button_width.'" class="crmbutton small cancel" value="&lt;&lt;">
</td>
<td align="center" style="width:'.$lateral_td_width.';padding-left:10px;box-sizing:border-box;" valign="middle">
	<div id="selectedDestDiv" name="selectedDest" style="overflow-y:auto;overflow-x:hidden;height:50px;width:100%;'.$border.';box-sizing:border-box;" class="small">
		'.$selectedTable.'
	</div>
</td>
';
echo $html_list.'-|@##@|-'.getTranslatedString('LBL_SEARCH_STRING');
?>