{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
{* crmv@102879 crmv@106857 crmv@126096 crmv@136397 *}

{assign var=COLCOUNT value=$COLUMNS|@count}

<tr class="tablefield_row" valign="top">
	<input type="hidden" name="{$PARENTFIELD}_row_{$ROWNO}" value="1"/>
	<input type="hidden" name="{$PARENTFIELD}_rowid_{$ROWNO}" value="{$ROWID}"/>
	<input type="hidden" name="{$PARENTFIELD}_seq_{$ROWNO}" class="tablefield_row_seq" value="{$ROWNO}"/>
	{if $SHOW_ACTIONS}
		<td class="tablefield_cell" width="100">
			{if $CANDELETEROWS}
				<i class="vteicon md-link md-sm" onclick="TableField.deleteRow(this, '{$PARENTFIELD}', '{$ROWNO}')" title="{$APP.LBL_DELETE_ROW}">delete</i>
			{/if}
			<i class="vteicon md-link md-sm" onclick="TableField.duplicateRow(this, '{$PARENTFIELD}', '{$ROWNO}')" title="{$APP.LBL_DUPLICATE_BUTTON}">content_copy</i>
			<i class="vteicon md-link md-sm tablefield_row_icon_up" onclick="TableField.moveUpDownRow('UP', this, '{$PARENTFIELD}', '{$ROWNO}')" title="Move Upward" style="visibility:hidden">arrow_upward</i>
			<i class="vteicon md-link md-sm tablefield_row_icon_down" onclick="TableField.moveUpDownRow('DOWN', this, '{$PARENTFIELD}', '{$ROWNO}')" title="Move Downward" style="visibility:hidden">arrow_downward</i>
		</td>
	{/if}
	{if !$SINGLE_LINE}<td class="tablefield_cell" width="100%">{/if} {* crmv@184897 *}
	{assign var=subrowno value=-1}
	{foreach name=tablefield_row_columns item=COL from=$COLUMNS}
		{foreach key=property item=property_value from=$COL}
			{assign var=$property value=$property_value}
		{/foreach}
		{if $SINGLE_LINE}
			<td class="tablefield_cell">
		{else}
			{* crmv@184897 *}
			{if $smarty.foreach.tablefield_row_columns.index eq 0}
				{if $smarty.foreach.tablefield_row_columns.index gt 0}</table>{/if}
				<table width="100%"><tr valign="top">
				{assign var=subrowno value=$subrowno+1}
			{/if}
			{* crmv@184897e *}
			<td width="{$WIDTHPERLINE[$subrowno]}%">
		{/if}
		{if $MODE eq 'detail' && $keyreadonly eq 100}
			{* hidden *}
		{else}
			{if $MODE eq 'detail'}
				{assign var="TPLCLASS" value="dvtCellInfo"}
				{assign var=TPLNAME value='DetailViewUI.tpl'}
			{else}
				{if $readonly eq 100}
					{assign var=TPLNAME value='DisplayFieldsHidden.tpl'}
				{elseif $readonly eq 99}
					{assign var="TPLCLASS" value="dvtCellInfoOff"}
					{assign var=TPLNAME value='DisplayFieldsReadonly.tpl'}
				{else}
					{if $mandatory && $MODE neq 'detail'}
						{assign var="TPLCLASS" value="dvtCellInfoM"}
					{else}
						{assign var="TPLCLASS" value="dvtCellInfo"}
					{/if}
					{assign var=TPLNAME value='EditViewUI.tpl'}
				{/if}
			{/if}
			{include file=$TPLNAME DIVCLASS=$TPLCLASS NOLABEL=$SINGLE_LINE}
		{/if}
		</td>
		{* crmv@184897 *}
		{if $newline eq 1}
			{if $smarty.foreach.tablefield_row_columns.index gt 0}</table>{/if}
			<table width="100%"><tr valign="top">
			{assign var=subrowno value=$subrowno+1}
		{/if}
		{* crmv@184897e *}
		{if !$SINGLE_LINE && $smarty.foreach.tablefield_row_columns.last eq true}</table>{/if}
	{/foreach}
	{if !$SINGLE_LINE}</td>{/if}
</tr>