{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}

{* crmv@98866 *}

<table width="100%" cellpadding="5" cellspacing="0" border="0" bgcolor="#FFFFFF">
	{if $LABEL.parent_id neq ''}
	{assign var="popup_params" value="&action=Popup&srcmodule=Calendar&forfield=parent_id"}
	<tr>
		<td width="15%" nowrap><b>{$MOD.LBL_RELATEDTO}</b></td>
		<td>
			<table cellspacing="0" cellspacing="0" width="100%">
				<tr>
					<td width="30%" class="dvtCellInfo" style="padding:0px 5px;vertical-align:middle;text-align:center;">
						<input name="parent_id" type="hidden" value="{$ACTIVITYDATA.parent_id.entityid}">
						<input name="del_actparent_rel" type="hidden" >
						<select name="parent_type" class="detailedViewTextBox" id="parent_type" onChange="reloadAutocomplete('parent_id','parent_name','module='+this.value+'{$popup_params}');document.EditView.parent_name.value='';document.EditView.parent_id.value='';enableReferenceField(document.EditView.parent_name);">
						{foreach item=combo key=id_pa from=$LABEL.parent_id.options}
							{if $LABEL.parent_id.selected eq $combo} {* crmv@204731 *}
								{assign var=selected_v value='selected'}
							{else}
								{assign var=selected_v value=''}
							{/if}
							<option value="{$combo}" {$selected_v}>{$combo|getTranslatedString:$combo}</option>
						{/foreach}
						</select>
					</td>
					<td width="70%">
						{assign var=fld_displayvalue value=$ACTIVITYDATA.parent_id.displayvalue}
						<div {if $fld_displayvalue|trim eq ''}class="dvtCellInfo"{else}class="dvtCellInfoOff"{/if}>
							{assign var=fld_style value='class="detailedViewTextBox" readonly'}
							{if $fld_displayvalue|trim eq ''}
								{assign var=fld_displayvalue value='LBL_SEARCH_STRING'|getTranslatedString}
								{assign var=fld_style value='class="detailedViewTextBox"'}
							{/if}
							<input type="text" class="detailedViewTextBox" name="parent_name" id="parent_name" {$fld_style} value="{$fld_displayvalue}">
							<script type="text/javascript">
								reloadAutocomplete(document.EditView.parent_id,document.EditView.parent_name,'module='+document.EditView.parent_type.value+'{$popup_params}');
							</script>
						</div>
					</td>
				</tr>
			</table>
		</td>
		<td nowrap>
			<i class="vteicon" onclick="openPopup('index.php?module='+document.EditView.parent_type.value+'{$popup_params}','test','width=640,height=602,resizable=0,scrollbars=0,top=150,left=200');" style="cursor:hand;cursor:pointer;">view_list</i>
			<i class="vteicon" onclick="document.EditView.del_actparent_rel.value=document.EditView.parent_id.value;document.EditView.parent_id.value='';document.EditView.parent_name.value='';enableReferenceField(document.EditView.parent_name);" style="cursor:hand;cursor:pointer;">highlight_off</i>
		</td>
	</tr>
	{/if}
	<tr valign="top">
		<td><b>{$APP.Contacts}</b></td>
		<td>
			<table cellpadding="0" cellspacing="0" width="100%">
				<tr valign="top">
					<td>
						<div class="dvtCellInfo">
							<input type="text" id="multi_contact_autocomplete" class="detailedViewTextBox" value="{'LBL_SEARCH_STRING'|getTranslatedString}">
						</div>
					</td>
				</tr>
				<tr valign="top">
					<td style="padding-top: 5px;">
						<script type="text/javascript">
							var empty_search_str = "{'LBL_SEARCH_STRING'|getTranslatedString}";
							initMultiContactAutocomplete('multi_contact_autocomplete','addEventUI',encodeURIComponent('module=Contacts&action=Popup&html=Popup_picker&form=EditView&return_module=Calendar&select=enable&popuptype=detailview&form_submit=false'));
						</script>
						<input name="contactidlist" id="contactidlist" value="{$CONTACTSID}" type="hidden">
						<input name="deletecntlist" id="deletecntlist" type="hidden">
						<div class="dvtCellInfo">
							<select name="contactlist" id="parentid" class="detailedViewTextBox" multiple>{$CONTACTSNAME}</select>
						</div>
					</td>
				</tr>
			</table>
		</td>
		<td align="left" width="20px">
			<i class="vteicon" onclick="openPopup('index.php?'+selectContact('true','general',document.EditView));" style="cursor:hand;cursor:pointer;">view_list</i>
			<i class="vteicon" onclick="removeActContacts();" style="cursor:hand;cursor:pointer;">highlight_off</i>
		</td>
	</tr>
</table>