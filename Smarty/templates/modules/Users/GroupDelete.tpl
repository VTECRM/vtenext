{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}
{* crmv@104283 *}

{assign var="FLOAT_TITLE" value=$MOD.LBL_DELETE_GROUP}
{assign var="FLOAT_WIDTH" value="450px"}
{capture assign="FLOAT_CONTENT"}
<form name="deleteGroupForm" action="index.php" onsubmit="VteJS_DialogBox.block();">
<input type="hidden" name="module" value="Users">
<input type="hidden" name="action" value="DeleteGroup">
<input type="hidden" name="delete_group_id" value="{$GROUPID}">	

<table border=0 cellspacing=0 cellpadding=5 width=95% align=center>
<tr>
	<td class="small">
	<table border=0 celspacing=0 cellpadding=5 width=100% align=center bgcolor=white>
	<tr>
		<td width="50%" class="cellLabel small"><b>{$MOD.LBL_DELETE_GROUPNAME}</b></td>
		<td width="50%" class="cellText small"><b>{$GROUPNAME}</b></td>
	</tr>
	<tr>
		<td align="left" class="cellLabel small" nowrap><b>{$MOD.LBL_TRANSFER_GROUP}</b></td>
		<td align="left" class="cellText small">

		<input name="assigntype" checked value="U" onclick="toggleAssignType(this.value,'assign_user','assign_team','')" type="radio">&nbsp;User {* crmv@173291 *}
		
		{if count($GROUPS) > 0}
			<input name="assigntype"  value="T" onclick="toggleAssignType(this.value,'assign_user','assign_team','')" type="radio">&nbsp;Group {* crmv@173291 *}
		{/if}
		
		<span id="assign_user" style="display: block;">
		<select class="select" name="transfer_user_id">
		{foreach item=user from=$USERS}
			<option value="{$user.id}">{$user.user_name|truncate:20:"..."}</option>
		{/foreach}
		</select>
		</span>
	
		{if count($GROUPS) > 0}
		<span id="assign_team" style="display: none;">
		<select class="select" name="transfer_group_id">
		{foreach item=group from=$GROUPS}
			<option value="{$group.groupid}">{$group.groupname|truncate:20:"..."}</option>
		{/foreach}
		</select>
		</span>
		{/if}
	
	</td>
	</tr>
	</table>
	</td>
</tr>
</table>
<table border=0 cellspacing=0 cellpadding=5 width=100% class="layerPopupTransport">
<tr>
	<td class="small" align="center"><input type="submit" name="Delete" value="{$APP.LBL_SAVE_BUTTON_LABEL}" class="crmbutton small save">
	</td>
</tr>
</table>
</form>
{/capture}
{include file="FloatingDiv.tpl" FLOAT_ID="DeleteLay" FLOAT_BUTTONS=""}