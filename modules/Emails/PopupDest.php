<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

global $small_page_title, $small_page_title;
$small_page_title = getTranslatedString('LBL_INVITEE','Emails');
$small_page_buttons = '
<table align="center" border="0" cellpadding="0" cellspacing="0" width="100%">
<tr>
	<td width="100%" style="padding:5px"></td>
 	<td align="right" style="padding: 5px;" nowrap>
 		<input type="button" onclick="javascript:closePopup();" class="crmbutton small cancel" value="'.getTranslatedString('LBL_CANCEL_BUTTON_LABEL').'">
		<input type="button" onclick="javascript:addInvitee();" class="crmbutton small save" value="'.getTranslatedString('LBL_ADD_NEW').'">
 	</td>
 </tr>
 </table>
';
include('themes/SmallHeader.php');

global $mod_strings, $app_strings, $theme;

$modulesSelectable = array('Contacts','Accounts','Vendors','Leads','Users');
foreach ($modulesSelectable as $k => $value) {
	$parent_type .= '<option value="'.$value.'">'.getTranslatedString($value,$value).'</option>';
}

$inputSearch = $app_strings['LBL_SEARCH_FOR'].': <input id="txtSearch" name="txtSearch" class="txtBox1" style="width:25%" value="">';
$imgSearch = '<img id="imgSearch" src="'.resourcever('clear_field.gif').'" alt="'.$mod_strings["LBL_CLEAR"].'" title="'.$mod_strings["LBL_CLEAR"].'" align="absmiddle" style="cursor:hand;cursor:pointer">';

$availableTable = '<table id="availableTable" class="small" border=0 cellspacing=0 cellpadding=0 style="width:100%;cursor:hand;cursor:pointer"></table>';

$title_height = '16px';
$tab_height = '94px';
$border = 'border:1px solid #DDDDDD;';
$border_left = 'border-left:1px solid #DDDDDD;';
$border_right = 'border-right:1px solid #DDDDDD;';

$button_width = '80%';
$lateral_td_width = '40%';
$center_td_width = '20%';

$selectedDivs = '
			<div id="selectedDestDivTo" style="height:110px;'.$border.'" align="left">
				<div style="height:'.$title_height.';">
					<b>'.$mod_strings["LBL_TO"].'</b>
				</div>
				<div id="selected1" style="overflow:auto;height:'.$tab_height.';">
					<table id="selectedDestTabTo" class="small" style="width:100%;cursor:hand;cursor:pointer" border=0 cellspacing=0 cellpadding=0 ></table>
				</div>
			</div>
			<div id="selectedDestDivCc" style="height:110px;'.$border_left.$border_right.'" align="left">
				<div style="height:'.$title_height.';">
					<b>'.$mod_strings["LBL_CC"].'</b>
				</div>
				<div id="selected2" style="overflow:auto;height:'.$tab_height.';">
					<table id="selectedDestTabCc" class="small" style="width:100%;cursor:hand;cursor:pointer" border=0 cellspacing=0 cellpadding=0 ></table>
				</div>
			</div>
			<div id="selectedDestDivBcc" style="height:110px;'.$border.'" align="left">
				<div style="height:'.$title_height.';">
					<b>'.$mod_strings["LBL_BCC"].'</b>
				</div>
				<div id="selected3" style="overflow:auto;height:'.$tab_height.';">
					<table id="selectedDestTabBcc" class="small" style="width:100%;cursor:hand;cursor:pointer" border=0 cellspacing=0 cellpadding=0 ></table>
				</div>
			</div>
';

$header = '
	<script language="javascript" type="text/javascript" src="include/js/jquery_plugins/slimscroll/jquery.slimscroll.min.js"></script>
	<link href="include/js/jquery_plugins/mCustomScrollbar/VTE.mCustomScrollbar.css" rel="stylesheet" type="text/css" />
	<script language="javascript" type="text/javascript" src="'.resourcever('modules/Emails/Emails.js').'"></script>
	<link href="themes/'.$theme.'/style.css" type="text/css" rel="stylesheet">
	<link rel="stylesheet" href="include/js/jquery_plugins/ui/themes/demos.css">
';

$html = '
	<table style="height:80%;width:100%" border=0 cellspacing=8 class="small">
		<tr style="height:10%;width:100%">
			<td align="left" colspan="3" style="width:100%;">
				'.$inputSearch.'
				'.$imgSearch.'
				&nbsp;&nbsp;
				<select id="parent_type" name="parent_type">
					<option value="all">'.$app_strings['LBL_ALL'].'</option>
					'.$parent_type.'
				</select>
			</td>
		</tr>
		<tr style="height:10%;width:100%">
			<td align="left" style="width:'.$lateral_td_width.'">
				<input type="button" onclick="CheckAllMails(\'availableTable\',true)" class="crmbutton small edit" value="'.$app_strings["LBL_SELECT_ALL"].'">
				<input type="button" onclick="CheckAllMails(\'availableTable\',false)" class="crmbutton small cancel" value="'.$app_strings["LBL_UNSELECT_ALL"].'">
			</td>
			<td style="width:'.$center_td_width.'"></td>
			<td style="width:'.$lateral_td_width.'"></td>
		</tr>
		<tr style="height:90%;width:100%">
			<td align="center" style="width:'.$lateral_td_width.'" valign="top">
				<div style="height:332px;'.$border.'">
					<div id="availableDest" name="availableDest" style="overflow:auto;height:332px;width:100%;" class="small">
						'.$availableTable.'
					</div>
				</div>
			</td>
			<td align="center" style="width:'.$center_td_width.'" valign="top">
				<input type="button" onclick="incDest(\'availableDest\',\'selectedDestTabTo\',this.value)" style="width:'.$button_width.'" class="crmbutton small edit" value="'.$mod_strings["LBL_TO"].' &gt;&gt;">
				<br><br>
				<input type="button" onclick="incDest(\'availableDest\',\'selectedDestTabCc\',this.value)" style="width:'.$button_width.'" class="crmbutton small edit" value="'.$mod_strings["LBL_CC"].' &gt;&gt;">
				<br><br>
				<input type="button" onclick="incDest(\'availableDest\',\'selectedDestTabBcc\',this.value)" style="width:'.$button_width.'" class="crmbutton small edit" value="'.$mod_strings["LBL_BCC"].' &gt;&gt;">
				<br><br>
				<input type="button" onclick="rmvDest(\'selectedDest\')" style="width:'.$button_width.'" class="crmbutton small cancel" value="&lt;&lt; '.$mod_strings["LBL_REMOVE"].' ">
			</td>
			<td align="center" style="width:'.$lateral_td_width.'" valign="top">
				<div id="selectedDest" name="selectedDest" style="overflow:auto;height:339px;width:100%;" class="small">
					'.$selectedDivs.'
				</div>
			</td>
		</tr>
	</table>
';
echo $header.$html;
?>
<script type="text/javascript">
	var alert_arr = new Array();
	jQuery(document).ready(function() {
		popupDestReady();
	});
</script>
<?php
include('themes/SmallFooter.php');
?>