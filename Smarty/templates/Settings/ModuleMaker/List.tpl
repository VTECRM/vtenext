{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}
{* crmv@64542 *}

<br>
<div style="width:100%; text-align:right">
	<span id="mmaker_busy" style="display:none;">{include file="LoadingIndicator.tpl"}</span>
	<input type="button" class="small crmbutton create" value="{$APP.LBL_ADD_BUTTON}" title="{$APP.LBL_ADD_BUTTON}" onclick="ModuleMaker.createNew()" />
	{if $CAN_IMPORT}
	<input type="button" class="small crmbutton create" value="{$APP.LBL_IMPORT}" title="{$APP.LBL_IMPORT}" onclick="ModuleMaker.importNew()" />
	{/if}
</div>
<br>

{if count($MODLIST) > 0}
	<table width="100%" cellspacing="0" cellpadding="5" border="0" class="listTable">
		<tr>
			<td class="colHeader small">{$APP.LBL_MODULE}</td>
			<td class="colHeader small">{$MOD.LBL_MODULELABEL}</td>
			<td class="colHeader small">{$MOD.LBL_RECORD_IDENTIFIER}</td>
			<td class="colHeader small">{$APP.LBL_INSTALLED}</td>
			<td class="colHeader small" width="140">{$APP.LBL_TOOLS}</td>
		</tr>
		
		{foreach item=row from=$MODLIST}
			<tr>
				<td class="listTableRow small">{$row.modulename}</td>
				<td class="listTableRow small">{$row.moduleinfo.mmaker_modlabel}</td>
				<td class="listTableRow small">{$row.moduleinfo.mmaker_mainfield}</td>
				<td class="listTableRow small">{if $row.installed}{$APP.LBL_YES}{else}{$APP.LBL_NO}{/if}</td>
				<td class="listTableRow small">
					{if $row.installed}
						<i class="vteicon md-link"  onclick="ModuleMaker.uninstallModule('{$row.id}')" title="{'LBL_UNINSTALL'|getTranslatedString}" >eject</i>&nbsp;
					{else}
						<i class="vteicon md-link" onclick="ModuleMaker.installModule('{$row.id}')" title="{'LBL_INSTALL'|getTranslatedString}" >play_arrow</i>&nbsp;
						<i class="vteicon md-link" onclick="ModuleMaker.editModule('{$row.id}')" title="{'LBL_EDIT'|getTranslatedString}" >create</i>&nbsp;
					{/if}
					{if $row.showlogs}
						<i class="vteicon md-link" onclick="ModuleMaker.openLogsPopup('{$row.id}')" title="{'LBL_INFORMATION'|getTranslatedString}" >help</i>
					{/if}
					{if $CAN_EXPORT}
						<i class="vteicon md-link" onclick="ModuleMaker.exportModule('{$row.id}')" title="{'LBL_EXPORT'|getTranslatedString}" >file_upload</i>&nbsp;
					{/if}
					{if !$row.installed}
						<i class="vteicon md-link" onclick="ModuleMaker.deleteModule('{$row.id}')" title="{'LBL_DELETE'|getTranslatedString}" >delete</i>
					{/if}
				</td>
			</tr>
		{/foreach}
	</table>
{else}
	<p>{$MOD.LBL_NO_CUSTOM_MODULES}</p>
{/if}

{* move fields in block *}
<div id="mmaker_div_logs" class="crmvDiv floatingDiv" style="width:600px;height:400px">
	<input type="hidden" id="mmaker_logs_moduleid"  value="" />

	<table border="0" cellpadding="5" cellspacing="0" width="100%">
		<tr height="34">
			<td class="level3Bg floatingHandle">
				<table cellpadding="0" cellspacing="0" width="100%">
				<tr>
					<td width="50%"><b>{$APP.LBL_INSTALLATION_LOGS}</b></td>
					<td width="50%" align="right">&nbsp;

					</td>
				</tr>
				</table>
			</td>
		</tr>
	</table>
	<div class="crmvDivContent">
		<p>&nbsp;&nbsp;{$MOD.LBL_SELECT_INSTALLATION_LOG}</p>
		<div style="width:100%;padding:10px;">
			<div style="margin-bottom:10px">
				<select id="mmaker_select_log" onchange="ModuleMaker.selectLog()">
					<option value=""></option>
					<option value="install_log">{$APP.LBL_INSTALLATION}</option>
					<option value="uninstall_log">{$APP.LBL_UNINSTALLATION}</option>
				</select>
			</div>
			<div>
				<textarea id="mmaker_log_text" rows="20" style="height:250px"></textarea>
			</div>
		</div>
	</div>
	<div class="closebutton" onclick="ModuleMakerFields.hideFloatingDiv('mmaker_div_logs')"></div>
</div>

{* loading message div *}
<div id="mmaker_div_message" class="crmvDiv floatingDiv" style="width:500px;height:100px">
	<input type="hidden" id="mmaker_logs_moduleid"  value="" />

	<table border="0" cellpadding="5" cellspacing="0" width="100%">
		<tr height="34">
			<td class="level3Bg floatingHandle">
				<table cellpadding="0" cellspacing="0" width="100%">
				<tr>
					<td width="50%"><b></b></td>
					<td width="50%" align="right">&nbsp;

					</td>
				</tr>
				</table>
			</td>
		</tr>
	</table>
	<div class="crmvDivContent" style="padding:10px">
		<div class="mmaker_message_text" id="mmaker_message_installing" style="display:none">{$MOD.LBL_MMAKER_INSTALLING_MODULE}</div>
		<div class="mmaker_message_text" id="mmaker_message_uninstalling" style="display:none">{$MOD.LBL_MMAKER_UNINSTALLING_MODULE}</div>
	</div>
	<div class="closebutton" onclick="ModuleMakerFields.hideFloatingDiv('mmaker_div_message')"></div>
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