{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}
{* crmv@128369 *}

{* assign var="BROWSER_TITLE" value=$MOD.TITLE_VTECRM_CREATE_REPORT *}
{include file="HTMLHeader.tpl" head_include="jquery,jquery_plugins,jquery_ui,prototype"}
	
<body class="small popup-edit-cluster">

{include file="Theme.tpl" THEME_MODE="body"}

{include file='CachedValues.tpl'}	{* crmv@26316 *}

<script type="text/javascript" src="{"modules/Reports/Reports.js"|resourcever}"></script> {* crmv@135016 *}
<script type="text/javascript" src="{"modules/Reports/EditReport.js"|resourcever}"></script>

{* crmv@133997 *}
<link rel="stylesheet" type="text/css" href="include/js/jquery_plugins/colorPicker/css/colorPicker.css" />
<script type="text/javascript" src="include/js/jquery_plugins/colorPicker/js/jquery.colorPicker.min.js"></script>

{literal}
<style>
	div.colorPicker-picker {
		height: 32px;
		width: 32px;
		background: none;
	}
	div.colorPicker-palette {
		width: 258px;
		position: absolute;
		border: 1px solid #CCCCCC;
		background-color: #FFFFFF;
		z-index: 9999;
		padding: 0px;
	}
	div.colorPicker-swatch {
		height: 30px;
		width: 30px;
		border: 0px none;
		float: left;
		cursor: pointer;
		line-height: 12px;
		margin: 1px;
	}
	div.colorPicker-swatch.transparent {
		text-align: center;
		line-height: 30px;
	}
</style>

<script type="text/javascript">
	var defaultColors = ["transparent",
		"#EF5350", "#F44336", "#E53935", // red 300, 400, 500
		"#7E57C2", "#673AB7", "#5E35B1", // deep_purple 300, 400, 500
		"#29B6F6", "#03A9F4", "#039BE5", // light_blue 300, 400, 500
		"#66BB6A", "#4CAF50", "#43A047", // green 300, 400, 500
		"#FFEE58", "#FFEB3B", "#FDD835", // yellow 300, 400, 500
		"#FF7043", "#FF5722", "#F4511E", // deep_orange 300, 400, 500
		"#78909C", "#607D8B", "#546E7A", // blue_grey 300, 400, 500
		"#EC407A", "#E91E63", "#D81B60", // pink 300, 400, 500
		"#5C6BC0", "#3F51B5", "#3949AB", // indigo 300, 400, 500
		"#26C6DA", "#00BCD4", "#00ACC1", // cyan 300, 400, 500
		"#9CCC65", "#8BC34A", "#7CB342", // light_green 300, 400, 500
		"#FFCA28", "#FFC107", "#FFB300", // amber 300, 400, 500
		"#8D6E63", "#795548", "#6D4C41", // brown 300, 400, 500
		"#AB47BC", "#9C27B0", "#8E24AA", // purple 300, 400, 500
		"#42A5F5", "#2196F3", "#1E88E5", // blue 300, 400, 500
		"#26A69A", "#009688", "#00897B", // teal 300, 400, 500
		"#D4E157", "#CDDC39", "#C0CA33", // lime 300, 400, 500
		"#FFA726", "#FF9800", "#FB8C00", // orange 300, 400, 500
		"#BDBDBD", // grey 300
	];
	
	jQuery.fn.colorPicker.previewColor = function() {}; // Not need to see the preview
</script>
{/literal}
{* crmv@133997e *}

{* popup status *}
<div id="editreport_busy" name="editreport_busy" style="display:none;position:fixed;right:200px;top:10px;z-index:100">
	{include file="LoadingIndicator.tpl"}
</div>

