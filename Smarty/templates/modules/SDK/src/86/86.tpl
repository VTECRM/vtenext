{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}
{if $sdk_mode eq 'detail'}
	{if $keyreadonly eq 99}
		{include file="FieldHeader.tpl" uitype=$keyid mandatory=$keymandatory label=$label}
		<div class="{$DIVCLASSOTHER}dvtCellInfoOff detailCellInfo ">
			<img src="modules/SDK/src/86/img/walistico.png" align="left" alt="Whatsapp" title="Whatsapp" style="width:15x;height:15px;margin-top:3px;margin-right:3px;" />
				{if $keyval neq ''}
					<a target="_blank" href="https://wa.me/39{$keyval}">{$keyval}</a>
				{/if}
		</div>
	{else}
		{include file="FieldHeader.tpl" uitype=$keyid mandatory=$keymandatory label=$label}
		<div class="{$DIVCLASSOTHER}dvtCellInfoOff detailCellInfo ">
			<img src="modules/SDK/src/86/img/walistico.png" align="left" alt="Whatsapp" title="Whatsapp" style="width:15x; height:15px;margin-top:3px;margin-right:3px;"/>
				{if $keyval neq ''}
					<a target="_blank" href="https://wa.me/39{$keyval}">{$keyval}</a>
				{/if}
		</div>
	{/if}
{elseif $sdk_mode eq 'edit'}
	{if $readonly eq 99}
		{include file="FieldHeader.tpl" uitype=$uitype mandatory=$keymandatory label=$fldlabel massedit=$MASS_EDIT}
		<div class="dvtCellInfoOn">
			<img src="modules/SDK/src/86/img/walistico.png" align="left" alt="Whatsapp" title="Whatsapp" style="width:15px; height:15px;margin-top:5px;margin-right:3px;"/>
			<input type="text" name="{$fldname}" tabindex="{$vt_tab}" value="{$fldvalue}" tabindex="{$vt_tab}" class=detailedViewTextBox style="display:inline-block; width: 96%">	
		</div>
	{elseif $readonly eq 100}
		<input type="hidden" name="{$fldname}" tabindex="{$vt_tab}" value="{$fldvalue}" tabindex="{$vt_tab}" class=detailedViewTextBox >
	{else}
		{include file="FieldHeader.tpl" uitype=$uitype mandatory=$keymandatory label=$fldlabel massedit=$MASS_EDIT}	
			<div class="dvtCellInfoOn">
				<img src="modules/SDK/src/86/img/walistico.png" align="left" alt="Whatsapp" title="Whatsapp" style="width:15x; height:15px;margin-top:5px;margin-right:3px;"/>
				<input type="text" name="{$fldname}" tabindex="{$vt_tab}" value="{$fldvalue}" tabindex="{$vt_tab}" class=detailedViewTextBox style="display:inline-block; width: 97%">
			</div>
	{/if}
{/if}