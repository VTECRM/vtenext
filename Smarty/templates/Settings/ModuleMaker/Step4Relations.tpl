{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}
{* crmv@64542 crmv@69398 *}

{if count($RELATIONS_N1) == 0 && count($STEPVARS.mmaker_relations) == 0}

<p>{$MOD.LBL_NO_RELATIONS_FOUND}</p>

{else}

<br>
<p>{$APP.LBL_RELATIONS}</p>
<table border="0" width="100%" class="listTable" cellspacing="0" cellpadding="5">
	<tr>
		<td class="colHeader small" width="120">{$APP.LBL_MODULE}</td>
		<td class="colHeader small" width="120">{$MOD.LBL_RELATION_TYPE}</td>
		<td class="colHeader small">{$APP.LBL_MODULES}</td>
		<td class="colHeader small">{$MOD.FieldName}</td>
		<td class="colHeader small" width="80" align="right">{$APP.LBL_ACTIONS}</td>
	</tr>
	{* relations N-1, with the field in this module *}
	{if count($RELATIONS_N1) > 0}
	{foreach item=rel from=$RELATIONS_N1}
		<tr>
			<td class="listTableRow small">{$NEWMODULENAME}</td>
			<td class="listTableRow small">{$MOD.LBL_RELATION_TYPE_NTO1}</td>
			<td class="listTableRow small">{", "|implode:$rel.mods}</td>
			<td class="listTableRow small">{$rel.label}</td>
			<td class="listTableRow small" align="right">
				<i class="vteicon md-link" onClick="ModuleMakerRelations.delRelationN1('{$rel.label}')" title="{$APP.LBL_DELETE_BUTTON}">delete</i>&nbsp;&nbsp;
			</td>
		</tr>
	{/foreach}
	{/if}
	
	{* other relations *}
	{if count($STEPVARS.mmaker_relations) > 0}
	{foreach key=relno item=rel from=$STEPVARS.mmaker_relations}
		<tr>
			{* fields *}
			<input type="hidden" name="relation_{$relno}_type" value="{$rel.type}" />
			<input type="hidden" name="relation_{$relno}_module" value="{$rel.module}" />
			<input type="hidden" name="relation_{$relno}_block" value="{$rel.block}" />
			<input type="hidden" name="relation_{$relno}_field" value="{$rel.field}" />
			
			{* table cells *}
			<td class="listTableRow small">
				{$NEWMODULENAME}
			</td>
			<td class="listTableRow small">
				{if $rel.type eq '1ton'}
					{$MOD.LBL_RELATION_TYPE_1TON}
				{elseif $rel.type eq 'nton'}
					{$MOD.LBL_RELATION_TYPE_NTON}
				{else}
					{$rel.type}
				{/if}
			</td>
			<td class="listTableRow small">{$rel.module|getTranslatedString:$rel.module}</td> {* crmv@183071 *}
			<td class="listTableRow small">{if $rel.fieldlabel neq ""}{$rel.fieldlabel}{else}{$rel.field}{/if}</td>
			<td class="listTableRow small" align="right">
				<i class="vteicon md-link" onClick="ModuleMakerRelations.delRelation('{$relno}')" title="{$APP.LBL_DELETE_BUTTON}">delete</i>&nbsp;&nbsp;
			</td>
			
		</tr>
	{/foreach}
	{/if}
</table>
{/if}