{* header *}
<table id="reportHeaderTab" class="mailClientWriteEmailHeader level2Bg menuSeparation" width="100%" border="0" cellspacing="0" cellpadding="5" > {* crmv@21048m *}
	<tr>
		<td class="moduleName" width="80%">{if $CLUSTERIDX neq ''}{$MOD.LBL_EDIT_CLUSTER}{else}{$MOD.LBL_ADD_CLUSTER}{/if}</td>
		<td width=30% nowrap class="componentName" align="right">{$MOD.LBL_CUSTOM_REPORTS}</td>
	</tr>
</table>

{* buttons *}
<div id="Buttons_List" class="level3Bg">
<table class="tableHeading" border="0" width="100%" cellspacing="0" cellspacing="5">
	<tr>
		<td class="small" align="right">
			<button class="small crmbutton save" onclick="EditReport.saveCluster('{$REPORTID}', '{$CLUSTERIDX}')">{$APP.LBL_SAVE_LABEL}</button>
			<button class="small crmbutton cancel" onclick="closePopup()">{$APP.LBL_CANCEL_BUTTON_LABEL}</button>
		</td>
	</tr>
</table>
</div>

<br><br>

<form id="NewCluster" name="NewCluster" onsubmit="return false">
			
<input type="hidden" name="reportid" id="reportid" value="{$REPORTID}" />
<input type="hidden" name="clusteridx" id="clusteridx" value="{$CLUSTERIDX}" />

<input type="hidden" id="primarymodule" value="{$PRIMARYMODULE}" />
<input type="hidden" id="primarymodule_display" value="{$PRIMARYMODULE_LABEL}" />

{* fields *}
<table border="0" width="100%">
	<tr>
		<td class="dimport_step_field_cell" align="right" width="35%"><span>{$MOD.LBL_CLUSTER_NAME}</span>&nbsp;&nbsp;</td>
		<td align="left" width="350">
			<div class="dvtCellInfoM">
				<input type="text" class="detailedViewTextBox" name="clustername" id="clustername" value="{$CLUSTERNAME|replace:'"':"&quot;"}"/> {* crmv@117392 *}
			</div>
		</td>
		<td align="left">
		</td>
	</tr>
	{* crmv@133997 *}
	<tr>
		<td class="dimport_step_field_cell" align="right" width="35%"><span>{$MOD.LBL_CLUSTER_COLOR}</span>&nbsp;&nbsp;</td>
		<td align="left" width="350">
			<div class="dvtCellInfo">
				<input type="text" class="" name="clustercolor" id="clustercolor" value="{$CLUSTERNAME|replace:'"':"&quot;"}"/>
			</div>
		</td>
		<td align="left">
		</td>
	</tr>
	{* crmv@133997e *}
</table>

{* crmv@133997 *}
{literal}
<script type="text/javascript">
(function() {
	var inputId = "clustercolor";
	var color = "{/literal}{$CLUSTERCOLOR}{literal}";
	jQuery('#' + inputId).colorPicker({
		colors: defaultColors,
		onColorChange: function(id, newValue) {
			var inputText = jQuery('#' + inputId);
			var value = newValue;
			if (newValue == "transparent") {
				value = "";
			}
			inputText.val(value);
		},
		showHexField: false,
		pickerDefault: color,
	});
})();
</script>
{/literal}
{* crmv@133997e *}

{* filters *}
<div id="clusterfilters" style="padding:30px">
{if $CLUSTERIDX === ''}
{include file="modules/Reports/EditStepAdvFilters.tpl" FILTERDESC=$MOD.LBL_CLUSTER_FILTER_DESC}
{/if}
</div>

</form>

<script type="text/javascript">
	
	{if $PRELOAD_JS}
	(function() {ldelim}
		var preload_js = {$PRELOAD_JS};
		EditReport.preloadCache(preload_js);
	{rdelim})();
	{/if}
	
	{if $CLUSTERIDX !== ''}
		EditReport.loadEditCluster('{$REPORTID}', {$CLUSTERIDX});
	{else}
		EditReport.initializeStep(4);
	{/if}
	
</script>