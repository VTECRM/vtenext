{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}

{* crmv@98866 *}

<table width="100%" cellpadding="5" cellspacing="0" border="0">

	{if $LABEL.parent_id neq ''}
	{assign var="popup_params" value="&action=Popup&maintab=Calendar&srcmodule=Calendar&forfield=parent_id"}
	<tr>
		<td width="15%" nowrap>
			<b>{$MOD.LBL_RELATEDTO}</b>
		</td>
		<td>
			<table cellspacing="0" cellspacing="0" width="100%">
				<tr>
					<td width="40%" class="dvtCellInfo" style="padding: 0px 5px; vertical-align: middle; text-align: center;">
						{* crmv@129994 *}
						{assign var=fld_value value=$ACTIVITYDATA.parent_id.entityid}
						<input name="parent_id" type="hidden" value="{$fld_value}">
						{* crmv@129994e *}
						<input name="del_actparent_rel" type="hidden">
						<div class="dvtCellInfo">
							<select name="parent_type" class="detailedViewTextBox" id="parent_type" onChange="reloadAutocomplete(document.createTodo.parent_id,document.createTodo.parent_name,'module='+this.value+'{$popup_params}');document.createTodo.parent_name.value='';document.createTodo.parent_id.value='';enableReferenceField(document.createTodo.parent_name);">
								{* crmv@129642 *}
								{foreach item=combo key=id_pa from=$LABEL.parent_id.options}
									{if isset($LABEL.parent_id.selected) && $combo eq $LABEL.parent_id.selected}
										{assign var=selected_v value=' selected'}
									{else}
										{assign var=selected_v value=''}
									{/if}
									<option value="{$combo}"{$selected_v}>{$combo|getTranslatedString:$combo}</option>
								{/foreach}
								{* crmv@129642e *}
							</select>
						</div>
					</td>
					<td width="60%">
						<div class="dvtCellInfo">
							{assign var=fld_displayvalue value=$ACTIVITYDATA.parent_id.displayvalue}
							<div {if $fld_displayvalue|trim eq ''}class="dvtCellInfo" {else}class="dvtCellInfoOff" {/if} style="position: relative">
								{assign var=fld_style value='class="detailedViewTextBox" readonly'} {if $fld_displayvalue|trim eq ''} {assign var=fld_displayvalue value='LBL_SEARCH_STRING'|getTranslatedString} {assign var=fld_style value='class="detailedViewTextBox"'} {/if}
								<input type="text" name="parent_name" id="parent_name" {$fld_style} value="{$fld_displayvalue}">
								<script type="text/javascript">
									reloadAutocomplete('parent_id', 'parent_name', 'module=' + document.createTodo.parent_type.value + '{$popup_params}');
								</script>
							</div>
						</div>
					</td>
				</tr>
			</table>
		</td>
		<td nowrap>
			<i class="vteicon" onclick="openPopup('index.php?module='+document.createTodo.parent_type.value+'{$popup_params}','test','width=640,height=602,resizable=0,scrollbars=0,top=150,left=200');" style="cursor: hand; cursor: pointer;">view_list</i>
			<i class="vteicon" onclick="document.createTodo.del_actparent_rel.value=document.createTodo.parent_id.value;document.createTodo.parent_id.value='';document.createTodo.parent_name.value='';enableReferenceField(document.createTodo.parent_name);" style="cursor: hand; cursor: pointer;">highlight_off</i>
		</td>
	</tr>
	{/if} 
	{if $LABEL.contact_id neq ''}
	<tr>
		<td width="25%" nowrap>
			<b>{$MOD.LBL_CONTACT_NAME}</b>
		</td>
		<td>
			{* crmv@97221 *} 
			{assign var=fld_displayvalue value=$ACTIVITYDATA.contact_id.displayvalue}
			<div {if $fld_displayvalue|trim eq ''}class="dvtCellInfo" {else}class="dvtCellInfoOff" {/if} style="position: relative">
				{assign var=fld_style value='class="detailedViewTextBox" readonly'} {if $fld_displayvalue|trim eq ''} {assign var=fld_displayvalue value='LBL_SEARCH_STRING'|getTranslatedString} {assign var=fld_style value='class="detailedViewTextBox"'} {/if}
				<input name="contact_name" id="contact_name" type="text" {$fld_style} value="{$fld_displayvalue}">
				<input name="contact_id" id="contact_id" type="hidden" value="{$ACTIVITYDATA.contact_id.entityid}">
				<input name="deletecntlist" id="deletecntlist" type="hidden">
				<script type="text/javascript">
					reloadAutocomplete('contact_id', 'contact_name', selectContact('false', 'task', document.createTodo, 'yes'));
				</script>
			</div>
			{* crmv@97221e *}
		</td>
		<td>
			<i class="vteicon" onclick="openPopup('index.php?'+selectContact('false','task',document.createTodo));" style="cursor: hand; cursor: pointer;">view_list</i>
			<i class="vteicon" onclick='document.createTodo.deletecntlist.value=document.createTodo.contact_name.value;document.createTodo.contact_name.value="";document.createTodo.contact_id.value="";enableReferenceField(document.createTodo.contact_name);' style="cursor: hand; cursor: pointer;">highlight_off</i>
		</td>
	</tr>
	{/if}
</table>