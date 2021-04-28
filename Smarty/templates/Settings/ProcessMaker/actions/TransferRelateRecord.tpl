{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
{* crmv@185548 *}

<td align=right width=15% nowrap="nowrap">
	{if $ENTITY eq 1}
		{include file="FieldHeader.tpl" mandatory=true label='LBL_PM_ACTION_TransferRelations'|getTranslatedString:'Settings'}
	{elseif $ENTITY eq 2}
		{include file="FieldHeader.tpl" mandatory=true label='LBL_TO_SMALL'|getTranslatedString:'Settings'}
	{/if}
</td>
<td align="left">
	<div class="dvtCellInfo">
		<select name="record{$ENTITY}" id="linkRecordSelect{$ENTITY}" class="detailedViewTextBox" 
				{if $ENTITY eq 1} onChange="ActionTaskScript.loadEntityRelations(this.value,'{$MODE}')"{/if}
				{if $ENTITY eq 2} {if $SHOW eq 'FALSE'} style="display:none" {/if} onChange="ActionTaskScript.reloadModuleList(this.value,'{$MODE}')"{/if}
		>
			{foreach key=k item=i from=$RECORDS_INVOLVED}
				{if isset($i.group)}
					<optgroup label="{$i.group}">
						{foreach key=kk item=ii from=$i.values}
							<option value="{$kk}" {$ii.1}>{$ii.0}</option>
						{/foreach}
					</optgroup>
				{else}
					<option value="{$k}" {$i.1}>{$i.0}</option>
				{/if}
			{/foreach}
		</select>
	</div>
</td>
<td align=right width=15% nowrap="nowrap">&nbsp;</td>