{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}
{* crmv@30014 *}
{if $sdk_mode eq 'detail'}
	{if $readonly eq 100}
		<td class="dvtCellInfo" align="left" width="25%"></td>
	{else}
		<td class="dvtCellInfo" align="left" width="25%">{$keyval}</td>
	{/if}
{elseif $sdk_mode eq 'edit'}
	<td width="20%" class="dvtCellLabel" align=right>{$fldlabel}</td>
	<td width="30%" align="left" class="dvtCellInfo">
		<input type="hidden" tabindex="{$vt_tab}" name="{$fldname}" id ="{$fldname}" value="{$fldvalue}" class="detailedViewTextBox" />
		{$secondvalue}
	</td>
{/if}