{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}
{* crmv@101930 *}
{if $sdk_mode eq 'detail'}
<table cellpadding="0" cellspacing="0" width="100%"  class="small">
	<tr>
		<td style="width:32px;padding: 5px;">
			<img src="{$keyoptions.avatar}" alt="{$keyoptions.ownername}" title="{$keyoptions.ownername}" class="userAvatar" />
		</td>
		<td>
			<div valign="top" style="position: relative;">
				{$keyoptions.ownername}
			</div>
			<div valign="top">
				<span title="{$keyval}" style="color: gray; text-decoration: none;">{$keyoptions.friendlytime}</span>
			</div>
		</td>
	</tr>
</table>
{/if}