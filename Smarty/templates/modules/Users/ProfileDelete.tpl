{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}
{* crmv@104283 *}

{assign var="FLOAT_TITLE" value=$MOD.LBL_DELETE_PROFILE}
{assign var="FLOAT_WIDTH" value="450px"}
{capture assign="FLOAT_CONTENT"}
<form name="newProfileForm" action="index.php" onsubmit="VteJS_DialogBox.block();">
<input type="hidden" name="module" value="Users">
<input type="hidden" name="action" value="DeleteProfile">
<input type="hidden" name="delete_prof_id" value="{$PROFILEID}">	
<table border=0 cellspacing=0 cellpadding=5 width=95% align=center> 
<tr>
	<td class="small">
	<table border=0 celspacing=0 cellpadding=5 width=100% align=center bgcolor=white>
	<tr>
		<td width="50%" class="cellLabel small"><b>{$MOD.LBL_PROFILE_TO_BE_DELETED}</b></td>
		<td width="50%" class="cellText small"><b>{$PROFILENAME}</b></td>
	</tr>
	<tr>
		<td align="left" class="cellLabel small" nowrap><b>{$MOD.LBL_TRANSFER_ROLES_TO_PROFILE}</b></td>
		<td align="left" class="cellText small">
		
		<select class="select" name="transfer_prof_id">
		{foreach item=profile from=$PROFILES}
			<option value="{$profile.profileid}">{$profile.profilename|truncate:20:"..."}</option>
		{/foreach}
		</select>
		</td>
	</tr>
	</table>
	</td>
</tr>
</table>
<table border=0 cellspacing=0 cellpadding=5 width=100% class="layerPopupTransport">
<tr>
	<td align=center class="small">
	<input type="submit" name="Delete" value="{$APP.LBL_SAVE_BUTTON_LABEL}" class="crmButton small save">
	</td>
</tr>
</table>
</form>
{/capture}
{include file="FloatingDiv.tpl" FLOAT_ID="DeleteLay" FLOAT_BUTTONS=""}