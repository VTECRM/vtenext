{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
 
{* crmv@104853 crmv@105588 *}
 
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
{/literal}

{literal}
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

<form action="index.php" method="post" name="coloration_form">
	<input type="hidden" name="clv_module" value="{$MODULE}">
	<input type="hidden" name="module" value="Settings">
	<input type="hidden" name="parenttab" value="Settings">
	<input type="hidden" name="fieldname" value="{$STATUS_FIELD}">
	<input type="hidden" name="remove_all" value="false">
	<input type="hidden" name="mode">
	<input type="hidden" name="action" value="SaveColoredListView">
	
	<p align="right">
		<input title="{$APP.LBL_SAVE_LABEL}" class="crmbutton small save" type="submit" name="save" value="{$APP.LBL_SAVE_LABEL}" style="min-width:70px">
	</p>

	{foreach item=entries key=id from=$STATUS_FIELD_ARRAY}
	
		{if $STATUS_FIELD eq $entries.fieldname}
			{assign var = "selected_val" value="display:block;"}
		{else}
			{assign var = "selected_val" value="display:none;"}
		{/if}
		
		<div id="status_field_{$entries.fieldname}" name="status_field_{$entries.fieldname}" style="{$selected_val}" >
			<table class="table" width="100%">
				<thead>
					<tr>
						<th colspan="3"> 
							{$entries.fieldlabel|@getTranslatedString:$MODULE}
						</th>
					</tr>
				</thead>
				{foreach item=value key=id from=$entries.values}
					{assign var="componentId" value=$entries.fieldname|cat:"_"|cat:$value.id}
					{assign var="componentId1" value=$entries.fieldname|cat:$value.id}
					<tr>
						<td width="50%">
							{$value.value_display|@getTranslatedString:$MODULE}
						</td>
						<td><div id="comp_{$componentId}"></div></td>
						<td width="50%">
							<input type="hidden" id="value_{$componentId1}" name="value_{$componentId1}" value="{$value.value}">
							<div class="dvtCellInfo">
								{if $STATUS_FIELD eq $entries.fieldname}
									<input type="text" readonly id="{$componentId}" value="{$value.color}" name="{$componentId}" class="detailedViewTextBox">
								{else}
									<input type="text" readonly id="{$componentId}" name="{$componentId}" class="detailedViewTextBox">
								{/if}
							</div>
						</td>
						{literal}
							<script type="text/javascript">
								(function() {
							    	var inputId = "{/literal}{$componentId}{literal}";
							    	var color = "{/literal}{$value.color}{literal}";
							    	jQuery('#comp_' + inputId).colorPicker({
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
					</tr>
				{/foreach}
			</table>
		</div>
	{/foreach}
</form>