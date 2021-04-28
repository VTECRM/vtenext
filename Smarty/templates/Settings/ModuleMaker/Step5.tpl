{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}
{* crmv@64542 *}

{* scripts and styles for the jqgrid component *}

{* crmv@193430 - jquery is already included *}
<link rel="stylesheet" type="text/css" media="screen" href="include/js/jquery_plugins/jqgrid/css/ui.jqgrid.css" />
<script language="JavaScript" type="text/javascript" src="include/js/jquery_plugins/jqgrid/i18n/grid.locale-{$SHORT_LANGUAGE}.js"></script>
<script language="JavaScript" type="text/javascript" src="include/js/jquery_plugins/jqgrid/jqgrid.js"></script>

<div>
	<p>{$MOD.LBL_MMAKER_STEP5_INTRO}</p>
</div>

{* filters *}
<table id="trans_search" name="trans_search" border="0" cellpadding="5" cellspacing="0" width="100%">
	<tr>
		<td align="center">{$MOD.LBL_TRANS_LANGUAGE}&nbsp;
			<select id="language_select" name="language_select" onchange="ModuleMakerLanguages.init('rebuild')">
				<option value="">{$MOD.LBL_TRANS_ALL}</option>
				{foreach key=langname item=lang from=$LANGUAGES}
					{if $lang.active}<option value="{$langname}">{$lang.label}</option>{/if}
				{/foreach}
			</select>	
		</td>
		<td align="center">{$MOD.LBL_TRANS_MODULE}&nbsp;
			<select id='module_select' name="module_select" onchange="ModuleMakerLanguages.init('reload');">
				<option value="">{$MOD.LBL_TRANS_ALL}</option>
				{foreach key=modname item=modlabel from=$LABELS_MODULES}
					<option value="{$modname}">{$modlabel}</option>
				{/foreach}
			</select>				
		</td>
		<td align="center">{$MOD.LBL_TRANS_FILTER_NAME}&nbsp;
			<select id="filter_select" name="filter_select" onchange="ModuleMakerLanguages.init('reload');">
				<option value="">{$MOD.LBL_TRANS_FILTER_ALL}</option>
				<option value="fields">{$MOD.LBL_TRANS_FILTER_FIELDS}</option>
				<option value="fieldvalues">{$MOD.LBL_TRANS_FILTER_FIELDVALUES}</option>
				<option value="other">{$MOD.LBL_TRANS_FILTER_OTHER}</option>
			</select>
		</td>
	</tr>
</table>

{* placeholders for the grid *}
<table id="trans_table" width="100%"><tr><td></td></tr></table> 
<div id="trans_table_nav"></div>

{* load the table *}
<script type="text/javascript">
{literal}
	jQuery(document).ready(function() {
		ModuleMakerLanguages.init();
	});
{/literal}
</script>