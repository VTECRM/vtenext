{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
{* crmv@100731 *}
{foreach item=RESOURCE from=$RESOURCES}
	<span id="ModCommentsUsers_list_{$RESOURCE.id}" class="addrBubble" style="cursor:pointer" title="{$RESOURCE.alt}">
		<table cellpadding="3" cellspacing="0" class="small">
		<tr>
			<td rowspan="2"><img src="{$RESOURCE.img}" class="userAvatar" /></td>
			<td>{$RESOURCE.fullname}</td>
		</tr>
		<tr>
			<td>{$RESOURCE.name}</td>
		</tr>
		</table>
	</span>
{/foreach}