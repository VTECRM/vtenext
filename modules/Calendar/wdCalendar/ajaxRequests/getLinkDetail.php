<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@26807	crmv@29190	crmv@59091	crmv@OPER6317 */

$inputWidth = '177px';	//crmv@26941
if ($_REQUEST['linktype'] == 'LinkDetails') {
	$userDetails = get_user_array(false);
	echo '<div id="link_div" class="textbox-fill-wrapper" style="width:100%;">';
	echo '	<table style="width:100%;"><tr>';
	echo '		<td width="14%" valign="top">';
	echo '			<input id="parent_id_link" type="hidden">';
	$relatedto = getCalendarRelatedToModules();
	echo '			<div class="textbox-fill-mid">';
	echo '				<input type="button" class="picklistButton crmbutton small edit" id="parent_type_link" name="'.$relatedto[0].'" style="white-space:nowrap;overflow:hidden;width:150px;text-transform:none;" value="'.getTranslatedString($relatedto[0],$relatedto[0]).'">';
	echo '				<table id="link_tab" class="picklistTab" style="cursor:hand;cursor:pointer;display:none;top:10px;position:absolute;overflow:hidden;background-color:white;border:1px solid #999999;z-index:101">';	//crmv@26921	//crmv@26935
							foreach($relatedto as $mod) {
								if(vtlib_isModuleActive($mod) && isPermitted($mod,'DetailView') == 'yes') {
									echo '<tr id="'.$mod.'" onmouseover="onMouseOverButton(this.id)" onmouseout="onMouseOutButton(this.id)" onclick="javascript:linkTabClick(\''.$mod.'\',\''.getTranslatedString($mod,$mod).'\');"><td style="padding:10px 20px;">'.getTranslatedString($mod,$mod).'</td></tr>';
								}
							}
	echo '				</table>';
	echo '			</div>';
	echo '		</td>';
	echo '		<td width="70%">';
	echo '			<input type="text" id="selectparent_link" class="detailedViewTextBox" style="white-space:nowrap;overflow:hidden;width:'.$inputWidth.';padding-left:5px;border:0px;" value="'.getTranslatedString('LBL_SEARCH_STRING').'">';	//crmv@26941
	echo '		</td>';
	echo '		<td width="8%">';
	echo '			<img onclick="javascript:linkClick(\'parent_type_link\')" src="themes/'.$theme.'/images/select.gif" alt="'.$mod_strings['LBL_SELECT'].'" title="'.$mod_strings['LBL_SELECT'].'" align="absmiddle" style="cursor:hand;cursor:pointer;">';
	echo '		</td>';
	echo '		<td width="8%">';
	echo '			<img onclick="javascript:clearLink(\'parent_id_link\',\'selectparent_link\');enableReferenceField(jQuery(\'#selectparent_link\'));" src="themes/'.$theme.'/images/clear_field.gif" alt="'.$mod_strings['LBL_CLEAR'].'" title="'.$mod_strings['LBL_CLEAR'].'" align="absmiddle" style="cursor:hand;cursor:pointer;">';
	echo '		</td>';
	echo '	</tr></table>';
}
else if ($_REQUEST['linktype'] == 'SingleContactDetails') {
	$userDetails = get_user_array(false);
	echo '<div id="link_div" class="textbox-fill-wrapper" style="width:100%;">';
	echo '	<table style="width:100%;"><tr>';
	echo '		<td width="14%" valign="top">';
	echo '			<input id="parent_id_link_singleContact" type="hidden">';
	echo '			<div class="textbox-fill-mid">';
	echo '				<span class="small" id="parent_type_link_singleContact">'.getTranslatedString('Contacts','Contacts').'</span>';
	echo '			</div>';
	echo '		</td>';
	echo '		<td width="70%">';
	echo '			<input type="text" id="selectparent_link_singleContact" class="detailedViewTextBox" style="white-space:nowrap;overflow:hidden;width:'.$inputWidth.';padding-left:5px;border:0px;" value="'.getTranslatedString('LBL_SEARCH_STRING').'">';	//crmv@26941
	echo '		</td>';
	echo '		<td width="8%" valign="top">';
	echo '			<img onclick="javascript:linkClick(\'parent_type_link_singleContact\')" src="themes/'.$theme.'/images/select.gif" alt="'.$mod_strings['LBL_SELECT'].'" title="'.$mod_strings['LBL_SELECT'].'" align="absmiddle" style="cursor:hand;cursor:pointer;">';
	echo '		</td>';
	echo '		<td width="8%" valign="top">';
	echo '			<img onclick="javascript:clearLink(\'parent_id_link_singleContact\',\'selectparent_link_singleContact\');enableReferenceField(jQuery(\'#selectparent_link_singleContact\'));" src="themes/'.$theme.'/images/clear_field.gif" alt="'.$mod_strings['LBL_CLEAR'].'" title="'.$mod_strings['LBL_CLEAR'].'" align="absmiddle" style="cursor:hand;cursor:pointer;">';
	echo '		</td>';
	echo '	</tr></table>';
}
else if ($_REQUEST['linktype'] == 'ContactsDetails') {
	$userDetails = get_user_array(false);
	echo '<div id="link_div" class="textbox-fill-wrapper" style="width:100%;">';
	echo '	<table style="width:100%;"><tr>';
	echo '		<td width="14%" valign="top">';
	echo '			<input id="parent_id_link_contacts" type="hidden">';
	echo '			<div class="textbox-fill-mid">';
	echo '				<span class="small" id="parent_type_link_contacts">'.getTranslatedString('Contacts','Contacts').'</span>';
	echo '			</div>';
	echo '		</td>';
	echo '		<td width="70%">';
	
	$userDetails = get_user_array(false);
	echo '			<table cellpadding="0" cellspacing="0" width="100%">
						<tr><td>
							<input type="text" id="multi_contact_autocomplete_wd" class="detailedViewTextBox" style="width:100%;padding-left:5px;border:0px;" value="'.getTranslatedString('LBL_SEARCH_STRING').'">
						</td></tr>
						<tr valign="bottom">
							<td style="padding-top: 5px;" nowrap>
								<div id="contacts_div" class="detailedViewTextBox" style="white-space:nowrap;width:'.$inputWidth.';height:50px;overflow-y:auto;overflow-x:hidden;border:1px solid #999999;width:100%;box-sizing:border-box;">
									<table style="width:100%"></table>
								</div>
							</td>
							<td align="right">
								<img onclick="javascript:linkClickContacts(\'contacts_div\')" src="themes/'.$theme.'/images/select.gif" alt="'.$mod_strings['LBL_SELECT'].'" title="'.$mod_strings['LBL_SELECT'].'" align="absmiddle" style="cursor:hand;cursor:pointer;">
								<img onclick="javascript:clearTrLinks()" src="themes/'.$theme.'/images/clear_field.gif" alt="'.$mod_strings['LBL_CLEAR'].'" title="'.$mod_strings['LBL_CLEAR'].'" align="absmiddle" style="cursor:hand;cursor:pointer;">
							</td>
						</tr>
					</table>
				</td>
			</tr></table>';
}
?>