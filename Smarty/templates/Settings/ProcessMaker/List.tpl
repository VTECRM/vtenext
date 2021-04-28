{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
{* crmv@92272 crmv@121416 crmv@185705 *}
<script src="{"modules/Settings/ProcessMaker/resources/ProcessMakerScript.js"|resourcever}" type="text/javascript"></script>
<script src="{"modules/Settings/KlondikeAI/resources/ProcessDiscoveryScript.js"|resourcever}" type="text/javascript"></script> {* crmv@190834 *}

<link rel="stylesheet" type="text/css" href="include/js/dataTables/css/dataTables.bootstrap.min.css"/>
<link rel="stylesheet" type="text/css" href="include/js/dataTables/plugins/FixedHeader/css/fixedHeader.bootstrap.min.css"/>
<link rel="stylesheet" type="text/css" href="include/js/dataTables/plugins/Responsive/css/responsive.bootstrap.min.css"/>
<link rel="stylesheet" type="text/css" href="include/js/dataTables/plugins/Select/css/select.bootstrap.min.css"/>

<script type="text/javascript" src="include/js/dataTables/jquery.dataTables.min.js"></script>
<script type="text/javascript" src="include/js/dataTables/dataTables.bootstrap.min.js"></script>
<script type="text/javascript" src="include/js/dataTables/plugins/FixedHeader/js/dataTables.fixedHeader.min.js"></script>

{* crmv@190834 *}
{if $DISCOVERY_MODE}
<br>
{elseif $MODE eq ''} 
{* crmv@190834e *}
	<table border=0 cellspacing=0 cellpadding=3 width=100%>
		<tr>
			<td colspan="6" align="right">
				<form style="display: inline;" action="index.php?module=Settings&amp;action=ProcessMaker&amp;mode=create&amp;parenttab=Settings" method="POST">
					<input type="submit" class="crmbutton small create" value='{$APP.LBL_NEW}' title='{$APP.LBL_NEW}'>
				</form>
				{if $SHOW_LOGS_BUTTON}
					<input type="button" class="crmbutton small create" value='{$MOD.LBL_PM_LOGS}' title='{$MOD.LBL_PM_LOGS}' onclick="window.open('index.php?module=Settings&amp;action=SettingsAjax&amp;file=ProcessMaker&amp;mode=logs&amp;parenttab=Settings');">
				{/if}
			</td>
		</tr>
	</table>
{/if}
<table class="table table-hover dataTable" id="processMakerListTable">
	{* crmv@136524 *}
	<thead>
	{if isset($SUBPROCESS)}
	<tr>
		<th><input type="radio" name="subprocess" id="subprocess_0" value="0" checked/></th>
		<th colspan="5"><label for="subprocess_0">{'LBL_PM_NO_SUBPROCESS'|getTranslatedString:'Settings'}</label></th>
	</tr>
	{/if}
	{* crmv@136524e *}
	<tr>
	{foreach item=column from=$HEADER name=header}
		{if $smarty.foreach.header.index gt 0}
			<th>{$column}
			<br><div class="dvtCellInfo"><input class="detailedViewTextBox" type="text" placeholder="{'LBL_SEARCH_FOR'|getTranslatedString} {$column}" /></div>
			</th>
		{else}
			<th>{$column}</th>
		{/if}
	{/foreach}
	</tr>
	</thead>
	{foreach item=entity from=$LIST}
		<tr>
			{foreach item=column from=$entity name=list_columns} {* crmv@190834 *}
				<td class="listTableRow small" {if $smarty.foreach.list_columns.index eq 0}nowrap{/if}>{$column}</td> {* crmv@190834 *}
			{/foreach}
		</tr>
	{/foreach}
</table>
{* crmv@147720 *}
<div style="display:none">
	<form enctype="multipart/form-data" name="Import" method="POST" action="index.php">
		<input type="hidden" name="module" value="Settings">
		<input type="hidden" name="action" value="SettingsAjax">
		<input type="hidden" name="file" value="ProcessMaker">
		<input type="hidden" name="mode" value="import">
		<input type="hidden" name="id" id="import_processmakerid" value="">
		<input type="file" name="bpmnfile" size="65" class=small onchange="VteJS_DialogBox.block(); this.form.submit();" />&nbsp;
		<input type="hidden" name="bpmnfile_hidden" value=""/>
	</form>
</div>
{* crmv@147720e *}
{* crmv@163905 *}
<script type="text/javascript">
{if $smarty.request.show_confirm_different_system_versions eq 'yes'}
	{literal}
	setTimeout(function(){
		vteconfirm('Trovate differenze nelle versioni dei moduli. Vuoi importare comunque la nuova versione di processo?', function(yes) {
			if (yes) {
				ProcessMakerScript.checkIncrementVersion({/literal}{$smarty.request.id}{literal}, {/literal}{$smarty.request.current_version}{literal}, function(force_version){
					location.href = 'index.php?module=Settings&action=SettingsAjax&file=ProcessMaker&mode=import&id={/literal}{$smarty.request.id}{literal}&cachefile=yes&force_version='+force_version;
				});
			}
		}, {btn_cancel_label:alert_arr.LBL_CANCEL, btn_ok_label:'OK'});
	},200);
	{/literal}
{elseif $smarty.request.check_increment_version eq 'yes'}
	{literal}
	setTimeout(function(){
		ProcessMakerScript.checkIncrementVersion({/literal}{$smarty.request.id}{literal}, {/literal}{$smarty.request.current_version}{literal}, function(force_version){
			location.href = 'index.php?module=Settings&action=SettingsAjax&file=ProcessMaker&mode=import&id={/literal}{$smarty.request.id}{literal}&cachefile=yes&force_version='+force_version;
		});
	},200);
	{/literal}
{/if}
</script>
{* crmv@163905e *}
<script type="text/javascript">
{literal}
jQuery(document).ready(function(){
	
	var processMakerListTable = jQuery('#processMakerListTable').DataTable({
		// crmv@190834
		"pageLength": {/literal}{$LIST_TABLE_PROP.0}{literal},
		"order": [[ {/literal}{$LIST_TABLE_PROP.1}{literal}, "{/literal}{$LIST_TABLE_PROP.2}{literal}" ]],
		// crmv@190834e
		
		// searching
		searching: true,
		search: {
			caseInsensitive: true,
			smart: false,	// disabled for the moment
		},
		
		// internationalization
		language: {
			url: "include/js/dataTables/i18n/{/literal}{$CURRENT_LANGUAGE}{literal}.lang.json"
		},
		
		columns: [
	    	{"orderable":false},null,null,null,null,null
	  	],
	});
	
	// wait for the table to be initialized
	processMakerListTable.columns().every(function (idx) {
		var that = this,
			header = this.header();
		
		// prevent propagation
		jQuery('input', header).on('click focus', function (event) {
			return false;
		});

		// use keypress, since the th has a listener on it and fires the redraw
		jQuery('input', header).on('keypress', function (event) {
			if (event.type == 'keypress' && event.keyCode == 13 && that.search() !== this.value) {
				that.search(this.value).draw();
				return false;
			}
		});
		
	});
});
{/literal}
</script>