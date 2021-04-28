{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}
{* crmv@113775 crmv@126184 *}
<td align=right width=15% nowrap="nowrap">
	{if $ENTITY eq 1}
		{include file="FieldHeader.tpl" mandatory=true label=$APP.LBL_LINK_ACTION}
	{elseif $ENTITY eq 2}
		{include file="FieldHeader.tpl" mandatory=true label='LBL_TO_SMALL'|getTranslatedString:'Settings'}
	{/if}
</td>
<td align="left">
	<div class="dvtCellInfo">
		<select name="record{$ENTITY}" id="linkRecordSelect{$ENTITY}" class="detailedViewTextBox" 
			{if $STATICRECORD}
				{if $ENTITY eq 1}
					onChange="ActionTaskScript.loadRelationsNtoN(this.value)"
				{elseif $ENTITY eq 2}
					onChange="ActionTaskScript.loadStaticRelatedRecords(jQuery('#linkRecordSelect1').val(), this.value)"
				{/if}
			{else}
				{if $ENTITY eq 1}onChange="ActionTaskScript.loadPotentialRelations(this.value)"{/if}
			{/if}
		>
		{* crmv@191351 *}
		{foreach key=k item=i from=$RECORDPICK}
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
		{* crmv@191351e *}
		</select>
	</div>
</td>
<td align=right width=15% nowrap="nowrap">&nbsp;</td>