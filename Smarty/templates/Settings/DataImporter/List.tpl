{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}
{* crmv@65455 *}

<br>
<div style="width:100%; text-align:right">
	<span id="dimporter_busy" style="display:none;">{include file="LoadingIndicator.tpl"}</span>
	<input type="button" class="small crmbutton create" value="{$APP.LBL_ADD_BUTTON}" title="{$APP.LBL_ADD_BUTTON}" onclick="DataImporter.createNew()" />
</div>
<br>

{if count($IMPORTLIST) > 0}
	<table width="100%" cellspacing="0" cellpadding="5" border="0" class="listTable">
		<tr>
			<td class="colHeader small">{$APP.LBL_MODULE}</td>
			<td class="colHeader small">{$APP.LBL_SOURCE}</td>
			<td class="colHeader small">{$MOD.LBL_LAST_IMPORT}</td>
			<td class="colHeader small">{$MOD.LBL_NEXT_IMPORT_TIME}</td>
			<td class="colHeader small">{'In Progress'|getTranslatedString}</td>
			<td class="colHeader small">{$MOD.LBL_ENABLED}</td>
			
			<td class="colHeader small" width="120">{$APP.LBL_TOOLS}</td>
		</tr>
		
		{foreach item=row from=$IMPORTLIST}
			<tr>
				<td class="listTableRow small">
					{if $row.module eq 'ProductRows' && $row.invmodule}
						{$row.invmodule|getTranslatedString:$row.invmodule}: {$MOD.LBL_RELATED_PRODUCTS}
					{else}
						{$row.module|getTranslatedString:$row.module}
					{/if}
				</td>
				<td class="listTableRow small">{$row.srcinfo.dimport_sourcetype}</td>
				<td class="listTableRow small">
					{if $row.lastimport neq '' && $row.lastimport neq '0000-00-00' && $row.lastimport neq '0000-00-00 00:00:00'}
						{$row.lastimport}
						{if $row.errors}
							{* errors *}
							<div class="dimport_error_badge" onclick="DataImporter.openLogsPopup('{$row.id}')" title="Import Error">!</div>
						{/if}
					{else}
						-
					{/if}
				</td>
				<td class="listTableRow small">
					{if $row.enabled}
						{if $row.override_runnow eq '1'}
							{$APP.LBL_NOW}
						{else}
							{$row.next_start}
						{/if}
					{else}
					-
					{/if}
				</td>
				<td class="listTableRow small">{if $row.running}{$APP.LBL_YES}{else}{$APP.LBL_NO}{/if}</td>
				<td class="listTableRow small">
					{if $row.running}
						{if $row.enabled}
						<i class="vteicon checkok" title="{$MOD.LBL_DISABLE}">check</i>
						{else}
						<i class="vteicon checkko" title="{$MOD.LBL_ENABLE}">clear</i>
						{/if}
					{else}
						{if $row.enabled}
						<a href="javascript:void(0);" onclick="DataImporter.disableImport('{$row.id}')"><i class="vteicon checkok" title="{$MOD.LBL_DISABLE}">check</i></a>
						{else}
						<a href="javascript:void(0);" onclick="DataImporter.enableImport('{$row.id}')"><i class="vteicon checkko" title="{$MOD.LBL_ENABLE}">clear</i></a>
						{/if}
					{/if}
				</td>
				<td class="listTableRow small">
					{if !$row.running}
						<i class="vteicon md-link" onclick="DataImporter.editImport('{$row.id}')" title="{'LBL_EDIT'|getTranslatedString}" >create</i>&nbsp;
						{if $CAN_RUN_MANUALLY && !$row.override_runnow && !$row.running}
							<i class="vteicon md-link" onclick="DataImporter.runNow('{$row.id}')" title="{$APP.LBL_START_NOW}" >play_arrow</i>&nbsp;
						{/if}
					{/if}
					{if $row.override_runnow || $row.running}
						<i class="vteicon md-link" onclick="DataImporter.abortImport('{$row.id}')" title="{$MOD.LBL_ABORT_IMPORT}" >eject</i>&nbsp;
					{/if}
					<i class="vteicon md-link" onclick="DataImporter.openLogsPopup('{$row.id}')" title="{'LBL_INFORMATION'|getTranslatedString}" >info_outline</i>
					{if !$row.running}
						<i class="vteicon md-link"  onclick="DataImporter.deleteImport('{$row.id}')" title="{'LBL_DELETE'|getTranslatedString}" >delete</i>
					{/if}
				</td>
			</tr>
		{/foreach}
	</table>
{else}
	<p>{$MOD.LBL_NO_DATA_IMPORTER}</p>
{/if}
<br>

{* logs window *}
<div id="dimport_div_logs" class="crmvDiv floatingDiv" style="width:680px;height:400px">
	<input type="hidden" id="dimport_logs_importid"  value="" />

	<table border="0" cellpadding="5" cellspacing="0" width="100%">
		<tr height="34">
			<td class="level3Bg floatingHandle">
				<table cellpadding="0" cellspacing="0" width="100%">
				<tr>
					<td width="50%"><b>{$MOD.LBL_LAST_IMPORT_LOG}</b></td>
					<td width="50%" align="right">&nbsp;</td>
				</tr>
				</table>
			</td>
		</tr>
	</table>
	<div class="crmvDivContent">
		<div style="width:100%;padding:10px;">
			<textarea id="dimport_log_text" class="detailedViewTextBox" style="height:300px">{$APP.LBL_LOADING}</textarea>   {* crmv@189795 *}
		</div>
	</div>
	<div class="closebutton" onclick="DataImporter.hideFloatingDiv('dimport_div_logs')"></div>
</div>

{* enable dragging for every floating div *}
<script type="text/javascript">
{literal}
(function() {
	// crmv@192014
	var floats = jQuery('div.floatingDiv');
	floats.each(function(index, f) {
		if (f) {
			var handle = jQuery(f).find('.floatingHandle').get(0);
			if (handle) {
				jQuery(f).draggable({
					handle: handle
				});
			}
		}
	});
	// crmv@192014e
})();
{/literal}
</script>