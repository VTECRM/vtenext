{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}
{* crmv@150024 *}

<div id="dynamicTargetsPanel" style="display:none">
<br>

{if count($CVLIST) > 0}
	<table border="0" cellspacing="0" cellpadding="0" width="100%" class="small detailBlockHeader">
		<tr>
			<td class="dvInnerHeader"><b>{$MOD.LBL_APPLIED_FILTERS}</b></td>
		</tr>
	</table>
	<table width="100%" border="0" class="listTable">
		<thead>
		<tr>
			<td class="colHeader small" width="100">{$APP.LBL_ACTIONS}</td>
			<td class="colHeader small" width="70%">{$APP.LBL_LIST_NAME}</td>
			<td class="colHeader small">{$APP.LBL_MODULE}</td>
		</tr>
		</thead>
		<tbody>
		{foreach item="CV" from=$CVLIST}
			<tr>
				<td class="listTableRow small"><i class="vteicon md-link md-sm" title="{$APP.LBL_DELETE_BUTTON}" onclick="deleteDynamicFilter('{$ID}', 'CustomView', '{$CV.objectid}', '{$CV.formodule}')">delete</i></td>
				<td class="listTableRow small"><a href="index.php?module={$CV.formodule}&amp;action=ListView&amp;viewname={$CV.objectid}">{$CV.objectname}</a></td>
				<td class="listTableRow small">{$CV.formodule|getTranslatedString:$CV.formodule}</td>
			</tr>
		{/foreach}
		</tbody>
	</table>
	<br>
	<br>
{/if}

{if count($REPLIST) > 0}
	<table border="0" cellspacing="0" cellpadding="0" width="100%" class="small detailBlockHeader">
		<tr>
			<td class="dvInnerHeader"><b>{$MOD.LBL_APPLIED_REPORTS}</b></td>
		</tr>
	</table>
	<table width="100%" border="0" class="listTable">
		<thead>
		<tr>
			<td class="colHeader small" width="100">{$APP.LBL_ACTIONS}</td>
			<td class="colHeader small" width="70%">{$APP.LBL_LIST_NAME}</td>
			<td class="colHeader small">{$APP.LBL_MODULE}</td>
		</tr>
		</thead>
		<tbody>
		{foreach item="REP" from=$REPLIST}
			<tr>
				<td class="listTableRow small"><i class="vteicon md-link md-sm" title="{$APP.LBL_DELETE_BUTTON}" onclick="deleteDynamicFilter('{$ID}', 'Report', '{$REP.objectid}', '{$REP.formodule}')">delete</i></td>
				<td class="listTableRow small"><a href="index.php?module=Reports&amp;action=SaveAndRun&amp;record={$REP.objectid}">{$REP.objectname}</a></td>
				<td class="listTableRow small">{$REP.formodule|getTranslatedString:$REP.formodule}</td>
			</tr>
		{/foreach}
		</tbody>
	</table>
	<br>
	<br>
{/if}

{if count($CVLIST) + count($REPLIST) == 0}
	<p>{$MOD.LBL_NO_DYNAMIC_FILTERS}</p>
{else}
	<p>{$MOD.LBL_DYNAMIC_FILTERS_AS_ADMIN}</p>
{/if}

<br><br>
</div>